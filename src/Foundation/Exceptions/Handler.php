<?php

namespace Javaabu\Jaravel\Foundation\Exceptions;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Illuminate\Support\Facades\Request;

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
        if ($this->isAdminPortal()) {
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

    protected function isAdminPortal(): bool
    {
        $host = Request::getHost();

        // get the main url
        $parse = parse_url(config('app.url'));
        $primary_domain = str_ireplace('www.', '', $parse['host']);

        // get the admin domain
        $admin_domain = config('app.admin_domain');

        if ($primary_domain != $admin_domain) {
            if ($host == $admin_domain) {
                return true;
            }
        } elseif ($admin_prefix = config('app.admin_prefix')) {
            // Get the current URL
            $url = Request::url();

            // Parse the URL
            $parsed_url = parse_url($url);

            // Get the path from the parsed URL
            $path = $parsed_url['path'] ?? '';

            // Get the first part of the path
            $first_part = trim(explode('/', $path)[1] ?? '', '/');

            if ($first_part == $admin_prefix) {
                return true;
            }
        }

        return false;
    }
}
