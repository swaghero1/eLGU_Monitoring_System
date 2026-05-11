let allTasks = [], allLgus = [], allLogs = [], allUsers = [];
let isViewingDone = false;
let currentLguFilters = { status: 'For Engagement', version: 'All', province: 'All' };
let chartER, chartV1, chartV2, chartBPCO, chartConv;
let loggedInUser = '';

// ==========================================
// INITIALIZATION & SECURITY
// ==========================================
document.addEventListener("DOMContentLoaded", () => {
    if(localStorage.getItem('sidebarCollapsed') === 'true') {
        document.getElementById('appSidebar')?.classList.add('collapsed');
    }

    if (localStorage.getItem('theme') === 'dark') {
        document.body.setAttribute('data-theme', 'dark');
        const themeBtn = document.getElementById('themeToggleBtn'); if(themeBtn) themeBtn.innerText = 'Switch to Light Mode';
        const ll = document.getElementById('logo-light'); if(ll) ll.style.display = 'none';
        const ld = document.getElementById('logo-dark'); if(ld) ld.style.display = 'block';
    } else {
        const ll = document.getElementById('logo-light'); if(ll) ll.style.display = 'block';
        const ld = document.getElementById('logo-dark'); if(ld) ld.style.display = 'none';
    }
    
    if(typeof ChartDataLabels !== 'undefined') { Chart.register(ChartDataLabels); }
    checkAuthentication(); 
});

function toggleSidebar() {
    const sidebar = document.getElementById('appSidebar');
    if(!sidebar) return;
    sidebar.classList.toggle('collapsed');
    const isCollapsed = sidebar.classList.contains('collapsed');
    localStorage.setItem('sidebarCollapsed', isCollapsed ? 'true' : 'false');
    setTimeout(updateLiveCharts, 300);
}

async function checkAuthentication() {
    try {
        const res = await fetch('api/auth.php?action=check'); const data = await res.json();
        if(!data.logged_in) { window.location.href = 'login.html'; } 
        else {
            document.body.removeAttribute('style'); loggedInUser = data.username;
            loadAdminProfile(); fetchTasks(); fetchLgus(); fetchLogs(); fetchUsers(); fetchMyEmail();
        }
    } catch(e) { console.error("Auth check failed.", e); }
}

async function logoutUser() { await fetch('api/auth.php?action=logout'); window.location.href = 'login.html'; }

function toggleTheme() {
    const isDark = document.body.getAttribute('data-theme') === 'dark';
    const themeBtn = document.getElementById('themeToggleBtn');
    const ll = document.getElementById('logo-light'); const ld = document.getElementById('logo-dark');
    if (isDark) {
        document.body.removeAttribute('data-theme'); localStorage.setItem('theme', 'light');
        if(themeBtn) themeBtn.innerText = 'Switch to Dark Mode';
        if(ll) ll.style.display = 'block'; if(ld) ld.style.display = 'none';
    } else {
        document.body.setAttribute('data-theme', 'dark'); localStorage.setItem('theme', 'dark');
        if(themeBtn) themeBtn.innerText = 'Switch to Light Mode';
        if(ll) ll.style.display = 'none'; if(ld) ld.style.display = 'block';
    }
    updateLiveCharts(); 
}

function switchView(viewId, btnElement) {
    document.querySelectorAll('.view-section').forEach(el => el.classList.remove('active'));
    document.querySelectorAll('.nav-btn').forEach(el => el.classList.remove('active'));
    const targetView = document.getElementById(viewId); if(targetView) targetView.classList.add('active');
    if(btnElement) btnElement.classList.add('active');

    if(viewId === 'view-dashboard') { updateLiveCharts(); renderV2EngagementsTable(); }
    if(viewId === 'view-tasks') renderTasksTable();
    if(viewId === 'view-masterlist') renderMasterlistTable();
    if(viewId === 'view-v1') renderV1Table();
    if(viewId === 'view-v2') renderV2Table();
    if(viewId === 'view-bpco') renderBpcoTable();
    if(viewId === 'view-audit') renderSystemLogs(); 
    if(viewId === 'view-users') fetchUsers();
}

function loadAdminProfile() {
    const pic = localStorage.getItem('adminPic') || '';
    const navName = document.getElementById('navAdminName'); const navInitials = document.getElementById('navAvatarInitials');
    if(navName) navName.innerText = loggedInUser || 'System Admin';
    if(navInitials) navInitials.innerText = (loggedInUser || 'A').charAt(0).toUpperCase();
    const adminNameInput = document.getElementById('adminNameInput'); if(adminNameInput) adminNameInput.value = loggedInUser;

    if (pic) {
        const navAvatar = document.getElementById('navAvatar'); const modAvatar = document.getElementById('modalAvatar');
        if(navAvatar) { navAvatar.src = pic; navAvatar.style.display = 'block'; }
        if(modAvatar) { modAvatar.src = pic; modAvatar.style.display = 'block'; }
        if(navInitials) navInitials.style.display = 'none';
        const modInitials = document.getElementById('modalAvatarInitials'); if(modInitials) modInitials.style.display = 'none';
    }
}

function openAdminProfile() { document.getElementById('adminProfileModal')?.classList.add('active'); }

function saveAdminProfile() {
    const fileInput = document.getElementById('adminPicFile');
    if (fileInput && fileInput.files && fileInput.files.length > 0) {
        const reader = new FileReader();
        reader.onload = function(e) { localStorage.setItem('adminPic', e.target.result); loadAdminProfile(); document.getElementById('adminProfileModal')?.classList.remove('active'); };
        reader.readAsDataURL(fileInput.files[0]);
    } else { document.getElementById('adminProfileModal')?.classList.remove('active'); }
}

// ==========================================
// OTP & SECURITY SETTINGS
// ==========================================
async function fetchMyEmail() {
    try {
        const res = await fetch('api/auth.php?action=get_my_email');
        const data = await res.json();
        if(data.success && document.getElementById('settingsRecoveryEmail')) {
            document.getElementById('settingsRecoveryEmail').value = data.email || '';
        }
    } catch(e) { console.error(e); }
}

async function updateRecoveryEmail() {
    const email = document.getElementById('settingsRecoveryEmail').value;
    if(!email) return alert("Please enter an email address.");
    try {
        const res = await fetch('api/auth.php?action=update_my_email', {
            method: 'POST', body: JSON.stringify({email})
        });
        const data = await res.json();
        if(data.success) {
            alert("Recovery email successfully updated.");
            fetchLogs();
        } else alert("Failed to update email.");
    } catch(e) { alert("System error."); }
}

let cpExpTimer;
async function requestSettingsOTP() {
    document.getElementById('cpSendBtn').innerText = "Sending...";
    try {
        const res = await fetch(`api/auth.php?action=request_otp`, { method: 'POST', body: JSON.stringify({username: loggedInUser}) });
        const data = await res.json();
        if(data.success) {
            document.getElementById('cpRequestUI').style.display = 'none';
            document.getElementById('cpVerifyUI').style.display = 'block';
            let exp = 180;
            cpExpTimer = setInterval(() => {
                exp--; let m = Math.floor(exp / 60); let s = exp % 60;
                document.getElementById('cpTimer').innerText = `Code expires in: ${m}:${s < 10 ? '0' : ''}${s}`;
                if(exp <= 0) { clearInterval(cpExpTimer); document.getElementById('cpTimer').innerText = "Expired."; }
            }, 1000);
        } else { alert(data.message); document.getElementById('cpSendBtn').innerText = "Send OTP Code"; }
    } catch (e) { alert("Failed to connect."); }
}

async function submitNewPassword() {
    const code = document.getElementById('cpCode').value;
    const new_password = document.getElementById('cpNewPass').value;
    const res = await fetch(`api/auth.php?action=reset_password`, { method: 'POST', body: JSON.stringify({username: loggedInUser, code, new_password}) });
    const data = await res.json();
    if(data.success) { alert("Password changed securely."); document.getElementById('changePasswordModal').classList.remove('active'); } 
    else alert(data.message);
}

function openPasswordChangeModal() { document.getElementById('changePasswordModal').classList.add('active'); }

// ==========================================
// AUDIT & LOGIN LOGS
// ==========================================
async function fetchLogs() {
    try {
        const res = await fetch('api/get_logs.php'); if(res.ok) {
            const data = await res.json();
            if (data.success) { allLogs = data.data; renderSystemLogs(); }
        }
    } catch(e) { console.error("Log fetch failed", e); }
}

function renderSystemLogs() {
    const tbody = document.getElementById('auditTableBody'); if(!tbody) return; tbody.innerHTML = '';
    const systemLogs = allLogs.filter(log => {
        const a = log.action.toLowerCase();
        return !a.includes('logged into the system') && !a.includes('created a new account');
    });
    systemLogs.forEach(log => { 
        tbody.innerHTML += `<tr><td><strong style="color:var(--brand-blue);">${log.timestamp}</strong></td><td><strong style="color:var(--text-main);">${log.username}</strong></td><td>${log.action}</td></tr>`; 
    });
}

function openLoginLogsModal() {
    const tbody = document.getElementById('loginLogsTableBody'); if(!tbody) return; tbody.innerHTML = '';
    const loginLogs = allLogs.filter(log => {
        const a = log.action.toLowerCase();
        return a.includes('logged into the system') || a.includes('created a new account');
    });
    loginLogs.forEach(log => { 
        tbody.innerHTML += `<tr><td><strong style="color:var(--brand-blue);">${log.timestamp}</strong></td><td><strong style="color:var(--text-main);">${log.username}</strong></td><td>${log.action}</td></tr>`; 
    });
    document.getElementById('loginLogsModal').classList.add('active');
}

