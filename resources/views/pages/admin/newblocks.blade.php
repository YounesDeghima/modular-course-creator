@extends('layouts.admin-base')

@section('css')
    <link rel="stylesheet" href="{{asset('css/modular-site.css')}}">
    <link rel="stylesheet" href="{{asset('css/block-page.css')}}">
@endsection

@section('back-button')
    <a class="back-button"
       href="{{route('admin.courses.chapters.lessons.index',['course'=>$course->id,'chapter'=>$chapter->id])}}">{{$course->title}}
        ->{{$chapter->title}}->{{$lesson->title}}</a>
@endsection
@section('main')
    <div class="editor-container">

        <div class="add-wrapper block-adder">
            <button class="plus-btn" id="block-adder">
                +
            </button>
            <div class="popup" id="block-popup">

                <form id="new-block-form" method="POST"
                      action="{{route('admin.courses.chapters.lessons.blocks.store',['course'=>$course->id,'chapter'=>$chapter->id,'lesson'=>$lesson->id])}}">
                    @csrf
                    <label>Ttile:</label>
                    <input class="value-input" type="text" name="title" required>

                    <label>block-number:</label>
                    <input class="value-input" type="number" name="block_number" value="{{$block_count+1}}" readonly>

                    <label>type:</label>
                    <select name="type">
                        <option value="header">header</option>
                        <option value="description">description</option>
                        <option value="note">note</option>
                        <option value="exercise">exercise</option>
                        <option value="code">code</option>
                    </select>


                    <label>content:</label>
                    <textarea class="value-input" name="content" required></textarea>


                    <div style="text-align:right; margin-top:10px;">
                        <button type="submit">create block</button>
                        <button type="button" id="close-popup">Cancel</button>
                    </div>
                </form>
            </div>
        </div>


        <div class="editor">
            <div id="blocks">
                @foreach($blocks as $block)
                    <div class="block">

                        <div class="block-header">

                            <form
                                action="{{route('admin.courses.chapters.lessons.blocks.update',[$course,$chapter,$lesson,$block])}}"
                                method="post">
                                @csrf
                                @method('PUT')
                                <button type="submit" name="update" value="up">↑</button>
                                <button type="submit" name="update" value="down">↓</button>

                            </form>


                            <form
                                action="{{route('admin.courses.chapters.lessons.blocks.destroy',[$course,$chapter,$lesson,$block])}}"
                                method="post">
                                @csrf
                                @method('DELETE')
                                <button type="submit" name="delete">✕</button>

                            </form>
                        </div>
                        <div class="block-body">
                            <form
                                action="{{route('admin.courses.chapters.lessons.blocks.updateAll',['course'=>$course->id,'chapter'=>$chapter->id,'lesson'=>$lesson->id,'block'=>$block->id])}}"
                                method="post">
                                @csrf
                                @method('PUT')
                                <input class="value-input" type="text" name="title" value="{{$block->title}}">
                                <label for="type">type</label>
                                <input class="value-input" type="text" name="type" id="type" value="{{$block->type}}" disabled>
                                @if($block->type=='exercise')
                                    <label>Question:</label>
                                    <textarea name="content">{{$block->content}}</textarea>
                                    @foreach($block->solutions as $solution)
                                        <label>Solution</label>
                                        <textarea name="solutions[{{ $solution->id }}]">{{$solution->content}}</textarea>
                                    @endforeach

                                @else
                                    <textarea name="content">{{$block->content}}</textarea>
                                @endif





                                <div class="info-row">
                                    <label for="block_number">number</label>
                                    <input class="value-input" type="hidden" name="block_number"
                                           value="{{$block->block_number}}">

                                </div>
                                <div class="info-row">
                                    <select name="type">
                                        <option value="header"{{ $block->type == 'header' ? 'selected' : '' }}>header
                                        </option>
                                        <option
                                            value="description" {{ $block->type == 'description' ? 'selected' : '' }}>
                                            description
                                        </option>
                                        <option value="note" {{ $block->type == 'note' ? 'selected' : '' }}>note
                                        </option>
                                        <option value="exercise" {{ $block->type == 'exercise' ? 'selected' : '' }}>
                                            exercise
                                        </option>
                                        <option value="code" {{$block->type == 'code' ? 'selected' : ''}}>code</option>
                                    </select>
                                </div>


                                <input class="value-input update-button" type="submit" name="update" value="update">

                            </form>

                        </div>

                    </div>
                @endforeach
            </div>


        </div>
        <div class="preview" id="preview">
            @foreach($blocks as $block)
                @switch($block->type)
                    @case('header')
                        <h1>{{$block->content}}</h1>
                        @break
                    @case('description')
                        <p>{{$block->content}}</p>
                        @break
                    @case('code')
                        <pre><code>{{$block->content}}</code></pre>
                        @break
                    @case('note')
                        <div class="note">{{$block->content}}</div>
                        @break
                    @case('exercise')
                        <div class="exercise">
                            <strong>Q: {{$block->content}}</strong>
                            <button class="toggle-solution" data-blockid="{{$block->id}}">show solution</button>
                            @foreach($block->solutions as $solution)
                                <div class="solution solution-{{$block->id}}">{{$solution->content}}</div>
                            @endforeach

                        </div>
                @endswitch
            @endforeach
        </div>
    </div>

