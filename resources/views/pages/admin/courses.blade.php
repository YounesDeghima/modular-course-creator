@extends('layouts.admin-base')
@section('css')

    <link rel="stylesheet" href="{{asset('css/modular-site-preview.css')}}">
@endsection
@section('main')

    <div class="blocks-container" id="blocks-container">

        <div class="block-adder">
            <button id="block-adder">+</button>
            <div class="popup" id="block-popup">

                <form id="new-block-form" method="POST" action="{{route('admin.courses.store')}}">
                    @csrf
                    <label>Title:</label>
                    <input class="value-input" type="text" name="title" required>

                    <label>Year (1-3):</label>
                    <select name="year" class="year-input">
                        <option value="1">Year 1</option>
                        <option value="2">Year 2</option>
                        <option value="3">Year 3</option>
                    </select>
                    <label class="branch-label">branch:</label>
                    <select name="branch" class="branch-input">
                        <option value="mi">mi</option>
                        <option value="st">st</option>
                        <option value="none" style="display: none">none</option>

                    </select>

                    <label>Description:</label>
                    <textarea class="value-input" name="description" required></textarea>

                    <div style="text-align:right; margin-top:10px;">
                        <button type="submit">Create Course</button>
                        <button type="button" id="close-popup">Cancel</button>
                    </div>
                </form>
            </div>

        </div>
        <div class="blocks">
            @foreach($courses as $course)
                <div class="block">

                    <div class="block-top">

                        <form class="update-form" action="{{route('admin.courses.update',$course->id)}}" method="post">
                            @csrf
                            @method('PUT')

                            <div>
                                <div class="info-row">
                                    <label for="name">Title</label>
                                    <input class="value-input" type="text" name="title" value="{{$course->title}}">
                                </div>

                                <div class="info-row">
                                    <label for="year">year</label>
                                    <select name="year" class="year-input">
                                        <option value="1" {{ $course->year == 1 ? 'selected' : '' }}>Year 1</option>
                                        <option value="2" {{ $course->year == 2 ? 'selected' : '' }}>Year 2</option>
                                        <option value="3" {{ $course->year == 3 ? 'selected' : '' }}>Year 3</option>
                                    </select>
                                </div>
                            </div>


                            <div class="info-row">
                                <label for="branch" class="branch-label">branch</label>
                                <select name="branch" class="branch-input">
                                    <option value="mi" {{ $course->branch == 'mi' ? 'selected' : '' }}>mi</option>
                                    <option value="st" {{ $course->branch == 'st' ? 'selected' : '' }}>st</option>
                                    <option value="none"
                                            style="display: none" {{ $course->branch == 'none' ? 'selected' : '' }}>none
                                    </option>
                                </select>
                            </div>

                            <div class="info-row">
                                <label for="description"></label>
                                <textarea name="description" class="value-input">{{$course->description}}</textarea>
                            </div>

                            <input class="value-input" type="submit" name="update" value="update">

                        </form>


                        <form action="{{route('admin.courses.destroy',$course->id)}}" method="post">
                            @csrf
                            @method('DELETE')
                            <input type="submit" name="course-delete" class="block-delete" value="delete">
                        </form>

                        <a href="{{route('admin.courses.chapters.index',$course)}}">manage chapters</a>

                    </div>


                </div>
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

        let years = Array.from(document.getElementsByClassName('year-input'));
        let branchs = Array.from(document.getElementsByClassName('branch-input'));
        let branchlabels = Array.from(document.getElementsByClassName('branch-label'));

        function togglebranch(year, i) {
            console.log(i);
            if (parseInt(year.value) > 1) {
                branchs[i].style.visibility = 'visible';
                branchlabels[i].style.visibility = 'visible';

            } else {
                branchs[i].style.visibility = 'hidden';
                branchlabels[i].style.visibility = 'hidden';

            }
        }

        years.forEach((year, i) => {
            year.addEventListener('change', () => togglebranch(year, i));

        });

        years.forEach((year, i) => togglebranch(year, i));


        // Get all update forms
        const forms = document.querySelectorAll('.update-form');

        forms.forEach(form => {
            const inputs = form.querySelectorAll('input, textarea, select');
            const updateBtn = form.querySelector('input[type="submit"]');

            // Store original values
            const originalValues = [];

            inputs.forEach((input, index) => {
                originalValues[index] = input.value;
            });

            // Hide button initially
            updateBtn.style.visibility = 'hidden';

            // Listen for changes
            inputs.forEach((input, index) => {
                input.addEventListener('input', () => {
                    let changed = false;

                    inputs.forEach((inp, i) => {
                        if (inp.value != originalValues[i]) {
                            changed = true;
                        }
                    });

                    updateBtn.style.visibility = changed ? 'visible' : 'hidden';
                });

                // For select elements (important)
                input.addEventListener('change', () => {
                    let changed = false;

                    inputs.forEach((inp, i) => {
                        if (inp.value != originalValues[i]) {
                            changed = true;
                        }
                    });

                    updateBtn.style.display = changed ? 'inline-block' : 'none';
                });
            });
        });


    </script>
@endsection