// ==========================================
// USER MANAGEMENT ENGINE
// ==========================================
async function fetchUsers() {
    try {
        const res = await fetch('api/get_users.php'); const data = await res.json();
        if(data.success) { allUsers = data.data; renderUsersTable(); populateTaskFilters(); }
    } catch (e) { console.error(e); }
}

function renderUsersTable() {
    const tbody = document.getElementById('usersTableBody'); if(!tbody) return; tbody.innerHTML = '';
    allUsers.forEach(u => {
        let currentStatus = u.status || 'Pending';
        let statColor = currentStatus === 'Approved' ? '#10b981' : (currentStatus === 'Disapproved' ? '#ef4444' : '#f59e0b');
        let actionBtns = '';
        if(currentStatus === 'Pending') {
            actionBtns = `
                <button class="btn-primary" style="padding:4px 8px; background:#10b981;" onclick="changeUserStatus(${u.id}, 'Approved')">Approve</button>
                <button class="btn-danger" style="padding:4px 8px;" onclick="changeUserStatus(${u.id}, 'Disapproved')">Disapprove</button>
            `;
        } else {
            actionBtns = `<button class="btn-primary" style="padding:4px 8px;" onclick="openUserModal(${u.id}, '${u.username}')">Edit</button> <button class="btn-danger" style="padding:4px 8px;" onclick="deleteUser(${u.id})">Delete</button>`;
        }

        tbody.innerHTML += `<tr>
            <td><strong>${u.username}</strong></td><td>${u.created_at}</td>
            <td><strong style="color:${statColor};">${currentStatus}</strong></td>
            <td>${actionBtns}</td>
        </tr>`;
    });
}

async function changeUserStatus(id, status) {
    if(!confirm(`Mark user as ${status}?`)) return;
    const res = await fetch(`api/manage_user.php?action=status`, { method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify({id, status}) });
    if(res.ok) { fetchUsers(); fetchLogs(); }
}

function openUserModal(id = null, username = '') {
    document.getElementById('manageUserId').value = id || '';
    document.getElementById('manageUserUsername').value = username;
    document.getElementById('manageUserPassword').value = '';
    document.getElementById('userModalTitle').innerText = id ? 'Edit System User' : 'Add New User';
    document.getElementById('passHint').style.display = id ? 'inline' : 'none';
    document.getElementById('manageUserPassword').required = !id; 
    document.getElementById('userModal').classList.add('active');
}

async function saveUser() {
    const id = document.getElementById('manageUserId').value;
    const username = document.getElementById('manageUserUsername').value;
    const password = document.getElementById('manageUserPassword').value;
    if(!username) return alert("Username is required.");
    
    if(!id) {
        if(!password) return alert("Password required for new users.");
        const res = await fetch('api/auth.php?action=register', { method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify({username, password, email: 'system@elgu.gov.ph'}) });
        const data = await res.json();
        if(data.success) { document.getElementById('userModal').classList.remove('active'); fetchUsers(); fetchLogs(); }
        else { alert("Error: " + data.message); }
    } else {
        const res = await fetch('api/manage_user.php?action=update', { method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify({id, username, password}) });
        const data = await res.json();
        if(data.success) { document.getElementById('userModal').classList.remove('active'); fetchUsers(); fetchLogs(); }
        else { alert("Error updating user."); }
    }
}

async function deleteUser(id) {
    if(!confirm("Are you sure you want to completely delete this user?")) return;
    const res = await fetch('api/manage_user.php?action=delete', { method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify({id}) });
    const data = await res.json();
    if(data.success) { fetchUsers(); fetchLogs(); } else { alert("Failed to delete."); }
}

// ==========================================
// LGU DATA FETCHING & FILTERING
// ==========================================
async function fetchLgus() {
    try {
        const res = await fetch('api/get_lgus.php');
        if (res.ok) { 
            const data = await res.json(); allLgus = data.data || []; 
            populateMasterlistFilters(); updateLiveCharts(); renderSummaryTable(); renderV2EngagementsTable();
            if(document.getElementById('view-masterlist')?.classList.contains('active')) renderMasterlistTable();
            if(document.getElementById('view-v1')?.classList.contains('active')) renderV1Table();
            if(document.getElementById('view-v2')?.classList.contains('active')) renderV2Table();
            if(document.getElementById('view-bpco')?.classList.contains('active')) renderBpcoTable();
            populateTaskLguDropdown();
        }
    } catch(e) { console.error("Failed to fetch LGUs", e); }
}

function populateMasterlistFilters() {
    let regs = new Set(), provs = new Set(), munis = new Set(), dists = new Set();
    allLgus.forEach(l => { if(l.region) regs.add(l.region); if(l.province) provs.add(l.province); if(l.municipality) munis.add(l.municipality); if(l.district) dists.add(l.district); });

    const fillSelects = (ids, set, defaultText) => {
        let options = `<option value="All">${defaultText}</option>`;
        [...set].sort().forEach(item => options += `<option value="${item}">${item}</option>`);
        ids.forEach(id => { const el = document.getElementById(id); if(el) el.innerHTML = options; });
    };

    fillSelects(['filterReg', 'filterRegV1', 'filterRegV2'], regs, 'All Regions');
    fillSelects(['filterProv', 'filterProvV1', 'filterProvV2'], provs, 'All Provinces');
    fillSelects(['filterMuni', 'filterMuniV1', 'filterMuniV2'], munis, 'All Municipalities');
    fillSelects(['filterDist', 'filterDistV1', 'filterDistV2'], dists, 'All Districts');

    const muniDatalist = document.getElementById('muniList');
    if(muniDatalist) { muniDatalist.innerHTML = ''; [...munis].sort().forEach(m => muniDatalist.innerHTML += `<option value="${m}">`); }
}

function getStatusColor(status) {
    if(!status) return 'var(--text-muted)';
    const s = status.toLowerCase();
    if(s.includes('operational') && !s.includes('non')) return '#10b981'; 
    if(s.includes('own system')) return '#3b82f6'; 
    if(s.includes('withdraw') || s.includes('non-operational')) return '#ef4444'; 
    if(s.includes('migration') || s.includes('testing') || s.includes('training') || s.includes('engagement') || s.includes('concerns')) return '#f59e0b'; 
    return 'var(--text-muted)'; 
}

function renderMasterlistTable() {
    const tbody = document.getElementById('masterlistTableBody'); if(!tbody) return; tbody.innerHTML = '';
    const search = (document.getElementById('searchMasterlist')?.value || '').toLowerCase();
    const fReg = document.getElementById('filterReg')?.value; const fProv = document.getElementById('filterProv')?.value;
    const fMuni = document.getElementById('filterMuni')?.value; const fDist = document.getElementById('filterDist')?.value;

    allLgus.filter(l => {
        if(search && !(l.municipality||'').toLowerCase().includes(search) && !(l.province||'').toLowerCase().includes(search) && !(l.overall_status||'').toLowerCase().includes(search)) return false;
        if(fReg && fReg !== 'All' && l.region !== fReg) return false;
        if(fProv && fProv !== 'All' && l.province !== fProv) return false;
        if(fMuni && fMuni !== 'All' && l.municipality !== fMuni) return false;
        if(fDist && fDist !== 'All' && l.district !== fDist) return false;
        return true;
    }).forEach(l => {
        
        let sysVersion = '<span style="color:var(--text-muted); font-weight:600;">None</span>';
        if (l.current_system_version === 'Own System') { sysVersion = '<span style="color:#3b82f6; font-weight:bold;">Own System</span>'; } 
        else if (l.current_system_version === 'V2') { sysVersion = '<span style="color:#10b981; font-weight:bold;">V2</span>'; } 
        else if (l.current_system_version === 'V1') { sysVersion = '<span style="color:#10b981; font-weight:bold;">V1</span>'; }

        // FIX: Force Pending if "None"
        let histStatus = l.overall_status || 'Pending';
        if (!l.current_system_version || l.current_system_version === 'None') {
            histStatus = 'Pending';
        }

        tbody.innerHTML += `
            <tr>
                <td><strong>${l.municipality}</strong><br><span class="text-muted">${l.mayor || 'No Mayor Listed'}</span></td>
                <td>${l.province}<br><span class="text-muted">${l.district || ''}</span></td>
                <td>${sysVersion}</td>
                <td><span style="font-weight:600; color:${getStatusColor(histStatus)};">${histStatus}</span></td>
                <td><button class="btn-primary" onclick="openEditLguModal(${l.id})">Edit Profile</button></td>
            </tr>`;
    });
}

