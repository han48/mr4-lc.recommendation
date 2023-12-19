<?php

namespace Mr4Lc\Recommendation;

use Illuminate\Support\ServiceProvider;

class RecommendationServiceProvider extends ServiceProvider
{

    public $assets = __DIR__ . '/../resources/assets';
    public $views = __DIR__ . '/../resources/views';
    public $config = __DIR__ . '/../config';

    /**
     * {@inheritdoc}
     */
    public function boot()
    {
        if ($this->app->runningInConsole() && $config = $this->config) {
            $this->publishes(
                [$config => config_path('')],
                'mr4-lc-recommendation'
            );
        }

        if ($this->app->runningInConsole() && $assets = $this->assets) {
            $this->publishes(
                [$assets => public_path('vendor/mr4-lc/recommendation')],
                'mr4-lc-recommendation'
            );
        }

        if ($this->app->runningInConsole() && $views = $this->views) {
            $this->publishes(
                [$views => resource_path('views/components/mr4-lc')],
                'mr4-lc-recommendation'
            );
        }

        if ($this->app->runningInConsole()) {
            $this->commands([
                \Mr4Lc\Recommendation\Console\Commands\RecommendationCreate::class,
                \Mr4Lc\Recommendation\Console\Commands\RecommendationExportData::class,
            ]);
        }
    }
}
