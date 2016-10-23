<?php

namespace WyriHaximus\React\Inspector;

use php_user_filter;

class StreamThroughputFilter extends php_user_filter
{
    /**
     * @var LoopDecorator
     */
    private $loopDecorator;

    /**
     * @var string
     */
    private $event;

    /**
     * @var resource
     */
    private $stream;

    public function onCreate()
    {
        $this->loopDecorator = $this->params['loopDecorator'];
        $this->event = $this->params['event'];
        $this->stream = $this->params['stream'];
    }

    function filter($in, $out, &$consumed, $closing)
    {
        while ($bucket = stream_bucket_make_writeable($in)) {
            $consumed += $bucket->datalen;
            stream_bucket_append($out, $bucket);

            $this->loopDecorator->emit(
                $this->event,
                [
                    $this->stream,
                    $bucket->datalen,
                ]
            );
        }
        return PSFS_PASS_ON;
    }
}
