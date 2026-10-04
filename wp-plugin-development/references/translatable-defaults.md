# Never store a translatable default in the database

Read this when a plugin has a setting, a widget option or a block attribute whose default is a text shown to somebody: a heading, a button label, an empty-state message, an email subject.

## The rule

A default text that is printed to someone, visitor or administrator, is never stored. It is resolved with `__()` on every request.

The moment it is stored it stops going through gettext and stays frozen in the language of whoever saved it, for every language of the site. On a multilingual site it is a visible bug: the user translates the string in WPML or Polylang and nothing changes.

## How it gets in

Almost always by accident:

1. **The settings field comes pre-filled.** The callback fills the textarea with the default when the option is empty, so the first "Save changes" on that screen writes it as is, even if the administrator was changing something else.
2. **Activation seeds the defaults.** An `add_option()` with the whole defaults array. Worse, translations are not loaded during activation, so what gets stored is the English source.
3. **A well-meant migration translates what is stored.** One release read the stored English text and replaced it with the translation of the active language. That is the same bug, made on purpose and permanent.

A quick way to find candidates:

```bash
grep -rnE --include='*.php' "=> *(esc_html__|__|_x)\s*\(" . | grep -iE "default|setting"
```

Then check whether those keys end up in a `value=` or inside a `<textarea>`.

## The recipe

It has been applied in four published plugins. Every step is there because leaving it out reopened the bug somewhere.

1. **Translatable defaults live in their own function and are merged when reading**, so they are resolved on every request.
2. **When saving, store nothing if the value equals the default.** In an array option that means deleting the key, not storing an empty string: in most plugins an empty value already means something else ("no prefix", "no heading", "send no instructions"), and mixing the two breaks the site of whoever emptied the field on purpose. In a flat option whose reader already treats empty as "use the default", return an empty string.
3. **Compare against every language, when saving too.** The form is rendered and saved in the language of the administrator, so comparing against the current language works in the usual case. An administrator whose profile language differs from the site's freezes the text again, and by then no upgrade routine is left to clean it. Use the same function on both paths.
4. **Clean what is already stored, once**, gated by a version option. Not a transient: a transient expires and the routine runs again. Run it on `init` or later, when translations can be loaded. A text the site actually wrote never matches any default and is left alone.
5. **Sweep the secondary stores.** Widget instances usually keep their own copy of the settings they were saved with (the `widget_{id_base}` option), so cleaning only the main option leaves every saved widget frozen. Blocks and shortcodes rarely need it, because they read the attribute only when it is present.
6. **Do not seed them on activation.** Seed everything else.
7. **Ship a `wpml-config.xml`** for the options that hold copy the site wrote, so that the site's own text can be translated. In an array option the keys go nested.

```php
/**
 * The settings whose default is a translatable string. Resolved on every request.
 */
function myplugin_get_translatable_defaults() {
    return array(
        'heading'    => __( 'Upcoming posts', 'my-plugin' ),
        'empty_text' => __( 'Nothing scheduled yet.', 'my-plugin' ),
    );
}

function myplugin_get_plain_defaults() {
    return array(
        'count'      => 5,
        'show_image' => true,
    );
}

function myplugin_get_settings() {
    $stored = get_option( 'myplugin_settings', array() );

    return wp_parse_args(
        is_array( $stored ) ? $stored : array(),
        array_merge( myplugin_get_translatable_defaults(), myplugin_get_plain_defaults() )
    );
}

/**
 * Keys of a stored array that only hold a copy of a bundled default, in any language
 * installed on the site. The same function serves the save path and the cleanup.
 */
function myplugin_find_stored_defaults( array $stored ) {
    $found   = array();
    $locales = array_unique( array_merge( array( 'en_US', get_locale() ), get_available_languages() ) );

    foreach ( $locales as $locale ) {
        $switched = switch_to_locale( $locale );
        $defaults = myplugin_get_translatable_defaults();

        if ( $switched ) {
            restore_previous_locale();
        }

        foreach ( $defaults as $key => $default ) {
            if ( isset( $stored[ $key ] ) && $stored[ $key ] === $default ) {
                $found[ $key ] = $key;
            }
        }
    }

    return $found;
}

// Save path: at the end of the sanitize callback of the array option.
function myplugin_sanitize_settings( $input ) {
    $sanitized = array();
    // ... sanitize each field into $sanitized ...

    foreach ( myplugin_find_stored_defaults( $sanitized ) as $key ) {
        unset( $sanitized[ $key ] );   // delete the key, do not store ''
    }

    return $sanitized;
}

// One-time cleanup of what earlier versions stored.
add_action( 'init', 'myplugin_maybe_upgrade', 20 );
function myplugin_maybe_upgrade() {
    if ( get_option( 'myplugin_version' ) === MYPLUGIN_VERSION ) {
        return;
    }

    $stored = get_option( 'myplugin_settings' );

    if ( is_array( $stored ) ) {
        $stale = myplugin_find_stored_defaults( $stored );

        if ( $stale ) {
            update_option( 'myplugin_settings', array_diff_key( $stored, $stale ) );
        }
    }

    // Repeat for every secondary store: each instance in widget_myplugin_widget, and so on.

    update_option( 'myplugin_version', MYPLUGIN_VERSION );
}

// Activation: only the defaults that are not text.
function myplugin_activate() {
    if ( false === get_option( 'myplugin_settings' ) ) {
        add_option( 'myplugin_settings', myplugin_get_plain_defaults() );
    }
}
```

## Flat options

When each text is its own option, the comparison goes on `sanitize_option_{$option}` at priority 20 and with two arguments, so that it runs after the sanitizer of the setting and knows which option it is looking at:

```php
add_filter( 'sanitize_option_myplugin_heading', 'myplugin_discard_default_text', 20, 2 );
function myplugin_discard_default_text( $value, $option ) {
    $default = myplugin_default_text_for( $option );   // '' when the option has no translatable default

    if ( '' === $default ) {
        return $value;
    }

    return ( trim( (string) $value ) === trim( $default ) ) ? '' : $value;
}
```

It cannot be the `sanitize_callback` of `register_setting()`: core hooks that callback with a single argument, so it never receives the option name. This snippet compares against the current language only: for the reason given in step 3, compare against every installed language here as well.

## `wpml-config.xml`

```xml
<wpml-config>
    <admin-texts>
        <key name="myplugin_settings">
            <key name="heading" />
            <key name="empty_text" />
        </key>
    </admin-texts>
</wpml-config>
```

## What else can freeze

Not only labels. In one plugin the stored defaults included the URLs of a set of links, which were translatable on purpose so that each language pointed at its local site. Anything that passes through `__()` in a defaults array is a candidate.
