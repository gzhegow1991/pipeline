<?php

namespace Gzhegow\Pipeline\Lib;

use Gzhegow\Pipeline\Lib\Traits\OsTrait;
use Gzhegow\Pipeline\Lib\Traits\PhpTrait;
use Gzhegow\Pipeline\Lib\Traits\ArrayTrait;
use Gzhegow\Pipeline\Lib\Traits\ParseTrait;
use Gzhegow\Pipeline\Lib\Traits\DebugTrait;
use Gzhegow\Pipeline\Lib\Traits\AssertTrait;


class Lib
{
    use ArrayTrait;
    use AssertTrait;
    use DebugTrait;
    use OsTrait;
    use ParseTrait;
    use PhpTrait;
}
