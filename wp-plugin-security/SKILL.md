---
name: wp-plugin-security
description: "Security guidelines for WordPress plugin development: sanitization, validation, escaping, nonces, capabilities, SQL injection prevention, XSS protection, and CSRF mitigation. Based on official WordPress Developer Resources."
compatibility: "WordPress 6.0+ / PHP 7.4+. Applies to plugins, themes, and custom code."
license: GPL-2.0-or-later
metadata:
  author: fernando-tellado
  version: "1.1"
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
| `sanitize_text_field()` | Single-line text input |
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
// Sanitize a text field from POST
$title = sanitize_text_field( $_POST['title'] ?? '' );

// Sanitize email
$email = sanitize_email( $_POST['email'] ?? '' );

// Sanitize URL for database storage
$url = sanitize_url( $_POST['website'] ?? '' );

// Sanitize textarea
$description = sanitize_textarea_field( $_POST['description'] ?? '' );
```

### Important notes on sanitization

- **Never use escape functions for sanitization** - they serve different purposes
- When using `filter_var()`, always specify a sanitizing filter (not `FILTER_DEFAULT`)
- Process only the specific keys you need, not the entire `$_POST`/`$_GET` array

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
if ( isset( $_POST['zip'] ) && ayudawp_is_valid_us_zip( $_POST['zip'] ) ) {
    $zip = sanitize_text_field( $_POST['zip'] );
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
| `manage_options` | Administrator |
| `edit_themes` | Administrator |
| `activate_plugins` | Administrator |

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

    // 2. Check capability
    if ( ! current_user_can( 'delete_posts' ) ) {
        wp_die( 'You do not have permission to delete items.' );
    }

    // 3. Validate and sanitize input
    $item_id = absint( $_POST['item_id'] ?? 0 );
    if ( ! $item_id ) {
        wp_die( 'Invalid item ID' );
    }

    // 4. Perform action
    // ... delete logic here
}
```

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
// Build placeholders for array
$ids = array( 1, 2, 3, 4, 5 );
$placeholders = implode( ', ', array_fill( 0, count( $ids ), '%d' ) );

$results = $wpdb->get_results(
    $wpdb->prepare(
        "SELECT * FROM {$wpdb->posts} WHERE ID IN ( $placeholders )",
        $ids
    )
);
```

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

// Instead, use upload_mimes filter for specific file types
add_filter( 'upload_mimes', function( $mimes ) {
    $mimes['svg'] = 'image/svg+xml';
    return $mimes;
} );
```

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
    $data = sanitize_text_field( $_POST['data'] ?? '' );

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

## Code review checklist

### Input handling

- [ ] All `$_POST`, `$_GET`, `$_REQUEST` values are sanitized
- [ ] All `$_FILES` uploads use `wp_handle_upload()`
- [ ] Database queries use `$wpdb->prepare()`
- [ ] Type casting used where appropriate (`absint()`, `(int)`, etc.)

### Output handling

- [ ] All dynamic output is escaped
- [ ] Correct escape function used for context (html/attr/url/js)
- [ ] Escaping happens at output time (late escaping)
- [ ] Translation functions are escaped (`esc_html__()` not `__()`)

### Authentication & authorization

- [ ] Nonces used on all forms and state-changing URLs
- [ ] Nonces verified before processing actions
- [ ] Capability checks performed before actions
- [ ] Both nonce AND capability checked (not just one)

### General

- [ ] No `ALLOW_UNFILTERED_UPLOADS`
- [ ] Direct file access prevented with `ABSPATH` check
- [ ] No `error_reporting()` in production code
- [ ] No timezone changes with `date_default_timezone_set()`
- [ ] Uses WordPress HTTP API, not raw cURL
- [ ] Uses `wp_enqueue_*` for scripts/styles

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

### Security sniffs that MUST NOT be suppressed

| Sniff | Real fix instead of `phpcs:ignore` |
|-------|------------------------------------|
| `WordPress.Security.EscapeOutput.OutputNotEscaped` | `wp_kses_post()` for simple HTML, `wp_kses()` with an allowlist for HTML with forms/inputs, or refactor the helper to echo directly with internal escaping. |
| `WordPress.Security.NonceVerification.Recommended` (on `$_GET`) | Add a `_wpnonce` to the URL the emitter generates; verify it in the reader before reading any other query arg. |
| `WordPress.Security.NonceVerification.Missing` (on `$_POST`) | Verify the parent hook's nonce explicitly inside the callback, even when the parent verifies it before firing. Add `exit;` literal after redirects. |
| `WordPress.Security.ValidatedSanitizedInput.MissingUnslash` | Add `wp_unslash()` before sanitizing every superglobal read. |
| `WordPress.Security.ValidatedSanitizedInput.InputNotSanitized` | Apply the most specific `sanitize_*` for the field. |

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

The reviewer does not require nonce for `$_SERVER` reads (`HTTP_USER_AGENT`, `REMOTE_ADDR`, `HTTP_X_FORWARDED_FOR`, etc.) because they are not user-controllable through a URL. Still sanitize and `wp_unslash` them and validate with `filter_var( $ip, FILTER_VALIDATE_IP )` for IP addresses:

```php
$user_agent = isset( $_SERVER['HTTP_USER_AGENT'] )
    ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) )
    : '';
```

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
- [WordPress Coding Standards](https://github.com/WordPress/WordPress-Coding-Standards)
