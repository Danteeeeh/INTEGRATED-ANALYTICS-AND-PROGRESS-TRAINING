@props([
    'mediaFile' => null,
    'autoplay' => false,
    'controls' => true,
    'loop' => false,
    'muted' => false,
    'showTranscript' => false,
    'allowDownload' => true,
])

<div class="multimedia-player" style="margin-top: 16px;">
    @if($mediaFile)
        @php
            $isVideo = \App\Helpers\FileHelper::isVideo($mediaFile->extension);
            $isAudio = \App\Helpers\FileHelper::isAudio($mediaFile->extension);
            $isImage = \App\Helpers\FileHelper::isImage($mediaFile->extension);
        @endphp
        
        @if($isVideo)
            <!-- Video Player -->
            <div class="video-container" style="position: relative; width: 100%; background: #000; border-radius: 12px; overflow: hidden;">
                <video id="videoPlayer_{{ $mediaFile->id }}" 
                       class="video-js vjs-default-skin"
                       controls="{{ $controls ? 'true' : 'false' }}"
                       {{ $autoplay ? 'autoplay' : '' }}
                       {{ $loop ? 'loop' : '' }}
                       {{ $muted ? 'muted' : '' }}
                       preload="metadata"
                       style="width: 100%; height: auto; max-height: 600px;"
                       poster="{{ $mediaFile->thumbnail_url ?? '' }}">
                    <source src="{{ route('files.serve', $mediaFile) }}" type="{{ $mediaFile->mime_type }}">
                    Your browser does not support the video tag.
                </video>
                
                <!-- Custom Controls Overlay -->
                <div class="video-controls-overlay" style="position: absolute; bottom: 0; left: 0; right: 0; background: linear-gradient(transparent, rgba(0,0,0,0.7)); padding: 20px; display: none;">
                    <div style="display: flex; justify-content: space-between; align-items: center; color: white;">
                        <div style="display: flex; gap: 16px; align-items: center;">
                            <button type="button" onclick="togglePlay({{ $mediaFile->id }})" class="control-btn" style="background: none; border: none; color: white; cursor: pointer; font-size: 1.5rem;">
                                <i class="fa-solid fa-play" id="playBtn_{{ $mediaFile->id }}"></i>
                            </button>
                            <button type="button" onclick="toggleMute({{ $mediaFile->id }})" class="control-btn" style="background: none; border: none; color: white; cursor: pointer; font-size: 1.2rem;">
                                <i class="fa-solid fa-volume-up" id="volumeBtn_{{ $mediaFile->id }}"></i>
                            </button>
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <span id="currentTime_{{ $mediaFile->id }}">0:00</span>
                                <input type="range" id="progressBar_{{ $mediaFile->id }}" min="0" max="100" value="0" 
                                       style="width: 200px; cursor: pointer;" onchange="seekVideo({{ $mediaFile->id }}, this.value)">
                                <span id="duration_{{ $mediaFile->id }}">0:00</span>
                            </div>
                        </div>
                        <div style="display: flex; gap: 16px; align-items: center;">
                            <button type="button" onclick="toggleFullscreen({{ $mediaFile->id }})" class="control-btn" style="background: none; border: none; color: white; cursor: pointer; font-size: 1.2rem;">
                                <i class="fa-solid fa-expand"></i>
                            </button>
                            <select onchange="changeSpeed({{ $mediaFile->id }}, this.value)" style="background: rgba(255,255,255,0.2); border: none; color: white; padding: 4px 8px; border-radius: 4px; cursor: pointer;">
                                <option value="0.5">0.5x</option>
                                <option value="0.75">0.75x</option>
                                <option value="1" selected>1x</option>
                                <option value="1.25">1.25x</option>
                                <option value="1.5">1.5x</option>
                                <option value="2">2x</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Video Info -->
            <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 12px; padding: 12px; background: #f8fafc; border-radius: 8px;">
                <div>
                    <div style="font-weight: 600; color: #1e293b;">{{ $mediaFile->original_name }}</div>
                    <div style="font-size: 0.85rem; color: #64748b;">
                        {{ \App\Helpers\FileHelper::formatFileSize($mediaFile->size) }} • {{ $mediaFile->extension }}
                    </div>
                </div>
                @if($allowDownload)
                    <a href="{{ route('files.download', $mediaFile) }}" class="btn-modal-cancel" style="padding: 8px 16px; border-radius: 6px; text-decoration: none; font-size: 0.85rem;">
                        <i class="fa-solid fa-download"></i> Download
                    </a>
                @endif
            </div>
            
        @elseif($isAudio)
            <!-- Audio Player -->
            <div class="audio-container" style="padding: 20px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 12px; color: white;">
                <div style="display: flex; align-items: center; gap: 20px; margin-bottom: 16px;">
                    <div style="width: 80px; height: 80px; background: rgba(255,255,255,0.2); border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                        <i class="fa-solid fa-music" style="font-size: 2rem;"></i>
                    </div>
                    <div style="flex: 1;">
                        <div style="font-weight: 600; font-size: 1.1rem; margin-bottom: 4px;">{{ $mediaFile->original_name }}</div>
                        <div style="font-size: 0.9rem; opacity: 0.9;">{{ \App\Helpers\FileHelper::formatFileSize($mediaFile->size) }}</div>
                    </div>
                </div>
                
                <audio id="audioPlayer_{{ $mediaFile->id }}" 
                       {{ $autoplay ? 'autoplay' : '' }}
                       {{ $loop ? 'loop' : '' }}
                       {{ $muted ? 'muted' : '' }}
                       style="width: 100%;">
                    <source src="{{ route('files.serve', $mediaFile) }}" type="{{ $mediaFile->mime_type }}">
                    Your browser does not support the audio tag.
                </audio>
                
                <!-- Custom Audio Controls -->
                <div style="margin-top: 16px;">
                    <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 12px;">
                        <button type="button" onclick="toggleAudioPlay({{ $mediaFile->id }})" class="control-btn" style="width: 50px; height: 50px; background: white; border: none; border-radius: 50%; cursor: pointer; color: #667eea; font-size: 1.2rem;">
                            <i class="fa-solid fa-play" id="audioPlayBtn_{{ $mediaFile->id }}"></i>
                        </button>
                        <div style="flex: 1;">
                            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">
                                <span id="audioCurrentTime_{{ $mediaFile->id }}">0:00</span>
                                <input type="range" id="audioProgressBar_{{ $mediaFile->id }}" min="0" max="100" value="0" 
                                       style="flex: 1; cursor: pointer;" onchange="seekAudio({{ $mediaFile->id }}, this.value)">
                                <span id="audioDuration_{{ $mediaFile->id }}">0:00</span>
                            </div>
                            <!-- Waveform Visualization -->
                            <div id="waveform_{{ $mediaFile->id }}" style="height: 40px; background: rgba(255,255,255,0.2); border-radius: 4px; display: flex; align-items: center; gap: 2px; padding: 0 8px;">
                                <!-- Waveform bars will be generated by JS -->
                            </div>
                        </div>
                    </div>
                    
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <div style="display: flex; gap: 12px; align-items: center;">
                            <button type="button" onclick="toggleAudioMute({{ $mediaFile->id }})" class="control-btn" style="background: none; border: none; color: white; cursor: pointer;">
                                <i class="fa-solid fa-volume-up" id="audioVolumeBtn_{{ $mediaFile->id }}"></i>
                            </button>
                            <input type="range" id="audioVolume_{{ $mediaFile->id }}" min="0" max="100" value="100" 
                                   style="width: 100px; cursor: pointer;" onchange="setAudioVolume({{ $mediaFile->id }}, this.value)">
                        </div>
                        <div style="display: flex; gap: 8px;">
                            <select onchange="changeAudioSpeed({{ $mediaFile->id }}, this.value)" style="background: rgba(255,255,255,0.2); border: none; color: white; padding: 4px 8px; border-radius: 4px; cursor: pointer;">
                                <option value="0.5">0.5x</option>
                                <option value="0.75">0.75x</option>
                                <option value="1" selected>1x</option>
                                <option value="1.25">1.25x</option>
                                <option value="1.5">1.5x</option>
                                <option value="2">2x</option>
                            </select>
                            @if($allowDownload)
                                <a href="{{ route('files.download', $mediaFile) }}" style="background: rgba(255,255,255,0.2); padding: 8px 12px; border-radius: 6px; text-decoration: none; color: white; font-size: 0.85rem;">
                                    <i class="fa-solid fa-download"></i>
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            
        @elseif($isImage)
            <!-- Image Viewer -->
            <div class="image-container" style="position: relative;">
                <img src="{{ route('files.serve', $mediaFile) }}" 
                     alt="{{ $mediaFile->original_name }}"
                     style="max-width: 100%; max-height: 600px; border-radius: 12px; cursor: zoom-in;"
                     onclick="openImageViewer('{{ route('files.serve', $mediaFile) }}')">
                
                <!-- Image Controls -->
                <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 12px; padding: 12px; background: #f8fafc; border-radius: 8px;">
                    <div>
                        <div style="font-weight: 600; color: #1e293b;">{{ $mediaFile->original_name }}</div>
                        <div style="font-size: 0.85rem; color: #64748b;">
                            {{ $mediaFile->width }}x{{ $mediaFile->height }} • {{ \App\Helpers\FileHelper::formatFileSize($mediaFile->size) }}
                        </div>
                    </div>
                    <div style="display: flex; gap: 8px;">
                        <button type="button" onclick="rotateImage(-90)" class="btn-modal-cancel" style="padding: 8px 12px; border-radius: 6px; cursor: pointer;">
                            <i class="fa-solid fa-rotate-left"></i>
                        </button>
                        <button type="button" onclick="rotateImage(90)" class="btn-modal-cancel" style="padding: 8px 12px; border-radius: 6px; cursor: pointer;">
                            <i class="fa-solid fa-rotate-right"></i>
                        </button>
                        @if($allowDownload)
                            <a href="{{ route('files.download', $mediaFile) }}" class="btn-modal-cancel" style="padding: 8px 12px; border-radius: 6px; text-decoration: none;">
                                <i class="fa-solid fa-download"></i>
                            </a>
                        @endif
                    </div>
                </div>
            </div>
            
        @else
            <!-- Default File Viewer -->
            <div class="file-container" style="padding: 40px; text-align: center; background: #f8fafc; border-radius: 12px; border: 2px dashed #cbd5e1;">
                <i class="{{ \App\Helpers\FileHelper::getFileIcon($mediaFile->extension) }}" style="font-size: 4rem; color: #64748b; margin-bottom: 16px;"></i>
                <div style="font-weight: 600; color: #1e293b; margin-bottom: 8px;">{{ $mediaFile->original_name }}</div>
                <div style="font-size: 0.9rem; color: #64748b; margin-bottom: 16px;">
                    {{ \App\Helpers\FileHelper::formatFileSize($mediaFile->size) }} • {{ strtoupper($mediaFile->extension) }}
                </div>
                @if($allowDownload)
                    <a href="{{ route('files.download', $mediaFile) }}" class="btn-add" style="display: inline-flex; align-items: center; padding: 12px 24px; border-radius: 8px; text-decoration: none;">
                        <i class="fa-solid fa-download"></i> Download File
                    </a>
                @endif
            </div>
        @endif
        
        <!-- Transcript Section -->
        @if($showTranscript && ($isVideo || $isAudio))
            <div class="transcript-section" style="margin-top: 24px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                    <h4 style="margin: 0;"><i class="fa-solid fa-closed-captioning"></i> Transcript</h4>
                    <button type="button" onclick="toggleTranscript()" class="btn-modal-cancel" style="padding: 6px 12px; border-radius: 6px; cursor: pointer;">
                        <i class="fa-solid fa-eye"></i> Show
                    </button>
                </div>
                <div id="transcriptContent" style="display: none; padding: 16px; background: #f8fafc; border-radius: 8px; max-height: 300px; overflow-y: auto;">
                    <div style="color: #64748b; font-style: italic;">Transcript not available for this media.</div>
                </div>
            </div>
        @endif
    @endif
