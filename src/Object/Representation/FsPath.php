<?php

class Kint_Object_Representation_FsPath extends Kint_Object_Representation
{
    public $perms = null;
    public $path = null;
    public $realpath = null;
    public $linktarget = null;
    public $size = 0;
    public $is_dir = false;
    public $is_file = false;
    public $is_link = false;
    public $typename = 'Unknown file';
    public $typeflag = '-';
    public $renderers = array('fspath');

    public function __construct(SplFileInfo $fileInfo)
    {
        $this->perms = $fileInfo->getPerms();
        $this->size = $fileInfo->getSize();
        $this->is_dir = $fileInfo->isDir();
        $this->is_file = $fileInfo->isFile();
        $this->is_link = $fileInfo->isLink();

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

        parent::__construct('FsPath');

        $this->path = $fileInfo->getPathname();
        $this->realpath = realpath($this->path);

        if ($this->is_link && method_exists($fileInfo, 'getLinktarget')) {
            $this->linktarget = $fileInfo->getLinktarget();
        }

        $flags = array($this->typeflag);

        // User
        $flags[] = (($this->perms & 0x0100) ? 'r' : '-');
        $flags[] = (($this->perms & 0x0080) ? 'w' : '-');
        $flags[] = (($this->perms & 0x0040) ? (($this->perms & 0x0800) ? 's' : 'x') : (($this->perms & 0x0800) ? 'S' : '-'));

        // Group
        $flags[] = (($this->perms & 0x0020) ? 'r' : '-');
        $flags[] = (($this->perms & 0x0010) ? 'w' : '-');
        $flags[] = (($this->perms & 0x0008) ? (($this->perms & 0x0400) ? 's' : 'x') : (($this->perms & 0x0400) ? 'S' : '-'));

        // Other
        $flags[] = (($this->perms & 0x0004) ? 'r' : '-');
        $flags[] = (($this->perms & 0x0002) ? 'w' : '-');
        $flags[] = (($this->perms & 0x0001) ? (($this->perms & 0x0200) ? 't' : 'x') : (($this->perms & 0x0200) ? 'T' : '-'));

        $this->contents = implode($flags);
    }

    public function getLabel()
    {
        return Kint_Object_Blob::escape($this->typename.' ('.sprintf('%.2fK', $this->size / 1024).')');
    }
}
