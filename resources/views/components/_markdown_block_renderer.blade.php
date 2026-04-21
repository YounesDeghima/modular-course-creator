{{--
    _markdown_block_renderer.blade.php
    ───────────────────────────────────
    Drop-in student renderer for `markdown` blocks.
    Uses marked.js (local) + MathJax 3 (local).

    Include this once in your student/preview block view, e.g. preview/blocks.blade.php:

        @include('partials.markdown_block_renderer')

    Then in your blocks @foreach, add the markdown case:

        @case('markdown')
            <div class="block-markdown-view" data-md="{{ e($block->content) }}"></div>
            @break
--}}

{{-- ── Script tags — load once per page ──────────────────────────────────── --}}
@once
    <script src="{{ asset('vendors/marked.min.js') }}"
            onerror="document.head.insertAdjacentHTML('beforeend',
              '<script src=\'https://cdn.jsdelivr.net/npm/marked@9/marked.min.js\'><\/script>')">
    </script>

    <script>
    window.MathJax = {
        tex: {
            inlineMath:  [['$','$'], ['\\(','\\)']],
            displayMath: [['$$','$$'], ['\\[','\\]']],
            processEscapes: true,
        },
        options: {
            skipHtmlTags: ['script','noscript','style','textarea','pre'],
        },
        startup: { typeset: false },
    };
    </script>
    <script src="{{ asset('vendors/mathjax/tex-chtml.js') }}"
            onerror="document.head.insertAdjacentHTML('beforeend',
              '<script src=\'https://cdn.jsdelivr.net/npm/mathjax@3/es5/tex-chtml.js\'><\/script>')">
    </script>

    <style>
    .block-markdown-view {
        font-family: 'Geist', sans-serif;
        font-size: 15px;
        line-height: 1.75;
        color: var(--text);
        max-width: 100%;
        overflow-wrap: break-word;
    }
    .block-markdown-view h1 { font-size: 1.5em; font-weight: 700; margin: .6em 0 .3em; border-bottom: 1px solid var(--border); padding-bottom: .2em; }
    .block-markdown-view h2 { font-size: 1.25em; font-weight: 600; margin: .55em 0 .25em; }
    .block-markdown-view h3 { font-size: 1.1em; font-weight: 600; margin: .45em 0 .2em; }
    .block-markdown-view p  { margin: .5em 0; }
    .block-markdown-view a  { color: var(--accent); text-decoration: underline; }
    .block-markdown-view code {
        font-family: 'JetBrains Mono', monospace;
        background: var(--bg-subtle);
        border: 1px solid var(--border);
        border-radius: 4px;
        padding: 1px 5px;
        font-size: .88em;
    }
    .block-markdown-view pre {
        background: #1e1e2e;
        border-radius: 8px;
        padding: 14px 16px;
        overflow-x: auto;
        margin: .6em 0;
    }
    .block-markdown-view pre code {
        background: none;
        border: none;
        color: #cdd6f4;
        font-size: .88em;
        padding: 0;
    }
    .block-markdown-view blockquote {
        border-left: 3px solid var(--accent);
        margin: .6em 0;
        padding: 6px 14px;
        background: var(--bg-subtle);
        border-radius: 0 6px 6px 0;
        color: var(--text-muted);
        font-style: italic;
    }
    .block-markdown-view table {
        border-collapse: collapse;
        width: 100%;
        font-size: .9em;
        margin: .6em 0;
        overflow-x: auto;
        display: block;
    }
    .block-markdown-view th,
    .block-markdown-view td {
        border: 1px solid var(--border);
        padding: 7px 12px;
        text-align: left;
    }
    .block-markdown-view th { background: var(--bg-subtle); font-weight: 600; }
    .block-markdown-view tr:nth-child(even) td { background: var(--bg-subtle); }
    .block-markdown-view ul, .block-markdown-view ol { padding-left: 1.6em; margin: .4em 0; }
    .block-markdown-view li { margin: .25em 0; }
    .block-markdown-view hr { border: none; border-top: 1px solid var(--border); margin: 1em 0; }
    .block-markdown-view img { max-width: 100%; border-radius: 6px; }
    /* MathJax display math centering */
    .block-markdown-view .MathJax_Display { overflow-x: auto; }
    </style>

    <script>
    // Render all .block-markdown-view elements on the page
    function renderAllMarkdownBlocks() {
        const els = document.querySelectorAll('.block-markdown-view[data-md]');
        els.forEach(el => {
            const raw = el.dataset.md || '';
            if (typeof marked !== 'undefined') {
                el.innerHTML = marked.parse(raw);
            } else {
                el.innerHTML = raw.replace(/\n/g, '<br>');
            }
            el.removeAttribute('data-md'); // prevent double-render
        });

        // Single MathJax pass after all blocks are rendered
        if (window.MathJax && MathJax.typesetPromise) {
            MathJax.typesetPromise(
                Array.from(document.querySelectorAll('.block-markdown-view'))
            ).catch(console.warn);
        }
    }

    // Run after DOM + MathJax are both ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => setTimeout(renderAllMarkdownBlocks, 100));
    } else {
        setTimeout(renderAllMarkdownBlocks, 100);
    }

    // Re-run when Livewire swaps content
    document.addEventListener('livewire:navigated', () => setTimeout(renderAllMarkdownBlocks, 150));
    document.addEventListener('livewire:morph',     () => setTimeout(renderAllMarkdownBlocks, 150));
    </script>
@endonce
