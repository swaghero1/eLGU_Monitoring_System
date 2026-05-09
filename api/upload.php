let allTasks = [], allLgus = [];
let isViewingDone = false;
let currentLguFilters = { status: 'For Engagement', version: 'All', province: 'All' };
let chart1, chart2;

document.addEventListener("DOMContentLoaded", () => {
    if (localStorage.getItem('theme') === 'dark') {
        document.body.setAttribute('data-theme', 'dark');
        const themeBtn = document.getElementById('themeToggleBtn');
        if(themeBtn) themeBtn.innerText = 'Switch to Light Mode';
        const ll = document.getElementById('logo-light'); if(ll) ll.style.display = 'none';
        const ld = document.getElementById('logo-dark'); if(ld) ld.style.display = 'block';
    } else {
        const ll = document.getElementById('logo-light'); if(ll) ll.style.display = 'block';
        const ld = document.getElementById('logo-dark'); if(ld) ld.style.display = 'none';
    }
    loadAdminProfile(); fetchTasks(); fetchLgus();
});

function toggleTheme() {
    const isDark = document.body.getAttribute('data-theme') === 'dark';
    const themeBtn = document.getElementById('themeToggleBtn');
    const ll = document.getElementById('logo-light');
    const ld = document.getElementById('logo-dark');
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
    const targetView = document.getElementById(viewId);
    if(targetView) targetView.classList.add('active');
    if(btnElement) btnElement.classList.add('active');

    if(viewId === 'view-masterlist') renderMasterlistTable();
    if(viewId === 'view-v1') renderV1Table();
    if(viewId === 'view-v2') renderV2Table();
    if(viewId === 'view-bpco') renderBpcoTable();
}

function loadAdminProfile() {
    const name = localStorage.getItem('adminName') || 'System Admin';
    const pic = localStorage.getItem('adminPic') || '';
    const navName = document.getElementById('navAdminName');
    const navInitials = document.getElementById('navAvatarInitials');
    if(navName) navName.innerText = name;
    if(navInitials) navInitials.innerText = name.charAt(0).toUpperCase();
    if (pic) {
        const navAvatar = document.getElementById('navAvatar');
        if(navAvatar) { navAvatar.src = pic; navAvatar.style.display = 'block'; }
        if(navInitials) navInitials.style.display = 'none';
    }
}

function openAdminProfile() {
    const nameInput = document.getElementById('adminNameInput');
    const picInput = document.getElementById('adminPicInput');
    if(nameInput) nameInput.value = localStorage.getItem('adminName') || 'System Admin';
    if(picInput) picInput.value = localStorage.getItem('adminPic') || '';
    document.getElementById('adminProfileModal')?.classList.add('active');
}

function saveAdminProfile() {
    const name = document.getElementById('adminNameInput')?.value || 'System Admin';
    const pic = document.getElementById('adminPicInput')?.value || '';
    localStorage.setItem('adminName', name); localStorage.setItem('adminPic', pic);
    loadAdminProfile(); document.getElementById('adminProfileModal')?.classList.remove('active');
}

function updateLiveCharts() {
    if (!allLgus || !allLgus.length) return;
    const total = allLgus.length;
    
    // Status drilldown for Chart 1
    const opCount = allLgus.filter(l => l.overall_status === 'Operational' || l.v1_status === 'Operational' || l.v2_status === 'Operational').length;
    const nonOpCount = allLgus.filter(l => l.overall_status === 'Non-Operational' || l.v1_status === 'Non-Operational' || l.v2_status === 'Non-Operational').length;
    const ownCount = allLgus.filter(l => l.own_system == 1 || l.v1_status === 'Own System' || l.v2_status === 'Own System').length;
    const engCount = allLgus.filter(l => l.overall_status === 'For Engagement' || l.v1_status === 'For Engagement' || l.v2_status === 'For Engagement' || l.v2_status === 'Ongoing Engagement').length;
    const otherCount = total - (opCount + nonOpCount + ownCount + engCount);

    // Penetration logic: (V1Op + V2Op) / (114 - OwnSys) * 100
    const v1OpCount = allLgus.filter(l => l.v1_operational == 1 || l.v1_status === 'Operational').length;
    const v2OpCount = allLgus.filter(l => l.v2_operational == 1 || l.v2_status === 'Operational').length;
    const sysOwnCount = allLgus.filter(l => l.own_system == 1).length;
    const penetrationTarget = total - sysOwnCount; 
    const penetrationPct = penetrationTarget > 0 ? Math.round(((v1OpCount + v2OpCount) / penetrationTarget) * 100) : 0;

    const elPen = document.getElementById('labelPenetration');
    if(elPen) elPen.innerText = `${penetrationPct}%`;

    if (chart1) chart1.destroy(); 
    if (chart2) chart2.destroy(); 

    const chartClick = (e, els, chart) => { 
        if(!els.length) return; 
        const statusClicked = chart.data.labels[els[0].index];
        const searchInput = document.getElementById('searchMasterlist');
        if(searchInput) {
            searchInput.value = statusClicked === 'Other' ? '' : statusClicked;
            switchView('view-masterlist', document.querySelectorAll('.nav-btn')[1]);
        }
    };
    
    const isDark = document.body.getAttribute('data-theme') === 'dark'; Chart.defaults.color = isDark ? '#94a3b8' : '#666';
    const opts = { responsive: true, maintainAspectRatio: false, onClick: chartClick, plugins: { legend: { position: 'right', labels: { boxWidth: 12 } } } };
    const optsNoLeg = { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false }, tooltip: { enabled: false } }, cutout: '70%' };

    const cOv = document.getElementById('overallChart');
    const cPen = document.getElementById('penetrationChart');
    
    if(cOv) chart1 = new Chart(cOv, { type: 'doughnut', data: { labels: ['Operational', 'Non-Operational', 'Own System', 'For Engagement', 'Other'], datasets: [{ data: [opCount, nonOpCount, ownCount, engCount, otherCount>0?otherCount:0], backgroundColor: ['#10b981', '#ef4444', '#8b5cf6', '#f59e0b', isDark?'#334155':'#cbd5e1'], borderColor: isDark ? '#1e293b' : '#fff' }] }, options: opts });
    if(cPen) chart2 = new Chart(cPen, { type: 'doughnut', data: { labels: ['Penetrated', 'Remaining'], datasets: [{ data: [penetrationPct, 100-penetrationPct], backgroundColor: ['#3b82f6', isDark?'#334155':'#e2e8f0'], borderColor: isDark ? '#1e293b' : '#fff', borderRadius: 5 }] }, options: optsNoLeg });
}

