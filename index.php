<?php
// ════════════════════════════════════════════════════════════════════════════
//  SENTINEL SAS v4.1 — User Console Domain Core
//  Credentials & Recognition:
//  Muhammad Ahmad Atif, 4th Sem BS AI — IMSC University
// ════════════════════════════════════════════════════════════════════════════
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>SENTINEL SAS v4.1 — Enterprise Domain Console</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;600;700;900&family=Rajdhani:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">
  <style>
    :root {
      --bg-void:     #050810;
      --bg-base:     #080d18;
      --bg-surface:  #0d1525;
      --bg-elevated: #121e35;
      --bg-panel:    #0f1a2e;
      --border:      #1a2d4a;
      --border-glow: #1e3d6a;
      --txt:         #dce8f5;
      --txt-muted:   #3d5a7a;
      --txt-dim:     #1e3352;
      --neon-blue:   #00f0ff;
      --neon-green:  #39ff14;
      --neon-amber:  #ffaa00;
      --neon-red:    #ff0055;
      --glow-blue:   rgba(0,240,255,0.15);
      --glow-green:  rgba(57,255,20,0.15);
      --glow-red:    rgba(255,0,85,0.15);
    }
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body {
      background-color: var(--bg-void);
      color: var(--txt);
      font-family: 'Rajdhani', sans-serif;
      font-size: 16px;
      font-weight: 500;
      line-height: 1.5;
      overflow-x: hidden;
      min-height: 100vh;
      display: flex;
      flex-direction: column;
    }
    header {
      background: linear-gradient(to bottom, var(--bg-surface), var(--bg-base));
      border-bottom: 2px solid var(--border);
      padding: 15px 30px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      position: sticky;
      top: 0;
      z-index: 100;
    }
    .logo-block h1 {
      font-family: 'Orbitron', sans-serif;
      font-weight: 900;
      font-size: 1.6rem;
      letter-spacing: 2px;
      color: #fff;
      text-shadow: 0 0 10px var(--glow-blue);
    }
    .logo-block h1 span { color: var(--neon-blue); }
    .logo-block p {
      font-family: 'JetBrains Mono', monospace;
      font-size: 0.75rem;
      color: var(--txt-muted);
      letter-spacing: 1px;
      margin-top: 2px;
    }
    .user-badge { display: flex; align-items: center; gap: 15px; }
    .badge-details { text-align: right; }
    .badge-name { font-weight: 700; color: #fff; font-size: 1.1rem; }
    .badge-role {
      font-family: 'JetBrains Mono', monospace;
      font-size: 0.7rem;
      text-transform: uppercase;
      padding: 2px 6px;
      background: var(--bg-elevated);
      border: 1px solid var(--border);
      border-radius: 4px;
      color: var(--neon-blue);
    }
    .btn-logout {
      background: transparent;
      border: 1px solid var(--neon-red);
      color: var(--neon-red);
      padding: 6px 12px;
      font-family: 'Orbitron', sans-serif;
      font-size: 0.8rem;
      font-weight: 700;
      cursor: pointer;
      border-radius: 4px;
      transition: all 0.2s ease;
    }
    .btn-logout:hover { background: var(--neon-red); color: #fff; box-shadow: 0 0 10px var(--glow-red); }
    main { flex: 1; padding: 40px 30px; max-width: 1600px; width: 100%; margin: 0 auto; display: flex; flex-direction: column; gap: 40px; }
    .screen { display: none; width: 100%; animation: fadeIn 0.4s ease both; }
    .screen.active { display: block; }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
    #authScreen { min-height: 70vh; display: flex; align-items: center; justify-content: center; }
    .auth-card {
      background: var(--bg-panel);
      border: 1px solid var(--border);
      border-top: 4px solid var(--neon-blue);
      border-radius: 6px;
      padding: 40px;
      width: 100%;
      max-width: 450px;
      box-shadow: 0 20px 40px rgba(0,0,0,0.5);
    }
    .auth-card h2 { font-family: 'Orbitron', sans-serif; font-size: 1.8rem; text-transform: uppercase; margin-bottom: 25px; text-align: center; }
    .form-group { margin-bottom: 20px; }
    .form-group label { display: block; margin-bottom: 8px; font-weight: 600; color: #fff; letter-spacing: 0.5px; }
    .input-ctrl {
      width: 100%;
      background: var(--bg-void);
      border: 1px solid var(--border);
      color: #fff;
      padding: 12px 15px;
      font-family: inherit;
      font-size: 1rem;
      border-radius: 4px;
      transition: border-color 0.2s ease;
    }
    .input-ctrl:focus { outline: none; border-color: var(--neon-blue); box-shadow: 0 0 8px var(--glow-blue); }
    .select-ctrl { appearance: none; cursor: pointer; }
    .btn-prime {
      width: 100%;
      background: var(--neon-blue);
      color: var(--bg-void);
      border: none;
      padding: 14px;
      font-family: 'Orbitron', sans-serif;
      font-weight: 700;
      font-size: 1rem;
      cursor: pointer;
      border-radius: 4px;
      text-transform: uppercase;
      letter-spacing: 1px;
      margin-top: 10px;
      transition: all 0.2s ease;
    }
    .btn-prime:hover { background: #fff; box-shadow: 0 0 15px #fff; }
    .dashboard-grid { display: grid; grid-template-columns: repeat(12, 1fr); gap: 30px; }
    .col-4 { grid-column: span 4; } .col-6 { grid-column: span 6; } .col-8 { grid-column: span 8; } .col-12 { grid-column: span 12; }
    @media(max-width: 1024px) { .col-4, .col-6, .col-8 { grid-column: span 12; } }
    .panel { background: var(--bg-panel); border: 1px solid var(--border); border-radius: 6px; padding: 25px; position: relative; overflow: hidden; }
    .panel-title { font-family: 'Orbitron', sans-serif; font-size: 1.1rem; text-transform: uppercase; margin-bottom: 20px; color: #fff; letter-spacing: 1px; display: flex; justify-content: space-between; align-items: center; }
    .panel-accent-blue { border-top: 3px solid var(--neon-blue); }
    .panel-accent-green { border-top: 3px solid var(--neon-green); }
    .panel-accent-amber { border-top: 3px solid var(--neon-amber); }
    .panel-accent-red { border-top: 3px solid var(--neon-red); }
    .metric-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(120px, 1fr)); gap: 20px; }
    .metric-card { background: var(--bg-void); border: 1px solid var(--border); padding: 15px; border-radius: 4px; text-align: center; }
    .metric-card p { font-family: 'JetBrains Mono', monospace; font-size: 0.75rem; color: var(--txt-muted); text-transform: uppercase; margin-bottom: 5px; }
    .metric-card h3 { font-family: 'Orbitron', sans-serif; font-size: 1.8rem; font-weight: 700; color: #fff; }
    .metric-card h3.blue { color: var(--neon-blue); }
    .metric-card h3.green { color: var(--neon-green); }
    .metric-card h3.amber { color: var(--neon-amber); }
    .metric-card h3.red { color: var(--neon-red); }
    .table-container { width: 100%; overflow-x: auto; margin-top: 10px; }
    table { width: 100%; border-collapse: collapse; text-align: left; }
    th { font-family: 'Orbitron', sans-serif; font-size: 0.8rem; text-transform: uppercase; color: var(--txt-muted); padding: 12px 15px; border-bottom: 2px solid var(--border); letter-spacing: 0.5px; }
    td { padding: 12px 15px; border-bottom: 1px solid var(--border); font-size: 0.95rem; }
    tr:last-child td { border-bottom: none; }
    tr:hover td { background: rgba(26,45,74,0.2); }
    .mono { font-family: 'JetBrains Mono', monospace; font-size: 0.85rem; }
    .status-pill { display: inline-block; padding: 2px 8px; border-radius: 4px; font-family: 'JetBrains Mono', monospace; font-size: 0.7rem; font-weight: 700; text-transform: uppercase; border: 1px solid transparent; }
    .status-pill.active { background: rgba(57,255,20,0.1); border-color: var(--neon-green); color: var(--neon-green); }
    .status-pill.restricted { background: rgba(255,170,0,0.1); border-color: var(--neon-amber); color: var(--neon-amber); }
    .status-pill.suspended { background: rgba(255,0,85,0.1); border-color: var(--neon-red); color: var(--neon-red); }
    .modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(5,8,16,0.85); backdrop-filter: blur(4px); z-index: 1000; display: flex; align-items: center; justify-content: center; opacity: 0; pointer-events: none; transition: opacity 0.3s ease; }
    .modal-overlay.active { opacity: 1; pointer-events: auto; }
    .modal-card { background: var(--bg-panel); border: 1px solid var(--border); border-top: 4px solid var(--neon-amber); border-radius: 6px; padding: 35px; width: 100%; max-width: 450px; box-shadow: 0 20px 50px rgba(0,0,0,0.7); transform: scale(0.9); transition: transform 0.3s ease; }
    .modal-overlay.active .modal-card { transform: scale(1); }
    .modal-card h3 { font-family: 'Orbitron', sans-serif; font-size: 1.4rem; text-transform: uppercase; margin-bottom: 15px; color: #fff; }
    .modal-card p { margin-bottom: 25px; color: var(--txt); }
    .toast-container { position: fixed; bottom: 30px; right: 30px; z-index: 10000; display: flex; flex-direction: column; gap: 12px; }
    .toast { background: var(--bg-panel); border: 1px solid var(--border); border-left: 4px solid var(--neon-blue); padding: 15px 20px; border-radius: 4px; display: flex; align-items: center; gap: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.5); min-width: 300px; max-width: 450px; animation: toastIn 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275) both; }
    @keyframes toastIn { from { opacity: 0; transform: translateX(50px); } to { opacity: 1; transform: translateX(0); } }
    .toast.toast-out { animation: toastOut 0.3s ease both; }
    @keyframes toastOut { to { opacity: 0; transform: translateX(50px); margin-bottom: -60px; } }
    .toast-icon { font-size: 1.4rem; }
    .toast-title { font-family: 'Orbitron', sans-serif; font-weight: 700; font-size: 0.9rem; text-transform: uppercase; color: #fff; margin-bottom: 2px; }
    .toast-msg { font-size: 0.85rem; color: var(--txt); }
    .toast.success { border-left-color: var(--neon-green); .toast-icon { color: var(--neon-green); } }
    .toast.warn { border-left-color: var(--neon-amber); .toast-icon { color: var(--neon-amber); } }
    .toast.error { border-left-color: var(--neon-red); .toast-icon { color: var(--neon-red); } }
    footer { border-top: 1px solid var(--border); padding: 15px 30px; display: flex; justify-content: space-between; align-items: center; background: var(--bg-void); font-family: 'JetBrains Mono', monospace; font-size: 0.75rem; color: var(--txt-muted); }
    footer a { color: var(--txt-muted); text-decoration: none; } footer a:hover { color: var(--neon-blue); }
  </style>
</head>
<body>

  <header>
    <div class="logo-block">
      <h1>SENTINEL <span>SAS</span></h1>
      <p>v4.1 // AUTOMATED DISCREPANCY IDENTIFICATION MATRIX</p>
    </div>
    <div class="user-badge" id="headerUserBadge" style="display: none;">
      <div class="badge-details">
        <div class="badge-name" id="sessionUser">USERNAME</div>
        <span class="badge-role" id="sessionRole">ROLE</span>
      </div>
      <button class="btn-logout" onclick="triggerLogout()">DISCONNECT</button>
    </div>
  </header>

  <main>

    <section class="screen" id="authScreen">
      <div class="auth-card">
        <h2>CONSOLE AUTH</h2>
        <form id="loginForm" onsubmit="triggerLogin(event)">
          <div class="form-group">
            <label>OPERATOR DOMAIN</label>
            <select class="input-ctrl select-ctrl" id="loginRole">
              <option value="Customer">CUSTOMER OPERATIONS</option>
              <option value="Employee">DESK TERMINAL OPERATOR</option>
              <option value="Owner">SYSTEM ADMINISTRATIVE OWNER</option>
            </select>
          </div>
          <div class="form-group">
            <label>IDENTITY HASH (USERNAME)</label>
            <input type="text" class="input-ctrl" id="loginUser" autocomplete="username" placeholder="e.g. raza" required>
          </div>
          <div class="form-group">
            <label>SECURITY PASSCODE</label>
            <input type="password" class="input-ctrl" id="loginPass" autocomplete="current-password" placeholder="••••••••" required>
          </div>
          <button type="submit" class="btn-prime">INITIALIZE CONSOLE</button>
        </form>
        <p style="font-family:'JetBrains Mono',monospace; font-size:0.75rem; color:var(--txt-muted); margin-top:20px; text-align:center; line-height:1.4;">
          Default seeds:<br>
          Customers: <b>areeb / zainab / hamza</b> — pass: <code>password123</code><br>
          Employees: <b>raza / sara / umar</b> — pass: <code>password123</code><br>
          System Owner: <b>owner</b> — pass: <code>owner321</code>
        </p>
      </div>
    </section>

    <section class="screen" id="customerDashboard">
      <div class="dashboard-grid">
        <div class="col-4">
          <div class="panel panel-accent-blue" style="height:100%;">
            <div class="panel-title">MEMBER SPECIFICATIONS</div>
            <div style="display:flex; flex-direction:column; gap:15px; margin-top:5px;">
              <div>
                <p style="font-size:0.8rem; color:var(--txt-muted); text-transform:uppercase; font-family:'JetBrains Mono',monospace;">System Reference Key</p>
                <p class="mono" id="custRefKey" style="font-size:1.2rem; color:#fff; font-weight:700;">ID: --</p>
              </div>
              <div>
                <p style="font-size:0.8rem; color:var(--txt-muted); text-transform:uppercase; font-family:'JetBrains Mono',monospace;">Account Level / Token Discount</p>
                <p id="custTierBadge" style="font-size:1.1rem; color:var(--neon-blue); font-weight:700;">BASIC (0% Matrix reduction)</p>
              </div>
              <div>
                <p style="font-size:0.8rem; color:var(--txt-muted); text-transform:uppercase; font-family:'JetBrains Mono',monospace;">Account Integrity Status</p>
                <span class="status-pill active" id="custStatusBadge">ACTIVE</span>
              </div>
              <div>
                <p style="font-size:0.8rem; color:var(--txt-muted); text-transform:uppercase; font-family:'JetBrains Mono',monospace;">Next Ledger Review Cycle</p>
                <p id="custBillingDate" class="mono">--</p>
              </div>
            </div>
          </div>
        </div>
        <div class="col-8">
          <div class="panel panel-accent-green">
            <div class="panel-title">LEDGER LEDGERS BALANCE BALANCES</div>
            <div class="metric-row">
              <div class="metric-card">
                <p>Allocated Vault Credit</p>
                <h3 class="green" id="custBalance">0.00 PKR</h3>
              </div>
              <div class="metric-card">
                <p>Gross Processed Vol.</p>
                <h3 class="blue" id="custTotalSpent">0.00 PKR</h3>
              </div>
            </div>
          </div>
        </div>
        <div class="col-12">
          <div class="panel">
            <div class="panel-title">HISTORICAL TRANSACTION BOOK</div>
            <div class="table-container">
              <table id="custTxTable">
                <thead>
                  <tr>
                    <th>TRANSACTION REF</th>
                    <th>PRODUCT DESCRIPTION</th>
                    <th>CHRONOLOGICAL DATE</th>
                    <th>NET DISPATCH VALUE</th>
                    <th>SECURE HASH SIGNATURE</th>
                  </tr>
                </thead>
                <tbody>
                  <tr><td colspan="5" style="text-align:center; color:var(--txt-muted);">No records registered to this domain reference.</td></tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </section>

    <section class="screen" id="employeeDashboard">
      <div class="dashboard-grid">
        <div class="col-4">
          <div class="panel panel-accent-blue" style="height:100%;">
            <div class="panel-title">OPERATOR ATTRIBUTES</div>
            <div style="display:flex; flex-direction:column; gap:15px; margin-top:5px;">
              <div>
                <p style="font-size:0.8rem; color:var(--txt-muted); text-transform:uppercase; font-family:'JetBrains Mono',monospace;">Operator Structural Post</p>
                <p id="empRoleTitle" style="font-size:1.2rem; color:#fff; font-weight:700;">Desk Agent</p>
              </div>
              <div>
                <p style="font-size:0.8rem; color:var(--txt-muted); text-transform:uppercase; font-family:'JetBrains Mono',monospace;">Hardware Bound Node ID</p>
                <p id="empNodeId" class="mono" style="color:var(--neon-blue);">NODE-WORKSTATION-X00</p>
              </div>
              <div class="metric-row" style="margin-top:5px;">
                <div class="metric-card" style="padding:10px;">
                  <p style="font-size:0.65rem;">Validated Hashes</p>
                  <h3 class="blue" id="empTxCount" style="font-size:1.3rem;">0</h3>
                </div>
                <div class="metric-card" style="padding:10px;">
                  <p style="font-size:0.65rem;">Logged Defects</p>
                  <h3 class="amber" id="empDefectCount" style="font-size:1.3rem;">0</h3>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="col-8">
          <div class="panel panel-accent-amber">
            <div class="panel-title">HARDWARE INTERROGATION TERMINAL</div>
            <div style="display:flex; flex-direction:column; gap:20px;">
              <div style="display:flex; gap:15px; align-items:flex-end;">
                <div style="flex:1;">
                  <label style="display:block; margin-bottom:8px; font-weight:600; font-size:0.85rem; letter-spacing:0.5px; color:var(--txt-muted);">16-HEX SYSTEM ASSET SIGNATURE</label>
                  <input type="text" class="input-ctrl mono" id="verifyHashInput" maxlength="16" placeholder="e.g. A4F299B100C3G7X2" style="text-transform:uppercase; letter-spacing:1px;">
                </div>
                <button class="btn-prime" onclick="submitHashValidation()" style="width:auto; padding:12px 25px; margin-top:0;">EXECUTE VERIFICATION</button>
              </div>
              <div id="verifyResultDisplay" style="background:var(--bg-void); border:1px dashed var(--border); padding:15px; border-radius:4px; min-height:80px; display:flex; align-items:center; justify-content:center; color:var(--txt-muted);">
                Awaiting token input signature for interrogation cycle...
              </div>
            </div>
          </div>
        </div>
        <div class="col-12">
          <div class="panel panel-accent-red">
            <div class="panel-title">DEFECT AND ANOMALY REGISTRATION ENGINE</div>
            <form id="defectForm" onsubmit="handleDefectSubmission(event)" style="display:grid; grid-template-columns:repeat(3, 1fr) 120px; gap:20px; align-items:flex-end;">
              <div style="grid-column:span 1;">
                <label style="display:block; margin-bottom:8px; font-weight:600; font-size:0.85rem; color:var(--txt-muted);">12-HEX SUBCOMPONENT SIGNATURE</label>
                <input type="text" class="input-ctrl mono" id="defectHash" maxlength="12" placeholder="e.g. B811FFAA0099" style="text-transform:uppercase; letter-spacing:1px;" required>
              </div>
              <div style="grid-column:span 1;">
                <label style="display:block; margin-bottom:8px; font-weight:600; font-size:0.85rem; color:var(--txt-muted);">ANOMALY RISK CLASSIFICATION</label>
                <select class="input-ctrl select-ctrl" id="defectSeverity">
                  <option value="Minor">MINOR ANOMALY STRUCTURAL DEVIATION</option>
                  <option value="Major">MAJOR CRITICAL OPERATIONAL FAULT</option>
                  <option value="Critical">CATASTROPHIC CORE PIPELINE BREAK</option>
                </select>
              </div>
              <div style="grid-column:span 1;">
                <label style="display:block; margin-bottom:8px; font-weight:600; font-size:0.85rem; color:var(--txt-muted);">DIAGNOSTIC TELEMETRY LOG NOTES</label>
                <input type="text" class="input-ctrl" id="defectNotes" placeholder="e.g. Pin displacement line 4" required>
              </div>
              <button type="submit" class="btn-prime" style="margin-top:0; padding:12px;">LOG ANOMALY</button>
            </form>
          </div>
        </div>
      </div>
    </section>

    <section class="screen" id="ownerDashboard">
      <div class="dashboard-grid">
        <div class="col-12">
          <div class="panel panel-accent-blue">
            <div class="panel-title">CENTRAL BALANCES AND THREAT METRICS</div>
            <div class="metric-row">
              <div class="metric-card">
                <p>System Aggregate Net Revenue</p>
                <h3 class="green" id="ownerRevenue">0.00 PKR</h3>
              </div>
              <div class="metric-card">
                <p>Interception Engine Captures</p>
                <h3 class="red" id="ownerFlaggedCount">0</h3>
              </div>
              <div class="metric-card">
                <p>Restricted Supply Nodes</p>
                <h3 class="amber" id="ownerRestrictedCount">0</h3>
              </div>
            </div>
          </div>
        </div>
        <div class="col-6">
          <div class="panel">
            <div class="panel-title">SUPPLIER STABILITY AND INTEGRITY LOGS</div>
            <div class="table-container">
              <table id="ownerSuppliersTable">
                <thead>
                  <tr>
                    <th>PREFIX</th>
                    <th>LEGAL ENTITY</th>
                    <th>VOLUME</th>
                    <th>INTEGRITY SCORE</th>
                    <th>SYSTEM STATUS</th>
                  </tr>
                </thead>
                <tbody>
                  <tr><td colspan="5" style="text-align:center; color:var(--txt-muted);">Loading live metrics pipeline...</td></tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
        <div class="col-6">
          <div class="panel panel-accent-red">
            <div class="panel-title">MALICIOUS INTERCEPTION AUDIO ENGINE AUDITS</div>
            <div class="table-container">
              <table id="ownerFlaggedTable">
                <thead>
                  <tr>
                    <th>TARGET ASSET HASH</th>
                    <th>INTERCEPT CRITERIA EXPLANATION</th>
                    <th>TIMESTAMP</th>
                  </tr>
                </thead>
                <tbody>
                  <tr><td colspan="3" style="text-align:center; color:var(--txt-muted);">No suspicious activities captured within current security frame.</td></tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </section>

  </main>

  <div class="modal-overlay" id="biometricModal">
    <div class="modal-card">
      <h3>SECURE NODE ACCESS CHALLENGE</h3>
      <p>Your operator credentials require an active node binding lock. Re-enter your security passcode to bind this session.</p>
      <form onsubmit="handleBiometricAuthorization(event)">
        <div class="form-group">
          <label>CONFIRMATION PASSWORD</label>
          <input type="password" class="input-ctrl" id="biometricPass" placeholder="••••••••" required>
        </div>
        <button type="submit" class="btn-prime" style="background:var(--neon-amber);">AUTHORIZE WORKSTATION NODE</button>
      </form>
    </div>
  </div>

  <div class="toast-container" id="toastDock"></div>

  <footer>
    <div>SYSTEM NODE IDENTITY STATE RUNNING IN CLEAN LOCAL INTERACTION LOOP</div>
    <div id="footerClock">01 JANUARY 2026 -- 00:00:00</div>
  </footer>

  <script>
    const API_URL = 'api.php';
    let currentSessionUser = null;
    let currentSessionRole = null;

    // ─── AUTH OPERATIONS ───────────────────────────────────────────────────────
    async function executeApiRequest(action, data = {}) {
      try {
        const res = await fetch(API_URL, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ action, ...data })
        });
        if (res.status === 401) {
          showToast('SECURITY BOUNDS EXCEEDED', 'Active session drop identified.', 'error');
          transitionToScreen('authScreen');
          return null;
        }
        return await res.json();
      } catch (err) {
        showToast('HARDWARE TIMEOUT', 'Database API layer interaction failure.', 'error');
        return null;
      }
    }

    async function evaluateSessionState() {
      const json = await executeApiRequest('check_session');
      if (json && json.success) {
        initializeWorkspace(json.data.username, json.data.role);
        return true;
      }
      transitionToScreen('authScreen');
      return false;
    }

    async function triggerLogin(e) {
      e.preventDefault();
      const role = document.getElementById('loginRole').value;
      const username = document.getElementById('loginUser').value.trim();
      const password = document.getElementById('loginPass').value;

      const json = await executeApiRequest('login', { username, password, role });
      if (json && json.success) {
        showToast('SECURITY PASSCODE MATCH', json.message, 'success');
        initializeWorkspace(username, json.data.role, json.data);
      } else {
        showToast('ACCESS CONDEMNED', json ? json.message : 'Unknown rejection.', 'error');
      }
    }

    async function triggerLogout() {
      const json = await executeApiRequest('logout');
      if (json && json.success) {
        showToast('SESSION TERMINATED', 'Operator profile detached from active workspace.', 'warn');
        currentSessionUser = null;
        currentSessionRole = null;
        document.getElementById('headerUserBadge').style.display = 'none';
        document.getElementById('loginForm').reset();
        transitionToScreen('authScreen');
      }
    }

    // ─── WORKSPACE GENERATION ROUTERS ──────────────────────────────────────────
    function transitionToScreen(screenId) {
      document.querySelectorAll('.screen').forEach(s => s.classList.remove('active'));
      document.getElementById(screenId).classList.add('active');
    }

    function initializeWorkspace(user, role, initialPayload = null) {
      currentSessionUser = user;
      currentSessionRole = role;

      document.getElementById('sessionUser').textContent = user;
      document.getElementById('sessionRole').textContent = role + " DOMAIN";
      document.getElementById('headerUserBadge').style.display = 'flex';

      if (role === 'Customer') {
        transitionToScreen('customerDashboard');
        populateCustomerWorkspace(initialPayload);
      } else if (role === 'Employee') {
        transitionToScreen('employeeDashboard');
        populateEmployeeWorkspace(initialPayload);
      } else if (role === 'Owner') {
        transitionToScreen('ownerDashboard');
        populateOwnerWorkspace();
      }
    }

    // ─── DOMAIN RENDER PIPELINES ──────────────────────────────────────────────
    async function populateCustomerWorkspace(payload) {
      let data = payload;
      if (!data) {
        const mockLogin = await executeApiRequest('login', { username: currentSessionUser, password: '', role: 'Customer' });
        if (mockLogin && mockLogin.data) data = mockLogin.data;
      }
      if (!data) return;

      document.getElementById('custRefKey').textContent = `ID: ${String(data.customer_db_id).padStart(4, '0')} // SUFFIX: [${data.member_suffix}]`;
      document.getElementById('custTierBadge').textContent = `${data.tier.toUpperCase()} TIER LEVEL [-${data.discount}% DISCOUNT]`;
      document.getElementById('custStatusBadge').className = `status-pill ${data.status.toLowerCase()}`;
      document.getElementById('custStatusBadge').textContent = data.status;
      document.getElementById('custBillingDate').textContent = formatChronologicalString(data.billing_date);

      animateCounterText('custBalance', 0, data.balance, 800, v => `${v.toFixed(2)} PKR`);
      animateCounterText('custTotalSpent', 0, data.total_spent, 800, v => `${v.toFixed(2)} PKR`);

      const tbody = document.querySelector('#custTxTable tbody');
      if (data.history && data.history.length > 0) {
        tbody.innerHTML = data.history.map(tx => `
          <tr>
            <td class="mono" style="color:var(--neon-blue); font-weight:700;">${tx.transaction_ref}</td>
            <td>${tx.item_description}</td>
            <td class="mono">${formatChronologicalString(tx.purchase_date)}</td>
            <td class="mono" style="color:var(--neon-green); font-weight:700;">${parseFloat(tx.net_amount).toFixed(2)} PKR</td>
            <td class="mono" style="color:var(--txt-muted); font-size:0.8rem;">${tx.asset_hash}</td>
          </tr>
        `).join('');
      } else {
        tbody.innerHTML = `<tr><td colspan="5" style="text-align:center; color:var(--txt-muted);">No records registered to this domain reference.</td></tr>`;
      }
    }

    async function populateEmployeeWorkspace(payload) {
      let data = payload;
      if (!data) {
        const mockLogin = await executeApiRequest('login', { username: currentSessionUser, password: '', role: 'Employee' });
        if (mockLogin && mockLogin.data) data = mockLogin.data;
      }
      if (!data) return;

      document.getElementById('empRoleTitle').textContent = data.role_title;
      document.getElementById('empNodeId').textContent = data.node_id;
      document.getElementById('empTxCount').textContent = data.tx_count;
      document.getElementById('empDefectCount').textContent = data.defect_count;

      if (payload && payload.is_first_login === 1) {
        document.getElementById('biometricModal').classList.add('active');
      }
    }

    async function handleBiometricAuthorization(e) {
      e.preventDefault();
      const password = document.getElementById('biometricPass').value;
      const json = await executeApiRequest('authorize_node', { password });
      if (json && json.success) {
        showToast('NODE CODES BOUND', json.message, 'success');
        document.getElementById('biometricModal').classList.remove('active');
        populateEmployeeWorkspace();
      } else {
        showToast('AUTHORIZATION LOCK FAILURE', json ? json.message : 'Invalid code.', 'error');
      }
    }

    async function submitHashValidation() {
      const hash = document.getElementById('verifyHashInput').value.trim().toUpperCase();
      const display = document.getElementById('verifyResultDisplay');

      if (!/^[0-9A-F]{16}$/.test(hash)) {
        showToast('STRUCTURAL ERROR', 'Token validation format bounds mismatch.', 'error');
        return;
      }

      display.innerHTML = `<span class="mono" style="color:var(--neon-blue);">INTERROGATING BROADCAST FREQUENCY ARRAY...</span>`;
      const json = await executeApiRequest('validate_hash', { hash });

      if (json && json.success) {
        populateEmployeeWorkspace();
        let markup = `<div style="width:100%; display:grid; grid-template-columns:1fr 1fr; gap:10px; font-size:0.9rem;" class="mono">`;

        if (json.data.vendor_name) {
          markup += `<div>SUPPLY ROOT: <b style="color:#fff;">${json.data.vendor_name}</b></div>`;
        } else {
          markup += `<div style="color:var(--neon-red);">SUPPLY ROOT: UNKNOWN MALICIOUS OR ANONYMOUS SOURCING</div>`;
        }

        markup += `<div>LEDGER CONFLICT STATE: ${json.data.duplicate ? '<b style="color:var(--neon-red);">DUPLICATE REDISTRIBUTION TRAPPED</b>' : '<b style="color:var(--neon-green);">SINGLE DISTRIBUTION UNBALANCED</b>'}</div>`;
        markup += `<div style="grid-column:span 2;">VENDOR DEVIATION RESTRICTIONS: ${json.data.restricted ? '<span class="status-pill suspended">RESTRICTED VENDOR STRUCTURAL BLOCK</span>' : '<span class="status-pill active">STABLE ENGINE COMPLIANCE</span>'}</div>`;
        markup += `</div>`;
        display.innerHTML = markup;

        if (json.data.duplicate) {
          showToast('ARBITRAGE INTERCEPTED', 'Simultaneous hash transaction signature blocked.', 'error');
        } else if (json.data.restricted) {
          showToast('RESTRICTED SOURCE DETECTED', 'Target vendor execution flags are locked.', 'warn');
        } else {
          showToast('INTERROGATION PASSED', 'Token structure validated successfully.', 'success');
        }
      } else {
        display.innerHTML = `<span style="color:var(--neon-red); font-weight:700;">${json ? json.message : 'Interrogation pipeline failure.'}</span>`;
      }
    }

    async function handleDefectSubmission(e) {
      e.preventDefault();
      const hash = document.getElementById('defectHash').value.trim().toUpperCase();
      const severity = document.getElementById('defectSeverity').value;
      const notes = document.getElementById('defectNotes').value.trim();

      const json = await executeApiRequest('submit_defect', { hash, severity, notes });
      if (json && json.success) {
        showToast('ANOMALY LOG COMMITTED', json.message, 'success');
        document.getElementById('defectForm').reset();
        populateEmployeeWorkspace();

        if (json.data.newly_restricted) {
          showToast('SUPPLIER DROPPED TO RESTRICTED', `Vendor integrity threshold fell below safety limits.`, 'error');
        }
      } else {
        showToast('SUBMISSION DENIED', json ? json.message : 'Invalid sequence.', 'error');
      }
    }

    async function populateOwnerWorkspace() {
      const json = await executeApiRequest('get_owner_stats');
      if (json && json.success) {
        animateCounterText('ownerRevenue', 0, json.data.revenue, 1000, v => `${v.toFixed(2)} PKR`);
        animateCounterText('ownerFlaggedCount', 0, json.data.flagged, 1000);
        animateCounterText('ownerRestrictedCount', 0, json.data.restricted, 1000);
      }

      const supsJson = await executeApiRequest('get_suppliers');
      if (supsJson && supsJson.success) {
        const tbody = document.querySelector('#ownerSuppliersTable tbody');
        tbody.innerHTML = supsJson.data.map(s => `
          <tr>
            <td class="mono" style="color:var(--neon-blue); font-weight:700;">${s.hash_prefix}</td>
            <td style="font-weight:700; color:#fff;">${s.name} <span style="font-size:0.75rem; color:var(--txt-muted); font-weight:500;">(${s.country})</span></td>
            <td class="mono">${s.defect_count} / ${s.units_supplied}</td>
            <td class="mono" style="color:${s.integrity_score >= 90 ? 'var(--neon-green)' : (s.integrity_score >= 85 ? 'var(--neon-amber)' : 'var(--neon-red)')}; font-weight:700;">${s.integrity_score.toFixed(2)}%</td>
            <td><span class="status-pill ${s.status.toLowerCase()}">${s.status}</span></td>
          </tr>
        `).join('');
      }

      const flagsJson = await executeApiRequest('get_flagged_hashes');
      if (flagsJson && flagsJson.success) {
        const tbody = document.querySelector('#ownerFlaggedTable tbody');
        if (flagsJson.data.length > 0) {
          tbody.innerHTML = flagsJson.data.map(f => `
            <tr>
              <td class="mono" style="color:var(--neon-red); font-weight:700; font-size:0.85rem;">${f.asset_hash}</td>
              <td style="font-size:0.9rem;">${f.reason}</td>
              <td class="mono" style="color:var(--txt-muted); font-size:0.75rem; white-space:nowrap;">${f.flagged_at}</td>
            </tr>
          `).join('');
        } else {
          tbody.innerHTML = `<tr><td colspan="3" style="text-align:center; color:var(--txt-muted);">No suspicious activities captured within current security frame.</td></tr>`;
        }
      }
    }

    // ─── GLOBAL UTILITY CORE MODULES ──────────────────────────────────────────
    function showToast(title, msg, type = 'blue') {
      const dock = document.getElementById('toastDock');
      const el = document.createElement('div');
      el.className = `toast ${type}`;
      
      const icons = { blue: '🧬', success: '⚡', warn: '⚠️', error: '🚨' };
      el.innerHTML = `
        <div class="toast-icon">${icons[type] || '🧬'}</div>
        <div class="toast-body">
          <div class="toast-title">${title}</div>
          <div class="toast-msg">${msg}</div>
        </div>
      `;
      dock.appendChild(el);
      setTimeout(() => {
        el.classList.add('toast-out');
        setTimeout(() => el.remove(), 350);
      }, 4000);
    }

    function formatChronologicalString(str) {
      if (!str) return '--';
      const d = new Date(str);
      return d.toLocaleDateString('en-GB', { day: 'numeric', month: 'long', year: 'numeric' });
    }

    function animateCounterText(id, from, to, dur, formatter) {
      const el = document.getElementById(id);
      if (!el) return;
      const start = performance.now();
      const fmt = formatter || (v => Math.round(v));
      requestAnimationFrame(function step(now) {
        const p = Math.min((now - start) / dur, 1);
        const ease = 1 - Math.pow(1 - p, 3);
        el.textContent = fmt(from + (to - from) * ease);
        if (p < 1) requestAnimationFrame(step);
      });
    }

    function refreshDisplayClock() {
      document.getElementById('footerClock').textContent =
        new Date().toLocaleString('en-PK', { dateStyle: 'medium', timeStyle: 'medium' });
    }

    // ─── RUNTIME INTIALIZATION ENTRY ─────────────────────────────────────────
    document.addEventListener('DOMContentLoaded', () => {
      refreshDisplayClock();
      setInterval(refreshDisplayClock, 1000);
      evaluateSessionState();
    });
  </script>
</body>
</html>
