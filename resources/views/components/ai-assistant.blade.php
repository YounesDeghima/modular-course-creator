{{--
    AI Course Assistant — components/ai-assistant.blade.php
    Modes: float (popup above FAB) | panel (right sidebar)
    Requires: $course, $chapter, $lesson, $name
--}}
<style>
    /* ══════════════════════════════════════════════════════════════
       FAB — fixed trigger button
    ══════════════════════════════════════════════════════════════ */
    #ai-fab {
        position: fixed;
        bottom: 24px;
        right: 24px;
        z-index: 9000;
        width: 40px;
        height: 40px;
        border-radius: 8px;
        background: var(--accent);
        border: none;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 17px;
        color: #fff;
        box-shadow: 0 2px 8px rgba(79,70,229,.35);
        transition: background .15s, box-shadow .15s;
    }
    #ai-fab:hover { background: var(--accent-hover); box-shadow: 0 4px 14px rgba(79,70,229,.45); }

    #ai-fab-badge {
        position: absolute;
        top: -4px; right: -4px;
        background: #ef4444;
        color: #fff;
        border-radius: 50%;
        font-size: 9px;
        font-weight: 700;
        width: 16px; height: 16px;
        display: none;
        align-items: center;
        justify-content: center;
        line-height: 1;
    }
    #ai-fab.has-unread #ai-fab-badge { display: flex; }

    /* ══════════════════════════════════════════════════════════════
       SHELL — shared base
    ══════════════════════════════════════════════════════════════ */
    #ai-assistant {
        position: fixed;
        z-index: 9001;
        display: flex;
        flex-direction: column;
        background: var(--bg);
        border: 1px solid var(--border);
        font-size: 13px;
        color: var(--text);
        font-family: inherit;
        overflow: hidden;
        opacity: 0;
        pointer-events: none;
        transition: opacity .18s, transform .18s;
        transform: translateY(8px);
    }
    #ai-assistant.open {
        opacity: 1;
        pointer-events: all;
        transform: translateY(0);
    }

    /* ── FLOAT mode ── */
    #ai-assistant.mode-float {
        bottom: 76px;
        right: 24px;
        width: 360px;
        height: 500px;
        max-height: 80vh;
        border-radius: 10px;
        box-shadow: 0 8px 32px var(--shadow);
    }

    /* ── PANEL mode ── */
    #ai-assistant.mode-panel {
        top: 60px;
        right: 0;
        bottom: 0;
        width: 340px;
        height: auto;
        border-radius: 10px 0 0 10px;
        border-right: none;
        box-shadow: -4px 0 24px var(--shadow);
        transform: translateX(8px);
    }
    #ai-assistant.mode-panel.open { transform: translateX(0); }

    /* ══════════════════════════════════════════════════════════════
       HEADER
    ══════════════════════════════════════════════════════════════ */
    #ai-header {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 10px 12px;
        background: var(--bg-subtle);
        border-bottom: 1px solid var(--border);
        flex-shrink: 0;
        user-select: none;
    }
    #ai-header-icon {
        font-size: 15px;
        flex-shrink: 0;
    }
    #ai-header-text { flex: 1; min-width: 0; }
    #ai-header-title {
        font-size: 13px;
        font-weight: 600;
        color: var(--text);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    #ai-header-sub {
        font-size: 10px;
        color: var(--text-faint);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        margin-top: 1px;
    }
    .ai-hbtn {
        width: 26px;
        height: 26px;
        border-radius: 6px;
        border: 1px solid var(--border);
        background: var(--bg);
        color: var(--text-muted);
        cursor: pointer;
        font-size: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        transition: background .13s, color .13s;
        line-height: 1;
    }
    .ai-hbtn:hover { background: var(--bg-hover); color: var(--text); }

    /* ══════════════════════════════════════════════════════════════
       SETTINGS DRAWER
    ══════════════════════════════════════════════════════════════ */
    #ai-settings {
        flex-shrink: 0;
        background: var(--bg-subtle);
        border-bottom: 1px solid var(--border);
        padding: 10px 12px;
        display: none;
        flex-direction: column;
        gap: 10px;
    }
    #ai-settings.open { display: flex; }

    .ai-setting-group { display: flex; flex-direction: column; gap: 4px; }

    .ai-setting-label {
        font-size: 10px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: .07em;
        color: var(--text-faint);
    }

    /* reuse your existing input style */
    #ai-settings select,
    #ai-settings input[type="range"] {
        width: 100%;
        font-size: 12px;
        padding: 5px 8px;
        border: 1px solid var(--border);
        border-radius: 6px;
        background: var(--bg);
        color: var(--text);
        font-family: inherit;
        outline: none;
        transition: border-color .15s;
        box-sizing: border-box;
    }
    #ai-settings select:focus { border-color: var(--accent); }

    /* Mode toggle row — like your .row-split pattern */
    .ai-mode-row { display: flex; gap: 5px; }
    .ai-mode-btn {
        flex: 1;
        padding: 5px 4px;
        border: 1px solid var(--border);
        border-radius: 6px;
        background: var(--bg);
        color: var(--text-muted);
        font-size: 11px;
        font-weight: 500;
        font-family: inherit;
        cursor: pointer;
        text-align: center;
        transition: background .13s, color .13s, border-color .13s;
    }
    .ai-mode-btn.active { background: var(--accent); color: #fff; border-color: var(--accent); }
    .ai-mode-btn:not(.active):hover { background: var(--bg-hover); color: var(--text); }

    /* Font size range label */
    #ai-fontsize-label {
        font-size: 10px;
        color: var(--text-faint);
        text-align: right;
        margin-top: -2px;
    }

    /* Temperature row */
    .ai-setting-row-split { display: flex; gap: 8px; }
    .ai-setting-row-split .ai-setting-group { flex: 1; }

    /* ══════════════════════════════════════════════════════════════
       CONTEXT BAR
    ══════════════════════════════════════════════════════════════ */
    #ai-context-bar {
        flex-shrink: 0;
        padding: 4px 12px;
        font-size: 10px;
        color: var(--text-faint);
        background: var(--bg);
        border-bottom: 1px solid var(--border);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    #ai-context-bar strong { color: var(--text-muted); font-weight: 600; }

    /* ══════════════════════════════════════════════════════════════
       MESSAGES
    ══════════════════════════════════════════════════════════════ */
    #ai-messages {
        flex: 1;
        overflow-y: auto;
        padding: 12px;
        display: flex;
        flex-direction: column;
        gap: 8px;
        scroll-behavior: smooth;
    }
    #ai-messages::-webkit-scrollbar { width: 4px; }
    #ai-messages::-webkit-scrollbar-thumb { background: var(--border); border-radius: 4px; }

    .ai-msg {
        display: flex;
        gap: 7px;
        max-width: 95%;
        animation: aiIn .15s ease;
    }
    @keyframes aiIn {
        from { opacity:0; transform:translateY(4px); }
        to   { opacity:1; transform:translateY(0); }
    }
    .ai-msg.user      { align-self: flex-end;  flex-direction: row-reverse; }
    .ai-msg.assistant { align-self: flex-start; }

    .ai-avatar {
        width: 24px; height: 24px;
        border-radius: 6px;
        flex-shrink: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 11px;
        font-weight: 700;
    }
    .ai-msg.assistant .ai-avatar { background: var(--accent); color: #fff; }
    .ai-msg.user      .ai-avatar { background: var(--bg-subtle); color: var(--text-muted); border: 1px solid var(--border); }

    .ai-bubble {
        padding: 7px 10px;
        border-radius: 8px;
        line-height: 1.6;
        word-break: break-word;
    }
    .ai-msg.user .ai-bubble {
        background: var(--accent);
        color: #fff;
        border-radius: 8px 8px 2px 8px;
    }
    .ai-msg.assistant .ai-bubble {
        background: var(--bg-subtle);
        color: var(--text);
        border: 1px solid var(--border);
        border-radius: 2px 8px 8px 8px;
    }

    /* typing dots */
    .ai-dots { display: flex; gap: 3px; padding: 2px 0; }
    .ai-dots span {
        width: 6px; height: 6px;
        border-radius: 50%;
        background: var(--accent);
        opacity: .4;
        animation: aiDot 1.1s infinite;
    }
    .ai-dots span:nth-child(2) { animation-delay: .18s; }
    .ai-dots span:nth-child(3) { animation-delay: .36s; }
    @keyframes aiDot {
        0%,80%,100% { opacity:.4; transform:scale(1); }
        40%         { opacity:1;  transform:scale(1.25); }
    }

    /* ══════════════════════════════════════════════════════════════
       INPUT ROW
    ══════════════════════════════════════════════════════════════ */
    #ai-input-row {
        flex-shrink: 0;
        display: flex;
        align-items: flex-end;
        gap: 6px;
        padding: 8px 10px;
        border-top: 1px solid var(--border);
        background: var(--bg-subtle);
    }
    #ai-input {
        flex: 1;
        resize: none;
        border: 1px solid var(--border);
        border-radius: 6px;
        padding: 6px 10px;
        font-family: inherit;
        color: var(--text);
        background: var(--bg);
        outline: none;
        max-height: 100px;
        overflow-y: auto;
        transition: border-color .15s;
        line-height: 1.5;
    }
    #ai-input:focus { border-color: var(--accent); box-shadow: 0 0 0 3px rgba(79,70,229,.08); }
    #ai-send {
        width: 32px; height: 32px;
        border-radius: 6px;
        background: var(--accent);
        border: none;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        flex-shrink: 0;
        transition: background .15s;
    }
    #ai-send:disabled { opacity: .4; cursor: not-allowed; }
    #ai-send:not(:disabled):hover { background: var(--accent-hover); }
