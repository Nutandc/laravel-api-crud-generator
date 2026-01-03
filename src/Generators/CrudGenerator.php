<?php

declare(strict_types=1);

namespace Nutandc\ApiCrud\Generators;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use Nutandc\ApiCrud\Support\FieldDefinition;
use Nutandc\ApiCrud\Support\GenerationResult;
use Nutandc\ApiCrud\Support\StubPathResolver;

final class CrudGenerator
{
    /** @var array<string, mixed> */
    private array $config;

    public function __construct(
        private readonly Filesystem $files,
        private readonly StubPathResolver $stubResolver,
        array $config,
    ) {
        $this->config = $config;
    }

    /**
     * @param array{route: bool, migration: bool, request: bool, resource: bool, controller: bool, model: bool, repository: bool, service: bool} $options
     */
    public function generate(string $name, string $fields, bool $force, array $options): GenerationResult
    {
        $result = new GenerationResult();

        $className = Str::studly($name);
        if ($className === '') {
            $result->addWarning('Model name is required.');
            return $result;
        }

        $definitions = $this->parseFields($fields);

        if ($options['migration']) {
            $this->generateMigration($className, $definitions, $force, $result);
        }

        if ($options['model']) {
            $this->generateModel($className, $definitions, $force, $result);
        }

        if ($options['request']) {
            $this->generateRequest($className, $definitions, $force, $result);
        }

        if ($options['resource']) {
            $this->generateResource($className, $definitions, $force, $result);
        }

        if ($options['repository']) {
            $this->generateRepository($className, $force, $result);
        }

        if ($options['service']) {
            $this->generateService($className, $force, $options['repository'], $result);
        }

        if ($options['controller']) {
            $this->generateController($className, $force, $options['repository'], $options['service'], $result);
        }

        if ($options['route']) {
            $this->appendRoute($className, $result);
        }

        return $result;
    }

    /**
     * @param FieldDefinition[] $definitions
     */
    private function generateMigration(string $className, array $definitions, bool $force, GenerationResult $result): void
    {
        $table = Str::snake(Str::plural($className));
        $columns = $this->buildMigrationColumns($definitions);

        $stub = $this->stubResolver->getStub('migration');
        $content = $this->render($stub, [
            'table' => $table,
            'columns' => $columns,
        ]);

        $filename = date('Y_m_d_His') . '_create_' . $table . '_table.php';
        $path = $this->path('migration', $filename);

        $this->writeFile($path, $content, $force, $result, 'Migration');
    }

    /**
     * @param FieldDefinition[] $definitions
     */
    private function generateModel(string $className, array $definitions, bool $force, GenerationResult $result): void
    {
        $fillable = $this->buildFillable($definitions);
        $namespace = (string) data_get($this->config, 'namespaces.model', 'App\\Models');

        $stub = $this->stubResolver->getStub('model');
        $content = $this->render($stub, [
            'namespace' => $namespace,
            'class' => $className,
            'fillable' => $fillable,
        ]);

        $path = $this->path('model', $className . '.php');
        $this->writeFile($path, $content, $force, $result, 'Model');
    }

    /**
     * @param FieldDefinition[] $definitions
     */
    private function generateRequest(string $className, array $definitions, bool $force, GenerationResult $result): void
    {
        $namespace = (string) data_get($this->config, 'namespaces.request', 'App\\Http\\Requests');
        $rules = $this->buildRules($definitions);

        $stub = $this->stubResolver->getStub('request');
        $content = $this->render($stub, [
            'namespace' => $namespace,
            'class' => $className . 'Request',
            'rules' => $rules,
        ]);

        $path = $this->path('request', $className . 'Request.php');
        $this->writeFile($path, $content, $force, $result, 'Request');
    }