function renderV1Table() {
    const tb = document.getElementById('v1TableBody'); if(!tb) return; tb.innerHTML = '';
    const search = (document.getElementById('searchV1')?.value || '').toLowerCase();
    const fReg = document.getElementById('filterRegV1')?.value;
    const fProv = document.getElementById('filterProvV1')?.value;
    const fMuni = document.getElementById('filterMuniV1')?.value;
    const fDist = document.getElementById('filterDistV1')?.value;

    allLgus.filter(l => {
        if(search && !(l.municipality||'').toLowerCase().includes(search) && !(l.province||'').toLowerCase().includes(search)) return false;
        if(fReg && fReg !== 'All' && l.region !== fReg) return false;
        if(fProv && fProv !== 'All' && l.province !== fProv) return false;
        if(fMuni && fMuni !== 'All' && l.municipality !== fMuni) return false;
        if(fDist && fDist !== 'All' && l.district !== fDist) return false;

        // V1 Exclusivity Check (Show if V1, OR if migrated from V1 to V2)
        if (l.current_system_version !== 'V1' && !(l.current_system_version === 'V2' && (l.converted_v1_to_v2 == 1 || l.previous_v1_user == 1))) {
            return false; 
        }
        return true;
    }).forEach(l => {
        let statusText = l.v1_status || 'Pending';
        let statusColor = getStatusColor(l.v1_status);
        if (l.current_system_version === 'V2' && (l.converted_v1_to_v2 == 1 || l.previous_v1_user == 1)) {
            statusText = 'Moved to V2';
            statusColor = '#8b5cf6';
        }

        tb.innerHTML += `<tr>
            <td><strong>${l.municipality}</strong><br><span class="text-muted">${l.province}</span></td>
            <td><span style="font-weight:600; color:${statusColor};">${statusText}</span></td>
            <td style="font-size:0.8rem; color:var(--text-muted);">
                LOI: ${l.v1_loi_date ? `<strong style="color:#10b981">✓ ${l.v1_loi_date}</strong>` : 'Pending'} |
                SB: ${l.v1_sb_reso_date ? `<strong style="color:#10b981">✓ ${l.v1_sb_reso_date}</strong>` : 'Pending'} <br>
                MOA: ${l.v1_moa_date ? `<strong style="color:#10b981">✓ ${l.v1_moa_date}</strong>` : 'Pending'} | 
                CBD: ${l.v1_cbd_date ? `<strong style="color:#10b981">✓ ${l.v1_cbd_date}</strong>` : 'Pending'}
            </td>
            <td><button class="btn-primary" onclick="openEditLguModal(${l.id}, 'tab-v1')">Manage V1</button></td>
        </tr>`;
    });
}

function renderV2Table() {
    const tb = document.getElementById('v2TableBody'); if(!tb) return; tb.innerHTML = '';
    const search = (document.getElementById('searchV2')?.value || '').toLowerCase();
    const fReg = document.getElementById('filterRegV2')?.value;
    const fProv = document.getElementById('filterProvV2')?.value;
    const fMuni = document.getElementById('filterMuniV2')?.value;
    const fDist = document.getElementById('filterDistV2')?.value;

    allLgus.filter(l => {
        if(search && !(l.municipality||'').toLowerCase().includes(search) && !(l.province||'').toLowerCase().includes(search)) return false;
        if(fReg && fReg !== 'All' && l.region !== fReg) return false;
        if(fProv && fProv !== 'All' && l.province !== fProv) return false;
        if(fMuni && fMuni !== 'All' && l.municipality !== fMuni) return false;
        if(fDist && fDist !== 'All' && l.district !== fDist) return false;

        // V2 STRICT EXCLUSIVITY
        if (l.current_system_version !== 'V2') return false; 
        return true;
    }).forEach(l => {
        tb.innerHTML += `<tr>
            <td><strong>${l.municipality}</strong><br><span class="text-muted">Score: ${l.ereadiness_score||0} (${l.e_readiness_label||'N/A'})</span></td>
            <td><span style="font-weight:600; color:${getStatusColor(l.v2_status)};">${l.v2_status || 'Pending'}</span><br>
                <span style="font-size:0.75rem; color:var(--text-muted)">Launch: ${l.v2_launch_date || 'TBA'}</span></td>
            <td style="font-size:0.8rem; color:var(--text-muted);">
                Sys UAT: <strong>${l.v2_uat_sys_status||'Pending'}</strong><br>Dry-Run: <strong>${l.v2_dry_run_date||'Pending'}</strong>
            </td>
            <td><button class="btn-primary" onclick="openEditLguModal(${l.id}, 'tab-v2')">Manage V2</button></td>
        </tr>`;
    });
}

function renderBpcoTable() {
    const tb = document.getElementById('bpcoTableBody'); if(!tb) return; tb.innerHTML = '';
    const s = (document.getElementById('searchBpco')?.value || '').toLowerCase();
    const filterLoi = document.getElementById('filterBpcoLoi')?.value || 'All';
    const filterDesig = document.getElementById('filterBpcoDesig')?.value || 'All';

    allLgus.filter(l => {
        if (s && !(l.municipality||'').toLowerCase().includes(s) && !(l.province||'').toLowerCase().includes(s)) return false;
        if (filterLoi === 'Submitted' && !l.bpco_loi_date) return false;
        if (filterLoi === 'Pending' && l.bpco_loi_date) return false;
        if (filterDesig === 'Submitted' && !l.bpco_desig_date) return false;
        if (filterDesig === 'Pending' && l.bpco_desig_date) return false;
        return true;
    }).forEach(l => {
        tb.innerHTML += `<tr>
            <td><strong>${l.municipality}</strong><br><span class="text-muted">${l.province}</span></td>
            <td><span style="font-weight:600; color:${getStatusColor(l.bpco_status)};">${l.bpco_status || 'Pending'}</span></td>
            <td style="font-size:0.8rem; color:var(--text-muted);">
                LOI: ${l.bpco_loi_date ? `<strong style="color:#10b981">✓ ${l.bpco_loi_date}</strong>` : 'Pending'} <br>
                Desig: ${l.bpco_desig_date ? `<strong style="color:#10b981">✓ ${l.bpco_desig_date}</strong>` : 'Pending'}
            </td>
            <td><button class="btn-primary" onclick="openEditLguModal(${l.id}, 'tab-bpco')">Manage BPCO</button></td>
        </tr>`;
    });
}

// ==========================================
// TASKS & SLA ENGINE
// ==========================================
async function fetchTasks() {
    try {
        const res = await fetch('api/get_tasks.php');
        if(res.ok) { 
            const data = await res.json(); 
            if (data.success) { 
                allTasks = data.data; 
                populateTaskFilters(); 
                renderTasksTable(); 
            } 
        }
    } catch(e) { console.error("Tasks fetch failed", e); }
}

function populateTaskFilters() {
    const pSelect = document.getElementById('taskPersonnelFilter');
    if(pSelect && allUsers) {
        let options = '<option value="All">All Personnel</option>';
        allUsers.forEach(u => options += `<option value="${u.username}">${u.username}</option>`);
        pSelect.innerHTML = options;
    }
}

function getUrgencyStyle(dueDate) {
    if(!dueDate) return '';
    const diff = Math.ceil((new Date(dueDate) - new Date()) / 86400000);
    if (diff <= 0) return 'color: #ef4444; font-weight: 800;'; 
    if (diff <= 7) return 'color: #f97316; font-weight: 800;'; 
    return 'color: var(--text-main);';
}

function renderTasksTable() {
    const tbody = document.getElementById('tasksTableBody'); if (!tbody) return; tbody.innerHTML = ''; 
    let filtered = allTasks.filter(t => t.status === (isViewingDone ? 'Completed' : 'Pending'));
    
    const persFilter = document.getElementById('taskPersonnelFilter')?.value || 'All';
    const urgFilter = document.getElementById('taskUrgencyFilter')?.value || 'All';
    const sortFilt = document.getElementById('taskSortFilter')?.value || 'due_asc';

    if(persFilter !== 'All') filtered = filtered.filter(t => t.personnel === persFilter);
    
    filtered = filtered.filter(t => {
        if(urgFilter === 'All') return true;
        const diff = Math.ceil((new Date(t.due_date) - new Date()) / 86400000);
        if(urgFilter === 'Overdue') return diff <= 0;
        if(urgFilter === 'Almost Due') return diff > 0 && diff <= 7;
        return true;
    });

    filtered.sort((a, b) => {
        if(sortFilt === 'latest') return b.id - a.id;
        const dA = new Date(a.due_date), dB = new Date(b.due_date);
        if(sortFilt === 'due_asc') return dA - dB;
        if(sortFilt === 'due_desc') return dB - dA;
        return 0;
    });

    filtered.forEach(task => tbody.appendChild(createTaskRow(task)));
    if (filtered.length === 0) { tbody.innerHTML = `<tr><td colspan="5" class="text-center text-muted">No ${isViewingDone ? 'completed' : 'pending'} tasks match your filters.</td></tr>`; }
}