</style>

{{-- FAB --}}
<button id="ai-fab" title="Course AI Assistant" aria-label="Open AI assistant">
    ✦
    <span id="ai-fab-badge">!</span>
</button>

{{-- Shell --}}
<div id="ai-assistant" class="mode-float" role="dialog" aria-label="AI Course Assistant">

    {{-- Header --}}
    <div id="ai-header">
        <span id="ai-header-icon">✦</span>
        <div id="ai-header-text">
            <div id="ai-header-title">Course Assistant</div>
            <div id="ai-header-sub">{{ $lesson->title ?? 'Current lesson' }}</div>
        </div>
        <button class="ai-hbtn" id="ai-settings-btn" title="Settings">⚙</button>
        <button class="ai-hbtn" id="ai-clear-btn"    title="Clear chat">↺</button>
        <button class="ai-hbtn" id="ai-close-btn"    title="Close">✕</button>
    </div>

    {{-- Settings Drawer --}}
    <div id="ai-settings">

        {{-- Mode --}}
        <div class="ai-setting-group">
            <div class="ai-setting-label">Display mode</div>
            <div class="ai-mode-row">
                <button class="ai-mode-btn active" data-mode="float">⬜ Float</button>
                <button class="ai-mode-btn"        data-mode="panel">▶ Panel</button>
            </div>
        </div>

        {{-- Model --}}
        <div class="ai-setting-group">
            <div class="ai-setting-label">Ollama model</div>
            <select id="ai-model-select">
                <option value="">— loading —</option>
            </select>
        </div>

        {{-- Font size + Temperature --}}
        <div class="ai-setting-row-split">
            <div class="ai-setting-group">
                <div class="ai-setting-label">Font size</div>
                <input type="range" id="ai-fontsize" min="11" max="16" value="13" step="1">
                <div id="ai-fontsize-label">13px</div>
            </div>
            <div class="ai-setting-group">
                <div class="ai-setting-label">Response style</div>
                <select id="ai-style-select">
                    <option value="balanced">Balanced</option>
                    <option value="concise">Concise</option>
                    <option value="detailed">Detailed</option>
                    <option value="socratic">Socratic</option>
                </select>
            </div>
        </div>

        {{-- Hint strength --}}
        <div class="ai-setting-group">
            <div class="ai-setting-label">Exercise help level</div>
            <select id="ai-hint-select">
                <option value="hint">Hints only (don't give full answers)</option>
                <option value="guided">Guided walkthrough</option>
                <option value="full">Full solution</option>
            </select>
        </div>

    </div>

    {{-- Context bar --}}
    <div id="ai-context-bar">
        📍 <strong>{{ $course->title ?? '' }}</strong>
        &rsaquo; {{ $chapter->title ?? '' }}
        &rsaquo; {{ $lesson->title ?? '' }}
    </div>

    {{-- Messages --}}
    <div id="ai-messages">
        <div class="ai-msg assistant" id="ai-welcome-msg">
            <div class="ai-avatar">✦</div>
            <div class="ai-bubble">Hi! I'm your assistant for <strong>{{ $course->title ?? 'this course' }}</strong>. I know where you are in the course — ask me anything about this lesson.</div>
        </div>
    </div>

    {{-- Input --}}
    <div id="ai-input-row">
        <textarea id="ai-input" placeholder="Ask about this lesson…" rows="1"></textarea>
        <button id="ai-send" disabled>
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <line x1="22" y1="2" x2="11" y2="13"/>
                <polygon points="22 2 15 22 11 13 2 9 22 2"/>
            </svg>
        </button>
    </div>
</div>

<script>
    (function () {
        const COURSE_ID   = {{ $course->id ?? 'null' }};
        const CHAPTER_ID  = {{ $chapter->id ?? 'null' }};
        const LESSON_ID   = {{ $lesson->id ?? 'null' }};
        const CHAT_URL    = '{{ route('admin.ai.course.assist', ['course' => $course->id ?? 0]) }}';
        const MODELS_URL  = '{{ route('admin.ai.models') }}';
        // FIX: read CSRF fresh on every send — Livewire can rotate the token,
        // and the meta tag may not be parsed yet when this line first executes.
        function getCsrf() {
            return document.querySelector('meta[name="csrf-token"]')?.content
                ?? document.cookie.match(/XSRF-TOKEN=([^;]+)/)?.[1]?.replace(/%3D/g,'=')
                ?? '';
        }
        const CSRF = getCsrf(); // keep for non-send uses (backward compat)
        const SESSION_KEY = `ai_chat_course_${COURSE_ID}`;
        const PREFS_KEY   = `ai_prefs_course_${COURSE_ID}`;

        let messages = [];
        let model    = '';
        let mode     = 'float';
        let style    = 'balanced';
        let hint     = 'hint';
        let fontSize = 13;
        let loading  = false;
        let isOpen   = false;

        const fab         = document.getElementById('ai-fab');
        const shell       = document.getElementById('ai-assistant');
        const messagesEl  = document.getElementById('ai-messages');
        const inputEl     = document.getElementById('ai-input');
        const sendBtn     = document.getElementById('ai-send');
        const closeBtn    = document.getElementById('ai-close-btn');
        const clearBtn    = document.getElementById('ai-clear-btn');
        const settingsBtn = document.getElementById('ai-settings-btn');
        const settingsEl  = document.getElementById('ai-settings');
        const modelSel    = document.getElementById('ai-model-select');
        const styleSel    = document.getElementById('ai-style-select');
        const hintSel     = document.getElementById('ai-hint-select');
        const fontSlider  = document.getElementById('ai-fontsize');
        const fontLabel   = document.getElementById('ai-fontsize-label');
        const modeBtns    = document.querySelectorAll('.ai-mode-btn');

        // ── Init ─────────────────────────────────────────────────────────────
        function init() {
            loadPrefs();
            loadSession();
            loadModels();
            applyMode(mode, false);
            applyFontSize(fontSize, false);
            if (styleSel) styleSel.value = style;
            if (hintSel)  hintSel.value  = hint;
            renderMessages();
            updateSend();
        }

        // ── Prefs ─────────────────────────────────────────────────────────────
        function loadPrefs() {
            try {
                const p = JSON.parse(localStorage.getItem(PREFS_KEY) || '{}');
                if (p.mode)     mode     = p.mode;
                if (p.model)    model    = p.model;
                if (p.style)    style    = p.style;
                if (p.hint)     hint     = p.hint;
                if (p.fontSize) fontSize = p.fontSize;
            } catch(_) {}
        }
        function savePrefs() {
            localStorage.setItem(PREFS_KEY, JSON.stringify({ mode, model, style, hint, fontSize }));
        }

        // ── Session ───────────────────────────────────────────────────────────
        function loadSession() {
            try {
                const s = JSON.parse(sessionStorage.getItem(SESSION_KEY) || '[]');
                if (Array.isArray(s)) messages = s;
            } catch(_) { messages = []; }
        }
        function saveSession() {
            try { sessionStorage.setItem(SESSION_KEY, JSON.stringify(messages)); } catch(_) {}
        }

        // ── Models ────────────────────────────────────────────────────────────
        async function loadModels() {
            try {
                const r = await fetch(MODELS_URL);
                const d = await r.json();
                if (d.ok && d.models?.length) {
                    modelSel.innerHTML = d.models
                        .map(m => `<option value="${m}" ${m === model ? 'selected' : ''}>${m}</option>`)
                        .join('');
                    if (!model) model = d.models[0];
                    modelSel.value = model;
                    updateSend();
                } else {
                    modelSel.innerHTML = '<option value="">No models — is Ollama running?</option>';
                }
            } catch(e) {
                modelSel.innerHTML = '<option value="">Cannot reach Ollama</option>';
            }
        }

        modelSel.addEventListener('change', () => { model = modelSel.value; savePrefs(); updateSend(); });
        styleSel.addEventListener('change', () => { style = styleSel.value; savePrefs(); });
        hintSel.addEventListener('change',  () => { hint  = hintSel.value;  savePrefs(); });

        // ── Font size ─────────────────────────────────────────────────────────
        function applyFontSize(size, save = true) {
            fontSize = size;
            messagesEl.style.fontSize = size + 'px';
            fontSlider.value = size;
            fontLabel.textContent = size + 'px';
            if (save) savePrefs();
        }
        fontSlider.addEventListener('input', () => applyFontSize(parseInt(fontSlider.value)));

        // ── Mode ──────────────────────────────────────────────────────────────
        function applyMode(newMode, save = true) {
            mode = newMode;
            shell.classList.remove('mode-float', 'mode-panel');
            shell.classList.add('mode-' + mode);
            modeBtns.forEach(b => b.classList.toggle('active', b.dataset.mode === mode));
            if (save) savePrefs();
        }
        modeBtns.forEach(b => b.addEventListener('click', () => applyMode(b.dataset.mode)));

        // ── Open / Close ──────────────────────────────────────────────────────
        function open() {
            isOpen = true;
            shell.classList.add('open');
            fab.classList.remove('has-unread');
            setTimeout(() => inputEl.focus(), 180);
            scrollDown();
        }
        function close() {
            isOpen = false;
            shell.classList.remove('open');
            settingsEl.classList.remove('open');
        }

        fab.addEventListener('click', () => isOpen ? close() : open());
        closeBtn.addEventListener('click', close);
        settingsBtn.addEventListener('click', () => settingsEl.classList.toggle('open'));
        clearBtn.addEventListener('click', () => {
            if (!confirm('Clear chat history?')) return;
            messages = [];
            saveSession();
            renderMessages();
        });

        // ── Render ────────────────────────────────────────────────────────────
        function renderMessages() {
            Array.from(messagesEl.children).forEach(el => {
                if (el.id !== 'ai-welcome-msg') el.remove();
            });
            messages.forEach(m => messagesEl.appendChild(bubble(m.role, m.content)));
            scrollDown();
        }

        function bubble(role, content) {
            const wrap   = document.createElement('div');
            wrap.className = `ai-msg ${role}`;
            const av     = document.createElement('div');
            av.className = 'ai-avatar';
            av.textContent = role === 'assistant' ? '✦' : '{{ substr($name ?? "U", 0, 1) }}';
            const bub    = document.createElement('div');
            bub.className = 'ai-bubble';
            bub.innerHTML = esc(content)
                .replace(/`([^`]+)`/g, '<code style="background:var(--bg-subtle);padding:1px 4px;border-radius:4px;font-family:monospace">$1</code>')
                .replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>')
                .replace(/\*([^*]+)\*/g, '<em>$1</em>');
            wrap.appendChild(av);
            wrap.appendChild(bub);
            return wrap;
        }

        function esc(s) { return s.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }

        function typingOn() {
            const w = document.createElement('div');
            w.className = 'ai-msg assistant'; w.id = 'ai-typing';
            w.innerHTML = `<div class="ai-avatar">✦</div><div class="ai-bubble"><div class="ai-dots"><span></span><span></span><span></span></div></div>`;
            messagesEl.appendChild(w);
            scrollDown();
        }
        function typingOff() { document.getElementById('ai-typing')?.remove(); }
        function scrollDown() { requestAnimationFrame(() => { messagesEl.scrollTop = messagesEl.scrollHeight; }); }

        // ── Send ──────────────────────────────────────────────────────────────
        function updateSend() {
            sendBtn.disabled = !inputEl.value.trim() || !model || loading;
        }

        inputEl.addEventListener('input', () => {
            inputEl.style.height = 'auto';
            inputEl.style.height = Math.min(inputEl.scrollHeight, 100) + 'px';
            updateSend();
        });
        inputEl.addEventListener('keydown', e => {
            if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); if (!sendBtn.disabled) send(); }
        });
        sendBtn.addEventListener('click', send);

        async function send() {
            const text = inputEl.value.trim();
            if (!text || !model || loading) return;

            messages.push({ role: 'user', content: text });
            saveSession(); renderMessages();
            inputEl.value = ''; inputEl.style.height = 'auto'; updateSend();

            loading = true; updateSend(); typingOn();
            if (!isOpen) fab.classList.add('has-unread');

            // Build style instruction suffix
            const styleHints = {
                concise:  ' Respond very concisely.',
                detailed: ' Respond in detail with examples.',
                socratic: ' Use the Socratic method — ask questions to guide the student rather than giving direct answers.',
                balanced: ''
            };
            const hintHints = {
                hint:    ' For exercises, give hints only — do not give full answers.',
                guided:  ' For exercises, give a guided walkthrough step by step.',
                full:    ' For exercises, give the full solution.'
            };

            // Clone messages and inject style into last user message
            const toSend = messages.map((m, i) =>
                (i === messages.length - 1 && m.role === 'user')
                    ? { ...m, content: m.content + (styleHints[style] || '') + (hintHints[hint] || '') }
                    : m
            );

            try {
                const r = await fetch(CHAT_URL, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': getCsrf(), 'Accept': 'application/json' },
                    body: JSON.stringify({ model, messages: toSend, lesson_id: LESSON_ID, chapter_id: CHAPTER_ID }),
                });
                const d = await r.json();
                typingOff();
                const reply = d.ok ? d.content : ('⚠ ' + (d.error || 'Something went wrong.'));
                messages.push({ role: 'assistant', content: reply });
                saveSession(); renderMessages();
                if (!isOpen) fab.classList.add('has-unread');
            } catch(e) {
                typingOff();
                messages.push({ role: 'assistant', content: '⚠ Network error. Please try again.' });
                saveSession(); renderMessages();
            } finally {
                loading = false; updateSend();
            }
        }

        init();
    })();
</script>
