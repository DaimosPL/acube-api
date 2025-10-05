<?php

declare(strict_types=1);

namespace App\Command;

use App\Repository\UploadedFileRepository;
use App\Service\Encoder\FileEncoderInterface;
use App\Service\Notification\NotificationWebhookService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:process-uploaded-files',
    description: 'Processes new files from the uploaded_files table in an infinite loop.'
)]
class ProcessUploadedFilesCommand extends Command
{
    public function __construct(
        private readonly UploadedFileRepository $uploadedFileRepository,
        private readonly FileEncoderInterface $fileEncoder,
        private readonly LoggerInterface $logger,
        private readonly NotificationWebhookService $notifier
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption(
                'bulk-size',
                null,
                InputOption::VALUE_OPTIONAL,
                'How many files to process in one batch',
                10
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->success('Start processing files.');

        $bulkSize = (int) $input->getOption('bulk-size');
        if ($bulkSize < 1) {
            $io->error('Bulk size must be at least 1.');
            return Command::FAILURE;
        }

        while (true) {
            $files = $this->uploadedFileRepository->findNewFiles($bulkSize);
            if (count($files) === 0) {
                sleep(5);
                $io->success('No new files found. Waiting...');
                continue;
            }
            foreach ($files as $file) {
                $io->note('Processing file ID: ' . $file->getId());
                $this->uploadedFileRepository->lockFileForProcessing($file);
                try {
                    $this->fileEncoder->handleFile($file);
                    $this->uploadedFileRepository->markFileProcessed($file);
                    // notify external webhook about processed file
                    $this->notifier->notify($file);
                    $io->success('File ID: ' . $file->getId() . ' processed.');
                } catch (\Throwable $e) {
                    $this->uploadedFileRepository->markFileFailed($file, $e->getMessage());
                    $this->logger->error('File processing failed', [
                        'fileId' => $file->getId(),
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                    $io->error('File ID: ' . $file->getId() . ' failed: ' . $e->getMessage());
                }
                // kill a process to avoid memory leaks
                $io->warning('Restarting process to avoid memory leaks.');
                return Command::SUCCESS;
            }
        }
    }
}
