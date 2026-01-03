<?php

declare(strict_types=1);

namespace Nutandc\ApiCrud\Support;

final class FieldDefinition
{
    public function __construct(
        public readonly string $name,
        public readonly string $type,
        public readonly bool $required,
    ) {
    }
}
