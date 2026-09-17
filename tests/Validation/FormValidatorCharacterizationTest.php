<?php

declare(strict_types=1);

namespace DalPraS\UnitTests\Validation;

use DalPraS\FormZero\Element;
use DalPraS\FormZero\FormValidator;
use DalPraS\FormZero\SubZeroForm;
use DalPraS\FormZero\ZeroForm;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class FormValidatorCharacterizationTest extends TestCase
{
    public function testRequiredAndOptionalElementsKeepCurrentValidationAndMessageSemantics(): void
    {
        $form = $this->createFlatForm();

        self::assertFalse($form->isValid([]));
        self::assertSame([
            'required' => ['required'],
        ], $form->getMessages());
        self::assertSame(['required'], $form->getMessagesForElement('required'));
        self::assertSame([], $form->getMessagesForElement('optional'));

        $valid = $this->createFlatForm();
        self::assertTrue($valid->isValid([
            'required' => 'present',
        ]));
        self::assertSame([], $valid->getMessages());
    }

    public function testPartialValidationDoesNotValidateMissingRequiredElement(): void
    {
        $full = $this->createFlatForm();
        self::assertFalse($full->isValid([]));

        $partial = $this->createFlatForm();
        self::assertTrue($partial->isValidPartial([]));
        self::assertSame([], $partial->getMessages());
    }

    public function testSuccessfulValidationClearsPreviousFormLevelValidationState(): void
    {
        $form = $this->createFlatForm();

        self::assertFalse($form->isValid([]));
        self::assertTrue($form->hasErrors());

        self::assertTrue($form->isValid(['required' => 'present']));
        self::assertFalse($form->hasErrors());
        self::assertSame([], $form->getMessages());
    }

    public function testNestedArraySubformMessagesKeepExactShapeAndLookupSemantics(): void
    {
        [$root] = $this->createNestedForm();
        $data = [
            'basedata' => [
                'metadata' => [
                    'metaTitle' => ValidationElement::INVALID_VALUE,
                ],
            ],
        ];

        self::assertFalse($root->isValid($data));

        $expected = [
            'basedata' => [
                'metadata' => [
                    'metaTitle' => ['invalid'],
                ],
            ],
        ];

        self::assertSame($expected, $root->getMessages());
        self::assertSame([
            'metadata' => [
                'metaTitle' => ['invalid'],
            ],
        ], $root->getMessagesForElement('basedata'));
        self::assertSame($expected, $root->getMessagesForElement('metadata'));
    }

    public function testCustomFormErrorStillShortCircuitsAggregatedMessages(): void
    {
        $form = $this->createFlatForm();
        $form->addError('form failed');

        self::assertSame(['form failed'], $form->getMessages());
        self::assertSame(['form failed'], $form->getMessagesForElement());
    }

    public function testRepeatedMessageReadsDoNotRevalidateElements(): void
    {
        $form = $this->createFlatForm();
        /** @var ValidationElement $required */
        $required = $form->getElement('required');

        self::assertFalse($form->isValid([
            'required' => ValidationElement::INVALID_VALUE,
        ]));
        self::assertSame(1, $required->validationCount());

        $first = $form->getMessages();
        $second = $form->getMessages();
        $elementFirst = $form->getMessagesForElement('required');
        $elementSecond = $form->getMessagesForElement('required');

        self::assertSame($first, $second);
        self::assertSame($elementFirst, $elementSecond);
        self::assertSame(1, $required->validationCount());
    }

    public function testValidatorCollaboratorMatchesZeroFormFacade(): void
    {
        [$facade] = $this->createNestedForm();
        [$direct] = $this->createNestedForm();
        $data = [
            'basedata' => [
                'metadata' => [
                    'metaTitle' => ValidationElement::INVALID_VALUE,
                ],
            ],
        ];

        $validator = new FormValidator();

        self::assertSame($facade->isValid($data), $validator->isValid($direct, $data));
        self::assertSame($facade->getMessages(), $validator->messages($direct));
        self::assertSame(
            $facade->getMessagesForElement('metadata'),
            $validator->messagesForElement($direct, 'metadata')
        );
    }

    private function createFlatForm(): ZeroForm
    {
        $form = $this->newForm(ZeroForm::class);
        $form->setName('form');

        $form->addElement(
            (new ValidationElement(required: true))->setName('required')
        );
        $form->addElement(
            (new ValidationElement(required: false))->setName('optional')
        );

        return $form;
    }

    /**
     * @return array{ZeroForm, SubZeroForm, SubZeroForm, ValidationElement}
     */
    private function createNestedForm(): array
    {
        $root = $this->newForm(ZeroForm::class);
        $root->setName('root');

        $basedata = $this->newForm(SubZeroForm::class);
        $metadata = $this->newForm(SubZeroForm::class);
        $metaTitle = (new ValidationElement(required: true))->setName('metaTitle');
        $metadata->addElement($metaTitle);

        $basedata->addSubForm($metadata, 'metadata');
        $root->addSubForm($basedata, 'basedata');

        return [$root, $basedata, $metadata, $metaTitle];
    }

    /**
     * @template T of ZeroForm
     * @param class-string<T> $class
     * @return T
     */
    private function newForm(string $class): ZeroForm
    {
        /** @var T $form */
        $form = (new ReflectionClass($class))->newInstanceWithoutConstructor();
        return $form;
    }
}

final class ValidationElement extends Element
{
    public const string INVALID_VALUE = '__invalid__';

    /** @var list<string> */
    private array $validationMessages = [];

    private int $validationCount = 0;

    public function __construct(private readonly bool $required)
    {
    }

    public function isValid($value, $context = null): bool
    {
        ++$this->validationCount;
        $this->setValue($value);
        $this->validationMessages = [];

        if ($this->required && ($value === null || $value === '')) {
            $this->validationMessages = ['required'];
            return false;
        }

        if ($value === self::INVALID_VALUE) {
            $this->validationMessages = ['invalid'];
            return false;
        }

        return true;
    }

    public function getMessages(): array
    {
        return $this->validationMessages;
    }

    public function hasErrors(): bool
    {
        return $this->validationMessages !== [];
    }

    public function validationCount(): int
    {
        return $this->validationCount;
    }
}
