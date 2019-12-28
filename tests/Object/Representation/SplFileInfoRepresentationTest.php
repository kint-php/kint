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

namespace Kint\Test\Object\Representation;

use DateTime;
use Kint\Object\Representation\SplFileInfoRepresentation;
use Kint\Test\KintTestCase;
use SplFileInfo;
use UnexpectedValueException;

class SplFileInfoRepresentationTest extends KintTestCase
{
    protected $socket;

    protected function setUp()
    {
        \symlink(\dirname(__DIR__), __DIR__.'/testDirLink');
        \symlink(__FILE__, __DIR__.'/testFileLink');
        \symlink(__DIR__.'/testDirLink', __DIR__.'/testDirLink2');
        \symlink(__DIR__.'/testFileLink', __DIR__.'/testFileLink2');

        \posix_mkfifo(__DIR__.'/testPipe', 0777);

        $this->socket = \socket_create(AF_UNIX, SOCK_STREAM, 0);
        \socket_bind($this->socket, __DIR__.'/testSocket');
    }

    protected function tearDown()
    {
        \unlink(__DIR__.'/testDirLink2');
        \unlink(__DIR__.'/testFileLink2');
        \unlink(__DIR__.'/testDirLink');
        \unlink(__DIR__.'/testFileLink');

        \unlink(__DIR__.'/testPipe');

        \socket_close($this->socket);
        \unlink(__DIR__.'/testSocket');
    }

    /**
     * @covers \Kint\Object\Representation\SplFileInfoRepresentation::__construct
     */
    public function testConstructFile()
    {
        $r = new SplFileInfoRepresentation(new SplFileInfo(__FILE__));

        $this->assertSame(\filesize(__FILE__), $r->size);
        $this->assertSame(\filectime(__FILE__), $r->ctime);
        $this->assertSame(\filemtime(__FILE__), $r->mtime);
        $this->assertSame(\fileperms(__FILE__), $r->perms);
        $this->assertSame(\fileowner(__FILE__), $r->owner);
        $this->assertSame(\filegroup(__FILE__), $r->group);
        $this->assertSame('File', $r->typename);
        $this->assertSame('-', $r->typeflag);
        $this->assertSame(__FILE__, $r->path);
        $this->assertSame(__FILE__, $r->realpath);
        $this->assertNull($r->linktarget);

        if ('file' === \filetype(__FILE__)) {
            $this->assertTrue($r->is_file);
            $this->assertFalse($r->is_dir);
            $this->assertFalse($r->is_link);
        } else {
            throw new UnexpectedValueException(__FILE__.' type is not "file"');
        }
    }

    /**
     * @covers \Kint\Object\Representation\SplFileInfoRepresentation::__construct
     */
    public function testConstructFileLink()
    {
        $f = __DIR__.'/testFileLink2';

        $r = new SplFileInfoRepresentation(new SplFileInfo($f));

        $this->assertSame(\filesize($f), $r->size);
        $this->assertSame(\filectime($f), $r->ctime);
        $this->assertSame(\filemtime($f), $r->mtime);
        $this->assertSame(\fileperms($f), $r->perms);
        $this->assertSame(\fileowner($f), $r->owner);
        $this->assertSame(\filegroup($f), $r->group);
        $this->assertSame('File symlink', $r->typename);
        $this->assertSame('l', $r->typeflag);
        $this->assertSame($f, $r->path);
        $this->assertSame(__FILE__, $r->realpath);
        $this->assertSame(__DIR__.'/testFileLink', $r->linktarget);

        if ('link' === \filetype($f)) {
            $this->assertTrue($r->is_file);
            $this->assertFalse($r->is_dir);
            $this->assertTrue($r->is_link);
        } else {
            throw new UnexpectedValueException($f.' type is not "link"');
        }
    }

