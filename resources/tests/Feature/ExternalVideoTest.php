<?php

namespace Tests\Feature;

use App\Models\Module;
use App\Support\VideoUrl;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExternalVideoTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{0: string, 1: string, 2: ?string}>
     */
    public static function providerCases(): array
    {
        return [
            // ── YouTube: every URL shape a user might paste ──
            'youtube watch' => ['https://www.youtube.com/watch?v=dQw4w9WgXcQ', 'youtube', 'https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ'],
            'youtube short link' => ['https://youtu.be/dQw4w9WgXcQ', 'youtube', 'https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ'],
            'youtube embed' => ['https://www.youtube.com/embed/dQw4w9WgXcQ', 'youtube', 'https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ'],
            'youtube with extra params' => ['https://www.youtube.com/watch?list=PL123&v=dQw4w9WgXcQ&t=90', 'youtube', 'https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ'],
            'youtube mobile' => ['https://m.youtube.com/watch?v=dQw4w9WgXcQ', 'youtube', 'https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ'],

            // ── Vimeo ──
            'vimeo standard' => ['https://vimeo.com/123456789', 'vimeo', 'https://player.vimeo.com/video/123456789'],
            'vimeo /video/' => ['https://vimeo.com/video/123456789', 'vimeo', 'https://player.vimeo.com/video/123456789'],
            'vimeo player' => ['https://player.vimeo.com/video/123456789', 'vimeo', 'https://player.vimeo.com/video/123456789'],

            // ── Direct files ──
            'mp4' => ['https://cdn.example.com/videos/intro.mp4', 'file', 'https://cdn.example.com/videos/intro.mp4'],
            'webm' => ['https://cdn.example.com/videos/intro.webm', 'file', 'https://cdn.example.com/videos/intro.webm'],
            'mp4 with query string' => ['https://cdn.example.com/videos/intro.mp4?token=abc', 'file', 'https://cdn.example.com/videos/intro.mp4?token=abc'],
            'uppercase extension' => ['https://cdn.example.com/videos/INTRO.MP4', 'file', 'https://cdn.example.com/videos/INTRO.MP4'],

            // ── Not embeddable ──
            'plain webpage' => ['https://example.com/some-article', 'link', null],
            'drive folder' => ['https://drive.google.com/drive/folders/xyz', 'link', null],
            'unknown platform' => ['https://some-vpn.example/watch/12345', 'link', null],
        ];
    }

    /**
     * @dataProvider providerCases
     */
    public function test_url_is_classified_and_embedded_correctly(string $url, string $provider, ?string $embed): void
    {
        $this->assertSame($provider, VideoUrl::provider($url));
        $this->assertSame($embed, VideoUrl::embedUrl($url));
    }

    public function test_blank_url_has_no_provider(): void
    {
        $this->assertNull(VideoUrl::provider(null));
        $this->assertNull(VideoUrl::provider(''));
        $this->assertNull(VideoUrl::embedUrl(null));
    }

    public function test_youtube_thumbnail_is_derived_when_none_supplied(): void
    {
        $this->assertSame(
            'https://img.youtube.com/vi/dQw4w9WgXcQ/hqdefault.jpg',
            VideoUrl::thumbnail('https://www.youtube.com/watch?v=dQw4w9WgXcQ')
        );
    }

    public function test_supplied_thumbnail_wins_over_derived_one(): void
    {
        $this->assertSame(
            'https://cdn.example.com/custom.jpg',
            VideoUrl::thumbnail('https://youtu.be/dQw4w9WgXcQ', 'https://cdn.example.com/custom.jpg')
        );
    }

    public function test_module_reports_provider_and_embed_url(): void
    {
        $vimeo = new Module(['external_video_url' => 'https://vimeo.com/123456789']);

        $this->assertSame('vimeo', $vimeo->getVideoProvider());
        $this->assertSame('https://player.vimeo.com/video/123456789', $vimeo->getEmbeddedVideoUrl());
        $this->assertTrue($vimeo->canEmbedExternalVideo());
        $this->assertFalse($vimeo->isYouTubeVideo());

        $link = new Module(['external_video_url' => 'https://example.com/article']);
        $this->assertSame('link', $link->getVideoProvider());
        $this->assertNull($link->getEmbeddedVideoUrl());
        $this->assertFalse($link->canEmbedExternalVideo());
    }

    /**
     * @dataProvider providerCases
     */
    public function test_component_renders_an_iframe_for_embeddable_urls(string $url, string $provider, ?string $embed): void
    {
        $html = $this->blade('<x-external-video :url="$url" title="Video" />', ['url' => $url]);

        if ($embed === null) {
            // Not embeddable: a link card, never an inline player.
            $this->assertStringNotContainsString('<iframe', $html, "Expected no iframe for {$url}");
            $this->assertStringNotContainsString('<video', $html, "Expected no video element for {$url}");
            $this->assertStringContainsString('Open video', $html);
            return;
        }

        // Direct files play through a <video> element, not an iframe.
        $element = $provider === 'file' ? '<video' : '<iframe';

        $this->assertStringContainsString($element, $html, "Expected {$element} for {$url}");
        $this->assertStringContainsString($embed, $html);
        $this->assertStringNotContainsString('can\'t be embedded', $html);
    }

    public function test_direct_video_file_uses_a_video_element_with_source(): void
    {
        $html = $this->blade('<x-external-video :url="$url" />', ['url' => 'https://cdn.example.com/intro.mp4']);

        $this->assertStringContainsString('<video', $html);
        $this->assertStringContainsString('controls', $html);
        $this->assertStringContainsString('<source src="https://cdn.example.com/intro.mp4">', $html);
    }

    public function test_missing_url_shows_a_helpful_message(): void
    {
        $html = $this->blade('<x-external-video />');

        $this->assertStringContainsString('No external video URL', $html);
        $this->assertStringNotContainsString('<iframe', $html);
    }

    public function test_thumbnail_is_used_as_video_poster(): void
    {
        $html = $this->blade('<x-external-video :url="$url" :thumbnail="$thumb" />', [
            'url' => 'https://cdn.example.com/intro.mp4',
            'thumb' => 'https://cdn.example.com/poster.jpg',
        ]);

        $this->assertStringContainsString('poster="https://cdn.example.com/poster.jpg"', $html);
    }
}