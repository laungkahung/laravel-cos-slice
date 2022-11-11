<?php

namespace Laungkahung\LaravelCosSlice;

use Illuminate\Http\Request;

class StrategyResolver
{

    public static function resolveFromRequest(Request $request, string $name = null): Strategy
    {
        $config = \array_replace_recursive(
            config('uploader.strategies.default', []),
            config("uploader.strategies.{$name}", [])
        );

        $formName = $config['name'] ?? 'file';

        \abort_if(!$request->hasFile($formName), 422, \sprintf('No file "%s" uploaded.', $formName));

        return new Strategy($config, $request->file($formName));
    }
}
