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

namespace Kint\Test\Parser;

use __PHP_Incomplete_Class;
use Kint\Parser\Parser;
use Kint\Parser\SerializePlugin;
use Kint\Test\KintTestCase;
use Kint\Value\AbstractValue;
use Kint\Value\Context\BaseContext;
use Kint\Value\FixedWidthValue;

/**
 * @coversNothing
 */
class SerializePluginTest extends KintTestCase
{
    /**
     * @covers \Kint\Parser\SerializePlugin::getTypes
     */
    public function testGetTypes()
    {
        $s = new SerializePlugin($this->createStub(Parser::class));

        $this->assertContains('string', $s->getTypes());
    }

    /**
     * @covers \Kint\Parser\SerializePlugin::getTriggers
     */
    public function testGetTriggers()
    {
        $s = new SerializePlugin($this->createStub(Parser::class));

        $this->assertSame(Parser::TRIGGER_SUCCESS, $s->getTriggers());
    }

    /**
     * @covers \Kint\Parser\SerializePlugin::parseComplete
     */
    public function testParse()
    {
        $p = new Parser();
        $b = new BaseContext('$v');
        $b->access_path = '$v';

        $v = \serialize(null);
        $obj = $p->parse($v, clone $b);
        $this->assertSame($v, $obj->getValue());

        $p->addPlugin(new SerializePlugin($p));

        $obj = $p->parse($v, clone $b);
        $this->assertInstanceOf(FixedWidthValue::class, $obj->getRepresentation('serialized')->getValue());
        $this->assertSame('null', $obj->getRepresentation('serialized')->getValue()->getType());

        $v = \serialize(['obj' => $p]);
        $obj = $p->parse($v, clone $b);
        $this->assertEquals(false, $obj->flags & AbstractValue::FLAG_GENERATED);
        $this->assertEquals(true, $obj->getRepresentation('serialized')->getValue()->flags & AbstractValue::FLAG_GENERATED);
        $this->assertEquals(true, $obj->getRepresentation('serialized')->getValue()->flags & AbstractValue::FLAG_BLACKLIST);

        SerializePlugin::$safe_mode = false;
        $obj = $p->parse($v, clone $b);
        $this->assertSame('obj', $obj->getRepresentation('serialized')->getValue()->getContents()['obj']->getContext()->getName());
        $this->assertSame(__PHP_Incomplete_Class::class, $obj->getRepresentation('serialized')->getValue()->getContents()['obj']->getClassName());
        $this->assertSame(
            'unserialize($v, '.\var_export(['allowed_classes' => false], true).')[\'obj\']',
            $obj->getRepresentation('serialized')->getValue()->getContents()['obj']->getContext()->getAccessPath()
        );
        $this->assertEquals(false, $obj->flags & AbstractValue::FLAG_GENERATED);
        $this->assertEquals(true, $obj->getRepresentation('serialized')->getValue()->flags & AbstractValue::FLAG_GENERATED);
        $this->assertEquals(false, $obj->getRepresentation('serialized')->getValue()->flags & AbstractValue::FLAG_BLACKLIST);

        SerializePlugin::$allowed_classes = [self::class];
        $obj = $p->parse($v, clone $b);
        $this->assertSame(__PHP_Incomplete_Class::class, $obj->getRepresentation('serialized')->getValue()->getContents()['obj']->getClassName());
        $this->assertSame(
            'unserialize($v, '.\var_export(['allowed_classes' => [self::class]], true).')[\'obj\']',
            $obj->getRepresentation('serialized')->getValue()->getContents()['obj']->getContext()->getAccessPath()
        );
        $this->assertEquals(false, $obj->flags & AbstractValue::FLAG_GENERATED);
        $this->assertEquals(true, $obj->getRepresentation('serialized')->getValue()->flags & AbstractValue::FLAG_GENERATED);
        $this->assertEquals(false, $obj->getRepresentation('serialized')->getValue()->flags & AbstractValue::FLAG_BLACKLIST);

        SerializePlugin::$allowed_classes[] = Parser::class;
        $obj = $p->parse($v, clone $b);
        $this->assertSame(Parser::class, $obj->getRepresentation('serialized')->getValue()->getContents()['obj']->getClassName());
        $this->assertSame(
            'unserialize($v, '.\var_export(['allowed_classes' => [self::class, Parser::class]], true).')[\'obj\']',
            $obj->getRepresentation('serialized')->getValue()->getContents()['obj']->getContext()->getAccessPath()
        );
        $this->assertEquals(false, $obj->flags & AbstractValue::FLAG_GENERATED);
        $this->assertEquals(true, $obj->getRepresentation('serialized')->getValue()->flags & AbstractValue::FLAG_GENERATED);
        $this->assertEquals(false, $obj->getRepresentation('serialized')->getValue()->flags & AbstractValue::FLAG_BLACKLIST);

        SerializePlugin::$allowed_classes = true;
        $obj = $p->parse($v, clone $b);
        $this->assertSame(Parser::class, $obj->getRepresentation('serialized')->getValue()->getContents()['obj']->getClassName());
        $this->assertSame('unserialize($v)[\'obj\']', $obj->getRepresentation('serialized')->getValue()->getContents()['obj']->getContext()->getAccessPath());

        $v = 'O:1:Hello World, this is just a string that looks like it';
        $obj = $p->parse($v, clone $b);
        $this->assertNull($obj->getRepresentation('serialized'));
    }
}
