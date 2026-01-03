<?php

declare(strict_types=1);

return [
    'paths' => [
        'model' => app_path('Models'),
        'controller' => app_path('Http/Controllers/Api'),
        'request' => app_path('Http/Requests'),
        'resource' => app_path('Http/Resources'),
        'migration' => database_path('migrations'),
        'repository' => app_path('Repositories'),
        'service' => app_path('Services'),
    ],

    'namespaces' => [
        'model' => 'App\\Models',
        'controller' => 'App\\Http\\Controllers\\Api',
        'request' => 'App\\Http\\Requests',
        'resource' => 'App\\Http\\Resources',
        'repository' => 'App\\Repositories',
        'service' => 'App\\Services',
    ],

    'route' => [
        'file' => base_path('routes/api.php'),
        'use_api_resource' => true,
    ],

    'repository' => [
        'enabled' => true,
        'path' => app_path('Repositories'),
        'base' => \Nutandc\ApiCrud\Repositories\BaseRepository::class,
    ],

    'service' => [
        'enabled' => false,
        'path' => app_path('Services'),
    ],

    'base_controller' => \Nutandc\ApiCrud\Http\Controllers\ApiController::class,

    'pagination' => [
        'per_page' => 15,
    ],

    'resource' => [
        'include_id' => true,
        'include_timestamps' => false,
    ],

    'stubs' => [
        'publish_path' => resource_path('stubs/api-crud-generator'),
    ],
];
