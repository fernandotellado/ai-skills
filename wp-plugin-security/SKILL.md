---
name: wp-plugin-security
description: "Security guidelines for WordPress plugin development: sanitization, validation, escaping (PHP and admin JavaScript), nonces, capabilities over objects, multisite boundaries, SQL injection, XSS, CSRF, request headers and the client IP behind proxies, authentication shortcuts, registered meta in REST, and secrets stored in the database. Bundles a release security gate: a procedure and three scripts to run before every release. Use it whenever you write or review plugin code that handles user input, prints dynamic output, registers AJAX or REST endpoints or meta, checks permissions, reads request headers or IP addresses, touches login or two-factor flows, writes files shared by a network, stores copies of configuration, or suppresses PHPCS security sniffs, and whenever a release, a security audit or a reply to the wordpress.org security review is being prepared, even if nobody says the word security. Based on official WordPress Developer Resources and on post-incident reviews of CVE-2026-81754."
compatibility: "WordPress 6.0+ / PHP 7.4+. Applies to plugins, themes, and custom code. The bundled scripts need Node.js, PHP CLI with mbstring, and Python 3."
license: GPL-2.0-or-later
metadata:
  author: fernando-tellado
  version: "1.3"
---

# WordPress plugin security

## When to use

Use this skill when:

- Developing new WordPress plugins or themes
- Reviewing existing code for security vulnerabilities
- Handling user input (forms, AJAX, REST API)
- Outputting dynamic content to the browser
- Interacting with the database
- Creating admin pages or settings
- Implementing AJAX or REST endpoints
- Processing file uploads

### Scope: audit the surface, not the diff

Reviewing only what changed is how a flaw survives for years while every release passes its security gate. Two rules make the difference:

- **If the change touches an escaper, a validator, a capability check or an `is_*_request()` helper, the unit of review is the whole function and every one of its callers**, not the lines of the diff. Ask "does this function do the right thing for all the contexts it is used in?", not "is this new value handled correctly?". In the incident behind these notes, a review looked at exactly the broken escaper, named it in the release notes and approved it, because it followed the path of the new value (which went to text position) instead of auditing the function and its other eleven uses in attribute position.
- **A review that only follows new data cannot find old flaws.** Rotate: each review takes one whole subsystem and reads it end to end, even if nothing in it changed. And state in writing what the review did **not** cover, so the next one starts there instead of repeating the same blind spot.
- **Checks catch regressions, not original design.** A flaw that was born with the feature never appears in a diff, and no mechanical check has a reason to see it. Whenever a module is touched, ask two questions by hand: which data in the request is chosen by the caller, and which security decisions are taken with it. And when a pattern is learned, sweep the whole codebase for it, not only the place where it showed up.
- **Repairing something that never worked wakes up what was asleep behind it.** A loop that did not iterate, a deletion that did not delete: once it works, every latent flaw in what that code touches becomes real. Audit the revived function as if it were new, and re-read the list of deferred issues, because "this change does not make it worse" stops being true when the change makes a deferred path run for the first time.

## Reference files

The sections below carry the rules. The detail, the longer recipes and the cases behind them are in four files. Load the one that matches the code in front of you.

| File | Read it when |
|------|--------------|
| `references/request-data-and-client-ip.md` | The plugin reads request headers, resolves the visitor's IP address, exempts some requests from a check, serves cached pages, or adds a step to the login |
| `references/registered-meta-and-core-filters.md` | It registers meta, or returns a value through `pre_get_document_title`, `wp_title` or another filter whose result core prints |
| `references/secrets.md` | It stores, exports, emails or diffs configuration files or settings |
| `references/release-gate.md` | A release, a security audit or a reply to the wordpress.org security review is being prepared |

## Release security gate

Before every release, and whenever a security audit is asked for, run the gate in `references/release-gate.md`. It is a procedure with three depths (ordinary change, sensitive change, security plugin or published vulnerability), the mechanical checks with their commands, the questions to answer in writing and the template of the release note.

Two things it rests on: a confirmed finding of medium severity or higher blocks the release, and a gate without a number has not been passed. The release note carries figures and says what was not looked at.

It bundles three scripts. Run `bash scripts/selftest.sh` once in a new environment before trusting them (`PHP=/path/to/php` if PHP is not on the `PATH`).

| Script | Use |
|--------|-----|
| `scripts/test-escapers.js <dir>` | Fails when a JavaScript escaper that does not encode quotes is used inside an attribute |
| `scripts/audit-suppressions.php <dir>` | Lists suppressions of security and SQL sniffs by risk, and fails when any of them has no written justification |
| `scripts/class-symbols.py <file.php>` | Fails when a class file uses a `self::` or `$this->` symbol it does not define, which `php -l` does not check |

## Core security principles

### The security mantra

```
Sanitize early
Escape late
Always validate
Never trust user input
```

### Key concepts

1. **Sanitization**: Clean/filter input data as soon as it is received
2. **Validation**: Verify data matches expected format/values (prefer over sanitization)
3. **Escaping**: Secure output data before rendering to prevent XSS
4. **Nonces**: Protect against CSRF attacks on forms and URLs
5. **Capabilities**: Verify user has permission to perform actions

## Sanitization

Sanitize input data immediately upon receipt. Use the most specific function available.

### Sanitization functions

| Function | Use case |
|----------|----------|
| `sanitize_text_field()` | Single-line text input (not URLs, paths or slugs: see the notes below) |
| `sanitize_textarea_field()` | Multi-line text input |
| `sanitize_email()` | Email addresses |
| `sanitize_file_name()` | File names |
| `sanitize_hex_color()` | Color values with hash |
| `sanitize_hex_color_no_hash()` | Color values without hash |
| `sanitize_html_class()` | HTML class names |
| `sanitize_key()` | Keys (lowercase alphanumeric, dashes, underscores) |
| `sanitize_meta()` | Meta values |
| `sanitize_mime_type()` | MIME types |
| `sanitize_option()` | Option values |
| `sanitize_sql_orderby()` | SQL ORDER BY clauses |
| `sanitize_title()` | Titles/slugs |
| `sanitize_title_with_dashes()` | URL-friendly titles |
| `sanitize_user()` | Usernames |
| `sanitize_url()` | URLs for storage |
| `wp_kses()` | HTML with allowed tags |
| `wp_kses_post()` | HTML allowed in posts |

### Sanitization example

```php
// Superglobals arrive slashed: wp_unslash() first, then the most specific sanitizer.

// Sanitize a text field from POST
$title = sanitize_text_field( wp_unslash( $_POST['title'] ?? '' ) );

// Sanitize email
$email = sanitize_email( wp_unslash( $_POST['email'] ?? '' ) );

// Sanitize URL for database storage
$url = sanitize_url( wp_unslash( $_POST['website'] ?? '' ) );

// Sanitize textarea
$description = sanitize_textarea_field( wp_unslash( $_POST['description'] ?? '' ) );
```

### Important notes on sanitization

