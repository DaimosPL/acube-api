<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\UploadedFile as UploadedFileEntity;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;

/**
 * Service for managing file storage operations.
 *
 * @author API Platform Team
 */
class FileStorageService
{
    private const UPLOAD_DIRECTORY = 'private/uploads';

    public function __construct(
        private readonly string $projectDir,
        private readonly SluggerInterface $slugger
    ) {
    }

    /**
     * Store uploaded file in private directory.
     *
     * @param UploadedFile $file
     * @return string The relative path where the file was stored
     * @throws \RuntimeException If file cannot be stored
     */
    public function store(UploadedFile $file): string
    {
        $uploadDir = $this->getUploadDirectory();
        $this->ensureDirectoryExists($uploadDir);

        $filename = $this->generateUniqueFilename($file);
        $relativePath = self::UPLOAD_DIRECTORY . '/' . $filename;
        $absolutePath = $this->projectDir . '/' . $relativePath;

        try {
            $file->move($uploadDir, $filename);
        } catch (\Exception $e) {
            throw new \RuntimeException(
                sprintf('Failed to store file: %s', $e->getMessage()),
                0,
                $e
            );
        }

        if (!file_exists($absolutePath)) {
            throw new \RuntimeException('File was not stored successfully');
        }

        return $relativePath;
    }

    /**
     * Delete stored file.
     *
     * @param UploadedFileEntity $fileEntity
     * @return bool True if file was deleted, false otherwise
     */
    public function delete(UploadedFileEntity $fileEntity): bool
    {
        $absolutePath = $this->projectDir . '/' . $fileEntity->getPath();

        if (!file_exists($absolutePath)) {
            return true; // File doesn't exist, consider it deleted
        }

        return unlink($absolutePath);
    }

    /**
     * Delete stored file by relative path.
     *
     * @param string $relativePath Relative path like 'private/uploads/filename.ext'
     * @return bool True if deleted or does not exist, false on failure
     */
    public function deleteByPath(string $relativePath): bool
    {
        $absolutePath = $this->projectDir . '/' . ltrim($relativePath, '/');

        if (!file_exists($absolutePath)) {
            return true;
        }

        return unlink($absolutePath);
    }

    /**
     * Check if stored file exists.
     *
     * @param UploadedFileEntity $fileEntity
     * @return bool
     */
    public function exists(UploadedFileEntity $fileEntity): bool
    {
        $absolutePath = $this->projectDir . '/' . $fileEntity->getPath();

        return file_exists($absolutePath) && is_readable($absolutePath);
    }

    /**
     * Get file content.
     *
     * @param UploadedFileEntity $fileEntity
     * @return string
     * @throws \RuntimeException If file cannot be read
     */
    public function getContent(UploadedFileEntity $fileEntity): string
    {
        $absolutePath = $this->projectDir . '/' . $fileEntity->getPath();

        if (!$this->exists($fileEntity)) {
            throw new \RuntimeException('File does not exist or is not readable');
        }

        $content = file_get_contents($absolutePath);
        if ($content === false) {
            throw new \RuntimeException('Failed to read file content');
        }

        return $content;
    }

    /**
     * Get absolute path to upload directory.
     */
    private function getUploadDirectory(): string
    {
        return $this->projectDir . '/' . self::UPLOAD_DIRECTORY;
    }

    /**
     * Generate unique filename for uploaded file.
     */
    private function generateUniqueFilename(UploadedFile $file): string
    {
        $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $extension = $file->getClientOriginalExtension();

        $safeFilename = $this->slugger->slug($originalName);
        $uniqueId = uniqid('', true);
        $timestamp = (new \DateTime())->format('Y-m-d_H-i-s');

        return sprintf('%s_%s_%s.%s', $safeFilename, $timestamp, $uniqueId, $extension);
    }

    /**
     * Ensure directory exists and is writable.
     *
     * @throws \RuntimeException If directory cannot be created or is not writable
     */
    private function ensureDirectoryExists(string $directory): void
    {
        if (!is_dir($directory)) {
            if (!mkdir($directory, 0755, true)) {
                throw new \RuntimeException(
                    sprintf('Failed to create upload directory: %s', $directory)
                );
            }
        }

        if (!is_writable($directory)) {
            throw new \RuntimeException(
                sprintf('Upload directory is not writable: %s', $directory)
            );
        }
    }
}
