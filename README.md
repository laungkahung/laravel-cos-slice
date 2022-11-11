<h1 align="center"> laravel-cos-slice </h1>

<p align="center"> A COS SDK.</p>


## Installing

1. Install package:
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

## Usage



## Contributing

You can contribute in one of three ways:

1. File bug reports using the [issue tracker](https://github.com/laungkahung/laravel-cos-slice/issues).
2. Answer questions or fix bugs on the [issue tracker](https://github.com/laungkahung/laravel-cos-slice/issues).
3. Contribute new features or update the wiki.

_The code contribution process is not very formal. You just need to make sure that you follow the PSR-0, PSR-1, and PSR-2 coding guidelines. Any new code contributions must be accompanied by unit tests where applicable._

## License

MIT



