<?php
// SPDX-License-Identifier: GPL-2.0-or-later
/**
 * audit-suppressions.php - inventory of suppressions of security sniffs.
 *
 * Purpose. A "phpcs:ignore" or "phpcs:disable" on a security sniff silences the check
 * that would otherwise flag the line, so a clean coding-standards report can hide a real
 * vulnerability behind a justification that was written once and copied from release to
 * release. This script lists every such suppression in a plugin tree (those naming a
 * WordPress.Security.*, WordPress.DB.PreparedSQL or PluginCheck.Security.* sniff), ranks
 * each one by what it puts at risk (output escaping, input validation and SQL are HIGH,
 * nonce checks MEDIUM or LOW, any other sniff in scope is LOW, and any suppression in a
 * file whose name looks like authentication, firewall, REST or permissions code is at
 * least MEDIUM) and flags the ones with no written justification after " -- ". It does
 * not decide whether a suppression is legitimate: that has to be read. It only makes
 * sure none goes unnoticed. It needs the PHP mbstring extension.
 *
 * Usage:       php audit-suppressions.php <plugin-dir> [--md] [--all]
 *                --md   print every suppression as a Markdown table, at every risk level
 *                --all  in the default listing, also print the LOW rows (the default
 *                       listing prints only HIGH and MEDIUM)
 *
 * Exit codes:  0  no suppression without a written justification (or none found)
 *              1  at least one suppression has no written justification. This counts
 *                 suppressions at every risk level, including LOW ones that the default
 *                 listing hides; the HIGH count is only reported in the summary line
 *              2  usage error: missing path, or not a directory
 *              3  self-check failed: the independent line count sees more suppressions
 *                 than the parser resolved, so the result cannot be read as a pass
 *
 * What it does NOT see:
 *  - Whether a suppression is legitimate. It only makes sure none goes unnoticed and ranks
 *    them by risk. A justification is checked for being present, not for being true.
 *  - Suppressions of sniffs outside the three families above (DirectDatabaseQuery,
 *    NoCaching, SlowDBQuery and the like), which are skipped on purpose.
 *  - Whether a phpcs:disable is ever closed by a phpcs:enable. An unclosed disable
 *    silences the sniff until the end of the file; this script does not pair them and
 *    does not count the lines a disable covers.
 *  - Anything outside PHP files: ruleset exclusions in XML, legacy @codingStandardsIgnore
 *    annotations, and files that do not end in .php.
 *  - It reads line by line with regular expressions, not with PHPCS, so a line that only
 *    mentions phpcs:ignore in prose counts as a directive.
 *
 * Validate it:  bash selftest.sh
 */

$root = $argv[1] ?? '';
$md   = in_array( '--md', $argv, true );
$all  = in_array( '--all', $argv, true );
if ( ! $root || ! is_dir( $root ) ) {
    fwrite( STDERR, "Usage: php audit-suppressions.php <plugin-dir> [--md] [--all]\n" );
    exit( 2 );
}

// Files where a suppression costs more: the ones that look like authentication, login,
// REST, firewall or permission code. Those are reviewed one by one.
$sensitive = '/(auth|login|two-factor|2fa|user-security|rest-api|firewall|capabilit|permission|security)/i';

// Risk per sniff. EscapeOutput is direct XSS; ValidatedSanitizedInput is the entry point of
// the data; NonceVerification is CSRF, and Missing weighs more than Recommended because the
// sniff only reports it where there is a write.
$risk = array(
    'EscapeOutput'                  => 'HIGH',
    'ValidatedSanitizedInput'       => 'HIGH',
    'NonceVerification.Missing'     => 'MEDIUM',
    'NonceVerification.Recommended' => 'LOW',
    'SQL'                           => 'HIGH',
    'PreparedSQL'                   => 'HIGH',
);

