<?php

namespace App\Helpers;

class FileHelper
{
    /**
     * Format file size in human-readable format
     *
     * @param  int  $bytes  File size in bytes
     * @param  int  $precision  Number of decimal places
     * @return string Formatted file size
     */
    public static function formatFileSize(int $bytes, int $precision = 2): string
    {
        if ($bytes === 0) {
            return '0 Bytes';
        }

        $units = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
        $exponent = floor(log($bytes, 1024));

        return round($bytes / (1024 ** $exponent), $precision).' '.$units[$exponent];
    }

    /**
     * Get file icon based on extension
     *
     * @param  string  $extension  File extension
     * @return string Font Awesome icon class
     */
    public static function getFileIcon(string $extension): string
    {
        $extension = strtolower($extension);

        $iconMap = [
            // Images
            'jpg' => 'fa-file-image',
            'jpeg' => 'fa-file-image',
            'png' => 'fa-file-image',
            'gif' => 'fa-file-image',
            'svg' => 'fa-file-image',
            'webp' => 'fa-file-image',

            // Documents
            'pdf' => 'fa-file-pdf',
            'doc' => 'fa-file-word',
            'docx' => 'fa-file-word',
            'txt' => 'fa-file-alt',
            'rtf' => 'fa-file-alt',

            // Spreadsheets
            'xls' => 'fa-file-excel',
            'xlsx' => 'fa-file-excel',
            'csv' => 'fa-file-excel',

            // Presentations
            'ppt' => 'fa-file-powerpoint',
            'pptx' => 'fa-file-powerpoint',

            // Audio
            'mp3' => 'fa-file-audio',
            'wav' => 'fa-file-audio',
            'ogg' => 'fa-file-audio',
            'flac' => 'fa-file-audio',

            // Video
            'mp4' => 'fa-file-video',
            'avi' => 'fa-file-video',
            'mov' => 'fa-file-video',
            'wmv' => 'fa-file-video',
            'flv' => 'fa-file-video',
            'webm' => 'fa-file-video',

            // Archives
            'zip' => 'fa-file-archive',
            'rar' => 'fa-file-archive',
            '7z' => 'fa-file-archive',
            'tar' => 'fa-file-archive',
            'gz' => 'fa-file-archive',

            // Code
            'php' => 'fa-file-code',
            'js' => 'fa-file-code',
            'html' => 'fa-file-code',
            'css' => 'fa-file-code',
            'json' => 'fa-file-code',
            'xml' => 'fa-file-code',
        ];

        return $iconMap[$extension] ?? 'fa-file';
    }

    /**
     * Check if file is an image
     *
     * @param  string  $extension  File extension
     */
    public static function isImage(string $extension): bool
    {
        $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'svg', 'webp', 'bmp'];

        return in_array(strtolower($extension), $imageExtensions);
    }

    /**
     * Check if file is a video
     *
     * @param  string  $extension  File extension
     */
    public static function isVideo(string $extension): bool
    {
        $videoExtensions = ['mp4', 'avi', 'mov', 'wmv', 'flv', 'webm', 'mkv'];

        return in_array(strtolower($extension), $videoExtensions);
    }

    /**
     * Check if file is an audio file
     *
     * @param  string  $extension  File extension
     */
    public static function isAudio(string $extension): bool
    {
        $audioExtensions = ['mp3', 'wav', 'ogg', 'flac', 'aac', 'm4a'];

        return in_array(strtolower($extension), $audioExtensions);
    }

    /**
     * Check if file is a document
     *
     * @param  string  $extension  File extension
     */
    public static function isDocument(string $extension): bool
    {
        $documentExtensions = ['pdf', 'doc', 'docx', 'txt', 'rtf', 'odt'];

        return in_array(strtolower($extension), $documentExtensions);
    }
}
