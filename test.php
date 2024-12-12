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
set_exception_handler(function (\Throwable $e) {
    // require_once getenv('COMPOSER_HOME') . '/vendor/autoload.php';
    // dd($e);

    $current = $e;
    do {
        echo "\n";

        echo \Gzhegow\Lib\Lib::debug_var_dump($current) . PHP_EOL;
        echo $current->getMessage() . PHP_EOL;

        foreach ( $e->getTrace() as $traceItem ) {
            echo "{$traceItem['file']} : {$traceItem['line']}" . PHP_EOL;
        }

        echo PHP_EOL;
    } while ( $current = $current->getPrevious() );

    die();
});


// > добавляем несколько функция для тестирования
function _dump(...$values) : void
{
    echo implode(' | ', array_map([ \Gzhegow\Lib\Lib::class, 'debug_value' ], $values));
}

function _dump_ln(...$values) : void
{
    echo implode(' | ', array_map([ \Gzhegow\Lib\Lib::class, 'debug_value' ], $values)) . PHP_EOL;
}

function _assert_call(\Closure $fn, array $expectResult = [], string $expectOutput = null) : void
{
    $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1);

    $expect = (object) [];

    if (count($expectResult)) {
        $expect->result = $expectResult[ 0 ];
    }

    if (null !== $expectOutput) {
        $expect->output = $expectOutput;
    }

    $status = \Gzhegow\Lib\Lib::assert_call($trace, $fn, $expect, $error, STDOUT);

    if (! $status) {
        throw new \Gzhegow\Pipeline\Exception\LogicException();
    }
}


// >>> ЗАПУСКАЕМ!

// > сначала всегда фабрика
$factory = new \Gzhegow\Pipeline\Factory\DemoPipelineFactory();

// > создаем процессор
// > его задача выполнять конечные функции, предоставляя зависимости для их вызова (например, при использовании контейнера DI)
$processor = $factory->makeProcessor();

// > создаем менеджер процессов
// > его задача выполнять шаги процессов, созданных на основе цепочек, и передавать управление процессору
$processManager = $factory->makeProcessManager($processor);

// > создаем фасад и сохраняем его глобально (не обязательно)
// > его задача - предоставить общий интерфейс для управления конвеерами во всей программе
$facade = $factory->makeFacade($processManager);

// > сохраняем фасад статически, позволяя вызывать его напрямую без внедрения зависимостей
// > но правильный путь - это всё-таки передавать фасад зависимостью, позволяя его подменить, а не фиксируя статику в коде
\Gzhegow\Pipeline\Pipeline::setFacade($facade);


// >>> TEST
// > цепочка может состоять из одного или нескольких действий
$fn = function () use ($factory, $processManager) {
    _dump_ln('[ TEST 1 ]');

    // > создаем конвеер
    $pipeline = $factory->newPipeline();

    // > вызывать можно и статически, если перед этим сохранили фасад
    // $pipeline = \Gzhegow\Pipeline\PipelineFacade::new();

    // > добавляем действия в конвеер
    $pipeline
        ->action(\Gzhegow\Pipeline\Handler\Demo\Action\Demo1stAction::class)
        ->action(\Gzhegow\Pipeline\Handler\Demo\Action\Demo2ndAction::class)
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

    _dump_ln('[ RESULT ]', $result);
    _dump('');
};
_assert_call($fn, [], <<<HEREDOC
"[ TEST 1 ]"
Gzhegow\Pipeline\Handler\Demo\Action\Demo1stAction::__invoke
Gzhegow\Pipeline\Handler\Demo\Action\Demo2ndAction::__invoke
"[ RESULT ]" | "Gzhegow\Pipeline\Handler\Demo\Action\Demo2ndAction::__invoke result."
""
HEREDOC
);

