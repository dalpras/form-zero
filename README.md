# FormZero

FormZero is a PHP 8.4.1+ form library built around three ideas:

1. **Forms are PHP objects.** Fields, validation, filtering, nested data, and rendering are configured in PHP.
2. **Rendering is modular.** FormZero renders through decorators and [Smart Template](../smart-template) instead of coupling forms to Twig, Blade, or another full view framework.
3. **Application infrastructure is injected.** The library receives a template engine, Symfony validator, optional translator, and an upload provider. It does not read the current HTTP request directly.

FormZero is used as a form library, not as an HTTP framework. Your controller/application is responsible for obtaining POST data, deciding when to validate, persisting values, and handling successful/failed requests.

---

## Requirements

The package currently requires:

- PHP `>= 8.4.1` (required by Symfony 8.1)
- `dalpras/smart-template ^5.0`
- `giggsey/libphonenumber-for-php ^9.0` (phone filter and validator)
- Symfony Validator `^8.1`
- Symfony HttpFoundation `^8.1` (uploaded files and optional session adapter)

Form labels and feedback are translated through SmartTemplate helpers; the FormZero factory does not need a separate translator. SmartTemplate declares its dependency on `symfony/translation-contracts`.

The package intentionally uses Symfony validation and `UploadedFile` objects, but FormZero itself does not depend on Symfony's `Request` object.

---

## Installation

When the package is available through Composer:

```bash
composer require dalpras/form-zero
```

In this repository it is normally installed as a local/path package together with `dalpras/smart-template`.

---

## Core concepts

A typical FormZero request uses these objects:

```text
Application/controller
        |
        | POST data
        v
     ZeroForm
        |
        +-- Elements / nested ZeroForms
        +-- FormDataMapper
        +-- FormValidator
        +-- Decorators
        |
        v
   FormFactory
        |
        +-- TemplateEngine
        +-- ValidatorInterface
        `-- UploadedFileProviderInterface
```

The public entry point is normally `FormFactory`.

---

# 1. Configure FormFactory

`FormFactory` requires three services. Form translations use the SmartTemplate helpers:

```php
use DalPraS\FormZero\Factory\FormFactory;
use DalPraS\FormZero\Upload\UploadedFileProviderInterface;
use DalPraS\SmartTemplate\TemplateEngine;
use Symfony\Component\Validator\Validator\ValidatorInterface;

$formFactory = new FormFactory(
    template: $template,
    uploadedFileProvider: $uploadedFileProvider,
    validator: $validator,
);
```

The constructor does **not** create any of these services for you.

This is deliberate: validator configuration, translation resources, upload access, and template customization belong to the application.

---

## 1.1 Configure Smart Template

The simplest standalone setup is:

```php
use DalPraS\FormZero\Preset\FormPreset;
use DalPraS\SmartTemplate\TemplateEngine;

$template = new TemplateEngine();
FormPreset::register($template); // Registers HtmlPreset and its |join modifier.
```

FormZero passes HTML attribute arrays as `'{attributes}|join'` so rendering
and escaping happen together in one pass. No custom `{attributes}` callback
or `TemplateEngine::attributes()` method is required.

If your application has a custom escaper or needs to configure the escaped
attribute names, override the default after registering the preset:

```php
use DalPraS\SmartTemplate\HtmlAttributes;

$escaper = $helpers->escaper();
(new HtmlAttributes(
    escape: static fn(string $value): string => $escaper->escapeHtml($value),
    escapedNames: ['id', 'title', 'name', 'alt', 'aria-label'],
    omitEmptyNames: ['id', 'name'],
))->register($template);
```

`{attributes}` remains the token in HTML template strings; the `|join`
modifier belongs on the **argument array key**, not inside markup. For
example: `['{attributes}|join' => ['class' => 'form-control']]`.

`FormPreset::register()` registers the FormZero templates from:

```text
resources/templates/form.php
```

under the `form` namespace.

If your application already has a shared UI preset/namespace, you may merge the FormZero templates into that namespace instead. The Vimar application does this through its `TemplateFactory`.

---

## 1.2 Configure Symfony Validator

FormZero expects an already-created `ValidatorInterface`.

Minimal example:

```php
use Symfony\Component\Validator\Validation;

$validator = Validation::createValidatorBuilder()
    ->getValidator();
```

With translations:

```php
use Symfony\Component\Translation\Translator;
use Symfony\Component\Validator\Validation;

/** @var Translator $translator */
$validator = Validation::createValidatorBuilder()
    ->setTranslator($translator)
    ->setTranslationDomain('validators')
    ->getValidator();
```

Create the validator once in your DI container and reuse it. Do not create a new Symfony validator for every form or every field.

---

## 1.3 Configure uploaded files

FormZero does not read `Request::$files` directly. It asks an `UploadedFileProviderInterface` for the upload associated with a compiled `FieldPath`.

```php
use DalPraS\FormZero\FieldPath;
use DalPraS\FormZero\Upload\UploadedFileProviderInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

interface UploadedFileProviderInterface
{
    /** @return UploadedFile|UploadedFile[]|null */
    public function get(FieldPath $fieldPath): array|UploadedFile|null;
}
```

A Symfony HTTP application can use a small adapter:

```php
use DalPraS\FormZero\FieldPath;
use DalPraS\FormZero\Upload\UploadedFileProviderInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;

final readonly class SymfonyUploadedFileProvider implements UploadedFileProviderInterface
{
    public function __construct(
        private Request $request,
    ) {
    }

    public function get(FieldPath $fieldPath): array|UploadedFile|null
    {
        $file = $fieldPath->find($this->request->files->all());

        return $file instanceof UploadedFile || is_array($file)
            ? $file
            : null;
    }
}
```

For an application that never handles uploads, provide an implementation that simply returns `null`:

```php
final class NullUploadedFileProvider implements UploadedFileProviderInterface
{
    public function get(FieldPath $fieldPath): array|UploadedFile|null
    {
        return null;
    }
}
```

---

# 2. Create a form

Create a class extending `ZeroForm` and define its structure in `init()`.

```php
<?php

declare(strict_types=1);

namespace App\Form;

use DalPraS\FormZero\Element\EmailElement;
use DalPraS\FormZero\Element\SubmitElement;
use DalPraS\FormZero\Element\TextElement;
use DalPraS\FormZero\FormLayout;
use DalPraS\FormZero\ZeroForm;
use Symfony\Component\Validator\Constraints as Assert;

