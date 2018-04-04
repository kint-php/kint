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
