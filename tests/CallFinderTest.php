<?php

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
use PHPUnit\Framework\TestCase;

class CallFinderTest extends TestCase
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
                        ],
                        [
                            'path' => '$woot[$wat] + 4',
                            'name' => '$woot[...] + 4',
                            'expression' => true,
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
                        ],
                        [
                            'path' => '[ ]',
                            'name' => '[]',
                            'expression' => false,
                        ],
                        [
                            'path' => '[ 1 ]',
                            'name' => '[...]',
                            'expression' => false,
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
                        ],
                        [
                            'path' => '$b',
                            'name' => '$b',
                            'expression' => false,
                        ],
                        [
                            'path' => '$gamma',
                            'name' => '$gamma',
                            'expression' => false,
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
                        ],
                        [
                            'path' => '$_SERVER["REMOTE_ADDR"]',
                            'name' => '$_SERVER[...]',
                            'expression' => false,
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
                        ],
                        [
                            'path' => '$_SERVER[$val]',
                            'name' => '$_SERVER[...]',
                            'expression' => false,
                        ],
                    ],
                ],
            ],
        ];

        $data['one on multiple lines end'] = $data['one on multiple lines start'];
        $data['one on multiple lines end']['line'] = 7;

        $data['one on multiple lines mid'] = $data['one on multiple lines start'];
        $data['one on multiple lines mid']['line'] = 5;

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
                        ],
                        [
                            'path' => '$_SERVER[$val]',
                            'name' => '$_SERVER[...]',
                            'expression' => false,
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
                        ],
                    ],
                ],
            ],
        ];

        $data['nested calls, single matching line'] = $data['nested calls'];
        $data['nested calls, single matching line']['line'] = 5;
        unset($data['nested calls, single matching line']['result'][1]);

        $data['multiple line params'] = [
            '<?php

            test(
                $a /* mixed */ + /** in */ $b ?>comments<?php + // test
                $c
            );
            ',
            'line' => 4,
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
    test::TEST,
    \\test::TEST,
    test :: TEST,
    \\test :: TEST
);',
            'line' => 10,
            'function' => 'd',
            'result' => [
                [
                    'modifiers' => [],
                    'parameters' => [
                        [
                            'path' => 'true?$_SERVER:array()',
                            'name' => 'true?$_SERVER:array()',
                            'expression' => true,
                        ],
                        [
                            'path' => '$x=1',
                            'name' => '$x=1',
                            'expression' => true,
                        ],
                        [
                            'path' => '$x+1',
                            'name' => '$x+1',
                            'expression' => true,
                        ],
                        [
                            'path' => '$x==1',
                            'name' => '$x==1',
                            'expression' => true,
                        ],
                        [
                            'path' => '$x-1',
                            'name' => '$x-1',
                            'expression' => true,
                        ],
                        [
                            'path' => '$x*1',
                            'name' => '$x*1',
                            'expression' => true,
                        ],
                        [
                            'path' => '$x/1',
                            'name' => '$x/1',
                            'expression' => true,
                        ],
                        [
                            'path' => '$x%1',
                            'name' => '$x%1',
                            'expression' => true,
                        ],
                        [
                            'path' => '$x++',
                            'name' => '$x++',
                            'expression' => true,
                        ],
                        [
                            'path' => '$x--',
                            'name' => '$x--',
                            'expression' => true,
                        ],
                        [
                            'path' => '$x**4',
                            'name' => '$x**4',
                            'expression' => true,
                        ],
                        [
                            'path' => '~$x',
                            'name' => '~$x',
                            'expression' => true,
                        ],
                        [
                            'path' => '$x instanceof bltest',
                            'name' => '$x instanceof bltest',
                            'expression' => true,
                        ],
                        [
                            'path' => '!$x',
                            'name' => '!$x',
                            'expression' => true,
                        ],
                        [
                            'path' => '$x%1',
                            'name' => '$x%1',
                            'expression' => true,
                        ],
                        [
                            'path' => '$_SERVER["HTTP_HOST"]',
                            'name' => '$_SERVER[...]',
                            'expression' => false,
                        ],
                        [
                            'path' => '$_SERVER[ "HTTP_HOST" ]',
                            'name' => '$_SERVER[...]',
                            'expression' => false,
                        ],
                        [
                            'path' => '$_SERVER [ "HTTP_HOST" ]',
                            'name' => '$_SERVER[...]',
                            'expression' => false,
                        ],
                        [
                            'path' => '[] + []',
                            'name' => '[] + []',
                            'expression' => true,
                        ],
                        [
                            'path' => 'new DateTime()',
                            'name' => 'new DateTime()',
                            'expression' => true,
                        ],
                        [
                            'path' => 'clone $db',
                            'name' => 'clone $db',
                            'expression' => true,
                        ],
                        [
                            'path' => 'array()',
                            'name' => 'array()',
                            'expression' => false,
                        ],
                        [
                            'path' => 'array( )',
                            'name' => 'array()',
                            'expression' => false,
                        ],
                        [
                            'path' => '"string"',
                            'name' => '"..."',
                            'expression' => false,
                        ],
                        [
                            'path' => '[]',
                            'name' => '[]',
                            'expression' => false,
                        ],
                        [
                            'path' => '[ ]',
                            'name' => '[]',
                            'expression' => false,
                        ],
                        [
                            'path' => '((((((("woot")))))))',
                            'name' => '(...)',
                            'expression' => false,
                        ],
                        [
                            'path' => '((((((()))))))',
                            'name' => '(...)',
                            'expression' => false,
                        ],
                        [
                            'path' => 'true',
                            'name' => 'true',
                            'expression' => false,
                        ],
                        [
                            'path' => 'TRUE',
                            'name' => 'TRUE',
                            'expression' => false,
                        ],
                        [
                            'path' => 'test::TEST',
                            'name' => 'test::TEST',
                            'expression' => false,
                        ],
                        [
                            'path' => '\\test::TEST',
                            'name' => '\\test::TEST',
                            'expression' => false,
                        ],
                        [
                            'path' => 'test :: TEST',
                            'name' => 'test::TEST',
                            'expression' => false,
                        ],
                        [
                            'path' => '\\test :: TEST',
                            'name' => '\\test::TEST',
                            'expression' => false,
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
            'line' => 4,
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

            echo test::test; test($val);',
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
                        ],
                        [
                            'path' => '$b',
                            'name' => '$b',
                            'expression' => false,
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
                        ],
                        [
                            'path' => '...$args',
                            'name' => '...$args',
                            'expression' => false,
                        ],
                    ],
                ],
            ],
        ];

        if (\version_compare(PHP_VERSION, '7.3') >= 0) {
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
                            ],
                            [
                                'path' => '$b',
                                'name' => '$b',
                                'expression' => false,
                            ],
                        ],
                    ],
                ],
            ];
        }

        if (KINT_PHP74) {
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
                            ],
                        ],
                    ],
                ],
            ];

            $data['short_functions'] = [
                '<?php

                test(($t) => test($t));',
                'line' => 3,
                'function' => 'test',
                'result' => [
                    [
                        'modifiers' => [],
                        'parameters' => [
                            [
                                'path' => '($t) => test($t)',
                                'name' => '(...) => test(...)',
                                'expression' => true,
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
