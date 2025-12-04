<?php

declare(strict_types=1);

namespace DalPraS\FormZero\Element\Traits;

use InvalidArgumentException;
use Symfony\Component\HttpFoundation\File\UploadedFile;

trait UploadFileTrait
{
    /**
     * Return the UploadedFile or array of UploadedFile for the current element.
     *
     * In Symfony, files are stored in the Request::$files bag and mapped as
     * Symfony\Component\HttpFoundation\File\UploadedFile instances.
     *
     * @return UploadedFile|UploadedFile[]|null
     */
    public function getUploadedFiles(): array|UploadedFile|null
    {
        // getHttpRequest() must return Symfony\Component\HttpFoundation\Request
        $request = $this->getFactory()->getHttpRequest();
        if ($request === null) {
            return null;
        }

        // This returns either:
        // - UploadedFile
        // - UploadedFile[]
        // - null
        $uploadedFile = $request->files->get($this->getName());

        if ($uploadedFile === null) {
            return null;
        }

        if ($this->isArray() && !is_array($uploadedFile)) {
            throw new InvalidArgumentException('Uploaded file is not an array as expected.');
        }

        return $uploadedFile;
    }

    /**
     * Check that a file has been uploaded with no errors.
     */
    public function isUploaded(): bool
    {
        /** @var UploadedFile|UploadedFile[]|null $uploadedFiles */
        $uploadedFiles = $this->getUploadedFiles();

        if (null === $uploadedFiles) {
            return false;
        }

        if ($this->isArray() && is_array($uploadedFiles)) {
            foreach ($uploadedFiles as $uploadFile) {
                if (!$uploadFile instanceof UploadedFile) {
                    return false;
                }
                if ($uploadFile->getError() !== UPLOAD_ERR_OK) {
                    return false;
                }
            }
        } elseif ($uploadedFiles instanceof UploadedFile) {
            if ($uploadedFiles->getError() !== UPLOAD_ERR_OK) {
                return false;
            }
        } else {
            return false;
        }

        return true;
    }

    /**
     * Consider the element empty if no file was uploaded.
     */
    public function isEmpty($value): bool
    {
        return $this->isUploaded() === false;
    }

}
