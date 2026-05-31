<?php

namespace App\Services;

use Closure;
use Illuminate\Support\Facades\DB;
use Throwable;

class TransactionService
{
    /**
     * Execute a callback within a database transaction.
     *
     * @param  Closure  $callback  The callback to execute
     * @param  int  $attempts  Number of attempts before throwing exception
     * @return mixed The return value of the callback
     *
     * @throws Throwable
     */
    public static function execute(Closure $callback, int $attempts = 1): mixed
    {
        return DB::transaction($callback, $attempts);
    }

    /**
     * Execute a callback within a transaction with automatic rollback on exception.
     *
     * @param  Closure  $callback  The callback to execute
     * @param  Closure|null  $onError  Optional error handler
     * @return mixed The return value of the callback, or null on error
     */
    public static function executeWithFallback(Closure $callback, ?Closure $onError = null): mixed
    {
        try {
            return DB::transaction($callback);
        } catch (Throwable $e) {
            if ($onError) {
                return $onError($e);
            }

            \Log::error('Transaction failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return null;
        }
    }
}
