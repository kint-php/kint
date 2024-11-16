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

namespace Kint\Test;

use Kint\CallFinder;

/**
 * @coversNothing
 */
class CallFinderTest extends KintTestCase
{
    public function sourceProvider()
    {
        $data = [];

        $data['function'] = [
            '<?php

            !@+-~test($wat, $woot[$wat] + 4);
            ',
            'line' => 3,
            'function' => 'Test',
            'result' => [
                [
                    'modifiers' => ['~', '-', '+', '@', '!'],
                    'parameters' => [
                        [
                            'path' => '$wat',
                            'name' => '$wat',
                            'expression' => false,
                            'literal' => false,
                            'new_without_parens' => false,
                        ],
                        [
                            'path' => '$woot[$wat] + 4',
                            'name' => '$woot[...] + 4',
                            'expression' => true,
                            'literal' => false,
                            'new_without_parens' => false,
                        ],
                    ],
                ],
            ],
        ];

        $data['global function'] = [
            '<?php

            !@+-~\\test($var);
            ',
            'line' => 3,
            'function' => 'Test',
            'result' => [
                [
                    'modifiers' => ['~', '-', '+', '@', '!'],
                    'parameters' => [
                        [
                            'path' => '$var',
                            'name' => '$var',
                            'expression' => false,
                            'literal' => false,
                            'new_without_parens' => false,
                        ],
                    ],
                ],
            ],
        ];

        $data['static method'] = [
            '<?php

            !subspace\\C :: Method([], [ ], [ 1 ]);
            ',
            'line' => 3,
            'function' => ['namespace\\subspace\\c', 'method'],
            'result' => [
                [
                    'modifiers' => ['!'],
                    'parameters' => [
                        [
                            'path' => '[]',
                            'name' => '[]',
                            'expression' => false,
                            'literal' => true,
                            'new_without_parens' => false,
                        ],
                        [
                            'path' => '[ ]',
                            'name' => '[]',
                            'expression' => false,
                            'literal' => true,
                            'new_without_parens' => false,
                        ],
                        [
                            'path' => '[ 1 ]',
                            'name' => '[...]',
                            'expression' => false,
                            'literal' => false,
                            'new_without_parens' => false,
                        ],
                    ],
                ],
            ],
        ];

        $data['static method global namespace'] = [
            '<?php

            !\\C :: Method(1, $b, $gamma);
            ',
            'line' => 3,
            'function' => ['c', 'method'],
            'result' => [
                [
                    'modifiers' => ['!'],
                    'parameters' => [
                        [
                            'path' => '1',
                            'name' => '1',
                            'expression' => false,
                            'literal' => true,
                            'new_without_parens' => false,
                        ],
                        [
                            'path' => '$b',
                            'name' => '$b',
                            'expression' => false,
                            'literal' => false,
                            'new_without_parens' => false,
                        ],
                        [
                            'path' => '$gamma',
                            'name' => '$gamma',
                            'expression' => false,
                            'literal' => false,
                            'new_without_parens' => false,
                        ],
                    ],
                ],
            ],
        ];

        $data['static method wrong class'] = [
            '<?php

            !subspace\\C :: Method([], [ ], [ 1 ]);
            ',
            'line' => 3,
            'function' => ['namespace\\subspace\\d', 'method'],
            'result' => [],
        ];

        $data['static method no class'] = [
            '<?php

            Method($val);
            ',
            'line' => 3,
            'function' => ['namespace\\subspace\\d', 'method'],
            'result' => [],
        ];

        $data['multiple on one line'] = [
            '<?php

            !Test($val); @test([ ], $_SERVER["REMOTE_ADDR"]);
            ',
            'line' => 3,
            'function' => 'test',
            'result' => [
                [
                    'modifiers' => ['!'],
                    'parameters' => [
                        [
                            'path' => '$val',
                            'name' => '$val',
                            'expression' => false,
                            'literal' => false,
                            'new_without_parens' => false,
                        ],
                    ],
                ],
                [
                    'modifiers' => ['@'],
                    'parameters' => [
                        [
                            'path' => '[ ]',
                            'name' => '[]',
                            'expression' => false,
                            'literal' => true,
                            'new_without_parens' => false,
                        ],
                        [
                            'path' => '$_SERVER["REMOTE_ADDR"]',
                            'name' => '$_SERVER[...]',
                            'expression' => false,
                            'literal' => false,
                            'new_without_parens' => false,
                        ],
                    ],
                ],
            ],
        ];

        $data['one on multiple lines start'] = [
            '<?php

            !c::method(
                // Wat,
                $val,
                $_SERVER[$val]
            );
            ',
            'line' => 3,
            'function' => ['namespace\\subspace\\C', 'Method'],
            'result' => [
                [
                    'modifiers' => ['!'],
                    'parameters' => [
                        [
                            'path' => '$val',
                            'name' => '$val',
                            'expression' => false,
                            'literal' => false,
                            'new_without_parens' => false,
                        ],
                        [
                            'path' => '$_SERVER[$val]',
                            'name' => '$_SERVER[...]',
                            'expression' => false,
                            'literal' => false,
                            'new_without_parens' => false,
                        ],
                    ],
                ],
            ],
        ];

        $data['one on multiple lines end'] = $data['one on multiple lines start'];
        $data['one on multiple lines end']['line'] = 6;
        if (KINT_PHP82) {
            $data['one on multiple lines end']['result'] = [];
        }

        $data['one on multiple lines mid'] = $data['one on multiple lines start'];
        $data['one on multiple lines mid']['line'] = 5;
        if (KINT_PHP82) {
            $data['one on multiple lines mid']['result'] = [];
        }

        $data['nested calls'] = [
            '<?php

            !test(
                @test($val),
                $_SERVER[$val]
            );
            ',
            'line' => 4,
            'function' => 'test',
            'result' => [
                [
                    'modifiers' => ['!'],
                    'parameters' => [
                        [
                            'path' => '@test($val)',
                            'name' => '@test(...)',
                            'expression' => false,
                            'literal' => false,
                            'new_without_parens' => false,
                        ],
                        [
                            'path' => '$_SERVER[$val]',
                            'name' => '$_SERVER[...]',
                            'expression' => false,
                            'literal' => false,
                            'new_without_parens' => false,
                        ],
                    ],
                ],
                [
                    'modifiers' => ['@'],
                    'parameters' => [
                        [
                            'path' => '$val',
                            'name' => '$val',
                            'expression' => false,
                            'literal' => false,
                            'new_without_parens' => false,
                        ],
                    ],
                ],
            ],
        ];
        if (KINT_PHP82) {
            \array_shift($data['nested calls']['result']);
        }

        $data['nested calls, single matching line'] = $data['nested calls'];
        $data['nested calls, single matching line']['line'] = 5;
        if (KINT_PHP82) {
            $data['nested calls, single matching line']['result'] = [];
        } else {
            unset($data['nested calls, single matching line']['result'][1]);
        }

        $data['multiple line params'] = [
            '<?php

            test(
                $a /* mixed */ + /** in */ $b ?>comments<?php + // test
                $c
            );
            ',
            'line' => 3,
            'function' => 'test',
            'result' => [
                [
                    'modifiers' => [],
                    'parameters' => [
                        [
                            'path' => '$a /* mixed */ + /** in */ $b ?>comments<?php + // test
                $c',
                            'name' => '$a + $b + $c',
                            'expression' => true,
                            'literal' => false,
                            'new_without_parens' => false,
                        ],
                    ],
                ],
            ],
        ];

        $data['space stripping'] = [
            '<?php

            test(  $var [ "key" ] +  /* test */  $var2  +$var3);',
            'line' => 3,
            'function' => 'test',
            'result' => [
                [
                    'modifiers' => [],
                    'parameters' => [
                        [
                            'path' => '$var [ "key" ] +  /* test */  $var2  +$var3',
                            'name' => '$var[...] + $var2 +$var3',
                            'expression' => true,
                            'literal' => false,
                            'new_without_parens' => false,
                        ],
                    ],
                ],
            ],
        ];

        $data['bstring types'] = [
            '<?php

            test(
                \'\',
                "",
                \'value\',
                "value",
                \'$value\',
                "$value",
                b\'\',
                b"",
                b\'value\',
                b"value",
                b\'$value\',
                b"$value",
            );',
            'line' => 3,
            'function' => 'test',
            'result' => [
                [
                    'modifiers' => [],
                    'parameters' => [
                        [
                            'path' => "''",
                            'name' => "''",
                            'expression' => false,
                            'literal' => true,
                            'new_without_parens' => false,
                        ],
                        [
                            'path' => '""',
                            'name' => '""',
                            'expression' => false,
                            'literal' => true,
                            'new_without_parens' => false,
                        ],
                        [
                            'path' => "'value'",
                            'name' => "'...'",
                            'expression' => false,
                            'literal' => true,
                            'new_without_parens' => false,
                        ],
                        [
                            'path' => '"value"',
                            'name' => '"..."',
                            'expression' => false,
                            'literal' => true,
                            'new_without_parens' => false,
                        ],
                        [
                            'path' => "'\$value'",
                            'name' => "'...'",
                            'expression' => false,
                            'literal' => true,
                            'new_without_parens' => false,
                        ],
                        [
                            'path' => '"$value"',
                            'name' => '"..."',
                            'expression' => false,
                            'literal' => false,
                            'new_without_parens' => false,
                        ],
                        [
                            'path' => "b''",
                            'name' => "b''",
                            'expression' => false,
                            'literal' => true,
                            'new_without_parens' => false,
                        ],
                        [
                            'path' => 'b""',
                            'name' => 'b""',
                            'expression' => false,
                            'literal' => true,
                            'new_without_parens' => false,
                        ],
                        [
                            'path' => "b'value'",
                            'name' => "b'...'",
                            'expression' => false,
                            'literal' => true,
                            'new_without_parens' => false,
                        ],
                        [
                            'path' => 'b"value"',
                            'name' => 'b"..."',
                            'expression' => false,
                            'literal' => true,
                            'new_without_parens' => false,
                        ],
                        [
                            'path' => "b'\$value'",
                            'name' => "b'...'",
                            'expression' => false,
                            'literal' => true,
                            'new_without_parens' => false,
                        ],
                        [
                            'path' => 'b"$value"',
                            'name' => 'b"..."',
                            'expression' => false,
                            'literal' => false,
                            'new_without_parens' => false,
                        ],
                    ],
                ],
            ],
        ];

        $data['expressions'] = [
            '<?php

d(
    true?$_SERVER:array(),
    $x=1,
    $x+1,
    $x==1,
    $x-1,
    $x*1,
    $x/1,
    $x%1,
    $x++,
    $x--,
    $x**4,
    ~$x,
    $x instanceof bltest,
    !$x,
    $x%1,
    $_SERVER["HTTP_HOST"],
    $_SERVER[ "HTTP_HOST" ],
    $_SERVER [ "HTTP_HOST" ],
    [] + [],
    new DateTime(),
    clone $db,
    array(),
    array( ),
    "string",
    [],
    [ ],
    ((((((("woot"))))))),
    ((((((())))))),
    true,
    TRUE,
    false,
    fAlSe,
    null,
    NuLL,
    test::TEST,
    \\test::TEST,
    test :: TEST,
    \\test :: TEST
);',
            'line' => 3,
            'function' => 'd',
            'result' => [
                [
                    'modifiers' => [],
                    'parameters' => [
                        [
                            'path' => 'true?$_SERVER:array()',
                            'name' => 'true?$_SERVER:array()',
                            'expression' => true,
                            'literal' => false,
                            'new_without_parens' => false,
                        ],
                        [
                            'path' => '$x=1',
                            'name' => '$x=1',
                            'expression' => true,
                            'literal' => false,
                            'new_without_parens' => false,
                        ],
                        [
                            'path' => '$x+1',
                            'name' => '$x+1',
                            'expression' => true,
                            'literal' => false,
                            'new_without_parens' => false,
                        ],
                        [
                            'path' => '$x==1',
                            'name' => '$x==1',
                            'expression' => true,
                            'literal' => false,
                            'new_without_parens' => false,
                        ],
                        [
                            'path' => '$x-1',
                            'name' => '$x-1',
                            'expression' => true,
                            'literal' => false,
                            'new_without_parens' => false,
                        ],
                        [
                            'path' => '$x*1',
                            'name' => '$x*1',
                            'expression' => true,
                            'literal' => false,
                            'new_without_parens' => false,
                        ],
                        [
                            'path' => '$x/1',
                            'name' => '$x/1',
                            'expression' => true,
                            'literal' => false,
                            'new_without_parens' => false,
                        ],
                        [
                            'path' => '$x%1',
                            'name' => '$x%1',
                            'expression' => true,
                            'literal' => false,
                            'new_without_parens' => false,
                        ],
                        [
                            'path' => '$x++',
                            'name' => '$x++',
                            'expression' => true,
                            'literal' => false,
                            'new_without_parens' => false,
                        ],
                        [
                            'path' => '$x--',
                            'name' => '$x--',
                            'expression' => true,
                            'literal' => false,
                            'new_without_parens' => false,
                        ],
                        [
                            'path' => '$x**4',
                            'name' => '$x**4',
                            'expression' => true,
                            'literal' => false,
                            'new_without_parens' => false,
                        ],
                        [
                            'path' => '~$x',
                            'name' => '~$x',
                            'expression' => true,
                            'literal' => false,
                            'new_without_parens' => false,
                        ],
                        [
                            'path' => '$x instanceof bltest',
                            'name' => '$x instanceof bltest',
                            'expression' => true,
                            'literal' => false,
                            'new_without_parens' => false,
                        ],
                        [
                            'path' => '!$x',
                            'name' => '!$x',
                            'expression' => true,
                            'literal' => false,
                            'new_without_parens' => false,
                        ],
                        [
                            'path' => '$x%1',
                            'name' => '$x%1',
                            'expression' => true,
                            'literal' => false,
                            'new_without_parens' => false,
                        ],
                        [
                            'path' => '$_SERVER["HTTP_HOST"]',
                            'name' => '$_SERVER[...]',
                            'expression' => false,
                            'literal' => false,
                            'new_without_parens' => false,
                        ],
                        [
                            'path' => '$_SERVER[ "HTTP_HOST" ]',
                            'name' => '$_SERVER[...]',
                            'expression' => false,
                            'literal' => false,
                            'new_without_parens' => false,
                        ],
                        [
                            'path' => '$_SERVER [ "HTTP_HOST" ]',
                            'name' => '$_SERVER[...]',
                            'expression' => false,
                            'literal' => false,
                            'new_without_parens' => false,
                        ],
                        [
                            'path' => '[] + []',
                            'name' => '[] + []',
                            'expression' => true,
                            'literal' => false,
                            'new_without_parens' => false,
                        ],
                        [
                            'path' => 'new DateTime()',
                            'name' => 'new DateTime()',
                            'expression' => !KINT_PHP84,
                            'literal' => false,
                            'new_without_parens' => false,
                        ],
                        [
                            'path' => 'clone $db',
                            'name' => 'clone $db',
                            'expression' => true,
                            'literal' => false,
                            'new_without_parens' => false,
                        ],
                        [
                            'path' => 'array()',
                            'name' => 'array()',
                            'expression' => false,
                            'literal' => true,
                            'new_without_parens' => false,
                        ],
                        [
                            'path' => 'array( )',
                            'name' => 'array()',
                            'expression' => false,
                            'literal' => true,
                            'new_without_parens' => false,
                        ],
                        [
                            'path' => '"string"',
                            'name' => '"..."',
                            'expression' => false,
                            'literal' => true,
                            'new_without_parens' => false,
                        ],
                        [
                            'path' => '[]',
                            'name' => '[]',
                            'expression' => false,
                            'literal' => true,
                            'new_without_parens' => false,
                        ],
                        [
                            'path' => '[ ]',
                            'name' => '[]',
                            'expression' => false,
                            'literal' => true,
                            'new_without_parens' => false,
                        ],
                        [
                            'path' => '((((((("woot")))))))',
                            'name' => '(...)',
                            'expression' => false,
                            'literal' => false,
                            'new_without_parens' => false,
                        ],
                        [
                            'path' => '((((((()))))))',
                            'name' => '(...)',
                            'expression' => false,
                            'literal' => false,
                            'new_without_parens' => false,
                        ],
                        [
                            'path' => 'true',
                            'name' => 'true',
                            'expression' => false,
                            'literal' => true,
                            'new_without_parens' => false,
                        ],
                        [
                            'path' => 'TRUE',
                            'name' => 'TRUE',
                            'expression' => false,
                            'literal' => true,
                            'new_without_parens' => false,
                        ],
                        [
                            'path' => 'false',
                            'name' => 'false',
                            'expression' => false,
                            'literal' => true,
                            'new_without_parens' => false,
                        ],
                        [
                            'path' => 'fAlSe',
                            'name' => 'fAlSe',
                            'expression' => false,
                            'literal' => true,
                            'new_without_parens' => false,
                        ],
                        [
                            'path' => 'null',
                            'name' => 'null',
                            'expression' => false,
                            'literal' => true,
                            'new_without_parens' => false,
                        ],
                        [
                            'path' => 'NuLL',
                            'name' => 'NuLL',
                            'expression' => false,
                            'literal' => true,
                            'new_without_parens' => false,
                        ],
                        [
                            'path' => 'test::TEST',
                            'name' => 'test::TEST',
                            'expression' => false,
                            'literal' => false,
                            'new_without_parens' => false,
                        ],
                        [
                            'path' => '\\test::TEST',
                            'name' => '\\test::TEST',
                            'expression' => false,
                            'literal' => false,
                            'new_without_parens' => false,
                        ],
                        [
                            'path' => 'test :: TEST',
                            'name' => 'test::TEST',
                            'expression' => false,
                            'literal' => false,
                            'new_without_parens' => false,
                        ],
                        [
                            'path' => '\\test :: TEST',
                            'name' => '\\test::TEST',
                            'expression' => false,
                            'literal' => false,
                            'new_without_parens' => false,
                        ],
                    ],
                ],
            ],
        ];

        $data['T_CURLY_OPEN in string'] = [
            '<?php

            test("string {$var} string");',
            'line' => 3,
            'function' => 'test',
            'result' => [
                [
                    'modifiers' => [],
                    'parameters' => [
                        [
                            'path' => '"string {$var} string"',
                            'name' => '"..."',
                            'expression' => false,
                            'literal' => false,
                            'new_without_parens' => false,
                        ],
                    ],
                ],
            ],
        ];

        $data['T_DOLLAR_OPEN_CURLY_BRACES, T_STRING_VARNAME in string'] = [
            '<?php

            test("string ${var} string");',
            'line' => 3,
            'function' => 'test',
            'result' => [
                [
                    'modifiers' => [],
                    'parameters' => [
                        [
                            'path' => '"string ${var} string"',
                            'name' => '"..."',
                            'expression' => false,
                            'literal' => false,
                            'new_without_parens' => false,
                        ],
                    ],
                ],
            ],
        ];

        $data['T_VARIABLE in string'] = [
            '<?php

            test("string $var string");',
            'line' => 3,
            'function' => 'test',
            'result' => [
                [
                    'modifiers' => [],
                    'parameters' => [
                        [
                            'path' => '"string $var string"',
                            'name' => '"..."',
                            'expression' => false,
                            'literal' => false,
                            'new_without_parens' => false,
                        ],
                    ],
                ],
            ],
        ];

        $data['strange token preceding'] = [
            '<?php

            $x &=test($val);',
            'line' => 3,
            'function' => 'test',
            'result' => [
                [
                    'modifiers' => [],
                    'parameters' => [
                        [
                            'path' => '$val',
                            'name' => '$val',
                            'expression' => false,
                            'literal' => false,
                            'new_without_parens' => false,
                        ],
                    ],
                ],
            ],
        ];

        $data['no real tokens following'] = [
            '<?php

            define("test", "woot");

            ?><?= test ?>',
            'line' => 5,
            'function' => 'test',
            'result' => [],
        ];

        $data['empty call'] = [
            '<?php

            test();',
            'line' => 3,
            'function' => 'test',
            'result' => [
                [
                    'modifiers' => [],
                    'parameters' => [],
                ],
            ],
        ];

        $data['whitespace call'] = [
            '<?php

            test(
                // Nothing here, but multiple tokens
            );',
            'line' => 3,
            'function' => 'test',
            'result' => [
                [
                    'modifiers' => [],
                    'parameters' => [],
                ],
            ],
        ];

        $data['non-function tokens'] = [
            '<?php

            echo test::test($a); $test2->test($b); test3::test; test($c);',
            'line' => 3,
            'function' => 'test',
            'result' => [
                [
                    'modifiers' => [],
                    'parameters' => [
                        [
                            'path' => '$c',
                            'name' => '$c',
                            'expression' => false,
                            'literal' => false,
                            'new_without_parens' => false,
                        ],
                    ],
                ],
            ],
        ];

        $data['whitespace hell'] = [
            '<?php

            ! Kint :: dump ( $val ) ; ',
            'line' => 3,
            'function' => ['Kint', 'dump'],
            'result' => [
                [
                    'modifiers' => ['!'],
                    'parameters' => [
                        [
                            'path' => '$val',
                            'name' => '$val',
                            'expression' => false,
                            'literal' => false,
                            'new_without_parens' => false,
                        ],
                    ],
                ],
            ],
        ];

        $data['whitespace hell 2'] = [
            '<?php

            + dump ( $val ) ; ',
            'line' => 3,
            'function' => 'dump',
            'result' => [
                [
                    'modifiers' => ['+'],
                    'parameters' => [
                        [
                            'path' => '$val',
                            'name' => '$val',
                            'expression' => false,
                            'literal' => false,
                            'new_without_parens' => false,
                        ],
                    ],
                ],
            ],
        ];

        $data['realtoken tweaking'] = [
            '<?php

            d((function () { return "woot"; })());',
            'line' => 3,
            'function' => 'd',
            'result' => [
                [
                    'modifiers' => [],
                    'parameters' => [
                        [
                            'path' => '(function () { return "woot"; })()',
                            'name' => '(...)()',
                            'expression' => false,
                            'literal' => false,
                            'new_without_parens' => false,
                        ],
                    ],
                ],
            ],
        ];

        $data['namespaced function'] = [
            '<?php

            X\\Y\\test($a, $b);',
            'line' => 3,
            'function' => 'test',
            'result' => [
                [
                    'modifiers' => [],
                    'parameters' => [
                        [
                            'path' => '$a',
                            'name' => '$a',
                            'expression' => false,
                            'literal' => false,
                            'new_without_parens' => false,
                        ],
                        [
                            'path' => '$b',
                            'name' => '$b',
                            'expression' => false,
                            'literal' => false,
                            'new_without_parens' => false,
                        ],
                    ],
                ],
            ],
        ];

        $data['namespaced method'] = [
            '<?php

            X\\Y::test($a, $b);',
            'line' => 3,
            'function' => ['X\\Y', 'test'],
            'result' => [
                [
                    'modifiers' => [],
                    'parameters' => [
                        [
                            'path' => '$a',
                            'name' => '$a',
                            'expression' => false,
                            'literal' => false,
                            'new_without_parens' => false,
                        ],
                        [
                            'path' => '$b',
                            'name' => '$b',
                            'expression' => false,
                            'literal' => false,
                            'new_without_parens' => false,
                        ],
                    ],
                ],
            ],
        ];

        $data['arg expansion'] = [
            '<?php

            test($args, ...$args);',
            'line' => 3,
            'function' => 'test',
            'result' => [
                [
                    'modifiers' => [],
                    'parameters' => [
                        [
                            'path' => '$args',
                            'name' => '$args',
                            'expression' => false,
                            'literal' => false,
                            'new_without_parens' => false,
                        ],
                        [
                            'path' => '...$args',
                            'name' => '...$args',
                            'expression' => false,
                            'literal' => false,
                            'new_without_parens' => false,
                        ],
                    ],
                ],
            ],
        ];

        $data['obj property'] = [
            '<?php

            test($args->prop);',
            'line' => 3,
            'function' => 'test',
            'result' => [
                [
                    'modifiers' => [],
                    'parameters' => [
                        [
                            'path' => '$args->prop',
                            'name' => '$args->prop',
                            'expression' => false,
                            'literal' => false,
                            'new_without_parens' => false,
                        ],
                    ],
                ],
            ],
        ];

        $data['obj property complex'] = [
            '<?php

            test($args->{$propname});',
            'line' => 3,
            'function' => 'test',
            'result' => [
                [
                    'modifiers' => [],
                    'parameters' => [
                        [
                            'path' => '$args->{$propname}',
                            'name' => '$args->{...}',
                            'expression' => false,
                            'literal' => false,
                            'new_without_parens' => false,
                        ],
                    ],
                ],
            ],
        ];

        $data['inline closure'] = [
            '<?php

            test(function ($a) { return $a * 2; });',
            'line' => 3,
            'function' => 'test',
            'result' => [
                [
                    'modifiers' => [],
                    'parameters' => [
                        [
                            'path' => 'function ($a) { return $a * 2; }',
                            'name' => 'function (...) {...}',
                            'expression' => true,
                            'literal' => false,
                            'new_without_parens' => false,
                        ],
                    ],
                ],
            ],
        ];

        $data['inline closure with use'] = [
            '<?php

test(function ($a) use ($b)    {
    return $a * 2;
});',
            'line' => 3,
            'function' => 'test',
            'result' => [
                [
                    'modifiers' => [],
                    'parameters' => [
                        [
                            'path' => 'function ($a) use ($b)    {
    return $a * 2;
}',
                            'name' => 'function (...) use (...) {...}',
                            'expression' => true,
                            'literal' => false,
                            'new_without_parens' => false,
                        ],
                    ],
                ],
            ],
        ];

