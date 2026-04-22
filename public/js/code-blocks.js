/**
 * code-blocks.js
 * Place in: public/js/code-blocks.js
 *
 * Loads CodeMirror 6 from local bundle (no CDN).
 * Bundle must be at: public/vendors/codemirror/codemirror.bundle.js
 */

// ── Synchronous stubs so onclick= handlers never throw ReferenceError ──
window.cbRun = function(id) { window._cbQueue.push(['run', id]); };
window.cbSync = function(id) {};
window.cbClearOutput = function(id) {
    var out = document.getElementById('cb-output-' + id);
    var cnt = document.getElementById('cb-output-content-' + id);
    if (out) out.style.display = 'none';
    if (cnt) cnt.innerHTML = '';
};

window._cbQueue = [];
window._cbReady = false;

(function () {
    // ── Local bundle path ──
    var BUNDLE = '/vendors/codemirror/codemirror.bundle.js';

    import(BUNDLE).then(function (mods) {

        var EditorState         = mods.EditorState;
        var EditorView          = mods.EditorView;
        var keymap              = mods.keymap;
        var lineNumbers         = mods.lineNumbers;
        var highlightActiveLine = mods.highlightActiveLine;
        var drawSelection       = mods.drawSelection;
        var defaultKeymap       = mods.defaultKeymap;
        var history             = mods.history;
        var historyKeymap       = mods.historyKeymap;
        var indentWithTab       = mods.indentWithTab;
        var indentOnInput       = mods.indentOnInput;
        var bracketMatching     = mods.bracketMatching;
        var autocompletion      = mods.autocompletion;
        var python              = mods.python;
        var javascript          = mods.javascript;
        var cpp                 = mods.cpp;
        var java                = mods.java;
        var rust                = mods.rust;
        var oneDark             = mods.oneDark;

        // ── Language map ──
        var CM_LANG = {
            python:     python(),
            javascript: javascript(),
            typescript: javascript({ typescript: true }),
            cpp:        cpp(),
            'c++':      cpp(),
            c:          cpp(),
            java:       java(),
            rust:       rust(),
        };

        var CB_VIEWS    = {};
        var CB_INITIAL  = {};
        var CB_LANG_MAP = {};

        // ── Helpers ──
        function populateVersions(id, lang) {
            var verSel = document.querySelector('.cb-ver-sel[data-block="' + id + '"]');
            if (!verSel) return;
            var versions = CB_LANG_MAP[lang] || [];
            verSel.innerHTML = versions
                .slice()
                .sort(function (a, b) { return b.localeCompare(a, undefined, { numeric: true }); })
                .map(function (v) { return '<option value="' + v + '">' + v + '</option>'; })
                .join('');
        }

        function syncHidden(id) {
            var view    = CB_VIEWS[id];
            var langSel = document.querySelector('.cb-lang-sel[data-block="' + id + '"]');
            var verSel  = document.querySelector('.cb-ver-sel[data-block="' + id + '"]');
            var hidden  = document.getElementById('cb-hidden-' + id);
            if (!view || !hidden) return;

            var payload = {
                language: langSel ? langSel.value : '',
                version:  verSel  ? verSel.value  : '',
                code:     view.state.doc.toString(),
            };
            hidden.value = JSON.stringify(payload);
            hidden.dispatchEvent(new Event('input', { bubbles: true }));
        }

        function createEditor(id) {
            var host = document.getElementById('cb-cm-' + id);
            if (!host) return;

            if (CB_VIEWS[id]) {
                CB_VIEWS[id].destroy();
                delete CB_VIEWS[id];
            }

            var raw = host.dataset.initialCode || '';
            var tmp = document.createElement('textarea');
            tmp.innerHTML = raw;
            var code = tmp.value || '# write your code here\n';
            CB_INITIAL[id] = code;

            var langSel = document.querySelector('.cb-lang-sel[data-block="' + id + '"]');
            var lang    = langSel ? langSel.value : 'python';

            var view = new EditorView({
                state: EditorState.create({
                    doc: code,
                    extensions: [
                        lineNumbers(),
                        highlightActiveLine(),
                        history(),
                        drawSelection(),
                        indentOnInput(),
                        bracketMatching(),
                        autocompletion(),
                        keymap.of(defaultKeymap.concat(historyKeymap).concat([indentWithTab])),
                        CM_LANG[lang] || [],
                        oneDark,
                        EditorView.updateListener.of(function () { syncHidden(id); }),
                    ],
                }),
                parent: host,
            });
            CB_VIEWS[id] = view;
        }

        function initBlock(id) {
            var langSel = document.querySelector('.cb-lang-sel[data-block="' + id + '"]');
            if (!langSel) return;

            var langs     = Object.keys(CB_LANG_MAP).sort();
            var savedLang = document.getElementById('cb-cm-' + id)?.dataset.initialLang || 'python';

            langSel.innerHTML = langs.map(function (l) {
                return '<option value="' + l + '"' + (l === savedLang ? ' selected' : '') + '>' +
                    l.charAt(0).toUpperCase() + l.slice(1) + '</option>';
            }).join('');

            var lang = langSel.value || langs[0] || 'python';
            populateVersions(id, lang);
            createEditor(id);
            syncHidden(id);

            langSel.addEventListener('change', function () {
                populateVersions(id, langSel.value);
                syncHidden(id);
            });

            var verSel = document.querySelector('.cb-ver-sel[data-block="' + id + '"]');
            if (verSel) verSel.addEventListener('change', function () { syncHidden(id); });
        }

        function initAll() {
            document.querySelectorAll('.cb-wrap').forEach(function (el) {
                var id = el.dataset.blockId;
                if (id) initBlock(id);
            });
        }

        // ── Real implementations ──

        window.cbSync = syncHidden;

        window.cbRun = function (id) {
            var view    = CB_VIEWS[id];
            var langSel = document.querySelector('.cb-lang-sel[data-block="' + id + '"]');
            var verSel  = document.querySelector('.cb-ver-sel[data-block="' + id + '"]');
            var btn     = document.querySelector('.cb-run-btn[data-block="' + id + '"]');

            if (!view) { alert('Editor not ready. Please wait a moment.'); return; }

            var code    = view.state.doc.toString();
            var lang    = langSel ? langSel.value : '';
            var version = verSel  ? verSel.value  : '';

            if (!lang || !version) { alert('Language not loaded yet. Please wait.'); return; }

            btn.disabled    = true;
            var orig        = btn.innerHTML;
            btn.innerHTML   = '⟳ Running…';

            var outputWrap  = document.getElementById('cb-output-' + id);
            var outputEl    = document.getElementById('cb-output-content-' + id);
            var badge       = document.getElementById('cb-badge-' + id);

            if (outputWrap) outputWrap.style.display = '';
            if (outputEl)   outputEl.innerHTML = '<span class="cb-out-system">Running…</span>';
            if (badge)      badge.style.display = 'none';

            var stdin   = '';
            var stdinEl = document.getElementById('cb-stdin-' + id);
            if (stdinEl) stdin = stdinEl.value;

            var csrf = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';

            fetch('/api/code/execute', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
                body: JSON.stringify({ language: lang, version: version, code: code, stdin: stdin }),
            })
                .then(function (res) { return res.json(); })
                .then(function (data) {
                    if (data.error) {
                        if (outputEl) outputEl.innerHTML = '<span class="cb-out-stderr">✗ ' + data.error + '</span>';
                        return;
                    }
                    var html = '';
                    if (data.compile && data.compile.stderr) html += '<span class="cb-out-compile">[Compile Error]\n' + data.compile.stderr + '</span>';
                    if (data.stdout) html += data.stdout;
                    if (data.stderr) html += '<span class="cb-out-stderr">' + data.stderr + '</span>';
                    if (!html) html = '<span class="cb-out-system">(no output)</span>';
                    if (outputEl) outputEl.innerHTML = html;

                    if (badge) {
                        var code2 = data.exit_code;
                        badge.className   = 'cb-exit-badge ' + (code2 === 0 ? 'ok' : 'fail');
                        badge.textContent = code2 === 0 ? 'Exit 0 ✓' : 'Exit ' + code2;
                        badge.style.display = 'inline-flex';
                    }
                })
                .catch(function (e) {
                    if (outputEl) outputEl.innerHTML = '<span class="cb-out-stderr">✗ ' + e.message + '</span>';
                })
                .finally(function () {
                    btn.disabled  = false;
                    btn.innerHTML = orig;
                });
        };

        window.cbClearOutput = function (id) {
            var out = document.getElementById('cb-output-' + id);
            var cnt = document.getElementById('cb-output-content-' + id);
            if (out) out.style.display = 'none';
            if (cnt) cnt.innerHTML = '';
        };

        // ── Load runtimes then init ──
        fetch('/api/code/runtimes')
            .then(function (r) { return r.json(); })
            .then(function (list) {
                list.forEach(function (r) {
                    if (!CB_LANG_MAP[r.language]) CB_LANG_MAP[r.language] = [];
                    CB_LANG_MAP[r.language].push(r.version);
                });
            })
            .catch(function () {
                CB_LANG_MAP = {
                    python: ['3.10.0'], javascript: ['18.15.0'],
                    cpp: ['10.2.0'], c: ['10.2.0'], java: ['17.0.0'], rust: ['1.68.0'],
                };
            })
            .finally(function () {
                initAll();
                window._cbReady = true;
                window._cbQueue.forEach(function (item) {
                    if (item[0] === 'run') window.cbRun(item[1]);
                });
                window._cbQueue = [];
            });

        // ── Re-init after Livewire re-renders ──
        document.addEventListener('livewire:update', function () {
            setTimeout(function () {
                document.querySelectorAll('.cb-wrap').forEach(function (el) {
                    var id = el.dataset.blockId;
                    if (!id) return;
                    var existing = CB_VIEWS[id];
                    if (!existing || !document.body.contains(existing.dom)) {
                        initBlock(id);
                    }
                });
            }, 120);
        });

    }).catch(function (e) {
        console.error('[CodeBlocks] Failed to load local CodeMirror bundle:', e);
    });
})();
