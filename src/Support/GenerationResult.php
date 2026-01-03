<?php

declare(strict_types=1);

namespace Nutandc\ApiCrud\Support;

final class GenerationResult
{
    /** @var string[] */
    public array $messages = [];

    /** @var string[] */
    public array $warnings = [];

    public function addMessage(string $message): void
    {
        $this->messages[] = $message;
    }

    public function addWarning(string $warning): void
    {
        $this->warnings[] = $warning;
    }
}
