/* ==========================================
   OFFICIAL eLGU BRAND VARIABLES & THEMES
   ========================================== */
:root {
    --brand-navy: #1B3679;
    --brand-blue: #1A44E8;
    --brand-red: #A81C1C;
    --brand-yellow: #EAB308;
    
    /* Light Mode */
    --bg-color: #F1F5F9;
    --surface-color: #FFFFFF;
    --text-main: #111827;
    --text-muted: #475569;
    --border-color: #e2e8f0;
    --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
    --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
    
    --font-heading: 'Montserrat', sans-serif;
    --font-body: 'Inter', sans-serif;
    --radius: 12px;
}

/* Dark Mode Overrides */
[data-theme="dark"] {
    --bg-color: #0f172a;
    --surface-color: #1e293b;
    --text-main: #f8fafc;
    --text-muted: #94a3b8;
    --border-color: #334155;
    --brand-navy: #93c5fd; 
    --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.5);
}

* { box-sizing: border-box; margin: 0; padding: 0; }

/* ==========================================
   GLOBAL LAYOUT (SIDEBAR ARCHITECTURE FIX)
   ========================================== */
body { 
    background-color: var(--bg-color); 
    color: var(--text-main); 
    font-family: var(--font-body); 
    display: flex; 
    height: 100vh; 
    overflow: hidden; 
    transition: background-color 0.3s, color 0.3s; 
}