function createTaskRow(t) {
    const tr = document.createElement('tr'); const safeDesc = t.description ? t.description.replace(/'/g, "\\'") : '';
    let action = isViewingDone ? `<span style="color:green; font-size:0.8rem;">✓ ${t.date_completed}</span>` : `<button class="btn-primary" style="padding:4px;" onclick="completeTask(${t.id}, '${safeDesc}', ${t.lgu_id})">Mark Done</button>`;
    
    // Clean System Tagging
    let sysTag = '';
    if(t.target_system && t.target_system !== 'General') {
        sysTag = ` <span style="font-size:0.7rem; color:var(--brand-blue);">(${t.target_system})</span>`;
    }

    tr.innerHTML = `<td><strong>${t.personnel}</strong></td><td>${t.municipality}${sysTag}</td><td>${t.description}</td><td><span style="font-size:0.7rem; display:block;">Started: ${t.date_started || 'N/A'}</span><strong style="${isViewingDone?'':getUrgencyStyle(t.due_date)}">Due: ${t.due_date}</strong></td><td>${action}</td>`;
    return tr;
}

function toggleTaskView() { isViewingDone = !isViewingDone; const btn = document.getElementById('viewDoneBtn'); if(btn) btn.innerText = isViewingDone ? 'View Pending' : 'View Completed'; renderTasksTable(); }

async function completeTask(id, desc, lguId) {
    if(!confirm("Mark complete?")) return;
    try {
        await fetch('api/complete_task.php', { method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify({ id: id }) });
        await fetchTasks();
        fetchLogs();
    } catch(e) { console.error(e); }
}

// INLINE V2 TASK MANAGER
function refreshInlineTasks(lguId) {
    const v2t = document.getElementById('v2TasksContainer'); if(!v2t) return;
    v2t.innerHTML = '';
    const lguTasks = allTasks.filter(t => t.lgu_id == lguId && t.status === 'Pending');
    if (lguTasks.length === 0) {
        v2t.innerHTML = '<span class="text-muted" style="font-size:0.8rem;">No pending action items.</span>';
    } else {
        lguTasks.forEach(t => {
            const safeDesc = t.description ? t.description.replace(/'/g, "\\'") : '';
            v2t.innerHTML += `
            <div style="display:flex; justify-content:space-between; align-items:center; background:#fef2f2; border-left:4px solid #A81C1C; padding:12px; margin-top:10px; border-radius:4px; font-size:0.85rem;">
                <div>
                    <strong style="color:var(--text-main);">${t.description}</strong><br>
                    <span style="color:var(--text-muted);">Assigned: <strong>${t.personnel}</strong> | Due: ${t.due_date}</span>
                </div>
                <button type="button" class="btn-primary" style="padding:6px 12px; font-size:0.8rem; background-color:#10b981;" onclick="completeTaskInline(${t.id}, '${safeDesc}', ${t.lgu_id})">✓ Mark Done</button>
            </div>`;
        });
    }
}

async function completeTaskInline(id, desc, lguId) {
    if(!confirm("Mark complete?")) return;
    try {
        await fetch('api/complete_task.php', { method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify({ id: id }) });
        await fetchTasks();
        refreshInlineTasks(lguId);
        fetchLogs();
    } catch(e) { console.error(e); }
}

function populateTaskLguDropdown() { const s = document.getElementById('taskLguSelect'); if(!s) return; s.innerHTML=''; allLgus.forEach(l => s.innerHTML+=`<option value="${l.id}">${l.municipality} (${l.province})</option>`); }

function toggleCustomAssignee() {
    const sel = document.getElementById('taskAssigneeSelect'); const customInput = document.getElementById('taskAssigneeCustom');
    if(sel && customInput) { if(sel.value === 'custom') { customInput.style.display = 'block'; customInput.required = true; customInput.value = ''; } else { customInput.style.display = 'none'; customInput.required = false; } }
}

function openAddTaskModal() { 
    const modal = document.getElementById('addTaskModal'); if(modal) modal.classList.add('active'); 
    const taskDesc = document.getElementById('taskDesc'); if(taskDesc) taskDesc.value = '';
    
    // Reset assigns
    const s = document.getElementById('taskAssigneeSelect'); if(s) {
        s.innerHTML = '<option value="">Select System User...</option>';
        allUsers.forEach(u => { s.innerHTML += `<option value="${u.username}">${u.username}</option>`; });
        s.innerHTML += `<option value="custom" style="font-weight:bold; color:var(--brand-blue);">+ Other (Type manually)</option>`;
    }
    toggleCustomAssignee(); 
}

function openAddTaskForLgu() {
    const lguId = document.getElementById('editLguId').value;
    if(!lguId) return alert("Please save the LGU profile first.");
    openAddTaskModal();
    const sel = document.getElementById('taskLguSelect');
    if(sel) { sel.value = lguId; }
}

function submitNewTask() {
    const lguId = document.getElementById('taskLguSelect')?.value || '';
    const lgu = allLgus.find(l => l.id == lguId);
    const targetSys = lgu ? (lgu.current_system_version || 'General') : 'General';
    let assignee = document.getElementById('taskAssigneeSelect')?.value;
    if(assignee === 'custom') { assignee = document.getElementById('taskAssigneeCustom')?.value || 'System'; }
    if(!assignee) return alert("Please select an Assignee.");
    
    fetch('api/add_task.php', { method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify({ lgu_id: lguId, personnel: assignee, description: document.getElementById('taskDesc')?.value || '', due_date: document.getElementById('taskDueDate')?.value || '', target_system: targetSys })})
    .then(async ()=> { 
        await fetchTasks(); fetchLogs(); 
        document.getElementById('addTaskModal')?.classList.remove('active'); 
        
        const modal = document.getElementById('editLguModal');
        if(modal && modal.classList.contains('active')) {
            refreshInlineTasks(lguId);
        }
    });
}

// ==========================================
// DASHBOARD DYNAMIC GRAPHICS ENGINE
// ==========================================
function updateLiveCharts() {
    if (!allLgus) return;
    const total = allLgus.length;
    
    const totalEl = document.getElementById('totalLguCount');
    if (totalEl) totalEl.innerText = total;

    const isDark = document.body.getAttribute('data-theme') === 'dark'; 
    Chart.defaults.color = isDark ? '#94a3b8' : '#334155';

    if (chartER) chartER.destroy(); if (chartV1) chartV1.destroy(); 
    if (chartV2) chartV2.destroy(); if (chartBPCO) chartBPCO.destroy(); if (chartConv) chartConv.destroy();

    let v1OpCount = 0;
    let v2OpCount = 0;
    let ownCount = 0;
    let noSysCount = 0;
    let bpcoOpCount = 0;

    allLgus.forEach(l => {
        let isOwn = l.current_system_version === 'Own System' || l.own_system == 1;
        let isV1 = l.current_system_version === 'V1';
        let isV2 = l.current_system_version === 'V2';
        
        if (isOwn) { ownCount++; }
        else if (isV2) { v2OpCount++; }
        else if (isV1) { v1OpCount++; }
        else { noSysCount++; }

        if (l.bpco_status === 'Operational') bpcoOpCount++;
    });

    const v1Pct = total > 0 ? ((v1OpCount / total) * 100).toFixed(2) : 0;
    const v2Pct = total > 0 ? ((v2OpCount / total) * 100).toFixed(2) : 0;
    const ownPct = total > 0 ? ((ownCount / total) * 100).toFixed(2) : 0;
    const noSysPct = total > 0 ? ((noSysCount / total) * 100).toFixed(2) : 0;
    const bpcoPct = total > 0 ? ((bpcoOpCount / total) * 100).toFixed(2) : 0;

    const penBody = document.getElementById('penetrationTableBody');
    if(penBody) {
        penBody.innerHTML = `
            <tr><td>eLGU Version 1</td><td>${v1OpCount}</td><td>${v1Pct}%</td></tr>
            <tr><td>eLGU Version 2</td><td>${v2OpCount}</td><td>${v2Pct}%</td></tr>
            <tr><td>Own System</td><td>${ownCount}</td><td>${ownPct}%</td></tr>
            <tr><td>No System</td><td>${noSysCount}</td><td>${noSysPct}%</td></tr>
            <tr><td>BPCO (Operational)</td><td>${bpcoOpCount}</td><td>${bpcoPct}%</td></tr>
        `;
    }

    const commonOpts = { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'left', labels: { boxWidth: 12, font: {size: 11} } }, datalabels: { color: isDark ? '#fff' : '#000', font: { weight: 'bold', size: 14 }, formatter: (value, ctx) => { return value > 0 ? value : ''; } } } };

    const erReady = allLgus.filter(l => l.e_readiness_label === 'Competitive Ready' || l.e_readiness_label === 'Ready' || l.e_readiness_label === 'Partially Ready' || l.e_readiness_label === 'Beginning Ready' || l.ereadiness_score >= 60).length;
    const erNotReady = allLgus.filter(l => l.e_readiness_label === 'Not Ready' && l.ereadiness_score > 0 && l.ereadiness_score < 60).length;
    const erOwn = allLgus.filter(l => l.current_system_version === 'Own System' || l.own_system == 1).length;
    const erNone = total - (erReady + erNotReady + erOwn);
    
    const erTitle = document.getElementById('ereadinessTitle'); if(erTitle) erTitle.innerText = `eReadiness Status of all LGUs (Total: ${total})`;
    const cvER = document.getElementById('ereadinessChart');
    if(cvER) chartER = new Chart(cvER, { type: 'doughnut', data: { labels: ['Digitally Ready', 'Not Ready', 'with own System', 'No eReadiness'], datasets: [{ data: [erReady, erNotReady, erOwn, erNone], backgroundColor: ['#0d47a1', '#fef08a', '#e2e8f0', '#b91c1c'], borderColor: isDark ? '#1e293b' : '#fff' }] }, options: {...commonOpts, cutout: '40%'} });

    const v1Provs = {}; allLgus.filter(l => l.current_system_version === 'V1').forEach(l => { v1Provs[l.province] = (v1Provs[l.province] || 0) + 1; });
    const v1T = document.getElementById('v1Title'); if(v1T) v1T.innerText = `LGUs with eLGU-BPLS v1`;
    const cvV1 = document.getElementById('v1ProvChart');
    if(cvV1) chartV1 = new Chart(cvV1, { type: 'pie', data: { labels: Object.keys(v1Provs), datasets: [{ data: Object.values(v1Provs), backgroundColor: ['#3b82f6', '#ef4444', '#f59e0b', '#10b981', '#f97316', '#06b6d4', '#8b5cf6', '#a855f7'], borderColor: isDark ? '#1e293b' : '#fff' }] }, options: commonOpts });

    const v2Op = allLgus.filter(l => l.current_system_version === 'V2' && l.v2_status === 'Operational').length;
    const v2Mig = allLgus.filter(l => l.current_system_version === 'V2' && (l.v2_status === 'Data Migration' || l.v2_migration_status === 'On-going')).length;
    const v2Pend = allLgus.filter(l => l.current_system_version === 'V2' && (l.v2_status === 'Pending' || l.v2_status === 'For Engagement' || l.v2_status === 'Lacking Documentary Requirements')).length;
    const v2Total = v2Op + v2Mig + v2Pend;
    const v2T = document.getElementById('v2Title'); if(v2T) v2T.innerText = `LGUs with eLGU v2 Pipelines (Total: ${v2Total})`;
    const cvV2 = document.getElementById('v2StatChart');
    if(cvV2) chartV2 = new Chart(cvV2, { type: 'doughnut', data: { labels: ['Operational', 'On going Data Migration', 'Pending / For Engagement'], datasets: [{ data: [v2Op, v2Mig, v2Pend], backgroundColor: ['#f59e0b', '#3b82f6', '#ef4444'], borderColor: isDark ? '#1e293b' : '#fff' }] }, options: {...commonOpts, cutout: '30%'} });

    const bpcoProvs = {}; allLgus.filter(l => l.bpco_status === 'Operational').forEach(l => { bpcoProvs[l.province] = (bpcoProvs[l.province] || 0) + 1; });
    const bpcoT = document.getElementById('bpcoTitle'); if(bpcoT) bpcoT.innerText = `LGUs w/ Operational BPCO`;
    const cvBPCO = document.getElementById('bpcoProvChart');
    if(cvBPCO) chartBPCO = new Chart(cvBPCO, { type: 'pie', data: { labels: Object.keys(bpcoProvs), datasets: [{ data: Object.values(bpcoProvs), backgroundColor: ['#3b82f6', '#ef4444', '#f59e0b', '#10b981', '#f97316', '#06b6d4'], borderColor: isDark ? '#1e293b' : '#fff' }] }, options: commonOpts });

    const convertedCount = allLgus.filter(l => l.converted_v1_to_v2 == 1 || l.previous_v1_user == 1).length;
    const newV2 = v2Total > convertedCount ? v2Total - convertedCount : 0;
    const cvConv = document.getElementById('conversionChart');
    if(cvConv) {
        chartConv = new Chart(cvConv, { 
            type: 'pie', 
            data: { labels: ['Migrated from V1', 'Native V2 Setup'], datasets: [{ data: [convertedCount, newV2], backgroundColor: ['#8b5cf6', '#3b82f6'], borderColor: isDark ? '#1e293b' : '#fff' }] }, 
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' }, datalabels: { color: isDark ? '#fff' : '#000', font: { weight: 'bold', size: 14 }, formatter: (value, ctx) => { return value > 0 ? value : ''; } } } } 
        });
    }
}

