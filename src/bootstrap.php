<?php

use WyriHaximus\React\Inspector\StreamThroughputFilter;

stream_filter_register(StreamThroughputFilter::class, StreamThroughputFilter::class);