final class ContactForm extends ZeroForm
{
    public function init(): void
    {
        $this->setAttribs([
            'method' => 'post',
            'action' => '',
        ]);

        $this->add(new TextElement(), 'name', [
            'label' => 'Name',
            'required' => true,
            'attribs' => [
                'autocomplete' => 'name',
                'maxlength' => 100,
            ],
            'filters' => [
                \DalPraS\FormZero\Filter\StringTrim::class,
                \DalPraS\FormZero\Filter\StripTags::class,
            ],
        ]);

        $this->add(new EmailElement(), 'email', [
            'label' => 'Email',
            'required' => true,
            'constraints' => [
                new Assert\Email(),
            ],
        ]);

        $this->add(new SubmitElement(), 'submit', [
            'text' => 'Send',
            'ignore' => true,
            'layout' => FormLayout::Submit,
        ]);
    }
}
```

Create it through the factory:

```php
$form = $formFactory->createForm(ContactForm::class);
```

`createForm()` performs these steps automatically:

1. creates the form and injects the factory;
2. calls `init()`;
3. installs default form decorators if the form did not define its own decorators.

---

## Important: element class names, not aliases

This is valid:

```php
$form->add(new TextElement(), 'name');
```

This is also valid:

```php
$form->add(TextElement::class, 'name');
```

This is **not** valid:

```php
$form->add('text', 'name');
```

FormZero does not currently provide string aliases such as `text`, `email`, or `select`.

---

# 3. Process a form in a controller

A normal controller flow is:

```php
$form = $formFactory->createForm(ContactForm::class);

if ($request->isMethod('POST')) {
    $data = $request->request->all();

    if ($form->isValid($data)) {
        $values = $form->getValues();

        // Persist/use $values.
    }
}

$html = $form->render();
```

Important points:

- pass normal submitted fields to `isValid()`;
- uploaded files are obtained independently through the configured upload provider;
- call `getValues()` after successful validation when you want normalized/filtered values;
- render the same form instance to show validation feedback.

---

# 4. Adding elements

`ZeroForm::add()` creates an element through `FormFactory`, adds it to the form, and returns the same stored instance.

```php
$element = $this->add(new TextElement(), 'title', [
    'label' => 'Title',
    'required' => true,
]);

$element->setDescription('Displayed below the field.');
```

Descriptions belong to individual elements, not to the form. Likewise, use the `order` argument of `addElement()` or `addSubForm()` to control the order of children; the form does not have its own `setOrder()` property.

The most commonly used option keys are:

| Option | Meaning |
|---|---|
| `label` | Element label |
| `description` | Help/description text |
| `required` | Marks the element required |
| `requiredMessage` | Custom required message |
| `allowEmpty` | Controls whether an optional empty value is accepted |
| `ignore` | Excludes the field from `getValues()` / `getValidValues()` |
| `isArray` | Treats the element value as an array |
| `filters` | Value filters applied before validation/value retrieval |
| `constraints` | Symfony Validator constraints |
| `attribs` | HTML attributes |
| `decorators` | Custom decorator stack |
| `layout` | A `FormLayout` preset |
| `disableTranslator` | Disables translation for that element |

Prefer explicit HTML attributes inside `attribs`:

```php
$this->add(new TextElement(), 'name', [
    'label' => 'Name',
    'attribs' => [
        'class' => 'form-control',
        'autocomplete' => 'name',
        'maxlength' => 100,
    ],
]);
```

`initOptions()` maps known option names to setters. Unknown keys fall back to HTML attributes, so using `attribs` explicitly makes configuration errors easier to see and review.

---

# 5. Available element types

The package currently provides:

```text
TextElement
TextareaElement
EmailElement
PasswordElement
SearchElement
HiddenElement
SubmitElement
CheckboxElement
CheckboxMultiElement
RadioElement
RadioImageElement
RadioPopupElement
SelectElement
SelectMultiElement
DatePickerElement
HashElement
SymfileElement
SymfileMultiElement
```

Choose the concrete element type that matches the rendered control and validation semantics you need.

---

# 6. Choice elements

Choice elements such as `SelectElement`, `RadioElement`, and their multi-value variants accept `multiChoices` as a `label => value` array.

```php
$this->add(new SelectElement(), 'country', [
    'label' => 'Country',
    'multiChoices' => [
        'Italy' => 'IT',
        'France' => 'FR',
        'Germany' => 'DE',
    ],
    'required' => true,
]);
```

The submitted value is validated against the configured choices.

`RadioElement` escapes **choice labels** as text by default. If the labels
contain trusted HTML created by your application (for example, icons), enable
`htmlChoiceLabels` on that specific radio group:

```php
$this->add(new RadioElement(), 'icon', [
    'label' => 'Icon',
    'htmlChoiceLabels' => true,
    'multiChoices' => [
        '<i class="fa-solid fa-book" aria-hidden="true"></i><span class="visually-hidden">Book</span>' => 'book',
        '<i class="fa-solid fa-globe" aria-hidden="true"></i><span class="visually-hidden">Globe</span>' => 'globe',
    ],
]);
```

**Security:** this option disables HTML escaping for *all choice labels in
that radio group*. Use it only for developer-controlled HTML; never include
unsanitized user or database text. Values, input attributes and normal radio
labels continue to be escaped. Do not rely on detecting `<` in a label to
decide whether it is HTML. HTML choice labels bypass automatic translation, while the surrounding field
label can still be translated. Translate/escape any dynamic text parts before
composing the markup.
A visible or screen-reader-only text name is needed when a choice consists of
an icon alone. The radio choice `<label>` is linked to its unique input ID,
so clicking the icon selects the option.

Multi-value elements use array semantics and validate each submitted value.

---

# 7. Filters and normalized values

Filters normalize values before validation and before values are returned from the form.

Example:

```php
use DalPraS\FormZero\Filter\StringTrim;
use DalPraS\FormZero\Filter\StripTags;

$this->add(new TextElement(), 'title', [
    'filters' => [
        StripTags::class,
        StringTrim::class,
    ],
]);
```

Input:

```text
"   <b>Hello</b>   "
```

Normalized value:

```text
"Hello"
```

FormZero caches the normalized value. Repeated calls to `getValue()` do not rerun an unchanged filter chain.

The cache is invalidated when:

- the raw value changes;
- array mode changes;
- the filter chain changes.

Available built-in filters include:

```text
StringTrim
StripTags
StripNewlines
StringToLower
StringToUpper
ToBoolean
ToFloat
ToInt
ToNull
ToString
ParseEmailFilter
PhoneNumberFilter
PublicKeyClean
CamelCaseToDash
CamelCaseToSeparator
DashToUnderscore
SeparatorToCamelCase
SeparatorToSeparator
```

Filters may also be passed as configured arrays:

```php
'filters' => [
    [ToNull::class, ['type' => ToNull::TYPE_STRING]],
],
```

---

# 8. Validation

FormZero delegates actual constraint validation to Symfony Validator.

```php
use Symfony\Component\Validator\Constraints as Assert;

