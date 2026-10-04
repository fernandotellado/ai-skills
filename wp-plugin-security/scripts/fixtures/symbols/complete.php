<?php
// SPDX-License-Identifier: GPL-2.0-or-later
// Synthetic fixture for class-symbols.py. Not code from any real plugin.
// Expected: everything used through self and $this is defined in the file, so exit 0.
class Fixture_Complete {
	const KNOWN = 'a';

	private $known_prop = 1;

	public function run() {
		return self::helper() . self::KNOWN . $this->known_prop;
	}

	private static function helper() {
		return 'x';
	}
}