    /**
     * @covers \Kint\Object\Representation\SplFileInfoRepresentation::__construct
     */
    public function testConstructLinkedFile()
    {
        $f = __DIR__.'/testDirLink/'.\basename(__DIR__).'/'.\basename(__FILE__);

        $r = new SplFileInfoRepresentation(new SplFileInfo($f));

        $this->assertSame(\filesize($f), $r->size);
        $this->assertSame(\filectime($f), $r->ctime);
        $this->assertSame(\filemtime($f), $r->mtime);
        $this->assertSame(\fileperms($f), $r->perms);
        $this->assertSame(\fileowner($f), $r->owner);
        $this->assertSame(\filegroup($f), $r->group);
        $this->assertSame('File', $r->typename);
        $this->assertSame('-', $r->typeflag);
        $this->assertSame($f, $r->path);
        $this->assertSame(__FILE__, $r->realpath);
        $this->assertNull($r->linktarget);

        if ('file' === \filetype($f)) {
            $this->assertTrue($r->is_file);
            $this->assertFalse($r->is_dir);
            $this->assertFalse($r->is_link);
        } else {
            throw new UnexpectedValueException($f.' type is not "file"');
        }
    }

    /**
     * @covers \Kint\Object\Representation\SplFileInfoRepresentation::__construct
     */
    public function testConstructDir()
    {
        $r = new SplFileInfoRepresentation(new SplFileInfo(__DIR__));

        $this->assertSame(\filesize(__DIR__), $r->size);
        $this->assertSame(\filectime(__DIR__), $r->ctime);
        $this->assertSame(\filemtime(__DIR__), $r->mtime);
        $this->assertSame(\fileperms(__DIR__), $r->perms);
        $this->assertSame(\fileowner(__DIR__), $r->owner);
        $this->assertSame(\filegroup(__DIR__), $r->group);
        $this->assertSame('Directory', $r->typename);
        $this->assertSame('d', $r->typeflag);
        $this->assertSame(__DIR__, $r->path);
        $this->assertSame(__DIR__, $r->realpath);
        $this->assertNull($r->linktarget);

        if ('dir' === \filetype(__DIR__)) {
            $this->assertFalse($r->is_file);
            $this->assertTrue($r->is_dir);
            $this->assertFalse($r->is_link);
        } else {
            throw new UnexpectedValueException(__DIR__.' type is not "dir"');
        }
    }

    /**
     * @covers \Kint\Object\Representation\SplFileInfoRepresentation::__construct
     */
    public function testConstructDirLink()
    {
        $f = __DIR__.'/testDirLink2';

        $r = new SplFileInfoRepresentation(new SplFileInfo($f));

        $this->assertSame(\filesize($f), $r->size);
        $this->assertSame(\filectime($f), $r->ctime);
        $this->assertSame(\filemtime($f), $r->mtime);
        $this->assertSame(\fileperms($f), $r->perms);
        $this->assertSame(\fileowner($f), $r->owner);
        $this->assertSame(\filegroup($f), $r->group);
        $this->assertSame('Directory symlink', $r->typename);
        $this->assertSame('l', $r->typeflag);
        $this->assertSame($f, $r->path);
        $this->assertSame(\dirname(__DIR__), $r->realpath);
        $this->assertSame(__DIR__.'/testDirLink', $r->linktarget);

        if ('link' === \filetype($f)) {
            $this->assertFalse($r->is_file);
            $this->assertTrue($r->is_dir);
            $this->assertTrue($r->is_link);
        } else {
            throw new UnexpectedValueException($f.' type is not "link"');
        }
    }

    /**
     * @covers \Kint\Object\Representation\SplFileInfoRepresentation::__construct
     */
    public function testConstructLinkedDir()
    {
        $f = __DIR__.'/testDirLink/'.\basename(__DIR__);

        $r = new SplFileInfoRepresentation(new SplFileInfo($f));

        $this->assertSame(\filesize($f), $r->size);
        $this->assertSame(\filectime($f), $r->ctime);
        $this->assertSame(\filemtime($f), $r->mtime);
        $this->assertSame(\fileperms($f), $r->perms);
        $this->assertSame(\fileowner($f), $r->owner);
        $this->assertSame(\filegroup($f), $r->group);
        $this->assertSame('Directory', $r->typename);
        $this->assertSame('d', $r->typeflag);
        $this->assertSame($f, $r->path);
        $this->assertSame(__DIR__, $r->realpath);
        $this->assertNull($r->linktarget);

        if ('dir' === \filetype($f)) {
            $this->assertFalse($r->is_file);
            $this->assertTrue($r->is_dir);
            $this->assertFalse($r->is_link);
        } else {
            throw new UnexpectedValueException($f.' type is not "dir"');
        }
    }