$this->add(new TextElement(), 'code', [
    'required' => true,
    'constraints' => [
        new Assert\Length(max: 20),
        new Assert\Regex('/^[A-Z0-9]+$/'),
    ],
]);
```

## Required fields

When `required => true`, FormZero automatically prepends a Symfony `NotBlank` constraint unless you already supplied one.

You can customize the implicit required message:

```php
$this->add(new TextElement(), 'name', [
    'required' => true,
    'requiredMessage' => 'Name is required.',
]);
```

### Required-field marker and legend

Required elements with a non-empty label receive the `required` CSS class on their rendered label. The application theme can use that class to display an asterisk, for example:

```css
label.required::before {
    content: '* ';
}
```

`FormDecorator` also renders the translated mandatory-fields legend automatically when the form, or one of its nested subforms, contains at least one required labeled element.

You can override that automatic behavior per form:

```php
// Always hide the mandatory-fields legend.
[FormDecorator::class, ['mandatory' => false]]

// Always show the mandatory-fields legend.
[FormDecorator::class, ['mandatory' => true]]
```

When the `mandatory` option is omitted, automatic detection is used.

## Full validation

```php
if ($form->isValid($data)) {
    // Every field, including absent required fields, has been checked.
}
```

## Partial validation

Use `isValidPartial()` when you intentionally want to validate only fields present in the input:

```php
if ($form->isValidPartial($data)) {
    // Missing required fields are not checked.
}
```

This is useful for PATCH-like workflows, builders, wizards, or draft persistence.

## Validation messages

```php
$messages = $form->getMessages();
```

For one element:

```php
$messages = $form->getMessagesForElement('email');
```

You may also add form-level errors manually:

```php
$form->addError('The operation could not be completed.');
```

---

# 9. Values and defaults

## Defaults

```php
$form->setDefaults([
    'name' => 'Mario Rossi',
    'email' => 'mario@example.com',
]);
```

Or one value:

```php
$form->setDefault('name', 'Mario Rossi');
```

## Read one value

```php
$name = $form->getValue('name');
```

## Read all values

```php
$values = $form->getValues();
```

`getValues()` returns normalized/filtered values and excludes elements marked with:

```php
'ignore' => true
```

`disabled` and `ignore` are **not** equivalent. A disabled element is still part of the FormZero value model unless it is also explicitly ignored.

## Read only valid values

```php
$validValues = $form->getValidValues($data);
```

`getValidValues()` validates submitted fields and returns only values that pass their element validation. This is useful for partially valid/draft workflows.

---

# 10. Nested forms and array notation

A regular `ZeroForm` is **reusable**: it operates as a standalone HTML form and
becomes a nested group only while attached to a parent through `addSubForm()`.
The same field definitions, filters and validation rules are used in both modes.

For example, define the reusable form once:

```php
use DalPraS\FormZero\ZeroForm;
use DalPraS\FormZero\Element\TextElement;

final class MetadataForm extends ZeroForm
{
    public function init(): void
    {
        $this->setName('MetadataForm');
        $this->add(new TextElement(), 'metaTitle', ['label' => 'Meta title']);
    }
}
```

Use it independently to edit metadata:

```php
$metadata = $formFactory->createForm(MetadataForm::class);
// $metadata->render() produces its own <form> element.
// $metadata->isValid($post) and $metadata->getValues() use flat keys.
```

Or create a **new instance of exactly the same class** for a larger form, as in
Article's `basedata -> metadata` tree:

```php
$article = $formFactory->createForm(ZeroForm::class);
$basedata = $formFactory->createForm(ZeroForm::class);
$metadata = $formFactory->createForm(MetadataForm::class);

$basedata->addSubForm($metadata, 'metadata');
$article->addSubForm($basedata, 'basedata');
// The metadata field is now basedata[metadata][metaTitle].
// The nested metadata form renders as <fieldset>, NOT another <form>.
// The parent validates and collects the complete data tree.
```

`$metadata->getValues(true)` returns its local values in nested mode;
`$article->getValues()` returns `['basedata' => ['metadata' => [...]]]`.
Calling `removeSubForm('metadata')` restores the child's original name,
standalone mapping and form rendering without modifying its decorators.
An attached instance cannot simultaneously belong to a second parent; use a
fresh factory-created instance wherever the same form appears twice.

All forms attached through `addSubForm()` are automatically namespaced under
that child key: no `ZeroForm::setIsArray()` is necessary or available. The
`isArray()` getter remains for internal namespace handling and reflects the
current nesting context. For a standalone form that intentionally uses a
namespaced payload, call `setElementsBelongTo('address')` explicitly. This does
not affect `Element::setIsArray()`, which is still required for individual
multiple-value fields. Custom decorators, such as Article's accordion/card
renderers, are left untouched. A normal FormDecorator is contextually
rendered as a fieldset when nested.

All nested groups are ordinary `ZeroForm` instances constructed through the
form factory; no separate nested-form class or creator method is needed. The
Article accordion/card decorators retain their existing `data-row-for` IDs
using explicit `id` attributes on the few groups that need stable UI selectors.
The same approach applies to Contact accordion groups.

PHP-style array keys containing hyphens, such as the Article image locale
`localized[x-default][title]`, are preserved in names, mapping, validation
and HTML field paths. A nested form's key must still be a single field-name
segment (bracket characters are not allowed in an `addSubForm()` key).

`Hydrator::hydrateForm($metadata, $callback)` continues to accept local
metadata fields when called directly on a child already nested under
`basedata[metadata]`, as in `ArticleForm::hydrateDefaults()`. For ordinary
`setDefaults()`/`isValid()` calls, use the child's named input branch when
it is nested, or call these methods on the parent with the full data tree.

### Building nested groups with ZeroForm

Create a normal form through the factory and attach it under the desired key:

```php
$metadata = $this->factory->createForm(ZeroForm::class);
$metadata->add(new TextElement(), 'metaTitle', [
    'label' => 'Meta title',
]);
$this->addSubForm($metadata, 'metadata');
```

For deeper structures, nest additional ordinary forms:

```php
$baseData = $this->factory->createForm(ZeroForm::class);
$metadata = $this->factory->createForm(ZeroForm::class);
$metadata->add(new TextElement(), 'metaTitle');
$baseData->addSubForm($metadata, 'metadata');
$this->addSubForm($baseData, 'basedata');
```

The nested field is named `basedata[metadata][metaTitle]` with HTML ID
`basedata-metadata-metaTitle`. Data extraction, validation, defaults and uploads
use the same hierarchical path. The standalone form keeps its normal field names
and HTML `<form>` rendering until attached.

Set an explicit `id` on a nested form when an accordion or external JavaScript
selector must stay fixed while field names use the hierarchical structure:

```php
$metadata->setAttrib('id', 'metadata');
```

### Subform legends

A subform has one legend rule and one renderer for it. The legend value belongs to the form:

```php
$metadata = $this->factory->createForm(ZeroForm::class);
$metadata->setLegend('Metadata');
$this->addSubForm($metadata, 'metadata');
```

A standalone `ZeroForm` defaults to `ElementsDecorator -> FormDecorator`.
When attached to a parent, `FormDecorator` renders a `FieldsetDecorator`
instead of creating a nested HTML `<form>` tag. Its original decorators remain
unchanged so the form can be detached and reused standalone.

`FieldsetDecorator` renders both the semantic `<fieldset>` wrapper and, when `getLegend()` is not empty, its `<legend>`. There is no separate legend decorator and no independent legend value on the fieldset decorator: `setLegend()` on the form/subform is the single source of truth.

For the uncommon case where a fieldset is required but its legend must not be rendered, suppress only the rendering:

```php
use DalPraS\FormZero\Decorator\ElementsDecorator;
use DalPraS\FormZero\Decorator\FieldsetDecorator;