$rows = array(); $unjustified = 0; $highs = 0;
$it = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $root, FilesystemIterator::SKIP_DOTS ) );
foreach ( $it as $f ) {
    if ( ! $f->isFile() || 'php' !== strtolower( $f->getExtension() ) ) { continue; }
    $path  = $f->getPathname();
    $lines = file( $path );
    foreach ( $lines as $n => $line ) {
        if ( ! preg_match( '/phpcs:(ignore|disable)\s+([^\r\n]*)/', $line, $m ) ) { continue; }
        $rest = $m[2];
        // DirectDatabaseQuery, NoCaching and SlowDBQuery are not security sniffs: suppressing
        // them is legitimate and common. The ones that count are those that switch off a defence.
        if ( ! preg_match( '/WordPress\.Security|WordPress\.DB\.PreparedSQL|PluginCheck\.Security/', $rest ) ) { continue; }

        // Sniffs suppressed on that line.
        preg_match_all( '/(?:WordPress\.Security\.|WordPress\.DB\.|PluginCheck\.Security\.)([A-Za-z.]+)/', $rest, $sn );
        $sniffs = $sn[1] ? array_unique( $sn[1] ) : array( '?' );

        // Justification: whatever follows " -- ".
        $just = '';
        if ( preg_match( '/--\s*(.+)$/', $rest, $j ) ) { $just = trim( $j[1] ); }

        // Highest risk level among the sniffs on the line.
        $level = 'LOW';
        foreach ( $sniffs as $s ) {
            foreach ( $risk as $key => $r ) {
                if ( false !== strpos( $s, $key ) ) {
                    if ( 'HIGH' === $r ) { $level = 'HIGH'; }
                    elseif ( 'MEDIUM' === $r && 'HIGH' !== $level ) { $level = 'MEDIUM'; }
                }
            }
        }
        $is_sensitive = (bool) preg_match( $sensitive, basename( $path ) );
        if ( $is_sensitive && 'HIGH' !== $level ) { $level = 'MEDIUM'; }

        // The affected line of code: the same line for an inline ignore, the next one if the
        // directive stands alone.
        $code = trim( preg_replace( '/\/\/.*|\/\*.*/', '', $line ) );
        if ( '' === $code && isset( $lines[ $n + 1 ] ) ) { $code = trim( $lines[ $n + 1 ] ); }

        if ( '' === $just ) { $unjustified++; }
        if ( 'HIGH' === $level ) { $highs++; }

        $rows[] = array(
            'level'     => $level,
            'sniff'     => implode( ',', $sniffs ),
            'where'     => str_replace( rtrim( $root, '/' ) . '/', '', $path ) . ':' . ( $n + 1 ),
            'sensitive' => $is_sensitive ? 'yes' : '',
            'just'      => $just,
            'code'      => mb_substr( $code, 0, 90 ),
        );
    }
}

$order = array( 'HIGH' => 0, 'MEDIUM' => 1, 'LOW' => 2 );
usort( $rows, function ( $a, $b ) use ( $order ) {
    return $order[ $a['level'] ] <=> $order[ $b['level'] ] ?: strcmp( $a['where'], $b['where'] );
} );

if ( $md ) {
    echo "| Risk | Sniff | Where | Sensitive file | Justification | Code |\n|---|---|---|---|---|---|\n";
    foreach ( $rows as $f ) {
        printf( "| %s | %s | %s | %s | %s | `%s` |\n", $f['level'], $f['sniff'], $f['where'], $f['sensitive'], $f['just'] ?: '**UNJUSTIFIED**', $f['code'] );
    }
} else {
    foreach ( $rows as $f ) {
        if ( 'LOW' === $f['level'] && ! $all ) { continue; }
        printf( "%-6s %-46s %-34s %s\n", $f['level'], $f['sniff'], $f['where'], $f['just'] ? '' : '<-- UNJUSTIFIED' );
    }
}

// Self-check: count by a path independent of the parser what should have been found. A zero
// has to be justified, never assumed.
$expected = 0;
$it2 = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $root, FilesystemIterator::SKIP_DOTS ) );
foreach ( $it2 as $f2 ) {
    if ( ! $f2->isFile() || 'php' !== strtolower( $f2->getExtension() ) ) { continue; }
    foreach ( file( $f2->getPathname() ) as $l ) {
        if ( preg_match( '/phpcs:(ignore|disable)/', $l )
            && preg_match( '/WordPress\.Security|WordPress\.DB\.PreparedSQL|PluginCheck\.Security/', $l ) ) {
            $expected++;
        }
    }
}
if ( count( $rows ) < $expected ) {
    fwrite( STDERR, sprintf(
        "\nSELF-CHECK FAILED: the independent count sees %d suppressions and the parser resolved %d.\n"
        . "This result cannot be read as a pass.\n", $expected, count( $rows ) ) );
    exit( 3 );
}

fprintf( STDERR, "\nSuppressions (security and SQL): %d total, %d high risk, %d without a written justification. (independent count: %d)\n", count( $rows ), $highs, $unjustified, $expected );
exit( $unjustified > 0 ? 1 : 0 );
