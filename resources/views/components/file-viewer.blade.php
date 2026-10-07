@props([
    'file',
    'label' => null,
])

@php
    // $file is a MediaFile. Decide up front how this format can be shown,
    // because "view" is not one code path: a browser renders images, PDFs and
    // text natively, plays media natively, but cannot lay out an Office file.
    $ext = strtolower((string) ($file->extension ?? ''));
    $displayName = $file->original_name ?: $file->file_name;

    $images = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg', 'ico', 'avif'];
    $pdfs = ['pdf'];
    $texts = ['txt', 'md', 'csv', 'json', 'log', 'xml', 'yml', 'yaml', 'sql', 'ini'];
    $videos = ['mp4', 'webm', 'ogg', 'ogv', 'mov', 'm4v', 'mkv'];
    $audios = ['mp3', 'wav', 'ogg', 'oga', 'm4a', 'aac', 'flac'];
    $office = ['doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'odt', 'ods', 'odp'];

    $kind = 'unsupported';

    if (in_array($ext, $images, true)) {
        $kind = 'image';
    } elseif (in_array($ext, $pdfs, true)) {
        $kind = 'pdf';
    } elseif (in_array($ext, $texts, true)) {
        $kind = 'text';
    } elseif (in_array($ext, $videos, true)) {
        $kind = 'video';
    } elseif (in_array($ext, $audios, true)) {
        $kind = 'audio';
    } elseif (in_array($ext, $office, true)) {
        $kind = 'office';
    }

    $serveUrl = route('files.serve', ['mediaFile' => $file->id]);
    $downloadUrl = route('files.download', ['mediaFile' => $file->id]);
@endphp

{{-- Trigger: a plain button that opens the shared modal below. --}}
<button type="button"
        class="btn btn-icon file-view-trigger"
        data-file-viewer-open
        data-viewer="{{ $id = 'fv-' . ($file->id ?? \Illuminate\Support\Str::random(6)) }}"
        title="View {{ $displayName }}"
        aria-label="View {{ $displayName }}">
    <i class="fa-solid fa-eye" aria-hidden="true"></i>
</button>

{{-- Text is fetched on demand so a large file never slows the page down. --}}
@if ($kind === 'text')
    <script type="application/json" id="{{ $id }}-text-source">@json(['url' => $serveUrl])</script>
@endif

<div id="{{ $id }}" class="file-viewer" hidden>
    <div class="file-viewer-backdrop" data-file-viewer-close></div>

    <div class="file-viewer-dialog" role="dialog" aria-modal="true" aria-label="Preview of {{ $displayName }}">
        <header class="file-viewer-head">
            <div class="file-viewer-title">
                <i class="fa-solid fa-file-lines" aria-hidden="true"></i>
                <span>{{ $displayName }}</span>
            </div>

            <div class="file-viewer-actions">
                <a href="{{ $downloadUrl }}" class="btn btn-sm">
                    <i class="fa-solid fa-download" aria-hidden="true"></i> Download
                </a>
                <button type="button" class="btn btn-icon" data-file-viewer-close title="Close" aria-label="Close">
                    <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                </button>
            </div>
        </header>

        <div class="file-viewer-body">
            @if ($kind === 'image')
                <img src="{{ $serveUrl }}" alt="{{ $displayName }}" class="file-viewer-image">

            @elseif ($kind === 'pdf')
                <iframe src="{{ $serveUrl }}" title="{{ $displayName }}" class="file-viewer-frame"></iframe>

            @elseif ($kind === 'video')
                <video src="{{ $serveUrl }}" controls preload="metadata" class="file-viewer-media">
                    Your browser cannot play this video.
                </video>

            @elseif ($kind === 'audio')
                <audio src="{{ $serveUrl }}" controls preload="metadata" class="file-viewer-audio">
                    Your browser cannot play this audio.
                </audio>

            @elseif ($kind === 'text')
                <pre id="{{ $id }}-text" class="file-viewer-text">Loading…</pre>

            @elseif ($kind === 'office')
                {{-- No browser lays out .docx/.xlsx natively. Saying so plainly beats
                     an empty frame the instructor assumes is a broken viewer. --}}
                <div class="file-viewer-unsupported">
                    <i class="fa-solid fa-file-word" aria-hidden="true"></i>
                    <p><strong>{{ strtoupper($ext) }} files cannot be previewed in the browser.</strong></p>
                    <p>Open it in Word, Excel or PowerPoint, or download a copy.</p>
                    <a href="{{ $downloadUrl }}" class="btn btn-primary btn-sm">
                        <i class="fa-solid fa-download" aria-hidden="true"></i> Download to open
                    </a>
                </div>

            @else
                <div class="file-viewer-unsupported">
                    <i class="fa-solid fa-file-circle-question" aria-hidden="true"></i>
                    <p><strong>This file type has no preview.</strong></p>
                    <p>Download it to open in an application on your device.</p>
                    <a href="{{ $downloadUrl }}" class="btn btn-primary btn-sm">
                        <i class="fa-solid fa-download" aria-hidden="true"></i> Download
                    </a>
                </div>
            @endif
        </div>
    </div>
</div>

@once
    @push('styles')
        <style>
            .file-viewer{position:fixed;inset:0;z-index:9999;display:flex;align-items:center;justify-content:center;padding:24px;}
            .file-viewer[hidden]{display:none;}
            .file-viewer-backdrop{position:absolute;inset:0;background:rgba(2,6,23,.82);backdrop-filter:blur(2px);}
            .file-viewer-dialog{position:relative;display:flex;flex-direction:column;width:min(1000px,100%);max-height:90vh;background:#0b1220;color:#e8eeff;border:1px solid #1f2c47;border-radius:14px;box-shadow:0 24px 70px rgba(0,0,0,.55);overflow:hidden;}
            .file-viewer-head{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:13px 16px;border-bottom:1px solid #1f2c47;flex-shrink:0;}
            .file-viewer-title{display:flex;align-items:center;gap:9px;font-size:.86rem;font-weight:600;min-width:0;}
            .file-viewer-title span{overflow:hidden;text-overflow:ellipsis;white-space:nowrap;}
            .file-viewer-actions{display:flex;align-items:center;gap:8px;flex-shrink:0;}
            .file-viewer-body{flex:1;min-height:280px;display:flex;align-items:center;justify-content:center;overflow:auto;padding:14px;background:#070c17;}
            .file-viewer-image{max-width:100%;max-height:calc(90vh - 90px);object-fit:contain;border-radius:8px;}
            .file-viewer-frame{width:100%;height:calc(90vh - 110px);min-height:420px;border:0;border-radius:8px;background:#fff;}
            .file-viewer-media{width:100%;max-height:calc(90vh - 140px);}
            .file-viewer-audio{width:100%;}
            .file-viewer-text{width:100%;max-height:calc(90vh - 110px);overflow:auto;margin:0;padding:14px;background:#0f172a;border:1px solid #1f2c47;border-radius:8px;font-size:.8rem;line-height:1.55;white-space:pre-wrap;word-break:break-word;}
            .file-viewer-unsupported{text-align:center;color:#9fb0cd;max-width:440px;}
            .file-viewer-unsupported i{font-size:2.4rem;margin-bottom:12px;display:block;color:#5b7099;}
            .file-viewer-unsupported p{margin:.35rem 0;font-size:.85rem;}
        </style>
    @endpush

    @push('scripts')
        <script>
            (function () {
                // One delegated listener for every viewer on the page, so a list
                // of a hundred attachments does not attach a hundred handlers.
                let lastTrigger = null;

                function closeAll() {
                    document.querySelectorAll('.file-viewer:not([hidden])').forEach(function (v) {
                        v.hidden = true;
                    });
                    if (lastTrigger) { lastTrigger.focus(); lastTrigger = null; }
                }

                document.addEventListener('click', function (e) {
                    const open = e.target.closest('[data-file-viewer-open]');

                    if (open) {
                        lastTrigger = open;

                        const viewer = document.getElementById(open.dataset.viewer);
                        if (!viewer) return;

                        viewer.hidden = false;

                        // Only fetch text when it is actually opened.
                        const textEl = viewer.querySelector('.file-viewer-text');
                        const source = document.getElementById(viewer.id + '-text-source');

                        if (textEl && source && textEl.dataset.loaded !== '1') {
                            try {
                                const cfg = JSON.parse(source.textContent);
                                fetch(cfg.url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                                    .then(function (r) {
                                        if (!r.ok) throw new Error('HTTP ' + r.status);
                                        return r.text();
                                    })
                                    .then(function (body) {
                                        textEl.textContent = body.slice(0, 200000);
                                        textEl.dataset.loaded = '1';
                                    })
                                    .catch(function (err) {
                                        textEl.textContent = 'Could not load the file: ' + err.message;
                                    });
                            } catch (err) {
                                textEl.textContent = 'Could not load the file.';
                            }
                        }

                        return;
                    }

                    if (e.target.closest('[data-file-viewer-close]')) {
                        closeAll();
                    }
                });

                document.addEventListener('keydown', function (e) {
                    if (e.key === 'Escape') closeAll();
                });
            })();
        </script>
    @endpush
@endonce