$metadata->setDecorators([
    ElementsDecorator::class,
    [FieldsetDecorator::class, ['renderLegend' => false]],
]);
```

Do not pass a second `legend` option to `FieldsetDecorator`; change the legend through `$metadata->setLegend(...)`.

---

# 11. File uploads

Use `SymfileElement` for one file and `SymfileMultiElement` for multiple files.

```php
use DalPraS\FormZero\Element\SymfileElement;
use Symfony\Component\Validator\Constraints as Assert;

$this->add(new SymfileElement(), 'attachment', [
    'label' => 'CSV file',
    'required' => true,
    'constraints' => [
        new Assert\File([
            'maxSize' => '10M',
            'extensions' => ['csv'],
        ]),
    ],
]);
```

FormZero automatically detects file elements when rendering the form and adds multipart encoding to the rendered `<form>` element. You do not need rendering code to mutate the form attributes manually.

## Correct upload processing order

Treat `form->isValid()` as the authoritative validation step.

```php
use DalPraS\FormZero\Element\SymfileElement;
use Symfony\Component\HttpFoundation\File\UploadedFile;

$data = $request->request->all();

if ($form->isValid($data)) {
    /** @var SymfileElement $attachment */
    $attachment = $form->getElement('attachment');

    $uploadedFile = $attachment->getUploadedFiles();

    if (!$uploadedFile instanceof UploadedFile) {
        throw new \LogicException(
            'Expected a valid upload after successful form validation.'
        );
    }

    // Store/process $uploadedFile.
}
```

Do **not** repeat application-level upload validation after the form has succeeded:

```php
// Avoid this in controller/business code after successful form validation.
$uploadedFile->isValid();
```

A storage service may still perform defensive transport checks before physically moving the file.

## Multiple uploads

```php
use DalPraS\FormZero\Element\SymfileMultiElement;

$this->add(new SymfileMultiElement(), 'attachments', [
    'constraints' => [
        new Assert\File(maxSize: '10M'),
    ],
]);
```

After successful validation:

```php
/** @var SymfileMultiElement $attachments */
$attachments = $form->getElement('attachments');
$uploadedFiles = $attachments->getUploadedFiles();

if (!is_array($uploadedFiles)) {
    throw new \LogicException('Expected multiple uploaded files.');
}
```

## Nested upload fields

The provider receives a compiled `FieldPath`, so nested names work correctly:

```text
profile[documents][attachment]
```

The Symfony adapter traverses the nested `FileBag` structure instead of trying to pass bracket notation directly to `FileBag::get()`.

## Do not read raw request files in business code

Prefer:

```php
/** @var SymfileElement $attachment */
$attachment = $form->getElement('attachment');
$file = $attachment->getUploadedFiles();
```

instead of:

```php
$file = $request->files->get('attachment');
```

This keeps nested paths, provider behavior, and form validation consistent.

---

# 12. Layouts

`FormLayout` selects one of the built-in decorator stacks:

```php
use DalPraS\FormZero\FormLayout;

$this->add(new TextElement(), 'title', [
    'layout' => FormLayout::HorizontalWide,
]);
```

Available layouts:

| Layout | Intended use |
|---|---|
| `FormLayout::Horizontal` | Standard label/content horizontal field |
| `FormLayout::HorizontalWide` | Wider content column |
| `FormLayout::HorizontalNarrow` | Narrower content column |
| `FormLayout::FullWidth` | Full-width content |
| `FormLayout::Submit` | Submit/button row |
| `FormLayout::Raw` | Element content only |

You can also change layout after creation:

```php
$element->layout(FormLayout::Raw);
```

---

# 13. Decorators

Decorators define how an element or form is rendered.

A normal form receives these decorators by default:

```php
[
    [ElementsDecorator::class],
    [FormDecorator::class],
]
```

An element receives the decorator stack associated with its `FormLayout` unless you provide `decorators` explicitly.

Custom example:

```php
use DalPraS\FormZero\Decorator\ElementContentDecorator;
use DalPraS\FormZero\Decorator\ElementLabelDecorator;
use DalPraS\FormZero\Decorator\ElementWrapperDecorator;

$this->add(new TextElement(), 'title', [
    'decorators' => [
        [ElementContentDecorator::class],
        [ElementLabelDecorator::class, ['class' => 'col-form-label']],
        [ElementWrapperDecorator::class, ['class' => 'row mb-3']],
    ],
]);
```

A decorator entry may be:

- a decorator instance;
- a decorator class name;
- `[DecoratorClass::class, $options]`;
- `null` (removed by the factory).

---

## CallbackDecorator

`CallbackDecorator` is useful when a field needs application-specific rendering around otherwise standard element output.

```php
use DalPraS\FormZero\Decorator\CallbackDecorator;
use DalPraS\FormZero\ElementInterface;
use DalPraS\FormZero\ZeroForm;
use DalPraS\SmartTemplate\Collection\RenderCollection;

[
    CallbackDecorator::class,
    [
        'callback' => static function (
            string $content,
            RenderCollection $render,
            ElementInterface|ZeroForm $element,
            string $namespace,
        ): string {
            return '<div class="custom-wrapper">' . $content . '</div>';
        },
    ],
]
```

Rendering exceptions are **not** converted to HTML. If a callback/template throws, the exception propagates to the application error handler.

---

# 14. Rendering behavior

Render with:

```php
echo $form->render();
```

or:

```php
echo (string) $form;
```

Rendering is designed to be observational:

```php
$before = $form->getValues();
$html = $form->render();
$after = $form->getValues();

// $before and $after represent the same form data.
```

Rendering does not:

- rewrite nested `belongsTo` values;
- change normal element data;
- write `enctype` back into the form object merely to render multipart HTML;
- swallow rendering exceptions.

`HashElement` is a special case: its render value is the current CSRF token, while its stored form/data value remains independent.

---

# 15. Template overrides

You can override FormZero templates when registering `FormPreset`:

```php
FormPreset::register($template, overrides: [
    'input' => '<input class="my-input" {attributes}>',
]);
```

Or register overrides later in the same namespace:

```php
$template->register(FormPreset::NAMESPACE, [
    'input' => '<input class="my-input" {attributes}>',
]);
```

Later registration replaces/extends keys in that namespace according to Smart Template behavior.

The default FormZero template file is:

```text
resources/templates/form.php
```

---

# 16. CSRF with HashElement

FormZero provides `HashElement` for session-backed CSRF protection.

Example with the Symfony session adapter:

```php
use DalPraS\FormZero\Element\HashElement;
use DalPraS\FormZero\FormLayout;
use DalPraS\FormZero\Session\SymfonySessionAdapter;

