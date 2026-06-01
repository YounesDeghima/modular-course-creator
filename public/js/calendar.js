// calendar.js — shared between admin and user views

const COLORS = {
    exam:       { chip: 'type-exam',       bar: 'bar-exam',       dot: 'dot-exam'       },
    vacation:   { chip: 'type-vacation',   bar: 'bar-vacation',   dot: 'dot-vacation'   },
    project:    { chip: 'type-project',    bar: 'bar-project',    dot: 'dot-project'    },
    assignment: { chip: 'type-assignment', bar: 'bar-assignment', dot: 'dot-assignment' },
    personal:   { chip: 'type-personal',   bar: 'bar-personal',   dot: 'dot-personal'   },
};

const MONTHS = ['January','February','March','April','May','June',
    'July','August','September','October','November','December'];
const DAYS   = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];

let events     = window.CAL_EVENTS || [];
let currentDate = new Date();
let currentView = 'month';
let activeFilters = new Set(['exam','vacation','project','assignment','personal']);
let editingId   = null;
let isAdmin     = window.CAL_IS_ADMIN || false;

// ── DOM refs ──
const monthTitle  = document.getElementById('cal-month-title');
const monthView   = document.getElementById('month-view');
const weekView    = document.getElementById('week-view');
const agendaView  = document.getElementById('agenda-view');
const daysGrid    = document.getElementById('days-grid');
const weekGrid    = document.getElementById('week-grid');
const agendaList  = document.getElementById('agenda-list');
const upcomingList= document.getElementById('upcoming-list');
const modal       = document.getElementById('cal-modal');
const modalTitle  = document.getElementById('modal-title');
const fTitle      = document.getElementById('f-title');
const fDesc       = document.getElementById('f-desc');
const fStart      = document.getElementById('f-start');
const fEnd        = document.getElementById('f-end');
const fType       = document.getElementById('f-type');
const fVis        = document.getElementById('f-vis');
const deleteBtn   = document.getElementById('cal-delete-btn');
const visRow      = document.getElementById('vis-row');

// ── Navigation ──
document.getElementById('cal-prev').addEventListener('click', () => {
    if (currentView === 'month') currentDate.setMonth(currentDate.getMonth() - 1);
    else currentDate.setDate(currentDate.getDate() - 7);
    render();
});

document.getElementById('cal-next').addEventListener('click', () => {
    if (currentView === 'month') currentDate.setMonth(currentDate.getMonth() + 1);
    else currentDate.setDate(currentDate.getDate() + 7);
    render();
});

// ── View toggle ──
document.querySelectorAll('.view-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        document.querySelectorAll('.view-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        currentView = btn.dataset.view;
        render();
    });
});

// ── Type filters ──
document.querySelectorAll('.type-filter').forEach(el => {
    el.addEventListener('click', () => {
        const t = el.dataset.type;
        if (activeFilters.has(t)) { activeFilters.delete(t); el.classList.add('off'); }
        else { activeFilters.add(t); el.classList.remove('off'); }
        render();
    });
});

// ── Modal open/close ──


// Visibility selector
/*document.querySelectorAll('.vis-opt').forEach(opt => {
    opt.addEventListener('click', () => {
        document.querySelectorAll('.vis-opt').forEach(o => o.classList.remove('selected'));
        opt.classList.add('selected');
        fVis.value = opt.dataset.val;
    });
});*/

function openModal(event = null) {
    editingId = event ? event.id : null;
    modalTitle.textContent = event ? 'Edit event' : 'New event';
    fTitle.value = event?.title || '';
    fDesc.value  = event?.description || '';
    fStart.value = event?.start_date || '';
    fEnd.value   = event?.end_date || '';
    fType.value  = event?.type || 'exam';
    fVis.value   = event?.visibility || (isAdmin ? 'global' : 'personal');

    // Sync visibility buttons
    document.querySelectorAll('.vis-opt').forEach(o => {
        o.classList.toggle('selected', o.dataset.val === fVis.value);
    });

    // Show/hide admin-only visibility row
    if (visRow) visRow.style.display = isAdmin ? 'flex' : 'none';

    // Show delete btn only when editing
    if (deleteBtn) deleteBtn.style.display = editingId ? 'block' : 'none';

    modal.classList.add('open');
}

