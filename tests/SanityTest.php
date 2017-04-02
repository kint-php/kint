<?php

class SanityTest extends KintTestCase
{
    /**
     * Essentially a copy paste job of the basic.php test.
     *
     * It's more of an integration test than a unit test.
     */
    public function testSanity()
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

        Kint::$enabled_mode = Kint::MODE_RICH;
        $this->assertLike(array_merge(
                $array_structure,
                array('&amp;array', '6'),
                $array_structure,
                array('&amp;array', 'Recursion')
            ),
            d($testdata)
        );

        Kint::$enabled_mode = Kint::MODE_PLAIN;
        $this->assertLike(array_merge(
                $array_structure,
                array('&amp;array', '6'),
                $array_structure,
                array('&amp;array', 'RECURSION')
            ),
            d($testdata)
        );

        Kint::$enabled_mode = Kint::MODE_CLI;
        $this->assertLike(array_merge(
                $array_structure,
                array('&array', '6'),
                $array_structure,
                array('&array', 'RECURSION')
            ),
            d($testdata)
        );

        Kint::$enabled_mode = Kint::MODE_TEXT;
        $this->assertLike(array_merge(
                $array_structure,
                array('&array', '6'),
                $array_structure,
                array('&array', 'RECURSION')
            ),
            d($testdata)
        );
    }

    /**
     * Test this test suite's restore after test.
     */
    public function testStore()
    {
        Kint::$return = 'test_store';
        $this->assertEquals('test_store', Kint::$return);
    }

    /**
     * Test this test suite's restore after test.
     *
     * @depends testStore
     */
    public function testRestore()
    {
        $this->assertNotEquals('test_store', Kint::$return);
    }
}
