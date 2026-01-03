# Laravel API CRUD Generator

Laravel API CRUD generator for models, controllers, requests, resources, migrations, routes, plus optional repository/service patterns.

## Requirements
- PHP 8.2+
- Laravel 10/11/12

## Installation
```bash
composer require nutandc/api-crud-generator
```

Publish config and stubs (optional):
```bash
php artisan vendor:publish --tag=api-crud-generator-config
php artisan vendor:publish --tag=api-crud-generator-stubs
```

## Usage
Generate everything:
```bash
php artisan crud:api Post --fields="title,body:text,author_id:integer,is_active:boolean"
```

Make a field required with `!`:
```bash
php artisan crud:api User --fields="!name,email:email,age:integer"
```

Enable/disable patterns:
```bash
php artisan crud:api Post --service --repo
php artisan crud:api Post --no-service --no-repo
```

Skip parts:
```bash
php artisan crud:api Category --no-migration --no-resource
```

Overwrite existing files:
```bash
php artisan crud:api Product --fields="name,price:decimal" --force
```

## Generated Files
- Model: `app/Models`
- Request: `app/Http/Requests`
- Resource: `app/Http/Resources`
- Controller: `app/Http/Controllers/Api`
- Migration: `database/migrations`
- Route: `routes/api.php`

## Field Types
Supported field types: `string`, `text`, `integer`, `bigInteger`, `boolean`, `date`, `dateTime`, `email`, `uuid`, `json`, `float`, `decimal`.

Examples:
```
title:string,body:text,price:decimal,uuid:uuid,is_active:boolean
```

## Config
`config/api-crud-generator.php` controls namespaces, paths, routes, base controller, pagination, and resource fields.
Repository/service patterns can be enabled or disabled via config or CLI flags.

Example config:
```
'repository' => [
    'enabled' => true,
    'path' => app_path('Repositories'),
],
'service' => [
    'enabled' => false,
    'path' => app_path('Services'),
],
```

## Stubs
Publish and customize stubs under:
```
resources/stubs/api-crud-generator
```

## License
MIT