function closeModal() { modal.classList.remove('open'); editingId = null; }

// ── Submit ──
/*document.getElementById('cal-submit-btn').addEventListener('click', async () => {
    const payload = {
        title:       fTitle.value.trim(),
        description: fDesc.value.trim(),
        start_date:  fStart.value,
        end_date:    fEnd.value || null,
        type:        fType.value,
        visibility:  fVis.value,
    };

    if (!payload.title || !payload.start_date) {
        alert('Title and start date required.');
        return;
    }

    try {
        const url    = editingId ? `/events/${editingId}` : '/events';
        const method = editingId ? 'put' : 'post';
        const res    = await axios[method](url, payload);

        if (editingId) {
            events = events.map(e => e.id === editingId ? res.data : e);
        } else {
            events.push(res.data);
        }

        closeModal();
        render();
    } catch (err) {
        alert(err.response?.data?.message || 'Error saving event.');
    }
});*/

// ── Delete ──
if (deleteBtn) {
    deleteBtn.addEventListener('click', async () => {
        if (!confirm('Delete this event?')) return;
        await axios.delete(`/events/${editingId}`);
        events = events.filter(e => e.id !== editingId);
        closeModal();
        render();
    });
}

// ── Helpers ──
function filteredEvents() {
    return events.filter(e => activeFilters.has(e.type));
}

function eventsOnDate(dateStr) {
    return filteredEvents().filter(e => {
        const s = e.start_date.substring(0,10);
        const en = e.end_date ? e.end_date.substring(0,10) : s;
        return dateStr >= s && dateStr <= en;
    });
}

function pad(n) { return String(n).padStart(2,'0'); }

function dateStr(y,m,d) { return `${y}-${pad(m+1)}-${pad(d)}`; }

// ── Render ──
function render() {
    events = window.CAL_EVENTS || [];

    // Re-grab DOM refs every render (Livewire may have replaced them)
    const monthView  = document.getElementById('month-view');
    const weekView   = document.getElementById('week-view');
    const agendaView = document.getElementById('agenda-view');
    const monthTitle = document.getElementById('cal-month-title');

    const today = new Date();
    const todayStr = dateStr(today.getFullYear(), today.getMonth(), today.getDate());

    monthTitle.textContent = `${MONTHS[currentDate.getMonth()]} ${currentDate.getFullYear()}`;

    if (currentView === 'month') {
        monthView.style.display = 'flex';
        weekView.style.display  = 'none';
        agendaView.style.display= 'none';
        renderMonth(todayStr);
    } else if (currentView === 'week') {
        monthView.style.display = 'none';
        weekView.style.display  = 'block';
        agendaView.style.display= 'none';
        renderWeek(todayStr);
    } else {
        monthView.style.display = 'none';
        weekView.style.display  = 'none';
        agendaView.style.display= 'block';
        renderAgenda(todayStr);
    }

    renderUpcoming(todayStr);
}

function renderMonth(todayStr) {
    const daysGrid = document.getElementById('days-grid');

    const year  = currentDate.getFullYear();
    const month = currentDate.getMonth();
    const first = new Date(year, month, 1).getDay();
    const days  = new Date(year, month+1, 0).getDate();

    daysGrid.innerHTML = '';

    // Prev month filler
    const prevDays = new Date(year, month, 0).getDate();
    for (let i = first - 1; i >= 0; i--) {
        const d = prevDays - i;
        const ds = dateStr(year, month-1, d);
        daysGrid.appendChild(makeCell(d, ds, todayStr, true));
    }

    // Current month
    for (let d = 1; d <= days; d++) {
        const ds = dateStr(year, month, d);
        daysGrid.appendChild(makeCell(d, ds, todayStr, false));
    }

    // Next month filler
    const total = first + days;
    const rem   = total % 7 === 0 ? 0 : 7 - (total % 7);
    for (let d = 1; d <= rem; d++) {
        const ds = dateStr(year, month+1, d);
        daysGrid.appendChild(makeCell(d, ds, todayStr, true));
    }
}