@endsection



@section('js')
    <script>

        const adder = document.getElementById('block-popup');
        const openBtn = document.getElementById('block-adder');
        const closeBtn = document.getElementById('close-popup');

        openBtn.addEventListener('click', () => {
            adder.style.visibility = 'visible';
            adder.style.opacity = 1;
        });

        closeBtn.addEventListener('click', () => {
            adder.style.visibility = 'hidden';
            adder.style.opacity = 0;
        });

        let solutionbuttons = document.querySelectorAll('.toggle-solution');
        solutionbuttons.forEach(button => {
            let blockid = button.dataset.blockid;
            let solutions = document.querySelectorAll(`.solution-${blockid}`);

            solutions.forEach(solution => {
                    solution.style.display = 'none';
                }
            )

            button.addEventListener('click', () => {
                let first_solution = solutions[0];
                let ishidden = first_solution.style.display === 'none';
                let display = ishidden ? 'block' : 'none';

                console.log(solutions);

                solutions.forEach(solution => {
                    solution.style.display = display;
                });
                button.textContent = ishidden ? 'hide solution' : 'show solution';
            });

        });

        // --- 5. CLIENT-SIDE BLOCK REORDERING ---
        document.addEventListener('DOMContentLoaded', function() {

            // Function to swap two DOM elements
            function swapElements(el1, el2) {
                const parent = el1.parentNode;
                const next = el2.nextSibling === el1 ? el2 : el2.nextSibling;
                parent.insertBefore(el1, next);
            }

            // Update all visible block numbers in the UI
            function updateBlockNumbers() {
                document.querySelectorAll('.blocks-list .block-row').forEach((block, index) => {
                    block.dataset.blockNumber = index + 1;
                    const numberInput = block.querySelector('input[name$="[block_number]"]');
                    if (numberInput) numberInput.value = index + 1;
                });
            }

            // Attach click events to arrow buttons
            document.querySelectorAll('.arrow-btn').forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.preventDefault(); // prevent form submission for instant reordering

                    const blockRow = btn.closest('.block-row');
                    if (!blockRow) return;

                    const isUp = btn.value.endsWith(':up');
                    const isDown = btn.value.endsWith(':down');

                    if (isUp) {
                        const prev = blockRow.previousElementSibling;
                        if (prev && prev.classList.contains('block-row')) {
                            swapElements(blockRow, prev);
                        }
                    } else if (isDown) {
                        const next = blockRow.nextElementSibling;
                        if (next && next.classList.contains('block-row')) {
                            swapElements(next, blockRow);
                        }
                    }

                    // Update block numbers after swap
                    updateBlockNumbers();
                });
            });

            // Initial update in case page loads out of order
            updateBlockNumbers();
        });
    </script>
@endsection
