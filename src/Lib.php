<?php

namespace Gzhegow\Pipeline;

class Lib
{
    public static function php_dump($value, ...$values)
    {
        array_unshift($values, $value);

        $strings = array_map(
            static function ($v) {
                return \Gzhegow\Pipeline\Lib::php_var_export($v, true);
            },
            $values
        );

        echo implode(" ", $strings) . PHP_EOL;

        return $value;
    }


    public static function php_throwable_args(...$args) : array
    {
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


    public static function php_var_dump($value, int $maxlen = null, int $dumpMaxlen = null) : string
    {
        if ($dumpMaxlen < 1) $dumpMaxlen = null;
        if ($maxlen < 1) $maxlen = null;

        $dumpMaxlen = $dumpMaxlen ?? $maxlen;

        $var = null;
        $dump = null;
        if (is_iterable($value)) {
            if (is_object($value)) {
                $var = 'iterable(' . get_class($value) . ' # ' . spl_object_id($value) . ')';

            } else {
                $var = 'array(' . count($value) . ')';
            }

        } else {
            if (is_object($value)) {
                $var = 'object(' . get_class($value) . ' # ' . spl_object_id($value) . ')';

                if (method_exists($value, '__debugInfo')) {
                    $dump = static::php_var_export(
                        $value, true,
                        [ "indent" => " ", "newline" => " " ]
                    );

                    $dump = (null !== $dumpMaxlen)
                        ? (substr($dump, 0, $dumpMaxlen) . '...')
                        : $dump;
                }

            } elseif (is_string($value)) {
                $var = 'string(' . strlen($value) . ')';

                $dump = (null !== $dumpMaxlen)
                    ? (substr($value, 0, $dumpMaxlen) . '...')
                    : $value;
                $dump = "\"{$dump}\"";

            } elseif (is_resource($value)) {
                $var = '{ resource(' . gettype($value) . ' # ' . ((int) $value) . ') }';

            } else {
                $var = null
                    ?? (($value === null) ? '{ NULL }' : null)
                    ?? (($value === false) ? '{ FALSE }' : null)
                    ?? (($value === true) ? '{ TRUE }' : null)
                    //
                    ?? (is_int($value) ? (var_export($value, 1)) : null) // INF
                    ?? (is_float($value) ? (var_export($value, 1)) : null) // NAN
                    //
                    ?? null;
            }
        }

        $_value = (null !== $dump)
            ? "{$var} : {$dump}"
            : "{$var}";

        $_value = trim($_value);
        $_value = preg_replace('/\s+/', ' ', $_value);

        $_value = (null !== $maxlen)
            ? (substr($_value, 0, $maxlen) . '...')
            : $_value;

        $_value = "{ {$_value} }";

        return $_value;
    }

    public static function php_var_export($var, bool $return = false, array $options = [])
    {
        $indent = $options[ 'indent' ] ?? "  ";
        $newline = $options[ 'newline' ] ?? "\n";

        switch ( gettype($var) ) {
            case "NULL":
                $result = "NULL";
                break;

            case "boolean":
                $result = ($var === true) ? "TRUE" : "FALSE";
                break;

            case "string":
                $result = '"' . addcslashes($var, "\\\$\"\r\n\t\v\f") . '"';
                break;

            case "array":
                $isList = true;
                $keys = array_keys($var);
                foreach ( $keys as $key ) {
                    if (is_string($key)) {
                        $isList = false;

                        break;
                    }
                }

                $isListIndexed = true
                    && $isList
                    && ($keys === range(0, count($var) - 1));

                $r = [];
                foreach ( $var as $key => $value ) {
                    $line = $indent;

                    if (! $isListIndexed) {
                        $line .= var_export($key, true);
                        $line .= " => ";
                    }

                    // ! recursion
                    $line .= static::php_var_export($value, true, $options);

                    $r[] = $line;
                }

                $result = ""
                    . "[" . $newline
                    . implode("," . $newline, $r) . $newline
                    . "{$indent}]";

                break;

            default:
                $result = var_export($var, true);
                break;
        }

        if ($return) {
            return $result;
        }

        echo $result;

        return null;
    }


    /**
     * @return object{ errors: array }
     */
    public static function php_errors() : object
    {
        static $stack;

        $stack = $stack
            ?? new class {
                public $errors = [];
            };

        return $stack;
    }

    /**
     * @return object{ list: array }
     */
    public static function php_errors_current() : object
    {
        $stack = static::php_errors();

        $errors = end($stack->errors);

        return $errors;
    }

    /**
     * @return object{ list: array }
     */
    public static function php_errors_new() : object
    {
        $errors = new class {
            public $list = [];
        };

        return $errors;
    }

    /**
     * @return object{ list: array }
     */
    public static function php_errors_start(object &$errors = null) : object
    {
        $stack = static::php_errors();

        $errors = static::php_errors_new();
        $stack->errors[] = $errors;

        return $errors;
    }

    public static function php_errors_end(?object $until) : array
    {
        $stack = static::php_errors();

        $errors = static::php_errors_new();

        while ( count($stack->errors) ) {
            $current = array_pop($stack->errors);

            foreach ( $current->list as $error ) {
                $errors->list[] = $error;
            }

            if ($current === $until) {
                break;
            }
        }

        return $errors->list;
    }

    public static function php_error($error, $result = null) // : mixed
    {
        $current = static::php_errors_current();

        $current->list[] = $error;

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
