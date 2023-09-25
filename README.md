<h1 align="center"> laravel-cos-slice </h1>

<p align="center"> A COS SDK.</p>

# Requirement

-   Laravel >= 9.0 
-   PHP >= 8.1

# Installing

1. Install Package:
    ```shell
   $ composer require laungkahung/laravel-cos-slice -vvv
    ```
    
    and publish the assets using command:
    
    ```shell
    $ php artisan vendor:publish --provider=Laungkahung\\LaravelCosSlice\\UploadServiceProvider
    ```
2. Routing

    You can register routes in `routes/admin.php` or other routes file:

    ```php
    \LaravelUploader::routes();
    
    // custom
    \LaravelUploader::routes([
       'as' => 'files.upload', 
       'middleware' => ['auth'],
       //...
    ]); 
    ```

# Configuration

1. Add a new disk to your `config/filesystems.php` config:

    ```php
    <?php
    
    return [
       'disks' => [
           //...
           'cos' => [
               'driver' => 'cos',
    
               'app_id'     => env('COS_APP_ID'),
               'secret_id'  => env('COS_SECRET_ID'),
               'secret_key' => env('COS_SECRET_KEY'),
               'region'     => env('COS_REGION', 'ap-guangzhou'),
    
               'bucket'     => env('COS_BUCKET'),  // 不带数字 app_id 后缀
               'cdn'        => env('COS_CDN'),
               'signed_url' => false,
    
               'prefix' => env('COS_PATH_PREFIX'), // 全局路径前缀
    
               'guzzle' => [
                   'timeout' => env('COS_TIMEOUT', 60),
                   'connect_timeout' => env('COS_CONNECT_TIMEOUT', 60),
               ],
           ],
           //...
        ]
    ];
    ```
    
    > 🚨 请注意：example-1230000001.cos.ap-guangzhou.mycloud.com
    >
    > 其中：**bucket**: example, **app_id**: 1230000001, **region**: ap-guangzhou

# Usage

```html
// 发送文件 html文件在根目录html/upload.html
function sendFile(blob, file) {
  var form_data = new FormData();
  var total_blob_num = Math.ceil(file.size / LENGTH);

  form_data.append("file", blob);
  form_data.append("required_id", uuid);
  form_data.append("blob_num", Number(blob_num));
  form_data.append("total_blob_num", Number(total_blob_num));
  form_data.append("original_name", original_name);

  xhr.open(
    "POST",
    "http://localhost:8000/api/files/slice-upload",
    false
  );

    ....
}
```

```php
$disk = Storage::disk('cos');

// create a file
$disk->put('avatars/filename.jpg', $fileContents);

// check if a file exists
$exists = $disk->has('file.jpg');

// get timestamp
$time = $disk->lastModified('file1.jpg');
$time = $disk->getTimestamp('file1.jpg');

// copy a file
$disk->copy('old/file1.jpg', 'new/file1.jpg');

// move a file
$disk->move('old/file1.jpg', 'new/file1.jpg');

// get file contents
$contents = $disk->read('folder/my_file.txt');
```

[Full API documentation.](http://flysystem.thephpleague.com/api/)

## Project supported by [overtrue](https://github.com/overtrue)

## Contributing

You can contribute in one of three ways:

1. File bug reports using the [issue tracker](https://github.com/laungkahung/laravel-cos-slice/issues).
2. Answer questions or fix bugs on the [issue tracker](https://github.com/laungkahung/laravel-cos-slice/issues).
3. Contribute new features or update the wiki.

_The code contribution process is not very formal. You just need to make sure that you follow the PSR-0, PSR-1, and PSR-2 coding guidelines. Any new code contributions must be accompanied by unit tests where applicable._

## License

MIT



