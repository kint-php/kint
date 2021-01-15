---
permalink: /settings/
title: Kint settings
---

<div id="leftmenu" class="col-sm-4 col-md-3 hidden-xs">
<ul class="nav nav-list side-navigation" data-spy="affix" data-offset-top="{{ site.affix_offset }}">
    <li><a href="#common">Most common settings</a></li>
    <li><a href="#kint">Kint settings</a></li>
    <li><a href="#encoding">Encoding settings</a></li>
    <li><a href="#rich">Rich renderer settings</a></li>
    <li><a href="#text">Text renderer settings</a></li>
    <li><a href="{{ site.baseurl }}/contrib/">Improving Kint &raquo;</a></li>
</ul>
</div>
<div class="col-sm-8 col-md-9" markdown="1">

# Kint settings

These are global Kint settings. For plugin settings please see the [plugins page]({{ site.baseurl }}/plugins/). 

<section id="common" markdown="1">

## Usage with a framework

Chances are you're using `composer`, in that case the cleanest way is to add an entry to the `autoload.files` configuration:

```json
    "autoload": {
        "psr-4": {
            "App\\": "src/"
        },
        "files": [
            "config/kint.php"
        ]
    },
```

Place your settings there (`"config/kint.php"`). [Here is what settings I personally use](https://gist.github.com/raveren/eba373d8abb572b0528c73d145103f95).

## Most common settings

These are the settings you're most likely going to want to customize Kint behavior

`Kint::$enabled_mode` | Determines what mode Kint will run in. `true` is automatic. `false` is disabled. Any of the keys to `Kint::$renderers` to select a specific mode. You should disable Kint in production, or only enable it per `$_SERVER['REMOTE_ADDR']`. Default `true`.
`Kint\Renderer\RichRenderer::$folder` | Whether to move all kint dumps into a folder attached to the bottom of the viewport. Default `true`.
`Kint::$plugins` | List of enabled plugins. Either string class names or instances. Plugins added through the Kint class will be cached and need to work with multiple parser instances. See [plugins page]({{ site.baseurl }}/plugins/) for more information.
`Kint::$max_depth` | The maximum depth to parse. 0 for unlimited. Tweak this to balance performance and verbosity. Default 6.
`Kint::$aliases` | List of helper function aliases. Either string for a function name, or array of two strings for a static method.
`Kint::$app_root_dirs` | An array of paths to aliases. These will be replaced in the mini trace, backtraces, etc. Default maps `$_SERVER['DOCUMENT_ROOT']` to `<ROOT>`.
`Kint\Renderer\RichRenderer::$theme` | Which theme to use. One of the CSS files from `resources/compiled/`: `original`, `aante-light`, `solarized`, `solarized-dark` or the full path to a CSS file. Default `original.css`.

</section>
<section id="kint" markdown="1">

## Kint settings

Other Kint settings

`Kint::$cli_detection` | Whether to detect if it's being run in a CLI and adjust the renderer. Default `true`
`Kint::$display_called_from` | Whether to display the called from (mini trace) information. Default `true`
`Kint::$expanded` | Whether to expand objects by default. Default `false`
`Kint::$file_link_format` | A format to link source code paths to. Easiest way to open files in PhpStorm directly from Kint output is to install the PhpStorm [`Remote Call`](https://plugins.jetbrains.com/plugin/6027-remote-call) plugin from inside the IDE settings and set `Kint::$file_link_format = 'http://localhost:8091/?message=%f:%l';`. Default `ini_get('xdebug.file_link_format')`
`Kint::$mode_default_cli` | The mode to select automatically when `$enabled_mode` and `$cli_detection` are true. Default `Kint::MODE_CLI`
`Kint::$mode_default` | The mode to select automatically when `$enabled_mode` is true. Default `Kint::MODE_RICH`
`Kint::$renderers` | A map of render modes to renderer classes.
`Kint::$return` | Whether to return or echo the output. Default `false`

</section>
<section id="encoding" markdown="1">

`Kint\Object\BlobObject::$char_encodings` | A list of multibyte character encodings to try to identify to be passed to `mb_detect_encoding`. Default includes `ASCII` and `UTF-8`
`Kint\Object\BlobObject::$legacy_encodings` | A list of windows single-byte and other ambiguous encodings. If no multibyte encoding was detected Kint will assume the first matching legacy_encoding is correct.

</section>
<section id="rich" markdown="1">

## Rich renderer settings

`Kint\Renderer\RichRenderer::$access_paths` | Enable or disable the showing of access paths. Default `true`
`Kint\Renderer\RichRenderer::$always_pre_render` | Whether to render CSS and JS on every dump. Default `false`
`Kint\Renderer\RichRenderer::$escape_types` | Enable or disable the escaping of types. Enabling will hurt performance, but allow you to use unicode text on non-utf8 web pages. Default `false`
`Kint\Renderer\RichRenderer::$object_plugins` | List of rich renderer object plugins
`Kint\Renderer\RichRenderer::$pre_render_sources` | List of callables to execute or strings to render before initial render.
`Kint\Renderer\RichRenderer::$sort` | Sort mode for object properties. Default `Kint\Renderer\Renderer::SORT_NONE`
`Kint\Renderer\RichRenderer::$strlen_max` | The maximum length of text to show in the bar. 0 to disable. Default 80.
`Kint\Renderer\RichRenderer::$tab_plugins` | List of rich renderer tab plugins
`Kint\Renderer\RichRenderer::$theme` | Which theme to use. One of the CSS files from `resources/compiled/` if using composer, or the full path to a CSS file. Default `original.css`

</section>
<section id="text" markdown="1">

## Text renderer settings

`Kint\Renderer\TextRenderer::$decorations` | Whether to render decorations around the header and footer. Default `true`
`Kint\Renderer\TextRenderer::$default_indent` | The indentation level in spaces. Default 4
`Kint\Renderer\TextRenderer::$default_width` | The width of header and footer decorations. Default 80
`Kint\Renderer\TextRenderer::$parser_plugin_whitelist` | List of whitelisted parser plugins. Any parser plugins not in this whitelist will not be added to the parser for performance reasons, and because their output isn't used in the text renderer.
`Kint\Renderer\TextRenderer::$plugins` | List of text renderer plugins.
`Kint\Renderer\TextRenderer::$sort` | Sort mode for object properties. Default `Kint\Renderer\Renderer::SORT_NONE`
`Kint\Renderer\TextRenderer::$strlen_max` | The maximum length of text. 0 to disable. Default 0.

---

The plain renderer inherits from the text renderer.

`Kint\Renderer\PlainRenderer::$always_pre_render` | Whether to render CSS and JS on every dump. Default `false`
`Kint\Renderer\PlainRenderer::$disable_utf8` | Whether to disable the utf-8 parts of the decorations and use htmlentities instead. Vastly increases output size. If <a href="http://utf8everywhere.org/" target="_blank">your web page isn't in utf-8</a>, set this to `true`. Default `false`
`Kint\Renderer\PlainRenderer::$pre_render_sources` | List of callables to execute or strings to render before initial render.
`Kint\Renderer\PlainRenderer::$theme` | Which theme to use. One of the CSS files from `resources/compiled/` if using composer, or the full path to a CSS file. Default `plain.css`

---

The CLI renderer inherits from the text renderer.

`Kint\Renderer\CliRenderer::$cli_colors` | Whether to print color codes. Default `true`
`Kint\Renderer\CliRenderer::$force_utf8` | Whether to force default utf-8 output on windows. Default `false`
`Kint\Renderer\CliRenderer::$detect_width` | Whether to detect the terminal width on startup and adjust the header and footer widths accordingly. Default `true`
`Kint\Renderer\CliRenderer::$min_terminal_width` | What minimum width must we detect before ignoring the detected width altogether. Default 40.

</section>

<h2><a href="{{ site.baseurl }}/contrib/">Improving Kint &raquo;</a></h2>

</div>
