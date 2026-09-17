<?php

declare(strict_types=1);

namespace DalPraS\UnitTests\Render;

use DalPraS\FormZero\Decorator\AbstractDecorator;
use DalPraS\FormZero\Decorator\ElementsDecorator;
use DalPraS\FormZero\Element\HashElement;
use DalPraS\FormZero\Element\SymfileElement;
use DalPraS\FormZero\Element\TextElement;
use DalPraS\FormZero\Session\SessionAdapterInterface;
use DalPraS\FormZero\SubZeroForm;
use DalPraS\FormZero\ZeroForm;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class RenderPurityTest extends TestCase
{
    public function testRenderDoesNotChangeValuesMessagesOrLogicalBelongsTo(): void
    {
        [$root, $basedata, $metadata, $metaTitle] = $this->createNestedForm();
        $metaTitle->addError('Invalid meta title');

        $valuesBefore = $root->getValues();
        $messagesBefore = $root->getMessages();
        $belongsToBefore = [
            $basedata->getElement('title')?->getBelongsTo(),
            $metadata->getElementsBelongTo(),
            $metaTitle->getBelongsTo(),
        ];

        $firstHtml = $root->render();

        self::assertSame($valuesBefore, $root->getValues());
        self::assertSame($messagesBefore, $root->getMessages());
        self::assertSame($belongsToBefore, [
            $basedata->getElement('title')?->getBelongsTo(),
            $metadata->getElementsBelongTo(),
            $metaTitle->getBelongsTo(),
        ]);

        $secondHtml = $root->render();
        self::assertSame($firstHtml, $secondHtml);
        self::assertSame($valuesBefore, $root->getValues());
        self::assertSame($messagesBefore, $root->getMessages());
    }

    public function testRepeatedRenderKeepsFullyQualifiedNestedNamesStable(): void
    {
        [$root, , , $metaTitle] = $this->createNestedForm();

        self::assertSame('basedata[metadata][metaTitle]', $metaTitle->getFullyQualifiedName());

        $root->render();
        self::assertSame('basedata[metadata][metaTitle]', $metaTitle->getFullyQualifiedName());

        $root->render();
        self::assertSame('basedata[metadata][metaTitle]', $metaTitle->getFullyQualifiedName());
    }

    public function testRenderingUploadElementDoesNotMutateFormAttributes(): void
    {
        $form = $this->newForm(ZeroForm::class);
        $form->setName('upload');
        $form->setDecorators([new ElementsDecorator()]);

        $file = (new SymfileElement())->setName('file');
        $file->setDecorators([new RenderPurityNoopDecorator()]);
        $form->addElement($file);

        self::assertTrue($form->requiresMultipartEncoding());
        self::assertNull($form->getAttrib('enctype'));
        $form->render();
        self::assertNull($form->getAttrib('enctype'));
        $form->render();
        self::assertNull($form->getAttrib('enctype'));
    }

    public function testMultipartRequirementIsDetectedThroughNestedSubforms(): void
    {
        $root = $this->newForm(ZeroForm::class);
        $root->setName('root');

        $uploadGroup = $this->newForm(SubZeroForm::class);
        $file = (new SymfileElement())->setName('attachment');
        $uploadGroup->addElement($file);
        $root->addSubForm($uploadGroup, 'uploads');

        self::assertTrue($root->requiresMultipartEncoding());
    }

    public function testHashRenderValueDoesNotOverwriteSubmittedValue(): void
    {
        $hash = (new HashElement(new RenderPuritySessionAdapter()))
            ->setName('hash')
            ->setValue('submitted-token');

        $renderValue = $hash->getRenderValue();

        self::assertIsString($renderValue);
        self::assertNotSame('', $renderValue);
        self::assertNotSame('submitted-token', $renderValue);
        self::assertSame('submitted-token', $hash->getValue());
    }

    /**
     * @return array{ZeroForm, SubZeroForm, SubZeroForm, TextElement}
     */
    private function createNestedForm(): array
    {
        $root = $this->newForm(ZeroForm::class);
        $root->setName('root');
        $root->setDecorators([new ElementsDecorator()]);

        $basedata = $this->newForm(SubZeroForm::class);
        $basedata->setDecorators([new ElementsDecorator()]);
        $title = (new TextElement())->setName('title')->setValue('Title');
        $title->setDecorators([new RenderPurityNoopDecorator()]);
        $basedata->addElement($title);

        $metadata = $this->newForm(SubZeroForm::class);
        $metadata->setDecorators([new ElementsDecorator()]);
        $metaTitle = (new TextElement())->setName('metaTitle')->setValue('Meta title');
        $metaTitle->setDecorators([new RenderPurityNoopDecorator()]);
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

final class RenderPurityNoopDecorator extends AbstractDecorator
{
    public function render(string $content = ''): string
    {
        return $content;
    }
}

final class RenderPuritySessionAdapter implements SessionAdapterInterface
{
    private bool $started = false;

    /** @var array<string, mixed> */
    private array $values = [];

    public function isStarted(): bool
    {
        return $this->started;
    }

    public function start(): void
    {
        $this->started = true;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->values[$key] ?? $default;
    }

    public function set(string $key, mixed $value): void
    {
        $this->values[$key] = $value;
    }

    public function remove(string $key): void
    {
        unset($this->values[$key]);
    }
}
