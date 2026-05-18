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
        return dirname(__DIR__, 2) . '/resources/templates/form.php';
    }
}