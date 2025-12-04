<?php declare(strict_types=1);

namespace DalPraS\FormZero\Element;

use DalPraS\FormZero\Element;
use DalPraS\FormZero\Element\Intefaces\UploadFileInterface;
use DalPraS\FormZero\Element\Traits\UploadFileTrait;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * The process for file uploads is as follows:
 *
 * isValid()  -> Apply Symfony Validator constraints
 * receive()  -> Store UploadedFile(s) in value (optionally after filters)
 * getValue() -> Return the uploaded file(s) after receive()
 */
class SymfileElement extends Element implements UploadFileInterface
{
    use UploadFileTrait;

    /**
     * Receive UploadedFile or array of UploadedFile.
     *
     * @return UploadedFile|UploadedFile[]|null
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Validate the element using Symfony Validator instead of Laminas ValidatorChain.
     *
     * @param mixed $value   Ignored (we always read from Request)
     * @param mixed $context Full POST data (if needed)
     */
    public function isValid($value, $context = null): bool
    {
        $this->isValidated = true;
        $this->messages    = [];

        // Empty upload
        if ($this->isEmpty($value)) {
            // not required and allow empty -> OK
            if ($this->isRequired() === false && $this->getAllowEmpty()) {
                return true;
            }

            // required but empty -> error
            $this->messages[] = 'A file is required.';
            return false;
        }

        /** @var UploadedFile|UploadedFile[]|null $files */
        $files = $this->getUploadedFiles();

        // Fetch Symfony validator from your factory
        /** @var ValidatorInterface $validator */
        $validator = $this->getFactory()->getValidator();

        $result = true;

        if ($this->isArray() && is_array($files)) {
            foreach ($files as $file) {
                if (!$file instanceof UploadedFile) {
                    $this->messages[] = 'Invalid uploaded file.';
                    $result           = false;
                    continue;
                }

                $violations = $validator->validate($file, $this->getConstraints());
                if (count($violations) > 0) {
                    $result = false;
                    foreach ($violations as $violation) {
                        $this->messages[] = $violation->getMessage();
                    }
                }
            }
        } elseif ($files instanceof UploadedFile) {
            $violations = $validator->validate($files, $this->getConstraints());
            if (count($violations) > 0) {
                $result = false;
                foreach ($violations as $violation) {
                    $this->messages[] = $violation->getMessage();
                }
            }
        } else {
            $this->messages[] = 'Invalid uploaded file.';
            $result           = false;
        }

        if ($this->isError) {
            return false;
        }

        return $result;
    }
}
