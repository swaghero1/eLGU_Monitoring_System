// ==========================================
// script.js - Master Data & UI Controller
// ==========================================

// GLOBAL STATE
let tasksData = [];
let allLgusData = []; 

// FILTER STATES
let currentTaskProvinceFilter = 'All';
let currentTaskDistrictFilter = 'All';
let currentReadinessProvinceFilter = 'All';
let currentReadinessDistrictFilter = 'All';
let currentStatus = 'For Engagement'; // Default tab

document.addEventListener("DOMContentLoaded", () => {
    // 1. Initial Data Load
    loadDashboardData();

    // 2. Task Filters Listener
    document.getElementById('taskProvinceFilter')?.addEventListener('change', (e) => {
        currentTaskProvinceFilter = e.target.value; 
        renderTasksTable();
    });
    document.getElementById('taskDistrictFilter')?.addEventListener('change', (e) => {
        currentTaskDistrictFilter = e.target.value; 
        renderTasksTable();
    });

    // 3. Readiness/Masterlist Filters Listener
    document.getElementById('readinessProvinceFilter')?.addEventListener('change', (e) => {
        currentReadinessProvinceFilter = e.target.value; 
        renderReadinessTable();
    });
    document.getElementById('readinessDistrictFilter')?.addEventListener('change', (e) => {
        currentReadinessDistrictFilter = e.target.value; 
        renderReadinessTable();
    });

    // 4. Status Tabs Listener
    const statusBoxes = document.querySelectorAll('.status-box');
    statusBoxes.forEach(box => {
        box.addEventListener('click', function() {
            statusBoxes.forEach(b => b.classList.remove('active-tab'));
            this.classList.add('active-tab');
            currentStatus = this.getAttribute('data-status');
            renderReadinessTable();
        });
    });
});

// ==========================================
// MASTER DATA FETCHING
// ==========================================
async function loadDashboardData() {
    try {
        const [tasksResponse, lgusResponse] = await Promise.all([
            fetch('api/get_tasks.php'),
            fetch('api/get_lgus.php')
        ]);

        const tasksResult = await tasksResponse.json();
        const lgusResult = await lgusResponse.json();

        if (tasksResult.success) {
            tasksData = tasksResult.data;
            renderTasksTable();
        }

        if (lgusResult.success) {
            allLgusData = lgusResult.data;
            updateDashboardKPIs();
            renderReadinessTable();
        }
    } catch (error) {
        console.error("Critical System Error loading dashboard data:", error);
    }
}

// ==========================================
// DYNAMIC KPI CALCULATION
// ==========================================
function updateDashboardKPIs() {
    let v1Count = 0;
    let v2Count = 0;
    let ownSystemCount = 0;

    allLgusData.forEach(lgu => {
        if (lgu.v1_operational == 1) v1Count++;
        if (lgu.v2_operational == 1) v2Count++;
        if (lgu.own_system == 1) ownSystemCount++;
    });

    if(document.getElementById('kpi-v1')) document.getElementById('kpi-v1').innerText = v1Count;
    if(document.getElementById('kpi-v2')) document.getElementById('kpi-v2').innerText = v2Count;
    if(document.getElementById('kpi-own-system')) document.getElementById('kpi-own-system').innerText = ownSystemCount;
}

// ==========================================
// TABLE RENDERING LOGIC
// ==========================================
function renderTasksTable() {
    const tbody = document.getElementById('tasksTableBody');
    if (!tbody) return;
    
    tbody.innerHTML = '';

    const filteredTasks = tasksData.filter(task => {
        const matchProv = (currentTaskProvinceFilter === 'All' || task.province === currentTaskProvinceFilter);
        const matchDist = (currentTaskDistrictFilter === 'All' || task.district === currentTaskDistrictFilter);
        const isPending = task.status === 'Pending';
        return matchProv && matchDist && isPending;
    });

    filteredTasks.forEach(task => {
        let badgeClass = task.urgency === 'Overdue' ? 'badge-red' : 
                         task.urgency === 'Near Due' ? 'badge-yellow' : 'badge-green';

        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td><strong>${task.personnel}</strong></td>
            <td>${task.municipality}<br><small style="color:var(--text-muted)">${task.province}</small></td>
            <td>${task.description}</td>
            <td><span class="badge ${badgeClass}">${task.due_date}</span></td>
            <td>
                <button class="btn-outline" style="padding: 4px 8px; font-size: 0.8rem;" onclick="markTaskComplete(${task.id})">Complete</button>
            </td>
        `;
        tbody.appendChild(tr);
    });
}

function renderReadinessTable() {
    const tbody = document.getElementById('lguTableBody'); // Make sure your HTML table body has this ID
    if (!tbody) return;

    tbody.innerHTML = '';

    const filteredLgus = allLgusData.filter(lgu => {
        const matchStatus = (lgu.overall_status === currentStatus);
        const matchProv = (currentReadinessProvinceFilter === 'All' || lgu.province === currentReadinessProvinceFilter);
        const matchDist = (currentReadinessDistrictFilter === 'All' || lgu.district === currentReadinessDistrictFilter);
        return matchStatus && matchProv && matchDist;
    });

    filteredLgus.forEach(lgu => {
        // Build badges for system status
        let systemBadges = '';
        if (lgu.v1_operational == 1) systemBadges += `<span class="badge badge-green" style="margin-right:4px;">V1</span>`;
        if (lgu.v2_operational == 1) systemBadges += `<span class="badge badge-blue" style="margin-right:4px;">V2</span>`;
        if (lgu.own_system == 1) systemBadges += `<span class="badge badge-yellow">Own System</span>`;
        if (systemBadges === '') systemBadges = `<span style="color:#94a3b8; font-size:0.8rem;">None Active</span>`;

        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td>
                <strong>${lgu.municipality}</strong><br>
                <small style="color:var(--text-muted)">${lgu.income_class}</small>
            </td>
            <td>${lgu.province}<br><small>${lgu.district}</small></td>
            <td>
                ${lgu.contact_name || 'N/A'}<br>
                <small>${lgu.contact_number || ''}</small>
            </td>
            <td>${systemBadges}</td>
            <td>
                <button class="btn-primary" style="padding: 6px 12px; font-size: 0.8rem;" onclick='openEditLguModal(${JSON.stringify(lgu).replace(/'/g, "\\'")})'>Manage</button>
            </td>
        `;
        tbody.appendChild(tr);
    });
}

// ==========================================
// QUICK ACTIONS
// ==========================================
async function markTaskComplete(taskId) {
    if(!confirm("Mark this task as completed?")) return;
    try {
        const response = await fetch('api/complete_task.php', {
            method: 'POST',
            body: JSON.stringify({ id: taskId })
        });
        const result = await response.json();
        if(result.success) loadDashboardData(); // Instantly refresh
    } catch(err) { console.error(err); }
} 