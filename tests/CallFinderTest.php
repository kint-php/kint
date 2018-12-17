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
        $data = array();

        $data['function'] = array(
            '<?php

            !@+-~test($wat, $woot[$wat] + 4);
            ',
            'line' => 3,
            'function' => 'Test',
            'result' => array(
                array(
                    'modifiers' => array('~', '-', '+', '@', '!'),
                    'parameters' => array(
                        array(
                            'path' => '$wat',
                            'name' => '$wat',
                            'expression' => false,
                        ),
                        array(
                            'path' => '$woot[$wat] + 4',
                            'name' => '$woot[...] + 4',
                            'expression' => true,
                        ),
                    ),
                ),
            ),
        );

        $data['static method'] = array(
            '<?php

            !subspace\\C :: Method([], [ ], [ 1 ]);
            ',
            'line' => 3,
            'function' => array('namespace\\subspace\\c', 'method'),
            'result' => array(
                array(
                    'modifiers' => array('!'),
                    'parameters' => array(
                        array(
                            'path' => '[]',
                            'name' => '[]',
                            'expression' => false,
                        ),
                        array(
                            'path' => '[ ]',
                            'name' => '[]',
                            'expression' => false,
                        ),
                        array(
                            'path' => '[ 1 ]',
                            'name' => '[...]',
                            'expression' => false,
                        ),
                    ),
                ),
            ),
        );

        $data['static method global namespace'] = array(
            '<?php

            !\\C :: Method(1, $b, $gamma);
            ',
            'line' => 3,
            'function' => array('c', 'method'),
            'result' => array(
                array(
                    'modifiers' => array('!'),
                    'parameters' => array(
                        array(
                            'path' => '1',
                            'name' => '1',
                            'expression' => false,
                        ),
                        array(
                            'path' => '$b',
                            'name' => '$b',
                            'expression' => false,
                        ),
                        array(
                            'path' => '$gamma',
                            'name' => '$gamma',
                            'expression' => false,
                        ),
                    ),
                ),
            ),
        );

        $data['static method wrong class'] = array(
            '<?php

            !subspace\\C :: Method([], [ ], [ 1 ]);
            ',
            'line' => 3,
            'function' => array('namespace\\subspace\\d', 'method'),
            'result' => array(),
        );

        $data['static method no class'] = array(
            '<?php

            Method($val);
            ',
            'line' => 3,
            'function' => array('namespace\\subspace\\d', 'method'),
            'result' => array(),
        );

        $data['multiple on one line'] = array(
            '<?php

            !Test($val); @test([ ], $_SERVER["REMOTE_ADDR"]);
            ',
            'line' => 3,
            'function' => 'test',
            'result' => array(
                array(
                    'modifiers' => array('!'),
                    'parameters' => array(
                        array(
                            'path' => '$val',
                            'name' => '$val',
                            'expression' => false,
                        ),
                    ),
                ),
                array(
                    'modifiers' => array('@'),
                    'parameters' => array(
                        array(
                            'path' => '[ ]',
                            'name' => '[]',
                            'expression' => false,
                        ),
                        array(
                            'path' => '$_SERVER["REMOTE_ADDR"]',
                            'name' => '$_SERVER[...]',
                            'expression' => false,
                        ),
                    ),
                ),
            ),
        );

        $data['one on multiple lines start'] = array(
            '<?php

            !c::method(
                // Wat,
                $val,
                $_SERVER[$val]
            );
            ',
            'line' => 3,
            'function' => array('namespace\\subspace\\C', 'Method'),
            'result' => array(
                array(
                    'modifiers' => array('!'),
                    'parameters' => array(
                        array(
                            'path' => '$val',
                            'name' => '$val',
                            'expression' => false,
                        ),
                        array(
                            'path' => '$_SERVER[$val]',
                            'name' => '$_SERVER[...]',
                            'expression' => false,
                        ),
                    ),
                ),
            ),
        );

        $data['one on multiple lines end'] = $data['one on multiple lines start'];
        $data['one on multiple lines end']['line'] = 7;

        $data['one on multiple lines mid'] = $data['one on multiple lines start'];
        $data['one on multiple lines mid']['line'] = 5;

        $data['nested calls'] = array(
            '<?php

            !test(
                @test($val),
                $_SERVER[$val]
            );
            ',
            'line' => 4,
            'function' => 'test',
            'result' => array(
                array(
                    'modifiers' => array('!'),
                    'parameters' => array(
                        array(
                            'path' => '@test($val)',
                            'name' => '@test(...)',
                            'expression' => false,
                        ),
                        array(
                            'path' => '$_SERVER[$val]',
                            'name' => '$_SERVER[...]',
                            'expression' => false,
                        ),
                    ),
                ),
                array(
                    'modifiers' => array('@'),
                    'parameters' => array(
                        array(
                            'path' => '$val',
                            'name' => '$val',
                            'expression' => false,
                        ),
                    ),
                ),
            ),
        );

        $data['nested calls, single matching line'] = $data['nested calls'];
        $data['nested calls, single matching line']['line'] = 5;
        unset($data['nested calls, single matching line']['result'][1]);

        $data['multiple line params'] = array(
            '<?php

            test(
                $a /* mixed */ + /** in */ $b ?>comments<?php + // test
                $c
            );
            ',
            'line' => 4,
            'function' => 'test',
            'result' => array(
                array(
                    'modifiers' => array(),
                    'parameters' => array(
                        array(
                            'path' => '$a /* mixed */ + /** in */ $b ?>comments<?php + // test
                $c',
                            'name' => '$a + $b + $c',
                            'expression' => true,
                        ),
                    ),
                ),
            ),
        );

        $data['space stripping'] = array(
            '<?php

            test(  $var [ "key" ] +  /* test */  $var2  +$var3);',
            'line' => 3,
            'function' => 'test',
            'result' => array(
                array(
                    'modifiers' => array(),
                    'parameters' => array(
                        array(
                            'path' => '$var [ "key" ] +  /* test */  $var2  +$var3',
                            'name' => '$var[...] + $var2 +$var3',
                            'expression' => true,
                        ),
                    ),
                ),
            ),
        );

        $data['expressions'] = array(
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
            'result' => array(
                array(
                    'modifiers' => array(),
                    'parameters' => array(
                        array(
                            'path' => 'true?$_SERVER:array()',
                            'name' => 'true?$_SERVER:array()',
                            'expression' => true,
                        ),
                        array(
                            'path' => '$x=1',
                            'name' => '$x=1',
                            'expression' => true,
                        ),
                        array(
                            'path' => '$x+1',
                            'name' => '$x+1',
                            'expression' => true,
                        ),
                        array(
                            'path' => '$x==1',
                            'name' => '$x==1',
                            'expression' => true,
                        ),
                        array(
                            'path' => '$x-1',
                            'name' => '$x-1',
                            'expression' => true,
                        ),
                        array(
                            'path' => '$x*1',
                            'name' => '$x*1',
                            'expression' => true,
                        ),
                        array(
                            'path' => '$x/1',
                            'name' => '$x/1',
                            'expression' => true,
                        ),
                        array(
                            'path' => '$x%1',
                            'name' => '$x%1',
                            'expression' => true,
                        ),
                        array(
                            'path' => '$x++',
                            'name' => '$x++',
                            'expression' => true,
                        ),
                        array(
                            'path' => '$x--',
                            'name' => '$x--',
                            'expression' => true,
                        ),
                        array(
                            'path' => '$x**4',
                            'name' => '$x**4',
                            'expression' => true,
                        ),
                        array(
                            'path' => '~$x',
                            'name' => '~$x',
                            'expression' => true,
                        ),
                        array(
                            'path' => '$x instanceof bltest',
                            'name' => '$x instanceof bltest',
                            'expression' => true,
                        ),
                        array(
                            'path' => '!$x',
                            'name' => '!$x',
                            'expression' => true,
                        ),
                        array(
                            'path' => '$x%1',
                            'name' => '$x%1',
                            'expression' => true,
                        ),
                        array(
                            'path' => '$_SERVER["HTTP_HOST"]',
                            'name' => '$_SERVER[...]',
                            'expression' => false,
                        ),
                        array(
                            'path' => '$_SERVER[ "HTTP_HOST" ]',
                            'name' => '$_SERVER[...]',
                            'expression' => false,
                        ),
                        array(
                            'path' => '$_SERVER [ "HTTP_HOST" ]',
                            'name' => '$_SERVER[...]',
                            'expression' => false,
                        ),
                        array(
                            'path' => '[] + []',
                            'name' => '[] + []',
                            'expression' => true,
                        ),
                        array(
                            'path' => 'new DateTime()',
                            'name' => 'new DateTime()',
                            'expression' => true,
                        ),
                        array(
                            'path' => 'clone $db',
                            'name' => 'clone $db',
                            'expression' => true,
                        ),
                        array(
                            'path' => 'array()',
                            'name' => 'array()',
                            'expression' => false,
                        ),
                        array(
                            'path' => 'array( )',
                            'name' => 'array()',
                            'expression' => false,
                        ),
                        array(
                            'path' => '"string"',
                            'name' => '"..."',
                            'expression' => false,
                        ),
                        array(
                            'path' => '[]',
                            'name' => '[]',
                            'expression' => false,
                        ),
                        array(
                            'path' => '[ ]',
                            'name' => '[]',
                            'expression' => false,
                        ),
                        array(
                            'path' => '((((((("woot")))))))',
                            'name' => '(...)',
                            'expression' => false,
                        ),
                        array(
                            'path' => '((((((()))))))',
                            'name' => '(...)',
                            'expression' => false,
                        ),
                        array(
                            'path' => 'true',
                            'name' => 'true',
                            'expression' => false,
                        ),
                        array(
                            'path' => 'TRUE',
                            'name' => 'TRUE',
                            'expression' => false,
                        ),
                        array(
                            'path' => 'test::TEST',
                            'name' => 'test::TEST',
                            'expression' => false,
                        ),
                        array(
                            'path' => '\\test::TEST',
                            'name' => '\\test::TEST',
                            'expression' => false,
                        ),
                        array(
                            'path' => 'test :: TEST',
                            'name' => 'test::TEST',
                            'expression' => false,
                        ),
                        array(
                            'path' => '\\test :: TEST',
                            'name' => '\\test::TEST',
                            'expression' => false,
                        ),
                    ),
                ),
            ),
        );

        $data['T_CURLY_OPEN in string'] = array(
            '<?php

            test("string {$var} string");',
            'line' => 3,
            'function' => 'test',
            'result' => array(
                array(
                    'modifiers' => array(),
                    'parameters' => array(
                        array(
                            'path' => '"string {$var} string"',
                            'name' => '"..."',
                            'expression' => false,
                        ),
                    ),
                ),
            ),
        );

        $data['T_DOLLAR_OPEN_CURLY_BRACES, T_STRING_VARNAME in string'] = array(
            '<?php

            test("string ${var} string");',
            'line' => 3,
            'function' => 'test',
            'result' => array(
                array(
                    'modifiers' => array(),
                    'parameters' => array(
                        array(
                            'path' => '"string ${var} string"',
                            'name' => '"..."',
                            'expression' => false,
                        ),
                    ),
                ),
            ),
        );

        $data['T_VARIABLE in string'] = array(
            '<?php

            test("string $var string");',
            'line' => 3,
            'function' => 'test',
            'result' => array(
                array(
                    'modifiers' => array(),
                    'parameters' => array(
                        array(
                            'path' => '"string $var string"',
                            'name' => '"..."',
                            'expression' => false,
                        ),
                    ),
                ),
            ),
        );

        $data['strange token preceding'] = array(
            '<?php

            $x &=test($val);',
            'line' => 3,
            'function' => 'test',
            'result' => array(
                array(
                    'modifiers' => array(),
                    'parameters' => array(
                        array(
                            'path' => '$val',
                            'name' => '$val',
                            'expression' => false,
                        ),
                    ),
                ),
            ),
        );

        $data['no real tokens following'] = array(
            '<?php

            define("test", "woot");

            ?><?= test ?>',
            'line' => 5,
            'function' => 'test',
            'result' => array(),
        );

        $data['empty call'] = array(
            '<?php

            test();',
            'line' => 3,
            'function' => 'test',
            'result' => array(
                array(
                    'modifiers' => array(),
                    'parameters' => array(),
                ),
            ),
        );

        $data['whitespace call'] = array(
            '<?php

            test(
                // Nothing here, but multiple tokens
            );',
            'line' => 4,
            'function' => 'test',
            'result' => array(
                array(
                    'modifiers' => array(),
                    'parameters' => array(),
                ),
            ),
        );

        $data['non-function tokens'] = array(
            '<?php

            echo test::test; test($val);',
            'line' => 3,
            'function' => 'test',
            'result' => array(
                array(
                    'modifiers' => array(),
                    'parameters' => array(
                        array(
                            'path' => '$val',
                            'name' => '$val',
                            'expression' => false,
                        ),
                    ),
                ),
            ),
        );

        $data['whitespace hell'] = array(
            '<?php

            ! Kint :: dump ( $val ) ; ',
            'line' => 3,
            'function' => array('Kint', 'dump'),
            'result' => array(
                array(
                    'modifiers' => array('!'),
                    'parameters' => array(
                        array(
                            'path' => '$val',
                            'name' => '$val',
                            'expression' => false,
                        ),
                    ),
                ),
            ),
        );

        $data['whitespace hell 2'] = array(
            '<?php

            + dump ( $val ) ; ',
            'line' => 3,
            'function' => 'dump',
            'result' => array(
                array(
                    'modifiers' => array('+'),
                    'parameters' => array(
                        array(
                            'path' => '$val',
                            'name' => '$val',
                            'expression' => false,
                        ),
                    ),
                ),
            ),
        );

        $data['realtoken tweaking'] = array(
            '<?php

            d((function () { return "woot"; })());',
            'line' => 3,
            'function' => 'd',
            'result' => array(
                array(
                    'modifiers' => array(),
                    'parameters' => array(
                        array(
                            'path' => '(function () { return "woot"; })()',
                            'name' => '(...)()',
                            'expression' => false,
                        ),
                    ),
                ),
            ),
        );

        if (KINT_PHP56) {
            $data['arg expansion'] = array(
                '<?php

                test($args, ...$args);',
                'line' => 3,
                'function' => 'test',
                'result' => array(
                    array(
                        'modifiers' => array(),
                        'parameters' => array(
                            array(
                                'path' => '$args',
                                'name' => '$args',
                                'expression' => false,
                            ),
                            array(
                                'path' => '...$args',
                                'name' => '...$args',
                                'expression' => false,
                            ),
                        ),
                    ),
                ),
            );
        }

        if (KINT_PHP73) {
            $data['trailing comma'] = array(
                '<?php

                test(
                    $a,
                    $b,
                );',
                'line' => 3,
                'function' => 'test',
                'result' => array(
                    array(
                        'modifiers' => array(),
                        'parameters' => array(
                            array(
                                'path' => '$a',
                                'name' => '$a',
                                'expression' => false,
                            ),
                            array(
                                'path' => '$b',
                                'name' => '$b',
                                'expression' => false,
                            ),
                        ),
                    ),
                ),
            );
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
     * @param array  $result
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
