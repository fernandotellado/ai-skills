# Registered meta in REST, and the values core does not escape for you

Read this when a plugin calls `register_post_meta()`, `register_term_meta()` or `register_meta()`, or returns a value through a filter whose result core prints: `pre_get_document_title`, `wp_title`, `document_title`, or any other `pre_*` short-circuit.

The two halves are one mistake seen from each end: assuming somebody else cleans the value. A review that stops at "this is already sanitized" has not finished, and neither has one that stops at "whoever prints it will escape it".

## Entry: a meta registered with `show_in_rest` is a write endpoint

It has no route of its own, so it is easy to miss when listing the plugin's entry points. Anyone who may edit the object can write the meta through the object's REST route.

**Without a `sanitize_callback`, the value is stored exactly as the request sent it**, even when the classic meta box of the same plugin sanitizes. The meta box has its own save handler. The REST route ends in `update_metadata()`, and the only cleaning there is `sanitize_meta()`, which runs the callback you registered and nothing else. In one audited plugin the term metas had carried a sanitizer since the beginning and the post metas had not, and nobody had looked.

`auth_callback` decides who may write. It says nothing about what gets written.

```php
add_action( 'init', 'ayudawp_register_meta' );
function ayudawp_register_meta() {
    register_post_meta(
        'post',
        '_ayudawp_subtitle',
        array(
            'type'              => 'string',
            'single'            => true,
            'show_in_rest'      => true,
            'sanitize_callback' => 'sanitize_text_field',        // what is stored
            'auth_callback'     => 'ayudawp_can_edit_subtitle',  // who may store it
        )
    );
}

function ayudawp_can_edit_subtitle( $allowed, $meta_key, $post_id ) {
    return current_user_can( 'edit_post', $post_id );
}
```

Registrations that look sanitized and are not:

| Registration | What happens |
|---|---|
| No `sanitize_callback`, or `null`, or `''` | Stored raw, with no warning |
| A PHP internal as the callback: `'trim'`, `'intval'` | `register_meta()` hooks the callback as a filter that receives three arguments, or four when the meta is registered for a subtype, which is what `register_post_meta()` does. A PHP internal does not accept extra arguments: on PHP 8 it throws `ArgumentCountError` on every write, on PHP 7.4 it returns `null` and the meta is saved empty. `absint()` and `sanitize_text_field()` are written in PHP and take the extra arguments quietly |
| `'type' => 'integer'`, `'boolean'` or `'number'` and no callback | The type is enforced by the REST schema, which rejects `7abc` with a 400. The registration itself enforces nothing: `update_post_meta()` called from PHP stores `7abc<b>` as given |
| `'sanitize_callback' => 'sanitize_text_field'` on a multi-line field | Line breaks are collapsed into spaces. Use `sanitize_textarea_field()` |
| A callback chosen at runtime: `self::pick_sanitizer( $key )` | It may work, but nobody can check it by reading the registration, and no tool can. Register each key with the literal name of a function that exists |

List every registration and fill in the row. It takes minutes and it is the only way to know:

```bash
grep -rnE --include='*.php' 'register_(post_|term_|comment_|user_)?meta\s*\(' .
```

| Meta key | Object | `show_in_rest` | `type` | `sanitize_callback` | `auth_callback` |
|---|---|---|---|---|---|

A row with `show_in_rest` true, a text type and no named sanitizer is a finding.

## Exit: the escaping of the title is a callback on a filter

`wp_get_document_title()` does not escape anything itself. Core hooks `wptexturize`, `convert_chars` and `esc_html` onto the `document_title` filter at priority 10 in `wp-includes/default-filters.php`, and the `pre_get_document_title` short-circuit returns from the function long before `document_title` is applied. The same line of `default-filters.php` does the same for `wp_title`.

So where your value enters decides whether it is escaped:

| Your value enters through | Escaped by core? |
|---|---|
| `document_title_parts`, or `document_title` at a priority below 10 | Yes |
| `pre_get_document_title`, at any priority | **No.** The function returns before the escaping filter runs |
| `document_title` or `wp_title` at priority 10, which is the default, or above | **No.** Core registered `esc_html` first, and callbacks of the same priority run in the order they were added, so yours runs after it |

Measured on WordPress 7.1 with a callback that returns `</title><script>`: at priority 9 it comes out escaped, at 10 and at 11 it comes out raw.

Every emitter then prints the value verbatim: `_wp_render_title_tag()`, `_block_template_render_title_tag()` (which replaces the former on block themes), the two `theme-compat` headers and the feed title, which goes to XML. A title that leaves your callback unescaped is printed raw inside `<title>`, and `</title><script>` closes the element.

In one audited plugin this was stored XSS reachable by any user with `edit_posts`, which means a contributor, and it had been there since the feature existed. The fix is to escape in the plugin:

```php
add_filter( 'pre_get_document_title', 'ayudawp_document_title', PHP_INT_MAX );
function ayudawp_document_title( $title ) {
    $custom = is_singular() ? (string) get_post_meta( get_queried_object_id(), '_ayudawp_subtitle', true ) : '';

    if ( '' === $custom ) {
        return $title;   // nothing to say: hand back what came in, untouched
    }

    return esc_html( $custom );   // core will not do it on this path
}
```

The same reasoning applies to any filter where core's own value is escaped by a default callback rather than at the point of output. Before returning a value through a `pre_*` filter, find out what the non-short-circuited path would have done to it.

## Verify by reading the installed core

A claim about what a core function does is checked in the installed version, not reasoned. The first written version of the rule above placed the `esc_html()` inside `wp_get_document_title()`, where it is not, and a reviewer caught it by opening the file.

Cite function names rather than line numbers when you write the finding down: lines move between minor versions. And for filters, dump who is actually hooked instead of deducing it:

```bash
wp eval 'global $wp_filter; foreach ( array( "document_title", "wp_title" ) as $h ) foreach ( $wp_filter[ $h ]->callbacks as $p => $cbs ) foreach ( $cbs as $k => $c ) echo "$h $p: $k\n";'
```

On a stock install that prints `wptexturize`, `convert_chars` and `esc_html` at 10 and `capital_P_dangit` at 11 for both filters. Anything else in the list is a plugin or the theme, and its priority tells you whether its value comes out escaped.
