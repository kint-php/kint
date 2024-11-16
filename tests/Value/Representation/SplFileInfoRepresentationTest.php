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

namespace Kint\Test\Value\Representation;

use DateTime;
use Kint\Test\KintTestCase;
use Kint\Value\Representation\SplFileInfoRepresentation;
use SplFileInfo;

/**
 * @coversNothing
 */
class SplFileInfoRepresentationTest extends KintTestCase
{
    protected int $uid;
    protected int $gid;
    protected int $umask;
    protected int $mtime;
    protected string $mstring;
    protected $socket;

    public function __construct(?string $name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);

        $this->uid = \getmyuid();
        $this->gid = \getmygid();
        $this->mtime = \time();
        $dt = DateTime::createFromFormat('U', (string) $this->mtime);
        $this->mstring = $dt->format('M d H:i');
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->umask = \umask(0);

        \ini_set('open_basedir', \realpath(__DIR__.'/../../../').':/dev:/tmp');

        \symlink(__DIR__.'/testFile', __DIR__.'/testFileLink');
        \symlink(__DIR__.'/testFileLink', __DIR__.'/testFileLink2');

        \symlink(\dirname(__DIR__), __DIR__.'/testDirLink');
        \symlink(__DIR__.'/testDirLink', __DIR__.'/testDirLink2');

        \file_put_contents(__DIR__.'/testFileRecent', 'Some data for you');
        \symlink(__DIR__.'/testFileRecent', __DIR__.'/testFileLinkRecent');

        \posix_mkfifo(__DIR__.'/testPipe', 0777);

        $this->socket = \socket_create(AF_UNIX, SOCK_STREAM, 0);
        \socket_bind($this->socket, __DIR__.'/testSocket');

        \touch(__DIR__.'/testFile', 1234567890);
        \touch(__DIR__.'/testFileRecent', $this->mtime);
        \touch(__DIR__.'/testPipe', 1234567890);
        \touch(__DIR__.'/testSocket', 1234567890);
        \touch(__DIR__, 1234567890);
        \touch(\dirname(__DIR__), 1234567890);

        \chmod(__DIR__, 0755);
        \chmod(\dirname(__DIR__), 0755);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        \umask($this->umask);

        \unlink(__DIR__.'/testFile');
        \unlink(__DIR__.'/testFileLink');
        \unlink(__DIR__.'/testFileLink2');

        \unlink(__DIR__.'/testDirLink');
        \unlink(__DIR__.'/testDirLink2');

        \unlink(__DIR__.'/testFileRecent');
        \unlink(__DIR__.'/testFileLinkRecent');

        \unlink(__DIR__.'/testPipe');

