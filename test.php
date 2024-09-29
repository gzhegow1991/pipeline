<?php

require_once __DIR__ . '/vendor/autoload.php';


// > настраиваем PHP
ini_set('memory_limit', '32M');

// > настраиваем обработку ошибок
error_reporting(E_ALL);
set_error_handler(function ($errno, $errstr, $errfile, $errline) {
    if (error_reporting() & $errno) {
        throw new \ErrorException($errstr, -1, $errno, $errfile, $errline);
    }
});
set_exception_handler(function ($e) {
    $current = $e;
    do {
        $file = $current->getFile() ?? '{file}';
        $line = $current->getLine() ?? '{line}';

        echo \Gzhegow\Pipeline\Lib::php_dump($current) . PHP_EOL;
        echo $current->getMessage() . PHP_EOL;
        echo "{$file} : { $line }" . PHP_EOL;
        echo PHP_EOL;
    } while ( $current = $current->getPrevious() );

    die();
});

// > добавляем несколько функция для тестирования
function _dump($value, ...$values)
{
    // // requires `symfony/var-dumper`
    // dump($value, ...$values);

    array_unshift($values, $value);

    $strings = array_map(
        function ($v) {
            return \Gzhegow\Pipeline\Lib::php_var_export($v, true);
        },
        $values
    );

    echo implode(" ", $strings) . PHP_EOL;

    return $value;
}

function _test(\Closure $fn, $expected = null)
{
    ob_start();
    $fn();
    $output = ob_get_clean();

    $output = trim($output);
    $output = str_replace("\r\n", "\n", $output);
    $output = preg_replace('/' . preg_quote('\\', '/') . '+/', '\\', $output);

    if ($expected !== $output) {
        throw new \RuntimeException('Test failed.');
    }

    echo 'Test OK.' . PHP_EOL;
}


// >>> ЗАПУСКАЕМ!

// > сначала всегда факторка
$factory = new \Gzhegow\Pipeline\PipelineFactory();

// >>> TEST 1
_test(function () use ($factory) {
    // > цепочка может состоять из одного или нескольких действий
    // > создаем конвеер
    $pipeline = $factory->newPipeline();

    // > добавляем действия в конвеер
    $pipeline
        ->action(\Gzhegow\Pipeline\Handler\Demo\Action\Demo1stAction::class)
        ->action(\Gzhegow\Pipeline\Handler\Demo\Action\Demo1stAction::class)
    ;

    // > запускаем конвеер
    $myInput = 'any data 1';
    $myContext = (object) [];
    $result = $pipeline->run($myInput, $myContext);
    _dump('[ RESULT ]', $result);
    _dump('');
}, <<<HEREDOC
string(60) "Gzhegow\Pipeline\Handler\Demo\Action\Demo1stAction::__invoke"
string(60) "Gzhegow\Pipeline\Handler\Demo\Action\Demo1stAction::__invoke"
"[ RESULT ]" "Gzhegow\\Pipeline\\Handler\\Demo\\Action\\Demo1stAction::__invoke result."
""
HEREDOC
);

// >>> TEST 2
_test(function () use ($factory) {
    // > действия могут передавать результат выполнения из одного в другое
    // > создаем конвеер
    $pipeline = $factory->newPipeline();

    // > добавляем действия в конвеер
    $pipeline
        ->action(\Gzhegow\Pipeline\Handler\Demo\Action\DemoPassAction::class)
        ->action(\Gzhegow\Pipeline\Handler\Demo\Action\DemoPassAction::class)
    ;

    // > запускаем конвеер
    $myInput = 'any data 2';
    $myContext = (object) [];
    $myResult = 'any result 2';
    $result = $pipeline->run($myInput, $myContext, $myResult);
    _dump('[ RESULT ]', $result);
    _dump('');
}, <<<HEREDOC
string(61) "Gzhegow\Pipeline\Handler\Demo\Action\DemoPassAction::__invoke"
string(61) "Gzhegow\Pipeline\Handler\Demo\Action\DemoPassAction::__invoke"
"[ RESULT ]" "any result 2"
""
HEREDOC
);

