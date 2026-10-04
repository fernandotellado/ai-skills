// SPDX-License-Identifier: GPL-2.0-or-later
// Synthetic fixture for test-escapers.js. Not code from any real plugin.
// Expected verdict: FAIL. A DOM-based helper, which does not encode quotes,
// is used inside a quoted attribute.
function escHtml(text) {
    var node = document.createElement('div');
    node.textContent = text;
    return node.innerHTML;
}

function renderField(item) {
    return '<input type="text" value="' + escHtml(item.label) + '">';
}
