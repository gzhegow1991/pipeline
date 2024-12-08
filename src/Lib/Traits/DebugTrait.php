<?php

namespace Gzhegow\Pipeline\Lib\Traits;

trait DebugTrait
{
    public static function debug_var_dump($var, array $options = [], int $level = 0) // : int|float|string
    {
        $withType = $options[ 'with_type' ] ?? true;
        $withId = $options[ 'with_id' ] ?? true;
        $withValue = $options[ 'with_value' ] ?? true;

        $withBraces = $withType || $withId;

        $newline = $options[ 'newline' ] ?? "\n";
        $quotes = $options[ 'quotes' ] ?? [ '"', '"' ];
        $braces = $options[ 'braces' ] ?? [ '{ ', ' }' ];
        $maxArrayLevel = $options[ 'max_array_level' ] ?? null;

        $type = gettype($var);

        $output = null;

        if (null === $output) {
            if (false
                || is_null($var)
            ) {
                $output = [];
                $output[] = "NULL";

                $withBraces = true;
            }
        }

        if (null === $output) {
            if (false
                || is_bool($var)
                || is_numeric($var)
            ) {
                $output = [];
                if ($withType) $output[] = $type;
                if ($withValue) $output[] = $var;
            }
        }

        if (null === $output) {
            if (is_string($var)) {
                $stringLen = strlen($var);

                $output = [];
                if ($withType) $output[] = "{$type}({$stringLen})";
                if ($withValue) $output[] = "{$quotes[0]}{$var}{$quotes[1]}";
            }
        }

        if (null === $output) {
            if (is_array($var)) {
                $arrayCopy = $var;
                $arrayCount = count($var);

                $isDump = $withValue;
                if (null !== $maxArrayLevel) {
                    $isDump = $isDump && ($maxArrayLevel >= $level);
                }

                $dump = null;
                if ($isDump) {
                    foreach ( $arrayCopy as $key => $value ) {
                        // ! recursion
                        $value = static::debug_var_dump(
                            $value,
                            [ 'quotes' => [ '', '' ] ] + $options,
                            $level + 1
                        );

                        $arrayCopy[ $key ] = $value;
                    }

                    $dump = static::debug_var_export($arrayCopy, [ 'addcslashes' => false ]);
                }

                $output = [];
                if ($withType) $output[] = "{$type}({$arrayCount})";
                if (null !== $dump) $output[] = $dump;
            }
        }

        if (null === $output) {
            if (is_object($var)) {
                $objectClass = get_class($var);
                $objectId = spl_object_id($var);
                $objectSubtypeIterable = (is_iterable($var) ? 'iterable' : null);
                $objectSubtypeCountable = (is_a($var, \Countable::class) ? 'countable(' . count($var) . ')' : null);

                $subtype = [];
                if ($objectSubtypeIterable) $subtype[] = $objectSubtypeIterable;
                if ($objectSubtypeCountable) $subtype[] = $objectSubtypeCountable;
                $subtype = implode(' ', $subtype);
                $subtype = ($subtype ? "({$subtype})" : null);

                $output = [];
                $output[] = "{$type}{$subtype}";
                $output[] = $objectClass;
                if ($withId) $output[] = $objectId;

                $withBraces = true;
            }
        }

        if (null === $output) {
            if (is_resource($var)) {
                $resourceType = get_resource_type($var);
                $resourceId = PHP_VERSION_ID > 80000
                    ? get_resource_id($var)
                    : (int) $var;

                $output = [];
                $output[] = "{$type}({$resourceType})";
                if ($withId) $output[] = $resourceId;

                $withBraces = true;
            }
        }

        if (count($output) > 1) {
            $output = implode(" # ", $output);

        } else {
            [ $output ] = $output;
        }

        if ("\n" !== $newline) {
            if (false !== strpos($output, "\n")) {
                $lines = explode("\n", $output);

                foreach ( $lines as $i => $line ) {
                    $line = preg_replace('/\s+/', ' ', $line);
                    $line = trim($line, ' ');

                    $lines[ $i ] = $line;
                }

                $output = implode($newline, $lines);
            }
        }

        $output = $withBraces
            ? "{$braces[0]}{$output}{$braces[1]}"
            : $output;

        return $output;
    }

