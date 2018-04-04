<?php

namespace Kint\Test\Object\Representation;

use Kint\Object\Representation\SourceRepresentation;
use Kint\Test\KintTestCase;

class SourceRepresentationTest extends KintTestCase
{
    /**
     * @covers \Kint\Object\Representation\SourceRepresentation::__construct
     * @covers \Kint\Object\Representation\SourceRepresentation::getSource
     */
    public function testConstruct()
    {
        $source = file_get_contents(__FILE__);
        $source = explode("\n", $source);
        $source = array_merge(array(null), $source);

        $r = new SourceRepresentation(__FILE__, 1);
        $this->assertSame('source', $r->getName());
        $this->assertSame(array_slice($source, 1, 8, true), $r->source);

        $r = new SourceRepresentation(__FILE__, 1, 7);
        $this->assertSame(array_slice($source, 1, 8, true), $r->source);

        $r = new SourceRepresentation(__FILE__, 1, 9);
        $this->assertSame(array_slice($source, 1, 10, true), $r->source);
        $this->assertSame(implode("\n", array_slice($source, 1, 10, true)), $r->contents);

        // Trims the whitespace line in contents
        $r = new SourceRepresentation(__FILE__, 1, 6);
        $this->assertSame(array_slice($source, 1, 7, true), $r->source);
        $this->assertSame(implode("\n", array_slice($source, 1, 7, true)), $r->contents);

        $r = new SourceRepresentation(__FILE__.'/nonexistant', 1);
        $this->assertNull($r->source);
        $this->assertEmpty($r->contents);
    }
}
