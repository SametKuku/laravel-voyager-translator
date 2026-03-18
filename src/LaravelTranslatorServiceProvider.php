<?php

namespace SametKuku\LaravelTranslator;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use SametKuku\LaravelTranslator\Commands\TranslateCommand;
use SametKuku\LaravelTranslator\Http\Controllers\TranslatorController;

class LaravelTranslatorServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/laravel-translator.php', 'laravel-translator');
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'laravel-translator');

        $this->registerRoutes();

        if ($this->app->runningInConsole()) {
            $this->commands([TranslateCommand::class]);

            $this->publishes([
                __DIR__ . '/../config/laravel-translator.php' => config_path('laravel-translator.php'),
            ], 'laravel-translator-config');

            $this->publishes([
                __DIR__ . '/../resources/views' => resource_path('views/vendor/laravel-translator'),
            ], 'laravel-translator-views');
        }
    }

    private function registerRoutes(): void
    {
        $prefix     = config('laravel-translator.route_prefix', 'laravel-translator');
        $middleware = config('laravel-translator.middleware', ['web']);

        Route::prefix($prefix)
            ->middleware($middleware)
            ->name('laravel-translator.')
            ->group(function () {
                // UI
                Route::get('/', [TranslatorController::class, 'index'])->name('index');

                // Voyager / DB mode
                Route::post('/load-db',         [TranslatorController::class, 'loadFromDb'])->name('load-db');
                Route::post('/upload-sql',      [TranslatorController::class, 'uploadSql'])->name('upload-sql');
                Route::post('/translate-batch', [TranslatorController::class, 'translateBatch'])->name('translate-batch');
                Route::post('/save',            [TranslatorController::class, 'saveToDb'])->name('save');
                Route::get('/export/sql',       [TranslatorController::class, 'exportSql'])->name('export-sql');
                Route::get('/export/json',      [TranslatorController::class, 'exportJson'])->name('export-json');

                // Lang Files mode
                Route::get('/lang/scan',              [TranslatorController::class, 'scanLang'])->name('lang-scan');
                Route::post('/lang/load',             [TranslatorController::class, 'loadLang'])->name('lang-load');
                Route::post('/lang/translate-batch',  [TranslatorController::class, 'translateLangBatch'])->name('lang-translate-batch');
                Route::post('/lang/write',            [TranslatorController::class, 'writeLangFiles'])->name('lang-write');
                Route::get('/lang/export',            [TranslatorController::class, 'exportLangZip'])->name('lang-export');
            });
    }
}