async function fetchTasks() {
    try {
        const res = await fetch('api/get_tasks.php');
        if(res.ok) { const data = await res.json(); if (data.success) { allTasks = data.data; renderTasksTable(); } }
    } catch(e) { console.error("Tasks fetch failed", e); }
}

function getUrgencyStyle(dueDate) {
    if(!dueDate) return '';
    const diff = Math.ceil((new Date(dueDate) - new Date()) / 86400000);
    if (diff <= 0) return 'color: #ef4444; font-weight: 800;'; // Red (Due/Overdue)
    if (diff <= 7) return 'color: #f97316; font-weight: 800;'; // Orange (< 1 wk)
    return 'color: var(--text-main);';
}

function renderTasksTable() {
    const tbody = document.getElementById('tasksTableBody'); if (!tbody) return; tbody.innerHTML = ''; 
    let filtered = allTasks.filter(t => t.status === (isViewingDone ? 'Completed' : 'Pending'));
    const sortFilter = document.getElementById('taskSortFilter');
    if(sortFilter && sortFilter.value === 'latest') { filtered.sort((a,b) => b.id - a.id); } else { filtered.sort((a,b) => String(a.personnel).localeCompare(String(b.personnel))); }
    
    filtered.slice(0, 5).forEach(task => tbody.appendChild(createTaskRow(task)));
    if (filtered.length > 5) { tbody.innerHTML += `<tr><td colspan="5" class="text-center"><button class="btn-outline" style="width:100%;" onclick="openViewAllTasksModal()">View All ${filtered.length} Items</button></td></tr>`; } 
    else if (filtered.length === 0) { tbody.innerHTML = `<tr><td colspan="5" class="text-center text-muted">No ${isViewingDone ? 'completed' : 'pending'} tasks.</td></tr>`; }
}