        \socket_close($this->socket);
        \unlink(__DIR__.'/testSocket');
    }

    public function fileProvider()
    {
        return [
            'file' => [
                __DIR__.'/testFile',
                '-rw-rw-rw- '.$this->uid.' '.$this->gid.' 0 Feb 13 2009 '.__DIR__.'/testFile',
                'File (0B)',
            ],
            'fileRecent' => [
                __DIR__.'/testFileRecent',
                '-rw-rw-rw- '.$this->uid.' '.$this->gid.' 17 '.$this->mstring.' '.__DIR__.'/testFileRecent',
                'File (17B)',
            ],
            'fileLinkRecent' => [
                __DIR__.'/testFileLinkRecent',
                'lrw-rw-rw- '.$this->uid.' '.$this->gid.' 17 '.$this->mstring.' '.__DIR__.'/testFileLinkRecent -> '.__DIR__.'/testFileRecent',
                'File symlink (17B)',
            ],
            'fileLink' => [
                __DIR__.'/testFileLink',
                'lrw-rw-rw- '.$this->uid.' '.$this->gid.' 0 Feb 13 2009 '.__DIR__.'/testFileLink -> '.__DIR__.'/testFile',
                'File symlink (0B)',
            ],
            'indirectFileLink' => [
                __DIR__.'/testFileLink2',
                'lrw-rw-rw- '.$this->uid.' '.$this->gid.' 0 Feb 13 2009 '.__DIR__.'/testFileLink2 -> '.__DIR__.'/testFileLink',
                'File symlink (0B)',
            ],
            'linkedFile' => [
                __DIR__.'/testDirLink/'.\basename(__DIR__).'/testFile',
                '-rw-rw-rw- '.$this->uid.' '.$this->gid.' 0 Feb 13 2009 '.__DIR__.'/testFile',
                'File (0B)',
            ],
            'dir' => [
                __DIR__,
                'drwxr-xr-x '.$this->uid.' '.$this->gid.' 4096 Feb 13 2009 '.__DIR__,
                'Directory',
            ],
            'open_basedir' => [
                '/usr',
                '----------     /usr',
                'Unknown file',
            ],
            'dirLink' => [
                __DIR__.'/testDirLink',
                'lrwxr-xr-x '.$this->uid.' '.$this->gid.' 4096 Feb 13 2009 '.__DIR__.'/testDirLink -> '.\dirname(__DIR__),
                'Directory symlink',
            ],
            'dirLink2' => [
                __DIR__.'/testDirLink2',
                'lrwxr-xr-x '.$this->uid.' '.$this->gid.' 4096 Feb 13 2009 '.__DIR__.'/testDirLink2 -> '.__DIR__.'/testDirLink',
                'Directory symlink',
            ],
            'linkedDir' => [
                __DIR__.'/testDirLink/'.\basename(__DIR__),
                'drwxr-xr-x '.$this->uid.' '.$this->gid.' 4096 Feb 13 2009 '.__DIR__,
                'Directory',
            ],
            'pipe' => [
                __DIR__.'/testPipe',
                'prwxrwxrwx '.$this->uid.' '.$this->gid.' 0 Feb 13 2009 '.__DIR__.'/testPipe',
                'Named pipe',
            ],
            'socket' => [
                __DIR__.'/testSocket',
                'srwxrwxrwx '.$this->uid.' '.$this->gid.' 0 Feb 13 2009 '.__DIR__.'/testSocket',
                'Socket',
            ],
            '404' => [
                __DIR__.'/nonexistant',
                '----------     '.__DIR__.'/nonexistant',
                'Unknown file',
            ],
            'empty' => [
                '',
                '----------     ',
                'Unknown file',
            ],
            'garbage' => [
                'asdffffff',
                '----------     asdffffff',
                'Unknown file',
            ],
        ];
    }

    /**
     * @dataProvider fileProvider
     *
     * @covers \Kint\Value\Representation\SplFileInfoRepresentation::__construct
     * @covers \Kint\Value\Representation\SplFileInfoRepresentation::getLabel
     */
    public function testConstruct(string $path, string $contents, string $label)
    {
        $r = new SplFileInfoRepresentation(new SplFileInfo($path));

        $this->assertSame($contents, $r->getValue());
        $this->assertSame($label, $r->getLabel());
    }

    /**
     * @covers \Kint\Value\Representation\SplFileInfoRepresentation::__construct
     * @covers \Kint\Value\Representation\SplFileInfoRepresentation::getLabel
     */
    public function testCharacterDevice()
    {
        $f = '/dev/null';

        $r = new SplFileInfoRepresentation(new SplFileInfo($f));

        $this->assertMatchesRegularExpression('%^crw-rw-rw- 0 0 0 .{11,12} /dev/null$%', $r->getValue());
        $this->assertSame('Character device', $r->getLabel());
    }

    /**
     * @covers \Kint\Value\Representation\SplFileInfoRepresentation::__construct
     * @covers \Kint\Value\Representation\SplFileInfoRepresentation::getLabel
     */
    public function testBlockDevice()
    {
        $f = '/dev/loop0';

        $sfi = $this->createMock(SplFileInfo::class);
        $sfi->method('getSize')->willReturn(0);
        $sfi->method('getCTime')->willReturn(\time());
        $sfi->method('getMTime')->willReturn(1234567890);
        $sfi->method('getPerms')->willReturn(0x6000 | 0660);
        $sfi->method('getOwner')->willReturn(0);
        $sfi->method('getGroup')->willReturn(0);
        $sfi->method('getPathname')->willReturn($f);
        $sfi->method('getRealPath')->willReturn($f);
        $sfi->method('isDir')->willReturn(false);
        $sfi->method('isFile')->willReturn(false);
        $sfi->method('isLink')->willReturn(false);

        $r = new SplFileInfoRepresentation($sfi);

        $this->assertSame('brw-rw---- 0 0 0 Feb 13 2009 '.$f, $r->getValue());
        $this->assertSame('Block device', $r->getLabel());
    }

    /**
     * @covers \Kint\Value\Representation\SplFileInfoRepresentation::getHint
     */
    public function testGetHint()
    {
        $r = new SplFileInfoRepresentation(new SplFileInfo(__DIR__.'/testFile'));
        $this->assertSame('splfileinfo', $r->getHint());
    }
}