function renderV2EngagementsTable() {
    const p = ["Zamboanga del Norte", "Zamboanga del Sur", "Zamboanga Sibugay", "Basilan", "Sulu", "Tawi-Tawi"];
    const tb = document.getElementById('v2EngagementsBody'); if(!tb) return; tb.innerHTML = '';
    let totals = [0,0,0,0,0,0,0,0,0];
    
    p.forEach(prov => {
        const l = allLgus.filter(x => x.province === prov && x.current_system_version === 'V2');
        const mig = l.filter(x => x.v2_status === 'Data Migration' || x.v2_migration_status === 'On-going').length;
        const egTest = l.filter(x => x.v2_uat_sys_status === 'On-going').length; 
        const egAcc = l.filter(x => x.v2_status === 'For Training').length; 
        const egEnr = l.filter(x => x.v2_status === 'Ongoing Engagement').length; 
        const trn = l.filter(x => x.v2_training_status === 'On-going' || x.v2_status === 'For Training').length;
        const eReadLoi = l.filter(x => x.v2_status === 'Lacking Documentary Requirements').length;
        const pres = l.filter(x => x.v2_status === 'For Engagement').length;
        const egApp = l.filter(x => x.v2_uat_sys_status === 'Completed').length;
        const withdr = l.filter(x => x.v2_status === 'Withdraw').length;

        const rowData = [mig, egTest, egAcc, egEnr, trn, eReadLoi, pres, egApp, withdr];
        totals = totals.map((val, i) => val + rowData[i]);
        tb.innerHTML += `<tr><td><strong>${prov}</strong></td><td>${mig}</td><td>${egTest}</td><td>${egAcc}</td><td>${egEnr}</td><td>${trn}</td><td>${eReadLoi}</td><td>${pres}</td><td>${egApp}</td><td>${withdr}</td></tr>`;
    });

    const foot = document.getElementById('v2EngagementsFoot');
    if(foot) { foot.innerHTML = `<tr><td><strong>TOTAL</strong></td><td>${totals[0]}</td><td>${totals[1]}</td><td>${totals[2]}</td><td>${totals[3]}</td><td>${totals[4]}</td><td>${totals[5]}</td><td>${totals[6]}</td><td>${totals[7]}</td><td>${totals[8]}</td></tr>`; }
}

function renderSummaryTable() {
    const zampenProvinces = ["Zamboanga del Norte", "Zamboanga del Sur", "Zamboanga Sibugay", "Zamboanga City", "Isabela City"];
    const basultaProvinces = ["Basilan", "Sulu", "Tawi-Tawi"];
    const allProvinces = [...zampenProvinces, ...basultaProvinces];
    let zp=[0,0,0,0,0], bs=[0,0,0,0,0]; const tb = document.getElementById('summaryTableBody'); if(!tb) return; tb.innerHTML='';
    
    allProvinces.forEach(prov => {
        const l = allLgus.filter(x=>x.province===prov); const c=l.length;
        const v1=l.filter(x=>x.current_system_version==='V1').length; 
        const v2=l.filter(x=>x.current_system_version==='V2').length;
        const forTrn=l.filter(x=>x.current_system_version==='V2' && x.v2_status==='For Training').length; 
        const onTrn=l.filter(x=>x.current_system_version==='V2' && (x.v2_status==='Ongoing Engagement' || x.v2_training_status==='On-going')).length;
        if(zampenProvinces.includes(prov)) zp=zp.map((v,i)=>v+[c,v1,v2,forTrn,onTrn][i]); else bs=bs.map((v,i)=>v+[c,v1,v2,forTrn,onTrn][i]);
        tb.innerHTML += `<tr><td>${prov}</td><td>${c}</td><td>${v1}</td><td>${v2}</td><td>${forTrn}</td><td>${onTrn}</td></tr>`;
    });
    
    const foot = document.getElementById('summaryTableFoot');
    if(foot) { foot.innerHTML = `<tr><td colspan="6"></td></tr><tr style="background:var(--bg-color); color:var(--brand-blue);"><td><strong>Zamboanga Peninsula</strong></td><td>${zp[0]}</td><td>${zp[1]}</td><td>${zp[2]}</td><td>${zp[3]}</td><td>${zp[4]}</td></tr><tr style="background:var(--bg-color); color:var(--brand-blue);"><td><strong>BASULTA</strong></td><td>${bs[0]}</td><td>${bs[1]}</td><td>${bs[2]}</td><td>${bs[3]}</td><td>${bs[4]}</td></tr>`; }
}

// ==========================================
// MASS IMPORT ENGINE
// ==========================================
async function importCSVData() {
    const fileInput = document.getElementById('importCsvFile');
    if (!fileInput || !fileInput.files || fileInput.files.length === 0) return alert("Please select a CSV file first.");
    
    const btn = document.getElementById('importBtn');
    if(btn) { btn.innerText = "⏳ Processing..."; btn.disabled = true; }

    const formData = new FormData();
    formData.append('csv_file', fileInput.files[0]);

    try {
        const res = await fetch('api/import_csv.php', { method: 'POST', body: formData });
        const data = await res.json();
        
        if (data.success) {
            alert(data.message);
            fileInput.value = '';
            fetchLgus(); fetchLogs(); 
            if(btn) { btn.innerText = "🚀 Run Import Sequence"; btn.disabled = false; }
        } else {
            alert("Import Failed: " + data.message);
            if(btn) { btn.innerText = "🚀 Run Import Sequence"; btn.disabled = false; }
        }
    } catch (e) {
        console.error("Network Error during import:", e);
        alert("Network Error: Could not connect to the import engine.");
        if(btn) { btn.innerText = "🚀 Run Import Sequence"; btn.disabled = false; }
    }
}

// ==========================================
// MASTER PROFILE (MODAL LOGIC)
// ==========================================
function switchProfTab(id) {
    document.querySelectorAll('.prof-tab').forEach(e=>e.style.display='none');
    document.querySelectorAll('.tab-btn').forEach(e=>e.classList.remove('active'));
    const targetTab = document.getElementById(id);
    if(targetTab) targetTab.style.display='block'; 
    if(event && event.target && event.target.classList) event.target.classList.add('active');
}

