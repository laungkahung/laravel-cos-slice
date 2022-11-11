<?php

namespace Laungkahung\LaravelCosSlice;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SliceUploaded
{
    public static function resolveFromRequest(Request $request, string $name = null): Strategy
    {
        $config = \array_replace_recursive(
            config('uploader.strategies.default', []),
            config("uploader.strategies.{$name}", [])
        );

        $disk = Storage::disk('public');
        $requiredId = $request->get(config('uploader.slice_field_names.required_id'));

        \abort_if(!$disk->exists('slices/' . $requiredId), 422, \sprintf('No Found directory "%s" uploaded.', $requiredId));

        return new Strategy($config);
    }

}
