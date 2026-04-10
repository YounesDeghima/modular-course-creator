@extends('layouts.edditor')
@section('css')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="stylesheet" href="{{asset('css/modular-site-preview.css')}}">
    <link rel="stylesheet" href="{{asset('css/modular-site-courses.css')}}">
@endsection


{{--// TODO: fix this whole vew--}}

@section('main')
    <div class="admin-main-top">
        <h2>Courses</h2>
    </div>

    {{-- New course popup --}}
    <div class="p-8 bg-gray-100 min-h-screen flex flex-col items-center">
        <h1 class="text-2xl font-bold mb-4">Course Generator</h1>

        <form id="pdfForm" action="{{ route('admin.pdf.jsonify') }}" method="POST" enctype="multipart/form-data" class="bg-white p-6 rounded-lg shadow-md">
            @csrf
            <label class="block mb-2 text-sm font-medium text-gray-900">Test AI Connection</label>

            <input type="file" name="pdf_file" id="pdf_file" class="mb-4 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"/>

            <div class="flex gap-4">
                <button type="button" id="testHiBtn" class="flex-1 bg-gray-600 text-white py-2 px-4 rounded hover:bg-gray-700 transition">
                    Test "Hi" (Normal Text)
                </button>

                <button type="submit" id="submitBtn" class="flex-1 bg-blue-600 text-white py-2 px-4 rounded hover:bg-blue-700 transition">
                    JSONify File (PDF)
                </button>
            </div>
        </form>

        <div id="resultArea" class="mt-8 p-6 bg-white rounded-lg shadow-inner hidden">
            <h2 class="text-xl font-semibold mb-4 text-gray-800">Preview AI Generated Course</h2>
            <pre id="jsonOutput" class="bg-gray-900 text-green-400 p-4 rounded-lg overflow-auto max-h-96 text-xs"></pre>

            <div class="mt-6 flex justify-end">
                <button id="saveBtn" onclick="saveToDatabase()" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-6 rounded-lg transition-all shadow-lg flex items-center">
                    <span id="saveBtnText">Save to Database</span>
                </button>
            </div>
        </div>

        <div id="debugArea" class="mt-8 p-6 bg-blue-50 border-l-4 border-blue-500 rounded-lg hidden">
            <h3 class="text-lg font-bold text-blue-800 mb-2">Reviewing Data for AI</h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <span class="text-sm font-semibold text-gray-600 uppercase">Extracted PDF Text (Preview)</span>
                    <div id="pdfTextPreview" class="mt-1 p-3 bg-white border rounded text-xs text-gray-700 h-40 overflow-auto italic"></div>
                </div>
                <div>
                    <span class="text-sm font-semibold text-gray-600 uppercase">Full Prompt Sent to Ollama</span>
                    <div id="finalPromptPreview" class="mt-1 p-3 bg-white border rounded text-xs text-gray-700 h-40 overflow-auto font-mono"></div>
                </div>
            </div>
        </div>

        <div  id="resultArea" class="mt-8 w-full max-w-2xl hidden">
            <h2 class="text-lg font-semibold mb-2">Resulting JSON:</h2>
            <pre id="jsonOutput" class="bg-black text-green-400 p-4 rounded overflow-x-auto text-xs"></pre>
        </div>
    </div>


    <livewire:coursecreate/>

    <livewire:courses :courses="$courses"/>
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
    <script>
        const JSONIFY_URL  = "{{ route('admin.pdf.jsonify') }}";
        const CSRF         = document.querySelector('meta[name="csrf-token"]').content;
        const output       = document.getElementById('jsonOutput');
        const resultArea   = document.getElementById('resultArea');
        //
        // function showResult(data) {
        //     // Ollama wraps response in { response: "..." } when format=json
        //     // but the actual JSON string is inside .response
        //     let display;
        //     try {
        //         const inner = data.response ? JSON.parse(data.response) : data;
        //         display = JSON.stringify(inner, null, 2);
        //     } catch {
        //         display = JSON.stringify(data, null, 2);
        //     }
        //     output.innerText = display;
        //     resultArea.classList.remove('hidden');
        // }

        function showError(msg) {
            output.innerText = '❌ ' + msg;
            resultArea.classList.remove('hidden');
        }

        // ── Test "Hi" button ──
        document.getElementById('testHiBtn').addEventListener('click', async () => {
            const btn = document.getElementById('testHiBtn');
            btn.innerText = 'Testing...';
            btn.disabled  = true;

            try {
                const res = await fetch(JSONIFY_URL, {
                    method:  'POST',
                    headers: {
                        'Content-Type':  'application/json',
                        'Accept':        'application/json',
                        'X-CSRF-TOKEN':  CSRF,
                    },
                    body: JSON.stringify({ test_mode: 'hi' }),
                });

                const data = await res.json();
                if (!res.ok) { showError(data.message || data.error || res.statusText); return; }
                showResult(data);

            } catch (e) {
                showError('Fetch failed: ' + e.message);
            } finally {
                btn.innerText = "Test \"Hi\"";
                btn.disabled  = false;
            }
        });

        // ── JSONify PDF ──
        document.getElementById('pdfForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const btn = document.getElementById('submitBtn');
            btn.innerText = 'Processing...';
            btn.disabled  = true;

            const formData = new FormData(e.target); // already contains csrf + pdf_file

            try {
                const res = await fetch(JSONIFY_URL, {
                    method:  'POST',
                    headers: {
                        // NO Content-Type here — browser sets multipart boundary automatically
                        'Accept':       'application/json',
                        'X-CSRF-TOKEN': CSRF,
                    },
                    body: formData,
                });

                const data = await res.json();
                if (!res.ok) { showError(data.message || data.error || res.statusText); return; }
                showResult(data);

            } catch (e) {
                showError('Fetch failed: ' + e.message);
            } finally {
                btn.innerText = 'JSONify PDF';
                btn.disabled  = false;
            }
        });

        {{--async function saveToDatabase() {--}}
        {{--    const saveBtn = document.getElementById('saveBtn'); // Add this button to your UI--}}
        {{--    saveBtn.innerText = "Saving to Database...";--}}
        {{--    saveBtn.disabled = true;--}}

        {{--    try {--}}
        {{--        const response = await fetch("{{ route('admin.pdf.store') }}", {--}}
        {{--            method: "POST",--}}
        {{--            headers: {--}}
        {{--                'Content-Type': 'application/json',--}}
        {{--                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content--}}
        {{--            },--}}
        {{--            body: JSON.stringify(currentJson) // This is the global variable where you stored the AI response--}}
        {{--        });--}}

        {{--        const result = await response.json();--}}
        {{--        if (result.success) {--}}
        {{--            alert("Success! Course and all modules saved.");--}}
        {{--            window.location.reload();--}}
        {{--        } else {--}}
        {{--            alert("Error: " + result.message);--}}
        {{--        }--}}
        {{--    } catch (error) {--}}
        {{--        console.error(error);--}}
        {{--        alert("Failed to reach server.");--}}
        {{--    } finally {--}}
        {{--        saveBtn.innerText = "Save to Database";--}}
        {{--        saveBtn.disabled = false;--}}
        {{--    }--}}
        {{--}--}}


        let currentJson = null; // To store the AI result globally

        // This function runs when the AI finishes
        function showResult(data) {
            try {
                // Ollama often wraps the JSON in a "response" string
                currentJson = data.response ? JSON.parse(data.response) : data;
                document.getElementById('jsonOutput').innerText = JSON.stringify(currentJson, null, 2);
                document.getElementById('resultArea').classList.remove('hidden');
            } catch (e) {
                console.error("JSON Parse Error:", e);
                document.getElementById('jsonOutput').innerText = "AI returned text, but it's not valid JSON. Try again.";
            }
        }

        async function saveToDatabase() {
            if (!currentJson) return alert("No data to save!");

            const btn = document.getElementById('saveBtn');
            const btnText = document.getElementById('saveBtnText');

            btn.disabled = true;
            btnText.innerText = "Writing to Database...";

            try {
                const response = await fetch("{{ route('admin.pdf.store') }}", {
                    method: "POST",
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify(currentJson)
                });

                const result = await response.json();

                if (result.success) {
                    alert("Success! Everything saved to database.");
                    window.location.reload(); // Refresh to see your new course in the list
                } else {
                    alert("Error: " + result.message);
                }
            } catch (error) {
                alert("Failed to connect to server. Check your terminal!");
            } finally {
                btn.disabled = false;
                btnText.innerText = "Save to Database";
            }
        }

        pdfForm.onsubmit = async (e) => {
            e.preventDefault();
            const btn = document.getElementById('submitBtn');
            const debugArea = document.getElementById('debugArea');
            const pdfPreview = document.getElementById('pdfTextPreview');
            const promptPreview = document.getElementById('finalPromptPreview');

            btn.innerText = "Reading PDF & Generating...";
            btn.disabled = true;

            const formData = new FormData(e.target);

            try {
                const response = await fetch("{{ route('admin.pdf.jsonify') }}", {
                    method: "POST",
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });

                const data = await response.json();

                // 1. Show the Debug info returned from the server
                if(data.debug) {
                    pdfPreview.innerText = data.debug.text_sample + "...";
                    promptPreview.innerText = data.debug.full_prompt;
                    debugArea.classList.remove('hidden');
                }

                // 2. Show the actual AI result
                showResult(data);

            } catch (error) {
                alert("Check your RTX 3060 connection/Ollama status.");
            } finally {
                btn.innerText = "JSONify File (PDF)";
                btn.disabled = false;
            }
        };
    </script>

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
@endsection
