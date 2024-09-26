<?php

namespace Gzhegow\Pipeline;

class Lib
{
    /**
     * > gzhegow, выводит короткую и наглядную форму содержимого переменной в виде строки
     */
    /**
     * > gzhegow, выводит короткую и наглядную форму содержимого переменной в виде строки
     */
    public static function php_dump($value, int $maxlen = null) : string
    {
        if (! is_iterable($value)) {
            if (is_object($value)) {
                if (! method_exists($value, '__debugInfo')) {
                    $_value = '{ object(' . get_class($value) . ' # ' . spl_object_id($value) . ') }';

                } else {
                    ob_start();
                    var_dump($value);
                    $_value = ob_get_clean();

                    $_value = '{ object(' . get_class($value) . ' # ' . spl_object_id($value) . '): ' . $_value . ' }';
                }

            } elseif (is_string($value)) {
                $_value = ''
                    . '{ '
                    . 'string(' . strlen($value) . ')'
                    . ' "'
                    . ($maxlen
                        ? (substr($value, 0, $maxlen) . '...')
                        : $value
                    )
                    . '"'
                    . ' }';

            } else {
                $_value = null
                    ?? (($value === null) ? '{ NULL }' : null)
                    ?? (($value === false) ? '{ FALSE }' : null)
                    ?? (($value === true) ? '{ TRUE }' : null)
                    ?? (is_resource($value) ? ('{ resource(' . gettype($value) . ' # ' . ((int) $value) . ') }') : null)
                    //
                    ?? (is_int($value) ? (var_export($value, 1)) : null) // INF
                    ?? (is_float($value) ? (var_export($value, 1)) : null) // NAN
                    //
                    ?? null;
            }

        } else {
            $_value = [];
            foreach ( $value as $k => $v ) {
                $_value[ $k ] = null
                    ?? (is_array($v) ? '{ array(' . count($v) . ') }' : null)
                    // ! recursion
                    ?? static::php_dump($v, $maxlen);
            }

            ob_start();
            var_dump($_value);
            $_value = ob_get_clean();

            if (is_object($value)) {
                $_value = '{ iterable(' . get_class($value) . ' # ' . spl_object_id($value) . '): ' . $_value . ' }';
            }

            $_value = trim($_value);
            $_value = preg_replace('/\s+/', ' ', $_value);
        }

        if (null === $_value) {
            throw static::php_throwable([ 'Unable to ' . __FUNCTION__ . '()', 'var' => $value ]);
        }

        return $_value;
    }

    /**
     * > gzhegow, перебрасывает исключение на "тихое", если из библиотеки внутреннее постоянно подсвечивается в PHPStorm
     *
     * @return \LogicException|\RuntimeException|null
     */
    public static function php_throwable($error = null, ...$errors) : ?object
    {
        if (is_a($error, \Closure::class)) {
            $error = $error(...$errors);
        }

        if (
            is_a($error, \LogicException::class)
            || is_a($error, \RuntimeException::class)
        ) {
            return $error;
        }

        $throwErrors = static::php_throwable_args($error, ...$errors);

        $message = $throwErrors[ 'message' ] ?? __FUNCTION__;
        $code = $throwErrors[ 'code' ] ?? -1;
        $previous = $throwErrors[ 'previous' ] ?? null;

        return $previous
            ? new \RuntimeException($message, $code, $previous)
            : new \LogicException($message, $code);
    }

    /**
     * > gzhegow, парсит ошибки для передачи результата в конструктор исключения
     *
     * @return array{
     *     messageList: string[],
     *     codeList: int[],
     *     previousList: string[],
     *     messageCodeList: array[],
     *     messageDataList: array[],
     *     message: ?string,
     *     code: ?int,
     *     previous: ?string,
     *     messageCode: ?string,
     *     messageData: ?array,
     *     messageObject: ?object,
     *     __unresolved: array,
     * }
     */
    public static function php_throwable_args($arg = null, ...$args) : array
    {
        array_unshift($args, $arg);

        $len = count($args);

        $messageList = null;
        $codeList = null;
        $previousList = null;
        $messageCodeList = null;
        $messageDataList = null;

        $__unresolved = [];

        for ( $i = 0; $i < $len; $i++ ) {
            $a = $args[ $i ];

            if (is_a($a, \Throwable::class)) {
                $previousList[ $i ] = $a;

                continue;
            }

            if (
                is_array($a)
                || is_a($a, \stdClass::class)
            ) {
                $messageDataList[ $i ] = (array) $a;

                if ('' !== ($messageString = (string) $messageDataList[ $i ][ 0 ])) {
                    $messageList[ $i ] = $messageString;

                    unset($messageDataList[ $i ][ 0 ]);

                    if (! $messageDataList[ $i ]) {
                        unset($messageDataList[ $i ]);
                    }
                }

                continue;
            }

            if (is_int($a)) {
                $codeList[ $i ] = $a;

                continue;
            }

            if ('' !== ($vString = (string) $a)) {
                $messageList[ $i ] = $vString;

                continue;
            }

            $__unresolved[ $i ] = $a;
        }

        for ( $i = 0; $i < $len; $i++ ) {
            if (isset($messageList[ $i ])) {
                if (preg_match('/^[a-z](?!.*\s)/i', $messageList[ $i ])) {
                    $messageCodeList[ $i ] = strtoupper($messageList[ $i ]);
                }
            }
        }

        $result = [];

        $result[ 'messageList' ] = $messageList;
        $result[ 'codeList' ] = $codeList;
        $result[ 'previousList' ] = $previousList;
        $result[ 'messageCodeList' ] = $messageCodeList;
        $result[ 'messageDataList' ] = $messageDataList;

        $messageDataList = $messageDataList ?? [];

        $message = $messageList ? end($messageList) : null;
        $code = $codeList ? end($codeList) : null;
        $previous = $previousList ? end($previousList) : null;
        $messageCode = $messageCodeList ? end($messageCodeList) : null;

        $messageData = $messageDataList
            ? array_replace(...$messageDataList)
            : [];

        $messageObject = (object) ([ $message ] + $messageData);

        $result[ 'message' ] = $message;
        $result[ 'code' ] = $code;
        $result[ 'previous' ] = $previous;
        $result[ 'messageCode' ] = $messageCode;
        $result[ 'messageData' ] = $messageData;

        $result[ 'messageObject' ] = $messageObject;

        $result[ '__unresolved' ] = $__unresolved;

        return $result;
    }


