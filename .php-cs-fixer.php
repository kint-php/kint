<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2013 Jonathan Vollebregt (jnvsor@gmail.com), Rokas Šleinius (raveren@gmail.com)
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy of
 * this software and associated documentation files (the "Software"), to deal in
 * the Software without restriction, including without limitation the rights to
 * use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of
 * the Software, and to permit persons to whom the Software is furnished to do so,
 * subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS
 * FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR
 * COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER
 * IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN
 * CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */

use PhpCsFixer\Runner\Parallel\ParallelConfigFactory;

return (new PhpCsFixer\Config())
    ->setRiskyAllowed(true)
    ->setParallelConfig(ParallelConfigFactory::detect())
    ->setUnsupportedPhpVersionAllowed(true)
    ->setRules([
        '@Symfony' => true,
        'array_indentation' => true,
        'combine_consecutive_issets' => true,
        'combine_consecutive_unsets' => true,
        'dir_constant' => true,
        'string_implicit_backslashes' => [
            'single_quoted' => 'escape',
        ],
        'explicit_indirect_variable' => true,
        'explicit_string_variable' => true,
        'fully_qualified_strict_types' => true,
        'header_comment' => [
            'header' => \trim(\file_get_contents(__DIR__.'/LICENSE')),
        ],
        'is_null' => true,
        'list_syntax' => true,
        'method_chaining_indentation' => true,
        'modernize_types_casting' => true,
        'multiline_comment_opening_closing' => true,
        'multiline_whitespace_before_semicolons' => true,
        'native_function_invocation' => [
            'include' => ['@all'],
        ],
        'no_alias_functions' => true,
        'no_homoglyph_names' => true,
        'no_superfluous_elseif' => true,
        'no_unreachable_default_argument_value' => true,
        'no_useless_else' => true,
        'no_useless_return' => true,
        'non_printable_character' => true,
        'ordered_class_elements' => [
            'order' => [
                'use_trait',
                'constant_public',
                'constant_protected',
                'constant_private',
                'property_public_static',
                'property_protected_static',
                'property_private_static',
                'property_public',
                'property_protected',
                'property_private',
                'construct',
                'destruct',
                'magic',
                'phpunit',
                'method_public',
                'method_protected',
                'method_private',
                'method_public_static',
                'method_protected_static',
                'method_private_static',
            ],
            'sort_algorithm' => 'none',
        ],
        'php_unit_construct' => [
            'assertions' => ['assertSame', 'assertNotSame'],
        ],
        'php_unit_dedicate_assert' => true,
        'php_unit_namespaced' => true,
        'php_unit_set_up_tear_down_visibility' => true,
        'php_unit_test_annotation' => true,
        'php_unit_test_class_requires_covers' => true,
        'phpdoc_to_comment' => false, // Required for certain Psalm workarounds
        'phpdoc_types_order' => true,
        'strict_param' => true,
        'string_line_ending' => true,
        'self_accessor' => true,
        'declare_strict_types' => true,
        'global_namespace_import' => [
            'import_constants' => false,
            'import_functions' => false,
            'import_classes' => true,
        ],
        'operator_linebreak' => [
            'only_booleans' => true,
            'position' => 'end',
        ],
        'no_null_property_initialization' => false,
        'trailing_comma_in_multiline' => [
            'elements' => ['array_destructuring', 'arrays', 'match'],
        ],
    ])
    ->setFinder(
        PhpCsFixer\Finder::create()
            ->in(__DIR__)
            ->path(['#^src/#', '#^tests/#'])
            ->notPath(['#^tests/Fixtures/#'])
            ->append(['build.php', 'init_helpers.php', 'init_phar.php', 'init.php', __FILE__])
    );
