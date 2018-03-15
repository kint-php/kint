<?php

namespace Kint\Test;

use Kint\Kint;
use Kint\Object\BlobObject;
use Kint\Renderer\TextRenderer;
use PHPUnit_Framework_TestCase;
use PHPUnit_Util_InvalidArgumentHelper;

abstract class KintTestCase extends PHPUnit_Framework_TestCase
{
    protected $kint_status;
    protected $char_encodings;
    protected $text_decorations;
    protected $text_plugin_whitelist;

    public function setUp()
    {
        $this->kint_status = Kint::settings();
        $this->char_encodings = BlobObject::$char_encodings;
        $this->text_decorations = TextRenderer::$decorations;
        $this->text_plugin_whitelist = TextRenderer::$parser_plugin_whitelist;
    }

    public function tearDown()
    {
        Kint::settings($this->kint_status);
        BlobObject::$char_encodings = $this->char_encodings;
        TextRenderer::$decorations = $this->text_decorations;
        TextRenderer::$parser_plugin_whitelist = $this->text_plugin_whitelist;
    }

    /**
     * Asserts that a condition is true.
     *
     * @param array  $expected
     * @param string $actual
     * @param string $message
     *
     * @throws PHPUnit_Framework_Exception
     */
    public function assertLike(array $expected, $actual, $message = '')
    {
        if (!is_string($actual)) {
            throw PHPUnit_Util_InvalidArgumentHelper::factory(2, 'string');
        }

        self::assertThat($actual, new ContainsInOrderConstraint($expected), $message);
    }
}
