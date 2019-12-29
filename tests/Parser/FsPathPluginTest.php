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
use Kint\Parser\FsPathPlugin;
use Kint\Parser\Parser;
use Kint\Test\KintTestCase;

class FsPathPluginTest extends KintTestCase
{
    protected function setUp()
    {
        \symlink(\dirname(__DIR__), __DIR__.'/testDirLink');
        \symlink(__FILE__, __DIR__.'/testFileLink');
    }

    protected function tearDown()
    {
        \unlink(__DIR__.'/testDirLink');
        \unlink(__DIR__.'/testFileLink');
    }

    /**
     * @covers \Kint\Parser\FsPathPlugin::getTypes
     */
    public function testGetTypes()
    {
        $p = new FsPathPlugin();

        $this->assertSame(['string'], $p->getTypes());
    }

    /**
     * @covers \Kint\Parser\FsPathPlugin::getTriggers
     */
    public function testGetTriggers()
    {
        $p = new FsPathPlugin();

        $this->assertSame(Parser::TRIGGER_SUCCESS, $p->getTriggers());
    }

    public function fsPathProvider()
    {
        return [
            'standard path' => [
                __FILE__,
                true,
            ],
            'folder' => [
                __DIR__,
                true,
            ],
            'looooong' => [
                __DIR__.\str_repeat('/testDirLink', 1000),
                false,
            ],
            'string' => [
                'This is just an ordinary string',
                false,
            ],
            'url' => [
                __DIR__.'?key=val',
                false,
            ],
            'nonexistant' => [
                __DIR__.'/404',
                false,
            ],
            'slash' => [
                '/',
                false,
            ],
            'dot' => [
                '.',
                false,
            ],
        ];
    }

    /**
     * @covers \Kint\Parser\FsPathPlugin::parse
     * @dataProvider fsPathProvider
     *
     * @param string $path
     * @param bool   $expect
     */
    public function testParse($path, $expect)
    {
        $p = new Parser();
        $b = BasicObject::blank('$v', '$v');

        $obj = $p->parse($path, clone $b);

        $this->assertNull($obj->getRepresentation('splfileinfo'));

        $p->addPlugin(new FsPathPlugin());
        $obj = $p->parse($path, clone $b);

        if ($expect) {
            $this->assertInstanceOf(
                '\\Kint\\Object\\Representation\\SplFileInfoRepresentation',
                $obj->getRepresentation('splfileinfo')
            );

            $this->assertContains('fspath', $obj->getRepresentation('splfileinfo')->hints);
        } else {
            $this->assertNull($obj->getRepresentation('splfileinfo'));
        }
    }

    /**
     * @covers \Kint\Parser\FsPathPlugin::parse
     */
    public function testParseBlacklist()
    {
        $p = new Parser();
        $p->addPlugin(new FsPathPlugin());
        $b = BasicObject::blank('$v', '$v');

        $v = __FILE__;

        $obj = $p->parse($v, clone $b);

        $this->assertNotNull($obj->getRepresentation('splfileinfo'));

        FsPathPlugin::$blacklist[] = $v;

        $obj = $p->parse($path, clone $b);

        $this->assertNull($obj->getRepresentation('splfileinfo'));
    }
}
