<?php declare(strict_types=1);

namespace DalPraS\UnitTests\Hardening;

use DalPraS\FormZero\Decorator\ElementBaseDecorator;
use DalPraS\FormZero\Element;
use DalPraS\FormZero\Element\CheckboxElement;
use DalPraS\FormZero\Element\HashElement;
use DalPraS\FormZero\Element\SelectElement;
use DalPraS\FormZero\Element\SelectMultiElement;
use DalPraS\FormZero\Element\TextElement;
use DalPraS\FormZero\Factory\FormFactory;
use DalPraS\FormZero\FieldPath;
use DalPraS\FormZero\Session\ArraySessionAdapter;
use DalPraS\FormZero\Upload\UploadedFileProviderInterface;
use DalPraS\FormZero\ZeroForm;
use DalPraS\SmartTemplate\Plugins\EscaperInterface;
use DalPraS\SmartTemplate\Plugins\HelpersInterface;
use DalPraS\SmartTemplate\TemplateEngine;
use InvalidArgumentException;
use LogicException;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Validation;

final class FormZeroHardeningTest extends TestCase
{
    public function testCsrfIsScopedToFinalNestedFieldNameAndErrorsAreVisible(): void
    {
        $factory = $this->factory();
        $hash = (new HashElement(new ArraySessionAdapter()))
            ->setFactory($factory)->setName('hash');
        $hash->init();
        $standaloneToken = $hash->getRenderValue();

        $child = $this->form();
        $child->addElement($hash);
        $root = $this->form();
        $root->addSubForm($child, 'shipping');
        self::assertSame('shipping[hash]', $hash->getFullyQualifiedName());

        $nestedToken = $hash->getRenderValue();
        self::assertNotSame($standaloneToken, $nestedToken);
        self::assertFalse($hash->isValid($standaloneToken));
        self::assertTrue($hash->hasErrors());
        self::assertSame(['Invalid or expired form security token.'], $hash->getMessages());
        self::assertSame($nestedToken, $hash->getRenderValue(), 'An invalid token must not rotate the valid token');

        self::assertTrue($root->isValid(['shipping' => ['hash' => $nestedToken]]));
        self::assertFalse($hash->hasErrors());
        self::assertSame([], $root->getMessages());
        self::assertNotSame($nestedToken, $hash->getRenderValue(), 'Successful validation rotates the token');

        $root->removeSubForm('shipping');
        self::assertSame('hash', $hash->getFullyQualifiedName());
        self::assertSame($standaloneToken, $hash->getRenderValue(), 'Detached form uses its original CSRF scope');
    }

    public function testCsrfMissingArrayAndInvalidValuesReportElementErrors(): void
    {
        $hash = (new HashElement(new ArraySessionAdapter()))
            ->setFactory($this->factory())->setName('hash');
        $hash->init();
        $initial = $hash->getHash();

        foreach ([null, [], '', 'invalid'] as $incoming) {
            self::assertFalse($hash->isValid($incoming));
            self::assertTrue($hash->hasErrors());
            self::assertSame(['Invalid or expired form security token.'], $hash->getMessages());
            self::assertSame($initial, $hash->getRenderValue());
        }

        self::assertTrue($hash->isValid($initial));
        self::assertFalse($hash->hasErrors());
        self::assertSame([], $hash->getMessages());
    }

    public function testRequiredMultiValueRejectsEmptyArrayAndBlankSelections(): void
    {
        $element = (new SelectMultiElement())
            ->setFactory($this->factory())->setName('tags')->setRequired(true);
        $element->setMultiChoices(['One' => 'one']);

        self::assertFalse($element->isValid([]));
        self::assertTrue($element->hasErrors());
        self::assertFalse($element->isValid(['']));
        self::assertTrue($element->isValid(['one']));
        self::assertSame([], $element->getMessages());

        $element->setRequired(false);
        $element->setAllowEmpty(true);
        self::assertTrue($element->isValid([]));
    }

    public function testDynamicChoicesDoNotAccumulateAndFollowUpdatedOptions(): void
    {
        $select = (new SelectElement())
            ->setFactory($this->factory())->setName('plan')->setRequired(true);
        $select->setMultiChoices(['Old' => 'old']);
        $persistent = $select->getConstraints();

        self::assertTrue($select->isValid('old'));
        self::assertTrue($select->isValid('old'));
        self::assertSame($persistent, $select->getConstraints());

        $select->setMultiChoices(['New' => 'new']);
        self::assertFalse($select->isValid('old'));
        self::assertTrue($select->hasErrors());
        self::assertTrue($select->isValid('new'));
        self::assertSame($persistent, $select->getConstraints());
    }

    public function testNestedExplicitFieldPathsCannotReadUnrelatedSubmittedValuesOrDefaults(): void
    {
        $root = $this->form();
        $profile = $this->form();
        $nickname = (new HardeningProbeElement())
            ->setName('nickname')->setBelongsTo('account[preferences]');
        $profile->addElement($nickname);
        $root->addSubForm($profile, 'profile');
        self::assertSame('profile[account][preferences][nickname]', $nickname->getFullyQualifiedName());

        $root->setDefaults(['profile' => ['nickname' => 'WRONG']]);
        self::assertNull($nickname->getValue());

        self::assertTrue($root->isValid(['profile' => ['nickname' => 'WRONG']]));
        self::assertNull($nickname->lastValidatedValue);
        self::assertSame([], $root->getValidValues(['profile' => ['nickname' => 'WRONG']]));

        $correct = ['profile' => ['account' => ['preferences' => ['nickname' => 'RIGHT']]]];
        $root->setDefaults($correct);
        self::assertSame('RIGHT', $nickname->getValue());
        self::assertTrue($root->isValid($correct));
        self::assertSame('RIGHT', $nickname->lastValidatedValue);
        self::assertSame($correct, $root->getValidValues($correct));
    }

