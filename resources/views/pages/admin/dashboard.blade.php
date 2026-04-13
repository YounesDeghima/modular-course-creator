@extends('layouts.edditor')

@section('css')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        /* ── Stats ── */
        .stat-card { background: var(--bg-subtle); border-radius: 8px; padding: 10px 12px; }
        .stat-val  { font-size: 22px; font-weight: 500; color: var(--text); }
        .stat-lbl  { font-size: 11px; color: var(--text-muted); margin-top: 2px; }

        /* ── Sidebar sections ── */
        .admin-sb-label {
            font-size: 10px; font-weight: 500; text-transform: uppercase;
            letter-spacing: .06em; color: var(--text-faint); margin-bottom: 6px; padding: 0 4px;
        }
        .admin-sb-divider { height: 1px; background: var(--border); margin: 4px 0; }
        .sb-filter-btn {
            display: block; width: 100%; padding: 6px 10px; border-radius: 6px;
            border: 0.5px solid transparent; background: none; font-size: 13px;
            color: var(--text-muted); text-align: left; cursor: pointer;
            font-family: inherit; transition: background .13s;
        }
        .sb-filter-btn:hover { background: var(--bg-hover); color: var(--text); }
        .sb-filter-btn.active { background: var(--bg-hover); color: var(--text); font-weight: 500; border-color: var(--border); }
        .admin-new-btn {
            width: 100%; padding: 8px; border-radius: 7px; border: none;
            background: var(--accent); color: #fff; font-size: 13px;
            font-weight: 500; cursor: pointer; font-family: inherit;
            transition: background .15s;
        }
        .admin-new-btn:hover { background: var(--accent-hover); }

        /* ── Top bar ── */
        .dash-topbar { display: flex; align-items: center; gap: 10px; margin-bottom: 16px; }
        .dash-search {
            flex: 1; padding: 8px 12px; border: 1px solid var(--border);
            border-radius: 7px; font-size: 13px; background: var(--bg-subtle);
            color: var(--text); outline: none; font-family: inherit;
            transition: border-color .15s;
        }
        .dash-search:focus { border-color: var(--accent); }
        .user-count { font-size: 12px; color: var(--text-muted); white-space: nowrap; }

        /* ── Table ── */
        .users-table-wrap { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; font-size: 13px; }
        thead tr { border-bottom: 1px solid var(--border); }
        th {
            text-align: left; padding: 8px 10px; font-size: 11px; font-weight: 500;
            text-transform: uppercase; letter-spacing: .05em; color: var(--text-muted);
        }
        td { padding: 10px 10px; color: var(--text); border-bottom: 1px solid var(--border-mid); vertical-align: middle; }
        tr:last-child td { border-bottom: none; }
        tbody tr:hover td { background: var(--bg-subtle); }

        /* Avatar */
        .user-avatar {
            width: 30px; height: 30px; border-radius: 50%; background: #EEEDFE;
            color: #3C3489; font-size: 11px; font-weight: 500; display: flex;
            align-items: center; justify-content: center; text-transform: uppercase; flex-shrink: 0;
        }
        [data-theme="dark"] .user-avatar { background: #3C3489; color: #CECBF6; }

        /* Role badges */
        .role-badge { font-size: 10px; padding: 2px 8px; border-radius: 999px; font-weight: 500; }
        .role-admin { background: #E6F1FB; color: #0C447C; }
        .role-user  { background: #F1EFE8; color: #5F5E5A; }
        [data-theme="dark"] .role-admin { background: #0C447C; color: #B5D4F4; }
        [data-theme="dark"] .role-user  { background: #2C2C2A; color: #B4B2A9; }

        /* Action buttons */
        .action-btn {
            padding: 4px 10px; border-radius: 5px; border: 1px solid var(--border);
            background: none; font-size: 11px; color: var(--text-muted);
            cursor: pointer; font-family: inherit; transition: background .13s;
        }
        .action-btn:hover { background: var(--bg-hover); color: var(--text); }
        .action-btn.danger { color: #ef4444; border-color: #fca5a5; }
        .action-btn.danger:hover { background: #fff5f5; }
        [data-theme="dark"] .action-btn.danger { color: #f87171; border-color: #7f1d1d; }
        [data-theme="dark"] .action-btn.danger:hover { background: #2a0f0f; }

        /* ── Modal ── */
        .modal-backdrop {
            display: none; position: fixed; inset: 0;
            background: rgba(0,0,0,.45); z-index: 500;
            align-items: center; justify-content: center;
        }
        .modal-backdrop.open { display: flex; }
        .modal {
            background: var(--bg); border: 1px solid var(--border);
            border-radius: 14px; padding: 24px; width: 100%; max-width: 440px;
            box-shadow: 0 20px 60px var(--shadow); position: relative;
        }
        .modal h3 { font-size: 15px; font-weight: 500; color: var(--text); margin-bottom: 18px; }
        .modal-close {
            position: absolute; top: 14px; right: 16px; font-size: 20px;
            color: var(--text-faint); cursor: pointer; background: none; border: none;
        }
        .modal-close:hover { color: var(--text); }
        .form-group { display: flex; flex-direction: column; gap: 4px; margin-bottom: 12px; }
        .form-group label {
            font-size: 10px; font-weight: 500; text-transform: uppercase;
            letter-spacing: .06em; color: var(--text-faint);
        }
        .form-group input,
        .form-group select {
            padding: 8px 10px; border: 1px solid var(--border); border-radius: 7px;
            font-size: 13px; color: var(--text); background: var(--bg-subtle);
            font-family: inherit; outline: none; transition: border-color .15s;
        }
        .form-group input:focus,
        .form-group select:focus { border-color: var(--accent); background: var(--bg); }
        .form-row { display: flex; gap: 10px; }
        .form-row .form-group { flex: 1; }
        .modal-actions { display: flex; gap: 8px; justify-content: flex-end; margin-top: 16px; }
        .btn-cancel {
            padding: 8px 16px; border-radius: 7px; border: 1px solid var(--border);
            background: none; font-size: 13px; color: var(--text-muted);
            cursor: pointer; font-family: inherit;
        }
        .btn-submit {
            padding: 8px 20px; border-radius: 7px; border: none;
            background: var(--accent); color: #fff; font-size: 13px;
            font-weight: 500; cursor: pointer; font-family: inherit;
            transition: background .15s;
        }
        .btn-submit:hover { background: var(--accent-hover); }
    </style>
@endsection

@section('sidebar-elements')
    <div style="padding:12px 10px;display:flex;flex-direction:column;gap:14px;">

        <div>
            <div class="admin-sb-label">Overview</div>
            <div style="display:flex;flex-direction:column;gap:6px;">
                <div class="stat-card">
                    <div class="stat-val">{{ $totalUsers }}</div>
                    <div class="stat-lbl">Total users</div>
                </div>
                <div class="stat-card">
                    <div class="stat-val" style="color:var(--accent);">{{ $totalAdmins }}</div>
                    <div class="stat-lbl">Admins</div>
                </div>
                <div class="stat-card">
                    <div class="stat-val" style="color:#3B6D11;">{{ $totalStudents }}</div>
                    <div class="stat-lbl">Students</div>
                </div>
            </div>
        </div>

        <div class="admin-sb-divider"></div>

        <div>
            <div class="admin-sb-label">Filter by role</div>
            <div style="display:flex;flex-direction:column;gap:3px;">
                <button class="sb-filter-btn active" data-role="all">All users</button>
                <button class="sb-filter-btn" data-role="admin">Admins</button>
                <button class="sb-filter-btn" data-role="user">Students</button>
            </div>
        </div>

        <div class="admin-sb-divider"></div>

        <div>
            <div class="admin-sb-label">Sort</div>
            <div style="display:flex;flex-direction:column;gap:3px;">
                <button class="sb-filter-btn active" data-sort="newest">Newest first</button>
                <button class="sb-filter-btn" data-sort="oldest">Oldest first</button>
                <button class="sb-filter-btn" data-sort="name">Name A–Z</button>
            </div>
        </div>

        <div class="admin-sb-divider"></div>

        <div>
            <div class="admin-sb-label">Actions</div>
            <button class="admin-new-btn" id="open-add-modal">+ Add user</button>
        </div>

    </div>
@endsection

@section('main')

    {{-- Add / Edit modal --}}
    <div class="modal-backdrop" id="user-modal">
        <div class="modal">
            <button class="modal-close" id="close-modal">✕</button>
            <h3 id="modal-title">Add user</h3>
            <div class="form-row">
                <div class="form-group">
                    <label>First name</label>
                    <input type="text" id="f-name" placeholder="Amina">
                </div>
                <div class="form-group">
                    <label>Last name</label>
                    <input type="text" id="f-last" placeholder="Meziane">
                </div>
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" id="f-email" placeholder="amina@example.com">
            </div>
            <div class="form-group" id="f-password-group">
                <label>Password</label>
                <input type="password" id="f-password" placeholder="Min. 6 characters">
            </div>
            <div class="form-group">
                <label>Role</label>
                <select id="f-role">
                    <option value="user">Student</option>
                    <option value="admin">Admin</option>
                </select>
            </div>
            <div class="modal-actions">
                <button class="btn-cancel" id="cancel-modal">Cancel</button>
                <button class="btn-submit" id="submit-modal">Create user</button>
            </div>
        </div>
    </div>

    {{-- Main content --}}
    <div class="dash-topbar">
        <input class="dash-search" id="user-search" placeholder="Search by name or email...">
        <span class="user-count" id="user-count">{{ $totalUsers }} users</span>
    </div>

    <div class="users-table-wrap">
        <table>
            <thead>
            <tr>
                <th style="width:36px;"></th>
                <th>Name</th>
                <th>Email</th>
                <th>Role</th>
                <th>Joined</th>
                <th>Actions</th>
            </tr>
            </thead>
            <tbody id="users-tbody">
            @foreach($users as $user)
                <livewire:user_info.user :user="$user"/>
            @endforeach
            </tbody>
        </table>
    </div>
@endsection

@section('js')
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <script>
        axios.defaults.headers.common['X-CSRF-TOKEN'] =
            document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        const modal       = document.getElementById('user-modal');
        const modalTitle  = document.getElementById('modal-title');
        const submitBtn   = document.getElementById('submit-modal');
        const fName       = document.getElementById('f-name');
        const fLast       = document.getElementById('f-last');
        const fEmail      = document.getElementById('f-email');
        const fPassword   = document.getElementById('f-password');
        const fPwdGroup   = document.getElementById('f-password-group');
        const fRole       = document.getElementById('f-role');
        let editingId     = null;

        function openModal(mode = 'add', data = {}) {
            editingId = mode === 'edit' ? data.id : null;
            modalTitle.textContent = mode === 'add' ? 'Add user' : 'Edit user';
            submitBtn.textContent  = mode === 'add' ? 'Create user' : 'Save changes';
            fPwdGroup.style.display = mode === 'add' ? 'flex' : 'none';
            fName.value     = data.name  || '';
            fLast.value     = data.last  || '';
            fEmail.value    = data.email || '';
            fPassword.value = '';
            fRole.value     = data.role  || 'user';
            modal.classList.add('open');
        }

        function closeModal() { modal.classList.remove('open'); editingId = null; }

        document.getElementById('open-add-modal').addEventListener('click', () => openModal('add'));
        document.getElementById('close-modal').addEventListener('click', closeModal);
        document.getElementById('cancel-modal').addEventListener('click', closeModal);
        modal.addEventListener('click', e => { if (e.target === modal) closeModal(); });

        // ── Edit buttons ──
        document.querySelectorAll('.edit-btn').forEach(btn => {
            btn.addEventListener('click', () => openModal('edit', {
                id:    btn.dataset.id,
                name:  btn.dataset.name,
                last:  btn.dataset.last,
                email: btn.dataset.email,
                role:  btn.dataset.role,
            }));
        });

        // ── Submit (create or update) ──
        submitBtn.addEventListener('click', async () => {
            const payload = {
                name:      fName.value.trim(),
                last_name: fLast.value.trim(),
                email:     fEmail.value.trim(),
                role:      fRole.value,
            };
            if (!editingId) payload.password = fPassword.value;

            try {
                const res = editingId
                    ? await axios.put(`/admin/users/${editingId}`, payload)
                    : await axios.post('/admin/users', payload);

                closeModal();
                location.reload(); // simple reload to reflect changes
            } catch (err) {
                alert(err.response?.data?.message || 'Something went wrong.');
            }
        });

        // ── Delete ──
        document.querySelectorAll('.delete-btn').forEach(btn => {
            btn.addEventListener('click', async () => {
                if (!confirm('Are you sure you want to delete this user?')) return;
                const row = btn.closest('tr');
                await axios.delete(`/admin/users/${btn.dataset.id}`);
                row.remove();
                updateCount();
            });
        });

        // ── Search ──
        document.getElementById('user-search').addEventListener('input', function () {
            const q = this.value.toLowerCase();
            filterRows();
        });

        // ── Role filter ──
        document.querySelectorAll('.sb-filter-btn[data-role]').forEach(btn => {
            btn.addEventListener('click', () => {
                document.querySelectorAll('[data-role]').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                filterRows();
            });
        });

        // ── Sort ──
        document.querySelectorAll('[data-sort]').forEach(btn => {
            btn.addEventListener('click', () => {
                document.querySelectorAll('[data-sort]').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                sortRows(btn.dataset.sort);
            });
        });

        function filterRows() {
            const q    = document.getElementById('user-search').value.toLowerCase();
            const role = document.querySelector('[data-role].active')?.dataset.role || 'all';
            let visible = 0;

            document.querySelectorAll('#users-tbody tr').forEach(row => {
                const matchSearch = row.dataset.name.includes(q) || row.dataset.email.includes(q);
                const matchRole   = role === 'all' || row.dataset.role === role;
                const show = matchSearch && matchRole;
                row.style.display = show ? '' : 'none';
                if (show) visible++;
            });

            updateCount(visible);
        }

        function sortRows(mode) {
            const tbody = document.getElementById('users-tbody');
            const rows  = Array.from(tbody.querySelectorAll('tr'));

            rows.sort((a, b) => {
                if (mode === 'name')   return a.dataset.name.localeCompare(b.dataset.name);
                if (mode === 'newest') return b.dataset.joined - a.dataset.joined;
                if (mode === 'oldest') return a.dataset.joined - b.dataset.joined;
            });

            rows.forEach(r => tbody.appendChild(r));
        }

        function updateCount(n) {
            const visible = n ?? document.querySelectorAll('#users-tbody tr:not([style*="none"])').length;
            document.getElementById('user-count').textContent = visible + ' users';
        }
    </script>
@endsection
