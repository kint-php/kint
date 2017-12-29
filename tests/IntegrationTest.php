<?php

namespace Kint\Test;

use Kint;

class IntegrationTest extends KintTestCase
{
    /**
     * @covers \d
     * @covers \s
     * @covers \Kint::dump
     */
    public function testBasicDumps()
    {
        $testdata = array(
            1234,
            (object) array('abc' => 'def'),
            1234.5678,
            'Good news everyone! I\'ve got some bad news!',
            null,
        );

        $testdata[] = &$testdata;

        $array_structure = array(
            '0', 'integer', '1234',
            '1', 'stdClass', '1',
            'public', 'abc', 'string', '3', 'def',
            '2', 'double', '1234.5678',
            '3', 'string', '43', 'Good news everyone! I\'ve got some bad news!',
            '4', 'null',
        );

        Kint::$return = true;
        Kint::$cli_detection = false;
        Kint::$display_called_from = false;

        Kint::$enabled_mode = Kint::MODE_RICH;
        $richbase = d($testdata);

        $this->assertLike(
            array_merge(
                $array_structure,
                array('&amp;array', '6'),
                $array_structure,
                array('&amp;array', 'Recursion')
            ),
            $richbase
        );

        Kint::$enabled_mode = true;
        $this->assertSame($richbase, d($testdata));
        $this->assertSame($richbase, Kint::dump($testdata));

        Kint::$enabled_mode = Kint::MODE_PLAIN;
        $plainbase = d($testdata);

        $this->assertLike(
            array_merge(
                $array_structure,
                array('&amp;array', '6'),
                $array_structure,
                array('&amp;array', 'RECURSION')
            ),
            $plainbase
        );

        $this->assertSame($plainbase, Kint::dump($testdata));

        Kint::$enabled_mode = true;
        $this->assertSame($plainbase, s($testdata));

        Kint::$enabled_mode = Kint::MODE_CLI;
        $clibase = d($testdata);

        $this->assertLike(
            array_merge(
                $array_structure,
                array('&array', '6'),
                $array_structure,
                array('&array', 'RECURSION')
            ),
            $clibase
        );

        $this->assertSame($clibase, Kint::dump($testdata));

        Kint::$enabled_mode = true;
        Kint::$cli_detection = true;
        $this->assertSame($clibase, d($testdata));
        $this->assertSame($clibase, s($testdata));
        Kint::$cli_detection = false;

        Kint::$enabled_mode = Kint::MODE_TEXT;
        $textbase = d($testdata);

        $this->assertLike(
            array_merge(
                $array_structure,
                array('&array', '6'),
                $array_structure,
                array('&array', 'RECURSION')
            ),
            $textbase
        );

        $this->assertSame($textbase, Kint::dump($testdata));

        Kint::$return = false;
        Kint::$enabled_mode = true;
        ob_start();
        ~d($testdata);
        $this->assertSame($textbase, ob_get_clean());

        Kint::$enabled_mode = false;
        $this->assertSame(0, d($testdata));
        $this->assertSame(0, s($testdata));
    }

    /**
     * Test this test suite's restore after test.
     *
     * @covers \Kint\Test\KintTestCase::setUp
     * @covers \Kint\Test\KintTestCase::tearDown
     */
    public function testStore()
    {
        Kint::$file_link_format = 'test_store';
        $this->assertEquals('test_store', Kint::$file_link_format);
    }

    /**
     * @covers \Kint\Test\KintTestCase::setUp
     * @covers \Kint\Test\KintTestCase::tearDown
     */
    public function testRestore()
    {
        $this->assertNotEquals('test_store', Kint::$file_link_format);
    }
}
