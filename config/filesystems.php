<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default filesystem disk that should be used
    | by the framework. The "local" disk, as well as a variety of cloud
    | based disks are available to your application. Just store away!
    |
    */

    'default' => 'local',

    /*
    |--------------------------------------------------------------------------
    | Default Cloud Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Many applications store files both locally and in the cloud. For this
    | reason, you may specify a default "cloud" driver here. This driver
    | will be bound as the Cloud disk implementation in the container.
    |
    */

    'cloud' => 's3',

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    |
    | Here you may configure as many filesystem "disks" as you wish, and you
    | may even configure multiple disks of the same driver. Defaults have
    | been setup for each driver as an example of the required options.
    |
    | Supported Drivers: "local", "ftp", "s3", "rackspace"
    |
    */

    'disks' => [

        'local' => [
            'driver' => 'local',
            'root' => storage_path('app'),
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'visibility' => 'public',
        ],

        's3' => [
            'driver' => 's3',
            'key' => 'your-key',
            'secret' => 'your-secret',
            'region' => 'your-region',
            'bucket' => 'your-bucket',
        ],

        'storage' => [
            'driver' => 'ftp',
            'host' => env('FTP_STORAGE_HOST'),
            'directory' => env('FTP_STORAGE_DIR'),
            'username' => env('FTP_STORAGE_USER'),
            'password' => env('FTP_STORAGE_PASSWORD'),
            'root' => env('FTP_STORAGE_DIR')
        ],

        'updates' => [
            'driver' => 'ftp',
            'host' => env('FTP_TUP_HOST'),
            'directory' => env('FTP_TUP_DIR'),
            'username' => env('FTP_TUP_USER'),
            'password' => env('FTP_TUP_PASSWORD'),
            'root' => env('FTP_TUP_DIR')
        ],

        'annotations' => [
            'driver' => 'ftp',
            'host' => env('FTP_ANNO_HOST'),
            'directory' => env('FTP_ANNO_DIR'),
            'username' => env('FTP_ANNO_USER'),
            'password' => env('FTP_ANNO_PASSWORD'),
            'root' => env('FTP_ANNO_DIR')
        ],

        'media_updates' => [
            'driver' => 'ftp',
            'host' => env('FTP_MEDIA_HOST'),
            'directory' => env('FTP_MEDIA_DIR_UPDATES'),
            'username' => env('FTP_MEDIA_USER'),
            'password' => env('FTP_MEDIA_PASSWORD'),
            'root' => env('FTP_MEDIA_DIR_UPDATES')
        ],

        'media_initial' => [
            'driver' => 'ftp',
            'host' => env('FTP_MEDIA_HOST'),
            'directory' => env('FTP_MEDIA_DIR_INITIAL'),
            'username' => env('FTP_MEDIA_USER'),
            'password' => env('FTP_MEDIA_PASSWORD'),
            'root' => env('FTP_MEDIA_DIR_INITIAL')
        ]
    ]
];
