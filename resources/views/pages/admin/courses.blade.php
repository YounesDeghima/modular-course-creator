@extends('layouts.edditor')
@section('css')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="stylesheet" href="{{asset('css/modular-site-preview.css')}}">
    <link rel="stylesheet" href="{{asset('css/modular-site-courses.css')}}">

    <style>

    .ai-generator-wrap {
    max-width: 860px;
    margin: 0 auto 2.5rem;
    padding: 0 1rem;
    font-family: inherit;
    }

    .ai-generator-header {
    display: flex;
    align-items: center;
    gap: .75rem;
    margin-bottom: 1.25rem;
    }
    .ai-generator-header h2 { margin: 0; font-size: 1.35rem; font-weight: 700; }
    .ai-generator-header p  { margin: 0; color: #6b7280; font-size: .875rem; }
    .ai-badge {
    background: linear-gradient(135deg, #6366f1, #8b5cf6);
    color: #fff;
    font-size: .7rem;
    font-weight: 700;
    letter-spacing: .05em;
    padding: .2rem .55rem;
    border-radius: 999px;
    white-space: nowrap;
    }

    .ai-card {
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: .75rem;
    padding: 1.25rem 1.5rem;
    margin-bottom: 1rem;
    box-shadow: 0 1px 3px rgba(0,0,0,.06);
    }
    .ai-card-title { font-weight: 700; font-size: 1rem; margin-bottom: .4rem; }
    .ai-card-sub   { color: #6b7280; font-size: .875rem; margin: 0 0 .9rem; }

    .ai-field-row {
    display: flex;
    align-items: center;
    gap: .75rem;
    flex-wrap: wrap;
    margin-bottom: .85rem;
    }
    .ai-label { font-size: .85rem; font-weight: 600; color: #374151; white-space: nowrap; }
    .ai-select {
    border: 1px solid #d1d5db;
    border-radius: .4rem;
    padding: .35rem .6rem;
    font-size: .875rem;
    background: #f9fafb;
    cursor: pointer;
    }
    .ai-file-input {
    flex: 1;
    font-size: .875rem;
    border: 1px dashed #d1d5db;
    border-radius: .4rem;
    padding: .4rem .7rem;
    background: #f9fafb;
    cursor: pointer;
    min-width: 0;
    }

    /* Buttons */
    .ai-btn {
    display: inline-flex;
    align-items: center;
    gap: .4rem;
    padding: .5rem 1.1rem;
    border-radius: .5rem;
    font-size: .875rem;
    font-weight: 600;
    cursor: pointer;
    border: none;
    transition: opacity .15s, transform .1s;
    }
    .ai-btn:disabled { opacity: .55; cursor: not-allowed; }
    .ai-btn:not(:disabled):active { transform: scale(.97); }

    .ai-btn--primary  { background: #6366f1; color: #fff; }
    .ai-btn--primary:not(:disabled):hover  { background: #4f46e5; }
    .ai-btn--success  { background: #10b981; color: #fff; }
    .ai-btn--success:not(:disabled):hover  { background: #059669; }
    .ai-btn--outline  { background: #fff; color: #374151; border: 1px solid #d1d5db; }
    .ai-btn--outline:not(:disabled):hover  { background: #f3f4f6; }
    .ai-btn--ghost    { background: transparent; color: #6b7280; border: 1px solid #e5e7eb; }
    .ai-btn--ghost:not(:disabled):hover    { background: #f9fafb; }

    /* Progress bar */
    .ai-progress-bar-wrap {
    height: 8px;
    background: #e5e7eb;
    border-radius: 999px;
    overflow: hidden;
    margin-bottom: .6rem;
    }
    .ai-progress-bar {
    height: 100%;
    background: linear-gradient(90deg, #6366f1, #8b5cf6);
    border-radius: 999px;
    width: 0%;
    transition: width .5s ease;
    animation: ai-pulse 1.6s ease-in-out infinite;
    }
    @keyframes ai-pulse {
    0%,100% { opacity: 1; }
    50%      { opacity: .6; }
    }
    .ai-progress-msg  { font-size: .9rem; font-weight: 600; color: #374151; margin: 0 0 .3rem; }
    .ai-progress-hint { font-size: .8rem; color: #9ca3af; margin: 0; }

    /* JSON preview */
    .ai-json-preview {
    background: #1e1e2e;
    color: #cdd6f4;
    border-radius: .5rem;
    padding: 1rem;
    font-size: .78rem;
    line-height: 1.55;
    overflow: auto;
    max-height: 380px;
    white-space: pre-wrap;
    word-break: break-word;
    margin-bottom: 1rem;
    }
    .ai-result-actions { display: flex; gap: .75rem; flex-wrap: wrap; }

    /* Generic result / feedback box */
    .ai-result {
    margin-top: .75rem;
    padding: .7rem 1rem;
    border-radius: .45rem;
    font-size: .875rem;
    white-space: pre-wrap;
    word-break: break-word;
    }
    .ai-result.ok    { background: #d1fae5; color: #065f46; border: 1px solid #a7f3d0; }
    .ai-result.error { background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; }

    .hidden { display: none !important; }
    </style>


    {{-- ═══════════════════════════════════════════════════════════════════════
         JavaScript
         ═══════════════════════════════════════════════════════════════════════ --}}

@endsection


{{--// TODO: fix this whole vew--}}

@section('main')
    <div class="admin-main-top">
        <h2>Courses</h2>
    </div>

    {{-- New course popup --}}

    <div class="ai-generator-wrap">

        <div class="ai-generator-header">
            <span class="ai-badge">⚡ AI</span>
            <h2>PDF → Course Generator</h2>
            <p>Upload a teacher's PDF — phi4 will extract every word into structured course blocks.</p>
        </div>

        {{-- ── Test connection ─────────────────────────────────────────────── --}}
        <div class="ai-card">
            <div class="ai-card-title">🔌 Connection test</div>
            <p class="ai-card-sub">Check that Ollama (phi4) is running locally before uploading a PDF.</p>
            <button id="testBtn" class="ai-btn ai-btn--outline">Test Ollama connection</button>
            <div id="testResult" class="ai-result hidden"></div>
        </div>

        {{-- ── Upload form ─────────────────────────────────────────────────── --}}
        <div class="ai-card">
            <div class="ai-card-title">📄 Upload PDF</div>

            <form id="pdfUploadForm" enctype="multipart/form-data">
                @csrf

                <div class="ai-field-row">
                    <label class="ai-label">Year</label>
                    <select name="course_year" class="ai-select year-input" id="aiYear">
                        <option value="1">Year 1</option>
                        <option value="2">Year 2</option>
                        <option value="3">Year 3</option>
                    </select>

                    <label class="ai-label branch-label" id="aiBranchLabel">Branch</label>
                    <select name="course_branch" class="ai-select branch-input" id="aiBranch">
                        <option value="none">None</option>
                        <option value="mi">MI</option>
                        <option value="st">ST</option>
                    </select>
                </div>

                <div class="ai-field-row">
                    <label class="ai-label">PDF file</label>
                    <input type="file" name="pdf_file" id="pdfFile" accept=".pdf" class="ai-file-input">
                </div>

                <button type="submit" id="uploadBtn" class="ai-btn ai-btn--primary">
                    ✨ JSONify PDF
                </button>
            </form>
        </div>

        {{-- ── Progress / polling ──────────────────────────────────────────── --}}
        <div id="progressCard" class="ai-card hidden">
            <div class="ai-card-title">⏳ Processing</div>
            <div class="ai-progress-bar-wrap">
                <div class="ai-progress-bar" id="progressBar"></div>
            </div>
            <p id="progressMsg" class="ai-progress-msg">Queued — waiting for the worker…</p>
            <p class="ai-progress-hint">
                Phi4 runs locally — large PDFs can take a few minutes. This page will update automatically.
            </p>
        </div>

        {{-- ── Result preview ──────────────────────────────────────────────── --}}
        <div id="resultCard" class="ai-card hidden">
            <div class="ai-card-title">✅ Result preview</div>
            <p class="ai-card-sub">
                Review the extracted structure. If it looks correct, click <strong>Save to database</strong>.
            </p>
            <pre id="jsonPreview" class="ai-json-preview"></pre>
            <div class="ai-result-actions">
                <button id="saveBtn" class="ai-btn ai-btn--success">💾 Save to database</button>
                <button id="discardBtn" class="ai-btn ai-btn--ghost">🗑 Discard</button>
            </div>
            <div id="saveResult" class="ai-result hidden"></div>
        </div>

    </div>



    <livewire:course.coursecreate/>


    <livewire:course.courses :courses="$courses" />
@endsection


@section('sidebar-elements')
    <div class="admin-sb-section">
        <button class="admin-new-btn" id="open-popup-btn">+ New course</button>
    </div>

    <div class="admin-sb-divider"></div>

    <livewire:overviewstats/>

    <div class="admin-sb-divider"></div>

    <div class="admin-sb-section">
        <div class="admin-sb-label">Filter</div>
        <button class="admin-filter-btn active" data-filter="all">All courses</button>
        <button class="admin-filter-btn" data-filter="published">Published</button>
        <button class="admin-filter-btn" data-filter="draft">Draft</button>
        <button class="admin-filter-btn" data-filter="year-1">Year 1</button>
        <button class="admin-filter-btn" data-filter="year-2">Year 2</button>
        <button class="admin-filter-btn" data-filter="year-3">Year 3</button>
    </div>

    <div class="admin-sb-divider"></div>

    <div class="admin-sb-section">
        <div class="admin-sb-label">Bulk actions</div>
        <form action="{{ route('admin.courses.toggle-everything') }}" method="POST">
            @csrf
            @method('PUT')
            <button type="button" class="btn-global-toggle" id="btn-global-toggle">
                🌍 Toggle all visibility
            </button>
        </form>
    </div>
@endsection

@section('js')

    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <script>
        axios.defaults.headers.common['X-CSRF-TOKEN'] =
            document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        const adder = document.getElementById('block-popup');
        const openBtn = document.getElementById('open-popup-btn');
        const closeBtn = document.getElementById('close-popup');

        openBtn.addEventListener('click', () => {
            adder.style.visibility = 'visible';
            adder.style.opacity = 1;
        });

        // ── Branch visibility per form ──
        // document.querySelectorAll('.update-form').forEach(form => {
        //     const year   = form.querySelector('.year-input');
        //     const branch = form.querySelector('.branch-input');
        //     const label  = form.querySelector('.branch-label');
        //
        //     function toggleBranch() {
        //         const show = parseInt(year.value) > 1;
        //         branch.style.visibility = show ? 'visible' : 'hidden';
        //         label.style.visibility  = show ? 'visible' : 'hidden';
        //     }
        //
        //     year.addEventListener('change', toggleBranch);
        //     toggleBranch();
        // });

        // Also for the new course popup
        const newYearSelect = document.querySelector('#new-block-form .year-input');
        const newBranchSelect = document.querySelector('#new-block-form .branch-input');
        const newBranchLabel = document.querySelector('#new-block-form .branch-label');

        if (newYearSelect) {
            function toggleNewBranch() {
                const show = parseInt(newYearSelect.value) > 1;
                newBranchSelect.style.visibility = show ? 'visible' : 'hidden';
                newBranchLabel.style.visibility  = show ? 'visible' : 'hidden';
            }
            newYearSelect.addEventListener('change', toggleNewBranch);
            toggleNewBranch();
        }

        // ── Show save button only when form changed ──
        // document.querySelectorAll('.update-form').forEach(form => {
        //     const inputs    = form.querySelectorAll('input, textarea, select');
        //     const updateBtn = form.querySelector('input[type="submit"]');
        //     const originals = Array.from(inputs).map(i => i.value);
        //
        //     updateBtn.style.visibility = 'hidden';
        //
        //     inputs.forEach((input, idx) => {
        //         ['input', 'change'].forEach(evt => {
        //             input.addEventListener(evt, () => {
        //                 const changed = Array.from(inputs).some((inp, i) => inp.value !== originals[i]);
        //                 updateBtn.style.visibility = changed ? 'visible' : 'hidden';
        //             });
        //         });
        //     });
        // });

        // ── Status toggle (FIXED) ──
        // function toggleSingleCourse(btn, event) {
        //     if (event) event.stopPropagation();
        //
        //     const courseId    = btn.dataset.courseId;
        //     const currentStatus = btn.dataset.status;
        //     const newStatus   = currentStatus === 'published' ? 'draft' : 'published';
        //
        //     updateButtonUI(btn, newStatus);
        //
        //     // Fix: go up to .block first, then find the form
        //     const block  = btn.closest('.block');
        //     const form   = block.querySelector('.update-form');
        //
        //     // Keep hidden input + data-status in sync for filters
        //     const hiddenStatus = form.querySelector('input[name="status"]');
        //     if (hiddenStatus) hiddenStatus.value = newStatus;
        //     block.dataset.status = newStatus;
        //
        //     const payload = {
        //         status:      newStatus,
        //         title:       form.querySelector('input[name="title"]').value,
        //         year:        form.querySelector('.year-input').value,
        //         branch:      form.querySelector('.branch-input').value,
        //         description: form.querySelector('textarea').value,
        //     };
        //
        //     axios.put(`/admin/courses/${courseId}`, payload)
        //         .then(() => refreshGlobalUI())
        //         .catch(err => console.error('Toggle failed:', err));
        // }

        function updateButtonUI(btn, status) {
            btn.dataset.status = status;
            btn.innerText = status.charAt(0).toUpperCase() + status.slice(1);
            btn.classList.remove('published', 'draft');
            btn.classList.add(status);
        }

        // ── Delete ──
        // function deleteCourse(courseId, btn) {
        //     if (!confirm('Are you sure you want to delete this course?')) return;
        //
        //     const block = btn.closest('.block');
        //
        //     axios.delete(`/admin/courses/${courseId}`)
        //         .finally(() => {
        //             block.remove();
        //             refreshGlobalUI();
        //         });
        // }

        // ── Refresh sidebar counters ──
        function refreshGlobalUI() {
            const allBtns   = document.querySelectorAll('.status-toggle-btn');
            const allBlocks = document.querySelectorAll('.block');
            let published = 0, draft = 0;

            allBtns.forEach(b => {
                if (b.dataset.status === 'published') published++;
                else draft++;
            });

            const totalEl     = document.getElementById('total-courses');
            const pubEl       = document.getElementById('published-count');
            const draftEl     = document.getElementById('draft-count');

            if (totalEl) totalEl.innerText = allBlocks.length;
            if (pubEl)   pubEl.innerText   = published;
            if (draftEl) draftEl.innerText = draft;

            updateGlobalButtonUI();
        }

        function updateGlobalButtonUI() {
            const globalBtn = document.querySelector('.btn-global-toggle');
            const allBtns   = document.querySelectorAll('.status-toggle-btn');
            if (!globalBtn || !allBtns.length) return;

            const hasAnyDrafts = Array.from(allBtns).some(b => b.dataset.status === 'draft');
            globalBtn.innerText = hasAnyDrafts ? '🚀 Publish all courses' : '📂 Move all to draft';
        }

        document.addEventListener('DOMContentLoaded', updateGlobalButtonUI);

        // ── Popup open/close (FIXED) ──
        document.getElementById('open-popup-btn').addEventListener('click', () => {
            document.getElementById('block-popup').classList.toggle('open');
        });
        document.getElementById('close-popup').addEventListener('click', () => {
            document.getElementById('block-popup').classList.remove('open');
        });

        // ── Sidebar filters (FIXED — reads live data-status) ──
        document.querySelectorAll('.admin-filter-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                document.querySelectorAll('.admin-filter-btn')
                    .forEach(b => b.classList.remove('active'));
                btn.classList.add('active');

                const filter = btn.dataset.filter;

                document.querySelectorAll('.block').forEach(block => {
                    const status = block.dataset.status;
                    const year   = block.dataset.year;

                    const show =
                        filter === 'all' ||
                        (filter === 'published'    && status === 'published') ||
                        (filter === 'draft'        && status === 'draft') ||
                        (filter === `year-${year}`);

                    block.style.display = show ? 'flex' : 'none';
                });
            });
        });




        const globalBtn = document.getElementById('btn-global-toggle');

        globalBtn.addEventListener('click', () => {
            const allBlocks = document.querySelectorAll('.block');
            const allBtns   = document.querySelectorAll('.status-toggle-btn');

            if (!allBlocks.length) return;

            // Check if any draft exists
            const hasDraft = Array.from(allBtns).some(b => b.dataset.status === 'draft');

            // Decide target state
            const newStatus = hasDraft ? 'published' : 'draft';

            globalBtn.innerText = "Updating...";
            globalBtn.disabled = true;

            axios.put('/admin/courses/toggle-everything', {
                status: newStatus
            })
                .then(() => {
                    // 🔥 Update ALL UI instantly
                    allBlocks.forEach(block => {
                        const btn  = block.querySelector('.status-toggle-btn');
                        const form = block.querySelector('.update-form');

                        // Update button
                        updateButtonUI(btn, newStatus);

                        // Update dataset (for filters)
                        block.dataset.status = newStatus;

                        // Update hidden input
                        const hidden = form.querySelector('input[name="status"]');
                        if (hidden) hidden.value = newStatus;
                    });

                    refreshGlobalUI();
                })
                .catch(err => {
                    console.error('Bulk toggle failed:', err);
                })
                .finally(() => {
                    globalBtn.disabled = false;
                    updateGlobalButtonUI();
                });
        });
    </script>

    <script>
        (() => {
            'use strict';

            const CSRF         = document.querySelector('meta[name="csrf-token"]').content;
            const TEST_URL     = "{{ route('admin.ai.test') }}";
            const JSONIFY_URL  = "{{ route('admin.ai.jsonify') }}";
            const STATUS_URL   = "{{ route('admin.ai.status', ['id' => '__ID__']) }}";
            const STORE_URL    = "{{ route('admin.ai.store') }}";

            // ── Element refs ──────────────────────────────────────────────────────
            const testBtn      = document.getElementById('testBtn');
            const testResult   = document.getElementById('testResult');
            const uploadForm   = document.getElementById('pdfUploadForm');
            const uploadBtn    = document.getElementById('uploadBtn');
            const progressCard = document.getElementById('progressCard');
            const progressBar  = document.getElementById('progressBar');
            const progressMsg  = document.getElementById('progressMsg');
            const resultCard   = document.getElementById('resultCard');
            const jsonPreview  = document.getElementById('jsonPreview');
            const saveBtn      = document.getElementById('saveBtn');
            const discardBtn   = document.getElementById('discardBtn');
            const saveResult   = document.getElementById('saveResult');
            const yearSel      = document.getElementById('aiYear');
            const branchLabel  = document.getElementById('aiBranchLabel');
            const branchSel    = document.getElementById('aiBranch');

            let currentJobId   = null;
            let pollTimer      = null;

            // ── Year → branch visibility ──────────────────────────────────────────
            function syncBranchVisibility() {
                const show = parseInt(yearSel.value) > 1;
                branchLabel.style.visibility = show ? 'visible' : 'hidden';
                branchSel.style.visibility   = show ? 'visible' : 'hidden';
                if (!show) branchSel.value = 'none';
            }
            yearSel.addEventListener('change', syncBranchVisibility);
            syncBranchVisibility();

            // ── Helpers ───────────────────────────────────────────────────────────
            function showFeedback(el, msg, type /* 'ok'|'error' */) {
                el.textContent = msg;
                el.className   = 'ai-result ' + type;
                el.classList.remove('hidden');
            }

            function setProgress(pct, msg) {
                progressBar.style.width = pct + '%';
                progressMsg.textContent  = msg;
            }

            function stopPolling() {
                if (pollTimer) { clearInterval(pollTimer); pollTimer = null; }
            }

            // ── Test Ollama ───────────────────────────────────────────────────────
            testBtn.addEventListener('click', async () => {
                testBtn.disabled = true;
                testBtn.textContent = 'Testing…';
                testResult.classList.add('hidden');

                try {
                    const res  = await fetch(TEST_URL, {
                        method:  'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
                        body:    JSON.stringify({}),
                    });
                    const data = await res.json();

                    if (res.ok && data.ok) {
                        showFeedback(testResult, '✅ ' + data.message, 'ok');
                    } else {
                        showFeedback(testResult, '❌ ' + (data.error || 'Unknown error'), 'error');
                    }
                } catch (e) {
                    showFeedback(testResult, '❌ Fetch failed: ' + e.message, 'error');
                } finally {
                    testBtn.disabled    = false;
                    testBtn.textContent = 'Test Ollama connection';
                }
            });

            // ── Upload PDF ────────────────────────────────────────────────────────
            uploadForm.addEventListener('submit', async (e) => {
                e.preventDefault();

                const fileInput = document.getElementById('pdfFile');
                if (!fileInput.files.length) {
                    alert('Please choose a PDF file first.');
                    return;
                }

                uploadBtn.disabled    = true;
                uploadBtn.textContent = 'Uploading…';

                const fd = new FormData(uploadForm);

                try {
                    const res  = await fetch(JSONIFY_URL, {
                        method:  'POST',
                        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF },
                        body:    fd,
                    });
                    const data = await res.json();

                    if (!res.ok) {
                        alert('Upload failed: ' + (data.message || data.error || res.statusText));
                        return;
                    }

                    currentJobId = data.job_id;
                    startPolling();

                } catch (e) {
                    alert('Upload error: ' + e.message);
                } finally {
                    uploadBtn.disabled    = false;
                    uploadBtn.textContent = '✨ JSONify PDF';
                }
            });

            // ── Poll for job status ───────────────────────────────────────────────
            function startPolling() {
                resultCard.classList.add('hidden');
                progressCard.classList.remove('hidden');
                setProgress(10, 'Queued — waiting for the worker…');

                pollTimer = setInterval(pollStatus, 3000); // check every 3 s
            }

            async function pollStatus() {
                if (!currentJobId) return;

                const url = STATUS_URL.replace('__ID__', currentJobId);

                try {
                    const res  = await fetch(url, { headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF } });
                    const data = await res.json();

                    switch (data.status) {
                        case 'queued':
                            setProgress(10, 'Queued — waiting for the worker…');
                            break;

                        case 'processing':
                            setProgress(55, 'Phi4 is reading your PDF… (this may take a few minutes)');
                            break;

                        case 'done':
                            stopPolling();
                            setProgress(100, 'Done! Rendering preview…');
                            setTimeout(() => {
                                progressCard.classList.add('hidden');
                                showResult(data.result);
                            }, 600);
                            break;

                        case 'failed':
                            stopPolling();
                            setProgress(100, 'Processing failed.');
                            progressBar.style.background = '#ef4444';
                            progressMsg.textContent = '❌ ' + (data.error || 'Unknown error');
                            break;
                    }
                } catch (e) {
                    // network blip — keep polling
                    console.warn('Poll error:', e.message);
                }
            }

            // ── Show result ───────────────────────────────────────────────────────
            function showResult(json) {
                jsonPreview.textContent = JSON.stringify(json, null, 2);
                saveResult.classList.add('hidden');
                resultCard.classList.remove('hidden');
                resultCard.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }

            // ── Save to database ──────────────────────────────────────────────────
            saveBtn.addEventListener('click', async () => {
                if (!currentJobId) return;

                saveBtn.disabled    = true;
                saveBtn.textContent = 'Saving…';
                saveResult.classList.add('hidden');

                try {
                    const res  = await fetch(STORE_URL, {
                        method:  'POST',
                        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF },
                        body:    JSON.stringify({ job_id: currentJobId }),
                    });
                    const data = await res.json();

                    if (res.ok && data.success) {
                        showFeedback(saveResult,
                            '✅ Course saved as draft (ID ' + data.course_id + '). Reload the page to see it.',
                            'ok'
                        );
                        saveBtn.disabled = true;
                    } else {
                        showFeedback(saveResult, '❌ ' + (data.error || data.message || 'Save failed'), 'error');
                        saveBtn.disabled = false;
                    }
                } catch (e) {
                    showFeedback(saveResult, '❌ ' + e.message, 'error');
                    saveBtn.disabled = false;
                } finally {
                    saveBtn.textContent = '💾 Save to database';
                }
            });

            // ── Discard ───────────────────────────────────────────────────────────
            discardBtn.addEventListener('click', () => {
                stopPolling();
                currentJobId = null;
                resultCard.classList.add('hidden');
                progressCard.classList.add('hidden');
                uploadForm.reset();
                syncBranchVisibility();
            });

        })();
    </script>
@endsection