    /**
     * @param FieldDefinition[] $definitions
     */
    private function generateResource(string $className, array $definitions, bool $force, GenerationResult $result): void
    {
        $namespace = (string) data_get($this->config, 'namespaces.resource', 'App\\Http\\Resources');
        $modelNamespace = (string) data_get($this->config, 'namespaces.model', 'App\\Models');
        $includeId = (bool) data_get($this->config, 'resource.include_id', true);
        $includeTimestamps = (bool) data_get($this->config, 'resource.include_timestamps', false);

        $fields = $this->buildResourceFields($definitions, $includeId, $includeTimestamps);

        $stub = $this->stubResolver->getStub('resource');
        $content = $this->render($stub, [
            'namespace' => $namespace,
            'class' => $className . 'Resource',
            'modelNamespace' => $modelNamespace,
            'modelClass' => $className,
            'fields' => $fields,
        ]);

        $path = $this->path('resource', $className . 'Resource.php');
        $this->writeFile($path, $content, $force, $result, 'Resource');
    }

    /**
     * @param FieldDefinition[] $definitions
     */
    private function generateController(string $className, bool $force, bool $useRepository, bool $useService, GenerationResult $result): void
    {
        $namespace = (string) data_get($this->config, 'namespaces.controller', 'App\\Http\\Controllers\\Api');
        $modelNamespace = (string) data_get($this->config, 'namespaces.model', 'App\\Models');
        $requestNamespace = (string) data_get($this->config, 'namespaces.request', 'App\\Http\\Requests');
        $resourceNamespace = (string) data_get($this->config, 'namespaces.resource', 'App\\Http\\Resources');
        $baseController = (string) data_get($this->config, 'base_controller', 'Nutandc\\ApiCrud\\Http\\Controllers\\ApiController');
        $repositoryNamespace = (string) data_get($this->config, 'namespaces.repository', 'App\\Repositories');
        $serviceNamespace = (string) data_get($this->config, 'namespaces.service', 'App\\Services');
        $baseControllerShort = class_basename($baseController);
        $repositoryClass = $repositoryNamespace . '\\' . $className . 'Repository';
        $repositoryShort = class_basename($repositoryClass);
        $serviceClass = $serviceNamespace . '\\' . $className . 'Service';
        $serviceShort = class_basename($serviceClass);

        $stub = $this->stubResolver->getStub($this->controllerStubName($useService, $useRepository));
        $content = $this->render($stub, [
            'namespace' => $namespace,
            'class' => $className . 'Controller',
            'modelNamespace' => $modelNamespace,
            'modelClass' => $className,
            'requestNamespace' => $requestNamespace,
            'requestClass' => $className . 'Request',
            'resourceNamespace' => $resourceNamespace,
            'resourceClass' => $className . 'Resource',
            'baseController' => $baseController,
            'baseControllerShort' => $baseControllerShort,
            'repositoryClass' => $repositoryClass,
            'repositoryShort' => $repositoryShort,
            'serviceClass' => $serviceClass,
            'serviceShort' => $serviceShort,
            'modelVar' => Str::camel($className),
            'modelVarPlural' => Str::camel(Str::plural($className)),
        ]);

        $path = $this->path('controller', $className . 'Controller.php');
        $this->writeFile($path, $content, $force, $result, 'Controller');
    }

    private function generateRepository(string $className, bool $force, GenerationResult $result): void
    {
        $namespace = (string) data_get($this->config, 'namespaces.repository', 'App\\Repositories');
        $modelNamespace = (string) data_get($this->config, 'namespaces.model', 'App\\Models');
        $baseRepository = (string) data_get($this->config, 'repository.base', 'Nutandc\\ApiCrud\\Repositories\\BaseRepository');
        $baseRepositoryShort = class_basename($baseRepository);

        $stub = $this->stubResolver->getStub('repository');
        $content = $this->render($stub, [
            'namespace' => $namespace,
            'class' => $className . 'Repository',
            'modelNamespace' => $modelNamespace,
            'modelClass' => $className,
            'baseRepository' => $baseRepository,
            'baseRepositoryShort' => $baseRepositoryShort,
        ]);

        $path = $this->path('repository', $className . 'Repository.php');
        $this->writeFile($path, $content, $force, $result, 'Repository');
    }