// >>> TEST
// > действия могут передавать результат выполнения из одного в другое
$fn = function () {
    _dump_ln('[ TEST 2 ]');

    // > создаем конвеер
    $pipeline = \Gzhegow\Pipeline\Pipeline::pipeline();

    // > добавляем действия в конвеер
    $pipeline
        ->action(\Gzhegow\Pipeline\Handler\Demo\Action\DemoPassInputToResultAction::class)
        ->action(\Gzhegow\Pipeline\Handler\Demo\Action\DemoPassInputToResultAction::class)
    ;

    $myInput = 'any data 2';
    $myContext = null;

    // > запускаем конвеер
    $result = \Gzhegow\Pipeline\Pipeline::run($pipeline, $myInput, $myContext);
    _dump_ln('[ RESULT ]', $result);
    _dump('');
};
_assert_call($fn, [], <<<HEREDOC
"[ TEST 2 ]"
Gzhegow\Pipeline\Handler\Demo\Action\DemoPassInputToResultAction::__invoke
Gzhegow\Pipeline\Handler\Demo\Action\DemoPassInputToResultAction::__invoke
"[ RESULT ]" | "any data 2"
""
HEREDOC
);

// >>> TEST
// > выброшенную ошибку можно превратить в результат используя fallback
$fn = function () {
    _dump_ln('[ TEST 3 ]');

    // > создаем конвеер
    $pipeline = \Gzhegow\Pipeline\Pipeline::pipeline();

    // > добавляем действия в конвеер
    $pipeline
        ->action(\Gzhegow\Pipeline\Handler\Demo\Action\DemoLogicExceptionAction::class)
        ->fallback(\Gzhegow\Pipeline\Handler\Demo\Fallback\DemoLogicExceptionFallback::class)
    ;

    $myInput = null;
    $myContext = null;

    // > запускаем конвеер
    $result = \Gzhegow\Pipeline\Pipeline::run($pipeline, $myInput, $myContext);
    _dump_ln('[ RESULT ]', $result);
    _dump('');
};
_assert_call($fn, [], <<<HEREDOC
"[ TEST 3 ]"
Gzhegow\Pipeline\Handler\Demo\Action\DemoLogicExceptionAction::__invoke
Gzhegow\Pipeline\Handler\Demo\Fallback\DemoLogicExceptionFallback::__invoke
"[ RESULT ]" | "Gzhegow\Pipeline\Handler\Demo\Fallback\DemoLogicExceptionFallback::__invoke result."
""
HEREDOC
);

// >>> TEST
// > цепочка может начинаться с исключения, которое нужно обработать
$fn = function () {
    _dump_ln('[ TEST 4 ]');

    // > создаем конвеер
    $pipeline = \Gzhegow\Pipeline\Pipeline::pipeline();

    // > добавляем действия в конвеер
    $pipeline
        ->throwable(new \LogicException('Hello, World!'))
        ->fallback(\Gzhegow\Pipeline\Handler\Demo\Fallback\DemoLogicExceptionFallback::class)
    ;

    $myInput = null;
    $myContext = null;

    // > запускаем конвеер
    $result = \Gzhegow\Pipeline\Pipeline::run($pipeline, $myInput, $myContext);
    _dump_ln('[ RESULT ]', $result);
    _dump('');
};
_assert_call($fn, [], <<<HEREDOC
"[ TEST 4 ]"
Gzhegow\Pipeline\Handler\Demo\Fallback\DemoLogicExceptionFallback::__invoke
"[ RESULT ]" | "Gzhegow\Pipeline\Handler\Demo\Fallback\DemoLogicExceptionFallback::__invoke result."
""
HEREDOC
);

