<?php declare(strict_types=1);

namespace DalPraS\FormZero;

/**
 * Named FormZero element layout profiles.
 *
 * These values describe common decorator stacks. They keep forms focused on
 * field configuration while DecoratorPreset owns the concrete Bootstrap/HTML
 * rendering details.
 */
enum FormLayout: string
{
    case Horizontal = 'horizontal';
    case HorizontalWide = 'horizontal_wide';
    case HorizontalNarrow = 'horizontal_narrow';
    case FullWidth = 'full_width';
    case Submit = 'submit';
    case Raw = 'raw';
}
