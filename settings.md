---
permalink: /settings/
title: Kint settings reference
---

<div id="leftmenu" class="col-sm-4 col-md-3 hidden-xs">
<ul class="nav nav-list side-navigation" data-spy="affix" data-offset-top="{{ site.affix_offset }}">
    <li><a href="#kint">Kint settings</a></li>
    <li><a href="#rich">Rich renderer settings</a></li>
    <li><a href="#text">Text renderer settings</a></li>
    <li><a href="#pplugins">Parser plugin settings</a></li>
    <li><a href="#rplugins">Renderer plugin settings</a></li>
    <li><a href="#other">Other settings</a></li>
    <li><a href="{{ site.baseurl }}/contrib/">Improving Kint &raquo;</a></li>
</ul>
</div>
<div class="col-sm-8 col-md-9" markdown="1">

# Kint settings reference

These are global Kint settings.

There is no specific configuration format, just set the values in PHP. You can best do this in your front controller whether it's a bootstrap file, composer autoloader, or an application object (Such as in symfony and laravel)

<section id="kint" markdown="1">

## Kint settings

These are technically on `Kint\Kint` but it's aliased to the root namespace for convenience.

`Kint::$enabled_mode` | Determines what mode Kint will run in. `true` is automatic. `false` is disabled. Any of the keys to `Kint::$renderers` to select a specific mode. You should disable Kint in production, or only enable it per `$_SERVER['REMOTE_ADDR']`. Default `true`
`Kint::$depth_limit` | The maximum depth to parse. 0 for unlimited. Tweak this to balance performance and verbosity. Default 7
`Kint::$return` | Whether to return or echo the output. Default `false`
`Kint::$aliases` | List of helper function aliases. Either string for a function name, or array of two strings for a static method.
`Kint::$plugins` | List of enabled plugins. Either string class names or instances. Plugins added here will be cached and need to work with multiple parser instances.
|
`Kint::$app_root_dirs` | An array of paths to aliases. These will be replaced in the mini trace, backtraces, etc. Default maps `$_SERVER['DOCUMENT_ROOT']` to `<ROOT>`
`Kint::$expanded` | Whether to expand values by default. Default `false`
`Kint::$cli_detection` | Whether to detect if it's being run in a CLI and adjust the renderer. Default `true`
`Kint::$display_called_from` | Whether to display the called from (mini trace) information. Default `true`
`Kint::$file_link_format` | A format to link source code paths to. Default `ini_get('xdebug.file_link_format')`
`Kint::$mode_default` | The mode to select automatically when `$enabled_mode` is true. Default `Kint::MODE_RICH`
`Kint::$mode_default_cli` | The mode to select automatically when `$enabled_mode` and `$cli_detection` are true. Default `Kint::MODE_CLI`
`Kint::$renderers` | A map of render modes to renderer classes.

</section>
<section id="rich" markdown="1">

## Rich renderer settings

These are on `Kint\Renderer\RichRenderer`.

`RichRenderer::$js_nonce` | A nonce attribute for the script tag for CSP, or `null` to disable. Default `null`
`RichRenderer::$css_nonce` | A nonce attribute for the style tag for CSP, or `null` to disable. Default `null`
`RichRenderer::$theme` | Which theme to use. One of the CSS files from `resources/compiled/` or the full path to a CSS file. Default `original.css`
`RichRenderer::$folder` | Enable a folder attached to the bottom of the viewport and place the dumps in that. Default `false`
`RichRenderer::$strlen_max` | The maximum length of text to show in the bar. 0 to disable. Default 80
|
`RichRenderer::$value_plugins` | List of rich renderer value plugins
`RichRenderer::$tab_plugins` | List of rich renderer tab plugins
`RichRenderer::$access_paths` | Enable or disable the showing of access paths. Default `true`
`RichRenderer::$escape_types` | Enable or disable the escaping of types. Enabling will hurt performance, but allow you to use unicode text on non-utf8 web pages. Default `false`
`RichRenderer::$sort` | Sort mode for object properties. Default `Kint\Renderer\Renderer::SORT_NONE`
`RichRenderer::$needs_pre_render` | Whether to render CSS and JS on the next dump. Default `true` before first dump.
`RichRenderer::$needs_folder_render` | Whether to render the folder on the next dump. Default `true` before first dump.
`RichRenderer::$always_pre_render` | Whether to render CSS, JS, and the folder on every dump. Default `false`
`RichRenderer::$pre_render_sources` | Array of arrays. Keys `script` `style` and `raw` are arrays of strings or callables returning strings. `script` and `style` will be placed in tags with nonces for CSP, while `raw` will be output as-is.


</section>
<section id="text" markdown="1">

## Text renderer settings

These are on `Kint\Renderer\TextRenderer`.

`TextRenderer::$decorations` | Whether to render decorations around the header and footer. Default `true`
`TextRenderer::$default_indent` | The indentation level in spaces. Default 4
`TextRenderer::$default_width` | The width of header and footer decorations. Default 80
`TextRenderer::$parser_plugin_whitelist` | List of whitelisted parser plugins. Any parser plugins not in this whitelist will not be added to the parser for performance reasons, and because their output isn't used in the text renderer.
`TextRenderer::$plugins` | List of text renderer plugins.
`TextRenderer::$sort` | Sort mode for object properties. Default `Kint\Renderer\Renderer::SORT_NONE`
`TextRenderer::$strlen_max` | The maximum length of text. 0 to disable. Default 0

### Plain renderer

`Kint\Renderer\PlainRenderer` inherits from the text renderer.

