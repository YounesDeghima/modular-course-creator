@extends('layouts.edditor')

@section('css')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="stylesheet" href="{{ asset('css/calendar.css') }}">
@endsection

@section('sidebar-elements')
    <div class="cal-sidebar">
        <button class="cal-add-btn" id="cal-add-btn">+ Add event</button>

        <div>
            <div class="cal-sb-label">Event types</div>
            <div style="display:flex;flex-direction:column;gap:2px;margin-top:4px;">
                @foreach(['exam','vacation','project','assignment','personal'] as $type)
                    <div class="type-filter" data-type="{{ $type }}">
                        <span class="type-dot dot-{{ $type }}"></span>
                        {{ ucfirst($type) }}
                    </div>
                @endforeach
            </div>
        </div>

        <div style="height:1px;background:var(--border);"></div>

        <div>
            <div class="cal-sb-label">Upcoming</div>
            <div id="upcoming-list" style="display:flex;flex-direction:column;gap:6px;margin-top:4px;"></div>
        </div>
    </div>
@endsection

@section('main')
    <div class="cal-page">
        <div class="cal-header">
            <div class="cal-nav">
                <button class="cal-nav-btn" id="cal-prev">‹</button>
                <span class="cal-month-title" id="cal-month-title"></span>
                <button class="cal-nav-btn" id="cal-next">›</button>
            </div>
            <div class="view-toggle">
                <button class="view-btn active" data-view="month">Month</button>
                <button class="view-btn" data-view="week">Week</button>
                <button class="view-btn" data-view="agenda">Agenda</button>
            </div>
        </div>

        <div class="month-view" id="month-view">
            <div class="dow-headers">
                @foreach(['Sun','Mon','Tue','Wed','Thu','Fri','Sat'] as $d)
                    <div class="dow-hdr">{{ $d }}</div>
                @endforeach
            </div>
            <div class="days-grid" id="days-grid"></div>
        </div>

        <div class="week-view" id="week-view">
            <div id="week-grid"></div>
        </div>

        <div class="agenda-view" id="agenda-view">
            <div id="agenda-list"></div>
        </div>
    </div>

    {{-- Event modal — admin has visibility toggle --}}
    <div class="cal-modal-backdrop" id="cal-modal">
        <div class="cal-modal">
            <button class="cal-modal-close" id="cal-modal-close">✕</button>
            <h3 id="modal-title">New event</h3>

            <div class="cal-form-group">
                <label>Title</label>
                <input type="text" id="f-title" placeholder="Event title">
            </div>
            <div class="cal-form-group">
                <label>Description</label>
                <textarea id="f-desc" rows="2" placeholder="Optional description"></textarea>
            </div>
            <div class="cal-form-row">
                <div class="cal-form-group">
                    <label>Start date</label>
                    <input type="date" id="f-start">
                </div>
                <div class="cal-form-group">
                    <label>End date</label>
                    <input type="date" id="f-end">
                </div>
            </div>
            <div class="cal-form-group">
                <label>Type</label>
                <select id="f-type">
                    <option value="exam">Exam</option>
                    <option value="vacation">Vacation</option>
                    <option value="project">Project</option>
                    <option value="assignment">Assignment</option>
                    <option value="personal">Personal</option>
                </select>
            </div>

            {{-- Admin only: visibility --}}
            <div class="cal-form-group" id="vis-row">
                <label>Visibility</label>
                <div class="visibility-row">
                    <button type="button" class="vis-opt selected" data-val="global">🌍 Global (all users)</button>
                    <button type="button" class="vis-opt" data-val="personal">🔒 Personal (only me)</button>
                </div>
                <input type="hidden" id="f-vis" value="global">
            </div>

            <div class="cal-modal-actions">
                <button class="cal-btn-delete" id="cal-delete-btn" style="display:none;">Delete</button>
                <button class="cal-btn-cancel" id="cal-cancel-btn">Cancel</button>
                <button class="cal-btn-submit" id="cal-submit-btn">Save event</button>
            </div>
        </div>
    </div>
@endsection

@section('js')
    <script>
        window.CAL_EVENTS   = @json($events);
        window.CAL_IS_ADMIN = true;

        // document.addEventListener('DOMContentLoaded', () => {
        //     // Set default CSRF header
        //     axios.defaults.headers.common['X-CSRF-TOKEN'] =
        //         document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        //
        //     // Initial render
        //     render();
        // });

    </script>
    <script src="{{ asset('js/axios.min.js') }}"></script>
    <script src="{{ asset('js/calendar.js') }}"></script>
@endsection
