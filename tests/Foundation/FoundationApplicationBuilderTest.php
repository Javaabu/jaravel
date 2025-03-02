<?php

namespace Javaabu\Jaravel\Tests\Foundation;

use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Javaabu\Jaravel\Foundation\Application;
use Mockery as m;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class FoundationApplicationBuilderTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();

        unset($_ENV['APP_BASE_PATH']);

        unset($_ENV['LARAVEL_STORAGE_PATH'], $_SERVER['LARAVEL_STORAGE_PATH']);

        parent::tearDown();
    }

    #[Test]
    public function it_can_create_an_application()
    {
        $app = Application::configure()->create();
        $this->assertInstanceOf(Application::class, $app);
    }

    #[Test]
    public function it_can_create_an_application_with_routing_callback()
    {
        $app = Application::configure()
            ->withRouting(
                web: __DIR__.'/../routes/web_routes.php',
                api: __DIR__.'/../routes/api.php',
                commands: __DIR__.'/../routes/console.php',
                health: '/up',
                apiPrefix: 'api/v1',
            )
            ->create();
        $this->assertInstanceOf(Application::class, $app);
    }

    #[Test]
    public function it_can_create_an_application_with_middleware()
    {
        $app = Application::configure()
            ->withMiddleware(function (Middleware $middleware) {
                $middleware->validateCsrfTokens(except: [
                    '/oauth/*/callback',
                ]);
            })
            ->create();

        $this->assertInstanceOf(Application::class, $app);
    }

    #[Test]
    public function it_can_create_an_application_with_exceptions()
    {
        $app = Application::configure()
            ->withExceptions(function (Exceptions $exceptions) {
                $exceptions->shouldRenderJsonWhen(function (Request $request, Throwable $e) {
                    if ($request->is('api/*')) {
                        return true;
                    }

                    return $request->expectsJson();
                });
            })
            ->create();

        $this->assertInstanceOf(Application::class, $app);
    }
}
