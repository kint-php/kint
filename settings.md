---
permalink: /settings/
title: Kint settings reference
---

<div id="leftmenu" class="col-sm-4 col-md-3 hidden-xs">
<ul class="nav nav-list side-navigation" data-spy="affix" data-offset-top="{{ site.affix_offset }}">
    <li><a href="#kint">Kint settings</a></li>
    <li><a href="#pplugins">Parser plugin settings</a></li>
    <li><a href="#renderer">Renderer settings</a></li>
    <li><a href="#rplugins">Renderer plugin settings</a></li>
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
`Kint::$mode_default` | The mode to select automatically when `$enabled_mode` is true. Default `Kint::MODE_RICH`
`Kint::$mode_default_cli` | The mode to select automatically when `$enabled_mode` and `$cli_detection` are true. Default `Kint::MODE_CLI`
`Kint::$cli_detection` | Whether to detect if it's being run in a CLI and adjust the renderer. Default `true`
`Kint::$app_root_dirs` | An array of paths to aliases. These will be replaced in the mini trace, backtraces, etc. Default maps `$_SERVER['DOCUMENT_ROOT']` to `<ROOT>`
`Kint::$file_link_format` | A format to link source code paths to. Default `ini_get('xdebug.file_link_format')`
`Kint::$return` | Whether to return or echo the output. Default `false`
`Kint::$depth_limit` | The maximum depth to parse. 0 for unlimited. Tweak this to balance performance and verbosity. Default 7
`Kint::$expanded` | Whether to expand values by default. Default `false`
`Kint::$display_called_from` | Whether to display the called from (mini trace) information. Default `true`
`Kint::$aliases` | List of helper function aliases. Either string for a function name, or array of two strings for a static method.
`Kint::$renderers` | A map of render modes to renderers. Either class strings or instances.
`Kint::$plugins` | List of enabled plugins. Either class strings or instances.

### Other settings

`Kint\Zval\Representation\ColorRepresentation`

`ColorRepresentation::$color_map` | A map of HTML color names to hex codes.

---

`Kint\Zval\BlobValue`

`BlobValue::$char_encodings` | A list of multibyte character encodings to try to identify to be passed to `mb_detect_encoding`. Default includes `ASCII` and `UTF-8`
`BlobValue::$legacy_encodings` | A list of windows single-byte and other ambiguous encodings. If no multibyte encoding was detected Kint will assume the first matching legacy_encoding is correct.

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

`BlacklistPlugin::$blacklist` | List of class strings to blacklist.
`BlacklistPlugin::$shallow_blacklist` | List of class strings to blacklist unless you dump it explicitly. This is great for heavy values that impact performance like DI containers. Default includes `Psr\Container\ContainerInterface`

### DOMDocumentPlugin

`DOMDocumentPlugin::$blacklist` | DOMNode properties to skip parsing. This is here because the DOMDocument system can introduce a huge amount of recursion.
`DOMDocumentPlugin::$verbose` | Show methods and properties. Default `false`

### DomPlugin

`DomPlugin::$blacklist` | Properties to skip parsing. This is here because the Dom can introduce a huge amount of recursion.
`DomPlugin::$verbose` | Show methods and properties. Default `false`

### FsPathPlugin

`FsPathPlugin::$blacklist` | List of paths to ignore. Default includes `/` and `.`

### IteratorPlugin

`IteratorPlugin::$blacklist` | Classes to prevent iterating over. Default includes `Dom\NamedNodeMap`, `Dom\NodeList`, `DOMNamedNodeMap`, `DOMNodeList`, `mysqli_result`, `PDOStatement`, `SimpleXMLElement`, `SplFileObject`

### SerializePlugin

`SerializePlugin::$safe_mode` | Only allow unserialization of scalars. Default `true`
`SerializePlugin::$allowed_classes` | Allowed classes to unserialize. An array of class strings or `false` for none. Default `false`

### SimpleXMLElementPlugin

`SimpleXMLElementPlugin::$verbose` | Show methods. Default `false`

### TablePlugin

`TablePlugin::$max_width` | Maximum number of columns in table. Default 300
`TablePlugin::$min_width` | Minimum number of columns in table. Default 2

### TimestampPlugin

`TimestampPlugin::$blacklist` | Integers to ignore. Default includes common integers like `PHP_INT_MAX`

### ToStringPlugin

`ToStringPlugin::$blacklist` | Objects to ignore. Default includes `SimpleXMLElement`, `SplFileInfo`

### TracePlugin

`TracePlugin::$blacklist` | Function calls to skip. A string for a function, or an array of class name and method name. Default includes `spl_autoload_call`
`TracePlugin::$path_blacklist` | Paths to skip. Any frame from a file starting with one of these will be skipped.

### XmlPlugin

`XmlPlugin::$parse_method` | One of `SimpleXML`, `DOMDocument`, or `XMLDocument` DOMDocument is slower, and you'll need to enable `DOMDocumentPlugin` for it to work. `XMLDocument` is PHP8.4+. Default `SimpleXML`

