<?php declare(strict_types=1);

namespace DalPraS\FormZero\Element;

use DalPraS\FormZero\Element\Intefaces\MultiChoicesInterface;
use DalPraS\FormZero\Element\Intefaces\UploadFileInterface;
use DalPraS\FormZero\Element\SymfileElement;
use DalPraS\FormZero\Element\Traits\MultiChoicesTrait;
use DalPraS\FormZero\Element\Traits\UploadFileTrait;

/**
 * Il processo per l'upload dei files è il seguente:
 *
 * isValid() -> applica i validatori
 * receive() -> applica i filtri per spostare i files, carica FileUploaded nel value
 * getValue() -> ritorna il FileUploaded
 *
 * Dato che Laminas può utilizzare direttamente $_FILES, lo uso direttamente.
 */
final class SymfileMultiElement extends SymfileElement implements MultiChoicesInterface, UploadFileInterface
{
    use MultiChoicesTrait;
    use UploadFileTrait;

    protected array $attribs = [
        'multiple' => true
    ];

    public function __construct()
    {
        // This says: "this element may have multiple values"
        $this->setIsArray(true);
    }

    public function isValid($value, $context = null): bool
    {
        $this->appendChoicesToConstraints();
        return parent::isValid($value, $context);
    }

}
