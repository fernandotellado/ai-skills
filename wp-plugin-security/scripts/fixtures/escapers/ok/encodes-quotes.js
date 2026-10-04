// SPDX-License-Identifier: GPL-2.0-or-later
// Synthetic fixture for test-escapers.js. Not code from any real plugin.
// Expected verdict: OK. The helper replaces the five characters that matter,
// so it is safe in an attribute.
function escAttr(value) {
    return String(value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function renderField(item) {
    return '<input type="text" value="' + escAttr(item.value) + '">';
}
