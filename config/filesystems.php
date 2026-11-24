<?php

/*
|--------------------------------------------------------------------------
| Persistent application storage
|--------------------------------------------------------------------------
|
| HostForge replaces the application container on deploy, so anything under
| the release directory can disappear. Point LMS_STORAGE_PATH at the
| platform's persistent volume (for example /data/lms). Uploads, public
| uploads and backups all live below this one root, which keeps a backup and
| the bytes it describes on the same durable filesystem.
|
| Leave it unset for local development; storage_path('app') is used then.
| A relative value is resolved against the project root so a misconfigured
| panel value cannot silently write to the process working directory.
*/
$storageRoot = env('LMS_STORAGE_PATH');
$storageRoot = is_string($storageRoot) ? trim($storageRoot) : '';

$isWindowsAbsolute = strlen($storageRoot) >= 3
    && ctype_alpha($storageRoot[0])
    && $storageRoot[1] === ':'
    && ($storageRoot[2] === '\\' || $storageRoot[2] === '/');

if ($storageRoot === '') {
    $storageRoot = storage_path('app');
} elseif (! str_starts_with($storageRoot, '/')
    && ! str_starts_with($storageRoot, '\\')
    && ! $isWindowsAbsolute) {
    $storageRoot = base_path($storageRoot);
}

$storageRoot = rtrim($storageRoot, '/\\');

// A root of "/" would otherwise become an empty string after rtrim.
if ($storageRoot === '') {
    $storageRoot = DIRECTORY_SEPARATOR;
}

return [

    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default filesystem disk that should be used
    | by the framework. The "local" disk, as well as a variety of cloud
    | based disks are available to your application for file storage.
    |
    */

    'default' => env('FILESYSTEM_DISK', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    |
    | Below you may configure as many filesystem disks as necessary, and you
    | may even configure multiple disks for the same driver. Examples for
    | most supported storage drivers are configured here for reference.
    |
    | Supported drivers: "local", "ftp", "sftp", "s3"
    |
    */

    'disks' => [

        'local' => [
            'driver' => 'local',
            'root' => $storageRoot,
            'serve' => true,
            'throw' => false,
            'report' => false,
        ],

        'public' => [
            'driver' => 'local',
            'root' => $storageRoot.'/public',
            'url' => env('APP_URL').'/storage',
            'visibility' => 'public',
            'throw' => false,
            'report' => false,
        ],

        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
            'endpoint' => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
            'throw' => false,
            'report' => false,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Symbolic Links
    |--------------------------------------------------------------------------
    |
    | Here you may configure the symbolic links that will be created when the
    | `storage:link` Artisan command is executed. The array keys should be
    | the locations of the links and the values should be their targets.
    |
    */

    'links' => [
        public_path('storage') => $storageRoot.'/public',
    ],

];
