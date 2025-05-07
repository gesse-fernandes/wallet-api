<?php

// app/Http/Middleware/DisableSwaggerCsrf.php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class DisableSwaggerCsrf
{
    public function handle(Request $request, Closure $next)
    {
        if (str_contains($request->header('User-Agent'), 'Swagger')) {
            $request->cookies->remove('XSRF-TOKEN');
        }

        return $next($request);
    }
}
