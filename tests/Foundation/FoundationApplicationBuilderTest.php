<?php

namespace Javaabu\Jaravel\Tests\Foundation;

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
}
