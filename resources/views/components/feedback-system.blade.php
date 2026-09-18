@props([
    'gradable' => null,
    'gradableType' => null,
    'studentId' => null,
    'feedbacks' => null,
    'canAddFeedback' => true,
    'showRatings' => true,
    'allowAnonymous' => false,
])

<div class="feedback-system" style="margin-top: 24px;">
    <!-- Feedback Summary -->
    @if($showRatings && $feedbacks && $feedbacks->whereNotNull('rating')->isNotEmpty())
        <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 20px; border-radius: 12px; color: white; margin-bottom: 20px;">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
                <div>
                    <div style="font-size: 2rem; font-weight: 700;">
                        {{ number_format($feedbacks->whereNotNull('rating')->avg('rating'), 1) }}
                    </div>
                    <div style="font-size: 0.9rem; opacity: 0.9;">
                        {{ $feedbacks->whereNotNull('rating')->count() }} ratings
                    </div>
                </div>
                <div style="flex: 1; max-width: 300px;">
                    @for($i = 1; $i <= 5; $i++)
                        <div style="display: flex; align-items: center; margin-bottom: 4px;">
                            <span style="width: 60px; font-size: 0.85rem;">{{ $i }} star</span>
                            <div style="flex: 1; height: 8px; background: rgba(255,255,255,0.3); border-radius: 4px; margin: 0 8px;">
                                <div style="height: 100%; background: white; border-radius: 4px; width: {{ 
                                    $feedbacks->whereNotNull('rating')->count() > 0 
                                        ? ($feedbacks->whereNotNull('rating')->where('rating', '>=', $i)->count() / $feedbacks->whereNotNull('rating')->count()) * 100 
                                        : 0 
                                }}%;"></div>
                            </div>
                            <span style="width: 40px; font-size: 0.85rem; text-align: right;">
                                {{ $feedbacks->whereNotNull('rating')->count() > 0 
                                    ? round(($feedbacks->whereNotNull('rating')->where('rating', '>=', $i)->count() / $feedbacks->whereNotNull('rating')->count()) * 100) 
                                    : 0 }}%
                            </span>
                        </div>
                    @endfor
                </div>
            </div>
        </div>
    @endif

    <!-- Add Feedback Form -->
    @if($canAddFeedback && auth()->check())
        <div class="form-card" style="margin-bottom: 20px;">
            <h3><i class="fa-solid fa-comment-dots"></i> Add Your Feedback</h3>
            
            <form id="feedbackForm" onsubmit="submitFeedback(event)" style="margin-top: 16px;">
                @csrf
                <input type="hidden" name="gradable_type" value="{{ $gradableType }}">
                <input type="hidden" name="gradable_id" value="{{ $gradable?->id }}">
                <input type="hidden" name="student_id" value="{{ $studentId ?? auth()->id() }}">
                
                @if($showRatings)
                    <div style="margin-bottom: 16px;">
                        <label style="display: block; font-weight: 600; margin-bottom: 8px;">Rating</label>
                        <div id="ratingStars" style="display: flex; gap: 4px;">
                            @for($i = 1; $i <= 5; $i++)
                                <button type="button" onclick="setRating({{ $i }})" 
                                        class="star-btn" data-rating="{{ $i }}"
                                        style="font-size: 2rem; background: none; border: none; cursor: pointer; color: #cbd5e1; transition: color 0.2s;">
                                    <i class="fa-solid fa-star"></i>
                                </button>
                            @endfor
                        </div>
                        <input type="hidden" id="ratingInput" name="rating" value="0">
                    </div>
                @endif
                
                <div style="margin-bottom: 16px;">
                    <label style="display: block; font-weight: 600; margin-bottom: 8px;">Feedback Type</label>
                    <select name="feedback_type" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px;">
                        <option value="general">General Feedback</option>
                        <option value="constructive">Constructive Criticism</option>
                        <option value="praise">Praise & Recognition</option>
                        <option value="suggestion">Suggestion</option>
                        <option value="question">Question</option>
                    </select>
                </div>
                
                <div style="margin-bottom: 16px;">
                    <label style="display: block; font-weight: 600; margin-bottom: 8px;">Your Feedback</label>
                    <textarea name="body" rows="4" required
                              style="width: 100%; padding: 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-family: inherit; resize: vertical;"
                              placeholder="Share your thoughts and feedback..."></textarea>
                </div>
                
                <div style="margin-bottom: 16px;">
                    <label style="display: block; font-weight: 600; margin-bottom: 8px;">Tags (optional)</label>
                    <input type="text" name="tags" 
                           style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px;"
                           placeholder="e.g., helpful, clear, needs_improvement">
                    <div style="font-size: 0.85rem; color: #64748b; margin-top: 4px;">Separate tags with commas</div>
                </div>
                
                @if($allowAnonymous)
                    <div style="margin-bottom: 16px;">
                        <label style="display: flex; align-items: center; gap: 8px;">
                            <input type="checkbox" name="is_anonymous" value="1" style="width: 18px; height: 18px;">
                            <span>Submit anonymously</span>
                        </label>
                    </div>
                @endif
                
                <div style="display: flex; gap: 8px;">
                    <button type="submit" class="btn-add" style="flex: 1; padding: 12px 24px; border-radius: 8px; cursor: pointer;">
                        <i class="fa-solid fa-paper-plane"></i> Submit Feedback
                    </button>
                    <button type="button" onclick="attachFile()" class="btn-modal-cancel" style="padding: 12px 20px; border-radius: 8px; cursor: pointer;">
                        <i class="fa-solid fa-paperclip"></i> Attach File
                    </button>
                </div>
            </form>
        </div>
    @endif

    <!-- Feedback List -->
    <div class="form-card">
        <h3><i class="fa-solid fa-comments"></i> Feedback & Comments ({{ $feedbacks?->count() ?? 0 }})</h3>
        
        @if($feedbacks && $feedbacks->isNotEmpty())
            <div style="margin-top: 16px;">
                @foreach($feedbacks as $feedback)
                    <div class="feedback-item" style="padding: 16px; border: 1px solid #e2e8f0; border-radius: 8px; margin-bottom: 12px; background: white;">
                        <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 12px;">
                            <div style="display: flex; align-items: center; gap: 12px;">
                                <div style="width: 40px; height: 40px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: 600;">
                                    {{ $feedback->author?->name ? substr($feedback->author->name, 0, 1) : 'A' }}
                                </div>
                                <div>
                                    <div style="font-weight: 600; color: #1e293b;">
                                        {{ $feedback->author?->name ?? 'Anonymous' }}
                                    </div>
                                    <div style="font-size: 0.85rem; color: #64748b;">
                                        {{ $feedback->created_at->format('M d, Y g:i A') }}
                                        @if($feedback->feedback_type)
                                            <span style="background: #f1f5f9; padding: 2px 8px; border-radius: 12px; font-size: 0.75rem; margin-left: 8px;">
                                                {{ ucfirst(str_replace('_', ' ', $feedback->feedback_type)) }}
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            
                            @if($showRatings && $feedback->rating)
                                <div style="display: flex; gap: 2px;">
                                    @for($i = 1; $i <= 5; $i++)
                                        <i class="fa-solid fa-star" style="color: {{ $i <= $feedback->rating ? '#fbbf24' : '#cbd5e1' }}; font-size: 0.9rem;"></i>
                                    @endfor
                                </div>
                            @endif
                        </div>
                        
                        <div style="color: #475569; line-height: 1.6; margin-bottom: 12px;">
                            {{ $feedback->body }}
                        </div>
                        
                        @if($feedback->tags && is_array($feedback->tags) && count($feedback->tags) > 0)
                            <div style="display: flex; gap: 4px; flex-wrap: wrap; margin-bottom: 12px;">
                                @foreach($feedback->tags as $tag)
                                    <span style="background: #e0f2fe; color: #0369a1; padding: 4px 10px; border-radius: 12px; font-size: 0.75rem;">
                                        #{{ str_replace('_', ' ', $tag) }}
                                    </span>
                                @endforeach
                            </div>
                        @endif
                        
                        @if($feedback->attachment)
                            <div style="display: flex; align-items: center; gap: 8px; padding: 8px; background: #f8fafc; border-radius: 6px;">
                                <i class="fa-solid fa-paperclip" style="color: #64748b;"></i>
                                <a href="{{ route('files.download', $feedback->attachment) }}" 
                                   style="color: #3b82f6; text-decoration: none; font-size: 0.9rem;">
                                    {{ $feedback->attachment->original_name }}
                                </a>
                            </div>
                        @endif
                        
                        <div style="display: flex; gap: 8px; margin-top: 12px; padding-top: 12px; border-top: 1px solid #f1f5f9;">
                            @if(auth()->check() && (auth()->id() === $feedback->author_id || auth()->user()->hasPermission('feedback.manage')))
                                <button type="button" onclick="editFeedback({{ $feedback->id }})" class="btn-modal-cancel" style="padding: 6px 12px; border-radius: 6px; font-size: 0.85rem; cursor: pointer;">
                                    <i class="fa-solid fa-edit"></i> Edit
                                </button>
                                <button type="button" onclick="deleteFeedback({{ $feedback->id }})" class="btn-delete" style="padding: 6px 12px; border-radius: 6px; font-size: 0.85rem; cursor: pointer;">
                                    <i class="fa-solid fa-trash"></i> Delete
                                </button>
                            @endif
                            <button type="button" onclick="replyToFeedback({{ $feedback->id }})" class="btn-modal-cancel" style="padding: 6px 12px; border-radius: 6px; font-size: 0.85rem; cursor: pointer;">
                                <i class="fa-solid fa-reply"></i> Reply
                            </button>
                            
                            @if(!$feedback->read_at && auth()->id() === $feedback->student_id)
                                <button type="button" onclick="markAsRead({{ $feedback->id }})" class="btn-add" style="padding: 6px 12px; border-radius: 6px; font-size: 0.85rem; cursor: pointer;">
                                    <i class="fa-solid fa-check"></i> Mark as Read
                                </button>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div style="padding: 40px; text-align: center; color: #64748b; background: #f8fafc; border-radius: 8px;">
                <i class="fa-solid fa-comments" style="font-size: 3rem; margin-bottom: 16px;"></i>
                <div style="font-size: 1.1rem;">No feedback yet</div>
                <div style="font-size: 0.9rem; margin-top: 8px;">Be the first to share your thoughts!</div>
            </div>
        @endif
    </div>
