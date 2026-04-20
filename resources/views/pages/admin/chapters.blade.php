@if(!request()->ajax())
    @extends('layouts.edditor')
@endif


@section('css')

    <link rel="stylesheet" href="{{asset('css/modular-site.css')}}">


    {{--    <link rel="stylesheet" href="{{asset('css/block-editor.css')}}">--}}
    <link rel="stylesheet" href="{{asset('css/admin-layout.css')}}">
    <style>
        .chapter-modal{
            display: flex;
        }

        [x-cloak] {
            display: none !important;
        }

        .btn-save-all {
            /* Your existing styles */
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }

        .btn-save-all:disabled {
            opacity: 0.7;
            cursor: not-allowed;
        }

        /* Optional: Spinner animation */
        .animate-spin {
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

    </style>
@endsection

@section('back-button')
    <a class="back-button" href="{{route('admin.courses.index')}}">{{$course->title}}</a>
@endsection




@section('main')


    @fragment('main-content')
        <div class="blocks-wrapper">

            <livewire:modular_site.navigation.navigation :course="$course" :chapter="$chapter" :lesson="$lesson"/>



            <livewire:modular_site.block.blocks :course="$course" :chapter="$chapter" :lesson="$lesson" :blocks="$blocks"/>
        </div>

        <div id="block-popup" class="modal-overlay">
            <div class="modal-content">
                <span class="close-btn" onclick="closeModal('block-popup')">&times;</span>
                <h3>Add New Content Block</h3>
                <form method="POST" action="{{route('admin.courses.chapters.lessons.blocks.store', [$course->id, $chapter->id, $lesson->id])}}" enctype="multipart/form-data">
                    @csrf

                    <div class="form-group">
                        <label>Block Type</label>
                        <select name="type" class="modal-input" id="new-block-type" onchange="toggleNewBlockFields(this)">
                            <option value="header">Header</option>
                            <option value="description">Description</option>
                            <option value="note">Note</option>
                            <option value="code">Code</option>
                            <option value="exercise">Exercise</option>
                            <option value="photo">Photo</option>
                            <option value="video">Video</option>
                            <option value="math">Math (LaTeX)</option>
                            <option value="graph">Graph/Chart</option>
                            <option value="table">Table</option>
                            <option value="function">Math Function</option>
                            <option value="ext">HTML/Embed</option>
                        </select>
                    </div>

                    {{-- Text content field (shown for most types) --}}
                    <div class="form-group" id="text-content-group">
                        <label>Initial Content</label>
                        <textarea class="modal-input" name="content" rows="4"></textarea>
                    </div>

                    {{-- File upload for photo/video --}}
                    <div class="form-group" id="file-content-group" style="display:none;">
                        <label>Upload File</label>
                        <input type="file" name="content_file" class="modal-input" style="padding:8px;">
                    </div>

                    {{-- Graph specific fields --}}
                    <div class="form-group" id="graph-content-group" style="display:none;">
                        <label>Chart Type</label>
                        <select name="chart_type" class="modal-input" style="margin-bottom:8px;">
                            <option value="line">Line Chart</option>
                            <option value="bar">Bar Chart</option>
                            <option value="pie">Pie Chart</option>
                        </select>
                        <label style="margin-top:12px;">Chart Data</label>
                        <textarea name="chart_data" class="modal-input" rows="3" placeholder="Jan, Feb, Mar&#10;10, 20, 15">Jan, Feb, Mar&#10;10, 20, 15</textarea>
                        <small style="color:var(--text-faint);font-size:11px;">Line 1: Labels | Line 2: Values (comma separated)</small>
                    </div>

                    {{-- Table specific fields --}}
                    <div class="form-group" id="table-content-group" style="display:none;">
                        <label>Initial Table (JSON format)</label>
                        <textarea name="table_data" class="modal-input" rows="4">[["Header 1","Header 2"],["Row 1 Col 1","Row 1 Col 2"]]</textarea>
                        <small style="color:var(--text-faint);font-size:11px;">Format: [["Header1","Header2"],["Row1Col1","Row1Col2"]]</small>
                    </div>

                    {{-- Function specific fields --}}
                    <div class="form-group" id="function-content-group" style="display:none;">
                        <label>Function f(x)</label>
                        <input type="text" name="func_expression" class="modal-input" value="sin(x)" placeholder="e.g., sin(x), x^2, cos(x)*x" style="font-family:'JetBrains Mono',monospace;margin-bottom:8px;">
                        <div style="display:flex;gap:8px;margin-bottom:8px;">
                            <div style="flex:1;">
                                <label style="font-size:11px;color:var(--text-faint);">X Range</label>
                                <div style="display:flex;gap:4px;">
                                    <input type="number" name="x_min" value="-10" class="modal-input" style="flex:1;" placeholder="Min">
                                    <input type="number" name="x_max" value="10" class="modal-input" style="flex:1;" placeholder="Max">
                                </div>
                            </div>
                            <div style="flex:1;">
                                <label style="font-size:11px;color:var(--text-faint);">Y Range</label>
                                <div style="display:flex;gap:4px;">
                                    <input type="number" name="y_min" value="-5" class="modal-input" style="flex:1;" placeholder="Min">
                                    <input type="number" name="y_max" value="5" class="modal-input" style="flex:1;" placeholder="Max">
                                </div>
                            </div>
                        </div>
                        <label style="font-size:11px;color:var(--text-faint);">Line Color</label>
                        <input type="color" name="func_color" value="#4f46e5" class="modal-input" style="height:40px;padding:4px;">
                        <small style="color:var(--text-faint);font-size:11px;">JavaScript math syntax supported</small>
                    </div>

                    <div class="form-group">
                        <label>Block Number</label>
                        <input style="visibility: hidden" class="modal-input" type="number" name="block_number" value="{{ $lesson->blocks->count() + 1 }}" min="1" required>
                    </div>

                    <button type="submit" class="btn-update">Create Block</button>
                </form>
            </div>
        </div>
    @endfragment
@endsection

@section('sidebar-elements')



    <livewire:modular_site.chapter.chapters :course="$course" :chapters="$chapters" :chapter="$chapter" :lesson="$lesson"/>


    <livewire:modular_site.chapter.chaptercreate :course="$course"/>


@endsection


@section('js')

    <script src="{{ asset('vendors/chart.js') }}"></script>
    <script src="{{ asset('vendors/katex/katex.min.js') }}"></script>
    <script src="{{ asset('vendors/katex/contrib/auto-render.min.js') }}"></script>


    <script src="{{ asset('js/function.js') }}"></script>


    <script>


        // Helper function to update hidden input
        function updateFunctionHiddenInput(container, funcExpr, xMin, xMax, yMin, yMax, color, step) {
            const hiddenInput = container.nextElementSibling;
            if (hiddenInput && hiddenInput.classList.contains('function-content-hidden')) {
                hiddenInput.value = JSON.stringify({
                    function: funcExpr,
                    x_min: xMin,
                    x_max: xMax,
                    y_min: yMin,
                    y_max: yMax,
                    color: color,
                    step: step
                });
            }
        }

        // Auto-render on input change
        document.addEventListener('input', function(e) {
            if (e.target.closest('.function-editor')) {
                const blockId = e.target.closest('.function-editor').dataset.blockId;
                // Debounce
                clearTimeout(window.funcRenderTimeout);
                window.funcRenderTimeout = setTimeout(() => renderFunctionPreview(blockId), 100);
            }
        });

        // Initial render for existing function blocks
        document.addEventListener('DOMContentLoaded', function() {
            // Wait for styles to load
            setTimeout(() => {
                document.querySelectorAll('.function-editor').forEach(editor => {
                    renderFunctionPreview(editor.dataset.blockId);
                });
            }, 100);
        });
    </script>


@endsection