// AUTO-SAVE ENGINE FOR V2 WATERFALL & STATUS SYNC
let autoSaveTimeout;
function checkV2Waterfall() {
    const scoreInput = document.getElementById('editScore').value;
    const s = parseFloat(scoreInput);
    
    let label = 'Not Ready';
    if (!scoreInput || isNaN(s)) label = 'Pending Validation';
    else if (s >= 95) label = 'Competitive Ready'; 
    else if (s >= 85) label = 'Ready'; 
    else if (s >= 70) label = 'Partially Ready'; 
    else if (s >= 60) label = 'Beginning Ready';
    
    const labelEl = document.getElementById('editScoreLabel');
    if(labelEl) labelEl.value = label;

    const p1 = document.getElementById('v2_phase_1');
    const p2 = document.getElementById('v2_phase_2');
    const p3 = document.getElementById('v2_phase_3');
    const p4 = document.getElementById('v2_phase_4');
    const p5 = document.getElementById('v2_phase_5');
    const p6 = document.getElementById('v2_phase_6');
    const p7 = document.getElementById('v2_phase_7');

    if(!p1) return; 

    let currentAutoStatus = 'Pending';

    p1.classList.add('locked-phase'); p2.classList.add('locked-phase'); p3.classList.add('locked-phase');
    p4.classList.add('locked-phase'); p5.classList.add('locked-phase'); p6.classList.add('locked-phase'); p7.classList.add('locked-phase');

    if(scoreInput && !isNaN(s)) {
        if(s >= 60) {
            p1.classList.remove('locked-phase');
            currentAutoStatus = 'Lacking Documentary Requirements';
            
            const loi = document.getElementById('v2Loi').value;
            const sb = document.getElementById('v2Sb').value;
            const mou = document.getElementById('v2Mou').value;
            const cbd = document.getElementById('v2Cbd').value;
            
            if(loi && sb && mou && cbd) {
                p2.classList.remove('locked-phase');
                p3.classList.remove('locked-phase');
                currentAutoStatus = 'Ongoing Engagement';
                
                const cbdApp = document.getElementById('v2CbdApproved').value;
                if(cbdApp) {
                    p4.classList.remove('locked-phase');
                    currentAutoStatus = 'For Training';
                    
                    const trnStat = document.getElementById('v2TrnStat').value;
                    if(trnStat === 'Completed') {
                        p5.classList.remove('locked-phase');
                        currentAutoStatus = 'On-Going Testing and Data Build-up';
                        
                        const uatSys = document.getElementById('v2UatSys').value;
                        const uatEgov = document.getElementById('v2UatEgovStat').value;
                        if(uatSys === 'Completed' && uatEgov === 'Completed') {
                            p6.classList.remove('locked-phase');
                            
                            const dryStat = document.getElementById('v2DryStat').value;
                            if(dryStat === 'Completed') {
                                p7.classList.remove('locked-phase');
                                currentAutoStatus = 'Data Migration';
                                
                                const migStat = document.getElementById('v2MigStat').value;
                                if(migStat === 'Completed') {
                                    currentAutoStatus = 'For Engagement'; 
                                    
                                    const launchStat = document.getElementById('v2LaunchStat').value;
                                    if(launchStat === 'Completed') { currentAutoStatus = 'Operational'; }
                                }
                            }
                        }
                    }
                }
            }
        } else { if (s > 0) currentAutoStatus = 'eReadiness for Rechecking'; }
    }

    const statusSelect = document.getElementById('v2Status');
    const manualOverrides = ['Withdraw', 'With Concerns', 'Non-Operational', 'N/A', 'Own System'];
    
    if (statusSelect && !manualOverrides.includes(statusSelect.value)) {
        if (statusSelect.value !== currentAutoStatus) { statusSelect.value = currentAutoStatus; }
    }

    // SYNC MASTER STATUS TO V2 WATERFALL STATUS
    const masterStatus = document.getElementById('editStatus');
    if(masterStatus && document.getElementById('editSystemVersion').value === 'V2') {
        masterStatus.value = currentAutoStatus;
    }

    clearTimeout(autoSaveTimeout);
    autoSaveTimeout = setTimeout(() => { submitEditLgu(true); }, 1000);
}

// SYNC V1 & NONE STATUS TO MASTER LIST
function syncMasterStatus() {
    const sys = document.getElementById('editSystemVersion').value;
    const masterStatus = document.getElementById('editStatus');
    
    if (sys === 'None') {
        if(masterStatus) masterStatus.value = 'Pending';
    } else if (sys === 'V1') {
        const v1Stat = document.getElementById('v1Status').value;
        if(masterStatus) masterStatus.value = v1Stat;
    }
    
    clearTimeout(autoSaveTimeout);
    autoSaveTimeout = setTimeout(() => { submitEditLgu(true); }, 1000);
}

function setupFileLinkView(inputId, linkValue) {
    const hiddenInput = document.getElementById(inputId + 'Link');
    const viewDiv = document.getElementById(inputId + 'View');
    if(hiddenInput) hiddenInput.value = linkValue || '';
    if(viewDiv) { 
        viewDiv.innerHTML = linkValue 
            ? `<a href="${linkValue}" target="_blank" class="file-view-btn"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg> View / Download Uploaded File</a>` 
            : '<span class="text-muted" style="font-size:0.8rem; display:inline-flex; align-items:center; gap:5px; margin-top:8px;"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"></path><polyline points="13 2 13 9 20 9"></polyline></svg> No file uploaded</span>'; 
    }
}

// FIX: ISOLATED MODALS AND V2 MIGRATION
function openEditLguModal(id, targetTab = 'tab-general') {
    const l = allLgus.find(x => x.id == id); if (!l) return;
    
    document.getElementById('editLguId').value = l.id;
    document.getElementById('profLguName').innerText = `${l.municipality} Master Profile`;
    document.getElementById('profLguGeo').innerText = l.province;

    const tabHeaders = document.getElementById('modalTabHeaders');
    if (tabHeaders) { tabHeaders.style.display = (targetTab !== 'tab-general') ? 'none' : 'flex'; }

    if(document.getElementById('editRegion')) document.getElementById('editRegion').value = l.region || 'Region IX';
    if(document.getElementById('editProvince')) document.getElementById('editProvince').value = l.province || '';
    if(document.getElementById('editMunicipality')) document.getElementById('editMunicipality').value = l.municipality || '';
    if(document.getElementById('editDistrict')) document.getElementById('editDistrict').value = l.district || '';
    if(document.getElementById('editIncome')) document.getElementById('editIncome').value = l.income_class || '';
    
    if(document.getElementById('editMayor')) document.getElementById('editMayor').value = l.mayor || '';
    if(document.getElementById('editScore')) document.getElementById('editScore').value = l.ereadiness_score || '';
    if(document.getElementById('editScoreLabel')) document.getElementById('editScoreLabel').value = l.e_readiness_label || 'Not Ready';
    
    // Overall Status
    let histStatus = l.overall_status || 'Pending';
    if (!l.current_system_version || l.current_system_version === 'None') histStatus = 'Pending';
    if(document.getElementById('editStatus')) document.getElementById('editStatus').value = histStatus;
    
    if(document.getElementById('editSystemVersion')) document.getElementById('editSystemVersion').value = l.current_system_version || 'None';
    if(document.getElementById('editBpcoView')) document.getElementById('editBpcoView').value = l.bpco_status || 'Pending';

    if(document.getElementById('v1Status')) document.getElementById('v1Status').value = l.v1_status || 'Pending';
    if(document.getElementById('v1Loi')) document.getElementById('v1Loi').value = l.v1_loi_date || '';
    if(document.getElementById('v1Sb')) document.getElementById('v1Sb').value = l.v1_sb_reso_date || '';
    if(document.getElementById('v1Moa')) document.getElementById('v1Moa').value = l.v1_moa_date || '';
    if(document.getElementById('v1Cbd')) document.getElementById('v1Cbd').value = l.v1_cbd_date || '';
    setupFileLinkView('v1Loi', l.v1_loi_link); setupFileLinkView('v1Sb', l.v1_sb_link); setupFileLinkView('v1Moa', l.v1_moa_link); setupFileLinkView('v1Cbd', l.v1_cbd_link);
    
    // V1 MIGRATION BUTTON & BANNER
    let isV1DocsComplete = (l.v1_loi_date && l.v1_sb_reso_date && l.v1_moa_date && l.v1_cbd_date);
    const v1BtnArea = document.getElementById('v1MigrateBtnArea');
    if (v1BtnArea) {
        if (l.current_system_version === 'V2' && (l.converted_v1_to_v2 == 1 || l.previous_v1_user == 1)) {
            v1BtnArea.innerHTML = `<div style="background:#f3e8ff; color:#6d28d9; padding:10px; border-radius:6px; font-weight:bold; text-align:center;">This LGU has been officially moved to the V2 Pipeline. Previous files remain safely stored.</div>`;
        } else if (isV1DocsComplete && l.current_system_version !== 'V2') {
            v1BtnArea.innerHTML = `<button type="button" class="btn-primary" style="background:#8b5cf6;" onclick="migrateV1toV2(${l.id})">🚀 Migrate to V2 Pipeline</button>`;
        } else { v1BtnArea.innerHTML = ''; }
    }

    if(document.getElementById('v2Status')) document.getElementById('v2Status').value = l.v2_status || 'Pending';
    if(document.getElementById('v2Loi')) document.getElementById('v2Loi').value = l.v2_loi_date || '';
    if(document.getElementById('v2Sb')) document.getElementById('v2Sb').value = l.v2_sb_reso_date || '';
    if(document.getElementById('v2Mou')) document.getElementById('v2Mou').value = l.v2_mou_date || '';
    if(document.getElementById('v2Cbd')) document.getElementById('v2Cbd').value = l.v2_cbd_date || '';
    setupFileLinkView('v2Loi', l.v2_loi_link); setupFileLinkView('v2Sb', l.v2_sb_link); setupFileLinkView('v2Mou', l.v2_mou_link); setupFileLinkView('v2Cbd', l.v2_cbd_link);
    setupFileLinkView('v2Ereadiness', l.v2_ereadiness_link);
    
    if(document.getElementById('v2CbdEndorsed')) document.getElementById('v2CbdEndorsed').value = l.v2_cbd_endorsed || '';
    if(document.getElementById('v2CbdApproved')) document.getElementById('v2CbdApproved').value = l.v2_cbd_approved || '';
    
    if(document.getElementById('v2TrnDate')) document.getElementById('v2TrnDate').value = l.v2_training_date || '';
    if(document.getElementById('v2TrnM')) document.getElementById('v2TrnM').value = l.v2_training_pax_m || 0;
    if(document.getElementById('v2TrnF')) document.getElementById('v2TrnF').value = l.v2_training_pax_f || 0;
    if(document.getElementById('v2TrnCon')) document.getElementById('v2TrnCon').value = l.v2_training_consent || 0;
    if(document.getElementById('v2TrnDes')) document.getElementById('v2TrnDes').value = l.v2_training_desig || 0;
    if(document.getElementById('v2TrnStat')) document.getElementById('v2TrnStat').value = l.v2_training_status || 'Pending';
    setupFileLinkView('v2TrnAar', l.v2_training_aar);
    
    if(document.getElementById('v2UatSys')) document.getElementById('v2UatSys').value = l.v2_uat_sys_status || 'Pending';
    if(document.getElementById('v2UatSysRem')) document.getElementById('v2UatSysRem').value = l.v2_uat_sys_rem || '';
    if(document.getElementById('v2UatEgovStat')) document.getElementById('v2UatEgovStat').value = l.v2_uat_egov_stat || 'Pending';
    if(document.getElementById('v2UatEgovRem')) document.getElementById('v2UatEgovRem').value = l.v2_uat_egov_rem || '';
    
    if(document.getElementById('v2Dry')) document.getElementById('v2Dry').value = l.v2_dry_run_date || '';
    if(document.getElementById('v2DryStat')) document.getElementById('v2DryStat').value = l.v2_dry_run_status || 'Pending';
    if(document.getElementById('v2DryM')) document.getElementById('v2DryM').value = l.v2_dry_run_pax_m || 0;
    if(document.getElementById('v2DryF')) document.getElementById('v2DryF').value = l.v2_dry_run_pax_f || 0;
    setupFileLinkView('v2DryAar', l.v2_dry_run_aar);
    
    if(document.getElementById('v2MigStat')) document.getElementById('v2MigStat').value = l.v2_migration_status || 'Pending';
    if(document.getElementById('v2MigRem')) document.getElementById('v2MigRem').value = l.v2_migration_remarks || '';
    
    if(document.getElementById('v2Launch')) document.getElementById('v2Launch').value = l.v2_launch_date || '';
    if(document.getElementById('v2LaunchPax')) document.getElementById('v2LaunchPax').value = l.v2_launch_pax || 0;
    if(document.getElementById('v2LaunchStat')) document.getElementById('v2LaunchStat').value = l.v2_launch_status || 'Pending';
    
    const v2Header = document.querySelector('#v2_phase_0 .section-header');
    if (v2Header) {
        if (l.converted_v1_to_v2 == 1 || l.previous_v1_user == 1) {
            v2Header.innerHTML = `0. eReadiness Phase <span class="badge" style="background:#fef08a; color:#b45309; margin-left:10px;">Previous V1 system user</span>`;
        } else { v2Header.innerHTML = `0. eReadiness Phase`; }
    }

    refreshInlineTasks(l.id);
    checkV2Waterfall(); 

    if(document.getElementById('bpcoStatus')) document.getElementById('bpcoStatus').value = l.bpco_status || 'Pending';
    if(document.getElementById('bpcoLoi')) document.getElementById('bpcoLoi').value = l.bpco_loi_date || '';
    if(document.getElementById('bpcoDesig')) document.getElementById('bpcoDesig').value = l.bpco_desig_date || '';
    setupFileLinkView('bpcoLoi', l.bpco_loi_link); setupFileLinkView('bpcoDesig', l.bpco_desig_link);

    if(document.getElementById('bpcoTrnStart')) document.getElementById('bpcoTrnStart').value = l.bpco_trn_start || '';
    if(document.getElementById('bpcoTrnEnd')) document.getElementById('bpcoTrnEnd').value = l.bpco_trn_end || '';
    if(document.getElementById('bpcoTrnM')) document.getElementById('bpcoTrnM').value = l.bpco_trn_m || 0;
    if(document.getElementById('bpcoTrnF')) document.getElementById('bpcoTrnF').value = l.bpco_trn_f || 0;

    if(document.getElementById('bpcoDbStart')) document.getElementById('bpcoDbStart').value = l.bpco_db_start || '';
    if(document.getElementById('bpcoDbEnd')) document.getElementById('bpcoDbEnd').value = l.bpco_db_end || '';
    if(document.getElementById('bpcoDbM')) document.getElementById('bpcoDbM').value = l.bpco_db_m || 0;
    if(document.getElementById('bpcoDbF')) document.getElementById('bpcoDbF').value = l.bpco_db_f || 0;

    const cBody = document.getElementById('contactsTableBody');
    if(cBody) { cBody.innerHTML = ''; if(l.contacts && l.contacts.length > 0) { l.contacts.forEach(c => addContactRow(c)); } }

    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    const btn = document.querySelector(`button[onclick="switchProfTab('${targetTab}')"]`);
    if(btn) btn.classList.add('active');

    switchProfTab(targetTab); 
    document.getElementById('editLguModal').classList.add('active');
}

