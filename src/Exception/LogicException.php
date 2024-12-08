<?php

namespace Gzhegow\Pipeline\Exception;


class LogicException extends \LogicException
    implements ExceptionInterface
{
    /**
     * @var string
     */
    public $file;
    /**
     * @var int
     */
    public $line;

    /**
     * @var \Throwable
     */
    public $previous;

    /**
     * @var string
     */
    protected $message;
    /**
     * @var int
     */
    protected $code;


    public function __construct(...$errors)
    {
        foreach ( \Gzhegow\Pipeline\Lib\Lib::php_throwable_args(...$errors) as $k => $v ) {
            if (property_exists($this, $k)) {
                $this->{$k} = $v;
            }
        }

        parent::__construct($this->message, $this->code, $this->previous);
    }


    /**
     * @var \Throwable[]
     */
    protected $previousList = [];

    /**
     * @return \Throwable[]
     */
    public function getPreviousList() : array
    {
        return $this->previousList;
    }

    /**
     * @return static
     */
    public function setPreviousList(array $previousList)
    {
        $this->previousList = [];

        foreach ( $previousList as $previous ) {
            $this->addPrevious($previous);
        }

        return $this;
    }

    /**
     * @return static
     */
    public function addPrevious(\Throwable $previous) // : static
    {
        $this->previousList[] = $previous;

        return $this;
    }
}
