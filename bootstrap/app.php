<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Kernel as HttpKernel;
use App\Console\Kernel as ConsoleKernel; // Ensure this class exists in the correct namespace

$app = Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Add global middleware if needed
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Configure exception handling
    })
    ->create();

// Bind the Kernel files
$app->singleton(
    Illuminate\Contracts\Http\Kernel::class,
    HttpKernel::class
);

$app->singleton(
    Illuminate\Contracts\Console\Kernel::class,
    ConsoleKernel::class
);

$app->singleton(
    Illuminate\Contracts\Debug\ExceptionHandler::class,
    App\Exceptions\Handler::class
);

return $app;
