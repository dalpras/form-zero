<?php

declare(strict_types=1);

namespace DalPraS\FormZero\Ajax;

/**
 * Framework-neutral JSON *payload* builder for AJAX-rendered subforms.
 *
 * Controllers still own request validation, permissions, persistence, status
 * codes and encoding (e.g. new JsonResponse(FragmentResponse::created(...))).
 */
final class FragmentResponse
{
    /** @param array<string, mixed> $context */
    public static function created(int $id, string $html, string $csrfToken, array $context = []): array
    {
        return [
            ...$context,
            'success' => true,
            'id' => $id,
            'html' => $html,
            'csrfToken' => $csrfToken,
        ];
    }

    /** @param array<string, mixed> $data */
    public static function saved(string $csrfToken, array $data = []): array
    {
        return [...$data, 'success' => true, 'csrfToken' => $csrfToken];
    }

    /** @param array<string, mixed> $errors */
    public static function invalid(array $errors, string $csrfToken, string $message = 'Validation failed'): array
    {
        return [
            'success' => false,
            'error' => $message,
            'errors' => $errors,
            'csrfToken' => $csrfToken,
        ];
    }

    public static function failed(string $message, ?string $csrfToken = null): array
    {
        return [
            'success' => false,
            'error' => $message,
            ...($csrfToken !== null ? ['csrfToken' => $csrfToken] : []),
        ];
    }
}
