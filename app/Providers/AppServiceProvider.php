<?php

namespace App\Providers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Model::preventLazyLoading(! app()->isProduction());

        Request::macro('isHtmx', function (): bool {
            return $this->hasHeader('HX-Request');
        });

        \Illuminate\Support\Facades\Blade::directive('usd', fn ($expr) => "<?php echo \\App\\Support\\Money::usd($expr); ?>");
    }
}