async function migrateV1toV2(lguId) {
    if(!confirm("Are you sure you want to migrate this LGU to Version 2? They will be marked as 'Moved to V2' in V1 Monitoring, and all their previously uploaded V1 files will remain safely stored.")) return;
    
    document.getElementById('editSystemVersion').value = 'V2';
    document.getElementById('v2Status').value = 'Pending';
    checkV2Waterfall(); 
    await submitEditLgu(false, true); 
}

function addContactRow(c = {}) {
    const tbody = document.getElementById('contactsTableBody'); if(!tbody) return;
    const tr = document.createElement('tr');
    tr.innerHTML = `
        <td><input type="text" placeholder="Name" class="modern-input contact-name" value="${c.name || ''}" style="margin-bottom:4px; padding:6px;"><input type="email" placeholder="Email" class="modern-input contact-email" value="${c.email || ''}" style="padding:6px;"></td>
        <td><input type="text" placeholder="Contact #" class="modern-input contact-phone" value="${c.contact_number || ''}" style="padding:6px;"></td>
        <td><input type="text" placeholder="Role (e.g. BPLO)" class="modern-input contact-role" value="${c.role || ''}" style="margin-bottom:4px; padding:6px;"><input type="text" placeholder="Designation" class="modern-input contact-desig" value="${c.designation || ''}" style="padding:6px;"></td>
        <td><select class="modern-input contact-status" style="padding:6px;"><option value="1" ${c.is_active == 1 ? 'selected' : ''}>Active</option><option value="0" ${c.is_active == 0 ? 'selected' : ''}>Inactive</option></select></td>
        <td><button type="button" class="btn-danger" style="padding:6px 10px; font-size:0.75rem;" onclick="this.parentElement.parentElement.remove()">Remove</button></td>
    `;
    tbody.appendChild(tr);
}

async function uploadSingleFile(fileElementId) {
    const input = document.getElementById(fileElementId);
    if (!input || !input.files || input.files.length === 0) return null;
    const formData = new FormData(); formData.append('file', input.files[0]);
    try {
        const res = await fetch('api/upload.php', { method: 'POST', body: formData });
        const data = await res.json();
        if (data.success) return data.file_path; return null;
    } catch (e) { console.error("Upload failed", e); return null; }
}

