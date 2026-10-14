<?php

declare(strict_types=1);

namespace DalPraS\UnitTests\Ajax;

use DalPraS\FormZero\Ajax\FragmentResponse;
use PHPUnit\Framework\TestCase;

final class FragmentResponseTest extends TestCase
{
    public function testCreatedPayloadProtectsReservedKeys(): void
    {
        self::assertSame([
            'sectionId' => 42,
            'success' => true,
            'id' => 7,
            'html' => '<div>Example</div>',
            'csrfToken' => 'token',
        ], FragmentResponse::created(7, '<div>Example</div>', 'token', [
            'sectionId' => 42,
            'success' => false,
        ]));
    }

    public function testSavedAndInvalidPayloads(): void
    {
        self::assertSame(['id' => 9, 'success' => true, 'csrfToken' => 'next'],
            FragmentResponse::saved('next', ['id' => 9]));
        self::assertSame([
            'success' => false,
            'error' => 'Validation failed',
            'errors' => ['title' => ['Required']],
            'csrfToken' => 'next',
        ], FragmentResponse::invalid(['title' => ['Required']], 'next'));
        self::assertSame(['success' => false, 'error' => 'Denied'], FragmentResponse::failed('Denied'));
    }
}