    private function generateService(string $className, bool $force, bool $useRepository, GenerationResult $result): void
    {
        $namespace = (string) data_get($this->config, 'namespaces.service', 'App\\Services');
        $modelNamespace = (string) data_get($this->config, 'namespaces.model', 'App\\Models');
        $repositoryNamespace = (string) data_get($this->config, 'namespaces.repository', 'App\\Repositories');

        $repositoryClass = $repositoryNamespace . '\\' . $className . 'Repository';
        $repositoryShort = class_basename($repositoryClass);

        $stubName = $useRepository ? 'service.repository' : 'service.simple';
        $stub = $this->stubResolver->getStub($stubName);
        $content = $this->render($stub, [
            'namespace' => $namespace,
            'class' => $className . 'Service',
            'modelNamespace' => $modelNamespace,
            'modelClass' => $className,
            'repositoryClass' => $repositoryClass,
            'repositoryShort' => $repositoryShort,
        ]);

        $path = $this->path('service', $className . 'Service.php');
        $this->writeFile($path, $content, $force, $result, 'Service');
    }

    private function appendRoute(string $className, GenerationResult $result): void
    {
        $routeFile = (string) data_get($this->config, 'route.file', base_path('routes/api.php'));
        $useApiResource = (bool) data_get($this->config, 'route.use_api_resource', true);
        $controllerNamespace = (string) data_get($this->config, 'namespaces.controller', 'App\\Http\\Controllers\\Api');

        $routeName = Str::snake(Str::plural($className));
        $controller = $controllerNamespace . '\\' . $className . 'Controller';
        $routeLine = $useApiResource
            ? "Route::apiResource('{$routeName}', \\{$controller}::class);"
            : "Route::resource('{$routeName}', \\{$controller}::class);";

        if ($this->files->exists($routeFile)) {
            $contents = $this->files->get($routeFile);
            if (str_contains($contents, $routeLine)) {
                $result->addWarning("Route already exists in {$routeFile}.");
                return;
            }
        }

        $this->files->ensureDirectoryExists(dirname($routeFile), 0755, true);
        $this->files->append($routeFile, $routeLine . PHP_EOL);
        $result->addMessage("Route appended: {$routeFile}");
    }

    /**
     * @param FieldDefinition[] $definitions
     */
    private function buildFillable(array $definitions): string
    {
        $fields = array_map(fn (FieldDefinition $field): string => "'{$field->name}'", $definitions);
        if ($fields === []) {
            return '';
        }

        return implode(', ', $fields);
    }

    /**
     * @param FieldDefinition[] $definitions
     */
    private function buildRules(array $definitions): string
    {
        if ($definitions === []) {
            return "            //
";
        }

        $lines = [];
        foreach ($definitions as $field) {
            $rules = $this->rulesForField($field);
            $ruleParts = array_map(fn (string $rule): string => "'{$rule}'", $rules);
            $lines[] = "            '{$field->name}' => [" . implode(', ', $ruleParts) . '],';
        }

        return implode(PHP_EOL, $lines);
    }

    /**
     * @param FieldDefinition[] $definitions
     */
    private function buildMigrationColumns(array $definitions): string
    {
        if ($definitions === []) {
            return '            //';
        }

        $lines = [];
        foreach ($definitions as $field) {
            $column = $this->migrationColumnForField($field);
            $lines[] = '            ' . $column;
        }

        return implode(PHP_EOL, $lines);
    }

    /**
     * @param FieldDefinition[] $definitions
     */
    private function buildResourceFields(array $definitions, bool $includeId, bool $includeTimestamps): string
    {
        $lines = [];
        if ($includeId) {
            $lines[] = "            'id' => \$this->id,";
        }

        foreach ($definitions as $field) {
            $lines[] = "            '{$field->name}' => \$this->{$field->name},";
        }

        if ($includeTimestamps) {
            $lines[] = "            'created_at' => \$this->created_at,";
            $lines[] = "            'updated_at' => \$this->updated_at,";
        }

        if ($lines === []) {
            return "            //";
        }

        return implode(PHP_EOL, $lines);
    }

