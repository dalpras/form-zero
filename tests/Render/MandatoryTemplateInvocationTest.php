<?php declare(strict_types=1);

namespace DalPraS\UnitTests\Render;

use DalPraS\FormZero\Decorator\FormDecorator;
use DalPraS\FormZero\Factory\FormFactory;
use DalPraS\FormZero\FieldPath;
use DalPraS\FormZero\Upload\UploadedFileProviderInterface;
use DalPraS\FormZero\ZeroForm;
use DalPraS\SmartTemplate\Plugins\HelpersInterface;
use DalPraS\SmartTemplate\Plugins\TranslatorInterface;
use DalPraS\SmartTemplate\TemplateEngine;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class MandatoryTemplateInvocationTest extends TestCase
{
    /**
     * Regression: real mandatory.php was declared with a required $render
     * argument, while FormDecorator invokes it with no arguments.
     */
    public function testRealMandatoryTemplateRendersWithNoArguments(): void
    {
        $translator = $this->createStub(TranslatorInterface::class);
        $translator->method('trans')->willReturn('Required fields');
        $helpers = $this->createStub(HelpersInterface::class);
        $helpers->method('translator')->willReturn($translator);

        $engine = (new TemplateEngine())->setHelpers($helpers);
        $mandatory = $engine->require(__DIR__ . '/../../resources/templates/include/components/mandatory.php');
        $engine->register('test', [
            'tag' => ['form' => '<form>{content}</form>'],
            'form' => ['components' => ['mandatory' => $mandatory]],
        ], true);

        $factory = new FormFactory(
            $engine,
            new class implements UploadedFileProviderInterface {
                public function get(FieldPath $fieldPath): array|UploadedFile|null
                {
                    return null;
                }
            },
            $this->createStub(ValidatorInterface::class),
        );

        $form = $factory->createForm(ZeroForm::class);
        $form->setName('article');
        $form->setDecorators([new FormDecorator(['mandatory' => true])]);

        self::assertSame('<form><p>Required fields</p></form>', $form->render());
    }
}
