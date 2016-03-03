# ReactPHP Event Loop Inspector
[![Linux Build Status](https://travis-ci.org/WyriHaximus/reactphp-event-loop-inspector.png)](https://travis-ci.org/WyriHaximus/reactphp-event-loop-inspector)
[![Latest Stable Version](https://poser.pugx.org/WyriHaximus/react-event-loop-inspector/v/stable.png)](https://packagist.org/packages/WyriHaximus/react-event-loop-inspector)
[![Total Downloads](https://poser.pugx.org/WyriHaximus/react-event-loop-inspector/downloads.png)](https://packagist.org/packages/WyriHaximus/react-event-loop-inspector)
[![Code Coverage](https://scrutinizer-ci.com/g/WyriHaximus/reactphp-event-loop-inspector/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/WyriHaximus/reactphp-event-loop-inspector/?branch=master)
[![License](https://poser.pugx.org/WyriHaximus/react-event-loop-inspector/license.png)](https://packagist.org/packages/wyrihaximus/react-event-loop-inspector)
[![PHP 7 ready](http://php7ready.timesplinter.ch/WyriHaximus/reactphp-event-loop-inspector/badge.svg)](https://travis-ci.org/WyriHaximus/reactphp-event-loop-inspector)

Wrap a [ReactPHP child process](https://github.com/reactphp/child-process) in a promise, once the process ends the promise resolves with the exit code and `STDERR` and `STDOUT` buffers.

### Installation ###

To install via [Composer](http://getcomposer.org/), use the command below, it will automatically detect the latest version and bind it with `^`.

```
composer require wyrihaximus/react-event-loop-inspector 
```

## Contributing ##

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## License ##

Copyright 2016 [Cees-Jan Kiewiet](http://wyrihaximus.net/)

Permission is hereby granted, free of charge, to any person
obtaining a copy of this software and associated documentation
files (the "Software"), to deal in the Software without
restriction, including without limitation the rights to use,
copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the
Software is furnished to do so, subject to the following
conditions:

The above copyright notice and this permission notice shall be
included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES
OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY,
WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR
OTHER DEALINGS IN THE SOFTWARE.
