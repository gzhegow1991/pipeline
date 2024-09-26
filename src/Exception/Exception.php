<?php

namespace Gzhegow\Pipeline\Exception;

use Gzhegow\Pipeline\Lib;


class Exception extends \Exception
    implements ExceptionInterface
{
    public $message;
    public $code;
    public $previous;

    public function __construct(...$errors)
    {
        foreach ( Lib::php_throwable_args(...$errors) as $k => $v ) {
            if (property_exists($this, $k)) {
                $this->{$k} = $v;
            }
        }

        parent::__construct($this->message, $this->code, $this->previous);
    }
}
