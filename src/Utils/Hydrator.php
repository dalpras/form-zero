<?php declare(strict_types=1);

namespace DalPraS\FormZero\Utils;

use Closure;
use DalPraS\FormZero\ZeroForm;
use DalPraS\FormZero\Element;
use DalPraS\FormZero\ElementInterface;
use DalPraS\FormZero\Exception\HydratorIgnoreFieldException;
use DalPraS\FormZero\Exception\HydratorInvalidFieldException;
use Throwable;

class Hydrator
{
    /**
     * Imposta i valori degli elementi di una form.
     *
     * $data contiene i valori delle proprietà dell'oggeto da settare mediante set*Property*().
     * Se la chiave è numerica usa un "getter" sull'oggeto. Se la chiave è stringa prende il dato passato come valore.
     */
    public static function hydrateDefaults(ZeroForm &$form, Closure $hydrate): void
    {
        $data = [];
        /** @var \DalPraS\FormZero\Element|\DalPraS\FormZero\SubZeroForm $element */
        foreach ($form->getElementsAndSubFormsOrdered() as $element) {
            if ($element instanceof Element && $element->getIgnore()) {
                continue;
            }
            $field = $element->getName();
            try {
                $value = $hydrate($field);
                $data[$field] = ($value instanceof Closure) ? $value($field, $element) : $value;
            } catch (HydratorIgnoreFieldException $th) {
                // ignora il valore
            } catch (Throwable $th) {
                throw $th;
            }
        }
        $form->setDefaults($data);
    }

    /**
     * Idrata l'oggetto in base al valore passato dalla funzione di idratazione
     * che passa nome e valore dell'elemento della form.
     * 
     * @var object $entity Oggetto da modificare
     * @var \DalPraS\FormZero\ZeroForm $form
     * @var Closure $hydrate funtion($field, $value) => void
     */
    public static function hydrateObject(object &$entity, ZeroForm $form, Closure $hydrate): void
    {
        /** @var \DalPraS\FormZero\Element $element */
        foreach ($form->getElementsAndSubFormsOrdered() as $element) {
            if ($element instanceof ElementInterface && $element->getIgnore()) {
                continue;
            }
            $field = $element->getName();
            $value = ($element instanceof ElementInterface) ? $element->getValue() : null;
            try {
                $setterValue = $hydrate($field, $value);
                if ($setterValue instanceof Closure) {
                    $setterValue($entity, $value, $element, $form);
                } else {
                    $setter = 'set' . ucfirst($field);
                    if (method_exists($entity, $setter) === false) {
                        throw new HydratorInvalidFieldException("Method {$setter} not found in object");
                    }
                    $entity->$setter($setterValue);
                }
            } catch (HydratorInvalidFieldException $th) {
                $element->addError($th->getMessage());
                $form->markAsError();
            } catch (HydratorIgnoreFieldException $th) {
                // ignora il valore
            } catch (Throwable $th) {
                throw $th;
            }
        }
    }
}
