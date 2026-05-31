<?php

namespace App\Traits;

use Illuminate\Support\Facades\Log;

/**
 * SecurityTrait provides secure methods for controllers.
 *
 * Includes HTML sanitization and logging helpers.
 */
trait SecurityTrait
{
    /**
     * Sanitize HTML input to prevent XSS attacks.
     *
     * @param  string  $input  The input to sanitize
     * @return string The sanitized input
     */
    protected function sanitizeHtml(string $input): string
    {
        return htmlspecialchars($input, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    /**
     * Log an authentication event.
     *
     * @param  string  $action  The action (e.g., 'login', 'logout')
     * @param  array  $context  Additional context
     */
    protected function logAuthEvent(string $action, array $context = []): void
    {
        Log::info('Authentication event', array_merge([
            'action' => $action,
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ], $context));
    }

    /**
     * Log a critical database operation.
     *
     * @param  string  $operation  The operation (e.g., 'create', 'update', 'delete')
     * @param  string  $model  The model name
     * @param  array  $context  Additional context
     */
    protected function logDatabaseOperation(string $operation, string $model, array $context = []): void
    {
        Log::info('Database operation', array_merge([
            'operation' => $operation,
            'model' => $model,
            'user_id' => auth()->id(),
            'ip' => request()->ip(),
        ], $context));
    }

    /**
     * Log an error or exception.
     *
     * @param  string  $message  The error message
     * @param  array  $context  Additional context
     */
    protected function logError(string $message, array $context = []): void
    {
        Log::error($message, array_merge([
            'user_id' => auth()->id(),
            'ip' => request()->ip(),
            'url' => request()->fullUrl(),
        ], $context));
    }
}
