<?php
// SPDX-License-Identifier: GPL-2.0-or-later
// Synthetic fixture for class-symbols.py. Not code from any real plugin.
// Expected: one method and one constant are used through self but never defined, so exit 1.
class Fixture_Missing {
	const KNOWN = 'a';

	private $known_prop = 1;

	public function run() {
		self::undefined_method();
		return self::UNDEFINED_CONST . self::KNOWN . $this->known_prop;
	}
}