        $data['unreadable classname'] = [
            '<?php

            __CLASS__::test($a);',
            'line' => 3,
            'function' => ['testclass', 'test'],
            'result' => [],
        ];

        $data['trailing comma'] = [
            '<?php

            test(
                $a,
                $b,
            );',
            'line' => 3,
            'function' => 'test',
            'result' => [
                [
                    'modifiers' => [],
                    'parameters' => [
                        [
                            'path' => '$a',
                            'name' => '$a',
                            'expression' => false,
                            'literal' => false,
                            'new_without_parens' => false,
                        ],
                        [
                            'path' => '$b',
                            'name' => '$b',
                            'expression' => false,
                            'literal' => false,
                            'new_without_parens' => false,
                        ],
                    ],
                ],
            ],
        ];

        $data['arrow closure'] = [
            '<?php

            test(fn ($a) => $a * $b);',
            'line' => 3,
            'function' => 'test',
            'result' => [
                [
                    'modifiers' => [],
                    'parameters' => [
                        [
                            'path' => 'fn ($a) => $a * $b',
                            'name' => 'fn (...) => $a * $b',
                            'expression' => true,
                            'literal' => false,
                            'new_without_parens' => false,
                        ],
                    ],
                ],
            ],
        ];

        $data['null_assign_op'] = [
            '<?php

            test($a ??= "1234");',
            'line' => 3,
            'function' => 'test',
            'result' => [
                [
                    'modifiers' => [],
                    'parameters' => [
                        [
                            'path' => '$a ??= "1234"',
                            'name' => '$a ??= "..."',
                            'expression' => true,
                            'literal' => false,
                            'new_without_parens' => false,
                        ],
                    ],
                ],
            ],
        ];

