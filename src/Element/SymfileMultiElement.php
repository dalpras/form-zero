<?php declare(strict_types=1);

namespace DalPraS\FormZero\Element;

use DalPraS\FormZero\Element\SymfileElement;

/**
 * Il processo per l'upload dei files è il seguente:
 *
 * isValid() -> applica i validatori
 * receive() -> applica i filtri per spostare i files, carica FileUploaded nel value
 * getValue() -> ritorna il FileUploaded
 *
 * Dato che Laminas può utilizzare direttamente $_FILES, lo uso direttamente.
 */
class SymfileMultiElement extends SymfileElement
{
    protected array $attribs = [
        'multiple' => true
    ];

    /**
     * Does the element represent an array?
     */
    public function isArray(): bool
    {
        return true;
    }    
}
