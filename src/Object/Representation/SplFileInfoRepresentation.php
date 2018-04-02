<?php

namespace Kint\Object\Representation;

use Kint\Utils;
use SplFileInfo;

class SplFileInfoRepresentation extends Representation
{
    public $perms = null;
    public $flags = null;
    public $path = null;
    public $realpath = null;
    public $linktarget = null;
    public $size = null;
    public $is_dir = false;
    public $is_file = false;
    public $is_link = false;
    public $owner = null;
    public $group = null;
    public $ctime = null;
    public $mtime = null;
    public $typename = 'Unknown file';
    public $typeflag = '-';
    public $hints = array('fspath');

    public function __construct(SplFileInfo $fileInfo)
    {
        if (!file_exists($fileInfo->getPathname())) {
            return;
        }

        $this->perms = $fileInfo->getPerms();
        $this->size = $fileInfo->getSize();
        $this->is_dir = $fileInfo->isDir();
        $this->is_file = $fileInfo->isFile();
        $this->is_link = $fileInfo->isLink();
        $this->owner = $fileInfo->getOwner();
        $this->group = $fileInfo->getGroup();
        $this->ctime = $fileInfo->getCTime();
        $this->mtime = $fileInfo->getMTime();

        if (($this->perms & 0xC000) === 0xC000) {
            $this->typename = 'File socket';
            $this->typeflag = 's';
        } elseif ($this->is_file) {
            if ($this->is_link) {
                $this->typename = 'File symlink';
                $this->typeflag = 'l';
            } else {
                $this->typename = 'File';
                $this->typeflag = '-';
            }
        } elseif (($this->perms & 0x6000) === 0x6000) {
            $this->typename = 'Block special file';
            $this->typeflag = 'b';
        } elseif ($this->is_dir) {
            if ($this->is_link) {
                $this->typename = 'Directory symlink';
                $this->typeflag = 'l';
            } else {
                $this->typename = 'Directory';
                $this->typeflag = 'd';
            }
        } elseif (($this->perms & 0x2000) === 0x2000) {
            $this->typename = 'Character special file';
            $this->typeflag = 'c';
        } elseif (($this->perms & 0x1000) === 0x1000) {
            $this->typename = 'FIFO pipe file';
            $this->typeflag = 'p';
        }

        parent::__construct('SplFileInfo');

        $this->path = $fileInfo->getPathname();
        $this->realpath = realpath($this->path);

        if ($this->is_link && method_exists($fileInfo, 'getLinktarget')) {
            $this->linktarget = $fileInfo->getLinkTarget();
        }

        $this->flags = array($this->typeflag);

        // User
        $this->flags[] = (($this->perms & 0400) ? 'r' : '-');
        $this->flags[] = (($this->perms & 0200) ? 'w' : '-');
        if ($this->perms & 0100) {
            $this->flags[] = ($this->perms & 04000) ? 's' : 'x';
        } else {
            $this->flags[] = ($this->perms & 04000) ? 'S' : '-';
        }

        // Group
        $this->flags[] = (($this->perms & 0040) ? 'r' : '-');
        $this->flags[] = (($this->perms & 0020) ? 'w' : '-');
        if ($this->perms & 0010) {
            $this->flags[] = ($this->perms & 02000) ? 's' : 'x';
        } else {
            $this->flags[] = ($this->perms & 02000) ? 'S' : '-';
        }

        // Other
        $this->flags[] = (($this->perms & 0004) ? 'r' : '-');
        $this->flags[] = (($this->perms & 0002) ? 'w' : '-');
        if ($this->perms & 0001) {
            $this->flags[] = ($this->perms & 01000) ? 's' : 'x';
        } else {
            $this->flags[] = ($this->perms & 01000) ? 'S' : '-';
        }

        $this->contents = implode($this->flags).' '.$this->owner.' '.$this->group;
        $this->contents .= ' '.$this->getSize().' '.$this->getMTime().' ';

        if ($this->is_link && $this->linktarget) {
            $this->contents .= $this->path.' -> '.$this->linktarget;
        } elseif (strlen($this->realpath) < strlen($this->path)) {
            $this->contents .= $this->realpath;
        } else {
            $this->contents .= $this->path;
        }
    }

    public function getLabel()
    {
        return $this->typename.' ('.$this->getSize().')';
    }

    public function getSize()
    {
        if ($this->size) {
            $size = Utils::getHumanReadableBytes($this->size);

            return round($size['value'], 2).$size['unit'];
        }
    }

    public function getMTime()
    {
        $year = date('Y', $this->mtime);

        if ($year !== date('Y')) {
            return date('M d Y', $this->mtime);
        } else {
            return date('M d H:i', $this->mtime);
        }
    }
}
