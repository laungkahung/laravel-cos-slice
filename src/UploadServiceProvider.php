<?php

namespace Laungkahung\LaravelCosSlice;

use Illuminate\Support\ServiceProvider;

class UploadServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->loadConfig();
    }

    protected function loadConfig()
    {
        $this->publishes([
            __DIR__ . '/../config/uploader.php' => config_path('uploader.php'),
        ]);
    }
}