// >>> TEST
// > если fallback возвращает NULL, то система попробует поймать исключение следующим fallback
$fn = function () {
    _dump_ln('[ TEST 5 ]');

    // > создаем конвеер
    $pipeline = \Gzhegow\Pipeline\Pipeline::pipeline();

    // > добавляем действия в конвеер
    $pipeline
        ->action(\Gzhegow\Pipeline\Handler\Demo\Action\DemoExceptionAction::class)
        ->fallback(\Gzhegow\Pipeline\Handler\Demo\Fallback\DemoSkipFallback::class)
        ->fallback(\Gzhegow\Pipeline\Handler\Demo\Fallback\DemoThrowableFallback::class)
    ;

    $myInput = null;
    $myContext = null;

    // > запускаем конвеер
    $result = \Gzhegow\Pipeline\Pipeline::run($pipeline, $myInput, $myContext);
    _dump_ln('[ RESULT ]', $result);
    _dump('');
};
_assert_call($fn, [], <<<HEREDOC
"[ TEST 5 ]"
Gzhegow\Pipeline\Handler\Demo\Action\DemoExceptionAction::__invoke
Gzhegow\Pipeline\Handler\Demo\Fallback\DemoSkipFallback::__invoke
Gzhegow\Pipeline\Handler\Demo\Fallback\DemoThrowableFallback::__invoke
"[ RESULT ]" | "Gzhegow\Pipeline\Handler\Demo\Fallback\DemoThrowableFallback::__invoke result."
""
HEREDOC
);

// >>> TEST
// > если ни один из fallback не обработает ошибку, ошибка будет выброшена наружу
$fn = function () {
    _dump_ln('[ TEST 6 ]');

    // > создаем конвеер
    $pipeline = \Gzhegow\Pipeline\Pipeline::pipeline();

    // > добавляем действия в конвеер
    $pipeline
        ->action(\Gzhegow\Pipeline\Handler\Demo\Action\DemoExceptionAction::class)
        ->fallback(\Gzhegow\Pipeline\Handler\Demo\Fallback\DemoLogicExceptionFallback::class)
    ;

    $myInput = null;
    $myContext = null;

    // > запускаем конвеер
    $result = null;
    try {
        $result = \Gzhegow\Pipeline\Pipeline::run($pipeline, $myInput, $myContext);
    }
    catch ( \Gzhegow\Pipeline\Exception\Runtime\PipelineException $e ) {
        _dump_ln('[ CATCH ]', get_class($e), $e->getMessage());

        foreach ( $e->getPreviousList() as $ee ) {
            _dump_ln('[ CATCH ]', get_class($ee), $ee->getMessage());
        }
    }
    _dump_ln('[ RESULT ]', $result);
    _dump('');
};
_assert_call($fn, [], <<<HEREDOC
"[ TEST 6 ]"
Gzhegow\Pipeline\Handler\Demo\Action\DemoExceptionAction::__invoke
"[ CATCH ]" | "Gzhegow\Pipeline\Exception\Runtime\PipelineException" | "Unhandled exception occured during processing pipeline"
"[ CATCH ]" | "Gzhegow\Pipeline\Exception\Exception" | "Hello, World!"
"[ RESULT ]" | { NULL }
""
HEREDOC
);

// >>> TEST
// > к любой цепочке можно подключить middleware
// > + они как фильтры, могут пропустить дальнейшие шаги в конвеере
// > + они как события, могут выполнить дополнительные действия или подготовить входные данные следуюших шагов
// > если необходимо, чтобы middleware оборачивал только некоторые действия, то их следует обернуть в отдельный Pipeline
$fn = function () use ($factory) {
    _dump_ln('[ TEST 7 ]');

    // > создаем конвеер
    $pipeline = \Gzhegow\Pipeline\Pipeline::pipeline();

    // > создаем посредник
    $middleware = $factory->newMiddleware(\Gzhegow\Pipeline\Handler\Demo\Middleware\Demo1stMiddleware::class);

    // > вызывать можно и статически, если перед этим сохранили фасад
    // $middleware = \Gzhegow\Pipeline\PipelineFacade::middleware(\Gzhegow\Pipeline\Handler\Demo\Middleware\Demo1stMiddleware::class);

    // > добавлять вложенные pipeline/middleware можно также используя синтаксис ->startX()/->endX()
    $middleware
        ->startMiddleware(\Gzhegow\Pipeline\Handler\Demo\Middleware\Demo2ndMiddleware::class)
        ->action(\Gzhegow\Pipeline\Handler\Demo\Action\Demo1stAction::class)
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
    _dump_ln('[ RESULT ]', $result);
    _dump('');
};
_assert_call($fn, [], <<<HEREDOC
"[ TEST 7 ]"
@before :: Gzhegow\Pipeline\Handler\Demo\Middleware\Demo1stMiddleware::__invoke
@before :: Gzhegow\Pipeline\Handler\Demo\Middleware\Demo2ndMiddleware::__invoke
Gzhegow\Pipeline\Handler\Demo\Action\Demo1stAction::__invoke
@after :: Gzhegow\Pipeline\Handler\Demo\Middleware\Demo2ndMiddleware::__invoke
@after :: Gzhegow\Pipeline\Handler\Demo\Middleware\Demo1stMiddleware::__invoke
"[ RESULT ]" | "Gzhegow\Pipeline\Handler\Demo\Action\Demo1stAction::__invoke result."
""
HEREDOC
);

