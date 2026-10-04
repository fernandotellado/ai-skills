<?php
// SPDX-License-Identifier: GPL-2.0-or-later
// Synthetic fixture for audit-suppressions.php. Not code from any real plugin.
// Expected: one HIGH-risk security suppression with no written justification, so exit 1.

function fixture_render( $value ) {
	echo $value; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}
