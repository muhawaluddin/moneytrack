<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class NormalizeMoneyInputs
{
    public function handle(Request $request, Closure $next): Response
    {
        foreach (['amount', 'opening_balance', 'limit_amount', 'target_amount'] as $field) {
            if ($request->has($field) && is_string($request->input($field))) {
                $value = $request->input($field);
                $negative = str_starts_with(trim($value), '-');
                $digits = preg_replace('/\D/', '', $value);
                $request->merge([$field => ($negative ? '-' : '').$digits]);
            }
        }

        return $next($request);
    }
}
