<?php

namespace Laungkahung\LaravelCosSlice;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Facades\Facade;

class LaravelUploader extends Facade
{
    /**
     * @param array $options
     *
     * @throws BindingResolutionException
     */
    public static function routes(array $options = [])
    {
        if (!self::$app->routesAreCached()) {
            self::$app->make('router')->post(
                'files/slice-upload',
                \array_merge([
                    'uses' => [\Laungkahung\LaravelCosSlice\Http\Controllers\UploadController::class, 'sliceUpload'],
                    'as' => 'file.slice-upload',
                ], $options)
            );
            self::$app->make('router')->post(
                'files/slice-upload-merge',
                \array_merge([
                    'uses' => [\Laungkahung\LaravelCosSlice\Http\Controllers\UploadController::class, 'sliceUploadMerge'],
                    'as' => 'file.slice-upload-merge',
                ], $options)
            );
        }
    }
}
