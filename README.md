# Pipeline

Конвеер, реализация паттерна "цепочка обязанностей"

Требуется в:

- в роутерах (возможность цеплять Middleware и Fallback)
- в логике (возможность писать RPC код без дублирования)
- в асинхронном выполнении (возможность перебрасывать шаги из нескольких конвееров на несколько ядер процессора, параллельность)

## Установка

```
composer require gzhegow/pipeline;
```

## Пример

```php
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
        echo PHP_EOL;

        echo \Gzhegow\Pipeline\Lib::php_var_dump($current) . PHP_EOL;
        echo $current->getMessage() . PHP_EOL;

        foreach ( $e->getTrace() as $traceItem ) {
            echo "{$traceItem['file']} : {$traceItem['line']}" . PHP_EOL;
        }

        echo PHP_EOL;
    } while ( $current = $current->getPrevious() );

    die();
});


// > добавляем несколько функция для тестирования
function _dump($value, ...$values)
{
    echo \Gzhegow\Pipeline\Lib::php_dump($value, ...$values) . PHP_EOL;
}

function _error($message, $code = -1, $previous = null, string $file = null, int $line = null)
{
    $e = new \Gzhegow\Pipeline\Exception\RuntimeException($message, $code, $previous);

    if (($file !== null) && ($line !== null)) {
        (function ($file, $line) {
            $this->file = $file;
            $this->line = $line;
        })->call($e, $file, $line);
    }

    return $e;
}

function _assert_call(\Closure $fn, array $exResult = null, string $exOutput = null)
{
    $exResult = $exResult ?? [];

    $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1);

    ob_start();
    $result = $fn();
    $output = ob_get_clean();

    $output = trim($output);
    $output = str_replace("\r\n", "\n", $output);
    $output = preg_replace('/' . preg_quote('\\', '/') . '+/', '\\', $output);

    if (count($exResult)) {
        [ $_exResult ] = $exResult;

        if ($_exResult !== $result) {
            var_dump($result);

            throw _error(
                'Test result check failed', -1, null,
                $trace[ 0 ][ 'file' ], $trace[ 0 ][ 'line' ]
            );
        }
    }

    if (null !== $exOutput) {
        if ($exOutput !== $output) {
            print_r($output);

            throw _error(
                'Test output check failed', -1, null,
                $trace[ 0 ][ 'file' ], $trace[ 0 ][ 'line' ]
            );
        }
    }

    echo 'Test OK.' . PHP_EOL;

    return true;
}


// >>> ЗАПУСКАЕМ!

// > сначала всегда фабрика
$factory = new \Gzhegow\Pipeline\PipelineFactory();


// >>> TEST 1
// > цепочка может состоять из одного или нескольких действий
$fn = function () use ($factory) {
    // > создаем конвеер
    $pipeline = $factory->newPipeline();

    // > добавляем действия в конвеер
    $pipeline
        ->action(\Gzhegow\Pipeline\Handler\Demo\Action\Demo1stAction::class)
        ->action(\Gzhegow\Pipeline\Handler\Demo\Action\Demo2ndAction::class)
    ;

    // > конвеер можно будет запустить заново, если сбросить его состояние перед вызовом ->run(), при первом запуске
    $pipeline->reset();

    // > устанавливаем стартовый $input, который будет меняться при прохождении по цепочке на результат прошлого действия
    $myInput = 'any data 1';

    // > устанавливаем произвольные данные в поле $context, они не будут затронуты механизмом Pipeline
    // > разумно передать сюда объект, чтобы он был общим для всех шагов и складывать сюда отчеты или промежуточные данные
    $myContext = (object) [];

    // > также можно установить произвольные данные вручную используя внутреннее поле $state
    // > в этом поле должны храниться данные, нужные для работы самого Pipeline, а не решаемой задачи
    $state = $pipeline->getState();
    $state->myProperty = 123;

    // > запускаем конвеер
    $result = $pipeline->run($myInput, $myContext);
    _dump('[ RESULT ]', $result);
    _dump('');
};
_assert_call($fn, [], <<<HEREDOC
Gzhegow\Pipeline\Handler\Demo\Action\Demo1stAction::__invoke
Gzhegow\Pipeline\Handler\Demo\Action\Demo2ndAction::__invoke
"[ RESULT ]" | "Gzhegow\\Pipeline\\Handler\\Demo\\Action\\Demo2ndAction::__invoke result."
""
HEREDOC
);

// >>> TEST 2
// > действия могут передавать результат выполнения из одного в другое
$fn = function () use ($factory) {
    // > создаем конвеер
    $pipeline = $factory->newPipeline();

    // > добавляем действия в конвеер
    $pipeline
        ->action(\Gzhegow\Pipeline\Handler\Demo\Action\DemoPassInputToResultAction::class)
        ->action(\Gzhegow\Pipeline\Handler\Demo\Action\DemoPassInputToResultAction::class)
    ;

    // > запускаем конвеер
    $myInput = 'any data 2';
    $myContext = null;
    $result = $pipeline->run($myInput, $myContext);
    _dump('[ RESULT ]', $result);
    _dump('');
};
_assert_call($fn, [], <<<HEREDOC
Gzhegow\Pipeline\Handler\Demo\Action\DemoPassInputToResultAction::__invoke
Gzhegow\Pipeline\Handler\Demo\Action\DemoPassInputToResultAction::__invoke
"[ RESULT ]" | "any data 2"
""
HEREDOC
);

// >>> TEST 3
// > цепочка может состоять даже из цепочек
$fn = function () use ($factory) {
    // > создаем дочерний конвеер
    $pipelineChild = $factory->newPipeline();

    // > добавляем действия в конвеер
    $pipelineChild
        ->action(\Gzhegow\Pipeline\Handler\Demo\Action\DemoPassInputToResultAction::class)
        ->action(\Gzhegow\Pipeline\Handler\Demo\Action\DemoPassInputToResultAction::class)
    ;

    // > создаем родительский конвеер
    $pipeline = $factory->newPipeline();

    // > добавляем действия (в том числе дочерние конвееры) в родительский конвеер
    $pipeline
        ->pipeline($pipelineChild)                                             // этот конвеер просто передаст $result дальше
        ->action(\Gzhegow\Pipeline\Handler\Demo\Action\Demo2ndAction::class)   // на этом этапе результат будет заменен
        ->pipeline($pipelineChild) // этот конвеер передаст измененный $result дальше
    ;

    // > запускаем конвеер
    $myInput = null;
    $myContext = null;
    $result = $pipeline->run($myInput, $myContext);
    _dump('[ RESULT ]', $result);
    _dump('');
};
_assert_call($fn, [], <<<HEREDOC
Gzhegow\Pipeline\Handler\Demo\Action\DemoPassInputToResultAction::__invoke
Gzhegow\Pipeline\Handler\Demo\Action\DemoPassInputToResultAction::__invoke
Gzhegow\Pipeline\Handler\Demo\Action\Demo2ndAction::__invoke
Gzhegow\Pipeline\Handler\Demo\Action\DemoPassInputToResultAction::__invoke
Gzhegow\Pipeline\Handler\Demo\Action\DemoPassInputToResultAction::__invoke
"[ RESULT ]" | "Gzhegow\Pipeline\Handler\Demo\Action\Demo2ndAction::__invoke result."
""
HEREDOC
);

// >>> TEST 4
// > выброшенную ошибку можно превратить в результат используя fallback
$fn = function () use ($factory) {
    // > создаем конвеер
    $pipeline = $factory->newPipeline();

    // > добавляем действия в конвеер
    $pipeline
        ->action(\Gzhegow\Pipeline\Handler\Demo\Action\DemoLogicExceptionAction::class)
        ->fallback(\Gzhegow\Pipeline\Handler\Demo\Fallback\DemoLogicExceptionFallback::class)
    ;

    // > запускаем конвеер
    $myInput = null;
    $myContext = null;
    $result = $pipeline->run($myInput, $myContext);
    _dump('[ RESULT ]', $result);
    _dump('');
};
_assert_call($fn, [], <<<HEREDOC
Gzhegow\Pipeline\Handler\Demo\Action\DemoLogicExceptionAction::__invoke
Gzhegow\Pipeline\Handler\Demo\Fallback\DemoLogicExceptionFallback::__invoke
"[ RESULT ]" | "Gzhegow\Pipeline\Handler\Demo\Fallback\DemoLogicExceptionFallback::__invoke result."
""
HEREDOC
);

// >>> TEST 5
// > цепочка может начинаться с исключения, которое нужно обработать
$fn = function () use ($factory) {
    // > создаем конвеер
    $pipeline = $factory->newPipeline();

    // > добавляем действия в конвеер
    $pipeline
        ->throwable(new \LogicException('Hello, World!'))
        ->fallback(\Gzhegow\Pipeline\Handler\Demo\Fallback\DemoLogicExceptionFallback::class)
    ;

    // > запускаем конвеер
    $myInput = null;
    $myContext = null;
    $result = $pipeline->run($myInput, $myContext);
    _dump('[ RESULT ]', $result);
    _dump('');
};
_assert_call($fn, [], <<<HEREDOC
Gzhegow\Pipeline\Handler\Demo\Fallback\DemoLogicExceptionFallback::__invoke
"[ RESULT ]" | "Gzhegow\Pipeline\Handler\Demo\Fallback\DemoLogicExceptionFallback::__invoke result."
""
HEREDOC
);

// >>> TEST 6
// > если fallback возвращает NULL, то система попробует поймать исключение следующим fallback
$fn = function () use ($factory) {
    // > создаем конвеер
    $pipeline = $factory->newPipeline();

    // > добавляем действия в конвеер
    $pipeline
        ->action(\Gzhegow\Pipeline\Handler\Demo\Action\DemoExceptionAction::class)
        ->fallback(\Gzhegow\Pipeline\Handler\Demo\Fallback\DemoSkipFallback::class)
        ->fallback(\Gzhegow\Pipeline\Handler\Demo\Fallback\DemoThrowableFallback::class)
    ;

    // > запускаем конвеер
    $myInput = null;
    $myContext = null;
    $result = $pipeline->run($myInput, $myContext);
    _dump('[ RESULT ]', $result);
    _dump('');
};
_assert_call($fn, [], <<<HEREDOC
Gzhegow\Pipeline\Handler\Demo\Action\DemoExceptionAction::__invoke
Gzhegow\Pipeline\Handler\Demo\Fallback\DemoSkipFallback::__invoke
Gzhegow\Pipeline\Handler\Demo\Fallback\DemoThrowableFallback::__invoke
"[ RESULT ]" | "Gzhegow\Pipeline\Handler\Demo\Fallback\DemoThrowableFallback::__invoke result."
""
HEREDOC
);

// >>> TEST 7
// > если ни один из fallback не обработает ошибку, ошибка будет выброшена наружу
$fn = function () use ($factory) {
    // > создаем конвеер
    $pipeline = $factory->newPipeline();

    // > добавляем действия в конвеер
    $pipeline
        ->action(\Gzhegow\Pipeline\Handler\Demo\Action\DemoExceptionAction::class)
        ->fallback(\Gzhegow\Pipeline\Handler\Demo\Fallback\DemoLogicExceptionFallback::class)
    ;

    // > запускаем конвеер
    $myInput = null;
    $myContext = null;
    $result = null;
    try {
        $result = $pipeline->run($myInput, $myContext);
    }
    catch ( \Gzhegow\Pipeline\Exception\Exception\PipelineException $e ) {
        _dump('[ CATCH ]', get_class($e), $e->getMessage());

        foreach ( $e->getPreviousList() as $ee ) {
            _dump('[ CATCH ]', get_class($ee), $ee->getMessage());
        }
    }
    _dump('[ RESULT ]', $result);
    _dump('');
};
_assert_call($fn, [], <<<HEREDOC
Gzhegow\Pipeline\Handler\Demo\Action\DemoExceptionAction::__invoke
"[ CATCH ]" | "Gzhegow\Pipeline\Exception\Exception\PipelineException" | "Unhandled exception occured during processing pipeline"
"[ CATCH ]" | "Gzhegow\Pipeline\Exception\Exception" | "Hello, World!"
"[ RESULT ]" | NULL
""
HEREDOC
);

// >>> TEST 8
// > к любой цепочке можно подключить middleware (они выполняются первыми и оборачивают цепь)
// > если необходимо, чтобы middleware оборачивал только некоторые действия, то их следует обернуть в отдельный Pipeline
$fn = function () use ($factory) {
    // > создаем конвеер
    $pipeline = $factory->newPipeline();

    // > добавляем действия в конвеер
    $pipeline
        ->middleware(\Gzhegow\Pipeline\Handler\Demo\Middleware\Demo1stMiddleware::class)
        ->middleware(\Gzhegow\Pipeline\Handler\Demo\Middleware\Demo2ndMiddleware::class)
        ->action(\Gzhegow\Pipeline\Handler\Demo\Action\Demo1stAction::class)
    ;

    // > запускаем конвеер
    $myInput = null;
    $myContext = null;
    $result = $pipeline->run($myInput, $myContext);
    _dump('[ RESULT ]', $result);
    _dump('');
};
_assert_call($fn, [], <<<HEREDOC
@before :: Gzhegow\Pipeline\Handler\Demo\Middleware\Demo1stMiddleware::__invoke
@before :: Gzhegow\Pipeline\Handler\Demo\Middleware\Demo2ndMiddleware::__invoke
Gzhegow\Pipeline\Handler\Demo\Action\Demo1stAction::__invoke
@after :: Gzhegow\Pipeline\Handler\Demo\Middleware\Demo2ndMiddleware::__invoke
@after :: Gzhegow\Pipeline\Handler\Demo\Middleware\Demo1stMiddleware::__invoke
"[ RESULT ]" | "Gzhegow\Pipeline\Handler\Demo\Action\Demo1stAction::__invoke result."
""
HEREDOC
);

// >>> TEST 9
// > middleware может предотвратить выполнение цепочки (то есть уже написанный код можно отменить, не редактируя его)
$fn = function () use ($factory) {
    // > создаем конвеер
    $pipeline = $factory->newPipeline();

    // > добавляем действия в конвеер
    $pipeline
        ->middleware(\Gzhegow\Pipeline\Handler\Demo\Middleware\DemoOmitMiddleware::class)
        ->action(\Gzhegow\Pipeline\Handler\Demo\Action\Demo1stAction::class)
        ->action(\Gzhegow\Pipeline\Handler\Demo\Action\Demo1stAction::class)
    ;

    // > запускаем конвеер
    $myInput = null;
    $myContext = null;
    $result = $pipeline->run($myInput, $myContext);
    _dump('[ RESULT ]', $result);
    _dump('');
};
_assert_call($fn, [], <<<HEREDOC
@before :: Gzhegow\Pipeline\Handler\Demo\Middleware\DemoOmitMiddleware::__invoke
@after :: Gzhegow\Pipeline\Handler\Demo\Middleware\DemoOmitMiddleware::__invoke
"[ RESULT ]" | "Gzhegow\Pipeline\Handler\Demo\Middleware\DemoOmitMiddleware::__invoke result."
""
HEREDOC
);

// >>> TEST 10
// > middleware может предотвратить выполнение цепочки (то есть уже написанный код можно отменить фильтром, не редактируя его)
$fn = function () use ($factory) {
    // > создаем конвеер
    $pipeline = $factory->newPipeline();

    // > добавляем действия в конвеер
    $pipeline
        ->middleware(\Gzhegow\Pipeline\Handler\Demo\Middleware\Demo1stMiddleware::class)
        ->middleware(\Gzhegow\Pipeline\Handler\Demo\Middleware\DemoOmitMiddleware::class)
        ->action(\Gzhegow\Pipeline\Handler\Demo\Action\Demo1stAction::class)
        ->action(\Gzhegow\Pipeline\Handler\Demo\Action\Demo1stAction::class)
    ;

    // > запускаем конвеер
    $myInput = null;
    $myContext = null;
    $result = $pipeline->run($myInput, $myContext);
    _dump('[ RESULT ]', $result);
    _dump('');
};
_assert_call($fn, [], <<<HEREDOC
@before :: Gzhegow\Pipeline\Handler\Demo\Middleware\Demo1stMiddleware::__invoke
@before :: Gzhegow\Pipeline\Handler\Demo\Middleware\DemoOmitMiddleware::__invoke
@after :: Gzhegow\Pipeline\Handler\Demo\Middleware\DemoOmitMiddleware::__invoke
@after :: Gzhegow\Pipeline\Handler\Demo\Middleware\Demo1stMiddleware::__invoke
"[ RESULT ]" | "Gzhegow\Pipeline\Handler\Demo\Middleware\DemoOmitMiddleware::__invoke result."
""
HEREDOC
);
```