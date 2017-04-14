<?php

abstract class KintTestCase extends PHPUnit_Framework_TestCase
{
    protected static $kint_status;

    public function setUp()
    {
        self::$kint_status = Kint::settings();
    }

    public function tearDown()
    {
        Kint::settings(self::$kint_status);
    }

    /**
     * Asserts that a condition is true.
     *
     * @param array  $expected
     * @param string $actual
     * @param string $message
     *
     * @throws PHPUnit_Framework_AssertionFailedError
     */
    public static function assertLike(array $expected, $actual, $message = '')
    {
        if (!is_string($actual)) {
            throw PHPUnit_Util_InvalidArgumentHelper::factory(2, 'string');
        }

        self::assertThat($actual, new ContainsInOrder($expected), $message);
    }
}
