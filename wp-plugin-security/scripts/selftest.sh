#!/usr/bin/env bash
# SPDX-License-Identifier: GPL-2.0-or-later
#
# selftest.sh - validates test-escapers.js, audit-suppressions.php and class-symbols.py
# against the synthetic inputs in fixtures/.
#
# Each cell is one tool run against one fixture. Its line shows the expected and the actual
# exit code, plus a check of a marker line in the output. The marker is needed because the
# exit code alone cannot tell the verdicts apart (WARN and OK both exit 0), and because a
# tool that did not really run must never be mistaken for a tool that passed.
#
# Usage:       bash selftest.sh
#              PHP=/path/to/php bash selftest.sh     (default: php from the PATH)
#
# Exit codes:  0  every cell matched
#              1  at least one cell that ran did not match
#              2  at least one cell could not run (node, php or python3 missing or not
#                 working) and every cell that did run matched. Such a cell is printed
#                 as NOT MEASURED and never counts as a pass.
#
# Paths are resolved relative to this script, so it works from any directory.

HERE="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
FIX="$HERE/fixtures"
PHP_BIN="${PHP:-php}"

# Does the runtime exist and answer --version? (A stub that only prints an installer
# prompt does not count as available.)
runtime_ok() {
    command -v "$1" >/dev/null 2>&1 && "$1" --version >/dev/null 2>&1
}

NODE_OK=0; PHP_OK=0; PY_OK=0
runtime_ok node && NODE_OK=1
runtime_ok "$PHP_BIN" && PHP_OK=1
runtime_ok python3 && PY_OK=1

echo "Runtimes:"
for pair in "node:$NODE_OK:node" "php:$PHP_OK:$PHP_BIN" "python3:$PY_OK:python3"; do
    name="${pair%%:*}"; rest="${pair#*:}"; ok="${rest%%:*}"; bin="${rest#*:}"
    if [ "$ok" = 1 ]; then
        printf '  %-8s %s\n' "$name" "$("$bin" --version 2>&1 | head -n 1)"
    else
        printf '  %-8s NOT AVAILABLE\n' "$name"
    fi
done
echo

TOTAL=0; MATCHED=0; MISMATCHED=0; UNMEASURED=0

# cell <runtime> <script> <fixture> <expected exit code> <expected output, as an ERE>
cell() {
    local runtime="$1" script="$2" fixture="$3" want="$4" pattern="$5"
    local bin ok out got status note
    TOTAL=$((TOTAL + 1))
    case "$runtime" in
        node)    bin="node";     ok=$NODE_OK ;;
        php)     bin="$PHP_BIN"; ok=$PHP_OK ;;
        python3) bin="python3";  ok=$PY_OK ;;
    esac
    if [ "$ok" != 1 ]; then
        printf 'NOT MEASURED  %-22s %-24s expected exit %s, actual exit -   (%s not available)\n' \
            "$script" "$fixture" "$want" "$runtime"
        UNMEASURED=$((UNMEASURED + 1))
        return
    fi
    out="$("$bin" "$HERE/$script" "$FIX/$fixture" 2>&1)"
    got=$?
    status=MATCH; note=""
    if [ "$got" != "$want" ]; then status=MISMATCH; fi
    if ! printf '%s\n' "$out" | grep -Eq -- "$pattern"; then
        status=MISMATCH; note="   (expected output not found: $pattern)"
    fi
    if [ "$status" = MATCH ]; then
        MATCHED=$((MATCHED + 1))
    else
        MISMATCHED=$((MISMATCHED + 1))
    fi
    printf '%-13s %-22s %-24s expected exit %s, actual exit %s%s\n' \
        "$status" "$script" "$fixture" "$want" "$got" "$note"
}

#     runtime  script                  fixture                  exit  output that must appear
cell node    test-escapers.js       escapers/fail            1 '^1 escapers: 1 FAIL, 0 WARN, 0 OK\.$'
cell node    test-escapers.js       escapers/warn            0 '^1 escapers: 0 FAIL, 1 WARN, 0 OK\.$'
cell node    test-escapers.js       escapers/ok              0 '^1 escapers: 0 FAIL, 0 WARN, 1 OK\.$'
cell php     audit-suppressions.php suppressions/unjustified 1 '^Suppressions \(security and SQL\): 1 total, 1 high risk, 1 without a written justification\.'
cell php     audit-suppressions.php suppressions/justified   0 '^Suppressions \(security and SQL\): 2 total, 1 high risk, 0 without a written justification\.'
cell python3 class-symbols.py       symbols/missing.php      1 '^2 undefined symbols in 1 files\.$'
cell python3 class-symbols.py       symbols/complete.php     0 '^0 undefined symbols in 1 files\.$'

printf '\n%d cells: %d matched, %d mismatched, %d not measured.\n' \
    "$TOTAL" "$MATCHED" "$MISMATCHED" "$UNMEASURED"

if [ "$MISMATCHED" -gt 0 ]; then
    exit 1
fi
if [ "$UNMEASURED" -gt 0 ]; then
    echo "Some cells could not run, so this result is not a pass."
    exit 2
fi
exit 0