    /**
     * @param FieldDefinition[] $definitions
     * @return FieldDefinition[]
     */
    private function parseFields(string $fields): array
    {
        $fields = trim($fields);
        if ($fields === '') {
            return [];
        }

        $tokens = preg_split('/[\s,]+/', $fields) ?: [];
        $definitions = [];

        foreach ($tokens as $token) {
            $token = trim($token);
            if ($token === '') {
                continue;
            }

            $name = $token;
            $type = 'string';

            if (str_contains($token, ':')) {
                [$name, $type] = explode(':', $token, 2);
            }

            $required = false;
            if (str_starts_with($name, '!')) {
                $required = true;
                $name = substr($name, 1);
            }

            if (str_ends_with($name, '!')) {
                $required = true;
                $name = substr($name, 0, -1);
            }

            if (str_ends_with($name, '?')) {
                $name = substr($name, 0, -1);
            }

            $name = trim($name);
            if ($name === '') {
                continue;
            }

            $normalized = $this->normalizeType($type);
            $definitions[$name] = new FieldDefinition($name, $normalized, $required);
        }

        return array_values($definitions);
    }

    private function normalizeType(string $type): string
    {
        $type = strtolower(trim($type));

        return match ($type) {
            'int', 'integer' => 'integer',
            'bigint', 'biginteger' => 'bigInteger',
            'bool', 'boolean' => 'boolean',
            'datetime', 'date_time' => 'dateTime',
            'uuid' => 'uuid',
            'email' => 'email',
            'json' => 'json',
            'float', 'double' => 'float',
            'decimal' => 'decimal',
            'text' => 'text',
            default => 'string',
        };
    }

    /**
     * @return string[]
     */
    private function rulesForField(FieldDefinition $field): array
    {
        $rules = [];
        $rules[] = $field->required ? 'required' : 'nullable';

        return array_merge($rules, match ($field->type) {
            'integer', 'bigInteger' => ['integer'],
            'boolean' => ['boolean'],
            'date', 'dateTime' => ['date'],
            'uuid' => ['uuid'],
            'email' => ['string', 'email', 'max:255'],
            'json' => ['array'],
            'float', 'decimal' => ['numeric'],
            'text' => ['string'],
            default => ['string', 'max:255'],
        });
    }

    private function migrationColumnForField(FieldDefinition $field): string
    {
        $nullable = $field->required ? '' : '->nullable()';

        return match ($field->type) {
            'integer' => "\$table->integer('{$field->name}'){$nullable};",
            'bigInteger' => "\$table->bigInteger('{$field->name}'){$nullable};",
            'boolean' => "\$table->boolean('{$field->name}'){$nullable};",
            'date' => "\$table->date('{$field->name}'){$nullable};",
            'dateTime' => "\$table->dateTime('{$field->name}'){$nullable};",
            'uuid' => "\$table->uuid('{$field->name}'){$nullable};",
            'email' => "\$table->string('{$field->name}'){$nullable};",
            'json' => "\$table->json('{$field->name}'){$nullable};",
            'float' => "\$table->float('{$field->name}'){$nullable};",
            'decimal' => "\$table->decimal('{$field->name}', 10, 2){$nullable};",
            'text' => "\$table->text('{$field->name}'){$nullable};",
            default => "\$table->string('{$field->name}'){$nullable};",
        };
    }

    /**
     * @param array<string, string> $replacements
     */
    private function render(string $stub, array $replacements): string
    {
        foreach ($replacements as $key => $value) {
            $stub = str_replace('{{' . $key . '}}', $value, $stub);
        }

        return $stub;
    }

    private function path(string $type, string $filename): string
    {
        $base = (string) data_get($this->config, "paths.{$type}");
        $this->files->ensureDirectoryExists($base, 0755, true);

        return rtrim($base, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $filename;
    }

    private function writeFile(string $path, string $content, bool $force, GenerationResult $result, string $label): void
    {
        if ($this->files->exists($path) && ! $force) {
            $result->addWarning("{$label} already exists: {$path}");
            return;
        }

        $this->files->put($path, $content);
        $result->addMessage("{$label} created: {$path}");
    }

    private function controllerStubName(bool $useService, bool $useRepository): string
    {
        if ($useService) {
            return 'controller.service';
        }

        if ($useRepository) {
            return 'controller.repository';
        }

        return 'controller.simple';
    }
}
