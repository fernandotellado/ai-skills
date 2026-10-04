<?php
// SPDX-License-Identifier: GPL-2.0-or-later
// Synthetic fixture for audit-suppressions.php. Not code from any real plugin.
// Expected: a HIGH-risk and a LOW-risk suppression, both with a written justification, so exit 0.

function fixture_render( $value ) {
	echo $value; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- $value is escaped by the caller with esc_html().
}

function fixture_current_tab() {
	return isset( $_GET['tab'] ) ? 'yes' : 'no'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only tab switch, it changes no data.
}