// >>> TEST 3
_test(function () use ($factory) {
    // > цепочка может состоять даже из цепочек
    // > создаем дочерний конвеер
    $pipelineChild = $factory->newPipeline();

    // > добавляем действия в конвеер
    $pipelineChild
        ->action(\Gzhegow\Pipeline\Handler\Demo\Action\DemoPassAction::class)
        ->action(\Gzhegow\Pipeline\Handler\Demo\Action\DemoPassAction::class)
    ;

    // > создаем родительский конвеер
    $pipeline = $factory->newPipeline();

    // > добавляем действия (в том числе дочерние конвееры) в родительский конвеер
    $pipeline
        ->action($pipelineChild) // этот конвеер просто передаст $result дальше
        ->action(\Gzhegow\Pipeline\Handler\Demo\Action\Demo2ndAction::class) // на этом этапе результат будет заменен
        ->action($pipelineChild) // этот конвеер передаст измененный $result дальше
    ;

    // > запускаем конвеер
    $myInput = 'any data 3';
    $myContext = (object) [];
    $myResult = 'any result 3';
    $result = $pipeline->run($myInput, $myContext, $myResult);
    _dump('[ RESULT ]', $result);
    _dump('');
}, <<<HEREDOC
string(61) "Gzhegow\Pipeline\Handler\Demo\Action\DemoPassAction::__invoke"
string(61) "Gzhegow\Pipeline\Handler\Demo\Action\DemoPassAction::__invoke"
string(60) "Gzhegow\Pipeline\Handler\Demo\Action\Demo2ndAction::__invoke"
string(61) "Gzhegow\Pipeline\Handler\Demo\Action\DemoPassAction::__invoke"
string(61) "Gzhegow\Pipeline\Handler\Demo\Action\DemoPassAction::__invoke"
"[ RESULT ]" "Gzhegow\Pipeline\Handler\Demo\Action\Demo2ndAction::__invoke result."
""
HEREDOC
);

// >>> TEST 4
_test(function () use ($factory) {
    // > к любой цепочке можно подключить middleware (они выполняются первыми и оборачивают всю цепь)
    // > создаем конвеер
    $pipeline = $factory->newPipeline();

    // > добавляем действия в конвеер
    $pipeline
        ->middleware(\Gzhegow\Pipeline\Handler\Demo\Middleware\Demo1stMiddleware::class)
        ->middleware(\Gzhegow\Pipeline\Handler\Demo\Middleware\Demo2ndMiddleware::class)
        ->action(\Gzhegow\Pipeline\Handler\Demo\Action\Demo1stAction::class)
    ;

    // > запускаем конвеер
    $myInput = 'any data 4';
    $myContext = (object) [];
    $result = $pipeline->run($myInput, $myContext);
    _dump('[ RESULT ]', $result);
    _dump('');
}, <<<HEREDOC
@before :: Gzhegow\Pipeline\Handler\Demo\Middleware\Demo1stMiddleware::__invoke
@before :: Gzhegow\Pipeline\Handler\Demo\Middleware\Demo2ndMiddleware::__invoke
string(60) "Gzhegow\Pipeline\Handler\Demo\Action\Demo1stAction::__invoke"
@after :: Gzhegow\Pipeline\Handler\Demo\Middleware\Demo2ndMiddleware::__invoke
@after :: Gzhegow\Pipeline\Handler\Demo\Middleware\Demo1stMiddleware::__invoke
"[ RESULT ]" "Gzhegow\Pipeline\Handler\Demo\Action\Demo1stAction::__invoke result."
""
HEREDOC
);

// >>> TEST 5
_test(function () use ($factory) {
    // > middleware может предотвратить выполнение цепочки (то есть уже написанный код можно отменить фильтром, не редактируя его)
    // > создаем конвеер
    $pipeline = $factory->newPipeline();

    // > добавляем действия в конвеер
    $pipeline
        ->middleware(\Gzhegow\Pipeline\Handler\Demo\Middleware\DemoOmitMiddleware::class)
        ->action(\Gzhegow\Pipeline\Handler\Demo\Action\Demo1stAction::class)
        ->action(\Gzhegow\Pipeline\Handler\Demo\Action\Demo1stAction::class)
    ;

    // > запускаем конвеер
    $myInput = 'any data 5';
    $myContext = (object) [];
    $result = $pipeline->run($myInput, $myContext);
    _dump('[ RESULT ]', $result);
    _dump('');
}, <<<HEREDOC
@before :: Gzhegow\Pipeline\Handler\Demo\Middleware\DemoOmitMiddleware::__invoke
@after :: Gzhegow\Pipeline\Handler\Demo\Middleware\DemoOmitMiddleware::__invoke
"[ RESULT ]" "Gzhegow\Pipeline\Handler\Demo\Middleware\DemoOmitMiddleware::__invoke result."
""
HEREDOC
);