function createTaskRow(t) {
    const tr = document.createElement('tr'); const safeDesc = t.description ? t.description.replace(/'/g, "\\'") : '';
    let action = isViewingDone ? `<span style="color:green; font-size:0.8rem;">✓ ${t.date_completed}</span>` : `<button class="btn-primary" style="padding:4px;" onclick="completeTask(${t.id}, '${safeDesc}', ${t.lgu_id})">Mark Done</button>`;
    tr.innerHTML = `<td><strong>${t.personnel}</strong></td><td>${t.municipality} <span style="font-size:0.7rem; color:var(--brand-blue);">(${t.target_system || 'General'})</span></td><td>${t.description}</td><td><span style="font-size:0.7rem; display:block;">Started: ${t.date_started || 'N/A'}</span><strong style="${isViewingDone?'':getUrgencyStyle(t.due_date)}">Due: ${t.due_date}</strong></td><td>${action}</td>`;
    return tr;
}

function openViewAllTasksModal() {
    const title = document.getElementById('viewAllTasksTitle'); if(title) title.innerText = isViewingDone ? 'Completed Tasks' : 'Pending Tasks';
    const tbody = document.getElementById('viewAllTasksBody'); if(!tbody) return; tbody.innerHTML = '';
    allTasks.filter(t => t.status === (isViewingDone ? 'Completed' : 'Pending')).forEach(t => tbody.appendChild(createTaskRow(t)));
    document.getElementById('viewAllTasksModal')?.classList.add('active');
}

function toggleTaskView() { isViewingDone = !isViewingDone; const btn = document.getElementById('viewDoneBtn'); if(btn) btn.innerText = isViewingDone ? 'View Pending' : 'View Completed'; renderTasksTable(); }

async function completeTask(id, desc, lguId) {
    if(!confirm("Mark complete?")) return;
    try {
        await fetch('api/complete_task.php', { method: 'POST', body: JSON.stringify({ id: id }) });
        
        // E-READINESS AUTOMATION WORKFLOW
        if(desc === 'eReadiness Validation') { 
            // Create coordination task
            await fetch('api/add_task.php', { method: 'POST', body: JSON.stringify({ lgu_id: lguId, personnel: 'Admin', description: 'Coordinate with LGU for LOI and other requirements submission', due_date: new Date(new Date().setDate(new Date().getDate() + 7)).toISOString().split('T')[0], target_system: 'V2' }) });
            // Auto-update LGU Status to Lacking Docs
            const lgu = allLgus.find(x => x.id == lguId);
            if(lgu) {
                lgu.v2_status = 'Lacking Documentary Requirements';
                await fetch('api/update_lgu.php', { method: 'POST', body: JSON.stringify(lgu) });
            }
        }
        
        await fetchTasks();
        const modal = document.getElementById('editLguModal');
        if(modal && modal.classList.contains('active')) {
            const currentLguId = document.getElementById('editLguId')?.value;
            if(currentLguId == lguId) {
                const activeTabBtn = document.querySelector('.tab-btn.active');
                let target = 'tab-general';
                if (activeTabBtn) {
                    if (activeTabBtn.innerText.includes('V1')) target = 'tab-v1'; else if (activeTabBtn.innerText.includes('V2')) target = 'tab-v2'; else if (activeTabBtn.innerText.includes('BPCO')) target = 'tab-bpco';
                }
                openEditLguModal(lguId, target);
            }
        }
    } catch(e) { console.error(e); }
}

function openAddTaskModal() { 
    const modal = document.getElementById('addTaskModal'); if(modal) modal.classList.add('active'); 
    const taskDesc = document.getElementById('taskDesc'); if(taskDesc) taskDesc.value = '';
    const assigneeSelect = document.getElementById('taskAssigneeSelect'); if(assigneeSelect) assigneeSelect.innerHTML = '<option value="">Select LGU First...</option>';
    toggleCustomAssignee(); checkLguTaskRouting(); 
}

function checkLguTaskRouting() {
    const sel = document.getElementById('taskLguSelect'); if(!sel) return;
    const lgu = allLgus.find(l => l.id == sel.value);
    const container = document.getElementById('taskRoutingContainer'); const targetSys = document.getElementById('taskTargetSystem');
    
    if(lgu && container && targetSys) {
        const inactiveFlags = ['N/A', 'N/A (No System Yet)', 'Non-Operational', ''];
        const hasV1 = lgu.v1_operational == 1 || (lgu.v1_status && !inactiveFlags.includes(lgu.v1_status));
        const hasV2 = lgu.v2_operational == 1 || (lgu.v2_status && !inactiveFlags.includes(lgu.v2_status));
        if (hasV1 && hasV2) { container.style.display = 'flex'; } else { container.style.display = 'none'; targetSys.value = hasV2 ? 'V2' : (hasV1 ? 'V1' : 'General'); }
    }

    const assigneeSelect = document.getElementById('taskAssigneeSelect');
    if(assigneeSelect && lgu) {
        assigneeSelect.innerHTML = '<option value="">Select Assigned Contact...</option>';
        if(lgu.contacts && lgu.contacts.length > 0) {
            const activeContacts = lgu.contacts.filter(c => c.is_active == 1 && c.name);
            if(activeContacts.length > 0) { activeContacts.forEach(c => { assigneeSelect.innerHTML += `<option value="${c.name}">${c.name} (${c.role || 'Contact'})</option>`; }); } 
            else { assigneeSelect.innerHTML += `<option value="" disabled>No Active Contacts Found for LGU</option>`; }
        } else { assigneeSelect.innerHTML += `<option value="" disabled>No Contacts Configured for this LGU</option>`; }
        assigneeSelect.innerHTML += `<option value="custom" style="font-weight:bold; color:var(--brand-blue);">+ Other (Type manually)</option>`;
    }
    toggleCustomAssignee();
}

function toggleCustomAssignee() {
    const sel = document.getElementById('taskAssigneeSelect'); const customInput = document.getElementById('taskAssigneeCustom');
    if(sel && customInput) { if(sel.value === 'custom') { customInput.style.display = 'block'; customInput.required = true; customInput.value = ''; } else { customInput.style.display = 'none'; customInput.required = false; } }
}

function submitNewTask() {
    const targetSys = document.getElementById('taskTargetSystem') ? document.getElementById('taskTargetSystem').value : 'General';
    let assignee = document.getElementById('taskAssigneeSelect')?.value || '';
    if(assignee === 'custom') { assignee = document.getElementById('taskAssigneeCustom')?.value || 'Unknown Personnel'; }
    fetch('api/add_task.php', { method: 'POST', body: JSON.stringify({ lgu_id: document.getElementById('taskLguSelect')?.value || '', personnel: assignee, description: document.getElementById('taskDesc')?.value || '', due_date: document.getElementById('taskDueDate')?.value || '', target_system: targetSys })})
    .then(()=> { fetchTasks(); document.getElementById('addTaskModal')?.classList.remove('active'); });
}

// ==========================================
// CORE LGU DATA FETCHING & RENDERING
// ==========================================
async function fetchLgus() {
    try {
        const res = await fetch('api/get_lgus.php');
        if (res.ok) { 
            const data = await res.json(); allLgus = data.data || []; 
            populateDropdown(); updateLiveCharts(); renderSummaryTable(); 
            if(document.getElementById('view-masterlist')?.classList.contains('active')) renderMasterlistTable();
            if(document.getElementById('view-v1')?.classList.contains('active')) renderV1Table();
            if(document.getElementById('view-v2')?.classList.contains('active')) renderV2Table();
            if(document.getElementById('view-bpco')?.classList.contains('active')) renderBpcoTable();
        }
    } catch(e) { console.error("Failed to fetch LGUs", e); }
}

function populateDropdown() { const s = document.getElementById('taskLguSelect'); if(!s) return; s.innerHTML=''; allLgus.forEach(l => s.innerHTML+=`<option value="${l.id}">${l.municipality} (${l.province})</option>`); }

function renderSummaryTable() {
    const zampenProvinces = ["Zamboanga del Norte", "Zamboanga del Sur", "Zamboanga Sibugay", "Zamboanga City", "Isabela City"];
    const basultaProvinces = ["Basilan", "Sulu", "Tawi-Tawi"];
    const allProvinces = [...zampenProvinces, ...basultaProvinces];
    
    let zp=[0,0,0,0,0], bs=[0,0,0,0,0]; const tb = document.getElementById('summaryTableBody'); if(!tb) return; tb.innerHTML='';
    
    allProvinces.forEach(prov => {
        const l = allLgus.filter(x=>x.province===prov);
        const c=l.length;
        const v1=l.filter(x=>x.v1_operational==1 || x.v1_status==='Operational').length;
        const v2=l.filter(x=>x.v2_operational==1 || x.v2_status==='Operational').length;
        const forTrn=l.filter(x=>x.v2_status==='For Training').length;
        const onTrn=l.filter(x=>x.v2_status==='Ongoing Engagement' || x.v2_training_status==='On-going').length;
        
        if(zampenProvinces.includes(prov)) zp=zp.map((v,i)=>v+[c,v1,v2,forTrn,onTrn][i]); else bs=bs.map((v,i)=>v+[c,v1,v2,forTrn,onTrn][i]);
        tb.innerHTML += `<tr><td>${prov}</td><td>${c}</td><td>${v1}</td><td>${v2}</td><td>${forTrn}</td><td>${onTrn}</td></tr>`;
    });
    
    const foot = document.getElementById('summaryTableFoot');
    if(foot) {
        foot.innerHTML = `<tr><td colspan="6"></td></tr>
        <tr style="background:var(--bg-color); color:var(--brand-blue);"><td><strong>Zamboanga Peninsula</strong></td><td>${zp[0]}</td><td>${zp[1]}</td><td>${zp[2]}</td><td>${zp[3]}</td><td>${zp[4]}</td></tr>
        <tr style="background:var(--bg-color); color:var(--brand-blue);"><td><strong>BASULTA</strong></td><td>${bs[0]}</td><td>${bs[1]}</td><td>${bs[2]}</td><td>${bs[3]}</td><td>${bs[4]}</td></tr>`;
    }
}

function renderMasterlistTable() {
    const tbody = document.getElementById('masterlistTableBody'); if(!tbody) return; tbody.innerHTML = '';
    const search = (document.getElementById('searchMasterlist')?.value || '').toLowerCase();
    allLgus.filter(l => (l.municipality||'').toLowerCase().includes(search) || (l.province||'').toLowerCase().includes(search) || (l.overall_status||'').toLowerCase().includes(search)).forEach(l => {
        tbody.innerHTML += `
            <tr>
                <td><strong>${l.municipality}</strong><br><span class="text-muted">${l.mayor || 'No Mayor Listed'}</span></td>
                <td>${l.province}<br><span class="text-muted">${l.district || ''}</span></td>
                <td>Score: <strong>${l.ereadiness_score||0}</strong><br><span style="font-size:0.8rem; font-weight:600; color:var(--text-main);">${l.overall_status||'Pending'}</span></td>
                <td style="font-size:0.8rem;">
                    V1: <span style="color:${l.v1_operational==1||l.v1_status==='Operational'?'#10b981':'var(--text-muted)'}">${l.v1_status||'N/A'}</span><br>
                    V2: <span style="color:${l.v2_operational==1||l.v2_status==='Operational'?'#10b981':'var(--text-muted)'}">${l.v2_status||'N/A'}</span><br>
                    BPCO: <span style="color:${l.bpco_status==='Operational'?'#10b981':'#ef4444'}">${l.bpco_status||'Pending'}</span>
                </td>
                <td><button class="btn-primary" onclick="openEditLguModal(${l.id})">Edit Profile</button></td>
            </tr>`;
    });
}

function renderV1Table() {
    const tb = document.getElementById('v1TableBody'); if(!tb) return; tb.innerHTML = '';
    const s = (document.getElementById('searchV1')?.value || '').toLowerCase();
    allLgus.filter(l => {
        if (s && !(l.municipality||'').toLowerCase().includes(s) && !(l.province||'').toLowerCase().includes(s)) return false;
        const inactive = ['N/A', 'N/A (No System Yet)', 'Non-Operational', '', 'Own System'];
        if (l.v1_operational == 0 && inactive.includes(l.v1_status)) return false; return true;
    }).forEach(l => {
        tb.innerHTML += `
            <tr>
                <td><strong>${l.municipality}</strong><br><span class="text-muted">${l.province}</span></td>
                <td><span style="font-weight:600; color:${l.v1_operational==1||l.v1_status==='Operational'?'#10b981':'var(--brand-blue)'};">${l.v1_status || 'Pending'}</span></td>
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
    const s = (document.getElementById('searchV2')?.value || '').toLowerCase();
    allLgus.filter(l => {
        if (s && !(l.municipality||'').toLowerCase().includes(s) && !(l.province||'').toLowerCase().includes(s)) return false;
        const inactive = ['N/A', 'N/A (No System Yet)', 'Non-Operational', '', 'Own System'];
        if (l.v2_operational == 0 && inactive.includes(l.v2_status)) return false; return true;
    }).forEach(l => {
        tb.innerHTML += `
            <tr>
                <td><strong>${l.municipality}</strong><br><span class="text-muted">Score: ${l.ereadiness_score||0} (${l.e_readiness_label||'N/A'})</span></td>
                <td><span style="font-weight:600; color:${l.v2_operational==1||l.v2_status==='Operational'?'#10b981':'var(--brand-blue)'};">${l.v2_status || 'Pending'}</span><br>
                    <span style="font-size:0.75rem; color:var(--text-muted)">Launch: ${l.v2_launch_date || 'TBA'}</span>
                </td>
                <td style="font-size:0.8rem; color:var(--text-muted);">
                    LOI: ${l.v2_loi_date ? `<strong style="color:#10b981">✓</strong>` : 'Pending'} | 
                    SB: ${l.v2_sb_reso_date ? `<strong style="color:#10b981">✓</strong>` : 'Pending'} 
                </td>
                <td style="font-size:0.8rem; color:var(--text-muted);">
                    Sys UAT: <strong>${l.v2_uat_sys_status||'Pending'}</strong><br>
                    Dry-Run: <strong>${l.v2_dry_run_date||'Pending'}</strong>
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
        tb.innerHTML += `
            <tr>
                <td><strong>${l.municipality}</strong><br><span class="text-muted">${l.province}</span></td>
                <td><span style="font-weight:600; color:${l.bpco_status==='Operational'?'#10b981':'#ef4444'};">${l.bpco_status || 'Pending'}</span></td>
                <td style="font-size:0.8rem; color:var(--text-muted);">
                    LOI: ${l.bpco_loi_date ? `<strong style="color:#10b981">✓ ${l.bpco_loi_date}</strong>` : 'Pending'} <br>
                    Desig: ${l.bpco_desig_date ? `<strong style="color:#10b981">✓ ${l.bpco_desig_date}</strong>` : 'Pending'}
                </td>
                <td><button class="btn-primary" onclick="openEditLguModal(${l.id}, 'tab-bpco')">Manage BPCO</button></td>
            </tr>
        `;
    });
}

// ==========================================
// PROFILE MODAL & TAB LOGIC
// ==========================================
function switchProfTab(id) {
    document.querySelectorAll('.prof-tab').forEach(e=>e.style.display='none');
    document.querySelectorAll('.tab-btn').forEach(e=>e.classList.remove('active'));
    const targetTab = document.getElementById(id);
    if(targetTab) targetTab.style.display='block'; 
    if(event && event.target && event.target.classList) event.target.classList.add('active');
}

function toggleV2TrainingBlock() {
    const v2Stat = document.getElementById('v2Status')?.value;
    const tBlock = document.getElementById('v2TrainingBlock');
    if(tBlock) tBlock.style.display = (v2Stat === 'For Training') ? 'block' : 'none';
}

function openEditLguModal(id, targetTab = 'tab-general') {
    const l = allLgus.find(x => x.id == id); if (!l) return;
    
    document.getElementById('editLguId').value = l.id;
    const nameField = document.getElementById('profLguName'); if(nameField) nameField.innerText = `${l.municipality} Master Profile`;
    const geoField = document.getElementById('profLguGeo'); if(geoField) geoField.innerText = l.province;

    if(document.getElementById('editRegion')) document.getElementById('editRegion').value = l.region || 'Region IX';
    if(document.getElementById('editProvince')) document.getElementById('editProvince').value = l.province || '';
    if(document.getElementById('editMunicipality')) document.getElementById('editMunicipality').value = l.municipality || '';
    if(document.getElementById('editDistrict')) document.getElementById('editDistrict').value = l.district || '';
    if(document.getElementById('editIncome')) document.getElementById('editIncome').value = l.income_class || '';
    if(document.getElementById('editMayor')) document.getElementById('editMayor').value = l.mayor || '';
    if(document.getElementById('editScore')) document.getElementById('editScore').value = l.ereadiness_score || '';
    if(document.getElementById('editScoreLabel')) document.getElementById('editScoreLabel').value = l.e_readiness_label || 'Not Ready';
    if(document.getElementById('editStatus')) document.getElementById('editStatus').value = l.overall_status || 'For Engagement';
    if(document.getElementById('editV1')) document.getElementById('editV1').checked = l.v1_operational == 1;
    if(document.getElementById('editV2')) document.getElementById('editV2').checked = l.v2_operational == 1;
    if(document.getElementById('editOwn')) document.getElementById('editOwn').checked = l.own_system == 1;
    if(document.getElementById('editBpcoView')) document.getElementById('editBpcoView').value = l.bpco_status || 'Pending';

    // V1 fields
    if(document.getElementById('v1Status')) document.getElementById('v1Status').value = l.v1_status || 'Pending';
    if(document.getElementById('v1Loi')) document.getElementById('v1Loi').value = l.v1_loi_date || '';
    if(document.getElementById('v1LoiLink')) document.getElementById('v1LoiLink').value = l.v1_loi_link || '';
    if(document.getElementById('v1Sb')) document.getElementById('v1Sb').value = l.v1_sb_reso_date || '';
    if(document.getElementById('v1SbLink')) document.getElementById('v1SbLink').value = l.v1_sb_link || '';
    if(document.getElementById('v1Moa')) document.getElementById('v1Moa').value = l.v1_moa_date || '';
    if(document.getElementById('v1MoaLink')) document.getElementById('v1MoaLink').value = l.v1_moa_link || '';
    if(document.getElementById('v1Cbd')) document.getElementById('v1Cbd').value = l.v1_cbd_date || '';
    if(document.getElementById('v1CbdLink')) document.getElementById('v1CbdLink').value = l.v1_cbd_link || '';
    
    // V2 fields
    if(document.getElementById('v2Status')) document.getElementById('v2Status').value = l.v2_status || 'Pending';
    if(document.getElementById('v2Loi')) document.getElementById('v2Loi').value = l.v2_loi_date || '';
    if(document.getElementById('v2LoiLink')) document.getElementById('v2LoiLink').value = l.v2_loi_link || '';
    if(document.getElementById('v2Sb')) document.getElementById('v2Sb').value = l.v2_sb_reso_date || '';
    if(document.getElementById('v2SbLink')) document.getElementById('v2SbLink').value = l.v2_sb_link || '';
    if(document.getElementById('v2Mou')) document.getElementById('v2Mou').value = l.v2_mou_date || '';
    if(document.getElementById('v2MouLink')) document.getElementById('v2MouLink').value = l.v2_mou_link || '';
    if(document.getElementById('v2Cbd')) document.getElementById('v2Cbd').value = l.v2_cbd_date || '';
    if(document.getElementById('v2CbdLink')) document.getElementById('v2CbdLink').value = l.v2_cbd_link || '';
    
    if(document.getElementById('v2TrnDate')) document.getElementById('v2TrnDate').value = l.v2_training_date || '';
    if(document.getElementById('v2TrnM')) document.getElementById('v2TrnM').value = l.v2_training_pax_m || 0;
    if(document.getElementById('v2TrnF')) document.getElementById('v2TrnF').value = l.v2_training_pax_f || 0;
    if(document.getElementById('v2TrnAar')) document.getElementById('v2TrnAar').value = l.v2_training_aar || '';
    if(document.getElementById('v2TrnCon')) document.getElementById('v2TrnCon').value = l.v2_training_consent || 0;
    if(document.getElementById('v2TrnDes')) document.getElementById('v2TrnDes').value = l.v2_training_desig || 0;
    if(document.getElementById('v2TrnStat')) document.getElementById('v2TrnStat').value = l.v2_training_status || 'Pending';
    
    if(document.getElementById('v2Dry')) document.getElementById('v2Dry').value = l.v2_dry_run_date || '';
    if(document.getElementById('v2DryStat')) document.getElementById('v2DryStat').value = l.v2_dry_run_status || 'Pending';
    if(document.getElementById('v2DryM')) document.getElementById('v2DryM').value = l.v2_dry_run_pax_m || 0;
    if(document.getElementById('v2DryF')) document.getElementById('v2DryF').value = l.v2_dry_run_pax_f || 0;
    if(document.getElementById('v2UatSys')) document.getElementById('v2UatSys').value = l.v2_uat_sys_status || 'Pending';
    if(document.getElementById('v2DryAar')) document.getElementById('v2DryAar').value = l.v2_dry_run_aar || '';
    
    if(document.getElementById('v2MigStat')) document.getElementById('v2MigStat').value = l.v2_migration_status || 'Pending';
    if(document.getElementById('v2MigRem')) document.getElementById('v2MigRem').value = l.v2_migration_remarks || '';
    
    if(document.getElementById('v2Launch')) document.getElementById('v2Launch').value = l.v2_launch_date || '';
    if(document.getElementById('v2LaunchPax')) document.getElementById('v2LaunchPax').value = l.v2_launch_pax || 0;
    if(document.getElementById('v2LaunchStat')) document.getElementById('v2LaunchStat').value = l.v2_launch_status || 'Pending';

    toggleV2TrainingBlock();

    // BPCO
    if(document.getElementById('bpcoStatus')) document.getElementById('bpcoStatus').value = l.bpco_status || 'Pending';
    if(document.getElementById('bpcoLoi')) document.getElementById('bpcoLoi').value = l.bpco_loi_date || '';
    if(document.getElementById('bpcoDesig')) document.getElementById('bpcoDesig').value = l.bpco_desig_date || '';
    
    // BPCO Expanded fields
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

    const v1t = document.getElementById('v1TasksContainer'); if(v1t) v1t.innerHTML='';
    const v2t = document.getElementById('v2TasksContainer'); if(v2t) v2t.innerHTML='';
    const lguTasks = allTasks.filter(t => t.lgu_id == id && t.status === 'Pending');
    if (lguTasks.length === 0) {
        if(v1t) v1t.innerHTML = `<span style="color:var(--text-muted); font-size:0.85rem;">No pending action items.</span>`;
        if(v2t) v2t.innerHTML = `<span style="color:var(--text-muted); font-size:0.85rem;">No pending action items.</span>`;
    } else {
        lguTasks.forEach(t => {
            const safeDesc = t.description ? t.description.replace(/'/g, "\\'") : '';
            let taskHtml = `
            <div style="display:flex; justify-content:space-between; align-items:center; background:#fef2f2; border-left:4px solid #A81C1C; padding:12px; margin-top:10px; border-radius:4px; font-size:0.85rem;">
                <div>
                    <strong style="color:var(--text-main);">${t.description}</strong><br>
                    <span style="color:var(--text-muted);">Assigned: <strong>${t.personnel}</strong> | <span style="${getUrgencyStyle(t.due_date)}">Due: ${t.due_date}</span></span>
                </div>
                <button type="button" class="btn-primary" style="padding:6px 12px; font-size:0.8rem; background-color:#10b981;" onclick="completeTask(${t.id}, '${safeDesc}', ${t.lgu_id})">✓ Mark Complete</button>
            </div>`;
            if(v1t) v1t.innerHTML += taskHtml;
            if(v2t) v2t.innerHTML += taskHtml;
        });
    }

    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    const btn = document.querySelector(`button[onclick="switchProfTab('${targetTab}')"]`);
    if(btn) btn.classList.add('active');

    switchProfTab(targetTab); 
    const modal = document.getElementById('editLguModal');
    if(modal) modal.classList.add('active');
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

async function submitEditLgu() {
    const id = document.getElementById('editLguId')?.value; if(!id) return;
    let score = parseFloat(document.getElementById('editScore')?.value) || 0;
    let status = document.getElementById('editStatus')?.value || 'For Engagement';
    
    let label = 'Not Ready'; 
    if(score >= 95) label = 'Competitive Ready'; 
    else if(score >= 85) label = 'Ready'; 
    else if(score >= 70) label = 'Partially Ready'; 
    else if(score >= 60) label = 'Beginning Ready';
    
    // E-Readiness automated Task generation
    if(score >= 60 && status !== 'Passed') { 
        status = 'Passed'; 
        fetch('api/add_task.php', { method: 'POST', body: JSON.stringify({ lgu_id: id, personnel: 'Validation Team', description: 'eReadiness Validation', due_date: new Date().toISOString().split('T')[0], target_system: 'V2' }) }); 
    } else if (score < 60 && status !== 'Failed' && score > 0) {
        status = 'Failed';
        fetch('api/add_task.php', { method: 'POST', body: JSON.stringify({ lgu_id: id, personnel: 'Admin', description: 'eReadiness Rechecking', due_date: new Date(new Date().setDate(new Date().getDate() + 7)).toISOString().split('T')[0], target_system: 'V2' }) });
    }

    let v2LaunchStat = document.getElementById('v2LaunchStat')?.value || 'Pending';
    let v2Op = document.getElementById('editV2')?.checked || false;
    if(v2LaunchStat === 'Completed') v2Op = true; 

    // Auto-mark BPCO Pending if empty
    let bpcoStat = document.getElementById('bpcoStatus')?.value || 'Pending';
    if(document.getElementById('bpcoLoi')?.value && document.getElementById('bpcoDesig')?.value) {
        // Only flip to operational if explicitly marked, otherwise leave pending
    }

    const contactArr = [];
    document.querySelectorAll('#contactsTableBody tr').forEach(tr => {
        contactArr.push({ name: tr.querySelector('.contact-name').value, email: tr.querySelector('.contact-email').value, contact_number: tr.querySelector('.contact-phone').value, role: tr.querySelector('.contact-role').value, designation: tr.querySelector('.contact-desig').value, is_active: tr.querySelector('.contact-status').value });
    });

    const payload = {
        id: id, overall_status: status, ereadiness_score: score, e_readiness_label: label,
        region: document.getElementById('editRegion')?.value || '', province: document.getElementById('editProvince')?.value || '', municipality: document.getElementById('editMunicipality')?.value || '', district: document.getElementById('editDistrict')?.value || '', mayor: document.getElementById('editMayor')?.value || '', income_class: document.getElementById('editIncome')?.value || '',
        v1_operational: document.getElementById('editV1')?.checked ? 1:0, v2_operational: v2Op ? 1:0, own_system: document.getElementById('editOwn')?.checked ? 1:0,
        
        v1_status: document.getElementById('v1Status')?.value || '', 
        v1_loi_date: document.getElementById('v1Loi')?.value || '', v1_loi_link: document.getElementById('v1LoiLink')?.value || '', 
        v1_sb_reso_date: document.getElementById('v1Sb')?.value || '', v1_sb_link: document.getElementById('v1SbLink')?.value || '', 
        v1_moa_date: document.getElementById('v1Moa')?.value || '', v1_moa_link: document.getElementById('v1MoaLink')?.value || '', 
        v1_cbd_date: document.getElementById('v1Cbd')?.value || '', v1_cbd_link: document.getElementById('v1CbdLink')?.value || '',
        
        v2_status: document.getElementById('v2Status')?.value || '', 
        v2_loi_date: document.getElementById('v2Loi')?.value || '', v2_loi_link: document.getElementById('v2LoiLink')?.value || '',
        v2_sb_reso_date: document.getElementById('v2Sb')?.value || '', v2_sb_link: document.getElementById('v2SbLink')?.value || '',
        v2_mou_date: document.getElementById('v2Mou')?.value || '', v2_mou_link: document.getElementById('v2MouLink')?.value || '',
        v2_cbd_date: document.getElementById('v2Cbd')?.value || '', v2_cbd_link: document.getElementById('v2CbdLink')?.value || '',
        
        v2_training_date: document.getElementById('v2TrnDate')?.value || '', v2_training_status: document.getElementById('v2TrnStat')?.value || '', v2_training_pax_m: document.getElementById('v2TrnM')?.value || 0, v2_training_pax_f: document.getElementById('v2TrnF')?.value || 0, v2_training_aar: document.getElementById('v2TrnAar')?.value || '', v2_training_consent: document.getElementById('v2TrnCon')?.value || 0, v2_training_desig: document.getElementById('v2TrnDes')?.value || 0,
        v2_dry_run_date: document.getElementById('v2Dry')?.value || '', v2_dry_run_status: document.getElementById('v2DryStat')?.value || '', v2_dry_run_pax_m: document.getElementById('v2DryM')?.value || 0, v2_dry_run_pax_f: document.getElementById('v2DryF')?.value || 0, v2_uat_sys_status: document.getElementById('v2UatSys')?.value || '', v2_dry_run_aar: document.getElementById('v2DryAar')?.value || '',
        v2_migration_status: document.getElementById('v2MigStat')?.value || '', v2_migration_remarks: document.getElementById('v2MigRem')?.value || '',
        v2_launch_date: document.getElementById('v2Launch')?.value || '', v2_launch_pax: document.getElementById('v2LaunchPax')?.value || 0, v2_launch_status: v2LaunchStat,
        
        bpco_status: bpcoStat, bpco_loi_date: document.getElementById('bpcoLoi')?.value || '', bpco_desig_date: document.getElementById('bpcoDesig')?.value || '',
        bpco_trn_start: document.getElementById('bpcoTrnStart')?.value || '', bpco_trn_end: document.getElementById('bpcoTrnEnd')?.value || '', bpco_trn_m: document.getElementById('bpcoTrnM')?.value || 0, bpco_trn_f: document.getElementById('bpcoTrnF')?.value || 0,
        bpco_db_start: document.getElementById('bpcoDbStart')?.value || '', bpco_db_end: document.getElementById('bpcoDbEnd')?.value || '', bpco_db_m: document.getElementById('bpcoDbM')?.value || 0, bpco_db_f: document.getElementById('bpcoDbF')?.value || 0,
        contacts: contactArr
    };

    try {
        const response = await fetch('api/update_lgu.php', { method: 'POST', body: JSON.stringify(payload) });
        const result = await response.json();
        if (result.success) { document.getElementById('editLguModal')?.classList.remove('active'); fetchLgus(); } 
        else { alert("Database Save Failed: " + result.message); console.error(result.message); }
    } catch(e) { alert("System Error: Could not connect to the database."); console.error(e); }
}

async function deleteLgu() {
    const id = document.getElementById('editLguId')?.value; if(!id) return;
    const lgu = allLgus.find(x => x.id == id);
    if (confirm(`CRITICAL WARNING: Are you sure you want to permanently delete ${lgu.municipality}? This action CANNOT be undone.`)) {
        try {
            const res = await fetch('api/delete_lgu.php', { method: 'POST', body: JSON.stringify({ id: id }) });
            const data = await res.json();
            if(data.success) { document.getElementById('editLguModal')?.classList.remove('active'); fetchLgus(); } 
            else { alert("Deletion failed: " + data.message); }
        } catch (e) { console.error(e); }
    }
}

function exportToCSV() {
    const hdrs = ["Region", "Province", "Municipality", "eReadiness", "V1 Op", "V2 Op", "BPCO", "Own Sys"];
    let csv = "data:text/csv;charset=utf-8," + hdrs.join(",") + "\n";
    allLgus.forEach(l => { csv += `"${l.region}","${l.province}","${l.municipality}","${l.ereadiness_score}","${l.v1_operational?'Y':'N'}","${l.v2_operational?'Y':'N'}","${l.bpco_status}","${l.own_system?'Y':'N'}"\n`; });
    const lnk = document.createElement("a"); lnk.href = encodeURI(csv); lnk.download = "eLGU_Export.csv"; document.body.appendChild(lnk); lnk.click();
} 