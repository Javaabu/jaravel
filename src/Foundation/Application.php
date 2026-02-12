<?php

namespace Javaabu\Jaravel\Foundation;

use Illuminate\Foundation\Application as BaseApplication;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Http\Request;
use Throwable;

class Application extends BaseApplication
{
    public static function configure(?string $basePath = null)
    {
        $basePath = match (true) {
            is_string($basePath) => $basePath,
            default => static::inferBasePath(),
        };

        return (new ApplicationBuilder(new static($basePath)))
            ->withKernels()
            ->withEvents()
            ->withCommands()
            ->withProviders()
            ->withExceptions(function (Exceptions $exceptions) {
                $exceptions->shouldRenderJsonWhen(function (Request $request, Throwable $e) {
                    if ($request->is(config('app.api_prefix').'/*')) {
                        return true;
                    }

                    return $request->expectsJson();
                });
            });
    }
}
