<?php

class Kint_Parser_TraceTest extends KintTestCase
{
    public function testParse()
    {
        $p = new Kint_Parser();
        $p->addPlugin(new Kint_Parser_Trace());

        $bt = defined('DEBUG_BACKTRACE_IGNORE_ARGS')
            ? debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS)
            : debug_backtrace();

        $o = Kint_Object::blank();

        $o = $p->parse($bt, $o);

        $this->assertContains('trace', $o->hints);
        $this->assertInstanceOf('Kint_Object_Trace', $o);
        $this->assertInstanceOf('Kint_Object_TraceFrame', $o->value->contents[0]);
    }

    public function provideTraces()
    {
        $bt = debug_backtrace(true);
        $bad_bt_1 = $bt;
        $bad_bt_1[0]['test'] = 'woot';
        $bad_bt_2 = $bt;
        $bat_bt_2[0]['function'] = 1234;

        return array(
            'empty' => array(
                'trace' => array(),
                'expect' => false,
            ),
            'backtrace' => array(
                'trace' => $bt,
                'expect' => true,
            ),
            'bad backtrace, extra key' => array(
                'trace' => $bad_bt_1,
                'expect' => false,
            ),
            'bad backtrace, wrong type' => array(
                'trace' => $bad_bt_1,
                'expect' => false,
            ),
            'mythical' => array(
                'trace' => array(
                    array(
                        'function' => 'mythical_internal_function_with_no_args_that_results_in_a_backtrace',
                        'file' => __FILE__,
                        'line' => 1,
                    ),
                ),
                'expect' => true,
            ),
        );
    }

    /**
     * @dataProvider provideTraces
     */
    public function testIsTrace(array $trace, $expected)
    {
        $this->assertEquals($expected, Kint_Parser_Trace::isTrace($trace));
    }

    public function provideFrames()
    {
        return array(
            'function match' => array(
                'frame' => array(
                    'function' => 'testAWeirdFunctionName',
                ),
                'matches' => array('testaweirdfunctionname'),
                'expected' => true,
            ),
            'function no match denormalized' => array(
                'frame' => array(
                    'function' => 'testAWeirdFunctionName',
                ),
                'matches' => array('testAWeirdFunctionName'),
                'expected' => false,
            ),
            'function no match method' => array(
                'frame' => array(
                    'function' => 'testAWeirdFunctionName',
                ),
                'matches' => array(array('test', 'testaweirdfunctionname')),
                'expected' => false,
            ),
            'method no match function' => array(
                'frame' => array(
                    'function' => 'testAWeirdFunctionName',
                    'class' => 'test',
                ),
                'matches' => array('testAWeirdFunctionName'),
                'expected' => false,
            ),
            'method match' => array(
                'frame' => array(
                    'function' => 'testAWeirdFunctionName',
                    'class' => 'test',
                ),
                'matches' => array(array('test', 'testaweirdfunctionname')),
                'expected' => true,
            ),
        );
    }

    /**
     * @dataProvider provideFrames
     */
    public function testFrameIsListed(array $frame, array $matches, $expected)
    {
        $this->assertEquals($expected, Kint_Parser_Trace::frameIsListed($frame, $matches));
    }

    public function testNormalizeAliases()
    {
        $input = array(
            'CamelCaseFunction',
            'snake_case_function',
            'One_of_the_FunctionsMyColleaguesMADE__',
            'stringThatCan\'tBeAfunction',
            'another string that can not be a function',
            array('clASs', 'meThod'),
            array($this, 'meThod'),
            array('a', 'b', 'c'),
        );

        $expected = array(
            'camelcasefunction',
            'snake_case_function',
            'one_of_the_functionsmycolleaguesmade__',
            array('class', 'method'),
        );

        Kint_Parser_Trace::normalizeAliases($input);

        $this->assertEquals($expected, $input);
    }
}