    public function testBadOrderDoesNotPartiallyInsertAnElement(): void
    {
        $form = $this->form();
        $form->addElement((new TextElement())->setName('first'), 5);
        try {
            $form->addElement((new TextElement())->setName('second'), 5);
            self::fail('Expected duplicate explicit order to be rejected');
        } catch (LogicException) {
            self::assertFalse($form->hasElement('second'));
            self::assertNull($form->get('second'));
            self::assertSame(['first'], array_keys(iterator_to_array($form)));
        }
    }

    public function testFailedReplacementDoesNotDeleteOriginalElement(): void
    {
        $form = $this->renderFactory()->createForm(ZeroForm::class);
        $old = $form->add(TextElement::class, 'title');
        try {
            $form->replaceElement('\\No\\Such\\FormElement', 'title');
            self::fail('Expected element factory failure');
        } catch (\Throwable) {
            self::assertSame($old, $form->getElement('title'));
            self::assertSame(['title'], array_keys(iterator_to_array($form)));
        }
    }

    public function testGetMultiElementReturnsNullForPlainElement(): void
    {
        $form = $this->form();
        $form->addElement((new TextElement())->setName('title'));
        $form->addElement((new SelectElement())->setName('category'));
        self::assertNull($form->getMultiElement('title'));
        self::assertNull($form->getMultiElement('missing'));
        self::assertSame($form->getElement('category'), $form->getMultiElement('category'));
    }

    public function testCheckboxHiddenFallbackDoesNotDuplicateTheVisibleControlId(): void
    {
        $factory = $this->renderFactory();
        $checkbox = (new CheckboxElement())->setFactory($factory)->setName('enabled');
        $checkbox->setRenderBelongsTo('options');
        $template = require dirname(__DIR__, 2) . '/resources/templates/include/elements/checkbox.php';
        $html = $template->call($factory->template(), $factory->template()->collection(), $checkbox, new ElementBaseDecorator());

        self::assertSame(1, substr_count($html, 'id="options-enabled"'));
        self::assertSame(2, substr_count($html, 'name="options[enabled]"'));
        self::assertSame(1, substr_count($html, 'type="hidden"'));
        self::assertSame(1, substr_count($html, 'type="checkbox"'));
    }

    public function testRadioOptionTextIsEscaped(): void
    {
        $factory = $this->renderFactory();
        $radio = (new \DalPraS\FormZero\Element\RadioElement())
            ->setFactory($factory)->setName('plan');
        $radio->setDisableTranslator(true);
        $radio->setMultiChoices(['<img src=x onerror=alert(1)>' => 'safe']);
        $template = require dirname(__DIR__, 2) . '/resources/templates/include/elements/radio.php';
        $html = $template->call($factory->template(), $factory->template()->collection(), $radio, new ElementBaseDecorator());
        self::assertStringNotContainsString('<img', $html);
        self::assertStringContainsString('&lt;img', $html);
    }

    private function form(): ZeroForm
    {
        return (new ReflectionClass(ZeroForm::class))->newInstanceWithoutConstructor();
    }

    private function factory(): FormFactory
    {
        return new FormFactory(new TemplateEngine(), $this->uploadProvider(), Validation::createValidator());
    }

    private function renderFactory(): FormFactory
    {
        $escaper = new class implements EscaperInterface {
            public function escapeHtml(string $text): string
            {
                return htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            }
            public function escapeJs(string $text): string { return $text; }
            public function escapeCss(string $text): string { return $text; }
            public function escapeUrl(string $text): string { return rawurlencode($text); }
        };
        $helpers = $this->createStub(HelpersInterface::class);
        $helpers->method('escaper')->willReturn($escaper);
        $engine = (new TemplateEngine())->setHelpers($helpers)->register('test', [
            'tag' => ['input' => '<input type="{type}" value="{value}" {attributes}>'],
            'form' => ['html' => [
                'form-element-checkbox' => '<input type="{type}" value="{value}" {checked} {attributes}>{content}',
            ]],
        ], true);
        $engine->addCustomParamCallback('{attributes}', static fn($param): string =>
            $param === null ? '' : $engine->attributes($param));
        return new FormFactory($engine, $this->uploadProvider(), Validation::createValidator());
    }

    private function uploadProvider(): UploadedFileProviderInterface
    {
        return new class implements UploadedFileProviderInterface {
            public function get(FieldPath $fieldPath): array|UploadedFile|null { return null; }
        };
    }
}

final class HardeningProbeElement extends Element
{
    public mixed $lastValidatedValue = null;

    public function isValid($value, $context = null): bool
    {
        $this->lastValidatedValue = $value;
        $this->setValue($value);
        return true;
    }
}
