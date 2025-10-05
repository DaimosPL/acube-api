<?php

declare(strict_types=1);

namespace App\Service\Encoder;

use App\Entity\UploadedFile;

interface FileEncoderInterface
{
    public function handleFile(UploadedFile $file): void;
}

