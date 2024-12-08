<?php

namespace Gzhegow\Pipeline\Lib\Traits;

trait AssertTrait
{
    /**
     * @param callable                                                          $fn
     * @param object{ microtime?: float, output?: string, result?: mixed }|null $except
     * @param array|null                                                        $error
     * @param resource|null                                                     $stdout
     */
    public static function assert_call(
        array $trace,
        $fn, object $except = null, array &$error = null,
        $stdout = null
    ) : bool
    {
        $error = null;

        $microtime = microtime(true);

        ob_start();
        $result = $fn();
        $output = ob_get_clean();

        if (property_exists($except, 'result')) {
            if ($except->result !== $result) {
                $microtime = round(microtime(true) - $microtime, 6);

                static::debug_diff_vars($result, $except->result, $diff);

                $error = [
                    'Test result check failed',
                    [
                        'result'    => $result,
                        'expect'    => $except->result,
                        'diff'      => $diff,
                        'microtime' => $microtime,
                        'file'      => $trace[ 0 ][ 'file' ],
                        'line'      => $trace[ 0 ][ 'line' ],
                    ],
                ];

                if (null !== $stdout) {
                    fwrite($stdout, '------' . PHP_EOL);
                    fwrite($stdout, '[ ERROR ] Test result check failed. ' . $microtime . 's' . PHP_EOL);
                    fwrite($stdout, $trace[ 0 ][ 'file' ] . ' / ' . $trace[ 0 ][ 'line' ] . PHP_EOL);
                    fwrite($stdout, $diff . PHP_EOL);
                    fwrite($stdout, '------' . PHP_EOL);
                }

                return false;
            }
        }

        if (property_exists($except, 'output')) {
            $isDiff = static::debug_diff($output, $except->output, $diff);

            if ($isDiff) {
                $microtime = round(microtime(true) - $microtime, 6);

                $error = [
                    'Test result check failed',
                    [
                        'output'    => $output,
                        'expect'    => $except->output,
                        'diff'      => $diff,
                        'microtime' => $microtime,
                        'file'      => $trace[ 0 ][ 'file' ],
                        'line'      => $trace[ 0 ][ 'line' ],
                    ],
                ];

                if (null !== $stdout) {
                    fwrite($stdout, '------' . PHP_EOL);
                    fwrite($stdout, '[ ERROR ] Test output check failed. ' . $microtime . 's' . PHP_EOL);
                    fwrite($stdout, $trace[ 0 ][ 'file' ] . ' / ' . $trace[ 0 ][ 'line' ] . PHP_EOL);
                    fwrite($stdout, $diff . PHP_EOL);
                    fwrite($stdout, '------' . PHP_EOL);
                }

                return false;
            }
        }

        $microtime = round(microtime(true) - $microtime, 6);

        if (property_exists($except, 'microtime')) {
            if ($except->microtime < $microtime) {
                $diff = $microtime - $except->microtime;

                $error = [
                    'Test result check failed',
                    [
                        'microtime' => $microtime,
                        'expect'    => $except->microtime,
                        'diff'      => $diff,
                        'file'      => $trace[ 0 ][ 'file' ],
                        'line'      => $trace[ 0 ][ 'line' ],
                    ],
                ];

                if (null !== $stdout) {
                    fwrite($stdout, '------' . PHP_EOL);
                    fwrite($stdout, '[ ERROR ] Test microtime check failed. ' . $microtime . 's' . PHP_EOL);
                    fwrite($stdout, $trace[ 0 ][ 'file' ] . ' / ' . $trace[ 0 ][ 'line' ] . PHP_EOL);
                    fwrite($stdout, $diff . PHP_EOL);
                    fwrite($stdout, '------' . PHP_EOL);
                }

                return false;
            }
        }

        if (null !== $stdout) {
            fwrite($stdout, '[ OK ] Test success. ' . $microtime . 's' . PHP_EOL);
        }

        return true;
    }
}
