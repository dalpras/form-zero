# Yet Another Form Library, but it works with all frameworks

**Form Zero is a form library that does not need forms tightly coupled with complex view libraries** such as Twig, Blade, Smarty, or framework-specific view layers.

During my developer years — 30 years and still working, crazy — I had problems when Smarty did not update its library for years, and when Zend Framework 1 eventually left its original view system behind.

If you maintain a large codebase and rely on a “do everything” framework, a small black swan event can force you to replace thousands of lines of code.

ZF1 had one very interesting idea: the decorator pattern for rendering forms. It was not perfect, but it was inspiring.

Forms need rendering, but rendering should stay modular. Otherwise, every new Bootstrap version, CSS framework change, or frontend convention can eat developer time.

So the goal is simple:

- keep forms framework-independent
- keep rendering modular
- avoid coupling forms to a large template engine
- avoid a custom template language
- keep PHP in control

This is where [Smart Template](https://github.com/dalpras/smart-template) comes in.

Form Zero uses Smart Template to render form fragments through small, composable template collections. Templates are registered through presets, not loaded automatically from a template directory.

---

## Requirements

- PHP 8.3 or newer
- `dalpras/smart-template`
- Symfony components used by your application integration, such as Validator, Translation, or HTTP Foundation

---

## Rendering model

Form Zero uses a Smart Template preset named `form`.

The preset registers the form template collection into the `TemplateEngine`.

```php
use DalPraS\FormZero\Preset\FormPreset;
use DalPraS\SmartTemplate\TemplateEngine;

$template = new TemplateEngine();

FormPreset::register($template);
```

The first registered preset becomes the default collection, so rendering can use:

```php
$template->collection();
```

or:

```php
$template->renderDefault(...);
```

The old filesystem-style setup is no longer used:

```php
// Old style - no longer recommended
new TemplateEngine($directory . '/../Template', 'form.php');
```

The new setup is preset-based:

```php
$template = new TemplateEngine();

FormPreset::register($template);
```

---

## Basic usage

```php
use DalPraS\FormZero\Factory\FormFactory;
use DalPraS\FormZero\Preset\FormPreset;
use DalPraS\FormZero\ZeroForm;
use DalPraS\SmartTemplate\TemplateEngine;
use Symfony\Component\HttpFoundation\Request;

$request = Request::createFromGlobals();

$template = new TemplateEngine();

FormPreset::register($template);

$template->addCustomParamCallback('{attributes}', static function ($param) use ($template): string {
    return $param === null ? '' : $template->attributes($param);
});

$formFactory = new FormFactory(
    template: $template,
    request: $request,
);

$form = $formFactory->createForm(ZeroForm::class);

$form->add('text', 'name');

echo $form->render();
```

---

## Simpler usage

`FormFactory` can create and configure the template engine for you.

```php
use DalPraS\FormZero\Factory\FormFactory;
use DalPraS\FormZero\ZeroForm;
use Symfony\Component\HttpFoundation\Request;

$request = Request::createFromGlobals();

$formFactory = new FormFactory(
    request: $request,
);

$form = $formFactory->createForm(ZeroForm::class);

$form->add('text', 'name');

echo $form->render();
```

Internally, the factory creates a `TemplateEngine`, registers `FormPreset`, and adds the default `{attributes}` callback.

---

## FormFactory setup

A preset-based factory should look like this:

```php
use DalPraS\FormZero\Preset\FormPreset;
use DalPraS\SmartTemplate\TemplateEngine;

public function getTemplate(): TemplateEngine
{
    if ($this->template === null) {
        $this->template = new TemplateEngine();

        FormPreset::register($this->template);

        $this->template->addCustomParamCallback(
            '{attributes}',
            fn($param): string => $param === null
                ? ''
                : $this->template->attributes($param)
        );
    }

    return $this->template;
}
```

No template directory is passed to `TemplateEngine`.

No `form.php` filename is passed to `TemplateEngine`.

The form template file is loaded explicitly by `FormPreset`.

---

## FormPreset

`FormPreset` owns the form template collection.

Example:

```php
<?php

declare(strict_types=1);

namespace DalPraS\FormZero\Preset;

use DalPraS\SmartTemplate\Collection\RenderCollection;
use DalPraS\SmartTemplate\Preset\HtmlPreset;
use DalPraS\SmartTemplate\Preset\PresetInterface;
use DalPraS\SmartTemplate\TemplateEngine;

final class FormPreset implements PresetInterface
{
    public const NAMESPACE = 'form';

    public static function register(
        TemplateEngine $engine,
        string $namespace = self::NAMESPACE,
        array $overrides = [],
        bool $default = true,
    ): TemplateEngine {
        HtmlPreset::register(
            engine: $engine,
            namespace: $namespace,
            default: $default,
        );

        $templates = $engine->require(self::path());

        if (!is_array($templates)) {
            throw new \RuntimeException(
                'FormPreset template file must return an array: ' . self::path()
            );
        }

        $engine->register($namespace, $templates);

        if ($overrides !== []) {
            $engine->register($namespace, $overrides);
        }

        return $engine;
    }

    public static function collection(
        TemplateEngine $engine,
        ?string $namespace = self::NAMESPACE,
    ): RenderCollection {
        return $engine->collection($namespace);
    }

    public static function path(): string
    {
        return dirname(__DIR__, 2) . '/Template/form.php';
    }
}
```

The final `form` collection contains:

- base HTML templates from `HtmlPreset`
- Form Zero templates from `Template/form.php`
- optional user overrides

---

## Overriding templates

You do not need an `extend()` method.

To override or add templates, call `register()` again with the same namespace.

```php
$template = new TemplateEngine();

FormPreset::register($template);

$template->register(FormPreset::NAMESPACE, [
    'input' => '<input class="my-input" {attributes}>',
]);
```

Or pass overrides directly when registering the preset:

```php
FormPreset::register($template, overrides: [
    'input' => '<input class="my-input" {attributes}>',
]);
```

The rule is:

```php
register('form', $templates); // create, merge, or override the form namespace
```

---

## Template file

The default Form Zero template file should return an array.

Example `Template/form.php`:

```php
<?php

declare(strict_types=1);

return [
    'form' => <<<'HTML'
<form {attributes}>
{content}
</form>
HTML,

    'input' => <<<'HTML'
<input {attributes}>
HTML,

    'label' => <<<'HTML'
<label {attributes}>{text}</label>
HTML,

    'field' => <<<'HTML'
<div class="{class}">
{label}
{input}
{errors}
</div>
HTML,

    'button' => <<<'HTML'
<button {attributes}>{text}</button>
HTML,
];
```

Template values can be strings, closures, nested arrays, or lazy template-file references.

---

## Lazy template files

A template file can split templates into smaller files.

```php
<?php

declare(strict_types=1);

return [
    'form' => '<form {attributes}>{content}</form>',

    'fields' => $this->lazyRequire(__DIR__ . '/fields.php'),
];
```

`fields.php`:

```php
<?php

declare(strict_types=1);

return [
    'text' => '<input type="text" {attributes}>',
    'email' => '<input type="email" {attributes}>',
];
```

The lazy file is loaded only when that branch is accessed.

---

## Rendering attributes

The `{attributes}` placeholder is usually handled by a Smart Template callback:

```php
$template->addCustomParamCallback('{attributes}', static function ($param) use ($template): string {
    return $param === null ? '' : $template->attributes($param);
});
```

Then templates can use:

```php
'<input {attributes}>'
```

And the renderer can pass:

```php
[
    '{attributes}' => [
        'type' => 'text',
        'name' => 'username',
        'id' => 'user[name]',
        'class' => 'form-control',
    ],
]
```

Result:

```html
<input type="text" name="username" id="user-name" class="form-control">
```

---

## CSRF token

CSRF behavior can be controlled through the factory:

```php
$formFactory->setIgnoreCsrfToken();

if ($formFactory->getIgnoreCsrfToken()) {
    // CSRF validation disabled
}
```

---

## Migration from the old Smart Template setup

### Before

```php
$reflector = new ReflectionClass(FormFactory::class);
$filename = $reflector->getFileName();
$directory = dirname($filename);

$template = new TemplateEngine($directory . '/../Template', 'form.php');

$template->addCustomParamCallback('{attributes}', function ($param) {
    return ($param === null) ? '' : $this->template->attributes($param);
});

$formFactory = new FormFactory($template, 'form.php', $serverRequest);
```

### After

```php
$template = new TemplateEngine();

FormPreset::register($template);

$template->addCustomParamCallback('{attributes}', static function ($param) use ($template): string {
    return $param === null ? '' : $template->attributes($param);
});

$formFactory = new FormFactory(
    template: $template,
    request: $serverRequest,
);
```

Or let the factory configure the template engine:

```php
$formFactory = new FormFactory(
    request: $serverRequest,
);
```

---

## Notes

- `form.php` is now an internal template file loaded by `FormPreset`.
- The public template namespace is `form`.
- Avoid using filenames such as `form.php` as namespaces.
- Use `register('form', [...])` to override or add templates.
- No cache configuration is required.
- No template directory is passed to `TemplateEngine`.

---

## License

See the package metadata in `composer.json`.
