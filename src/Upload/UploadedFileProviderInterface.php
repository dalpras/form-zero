<?php declare(strict_types=1);

namespace DalPraS\FormZero\Upload;

use DalPraS\FormZero\FieldPath;
use Symfony\Component\HttpFoundation\File\UploadedFile;

interface UploadedFileProviderInterface
{
    /** @return UploadedFile|UploadedFile[]|null */
    public function get(FieldPath $fieldPath): array|UploadedFile|null;
}
