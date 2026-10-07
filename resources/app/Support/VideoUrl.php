<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Classifies an external video URL so it can actually be displayed, rather
 * than only being rendered as a bare text link.
 */
class VideoUrl
{
    public const PROVIDER_YOUTUBE = 'youtube';

    public const PROVIDER_VIMEO = 'vimeo';

    public const PROVIDER_FILE = 'file';

    public const PROVIDER_LINK = 'link';

    /**
     * File extensions a browser can play directly with a <video> element.
     */
    protected const VIDEO_EXTENSIONS = ['mp4', 'webm', 'ogv', 'ogg', 'mov', 'm4v'];

    /**
     * Extract the YouTube video id from any of the common URL shapes.
     */
    public static function youtubeId(?string $url): ?string
    {
        if (blank($url)) {
            return null;
        }

        $pattern = '/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([\w-]{11})/i';

        return preg_match($pattern, $url, $matches) ? $matches[1] : null;
    }

    /**
     * Extract the Vimeo video id.
     */
    public static function vimeoId(?string $url): ?string
    {
        if (blank($url)) {
            return null;
        }

        $pattern = '/vimeo\.com\/(?:video\/)?(\d+)/i';

        return preg_match($pattern, $url, $matches) ? $matches[1] : null;
    }

    /**
     * Does the URL point straight at a playable video file?
     */
    public static function isDirectFile(?string $url): bool
    {
        if (blank($url)) {
            return false;
        }

        $path = parse_url($url, PHP_URL_PATH);

        if (! is_string($path)) {
            return false;
        }

        // Ignore anything that has no extension (a watch page, not a file).
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        return in_array($extension, self::VIDEO_EXTENSIONS, true);
    }

    /**
     * Which provider this URL belongs to, or 'link' when it cannot be embedded.
     */
    public static function provider(?string $url): ?string
    {
        if (blank($url)) {
            return null;
        }

        if (self::youtubeId($url)) {
            return self::PROVIDER_YOUTUBE;
        }

        if (self::vimeoId($url)) {
            return self::PROVIDER_VIMEO;
        }

        if (self::isDirectFile($url)) {
            return self::PROVIDER_FILE;
        }

        return self::PROVIDER_LINK;
    }

    /**
     * A privacy-friendly embed URL, or the raw URL for direct files.
     * Returns null when the URL cannot be embedded at all.
     */
    public static function embedUrl(?string $url): ?string
    {
        return match (self::provider($url)) {
            self::PROVIDER_YOUTUBE => 'https://www.youtube-nocookie.com/embed/'.self::youtubeId($url),
            self::PROVIDER_VIMEO => 'https://player.vimeo.com/video/'.self::vimeoId($url),
            self::PROVIDER_FILE => $url,
            default => null,
        };
    }

    /**
     * Best available poster image, falling back to YouTube's own thumbnail.
     */
    public static function thumbnail(?string $url, ?string $fallback = null): ?string
    {
        if (filled($fallback)) {
            return $fallback;
        }

        if ($id = self::youtubeId($url)) {
            return "https://img.youtube.com/vi/{$id}/hqdefault.jpg";
        }

        return null;
    }

    /**
     * Human-readable label for the source, e.g. "YouTube".
     */
    public static function label(?string $url): string
    {
        return match (self::provider($url)) {
            self::PROVIDER_YOUTUBE => 'YouTube',
            self::PROVIDER_VIMEO => 'Vimeo',
            self::PROVIDER_FILE => 'Video file',
            default => 'External link',
        };
    }

    /**
     * Shortened URL for display.
     */
    public static function display(?string $url, int $limit = 60): string
    {
        return (string) Str::limit((string) $url, $limit);
    }
}