</div>

<script>
    let currentRating = 0;

    function setRating(rating) {
        currentRating = rating;
        document.getElementById('ratingInput').value = rating;
        
        const stars = document.querySelectorAll('.star-btn');
        stars.forEach((star, index) => {
            if (index < rating) {
                star.style.color = '#fbbf24';
            } else {
                star.style.color = '#cbd5e1';
            }
        });
    }

    function submitFeedback(event) {
        event.preventDefault();
        
        const formData = new FormData(event.target);
        
        // Handle tags
        const tagsInput = formData.get('tags');
        if (tagsInput) {
            const tags = tagsInput.split(',').map(tag => tag.trim().toLowerCase().replace(/\s+/g, '_'));
            formData.set('tags', JSON.stringify(tags));
        }
        
        fetch('/feedback', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Feedback submitted successfully!');
                location.reload();
            } else {
                alert('Error submitting feedback: ' + data.message);
            }
        })
        .catch(error => {
            alert('Error submitting feedback: ' + error.message);
        });
    }

    function attachFile() {
        // Implement file attachment
        alert('File attachment feature - would open file picker');
    }

    function editFeedback(feedbackId) {
        // Implement edit functionality
        alert('Edit feedback ' + feedbackId);
    }

    function deleteFeedback(feedbackId) {
        if (!confirm('Are you sure you want to delete this feedback?')) return;
        
        fetch('/feedback/' + feedbackId, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Feedback deleted successfully');
                location.reload();
            } else {
                alert('Error deleting feedback: ' + data.message);
            }
        })
        .catch(error => {
            alert('Error deleting feedback: ' + error.message);
        });
    }

    function replyToFeedback(feedbackId) {
        // Implement reply functionality
        alert('Reply to feedback ' + feedbackId);
    }

    function markAsRead(feedbackId) {
        fetch('/feedback/' + feedbackId + '/mark-read', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            }
        })
        .catch(error => {
            console.error('Error marking as read:', error);
        });
    }
</script>