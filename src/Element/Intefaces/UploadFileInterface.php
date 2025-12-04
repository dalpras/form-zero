<?php

declare(strict_types=1);

namespace DalPraS\FormZero\Element\Intefaces;

use Symfony\Component\HttpFoundation\File\UploadedFile;

interface UploadFileInterface
{
    public function getUploadedFiles(): array|UploadedFile|null;
    public function isUploaded(): bool;
    public function isEmpty($value): bool;
}

