<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Service for validating uploaded files.
 *
 * @author API Platform Team
 */
class FileValidationService
{
    private const ALLOWED_EXTENSIONS = ['csv', 'json', 'xlsx', 'ods'];

    private const ALLOWED_MIME_TYPES = [
        'text/csv',
        'application/csv',
        'text/comma-separated-values',
        'text/plain',
        'application/json',
        'text/json',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/vnd.oasis.opendocument.spreadsheet',
    ];

    private const MAX_FILE_SIZE = 10 * 1024 * 1024; // 10MB

    /**
     * Validate uploaded file.
     *
     * @param UploadedFile $file
     * @return array<string> Array of validation errors, empty if valid
     */
    public function validate(UploadedFile $file): array
    {
        $errors = [];

        // Check if file was uploaded successfully
        if (!$file->isValid()) {
            $errors[] = 'File upload failed: ' . $file->getErrorMessage();
            return $errors;
        }

        // Validate file size
        if ($file->getSize() > self::MAX_FILE_SIZE) {
            $errors[] = sprintf(
                'File size (%s) exceeds maximum allowed size (%s)',
                $this->formatBytes($file->getSize()),
                $this->formatBytes(self::MAX_FILE_SIZE)
            );
        }

        // Validate file extension
        $extension = strtolower($file->getClientOriginalExtension());
        if (!in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
            $errors[] = sprintf(
                'File extension "%s" is not allowed. Allowed extensions: %s',
                $extension,
                implode(', ', self::ALLOWED_EXTENSIONS)
            );
        }

        // Validate MIME type
        $mimeType = $file->getMimeType();
        if ($mimeType && !in_array($mimeType, self::ALLOWED_MIME_TYPES, true)) {
            $errors[] = sprintf(
                'File MIME type "%s" is not allowed',
                $mimeType
            );
        }

        // Additional validation for specific file types
        $errors = array_merge($errors, $this->validateFileContent($file, $extension));

        return $errors;
    }

    /**
     * Get allowed file extensions.
     *
     * @return array<string>
     */
    public function getAllowedExtensions(): array
    {
        return self::ALLOWED_EXTENSIONS;
    }

    /**
     * Get allowed MIME types.
     *
     * @return array<string>
     */
    public function getAllowedMimeTypes(): array
    {
        return self::ALLOWED_MIME_TYPES;
    }

    /**
     * Validate file content based on extension.
     *
     * @param UploadedFile $file
     * @param string $extension
     * @return array<string>
     */
    private function validateFileContent(UploadedFile $file, string $extension): array
    {
        $errors = [];

        try {
            switch ($extension) {
                case 'json':
                    $content = file_get_contents($file->getPathname());
                    if ($content === false) {
                        $errors[] = 'Unable to read file content';
                        break;
                    }

                    json_decode($content, flags: JSON_THROW_ON_ERROR);
                    break;

                case 'csv':
                    $handle = fopen($file->getPathname(), 'r');
                    if ($handle === false) {
                        $errors[] = 'Unable to read CSV file';
                        break;
                    }

                    // Try to read first line to validate CSV format
                    $firstLine = fgetcsv($handle);
                    if ($firstLine === false) {
                        $errors[] = 'Invalid CSV file format';
                    }
                    fclose($handle);
                    break;

                // For XLSX and ODS, basic validation is done through MIME type
                case 'xlsx':
                case 'ods':
                default:
                    // Additional validation could be added here if needed
                    break;
            }
        } catch (\JsonException $e) {
            $errors[] = 'Invalid JSON format: ' . $e->getMessage();
        } catch (\Throwable $e) {
            $errors[] = 'File validation error: ' . $e->getMessage();
        }

        return $errors;
    }

    /**
     * Format bytes to human readable format.
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $factor = floor((strlen((string) $bytes) - 1) / 3);

        return sprintf('%.2f %s', $bytes / (1024 ** $factor), $units[$factor] ?? 'TB');
    }
}
