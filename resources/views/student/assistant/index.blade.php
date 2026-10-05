@extends('layouts.student')
@section('title', 'Study Assistant')
@php($activeNav='assistant')
@section('page-title-bar')
<div class="page-title-bar"><h2 class="page-title"><i class="fa-solid fa-robot"></i> Study Assistant</h2><div class="page-actions"><span style="font-size:0.85rem;color:#64748b">AI study support</span></div></div>
@endsection
@section('content')
<div class="crud-card">
    <div class="crud-header"><h3>Ask about your deadlines, progress &amp; priorities</h3></div>
    <div id="chatBox" class="chat-box">
        <div class="chat-bubble chat-bubble--bot">Hi! I'm your study assistant. Try asking: "What's due this week?", "How am I doing?", "What should I work on first?", or "How do I catch up?"</div>
    </div>
    <form id="chatForm" class="chat-form">
        @csrf
        <input type="text" id="message" name="message" maxlength="500" placeholder="Type your question..." class="chat-input" required>
        <button type="submit" id="chatSubmit" class="btn-add chat-submit"><i class="fa-solid fa-paper-plane"></i> Send</button>
    </form>
</div>
<script>
    (function () {
        const box = document.getElementById('chatBox');
        const form = document.getElementById('chatForm');
        const input = document.getElementById('message');
        const submitBtn = document.getElementById('chatSubmit');

        function csrfToken() {
            const meta = document.querySelector('meta[name="csrf-token"]');
            return meta ? meta.getAttribute('content') : '';
        }

        function append(text, fromUser) {
            const div = document.createElement('div');
            div.className = 'chat-bubble ' + (fromUser ? 'chat-bubble--user' : 'chat-bubble--bot');
            div.textContent = text;
            box.appendChild(div);
            requestAnimationFrame(function () { box.scrollTop = box.scrollHeight; });
            return div;
        }

        function setSubmitting(isBusy) {
            if (submitBtn) {
                submitBtn.disabled = isBusy;
                submitBtn.style.opacity = isBusy ? '0.65' : '1';
                submitBtn.style.pointerEvents = isBusy ? 'none' : 'auto';
                submitBtn.style.cursor = isBusy ? 'not-allowed' : 'pointer';
                const icon = submitBtn.querySelector('i');
                if (icon) {
                    icon.className = isBusy ? 'fa-solid fa-spinner fa-spin' : 'fa-solid fa-paper-plane';
                }
            }
            if (input) {
                input.disabled = isBusy;
            }
        }

        form.addEventListener('submit', function (e) {
            e.preventDefault();
            const text = input.value.trim();
            if (!text) return;
            if (submitBtn && submitBtn.disabled) return;

            append(text, true);
            input.value = '';
            const loading = append('...', false);
            setSubmitting(true);

            fetch('{{ route('student.assistant.store') }}', {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'X-CSRF-TOKEN': csrfToken(),
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ message: text })
            })
            .then(function (r) {
                if (r.ok) return r.json();
                return Promise.reject(new Error('HTTP ' + r.status));
            })
            .then(function (data) {
                loading.textContent = (data && data.reply) || 'Sorry, I could not answer that right now.';
                if (data && data.csrf_token) {
                    const meta = document.querySelector('meta[name="csrf-token"]');
                    if (meta) meta.setAttribute('content', data.csrf_token);
                }
            })
            .catch(function (error) {
                console.error('Chat error:', error);
                loading.textContent = 'Something went wrong. Please try again.';
            })
            .finally(function () {
                setSubmitting(false);
                if (input) {
                    input.focus();
                }
            });
        });
    })();
</script>
<style>
    .chat-box {
        min-height: 320px;
        max-height: 420px;
        overflow-y: auto;
        padding: 16px;
        background: var(--dash-surface-raised, #1b2437);
        border-bottom: 1px solid var(--dash-line, rgba(153,174,214,.18));
        display: flex;
        flex-direction: column;
        gap: 10px;
        scroll-behavior: smooth;
    }
    body.light-mode .chat-box {
        background: var(--dash-surface-raised, #f2f6fc);
    }

    .chat-bubble {
        border-radius: 12px;
        padding: 10px 14px;
        max-width: 80%;
        white-space: pre-wrap;
        word-break: break-word;
        line-height: 1.5;
        font-size: 0.88rem;
    }

    .chat-bubble--user {
        align-self: flex-end;
        background: #2563eb !important;
        color: #ffffff !important;
        border: 1px solid #2563eb !important;
    }

    .chat-bubble--bot {
        align-self: flex-start;
        background: var(--dash-surface, #151c2c) !important;
        color: var(--dash-text, #eef4ff) !important;
        border: 1px solid var(--dash-line, rgba(153,174,214,.18)) !important;
    }
    body.light-mode .chat-bubble--bot {
        background: var(--dash-surface, #ffffff) !important;
        color: var(--dash-text, #16233c) !important;
        border: 1px solid var(--dash-line, rgba(31,52,88,.14)) !important;
    }

    .chat-form {
        display: flex;
        gap: 8px;
        padding: 14px;
    }

    .chat-input {
        flex: 1;
        padding: 10px 12px;
        border-radius: 6px;
        border: 1px solid var(--dash-line, rgba(153,174,214,.18));
        background: var(--dash-bg, #101625) !important;
        color: var(--dash-text, #eef4ff) !important;
    }
    body.light-mode .chat-input {
        background: #ffffff !important;
        color: #0f172a !important;
        border-color: #cbd5e1 !important;
    }
    .chat-input:disabled {
        opacity: 0.7;
    }

    .chat-submit {
        padding: 10px 22px;
        border-radius: 6px;
        cursor: pointer;
    }
</style>
@endsection