$hash = new HashElement(
    new SymfonySessionAdapter($session),
);

$this->add($hash, 'hash')->layout(FormLayout::Raw);
```

Other provided session adapters are:

```text
ArraySessionAdapter
NativeSessionAdapter
SymfonySessionAdapter
```

`HashElement`:

- generates a cryptographically random token;
- stores token and expiry in the session;
- checks the submitted token with `hash_equals()`;
- rotates the token after validation according to its configured strategy;
- renders the current token without mutating the stored data value.

## Ignoring CSRF

The factory exposes:

```php
$formFactory->setIgnoreCsrfToken();
```

This is an application policy switch. Use it only when another trusted mechanism protects the request, for example an authenticated API flow that deliberately does not use browser-session CSRF protection.

---

# 17. Translation

FormZero uses the translator available from SmartTemplate helpers (not a separate factory dependency):

```php
$translator = $template->getHelpers()->translator();
```

Element translation can be disabled with:

```php
'disableTranslator' => true
```

or:

```php
$element->setDisableTranslator(true);
```

The validator's translator is configured separately when creating the Symfony validator.

---

# 18. Ordering elements and subforms

You may provide an explicit order when adding an element:

```php
$this->add(new TextElement(), 'second', [], order: 20);
$this->add(new TextElement(), 'first', [], order: 10);
```

The same ordering model is used for subforms.

FormZero uses `IteratorAggregate`, so independent/nested iteration is safe:

```php
foreach ($form as $outer) {
    foreach ($form as $inner) {
        // The inner iteration does not corrupt the outer iterator state.
    }
}
```

Duplicate explicit order positions are rejected.

---

# 19. Custom filters

A custom filter implements `FilterInterface`.

Use it exactly like a built-in filter:

```php
$this->add(new TextElement(), 'value', [
    'filters' => [
        MyFilter::class,
    ],
]);
```

You can also mutate the filter chain directly:

```php
$element->getFilterChain()->attach($filter);
```

FormZero tracks filter-chain revisions so the normalized-value cache is invalidated when filters change.

---

# 20. Custom elements

Custom elements are an advanced extension point.

The default `ElementBaseDecorator` deliberately dispatches only the built-in element classes. Therefore, creating a new subclass of `Element` is **not enough by itself**: you must also provide rendering support for that class, either by extending the FormZero renderer/template mapping or by supplying a custom decorator stack that renders the element without `ElementBaseDecorator`.

For small visual variations, prefer one of these approaches before creating a new element type:

- configure `attribs`;
- choose a different `FormLayout`;
- provide custom decorators;
- use `CallbackDecorator` around a supported element.

Create a new element class when it also represents genuinely different form semantics or validation behavior, and add matching rendering/tests at the same time.

---

# 21. Common mistakes

## Do not use element aliases

Wrong:

```php
$form->add('text', 'name');
```

Correct:

```php
$form->add(new TextElement(), 'name');
```

or:

```php
$form->add(TextElement::class, 'name');
```

## Do not construct the validator repeatedly

Create one shared `ValidatorInterface` and inject it into the factory.

## Do not read request files in form business logic

Wrong:

```php
$file = $request->files->get('attachment');
```

Preferred:

```php
/** @var SymfileElement $attachment */
$attachment = $form->getElement('attachment');
$file = $attachment->getUploadedFiles();
```

## Do not validate an uploaded file twice in controllers

After successful form validation, retrieve and use the upload. Leave defensive file-transport checks to the storage boundary.

## Do not confuse `disabled` and `ignore`

`ignore` controls whether FormZero returns the value. A disabled HTML control is a rendering/browser concern.

## Do not rely on rendering to initialize data

Set defaults/values explicitly. Rendering is pure with respect to form data.

## Do not swallow decorator/template exceptions

FormZero intentionally propagates rendering errors. Let the application error handler log/display them according to environment.

---

# 22. Recommended controller pattern

A complete non-upload example:

```php
$form = $formFactory->createForm(ProfileForm::class);

$form->setDefaults($existingValues);

if ($request->isMethod('POST')) {
    $data = $request->request->all();

    if ($form->isValid($data)) {
        $values = $form->getValues();

        $service->save($values);

        // Redirect or render a success response.
    }
}

echo $form->render();
```

A complete upload example:

```php
$form = $formFactory->createForm(DocumentUploadForm::class);

if ($request->isMethod('POST')) {
    $data = $request->request->all();

    if ($form->isValid($data)) {
        /** @var SymfileElement $attachment */
        $attachment = $form->getElement('attachment');

        $file = $attachment->getUploadedFiles();

        if (!$file instanceof UploadedFile) {
            throw new \LogicException(
                'Expected a valid uploaded file after successful validation.'
            );
        }

        $documentService->store($file);
    }
}

echo $form->render();
```

---

# 23. Internal architecture

The current internal responsibilities are intentionally separated:

```text
ZeroForm
  |
  +-- FormDataMapper
  |     setDefaults()
  |     getValues()
  |     getValidValues()
  |
  +-- FormValidator
  |     isValid()
  |     isValidPartial()
  |     message aggregation
  |
  +-- FieldPath
  |     compiled nested names
  |     nested read/write/remove/find operations
  |
  +-- ElementsOrdered
  |     stable element/subform ordering
  |
  `-- Decorators
        rendering only
```

`ZeroForm` remains the public facade; the helper classes above are implementation details unless explicitly documented otherwise.

`FieldPath` is also used by the upload-provider contract because the provider needs the exact compiled nested file path.

---

# 24. Testing

The package uses PHPUnit in development:

```bash
composer install
./vendor/bin/phpunit
```

Useful focused suites include the tests for:

- `FieldPath` and compiled field names;
- data mapping;
- validation/message traversal;
- render purity;
- render exception propagation;
- ordering / `IteratorAggregate`;
- upload provider behavior.

When changing FormZero internals, preserve these contracts:

1. rendering must not change form data;
2. repeated value reads must remain stable;
3. nested names/IDs must remain stable;
4. upload lookup must follow compiled nested paths;
5. `isValid()` remains the authoritative form-validation step;
6. public `ZeroForm` APIs should remain compatible unless a breaking change is intentional and documented.

---

# 25. Migration notes from older FormZero versions

If you are updating older integration code, the most important changes are:

## Factory no longer receives Request

Old pattern:

```php
new FormFactory(
    template: $template,
    request: $request,
);
```

Current pattern:

```php
new FormFactory(
    template: $template,
    uploadedFileProvider: $uploadedFileProvider,
    validator: $validator,
);
```

## FormFactory no longer creates Validator

Inject a shared `ValidatorInterface`.

## FormFactory does not create TemplateEngine

Configure `TemplateEngine` and register FormZero templates before constructing the factory.

## Upload access moved behind UploadedFileProviderInterface

Business/controller code should retrieve uploaded files from the corresponding form element rather than directly from `Request::$files`.

## Rendering is pure

Do not depend on `render()` to mutate `belongsTo`, values, CSRF data state, or form attributes.

## Render exceptions propagate

Do not expect rendering errors to be returned as HTML strings. They now reach the normal application error handler.

## Element aliases are not supported

Use concrete element instances or element class names.

---

# 26. Quick reference

Create a form:

```php
$form = $formFactory->createForm(MyForm::class);
```

Validate:

```php
$valid = $form->isValid($data);
```

Validate only submitted fields:

```php
$valid = $form->isValidPartial($data);
```

Get normalized values:

```php
$values = $form->getValues();
```

Get only valid submitted values:

```php
$values = $form->getValidValues($data);
```

Set defaults:

```php
$form->setDefaults($defaults);
```

Get messages:

```php
$messages = $form->getMessages();
```

Render:

```php
$html = $form->render();
```

Get an element:

```php
$element = $form->getElement('name');
```

Get an upload after successful validation:

```php
/** @var SymfileElement $attachment */
$attachment = $form->getElement('attachment');
$file = $attachment->getUploadedFiles();
```

Check whether multipart rendering is required:

```php
$multipart = $form->requiresMultipartEncoding();
```

---

# 27. Validation and nested-form hardening

- **CSRF identity follows the rendered field path.** A `HashElement` created in a
  standalone form automatically switches to the nested session scope when its
  form is attached. Always obtain the token to submit from the **final rendered
  form**, rather than caching `getHash()` before nesting. Invalid, missing, or
  expired tokens return `false` and appear in `getMessages()`; they do not rotate
  the valid token. Successful validation rotates it as configured.
- **Submitted field paths must match their declared namespace.** For instance,
  `profile[account][preferences][nickname]` cannot be supplied as
  `profile[nickname]`. Standalone forms can still use `setElementsBelongTo()` to
  namespace the entire form, and their established unwrapped-default behavior
  remains supported.
- **A required multi-value field rejects `[]`.** Optional multi-value elements
  still accept an empty selection when `allowEmpty` is enabled.
- **Generated validation constraints are temporary.** Requiredness and choice
  constraints are assembled per validation, so repeated validation or changes
  to `setMultiChoices()` do not leave stale constraints in `getConstraints()`.
- **Element insertion is atomic for order collisions.** If the requested order
  is already taken, `addElement()` raises `LogicException` without inserting
  the new element. `replaceElement()` creates the replacement before deleting
  the old one, retaining the old element if construction fails.
- `getMultiElement()` returns `null` when the name is missing or belongs to an
  element that does not implement `MultiChoicesInterface`.
- Checkbox hidden fallback inputs have **no ID**; only the visible control owns
  the corresponding ID. Radio choice labels are HTML-escaped by default; use a
  dedicated trusted-content renderer instead of embedding markup into labels.

---

# License

See `composer.json` for the package license and metadata.

---

# Optional AJAX fragments (PHP payloads + native ES module)

FormZero's **core has no HTTP or JavaScript dependency**. For independently
saved nested forms, the package provides two optional, framework-neutral pieces:

- `DalPraS\FormZero\Ajax\FragmentResponse` builds response arrays; your
  controller chooses HTTP status, authorization, data persistence and
  `JsonResponse` (or equivalent).
- `resources/js/FormZeroAjax.mjs` progressively enhances explicitly marked
  forms and buttons. It uses `fetch`, `FormData`, `CustomEvent` and one delegated
  listener per enhanced form; it requires no npm package or frontend framework.

The script is a source module, not an automatically included browser asset.
Import it in your application's JS bundle or serve it as an ES module:

```js
import FormZeroAjax from '<path-to-form-zero>/resources/js/FormZeroAjax.mjs';
new FormZeroAjax().init(document.querySelector('#my-editor'));
```

## 1. Declarative create and save

A parent HTML `<form>` carries a **dedicated fragment CSRF token**:

```html
<form id="my-editor" data-fz-token="SERVER_GENERATED_FRAGMENT_TOKEN">
  <!-- Child forms from FormZero, normal whole-page submit still works -->
  <a href="/fallback/add" data-fz-action="create"
     data-fz-url="/api/items/create"
     data-fz-target="#items"
     data-fz-fragment-prefix="item-"
     data-fz-success="Created">Add item</a>
  <div id="items"></div>
</form>
```

The server creates an item and returns a JSON object such as
`{ "success":true, "id":17, "html":"<section id=\"item-17\">...</section>",
"csrfToken":"NEXT_TOKEN" }`. The module checks that the expected
`item-17` exists before appending. It never reloads the page or replaces
existing unsaved inputs.

To save a subform, render an action button *inside its own row*:

```html
<section id="item-17">
  <input name="items[17][title]" value="Example">
  <input name="items[17][children][6][title]" value="Unsaved child">
  <button type="button" data-fz-action="save"
          data-fz-url="/api/items/save?id=17"
          data-fz-row="#item-17"
          data-fz-scope="items[17]"
          data-fz-fields="title,enabled,options"
          data-fz-success="Saved">Save item</button>
</section>
```

Only direct `items[17][title]`, `items[17][enabled]` and
`items[17][options][]` controls are serialized. Nested children are **never**
included even when the child fields have the same final name. Disabled and
unchecked controls are omitted; multiselects contribute one value per
selected option. **Normalize omitted checkbox/multiselect fields on the PHP
side** (e.g. `$submitted['options'] ??= []`). Submitted file fields are
included when present and selected. The standalone subform should be
validated with `isValid()`, not with a permissive `isValidPartial()`.

Supported trigger attributes:

| Attribute | Purpose |
| --- | --- |
| `data-fz-action` | `create`, `save`, `delete`, or `mutate` |
| `data-fz-url` | POST URL, HTML-escaped once in its attribute rendering context |
| `data-fz-target` | **Create**: selector of the existing append container |
| `data-fz-fragment-prefix` | Expected row ID prefix; result `id` must be a positive integer |
| `data-fz-fragment-match` | Optional selector that the returned row **must** match (e.g. `[data-dragger=item]`) |
| `data-fz-context-key` / `data-fz-context-value` | Optional create guard: response property (`sectionId` etc.) must match expected parent ID |
| `data-fz-row` | **Save/delete/mutate**: selector of the existing subform row enclosing the button |
| `data-fz-confirm` | **Delete/mutate**: optional browser confirmation message |
| `data-fz-scope` | **Save**: bracket notation field name prefix, e.g. `sections[7]` |
| `data-fz-fields` | **Save**: comma-separated **explicit field whitelist** |
| `data-fz-label-target` | **Save**: descendant selector whose `textContent` receives returned `label` (never HTML) |
| `data-fz-replace="if-html"` | **Save/mutate**: replace that row only when server sends `html` (for `mutate`, dirty rows are rejected before sending) |
| `data-fz-success` | Optional text or application translation key for the status message; save actions fall back to configurable `savedMessage` (`Saved` by default) |

