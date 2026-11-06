<?php



use Illuminate\Foundation\Application;

use Illuminate\Foundation\Configuration\Exceptions;

use Illuminate\Foundation\Configuration\Middleware;



return Application::configure(basePath: dirname(__DIR__))

    ->withRouting(

        web: __DIR__.'/../routes/web.php',

        commands: __DIR__.'/../routes/console.php',

        health: '/up',

    )

    ->withMiddleware(function (Middleware $middleware): void {

        $middleware->web(append: [

            \App\Http\Middleware\SetLocale::class,
            \App\Http\Middleware\EnsureUserIsNotBlocked::class,
            \App\Http\Middleware\TrackLastActivity::class,
            \App\Http\Middleware\EnsurePasswordChanged::class,
            \App\Http\Middleware\EnsureFarmWritable::class,
            \App\Http\Middleware\RunDailyTasks::class,

        ]);

        $middleware->alias([
            'admin' => \App\Http\Middleware\EnsureUserIsAdmin::class,
            'farm' => \App\Http\Middleware\EnsureCurrentFarm::class,
        ]);

        $middleware->validateCsrfTokens(except: [

            'telegram/webhook',

        ]);

    })

    ->withExceptions(function (Exceptions $exceptions): void {

        // 419 (CSRF token eskirgan): Laravel uni HttpException(419) ga aylantiradi, asl sabab — previous
        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\HttpException $e, \Illuminate\Http\Request $request) {
            if ($e->getStatusCode() === 419 && $e->getPrevious() instanceof \Illuminate\Session\TokenMismatchException) {
                return \App\Support\PageExpired::respond($request);
            }

            return null;
        });

    })->create();