async function submitEditLgu(isAutoSave = false, triggerV1toV2 = false) {
    const id = document.getElementById('editLguId')?.value; if(!id) return;
    const btn = document.getElementById('saveLguBtn');
    if(!isAutoSave && btn) { btn.innerText = "Uploading & Saving..."; btn.disabled = true; }

    let score = parseFloat(document.getElementById('editScore')?.value) || 0;
    let status = document.getElementById('editStatus')?.value || 'For Engagement';
    let label = document.getElementById('editScoreLabel')?.value || 'Not Ready';
    let sysVersion = document.getElementById('editSystemVersion')?.value || 'None';

    // Enforcement: If system is None, Overall Status is Pending
    if (sysVersion === 'None') {
        status = 'Pending';
    } else if(!isAutoSave) {
        if(score >= 60 && status !== 'Passed') { 
            status = 'Passed'; fetch('api/add_task.php', { method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify({ lgu_id: id, personnel: loggedInUser || 'System User', description: 'eReadiness Validation', due_date: new Date().toISOString().split('T')[0], target_system: 'V2' }) }); 
        } else if (score > 0 && score < 60 && status !== 'Failed') {
            status = 'Failed'; fetch('api/add_task.php', { method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify({ lgu_id: id, personnel: loggedInUser || 'System User', description: 'eReadiness Rechecking', due_date: new Date(new Date().setDate(new Date().getDate() + 7)).toISOString().split('T')[0], target_system: 'V2' }) });
        }
    }

    let v2LaunchStat = document.getElementById('v2LaunchStat')?.value || 'Pending';
    let bpcoStat = document.getElementById('bpcoStatus')?.value || 'Pending';

    const contactArr = [];
    document.querySelectorAll('#contactsTableBody tr').forEach(tr => {
        contactArr.push({ name: tr.querySelector('.contact-name').value, email: tr.querySelector('.contact-email').value, contact_number: tr.querySelector('.contact-phone').value, role: tr.querySelector('.contact-role').value, designation: tr.querySelector('.contact-desig').value, is_active: tr.querySelector('.contact-status').value });
    });

    const payload = {
        id: id, overall_status: status, ereadiness_score: score, e_readiness_label: label,
        region: document.getElementById('editRegion')?.value || '', province: document.getElementById('editProvince')?.value || '', municipality: document.getElementById('editMunicipality')?.value || '', district: document.getElementById('editDistrict')?.value || '', mayor: document.getElementById('editMayor')?.value || '', income_class: document.getElementById('editIncome')?.value || '',
        current_system_version: sysVersion, trigger_v1_to_v2: triggerV1toV2,
        
        v1_status: document.getElementById('v1Status')?.value || '', v1_loi_date: document.getElementById('v1Loi')?.value || '', v1_loi_link: await uploadSingleFile('v1LoiFile') || document.getElementById('v1LoiLink')?.value || '', v1_sb_reso_date: document.getElementById('v1Sb')?.value || '', v1_sb_link: await uploadSingleFile('v1SbFile') || document.getElementById('v1SbLink')?.value || '', v1_moa_date: document.getElementById('v1Moa')?.value || '', v1_moa_link: await uploadSingleFile('v1MoaFile') || document.getElementById('v1MoaLink')?.value || '', v1_cbd_date: document.getElementById('v1Cbd')?.value || '', v1_cbd_link: await uploadSingleFile('v1CbdFile') || document.getElementById('v1CbdLink')?.value || '',
        
        v2_status: document.getElementById('v2Status')?.value || '', v2_loi_date: document.getElementById('v2Loi')?.value || '', v2_loi_link: await uploadSingleFile('v2LoiFile') || document.getElementById('v2LoiLink')?.value || '', v2_sb_reso_date: document.getElementById('v2Sb')?.value || '', v2_sb_link: await uploadSingleFile('v2SbFile') || document.getElementById('v2SbLink')?.value || '', v2_mou_date: document.getElementById('v2Mou')?.value || '', v2_mou_link: await uploadSingleFile('v2MouFile') || document.getElementById('v2MouLink')?.value || '', v2_cbd_date: document.getElementById('v2Cbd')?.value || '', v2_cbd_link: await uploadSingleFile('v2CbdFile') || document.getElementById('v2CbdLink')?.value || '',
        v2_ereadiness_link: await uploadSingleFile('v2EreadinessFile') || document.getElementById('v2EreadinessLink')?.value || '', v2_cbd_endorsed: document.getElementById('v2CbdEndorsed')?.value || '', v2_cbd_approved: document.getElementById('v2CbdApproved')?.value || '',
        
        v2_training_date: document.getElementById('v2TrnDate')?.value || '', v2_training_status: document.getElementById('v2TrnStat')?.value || '', v2_training_pax_m: document.getElementById('v2TrnM')?.value || 0, v2_training_pax_f: document.getElementById('v2TrnF')?.value || 0, v2_training_aar: await uploadSingleFile('v2TrnAarFile') || document.getElementById('v2TrnAarLink')?.value || '', v2_training_consent: document.getElementById('v2TrnCon')?.value || 0, v2_training_desig: document.getElementById('v2TrnDes')?.value || 0,
        v2_dry_run_date: document.getElementById('v2Dry')?.value || '', v2_dry_run_status: document.getElementById('v2DryStat')?.value || '', v2_dry_run_pax_m: document.getElementById('v2DryM')?.value || 0, v2_dry_run_pax_f: document.getElementById('v2DryF')?.value || 0, v2_uat_sys_status: document.getElementById('v2UatSys')?.value || '', v2_dry_run_aar: await uploadSingleFile('v2DryAarFile') || document.getElementById('v2DryAarLink')?.value || '', v2_uat_sys_rem: document.getElementById('v2UatSysRem')?.value || '', v2_uat_egov_stat: document.getElementById('v2UatEgovStat')?.value || 'Pending', v2_uat_egov_rem: document.getElementById('v2UatEgovRem')?.value || '',
        v2_migration_status: document.getElementById('v2MigStat')?.value || '', v2_migration_remarks: document.getElementById('v2MigRem')?.value || '',
        v2_launch_date: document.getElementById('v2Launch')?.value || '', v2_launch_pax: document.getElementById('v2LaunchPax')?.value || 0, v2_launch_status: v2LaunchStat,
        
        bpco_status: bpcoStat, bpco_loi_date: document.getElementById('bpcoLoi')?.value || '', bpco_loi_link: await uploadSingleFile('bpcoLoiFile') || document.getElementById('bpcoLoiLink')?.value || '', bpco_desig_date: document.getElementById('bpcoDesig')?.value || '', bpco_desig_link: await uploadSingleFile('bpcoDesigFile') || document.getElementById('bpcoDesigLink')?.value || '',
        bpco_trn_start: document.getElementById('bpcoTrnStart')?.value || '', bpco_trn_end: document.getElementById('bpcoTrnEnd')?.value || '', bpco_trn_m: document.getElementById('bpcoTrnM')?.value || 0, bpco_trn_f: document.getElementById('bpcoTrnF')?.value || 0,
        bpco_db_start: document.getElementById('bpcoDbStart')?.value || '', bpco_db_end: document.getElementById('bpcoDbEnd')?.value || '', bpco_db_m: document.getElementById('bpcoDbM')?.value || 0, bpco_db_f: document.getElementById('bpcoDbF')?.value || 0,
        contacts: contactArr
    };

    try {
        const response = await fetch('api/update_lgu.php', { method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify(payload) });
        const result = await response.json();
        if (result.success) { 
            fetchLgus(); 
            if(!isAutoSave) { document.getElementById('editLguModal')?.classList.remove('active'); fetchLogs(); }
        } else { if(!isAutoSave) alert("Database Save Failed: " + result.message); }
    } catch(e) { if(!isAutoSave) alert("System Error."); }
    
    if(!isAutoSave && btn) { btn.innerText = "Save & Audit Profile"; btn.disabled = false; }
}

async function deleteLgu() {
    const id = document.getElementById('editLguId')?.value; if(!id) return;
    const lgu = allLgus.find(x => x.id == id);
    if (confirm(`CRITICAL WARNING: Are you sure you want to permanently delete ${lgu.municipality}? This action CANNOT be undone.`)) {
        try {
            const res = await fetch('api/delete_lgu.php', { method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify({ id: id }) });
            const data = await res.json();
            if(data.success) { document.getElementById('editLguModal')?.classList.remove('active'); fetchLgus(); fetchLogs(); } 
            else { alert("Deletion failed: " + data.message); }
        } catch (e) { console.error(e); }
    }
}

// ==========================================
// CUSTOM DATA EXPORT ENGINE
// ==========================================
function exportCustomData() {
    const expMaster = document.getElementById('exportMasterlist').checked;
    const expV1 = document.getElementById('exportV1').checked;
    const expV2 = document.getElementById('exportV2').checked;
    const expBPCO = document.getElementById('exportBPCO').checked;
    const expTasks = document.getElementById('exportTasks').checked;

    if (!expMaster && !expV1 && !expV2 && !expBPCO && !expTasks) {
        return alert("Please select at least one data set to export.");
    }

    if (expMaster || expV1 || expV2 || expBPCO) {
        let hdrs = [];
        hdrs.push("LGU ID", "Region", "Province", "Municipality");

        if(expMaster) hdrs.push("District", "Income Class", "Mayor", "Overall Status", "eReadiness Score", "eReadiness Label", "Current Version");
        if(expV1) hdrs.push("V1 Status", "V1 LOI Date", "V1 SB Reso Date", "V1 MOA Date", "V1 CBD Date");
        if(expV2) hdrs.push("V2 Status", "V2 LOI Date", "V2 SB Reso Date", "V2 MOU Date", "V2 CBD Date", "V2 CBD Endorsed", "V2 CBD Approved", "V2 Training Date", "V2 Trn M", "V2 Trn F", "V2 UAT Sys Stat", "V2 UAT eGov Stat", "V2 DryRun Date", "V2 Mig Stat", "V2 Launch Date");
        if(expBPCO) hdrs.push("BPCO Status", "BPCO LOI Date", "BPCO Desig Date", "BPCO Trn Start", "BPCO Trn End", "BPCO DB Start", "BPCO DB End");

        let csv = "data:text/csv;charset=utf-8," + hdrs.map(h => `"${h}"`).join(",") + "\n";
        
        allLgus.forEach(l => {
            let row = [];
            row.push(l.id, l.region, l.province, l.municipality);

            if(expMaster) row.push(l.district, l.income_class, l.mayor, l.overall_status, l.ereadiness_score, l.e_readiness_label, l.current_system_version);
            if(expV1) row.push(l.v1_status, l.v1_loi_date, l.v1_sb_reso_date, l.v1_moa_date, l.v1_cbd_date);
            if(expV2) row.push(l.v2_status, l.v2_loi_date, l.v2_sb_reso_date, l.v2_mou_date, l.v2_cbd_date, l.v2_cbd_endorsed, l.v2_cbd_approved, l.v2_training_date, l.v2_training_pax_m, l.v2_training_pax_f, l.v2_uat_sys_status, l.v2_uat_egov_stat, l.v2_dry_run_date, l.v2_migration_status, l.v2_launch_date);
            if(expBPCO) row.push(l.bpco_status, l.bpco_loi_date, l.bpco_desig_date, l.bpco_trn_start, l.bpco_trn_end, l.bpco_db_start, l.bpco_db_end);
            
            csv += row.map(v => `"${(v || '').toString().replace(/"/g, '""')}"`).join(",") + "\n";
        });
        
        const lnk = document.createElement("a"); lnk.href = encodeURI(csv); lnk.download = "eLGU_Custom_Data_Export.csv"; document.body.appendChild(lnk); lnk.click(); document.body.removeChild(lnk);
    }

    if (expTasks) {
        let thdrs = ["Task ID", "LGU ID", "Municipality", "Assigned To", "Task Description", "Due Date", "Status", "Date Started", "Date Completed", "Target System"];
        let tcsv = "data:text/csv;charset=utf-8," + thdrs.map(h => `"${h}"`).join(",") + "\n";
        
        allTasks.forEach(t => {
            let row = [t.id, t.lgu_id, t.municipality, t.personnel, t.description, t.due_date, t.status, t.date_started, t.date_completed, t.target_system];
            tcsv += row.map(v => `"${(v || '').toString().replace(/"/g, '""')}"`).join(",") + "\n";
        });
        
        const lnk = document.createElement("a"); lnk.href = encodeURI(tcsv); lnk.download = "eLGU_Tasks_Export.csv"; document.body.appendChild(lnk); lnk.click(); document.body.removeChild(lnk);
    }
} 