@extends('layouts.student')
@section('title', 'Study Assistant')
@php($activeNav='assistant')
@section('page-title-bar')
<div class="page-title-bar"><h2 class="page-title"><i class="fa-solid fa-robot"></i> Study Assistant</h2><div class="page-actions"><span style="font-size:0.85rem;color:#64748b">AI study support</span></div></div>
@endsection
@section('content')
<div class="crud-card">
    <div class="crud-header"><h3>Ask about your deadlines, progress &amp; priorities</h3></div>
    <div id="chatBox" style="min-height:320px;max-height:420px;overflow-y:auto;padding:16px;background:#f8fafc;border-bottom:1px solid #e2e8f0;display:flex;flex-direction:column;gap:10px">
        <div style="align-self:flex-start;background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:10px 14px;max-width:80%">Hi! I'm your study assistant. Try asking: "What's due this week?", "How am I doing?", "What should I work on first?", or "How do I catch up?"</div>
    </div>
    <form id="chatForm" style="display:flex;gap:8px;padding:14px">
        @csrf
        <input type="text" id="message" name="message" maxlength="500" placeholder="Type your question..." style="flex:1;padding:10px;border:1px solid #cbd5e1;border-radius:6px" required>
        <button type="submit" class="btn-add" style="padding:10px 22px;border-radius:6px;cursor:pointer"><i class="fa-solid fa-paper-plane"></i> Send</button>
    </form>
</div>
<script>
    (function () {
        const box = document.getElementById('chatBox');
        const form = document.getElementById('chatForm');
        const input = document.getElementById('message');
        const csrf = document.querySelector('meta[name="csrf-token"]').content;

        function append(text, fromUser) {
            const div = document.createElement('div');
            div.style.cssText = 'align-self:' + (fromUser ? 'flex-end' : 'flex-start') + ';background:' + (fromUser ? '#2563eb' : '#fff') + ';color:' + (fromUser ? '#fff' : '#0f172a') + ';border:1px solid ' + (fromUser ? '#2563eb' : '#e2e8f0') + ';border-radius:12px;padding:10px 14px;max-width:80%;white-space:pre-wrap';
            div.textContent = text;
            box.appendChild(div);
            box.scrollTop = box.scrollHeight;
            return div;
        }

        form.addEventListener('submit', function (e) {
            e.preventDefault();
            const text = input.value.trim();
            if (!text) return;
            append(text, true);
            input.value = '';
            const loading = append('...', false);
            fetch('{{ route('student.assistant.store') }}', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json', 'Content-Type': 'application/json' },
                body: JSON.stringify({ message: text })
            })
            .then(r => r.json())
            .then(data => { loading.textContent = data.reply || 'Sorry, I could not answer that right now.'; })
            .catch(() => { loading.textContent = 'Something went wrong. Please try again.'; });
        });
    })();
</script>
@endsection
