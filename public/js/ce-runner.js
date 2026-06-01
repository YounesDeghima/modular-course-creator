/**
 * ce-runner.js
 * ─────────────────────────────────────────────────────────────
 * Shared SSE-based code runner + xterm.js terminal manager.
 * Used by:
 *   - Full code editor page  (codeeditor.blade.php)
 *   - Inline code blocks     (edit + preview)
 *
 * Public API:
 *   CeRunner.create(containerEl, options) → runner instance
 *
 * Instance methods:
 *   runner.run(language, code)
 *   runner.sendStdin(text)
 *   runner.clear()
 *   runner.dispose()
 *
 * Options:
 *   endpoint  : string   POST URL for /code-runner/run
 *   csrfToken : string   Laravel CSRF token
 *   onStatus  : fn(msg)  called with status text
 *   onDone    : fn(code) called when execution finishes
 */

(function (global) {
    'use strict';

    // ── xterm.js CDN loader (idempotent) ──────────────────────
    const XTERM_CSS = 'https://cdnjs.cloudflare.com/ajax/libs/xterm/5.3.0/xterm.min.css';
    const XTERM_JS  = 'https://cdnjs.cloudflare.com/ajax/libs/xterm/5.3.0/xterm.min.js';
    const FIT_JS    = 'https://cdnjs.cloudflare.com/ajax/libs/xterm/5.3.0/addon-fit.min.js';

    let xtermReady    = null; // Promise

    function loadXterm() {
        if (xtermReady) return xtermReady;
        xtermReady = new Promise((resolve, reject) => {
            if (window.Terminal) { resolve(); return; }

            // CSS
            if (!document.querySelector(`link[href="${XTERM_CSS}"]`)) {
                const link = document.createElement('link');
                link.rel  = 'stylesheet';
                link.href = XTERM_CSS;
                document.head.appendChild(link);
            }

            // xterm.js
            const s1 = document.createElement('script');
            s1.src = XTERM_JS;
            s1.onload = () => {
                // fit addon
                const s2 = document.createElement('script');
                s2.src = FIT_JS;
                s2.onload = resolve;
                s2.onerror = reject;
                document.head.appendChild(s2);
            };
            s1.onerror = reject;
            document.head.appendChild(s1);
        });
        return xtermReady;
    }

    // ── ANSI helpers ──────────────────────────────────────────
    const ANSI = {
        green:  s => `\x1b[32m${s}\x1b[0m`,
        red:    s => `\x1b[31m${s}\x1b[0m`,
        yellow: s => `\x1b[33m${s}\x1b[0m`,
        cyan:   s => `\x1b[36m${s}\x1b[0m`,
        gray:   s => `\x1b[90m${s}\x1b[0m`,
        bold:   s => `\x1b[1m${s}\x1b[0m`,
    };

    // ── Runner factory ────────────────────────────────────────
    function create(container, options) {
        const opts = Object.assign({
            endpoint:  '/admin/code-runner/run',
            csrfToken: document.querySelector('meta[name="csrf-token"]')?.content ?? '',
            onStatus:  () => {},
            onDone:    () => {},
        }, options);

        let term       = null;
        let fitAddon   = null;
        let evtSource  = null;   // XHR / fetch reader
        let running    = false;
        let stdinBuf   = [];     // queued stdin lines
        let stdinInput = null;   // <input> element if we create one
        let stdinRow   = null;

        // ── Build DOM ─────────────────────────────────────────
        container.style.display        = 'flex';
        container.style.flexDirection  = 'column';
        container.style.background     = '#0d1117';
        container.style.borderRadius   = '8px';
        container.style.overflow       = 'hidden';
        container.style.minHeight      = '200px';

        // terminal div
        const termDiv = document.createElement('div');
        termDiv.style.flex   = '1';
        termDiv.style.minHeight = '160px';
        container.appendChild(termDiv);

        // stdin row (hidden until process is running)
        stdinRow = document.createElement('div');
        stdinRow.style.cssText = 'display:none;align-items:center;gap:8px;padding:6px 12px;border-top:1px solid #30363d;background:#161b22;flex-shrink:0';

        const prompt = document.createElement('span');
        prompt.textContent = '›';
        prompt.style.cssText = 'color:#22c55e;font-weight:bold;font-size:14px;font-family:monospace;flex-shrink:0';

        stdinInput = document.createElement('input');
        stdinInput.type = 'text';
        stdinInput.placeholder = 'Type input and press Enter…';
        stdinInput.autocomplete = 'off';
        stdinInput.spellcheck = false;
        stdinInput.style.cssText = 'flex:1;background:transparent;border:none;outline:none;color:#79c0ff;font-family:"JetBrains Mono","Consolas",monospace;font-size:12px';

        const sendBtn = document.createElement('button');
        sendBtn.textContent = '↵';
        sendBtn.style.cssText = 'background:#21262d;border:1px solid #30363d;border-radius:4px;color:#8b949e;cursor:pointer;padding:3px 9px;font-size:12px;font-family:inherit';
        sendBtn.onmouseenter = () => sendBtn.style.background = '#30363d';
        sendBtn.onmouseleave = () => sendBtn.style.background = '#21262d';

        stdinInput.addEventListener('keydown', e => {
            if (e.key === 'Enter') sendStdin(stdinInput.value);
        });
        sendBtn.onclick = () => sendStdin(stdinInput.value);

        stdinRow.appendChild(prompt);
        stdinRow.appendChild(stdinInput);
        stdinRow.appendChild(sendBtn);
        container.appendChild(stdinRow);

        // ── Init xterm ────────────────────────────────────────
        async function initTerm() {
            await loadXterm();
            if (term) return;

            fitAddon = new window.FitAddon.FitAddon();
            term = new window.Terminal({
                theme: {
                    background:  '#0d1117',
                    foreground:  '#c9d1d9',
                    cursor:      '#58a6ff',
                    black:       '#0d1117',
                    red:         '#ff7b72',
                    green:       '#3fb950',
                    yellow:      '#d29922',
                    blue:        '#58a6ff',
                    magenta:     '#bc8cff',
                    cyan:        '#39c5cf',
                    white:       '#b1bac4',
                    brightBlack: '#6e7681',
                },
                fontFamily: '"JetBrains Mono","Fira Code","Consolas",monospace',
                fontSize:   13,
                lineHeight: 1.5,
                cursorBlink: false,
                disableStdin: true,
                convertEol: true,
                scrollback: 2000,
            });

            term.loadAddon(fitAddon);
            term.open(termDiv);
            fitAddon.fit();

            // Refit on resize
            const ro = new ResizeObserver(() => fitAddon?.fit());
            ro.observe(container);
        }

        // ── write helpers ─────────────────────────────────────
        function write(text) {
            if (!term) return;
            // Replace \n with \r\n for xterm
            term.write(text.replace(/\n/g, '\r\n'));
        }

        function writeln(text) {
            write(text + '\r\n');
        }

        // ── run ───────────────────────────────────────────────
        async function run(language, code) {
            if (running) {
                writeln(ANSI.yellow('⚠ Already running. Wait for completion.'));
                return;
            }

            await initTerm();
            term.clear();
            stdinBuf = [];
            running  = true;

            // Show stdin row
            stdinRow.style.display = 'flex';
            stdinInput.value = '';

            opts.onStatus('Running…');
            writeln(ANSI.gray(`▶ Running ${language}…\r\n`));

            // Build form data
            const body = new URLSearchParams({
                language:  language,
                code:      code,
                stdin:     stdinBuf.join('\n'),
                _token:    opts.csrfToken,
            });

            try {
                const response = await fetch(opts.endpoint, {
                    method:  'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'Accept': 'text/event-stream' },
                    body:    body.toString(),
                });

                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}`);
                }

                const reader  = response.body.getReader();
                const decoder = new TextDecoder();
                let   buffer  = '';

                while (true) {
                    const { done, value } = await reader.read();
                    if (done) break;

                    buffer += decoder.decode(value, { stream: true });

                    // SSE lines: "data: {...}\n\n"
                    const lines = buffer.split('\n');
                    buffer = lines.pop(); // keep incomplete line

                    for (const line of lines) {
                        const trimmed = line.trim();
                        if (!trimmed.startsWith('data:')) continue;
                        const raw = trimmed.slice(5).trim();
                        if (!raw) continue;

                        let evt;
                        try {
                            evt = JSON.parse(raw);
                        } catch {
                            write(raw);
                            continue;
                        }

                        handleEvent(evt);
                    }
                }

            } catch (err) {
                writeln(ANSI.red(`\r\n✗ Error: ${err.message}`));
            } finally {
                running = false;
                stdinRow.style.display = 'none';
                opts.onStatus('Done');
            }
        }

        function handleEvent(evt) {
            switch (evt.type) {
                case 'stdout':
                    write(evt.data);
                    break;

                case 'stderr':
                    write(ANSI.red(evt.data));
                    break;

                case 'exit':
                    writeln('');
                    if (evt.code === 0) {
                        writeln(ANSI.green(`\r\n✓ Exited with code 0`));
                    } else {
                        writeln(ANSI.red(`\r\n✗ Exited with code ${evt.code}`));
                    }
                    opts.onDone(evt.code);
                    break;

                case 'done':
                    // Final confirmation from controller
                    break;

                case 'error':
                    writeln(ANSI.red(`\r\n✗ ${evt.message}`));
                    opts.onDone(-1);
                    break;

                default:
                    break;
            }
        }

        // ── stdin ─────────────────────────────────────────────
        function sendStdin(text) {
            if (!text && text !== '0') return;
            stdinInput.value = '';
            // Echo to terminal
            writeln(ANSI.cyan(`› ${text}`));
            // NOTE: stdin is passed with initial request.
            // For true interactive stdin, the user must re-run with input pre-filled.
            // We show it visually but note it's pre-run stdin.
            stdinBuf.push(text);
        }

        // ── clear ─────────────────────────────────────────────
        function clear() {
            term?.clear();
        }

        // ── dispose ───────────────────────────────────────────
        function dispose() {
            term?.dispose();
            term     = null;
            fitAddon = null;
        }

        return { run, sendStdin, clear, dispose };
    }

    // ── Inline code block bootstrapper ───────────────────────
    /**
     * ceRunBlock(blockId, language, code, endpoint, csrf)
     * Called from within each code block's Run button.
     * Finds or creates the terminal container below the block.
     */
    function runBlock(blockId, language, code, endpoint, csrf) {
        const blockEl = document.getElementById(`block-${blockId}`);
        if (!blockEl) return;

        let termWrap = blockEl.querySelector('.ce-block-terminal');
        if (!termWrap) {
            termWrap = document.createElement('div');
            termWrap.className = 'ce-block-terminal';
            termWrap.style.cssText = 'margin-top:12px;height:240px;border-radius:8px;overflow:hidden;border:1px solid #30363d;';
            blockEl.appendChild(termWrap);
        }

        // Get or create runner for this block
        if (!blockEl._ceRunner) {
            blockEl._ceRunner = create(termWrap, { endpoint, csrfToken: csrf });
        }

        blockEl._ceRunner.run(language, code);
    }

    global.CeRunner = { create, runBlock };

})(window);
