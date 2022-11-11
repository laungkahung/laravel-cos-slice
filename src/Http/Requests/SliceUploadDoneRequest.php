<?php

namespace Laungkahung\LaravelCosSlice\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use JetBrains\PhpStorm\ArrayShape;

class SliceUploadDoneRequest extends FormRequest
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
    #[ArrayShape(['required_id' => "string", 'original_name' => "string"])] public function rules(): array
    {
        return [
            'required_id' => 'required|string|max:255',
            'original_name' => 'required|string|max:255',
        ];
    }
}
