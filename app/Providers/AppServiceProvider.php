<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Livewire\Livewire;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        if ($this->app->isProduction()) {
            URL::forceScheme('https');
            URL::forceRootUrl(config('app.url'));
        }

        Password::defaults(fn (): Password => Password::min(12)->mixedCase()->numbers()->symbols()->uncompromised());
        Livewire::addPersistentMiddleware(ThrottleRequests::class);

        RateLimiter::for('registration', fn (Request $request): Limit => Limit::perMinute(5)->by($this->emailAndIpKey($request)));
        RateLimiter::for('password-reset', fn (Request $request): Limit => Limit::perMinute(5)->by($this->emailAndIpKey($request)));
        RateLimiter::for('public', fn (Request $request): Limit => Limit::perMinute(60)->by($request->ip()));
        RateLimiter::for('admin', fn (Request $request): Limit => Limit::perMinute(60)->by($request->user()?->id.'|'.$request->ip()));
        RateLimiter::for('checkout', fn (Request $request): Limit => Limit::perMinute(5)->by($request->user()?->id ?: $request->ip()));
        RateLimiter::for('contact', fn (Request $request): Limit => Limit::perMinute(3)->by($request->ip()));
    }

    private function emailAndIpKey(Request $request): string
    {
        return Str::transliterate(Str::lower((string) $request->input('email')).'|'.$request->ip());
    }
}