    public static function php_trigger_error_enabled(bool $enable = null) : bool
    {
        static $enabled;

        $enabled = $enable ?? $enabled ?? false;

        return $enabled;
    }

    public static function php_trigger_error($err, int $error_level = null, $result = null) // : mixed
    {
        $error_level = $error_level ?? E_USER_NOTICE;

        $error = is_array($err)
            ? (array) $err
            : [ $err ];

        if (static::php_trigger_error_enabled()) {
            trigger_error($error[ 0 ], $error_level);
        }

        return $result;
    }


    /**
     * @param callable|array|object|class-string     $mixed
     *
     * @param array{0: class-string, 1: string}|null $resultArray
     * @param callable|string|null                   $resultString
     *
     * @return array{0: class-string|object, 1: string}|null
     */
    public static function php_method_exists(
        $mixed, $method = null,
        array &$resultArray = null, string &$resultString = null
    ) : ?array
    {
        $resultArray = null;
        $resultString = null;

        $method = $method ?? '';

        $_class = null;
        $_object = null;
        $_method = null;
        if (is_object($mixed)) {
            $_object = $mixed;

        } elseif (is_array($mixed)) {
            $list = array_values($mixed);

            /** @noinspection PhpWrongStringConcatenationInspection */
            [ $classOrObject, $_method ] = $list + [ '', '' ];

            is_object($classOrObject)
                ? ($_object = $classOrObject)
                : ($_class = $classOrObject);

        } elseif (is_string($mixed)) {
            [ $_class, $_method ] = explode('::', $mixed) + [ '', '' ];

            $_method = $_method ?? $method;
        }

        if (isset($_method) && ! is_string($_method)) {
            return null;
        }

        if ($_object) {
            if ($_object instanceof \Closure) {
                return null;
            }

            if (method_exists($_object, $_method)) {
                $class = get_class($_object);

                $resultArray = [ $class, $_method ];
                $resultString = $class . '::' . $_method;

                return [ $_object, $_method ];
            }

        } elseif ($_class) {
            if (method_exists($_class, $_method)) {
                $resultArray = [ $_class, $_method ];
                $resultString = $_class . '::' . $_method;

                return [ $_class, $_method ];
            }
        }

        return null;
    }


    /**
     * > gzhegow, всегда возвращает публичные свойства объекта
     */
    public static function php_get_object_vars_public(object $object) : array
    {
        $vars = get_object_vars($object);

        return $vars;
    }

    /**
     * > gzhegow, всегда возвращает все (публичные и защищенные) свойства объекта
     */
    public static function php_get_object_vars(object $object) : array
    {
        $vars = (function () {
            return get_object_vars($this);
        })->call($object);

        return $vars;
    }


    public static function parse_string($value) : ?string
    {
        if (is_string($value)) {
            return $value;
        }

        if (
            (null === $value)
            || is_array($value)
            || is_resource($value)
        ) {
            return null;
        }

        if (is_object($value)) {
            if (method_exists($value, '__toString')) {
                $_value = (string) $value;

                return $_value;
            }

            return null;
        }

        $_value = $value;
        $status = @settype($_value, 'string');

        if ($status) {
            return $_value;
        }

        return null;
    }

    public static function parse_astring($value) : ?string
    {
        if (null === ($_value = static::parse_string($value))) {
            return null;
        }

        if ('' === $_value) {
            return null;
        }

        return $_value;
    }


    /**
     * @param callable ...$fnExistsList
     */
    public static function filter_struct($value, bool $useRegex = null, ...$fnExistsList) : ?string
    {
        $useRegex = $useRegex ?? false;
        $fnExistsList = $fnExistsList ?: [ 'class_exists' ];

        if (is_object($value)) {
            return ltrim(get_class($value), '\\');
        }

        if (null === ($_value = static::parse_astring($value))) {
            return null;
        }

        $_value = ltrim($_value, '\\');

        foreach ( $fnExistsList as $fn ) {
            if ($fn($_value)) {
                return $_value;
            }
        }

        if ($useRegex) {
            if (! preg_match(
                '/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*(\\[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)*$/',
                $_value
            )) {
                return null;
            }
        }

        return $_value;
    }

    public static function filter_class($value, bool $useRegex = null) : ?string
    {
        $_value = static::filter_struct($value, $useRegex, 'class_exists');

        if (null === $_value) {
            return null;
        }

        return $_value;
    }


    /**
     * > gzhegow, разбивает массив на два, где в первом все цифровые ключи (список), во втором - все буквенные (словарь)
     *
     * @return array{
     *     0: array<int, mixed>,
     *     1: array<string, mixed>
     * }
     */
    public static function array_kwargs(array $src = null) : array
    {
        if (! isset($src)) return [];

        $list = [];
        $dict = [];

        foreach ( $src as $idx => $val ) {
            is_int($idx)
                ? ($list[ $idx ] = $val)
                : ($dict[ $idx ] = $val);
        }

        return [ $list, $dict ];
    }
}