- **Never use escape functions for sanitization** - they serve different purposes
- **And never use sanitization as escaping, which is the direction that actually causes breaches.** No `sanitize_*` function prepares a value for a specific output context. `sanitize_text_field()` strips tags, so the value *looks* clean, but it does not touch quotes: a "sanitized" string can still close an HTML attribute and open a new one. Sanitizing is for storing, escaping is for printing, and the correct escape depends on where the value lands. Any review reasoning that stops at "this is already sanitized" has not finished
- **`sanitize_text_field()` and `sanitize_textarea_field()` delete percent-encoded octets.** They do not decode `%C3%AD`, they remove it. A URL, a path, a `REQUEST_URI` or a slug with accents or in a non-Latin script, exactly as the browser writes it, loses its characters: `/categor%C3%ADa/` is stored as `/categora/`, and a slug in Cyrillic comes back empty. Read URLs with `esc_url_raw()` and paths and slugs with `wp_strip_all_tags()`, which WPCS accepts as sanitizers, behind an `is_string()` guard, because `esc_url_raw()` throws a `TypeError` on PHP 8 when it is handed an array
- **`sanitize_text_field()` collapses line breaks into spaces.** For anything multi-line use `sanitize_textarea_field()`
- When using `filter_var()`, always specify a sanitizing filter (not `FILTER_DEFAULT`)
- Process only the specific keys you need, not the entire `$_POST`/`$_GET` array

```php
// A URL keeps its bytes
$target = ( isset( $_POST['target'] ) && is_string( $_POST['target'] ) )
    ? esc_url_raw( wp_unslash( $_POST['target'] ) )
    : '';

// A path or a slug: tags out, %XX octets kept
$source = ( isset( $_POST['source'] ) && is_string( $_POST['source'] ) )
    ? wp_strip_all_tags( wp_unslash( $_POST['source'] ), true )
    : '';

// WRONG for both: the octets are gone before esc_url_raw() sees them
$target = esc_url_raw( sanitize_text_field( wp_unslash( $_POST['target'] ) ) );
```

To cut the path out of a full URL, do it by hand rather than with `wp_parse_url()`. On recent PHP versions `parse_url()` replaces some bytes of raw UTF-8 with an underscore: `/categoría/` comes out intact on PHP 7.4 and broken on PHP 8.5.

```php
// The path of a full URL, without parse_url()
$path = (string) preg_replace( '~^[a-z][a-z0-9+.\-]*://[^/?#]*~i', '', $url );
$path = substr( $path, 0, strcspn( $path, '?#' ) );
```

```php
// CORRECT: Specify sanitizing filter
$post_id = filter_input( INPUT_GET, 'post_id', FILTER_SANITIZE_NUMBER_INT );

// WRONG: No filter or FILTER_DEFAULT does not sanitize
$post_id = filter_input( INPUT_GET, 'post_id' ); // Insecure!
```

## Validation

Validation verifies data matches expected patterns. **Prefer validation over sanitization when possible.**

### Validation philosophies

#### Safelist (recommended)

Accept only known, trusted values:

```php
$allowed_values = array( 'draft', 'pending', 'publish' );

// Use strict comparison (third parameter = true)
if ( in_array( $status, $allowed_values, true ) ) {
    // Valid
} else {
    wp_die( 'Invalid status' );
}
```

#### Format detection

Test data format and reject if invalid:

```php
// Check alphanumeric only
if ( ! ctype_alnum( $data ) ) {
    wp_die( 'Invalid format' );
}

// Check against regex
if ( ! preg_match( '/^\d{5}(-\d{4})?$/', $zip_code ) ) {
    wp_die( 'Invalid ZIP code format' );
}
```

#### Type checking

Always use strict comparison (`===`) to prevent type juggling attacks:

```php
// CORRECT: Strict comparison
if ( 1 === $user_input ) {
    // Exactly integer 1
}

// WRONG: Loose comparison - "1 malicious" == 1 evaluates to true
if ( 1 == $user_input ) {
    // Vulnerable!
}
```

### Validation functions

| Function | Purpose |
|----------|---------|
| `is_email()` | Validate email format |
| `term_exists()` | Check if taxonomy term exists |
| `username_exists()` | Check if username exists |
| `validate_file()` | Validate file path (not existence) |
| `is_array()` | Check if value is array |
| `absint()` | Return absolute integer |
| `in_array( $val, $arr, true )` | Check value in array (strict) |

### Validation example

```php
function ayudawp_is_valid_us_zip( string $zip ): bool {
    if ( empty( $zip ) ) {
        return false;
    }

    if ( strlen( trim( $zip ) ) > 10 ) {
        return false;
    }

    if ( ! preg_match( '/^\d{5}(-?\d{4})?$/', $zip ) ) {
        return false;
    }

    return true;
}

// Usage
$zip = isset( $_POST['zip'] ) ? sanitize_text_field( wp_unslash( $_POST['zip'] ) ) : '';

if ( ayudawp_is_valid_us_zip( $zip ) ) {
    // Process valid ZIP
}
```

## Escaping

Escape output data **as late as possible**, immediately when echoing.

### Escaping functions

| Function | Use case |
|----------|----------|
| `esc_html()` | Text inside HTML elements |
| `esc_attr()` | Values inside HTML attributes |
| `esc_url()` | URLs in href, src attributes |
| `esc_url_raw()` | URLs for database storage (NOT escaping) |
| `esc_js()` | Inline JavaScript values |
| `esc_textarea()` | Content inside textarea |
| `esc_xml()` | XML content |
| `wp_kses()` | HTML with custom allowed tags |
| `wp_kses_post()` | HTML allowed in post content |
| `wp_kses_data()` | HTML allowed in comments |

### Escaping examples

```php
// Text inside HTML element
<h4><?php echo esc_html( $title ); ?></h4>

// URL in attribute
<a href="<?php echo esc_url( $link ); ?>">Link</a>

// Value in attribute
<input type="text" value="<?php echo esc_attr( $value ); ?>">

// Image source
<img src="<?php echo esc_url( $image_url ); ?>" alt="<?php echo esc_attr( $alt ); ?>">

// Inline JavaScript
<div onclick="doSomething('<?php echo esc_js( $param ); ?>')">

// Textarea content
<textarea><?php echo esc_textarea( $content ); ?></textarea>

// HTML content (preserves allowed HTML)
<div><?php echo wp_kses_post( $html_content ); ?></div>
```

### Escape late pattern

Always escape at the point of output:

```php
// WRONG: Escaping early
$url = esc_url( $url );
$text = esc_html( $text );
echo '<a href="' . $url . '">' . $text . '</a>';

// CORRECT: Escaping late
echo '<a href="' . esc_url( $url ) . '">' . esc_html( $text ) . '</a>';
```

### Echoing the return value of a helper that already escapes

The wordpress.org review team **rejects** `echo my_helper()` even if `my_helper()` already escapes every value internally. Late escaping must be visible at the `echo` call site. There are three valid options depending on what the helper returns:

