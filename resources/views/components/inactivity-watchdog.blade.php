{{-- Client-side warning only; TrackUserActivity is the authoritative server-side timeout. --}}
@auth
<script>
(function () {
    'use strict';

    var timeoutSeconds = {{ (int) config('lms.session_inactivity_timeout', 30) * 60 }};
    var warnSeconds = Math.max(10, Math.min(60, Math.floor(timeoutSeconds / 4)));
    if (!timeoutSeconds) return;

    var warned = false;
    var idleTimer = null;
    var countdownTimer = null;
    var overlay = null;
    var countdownLeft = warnSeconds;

    function makeOverlay() {
        var o = document.createElement('div');
        o.id = 'inactivity-overlay';
        o.setAttribute('role', 'dialog');
        o.setAttribute('aria-live', 'assertive');
        o.setAttribute('aria-label', 'Inactivity warning');
        o.style.cssText = 'position:fixed;inset:0;z-index:99999;display:flex;align-items:center;justify-content:center;background:rgba(11,18,32,.62);backdrop-filter:blur(2px);font-family:Inter,ui-sans-serif,system-ui,sans-serif;';
        o.innerHTML =
            '<div style="background:#fff;color:#13213b;border-radius:16px;box-shadow:0 24px 70px rgba(9,25,58,.35);padding:34px 38px;max-width:420px;width:calc(100% - 48px);text-align:center;">' +
            '<div style="font-size:1.05rem;font-weight:800;color:#173aa8;margin-bottom:10px;">You have been idle</div>' +
            '<p style="margin:0 0 18px;color:#58708f;font-size:.92rem;line-height:1.6;">For your security, you will be signed out due to inactivity. Any activity will keep you signed in.</p>' +
            '<div id="inactivity-countdown" style="font-size:1.5rem;font-weight:800;color:#173aa8;margin-bottom:20px;">' + countdownLeft + 's</div>' +
            '<div style="display:flex;gap:10px;justify-content:center;">' +
            '<button type="button" id="inactivity-stay" style="flex:1;height:42px;border:0;border-radius:10px;background:linear-gradient(135deg,#173aa8,#092179);color:#fff;font-weight:800;cursor:pointer;">Stay signed in</button>' +
            '<button type="button" id="inactivity-now" style="flex:1;height:42px;border:1px solid #dce4f0;border-radius:10px;background:#fbfdff;color:#13213b;font-weight:700;cursor:pointer;">Sign out now</button>' +
            '</div></div>';
        document.body.appendChild(o);
        overlay = o;

        o.querySelector('#inactivity-stay').addEventListener('click', function () {
            fetch('{{ route('session.keep-alive') }}', {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    'Accept': 'application/json'
                }
            }).then(function (response) {
                if (!response.ok) throw new Error('Keep-alive failed');
                reset();
            }).catch(signOut);
        });
        o.querySelector('#inactivity-now').addEventListener('click', signOut);
    }

    function updateCountdown() {
        var el = document.getElementById('inactivity-countdown');
        if (el) el.textContent = countdownLeft + 's';
    }

    function warn() {
        if (warned) return;
        warned = true;
        countdownLeft = warnSeconds;
        makeOverlay();
        updateCountdown();
        countdownTimer = setInterval(function () {
            countdownLeft -= 1;
            if (countdownLeft <= 0) {
                clearInterval(countdownTimer);
                signOut();
                return;
            }
            updateCountdown();
        }, 1000);
    }

    function reset() {
        warned = false;
        clearTimeout(idleTimer);
        clearInterval(countdownTimer);
        if (overlay) {
            overlay.remove();
            overlay = null;
        }
        idleTimer = setTimeout(warn, timeoutSeconds * 1000);
    }

    function signOut() {
        clearTimeout(idleTimer);
        clearInterval(countdownTimer);
        var csrf = document.querySelector('meta[name="csrf-token"]');
        var form = document.createElement('form');
        form.method = 'POST';
        form.action = '{{ route('logout') }}';
        var token = document.createElement('input');
        token.type = 'hidden';
        token.name = '_token';
        token.value = csrf ? csrf.content : '';
        form.appendChild(token);
        document.body.appendChild(form);
        form.submit();
    }

    ['mousemove', 'keydown', 'click', 'scroll', 'touchstart'].forEach(function (eventName) {
        document.addEventListener(eventName, reset, { passive: true });
    });

    reset();
})();
</script>
@endauth
