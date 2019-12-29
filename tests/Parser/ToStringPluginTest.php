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

namespace Kint\Test\Parser;

use Kint\Object\BasicObject;
use Kint\Parser\Parser;
use Kint\Parser\ToStringPlugin;
use Kint\Test\KintTestCase;
use SplFileInfo;
use stdClass;

class ToStringPluginTest extends KintTestCase
{
    /**
     * @covers \Kint\Parser\ToStringPlugin::getTypes
     */
    public function testGetTypes()
    {
        $p = new ToStringPlugin();

        $this->assertSame(['object'], $p->getTypes());
    }

    /**
     * @covers \Kint\Parser\ToStringPlugin::getTriggers
     */
    public function testGetTriggers()
    {
        $p = new ToStringPlugin();

        $this->assertSame(Parser::TRIGGER_SUCCESS, $p->getTriggers());
    }

    /**
     * @covers \Kint\Parser\ToStringPlugin::parse
     */
    public function testParse()
    {
        $p = new Parser();
        $p->addPlugin(new ToStringPlugin());
        $b = BasicObject::blank('$v', '$v');

        $v = new SplFileInfo(__FILE__);

        $obj = $p->parse($v, clone $b);
        $rep = $obj->getRepresentation('tostring');

        $this->assertNotNull($rep);
        $this->assertSame(__FILE__, $rep->contents);
    }

    /**
     * @covers \Kint\Parser\ToStringPlugin::parse
     */
    public function testParseNormalObject()
    {
        $p = new Parser();
        $p->addPlugin(new ToStringPlugin());
        $b = BasicObject::blank('$v', '$v');

        $v = new stdClass();

        $obj = $p->parse($v, clone $b);

        $this->assertNull($obj->getRepresentation('tostring'));
    }

    /**
     * @covers \Kint\Parser\ToStringPlugin::parse
     */
    public function testParseBlacklist()
    {
        $p = new Parser();
        $p->addPlugin(new ToStringPlugin());
        $b = BasicObject::blank('$v', '$v');

        $v = new SplFileInfo(__FILE__);

        $obj = $p->parse($v, clone $b);

        $this->assertNotNull($obj->getRepresentation('tostring'));

        ToStringPlugin::$blacklist[] = 'SplFileInfo';

        $obj = $p->parse($v, clone $b);

        $this->assertNull($obj->getRepresentation('tostring'));
    }
}
