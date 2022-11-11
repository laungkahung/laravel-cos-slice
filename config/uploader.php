<?php

return [
    /*
     * Base uri for $response->url
     */
    'base_uri' => null,

    //分片字段名，以下参数都是由前端提交，并且每一片请求必须提交（除original_name）
    'slice_field_names' => [
        'current' => 'blob_num',//按分片的正序，当前片数 用1、2、3....表示
        'total' => 'total_blob_num',//同一个文件，总分片数
        'required_id' => 'required_id',//同一个文件唯一标识
        'original_name' => 'original_name',//源文件名
    ],

    'strategies' => [
        /*
         * default strategy.
         */
        'default' => [
            /*
             * The form name for file.
             */
            'name' => 'file',

            /*
             * Allowed MIME types.
             */
            'mimes' => ['image/jpeg', 'image/png', 'image/bmp', 'image/gif', 'application/pdf', 'application/octet-stream'],
//            'mime2extension' => ['image/jpeg' => 'jpg', 'image/png' => 'png'],

            /*
             * The disk name to store file, the value is key of `disks` in `config/filesystems.php`
             */
            'disk' => env('FILESYSTEM_DRIVER', 'cos'),

            /*
             * Default directory template.
             * Variables:
             *  - `Y`   Year, example: 2019
             *  - `m`   Month, example: 04
             *  - `d`   Date, example: 08
             *  - `H`   Hour, example: 12
             *  - `i`   Minute, example: 03
             *  - `s`   Second, example: 12
             */
            'directory' => 'uploads/{Y}/{m}/{d}',

            /*
             * File size limit
             */
            'max_size' => '2m',

            /*
             * Strategy of filename.
             *
             * Available:
             *  - `random` Use random string as filename.
             *  - `md5_file` Use md5 of file as filename.
             *  - `original` Use the origin client file name.
             */
            'filename_type' => 'md5_file',
        ],

        /*
         * You can create custom strategy to override the default strategy.
         */
        'avatar' => [
            'directory' => 'avatars/{Y}/{m}/{d}',
        ],

        //...
    ],
];
