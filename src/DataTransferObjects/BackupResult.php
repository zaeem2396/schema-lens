<?php

namespace Zaeem2396\SchemaLens\DataTransferObjects;

/**
 * @phpstan-type BackupResultArray array{success: bool, path?: string, message?: string}
 */
final class BackupResult
{
    /**
     * @param  BackupResultArray  $payload
     */
    public function __construct(
        public readonly array $payload
    ) {}

    public function succeeded(): bool
    {
        return $this->payload['success'] === true;
    }

    public function path(): ?string
    {
        return $this->payload['path'] ?? null;
    }

    public function message(): ?string
    {
        return $this->payload['message'] ?? null;
    }

    /**
     * @return BackupResultArray
     */
    public static function success(string $path): array
    {
        return ['success' => true, 'path' => $path];
    }

    /**
     * @return BackupResultArray
     */
    public static function failure(string $message): array
    {
        return ['success' => false, 'message' => $message];
    }
}