    public static function debug_var_export($var, array $options = []) : ?string
    {
        $indent = $options[ 'indent' ] ?? "  ";
        $newline = $options[ 'newline' ] ?? "\n";
        $addcslashes = $options[ 'addcslashes' ] ?? true;

        switch ( gettype($var) ) {
            case "NULL":
                $result = "NULL";
                break;

            case "boolean":
                $result = ($var === true) ? "TRUE" : "FALSE";
                break;

            case "string":
                $result = $addcslashes
                    ? addcslashes($var, "\\\$\"\r\n\t\v\f")
                    : $var;

                $result = "\"{$result}\"";

                break;

            case "array":
                $keys = array_keys($var);

                foreach ( $keys as $key ) {
                    if (is_string($key)) {
                        $isList = false;

                        break;
                    }
                }
                $isList = $isList ?? true;

                $isListIndexed = $isList
                    && ($keys === range(0, count($var) - 1));

                $lines = [];
                foreach ( $var as $key => $value ) {
                    $line = $indent;

                    if (! $isListIndexed) {
                        $line .= is_string($key) ? "\"{$key}\"" : $key;
                        $line .= " => ";
                    }

                    // ! recursion
                    $line .= static::debug_var_export($value, $options);

                    $lines[] = $line;
                }

                $result = "["
                    . $newline
                    . implode("," . $newline, $lines) . $newline
                    . $indent . "]";

                break;

            default:
                $result = var_export($var, true);

                break;
        }

        return $result;
    }


    public static function debug_type($value, array $options = []) : string
    {
        $output = static::debug_var_dump($value,
            $options + [
                'with_type'       => true,
                'with_id'         => false,
                'with_dump'       => false,
                'newline'         => null,
                'max_array_level' => 0,
            ]
        );

        return $output;
    }

    public static function debug_type_id($value, array $options = []) : string
    {
        $output = static::debug_var_dump($value,
            $options + [
                'with_type'       => true,
                'with_id'         => false,
                'with_dump'       => true,
                'max_array_level' => 1,
            ]
        );

        return $output;
    }

    public static function debug_value($value, array $options = []) : string
    {
        $output = static::debug_var_dump($value,
            $options + [
                'with_type'       => false,
                'with_id'         => false,
                'with_dump'       => true,
                'newline'         => ' ',
                'max_array_level' => 0,
            ]
        );

        return $output;
    }

    public static function debug_type_value($value, array $options = []) : string
    {
        $output = static::debug_var_dump($value,
            $options + [
                'with_type'       => true,
                'with_id'         => false,
                'with_dump'       => true,
                'newline'         => ' ',
                'max_array_level' => 0,
            ]
        );

        return $output;
    }


    public static function debug_diff(string $a, string $b, string &$result = null) : bool
    {
        $result = null;

        static::os_eol($a, $aLines);
        static::os_eol($b, $bLines);

        $cnt = max(
            count($aLines),
            count($bLines)
        );

        $lines = [];

        $isDiff = false;

        for ( $i = 0; $i < $cnt; $i++ ) {
            $aLine = ($aLines[ $i ] ?? '{ NULL }') ?: '""';
            $bLine = ($bLines[ $i ] ?? '{ NULL }') ?: '""';

            if ($aLine === $bLine) {
                $lines[] = $aLine;

                continue;
            }

            $lines[] = '--- ' . $aLine;
            $lines[] = '+++ ' . $bLine;

            $isDiff = true;
        }

        $result = implode(PHP_EOL, $lines);

        return $isDiff;
    }

    public static function debug_diff_vars($a, $b, string &$result = null) : bool
    {
        ob_start();
        var_dump($a);
        $aString = ob_get_clean();

        ob_start();
        var_dump($b);
        $bString = ob_get_clean();

        $isDiff = static::debug_diff(
            $aString,
            $bString,
            $result
        );

        return $isDiff;
    }
}