```php
// HELPER RETURNS SIMPLE HTML (spans, links, basic tags)
// Wrap the echo in wp_kses_post():
echo wp_kses_post( ayudawp_render_status_badge( $post_id ) );

// HELPER RETURNS HTML THAT wp_kses_post() WOULD STRIP (forms, inputs, selects, buttons)
// Refactor the helper to echo directly (void return) and keep a string wrapper
// only for callers that genuinely need a return value (shortcodes that return).
function ayudawp_render_form( $args = array() ) {
    // ... uses esc_attr, esc_html, esc_url internally, but echoes the markup ...
    ?>
    <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
        <input type="text" name="ayudawp_field" value="<?php echo esc_attr( $args['value'] ); ?>">
    </form>
    <?php
}

function ayudawp_get_form_html( $args = array() ) {
    ob_start();
    ayudawp_render_form( $args );
    return ob_get_clean();
}

// Then the endpoint caller just calls the void version:
ayudawp_render_form( $args );        // No echo, no wrapping needed.

// And the shortcode caller uses the string wrapper:
return ayudawp_get_form_html( $args );

// HELPER RETURNS HTML WITH MIXED ALLOWED TAGS
// Use wp_kses() with an explicit allowlist:
$allowed = array(
    'select' => array( 'name' => true, 'id' => true, 'class' => true ),
    'option' => array( 'value' => true, 'selected' => true ),
);
echo wp_kses( wp_dropdown_pages( array( 'echo' => 0, /* ... */ ) ), $allowed );
```

The "escaped internally" comment with a `phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped` is a **rejection trigger** in the manual review, regardless of whether the helper does escape correctly. Refactor instead of suppressing.

### Escaping with localization

Use combined escape + localization functions:

```php
// Escape + translate
echo esc_html__( 'Hello World', 'text-domain' );
esc_html_e( 'Hello World', 'text-domain' );

// With context
echo esc_html_x( 'Post', 'noun', 'text-domain' );

// For attributes
echo esc_attr__( 'Submit', 'text-domain' );
esc_attr_e( 'Submit', 'text-domain' );
```

Available combined functions:
- `esc_html__()`, `esc_html_e()`, `esc_html_x()`
- `esc_attr__()`, `esc_attr_e()`, `esc_attr_x()`

### Important escaping notes

- **Never use `__()` or `_e()` without escaping** - they do not escape output
- **`esc_url_raw()` is NOT an escaping function** - it's for sanitizing URLs for storage
- Use `wp_kses_post()` or `wp_kses()` for HTML output, NOT `esc_html()` which strips HTML
- When escaping HTML attributes, escape the entire value, not parts

```php
// CORRECT: Escape the whole attribute value
echo '<div id="' . esc_attr( $prefix . '-box-' . $id ) . '">';

// WRONG: Escaping parts separately
echo '<div id="' . esc_attr( $prefix ) . '-box-' . esc_attr( $id ) . '">';
```

### A dynamic tag name needs an allowlist, not an escape

`esc_attr()` protects a value inside quotes. In tag-name position there are no quotes, and it does not encode spaces or `=`, so a value such as `img src=x onerror=alert(1)` builds a whole new element. No sniff flags it, because the output is "escaped".

```php
// WRONG: a shortcode attribute chooses the element
echo '<' . esc_attr( $tag ) . ' class="title">' . esc_html( $text ) . '</' . esc_attr( $tag ) . '>';

// CORRECT: only known tags reach that position
$allowed = array( 'h2', 'h3', 'h4', 'p', 'div', 'span' );
$tag     = in_array( $tag, $allowed, true ) ? $tag : 'div';

printf( '<%1$s class="title">%2$s</%1$s>', tag_escape( $tag ), esc_html( $text ) );
```

In one audited plugin this was XSS reachable by a contributor through a shortcode attribute, and an external scanner found it before the maintainer did.

### Values core does not escape for you

"Whoever prints it will escape it" does not finish a review, in the same way that "it is already sanitized" does not. The escaping of the document title is a callback that core hooks on the `document_title` filter at priority 10, not a line inside `wp_get_document_title()`. A value returned through the `pre_get_document_title` short-circuit, or added on `document_title` or `wp_title` at priority 10 or above, is printed raw inside `<title>`. Priority 10 is the default: only a callback hooked below it runs before core's `esc_html`. Escape it in the plugin.

The table of which entry points are escaped, the emitters and how to check it against the installed core: `references/registered-meta-and-core-filters.md`.

### Custom HTML escaping with wp_kses

```php
$allowed_html = array(
    'a'      => array(
        'href'  => array(),
        'title' => array(),
    ),
    'br'     => array(),
    'em'     => array(),
    'strong' => array(),
);

echo wp_kses( $user_html, $allowed_html );
```

## Nonces

Nonces protect against CSRF (Cross-Site Request Forgery) attacks.

### Creating nonces

```php
// In a URL
$url = wp_nonce_url( $base_url, 'delete-post_' . $post_id );

// In a form (echoes hidden fields)
wp_nonce_field( 'save-settings_' . $user_id, 'ayudawp_nonce' );

// Get nonce value only
$nonce = wp_create_nonce( 'my-action_' . $post_id );
```

### Verifying nonces

```php
// In admin screens (also checks referrer)
check_admin_referer( 'delete-post_' . $post_id, 'ayudawp_nonce' );

// In AJAX requests
check_ajax_referer( 'my-ajax-action', 'security' );

// Manual verification
if ( ! wp_verify_nonce( 
    sanitize_text_field( wp_unslash( $_POST['ayudawp_nonce'] ?? '' ) ), 
    'my-action_' . $post_id 
) ) {
    wp_die( 'Security check failed' );
}
```

### Nonce best practices

- Make action strings specific: `'delete-post_' . $post_id` not just `'delete'`
- Always sanitize nonce before verification:

```php
// CORRECT: Sanitize nonce input
if ( ! isset( $_POST['_wpnonce'] ) || 
     ! wp_verify_nonce( 
         sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), 
         'my_action' 
     ) 
) {
    wp_die( 'Security check failed' );
}
```

- Nonces have limited lifetime (default 24 hours, configurable)
- **Nonces alone are not sufficient** - always combine with capability checks
- Nonces are user-specific and session-specific

### Nonces on `$_GET` reads from your own redirects

When a form handler redirects back to the original page with a feedback flag (`?my_sent=1`, `?my_error=fields`, `?my_bulk_done=3`), the receiving code is reading `$_GET` and the security sniffer flags it. **Suppressing the sniffer with `phpcs:disable` is a rejection trigger** in the manual review, even though the data only exists because your own handler put it there.

The correct pattern is mutual nonce verification: the emitter generates a `wp_create_nonce()` and adds it to the redirect URL; the receiver verifies it before reading the flag and silently degrades if invalid.

```php
// EMITTER (form handler, bulk action handler, etc.)
function ayudawp_redirect_with_success() {
    $url = wp_get_referer() ? wp_get_referer() : home_url();
    $url = add_query_arg(
        array(
            'ayudawp_sent' => '1',
            '_wpnonce'     => wp_create_nonce( 'ayudawp_form_feedback' ),
        ),
        $url
    );
    wp_safe_redirect( $url );
    exit;
}

// RECEIVER (form renderer, admin notice, etc.)
$success = false;

if ( isset( $_GET['_wpnonce'] )
    && wp_verify_nonce(
        sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ),
        'ayudawp_form_feedback'
    )
) {
    $success = isset( $_GET['ayudawp_sent'] )
        && '1' === sanitize_text_field( wp_unslash( $_GET['ayudawp_sent'] ) );
}
```

