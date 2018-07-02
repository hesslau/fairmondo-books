<?php
return [
    "updates" => [
        "host" => env("FTP_TUP_HOST"),
        "directory" => env("FTP_TUP_DIR"),
        "user" => env("FTP_TUP_USER"),
        "password" => env("FTP_TUP_PASSWORD"),
        "downloadDirectory" => storage_path("app/download")
        ],
    "annotations" => [
        "host" => env("FTP_ANNO_HOST"),
        "directory" => env("FTP_ANNO_DIR"),
        "user" => env("FTP_ANNO_USER"),
        "password" => env("FTP_ANNO_PASSWORD"),
        "downloadDirectory" => storage_path("app/annotations")
    ],
    "storage" => [
        "host" => env("FTP_STORAGE_HOST"),
        "directory" => env("FTP_STORAGE_DIR"),
        "user" => env("FTP_STORAGE_USER"),
        "password" => env("FTP_STORAGE_PASSWORD")
    ]
];