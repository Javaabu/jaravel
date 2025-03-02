<?php

namespace Javaabu\Jaravel\Foundation\Exceptions;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class Handler extends ExceptionHandler
{
    protected function unauthenticated($request, AuthenticationException $exception)
    {
        return $this->shouldReturnJson($request, $exception)
            ? response()->json(['message' => $exception->getMessage()], 401)
            : redirect()->guest($exception->redirectTo($request) ?? $this->getGuestRedirectPath($exception));
    }

    protected function getGuestRedirectPath(AuthenticationException $exception)
    {
        $guard = $exception->guards()[0] ?? null;

        $provider = config("auth.guards.{$guard}.provider");
        $model = config("auth.providers.{$provider}.model");

        if ($model) {
            return with(new $model)->loginUrl();
        }

        return '/login';
    }

    protected function getHttpExceptionView(HttpExceptionInterface $e)
    {
        if (current_portal() == 'admin') {
            return parent::getHttpExceptionView($e);
        }

        $view = 'web.errors.'.$e->getStatusCode();

        if (view()->exists($view)) {
            return $view;
        }

        $view = substr($view, 0, -2).'xx';

        if (view()->exists($view)) {
            return $view;
        }

        return parent::getHttpExceptionView($e);
    }
}