// >>> TEST
// > middleware может предотвратить выполнение цепочки (то есть уже написанный код можно отменить фильтром, не редактируя его)
$fn = function () {
    _dump_ln('[ TEST 8 ]');

    // > создаем конвеер
    $pipeline = \Gzhegow\Pipeline\Pipeline::pipeline();

    // > добавляем действия в конвеер
    $pipeline
        ->startMiddleware(\Gzhegow\Pipeline\Handler\Demo\Middleware\Demo1stMiddleware::class)
        ->startMiddleware(\Gzhegow\Pipeline\Handler\Demo\Middleware\DemoOmitMiddleware::class)
        ->action(\Gzhegow\Pipeline\Handler\Demo\Action\Demo1stAction::class)
        ->action(\Gzhegow\Pipeline\Handler\Demo\Action\Demo2ndAction::class)
        ->action(\Gzhegow\Pipeline\Handler\Demo\Action\Demo3rdAction::class)
        ->action(\Gzhegow\Pipeline\Handler\Demo\Action\Demo4thAction::class)
        ->endMiddleware()
        ->endMiddleware()
    ;

    $myInput = null;
    $myContext = null;

    // > запускаем конвеер
    $result = \Gzhegow\Pipeline\Pipeline::run($pipeline, $myInput, $myContext);
    _dump_ln('[ RESULT ]', $result);
    _dump('');
};
_assert_call($fn, [], <<<HEREDOC
"[ TEST 8 ]"
@before :: Gzhegow\Pipeline\Handler\Demo\Middleware\Demo1stMiddleware::__invoke
@before :: Gzhegow\Pipeline\Handler\Demo\Middleware\DemoOmitMiddleware::__invoke
@after :: Gzhegow\Pipeline\Handler\Demo\Middleware\DemoOmitMiddleware::__invoke
@after :: Gzhegow\Pipeline\Handler\Demo\Middleware\Demo1stMiddleware::__invoke
"[ RESULT ]" | "Gzhegow\Pipeline\Handler\Demo\Middleware\DemoOmitMiddleware::__invoke result."
""
HEREDOC
);

