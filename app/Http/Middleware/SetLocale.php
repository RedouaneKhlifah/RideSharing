<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class SetLocale
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Get the locale from the request header (e.g., Accept-Language or X-Locale)
        $locale = $request->header('Accept-Language', config('app.fallback_locale'));

        // Ensure the locale is supported
        if (!in_array($locale, ['en', 'fr', 'ar'])) {
            $locale = config('app.fallback_locale'); // Fallback to default locale
        }

        // Set the application locale
        App::setLocale($locale);


        // Proceed to the next middleware/request
        return $next($request);
    }
}