{{--
    resources/views/pages/teacher/section.blade.php
    Route: teacher.section  (GET /teacher/section/{section})
    Controller: App\Http\Controllers\Teacher\TeacherController@section
--}}
@extends('layouts.app')

@section('css')
    <style>
        .section-page-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 20px;
        }

        .section-page-back {
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 12.5px;
            color: var(--text-muted);
            text-decoration: none;
            padding: 5px 10px;
            border: 1px solid var(--border);
            border-radius: 7px;
            background: var(--bg-subtle);
            transition: background .13s, color .13s;
            flex-shrink: 0;
        }

        .section-page-back:hover {
            background: var(--bg-hover);
            color: var(--text);
        }

        .section-page-title {
            flex: 1;
        }

        .section-page-title h1 {
            font-size: 18px;
            font-weight: 500;
            color: var(--text);
            line-height: 1.2;
        }

        .section-page-title p {
            font-size: 12px;
            color: var(--text-muted);
            margin-top: 2px;
        }

        .section-page-badge {
            font-size: 11px;
            padding: 3px 10px;
            border-radius: 999px;
            background: #EEEDFE;
            color: #3C3489;
            font-weight: 500;
            flex-shrink: 0;
        }

        [data-theme="dark"] .section-page-badge {
            background: #3C3489;
            color: #CECBF6;
        }
    </style>
@endsection

@section('main')

    <div class="section-page-header">
        <a href="{{ route('user.main') }}" class="section-page-back">
            ‹ Back
        </a>
        <div class="section-page-title">
            <h1>Section {{ $section->section_number }}</h1>
            <p>Students, progress and activity for this section</p>
        </div>
        <span class="section-page-badge">{{ $section->student_count() }} students</span>
    </div>

    <livewire:teacher.teacher-section-dashboard :user="$user" :initialSection="$section->id" />

@endsection

@section('sidebar-elements')
    <div style="padding:16px 12px;display:flex;flex-direction:column;gap:16px;">

        <livewire:platform-overview :user="$user" lazy/>

        <div style="height:1px;background:var(--border);"></div>

        <div>
            <div style="font-size:10px;font-weight:500;text-transform:uppercase;letter-spacing:.06em;color:var(--text-faint);margin-bottom:8px;">
                Navigate
            </div>
            <div style="display:flex;flex-direction:column;gap:3px;">
                <a href="{{ route('user.main') }}"
                   style="padding:6px 8px;border-radius:6px;font-size:13px;color:var(--text-muted);text-decoration:none;transition:background .13s;display:block;"
                   onmouseover="this.style.background='var(--bg-hover)'" onmouseout="this.style.background=''">← Dashboard</a>
                <a href="{{ route('admin.courses.index') }}"
                   style="padding:6px 8px;border-radius:6px;font-size:13px;color:var(--text-muted);text-decoration:none;transition:background .13s;display:block;"
                   onmouseover="this.style.background='var(--bg-hover)'" onmouseout="this.style.background=''">Courses</a>
                <a href="{{ route('admin.preview.courses') }}"
                   style="padding:6px 8px;border-radius:6px;font-size:13px;color:var(--text-muted);text-decoration:none;transition:background .13s;display:block;"
                   onmouseover="this.style.background='var(--bg-hover)'" onmouseout="this.style.background=''">Preview</a>
                <a href="{{ route('admin.calendar') }}"
                   style="padding:6px 8px;border-radius:6px;font-size:13px;color:var(--text-muted);text-decoration:none;transition:background .13s;display:block;"
                   onmouseover="this.style.background='var(--bg-hover)'" onmouseout="this.style.background=''">Calendar</a>
            </div>
        </div>

        {{-- Other sections taught by this teacher --}}
        @if($otherSections->count())
            <div>
                <div style="font-size:10px;font-weight:500;text-transform:uppercase;letter-spacing:.06em;color:var(--text-faint);margin-bottom:8px;">
                    Your other sections
                </div>
                <div style="display:flex;flex-direction:column;gap:3px;">
                    @foreach($otherSections as $s)
                        <a href="{{ route('teacher.section', $s->id) }}"
                           style="padding:6px 8px;border-radius:6px;font-size:13px;color:var(--text-muted);text-decoration:none;transition:background .13s;display:flex;align-items:center;justify-content:space-between;"
                           onmouseover="this.style.background='var(--bg-hover)'" onmouseout="this.style.background=''">
                            <span>Section {{ $s->section_number }}</span>
                            <span style="font-size:10px;background:var(--bg-subtle);padding:1px 7px;border-radius:999px;color:var(--text-muted);">{{ $s->student_count() }}</span>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif

    </div>
@endsection
