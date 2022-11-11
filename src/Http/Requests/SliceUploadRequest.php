<?php

namespace Laungkahung\LaravelCosSlice\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SliceUploadRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $sliceFieldNames = config('uploader.slice_field_names');
        $strategy = request('strategy', 'default');
        $fileFieldName = config("uploader.strategies.$strategy")['name'];

        return [
            $fileFieldName => 'required|file',
            $sliceFieldNames['current'] => 'required|int|max:1500',
            $sliceFieldNames['total'] => 'required|int|max:1500',
            $sliceFieldNames['required_id'] => 'required|string|max:255',
            $sliceFieldNames['original_name'] => [
                Rule::requiredIf(function () use ($sliceFieldNames) {
                    return request($sliceFieldNames['current']) == 1;//当第一片的时候必须传
                }),
                'string',
                'max:255',
            ],
        ];
    }

    public function bodyParameters(): array
    {
        return [];
    }
}