</section>
<section id="renderer" markdown="1">

## Renderer settings

`Kint\Renderer\AbstractRenderer`

`AbstractRenderer::$js_nonce` | A nonce attribute for the script tag for CSP, or `null` to disable. Default `null`
`AbstractRenderer::$css_nonce` | A nonce attribute for the style tag for CSP, or `null` to disable. Default `null`

### Rich renderer settings

`Kint\Renderer\RichRenderer`

`RichRenderer::$value_plugins` | List of rich renderer value plugins
`RichRenderer::$tab_plugins` | List of rich renderer tab plugins
`RichRenderer::$pre_render_sources` | Array of arrays. Keys `script` `style` and `raw` are arrays of strings or callables returning strings. `script` and `style` will be placed in tags with nonces for CSP, while `raw` will be output as-is.
`RichRenderer::$theme` | Which theme to use. One of the CSS files from `resources/compiled/` or the full path to a CSS file. Default `original.css`
`RichRenderer::$strlen_max` | The maximum length of text to show in the bar. 0 to disable. Default 80
`RichRenderer::$timestamp` | Timestamp to print in footer in <a href="https://secure.php.net/datetime.format#refsect1-datetime.format-parameters" target="_blank">date()</a> format. Default `null`
`RichRenderer::$access_paths` | Enable or disable the showing of access paths. Default `true`
`RichRenderer::$escape_types` | Enable or disable the escaping of types. Enabling will hurt performance, but allow you to use unicode text on non-utf8 web pages. Default `false`
`RichRenderer::$folder` | Enable a folder attached to the bottom of the viewport and place the dumps in that. Default `false`
`RichRenderer::$needs_pre_render` | Whether to render CSS and JS on the next dump. Default `true` before first dump.
`RichRenderer::$always_pre_render` | Whether to render CSS and JS on every dump. Default `false`

### Text renderer settings

`Kint\Renderer\TextRenderer`

`TextRenderer::$plugins` | List of text renderer plugins.
`TextRenderer::$parser_plugin_whitelist` | List of whitelisted parser plugins. Any parser plugins not in this whitelist will not be added to the parser for performance reasons, and because their output isn't used in the text renderer.
`TextRenderer::$strlen_max` | The maximum length of text. 0 to disable. Default 0
`TextRenderer::$timestamp` | Timestamp to print in footer in <a href="https://secure.php.net/datetime.format#refsect1-datetime.format-parameters" target="_blank">date()</a> format. Default `null`
`TextRenderer::$default_width` | The default width of header and footer decorations. Default 80
`TextRenderer::$default_indent` | The indentation level in spaces. Default 4
`TextRenderer::$decorations` | Whether to render decorations around the header and footer. Default `true`

---

`Kint\Renderer\PlainRenderer` inherits from `TextRenderer`

`PlainRenderer::$pre_render_sources` | List of callables to execute or strings to render before initial render. See also `RichRenderer::$pre_render_sources`
`PlainRenderer::$theme` | Which theme to use. One of the CSS files from `resources/compiled/` if using composer, or the full path to a CSS file. Default `plain.css`
`PlainRenderer::$disable_utf8` | Whether to disable the utf-8 parts of the decorations and use htmlentities instead. Vastly increases output size. If <a href="http://utf8everywhere.org/" target="_blank">your web page isn't in utf-8</a>, set this to `true`. Default `false`
`PlainRenderer::$needs_pre_render` | Whether to render CSS and JS on the next dump. Default `true` before first dump.
`PlainRenderer::$always_pre_render` | Whether to render CSS and JS on every dump. Default `false`

---

`Kint\Renderer\CliRenderer` inherits from `TextRenderer`

`CliRenderer::$cli_colors` | Whether to print color codes. Default `true`
`CliRenderer::$detect_width` | Whether to detect the terminal width on startup and adjust the header and footer widths accordingly. Default `true`
`CliRenderer::$min_terminal_width` | What minimum width must we detect before using the default instead. Default 40
`CliRenderer::$force_utf8` | Whether to force default utf-8 output on older versions of windows. Default `false`
`CliRenderer::$windows_stream` | Which stream to detect VT100 support with. Default `STDOUT`

</section>
<section id="rplugins" markdown="1">

## Renderer plugin settings

`Kint\Renderer\Rich\BinaryPlugin`

`BinaryPlugin::$chunk_length` | The amount of bytes per chunk of output. Default `0x4`
`BinaryPlugin::$line_length` | The amount of bytes per line of output. Default `0x10`

---

`Kint\Renderer\Rich\TablePlugin`

`TablePlugin::$respect_str_length` | Whether the cells of the table should respect `RichRenderer::$strlen_max`. Default `true`

</section>

<h2><a href="{{ site.baseurl }}/contrib/">Improving Kint &raquo;</a></h2>

</div>
