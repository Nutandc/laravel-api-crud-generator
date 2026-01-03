<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

final class CrudGeneratorTest extends TestCase
{
    public function testItGeneratesCrudFiles(): void
    {
        $base = storage_path('app/crud-test');
        File::deleteDirectory($base);

        config([
            'api-crud-generator.paths.model' => $base . '/Models',
            'api-crud-generator.paths.controller' => $base . '/Http/Controllers/Api',
            'api-crud-generator.paths.request' => $base . '/Http/Requests',
            'api-crud-generator.paths.resource' => $base . '/Http/Resources',
            'api-crud-generator.paths.migration' => $base . '/database/migrations',
            'api-crud-generator.paths.repository' => $base . '/Repositories',
            'api-crud-generator.paths.service' => $base . '/Services',
            'api-crud-generator.route.file' => $base . '/routes/api.php',
        ]);

        File::ensureDirectoryExists($base . '/routes');
        File::put($base . '/routes/api.php', "<?php\n\nuse Illuminate\\Support\\Facades\\Route;\n");

        $this->artisan('crud:api', [
            'name' => 'Post',
            '--fields' => 'title,body:text,is_active:boolean',
        ])->assertExitCode(0);

        $this->assertTrue(File::exists($base . '/Models/Post.php'));
        $this->assertTrue(File::exists($base . '/Http/Controllers/Api/PostController.php'));
        $this->assertTrue(File::exists($base . '/Http/Requests/PostRequest.php'));
        $this->assertTrue(File::exists($base . '/Http/Resources/PostResource.php'));
        $this->assertTrue(File::exists($base . '/Repositories/PostRepository.php'));
        $this->assertFalse(File::exists($base . '/Services/PostService.php'));

        $migrations = File::glob($base . '/database/migrations/*_create_posts_table.php');
        $this->assertNotEmpty($migrations);

        $routeContents = File::get($base . '/routes/api.php');
        $this->assertStringContainsString("Route::apiResource('posts', \\App\\Http\\Controllers\\Api\\PostController::class);", $routeContents);
    }

    public function testItGeneratesServiceWithoutRepository(): void
    {
        $base = storage_path('app/crud-test-service');
        File::deleteDirectory($base);

        config([
            'api-crud-generator.paths.model' => $base . '/Models',
            'api-crud-generator.paths.controller' => $base . '/Http/Controllers/Api',
            'api-crud-generator.paths.request' => $base . '/Http/Requests',
            'api-crud-generator.paths.resource' => $base . '/Http/Resources',
            'api-crud-generator.paths.migration' => $base . '/database/migrations',
            'api-crud-generator.paths.repository' => $base . '/Repositories',
            'api-crud-generator.paths.service' => $base . '/Services',
            'api-crud-generator.route.file' => $base . '/routes/api.php',
        ]);

        File::ensureDirectoryExists($base . '/routes');
        File::put($base . '/routes/api.php', "<?php\n\nuse Illuminate\\Support\\Facades\\Route;\n");

        $this->artisan('crud:api', [
            'name' => 'Order',
            '--fields' => '!total:decimal',
            '--service' => true,
            '--no-repo' => true,
        ])->assertExitCode(0);

        $this->assertTrue(File::exists($base . '/Services/OrderService.php'));
        $this->assertFalse(File::exists($base . '/Repositories/OrderRepository.php'));
        $this->assertTrue(File::exists($base . '/Http/Controllers/Api/OrderController.php'));
    }
}
