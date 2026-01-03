<?php

declare(strict_types=1);

namespace Nutandc\ApiCrud\Providers;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\ServiceProvider;
use Nutandc\ApiCrud\Commands\MakeCrudApiCommand;
use Nutandc\ApiCrud\Generators\CrudGenerator;
use Nutandc\ApiCrud\Support\StubPathResolver;

final class ApiCrudGeneratorServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/api-crud-generator.php', 'api-crud-generator');

        $this->app->singleton(StubPathResolver::class, function ($app): StubPathResolver {
            return new StubPathResolver(
                $app->make(Filesystem::class),
                __DIR__ . '/../../stubs',
                (string) data_get($app['config']->get('api-crud-generator', []), 'stubs.publish_path', ''),
            );
        });

        $this->app->singleton(CrudGenerator::class, function ($app): CrudGenerator {
            return new CrudGenerator(
                $app->make(Filesystem::class),
                $app->make(StubPathResolver::class),
                (array) $app['config']->get('api-crud-generator', []),
            );
        });

        $this->app->singleton(MakeCrudApiCommand::class, function ($app): MakeCrudApiCommand {
            return new MakeCrudApiCommand($app->make(CrudGenerator::class));
        });
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../../config/api-crud-generator.php' => config_path('api-crud-generator.php'),
        ], 'api-crud-generator-config');

        $this->publishes([
            __DIR__ . '/../../stubs' => resource_path('stubs/api-crud-generator'),
        ], 'api-crud-generator-stubs');

        if ($this->app->runningInConsole()) {
            $this->commands([
                MakeCrudApiCommand::class,
            ]);
        }
    }
}
