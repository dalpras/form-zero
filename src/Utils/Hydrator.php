<?php declare(strict_types=1);

namespace DalPraS\FormZero\Utils;

use Closure;
use DalPraS\FormZero\ZeroForm;
use DalPraS\FormZero\Element;
use DalPraS\FormZero\ElementInterface;
use DalPraS\FormZero\Exception\HydratorIgnoreFieldException;
use DalPraS\FormZero\Exception\HydratorInvalidFieldException;
use DalPraS\FormZero\SubZeroForm;
use Throwable;
use TypeError;

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
                $result = match (true) {
                    is_array($value) 
                        => $value,
                    $value instanceof Closure 
                        => $value($field, $element),
                    default
                        => $value
                };
                $data[$field] = $result;
            } catch (HydratorIgnoreFieldException $th) {
                // ignora il valore
            } catch (Throwable $th) {
                throw $th;
            }
        }
        $form->setDefaults($data);
    }

    /**
     * Idrata un oggetto a partire dai valori di una ZeroForm.
     *
     * @param object  $entity   Oggetto da modificare (passato per riferimento)
     * @param ZeroForm $form
     * @param callable $hydrate function(string $field, mixed $value): mixed|Closure
     *                          Se ritorna una Closure, verrà chiamata come
     *                          function (object $entity, mixed $value, ElementInterface $element, ZeroForm $form): void
     */
    public static function hydrateObject(
        object &$entity,
        ZeroForm $form,
        callable $hydrate
    ): void {
        /** @var mixed $element */
        foreach ($form->getElementsAndSubFormsOrdered() as $element) {
            // Considera solo elementi idratabili
            if ($element instanceof ElementInterface && $element->getIgnore()) {
                continue;
            }

            $field = $element->getName();
            // $value = ($element instanceof ElementInterface) ? $element->getValue() : null;

            $value = match (true) {
                $element instanceof ElementInterface 
                    => $element->getValue(),
                $element instanceof SubZeroForm 
                    => $element->getValues(),
                default 
                    => null
            };

            try {
                $setterValue = $hydrate($field, $value);

                if ($setterValue instanceof Closure) {
                    // Custom setter supplied by the hydrator
                    $setterValue($entity, $value, $element, $form);
                    continue;
                }

                $setter = 'set' . self::toPascalCase((string) $field);

                if (!method_exists($entity, $setter)) {
                    throw new HydratorInvalidFieldException(
                        sprintf('Setter %s() non trovato per il campo "%s".', $setter, (string)$field)
                    );
                }

                $entity->$setter($setterValue);
                
            } catch (HydratorIgnoreFieldException $th) {
                // IgnoreField: non fare nulla

            } catch (HydratorInvalidFieldException|TypeError $th) {
                $element->addError($th->getMessage());
                $form->markAsError();

            } catch (Throwable $th) {
                // Altre eccezioni applicative: propagale
                throw $th;
            }
        }
    }

    /**
     * Converte "first_name" o "first-name" o "first name" in "FirstName".
     */
    public static function toPascalCase(string $name): string
    {
        $name = str_replace(['-', '_'], ' ', $name);
        $name = ucwords($name);
        return str_replace(' ', '', $name);
    }

}
