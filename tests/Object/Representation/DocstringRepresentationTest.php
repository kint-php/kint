<?php

namespace Kint\Test\Object\Representation;

use Kint\Object\Representation\DocstringRepresentation;
use Kint\Test\KintTestCase;

class DocstringRepresentationTest extends KintTestCase
{
    /**
     * @covers \Kint\Object\Representation\DocstringRepresentation::__construct
     */
    public function testConstruct()
    {
        $r = new DocstringRepresentation('this is a string', 'filename', 123, 'classname');

        $this->assertSame('this is a string', $r->contents);
        $this->assertSame('filename', $r->file);
        $this->assertSame(123, $r->line);
        $this->assertSame('classname', $r->class);
        $this->assertSame('docstring', $r->getName());
    }

    public function docstringProvider()
    {
        return array(
            'single line' => array(
                '/**
                  * @return wat
                  */',
                 '@return wat',
            ),
            'empty' => array(
                '',
                null,
            ),
            'bullets' => array(
                '/**
                  * * This is an item
                  * * This is another item
                  */',
                 "* This is an item\n* This is another item",
            ),
        );
    }

    /**
     * @covers \Kint\Object\Representation\DocstringRepresentation::getDocstringWithoutComments
     * @dataProvider docstringProvider
     */
    public function testGetDocstringWithoutComments($input, $expect)
    {
        $r = new DocstringRepresentation($input, null, null);

        $this->assertSame($expect, $r->getDocstringWithoutComments());
    }
}
