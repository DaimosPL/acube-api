<?php

declare(strict_types=1);

namespace App\Service\Encoder;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class BasicFileEncoder implements FileEncoderInterface
{
    public function handleFile(\App\Entity\UploadedFile $file): void
    {
        // Simulate processing only, no DB operations
        sleep(10);
        // If you want to simulate an error, throw an exception here
        // throw new \RuntimeException('Simulated error');
    }
}