h1, h2, h3, h4, h5, h6 { font-family: var(--font-heading); color: var(--brand-navy); font-weight: 700; }
.flex-between { display: flex; justify-content: space-between; align-items: center; }
.text-center { text-align: center; }
.text-muted { color: var(--text-muted); font-size: 0.85rem;}
.badge { background: #e0e7ff; color: var(--brand-blue); font-size: 0.75rem; padding: 4px 10px; border-radius: 12px; font-weight: 600; display: inline-block; margin-top: 4px;}

.app-header {
    position: fixed; top: 0; left: 0; width: 100%; height: 64px;
    background: var(--surface-color); border-bottom: 1px solid var(--border-color);
    display: flex; align-items: center; padding: 0 20px; z-index: 1050; box-sizing: border-box;
}

.sidebar { 
    position: fixed; top: 64px; left: 0; height: calc(100vh - 64px);
    width: 260px; 
    background: var(--surface-color); 
    border-right: 1px solid var(--border-color); 
    display: flex; 
    flex-direction: column; 
    z-index: 1000;
    transition: width 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    overflow-x: hidden; white-space: nowrap;
}
.sidebar.collapsed { width: 72px; }

.sidebar-header { padding: 24px 20px; border-bottom: 1px solid var(--border-color); display: flex; align-items: center; gap: 12px; }
.sidebar-header img { height: 40px; }
.sidebar-header h2 { font-size: 1.15rem; margin: 0; line-height: 1.2; }

.user-widget { padding: 20px; display: flex; align-items: center; gap: 12px; cursor: pointer; border-bottom: 1px solid var(--border-color); transition: background 0.2s; }
.user-widget:hover { background: var(--bg-color); }
.user-avatar { width: 45px; height: 45px; border-radius: 50%; background: var(--brand-blue); color: white; display: flex; justify-content: center; align-items: center; font-weight: bold; font-size: 1.2rem; object-fit: cover; }
.user-info h4 { margin: 0; font-size: 0.95rem; color: var(--text-main); }
.user-info span { font-size: 0.75rem; color: var(--brand-blue); font-weight: 600; }

.side-nav { padding: 20px 10px; display: flex; flex-direction: column; gap: 8px; flex: 1; overflow-y: auto; }
.nav-btn { background: transparent; border: none; padding: 12px 16px; text-align: left; font-size: 0.95rem; font-weight: 600; color: var(--text-muted); border-radius: 8px; cursor: pointer; transition: all 0.2s; font-family: var(--font-body); }
.nav-btn:hover { background: var(--bg-color); color: var(--brand-blue); }
.nav-btn.active { background: var(--brand-blue); color: white; }

.main-content { 
    margin-top: 64px; 
    margin-left: 260px; 
    width: calc(100% - 260px); 
    padding: 30px; 
    box-sizing: border-box; 
    transition: margin-left 0.3s cubic-bezier(0.4, 0, 0.2, 1), width 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    overflow-y: auto; height: calc(100vh - 64px);
}
.sidebar.collapsed ~ .main-content { margin-left: 72px; width: calc(100% - 72px); }

.view-section { display: none; animation: fadeIn 0.3s ease; }
.view-section.active { display: block; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

.top-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
.split-layout { display: flex; gap: 24px; align-items: start;}
.left-panel { flex: 1.4; } 
.right-panel { flex: 1; display: flex; flex-direction: column; gap: 24px; }

.card { background: var(--surface-color); border-radius: var(--radius); box-shadow: var(--shadow-md); padding: 24px; border: 1px solid var(--border-color); margin-bottom: 24px; }
.card-header { margin-bottom: 20px; }

/* Buttons & Inputs */
.btn-primary { background-color: var(--brand-blue); color: white; border: none; padding: 10px 20px; border-radius: 8px; font-weight: 600; font-family: var(--font-body); cursor: pointer; transition: background-color 0.2s; }
.btn-primary:hover { background-color: var(--brand-navy); }
.btn-outline { background-color: transparent; color: var(--brand-navy); border: 1px solid var(--border-color); padding: 10px 20px; border-radius: 8px; font-weight: 600; font-family: var(--font-body); cursor: pointer; transition: all 0.2s; }
.btn-outline:hover { background-color: var(--bg-color); border-color: var(--text-muted); }
.btn-danger { background-color: #fef2f2; color: var(--brand-red); border: 1px solid #fca5a5; padding: 10px 20px; border-radius: 8px; font-weight: 600; font-family: var(--font-body); cursor: pointer; transition: all 0.2s; }
.btn-danger:hover { background-color: #fee2e2; }

.modern-select, .modern-input { padding: 10px 14px; border: 1px solid var(--border-color); border-radius: 8px; background-color: var(--surface-color); color: var(--text-main); font-size: 0.95rem; font-family: var(--font-body); outline: none; width: 100%; transition: all 0.2s;}
.modern-select:focus, .modern-input:focus { border-color: var(--brand-blue); box-shadow: 0 0 0 3px rgba(26, 68, 232, 0.1); }
.assignment-filters { display: flex; gap: 12px; flex-wrap: wrap; margin-top: 12px; align-items: center; }

/* Tables */
.table-container { overflow-x: auto; width: 100%; }
.modern-table { width: 100%; border-collapse: collapse; font-size: 0.95rem; }
.modern-table th, .modern-table td { padding: 16px; text-align: left; border-bottom: 1px solid var(--border-color); vertical-align: middle; }
.modern-table th { background-color: var(--bg-color); font-family: var(--font-heading); font-weight: 700; color: var(--brand-navy); text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.05em; }
.modern-table tbody tr:hover { background-color: var(--bg-color); }

/* Charts */
.charts-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
.bpco-span { grid-column: span 2; }
.chart-card { padding: 16px; display: flex; flex-direction: column; align-items: center; border: 1px solid var(--border-color); box-shadow: none; border-radius: var(--radius); background: var(--surface-color); }
.chart-card h4 { font-size: 0.95rem; margin-bottom: 10px; }

/* Modals */
.modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(17, 24, 39, 0.7); backdrop-filter: blur(4px); z-index: 1200; align-items: center; justify-content: center; overflow-y: auto; padding: 20px;}
.modal-overlay.active { display: flex; }
.modal-box { background: var(--surface-color); border-radius: var(--radius); width: 600px; max-width: 100%; box-shadow: var(--shadow-lg); animation: slideUp 0.3s ease-out; margin: auto; display: flex; flex-direction: column; overflow: hidden; }
.modal-large { width: 1000px; }
@keyframes slideUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }

.modal-header { padding: 20px 24px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; background: var(--surface-color); }
.modal-header h2 { margin: 0; font-size: 1.25rem; }
.close-btn { background: none; border: none; font-size: 1.5rem; color: var(--text-muted); cursor: pointer; transition: color 0.2s; }
.close-btn:hover { color: var(--brand-red); }

.modal-body { padding: 24px; overflow-y: auto; flex: 1; }
.modal-footer { padding: 20px 24px; border-top: 1px solid var(--border-color); display: flex; justify-content: flex-end; gap: 12px; background: var(--bg-color); }

.section-title { font-size: 0.95rem; color: var(--brand-blue); text-transform: uppercase; letter-spacing: 0.05em; border-bottom: 1px solid var(--border-color); padding-bottom: 8px; margin-bottom: 16px; margin-top: 20px; font-family: var(--font-heading); font-weight: 700;}
.section-title:first-child { margin-top: 0; }

.form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;}
.input-group { display: flex; flex-direction: column; gap: 6px; }
.input-group label { font-weight: 600; font-size: 0.85rem; color: var(--text-muted); }
.full-width { grid-column: span 2; }

/* Internal Profile Tabs */
.tab-btn { flex: 1; padding: 14px; border: none; background: transparent; font-weight: 600; color: var(--text-muted); cursor: pointer; border-bottom: 3px solid transparent; transition: 0.2s; font-family: var(--font-heading); font-size: 0.9rem;}
.tab-btn:hover { background: var(--bg-color); color: var(--brand-navy); }
.tab-btn.active { color: var(--brand-blue); border-bottom: 3px solid var(--brand-blue); background: var(--surface-color);}
.prof-tab { display: none; } 