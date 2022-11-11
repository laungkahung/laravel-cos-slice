<?php

namespace Laungkahung\LaravelCosSlice;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use JetBrains\PhpStorm\Pure;

class Response implements Jsonable, Arrayable
{
    public string $disk;
    public string $path;
    public ?string $mime;
    public string $size;
    public string $url;
    public string $location;
    public string $relativeUrl;
    public string $filename;
    public string $extension;
    public string $originalName;
    public UploadedFile $file;
    public Strategy $strategy;

    /**
     * @param string $path
     * @param Strategy $strategy
     * @param UploadedFile $file
     * @param string|null $originalName
     */
    public function __construct(string $path, Strategy $strategy, UploadedFile $file, ?string $originalName)
    {
        $disk = Storage::disk($strategy->getDisk());
        $baseUri = rtrim(config('uploader.base_uri'), '/');
        $driver = config('filesystems.disks.%s.driver', $strategy->getDisk());
        $relativeUrl = \sprintf('/%s', \ltrim($path, '/'));
        $url = url($path);

        if ($baseUri && 'local' !== $driver) {
            $url = \sprintf('%s/%s', $baseUri, $path);
        } elseif (method_exists($disk, 'url')) {
            $url = $disk->url($path);
        }

        $this->path = $path;
        $this->file = $file;
        $this->disk = $strategy->getDisk();
        $this->strategy = $strategy;
        $this->filename = \basename($path);
        $this->extension = $file->getClientOriginalExtension();
        $this->originalName = $originalName ?? $file->getClientOriginalName();
        $this->mime = $file->getMimeType();
        $this->size = $file->getSize();
        $this->url = $url;
        $this->relativeUrl = $relativeUrl;
    }

    /**
     * @param int $options
     *
     * @return string
     */
    public function toJson($options = 0): string
    {
        return \json_encode($this->toArray());
    }

    /**
     * @return array
     */
    #[Pure] public function toArray(): array
    {
        return [
            'mime' => $this->mime,
            'size' => $this->size,
            'path' => $this->path,
            'url' => $this->url,
            'disk' => $this->disk,
            'filename' => $this->filename,
            'extension' => $this->extension,
            'relative_url' => $this->relativeUrl,
            'location' => $this->url,
            'original_name' => $this->originalName,
            'strategy' => $this->strategy->getName(),
        ];
    }
}