function makeCell(day, ds, todayStr, otherMonth) {
    const cell = document.createElement('div');
    cell.className = 'day-cell';
    cell.addEventListener('click', () => {
        fStart.value = ds;
        openModal();
    });

    const num = document.createElement('div');
    num.className = 'day-num' + (ds === todayStr ? ' today' : '') + (otherMonth ? ' other-month' : '');
    num.textContent = day;
    cell.appendChild(num);

    eventsOnDate(ds).slice(0,3).forEach(ev => {
        const chip = document.createElement('div');
        chip.className = `ev-chip ${COLORS[ev.type].chip}`;
        chip.textContent = ev.title;
        chip.addEventListener('click', e => { e.stopPropagation(); openModal(ev); });
        cell.appendChild(chip);
    });

    const more = eventsOnDate(ds).length - 3;
    if (more > 0) {
        const m = document.createElement('div');
        m.style.cssText = 'font-size:10px;color:var(--text-faint);padding:1px 4px;';
        m.textContent = `+${more} more`;
        cell.appendChild(m);
    }

    return cell;
}

function renderWeek(todayStr) {
    const weekGrid   = document.getElementById('week-grid');
    const monthTitle = document.getElementById('cal-month-title');

    const wd  = currentDate.getDay();
    const mon = new Date(currentDate);
    mon.setDate(currentDate.getDate() - wd);

    weekGrid.innerHTML = '';

    // Header row
    const hRow = document.createElement('div');
    hRow.className = 'week-grid';
    hRow.style.cssText = 'border-bottom:1px solid var(--border);flex-shrink:0;';
    const corner = document.createElement('div');
    corner.style.cssText = 'padding:8px;font-size:10px;color:var(--text-faint);border-right:1px solid var(--border);';
    hRow.appendChild(corner);

    const weekDays = [];
    for (let i = 0; i < 7; i++) {
        const d = new Date(mon);
        d.setDate(mon.getDate() + i);
        const ds = dateStr(d.getFullYear(), d.getMonth(), d.getDate());
        weekDays.push({ date: d, ds });

        const hdr = document.createElement('div');
        hdr.className = 'week-day-hdr' + (ds === todayStr ? ' today-col' : '');
        hdr.style.cssText = 'padding:8px 4px;text-align:center;border-left:1px solid var(--border-mid);font-size:12px;color:var(--text-muted);';
        hdr.innerHTML = `<div style="font-size:10px;text-transform:uppercase;">${DAYS[d.getDay()]}</div><div style="font-size:16px;font-weight:500;">${d.getDate()}</div>`;
        if (ds === todayStr) hdr.style.color = 'var(--accent)';
        hRow.appendChild(hdr);
    }

    weekGrid.appendChild(hRow);

    // All-day events row
    const adRow = document.createElement('div');
    adRow.className = 'week-grid';
    adRow.style.cssText = 'border-bottom:1px solid var(--border);';
    const adLabel = document.createElement('div');
    adLabel.style.cssText = 'font-size:10px;color:var(--text-faint);padding:4px 6px;text-align:right;border-right:1px solid var(--border);';
    adLabel.textContent = 'all day';
    adRow.appendChild(adLabel);

    weekDays.forEach(({ ds }) => {
        const cell = document.createElement('div');
        cell.style.cssText = 'border-left:1px solid var(--border-mid);padding:3px;min-height:32px;';
        eventsOnDate(ds).forEach(ev => {
            const chip = document.createElement('div');
            chip.className = `ev-chip ${COLORS[ev.type].chip}`;
            chip.style.marginBottom = '2px';
            chip.textContent = ev.title;
            chip.addEventListener('click', () => openModal(ev));
            cell.appendChild(chip);
        });
        adRow.appendChild(cell);
    });

    weekGrid.appendChild(adRow);

    monthTitle.textContent = `Week of ${MONTHS[mon.getMonth()]} ${mon.getDate()}, ${mon.getFullYear()}`;
}

