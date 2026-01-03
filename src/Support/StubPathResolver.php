<?php

declare(strict_types=1);

namespace Nutandc\ApiCrud\Support;

use Illuminate\Filesystem\Filesystem;

final class StubPathResolver
{
    public function __construct(
        private readonly Filesystem $files,
        private readonly string $packagePath,
        private readonly string $publishedPath,
    ) {
    }

    public function getStub(string $name): string
    {
        $filename = $name . '.stub';

        $published = rtrim($this->publishedPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $filename;
        if ($this->publishedPath !== '' && $this->files->exists($published)) {
            return $this->files->get($published);
        }

        $package = rtrim($this->packagePath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $filename;
        return $this->files->get($package);
    }
}