        $data['nested arrow closure'] = [
            '<?php

            test(fn($t) => test($t));',
            'line' => 3,
            'function' => 'test',
            'result' => [
                [
                    'modifiers' => [],
                    'parameters' => [
                        [
                            'path' => 'fn($t) => test($t)',
                            'name' => 'fn(...) => test(...)',
                            'expression' => true,
                            'literal' => false,
                            'new_without_parens' => false,
                        ],
                    ],
                ],
                [
                    'modifiers' => [],
                    'parameters' => [
                        [
                            'path' => '$t',
                            'name' => '$t',
                            'expression' => false,
                            'literal' => false,
                            'new_without_parens' => false,
                        ],
                    ],
                ],
            ],
        ];

        if (KINT_PHP80) {
            $data['attributes'] = [
                '<?php

                test(new #[TestAttribute] class {});',
                'line' => 3,
                'function' => 'test',
                'result' => [
                    [
                        'modifiers' => [],
                        'parameters' => [
                            [
                                'path' => 'new #[TestAttribute] class {}',
                                'name' => 'new #[...] class {}',
                                'expression' => !KINT_PHP84,
                                'literal' => false,
                                'new_without_parens' => false,
                            ],
                        ],
                    ],
                ],
            ];

            $data['nested attributes'] = [
                '<?php

                test(new #[TestAttribute(#[TestAttributeChild])] class {});',
                'line' => 3,
                'function' => 'test',
                'result' => [
                    [
                        'modifiers' => [],
                        'parameters' => [
                            [
                                'path' => 'new #[TestAttribute(#[TestAttributeChild])] class {}',
                                'name' => 'new #[...] class {}',
                                'expression' => !KINT_PHP84,
                                'literal' => false,
                                'new_without_parens' => false,
                            ],
                        ],
                    ],
                ],
            ];

            $data['nullsafe obj property'] = [
                '<?php

                test($args?->prop);',
                'line' => 3,
                'function' => 'test',
                'result' => [
                    [
                        'modifiers' => [],
                        'parameters' => [
                            [
                                'path' => '$args?->prop',
                                'name' => '$args?->prop',
                                'expression' => false,
                                'literal' => false,
                                'new_without_parens' => false,
                            ],
                        ],
                    ],
                ],
            ];

            $data['match'] = [
                '<?php

                test(match ($a) { 0 => false, default => true });',
                'line' => 3,
                'function' => 'test',
                'result' => [
                    [
                        'modifiers' => [],
                        'parameters' => [
                            [
                                'path' => 'match ($a) { 0 => false, default => true }',
                                'name' => 'match (...) {...}',
                                'expression' => true,
                                'literal' => false,
                                'new_without_parens' => false,
                            ],
                        ],
                    ],
                ],
            ];

            $data['named params'] = [
                '<?php

                test(abc(paramName: $value));',
                'line' => 3,
                'function' => 'test',
                'result' => [
                    [
                        'modifiers' => [],
                        'parameters' => [
                            [
                                'path' => 'abc(paramName: $value)',
                                'name' => 'abc(...)',
                                'expression' => false,
                                'literal' => false,
                                'new_without_parens' => false,
                            ],
                        ],
                    ],
                ],
            ];
        }

        if (KINT_PHP81) {
            $data['ignore firstclass callables'] = [
                '<?php

                $x = test(...); test($a);',
                'line' => 3,
                'function' => 'test',
                'result' => [
                    [
                        'modifiers' => [],
                        'parameters' => [
                            [
                                'path' => '$a',
                                'name' => '$a',
                                'expression' => false,
                                'literal' => false,
                                'new_without_parens' => false,
                            ],
                        ],
                    ],
                ],
            ];
        }

        if (KINT_PHP82) {
            $data['dnf types'] = [
                '<?php

                test(function ((A & B) | C | null $test) {});',
                'line' => 3,
                'function' => 'test',
                'result' => [
                    [
                        'modifiers' => [],
                        'parameters' => [
                            [
                                'path' => 'function ((A & B) | C | null $test) {}',
                                'name' => 'function (...) {}',
                                'expression' => true,
                                'literal' => false,
                                'new_without_parens' => false,
                            ],
                        ],
                    ],
                ],
            ];
        }

        if (KINT_PHP83) {
            $data['dynamic class constant'] = [
                '<?php

                test(ABC::{$name});',
                'line' => 3,
                'function' => 'test',
                'result' => [
                    [
                        'modifiers' => [],
                        'parameters' => [
                            [
                                'path' => 'ABC::{$name}',
                                'name' => 'ABC::{...}',
                                'expression' => false,
                                'literal' => false,
                                'new_without_parens' => false,
                            ],
                        ],
                    ],
                ],
            ];
        }

        if (KINT_PHP84) {
            $data['call from property hook'] = [
                '<?php

                class TestClass {
                    public private(set) string $test_property {
                        set (string $value) {
                            test("Setter called:", $value);
                            $this->test_property = $value;
                        }

                        get => $this->test_property;
                    }
                }',
                'line' => 6,
                'function' => 'test',
                'result' => [
                    [
                        'modifiers' => [],
                        'parameters' => [
                            [
                                'path' => '"Setter called:"',
                                'name' => '"..."',
                                'expression' => false,
                                'literal' => true,
                                'new_without_parens' => false,
                            ],
                            [
                                'path' => '$value',
                                'name' => '$value',
                                'expression' => false,
                                'literal' => false,
                                'new_without_parens' => false,
                            ],
                        ],
                    ],
                ],
            ];

            $data['new without parens call'] = [
                '<?php

                new X()->y($value);',
                'line' => 3,
                'function' => ['X', 'y'],
                'result' => [],
            ];

            $data['call on new without parens'] = [
                '<?php

                test(new X($v1)->y);',
                'line' => 3,
                'function' => 'test',
                'result' => [
                    [
                        'modifiers' => [],
                        'parameters' => [
                            [
                                'path' => 'new X($v1)->y',
                                'name' => 'new X(...)->y',
                                'expression' => false,
                                'literal' => false,
                                'new_without_parens' => false,
                            ],
                        ],
                    ],
                ],
            ];

            $data['call on new without parens no calls'] = [
                '<?php

                test(new     X);',
                'line' => 3,
                'function' => 'test',
                'result' => [
                    [
                        'modifiers' => [],
                        'parameters' => [
                            [
                                'path' => 'new     X',
                                'name' => 'new X',
                                'expression' => false,
                                'literal' => false,
                                'new_without_parens' => true,
                            ],
                        ],
                    ],
                ],
            ];

            $data['call on new without parens expression classname'] = [
                '<?php

                test(new ("X"));',
                'line' => 3,
                'function' => 'test',
                'result' => [
                    [
                        'modifiers' => [],
                        'parameters' => [
                            [
                                'path' => 'new ("X")',
                                'name' => 'new (...)',
                                'expression' => false,
                                'literal' => false,
                                'new_without_parens' => true,
                            ],
                        ],
                    ],
                ],
            ];

            $data['call on new without parens variable classname'] = [
                '<?php

                test(new $x);',
                'line' => 3,
                'function' => 'test',
                'result' => [
                    [
                        'modifiers' => [],
                        'parameters' => [
                            [
                                'path' => 'new $x',
                                'name' => 'new $x',
                                'expression' => false,
                                'literal' => false,
                                'new_without_parens' => true,
                            ],
                        ],
                    ],
                ],
            ];

            $data['call on new without parens junk before new'] = [
                '<?php

                test(/*test*/new $x);',
                'line' => 3,
                'function' => 'test',
                'result' => [
                    [
                        'modifiers' => [],
                        'parameters' => [
                            [
                                'path' => 'new $x',
                                'name' => 'new $x',
                                'expression' => false,
                                'literal' => false,
                                'new_without_parens' => true,
                            ],
                        ],
                    ],
                ],
            ];
        }

        return $data;
    }

    /**
     * @dataProvider sourceProvider
     *
     * @covers \Kint\CallFinder
     *
     * @param string $source
     * @param int    $line
     * @param string $function
     */
    public function testGetFunctionCalls($source, $line, $function, array $result)
    {
        $output = CallFinder::getFunctionCalls($source, $line, $function);

        $this->assertCount(\count($result), $output);

        foreach ($result as $index => $function) {
            $this->assertEquals($function, $output[$index]);
        }
    }
}
