<?php

namespace SametKuku\VoyagerTranslator;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use SametKuku\VoyagerTranslator\Commands\TranslateCommand;
use SametKuku\VoyagerTranslator\Http\Controllers\TranslatorController;

class VoyagerTranslatorServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/voyager-translator.php', 'voyager-translator');
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'voyager-translator');

        $this->registerRoutes();

        if ($this->app->runningInConsole()) {
            $this->commands([TranslateCommand::class]);

            $this->publishes([
                __DIR__ . '/../config/voyager-translator.php' => config_path('voyager-translator.php'),
            ], 'voyager-translator-config');

            $this->publishes([
                __DIR__ . '/../resources/views' => resource_path('views/vendor/voyager-translator'),
            ], 'voyager-translator-views');
        }
    }

    private function registerRoutes(): void
    {
        $prefix     = config('voyager-translator.route_prefix', 'voyager-translator');
        $middleware = config('voyager-translator.middleware', ['web']);

        Route::prefix($prefix)
            ->middleware($middleware)
            ->name('voyager-translator.')
            ->group(function () {
                Route::get('/',                  [TranslatorController::class, 'index'])->name('index');
                Route::post('/load-db',          [TranslatorController::class, 'loadFromDb'])->name('load-db');
                Route::post('/upload-sql',       [TranslatorController::class, 'uploadSql'])->name('upload-sql');
                Route::post('/translate-batch',  [TranslatorController::class, 'translateBatch'])->name('translate-batch');
                Route::post('/save',             [TranslatorController::class, 'saveToDb'])->name('save');
                Route::get('/export/sql',        [TranslatorController::class, 'exportSql'])->name('export-sql');
                Route::get('/export/json',       [TranslatorController::class, 'exportJson'])->name('export-json');
            });
    }
}
