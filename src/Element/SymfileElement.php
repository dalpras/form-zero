<?php declare(strict_types=1);

namespace DalPraS\FormZero\Element;

use DalPraS\FormZero\Element;
use InvalidArgumentException;
use Laminas\Diactoros\UploadedFile;
use Laminas\Validator\File\UploadFile;

/**
 * The process for file uploads is as follows:
 *
 * isValid()  -> Apply validators
 * receive()  -> Apply the filters to move the files, charge fileuploaded in the value
 * getValue() -> The fileuploaded returns
*/
class SymfileElement extends Element
{
    protected array $attribs = [];

    protected bool $received = false;

    /**
     * ritorna il il \laminas\Ros\uple o l'array dell'element corrente.
     *
     * @see https://www.php-fig.org/psr/psr-7/
     *
     * @return \Laminas\Diactoros\UploadedFile|\Laminas\Diactoros\UploadedFile[]
     */
    public function getUploadedFiles(): array|UploadedFile|null
    {
        $uploadedFiles = $this->getFactory()->getPsrRequest()->getUploadedFiles();
        if (isset($uploadedFiles[$this->getName()])) {
            $uploadedFile = $uploadedFiles[$this->getName()];
            if ($this->isArray() && is_array($uploadedFile) === false) {
                throw new InvalidArgumentException('Uploaded file is not an array as expected.');
            }
            return $uploadedFile;
        }
        return null;
    }

    public function isReceived(): bool
    {
        return $this->received;
    }

    /**
     * Check if the element has been validated and in case it proceeds to validate.
     * I therefore initialize the value of the element by applying the filters.
     * If it is multiple there will be an array of fileuploaded, if only one element single.
     */
    public function receive(): bool
    {
        $this->received = true;
        if ( !$this->isValidated ) {
            if ( !$this->isValid(null) ) {
                return false;
            }
        }

        // UPLOAD_ERR_OK:         Value: 0; There is no error, the file uploaded with success.
        // UPLOAD_ERR_INI_SIZE:   Value: 1; The uploaded file exceeds the upload_max_filesize directive in php.ini.
        // UPLOAD_ERR_FORM_SIZE:  Value: 2; The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.
        // UPLOAD_ERR_PARTIAL:    Value: 3; The uploaded file was only partially uploaded.
        // UPLOAD_ERR_NO_FILE:    Value: 4; No file was uploaded.
        // UPLOAD_ERR_NO_TMP_DIR: Value: 6; Missing a temporary folder.
        // UPLOAD_ERR_CANT_WRITE: Value: 7; Failed to write file to disk.
        // UPLOAD_ERR_EXTENSION:  Value: 8; A PHP extension stopped the file upload.
        if ( $this->isUploaded() === false ) {
            return false;
        }

        /** @var \Laminas\Diactoros\UploadedFile|\Laminas\Diactoros\UploadedFile[] $uploadedFiles */
        $uploadedFiles = $this->getUploadedFiles();

        if ($this->isArray() && is_array($uploadedFiles)) {
            $value = [];
            foreach ($uploadedFiles as $upFile) {
                // the Renameupload filter must always be applied last
                $upFile = $this->getFilterChain()->filter($upFile);
                $value[] = $upFile;
            }
        } elseif ($uploadedFiles instanceof UploadedFile) {
            $upFile = $uploadedFiles;
            $upFile = $this->getFilterChain()->filter($upFile);
            $value = $upFile;
        } else {
            $value = null;
        }
        $this->setValue($value);

        return true;
    }

    /**
     * Check that the file does not present errors (is actually loaded).
     */
    public function isUploaded(): bool
    {
        $uploadedFiles = $this->getUploadedFiles();

        // If getUploadedFiles() returns null, there's no file in the request.
        if (null === $uploadedFiles) {
            return false;
        }

        if ($this->isArray() && is_array($uploadedFiles)) {
            foreach ($uploadedFiles as $uploadFile) {
                if ($uploadFile->getError() !== UPLOAD_ERR_OK) {
                    return false;
                }
            }
        } elseif ($uploadedFiles instanceof UploadedFile && $uploadedFiles->getError() !== UPLOAD_ERR_OK) {
            return false;
        }

        return true;
    }


    /**
     * Receive UploadedFile or array of UploadedFile
     *
     * @return \Laminas\Diactoros\UploadedFile|array|NULL
     */
    public function getValue()
    {
        if (! $this->received ) {
            return null;
        }

        if ($this->value !== null) {
            return $this->value;
        }

        if ($this->isUploaded() && !$this->receive() ) {
            return null;
        }

        return $this->value;
    }

    /**
     * I do not value the verification, but on the fact that the file is
     * has been loaded or not.
     */
    protected function isEmpty($value): bool
    {
        return $this->isUploaded() === false;
    }

    /**
     * Valid the element.
     *
     * The stream of uploadedfile contains the metadato 'uri' which indicates the path.
     * In case it is moved, the Moved property in uploadedfile is set to True.
     * If I try to access the 'moved' stream, an expotion appears.
     *
     * In the case of $ _files Isvalid is called with Null value (as here).
     * For convenience we extract from $ _files a \ Laminas \ diactoros \ uploadedfile that
     * Then we will store on $ values ​​and on which it will be possible to apply filters and validators.
     *
     * @param NULL $value
     * @param mixed $context contiene le informazioni del POST totale come array di dati
     * @return boolean
     */
    public function isValid($value, $context = null): bool
    {
        $this->isValidated = true;

        if ( $this->isEmpty($value) && $this->isRequired() === false && $this->getAllowEmpty() ) {
            return true;
        }

        if ($this->isRequired()) {
            $this->getValidatorChain()->prependByName(UploadFile::class, [], true);
        }
        // nel caso di più validazioni, resettiamo sempre il valore
        $this->messages = [];

        // assume that isvalid
        $result = true;
        $isArray = $this->isArray();
        /** @var \Laminas\Diactoros\UploadedFile $value */
        $value = $this->getUploadedFiles();

        foreach ($this->getValidatorChain() as $validator) {
            if ($isArray && is_array($value)) {
                $messages = [];
                if ($this->isRequired() || (!$this->isRequired() && !$this->getAllowEmpty()) ) {
                    $value = '';
                }
                foreach ( (array) $value as $val ) {
                    // in case of failures it is not valid
                    if ( $validator['instance']->isValid($val, $context) === false ) {
                        $result = false;
                        if ($this->hasErrorMessages()) {
                            $messages = $this->_getErrorMessages();
                        } else {
                            $messages = array_merge($messages, $validator['instance']->getMessages());
                        }
                    }
                }
                if ($result) {
                    continue;
                }
            } elseif ( $validator['instance']->isValid($value, $context) === true ) {
                continue;
            } else {
                $result = false;
                if ($this->hasErrorMessages()) {
                    $messages = $this->_getErrorMessages();
                } else {
                    $messages = $validator['instance']->getMessages();
                }
            }

            $result = false;
            // ciclicamente aggiunge i messaggi
            $this->messages = array_merge($this->messages, $messages);

            if ($validator['breakChainOnFailure'] === true) {
                break;
            }
        }

        // If element manually flagged as invalid, return false
        if ($this->isError) {
            return false;
        }
        return $result;
    }
}

