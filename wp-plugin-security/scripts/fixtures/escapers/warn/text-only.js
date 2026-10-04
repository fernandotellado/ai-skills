// SPDX-License-Identifier: GPL-2.0-or-later
// Synthetic fixture for test-escapers.js. Not code from any real plugin.
// Expected verdict: WARN. The same DOM-based helper, but it is only used in text position.
function escHtml(text) {
    var node = document.createElement('div');
    node.textContent = text;
    return node.innerHTML;
}

function renderCell(item) {
    return '<td>' + escHtml(item.label) + '</td>';
}
