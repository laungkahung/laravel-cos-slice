<?php

namespace Laungkahung\LaravelCosSlice\Events;

use Laungkahung\LaravelCosSlice\Response;
use Laungkahung\LaravelCosSlice\Strategy;
use Illuminate\Http\UploadedFile;

class FileUploaded
{
    public UploadedFile $file;
    public Response $response;
    public Strategy $strategy;

    /**
     * @param UploadedFile $file
     * @param Response $response
     * @param Strategy $strategy
     */
    public function __construct(UploadedFile $file, Response $response, Strategy $strategy)
    {
        $this->file = $file;
        $this->response = $response;
        $this->strategy = $strategy;
    }
}