    /**
     * @covers \Kint\Object\Representation\SplFileInfoRepresentation::__construct
     */
    public function testConstructPipe()
    {
        $f = __DIR__.'/testPipe';

        $r = new SplFileInfoRepresentation(new SplFileInfo($f));

        $this->assertSame(\filesize($f), $r->size);
        $this->assertSame(\filectime($f), $r->ctime);
        $this->assertSame(\filemtime($f), $r->mtime);
        $this->assertSame(\fileperms($f), $r->perms);
        $this->assertSame(\fileowner($f), $r->owner);
        $this->assertSame(\filegroup($f), $r->group);
        $this->assertSame('Named pipe', $r->typename);
        $this->assertSame('p', $r->typeflag);
        $this->assertSame($f, $r->path);
        $this->assertSame($f, $r->realpath);
        $this->assertNull($r->linktarget);

        if ('fifo' === \filetype($f)) {
            $this->assertFalse($r->is_file);
            $this->assertFalse($r->is_dir);
            $this->assertFalse($r->is_link);
        } else {
            throw new UnexpectedValueException($f.' type is not "fifo"');
        }
    }

    /**
     * @covers \Kint\Object\Representation\SplFileInfoRepresentation::__construct
     */
    public function testConstructSocket()
    {
        $f = __DIR__.'/testSocket';

        $r = new SplFileInfoRepresentation(new SplFileInfo($f));

        $this->assertSame(\filesize($f), $r->size);
        $this->assertSame(\filectime($f), $r->ctime);
        $this->assertSame(\filemtime($f), $r->mtime);
        $this->assertSame(\fileperms($f), $r->perms);
        $this->assertSame(\fileowner($f), $r->owner);
        $this->assertSame(\filegroup($f), $r->group);
        $this->assertSame('Socket', $r->typename);
        $this->assertSame('s', $r->typeflag);
        $this->assertSame($f, $r->path);
        $this->assertSame($f, $r->realpath);
        $this->assertNull($r->linktarget);

        if ('socket' === \filetype($f)) {
            $this->assertFalse($r->is_file);
            $this->assertFalse($r->is_dir);
            $this->assertFalse($r->is_link);
        } else {
            throw new UnexpectedValueException($f.' type is not "socket"');
        }
    }

    /**
     * @covers \Kint\Object\Representation\SplFileInfoRepresentation::__construct
     */
    public function testConstructCharacterDevice()
    {
        $f = '/dev/null';

        $r = new SplFileInfoRepresentation(new SplFileInfo($f));

        $this->assertSame(\filesize($f), $r->size);
        $this->assertSame(\filectime($f), $r->ctime);
        $this->assertSame(\filemtime($f), $r->mtime);
        $this->assertSame(\fileperms($f), $r->perms);
        $this->assertSame(\fileowner($f), $r->owner);
        $this->assertSame(\filegroup($f), $r->group);
        $this->assertSame('Character device', $r->typename);
        $this->assertSame('c', $r->typeflag);
        $this->assertSame($f, $r->path);
        $this->assertSame($f, $r->realpath);
        $this->assertNull($r->linktarget);

        if ('char' === \filetype($f)) {
            $this->assertFalse($r->is_file);
            $this->assertFalse($r->is_dir);
            $this->assertFalse($r->is_link);
        } else {
            throw new UnexpectedValueException($f.' type is not "char"');
        }
    }

