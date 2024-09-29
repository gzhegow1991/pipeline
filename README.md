# Pipeline

Первая реализация конвеера действий с возможностью добавлять Middleware и Fallback, реализация паттерн "цепочка обязанностей"

Нужно в роутерах, в последствии - в асинхронном выполнении тоже, только требует доработки. Когда-нибудь.

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
    var_dump(\Gzhegow\Pipeline\Lib::php_dump($e));
    var_dump($e->getMessage());
    var_dump(($e->getFile() ?? '{file}') . ': ' . ($e->getLine() ?? '{line}'));

    die();
});


/**
 * @param mixed $value
 * @param mixed ...$values
 */
function _dump($value, ...$values)
{
    // // requires `symfony/var-dumper`
    // dump($value, ...$values);

    array_unshift($values, $value);
    $strings = array_map([ \Gzhegow\Pipeline\Lib::class, 'php_dump' ], $values);
    echo implode(" ", $strings) . PHP_EOL;

    return $value;
}


// > сначала всегда факторка
$factory = new \Gzhegow\Pipeline\PipelineFactory();

(function () use ($factory) {
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
    // string(60) "Gzhegow\Pipeline\Handler\Demo\Action\Demo1stAction::__invoke"
    // string(60) "Gzhegow\Pipeline\Handler\Demo\Action\Demo1stAction::__invoke"
    // { string(10) "[ RESULT ]" } { string(68) "Gzhegow\Pipeline\Handler\Demo\Action\Demo1stAction::__invoke result." }
    // { string(0) "" }
})();

$pipelinePrevious = (function () use ($factory) {
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
    // string(61) "Gzhegow\Pipeline\Handler\Demo\Action\DemoPassAction::__invoke"
    // string(61) "Gzhegow\Pipeline\Handler\Demo\Action\DemoPassAction::__invoke"
    // { string(10) "[ RESULT ]" } { string(12) "any result 2" }
    // { string(0) "" }

    return $pipeline;
})();

(function () use ($factory, $pipelinePrevious) {
    // > цепочка может состоять даже из цепочек
    // > создаем конвеер
    $pipeline = $factory->newPipeline();

    // > добавляем действия в конвеер
    $pipeline
        ->action($pipelinePrevious)
        ->action(\Gzhegow\Pipeline\Handler\Demo\Action\Demo2ndAction::class)
        ->action($pipelinePrevious)
    ;

    // > запускаем конвеер
    $myInput = 'any data 3';
    $myContext = (object) [];
    $myResult = 'any result 3';
    $result = $pipeline->run($myInput, $myContext, $myResult);
    _dump('[ RESULT ]', $result);
    _dump('');
    // string(61) "Gzhegow\Pipeline\Handler\Demo\Action\DemoPassAction::__invoke"
    // string(61) "Gzhegow\Pipeline\Handler\Demo\Action\DemoPassAction::__invoke"
    // string(60) "Gzhegow\Pipeline\Handler\Demo\Action\Demo2ndAction::__invoke"
    // string(61) "Gzhegow\Pipeline\Handler\Demo\Action\DemoPassAction::__invoke"
    // string(61) "Gzhegow\Pipeline\Handler\Demo\Action\DemoPassAction::__invoke"
    // { string(10) "[ RESULT ]" } { string(12) "any result 3" }
    // { string(0) "" }
})();

(function () use ($factory) {
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
    // @before :: Gzhegow\Pipeline\Handler\Demo\Middleware\Demo1stMiddleware::__invoke
    // @before :: Gzhegow\Pipeline\Handler\Demo\Middleware\Demo2ndMiddleware::__invoke
    // string(60) "Gzhegow\Pipeline\Handler\Demo\Action\Demo1stAction::__invoke"
    // @after :: Gzhegow\Pipeline\Handler\Demo\Middleware\Demo2ndMiddleware::__invoke
    // @after :: Gzhegow\Pipeline\Handler\Demo\Middleware\Demo1stMiddleware::__invoke
    // { string(10) "[ RESULT ]" } { string(68) "Gzhegow\Pipeline\Handler\Demo\Action\Demo1stAction::__invoke result." }
    // { string(0) "" }
})();

(function () use ($factory) {
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
    // @before :: Gzhegow\Pipeline\Handler\Demo\Middleware\DemoOmitMiddleware::__invoke
    // @after :: Gzhegow\Pipeline\Handler\Demo\Middleware\DemoOmitMiddleware::__invoke
    // { string(10) "[ RESULT ]" } { string(77) "Gzhegow\Pipeline\Handler\Demo\Middleware\DemoOmitMiddleware::__invoke result." }
    // { string(0) "" }
})();

(function () use ($factory) {
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
})();

(function () use ($factory) {
    // > если fallback возвращает NULL, то система попробует поймать исключение следующим fallback
    // > создаем конвеер
    $pipeline = $factory->newPipeline();

    // > добавляем действия в конвеер
    $pipeline
        ->action(\Gzhegow\Pipeline\Handler\Demo\Action\DemoExceptionAction::class)
        ->fallback(\Gzhegow\Pipeline\Handler\Demo\Fallback\DemoStepFallback::class)
        ->fallback(\Gzhegow\Pipeline\Handler\Demo\Fallback\DemoStepFallback::class)
        ->fallback(\Gzhegow\Pipeline\Handler\Demo\Fallback\DemoStepFallback::class)
        ->fallback(\Gzhegow\Pipeline\Handler\Demo\Fallback\DemoFallback::class)
    ;

    // > запускаем конвеер
    $myInput = 'any data 7';
    $myContext = (object) [];
    $result = $pipeline->run($myInput, $myContext);
    _dump('[ RESULT ]', $result);
    _dump('');
    // string(66) "Gzhegow\Pipeline\Handler\Demo\Action\DemoExceptionAction::__invoke"
    // string(65) "Gzhegow\Pipeline\Handler\Demo\Fallback\DemoStepFallback::__invoke"
    // string(65) "Gzhegow\Pipeline\Handler\Demo\Fallback\DemoStepFallback::__invoke"
    // string(65) "Gzhegow\Pipeline\Handler\Demo\Fallback\DemoStepFallback::__invoke"
    // string(61) "Gzhegow\Pipeline\Handler\Demo\Fallback\DemoFallback::__invoke"
    // { string(10) "[ RESULT ]" } { string(69) "Gzhegow\Pipeline\Handler\Demo\Fallback\DemoFallback::__invoke result." }
    // { string(0) "" }
})();

(function () use ($factory) {
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
        _dump('[ CATCH ] ', get_class($e));
    }
    _dump('[ RESULT ]', $result);
    _dump('');
    // string(66) "Gzhegow\Pipeline\Handler\Demo\Action\DemoExceptionAction::__invoke"
    // { string(10) "[ CATCH ] " } { string(36) "Gzhegow\Pipeline\Exception\Exception" }
    // { string(10) "[ RESULT ]" } { NULL }
    // { string(0) "" }
})();
```