{{-- ================= SHAREABLE SHORTLIST =================
     The bar, the dialog and everything that turns a set of ticked hoardings
     into a link someone can send. Included by /search and by the Map, which
     pick their hoardings in completely different ways — a checkbox on a card
     there, a checkbox in a map popup here — but share them identically.

     A page does NOT touch the markup below. It talks to window.BrandAddaShare:

        BrandAddaShare.toggle(id, on)   tick / untick one hoarding
        BrandAddaShare.has(id)          is it ticked
        BrandAddaShare.size()           how many are
        BrandAddaShare.clear()          drop them all
        BrandAddaShare.onRender(fn)     called whenever the set changes, so the
                                        page can refresh its own controls
        BrandAddaShare.onClear(fn)      called when Clear is pressed, so the
                                        page can untick its own boxes

     Team only: the whole block renders behind site_admin(), and the endpoint
     it posts to checks again rather than trusting that. --}}
@if (site_admin())
        <div class="share-bar" id="shareBar" aria-live="polite">
            <div class="share-bar-count">
                <strong id="shareCount">0</strong> hoarding<span id="shareCountPlural"></span> shortlisted
            </div>
            <button type="button" class="share-bar-clear" id="shareClear">Clear</button>
            <button type="button" class="share-bar-go" id="shareGenerate">
                <i class="bi bi-link-45deg" aria-hidden="true"></i> Share selected
            </button>
        </div>

        <div class="share-modal" id="shareModal" role="dialog" aria-modal="true" aria-labelledby="shareModalTitle">
            <div class="share-modal-card">
                <div class="share-modal-head">
                    <h5 id="shareModalTitle">Shareable link ready</h5>
                    <button type="button" class="share-modal-close" id="shareModalClose" aria-label="Close">&times;</button>
                </div>

                <p class="share-modal-sub" id="shareModalSub"></p>

                <div class="share-copy">
                    <input type="text" id="shareUrl" readonly>
                    <button type="button" id="shareCopy">Copy</button>
                </div>

                <div class="share-send">
                    <a href="#" target="_blank" rel="noopener" id="shareWhatsApp" class="share-send-btn wa">
                        <i class="bi bi-whatsapp" aria-hidden="true"></i> WhatsApp
                    </a>
                    <a href="#" id="shareEmail" class="share-send-btn mail">
                        <i class="bi bi-envelope" aria-hidden="true"></i> Email
                    </a>
                    <a href="#" id="shareSms" class="share-send-btn sms">
                        <i class="bi bi-chat-dots" aria-hidden="true"></i> SMS
                    </a>
                    <a href="#" target="_blank" rel="noopener" id="shareTelegram" class="share-send-btn tg">
                        <i class="bi bi-telegram" aria-hidden="true"></i> Telegram
                    </a>
                    {{-- The device's own share sheet — anything else the team has
                         installed. Revealed only where the browser supports it,
                         which in practice means phones and tablets. --}}
                    <button type="button" id="shareMore" class="share-send-btn more" hidden>
                        <i class="bi bi-three-dots" aria-hidden="true"></i> More apps
                    </button>
                </div>

                <p class="share-modal-note">
                    Anyone with this link can view only these hoardings. It does not expire.
                </p>
            </div>
        </div>

    <script>
        (function () {
            const bar         = document.getElementById('shareBar');
            const countEl     = document.getElementById('shareCount');
            const pluralEl    = document.getElementById('shareCountPlural');
            const modal       = document.getElementById('shareModal');
            const urlInput    = document.getElementById('shareUrl');
            const generateBtn = document.getElementById('shareGenerate');

            // The ticked ids live here rather than being read back off the DOM.
            // Both pages replace their markup underneath the selection - cards
            // are appended, markers are torn down and rebuilt on every filter
            // change - and a Set survives that where a DOM scan would not.
            const picked = new Set();

            // What the including page asked to be told about.
            let onRender = function () {};
            let onClear  = function () {};

            function render() {
                countEl.textContent = picked.size;
                pluralEl.textContent = picked.size === 1 ? '' : 's';
                bar.classList.toggle('is-visible', picked.size > 0);
                onRender(picked.size);
            }

            window.BrandAddaShare = {
                toggle(id, on) {
                    id = parseInt(id, 10);
                    if (on) { picked.add(id); } else { picked.delete(id); }
                    render();
                },
                has(id) { return picked.has(parseInt(id, 10)); },
                size()  { return picked.size; },
                ids()   { return Array.from(picked); },
                clear() { picked.clear(); onClear(); render(); },
                onRender(fn) { onRender = fn; render(); },
                onClear(fn)  { onClear = fn; },
                refresh() { render(); }
            };

            document.getElementById('shareClear').addEventListener('click', function () {
                window.BrandAddaShare.clear();
            });

            generateBtn.addEventListener('click', function () {
                if (!picked.size) return;

                generateBtn.disabled = true;
                generateBtn.textContent = 'Generating...';

                fetch("{{ route('shared.link.store') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': "{{ csrf_token() }}",
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ media_ids: Array.from(picked) })
                })
                    .then(r => r.json().then(body => ({ ok: r.ok, body })))
                    .then(({ ok, body }) => {
                        if (!ok || !body.ok) {
                            alert(body.message || 'Could not generate the link. Please try again.');
                            return;
                        }
                        openModal(body.url, body.count);
                    })
                    .catch(() => alert('Could not generate the link. Please try again.'))
                    .finally(() => {
                        generateBtn.disabled = false;
                        generateBtn.innerHTML = '<i class="bi bi-link-45deg"></i> Share selected';
                    });
            });

            function openModal(url, count) {
                urlInput.value = url;
                document.getElementById('shareModalSub').textContent =
                    count + ' hoarding' + (count === 1 ? '' : 's') + ' in this shortlist.';

                // One message for every channel, from config/share_link.php
                // so marketing can reword it without touching this view.
                // split/join rather than replace(): a $-sequence in the URL
                // would otherwise be read as a replacement pattern.
                const subject = @json(config('share_link.subject'));
                const message = @json(config('share_link.message')).split(':link').join(url);

                document.getElementById('shareWhatsApp').href =
                    'https://wa.me/?text=' + encodeURIComponent(message);
                document.getElementById('shareEmail').href =
                    'mailto:?subject=' + encodeURIComponent(subject) +
                    '&body=' + encodeURIComponent(message);
                // ?&body= is the form both iOS and Android accept; either
                // one alone is ignored by the other.
                document.getElementById('shareSms').href =
                    'sms:?&body=' + encodeURIComponent(message);
                // Telegram requires url=, and renders text= above it.
                document.getElementById('shareTelegram').href =
                    'https://t.me/share/url?url=' + encodeURIComponent(url) +
                    '&text=' + encodeURIComponent(message);

                // Everything else the device can share to. navigator.share
                // needs a user gesture and a secure context, so the button
                // is only offered where it will actually open something.
                const moreBtn = document.getElementById('shareMore');
                if (navigator.share && window.isSecureContext) {
                    moreBtn.hidden = false;
                    moreBtn.onclick = function () {
                        navigator.share({ title: subject, text: message, url: url })
                            .catch(function () { /* dismissed - nothing to report */ });
                    };
                } else {
                    moreBtn.hidden = true;
                }

                modal.classList.add('is-open');
            }

            function closeModal() {
                modal.classList.remove('is-open');
            }

            document.getElementById('shareModalClose').addEventListener('click', closeModal);
            modal.addEventListener('click', e => { if (e.target === modal) closeModal(); });
            document.addEventListener('keydown', e => {
                if (e.key === 'Escape' && modal.classList.contains('is-open')) closeModal();
            });

            document.getElementById('shareCopy').addEventListener('click', function () {
                const btn = this;
                // execCommand fallback: the async clipboard API needs a
                // secure context, and this panel is used on plain http in
                // local and staging.
                const done = () => {
                    btn.textContent = 'Copied';
                    setTimeout(() => (btn.textContent = 'Copy'), 1600);
                };

                if (navigator.clipboard && window.isSecureContext) {
                    navigator.clipboard.writeText(urlInput.value).then(done);
                } else {
                    urlInput.select();
                    document.execCommand('copy');
                    done();
                }
            });

            render();
        })();
    </script>
@endif
