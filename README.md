# FormZero

FormZero is a PHP 8.3+ form library built around three ideas:

1. **Forms are PHP objects.** Fields, validation, filtering, nested data, and rendering are configured in PHP.
2. **Rendering is modular.** FormZero renders through decorators and [Smart Template](../smart-template) instead of coupling forms to Twig, Blade, or another full view framework.
3. **Application infrastructure is injected.** The library receives a template engine, Symfony validator, optional translator, and an upload provider. It does not read the current HTTP request directly.

FormZero is used as a form library, not as an HTTP framework. Your controller/application is responsible for obtaining POST data, deciding when to validate, persisting values, and handling successful/failed requests.

---

## Requirements

The package currently requires:

- PHP `>= 8.3`
- `dalpras/smart-template ^4.0`
- Symfony Validator `^7.4`
- Symfony Translation `^7.4`
- Symfony HttpFoundation `^7.3`
- Symfony Mime `^7.4`
- Symfony PSR HTTP Message Bridge `^7.4`

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
        +-- Elements / SubZeroForms
        +-- FormDataMapper
        +-- FormValidator
        +-- Decorators
        |
        v
   FormFactory
        |
        +-- TemplateEngine
        +-- ValidatorInterface
        +-- UploadedFileProviderInterface
        `-- optional Translator
```

The public entry point is normally `FormFactory`.

---

# 1. Configure FormFactory

`FormFactory` requires three services and accepts an optional translator:

```php
use DalPraS\FormZero\Factory\FormFactory;
use DalPraS\FormZero\Upload\UploadedFileProviderInterface;
use DalPraS\SmartTemplate\TemplateEngine;
use Symfony\Component\Translation\Translator;
use Symfony\Component\Validator\Validator\ValidatorInterface;

$formFactory = new FormFactory(
    template: $template,
    uploadedFileProvider: $uploadedFileProvider,
    validator: $validator,
    translator: $translator, // optional; may be null
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

FormPreset::register($template);

$template->addCustomParamCallback(
    '{attributes}',
    static fn ($attributes): string => $attributes === null
        ? ''
        : $template->attributes($attributes)
);
```

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

Use `SubZeroForm` to group data or model nested arrays.

```php
$metadata = $this->createSubZeroForm();
$metadata->setElementsBelongTo('metadata');

$metadata->add(new TextElement(), 'metaTitle', [
    'label' => 'Meta title',
]);

$this->addSubForm($metadata, 'metadata');
```

A parent array form can nest it further:

```php
$baseData = $this->createSubZeroForm();
$baseData->setElementsBelongTo('basedata');

$metadata = $baseData->createSubZeroForm();
$metadata->setElementsBelongTo('metadata');

$metadata->add(new TextElement(), 'metaTitle');
$baseData->addSubForm($metadata, 'metadata');

$this->addSubForm($baseData, 'basedata');
```

The resulting HTML field name is:

```text
basedata[metadata][metaTitle]
```

and its generated ID is:

```text
basedata-metadata-metaTitle
```

FormZero compiles these paths and reuses them for rendering, data mapping, validation, and uploaded-file lookup.

Rendering does not rewrite the logical `belongsTo` data structure.

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

`FormFactory` accepts an optional Symfony `Translator`:

```php
$formFactory = new FormFactory(
    template: $template,
    uploadedFileProvider: $uploads,
    validator: $validator,
    translator: $translator,
);
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
    translator: $translator,
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

# License

See `composer.json` for the package license and metadata.