When generating `data-fz-url` from an application URL helper, pass the **raw** URL
to the attribute serializer. For example, if `url()` defaults to an HTML-encoded
query separator (`&amp;`), request `&` explicitly and let
`{attributes}|join` escape `data-fz-url` once. Pre-escaped query strings
will be double-encoded and multi-parameter URLs will fail.

On successful saves, FormZeroAjax replaces stale *unsaved* or error status with
a success notice. To localize the fallback, configure `savedMessage` in
`new FormZeroAjax({savedMessage: 'translation.key', translate})`. If the user
changes a field while a save is in flight, the unsaved warning takes
precedence; those newer edits still need saving.

The create link's `href` is a non-JavaScript fallback. Save buttons use
`type="button"` and do not trigger the global form submit. The `data-fz-*`
selectors are resolved **from the parent form**, not by assuming the Add
button is inside its target accordion. This also handles decorators that
prepend buttons as siblings of the accordion.

## 2. PHP controller response contract

```php
use DalPraS\FormZero\Ajax\FragmentResponse;

// Validate authorization and the separate fragment CSRF token FIRST.
// Persist only the intended entity, then render only its child form.
return new JsonResponse(
    FragmentResponse::created($newId, $childForm->render(), $nextToken, [
        'sectionId' => $sectionId,
    ]),
    200,
);

// Successful scoped update. Preserve the current browser subform by default.
return new JsonResponse(FragmentResponse::saved($nextToken, [
    'id' => $id,
    'label' => $newCaption,
]));

// Validation errors use HTTP 422 and carry a replacement token, if rotated.
return new JsonResponse(
    FragmentResponse::invalid($form->getMessages(true), $nextToken),
    422,
);
```

Responses must be JSON (`application/json`) with `success: true|false`.
`csrfToken` is adopted on both successful and HTTP 422 responses. A 403 with
an expired/invalid token should prompt a reload or fresh token flow; **do not
make CSRF optional**. Never interpret the requested entity ID or field
whitelist as authorization: verify entity ownership, ACL and allowed fields
on the server, then validate a standalone form and persist only those fields.

The helper returns **plain arrays**, keeping FormZero independent of
Laminas/Symfony controllers. The application decides its status codes. It
does not change `ZeroForm`, add a JS dependency to Composer or register AJAX
behavior during ordinary form rendering.

## 3. Events and editor integrations

The library dispatches document-level `CustomEvent`s with a `detail` payload:

- `formzero:before-submit`: `{form, trigger, root}`; synchronize rich editors
  **before** `FormData` serialization.
- `formzero:fragment-added`: `{form, trigger, root, result}`; initialize newly
  inserted widgets and optionally focus the first field.
- `formzero:fragment-replaced`: same payload when an explicit row replacement
  is authorized.
- `formzero:committed`: `{form, trigger, result, action}` immediately upon a
  successful server response, **before DOM rendering**. Use this to mark a
  whole-page snapshot stale even if returned HTML cannot be inserted.
- `formzero:fragment-saved`: `{form, trigger, root, result, action}` after
  success. UI status is reported through `role="status"`.
- `formzero:fragment-removed`: `{form, trigger, root, result}` after a
  successful delete and removal of that row.
- `formzero:reordered`: `{form, type, ownerId, ids, result}` after a
  successful scoped reorder.
- `formzero:dirty`: `{row, trigger}` when an independent subform changes.
- `formzero:conflict`: `{form, trigger, error}` when a response is HTTP 409.
- `formzero:fragment-warning`: `{form, trigger, root, result}` if persistence
  succeeded but the server reports a nonfatal follow-up warning (e.g. file cleanup).
- `formzero:uploaded`: `{form, trigger, completed}` after a file-selection batch.
- `formzero:fragment-error`: `{form, trigger, error}`; validation and network
  errors are displayed via `role="alert"` and `aria-live`.

The status CSS classes are configurable with `statusClasses`; by default the
library uses `fz-status`, `fz-status--success`, `fz-status--warning`
and `fz-status--error` without requiring Bootstrap. A Bootstrap app may pass
`{statusClasses:{base:'ms-2 small', success:'text-success', error:'text-danger'}}`.

For example:

```js
document.addEventListener('formzero:before-submit', ({detail}) => {
    window.MyEditor?.sync(detail.root);
});
document.addEventListener('formzero:fragment-added', ({detail}) => {
    initializeEditorWidgets(detail.root);
});
```

All requests sharing one HTML form are **queued**, so concurrent clicks don't
submit the same rotating CSRF token. The same action button cannot be sent
twice while pending. Requests in different browser tabs may still race; for
non-idempotent creates, applications can add idempotency keys if needed.

Returned HTML is treated as **trusted server-rendered markup**. The controller
must correctly escape user-provided values and must not return user-controlled
raw scripts/HTML. The library intentionally does not sanitize application
markup or contain any database rules.

This optional module is tested from the application's Node test runner; the
PHP `FragmentResponseTest` runs in the package's PHPUnit suite.

## 4. Optional multipart file uploads

A `SymfileElement` or `SymfileMultiElement` can opt in to AJAX uploading
**without changing FormZero's core**. Add the attributes below to the
rendered `<input type="file">`. On selection, `FormZeroAjax` sends one file
per POST, using `FormData` field `file`, then appends the validated server
fragment into `data-fz-target`. Without JavaScript the same input remains a
normal multipart field for a full-page POST.

```html
<form data-fz-token="..." data-fz-revision="12" enctype="multipart/form-data">
  <input type="file" multiple name="image[]"
         data-fz-action="upload" data-fz-url="/api/media/upload?type=image"
         data-fz-target="#images" data-fz-fragment-prefix="image-"
         data-fz-fragment-match="[data-dragger=item]"
         data-fz-context-key="type" data-fz-context-value="image"
         data-fz-success="Uploaded">
  <div id="images"></div>
</form>
```

Each successful JSON response must contain `success`, a positive `id`, the
matching `type`, server-rendered `html`, `csrfToken`, and (for revisioned
editors) `editorRevision`. Selected batches are capped at 20 files, queued
in order, and serialized with all other fragment actions on the same form.
A failed later upload **does not undo earlier successful uploads**: the status
shows how many succeeded and keeps their inserted rows. File inputs clear
after the attempt, allowing an identical filename to be selected again.
During the batch, navigation triggers the standard unsaved-changes warning.

