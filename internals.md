---
permalink: /internals/
title: Kint internals
---

<div id="leftmenu" class="col-sm-4 col-md-3 hidden-xs">
<ul class="nav nav-list side-navigation" data-spy="affix" data-offset-top="{{ site.affix_offset }}">
    <li><a href="#overview">Overview</a></li>
    <li><a href="#parser">Parser</a></li>
    <li><a href="#value">Values</a></li>
    <li><a href="#renderer">Renderer</a></li>
    <li><a href="#guidelines">Contributor guidelines</a></li>
    <li><a href="{{ site.baseurl }}/writing-plugins/">Writing plugins &raquo;</a></li>
</ul>
</div>
<div class="col-sm-8 col-md-9" markdown="1">

# Kint internals

<section id="overview" markdown="1">

## Overview

Kint is typically used as an install-and-forget dumping tool but has plenty of options for customization and to be used as a library.

For both contributing to Kint and for customizing behavior a certain familiarity with Kint's internals is required. This page provides a brief overview of what goes on under the hood.

First, a `Kint\Value\Context\ContextInterface` is passed into `Kint\Parser` along with a PHP value. The parser then creates a `Kint\Value\AbstractValue` to represent the value and returns it.

Once the `AbstractValue` has been built it's passed to a `Kint\Renderer\RendererInterface` which produces a string output.

Sticking all of this together and creating the initial `ContextInterface` is handled by the `Kint\Kint` facade (which is aliased to `\Kint`)

</section>
<section id="parser" markdown="1">

## Parser

The parser receives an input value and a `ContextInterface` as parameters. It calls `gettype()` to get the first piece of information about the input data.

The parser loops through its `Kint\Parser\PluginBeginInterface` plugins and if one of them returns an `AbstractValue` this is the value returned.

If not, it switches on the type and calls one of various specialized parsing routines. In the case of arrays and objects this will result in recursive parsing of child values. Since references are a thing the parser has to detect recursive data structures to prevent infinite recursion.

After the basic parse is done and the `AbstractValue` has been created the parser loops through its `PluginCompleteInterface` plugins which can each alter the value and returns the final result.

</section>
<section id="value" markdown="1">

## Values

Values all must inherit from `AbstractValue`. All values have a string `$type` and an integer `$flags` which contains a bitmask of states common to all values (Such as when a value is blacklisted or reaches the depth limit)

Depending on the type of data a value may have more properties. A `StringValue` has a string, an encoding, and a length. Meanwhile an `ArrayValue` has a size and a list of child `AbstractValue`

Once the parser receives a parameter you no longer have information from the parent scope: what it was called, if it was a child of an array, if it was a private property, or if it was a reference. This information is passed into the parser from the parent scope as a `ContextInterface` and stored in `$context`

Lastly, `$representations` stores a list of `Kint\Value\Representation\RepresentationInterface` attached to the value, which allows the rich renderer to have multiple tabs showing different representations of the same data.

</section>
<section id="renderer" markdown="1">

## Renderer

The renderer takes values produced by the parser and outputs a string. The exact process varies depending on the renderer. It could print out text or it could print out HTML. It could store the dumped data in a database or email it to the ISS.

### RichRenderer

On its own the `Kint\Renderer\RichRenderer` only renders 3 types of representation: `StringRepresentation`, `ValueRepresentation` and `ContainerRepresentation`.

It has two sets of plugins: A list of `Kint\Renderer\Rich\ValuePluginInterface` for custom rendering of values, and a list of `TabPluginInterface` for custom rendering of representations in a value.

### TextRenderer

The `TextRenderer` ignores representations entirely and provides the most succinct explanation possible. For scalar values it will show the value, for arrays it will show the children, and for objects it will show the "Children".

An objects children are typically the properties but plugins can overwrite them in certain cases. For instance, if an object has no properties the `IteratorPlugin` will set the iterator contents to be the children.

`TextRenderer` has a list of `Kint\Renderer\Text\PluginInterface` which can alter the rendering of values, and a whitelist of parser plugins to stop all of the plugins from running on every dump.

The `CliRenderer` and `PlainRenderer` extend `TextRenderer` and provide additional features like colors.

</section>
<section id="guidelines" markdown="1">

## Contributor guidelines

You can see <a href="https://github.com/kint-php/kint/blob/master/CONTRIBUTING.md" target="_blank">a full list of contributor guidelines</a> in the repository.

Since the parser takes PHP data by reference, and since input data can contain references itself, you can very easily break your application by accidentally altering data inside the dump. Be **very careful** not to alter user data.

### Setting up a dev environment

For developing on core Kint you'll need two things:

1. Composer (PHP)
2. Npm (NodeJS)

If you don't have composer <a href="https://getcomposer.org/" target="_blank">do yourself a favor and learn it</a>. If you don't have npm that's understandable.

In any case, Kint uses composer and npm for development tools. Composer calls npm under the assumption that it's in your `$PATH`.

If all of these are installed simply run `composer install` and all your dependencies will be installed automatically.

### Format and Build

* Static analysis can find bugs in code when even unit tests don't. You should always run `composer analyze` before a commit and fix any errors.
* Tests have to pass. You should always run `./vendor/bin/phpunit tests` before a commit and fix any errors.
* Since code style is a good thing you should always run `composer format` before a commit to fix any inconsistent code style.
* Since we deliver reproducibly compiled JS/CSS/PHAR files in our repo you should always run `composer build` before a commit to rebuild the files and commit them.

If you fail any of these steps CI will complain.

</section>

<h2><a href="{{ site.baseurl }}/writing-plugins/">Writing plugins &raquo;</a></h2>

</div>
