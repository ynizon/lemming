<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App;
use Cache;
use File;

class LocalizationServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        Cache::pull('translations');
        Cache::rememberForever('translations', function () {
            return file_get_contents(resource_path('lang/') . App::getLocale() . ".json");
        });
    }
}
