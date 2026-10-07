<?php

namespace App\View\Components;

use App\Support\VideoUrl;
use Illuminate\View\Component;

/**
 * Renders an external video URL in the best form available: an inline embed for
 * YouTube / Vimeo / direct video files, or a link card when it cannot be embedded.
 */
class ExternalVideo extends Component
{
    public function __construct(
        public ?string $url = null,
        public ?string $thumbnail = null,
        public string $title = 'External Video',
        public bool $showLink = true,
    ) {}

    public function provider(): ?string
    {
        return VideoUrl::provider($this->url);
    }

    public function embedUrl(): ?string
    {
        return VideoUrl::embedUrl($this->url);
    }

    public function thumbnailUrl(): ?string
    {
        return VideoUrl::thumbnail($this->url, $this->thumbnail);
    }

    public function label(): string
    {
        return VideoUrl::label($this->url);
    }

    public function canEmbed(): bool
    {
        return filled($this->embedUrl());
    }

    public function isYoutube(): bool
    {
        return $this->provider() === VideoUrl::PROVIDER_YOUTUBE;
    }

    public function isVimeo(): bool
    {
        return $this->provider() === VideoUrl::PROVIDER_VIMEO;
    }

    public function isFile(): bool
    {
        return $this->provider() === VideoUrl::PROVIDER_FILE;
    }

    public function render()
    {
        return view('components.external-video');
    }
}