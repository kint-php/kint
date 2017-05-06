<?php

abstract class KintTestCase extends PHPUnit_Framework_TestCase
{
    protected $kint_status;
    protected $char_encodings;

    public function setUp()
    {
        $this->kint_status = Kint::settings();
        $this->char_encodings = Kint_Object_Blob::$char_encodings;
    }

    public function tearDown()
    {
        Kint::settings($this->kint_status);
        Kint_Object_Blob::$char_encodings = $this->char_encodings;
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
