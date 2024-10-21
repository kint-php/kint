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

namespace Kint\Value\Representation;

use Kint\Utils;
use RuntimeException;
use SplFileInfo;

class SplFileInfoRepresentation extends StringRepresentation
{
    protected ?int $size = null;
    protected bool $is_file = false;
    protected string $typename = 'Unknown file';

    public function __construct(SplFileInfo $fileInfo)
    {
        $path = $fileInfo->getPathname();

        $perms = 0;
        $owner = null;
        $group = null;
        $mtime = null;
        $realpath = null;
        $linktarget = null;
        $is_dir = false;
        $is_link = false;

        try {
            if (\strlen($path) && $fileInfo->getRealPath()) {
                $perms = $fileInfo->getPerms();
                $this->size = $fileInfo->getSize();
                $owner = $fileInfo->getOwner();
                $group = $fileInfo->getGroup();
                $mtime = $fileInfo->getMTime();
                $realpath = $fileInfo->getRealPath();
            }

            $is_dir = $fileInfo->isDir();
            $this->is_file = $fileInfo->isFile();
            $is_link = $fileInfo->isLink();

            if ($is_link) {
                $lt = $fileInfo->getLinkTarget();
                $linktarget = false === $lt ? null : $lt;
            }
        } catch (RuntimeException $e) {
            if (false === \strpos($e->getMessage(), ' open_basedir ')) {
                throw $e; // @codeCoverageIgnore
            }
        }

        $typeflag = '-';

        switch ($perms & 0xF000) {
            case 0xC000:
                $this->typename = 'Socket';
                $typeflag = 's';
                break;
            case 0x6000:
                $this->typename = 'Block device';
                $typeflag = 'b';
                break;
            case 0x2000:
                $this->typename = 'Character device';
                $typeflag = 'c';
                break;
            case 0x1000:
                $this->typename = 'Named pipe';
                $typeflag = 'p';
                break;
            default:
                if ($this->is_file) {
                    if ($is_link) {
                        $this->typename = 'File symlink';
                        $typeflag = 'l';
                    } else {
                        $this->typename = 'File';
                        $typeflag = '-';
                    }
                } elseif ($is_dir) {
                    if ($is_link) {
                        $this->typename = 'Directory symlink';
                        $typeflag = 'l';
                    } else {
                        $this->typename = 'Directory';
                        $typeflag = 'd';
                    }
                }
                break;
        }

        $flags = [$typeflag];

        // User
        $flags[] = (($perms & 0400) ? 'r' : '-');
        $flags[] = (($perms & 0200) ? 'w' : '-');
        if ($perms & 0100) {
            $flags[] = ($perms & 04000) ? 's' : 'x';
        } else {
            $flags[] = ($perms & 04000) ? 'S' : '-';
        }

        // Group
        $flags[] = (($perms & 0040) ? 'r' : '-');
        $flags[] = (($perms & 0020) ? 'w' : '-');
        if ($perms & 0010) {
            $flags[] = ($perms & 02000) ? 's' : 'x';
        } else {
            $flags[] = ($perms & 02000) ? 'S' : '-';
        }

        // Other
        $flags[] = (($perms & 0004) ? 'r' : '-');
        $flags[] = (($perms & 0002) ? 'w' : '-');
        if ($perms & 0001) {
            $flags[] = ($perms & 01000) ? 's' : 'x';
        } else {
            $flags[] = ($perms & 01000) ? 'S' : '-';
        }

        $contents = \implode($flags).' '.$owner.' '.$group.' '.$this->size.' ';

        if (null !== $mtime) {
            if (\date('Y', $mtime) === \date('Y')) {
                $contents .= \date('M d H:i', $mtime);
            } else {
                $contents .= \date('M d Y', $mtime);
            }
        }

        $contents .= ' ';

        if ($is_link && null !== $linktarget) {
            $contents .= $path.' -> '.$linktarget;
        } elseif (null !== $realpath && \strlen($realpath) < \strlen($path)) {
            $contents .= $realpath;
        } else {
            $contents .= $path;
        }

        parent::__construct('SplFileInfo', $contents);
    }

    public function getHint(): string
    {
        return 'splfileinfo';
    }

    public function getLabel(): string
    {
        if (null !== $this->size && $this->is_file) {
            $size = Utils::getHumanReadableBytes($this->size);

            return $this->typename.' ('.$size['value'].$size['unit'].')';
        }

        return $this->typename;
    }
}
