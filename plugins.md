---
permalink: /plugins/
title: Plugins
---

<div id="leftmenu" class="col-sm-4 col-md-3 hidden-xs">
<ul class="nav nav-list side-navigation" data-spy="affix" data-offset-top="{{ site.affix_offset }}">
    <li><a href="#notable">Notable plugins</a></li>
    <li><a href="#opt-in">Opt-in plugins</a></li>
    <li><a href="#rest">Default plugins</a></li>
    <li><a href="#text">Text mode plugins</a></li>
    <li><a href="{{ site.baseurl }}/settings/">Kint settings &raquo;</a></li>
</ul>
</div>
<div class="col-sm-8 col-md-9" markdown="1">

# Plugins

Kint ships with a ton of plugins for built-in PHP functionality. Most of these are for data retrieval -- gathering extra information about a variable or displaying it in a different way.

You can enable or disable plugins in the `Kint::$plugins` array.

<section id="notable" markdown="1">

## Notable plugins

### The blacklist plugin

Lets you hide information you don't want dumped, and improve performance by skipping heavy objects.

`Kint_Parser_Blacklist::$blacklist` | List of classes to blacklist.
`Kint_Parser_Blacklist::$shallow_blacklist` | List of classes to blacklist unless you dump it specifically. This is great for heavy objects that impact performance like DI containers.

### The microtime plugin

Automatically shows memory and timing information when you use it. Call `Kint_Parser_Microtime::clean()` to reset the counters.

![]({{ site.baseurl }}/images/plugin-microtime.png)

</section>
<section id="opt-in" markdown="1">

## Opt-in plugins

These plugins are off by default.

### Kint_Parser_Binary

Displays non-utf8 strings like `xxd`

![]({{ site.baseurl }}/images/plugin-binary.png)

`Kint_Renderer_Rich_Binary::$line_length` | The amount of bytes per line of output. Default `0x10`
`Kint_Renderer_Rich_Binary::$chunk_length` | The amount of bytes per chunk of output. Default `0x4`

### Kint_Parser_Serialize

Decodes serialized strings.

Because deserializing objects can pose a security risk, the following options are available.

`Kint_Parser_Serialize::$safe_mode` | Don't allow deserialization of arrays or objects. Default true.
`Kint_Parser_Serialize::$options` | Options to pass to <a href="http://php.net/manual/en/function.unserialize.php#refsect1-function.unserialize-parameters" target="_blank">unserialize</a>. Only for PHP7.

### Kint_Parser_DOMNode &amp; Kint_Parser_DOMIterator

Add support for DOMDocument parsing.

`Kint_Parser_DOMNode::$blacklist` | DOMNode properties to skip parsing. This is here because the DOMDocument system can introduce a huge amount of recursion.
`Kint_Parser_DOMNode::$verbose` | Show methods and properties. Default false.

</section>
<section id="rest" markdown="1">

## Default plugins

These are the remaining Kint parsers. They are on by default.

### Kint_Parser_Base64

Decodes base64 encoded strings.

`Kint_Parser_Base64::$min_length_hard` | Minimum length before the binary parser will start. Default 16.
`Kint_Parser_Base64::$min_length_soft` | Minimum length before the binary representation will take precedence over the string representation. Default 50.

### Kint_Parser_ClassMethods

Shows methods available for an object.

### Kint_Parser_ClassStatics

Shows statics and constants for an object.

### Kint_Parser_Closure

Shows properties and uses for a closure.

### Kint_Parser_Color

Detects a color string, then shows a color swatch and different ways to write the color.

`Kint_Object_Color::$color_map` | A map of HTML color names to hex codes

### Kint_Parser_DateTime

Shows the formatted datetime inline, before unfolding.

### Kint_Parser_FsPath

Detects server file paths and shows information about them. See also Kint_Parser_SplFileInfo.

### Kint_Parser_Iterator

Shows the contents of an iterator.

`Kint_Parser_Iterator::$blacklist` | Classes to prevent iterating over. Default includes `PDOStatement`, `DOMNodeList`, `DOMNamedNodeMap`.

### Kint_Parser_Json

Decodes JSON encoded strings.

### Kint_Parser_SimpleXMLElement

Displays SimpleXMLElements with tabs for children and attributes.

`Kint_Parser_SimpleXMLElement::$verbose` | Show methods and properties. Default false.

### Kint_Parser_SplFileInfo

Shows information about the file.

### Kint_Parser_SplObjectStorage

Shows the SplObjectStorage contents.

### Kint_Parser_Stream

Shows stream metadata for stream resources.

### Kint_Parser_Table

Shows table-like multidimensional arrays as a table.

`Kint_Renderer_Rich_Table::$respect_str_length` | Whether the cells of the table should respect `Kint_Renderer_Rich::$strlen_max`. Default `true`

### Kint_Parser_Throwable

Shows the exception or error message inline, before unfolding.

### Kint_Parser_Timestamp

Detects integers that seem like timestamps (Between 9 and 10 digits) and formats the time.

`Kint_Parser_Timestamp::$blacklist` | Integers to ignore. Default includes common integers like `PHP_INT_MAX`

### Kint_Parser_ToString

Shows the string representation of an object with a `__toString()` method.

`Kint_Parser_ToString::$blacklist` | Objects to ignore. Default includes `SimpleXMLElement`

### Kint_Parser_Trace

Detects a backtrace, and gathers information like stack frame source code snippets.

`Kint_Parser_Trace::$blacklist` | Function calls to skip. A string for a function, or an array of class name and method name. Default includes `spl_autoload_call`

### Kint_Parser_Xml

Detects an XML string and loads it.

`Kint_Parser_Xml::$parse_method` | One of `SimpleXML` or `DOMDocument`. DOMDocument is slower, and you'll need to enable `Kint_Parser_DOMNode` and `Kint_Parser_DOMIterator` for it to work. Default `SimpleXML`

</section>
<section id="text" markdown="1">

## Text mode plugins

Since text mode renderers can't show most of the parsed information anyway, the text renderer disables some plugins before parsing begins. `Kint_Renderer_Text::$parser_plugin_whitelist` is set to only allow the following plugins:

* `Kint_Parser_Blacklist`
* `Kint_Parser_Stream`
* `Kint_Parser_Trace`

</section>

<h2><a href="{{ site.baseurl }}/settings/">Kint settings &raquo;</a></h2>

</div>
