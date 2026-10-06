{{-- Renders an external video URL inline where possible, otherwise as a link card. --}}
@if(blank($url))
    <div class="ext-video-empty">
        <i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i>
        <span>No external video URL has been set for this {{ strtolower($title) }}.</span>
    </div>
@elseif($canEmbed())
    <div class="ext-video">
        @if($isYoutube())
            <iframe
                src="{{ $embedUrl() }}"
                title="{{ $title }}"
                class="ext-video-frame"
                loading="lazy"
                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                referrerpolicy="strict-origin-when-cross-origin"
                allowfullscreen
            ></iframe>
        @elseif($isVimeo())
            <iframe
                src="{{ $embedUrl() }}"
                title="{{ $title }}"
                class="ext-video-frame"
                loading="lazy"
                allow="autoplay; fullscreen; picture-in-picture; clipboard-write; encrypted-media"
                referrerpolicy="strict-origin-when-cross-origin"
                allowfullscreen
            ></iframe>
        @elseif($isFile())
            <video
                class="ext-video-frame"
                controls
                preload="metadata"
                playsinline
                @if($thumbnailUrl())
                    poster="{{ $thumbnailUrl() }}"
                @endif
            >
                <source src="{{ $embedUrl() }}">
                Your browser does not support embedded video.
                <a href="{{ $url }}" target="_blank" rel="noopener">Download the video</a>.
            </video>
        @endif
    </div>

    @if($showLink)
        <p class="ext-video-meta">
            <i class="fa-solid fa-link" aria-hidden="true"></i>
            <a href="{{ $url }}" target="_blank" rel="noopener">{{ \Illuminate\Support\Str::limit($url, 70) }}</a>
            <span class="ext-video-badge">{{ $label() }}</span>
        </p>
    @endif
@else
    {{-- Not a recognised video source, so it cannot be embedded. --}}
    <div class="ext-video ext-video-unembeddable">
        <div class="ext-video-fallback">
            @if($thumbnailUrl())
                <img src="{{ $thumbnailUrl() }}" alt="" loading="lazy" class="ext-video-poster">
            @else
                <div class="ext-video-poster ext-video-poster-blank" aria-hidden="true">
                    <i class="fa-solid fa-link"></i>
                </div>
            @endif
            <div class="ext-video-fallback-body">
                <p class="ext-video-fallback-note">
                    <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
                    This link can't be embedded in the page. Open it in a new tab to view the resource.
                </p>
                <a href="{{ $url }}" target="_blank" rel="noopener" class="btn btn-secondary ext-video-open">
                    <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i> Open video
                </a>
            </div>
        </div>
    </div>

    @if($showLink)
        <p class="ext-video-meta">
            <i class="fa-solid fa-link" aria-hidden="true"></i>
            <a href="{{ $url }}" target="_blank" rel="noopener">{{ \Illuminate\Support\Str::limit($url, 70) }}</a>
            <span class="ext-video-badge">{{ $label() }}</span>
        </p>
    @endif
@endif