// >>> TEST
// > цепочка может состоять даже из цепочек
$fn = function () {
    _dump_ln('[ TEST 9 ]');

    // > создаем дочерний конвеер
    $pipelineChild = \Gzhegow\Pipeline\Pipeline::pipeline();

    // > добавляем действия в конвеер
    $pipelineChild
        ->action(\Gzhegow\Pipeline\Handler\Demo\Action\DemoPassInputToResultAction::class)
        ->action(\Gzhegow\Pipeline\Handler\Demo\Action\DemoPassInputToResultAction::class)
    ;

    // > создаем родительский конвеер
    $pipeline = \Gzhegow\Pipeline\Pipeline::pipeline();

    // > добавляем действия (в том числе дочерние конвееры) в родительский конвеер
    // > при добавлении конвееров они будут склонированы, то есть один и тот же экземпляр может быть добавлен сколько угодно раз
    $pipeline
        ->pipeline($pipelineChild)                                           // этот конвеер просто передаст $result дальше
        ->action(\Gzhegow\Pipeline\Handler\Demo\Action\Demo1stAction::class) // на этом этапе результат будет заменен
        ->pipeline($pipelineChild)                                           // этот конвеер передаст измененный $result дальше
    ;

    $myInput = null;
    $myContext = null;

    // > запускаем конвеер
    $result = \Gzhegow\Pipeline\Pipeline::run($pipeline, $myInput, $myContext);
    _dump_ln('[ RESULT ]', $result);
    _dump('');
};
_assert_call($fn, [], <<<HEREDOC
"[ TEST 9 ]"
Gzhegow\Pipeline\Handler\Demo\Action\DemoPassInputToResultAction::__invoke
Gzhegow\Pipeline\Handler\Demo\Action\DemoPassInputToResultAction::__invoke
Gzhegow\Pipeline\Handler\Demo\Action\Demo1stAction::__invoke
Gzhegow\Pipeline\Handler\Demo\Action\DemoPassInputToResultAction::__invoke
Gzhegow\Pipeline\Handler\Demo\Action\DemoPassInputToResultAction::__invoke
"[ RESULT ]" | "Gzhegow\Pipeline\Handler\Demo\Action\Demo1stAction::__invoke result."
""
HEREDOC
);

// >>> TEST
// > может состоять из middleware вложенных друг в друга
$fn = function () {
    _dump_ln('[ TEST 10 ]');

    // > добавляем действия (в том числе дочерние конвееры) в родительский конвеер
    $pipeline = \Gzhegow\Pipeline\Pipeline::pipeline()
        ->startMiddleware(\Gzhegow\Pipeline\Handler\Demo\Middleware\Demo1stMiddleware::class)
        ->startMiddleware(\Gzhegow\Pipeline\Handler\Demo\Middleware\Demo2ndMiddleware::class)
        ->action(\Gzhegow\Pipeline\Handler\Demo\Action\Demo1stAction::class)
        ->action(\Gzhegow\Pipeline\Handler\Demo\Action\Demo2ndAction::class)
        ->endMiddleware()
        ->endMiddleware()
    ;

    $myInput = null;
    $myContext = null;

    // > запускаем конвеер
    $result = \Gzhegow\Pipeline\Pipeline::run($pipeline, $myInput, $myContext);
    _dump_ln('[ RESULT ]', $result);
    _dump('');
};
_assert_call($fn, [], <<<HEREDOC
"[ TEST 10 ]"
@before :: Gzhegow\Pipeline\Handler\Demo\Middleware\Demo1stMiddleware::__invoke
@before :: Gzhegow\Pipeline\Handler\Demo\Middleware\Demo2ndMiddleware::__invoke
Gzhegow\Pipeline\Handler\Demo\Action\Demo1stAction::__invoke
Gzhegow\Pipeline\Handler\Demo\Action\Demo2ndAction::__invoke
@after :: Gzhegow\Pipeline\Handler\Demo\Middleware\Demo2ndMiddleware::__invoke
@after :: Gzhegow\Pipeline\Handler\Demo\Middleware\Demo1stMiddleware::__invoke
"[ RESULT ]" | "Gzhegow\Pipeline\Handler\Demo\Action\Demo2ndAction::__invoke result."
""
HEREDOC
);

