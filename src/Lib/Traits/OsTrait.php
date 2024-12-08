<?php

namespace Gzhegow\Pipeline\Lib\Traits;

trait OsTrait
{
    /**
     * @param string[]|null $lines
     */
    public static function os_eol(string $str, array &$lines = null) : string
    {
        $lines = null;

        if (false === strpos($str, "\n")) {
            $output = $str;

        } else {
            $linesArray = explode("\n", $str);

            foreach ( $linesArray as $i => $line ) {
                $linesArray[ $i ] = rtrim($line, PHP_EOL);
            }

            $lines = $linesArray;

            $output = implode("\n", $linesArray);
        }

        return $output;
    }
}
