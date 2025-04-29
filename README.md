# Pipeline

Конвеер, реализация паттерна "цепочка обязанностей"

Требуется в:

- в роутерах: возможность цеплять Middleware и Fallback
- в логике: возможность писать обработчики данных без дублирования (Chain Of Responsibility)
- в отладке: подменять этапы, не меняя их код и не изучая его
- в асинхронном выполнении: возможность перебрасывать шаги из нескольких конвееров на несколько ядер процессора, параллельность

## Установить

```
composer require gzhegow/pipeline
```

## Запустить тесты

```
php test.php
```

## Примеры и тесты

```php
<?php

require_once __DIR__ . '/../vendor/autoload.php';


// > настраиваем PHP
ini_set('memory_limit', '32M');


// > настраиваем обработку ошибок
\Gzhegow\Lib\Lib::errorHandler()
    ->setDirRoot(__DIR__ . '/..')
    //
    ->useErrorReporting()
    ->useErrorHandler()
    ->useExceptionHandler()
;


// > добавляем несколько функция для тестирования
$ffn = new class {
    function root() : string
    {
        return realpath(__DIR__ . '/..');
    }


    function values($separator = null, ...$values) : string
    {
        return \Gzhegow\Lib\Lib::debug()->values([], $separator, ...$values);
    }


    function print(...$values) : void
    {
        echo $this->values(' | ', ...$values) . PHP_EOL;
    }


    function assert_stdout(
        \Closure $fn, array $fnArgs = [],
        string $expectedStdout = null
    ) : void
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1);

        \Gzhegow\Lib\Lib::test()->assertStdout(
            $trace,
            $fn, $fnArgs,
            $expectedStdout
        );
    }
};


// >>> ЗАПУСКАЕМ!

// > сначала всегда фабрика
$factory = new \Gzhegow\Pipeline\PipelineFactory();

// > создаем процессор
// > его задача выполнять конечные функции, предоставляя зависимости для их вызова (например, при использовании контейнера DI)
$processor = new \Gzhegow\Pipeline\Processor\PipelineProcessor($factory);

// > создаем менеджер процессов
// > его задача выполнять шаги процессов, созданных на основе цепочек, и передавать управление процессору
$processManager = new \Gzhegow\Pipeline\ProcessManager\PipelineProcessManager(
    $factory,
    $processor
);

// > создаем фасад и сохраняем его глобально (не обязательно)
// > его задача - предоставить общий интерфейс для управления конвеерами во всей программе
$facade = new \Gzhegow\Pipeline\PipelineFacade(
    $factory,
    $processManager
);

// > сохраняем фасад статически, позволяя вызывать его напрямую без внедрения зависимостей
// > но правильный путь - это всё-таки передавать фасад зависимостью, позволяя его подменить, а не фиксируя статику в коде
\Gzhegow\Pipeline\Pipeline::setFacade($facade);


// >>> TEST
// > цепочка может состоять из одного или нескольких действий
$fn = function () use ($factory, $processManager, $ffn) {
    $ffn->print('[ TEST 1 ]');
    echo PHP_EOL;

    // > создаем конвеер
    $pipeline = $factory->newPipeline();

    // > вызывать можно и статически, если перед этим сохранили фасад
    // $pipeline = \Gzhegow\Pipeline\PipelineFacade::new();

    // > добавляем действия в конвеер
    $pipeline
        ->action(\Gzhegow\Pipeline\Demo\Handler\Action\Demo1stAction::class)
        ->action(\Gzhegow\Pipeline\Demo\Handler\Action\Demo2ndAction::class)
    ;

    // > устанавливаем стартовый $input, который будет меняться при прохождении по цепочке на результат прошлого действия
    $myInput = 'any data 1';

    // > устанавливаем произвольные данные в поле $context, они не будут затронуты механизмом Pipeline
    // > разумно передать сюда объект, чтобы он был общим для всех шагов и складывать сюда отчеты или промежуточные данные
    $myContext = (object) [];

    // > устанавливаем менеджер процессов для цепочки + запускаем конвеер из самой цепочки
    $pipeline->setProcessManager($processManager);
    $result = $pipeline->run($myInput, $myContext);

    // > либо иным способом
    // $result = $processManager->run($pipeline, $myInput, $myContext); // то же самое
    // $result = \Gzhegow\Pipeline\Pipeline::run($pipeline, $myInput, $myContext); // то же самое

    $ffn->print('[ RESULT ]', $result);
};
$ffn->assert_stdout($fn, [], '
"[ TEST 1 ]"

Gzhegow\Pipeline\Demo\Handler\Action\Demo1stAction::__invoke
Gzhegow\Pipeline\Demo\Handler\Action\Demo2ndAction::__invoke
"[ RESULT ]" | "Gzhegow\Pipeline\Demo\Handler\Action\Demo2ndAction::__invoke result."
'
);

// >>> TEST
// > действия могут передавать результат выполнения из одного в другое
$fn = function () use ($ffn) {
    $ffn->print('[ TEST 2 ]');
    echo PHP_EOL;

    // > создаем конвеер
    $pipeline = \Gzhegow\Pipeline\Pipeline::pipeline();

    // > добавляем действия в конвеер
    $pipeline
        ->action(\Gzhegow\Pipeline\Demo\Handler\Action\DemoPassInputToResultAction::class)
        ->action(\Gzhegow\Pipeline\Demo\Handler\Action\DemoPassInputToResultAction::class)
    ;

    $myInput = 'any data 2';
    $myContext = null;

    // > запускаем конвеер
    $result = \Gzhegow\Pipeline\Pipeline::run($pipeline, $myInput, $myContext);
    $ffn->print('[ RESULT ]', $result);
};
$ffn->assert_stdout($fn, [], '
"[ TEST 2 ]"

Gzhegow\Pipeline\Demo\Handler\Action\DemoPassInputToResultAction::__invoke
Gzhegow\Pipeline\Demo\Handler\Action\DemoPassInputToResultAction::__invoke
"[ RESULT ]" | "any data 2"
');

// >>> TEST
// > выброшенную ошибку можно превратить в результат используя fallback
$fn = function () use ($ffn) {
    $ffn->print('[ TEST 3 ]');
    echo PHP_EOL;

    // > создаем конвеер
    $pipeline = \Gzhegow\Pipeline\Pipeline::pipeline();

    // > добавляем действия в конвеер
    $pipeline
        ->action(\Gzhegow\Pipeline\Demo\Handler\Action\DemoLogicExceptionAction::class)
        ->fallback(\Gzhegow\Pipeline\Demo\Handler\Fallback\DemoLogicExceptionFallback::class)
    ;

    $myInput = null;
    $myContext = null;

    // > запускаем конвеер
    $result = \Gzhegow\Pipeline\Pipeline::run($pipeline, $myInput, $myContext);
    $ffn->print('[ RESULT ]', $result);
};
$ffn->assert_stdout($fn, [], '
"[ TEST 3 ]"

Gzhegow\Pipeline\Demo\Handler\Action\DemoLogicExceptionAction::__invoke
Gzhegow\Pipeline\Demo\Handler\Fallback\DemoLogicExceptionFallback::__invoke
"[ RESULT ]" | "Gzhegow\Pipeline\Demo\Handler\Fallback\DemoLogicExceptionFallback::__invoke result."
');

// >>> TEST
// > цепочка может начинаться с исключения, которое нужно обработать
$fn = function () use ($ffn) {
    $ffn->print('[ TEST 4 ]');
    echo PHP_EOL;

    // > создаем конвеер
    $pipeline = \Gzhegow\Pipeline\Pipeline::pipeline();

    // > добавляем действия в конвеер
    $pipeline
        ->throwable(new \LogicException('Hello, World!'))
        ->fallback(\Gzhegow\Pipeline\Demo\Handler\Fallback\DemoLogicExceptionFallback::class)
    ;

    $myInput = null;
    $myContext = null;

    // > запускаем конвеер
    $result = \Gzhegow\Pipeline\Pipeline::run($pipeline, $myInput, $myContext);
    $ffn->print('[ RESULT ]', $result);
};
$ffn->assert_stdout($fn, [], '
"[ TEST 4 ]"

Gzhegow\Pipeline\Demo\Handler\Fallback\DemoLogicExceptionFallback::__invoke
"[ RESULT ]" | "Gzhegow\Pipeline\Demo\Handler\Fallback\DemoLogicExceptionFallback::__invoke result."
');

// >>> TEST
// > если fallback возвращает NULL, то система попробует поймать исключение следующим fallback
$fn = function () use ($ffn) {
    $ffn->print('[ TEST 5 ]');
    echo PHP_EOL;

    // > создаем конвеер
    $pipeline = \Gzhegow\Pipeline\Pipeline::pipeline();

    // > добавляем действия в конвеер
    $pipeline
        ->action(\Gzhegow\Pipeline\Demo\Handler\Action\DemoExceptionAction::class)
        ->fallback(\Gzhegow\Pipeline\Demo\Handler\Fallback\DemoSkipFallback::class)
        ->fallback(\Gzhegow\Pipeline\Demo\Handler\Fallback\DemoThrowableFallback::class)
    ;

    $myInput = null;
    $myContext = null;

    // > запускаем конвеер
    $result = \Gzhegow\Pipeline\Pipeline::run($pipeline, $myInput, $myContext);
    $ffn->print('[ RESULT ]', $result);
};
$ffn->assert_stdout($fn, [], '
"[ TEST 5 ]"

Gzhegow\Pipeline\Demo\Handler\Action\DemoExceptionAction::__invoke
Gzhegow\Pipeline\Demo\Handler\Fallback\DemoSkipFallback::__invoke
Gzhegow\Pipeline\Demo\Handler\Fallback\DemoThrowableFallback::__invoke
"[ RESULT ]" | "Gzhegow\Pipeline\Demo\Handler\Fallback\DemoThrowableFallback::__invoke result."
');

// >>> TEST
// > если ни один из fallback не обработает ошибку, ошибка будет выброшена наружу
$fn = function () use ($ffn) {
    $ffn->print('[ TEST 6 ]');
    echo PHP_EOL;

    // > создаем конвеер
    $pipeline = \Gzhegow\Pipeline\Pipeline::pipeline();

    // > добавляем действия в конвеер
    $pipeline
        ->action(\Gzhegow\Pipeline\Demo\Handler\Action\DemoExceptionAction::class)
        ->fallback(\Gzhegow\Pipeline\Demo\Handler\Fallback\DemoLogicExceptionFallback::class)
    ;

    $myInput = null;
    $myContext = null;

    // > запускаем конвеер
    $result = null;
    try {
        $result = \Gzhegow\Pipeline\Pipeline::run($pipeline, $myInput, $myContext);
    }
    catch ( \Gzhegow\Pipeline\Exception\Runtime\PipelineException $e ) {
        $ffn->print('[ CATCH ]', get_class($e), $e->getMessage());

        foreach ( $e->getPreviousList() as $ee ) {
            $ffn->print('[ CATCH ]', get_class($ee), $ee->getMessage());
        }
    }
    $ffn->print('[ RESULT ]', $result);
};
$ffn->assert_stdout($fn, [], '
"[ TEST 6 ]"

Gzhegow\Pipeline\Demo\Handler\Action\DemoExceptionAction::__invoke
Gzhegow\Pipeline\Demo\Handler\Fallback\DemoLogicExceptionFallback::__invoke
"[ CATCH ]" | "Gzhegow\Pipeline\Exception\Runtime\PipelineException" | "Unhandled exception occured during processing pipeline"
"[ CATCH ]" | "Gzhegow\Pipeline\Exception\Exception" | "Hello, World!"
"[ RESULT ]" | NULL
');

// >>> TEST
// > к любой цепочке можно подключить middleware
// > + они как фильтры, могут пропустить дальнейшие шаги в конвеере
// > + они как события, могут выполнить дополнительные действия или подготовить входные данные следуюших шагов
// > если необходимо, чтобы middleware оборачивал только некоторые действия, то их следует обернуть в отдельный Pipeline
$fn = function () use ($factory, $ffn) {
    $ffn->print('[ TEST 7 ]');
    echo PHP_EOL;

    // > создаем конвеер
    $pipeline = \Gzhegow\Pipeline\Pipeline::pipeline();

    // > создаем посредник
    $middleware = $factory->newMiddleware(\Gzhegow\Pipeline\Demo\Handler\Middleware\Demo1stMiddleware::class);

    // > вызывать можно и статически, если перед этим сохранили фасад
    // $middleware = \Gzhegow\Pipeline\PipelineFacade::middleware(\Gzhegow\Pipeline\Demo\Handler\Middleware\Demo1stMiddleware::class);

    // > добавлять вложенные pipeline/middleware можно также используя синтаксис ->startX()/->endX()
    $middleware
        ->startMiddleware(\Gzhegow\Pipeline\Demo\Handler\Middleware\Demo2ndMiddleware::class)
        ->action(\Gzhegow\Pipeline\Demo\Handler\Action\Demo1stAction::class)
        ->endMiddleware()
    ;

    // > добавляем действия в конвеер
    $pipeline
        ->middleware($middleware)
    ;

    $myInput = null;
    $myContext = null;

    // > запускаем конвеер
    $result = \Gzhegow\Pipeline\Pipeline::run($pipeline, $myInput, $myContext);
    $ffn->print('[ RESULT ]', $result);
};
$ffn->assert_stdout($fn, [], '
"[ TEST 7 ]"

@before :: Gzhegow\Pipeline\Demo\Handler\Middleware\Demo1stMiddleware::__invoke
@before :: Gzhegow\Pipeline\Demo\Handler\Middleware\Demo2ndMiddleware::__invoke
Gzhegow\Pipeline\Demo\Handler\Action\Demo1stAction::__invoke
@after :: Gzhegow\Pipeline\Demo\Handler\Middleware\Demo2ndMiddleware::__invoke
@after :: Gzhegow\Pipeline\Demo\Handler\Middleware\Demo1stMiddleware::__invoke
"[ RESULT ]" | "Gzhegow\Pipeline\Demo\Handler\Action\Demo1stAction::__invoke result."
');

// >>> TEST
// > middleware может предотвратить выполнение цепочки (то есть уже написанный код можно отменить фильтром, не редактируя его)
$fn = function () use ($ffn) {
    $ffn->print('[ TEST 8 ]');
    echo PHP_EOL;

    // > создаем конвеер
    $pipeline = \Gzhegow\Pipeline\Pipeline::pipeline();

    // > добавляем действия в конвеер
    $pipeline
        ->startMiddleware(\Gzhegow\Pipeline\Demo\Handler\Middleware\Demo1stMiddleware::class)
        ->startMiddleware(\Gzhegow\Pipeline\Demo\Handler\Middleware\DemoOmitMiddleware::class)
        ->action(\Gzhegow\Pipeline\Demo\Handler\Action\Demo1stAction::class)
        ->action(\Gzhegow\Pipeline\Demo\Handler\Action\Demo2ndAction::class)
        ->action(\Gzhegow\Pipeline\Demo\Handler\Action\Demo3rdAction::class)
        ->action(\Gzhegow\Pipeline\Demo\Handler\Action\Demo4thAction::class)
        ->endMiddleware()
        ->endMiddleware()
    ;

    $myInput = null;
    $myContext = null;

    // > запускаем конвеер
    $result = \Gzhegow\Pipeline\Pipeline::run($pipeline, $myInput, $myContext);
    $ffn->print('[ RESULT ]', $result);
};
$ffn->assert_stdout($fn, [], '
"[ TEST 8 ]"

@before :: Gzhegow\Pipeline\Demo\Handler\Middleware\Demo1stMiddleware::__invoke
@before :: Gzhegow\Pipeline\Demo\Handler\Middleware\DemoOmitMiddleware::__invoke
@after :: Gzhegow\Pipeline\Demo\Handler\Middleware\DemoOmitMiddleware::__invoke
@after :: Gzhegow\Pipeline\Demo\Handler\Middleware\Demo1stMiddleware::__invoke
"[ RESULT ]" | "Gzhegow\Pipeline\Demo\Handler\Middleware\DemoOmitMiddleware::__invoke result."
');

// >>> TEST
// > цепочка может состоять даже из цепочек
$fn = function () use ($ffn) {
    $ffn->print('[ TEST 9 ]');
    echo PHP_EOL;

    // > создаем дочерний конвеер
    $pipelineChild = \Gzhegow\Pipeline\Pipeline::pipeline();

    // > добавляем действия в конвеер
    $pipelineChild
        ->action(\Gzhegow\Pipeline\Demo\Handler\Action\DemoPassInputToResultAction::class)
        ->action(\Gzhegow\Pipeline\Demo\Handler\Action\DemoPassInputToResultAction::class)
    ;

    // > создаем родительский конвеер
    $pipeline = \Gzhegow\Pipeline\Pipeline::pipeline();

    // > добавляем действия (в том числе дочерние конвееры) в родительский конвеер
    // > при добавлении конвееров они будут склонированы, то есть один и тот же экземпляр может быть добавлен сколько угодно раз
    $pipeline
        ->pipeline($pipelineChild)                                           // этот конвеер просто передаст $result дальше
        ->action(\Gzhegow\Pipeline\Demo\Handler\Action\Demo1stAction::class) // на этом этапе результат будет заменен
        ->pipeline($pipelineChild)                                           // этот конвеер передаст измененный $result дальше
    ;

    $myInput = null;
    $myContext = null;

    // > запускаем конвеер
    $result = \Gzhegow\Pipeline\Pipeline::run($pipeline, $myInput, $myContext);
    $ffn->print('[ RESULT ]', $result);
};
$ffn->assert_stdout($fn, [], '
"[ TEST 9 ]"

Gzhegow\Pipeline\Demo\Handler\Action\DemoPassInputToResultAction::__invoke
Gzhegow\Pipeline\Demo\Handler\Action\DemoPassInputToResultAction::__invoke
Gzhegow\Pipeline\Demo\Handler\Action\Demo1stAction::__invoke
Gzhegow\Pipeline\Demo\Handler\Action\DemoPassInputToResultAction::__invoke
Gzhegow\Pipeline\Demo\Handler\Action\DemoPassInputToResultAction::__invoke
"[ RESULT ]" | "Gzhegow\Pipeline\Demo\Handler\Action\Demo1stAction::__invoke result."
');

// >>> TEST
// > может состоять из middleware вложенных друг в друга
$fn = function () use ($ffn) {
    $ffn->print('[ TEST 10 ]');
    echo PHP_EOL;

    // > добавляем действия (в том числе дочерние конвееры) в родительский конвеер
    $pipeline = \Gzhegow\Pipeline\Pipeline::pipeline()
        ->startMiddleware(\Gzhegow\Pipeline\Demo\Handler\Middleware\Demo1stMiddleware::class)
        ->startMiddleware(\Gzhegow\Pipeline\Demo\Handler\Middleware\Demo2ndMiddleware::class)
        ->action(\Gzhegow\Pipeline\Demo\Handler\Action\Demo1stAction::class)
        ->action(\Gzhegow\Pipeline\Demo\Handler\Action\Demo2ndAction::class)
        ->endMiddleware()
        ->endMiddleware()
    ;

    $myInput = null;
    $myContext = null;

    // > запускаем конвеер
    $result = \Gzhegow\Pipeline\Pipeline::run($pipeline, $myInput, $myContext);
    $ffn->print('[ RESULT ]', $result);
};
$ffn->assert_stdout($fn, [], '
"[ TEST 10 ]"

@before :: Gzhegow\Pipeline\Demo\Handler\Middleware\Demo1stMiddleware::__invoke
@before :: Gzhegow\Pipeline\Demo\Handler\Middleware\Demo2ndMiddleware::__invoke
Gzhegow\Pipeline\Demo\Handler\Action\Demo1stAction::__invoke
Gzhegow\Pipeline\Demo\Handler\Action\Demo2ndAction::__invoke
@after :: Gzhegow\Pipeline\Demo\Handler\Middleware\Demo2ndMiddleware::__invoke
@after :: Gzhegow\Pipeline\Demo\Handler\Middleware\Demo1stMiddleware::__invoke
"[ RESULT ]" | "Gzhegow\Pipeline\Demo\Handler\Action\Demo2ndAction::__invoke result."
');

// >>> TEST
// > что не отменяет возможности, что в одном из действий произойдет ошибка, которая должна быть поймана, а цепочка - продолжиться
$fn = function () use ($ffn) {
    $ffn->print('[ TEST 11 ]');
    echo PHP_EOL;

    // > добавляем действия (в том числе дочерние конвееры) в родительский конвеер
    $pipeline = \Gzhegow\Pipeline\Pipeline::pipeline()
        ->startMiddleware(\Gzhegow\Pipeline\Demo\Handler\Middleware\Demo1stMiddleware::class)
        ->startMiddleware(\Gzhegow\Pipeline\Demo\Handler\Middleware\Demo2ndMiddleware::class)
        ->action(\Gzhegow\Pipeline\Demo\Handler\Action\DemoLogicExceptionAction::class)
        ->fallback(\Gzhegow\Pipeline\Demo\Handler\Fallback\DemoRuntimeExceptionFallback::class)
        ->fallback(\Gzhegow\Pipeline\Demo\Handler\Fallback\DemoLogicExceptionFallback::class)
        ->fallback(\Gzhegow\Pipeline\Demo\Handler\Fallback\DemoThrowableFallback::class)
        ->endMiddleware()
        ->endMiddleware()
    ;

    $myInput = null;
    $myContext = null;

    // > запускаем конвеер
    $result = \Gzhegow\Pipeline\Pipeline::run($pipeline, $myInput, $myContext);
    $ffn->print('[ RESULT ]', $result);
};
$ffn->assert_stdout($fn, [], '
"[ TEST 11 ]"

@before :: Gzhegow\Pipeline\Demo\Handler\Middleware\Demo1stMiddleware::__invoke
@before :: Gzhegow\Pipeline\Demo\Handler\Middleware\Demo2ndMiddleware::__invoke
Gzhegow\Pipeline\Demo\Handler\Action\DemoLogicExceptionAction::__invoke
Gzhegow\Pipeline\Demo\Handler\Fallback\DemoRuntimeExceptionFallback::__invoke
Gzhegow\Pipeline\Demo\Handler\Fallback\DemoLogicExceptionFallback::__invoke
@after :: Gzhegow\Pipeline\Demo\Handler\Middleware\Demo2ndMiddleware::__invoke
@after :: Gzhegow\Pipeline\Demo\Handler\Middleware\Demo1stMiddleware::__invoke
"[ RESULT ]" | "Gzhegow\Pipeline\Demo\Handler\Fallback\DemoLogicExceptionFallback::__invoke result."
');

// >>> TEST
// > а вообще, даже из цепочек-в-цепочках может состоять
// > вообще, этот конструктор нужен, чтобы ограничивать действие middleware только на несколько действий, а не на все
$fn = function () use ($ffn) {
    $ffn->print('[ TEST 12 ]');
    echo PHP_EOL;

    // > добавляем действия в конвеер 2 уровня
    $middleware2nd = \Gzhegow\Pipeline\Pipeline::middleware(\Gzhegow\Pipeline\Demo\Handler\Middleware\Demo2ndMiddleware::class)
        ->action(\Gzhegow\Pipeline\Demo\Handler\Action\Demo3rdAction::class)
        ->action(\Gzhegow\Pipeline\Demo\Handler\Action\Demo4thAction::class)
    ;

    $pipeline2nd = \Gzhegow\Pipeline\Pipeline::pipeline()
        ->middleware($middleware2nd)
    ;

    // > добавляем действия (в том числе дочерние конвееры) в конвеер 1 уровня
    $middleware1st = \Gzhegow\Pipeline\Pipeline::middleware(\Gzhegow\Pipeline\Demo\Handler\Middleware\Demo1stMiddleware::class)
        ->action(\Gzhegow\Pipeline\Demo\Handler\Action\Demo1stAction::class)
        ->pipeline($pipeline2nd)
        ->action(\Gzhegow\Pipeline\Demo\Handler\Action\Demo2ndAction::class)
    ;

    $pipeline1st = \Gzhegow\Pipeline\Pipeline::pipeline()
        ->middleware($middleware1st)
    ;

    // > добавляем действия (в том числе дочерние конвееры) в родительский конвеер
    $pipeline = \Gzhegow\Pipeline\Pipeline::pipeline()
        ->action(\Gzhegow\Pipeline\Demo\Handler\Action\DemoPassInputToResultAction::class)
        ->pipeline($pipeline1st)
        ->action(\Gzhegow\Pipeline\Demo\Handler\Action\DemoPassInputToResultAction::class)
        ->action(\Gzhegow\Pipeline\Demo\Handler\Action\DemoPassInputToResultAction::class)
        ->pipeline($pipeline1st)
        ->action(\Gzhegow\Pipeline\Demo\Handler\Action\DemoPassInputToResultAction::class)
        ->action(\Gzhegow\Pipeline\Demo\Handler\Action\DemoPassInputToResultAction::class)
        ->pipeline($pipeline1st)
        ->action(\Gzhegow\Pipeline\Demo\Handler\Action\DemoPassInputToResultAction::class)
    ;

    $myInput = null;
    $myContext = null;

    // > запускаем конвеер
    $result = \Gzhegow\Pipeline\Pipeline::run($pipeline, $myInput, $myContext);
    $ffn->print('[ RESULT ]', $result);
};
$ffn->assert_stdout($fn, [], '
"[ TEST 12 ]"

Gzhegow\Pipeline\Demo\Handler\Action\DemoPassInputToResultAction::__invoke
@before :: Gzhegow\Pipeline\Demo\Handler\Middleware\Demo1stMiddleware::__invoke
Gzhegow\Pipeline\Demo\Handler\Action\Demo1stAction::__invoke
@before :: Gzhegow\Pipeline\Demo\Handler\Middleware\Demo2ndMiddleware::__invoke
Gzhegow\Pipeline\Demo\Handler\Action\Demo3rdAction::__invoke
Gzhegow\Pipeline\Demo\Handler\Action\Demo4thAction::__invoke
@after :: Gzhegow\Pipeline\Demo\Handler\Middleware\Demo2ndMiddleware::__invoke
Gzhegow\Pipeline\Demo\Handler\Action\Demo2ndAction::__invoke
@after :: Gzhegow\Pipeline\Demo\Handler\Middleware\Demo1stMiddleware::__invoke
Gzhegow\Pipeline\Demo\Handler\Action\DemoPassInputToResultAction::__invoke
Gzhegow\Pipeline\Demo\Handler\Action\DemoPassInputToResultAction::__invoke
@before :: Gzhegow\Pipeline\Demo\Handler\Middleware\Demo1stMiddleware::__invoke
Gzhegow\Pipeline\Demo\Handler\Action\Demo1stAction::__invoke
@before :: Gzhegow\Pipeline\Demo\Handler\Middleware\Demo2ndMiddleware::__invoke
Gzhegow\Pipeline\Demo\Handler\Action\Demo3rdAction::__invoke
Gzhegow\Pipeline\Demo\Handler\Action\Demo4thAction::__invoke
@after :: Gzhegow\Pipeline\Demo\Handler\Middleware\Demo2ndMiddleware::__invoke
Gzhegow\Pipeline\Demo\Handler\Action\Demo2ndAction::__invoke
@after :: Gzhegow\Pipeline\Demo\Handler\Middleware\Demo1stMiddleware::__invoke
Gzhegow\Pipeline\Demo\Handler\Action\DemoPassInputToResultAction::__invoke
Gzhegow\Pipeline\Demo\Handler\Action\DemoPassInputToResultAction::__invoke
@before :: Gzhegow\Pipeline\Demo\Handler\Middleware\Demo1stMiddleware::__invoke
Gzhegow\Pipeline\Demo\Handler\Action\Demo1stAction::__invoke
@before :: Gzhegow\Pipeline\Demo\Handler\Middleware\Demo2ndMiddleware::__invoke
Gzhegow\Pipeline\Demo\Handler\Action\Demo3rdAction::__invoke
Gzhegow\Pipeline\Demo\Handler\Action\Demo4thAction::__invoke
@after :: Gzhegow\Pipeline\Demo\Handler\Middleware\Demo2ndMiddleware::__invoke
Gzhegow\Pipeline\Demo\Handler\Action\Demo2ndAction::__invoke
@after :: Gzhegow\Pipeline\Demo\Handler\Middleware\Demo1stMiddleware::__invoke
Gzhegow\Pipeline\Demo\Handler\Action\DemoPassInputToResultAction::__invoke
"[ RESULT ]" | "Gzhegow\Pipeline\Demo\Handler\Action\Demo2ndAction::__invoke result."
');


// >>> TEST
// > исключение возникло в глубине цепочки и не было обработано, но в конце добавлены Fallback, которые его поймают
$fn = function () use ($ffn) {
    $ffn->print('[ TEST 13 ]');
    echo PHP_EOL;

    // > добавляем действия (в том числе дочерние конвееры) в родительский конвеер
    $pipeline = \Gzhegow\Pipeline\Pipeline::pipeline()
        ->startMiddleware(\Gzhegow\Pipeline\Demo\Handler\Middleware\Demo1stMiddleware::class)
        ->startMiddleware(\Gzhegow\Pipeline\Demo\Handler\Middleware\Demo2ndMiddleware::class)
        ->action(\Gzhegow\Pipeline\Demo\Handler\Action\DemoLogicExceptionAction::class)
        ->endMiddleware()
        ->endMiddleware()
        ->fallback(\Gzhegow\Pipeline\Demo\Handler\Fallback\DemoRuntimeExceptionFallback::class)
        ->fallback(\Gzhegow\Pipeline\Demo\Handler\Fallback\DemoLogicExceptionFallback::class)
        ->fallback(\Gzhegow\Pipeline\Demo\Handler\Fallback\DemoThrowableFallback::class)
    ;

    $myInput = null;
    $myContext = null;

    // > запускаем конвеер
    $result = \Gzhegow\Pipeline\Pipeline::run($pipeline, $myInput, $myContext);
    $ffn->print('[ RESULT ]', $result);
};
$ffn->assert_stdout($fn, [], '
"[ TEST 13 ]"

@before :: Gzhegow\Pipeline\Demo\Handler\Middleware\Demo1stMiddleware::__invoke
@before :: Gzhegow\Pipeline\Demo\Handler\Middleware\Demo2ndMiddleware::__invoke
Gzhegow\Pipeline\Demo\Handler\Action\DemoLogicExceptionAction::__invoke
@after :: Gzhegow\Pipeline\Demo\Handler\Middleware\Demo2ndMiddleware::__invoke
@after :: Gzhegow\Pipeline\Demo\Handler\Middleware\Demo1stMiddleware::__invoke
Gzhegow\Pipeline\Demo\Handler\Fallback\DemoRuntimeExceptionFallback::__invoke
Gzhegow\Pipeline\Demo\Handler\Fallback\DemoLogicExceptionFallback::__invoke
"[ RESULT ]" | "Gzhegow\Pipeline\Demo\Handler\Fallback\DemoLogicExceptionFallback::__invoke result."
');
```

