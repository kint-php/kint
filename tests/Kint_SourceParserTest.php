<?php

class Kint_SourceParserTest extends KintTestCase
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
            'skipto' => 0,
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

            !subspace\\c :: Method([], [ ], [ 1 ]);
            ',
            'line' => 3,
            'function' => array('namespace\\subspace\\c', 'method'),
            'skipto' => 0,
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

        $data['multiple on one line'] = array(
            '<?php

            !test($val); @test([ ], $_SERVER["REMOTE_ADDR"]);
            ',
            'line' => 3,
            'function' => 'test',
            'skipto' => 0,
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

            !test(
                // Wat,
                $val,
                $_SERVER[$val]
            );
            ',
            'line' => 3,
            'function' => 'test',
            'skipto' => 0,
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
            'skipto' => 0,
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
            'skipto' => 0,
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
            'skipto' => 0,
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
    [],
    [ ],
    ((((((("woot"))))))),
    true,
    TRUE,
    test::TEST,
    \test::TEST,
    test :: TEST,
    \test :: TEST
);',
            'line' => 10,
            'function' => 'd',
            'skipto' => 0,
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
                            'path' => '\test::TEST',
                            'name' => '\test::TEST',
                            'expression' => false,
                        ),
                        array(
                            'path' => 'test :: TEST',
                            'name' => 'test::TEST',
                            'expression' => false,
                        ),
                        array(
                            'path' => '\test :: TEST',
                            'name' => '\test::TEST',
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
                'skipto' => 0,
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

        return $data;
    }

    /**
     * @dataProvider sourceProvider
     */
    public function testGetFunctionCalls($source, $line, $function, $skipto, $result)
    {
        $output = Kint_SourceParser::getFunctionCalls($source, $line, $function, $skipto);

        $this->assertCount(count($result), $output);

        foreach ($result as $index => $function) {
            $this->assertEquals($function, $output[$index]);
        }
    }
}
