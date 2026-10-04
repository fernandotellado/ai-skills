# Data chosen by the caller, and the client address

Read this when a plugin reads a request header, resolves the visitor's IP address, exempts some requests from a check, serves cached pages, or implements a login step. It expands "Security decisions must not rest on request data" in `SKILL.md`.

## Contents

- [The rule](#the-rule)
- [Two questions for every module](#two-questions-for-every-module)
- [Exemptions: scope them to what they exist for](#exemptions-scope-them-to-what-they-exist-for)
- [The client address](#the-client-address)
- [Caches: the key comes from what the page is generated with](#caches-the-key-comes-from-what-the-page-is-generated-with)
- [Second-factor flows](#second-factor-flows)

## The rule

A value the caller chooses is good for logging and for display. It never decides whether somebody gets in, whether a check is skipped or who the visitor is.

Chosen by the caller: `User-Agent`, `Referer`, `Host` and every proxy header (`X-Forwarded-For`, `X-Forwarded-Proto`, `CF-Connecting-IP`, `X-Real-IP`, `True-Client-IP`, `Forwarded`, `Client-IP`). In PHP these are the `HTTP_*` keys of `$_SERVER`. Set by the server: `REMOTE_ADDR`, which comes from the connection itself.

Two real cases, both shipped for years before anyone noticed:

- A "private site" mode let search engines in by user agent, so it opened for anyone sending `User-Agent: Googlebot`. The repair was to verify the bot by reverse DNS.
- A firewall had a user-agent allowlist so that remote management services were not treated as bad bots. The allowlist returned before the IP blocklist and before every pattern rule, so whoever sent one of the configured strings also skipped the SQL injection, XSS, traversal and HTTP method rules.

## Two questions for every module

Release gates catch regressions. They do not catch a flaw that was born with the feature, because no diff ever shows it: in one audited plugin, three flaws of this kind had been present for 75 releases, each since the version that introduced the feature. So ask by hand, every time a module is touched:

1. Which data in this request is chosen by the caller?
2. Which security decisions are taken with it?

And list the reads mechanically in every release, even when that area did not change:

```bash
# Literal keys
grep -rnE --include='*.php' '\$_SERVER\[.(HTTP_[A-Z_]+)' .
# Variable keys: $_SERVER[ $key ] inside a loop or a closure, which the first grep cannot see
grep -rnE --include='*.php' '\$_SERVER\[ *\$' .
```

For each result, answer in writing: **is this recorded, or is something decided with it?** If it decides, it is a flaw until proven otherwise. The volume is manageable: across a family of 23 plugins there were 67 header reads, and one of them decided something.

Before believing a zero from either grep, run it on a line you know should match. The first version of the first one had its character classes badly escaped and returned nothing in 23 plugins, which reads as "all clean".

When you learn a pattern like this, the unit of work is the whole codebase and not the place where it showed up. The rule about authorization shortcuts had been written four days earlier while looking at a two-factor bypass, and nobody ran it over the firewall of the same plugin.

## Exemptions: scope them to what they exist for

When the feature is legitimate, keep it and narrow it. A user-agent allowlist exists so that a management service is not treated as a bad bot, so it spares the user-agent rules and nothing else:

```php
// WRONG: one claim skips everything that follows
if ( $this->user_agent_is_allowlisted() ) {
    return;
}
$this->check_ip_blocklist();
$this->check_request_patterns();
$this->check_user_agent_rules();

// CORRECT: the claim only spares the check it is about
$this->check_ip_blocklist();
$this->check_request_patterns();
if ( ! $this->user_agent_is_allowlisted() ) {
    $this->check_user_agent_rules();
}
```

## The client address

Blocklists, allowlists, rate limits and login lockouts all act on the address the plugin believes. If the visitor can choose it, they get out of the blocklist, into the allowlist and onto a fresh address for every request. Three rules, each learned from a separate flaw in the same plugin:

1. **One resolver for the whole plugin.** Two ways of resolving the address in one codebase means one good one and one exploitable one. Every module calls the same function, and every read of a proxy header lives in that one class.
2. **A proxy header counts only when the connection comes from a proxy you trust.** Letting the administrator declare "this site is behind X-Forwarded-For" is not enough: with that option on, anyone who reaches the origin directly forges the header. The header is honoured only if `REMOTE_ADDR` is in a list of proxy addresses or ranges the administrator declared, or, with no list, an address of your own network. A load balancer with a public address has to be listed, otherwise its header is ignored, which is the safe default. For a header that only one provider sends, that provider's published ranges are a reasonable built-in.
3. **Read `X-Forwarded-For` from the right.** A proxy appends the address it received the connection from and keeps in front whatever the visitor sent. In `a, b, c` the visitor may have written `a` and `b`. Taking the first entry, which is what most snippets do, takes the one the visitor chose.

### A resolver you can copy

`resolve()` is a pure function over a `$_SERVER`-like array so that it can be tested without a request. Test it before trusting it, with the oldest and the newest PHP you support.

```php
/**
 * The one place in the plugin that answers "who is the visitor?".
 */
final class Ayudawp_Client_IP {

    // Proxy headers an administrator may declare as trusted, and their $_SERVER key.
    const HEADERS = array(
        'cf-connecting-ip' => 'HTTP_CF_CONNECTING_IP',
        'x-forwarded-for'  => 'HTTP_X_FORWARDED_FOR',
        'x-real-ip'        => 'HTTP_X_REAL_IP',
    );

    /**
     * Address of the current request.
     *
     * @param string   $trusted_header  Key of HEADERS chosen in the settings, or '' for none.
     * @param string[] $trusted_proxies Addresses or CIDR ranges of the proxies in front of the site.
     */
    public static function get( $trusted_header = '', array $trusted_proxies = array() ) {
        $server = array();

        if ( isset( $_SERVER['REMOTE_ADDR'] ) ) {
            $server['REMOTE_ADDR'] = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );
        }

        if ( isset( self::HEADERS[ $trusted_header ] ) ) {
            $key = self::HEADERS[ $trusted_header ];

            if ( isset( $_SERVER[ $key ] ) ) {
                $server[ $key ] = sanitize_text_field( wp_unslash( $_SERVER[ $key ] ) );
            }
        }

        return self::resolve( $server, $trusted_header, $trusted_proxies );
    }

    // The same decision over a $_SERVER-like array, so it can be tested without a request.
    public static function resolve( array $server, $trusted_header = '', array $trusted_proxies = array() ) {
        $remote = isset( $server['REMOTE_ADDR'] ) ? trim( (string) $server['REMOTE_ADDR'] ) : '';

        if ( ! filter_var( $remote, FILTER_VALIDATE_IP ) ) {
            return '0.0.0.0';
        }

        $key = isset( self::HEADERS[ $trusted_header ] ) ? self::HEADERS[ $trusted_header ] : '';

        // The header counts only when the connection itself comes from a proxy we trust.
        if ( '' !== $key && ! empty( $server[ $key ] ) && self::peer_is_trusted( $remote, $trusted_proxies ) ) {
            $client = self::from_chain( (string) $server[ $key ] );

            if ( '' !== $client ) {
                return $client;
            }
        }

        return $remote;
    }

    private static function peer_is_trusted( $remote, array $trusted_proxies ) {
        $remote = self::unmap_ipv4( $remote );

        foreach ( $trusted_proxies as $range ) {
            if ( self::cidr_match( $remote, (string) $range ) ) {
                return true;
            }
        }

        // With no list, only a proxy inside your own network is believed. A load
        // balancer that connects from a public address has to be listed.
        return empty( $trusted_proxies ) && self::is_own_network( $remote );
    }

    // In "a, b, c" the visitor may have written a and b. Only c was added by the
    // proxy that connected to the site, so the chain is read from the right.
    public static function from_chain( $value ) {
        $entries = array_reverse( array_map( 'trim', explode( ',', $value ) ) );
        $nearest = '';

        foreach ( $entries as $entry ) {
            $address = self::unmap_ipv4( $entry );

            // Nothing to the left of a non-address can be told apart from what the visitor wrote.
            if ( ! filter_var( $address, FILTER_VALIDATE_IP ) ) {
                break;
            }

            // An address of your own network is one more proxy: pass over it.
            if ( ! self::is_own_network( $address ) ) {
                return $address;
            }

            if ( '' === $nearest ) {
                $nearest = $address;
            }
        }

        return $nearest;
    }

    // Written out on purpose: what the filter_var() range flags cover changes between PHP versions.
    public static function is_own_network( $address ) {
        $ranges = array(
            '10.0.0.0/8',
            '172.16.0.0/12',
            '192.168.0.0/16',
            '127.0.0.0/8',
            '169.254.0.0/16',
            '100.64.0.0/10',
            '::1/128',
            'fc00::/7',
            'fe80::/10',
        );

        foreach ( $ranges as $range ) {
            if ( self::cidr_match( $address, $range ) ) {
                return true;
            }
        }

        return false;
    }

    // An IPv4 address written as IPv6 (::ffff:203.0.113.7), as the IPv4 address it is.
    public static function unmap_ipv4( $address ) {
        if ( ! filter_var( $address, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) ) {
            return $address;
        }

        $packed = inet_pton( $address );

        if ( false !== $packed && 16 === strlen( $packed ) && str_repeat( "\0", 10 ) . "\xff\xff" === substr( $packed, 0, 12 ) ) {
            $ipv4 = inet_ntop( substr( $packed, 12 ) );

            return false === $ipv4 ? $address : $ipv4;
        }

        return $address;
    }

    // A range without a mask is one address.
    private static function cidr_match( $address, $range ) {
        $parts  = explode( '/', $range, 2 );
        $subnet = $parts[0];

        if ( ! filter_var( $address, FILTER_VALIDATE_IP ) || ! filter_var( $subnet, FILTER_VALIDATE_IP ) ) {
            return false;
        }

        $address_bin = inet_pton( $address );
        $subnet_bin  = inet_pton( $subnet );

        if ( strlen( $address_bin ) !== strlen( $subnet_bin ) ) {
            return false;
        }

        $max  = 8 * strlen( $address_bin );
        $bits = isset( $parts[1] ) ? (int) $parts[1] : $max;

        if ( ( isset( $parts[1] ) && ! ctype_digit( $parts[1] ) ) || $bits > $max ) {
            return false;
        }

        $bytes = intdiv( $bits, 8 );
        $rest  = $bits % 8;

        if ( substr( $address_bin, 0, $bytes ) !== substr( $subnet_bin, 0, $bytes ) ) {
            return false;
        }

        if ( 0 === $rest ) {
            return true;
        }

        $mask = ( 0xFF << ( 8 - $rest ) ) & 0xFF;

        return ( ord( $address_bin[ $bytes ] ) & $mask ) === ( ord( $subnet_bin[ $bytes ] ) & $mask );
    }
}
```

Cells worth having in that test, each of which fails with one specific mistake:

| Request | Expected | Fails if |
|---|---|---|
| Trusted header set, public peer not in the list, `X-Forwarded-For: 1.2.3.4` | the peer address | the header is honoured without checking the peer |
| Peer `10.0.0.5`, `X-Forwarded-For: 127.0.0.1, 8.8.8.8, 203.0.113.9` | `203.0.113.9` | the first entry is taken |
| Peer `10.0.0.5`, `X-Forwarded-For: 203.0.113.9, 10.0.0.7` | `203.0.113.9` | an inner proxy of your own network is taken as the visitor |
| Peer `10.0.0.5`, `X-Forwarded-For: 1.2.3.4, ::ffff:203.0.113.7` | `203.0.113.7` | mapped addresses are classified with `filter_var()` flags |
| A list is configured, peer `10.0.0.5` is not in it | the peer address | "own network" is still believed when a list exists |

### What changes between PHP versions

The first version of the fix told public addresses from private ones with `FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE`. What those flags cover is not stable. Measured on PHP 7.4.30 and 8.5.3:

| Address | PHP 7.4 | PHP 8.5 |
|---|---|---|
| `::ffff:203.0.113.7`, a public IPv4 written as IPv6, which is how a dual-stack proxy writes it | public | reserved |
| `::ffff:10.0.0.5`, a private IPv4 written as IPv6 | public | reserved |
| `2001:db8::1`, the documentation range | reserved | public |
| `100.64.0.1`, the shared space carriers use inside their networks | public | public |

So on the newer PHP a real visitor behind a dual-stack proxy was passed over as "reserved" and the entry the visitor wrote won again. That is why the ranges that decide are written by hand, a mapped address is converted to IPv4 before it is classified, and the test runs on both ends of the PHP range.

### Validate the proxy list when it is saved

The list is a security setting. Accept exact addresses and CIDR ranges, reject wildcards, and reject a prefix so wide that it trusts the whole internet (`0.0.0.0/0`, `::/0`, a `/1`). A resolver honours what the list says.

## Caches: the key comes from what the page is generated with

A page cache that chooses which copy to store or serve from a header the render ignores can be poisoned by anyone.

```php
// WRONG: the variant is chosen by a header that WordPress itself does not read
$variant = ( 'https' === ayudawp_request_header( 'X-Forwarded-Proto' ) ) ? 'https' : 'http';

// CORRECT: the variant comes from the same call the page is rendered with,
// and a header that contradicts it only keeps the request out of the cache
$variant = is_ssl() ? 'https' : 'http';

if ( ayudawp_proxy_contradicts_wordpress() ) {
    return;   // serve uncached, store nothing
}
```

In the WRONG version, a plain http request carrying `X-Forwarded-Proto: https` stored, as the https copy, a page whose assets were all `http://`. Every https visitor then received it with mixed content blocked. WordPress builds its URLs from `is_ssl()`, which does not read that header, so the cache and the render disagreed about what the page was.

## Second-factor flows

### The shape without a shortcut

A sketch of the shape, not an implementation to copy. The `ayudawp_*` helpers are placeholders, and each one hides a decision that the list below spells out. Why an exemption for "a request that is already verifying" is a bypass is explained in `SKILL.md`.

```php
// Hooked after everything else that may refuse this login (core's multisite spam
// check runs at 99), because the verification path below does not run those again.
add_filter( 'authenticate', 'ayudawp_require_second_factor', 100, 3 );
function ayudawp_require_second_factor( $user, $username, $password ) {
    if ( ! $user instanceof WP_User || ! ayudawp_user_has_second_factor( $user ) ) {
        return $user;
    }

    // The one request that passes: nothing was submitted, and the user is the owner
    // of a session cookie that validates. That session passed the second factor when
    // it was created. Without this, opening wp-login.php while logged in asks again.
    if ( '' === (string) $username && '' === (string) $password && (int) wp_validate_auth_cookie() === $user->ID ) {
        return $user;
    }

    // Wrong codes are counted per user, across pending verifications.
    if ( ayudawp_code_attempts( $user->ID ) >= 5 ) {
        return new WP_Error( 'ayudawp_2fa_locked', __( 'Too many wrong codes. Try again later.', 'text-domain' ) );
    }

    ayudawp_start_pending_verification( $user->ID );   // server-side, tied to a random token in a cookie

    return new WP_Error( 'ayudawp_2fa_required', __( 'Enter your verification code.', 'text-domain' ) );
}

// The verification form authenticates on its own path: it checks the code and
// issues the cookie itself, without going back through wp_authenticate().
add_action( 'login_form_ayudawp_2fa', 'ayudawp_handle_second_factor' );
function ayudawp_handle_second_factor() {
    $user_id = ayudawp_get_pending_user_id();   // from the server-side record, never from the request
    $user    = $user_id ? get_user_by( 'id', $user_id ) : false;

    if ( ! $user ) {
        wp_safe_redirect( wp_login_url() );
        exit;
    }

    if ( ! isset( $_SERVER['REQUEST_METHOD'] ) || 'POST' !== $_SERVER['REQUEST_METHOD'] ) {
        ayudawp_render_code_form();   // login_header(), the form with its nonce, login_footer()
        exit;
    }

    if ( ! isset( $_POST['_wpnonce'] )
        || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), 'ayudawp_2fa_verify' )
    ) {
        wp_safe_redirect( wp_login_url() );
        exit;
    }

    // Count the attempt before checking the code, in one atomic step, and read back
    // the new total. Checking first lets parallel requests all get past the cap
    // before any of them is recorded.
    if ( ayudawp_count_code_attempt( $user->ID ) > 5 ) {
        ayudawp_clear_pending_verification();
        wp_safe_redirect( wp_login_url() );
        exit;
    }

    $code = isset( $_POST['ayudawp_code'] ) ? sanitize_text_field( wp_unslash( $_POST['ayudawp_code'] ) ) : '';

    if ( ! ayudawp_verify_code( $user->ID, $code ) ) {
        wp_safe_redirect( add_query_arg( 'action', 'ayudawp_2fa', wp_login_url() ) );
        exit;
    }

    ayudawp_clear_pending_verification();
    ayudawp_clear_code_attempts( $user->ID );

    wp_set_auth_cookie( $user->ID, false );
    // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- wp_login is a core hook, fired here because this path does not go through wp_signon().
    do_action( 'wp_login', $user->user_login, $user );

    wp_safe_redirect( admin_url() );
    exit;
}
```

### What a complete implementation still has to get right

Every item comes from a real flaw: most of them from a second factor that had already been "fixed" once, and the first and the last from earlier drafts of the sketch above.

- **Wrong codes are counted per user, across pending verifications, before the code is checked, and in one atomic step.** Each part closes a different hole. A cap that lives in the pending verification is reset by typing the password again, which hands whoever has the password an unlimited number of guesses at a six-digit code. Checking the cap first and counting afterwards lets parallel requests all pass before any of them is recorded. And a count kept by reading a transient, adding one and writing it back loses increments under the same parallel requests: use a single SQL `UPDATE`, or `wp_cache_incr()` on a persistent object cache. The simplest design is to record each attempt in the same limiter that counts wrong passwords, and clear it on success.
- **A correct password that is refused to ask for the code looks like a failed login.** `wp_authenticate()` fires `wp_login_failed` for every `WP_Error` except an empty username or password. If your `authenticate` filter returns its own error to request the second factor, a login limiter counts one failed attempt for each correct password, and a user who mistypes the code a couple of times ends up locked out by their own correct password. The limiter has to recognise the error codes the plugin issues itself.
- **The verification path runs none of the other `authenticate` callbacks.** It issues the cookie itself, so whatever else may refuse a login has to have run before the pending verification starts. Hook the filter after them: core's multisite spam check runs at priority 99.
- **The only request that passes without a code is a session that validates.** Letting through every request with no credentials is not enough of a test, because another plugin may authenticate without credentials (single sign-on, a magic link). Compare the user with `wp_validate_auth_cookie()`, and decide on purpose whether those other logins need the factor.
- **REST and XML-RPC have no form to show.** Refuse there without creating a pending verification. An application password is a factor of its own and should not be asked for a second one.
- **The pending verification is found by a random token the visitor holds in a cookie, never by IP address.** Looking it up by address handed one user's pending state to another user behind the same proxy.
- **The handler serves the form too.** On a GET it prints the form and ends the request. A handler that only accepts a POST sends every wrong code back to the password screen. And every path out of it ends with `exit`: a bare `return` falls through to `wp-login.php`, which calls `wp_signon()`.
