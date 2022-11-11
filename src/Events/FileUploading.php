<?php

namespace Laungkahung\LaravelCosSlice\Events;

use Illuminate\Http\UploadedFile;

class FileUploading
{
    public UploadedFile $file;

    /**
     * @param UploadedFile $file
     */
    public function __construct(UploadedFile $file)
    {
        $this->file = $file;
    }
}
