# Release security gate

Read this before releasing a plugin version, when asked to audit a plugin, or when an email from the wordpress.org automated security review arrives. It is a procedure: do the steps, and write down the numbers they leave.

## Contents

- [What a gate is for, and what it cannot do](#what-a-gate-is-for-and-what-it-cannot-do)
- [Step 1: decide the depth before starting](#step-1-decide-the-depth-before-starting)
- [Step 2: mechanical checks, always](#step-2-mechanical-checks-always)
- [Step 3: written analysis](#step-3-written-analysis)
- [Step 4: cross-review](#step-4-cross-review)
- [Step 5: the release note, with numbers](#step-5-the-release-note-with-numbers)
- [Rules for trusting a gate](#rules-for-trusting-a-gate)
- [The permanent regression harness](#the-permanent-regression-harness)
- [The wordpress.org automated security review](#the-wordpressorg-automated-security-review)
- [Bundled scripts, and what each one does not see](#bundled-scripts-and-what-each-one-does-not-see)

## What a gate is for, and what it cannot do

The flaw behind CVE-2026-81754 shipped in 70 releases, and every one of them had passed a security review. The reviews followed the diff, the flaw lived in JavaScript that no PHP check read, and a suppression comment with a false justification covered the bypass next to it. A gate is the set of checks that would have caught those, run the same way every time, leaving figures that the next release can be compared against.

It has a limit worth saying up front: **gates catch regressions, not original design.** A flaw that was born with the feature never appears in a diff and no mechanical check has a reason to see it. Step 3 exists to compensate for that by hand.

## Step 1: decide the depth before starting

Decide the level when the release starts and write it down. Applying the full process to an ordinary change cost about ten hours on one release, which is how a process stops being followed.

| Level | When | What it takes |
|---|---|---|
| 1. Ordinary change | Texts, interface, settings, small fixes | Steps 2 and 5. No cross-review. Compare each tool's output with the previous release: only a **new** result has to be analysed in writing |
| 2. Sensitive change | User input, capabilities, files shared by a network (`.htaccess`, `wp-config.php`), authentication, caches that serve content, the gate tools themselves | Level 1, plus step 3, plus **one** bounded cross-review. A second round only if the first finds a flaw of medium severity or higher in shipped code. Two rounds at most |
| 3. Security plugin, or a published vulnerability | Always for a plugin whose function is security; any plugin after a CVE | Chained blind rounds. If a round forces shipped code to be redone, the result is reviewed again. The chain stops when a round finds nothing in shipped code and its findings stay in the test harness or the changelog |

At any level:

- A confirmed finding of medium severity or higher **blocks the release**.
- Low and informational findings do not change code in the middle of the chain. Write them down for the next minor version.
- If the change turns out to touch something sensitive, raise the level.
- State a budget at the start, in time. When it is exceeded, stop and decide instead of drifting. Record what the release cost next to its figures: it is the only way to know whether the process works.
- Keep releases small. Several large features in one version multiply every step below.

## Step 2: mechanical checks, always

Run from the plugin root. `SKILL` is the folder of this skill.

```bash
# 1. PHP: a dynamic tag name without an allowlist, and superglobals echoed. This does not read JavaScript.
grep -rnE --include='*.php' "'<' \. esc_attr|'<' \. \\\$|echo \\\$_(GET|POST|REQUEST)" .

# 2. JavaScript: every escaper against the context it is used in. A FAIL stops the release.
node "$SKILL/scripts/test-escapers.js" assets/js

# 3. Entry points, to fill the inventory below
grep -rnE --include='*.php' "wp_ajax_|admin_post_|register_rest_route|add_shortcode" .

# 4. Registered meta (references/registered-meta-and-core-filters.md)
grep -rnE --include='*.php' 'register_(post_|term_|comment_|user_)?meta\s*\(' .

# 5. Suppressions of security and SQL sniffs, and disables that are never closed
grep -rnE --include='*.php' 'phpcs:(ignore|disable).*(WordPress\.Security|WordPress\.DB\.PreparedSQL|PluginCheck\.Security)' .
grep -rliE --include='*.php' 'phpcs:disable' . | while IFS= read -r f; do
  d=$(grep -ciE '((//|#|/\*+)[[:space:]]*|^[[:space:]]*\*+[[:space:]]*)@?phpcs:disable' "$f" || true)
  e=$(grep -ciE '((//|#|/\*+)[[:space:]]*|^[[:space:]]*\*+[[:space:]]*)@?phpcs:enable' "$f" || true)
  if [ "$d" -gt "$e" ]; then echo "NOT CLOSED: $f ($d disable, $e enable)"; fi
done
php "$SKILL/scripts/audit-suppressions.php" .

# 6. Headers chosen by the caller, with literal and with variable keys (references/request-data-and-client-ip.md)
grep -rnE --include='*.php' '\$_SERVER\[.(HTTP_[A-Z_]+)' .
grep -rnE --include='*.php' '\$_SERVER\[ *\$' .

# 7. Code copied from another plugin: lint checks syntax, not that the symbols exist
php -l includes/class-copied.php
python3 "$SKILL/scripts/class-symbols.py" includes/class-copied.php

# 8. Plugin Check with zero errors and zero warnings
wp plugin check your-plugin-slug

# 9. Your permanent harness (see below)
bash tests/security/run-all.sh
```

Check a zero before believing it. Each grep above has returned a false zero at some point because of an escaping mistake: an unescaped parenthesis that `grep -E` read as an open group, a character class that matched nothing, a `\|` that the local `grep` did not understand. Run the real command once on lines you know must match:

```bash
mkdir -p /tmp/canary && printf '%s\n' "<?php" "echo '<' . esc_attr( \$tag );" "echo '<' . \$tag;" "echo \$_GET['x'];" > /tmp/canary/a.php
grep -rnE --include='*.php' "'<' \. esc_attr|'<' \. \\\$|echo \\\$_(GET|POST|REQUEST)" /tmp/canary | wc -l   # must print 3
printf '%s\n' '<?php' '// phpcs:disable' > /tmp/canary/b.php   # the loop of check 5, run on /tmp/canary, must report b.php
```

And know what each one cannot see, so that its zero is not read as more than it is:

- Check 1 only finds the single-quoted concatenation. The same mistake written with `printf( '<%s>', esc_attr( $tag ) )` or with double quotes is invisible to it. It is a net for the commonest shape, not a proof
- The loop of check 5 counts a directive that sits right at the start of a comment (`//`, `#`, `/*` or a ` * ` line of a docblock), in any letter case, with or without the `@` that PHPCS also accepts, and also after code on the same line. Requiring it at the start of the comment is what keeps a mention in prose from counting. A file where the counts balance can still have a sniff switched off for good, because an `enable` may close a different sniff from the one the `disable` opened: read what each one closes

### The entry-point inventory

One row per handler. The last four columns are the difference with an audit that only checks that a capability exists: they are the ones that show a capability that exists and is not enough.

| Handler | Hook or route | Nonce | Capability | Takes an object id from the request? | Permission over that object? | Touches a resource shared by the network? | Network gate? |
|---|---|---|---|---|---|---|---|

Regenerate it in every release. Its diff is what gives away a new handler without a gate. Close each alert by fixing it or by writing the justification in its row.

What this inventory does not cover, and has to be reviewed by hand: interceptors hooked on `init`, `admin_init` or `template_redirect` (a firewall or a maintenance mode lives entirely there), `update_option_*` hooks, activation, deactivation and uninstall routines, and hooks whose name is built in a variable. In a plugin that writes files when it is activated, a clean inventory covers none of that.

## Step 3: written analysis

For level 2 and above, and at level 1 only when the change touches the area. Answer in writing:

1. **Surface, not diff.** If the change touches an escaper, a validator, a capability check or an `is_*_request()` helper, list every caller (`grep -rn 'symbol' .`) and classify each use. The unit of review is the function, not the lines that changed.
2. **For each handler: is the capability enough for what it touches?** Not "is there a check".
3. **For each shortcut that skips a check: who holds every value it depends on?**
4. **Which data in the request is chosen by the caller, and what is decided with it?**
5. **Does anything store, export or email a copy of something that carries secrets?**
6. **Does this release repair something that never worked?** A loop that did not iterate, a deletion that did not delete, a comparison that did not compare. Audit the revived function as if it were new, because for users it is. And cross the list of deferred issues with what the release fixes: a deferred note that says "this does not make it worse" expires the moment something that feeds it is touched. In one release, fixing a session limit that had never closed anything turned a known, deferred design flaw into a real one, and the review that read the deferred note still approved it.
7. **What did this review not look at?** Write it down, so that the next one starts there instead of repeating the same blind spot.

## Step 4: cross-review

The review that works is done by someone who did not write the change and is not handed your conclusions: a colleague, or a fresh AI session that receives the code and the questions and nothing else. A reviewer given your hypothesis tends to return it confirmed.

Keep the brief bounded: the diff, a short list of concrete questions, a time limit and a word limit for the report. "Review everything" costs more than the change it reviews and comes back worse.

For each finding of medium severity or higher, add a cell to the harness and a mutant that reintroduces the flaw and makes that cell fail, and note it in the header of the harness ("removing X makes J1 fail"). Without the mutant, a green harness only says that today's flaw is not one of those its author imagined: one harness kept reporting 42 passes with both of the flaws that the previous review had just fixed put back in.

## Step 5: the release note, with numbers

A gate without a number has not been passed. Prose such as "security reviewed, all good" cannot be audited by the next release. Figures can:

```text
Gates 2.4.1 (level 1, 50 min): escapers 5 functions, 0 FAIL · entry points 59, 4 alerts justified ·
registered meta 11, all with a named sanitizer · security suppressions 60, 0 without justification,
0 disable left open, the 27 in authentication files re-read · header reads 16, 0 decide ·
Plugin Check 0/0 · harness 42 cells pass.
Not looked at: the block editor script, the uninstall routine.
```

When you report suppressions, report their reach and not their count. A `phpcs:disable` over 1,500 lines and one over 30 both count as one.

## Rules for trusting a gate

Each of these was learned from a gate that reported green while the flaw was there.

- **A gate never answers "nothing to see" when the truth is "I could not look".** An inventory tool recognised handlers registered as `array( $this, 'method' )` and not as `array( __CLASS__, 'method' )`, so it reported "0 entry points, 0 alerts" for a plugin with 24 handlers, and everybody read that as approved. A tool should count what it expects to find by an independent path, compare that with what it managed to parse, and exit with an error when they differ. A zero is justified, never assumed.
- **A tool is not verified until it has caught a known flaw and left a correct case alone.** Two successive versions of the same escaper test were broken in opposite directions: the first flagged the recommended recipe, the second stopped seeing the exact shape of the CVE. Keep the validation matrix written down and run it again whenever the tool changes.
- **A false positive is as serious as a false negative.** A gate that warns without reason ends up ignored, like one that stays silent.
- **A detector looks for the character that breaks the context, not for the name of the payload.** Searching the output for `onload=X` reported a failure inside `&lt;svg onload=X&gt;`, which is harmless. Look for the real `<`, or the real quote that closes the attribute.
- **Before concluding that a fix does not work, confirm that the code you think is running is the code running.** Seen on the same day: OPcache still serving the previous copy, because `rsync -a` preserves modification times (touch the files after deploying and wait a few seconds); a whole matrix failing because the scenario was not set up (the active theme was not the one under test); admin renders returning a 55-byte error because admin classes are not loaded under WP-CLI, where `is_admin()` is false. Each row of a test should print the version and the scenario it measured, read from the response itself, and the harness should tell apart "the code fails", "the environment is missing" and "the scenario is not set up".
- **Measure the effect, not the write.** A login lockout was tested for 73 releases by checking that the row was written, and never by checking that the next request was blocked. It only worked on sites set to UTC.
- **Write dismissals down, with the argument and the date.** A dismissed finding is a security decision. Record the exact link in the chain that does not hold, not "reviewed, does not apply", and do not reopen it without new evidence. A dismissal that rests on "it needs an unusual configuration" is not closed until you have checked what exactly is needed: in one case it was a native checkbox in Network Settings.
- **Do not rely on a gate you have not run.** Documentation that describes a check that was never built is worse than none, because it feeds reasoning such as "the manifest would have caught that".
- **Keep the artifacts.** Reports go in a known folder with the date in the name. An audit whose report is gone cannot tell the next one what it covered.

## The permanent regression harness

Tests that protect a behaviour are run at every release, not merely kept.

- They live in one place that is not named after a version, for example `tests/security/`, with a single `run-all.sh`. Tests left in a folder called after a release are never run again.
- **Every proof of concept of a published vulnerability becomes a permanent test the same day.** Its value is not showing today's flaw but catching the regression a year from now. It has to fail against the vulnerable tag and pass against the current code.
- A time-based protection (lockout, expiry, rate limit) is tested by watching it act on the next request, on a site whose timezone is not UTC.
- The harness is not shipped in the plugin zip.

## The wordpress.org automated security review

Since June 2026 every release of a plugin hosted on wordpress.org is analysed, during a cooldown period before it reaches the update API, by several AI models together with Jetpack Scan. A release with a high risk score is blocked: sites keep receiving the previous version and all committers get an email with the findings. It is documented in the [handbook](https://developer.wordpress.org/plugins/wordpress-org/automated-security-review/).

What that changes in practice:

- **Plugin Check at zero predicts nothing about it.** Plugin Check looks at coding rules in PHP files. The review analyses logic. A release went out with Plugin Check at 0/0 and three real security flaws inside, and was blocked the same day.
- **It cannot be run before uploading**, and neither `phpcs:ignore` nor any configuration file affects it. What you can do is ask yourself its questions first, which are items 3 to 5 of step 3.
- **Sort the findings into two piles before touching anything.** Signature findings, where a scanner matches patterns in files that are the plugin's function (a malware signature database, an editor of `wp-config.php`), are not fixed in code: reply to the email explaining why the finding does not apply. Logic findings are almost always real: reproduce them and fix them. Mixing the piles costs in both directions, rewriting correct code for a false positive or dismissing a real flaw because it arrived next to a false one.
- **Appeal and fix at the same time.** The handbook says it plainly: publishing a fixed release is almost always faster than waiting for the manual review of an appeal.
- **Do not add signature surface by accident.** It usually arrives in comments: documenting a security fix by writing the exact shape of the attack puts literals such as `eval( $_GET[...] )` in a file that ships, where a pattern scanner weighs them like code. The shape of the attack belongs in the test harness, which is not shipped, and the comment points to its test. Never put an invisible character in source code.

Two checks before tagging, both a matter of seconds:

```bash
PREV=../tags/2.4.0   # the previous published release
for p in 'eval *\(' 'base64_decode' 'gzinflate' 'str_rot13' 'assert *\('; do
  a=$(grep -rniE --include='*.php' --include='*.js' --include='*.json' "$p" "$PREV" | wc -l | tr -d ' ')
  b=$(grep -rniE --include='*.php' --include='*.js' --include='*.json' "$p" . | wc -l | tr -d ' ')
  printf "  %-16s previous=%-4s current=%-4s %s\n" "$p" "$a" "$b" "$([ "$a" = "$b" ] && echo SAME || echo CHANGED)"
done
# Zero-width characters, a byte order mark, a non-breaking space. Written as bytes on purpose:
# grep -P is not available in the grep that ships with macOS, where it prints nothing.
# `command grep` reaches the system grep where `grep` is a shell function that wraps another tool:
# ugrep, which some coding agents put in its place, does not see a byte order mark at the start of a file.
LC_ALL=C command grep -rn --include='*.php' --include='*.js' --include='*.json' --include='*.css' \
  -e $'\xe2\x80\x8b' -e $'\xe2\x80\x8c' -e $'\xe2\x80\x8d' -e $'\xef\xbb\xbf' -e $'\xc2\xa0' .
```

The first has to give the same figures as the previous release, or you have to know why not. The second has to print nothing. Both have returned a false zero before, so each has its canary:

```bash
printf 'eval( $x );\n' > /tmp/canary.php && grep -cniE 'eval *\(' /tmp/canary.php                 # must print 1
printf '\xef\xbb\xbf<?php\n' > /tmp/canary.php && LC_ALL=C command grep -c -e $'\xef\xbb\xbf' /tmp/canary.php   # must print 1
```

## Bundled scripts, and what each one does not see

Run `bash "$SKILL/scripts/selftest.sh"` once in a new environment. It runs each tool against a case it must flag and a case it must leave alone, and reports any cell it could not measure instead of counting it as a pass. Set `PHP=/path/to/php` if PHP is not on the `PATH`.

| Script | What it answers | What it does not see |
|---|---|---|
| `scripts/test-escapers.js <dir>` | Does any JavaScript escaper that does not encode quotes end up inside an attribute? `FAIL` exits 1, `WARN` means it is only used in text today, `OK` means it is safe anywhere | It reads one line at a time, so an attribute built across several lines is invisible. It only finds helpers named `esc*` or `escape*`, and only looks for their uses in the file that defines them. Its zero only means something if the plugin builds HTML in JavaScript at all |
| `scripts/audit-suppressions.php <dir>` | Which suppressions of security and SQL sniffs exist, ranked by risk. Exits 1 when any of them has no written justification after `--`, and 3 when its own independent count does not match what it parsed | Whether a suppression is legitimate, or whether its justification is true. That has to be read. It does not pair `phpcs:disable` with `phpcs:enable`, and does not measure how many lines a disable leaves uncovered |
| `scripts/class-symbols.py <file.php> [...]` | Does every `self::X()`, `$this->X()` and `self::CONSTANT` used in a class file exist in that file? For code copied between plugins, where `php -l` passes and the first page load is a fatal error | Inheritance and traits (everything inherited is reported as missing), calls to other classes, dynamic names, properties without declared visibility. It reads with regular expressions, not with the PHP tokenizer |

`test-escapers.js` runs the body of each escaper it finds, to see what the function does with a hostile string. Use it on your own code, not on a plugin you do not trust.

A tool's blind spots travel with the tool. Read the header of each script before trusting its zero, and when you find a new blind spot, write it there.
