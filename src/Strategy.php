<?php

namespace Laungkahung\LaravelCosSlice;

use Laungkahung\LaravelCosSlice\Events\FileUploaded;
use Laungkahung\LaravelCosSlice\Events\FileUploading;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Fluent;
use Illuminate\Support\Str;
use JetBrains\PhpStorm\Pure;
use Symfony\Component\HttpFoundation\File\UploadedFile as SymfonyUploadedFile;

class Strategy
{
    const SLICE_TOTAL = 1;//文件大小，小于等于1片的都不需要合成

    protected string $disk;
    protected string $directory;
    protected array $mimes = [];
    protected string $name;
    protected int $maxSize = 0;
    protected string $filenameType;
    protected UploadedFile|null $file;
    protected Fluent $config;

    /**
     * @param array $config
     * @param UploadedFile|null $file
     */
    public function __construct(array $config, ?UploadedFile $file = null)
    {
        $config = new Fluent($config);
        $this->config = $config;

        $this->file = $file;
        $this->disk = $config->get('disk', config('filesystems.default'));
        $this->mimes = $config->get('mimes', ['*']);
        $this->name = $config->get('name', 'file');
        $this->directory = $config->get('directory');
        $this->maxSize = $this->filesize2bytes($config->get('max_size', 0));
        $this->filenameType = $config->get('filename_type', 'md5_file');
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getDisk(): string
    {
        return $this->disk;
    }

    /**
     * @return mixed
     */
    public function getMimes(): mixed
    {
        return $this->mimes;
    }

    /**
     * @return int|string
     */
    public function getMaxSize(): int|string
    {
        return $this->maxSize;
    }

    public function getFile(): UploadedFile
    {
        return $this->file;
    }

    /**
     * @return string
     */
    public function getFilename(): string
    {
        return match ($this->filenameType) {
            'original' => $this->file->getClientOriginalName(),
            'md5_file' => md5_file($this->file->getRealPath()) . 'laravel-cos-slice' . $this->file->getClientOriginalExtension(),
            default => $this->file->hashName(),
        };
    }

    /**
     * @param array $options
     * @return string
     */
    public function getMergeFilename(array $options): string
    {
        abort_if($this->filenameType === 'original' && (isset($options['original_name']) && empty($options['original_name'])), 422, '找不到original_name');

        return match ($this->filenameType) {
            'original' => $options['original_name'],
            'md5_file' => md5_file($options['path']) . 'laravel-cos-slice' . $options['extension'],
            default => Str::random(40) . 'laravel-cos-slice' . $options['extension'],
        };
    }

    /**
     * @return string
     */
    public function getDirectory(): string
    {
        return $this->directory;
    }

    /**
     * @return bool
     */
    #[Pure] public function isValidMime(): bool
    {
        return $this->mimes === ['*'] || in_array($this->file->getClientMimeType(), $this->mimes);
    }

    /**
     * @return bool
     */
    public function isValidSize(): bool
    {
        return $this->file->getSize() <= $this->maxSize || 0 === $this->maxSize;
    }

    /**
     * @return void
     */
    public function validate(): void
    {
        if (!$this->isValidMime()) {
            abort(422, sprintf('Invalid mime "%s".', $this->file->getMimeType()));
        }

        if (!$this->isValidSize()) {
            abort(422, sprintf('File has too large size("%s").', $this->file->getSize()));
        }
    }

    /**
     * @param array $options
     * @return Response
     */
    public function upload(array $options = []): Response
    {
        $this->validate();

        $path = $this->filepath($options);

        Event::dispatch(new FileUploading($this->file));

        $stream = fopen($this->file->getRealPath(), 'r');

        $result = Storage::disk($this->disk)->put($path, $stream, $options);
        $response = new Response($result ? $path : false, $this, $this->file, $options[config('uploader.slice_field_names.original_name')]);

        Event::dispatch(new FileUploaded($this->file, $response, $this));

        if (is_resource($stream)) {
            fclose($stream);
        }

        return $response;
    }

    public function merge(array $options = []): Response
    {
        $disk = Storage::disk('public');

        $slicesDir = 'slices/' . $options['required_id'];
        $slices = $disk->files($slicesDir);

        array_multisort($slices, array_values($slices));

        $mime = $disk->mimeType($slices[0]);
        $extension = $this->mime2extension($mime);
        abort_if(!$extension, 422, '找不到对应文件扩展名，可通过配置文件添加');

        $options['path'] = $disk->path($slices[0]);
        $options['extension'] = $extension;
        $filename = $this->getMergeFilename($options);

        $dir = rtrim($this->formatDirectory($this->directory), '/');
        $path = sprintf('%s/%s', $dir, $filename);

        $fullPath = $disk->path($path);

        if (!$disk->exists($dir)) {
            $disk->makeDirectory($dir);
        }

        foreach ($slices as $slice) {
            $file = $disk->get($slice);
            file_put_contents($fullPath, $file, FILE_APPEND);
        }
        $disk->deleteDirectory($slicesDir);

        $result = '';
        if ($this->disk !== 'local') {
            $stream = fopen($fullPath, 'r');
            $result = Storage::disk($this->disk)->put($path, $stream, $options);

            if (is_resource($stream)) {
                fclose($stream);
            }
        }

        $this->file = UploadedFile::createFromBase(new SymfonyUploadedFile($fullPath, $filename, $mime));

        return new Response($result ? $path : false, $this, $this->file, $options['original_name'] ?? null);
    }

    /**
     * Replace date variable in dir path.
     *
     * @param string $dir
     *
     * @return string
     */
    protected function formatDirectory(string $dir): string
    {
        $replacements = [
            '{Y}' => date('Y'),
            '{m}' => date('m'),
            '{d}' => date('d'),
            '{H}' => date('H'),
            '{i}' => date('i'),
            '{s}' => date('s'),
        ];

        return str_replace(array_keys($replacements), $replacements, $dir);
    }

    /**
     * @param mixed $humanFileSize
     *
     * @return int
     */
    protected function filesize2bytes(mixed $humanFileSize): int
    {
        $bytesUnits = array(
            'K' => 1024,
            'M' => 1024 * 1024,
            'G' => 1024 * 1024 * 1024,
            'T' => 1024 * 1024 * 1024 * 1024,
            'P' => 1024 * 1024 * 1024 * 1024 * 1024,
        );

        $bytes = floatval($humanFileSize);

        if (preg_match('~([KMGTP])$~si', rtrim($humanFileSize, 'B'), $matches)
            && !empty($bytesUnits[strtoupper($matches[1])])) {
            $bytes *= $bytesUnits[strtoupper($matches[1])];
        }

        return intval(round($bytes, 2));
    }

    protected function filepath(array $options): string
    {
        if (isset($options[config('uploader.slice_field_names.current')], $options[config('uploader.slice_field_names.total')], $options[config('uploader.slice_field_names.required_id')])
            && $options[config('uploader.slice_field_names.total')] > self::SLICE_TOTAL) {

            $this->disk = 'public';//分片上传，文件只能临时放本地
            return sprintf('%s/%s/%s',
                'slices/',
                rtrim($options[config('uploader.slice_field_names.required_id')], '/'),
                $options[config('uploader.slice_field_names.current')]
            );
        }

        return sprintf('%s/%s', rtrim($this->formatDirectory($this->directory), '/'), $this->getFilename());
    }

    protected function mime2extension($mime): string
    {
        $mimes = [
            'video/3gpp2' => '3g2',
            'video/3gp' => '3gp',
            'video/3gpp' => '3gp',
            'application/x-compressed' => '7zip',
            'audio/x-acc' => 'aac',
            'audio/ac3' => 'ac3',
            'application/postscript' => 'ai',
            'audio/x-aiff' => 'aif',
            'audio/aiff' => 'aif',
            'audio/x-au' => 'au',
            'video/x-msvideo' => 'avi',
            'video/msvideo' => 'avi',
            'video/avi' => 'avi',
            'application/x-troff-msvideo' => 'avi',
            'application/macbinary' => 'bin',
            'application/mac-binary' => 'bin',
            'application/x-binary' => 'bin',
            'application/x-macbinary' => 'bin',
            'image/bmp' => 'bmp',
            'image/x-bmp' => 'bmp',
            'image/x-bitmap' => 'bmp',
            'image/x-xbitmap' => 'bmp',
            'image/x-win-bitmap' => 'bmp',
            'image/x-windows-bmp' => 'bmp',
            'image/ms-bmp' => 'bmp',
            'image/x-ms-bmp' => 'bmp',
            'application/bmp' => 'bmp',
            'application/x-bmp' => 'bmp',
            'application/x-win-bitmap' => 'bmp',
            'application/cdr' => 'cdr',
            'application/coreldraw' => 'cdr',
            'application/x-cdr' => 'cdr',
            'application/x-coreldraw' => 'cdr',
            'image/cdr' => 'cdr',
            'image/x-cdr' => 'cdr',
            'zz-application/zz-winassoc-cdr' => 'cdr',
            'application/mac-compactpro' => 'cpt',
            'application/pkix-crl' => 'crl',
            'application/pkcs-crl' => 'crl',
            'application/x-x509-ca-cert' => 'crt',
            'application/pkix-cert' => 'crt',
            'text/css' => 'css',
            'text/x-comma-separated-values' => 'csv',
            'text/comma-separated-values' => 'csv',
            'application/vnd.msexcel' => 'csv',
            'application/x-director' => 'dcr',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
            'application/x-dvi' => 'dvi',
            'message/rfc822' => 'eml',
            'application/x-msdownload' => 'exe',
            'video/x-f4v' => 'f4v',
            'audio/x-flac' => 'flac',
            'video/x-flv' => 'flv',
            'image/gif' => 'gif',
            'application/gpg-keys' => 'gpg',
            'application/x-gtar' => 'gtar',
            'application/x-gzip' => 'gzip',
            'application/mac-binhex40' => 'hqx',
            'application/mac-binhex' => 'hqx',
            'application/x-binhex40' => 'hqx',
            'application/x-mac-binhex40' => 'hqx',
            'text/html' => 'html',
            'image/x-icon' => 'ico',
            'image/x-ico' => 'ico',
            'image/vnd.microsoft.icon' => 'ico',
            'text/calendar' => 'ics',
            'application/java-archive' => 'jar',
            'application/x-java-application' => 'jar',
            'application/x-jar' => 'jar',
            'image/jp2' => 'jp2',
            'video/mj2' => 'jp2',
            'image/jpx' => 'jp2',
            'image/jpm' => 'jp2',
            'image/jpeg' => 'jpeg',
            'image/pjpeg' => 'jpeg',
            'application/x-javascript' => 'js',
            'application/json' => 'json',
            'text/json' => 'json',
            'application/vnd.google-earth.kml+xml' => 'kml',
            'application/vnd.google-earth.kmz' => 'kmz',
            'text/x-log' => 'log',
            'audio/x-m4a' => 'm4a',
            'audio/mp4' => 'm4a',
            'application/vnd.mpegurl' => 'm4u',
            'audio/midi' => 'mid',
            'application/vnd.mif' => 'mif',
            'video/quicktime' => 'mov',
            'video/x-sgi-movie' => 'movie',
            'audio/mpeg' => 'mp3',
            'audio/mpg' => 'mp3',
            'audio/mpeg3' => 'mp3',
            'audio/mp3' => 'mp3',
            'video/mp4' => 'mp4',
            'video/mpeg' => 'mpeg',
            'application/oda' => 'oda',
            'audio/ogg' => 'ogg',
            'video/ogg' => 'ogg',
            'application/ogg' => 'ogg',
            'application/x-pkcs10' => 'p10',
            'application/pkcs10' => 'p10',
            'application/x-pkcs12' => 'p12',
            'application/x-pkcs7-signature' => 'p7a',
            'application/pkcs7-mime' => 'p7c',
            'application/x-pkcs7-mime' => 'p7c',
            'application/x-pkcs7-certreqresp' => 'p7r',
            'application/pkcs7-signature' => 'p7s',
            'application/pdf' => 'pdf',
            'application/octet-stream' => 'pdf',
            'application/x-x509-user-cert' => 'pem',
            'application/x-pem-file' => 'pem',
            'application/pgp' => 'pgp',
            'application/x-httpd-php' => 'php',
            'application/php' => 'php',
            'application/x-php' => 'php',
            'text/php' => 'php',
            'text/x-php' => 'php',
            'application/x-httpd-php-source' => 'php',
            'image/png' => 'png',
            'image/x-png' => 'png',
            'application/powerpoint' => 'ppt',
            'application/vnd.ms-powerpoint' => 'ppt',
            'application/vnd.ms-office' => 'ppt',
            'application/msword' => 'ppt',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'pptx',
            'application/x-photoshop' => 'psd',
            'image/vnd.adobe.photoshop' => 'psd',
            'audio/x-realaudio' => 'ra',
            'audio/x-pn-realaudio' => 'ram',
            'application/x-rar' => 'rar',
            'application/rar' => 'rar',
            'application/x-rar-compressed' => 'rar',
            'audio/x-pn-realaudio-plugin' => 'rpm',
            'application/x-pkcs7' => 'rsa',
            'text/rtf' => 'rtf',
            'text/richtext' => 'rtx',
            'video/vnd.rn-realvideo' => 'rv',
            'application/x-stuffit' => 'sit',
            'application/smil' => 'smil',
            'text/srt' => 'srt',
            'image/svg+xml' => 'svg',
            'application/x-shockwave-flash' => 'swf',
            'application/x-tar' => 'tar',
            'application/x-gzip-compressed' => 'tgz',
            'image/tiff' => 'tiff',
            'text/plain' => 'txt',
            'text/x-vcard' => 'vcf',
            'application/videolan' => 'vlc',
            'text/vtt' => 'vtt',
            'audio/x-wav' => 'wav',
            'audio/wave' => 'wav',
            'audio/wav' => 'wav',
            'application/wbxml' => 'wbxml',
            'video/webm' => 'webm',
            'audio/x-ms-wma' => 'wma',
            'application/wmlc' => 'wmlc',
            'video/x-ms-wmv' => 'wmv',
            'video/x-ms-asf' => 'wmv',
            'application/xhtml+xml' => 'xhtml',
            'application/excel' => 'xl',
            'application/msexcel' => 'xls',
            'application/x-msexcel' => 'xls',
            'application/x-ms-excel' => 'xls',
            'application/x-excel' => 'xls',
            'application/x-dos_ms_excel' => 'xls',
            'application/xls' => 'xls',
            'application/x-xls' => 'xls',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
            'application/vnd.ms-excel' => 'xlsx',
            'application/xml' => 'xml',
            'text/xml' => 'xml',
            'text/xsl' => 'xsl',
            'application/xspf+xml' => 'xspf',
            'application/x-compress' => 'z',
            'application/x-zip' => 'zip',
            'application/zip' => 'zip',
            'application/x-zip-compressed' => 'zip',
            'application/s-compressed' => 'zip',
            'multipart/x-zip' => 'zip',
            'text/x-scriptzsh' => 'zsh',
        ];
        $mimes = array_merge($mimes, $this->config->get('mime2extension', []));

        return $mimes[$mime] ?? false;
    }
}
