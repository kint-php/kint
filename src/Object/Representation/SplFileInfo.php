<?php

class Kint_Object_Representation_SplFileInfo extends Kint_Object_Representation
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
            $this->linktarget = $fileInfo->getLinktarget();
        }

        $this->flags = array($this->typeflag);

        // User
        $this->flags[] = (($this->perms & 0400) ? 'r' : '-');
        $this->flags[] = (($this->perms & 0200) ? 'w' : '-');
        $this->flags[] = (($this->perms & 0100) ? (($this->perms & 04000) ? 's' : 'x') : (($this->perms & 04000) ? 'S' : '-'));

        // Group
        $this->flags[] = (($this->perms & 0040) ? 'r' : '-');
        $this->flags[] = (($this->perms & 0020) ? 'w' : '-');
        $this->flags[] = (($this->perms & 0010) ? (($this->perms & 02000) ? 's' : 'x') : (($this->perms & 02000) ? 'S' : '-'));

        // Other
        $this->flags[] = (($this->perms & 0004) ? 'r' : '-');
        $this->flags[] = (($this->perms & 0002) ? 'w' : '-');
        $this->flags[] = (($this->perms & 0001) ? (($this->perms & 01000) ? 't' : 'x') : (($this->perms & 01000) ? 'T' : '-'));

        $this->contents = implode($this->flags).' '.$this->owner.' '.$this->group.' '.$this->getSize().' '.$this->getMTime().' ';

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
        static $unit = array('B', 'KB', 'MB', 'GB', 'TB');

        $size = $this->size;

        if ($this->size) {
            $i = floor(log($this->size, 1024));
            $size = round($this->size / pow(1024, $i), 2).$unit[$i];
        }

        return $size;
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