`PlainRenderer::$disable_utf8` | Whether to disable the utf-8 parts of the decorations and use htmlentities instead. Vastly increases output size. If <a href="http://utf8everywhere.org/" target="_blank">your web page isn't in utf-8</a>, set this to `true`. Default `false`
`PlainRenderer::$needs_pre_render` | Whether to render CSS and JS on the next dump. Default `true` before first dump.
`PlainRenderer::$always_pre_render` | Whether to render CSS and JS on every dump. Default `false`
`PlainRenderer::$pre_render_sources` | List of callables to execute or strings to render before initial render. See also `Kint\Renderer\RichRenderer::$pre_render_sources`
`PlainRenderer::$theme` | Which theme to use. One of the CSS files from `resources/compiled/` if using composer, or the full path to a CSS file. Default `plain.css`

### CLI renderer

`Kint\Renderer\CliRenderer` inherits from the text renderer.

`CliRenderer::$cli_colors` | Whether to print color codes. Default `true`
`CliRenderer::$detect_width` | Whether to detect the terminal width on startup and adjust the header and footer widths accordingly. Default `true`
`CliRenderer::$force_utf8` | Whether to force default utf-8 output on older versions of windows. Default `false`
`CliRenderer::$min_terminal_width` | What minimum width must we detect before ignoring the detected width altogether. Default 40
`CliRenderer::$windows_stream` | Which stream to detect VT100 support with. Default `STDOUT`

</section>
<section id="pplugins" markdown="1">

## Parser plugin settings

All parser plugins delivered with Kint are in the `Kint\Parser` namespace.

### ArrayLimitPlugin

`ArrayLimitPlugin::$trigger` | Maximum size of arrays before limiting output. Default 1000
`ArrayLimitPlugin::$limit` | Maximum amount of items to show in a limited array. Default 50
`ArrayLimitPlugin::$numeric_only` | Whether to only limit arrays without string keys. Default `true`

### Base64Plugin

`Base64Plugin::$min_length_hard` | Minimum length before the binary parser will start. Default 16
`Base64Plugin::$min_length_soft` | Minimum length before the binary representation will take precedence over the string representation. Default 50

### BlacklistPlugin

`BlacklistPlugin::$blacklist` | List of classes to blacklist.
`BlacklistPlugin::$shallow_blacklist` | List of classes to blacklist unless you dump it specifically. This is great for heavy values that impact performance like DI containers. Default includes `Psr\Container\ContainerInterface`

### DOMDocumentPlugin

`DOMDocumentPlugin::$blacklist` | DOMNode properties to skip parsing. This is here because the DOMDocument system can introduce a huge amount of recursion.
`DOMDocumentPlugin::$verbose` | Show methods and properties. Default `false`

### FsPathPlugin

`FsPathPlugin::$blacklist` | List of paths to ignore. Default includes `/` and `.`

### IteratorPlugin

`IteratorPlugin::$blacklist` | Classes to prevent iterating over. Default includes `PDOStatement`, `mysqli_result`, `DOMNodeList`, `DOMNamedNodeMap`, `SplFileObject`

### SerializePlugin

`SerializePlugin::$safe_mode` | Don't allow deserialization of arrays or objects. Default `true`
`SerializePlugin::$options` | Options to pass to <a href="http://php.net/manual/en/function.unserialize.php#refsect1-function.unserialize-parameters" target="_blank">unserialize</a>. Only for PHP7.

### SimpleXMLElementPlugin

`SimpleXMLElementPlugin::$verbose` | Show methods and properties. Default `false`

### TimestampPlugin

`TimestampPlugin::$blacklist` | Integers to ignore. Default includes common integers like `PHP_INT_MAX`

### ToStringPlugin

`ToStringPlugin::$blacklist` | Objects to ignore. Default includes `SimpleXMLElement`, `SplFileObject`

### TracePlugin

`TracePlugin::$blacklist` | Function calls to skip. A string for a function, or an array of class name and method name. Default includes `spl_autoload_call`
`TracePlugin::$path_blacklist` | Paths to skip. Any frame from a file starting with one of these will be skipped.

### XmlPlugin

`XmlPlugin::$parse_method` | One of `SimpleXML` or `DOMDocument`. DOMDocument is slower, and you'll need to enable `DOMDocumentPlugin` for it to work. Default `SimpleXML`

</section>
<section id="rplugins" markdown="1">

## Renderer plugin settings

All rich renderer plugins delivered with Kint are in the `Kint\Renderer\Rich` namespace.

### BinaryPlugin

`BinaryPlugin::$chunk_length` | The amount of bytes per chunk of output. Default `0x4`
`BinaryPlugin::$line_length` | The amount of bytes per line of output. Default `0x10`

### TablePlugin

`TablePlugin::$respect_str_length` | Whether the cells of the table should respect `RichRenderer::$strlen_max`. Default `true`

</section>
<section id="other" markdown="1">

## Other settings

`Kint\Zval\Representation\ColorRepresentation::$color_map` | A map of HTML color names to hex codes.

### Encoding settings

These are on `Kint\Zval\BlobValue`.

`BlobValue::$char_encodings` | A list of multibyte character encodings to try to identify to be passed to `mb_detect_encoding`. Default includes `ASCII` and `UTF-8`
`BlobValue::$legacy_encodings` | A list of windows single-byte and other ambiguous encodings. If no multibyte encoding was detected Kint will assume the first matching legacy_encoding is correct.

</section>

<h2><a href="{{ site.baseurl }}/contrib/">Improving Kint &raquo;</a></h2>

</div>
