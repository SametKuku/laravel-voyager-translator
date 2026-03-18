<?php

namespace SametKuku\VoyagerTranslator;

use Illuminate\Support\ServiceProvider;
use SametKuku\VoyagerTranslator\Commands\TranslateCommand;

class VoyagerTranslatorServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/voyager-translator.php', 'voyager-translator');
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([TranslateCommand::class]);

            $this->publishes([
                __DIR__ . '/../config/voyager-translator.php' => config_path('voyager-translator.php'),
            ], 'voyager-translator-config');
        }
    }
}