Same pattern applies to "pre-fill" links built by your plugin (e.g. a WooCommerce My Account button that pre-fills an order number in a form): bind the nonce to the specific resource (`'ayudawp_prefill_' . $order_ref`) so an old bookmark or a guessed URL cannot trigger the prefill.

### Explicit `exit;` after redirect inside a nonce check

PHPCS and the manual review do not follow execution into helper functions. If your nonce check looks like this:

```php
// REJECTED: the helper does exit; internally, but the sniffer cannot tell.
if ( ! wp_verify_nonce( ... ) ) {
    ayudawp_redirect_with_error( 'nonce' );
}

// Code continues reading $_POST → sniffer flags "no nonce check found".
$name = sanitize_text_field( wp_unslash( $_POST['ayudawp_name'] ) );
```

Add a literal `exit;` even though the helper already exits:

```php
// ACCEPTED: the exit; is in the same scope as the read, sniffer is satisfied.
if ( ! isset( $_POST['ayudawp_nonce'] ) ) {
    ayudawp_redirect_with_error( 'nonce' );
    exit;
}

$nonce = sanitize_text_field( wp_unslash( $_POST['ayudawp_nonce'] ) );

if ( ! wp_verify_nonce( $nonce, 'ayudawp_submit_action' ) ) {
    ayudawp_redirect_with_error( 'nonce' );
    exit;
}
```

Splitting the `isset()` and the `wp_verify_nonce()` into two separate `if` blocks also helps: it makes the security boundary unambiguous to humans reading the diff.

### Explicit nonce verification even when the parent hook already verifies

Some WordPress and WooCommerce hooks already verify a nonce before firing (e.g. `woocommerce_process_product_meta` fires only after `woocommerce_meta_nonce` has been verified by core). The sniffer does not know that, and the manual review rejects callbacks that do not verify the nonce themselves.

```php
// REJECTED, even with a comment explaining that WC already verifies:
function ayudawp_save_product_meta( $post_id ) {
    // phpcs:disable WordPress.Security.NonceVerification.Missing
    $value = isset( $_POST['_ayudawp_excluded'] ) ? 'yes' : 'no';
    // phpcs:enable WordPress.Security.NonceVerification.Missing
    update_post_meta( $post_id, '_ayudawp_excluded', $value );
}

// ACCEPTED: belt-and-suspenders verification + explicit capability check.
function ayudawp_save_product_meta( $post_id ) {
    if ( ! isset( $_POST['woocommerce_meta_nonce'] )
        || ! wp_verify_nonce(
            sanitize_text_field( wp_unslash( $_POST['woocommerce_meta_nonce'] ) ),
            'woocommerce_save_data'
        )
    ) {
        return;
    }

    if ( ! current_user_can( 'edit_product', $post_id ) ) {
        return;
    }

    $value = isset( $_POST['_ayudawp_excluded'] ) ? 'yes' : 'no';
    update_post_meta( $post_id, '_ayudawp_excluded', $value );
}
```

### Modifying nonce lifetime

```php
add_filter( 'nonce_life', function() {
    return 4 * HOUR_IN_SECONDS;
} );
```

## User capabilities

Always verify user has permission before performing actions.

### Checking capabilities

```php
// Check current user capability
if ( ! current_user_can( 'edit_posts' ) ) {
    wp_die( 'You do not have permission to do this.' );
}

// Check capability for specific post
if ( ! current_user_can( 'edit_post', $post_id ) ) {
    wp_die( 'You cannot edit this post.' );
}

// Check if user is admin
if ( ! current_user_can( 'manage_options' ) ) {
    wp_die( 'Administrator access required.' );
}
```

### Common capabilities

| Capability | Role level |
|------------|------------|
| `read` | Subscriber+ |
| `edit_posts` | Contributor+ |
| `publish_posts` | Author+ |
| `edit_others_posts` | Editor+ |
| `manage_options` | Administrator **of each site** (see the multisite note below) |
| `edit_themes` | Administrator |
| `activate_plugins` | Administrator |
| `manage_network_options` | Network administrator (multisite only) |
| `manage_network_users` | Network administrator (multisite only) |

### `manage_options` on multisite is not what it looks like

On a network, `manage_options` is held by the administrator of **every** subsite, and those administrators are often customers, clients or colleagues who are deliberately not trusted with the whole install. So `manage_options` is the right capability for anything that belongs to one site, and the wrong one for anything shared by the network:

- `wp-config.php`, and the `.htaccess` or `robots.txt` at the document root
- network options, and any dump that follows `$wpdb->prefix` (on the main site that prefix matches every subsite table and the global user tables)
- user accounts, which on a network belong to the network and not to one site
- user meta, which lives in one table for the whole network: session tokens, application passwords, second-factor secrets. A per-site setting that acts on user meta acts on that user everywhere. In one audited plugin a per-site "limit concurrent sessions" option let a subsite administrator close the sessions a user had on other sites

The recipe, for anything in that list:

```php
// Shared resource: one site must not be able to rewrite what the whole network reads.
return is_multisite()
    ? current_user_can( 'manage_network_options' )
    : current_user_can( 'manage_options' );
```

```php
// Acting on somebody else's account. On single site an administrator always passes,
// so nothing changes there; on a network map_meta_cap denies a target this user does
// not administer, which is exactly the intent.
if ( ! current_user_can( 'edit_user', $user_id ) ) { ... }
```

This is not a corner case. In one audited plugin, five separate handlers guarded with `manage_options` let a subsite administrator download the network `wp-config.php` (auth salts and DB credentials), read another administrator's two-factor backup codes, revoke their sessions and force a password reset on them.

### Complete security check example

```php
function ayudawp_delete_item() {
    // 1. Check nonce
    if ( ! isset( $_POST['_wpnonce'] ) ||
         ! wp_verify_nonce( 
             sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), 
             'delete_item_' . absint( $_POST['item_id'] ?? 0 )
         )
    ) {
        wp_die( 'Security check failed' );
    }

    // 2. Validate the object id first: you cannot ask for permission over an
    //    object until you know which object it is.
    $item_id = absint( $_POST['item_id'] ?? 0 );
    if ( ! $item_id ) {
        wp_die( 'Invalid item ID' );
    }

    // 3. Check capability OVER THAT OBJECT, not in general.
    //    delete_posts is a primitive capability and answers "may this user
    //    delete posts at all?". delete_post is a meta capability and answers
    //    "may this user delete THIS post?", which is the question that matters
    //    when the id arrives in the request.
    if ( ! current_user_can( 'delete_post', $item_id ) ) {
        wp_die( 'You do not have permission to delete this item.' );
    }

    // 4. Perform action
    // ... delete logic here
}
```

**Primitive vs meta capabilities, and why this is the single most common authorization bug.** A handler that accepts an object id from the request and checks only a primitive capability is not authorizing anything: it is confirming that the caller is, broadly, the kind of user who does this sort of thing. `map_meta_cap()` exists to answer the specific question, and it is the one to use whenever an id travels in the request:

| Instead of | Use | Because |
|------------|-----|---------|
| `current_user_can( 'edit_posts' )` | `current_user_can( 'edit_post', $post_id )` | The caller may edit posts, but perhaps not this one |
| `current_user_can( 'delete_posts' )` | `current_user_can( 'delete_post', $post_id )` | Same, for deletion |
| `current_user_can( 'edit_users' )` | `current_user_can( 'edit_user', $user_id )` | On multisite this is the difference between a site and the whole network |
| `current_user_can( 'manage_options' )` | `current_user_can( 'edit_user', $user_id )` | `manage_options` says nothing about the target account |

