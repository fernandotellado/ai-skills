# A secret does not change places unless somebody decides it

Read this when a plugin copies, caches, exports, emails, logs or stores the content of a file or of a setting to use it later: integrity baselines, line diffs, backups, settings exports, debug reports, support bundles.

## The rule

Before storing a copy of something, ask what that something carries inside.

An integrity scanner kept the whole `wp-config.php` in an option so that it could show which lines had changed. With it went the database password and the eight authentication keys and salts, which from then on were within reach of anybody who could read the database or a backup of it, without ever touching the filesystem: a SQL injection in any other plugin, a leaked dump, a staging copy. It had been that way since the version that introduced the diff, and it was found by an automated review, not by the author.

Files that are never stored whole: `wp-config.php`, `.env`, `.htpasswd`, any database dump. `.htaccess` deserves the same care, because it can carry tokens and credentials in `SetEnv`, `RequestHeader` and authentication directives.

The same question applies to everything that leaves the site: a settings export that includes API keys, a "copy system info" button that prints constants, an email that quotes a configuration line.

## The recipe

1. **Keep hashing the complete file.** A change to a secret is still detected. What changes is that the diff cannot show it, which is the right trade.
2. **Redact both sides of the comparison the same way.** Otherwise every credential line shows up as a change nobody made.
3. **Check the output, and store nothing if the check fails.** The redaction is the part most likely to miss a shape nobody thought of, and the price of missing one is a secret in the database. So after redacting, compare the result against the values actually in force, and if one survived, keep no copy at all. The scan then reports the change without a line diff. Losing the diff is better than leaking.
4. **Write the list the other way round.** A list of what is secret is never complete. Redact every value, and keep a short list of what is known not to be secret: the site URL, paths, memory limits, the table prefix.
5. **Clean what earlier versions stored.** The fix is not finished while the old copies are still in the options table. Redact them once, gated by a version option.
6. **On a network, show the lines only to whoever may read the file.** A subsite administrator sees that a shared file changed, not what it says.

```php
/**
 * A copy of a configuration file that is safe to store, or '' when it cannot be made safe.
 */
function ayudawp_storable_copy( $content ) {
    $redacted = ayudawp_redact_values( $content );   // every value, read as PHP tokens

    // Output check against the values in force: every constant the file names
    // and every environment variable it reads, not a fixed list of core constants.
    foreach ( ayudawp_values_in_force( $content ) as $value ) {
        // Short values match by accident: a database called "wp" appears in any PHP file.
        if ( strlen( $value ) >= 8 && false !== strpos( $redacted, $value ) ) {
            return '';
        }
    }

    return $redacted;
}
```

## Why a list of secret names fails

The first version of the fix redacted `define()` calls whose name looked like a credential, and its output check covered the twelve constants WordPress defines. Measured against real configuration files, 10 of 15 shapes still stored their secret. Use them as test cases for any redaction:

```php
define( 'FTP_PASS', 'secret' );                                // a name the list did not have
define( 'SMTP_PASSWORD', 'secret' );                           // and another one
const API_SECRET = 'secret';                                   // const, not define()
putenv( 'SERVICE_KEY=secret' );                                // inside the string
$_ENV['SERVICE_KEY'] = 'secret';                               // array write
define( 'CACHE_CONFIG', serialize( array( 'pass' => 'secret' ) ) );   // nested
define( 'TOKEN', getenv( 'TOKEN' ) ?: 'secret' );              // fallback value
define( 'LONG_KEY', <<<EOT
secret
EOT
);                                                             // heredoc
// define( 'OLD_PASSWORD', 'secret' );                         // commented out, still on disk
require '/home/user/secret-token/config.php';                  // a path can carry one too
```

Reading the file with `token_get_all()` and replacing every string token handles all of them, because it does not depend on recognising the name. What stays readable is what names a thing rather than holds it: the first argument of `define()`, array keys, and the values on the short readable list.

What is a comment depends on the PHP version that tokenizes the file (`#[` opens an attribute on PHP 8 and a comment on PHP 7), so test the redaction with the oldest and the newest PHP you support.