    /**
     * @covers \Kint\Object\Representation\SplFileInfoRepresentation::__construct
     */
    public function testConstructBlockDevice()
    {
        $f = '/dev/loop0';

        if (\file_exists($f)) {
            $sfi = new SplFileInfo($f);
            $size = \filesize($f);
            $ctime = \filectime($f);
            $mtime = \filemtime($f);
            $perms = \fileperms($f);
            $owner = \fileowner($f);
            $group = \filegroup($f);
            $type = \filetype($f);
        } else {
            $type = 'block';
            $mock = $this->prophesize('SplFileInfo');
            $mock->getSize()->willReturn($size = 0);
            $mock->getCTime()->willReturn($ctime = \time());
            $mock->getMTime()->willReturn($mtime = \time());
            $mock->getPerms()->willReturn($perms = 0x6444);
            $mock->getOwner()->willReturn($owner = \fileowner(__FILE__));
            $mock->getGroup()->willReturn($group = \filegroup(__FILE__));
            $mock->getPathname()->willReturn($f);
            $mock->getRealPath()->willReturn($f);
            $mock->isDir()->willReturn(false);
            $mock->isFile()->willReturn(false);
            $mock->isLink()->willReturn(false);
            $sfi = $mock->reveal();
        }

        $r = new SplFileInfoRepresentation($sfi);

        $this->assertSame($size, $r->size);
        $this->assertSame($ctime, $r->ctime);
        $this->assertSame($mtime, $r->mtime);
        $this->assertSame($perms, $r->perms);
        $this->assertSame($owner, $r->owner);
        $this->assertSame($group, $r->group);
        $this->assertSame('Block device', $r->typename);
        $this->assertSame('b', $r->typeflag);
        $this->assertSame($f, $r->path);
        $this->assertSame($f, $r->realpath);
        $this->assertNull($r->linktarget);

        if ('block' === $type) {
            $this->assertFalse($r->is_file);
            $this->assertFalse($r->is_dir);
            $this->assertFalse($r->is_link);
        } else {
            throw new UnexpectedValueException($f.' type is not "block"');
        }
    }

    /**
     * @covers \Kint\Object\Representation\SplFileInfoRepresentation::__construct
     */
    public function testConstructNone()
    {
        $f = __FILE__.'/nonexistant';

        $r = new SplFileInfoRepresentation(new SplFileInfo($f));

        $this->assertNull($r->size);
        $this->assertNull($r->ctime);
        $this->assertNull($r->mtime);
        $this->assertNull($r->perms);
        $this->assertNull($r->owner);
        $this->assertNull($r->group);
        $this->assertSame('Unknown file', $r->typename);
        $this->assertSame('-', $r->typeflag);
        $this->assertSame($f, $r->path);
        $this->assertNull($r->realpath);
        $this->assertNull($r->linktarget);
        $this->assertFalse($r->is_file);
        $this->assertFalse($r->is_dir);
        $this->assertFalse($r->is_link);
    }

    /**
     * @covers \Kint\Object\Representation\SplFileInfoRepresentation::getLabel
     */
    public function testGetLabel()
    {
        $r = new SplFileInfoRepresentation(new SplFileInfo(__FILE__));

        $r->typename = 'test123';
        $r->size = 1100;

        $this->assertSame('test123 (1.1KB)', $r->getLabel());
    }

    /**
     * @covers \Kint\Object\Representation\SplFileInfoRepresentation::getSize
     */
    public function testGetSize()
    {
        $r = new SplFileInfoRepresentation(new SplFileInfo(__FILE__));

        $r->size = 0;
        $this->assertNull($r->getSize());

        $r->size = 42;
        $this->assertSame('42B', $r->getSize());

        $r->size = 1024;
        $this->assertSame('1KB', $r->getSize());

        $r->size = 1100;
        $this->assertSame('1.1KB', $r->getSize());

        $r->size = 1024 * 1024;
        $this->assertSame('1MB', $r->getSize());
    }

    /**
     * @covers \Kint\Object\Representation\SplFileInfoRepresentation::getMTime
     */
    public function testGetMTime()
    {
        $r = new SplFileInfoRepresentation(new SplFileInfo(__FILE__));
        $r->mtime = 0;

        $this->assertSame('Jan 01 1970', $r->getMTime());

        $dt = new DateTime('midnight +1 hour +23 minutes');
        $r->mtime = $dt->format('U');

        $this->assertSame(\date('M d').' 01:23', $r->getMTime());
    }
}