File type/MIME, size, ownership, CSRF, per-entity revision and storage rules
MUST be checked by the application endpoint. The HTML `data-fz-*` attributes
are configuration, not authorization. In particular, never put arbitrary
filesystem paths or an HTML fragment supplied by the browser into the
server's response. Normal server-side validation is always required.

`formzero:committed` and `formzero:fragment-added` fire separately for every
successfully uploaded file; `formzero:uploaded` fires after the full batch
with `{form, trigger, completed}`. Physical file deletion is deliberately an
application-side operation, not a FormZero feature.

## 5. Revision-aware saves, deletion, reordering and unsaved changes

Optionally set `data-fz-revision="1"` on the parent form. The module then
includes `editor_revision` on each fragment POST and updates that attribute
only when a successful JSON response provides a numeric `editorRevision`.
HTTP 409 emits `formzero:conflict`; the token returned in `csrfToken` is
still adopted but the revision is **never** updated on a failed request.

Delete an existing row only after the server has committed the deletion:

```html
<button type="button" data-fz-action="delete"
        data-fz-url="/api/items/delete?id=17"
        data-fz-row="#item-17"
        data-fz-confirm="Delete this item?">Delete</button>
```

For an action that updates a record but must **not** submit fields or remove
its row, use `data-fz-action="mutate"`. The server may return a refreshed
`html` fragment; the row is replaced only when `data-fz-replace="if-html"`
is present. A mutate request is rejected before transmission if the target
row contains unsaved fields, so an updated fragment cannot silently discard
those edits. Use a real POST route with CSRF and revision checks; this is
useful for unlinking a physical file while preserving its CMS record:

```html
<button type="button" data-fz-action="mutate"
        data-fz-row="#video-12"
        data-fz-url="/api/media/unlink-file?id=12"
        data-fz-confirm="Delete the uploaded file?"
        data-fz-replace="if-html"
        data-fz-fragment-prefix="video-">Remove file</button>
```

For reordering, call the JavaScript API from your drag handler:

```js
await ajax.reorder(form, '/api/items/reorder', 'item', 7, [21, 19, 23]);
```

The application must authenticate and validate ownership of every ID,
validate the *complete* list of siblings on the server, and use a transaction
and revision check. On failure, the drag handler should restore the previous
DOM order. FormZero deliberately knows nothing about rank, parent entities,
or persistence.

`init()` tracks an independent fingerprint of each row with a matching
`data-fz-action="save"` button. Only fields declared in `data-fz-fields`
participate. It signals unsaved changes with `data-fz-dirty`, an accessible
message alongside the save button, `formzero:dirty`, and a browser
`beforeunload` warning. After a successful save, changes typed **during** the
request remain dirty; a failed save never discards user input.

`FormZeroAjax` options `dirtyMessage` and `statusClasses` let applications
translate/customize messages without coupling the package to Bootstrap.
This is deliberately an **explicit-save** API: no invisible autosave or
unconditional background traffic is enabled by the library.

Security rules still apply: the server must not trust a client-provided
`editor_revision`, target selector, field whitelist, form scope, or row ID to
prove access. Check authentication, CSRF, ownership, form validation and
revision *server-side*. For double-escaped URL avoidance, use the raw URL as
the value of `data-fz-url` (and any other HTML-escaped `data-fz-*` URL).

### Notification and layout-stable dirty indicator

`FormZeroAjax` can optionally delegate operation-level notifications to the host
application instead of inserting text after a Save button. Its core stays
independent of any toaster or CSS framework:

```js
const ajax = new FormZeroAjax({
    translate: key => translator.trans(key),
    notify: ({type, message}) => toastNotifier.show({type, message}),
    inlineDirty: false,
});
```

- The default behavior remains unchanged: inline status text and inline dirty
  warnings, without requiring `ToastNotifier`.
- In this opt-in mode, successful saves, creations, deletes and completed upload
  batches produce one notification; errors are reported through the same callback.
  Intermediate multi-file progress counters are suppressed in toast mode rather
  than generating a flood of notifications.
- Editing a field never creates a toast. `data-fz-dirty="true"` is placed on the
  row and its Save button, and the button's accessible label gains the translated
  `dirtyMessage`. Both are restored on successful save or reverting the edit.
  Applications can style the state with a small CSS pseudo-element without
  changing the toolbar layout. The `beforeunload` unsaved-change protection
  still applies.
- Saves with additional changes made while the request was pending retain the
  dirty marker and do not incorrectly announce a fully saved form.
- Save errors continue to be delivered as `formzero:fragment-error` events and
  valid responses still rotate CSRF/revision tokens. The notification callback
  must display text, not treat server-provided messages as trusted HTML.


### Optional per-component status UI

Applications may keep operation-level toasts while rendering independent saves
locally, without modifying `ZeroForm` or depending on any CSS framework:

```js
const ajax = new FormZeroAjax({
    notify: ({type, message}) => toaster.show({type, message}),
    inlineDirty: false,
    onRowStatus: ({row, trigger, state, message}) => {
        // Render inside the application's own component, NOT as a sibling of Save.
        // state: idle | dirty | saving | saved | error
        componentFeedback.update(row, trigger, state, message);
    },
});
```

`onRowStatus` is optional. The callback is invoked when the initial row is
snapshotted, when its permitted fields become dirty or clean, before a scoped
save request, and after a successful or failed save. A successful component
save no longer calls `notify` if this callback exists; failures reach the
component and (when configured) the general notification callback. Create,
delete, upload and other operation messages still use `notify`. For consumers
without `onRowStatus`, the original inline/toast behavior is unchanged.

### Optional confirmation callback and localized server errors

For `data-fz-action="delete"` or `"mutate"` with `data-fz-confirm`, the
application can supply an asynchronous confirmation function:

```js
const ajax = new FormZeroAjax({
    translate: key => translator.trans(key),
    confirm: (message, trigger) => confirmationDialog.ask(message, trigger),
});
```

The promise must resolve to `true` to perform the request; cancellation
never sends a POST. While confirmation is pending, repeated clicks on the
same trigger are ignored. Without an application callback, native browser
`confirm()` remains the default. The library never assumes Bootstrap or a
particular modal service. The server must still validate CSRF, ownership,
revision, and deletion constraints after the user confirms.

When a JSON failure includes a single `error` string, FormZero passes it
through the configured translator before displaying it. Applications can
therefore return stable message keys, including for domain-specific 422
failures, without embedding HTML or duplicating translations in controllers.
Structured field-validation `errors` remain untouched because they may
already contain translated per-field messages.

The callback must not throw, mutate form fields, or cause persistence calls.
FormZero catches presentation errors so a saved record cannot be reported as
an unsuccessful HTTP operation solely because a notification failed. Keep
local feedback text-only and reserve space in the component so visual changes
do not shift the Save toolbar.