Sanitizing the id with `absint()` is necessary and is not authorization. A perfectly sanitized id pointing at an object the caller may not touch is exactly the shape of a real-world privilege escalation, and the clean-looking `absint()` is what makes it read as safe.

## SQL injection prevention

### Use $wpdb->prepare()

Always use prepared statements for database queries:

```php
global $wpdb;

// Single value
$result = $wpdb->get_var( 
    $wpdb->prepare(
        "SELECT post_title FROM {$wpdb->posts} WHERE ID = %d",
        $post_id
    )
);

// Multiple values
$results = $wpdb->get_results(
    $wpdb->prepare(
        "SELECT * FROM {$wpdb->posts} WHERE post_status = %s AND post_author = %d",
        $status,
        $author_id
    )
);
```

### Placeholders

| Placeholder | Type |
|-------------|------|
| `%d` | Integer |
| `%f` | Float |
| `%s` | String |
| `%i` | Identifier (table/column name, WP 6.2+) |

### Arrays in queries

```php
// Build the placeholders inside the call, so nothing but the table name is interpolated
$ids = array( 1, 2, 3, 4, 5 );

$results = $wpdb->get_results(
    $wpdb->prepare(
        sprintf(
            "SELECT * FROM {$wpdb->posts} WHERE ID IN (%s)",
            implode( ',', array_fill( 0, count( $ids ), '%d' ) )
        ),
        $ids
    )
);
```

Do not build the list in a variable and interpolate it (`IN ( $placeholders )`). The value is safe, but `WordPress.DB.PreparedSQL.InterpolatedNotPrepared` flags any interpolated variable, and the only way to quiet it is to suppress a SQL sniff, which is exactly the kind of suppression you do not want in the tree.

### Queries that vary: one literal per variant

Write every query as a complete literal, with only `{$wpdb->table}` or `{$wpdb->prefix}literal_name` interpolated and every value through a placeholder. When the columns or the conditions vary, write one literal query per variant. It is verbose and it can be audited by reading it.

```php
// WRONG: a SQL fragment travels in a variable. The value comes from an internal map,
// so it is safe today, and Plugin Check still reports it as an error.
$allowed = array( 'status' => 'status', 'date' => 'created_at' );
$order   = $allowed[ $key ] ?? 'created_at';
$rows    = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}ayudawp_log ORDER BY {$order} DESC" );

// CORRECT: one literal per variant
if ( 'status' === $key ) {
    $rows = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}ayudawp_log ORDER BY status DESC" );
} else {
    $rows = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}ayudawp_log ORDER BY created_at DESC" );
}
```

- Plugin Check's data-flow sniff (`PluginCheck.Security.DirectDB.UnescapedDBParameter`) flags any PHP variable interpolated in a SQL string, whatever its origin
- A one-line `phpcs:ignore` does not cover a multi-line SQL string: the sniff reports on the line of the fragment, not on the line of the call
- For an identifier that has to be dynamic, use the `%i` placeholder (WordPress 6.2+)
- The only suppressions that belong on a query are `WordPress.DB.DirectDatabaseQuery.DirectQuery` and `.NoCaching`, which every direct query triggers. They are not security sniffs: add a cache, or justify them on the exact line of the `$wpdb` call

### Use WordPress functions when possible

Prefer WordPress API functions over direct SQL:

```php
// PREFERRED: Use WordPress functions
update_post_meta( $post_id, 'my_key', $value );
get_option( 'my_option' );
WP_Query for post queries

// AVOID: Direct SQL unless necessary
$wpdb->query( "INSERT INTO..." );
```

## Common vulnerabilities

### XSS (Cross-Site Scripting)

**Prevention**: Escape all output

```php
// Vulnerable
echo $user_input;

// Secure
echo esc_html( $user_input );
```

### CSRF (Cross-Site Request Forgery)

**Prevention**: Use nonces + capability checks

```php
// In form
wp_nonce_field( 'my_action', 'my_nonce' );

// On submission
check_admin_referer( 'my_action', 'my_nonce' );
if ( ! current_user_can( 'manage_options' ) ) {
    wp_die( 'Unauthorized' );
}
```

### SQL Injection

**Prevention**: Use prepared statements

```php
// Vulnerable
$wpdb->query( "DELETE FROM table WHERE id = " . $_GET['id'] );

// Secure
$wpdb->query( 
    $wpdb->prepare( "DELETE FROM table WHERE id = %d", absint( $_GET['id'] ) )
);
```

## File handling security

### Use WordPress upload functions

```php
// CORRECT: Use wp_handle_upload
$uploaded = wp_handle_upload( $_FILES['my_file'], array( 
    'test_form' => false 
) );

// WRONG: Direct move_uploaded_file
move_uploaded_file( $_FILES['my_file']['tmp_name'], $destination );
```

### Never allow unfiltered uploads

```php
// NEVER DO THIS
define( 'ALLOW_UNFILTERED_UPLOADS', true );

// Instead, use the upload_mimes filter for the specific types you need
add_filter( 'upload_mimes', function( $mimes ) {
    $mimes['json'] = 'application/json';
    return $mimes;
} );
```

Do not enable SVG this way. An SVG is a document that can carry `<script>` and event handlers, so allowing the type hands stored XSS to everybody who can upload. If a plugin needs SVG, every file has to go through a maintained sanitizer on upload and the capability has to be restricted.

### Validate file types

```php
$allowed_types = array( 'image/jpeg', 'image/png', 'image/gif' );
$file_type = wp_check_filetype( $filename );

if ( ! in_array( $file_type['type'], $allowed_types, true ) ) {
    wp_die( 'Invalid file type' );
}
```

## Direct file access prevention

Add to all PHP files that could execute code:

```php
<?php
// Prevent direct file access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
```

## AJAX security

### Register AJAX handlers

```php
// For logged-in users
add_action( 'wp_ajax_my_action', 'ayudawp_ajax_handler' );

// For non-logged-in users (if needed)
add_action( 'wp_ajax_nopriv_my_action', 'ayudawp_ajax_handler' );

function ayudawp_ajax_handler() {
    // 1. Verify nonce
    check_ajax_referer( 'my_ajax_nonce', 'security' );

    // 2. Check capabilities
    if ( ! current_user_can( 'edit_posts' ) ) {
        wp_send_json_error( 'Unauthorized', 403 );
    }

    // 3. Sanitize input
    $data = sanitize_text_field( wp_unslash( $_POST['data'] ?? '' ) );

    // 4. Process and respond
    wp_send_json_success( array( 'result' => $data ) );
}
```

### JavaScript side

```php
// Localize script with nonce
wp_localize_script( 'my-script', 'myAjax', array(
    'ajaxurl' => admin_url( 'admin-ajax.php' ),
    'nonce'   => wp_create_nonce( 'my_ajax_nonce' ),
) );
```

```javascript
// AJAX call
jQuery.post( myAjax.ajaxurl, {
    action: 'my_action',
    security: myAjax.nonce,
    data: 'my data'
}, function( response ) {
    // Handle response
});
```

