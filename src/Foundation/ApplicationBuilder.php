<?php

namespace Javaabu\Jaravel\Foundation;

use Closure;
use Illuminate\Contracts\Http\Kernel as HttpKernel;
use Illuminate\Foundation\Configuration\ApplicationBuilder as BaseApplicationBuilder;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Foundation\Events\DiagnosingHealth;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Javaabu\Jaravel\Foundation\Exceptions\Handler;
use Laravel\Folio\Folio;

class ApplicationBuilder extends BaseApplicationBuilder
{
    /**
     * Add as('api.') to api routes
     */
    protected function buildRoutingCallback(
        array|string|null $web,
        array|string|null $api,
        ?string           $pages,
        ?string           $health,
        string            $apiPrefix,
        ?callable         $then
    ): Closure
    {
        return function () use ($web, $api, $pages, $health, $apiPrefix, $then) {
            if (is_string($api) || is_array($api)) {
                if (is_array($api)) {
                    foreach ($api as $apiRoute) {
                        if (realpath($apiRoute) !== false) {
                            Route::middleware('api')->as('api.')->prefix($apiPrefix)->group($apiRoute);
                        }
                    }
                } else {
                    Route::middleware('api')->as('api.')->prefix($apiPrefix)->group($api);
                }
            }

            if (is_string($health)) {
                Route::get($health, function () {
                    $exception = null;

                    try {
                        Event::dispatch(new DiagnosingHealth);
                    } catch (\Throwable $e) {
                        if (app()->hasDebugModeEnabled()) {
                            throw $e;
                        }

                        report($e);

                        $exception = $e->getMessage();
                    }

                    return response(View::file(__DIR__.'/../../resources/health-up.blade.php', [
                        'exception' => $exception,
                    ]), status: $exception ? 500 : 200);
                });
            }

            if (is_string($web) || is_array($web)) {
                if (is_array($web)) {
                    foreach ($web as $webRoute) {
                        if (realpath($webRoute) !== false) {
                            Route::middleware('web')->group($webRoute);
                        }
                    }
                } else {
                    Route::middleware('web')->group($web);
                }
            }

            foreach ($this->additionalRoutingCallbacks as $callback) {
                $callback();
            }

            if (is_string($pages) &&
                realpath($pages) !== false &&
                class_exists(Folio::class)) {
                Folio::route($pages, middleware: $this->pageMiddleware);
            }

            if (is_callable($then)) {
                $then($this->app);
            }
        };
    }

    /**
     * Removing redirect guests to route('login')
     */
    public function withMiddleware(?callable $callback = null)
    {
        $this->app->afterResolving(HttpKernel::class, function ($kernel) use ($callback) {
            $middleware = (new Middleware);


            if (!is_null($callback)) {
                $callback($middleware);
            }

            $this->pageMiddleware = $middleware->getPageMiddleware();
            $kernel->setGlobalMiddleware($middleware->getGlobalMiddleware());
            $kernel->setMiddlewareGroups($middleware->getMiddlewareGroups());
            $kernel->setMiddlewareAliases($middleware->getMiddlewareAliases());

            if ($priorities = $middleware->getMiddlewarePriority()) {
                $kernel->setMiddlewarePriority($priorities);
            }

            if ($priorityAppends = $middleware->getMiddlewarePriorityAppends()) {
                foreach ($priorityAppends as $newMiddleware => $after) {
                    $kernel->addToMiddlewarePriorityAfter($after, $newMiddleware);
                }
            }

            if ($priorityPrepends = $middleware->getMiddlewarePriorityPrepends()) {
                foreach ($priorityPrepends as $newMiddleware => $before) {
                    $kernel->addToMiddlewarePriorityBefore($before, $newMiddleware);
                }
            }
        });

        return $this;
    }


    /**
     * Using our custom Exception handler
     */
    public function withExceptions(?callable $using = null)
    {
        $this->app->singleton(
            \Illuminate\Contracts\Debug\ExceptionHandler::class,
            Handler::class
        );

        $using ??= fn() => true;

        $this->app->afterResolving(
            Handler::class,
            fn($handler) => $using(new Exceptions($handler)),
        );

        return $this;
    }
}
