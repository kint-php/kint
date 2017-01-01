---
permalink: /contrib/
title: Improving Kint
---

<div id="leftmenu" class="col-sm-4 col-md-3 hidden-xs">
<ul class="nav nav-list side-navigation" data-spy="affix" data-offset-top="{{ site.affix_offset }}">
    <li><a href="#guidelines">Contributor guidelines</a></li>
    <li><a href="#devenv">Setting up a dev environment</a></li>
    <li><a href="#lints">Format and Build</a></li>
    <li><a href="#architecture">Kint architecture and design</a></li>
    <li><a href="{{ site.baseurl }}/plugins/">Writing plugins &raquo;</a></li>
</ul>
</div>
<div class="col-sm-8 col-md-9" markdown="1">

# Improving Kint

<section id="guidelines" markdown="1">

## Contributor guidelines

You can see <a href="https://github.com/kint-php/kint/blob/master/CONTRIBUTING.md" target="_blank">a full list of contributor guidelines</a> in the repository. The short (And possibly out of date) version is here:

* Don't make code that doesn't run on PHP 5.1.2
* Don't make code that doesn't run on anything after that either
* Don't make bad code
* Don't break BC
* If you make changes to JS/CSS make sure you rebuild the resources
* If you make changes to anything at all make sure you run the formatter
* Default plugins should only handle PHP functionality
* Don't add any obscure edge-cases (Especially if they can be implemented by the end-user)
* Don't display incorrect information
* Arrays may contain references. Objects are always references. **Don't alter user input in the parser.**

</section>
<section id="devenv" markdown="1">

## Setting up a dev environment

For developing on core Kint you'll need three things:

1. Composer (PHP)
2. Npm (NodeJS)
3. Bundler (Ruby)

If you don't have composer <a href="https://getcomposer.org/" target="_blank">do yourself a favor and learn it</a>. If you don't have npm or bundler that's understandable.

In any case, Kint uses composer, npm, and bundler for development tools. Composer calls npm and bundler under the assumption that they're in your `$PATH`.

If all of these are installed simply run `composer install` and all your dependencies will be installed automatically.

</section>
<section id="lints" markdown="1">

## Format and Build

The reason you need composer, npm, and bundler is because they'll let us write sloppy code and have the computer fix it for us!

Specifically, composer runs <a href="https://github.com/FriendsOfPHP/PHP-CS-Fixer" target="_blank">php-cs-fixer</a> to reformat PHP code to a consistent style, while npm and bundler do the same for the JS and CSS files.

Since code style is a good thing you should always run `composer format` before a commit to fix any poor code style. If you don't CI will complain.

Since we deliver the compiled JS/CSS files in our repo you should always run `composer build` before a commit to rebuild the JS/CSS files. If you don't CI will complain.

### A word on JS

In JS we're using <a href="http://eslint.org/" target="_blank">eslint</a> to format our code, but unlike `php-cs-fixer` it doesn't always fix the code for you. Sometimes it will just complain loudly. In the event this happens you'll have to read the errors and fix them by hand.

</section>
<section id="architecture" markdown="1">

## Kint architecture and design

Kint's architecture can roughly be split into three separate sections.

* Parsing
* Rendering
* The Kint helper

### Parsing

The `Kint_Parser` class is instantiated and loaded up with `Kint_Parser_Plugin` objects and let loose on the incoming data. It returns a `Kint_Object` containing information about the input data's type, name, visibility, access path, and a list of `Kint_Object_Representations` of the data. (Among other things)

When it's done parsing it loads up all the applicable `Kint_Parser_Plugin` and lets them alter the `Kint_Object` at will. By the time the object gets back it will likely have even more representations of data. Each of the representations may in turn hold more `Kint_Object`.

In addition to altering the data in the `Kint_Object`, the plugins may add to the `hints` arrays on both the `Kint_Object` and the `Kint_Object_Representation` to inform the renderer as to their options.

`Kint_Object` can be extended to alter behavior at the object level. Again this is mostly of use in the rendering stage.

### Rendering

After you have a `Kint_Object` representing the variable, you need to render it. What the renderer does is somewhat irrelevant. It could print out text or it could print out HTML. It could store the dumped data in a database or email it to the ISS.

If you're using the parser on it's own you can do whatever you want with the data, but if you want to make a renderer that's easily integrated into Kint you'll want to extend the `Kint_Renderer` class.

Implementing it is fairly simple: It has three methods that return strings to output.

* `preRender()`
* `render(Kint_Object)`
* `postRender()`

The renderer typically uses text hints stored in the `$object->hints` and `$representation->hints` arrays by the parser and its plugins to inform its rendering behavior. For example, the `blacklist` hint causes the rich renderer to draw a crossed out button instead of a `+`.

### The Kint helper

The `Kint` class is a helper for dumping data. As you've read above, the process for a standard Kint dump goes like this:

* Create a renderer with state information (Calling file/line number, modifiers, parameter names, etc)
* Call the renderer's `preRender` method and echo the output
* Create a parser
* Add all the plugins you want to use to the parser
* For each variable you want to dump
    * Pass it into the parser
    * Pass the parser's output `Kint_Object` to the renderer and echo the output
* Call the renderers `postRender` method and echo the output

This is a big list, and really painful to do on your own. The `Kint` class handles all of this for you. All the modifiers and most general options live in the `Kint` class, and the `Kint` class has only static properties and methods.

</section>

<h2><a href="{{ site.baseurl }}/plugins/">Writing plugins &raquo;</a></h2>

</div>

