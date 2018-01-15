<?php

namespace Kint\Test\Parser;

use Kint\Object\BasicObject;
use Kint\Parser\Parser;
use Kint\Parser\TracePlugin;
use PHPUnit_Framework_TestCase;

class TracePluginTest extends PHPUnit_Framework_TestCase
{
    protected $blacklist_stash;

    /**
     * @covers \Kint\Parser\TracePlugin::parse
     */
    public function testParse()
    {
        $p = new Parser();
        $p->addPlugin(new TracePlugin());

        $bt = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);

        $o = BasicObject::blank();

        $o = $p->parse($bt, $o);

        $this->assertContains('trace', $o->hints);
        $this->assertInstanceOf('Kint\\Object\\TraceObject', $o);
        $this->assertInstanceOf('Kint\\Object\\TraceFrameObject', $o->value->contents[0]);
    }

    /**
     * @covers \Kint\Parser\TracePlugin::parse
     */
    public function testParseMismatch()
    {
        $bt = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
        $b = BasicObject::blank();
        $parser = new Parser();
        $plugin = new TracePlugin();

        $incorrect = $parser->parse($bt, clone $b);
        $incorrect->value->contents[0]->name = 'newName';
        $parser->addPlugin($plugin);
        $plugin->parse($bt, $incorrect, Parser::TRIGGER_SUCCESS);

        array_shift($bt);
        $correct = $parser->parse($bt, clone $b);

        foreach ($correct->value->contents as $frame) {
            ++$frame->name;
        }

        $this->assertEquals($correct, $incorrect);
    }

    /**
     * @covers \Kint\Parser\TracePlugin::parse
     */
    public function testParseNoValue()
    {
        $p = new TracePlugin();

        $b = BasicObject::blank();
        $o = clone $b;
        $v = array();

        $p->parse($v, $o, Parser::TRIGGER_SUCCESS);

        $this->assertEquals($b, $o);
    }

    /**
     * @covers \Kint\Parser\TracePlugin::parse
     */
    public function testParseBlacklist()
    {
        $bt = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
        $shortbt = $bt;
        array_shift($shortbt);

        $p = new Parser();
        $p->addPlugin(new TracePlugin());

        $b = BasicObject::blank();

        $o = $p->parse($shortbt, clone $b);

        foreach ($o->value->contents as $frame) {
            ++$frame->name;
        }

        TracePlugin::$blacklist[] = array(__CLASS__, __FUNCTION__);

        $this->assertEquals($o->value, $p->parse($bt, clone $b)->value);
    }

    public function provideTraces()
    {
        $bt = debug_backtrace(true);
        $bad_bt_1 = $bt;
        $bad_bt_1[0]['test'] = 'woot';
        $bad_bt_2 = $bt;
        $bad_bt_2[0]['function'] = 1234;

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
                'trace' => $bad_bt_2,
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
            'normal array' => array(
                'trace' => array(1, 2, 3),
                'expect' => false,
            ),
        );
    }

    /**
     * @dataProvider provideTraces
     * @covers \Kint\Parser\TracePlugin::isTrace
     */
    public function testIsTrace(array $trace, $expected)
    {
        $this->assertEquals($expected, TracePlugin::isTrace($trace));
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
     * @covers \Kint\Parser\TracePlugin::frameIsListed
     */
    public function testFrameIsListed(array $frame, array $matches, $expected)
    {
        $this->assertEquals($expected, TracePlugin::frameIsListed($frame, $matches));
    }

    /**
     * @covers \Kint\Parser\TracePlugin::normalizeAliases
     */
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

        TracePlugin::normalizeAliases($input);

        $this->assertEquals($expected, $input);
    }

    /**
     * @covers \Kint\Parser\TracePlugin::getTypes
     * @covers \Kint\Parser\TracePlugin::getTriggers
     */
    public function testHooks()
    {
        $p = new TracePlugin();

        $this->assertEquals(array('array'), $p->getTypes());
        $this->assertEquals(Parser::TRIGGER_SUCCESS, $p->getTriggers());
    }

    public function setUp()
    {
        $this->blacklist_stash = TracePlugin::$blacklist;
    }

    public function tearDown()
    {
        TracePlugin::$blacklist = $this->blacklist_stash;
    }
}
