<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\UploadedFile as UploadedFileEntity;
use App\Enum\FileStatus;
use App\Service\FileStorageService;
use App\Service\FileValidationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controller for handling file upload operations.
 *
 * @author API Platform Team
 */
class FileUploadController extends AbstractController
{
    public function __construct(
        private readonly FileValidationService $validationService,
        private readonly FileStorageService $storageService,
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    /**
     * Handle file upload request.
     */
    public function __invoke(Request $request): JsonResponse
    {
        try {
            /** @var UploadedFile|null $uploadedFile */
            $uploadedFile = $request->files->get('file');

            if (!$uploadedFile instanceof UploadedFile) {
                return $this->createErrorResponse(
                    'No file provided. Please upload a file using the "file" form field.',
                    Response::HTTP_BAD_REQUEST
                );
            }

            // Validate the uploaded file
            $validationErrors = $this->validationService->validate($uploadedFile);
            if (!empty($validationErrors)) {
                return $this->createErrorResponse(
                    'File validation failed',
                    Response::HTTP_BAD_REQUEST,
                    $validationErrors
                );
            }

            // --- Collect metadata BEFORE moving the file ---
            $originalName = $uploadedFile->getClientOriginalName() ?? '';
            $extension = strtolower($uploadedFile->getClientOriginalExtension() ?? '');
            $size = $uploadedFile->getSize() ?? 0;
            $mimeType = $uploadedFile->getMimeType() ?? 'application/octet-stream';

            // Store the file
            $storedPath = $this->storageService->store($uploadedFile);

            // Create database entity
            $fileEntity = new UploadedFileEntity();
            $fileEntity->setPath($storedPath);
            $fileEntity->setOriginalName($originalName);
            $fileEntity->setExtension($extension);
            $fileEntity->setSize((int) $size);
            $fileEntity->setMimeType($mimeType);
            $fileEntity->setStatus(FileStatus::NEW);

            // Persist to database
            try {
                $this->entityManager->persist($fileEntity);
                $this->entityManager->flush();
            } catch (\Throwable $e) {
                // Cleanup stored file to avoid orphan files
                try {
                    $this->storageService->deleteByPath($storedPath);
                } catch (\Throwable $cleanupException) {
                    // Swallow cleanup exceptions but log them if logger available
                }

                throw $e; // rethrow to be handled by outer catch
            }

            return $this->createSuccessResponse($fileEntity);

        } catch (\Exception $e) {
            return $this->createErrorResponse(
                'An unexpected error occurred during file upload',
                Response::HTTP_INTERNAL_SERVER_ERROR,
                [$e->getMessage()]
            );
        }
    }

    /**
     * Create success response.
     */
    private function createSuccessResponse(UploadedFileEntity $fileEntity): JsonResponse
    {
        return new JsonResponse([
            'success' => true,
            'message' => 'File uploaded successfully',
            'data' => [
                'id' => $fileEntity->getId(),
                'originalName' => $fileEntity->getOriginalName(),
                'extension' => $fileEntity->getExtension(),
                'size' => $fileEntity->getSize(),
                'mimeType' => $fileEntity->getMimeType(),
                'status' => $fileEntity->getStatus()->value,
                'createdAt' => $fileEntity->getCreatedAt()->format('c'),
                'updatedAt' => $fileEntity->getUpdatedAt()->format('c'),
            ]
        ], Response::HTTP_CREATED);
    }

    /**
     * Create error response.
     *
     * @param string $message
     * @param int $statusCode
     * @param array<string> $errors
     */
    private function createErrorResponse(
        string $message,
        int $statusCode = Response::HTTP_BAD_REQUEST,
        array $errors = []
    ): JsonResponse {
        $response = [
            'success' => false,
            'message' => $message,
        ];

        if (!empty($errors)) {
            $response['errors'] = $errors;
        }

        return new JsonResponse($response, $statusCode);
    }
}