// >>> TEST 6
_test(function () use ($factory) {
    // > выброшенную ошибку можно превратить в результат используя fallback
    // > создаем конвеер
    $pipeline = $factory->newPipeline();

    // > добавляем действия в конвеер
    $pipeline
        ->action(\Gzhegow\Pipeline\Handler\Demo\Action\DemoLogicExceptionAction::class)
        ->fallback(\Gzhegow\Pipeline\Handler\Demo\Fallback\DemoLogicExceptionFallback::class)
    ;

    // > запускаем конвеер
    $myInput = 'any data 6';
    $myContext = (object) [];
    $result = $pipeline->run($myInput, $myContext);
    _dump('[ RESULT ]', $result);
    _dump('');
    // string(71) "Gzhegow\Pipeline\Handler\Demo\Action\DemoLogicExceptionAction::__invoke"
    // string(75) "Gzhegow\Pipeline\Handler\Demo\Fallback\DemoLogicExceptionFallback::__invoke"
    // { string(10) "[ RESULT ]" } { string(83) "Gzhegow\Pipeline\Handler\Demo\Fallback\DemoLogicExceptionFallback::__invoke result." }
    // { string(0) "" }
}, <<<HEREDOC
string(71) "Gzhegow\Pipeline\Handler\Demo\Action\DemoLogicExceptionAction::__invoke"
string(75) "Gzhegow\Pipeline\Handler\Demo\Fallback\DemoLogicExceptionFallback::__invoke"
"[ RESULT ]" "Gzhegow\Pipeline\Handler\Demo\Fallback\DemoLogicExceptionFallback::__invoke result."
""
HEREDOC
);

// >>> TEST 7
_test(function () use ($factory) {
    // > если fallback возвращает NULL, то система попробует поймать исключение следующим fallback
    // > создаем конвеер
    $pipeline = $factory->newPipeline();

    // > добавляем действия в конвеер
    $pipeline
        ->action(\Gzhegow\Pipeline\Handler\Demo\Action\DemoExceptionAction::class)
        ->fallback(\Gzhegow\Pipeline\Handler\Demo\Fallback\DemoStepFallback::class)
        ->fallback(\Gzhegow\Pipeline\Handler\Demo\Fallback\DemoFallback::class)
    ;

    // > запускаем конвеер
    $myInput = 'any data 7';
    $myContext = (object) [];
    $result = $pipeline->run($myInput, $myContext);
    _dump('[ RESULT ]', $result);
    _dump('');
}, <<<HEREDOC
string(66) "Gzhegow\Pipeline\Handler\Demo\Action\DemoExceptionAction::__invoke"
string(65) "Gzhegow\Pipeline\Handler\Demo\Fallback\DemoStepFallback::__invoke"
string(61) "Gzhegow\Pipeline\Handler\Demo\Fallback\DemoFallback::__invoke"
"[ RESULT ]" "Gzhegow\Pipeline\Handler\Demo\Fallback\DemoFallback::__invoke result."
""
HEREDOC
);

// >>> TEST 8
_test(function () use ($factory) {
    // > если ни один из fallback не обработает ошибку, ошибка будет выброшена наружу
    // > создаем конвеер
    $pipeline = $factory->newPipeline();

    // > добавляем действия в конвеер
    $pipeline
        ->action(\Gzhegow\Pipeline\Handler\Demo\Action\DemoExceptionAction::class)
        ->fallback(\Gzhegow\Pipeline\Handler\Demo\Fallback\DemoLogicExceptionFallback::class)
    ;

    // > запускаем конвеер
    $myInput = 'any data 8';
    $myContext = (object) [];
    $result = null;
    try {
        $result = $pipeline->run($myInput, $myContext);
    }
    catch ( \Throwable $e ) {
        _dump('[ CATCH ]', get_class($e), $e->getMessage());
    }
    _dump('[ RESULT ]', $result);
    _dump('');
}, <<<HEREDOC
string(66) "Gzhegow\Pipeline\Handler\Demo\Action\DemoExceptionAction::__invoke"
"[ CATCH ]" "Gzhegow\Pipeline\Exception\Exception" "Hello, World!"
"[ RESULT ]" NULL
""
HEREDOC
);