// >>> TEST
// > что не отменяет возможности, что в одном из действий произойдет ошибка, которая должна быть поймана, а цепочка - продолжиться
$fn = function () {
    _dump_ln('[ TEST 11 ]');

    // > добавляем действия (в том числе дочерние конвееры) в родительский конвеер
    $pipeline = \Gzhegow\Pipeline\Pipeline::pipeline()
        ->startMiddleware(\Gzhegow\Pipeline\Handler\Demo\Middleware\Demo1stMiddleware::class)
        ->startMiddleware(\Gzhegow\Pipeline\Handler\Demo\Middleware\Demo2ndMiddleware::class)
        ->action(\Gzhegow\Pipeline\Handler\Demo\Action\DemoLogicExceptionAction::class)
        ->fallback(\Gzhegow\Pipeline\Handler\Demo\Fallback\DemoRuntimeExceptionFallback::class)
        ->fallback(\Gzhegow\Pipeline\Handler\Demo\Fallback\DemoLogicExceptionFallback::class)
        ->fallback(\Gzhegow\Pipeline\Handler\Demo\Fallback\DemoThrowableFallback::class)
        ->endMiddleware()
        ->endMiddleware()
    ;

    $myInput = null;
    $myContext = null;

    // > запускаем конвеер
    $result = \Gzhegow\Pipeline\Pipeline::run($pipeline, $myInput, $myContext);
    _dump_ln('[ RESULT ]', $result);
    _dump('');
};
_assert_call($fn, [], <<<HEREDOC
"[ TEST 11 ]"
@before :: Gzhegow\Pipeline\Handler\Demo\Middleware\Demo1stMiddleware::__invoke
@before :: Gzhegow\Pipeline\Handler\Demo\Middleware\Demo2ndMiddleware::__invoke
Gzhegow\Pipeline\Handler\Demo\Action\DemoLogicExceptionAction::__invoke
Gzhegow\Pipeline\Handler\Demo\Fallback\DemoLogicExceptionFallback::__invoke
@after :: Gzhegow\Pipeline\Handler\Demo\Middleware\Demo2ndMiddleware::__invoke
@after :: Gzhegow\Pipeline\Handler\Demo\Middleware\Demo1stMiddleware::__invoke
"[ RESULT ]" | "Gzhegow\Pipeline\Handler\Demo\Fallback\DemoLogicExceptionFallback::__invoke result."
""
HEREDOC
);

