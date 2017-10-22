<?php

namespace Kint\Test;

use Kint;
use Kint\Object\BlobObject;
use PHPUnit_Framework_TestCase;
use PHPUnit_Util_InvalidArgumentHelper;

abstract class KintTestCase extends PHPUnit_Framework_TestCase
{
    protected $kint_status;
    protected $char_encodings;

    public function setUp()
    {
        $this->kint_status = Kint::settings();
        $this->char_encodings = BlobObject::$char_encodings;
    }

    public function tearDown()
    {
        Kint::settings($this->kint_status);
        BlobObject::$char_encodings = $this->char_encodings;
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

        self::assertThat($actual, new ContainsInOrderConstraint($expected), $message);
    }
}