</div>

<!-- Image Viewer Modal -->
<div id="imageViewerModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.9); z-index: 2000; align-items: center; justify-content: center;">
    <button type="button" onclick="closeImageViewer()" style="position: absolute; top: 20px; right: 20px; background: none; border: none; color: white; font-size: 2rem; cursor: pointer;">
        <i class="fa-solid fa-times"></i>
    </button>
    <img id="viewerImage" src="" alt="Full size image" style="max-width: 90%; max-height: 90%; object-fit: contain;">
</div>

<script>
    // Video Player Functions
    function togglePlay(mediaId) {
        const video = document.getElementById('videoPlayer_' + mediaId);
        const playBtn = document.getElementById('playBtn_' + mediaId);
        
        if (video.paused) {
            video.play();
            playBtn.classList.remove('fa-play');
            playBtn.classList.add('fa-pause');
        } else {
            video.pause();
            playBtn.classList.remove('fa-pause');
            playBtn.classList.add('fa-play');
        }
    }

    function toggleMute(mediaId) {
        const video = document.getElementById('videoPlayer_' + mediaId);
        const volumeBtn = document.getElementById('volumeBtn_' + mediaId);
        
        video.muted = !video.muted;
        volumeBtn.classList.toggle('fa-volume-up');
        volumeBtn.classList.toggle('fa-volume-mute');
    }

    function seekVideo(mediaId, value) {
        const video = document.getElementById('videoPlayer_' + mediaId);
        video.currentTime = (value / 100) * video.duration;
    }

    function toggleFullscreen(mediaId) {
        const video = document.getElementById('videoPlayer_' + mediaId);
        if (video.requestFullscreen) {
            video.requestFullscreen();
        } else if (video.webkitRequestFullscreen) {
            video.webkitRequestFullscreen();
        }
    }

    function changeSpeed(mediaId, speed) {
        const video = document.getElementById('videoPlayer_' + mediaId);
        video.playbackRate = parseFloat(speed);
    }

    // Audio Player Functions
    function toggleAudioPlay(mediaId) {
        const audio = document.getElementById('audioPlayer_' + mediaId);
        const playBtn = document.getElementById('audioPlayBtn_' + mediaId);
        
        if (audio.paused) {
            audio.play();
            playBtn.classList.remove('fa-play');
            playBtn.classList.add('fa-pause');
            generateWaveform(mediaId);
        } else {
            audio.pause();
            playBtn.classList.remove('fa-pause');
            playBtn.classList.add('fa-play');
        }
    }

    function toggleAudioMute(mediaId) {
        const audio = document.getElementById('audioPlayer_' + mediaId);
        const volumeBtn = document.getElementById('audioVolumeBtn_' + mediaId);
        
        audio.muted = !audio.muted;
        volumeBtn.classList.toggle('fa-volume-up');
        volumeBtn.classList.toggle('fa-volume-mute');
    }

    function setAudioVolume(mediaId, value) {
        const audio = document.getElementById('audioPlayer_' + mediaId);
        audio.volume = value / 100;
    }

    function seekAudio(mediaId, value) {
        const audio = document.getElementById('audioPlayer_' + mediaId);
        audio.currentTime = (value / 100) * audio.duration;
    }

    function changeAudioSpeed(mediaId, speed) {
        const audio = document.getElementById('audioPlayer_' + mediaId);
        audio.playbackRate = parseFloat(speed);
    }

    function generateWaveform(mediaId) {
        const waveform = document.getElementById('waveform_' + mediaId);
        waveform.innerHTML = '';
        
        for (let i = 0; i < 50; i++) {
            const bar = document.createElement('div');
            const height = Math.random() * 30 + 10;
            bar.style.cssText = `width: 2px; height: ${height}px; background: rgba(255,255,255,0.5); border-radius: 1px;`;
            waveform.appendChild(bar);
        }
    }

    // Image Viewer Functions
    function openImageViewer(src) {
        document.getElementById('viewerImage').src = src;
        document.getElementById('imageViewerModal').style.display = 'flex';
    }

    function closeImageViewer() {
        document.getElementById('imageViewerModal').style.display = 'none';
    }

    let currentRotation = 0;
    function rotateImage(degrees) {
        currentRotation += degrees;
        const img = document.querySelector('.image-container img');
        img.style.transform = `rotate(${currentRotation}deg)`;
    }

    // Transcript Functions
    function toggleTranscript() {
        const content = document.getElementById('transcriptContent');
        const btn = event.target;
        
        if (content.style.display === 'none') {
            content.style.display = 'block';
            btn.innerHTML = '<i class="fa-solid fa-eye-slash"></i> Hide';
        } else {
            content.style.display = 'none';
            btn.innerHTML = '<i class="fa-solid fa-eye"></i> Show';
        }
    }

    // Time formatting
    function formatTime(seconds) {
        const mins = Math.floor(seconds / 60);
        const secs = Math.floor(seconds % 60);
        return mins + ':' + (secs < 10 ? '0' : '') + secs;
    }

    // Initialize media players
    document.addEventListener('DOMContentLoaded', function() {
        // Set up video time updates
        @if($mediaFile && \App\Helpers\FileHelper::isVideo($mediaFile->extension))
            const video = document.getElementById('videoPlayer_{{ $mediaFile->id }}');
            if (video) {
                video.addEventListener('timeupdate', function() {
                    const progress = (video.currentTime / video.duration) * 100;
                    document.getElementById('progressBar_{{ $mediaFile->id }}').value = progress;
                    document.getElementById('currentTime_{{ $mediaFile->id }}').textContent = formatTime(video.currentTime);
                });
                
                video.addEventListener('loadedmetadata', function() {
                    document.getElementById('duration_{{ $mediaFile->id }}').textContent = formatTime(video.duration);
                });
            }
        @endif
        
        // Set up audio time updates
        @if($mediaFile && \App\Helpers\FileHelper::isAudio($mediaFile->extension))
            const audio = document.getElementById('audioPlayer_{{ $mediaFile->id }}');
            if (audio) {
                audio.addEventListener('timeupdate', function() {
                    const progress = (audio.currentTime / audio.duration) * 100;
                    document.getElementById('audioProgressBar_{{ $mediaFile->id }}').value = progress;
                    document.getElementById('audioCurrentTime_{{ $mediaFile->id }}').textContent = formatTime(audio.currentTime);
                });
                
                audio.addEventListener('loadedmetadata', function() {
                    document.getElementById('audioDuration_{{ $mediaFile->id }}').textContent = formatTime(audio.duration);
                });
            }
        @endif
    });
</script>