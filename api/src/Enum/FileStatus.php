<?php

declare(strict_types=1);

namespace App\Enum;

/**
 * Enum representing the status of uploaded files.
 *
 * @author API Platform Team
 */
enum FileStatus: string
{
    case NEW = 'new';
    case PROCESSING = 'processing';
    case PROCESSED = 'processed';
    case FAILED = 'failed';
    case ARCHIVED = 'archived';

    /**
     * Get all possible status values.
     *
     * @return array<string>
     */
    public static function getValues(): array
    {
        return array_map(fn(self $status) => $status->value, self::cases());
    }

    /**
     * Get human-readable label for the status.
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::NEW => 'New',
            self::PROCESSING => 'Processing',
            self::PROCESSED => 'Processed',
            self::FAILED => 'Failed',
            self::ARCHIVED => 'Archived',
        };
    }
}