### Escaping in JavaScript

The PHP side of a plugin is usually reviewed. The admin JavaScript that rebuilds the same tables over AJAX usually is not, and it is a real output context with the same rules. This is where CVE-2026-81754 lived.

**A helper built on the DOM does not encode quotes.** All three of these are correct for text and unsafe inside an attribute:

```javascript
// WRONG in attribute position: encodes & < > and nothing else
function escapeHtml( text ) {
    var div = document.createElement( 'div' );
    div.textContent = text;
    return div.innerHTML;
}
var escHtml = function ( s ) { return jQuery( '<span>' ).text( s ).html(); };   // same
var escHtml = function ( s ) { return document.createTextNode( s ).textContent; }; // same
```

```javascript
// CORRECT everywhere, including attributes
function escAttr( text ) {
    if ( text === null || typeof text === 'undefined' ) {
        return '';
    }
    return String( text )
        .replace( /&/g, '&amp;' )
        .replace( /</g, '&lt;' )
        .replace( />/g, '&gt;' )
        .replace( /"/g, '&quot;' )
        .replace( /'/g, '&#039;' );
}
```

Three rules that follow:

1. **Either one escaper that is safe in attribute position, or two with explicit names**, `escHtml()` for text and `escAttr()` for attributes. One helper called `escapeHtml()` used in both places is a bug waiting for its context.
2. **The escape is chosen by the output context, not by trust in the origin.** A translated string in an attribute goes through `escAttr()` exactly like user data does, because what changes tomorrow is who calls the function, not what the function escapes. A renderer that is safe today because its only caller is the user's own AJAX response becomes stored XSS the day somebody moves that render to a paginated list fed from the database, and no escaping review will catch it: the escaper did not change.
3. **Escaping quotes does not make a URL safe.** A value that lands in `href=` or `src=` still needs its scheme validated, or `javascript:` and `data:` walk straight through.

Worth knowing when reviewing the server side of the same feature: `esc_attr( wp_json_encode( $data, JSON_HEX_APOS | JSON_HEX_QUOT ) )` is the correct way to put a JSON payload into an attribute in PHP. A plugin can be perfectly safe on that path and vulnerable on the JavaScript one that rebuilds the same row.

### Security decisions must not rest on request data

A check that decides "this request is already past authentication" by reading something the caller sends is not a check. The attacker sends it too.

```php
// WRONG: the action travels in the request, so anyone can claim to be mid-verification
private function is_verification_request() {
    return 'my_2fa' === ( $_REQUEST['action'] ?? '' );
}
```

The tempting repair is to ask for more evidence: the action, the nonce of the verification form and a pending session stored server-side for that user. It looks airtight and it is not. **Version 1.2 of this skill showed the function below as the correct pattern. It is a bypass.**

```php
// STILL WRONG: whoever knows the password already holds all three
private function is_verification_request( $user = null ) {
    if ( 'my_2fa' !== ( $_REQUEST['action'] ?? '' ) ) {
        return false;
    }
    if ( ! isset( $_POST['_wpnonce'] )
        || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), 'my_2fa_verify' ) ) {
        return false;
    }
    $pending = $this->get_pending_user_id();   // stored server-side when the password was accepted
    return $pending && ( ! $user instanceof WP_User || $pending === (int) $user->ID );
}
```

**Justify an authorization shortcut by listing who holds each value it depends on.** Here the nonce is printed on the form that is served to the visitor with the pending session, and the pending session is created by that same visitor when they submit the password. All three values are in the hands of the one attacker a second factor exists to stop: the one who has the password.