// >>> TEST
// > а вообще, даже из цепочек-в-цепочках может состоять
// > вообще, этот конструктор нужен, чтобы ограничивать действие middleware только на несколько действий, а не на все
$fn = function () {
    _dump_ln('[ TEST 12 ]');

    // > добавляем действия в конвеер 2 уровня
    $middleware2nd = \Gzhegow\Pipeline\Pipeline::middleware(\Gzhegow\Pipeline\Handler\Demo\Middleware\Demo2ndMiddleware::class)
        ->action(\Gzhegow\Pipeline\Handler\Demo\Action\Demo3rdAction::class)
        ->action(\Gzhegow\Pipeline\Handler\Demo\Action\Demo4thAction::class)
    ;

    $pipeline2nd = \Gzhegow\Pipeline\Pipeline::pipeline()
        ->middleware($middleware2nd)
    ;

    // > добавляем действия (в том числе дочерние конвееры) в конвеер 1 уровня
    $middleware1st = \Gzhegow\Pipeline\Pipeline::middleware(\Gzhegow\Pipeline\Handler\Demo\Middleware\Demo1stMiddleware::class)
        ->action(\Gzhegow\Pipeline\Handler\Demo\Action\Demo1stAction::class)
        ->pipeline($pipeline2nd)
        ->action(\Gzhegow\Pipeline\Handler\Demo\Action\Demo2ndAction::class)
    ;

    $pipeline1st = \Gzhegow\Pipeline\Pipeline::pipeline()
        ->middleware($middleware1st)
    ;

    // > добавляем действия (в том числе дочерние конвееры) в родительский конвеер
    $pipeline = \Gzhegow\Pipeline\Pipeline::pipeline()
        ->action(\Gzhegow\Pipeline\Handler\Demo\Action\DemoPassInputToResultAction::class)
        ->pipeline($pipeline1st)
        ->action(\Gzhegow\Pipeline\Handler\Demo\Action\DemoPassInputToResultAction::class)
        ->action(\Gzhegow\Pipeline\Handler\Demo\Action\DemoPassInputToResultAction::class)
        ->pipeline($pipeline1st)
        ->action(\Gzhegow\Pipeline\Handler\Demo\Action\DemoPassInputToResultAction::class)
        ->action(\Gzhegow\Pipeline\Handler\Demo\Action\DemoPassInputToResultAction::class)
        ->pipeline($pipeline1st)
        ->action(\Gzhegow\Pipeline\Handler\Demo\Action\DemoPassInputToResultAction::class)
    ;

    $myInput = null;
    $myContext = null;

    // > запускаем конвеер
    $result = \Gzhegow\Pipeline\Pipeline::run($pipeline, $myInput, $myContext);
    _dump_ln('[ RESULT ]', $result);
    _dump('');
};
_assert_call($fn, [], <<<HEREDOC
"[ TEST 12 ]"
Gzhegow\Pipeline\Handler\Demo\Action\DemoPassInputToResultAction::__invoke
@before :: Gzhegow\Pipeline\Handler\Demo\Middleware\Demo1stMiddleware::__invoke
Gzhegow\Pipeline\Handler\Demo\Action\Demo1stAction::__invoke
@before :: Gzhegow\Pipeline\Handler\Demo\Middleware\Demo2ndMiddleware::__invoke
Gzhegow\Pipeline\Handler\Demo\Action\Demo3rdAction::__invoke
Gzhegow\Pipeline\Handler\Demo\Action\Demo4thAction::__invoke
@after :: Gzhegow\Pipeline\Handler\Demo\Middleware\Demo2ndMiddleware::__invoke
Gzhegow\Pipeline\Handler\Demo\Action\Demo2ndAction::__invoke
@after :: Gzhegow\Pipeline\Handler\Demo\Middleware\Demo1stMiddleware::__invoke
Gzhegow\Pipeline\Handler\Demo\Action\DemoPassInputToResultAction::__invoke
Gzhegow\Pipeline\Handler\Demo\Action\DemoPassInputToResultAction::__invoke
@before :: Gzhegow\Pipeline\Handler\Demo\Middleware\Demo1stMiddleware::__invoke
Gzhegow\Pipeline\Handler\Demo\Action\Demo1stAction::__invoke
@before :: Gzhegow\Pipeline\Handler\Demo\Middleware\Demo2ndMiddleware::__invoke
Gzhegow\Pipeline\Handler\Demo\Action\Demo3rdAction::__invoke
Gzhegow\Pipeline\Handler\Demo\Action\Demo4thAction::__invoke
@after :: Gzhegow\Pipeline\Handler\Demo\Middleware\Demo2ndMiddleware::__invoke
Gzhegow\Pipeline\Handler\Demo\Action\Demo2ndAction::__invoke
@after :: Gzhegow\Pipeline\Handler\Demo\Middleware\Demo1stMiddleware::__invoke
Gzhegow\Pipeline\Handler\Demo\Action\DemoPassInputToResultAction::__invoke
Gzhegow\Pipeline\Handler\Demo\Action\DemoPassInputToResultAction::__invoke
@before :: Gzhegow\Pipeline\Handler\Demo\Middleware\Demo1stMiddleware::__invoke
Gzhegow\Pipeline\Handler\Demo\Action\Demo1stAction::__invoke
@before :: Gzhegow\Pipeline\Handler\Demo\Middleware\Demo2ndMiddleware::__invoke
Gzhegow\Pipeline\Handler\Demo\Action\Demo3rdAction::__invoke
Gzhegow\Pipeline\Handler\Demo\Action\Demo4thAction::__invoke
@after :: Gzhegow\Pipeline\Handler\Demo\Middleware\Demo2ndMiddleware::__invoke
Gzhegow\Pipeline\Handler\Demo\Action\Demo2ndAction::__invoke
@after :: Gzhegow\Pipeline\Handler\Demo\Middleware\Demo1stMiddleware::__invoke
Gzhegow\Pipeline\Handler\Demo\Action\DemoPassInputToResultAction::__invoke
"[ RESULT ]" | "Gzhegow\Pipeline\Handler\Demo\Action\Demo2ndAction::__invoke result."
""
HEREDOC
);
