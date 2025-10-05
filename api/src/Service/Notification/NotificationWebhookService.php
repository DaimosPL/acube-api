<?php

declare(strict_types=1);

namespace App\Service\Notification;

use App\Entity\UploadedFile;
use App\Repository\UploadedFileRepository;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use App\Enum\NotificationStatus;

class NotificationWebhookService
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly UploadedFileRepository $uploadedFileRepository,
        private readonly LoggerInterface $logger,
        private readonly string $endpoint
    ) {
    }

    public function notify(UploadedFile $file): void
    {
        // ensure DB reflects pending status
        $this->uploadedFileRepository->setNotificationStatus($file, NotificationStatus::PENDING);

        try {
            $payload = [
                'id' => $file->getId(),
                'originalName' => $file->getOriginalName(),
                'path' => $file->getPath(),
                'extension' => $file->getExtension(),
                'size' => $file->getSize(),
                'mimeType' => $file->getMimeType(),
                'status' => $file->getStatus()->value,
                'createdAt' => $file->getCreatedAt()->format('c'),
                'updatedAt' => $file->getUpdatedAt()->format('c'),
            ];

            $response = $this->httpClient->request('POST', $this->endpoint, [
                'json' => $payload,
                'timeout' => 5,
            ]);

            $statusCode = $response->getStatusCode();
            if ($statusCode >= 200 && $statusCode < 300) {
                $this->uploadedFileRepository->markNotificationSent($file);
                $this->logger->info('Notification sent for file', ['fileId' => $file->getId(), 'status' => $statusCode]);
            } else {
                $this->logger->error('Notification endpoint returned non-2xx', ['fileId' => $file->getId(), 'status' => $statusCode]);
                // leave as pending for retry
            }
        } catch (\Throwable $e) {
            $this->logger->error('Notification send failed', ['fileId' => $file->getId(), 'error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            // leave as pending for retry
        }
    }
}
