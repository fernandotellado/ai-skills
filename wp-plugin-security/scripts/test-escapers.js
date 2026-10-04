#!/usr/bin/env node
// SPDX-License-Identifier: GPL-2.0-or-later
/**
 * test-escapers.js - contract test for the escaping helpers in a plugin's JavaScript,
 * crossed with the context in which each helper is used.
 *
 * Purpose. It finds the functions named esc*() or escape*() and decides whether each one
 * encodes quotes, by running it on a hostile payload (or, when it builds its result with
 * the DOM, by its shape). What decides the verdict is not how the function is written but
 * whether an escaper that does not encode quotes ends up inside a quoted HTML attribute,
 * which is the shape of CVE-2026-81754. It prints FAIL for such an escaper used in
 * attribute position, WARN for one used only in text position (correct today, fragile) or
 * for one that could not be evaluated and needs a manual review, and OK for an escaper
 * that encodes quotes and is safe in any position. Each escaper body is evaluated with
 * new Function(), so run it only on code you trust.
 *
 * Usage:       node test-escapers.js <directory-with-js>
 *
 * Exit codes:  0  no FAIL (WARN and OK do not change the exit code)
 *              1  at least one FAIL (an unreadable directory also ends in exit 1,
 *                 because Node reports the uncaught exception that way)
 *              2  usage error: no directory given
 *
 * What it does NOT see:
 *  - Anything that is not on the same line as the call. For each call it looks backwards
 *    along the current line to see whether a quoted attribute is open, so an attribute
 *    built across several lines is invisible to it.
 *  - Its zero is only meaningful if the plugin builds HTML in JavaScript at all. A folder
 *    with no esc*() or escape*() function reports 0 escapers and passes.
 *  - Helpers whose name does not start with esc or escape, and minified files (*.min.js).
 *  - Usages in other files: the usages of a helper are looked for only in the file that
 *    defines it.
 *  - Escapers that cannot run in isolation (they use variables from an outer scope) are
 *    reported as WARN "could not be evaluated". A body that delegates with
 *    "return this.name(" or "return self.name(" is resolved only when name is itself an
 *    esc*() or escape*() function of the same file; any other delegation is judged by
 *    where the helper is used, with the reason shown as "undefined".
 *
 * Validate it:  bash selftest.sh
 */
const fs = require('fs'), path = require('path');

const dir = process.argv[2];
if (!dir) { console.error('Usage: node test-escapers.js <directory-with-js>'); process.exit(2); }

const PAYLOAD = '\'" onerror="alert(1)" x=\'y\'<img>&';

function jsFiles(d) {
    let out = [];
    for (const e of fs.readdirSync(d, { withFileTypes: true })) {
        const p = path.join(d, e.name);
        if (e.isDirectory()) out = out.concat(jsFiles(p));
        else if (e.name.endsWith('.js') && !e.name.endsWith('.min.js')) out.push(p);
    }
    return out;
}

function bodyFrom(src, start) {
    let i = src.indexOf('{', start), depth = 0;
    for (let j = i; j < src.length; j++) {
        if (src[j] === '{') depth++;
        else if (src[j] === '}') { depth--; if (depth === 0) return src.slice(i + 1, j); }
    }
    return '';
}

function extract(src) {
    const out = [];
    const patterns = [
        /(?:function\s+)(esc[A-Za-z]*|escape[A-Za-z]*)\s*\(([^)]*)\)\s*\{/g,
        /(esc[A-Za-z]*|escape[A-Za-z]*)\s*:\s*function\s*\(([^)]*)\)\s*\{/g,
        /(?:var|let|const)\s+(esc[A-Za-z]*|escape[A-Za-z]*)\s*=\s*function\s*\(([^)]*)\)\s*\{/g,
    ];
    for (const re of patterns) {
        let m;
        while ((m = re.exec(src)) !== null) {
            out.push({ name: m[1], args: m[2], body: bodyFrom(src, m.index + m[0].length - 1) });
        }
    }
    return out;
}

