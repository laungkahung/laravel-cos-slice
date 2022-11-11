<?php

namespace Laungkahung\LaravelCosSlice\Http\Controllers;

use Laungkahung\LaravelCosSlice\Http\Requests\SliceUploadDoneRequest;
use Laungkahung\LaravelCosSlice\Http\Requests\SliceUploadRequest;
use Laungkahung\LaravelCosSlice\SliceUploaded;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller as BaseController;
use Laungkahung\LaravelCosSlice\StrategyResolver;

class UploadController extends BaseController
{
    const ONLY_ONE = 1;//仅有一片的情况下

    /**
     * 分片上传
     */
    public function sliceUpload(SliceUploadRequest $sliceUploadRequest): Response|JsonResponse
    {
        $sliceFieldNames = config('uploader.slice_field_names');
        $options = $sliceUploadRequest->only(array_values($sliceFieldNames));

        $resp = StrategyResolver::resolveFromRequest($sliceUploadRequest, $sliceUploadRequest->get('strategy', 'default'))->upload($options);

        if ($options[$sliceFieldNames['total']] == self::ONLY_ONE) {

            return response()->json($resp);
        }

        return response()->noContent();
    }

    /**
     * 所有分片上传完毕后，通知合成文件
     * @param SliceUploadDoneRequest $sliceUploadDoneRequest
     * @return JsonResponse
     */
    public function sliceUploadMerge(SliceUploadDoneRequest $sliceUploadDoneRequest): JsonResponse
    {
        $resp = SliceUploaded::resolveFromRequest($sliceUploadDoneRequest, $sliceUploadDoneRequest->get('strategy', 'default'))
            ->merge(['required_id' => $sliceUploadDoneRequest->get('required_id'), 'original_name' => $sliceUploadDoneRequest->get('original_name')]);

        return response()->json($resp);
    }
}