function renderAgenda(todayStr) {

    const agendaList = document.getElementById('agenda-list');


    agendaList.innerHTML = '';

    const sorted = filteredEvents()
        .filter(e => (e.end_date || e.start_date).substring(0,10) >= todayStr)
        .sort((a,b) => a.start_date.localeCompare(b.start_date));

    if (sorted.length === 0) {
        agendaList.innerHTML = `<div style="text-align:center;padding:40px;color:var(--text-faint);font-size:14px;">No upcoming events.</div>`;
        return;
    }

    // Group by date
    const groups = {};
    sorted.forEach(ev => {
        const ds = ev.start_date.substring(0,10);
        if (!groups[ds]) groups[ds] = [];
        groups[ds].push(ev);
    });

    Object.entries(groups).forEach(([ds, evs]) => {
        const d     = new Date(ds + 'T00:00:00');
        const label = d.toLocaleDateString('en-GB', { weekday:'long', day:'numeric', month:'long', year:'numeric' });

        const group = document.createElement('div');
        group.className = 'agenda-date-group';

        const dateLabel = document.createElement('div');
        dateLabel.className = 'agenda-date-label';
        dateLabel.textContent = label;
        if (ds === todayStr) dateLabel.style.color = 'var(--accent)';
        group.appendChild(dateLabel);

        evs.forEach(ev => {
            const item = document.createElement('div');
            item.className = 'agenda-event';
            item.innerHTML = `
                <div class="agenda-type-bar ${COLORS[ev.type].bar}"></div>
                <div style="flex:1;min-width:0;">
                    <div class="agenda-event-title">${ev.title}</div>
                    ${ev.description ? `<div class="agenda-event-desc">${ev.description}</div>` : ''}
                </div>
                <span class="agenda-event-badge ${COLORS[ev.type].chip}">${ev.type}</span>
                ${ev.visibility === 'global' ? '<span style="font-size:10px;color:var(--text-faint);margin-left:4px;">global</span>' : ''}
            `;
            item.addEventListener('click', () => openModal(ev));
            group.appendChild(item);
        });

        agendaList.appendChild(group);
    });
}

function renderUpcoming(todayStr) {

    const upcomingList = document.getElementById('upcoming-list');

    upcomingList.innerHTML = '';
    const next5 = filteredEvents()
        .filter(e => (e.end_date || e.start_date).substring(0,10) >= todayStr)
        .sort((a,b) => a.start_date.localeCompare(b.start_date))
        .slice(0, 5);

    if (next5.length === 0) {
        upcomingList.innerHTML = `<div style="font-size:12px;color:var(--text-faint);padding:4px 6px;">No upcoming events.</div>`;
        return;
    }

    next5.forEach(ev => {
        const d    = new Date(ev.start_date.substring(0,10) + 'T00:00:00');
        const label= d.toLocaleDateString('en-GB', { day:'numeric', month:'short' });
        const item = document.createElement('div');
        item.className = 'upcoming-item';
        item.innerHTML = `<div class="upcoming-date">${label}</div><div class="upcoming-title">${ev.title}</div>`;
        item.addEventListener('click', () => openModal(ev));
        upcomingList.appendChild(item);
    });
}

// ── Init ──
axios.defaults.headers.common['X-CSRF-TOKEN'] =
    document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

render();
