<?php declare(strict_types=1);

namespace DalPraS\FormZero\Decorator;

use DalPraS\FormZero\FormLayout;

/**
 * Centralized decorator stacks for common FormZero layouts.
 */
final class DecoratorPreset
{
    /**
     * @return list<array{0: class-string<AbstractDecorator>, 1?: array<string, mixed>}>
     */
    public static function forLayout(FormLayout|string|null $layout): array
    {
        $layout = $layout instanceof FormLayout
            ? $layout
            : FormLayout::from((string) ($layout ?: FormLayout::Horizontal->value));

        return match ($layout) {
            FormLayout::Horizontal => self::horizontal(),
            FormLayout::HorizontalWide => self::horizontalWide(),
            FormLayout::HorizontalNarrow => self::horizontalNarrow(),
            FormLayout::FullWidth => self::fullWidth(),
            FormLayout::Submit => self::submit(),
            FormLayout::Raw => self::raw(),
        };
    }

    /**
     * @return list<array{0: class-string<AbstractDecorator>, 1?: array<string, mixed>}>
     */
    public static function horizontal(
        string $contentClass = 'col-12 col-sm-6',
        string $labelClass = 'col-form-label col-12 col-sm-3 col-md-2',
        string $rowClass = 'row mb-3',
    ): array {
        return [
            [ElementContentDecorator::class],
            [ElementWrapperDecorator::class, ['class' => $contentClass]],
            [ElementLabelDecorator::class, ['class' => $labelClass]],
            [ElementWrapperDecorator::class, ['class' => $rowClass]],
        ];
    }

    /**
     * @return list<array{0: class-string<AbstractDecorator>, 1?: array<string, mixed>}>
     */
    public static function horizontalWide(): array
    {
        return self::horizontal('col-12 col-sm-9');
    }

    /**
     * @return list<array{0: class-string<AbstractDecorator>, 1?: array<string, mixed>}>
     */
    public static function horizontalNarrow(): array
    {
        return self::horizontal('col-12 col-sm-3');
    }

    /**
     * @return list<array{0: class-string<AbstractDecorator>, 1?: array<string, mixed>}>
     */
    public static function fullWidth(string $rowClass = 'row mb-3'): array
    {
        return [
            [ElementContentDecorator::class],
            [ElementWrapperDecorator::class, ['class' => 'col-12']],
            [ElementWrapperDecorator::class, ['class' => $rowClass]],
        ];
    }

    /**
     * @return list<array{0: class-string<AbstractDecorator>, 1?: array<string, mixed>}>
     */
    public static function submit(string $rowClass = 'row mb-3'): array
    {
        return [
            [ElementContentDecorator::class],
            [ElementLabelDecorator::class, ['class' => 'visually-hidden']],
            [ElementWrapperDecorator::class, ['class' => 'col-12']],
            [ElementWrapperDecorator::class, ['class' => $rowClass]],
        ];
    }

    /**
     * @return list<array{0: class-string<AbstractDecorator>, 1?: array<string, mixed>}>
     */
    public static function raw(): array
    {
        return [[ElementContentDecorator::class]];
    }
}
