<?php
require "vendor/autoload.php";
$app = require_once "bootstrap/app.php";
$app->make("Illuminate\Contracts\Console\Kernel")->bootstrap();

$mf = \App\Models\MediaFile::find(10);
if ($mf) {
    echo "Found: " . $mf->id . " | " . $mf->original_name . " | " . $mf->path . " | disk: " . $mf->disk . PHP_EOL;
    echo "URL: " . $mf->url . PHP_EOL;
    echo "Exists on disk: " . (\Illuminate\Support\Facades\Storage::disk($mf->disk)->exists($mf->path) ? "yes" : "NO") . PHP_EOL;
} else {
    echo "MediaFile 10 NOT FOUND" . PHP_EOL;
    $mf = \App\Models\MediaFile::withTrashed()->find(10);
    if ($mf) {
        echo "Found with trashed: " . $mf->id . " | deleted_at: " . $mf->deleted_at . PHP_EOL;
    } else {
        echo "Not found even with trashed" . PHP_EOL;
    }
}