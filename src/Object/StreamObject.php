<?php

namespace Kint\Object;

use Kint\Kint;

class StreamObject extends ResourceObject
{
    public $stream_meta = null;

    public function __construct(array $meta = null)
    {
        parent::__construct();
        $this->stream_meta = $meta;
    }

    public function getValueShort()
    {
        if (empty($this->stream_meta['uri'])) {
            return;
        }

        $uri = $this->stream_meta['uri'];

        if (stream_is_local($uri)) {
            return Kint::shortenPath($uri);
        } else {
            return $uri;
        }
    }
}