/** Does it encode quotes? Checked by running it and, when that is not possible, by shape. */
function safeInAttribute(fn) {
    if (/textContent\s*=|createTextNode|\.text\s*\(/.test(fn.body) && /innerHTML|\.html\s*\(\s*\)/.test(fn.body)) {
        return { safe: false, reason: 'escapes through the DOM (textContent/innerHTML), which does not encode quotes' };
    }
    try {
        const f = new Function(fn.args.split(',')[0].trim() || 'text', fn.body);
        const s = String(f(PAYLOAD));
        const missing = ['"', "'", '<', '>'].filter(c => s.includes(c));
        return missing.length
            ? { safe: false, reason: 'leaves unencoded: ' + missing.join(' ') }
            : { safe: true, reason: '' };
    } catch (e) {
        // It delegates to another function of the same object: resolved by name further down.
        const delegation = fn.body.match(/return\s+(?:this|self)\.([A-Za-z_$][\w$]*)\s*\(/);
        if (delegation) return { delegate: delegation[1] };
        return { undetermined: true, reason: e.message };
    }
}

/** Context of each call: inside a quoted attribute or not. */
function usages(src, name) {
    const re = new RegExp('(?:^|[^\\w.])((?:this|self|[A-Za-z_$][\\w$]*)\\.)?' + name + '\\s*\\(', 'g');
    const out = [];
    let m;
    while ((m = re.exec(src)) !== null) {
        const line = src.slice(0, m.index).split('\n').length;
        // Look backwards, within the current line, for the last attribute delimiter that
        // is still open.
        const before = src.slice(Math.max(0, m.index - 400), m.index);
        const piece = before.slice(before.lastIndexOf('\n') + 1);
        // HTML is built by concatenation, so the text before the call usually ends in
        //  ..." +  or  ...' + . That concatenation tail is removed and what is left is
        // checked for an open attribute. Without this,  value="' + esc(x) + '"  would not
        // be detected, and that is exactly the shape of CVE-2026-81754.
        let tail = piece.replace(/\\/g, '');
        tail = tail.replace(/(?:['"]\s*\+\s*)+$/, '');
        // The '=' must come from an attribute name (letter, digit, hyphen or underscore),
        // otherwise  row += '<td>'  would count as an attribute because of the +=.
        const c = tail.trim();
        const inAttribute = /[A-Za-z0-9_-]=\s*['"]$/.test(c) || /[A-Za-z0-9_-]=\s*['"][^'"]*$/.test(c);
        out.push({ line, inAttribute, text: piece.trim().slice(-70) });
    }
    return out;
}

const files = jsFiles(dir);
let fails = 0, warns = 0, oks = 0;

for (const f of files) {
    const src = fs.readFileSync(f, 'utf8');
    const rel = path.relative(dir, f);
    const fns = extract(src);
    const byName = {};
    fns.forEach(fn => { byName[fn.name] = fn; });

    for (const fn of fns) {
        let v = safeInAttribute(fn);
        if (v.delegate && byName[v.delegate]) v = safeInAttribute(byName[v.delegate]);
        if (v.undetermined) { console.log(`WARN   ${rel}:${fn.name}() could not be evaluated (${v.reason}). Review by hand.`); warns++; continue; }
        if (v.safe) { console.log(`OK     ${rel}: ${fn.name}() encodes quotes, safe in any position.`); oks++; continue; }

        // It does not encode quotes: what decides the verdict is where it is used.
        // Only within its own file: two files of the same plugin can define escapeHtml()
        // with different implementations, one broken and one correct, and attributing the
        // usages of one to the other would be misleading.
        const inAttribute = usages(src, fn.name).filter(u => u.inAttribute)
            .map(u => `${rel}:${u.line}`);
        if (inAttribute.length) {
            console.log(`FAIL   ${rel}: ${fn.name}() ${v.reason},`);
            console.log(`       and is used in attribute position at: ${inAttribute.join(', ')}`);
            fails++;
        } else {
            console.log(`WARN   ${rel}: ${fn.name}() ${v.reason}, but today it is only used in text position.`);
            console.log(`       Correct now, broken the day someone puts it in an attribute. Rename it to escHtml().`);
            warns++;
        }
    }
}

console.log(`\n${fails + warns + oks} escapers: ${fails} FAIL, ${warns} WARN, ${oks} OK.`);
if (!fails) console.log('Gate passed: no escaper that leaves quotes unencoded reaches an attribute.');
process.exit(fails ? 1 : 0);
