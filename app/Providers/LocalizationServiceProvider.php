<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App;
use Cache;
use File;

class LocalizationServiceProvider extends ServiceProvider
{
    /**
     * @var string
     */
    private $langPath;

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
        /*
        $this->langPath = resource_path( 'lang/'. App::getLocale() );
        Cache::rememberForever( 'translations', function () {
            return collect( File::allFiles( $this->langPath ) )->flatMap( function ( $file ) {
                return [
                    $translation = $file->getBasename( '.php' ) => trans( $translation ),
                ];
            } )->toJson();
        } );
        */

        Cache::pull( 'translations' );
        Cache::rememberForever( 'translations', function () {
            return file_get_contents(resource_path('lang/') . App::getLocale() . ".json");
        });
    }
}