On `wp-login.php` the hole does not show, because the custom action is routed to a `login_form_*` handler that ends the request before the `authenticate` filter can take the shortcut. Any other login form that calls `wp_signon()` (a shop's account page, a membership or LMS plugin) does reach the filter with those three values, and the login completes with no code. This was reproduced against a published release.

The repair is to have no exemption that rests on what the request says about itself. The `authenticate` filter never lets through a user who needs a second factor because of an action, a nonce or a pending record: it records a pending verification server-side and returns a `WP_Error`. The verification form checks the code in its own `login_form_*` handler and issues the cookie itself with `wp_set_auth_cookie()`, without going back through `wp_authenticate()`, so nothing legitimate ever needed the shortcut. The one request that does pass without a code, a session whose cookie validates, is checked against the server and not claimed. A sketch of both halves, and the list of what a complete implementation still has to get right, are in `references/request-data-and-client-ip.md`.

**Test every fix of a login bypass from a form that is not `wp-login.php`.** Shop, membership and LMS plugins bring their own login form, and that is where this one was open.

The same idea covers user agents, referers and any `X-Forwarded-*` header: they are claims, not evidence. If a plugin opens something because the client says it is Googlebot, it opens for everybody who says it. **A value the caller chooses is good for logging and for display, never for deciding.**

When an exemption is legitimate, scope it to what it exists for. A user-agent allowlist that spares a management service from the bad-bot rules spares it from those rules and from nothing else: in one audited firewall it returned before the IP blocklist and every pattern rule.

**The client address is request data too**, and blocklists, allowlists, rate limits and lockouts all act on it:

- One resolver for the whole plugin. Two ways of resolving the address means one good one and one exploitable one
- A proxy header (`X-Forwarded-For`, `CF-Connecting-IP`, `X-Real-IP`) counts only when `REMOTE_ADDR` is a proxy you trust: a list the administrator declared or, with no list, your own network. A setting that says "this site is behind a proxy" is not enough, because anyone who reaches the origin directly forges the header
- Read `X-Forwarded-For` from the right. The proxy appends the address it saw and keeps in front whatever the visitor sent, so the first entry is the one the visitor chose
- Do not tell public from private addresses with the `filter_var()` range flags: what they cover changes between PHP versions

And for any cache: the key of a stored copy comes from what the page is generated with (`is_ssl()`), never from a header the render ignores (`X-Forwarded-Proto`). A header that contradicts the render can only keep the request out of the cache.

A tested resolver, the PHP version table, the cache case and the side effects of second-factor flows: `references/request-data-and-client-ip.md`.

**And when a check fails inside a `login_form_*` handler, end the request.** A bare `return` hands control back to `wp-login.php`, which falls through to its default case and calls `wp_signon()`, completing the login the check was supposed to stop:

```php
if ( ! $nonce_is_valid ) {
    wp_safe_redirect( wp_login_url() );
    exit;   // not: return;
}
```

## REST API security

```php
register_rest_route( 'myplugin/v1', '/items', array(
    'methods'             => 'POST',
    'callback'            => 'ayudawp_create_item',
    'permission_callback' => function() {
        return current_user_can( 'edit_posts' );
    },
    'args'                => array(
        'title' => array(
            'required'          => true,
            'sanitize_callback' => 'sanitize_text_field',
            'validate_callback' => function( $value ) {
                return ! empty( $value );
            },
        ),
    ),
) );
```

For a route that takes an id, the `permission_callback` asks about that object, `current_user_can( 'edit_post', $request['id'] )`, for the same reason as in any other handler. And `'permission_callback' => '__return_true'` is a decision to make the route public: write down why next to it.

### A registered meta is a REST write endpoint

`register_post_meta()`, `register_term_meta()` and `register_meta()` with `show_in_rest` have no route of their own, so they are easy to leave out when listing entry points. Without a `sanitize_callback` the value is stored exactly as the request sent it, even when the classic meta box of the same plugin sanitizes. `auth_callback` decides who may write, not what is written.

Name a sanitizer that exists, literally, for every key. A PHP internal such as `'trim'` or `'intval'` as the callback throws `ArgumentCountError` on every write on PHP 8, because core calls it with extra arguments. And `'type' => 'integer'` is enforced by the REST schema only: `update_post_meta()` stores whatever it is given. The full table and how to list the registrations: `references/registered-meta-and-core-filters.md`.

## Secrets in copies, diffs and exports

A secret does not change places unless somebody decides it. Before storing, exporting or emailing a copy of a file or of a setting, ask what it carries inside. An integrity scanner that kept the whole `wp-config.php` in an option, to show which lines had changed, put the database password and the salts within reach of anyone who could read the database or a backup of it.

- Never keep `wp-config.php`, `.env`, `.htpasswd` or a database dump whole
- Hash the complete file, redact both sides of a diff the same way, and check the redacted result against the values in force: if one survived, store nothing
- Write "sensitive" lists the other way round, as the list of what may be shown. A list of secret names is never complete: a redaction keyed on names missed 10 of 15 real shapes

The recipe and the shapes to test against: `references/secrets.md`.

## Time comparisons

Write and compare the timestamps of a table in one zone. `current_time( 'mysql' )` returns site time. `gmdate()` and `current_time( 'mysql', true )` return UTC. Mixing them breaks every protection that asks "has this expired yet?".

In one audited plugin the last attempt was stored in site time and the lockout expiry in UTC for 73 releases. The login lockout only worked on sites set to UTC: ahead of UTC the lockout never looked active, behind it the attempts were never counted. No test saw it, because they all checked that the row was written and none that the next request was blocked.

```php
// WRONG: two zones in values that are compared with each other
$attempted_at = current_time( 'mysql' );                         // site time
$is_locked    = $row->lockout_until > gmdate( 'Y-m-d H:i:s' );   // UTC

// CORRECT: UTC for everything that is compared, converted only for display
$attempted_at = current_time( 'mysql', true );
$is_locked    = $row->lockout_until > gmdate( 'Y-m-d H:i:s' );
```

A time-based protection is tested when it has been seen acting on the next request, on a site whose timezone is not UTC.

## Code review checklist

### Input handling

- [ ] All `$_POST`, `$_GET`, `$_REQUEST` values are sanitized
- [ ] All `$_FILES` uploads use `wp_handle_upload()`
- [ ] Database queries use `$wpdb->prepare()`
- [ ] Every query is a complete literal: no SQL fragment travels in a variable
- [ ] Type casting used where appropriate (`absint()`, `(int)`, etc.)
- [ ] URLs are read with `esc_url_raw()` and paths and slugs with `wp_strip_all_tags()`, never with `sanitize_text_field()`, which deletes `%XX` octets
- [ ] Every registered meta exposed in REST names a sanitizer that exists

### Output handling

- [ ] All dynamic output is escaped
- [ ] Correct escape function used for context (html/attr/url/js)
- [ ] Escaping happens at output time (late escaping)
- [ ] Translation functions are escaped (`esc_html__()` not `__()`)
- [ ] No dynamic value reaches tag-name position without an allowlist
- [ ] Values returned through `pre_get_document_title`, or hooked on `document_title` or `wp_title` at priority 10 or above, are escaped in the plugin

### Authentication & authorization

- [ ] Nonces used on all forms and state-changing URLs
- [ ] Nonces verified before processing actions
- [ ] Capability checks performed before actions
- [ ] Both nonce AND capability checked (not just one)
- [ ] **Is that capability enough for what the handler touches?** Not "is there a check" but "does this check answer the right question"
- [ ] Any object id arriving in the request is authorized **over that object** (`edit_post`, `edit_user`), not with a primitive capability
- [ ] Anything shared by a multisite network (`wp-config.php`, root `.htaccess` or `robots.txt`, network options, dumps following `$wpdb->prefix`, user accounts) asks for `manage_network_options` or `manage_network_users`, not `manage_options`
- [ ] No security decision rests on request data alone (`$_REQUEST['action']`, user agent, `X-Forwarded-*`)
- [ ] Any shortcut that skips a check has been justified by listing who holds each value it depends on, and a login-bypass fix has been tested from a form that is not `wp-login.php`
- [ ] A failed check inside a `login_form_*` handler ends the request with `exit`, never a bare `return`
- [ ] The client IP comes from one resolver; a proxy header is honoured only when `REMOTE_ADDR` is a trusted proxy, and `X-Forwarded-For` is read from the right
- [ ] No cache key or variant is chosen from a header the render ignores
- [ ] Per-site settings do not act on network-wide user meta (sessions, application passwords, second-factor secrets)

### Output in JavaScript

- [ ] Every escaper in the plugin's JS encodes quotes, or is named so that its context is explicit (`escHtml` / `escAttr`)
- [ ] No value reaches attribute position through a helper built on `textContent` / `innerHTML`
- [ ] Values landing in `href=` or `src=` have their scheme validated, not just their quotes escaped

### General

- [ ] No `ALLOW_UNFILTERED_UPLOADS`
- [ ] Direct file access prevented with `ABSPATH` check
- [ ] No `error_reporting()` in production code
- [ ] No timezone changes with `date_default_timezone_set()`
- [ ] Uses WordPress HTTP API, not raw cURL
- [ ] Uses `wp_enqueue_*` for scripts/styles
- [ ] No copy of `wp-config.php`, `.env`, `.htpasswd` or a database dump is stored, exported or emailed whole
- [ ] Timestamps that are compared are written in one zone, and time-based protections were seen acting on the next request on a site that is not in UTC

### Before a release

- [ ] The release gate was run at the depth the change calls for (`references/release-gate.md`)
- [ ] Its figures are written down, together with what was not looked at
- [ ] Each tool's output was compared with the previous release, and every new result explained

### wordpress.org review hardening

- [ ] No `phpcs:ignore` / `phpcs:disable` on any `WordPress.Security.*` sniff
- [ ] No `phpcs:ignore` on `EscapeOutput.OutputNotEscaped` — refactor instead
- [ ] Every `echo helper()` either wraps in `wp_kses_post()` / `wp_kses()` or the helper echoes directly
- [ ] Every `$_GET` read on a feedback flag is gated by an explicit `wp_verify_nonce()`
- [ ] Every nonce check has a literal `exit;` (or `return;`) after the redirect, in the same scope as the read
- [ ] Callbacks attached to hooks that "already verify a nonce" verify it themselves anyway
- [ ] `Reply-To` / `From` headers built from user input run through `sanitize_text_field()` + `sanitize_email()` before composition

## WPCS security sniffs

WordPress Coding Standards includes these security sniffs:

- `EscapeOutputSniff` - Verifies output is escaped
- `NonceVerificationSniff` - Verifies nonce checks
- `ValidatedSanitizedInputSniff` - Verifies input sanitization
- `SafeRedirectSniff` - Verifies safe redirects
- `PluginMenuSlugSniff` - Verifies menu slug safety

Run PHPCS with WordPress standards:

```bash
phpcs --standard=WordPress path/to/plugin
```

## Surviving the wordpress.org review

The plugin review team rejects more aggressively than PHPCS alone. Their reviewers do not read comments that justify a `phpcs:ignore` — they treat the suppression itself as a red flag. Aim for **zero security-sniff suppressions** in the codebase you submit.

### The automated security review

Since June 2026 every release of a plugin hosted on wordpress.org also goes through an automated security review, by several AI models together with Jetpack Scan, during a cooldown period before it reaches the update API. A release with a high risk score is blocked from distribution and all committers get an email with the findings.

A clean Plugin Check run predicts nothing about it: one looks at coding rules, the other analyses logic. It cannot be run before uploading and no suppression affects it, so the preparation is to ask its questions first: who holds each value a shortcut depends on, what is decided with data the caller chooses, and which secrets are being copied somewhere.

When a blocked-release email arrives, sort the findings before touching anything. A signature match on a file that is the plugin's function is answered by replying to the email. A logic finding is almost always real: reproduce it and fix it. How to handle both, and how not to add signature surface by accident, is in `references/release-gate.md`.

### Security sniffs that MUST NOT be suppressed

| Sniff | Real fix instead of `phpcs:ignore` |
|-------|------------------------------------|
| `WordPress.Security.EscapeOutput.OutputNotEscaped` | `wp_kses_post()` for simple HTML, `wp_kses()` with an allowlist for HTML with forms/inputs, or refactor the helper to echo directly with internal escaping. |
| `WordPress.Security.NonceVerification.Recommended` (on `$_GET`) | Add a `_wpnonce` to the URL the emitter generates; verify it in the reader before reading any other query arg. |
| `WordPress.Security.NonceVerification.Missing` (on `$_POST`) | Verify the parent hook's nonce explicitly inside the callback, even when the parent verifies it before firing. Add `exit;` literal after redirects. |
| `WordPress.Security.ValidatedSanitizedInput.MissingUnslash` | Add `wp_unslash()` before sanitizing every superglobal read. |
| `WordPress.Security.ValidatedSanitizedInput.InputNotSanitized` | Apply the most specific `sanitize_*` for the field. |

### A `phpcs:ignore` is a claim with an expiry date

Suppressing a sniff is asserting that the code is fine anyway. Nobody ever re-reads that assertion, so it must be written to be checkable:

- **No justification, no suppression.** Without the `--` explanation, a suppression is indistinguishable from carelessness. Audited plugin, real numbers: 60 security suppressions, 35 of them with no justification at all.
- **A justification that claims something about another part of the code must cite it as `file:line`.** Comments like `-- nonce verified in handler` cannot be verified, and that exact comment was hiding a complete two-factor authentication bypass: the handler it referred to returned early on an invalid nonce and never verified anything. `-- nonce verified in class-foo.php:412` can be checked in seconds.
- **Suppressions are inherited when code is copied**, and nobody re-reviews them at the destination. The one above was written once and travelled untouched through 70 releases.
- **A `phpcs:disable` without its matching `phpcs:enable` silences the sniff to the end of the file, and that is never acceptable.** A database class opened with a five-sniff disable that covered its 1,552 lines. The justification was true the day it was written, and from then on every new query in that file was born without a net while the report stayed green. Count both directives per file, and mind two traps: a mention in prose inside a comment counts as a directive unless the pattern requires it to start the line, and a file can balance and still have a sniff switched off for good, because its `enable` lines close other sniffs. Look at what each one closes. A bare `// phpcs:enable` with no list re-enables everything.
- **Before accepting a disable, check that it is needed.** Remove it and run the check. In that class twelve errors appeared, all in one migration block and all false positives. Bounding that block returned 1,500 lines to coverage, in two minutes that nobody had spent in years.
- **Report the reach of suppressions, not their count.** A disable over 1,552 lines and one over 30 count the same. And before patching a file, look for open suppressions in it: if there are any, the patch is written without a net and the report will approve it anyway.
- **And a clean Plugin Check run with suppressions in the tree is not security coverage.** It is the metric measuring its own silencer. Report the number of security suppressions next to the zero.

### Security sniffs that are safe to leave suppressed (with justification)

These are not security issues; the manual reviewer recognizes them as performance/style hints:

- `WordPress.DB.SlowDBQuery.slow_db_query_meta_key` / `meta_value` / `meta_query` on a query where the meta key is genuinely the only way to look up the data.
- `Generic.CodeAnalysis.UnusedFunctionParameter.Found` on a hook callback whose signature is fixed by WordPress.

Keep the comment justification short and on the same line:

```php
'meta_query' => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- look-up by customer email per Privacy API contract.
    array(
        'key'   => '_ayudawp_email',
        'value' => $email,
    ),
),
```

### Reading `$_SERVER` without nonce

The reviewer does not require a nonce for `$_SERVER` reads (`HTTP_USER_AGENT`, `REMOTE_ADDR`, `HTTP_X_FORWARDED_FOR`, etc.), because a nonce protects against a forged request and says nothing about these values. Still `wp_unslash` and sanitize them:

```php
$user_agent = isset( $_SERVER['HTTP_USER_AGENT'] )
    ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) )
    : '';
```

That satisfies the sniff and says nothing about trust. `REMOTE_ADDR` is set by the web server from the connection. Every `HTTP_*` key is a request header, written by whoever sends the request: an `X-Forwarded-For` that passes `FILTER_VALIDATE_IP` is a well-formed address that the caller chose. Use those values to log and to display, never to decide who the visitor is or whether a check applies (see "Security decisions must not rest on request data").

### Email header injection on `Reply-To`

If you build `Reply-To` or `From` headers from user input (e.g. the customer's email so the admin can reply directly), sanitize both name and email before composing the header string:

```php
$clean_name  = sanitize_text_field( $name );   // strips \r and \n
$clean_email = sanitize_email( $email );        // validates and strips control chars
$headers[]   = sprintf( 'Reply-To: %s <%s>', $clean_name, $clean_email );

wp_mail( $to, $subject, $body, $headers );
```

Never concatenate raw `$_POST` values into a header — CRLF injection can append arbitrary BCC/CC recipients.

## References

- [WordPress Security API](https://developer.wordpress.org/apis/security/)
- [Escaping Data](https://developer.wordpress.org/apis/security/escaping/)
- [Sanitizing Data](https://developer.wordpress.org/apis/security/sanitizing/)
- [Data Validation](https://developer.wordpress.org/apis/security/data-validation/)
- [Nonces](https://developer.wordpress.org/apis/security/nonces/)
- [User Roles and Capabilities](https://developer.wordpress.org/apis/security/user-roles-and-capabilities/)
- [Common Vulnerabilities](https://developer.wordpress.org/apis/security/common-vulnerabilities/)
- [Plugin Review Team Common Issues](https://developer.wordpress.org/plugins/wordpress-org/common-issues/)
- [Automated Security Review](https://developer.wordpress.org/plugins/wordpress-org/automated-security-review/)
- [WordPress Coding Standards](https://github.com/WordPress/WordPress-Coding-Standards)
