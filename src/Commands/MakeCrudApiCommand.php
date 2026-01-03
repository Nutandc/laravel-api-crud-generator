<?php

declare(strict_types=1);

namespace Nutandc\ApiCrud\Commands;

use Illuminate\Console\Command;
use Nutandc\ApiCrud\Generators\CrudGenerator;

final class MakeCrudApiCommand extends Command
{
    protected $signature = 'crud:api
        {name : Model class name in singular form}
        {--fields= : Comma-separated fields. Example: name,email,age:integer,is_active:boolean}
        {--force : Overwrite existing files}
        {--repo : Force repository pattern}
        {--no-repo : Disable repository pattern}
        {--service : Force service pattern}
        {--no-service : Disable service pattern}
        {--no-route : Do not append the apiResource route}
        {--no-migration : Skip migration generation}
        {--no-request : Skip request generation}
        {--no-resource : Skip resource generation}
        {--no-controller : Skip controller generation}
        {--no-model : Skip model generation}';

    protected $description = 'Generate API CRUD files (model, controller, request, resource, migration, and route)';

    public function __construct(private readonly CrudGenerator $generator)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $name = $this->stringArgument('name');
        $fields = $this->stringOption('fields');
        $force = (bool) $this->option('force');

        if ($fields === '') {
            $answer = $this->ask('Enter fields (comma-separated). Example: name,email,age:integer');
            $fields = is_string($answer) ? $answer : '';
        }

        $options = [
            'route' => ! (bool) $this->option('no-route'),
            'migration' => ! (bool) $this->option('no-migration'),
            'request' => ! (bool) $this->option('no-request'),
            'resource' => ! (bool) $this->option('no-resource'),
            'controller' => ! (bool) $this->option('no-controller'),
            'model' => ! (bool) $this->option('no-model'),
            'repository' => $this->resolveFlag('repo', 'no-repo', (bool) config('api-crud-generator.repository.enabled', true)),
            'service' => $this->resolveFlag('service', 'no-service', (bool) config('api-crud-generator.service.enabled', false)),
        ];

        $result = $this->generator->generate($name, $fields, $force, $options);

        foreach ($result->messages as $message) {
            $this->components->info($message);
        }

        foreach ($result->warnings as $warning) {
            $this->components->warn($warning);
        }

        return self::SUCCESS;
    }

    private function stringArgument(string $key): string
    {
        $value = $this->argument($key);
        if (is_array($value)) {
            return (string) ($value[0] ?? '');
        }

        return is_string($value) ? $value : '';
    }

    private function stringOption(string $key): string
    {
        $value = $this->option($key);
        if (is_array($value)) {
            return implode(',', array_map('strval', $value));
        }

        return is_string($value) ? $value : '';
    }

    private function resolveFlag(string $on, string $off, bool $default): bool
    {
        if ((bool) $this->option($on)) {
            return true;
        }

        if ((bool) $this->option($off)) {
            return false;
        }

        return $default;
    }
}
