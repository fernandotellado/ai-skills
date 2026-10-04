#!/usr/bin/env python3
# SPDX-License-Identifier: GPL-2.0-or-later
"""class-symbols.py - checks that every self::X(), $this->X() and self::CONSTANT used by
a PHP class file is defined in that same file.

Purpose. A call to a method that does not exist is a runtime error, not a syntax error,
so a file that makes it still passes `php -l` and only fails when that code path runs.
This script catches it in a second, without running anything. It is meant for code that
is copied from one plugin to another, which is where this mistake lives: the copied block
calls a method that exists in the source class but not in the destination class.

Usage:       python3 class-symbols.py <file.php> [...]

Exit codes:  0  every symbol used is defined in the same file
             1  at least one symbol is missing (an unreadable file also ends in exit 1,
                because Python reports the uncaught exception that way)
             2  no file given (this text is printed)

What it does NOT see:
- Inheritance and traits: a class that extends another class or uses a trait gets false
  positives for everything it inherits.
- Calls to other classes (Other_Class::method()): it does not look at them, only at
  self::, static:: and $this->.
- Dynamic names ($this->$method(), constant()): invisible.
- Properties without a declared visibility (var $x) or created on the fly: it reports
  them as missing.
- It reads with regular expressions, not with the PHP tokenizer, so it strips comments
  first to avoid counting examples written in prose, and that is as fine-grained as it
  gets: only /* */ and // comments are stripped (a # comment is still read), and a //
  inside a string literal, such as a URL, hides the rest of that line.

Validate it:  bash selftest.sh
"""
import io, re, sys


def check(path):
    s = io.open(path, encoding='utf-8').read()

    # Strip the comments: an example written in a docblock is not a call.
    code = re.sub(r'/\*.*?\*/', '', s, flags=re.S)
    code = re.sub(r'(?m)//.*$', '', code)

    methods = set(re.findall(r'function\s+(\w+)\s*\(', code))
    constants = set(re.findall(r'const\s+(\w+)\s*=', code))
    properties = set(re.findall(r'(?:private|protected|public)\s+(?:static\s+)?\$(\w+)', code))

    used_m = set(re.findall(r'(?:self|static)::(\w+)\s*\(', code)) | set(re.findall(r'\$this->(\w+)\s*\(', code))
    used_c = set(re.findall(r'(?:self|static)::([A-Z][A-Z0-9_]*)\b(?!\s*\()', code))
    used_p = set(re.findall(r'(?:self|static)::\$(\w+)', code)) | set(re.findall(r'\$this->(\w+)\b(?!\s*\()', code))

    missing = [('method', m) for m in sorted(used_m - methods)]
    missing += [('const', c) for c in sorted(used_c - constants)]
    missing += [('prop', p) for p in sorted(used_p - properties)]

    if missing:
        print('MISSING in %s:' % path)
        for kind, name in missing:
            print('   %-6s %s' % (kind, name))
    else:
        print('OK    %s (%d methods, %d constants)' % (path, len(methods), len(constants)))

    return len(missing)


def main():
    if len(sys.argv) < 2:
        print(__doc__)
        return 2

    total = sum(check(p) for p in sys.argv[1:])
    print('\n%d undefined symbols in %d files.' % (total, len(sys.argv) - 1))
    return 1 if total else 0


if __name__ == '__main__':
    sys.exit(main())
