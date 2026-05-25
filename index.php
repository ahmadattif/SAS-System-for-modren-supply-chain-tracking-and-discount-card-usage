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
    /* ── DESIGN TOKENS ──────────────────────────────────────────────── */
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

      --cyan:    #00d4ff;
      --mint:    #00e5a0;
      --crimson: #ff3355;
      --amber:   #ffaa00;
      --purple:  #8b5cf6;
      --gold:    #f5c518;

      --cyan-bg:    rgba(0,212,255,0.06);
      --mint-bg:    rgba(0,229,160,0.06);
      --crimson-bg: rgba(255,51,85,0.06);
      --amber-bg:   rgba(255,170,0,0.06);
      --purple-bg:  rgba(139,92,246,0.06);
      --gold-bg:    rgba(245,197,24,0.08);

      --font-display: 'Orbitron', sans-serif;
      --font-body:    'Rajdhani', sans-serif;
      --font-mono:    'JetBrains Mono', monospace;

      --radius-sm: 6px;
      --radius:    10px;
      --radius-lg: 16px;

      --shadow-panel: 0 4px 32px rgba(0,0,0,0.5);
      --shadow-glow:  0 0 24px rgba(0,212,255,0.12);
      --shadow-gold:  0 0 24px rgba(245,197,24,0.12);

      --transition: 0.18s ease;
    }

    /* ── RESET & BASE ───────────────────────────────────────────────── */
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    html { scroll-behavior: smooth; }

    body {
      font-family: var(--font-body);
      background-color: var(--bg-void);
      color: var(--txt);
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      font-size: 15px;
      line-height: 1.5;
      overflow-x: hidden;
    }

    /* Subtle dot-grid background */
    body::before {
      content: '';
      position: fixed;
      inset: 0;
      background-image:
        radial-gradient(circle at 20% 20%, rgba(0,212,255,0.025) 0%, transparent 50%),
        radial-gradient(circle at 80% 80%, rgba(139,92,246,0.025) 0%, transparent 50%),
        radial-gradient(rgba(30,53,106,0.35) 1px, transparent 1px);
      background-size: auto, auto, 28px 28px;
      pointer-events: none;
      z-index: 0;
    }

    body > * { position: relative; z-index: 1; }

    /* ── SCROLLBAR ──────────────────────────────────────────────────── */
    ::-webkit-scrollbar { width: 6px; height: 6px; }
    ::-webkit-scrollbar-track { background: var(--bg-void); }
    ::-webkit-scrollbar-thumb { background: var(--border-glow); border-radius: 3px; }
    ::-webkit-scrollbar-thumb:hover { background: var(--cyan); }

    /* ── HEADER ─────────────────────────────────────────────────────── */
    header {
      background: linear-gradient(180deg, rgba(13,21,37,0.98) 0%, rgba(8,13,24,0.95) 100%);
      border-bottom: 1px solid var(--border);
      padding: 0 2rem;
      height: 58px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 1rem;
      position: sticky;
      top: 0;
      z-index: 100;
      backdrop-filter: blur(12px);
    }

    .logo {
      font-family: var(--font-display);
      font-size: 0.9rem;
      font-weight: 700;
      letter-spacing: 0.08em;
      color: var(--txt);
      display: flex;
      align-items: center;
      gap: 0.6rem;
    }
    .logo-icon {
      width: 28px; height: 28px;
      background: var(--cyan-bg);
      border: 1px solid var(--border-glow);
      border-radius: 6px;
      display: flex; align-items: center; justify-content: center;
    }
    .logo-icon svg { width: 15px; height: 15px; fill: var(--cyan); }
    .logo span { color: var(--cyan); }
    .logo-version {
      font-family: var(--font-mono);
      font-size: 0.6rem;
      color: var(--txt-muted);
      letter-spacing: 0.1em;
      border: 1px solid var(--border);
      padding: 1px 6px;
      border-radius: 3px;
    }

    .header-right {
      display: flex;
      align-items: center;
      gap: 0.75rem;
    }

    .user-badge {
      display: flex;
      align-items: center;
      gap: 0.6rem;
      background: var(--bg-elevated);
      border: 1px solid var(--border);
      border-radius: var(--radius-sm);
      padding: 0.35rem 0.85rem;
      font-size: 0.85rem;
      color: var(--txt-muted);
      transition: border-color var(--transition);
    }
    .user-badge.authenticated {
      border-color: var(--border-glow);
      color: var(--txt);
    }

    .role-dot {
      width: 7px; height: 7px;
      border-radius: 50%;
      background: var(--txt-dim);
      transition: background var(--transition), box-shadow var(--transition);
    }
    .role-dot.active-customer { background: var(--mint);    box-shadow: 0 0 8px var(--mint); }
    .role-dot.active-employee { background: var(--cyan);    box-shadow: 0 0 8px var(--cyan); }
    .role-dot.active-owner    { background: var(--purple);  box-shadow: 0 0 8px var(--purple); }

    #logoutBtn {
      display: none;
      padding: 0.28rem 0.7rem;
      font-size: 0.72rem;
      font-family: var(--font-mono);
      background: transparent;
      border: 1px solid var(--border);
      color: var(--txt-muted);
      border-radius: 4px;
      cursor: pointer;
      letter-spacing: 0.05em;
      transition: all var(--transition);
    }
    #logoutBtn:hover { border-color: var(--crimson); color: var(--crimson); }

    /* ── MAIN CONTAINER ─────────────────────────────────────────────── */
    main {
      flex: 1;
      padding: 2.25rem 2rem;
      max-width: 1640px;
      width: 100%;
      margin: 0 auto;
    }

    /* ── SCREEN CONTROLLER ──────────────────────────────────────────── */
    .screen { display: none; }
    .screen.active {
      display: block;
      animation: screenIn 0.35s ease forwards;
    }
    @keyframes screenIn {
      from { opacity: 0; transform: translateY(10px); }
      to   { opacity: 1; transform: translateY(0);    }
    }

    /* ── AUTH SCREEN ────────────────────────────────────────────────── */
    #authScreen { display: flex; align-items: center; justify-content: center; min-height: calc(100vh - 140px); }
    #authScreen.active { display: flex; }

    .auth-shell {
      width: 100%;
      max-width: 440px;
    }

    .auth-biometric {
      display: flex;
      justify-content: center;
      margin-bottom: 2rem;
    }
    .biometric-ring {
      position: relative;
      width: 76px; height: 76px;
    }
    .biometric-ring svg {
      width: 100%; height: 100%;
      animation: ringPulse 3s ease-in-out infinite;
    }
    @keyframes ringPulse {
      0%,100% { opacity: 0.6; transform: scale(1); }
      50%      { opacity: 1;   transform: scale(1.04); }
    }
    .biometric-inner {
      position: absolute;
      inset: 18px;
      border: 2px solid var(--border-glow);
      border-radius: 50%;
      background: var(--cyan-bg);
      display: flex; align-items: center; justify-content: center;
    }
    .biometric-inner::before {
      content: '';
      width: 10px; height: 10px;
      background: var(--cyan);
      border-radius: 50%;
      box-shadow: 0 0 10px var(--cyan);
      animation: corePulse 2s ease-in-out infinite;
    }
    @keyframes corePulse {
      0%,100% { opacity: 0.7; transform: scale(1); }
      50%      { opacity: 1;   transform: scale(1.3); }
    }

    .auth-card {
      background: var(--bg-surface);
      border: 1px solid var(--border);
      border-radius: var(--radius-lg);
      padding: 2.5rem;
      display: flex;
      flex-direction: column;
      gap: 1.5rem;
      box-shadow: var(--shadow-panel), 0 0 60px rgba(0,212,255,0.04);
    }

    .auth-header { text-align: center; }
    .auth-header h2 {
      font-family: var(--font-display);
      font-size: 1.05rem;
      font-weight: 700;
      letter-spacing: 0.05em;
      color: var(--txt);
      margin-bottom: 0.4rem;
    }
    .auth-header p {
      font-size: 0.85rem;
      color: var(--txt-muted);
      font-family: var(--font-mono);
    }

    .auth-divider {
      height: 1px;
      background: linear-gradient(90deg, transparent, var(--border), transparent);
    }

    .auth-error {
      background: var(--crimson-bg);
      border: 1px solid rgba(255,51,85,0.25);
      color: var(--crimson);
      padding: 0.6rem 1rem;
      border-radius: var(--radius-sm);
      font-size: 0.82rem;
      font-family: var(--font-mono);
      display: none;
    }
    .auth-error.show { display: block; animation: shakeX 0.35s ease; }
    @keyframes shakeX {
      0%,100% { transform: translateX(0); }
      20%,60% { transform: translateX(-5px); }
      40%,80% { transform: translateX(5px); }
    }

    /* ── FORM ELEMENTS ──────────────────────────────────────────────── */
    .form-group { display: flex; flex-direction: column; gap: 0.4rem; }
    .form-group + .form-group { margin-top: 0.85rem; }

    label {
      font-size: 0.78rem;
      font-weight: 600;
      letter-spacing: 0.06em;
      text-transform: uppercase;
      color: var(--txt-muted);
    }

    input, select, textarea {
      background: var(--bg-base);
      border: 1px solid var(--border);
      color: var(--txt);
      padding: 0.7rem 1rem;
      border-radius: var(--radius-sm);
      font-family: var(--font-body);
      font-size: 0.95rem;
      width: 100%;
      transition: border-color var(--transition), box-shadow var(--transition);
      -webkit-appearance: none;
    }
    input:focus, select:focus, textarea:focus {
      outline: none;
      border-color: var(--cyan);
      box-shadow: 0 0 0 2px rgba(0,212,255,0.1);
    }
    input::placeholder { color: var(--txt-dim); }
    textarea { resize: vertical; min-height: 72px; }

    select option { background: var(--bg-elevated); }

    /* ── BUTTONS ────────────────────────────────────────────────────── */
    .btn {
      font-family: var(--font-body);
      font-weight: 700;
      font-size: 0.9rem;
      letter-spacing: 0.05em;
      padding: 0.75rem 1.5rem;
      border-radius: var(--radius-sm);
      border: none;
      cursor: pointer;
      transition: all var(--transition);
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 0.5rem;
    }
    .btn-primary {
      background: var(--cyan);
      color: var(--bg-void);
    }
    .btn-primary:hover {
      background: #33ddff;
      box-shadow: 0 0 20px rgba(0,212,255,0.35);
      transform: translateY(-1px);
    }
    .btn-secondary {
      background: transparent;
      border: 1px solid var(--border);
      color: var(--txt-muted);
    }
    .btn-secondary:hover {
      border-color: var(--cyan);
      color: var(--cyan);
    }
    .btn-danger {
      background: var(--crimson-bg);
      border: 1px solid rgba(255,51,85,0.3);
      color: var(--crimson);
    }
    .btn-danger:hover {
      background: var(--crimson);
      color: white;
      box-shadow: 0 0 20px rgba(255,51,85,0.3);
    }
    .btn-warning {
      background: var(--amber-bg);
      border: 1px solid rgba(255,170,0,0.3);
      color: var(--amber);
    }
    .btn-warning:hover {
      background: var(--amber);
      color: var(--bg-void);
      box-shadow: 0 0 20px rgba(255,170,0,0.3);
    }
    .btn-full { width: 100%; }

    /* ── PANELS & CARDS ─────────────────────────────────────────────── */
    .panel {
      background: var(--bg-surface);
      border: 1px solid var(--border);
      border-radius: var(--radius-lg);
      padding: 1.75rem;
      box-shadow: var(--shadow-panel);
    }

    .panel-hd {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 1.5rem;
      gap: 1rem;
    }
    .panel-title {
      font-family: var(--font-display);
      font-size: 0.72rem;
      font-weight: 600;
      letter-spacing: 0.1em;
      text-transform: uppercase;
      color: var(--txt);
    }
    .panel-badge {
      font-family: var(--font-mono);
      font-size: 0.68rem;
      padding: 2px 8px;
      border-radius: 3px;
      border: 1px solid;
    }
    .panel-badge.cyan    { border-color: rgba(0,212,255,0.3);   color: var(--cyan);    background: var(--cyan-bg); }
    .panel-badge.mint    { border-color: rgba(0,229,160,0.3);   color: var(--mint);    background: var(--mint-bg); }
    .panel-badge.crimson { border-color: rgba(255,51,85,0.3);   color: var(--crimson); background: var(--crimson-bg); }
    .panel-badge.amber   { border-color: rgba(255,170,0,0.3);   color: var(--amber);   background: var(--amber-bg); }
    .panel-badge.purple  { border-color: rgba(139,92,246,0.3);  color: var(--purple);  background: var(--purple-bg); }
    .panel-badge.gold    { border-color: rgba(245,197,24,0.3);  color: var(--gold);    background: var(--gold-bg); }

    /* ── METRICS STRIP ──────────────────────────────────────────────── */
    .metrics-strip {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(230px, 1fr));
      gap: 1.25rem;
      margin-bottom: 2rem;
    }

    .metric-card {
      background: var(--bg-surface);
      border: 1px solid var(--border);
      border-radius: var(--radius);
      padding: 1.4rem;
      position: relative;
      overflow: hidden;
      transition: border-color var(--transition), box-shadow var(--transition);
    }
    .metric-card:hover {
      border-color: var(--border-glow);
      box-shadow: var(--shadow-glow);
    }
    .metric-card::after {
      content: '';
      position: absolute;
      top: 0; left: 0;
      width: 3px; height: 100%;
      border-radius: 3px 0 0 3px;
    }
    .metric-card.mc-cyan::after   { background: var(--cyan); }
    .metric-card.mc-mint::after   { background: var(--mint); }
    .metric-card.mc-crimson::after{ background: var(--crimson); }
    .metric-card.mc-amber::after  { background: var(--amber); }
    .metric-card.mc-purple::after { background: var(--purple); }
    .metric-card.mc-gold::after   { background: var(--gold); }

    .metric-label {
      font-size: 0.72rem;
      font-weight: 600;
      letter-spacing: 0.08em;
      text-transform: uppercase;
      color: var(--txt-muted);
      margin-bottom: 0.6rem;
    }
    .metric-value {
      font-family: var(--font-display);
      font-size: 1.6rem;
      font-weight: 700;
      line-height: 1;
      color: var(--txt);
    }
    .metric-sub {
      margin-top: 0.35rem;
      font-size: 0.78rem;
      color: var(--txt-muted);
    }

    /* ── TABLES ─────────────────────────────────────────────────────── */
    .table-wrapper { overflow-x: auto; }

    table {
      width: 100%;
      border-collapse: collapse;
      font-size: 0.88rem;
    }
    thead th {
      padding: 0.65rem 1rem;
      background: rgba(255,255,255,0.02);
      border-bottom: 1px solid var(--border);
      font-size: 0.7rem;
      font-weight: 700;
      letter-spacing: 0.08em;
      text-transform: uppercase;
      color: var(--txt-muted);
      text-align: left;
      white-space: nowrap;
    }
    tbody td {
      padding: 0.85rem 1rem;
      border-bottom: 1px solid rgba(26,45,74,0.5);
      color: var(--txt);
      vertical-align: middle;
    }
    tbody tr {
      transition: background var(--transition);
    }
    tbody tr:hover { background: rgba(255,255,255,0.02); }
    tbody tr:last-child td { border-bottom: none; }

    .mono { font-family: var(--font-mono); font-size: 0.82rem; }

    /* ── STATUS BADGES ──────────────────────────────────────────────── */
    .badge {
      display: inline-flex;
      align-items: center;
      gap: 5px;
      padding: 3px 9px;
      border-radius: 4px;
      font-size: 0.72rem;
      font-weight: 600;
      letter-spacing: 0.05em;
      border: 1px solid;
    }
    .badge-dot {
      width: 5px; height: 5px;
      border-radius: 50%;
    }
    .badge.b-mint    { color: var(--mint);    background: var(--mint-bg);    border-color: rgba(0,229,160,0.25); }
    .badge.b-cyan    { color: var(--cyan);    background: var(--cyan-bg);    border-color: rgba(0,212,255,0.25); }
    .badge.b-crimson { color: var(--crimson); background: var(--crimson-bg); border-color: rgba(255,51,85,0.25); }
    .badge.b-amber   { color: var(--amber);   background: var(--amber-bg);   border-color: rgba(255,170,0,0.25); }
    .badge.b-purple  { color: var(--purple);  background: var(--purple-bg);  border-color: rgba(139,92,246,0.25); }
    .badge.b-gold    { color: var(--gold);    background: var(--gold-bg);    border-color: rgba(245,197,24,0.3); }

    /* ── GRID LAYOUTS ───────────────────────────────────────────────── */
    .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; }
    .grid-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1.5rem; }
    .grid-aside { display: grid; grid-template-columns: 1fr 380px; gap: 1.5rem; }
    @media (max-width: 1100px) {
      .grid-2, .grid-3, .grid-aside { grid-template-columns: 1fr; }
    }

    .mb-2 { margin-bottom: 1.5rem; }

    /* ════════════════════════════════════════════════════════════════
       ── CUSTOMER SCREEN ──────────────────────────────────────────
    ════════════════════════════════════════════════════════════════ */

    /* Tier membership card */
    .tier-card {
      border-radius: var(--radius-lg);
      padding: 2rem;
      position: relative;
      overflow: hidden;
      display: flex;
      align-items: flex-start;
      justify-content: space-between;
      gap: 1rem;
    }
    .tier-card.tier-basic  { background: linear-gradient(135deg, #0d1a2e 0%, #0f2040 100%); border: 1px solid rgba(0,212,255,0.2); }
    .tier-card.tier-silver { background: linear-gradient(135deg, #101520 0%, #1a1f30 100%); border: 1px solid rgba(150,160,180,0.25); }
    .tier-card.tier-gold   { background: linear-gradient(135deg, #1a1200 0%, #2a1e00 100%); border: 1px solid rgba(245,197,24,0.3); box-shadow: var(--shadow-gold); }

    .tier-card::before {
      content: '';
      position: absolute;
      top: -40px; right: -40px;
      width: 160px; height: 160px;
      border-radius: 50%;
      filter: blur(40px);
    }
    .tier-basic::before  { background: rgba(0,212,255,0.08); }
    .tier-silver::before { background: rgba(150,160,180,0.08); }
    .tier-gold::before   { background: rgba(245,197,24,0.1); }

    .tier-info { flex: 1; }
    .tier-label {
      font-size: 0.7rem;
      font-weight: 700;
      letter-spacing: 0.12em;
      text-transform: uppercase;
      color: var(--txt-muted);
      margin-bottom: 0.4rem;
    }
    .tier-name {
      font-family: var(--font-display);
      font-size: 1.7rem;
      font-weight: 900;
      line-height: 1;
      margin-bottom: 0.5rem;
    }
    .tier-basic  .tier-name { color: var(--cyan); }
    .tier-silver .tier-name { color: #b0bec5; }
    .tier-gold   .tier-name { color: var(--gold); }
    .tier-disc {
      font-family: var(--font-mono);
      font-size: 0.8rem;
      color: var(--txt-muted);
    }
    .tier-discount-val { color: var(--mint); font-weight: 600; }

    .tier-emblem {
      font-family: var(--font-display);
      font-size: 3rem;
      line-height: 1;
      opacity: 0.15;
    }

    /* Billing countdown */
    .billing-countdown {
      display: flex;
      align-items: center;
      gap: 1rem;
      background: var(--bg-elevated);
      border: 1px solid var(--border);
      border-radius: var(--radius);
      padding: 1.1rem 1.4rem;
    }
    .billing-icon {
      width: 40px; height: 40px; flex-shrink: 0;
      background: var(--amber-bg);
      border: 1px solid rgba(255,170,0,0.2);
      border-radius: 8px;
      display: flex; align-items: center; justify-content: center;
      font-size: 1.1rem;
    }
    .billing-info { flex: 1; }
    .billing-label { font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.07em; color: var(--txt-muted); margin-bottom: 2px; }
    .billing-date  { font-size: 1rem; font-weight: 700; color: var(--txt); }
    .billing-days  { font-family: var(--font-mono); font-size: 0.78rem; }
    .billing-days.urgent  { color: var(--crimson); }
    .billing-days.soon    { color: var(--amber); }
    .billing-days.ok      { color: var(--mint); }

    /* ════════════════════════════════════════════════════════════════
       ── EMPLOYEE SCREEN ──────────────────────────────────────────
    ════════════════════════════════════════════════════════════════ */

    .shift-header {
      background: linear-gradient(135deg, #0d1a2e 0%, #08132b 50%, #0a0f20 100%);
      border: 1px solid var(--border-glow);
      border-radius: var(--radius-lg);
      padding: 1.75rem 2rem;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 1.5rem;
      margin-bottom: 1.75rem;
      position: relative;
      overflow: hidden;
      box-shadow: 0 0 40px rgba(0,212,255,0.05);
    }
    .shift-header::before {
      content: '';
      position: absolute;
      bottom: 0; left: 0; right: 0;
      height: 1px;
      background: linear-gradient(90deg, transparent, var(--cyan), transparent);
      opacity: 0.4;
    }

    .shift-agent { flex: 1; }
    .shift-online {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      font-family: var(--font-mono);
      font-size: 0.68rem;
      color: var(--mint);
      letter-spacing: 0.08em;
      margin-bottom: 0.4rem;
    }
    .shift-online::before {
      content: '';
      width: 6px; height: 6px;
      background: var(--mint);
      border-radius: 50%;
      box-shadow: 0 0 8px var(--mint);
      animation: blink 2s ease-in-out infinite;
    }
    @keyframes blink { 0%,100%{opacity:1} 50%{opacity:0.3} }

    .shift-name {
      font-family: var(--font-display);
      font-size: 1.3rem;
      font-weight: 700;
      color: var(--txt);
      letter-spacing: 0.02em;
      margin-bottom: 0.2rem;
    }
    .shift-role {
      font-size: 0.82rem;
      color: var(--cyan);
      font-family: var(--font-mono);
    }

    .shift-stats {
      display: flex;
      gap: 1.5rem;
    }
    .shift-stat {
      text-align: center;
      background: rgba(0,0,0,0.25);
      border: 1px solid var(--border);
      border-radius: var(--radius-sm);
      padding: 0.7rem 1.2rem;
      min-width: 90px;
    }
    .shift-stat-val {
      font-family: var(--font-display);
      font-size: 1.4rem;
      font-weight: 700;
      color: var(--cyan);
      line-height: 1;
    }
    .shift-stat-lbl {
      font-size: 0.66rem;
      text-transform: uppercase;
      letter-spacing: 0.07em;
      color: var(--txt-muted);
      margin-top: 3px;
    }

    /* Hash Deconstructor */
    .hash-input-wrapper {
      position: relative;
    }
    .hash-input-wrapper input {
      font-family: var(--font-mono);
      font-size: 1.05rem;
      letter-spacing: 0.12em;
      text-transform: uppercase;
      padding-right: 2.5rem;
    }
    .hash-char-count {
      position: absolute;
      right: 0.75rem;
      top: 50%;
      transform: translateY(-50%);
      font-family: var(--font-mono);
      font-size: 0.7rem;
      color: var(--txt-muted);
      pointer-events: none;
    }

    .hash-visual {
      background: var(--bg-base);
      border: 1px solid var(--border);
      border-radius: var(--radius);
      padding: 1.25rem 1.5rem;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 0.35rem;
      margin: 1.25rem 0;
      min-height: 72px;
      font-family: var(--font-mono);
    }
    .hash-chunk {
      padding: 0.35rem 0.7rem;
      border-radius: var(--radius-sm);
      font-size: 1.3rem;
      font-weight: 700;
      letter-spacing: 0.06em;
      transition: all var(--transition);
      position: relative;
    }
    .hash-sep {
      color: var(--txt-dim);
      font-size: 1rem;
      user-select: none;
    }
    .hc-sup { background: rgba(255,51,85,0.1);   color: var(--crimson); border: 1px solid rgba(255,51,85,0.2); }
    .hc-typ { background: rgba(255,170,0,0.1);   color: var(--amber);   border: 1px solid rgba(255,170,0,0.2); }
    .hc-ser { background: rgba(0,212,255,0.1);   color: var(--cyan);    border: 1px solid rgba(0,212,255,0.2); }
    .hc-mem { background: rgba(0,229,160,0.1);   color: var(--mint);    border: 1px solid rgba(0,229,160,0.2); }

    .hash-chunk.valid { box-shadow: 0 0 12px currentColor; opacity: 1; }
    .hash-chunk.empty { opacity: 0.3; }

    .hash-lookup-table td:first-child {
      width: 200px;
      font-size: 0.8rem;
      font-weight: 700;
    }
    .hash-lookup-table td:last-child {
      font-family: var(--font-mono);
      font-size: 0.82rem;
      color: var(--txt-muted);
    }
    .hash-lookup-table td { padding: 0.6rem 0.75rem; }

    /* Progress bar for integrity score */
    .score-bar {
      display: flex;
      align-items: center;
      gap: 0.75rem;
    }
    .score-track {
      flex: 1;
      height: 5px;
      background: var(--bg-base);
      border-radius: 3px;
      overflow: hidden;
    }
    .score-fill {
      height: 100%;
      border-radius: 3px;
      transition: width 0.6s ease;
    }
    .score-num {
      font-family: var(--font-mono);
      font-size: 0.8rem;
      font-weight: 600;
      min-width: 45px;
      text-align: right;
    }

    /* ════════════════════════════════════════════════════════════════
       ── OWNER SCREEN ─────────────────────────────────────────────
    ════════════════════════════════════════════════════════════════ */

    .owner-metric-large {
      background: var(--bg-surface);
      border: 1px solid var(--border);
      border-radius: var(--radius-lg);
      padding: 1.75rem;
      position: relative;
      overflow: hidden;
    }
    .owner-metric-large .big-number {
      font-family: var(--font-display);
      font-size: 2.4rem;
      font-weight: 900;
      line-height: 1;
      margin: 0.5rem 0;
    }
    .big-plus { color: var(--mint); font-size: 0.9em; }
    .trend-tag {
      display: inline-flex;
      align-items: center;
      gap: 4px;
      font-family: var(--font-mono);
      font-size: 0.72rem;
      padding: 2px 8px;
      border-radius: 4px;
      border: 1px solid;
    }
    .trend-up   { color: var(--mint);    background: var(--mint-bg);    border-color: rgba(0,229,160,0.25); }
    .trend-down { color: var(--crimson); background: var(--crimson-bg); border-color: rgba(255,51,85,0.25); }
    .trend-flat { color: var(--txt-muted); background: rgba(255,255,255,0.02); border-color: var(--border); }

    /* Domain link map panel */
    .domain-map { display: flex; flex-direction: column; gap: 1rem; }
    .domain-item {
      background: var(--bg-elevated);
      border: 1px solid var(--border);
      border-radius: var(--radius);
      padding: 1rem 1.2rem;
      display: flex;
      align-items: flex-start;
      gap: 0.85rem;
      transition: border-color var(--transition);
    }
    .domain-item:hover { border-color: var(--border-glow); }
    .domain-icon {
      width: 32px; height: 32px; flex-shrink: 0;
      border-radius: 7px;
      display: flex; align-items: center; justify-content: center;
      font-size: 0.85rem;
    }
    .d-consumer .domain-icon { background: var(--mint-bg);    border: 1px solid rgba(0,229,160,0.2); }
    .d-employee .domain-icon { background: var(--cyan-bg);    border: 1px solid rgba(0,212,255,0.2); }
    .d-business .domain-icon { background: var(--crimson-bg); border: 1px solid rgba(255,51,85,0.2); }
    .domain-name {
      font-size: 0.8rem;
      font-weight: 700;
      margin-bottom: 2px;
    }
    .d-consumer .domain-name { color: var(--mint); }
    .d-employee .domain-name { color: var(--cyan); }
    .d-business .domain-name { color: var(--crimson); }
    .domain-desc {
      font-size: 0.8rem;
      color: var(--txt-muted);
      line-height: 1.45;
    }

    /* ── MODAL ──────────────────────────────────────────────────────── */
    .modal-backdrop {
      position: fixed; inset: 0;
      background: rgba(5,8,16,0.85);
      backdrop-filter: blur(6px);
      display: none;
      align-items: center;
      justify-content: center;
      z-index: 500;
    }
    .modal-backdrop.open {
      display: flex;
      animation: fadeIn 0.2s ease;
    }
    @keyframes fadeIn { from{opacity:0} to{opacity:1} }

    .modal-box {
      background: var(--bg-surface);
      border: 1px solid var(--border-glow);
      border-radius: var(--radius-lg);
      padding: 2.25rem;
      max-width: 500px;
      width: 90%;
      box-shadow: 0 20px 80px rgba(0,0,0,0.6), 0 0 60px rgba(255,170,0,0.06);
      animation: modalIn 0.3s cubic-bezier(0.34,1.56,0.64,1) forwards;
    }
    @keyframes modalIn {
      from { opacity:0; transform:scale(0.88) translateY(20px); }
      to   { opacity:1; transform:scale(1) translateY(0); }
    }

    .modal-warn-band {
      display: flex;
      align-items: center;
      gap: 0.75rem;
      background: var(--amber-bg);
      border: 1px solid rgba(255,170,0,0.2);
      border-radius: var(--radius-sm);
      padding: 0.85rem 1rem;
      margin-bottom: 1.5rem;
    }
    .modal-warn-icon {
      font-size: 1.25rem;
      line-height: 1;
    }
    .modal-warn-text {
      font-family: var(--font-mono);
      font-size: 0.8rem;
      color: var(--amber);
      letter-spacing: 0.04em;
    }

    .modal-title {
      font-family: var(--font-display);
      font-size: 1.1rem;
      font-weight: 700;
      color: var(--txt);
      margin-bottom: 0.5rem;
      letter-spacing: 0.04em;
    }
    .modal-body {
      font-size: 0.88rem;
      color: var(--txt-muted);
      line-height: 1.6;
      margin-bottom: 1.5rem;
    }

    .node-display {
      background: var(--bg-base);
      border: 1px solid var(--border);
      border-radius: var(--radius-sm);
      padding: 0.7rem 1rem;
      font-family: var(--font-mono);
      font-size: 0.88rem;
      color: var(--cyan);
      letter-spacing: 0.06em;
      margin-bottom: 1.5rem;
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }
    .node-dot {
      width: 6px; height: 6px;
      background: var(--cyan);
      border-radius: 50%;
      box-shadow: 0 0 6px var(--cyan);
    }

    /* ── TOAST SYSTEM ───────────────────────────────────────────────── */
    #toastContainer {
      position: fixed;
      top: 70px;
      right: 1.5rem;
      display: flex;
      flex-direction: column;
      gap: 0.6rem;
      z-index: 999;
      pointer-events: none;
    }

    .toast {
      background: var(--bg-elevated);
      border-radius: var(--radius-sm);
      padding: 0.8rem 1.1rem;
      display: flex;
      align-items: flex-start;
      gap: 0.7rem;
      min-width: 300px;
      max-width: 380px;
      box-shadow: 0 8px 32px rgba(0,0,0,0.4);
      border: 1px solid;
      animation: toastIn 0.3s ease forwards;
      pointer-events: all;
    }
    @keyframes toastIn {
      from { opacity:0; transform:translateX(20px); }
      to   { opacity:1; transform:translateX(0); }
    }
    .toast.toast-out {
      animation: toastOut 0.3s ease forwards;
    }
    @keyframes toastOut {
      to { opacity:0; transform:translateX(20px); height:0; padding:0; margin:0; border-width:0; }
    }

    .toast.t-success { border-color: rgba(0,229,160,0.25); }
    .toast.t-error   { border-color: rgba(255,51,85,0.25); }
    .toast.t-warning { border-color: rgba(255,170,0,0.25); }
    .toast.t-info    { border-color: rgba(0,212,255,0.25); }

    .toast-icon { font-size: 1rem; margin-top: 1px; flex-shrink: 0; }
    .t-success .toast-icon { color: var(--mint); }
    .t-error   .toast-icon { color: var(--crimson); }
    .t-warning .toast-icon { color: var(--amber); }
    .t-info    .toast-icon { color: var(--cyan); }

    .toast-body { flex: 1; }
    .toast-title { font-weight: 700; font-size: 0.85rem; margin-bottom: 2px; }
    .t-success .toast-title { color: var(--mint); }
    .t-error   .toast-title { color: var(--crimson); }
    .t-warning .toast-title { color: var(--amber); }
    .t-info    .toast-title { color: var(--cyan); }
    .toast-msg  { font-size: 0.8rem; color: var(--txt-muted); line-height: 1.4; }

    /* ── FOOTER ─────────────────────────────────────────────────────── */
    footer {
      border-top: 1px solid var(--border);
      padding: 0.85rem 2rem;
      font-family: var(--font-mono);
      font-size: 0.7rem;
      color: var(--txt-dim);
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 1rem;
      letter-spacing: 0.05em;
    }
    footer span { color: var(--txt-muted); }

    /* ── UTILITY ────────────────────────────────────────────────────── */
    .txt-cyan    { color: var(--cyan) !important; }
    .txt-mint    { color: var(--mint) !important; }
    .txt-crimson { color: var(--crimson) !important; }
    .txt-amber   { color: var(--amber) !important; }
    .txt-purple  { color: var(--purple) !important; }
    .txt-gold    { color: var(--gold) !important; }
    .txt-muted   { color: var(--txt-muted) !important; }

    .fw-7 { font-weight: 700; }

    .divider {
      height: 1px;
      background: linear-gradient(90deg, transparent, var(--border), transparent);
      margin: 1.5rem 0;
    }
  </style>
</head>
<body>

  <!-- ── HEADER ────────────────────────────────────────────────────── -->
  <header>
    <div class="logo">
      <div class="logo-icon">
        <svg viewBox="0 0 24 24"><path d="M12 2L3 7v5c0 5.25 3.75 10.15 9 11.35C17.25 22.15 21 17.25 21 12V7L12 2zm0 5l5 3v3c0 3.45-2.45 6.65-5 7.75V10L7 8V7l5-3z"/></svg>
      </div>
      SENTINEL <span>SAS</span>
      <span class="logo-version">v4.1</span>
    </div>
    <div class="header-right">
      <div class="user-badge" id="userBadge">
        <div class="role-dot" id="roleDot"></div>
        <span id="userLabel">Awaiting Authentication</span>
      </div>
      <button id="logoutBtn" onclick="handleLogout()">EXIT SESSION</button>
    </div>
  </header>

  <!-- ── TOAST CONTAINER ───────────────────────────────────────────── -->
  <div id="toastContainer"></div>

  <!-- ── MAIN ──────────────────────────────────────────────────────── -->
  <main>

    <!-- ═══════════════════════════════════════════
         AUTH SCREEN
    ═══════════════════════════════════════════ -->
    <div id="authScreen" class="screen active">
      <div class="auth-shell">
        <div class="auth-biometric">
          <div class="biometric-ring">
            <svg viewBox="0 0 76 76" xmlns="http://www.w3.org/2000/svg">
              <circle cx="38" cy="38" r="35" fill="none" stroke="rgba(0,212,255,0.15)" stroke-width="1"/>
              <circle cx="38" cy="38" r="35" fill="none" stroke="rgba(0,212,255,0.5)" stroke-width="1"
                stroke-dasharray="60 160" stroke-dashoffset="0">
                <animateTransform attributeName="transform" type="rotate" from="0 38 38" to="360 38 38" dur="8s" repeatCount="indefinite"/>
              </circle>
              <circle cx="38" cy="38" r="35" fill="none" stroke="rgba(0,212,255,0.25)" stroke-width="1"
                stroke-dasharray="20 200" stroke-dashoffset="40">
                <animateTransform attributeName="transform" type="rotate" from="360 38 38" to="0 38 38" dur="5s" repeatCount="indefinite"/>
              </circle>
            </svg>
            <div class="biometric-inner"></div>
          </div>
        </div>
        <div class="auth-card">
          <div class="auth-header">
            <h2>GATEWAY AUTHENTICATION</h2>
            <p>Multi-domain access credential verification</p>
          </div>
          <div class="auth-divider"></div>
          <div id="authError" class="auth-error"></div>
          <div class="form-group">
            <label>Identity Handle</label>
            <input type="text" id="authUsername" placeholder="Username" autocomplete="off">
          </div>
          <div class="form-group">
            <label>Biometric Key Mapping</label>
            <input type="password" id="authPassword" placeholder="••••••••" onkeydown="if(event.key==='Enter')handleLogin()">
          </div>
          <div class="form-group">
            <label>Access Domain</label>
            <select id="authRole">
              <option value="Customer">Consumer Domain  — Customer</option>
              <option value="Employee">Operational Hub  — Employee</option>
              <option value="Owner">Administrative Core — Owner</option>
            </select>
          </div>
          <button class="btn btn-primary btn-full" onclick="handleLogin()">
            ⬡ &nbsp;VERIFY CREDENTIALS
          </button>
          <p style="font-size:0.72rem; color:var(--txt-dim); text-align:center; font-family:var(--font-mono);">
            Demo: any username + any password. Select a domain above.
          </p>
        </div>
      </div>
    </div>

    <!-- ═══════════════════════════════════════════
         CUSTOMER SCREEN
    ═══════════════════════════════════════════ -->
    <div id="customerScreen" class="screen">

      <div class="metrics-strip" style="grid-template-columns: 2fr 1fr 1fr; margin-bottom: 1.75rem;">

        <!-- Tier Card -->
        <div id="tierCard" class="tier-card tier-gold">
          <div class="tier-info">
            <div class="tier-label">Verified Membership Status</div>
            <div class="tier-name" id="custTierName">Gold Privilege</div>
            <div class="tier-disc">Loyalty Tier &nbsp;·&nbsp; <span class="tier-discount-val" id="custTierDisc">12%</span> purchase discount applied</div>
          </div>
          <div class="tier-emblem" id="tierEmblem">★</div>
        </div>

        <!-- Balance -->
        <div class="metric-card mc-cyan">
          <div class="metric-label">Account Balance</div>
          <div class="metric-value txt-cyan" id="custBalance">Rs.&nbsp;4,500</div>
          <div class="metric-sub" id="custSpent">Total spent: Rs. 28,450</div>
        </div>

        <!-- Billing -->
        <div class="metric-card mc-amber">
          <div class="metric-label">Membership Status</div>
          <div class="metric-value" id="custMemberStatus" style="font-size:1.2rem; padding-top:4px;">Active</div>
          <div class="metric-sub" id="custMemberSub">Renewal: Jun 15, 2026</div>
        </div>
      </div>

      <!-- Billing countdown banner -->
      <div class="billing-countdown mb-2">
        <div class="billing-icon">📅</div>
        <div class="billing-info">
          <div class="billing-label">Next Automated Renewal Cycle</div>
          <div class="billing-date" id="custBillingDate">June 15, 2026</div>
        </div>
        <div class="billing-days ok" id="custBillingDays">Loading…</div>
      </div>

      <div class="panel">
        <div class="panel-hd">
          <div class="panel-title">Asset Ownership &amp; Purchase History</div>
          <span class="panel-badge mint" id="custHistoryCount">2 Records</span>
        </div>
        <div class="table-wrapper">
          <table>
            <thead>
              <tr>
                <th>16-Char Asset Hash</th>
                <th>Product Description</th>
                <th>Transaction Ref</th>
                <th>Purchase Date</th>
                <th>Final Net (Rs.)</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody id="custHistoryBody"></tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- ═══════════════════════════════════════════
         EMPLOYEE SCREEN
    ═══════════════════════════════════════════ -->
    <div id="employeeScreen" class="screen">

      <!-- Shift Header -->
      <div class="shift-header">
        <div class="shift-agent">
          <div class="shift-online">SESSION ACTIVE</div>
          <div class="shift-name" id="empName">Workspace Agent</div>
          <div class="shift-role" id="empRole">Systems Specialist // Node: Secure</div>
        </div>
        <div class="shift-stats">
          <div class="shift-stat">
            <div class="shift-stat-val" id="empTxCount">14</div>
            <div class="shift-stat-lbl">Audits</div>
          </div>
          <div class="shift-stat">
            <div class="shift-stat-val" id="empDefectCount">3</div>
            <div class="shift-stat-lbl">Defects</div>
          </div>
          <div class="shift-stat">
            <div class="shift-stat-val txt-mint">●</div>
            <div class="shift-stat-lbl">Node</div>
          </div>
        </div>
      </div>

      <div class="grid-2">

        <!-- Hash Deconstructor -->
        <div class="panel">
          <div class="panel-hd">
            <div class="panel-title">Asset Verification &amp; Hex Deconstructor</div>
            <span class="panel-badge cyan">LIVE</span>
          </div>

          <div class="form-group">
            <label>Scan / Paste 16-Character System Asset Key</label>
            <div class="hash-input-wrapper">
              <input type="text" id="empHashInput" maxlength="16"
                placeholder="e.g. A4F299B100C3E9D2"
                value="A4F299B100C3E9D2"
                oninput="updateHashDeconstruct()"
                style="font-family:var(--font-mono);text-transform:uppercase;letter-spacing:.1em;">
              <span class="hash-char-count" id="hashCharCount">16/16</span>
            </div>
          </div>

          <div class="hash-visual" id="hashVisual">
            <span class="hash-chunk hc-sup" id="hcSup">----</span>
            <span class="hash-sep">·</span>
            <span class="hash-chunk hc-typ" id="hcTyp">----</span>
            <span class="hash-sep">·</span>
            <span class="hash-chunk hc-ser" id="hcSer">----</span>
            <span class="hash-sep">·</span>
            <span class="hash-chunk hc-mem" id="hcMem">----</span>
          </div>

          <table class="hash-lookup-table">
            <tbody>
              <tr>
                <td><span class="txt-crimson fw-7">▪ Supplier ID (0–3)</span></td>
                <td id="lblSup">—</td>
              </tr>
              <tr>
                <td><span class="txt-amber fw-7">▪ Item Type (4–7)</span></td>
                <td id="lblTyp">—</td>
              </tr>
              <tr>
                <td><span class="txt-cyan fw-7">▪ Serial Sig. (8–11)</span></td>
                <td id="lblSer">—</td>
              </tr>
              <tr>
                <td><span class="txt-mint fw-7">▪ Member ID (12–15)</span></td>
                <td id="lblMem">—</td>
              </tr>
            </tbody>
          </table>

          <div class="divider"></div>

          <div id="hashValidationAlert" style="display:none;" class="auth-error"></div>
          <button class="btn btn-primary btn-full" onclick="validateHash()">⬡ &nbsp;SUBMIT HASH FOR VALIDATION</button>
        </div>

        <!-- Defect Logger -->
        <div class="panel">
          <div class="panel-hd">
            <div class="panel-title">Defect Log Submission</div>
            <span class="panel-badge crimson">STOCK LEDGER</span>
          </div>

          <div class="form-group">
            <label>12-Character Unit Hash (Supplier + Type + Serial)</label>
            <div class="hash-input-wrapper">
              <input type="text" id="defectHash" maxlength="12"
                placeholder="e.g. A4F299B100C3"
                oninput="this.value=this.value.toUpperCase();updateDefectCount()"
                style="font-family:var(--font-mono);letter-spacing:.1em;">
              <span class="hash-char-count" id="defectCharCount">0/12</span>
            </div>
          </div>
          <div class="form-group">
            <label>Defect Severity Level</label>
            <select id="defectSeverity">
              <option value="Minor">Minor — Cosmetic / Non-functional</option>
              <option value="Major">Major — Performance Degradation</option>
              <option value="Critical">Critical — Complete System Failure</option>
            </select>
          </div>
          <div class="form-group">
            <label>Technical Error Narrative</label>
            <textarea id="defectNotes" placeholder="Describe observed hardware fault or firmware failure…"></textarea>
          </div>
          <button class="btn btn-danger btn-full" onclick="submitDefect()">
            ⚠ &nbsp;RECORD DEFECT &amp; APPLY SUPPLIER PENALTY
          </button>

          <div class="divider"></div>

          <div class="panel-hd" style="margin-bottom:0.75rem;">
            <div class="panel-title" style="font-size:0.68rem;">Recent Defect Log (Session)</div>
          </div>
          <div id="defectLog" style="font-family:var(--font-mono);font-size:0.75rem;color:var(--txt-muted);max-height:180px;overflow-y:auto;display:flex;flex-direction:column;gap:0.4rem;">
            <span>No defects logged in this session.</span>
          </div>
        </div>

      </div>
    </div>

    <!-- ═══════════════════════════════════════════
         OWNER SCREEN
    ═══════════════════════════════════════════ -->
    <div id="ownerScreen" class="screen">

      <!-- Top-line financials -->
      <div class="metrics-strip" style="grid-template-columns:repeat(4,1fr); margin-bottom: 1.75rem;">
        <div class="metric-card mc-cyan">
          <div class="metric-label">Total Net Revenue</div>
          <div class="metric-value txt-cyan" id="ownerRevenue">Rs. 0</div>
          <div class="metric-sub"><span class="trend-tag trend-up">▲ 8.4%</span> vs. last period</div>
        </div>
        <div class="metric-card mc-mint">
          <div class="metric-label">Arbitrage Shield Rate</div>
          <div class="metric-value txt-mint">100.00%</div>
          <div class="metric-sub">Zero refund exploits detected</div>
        </div>
        <div class="metric-card mc-crimson">
          <div class="metric-label">Flagged Return Attempts</div>
          <div class="metric-value txt-crimson" id="ownerFlagged">0</div>
          <div class="metric-sub">Intercepted by hash engine</div>
        </div>
        <div class="metric-card mc-amber">
          <div class="metric-label">Restricted Vendors</div>
          <div class="metric-value txt-amber" id="ownerRestricted">1</div>
          <div class="metric-sub">Below 85% integrity threshold</div>
        </div>
      </div>

      <div class="grid-aside">

        <!-- Vendor Quality Control Table -->
        <div class="panel">
          <div class="panel-hd">
            <div class="panel-title">Global Vendor Quality Control Ledger</div>
            <span class="panel-badge crimson">LIVE SYNC</span>
          </div>
          <div class="table-wrapper">
            <table>
              <thead>
                <tr>
                  <th>Hash Prefix</th>
                  <th>Manufacturer</th>
                  <th>Units Supplied</th>
                  <th>Defect Rate</th>
                  <th>Integrity Score</th>
                  <th>System Status</th>
                </tr>
              </thead>
              <tbody id="ownerVendorBody"></tbody>
            </table>
          </div>
        </div>

        <!-- Sidebar -->
        <div style="display:flex; flex-direction:column; gap:1.5rem;">

          <!-- Domain Map -->
          <div class="panel">
            <div class="panel-hd">
              <div class="panel-title">Platform Domain Architecture</div>
            </div>
            <div class="domain-map">
              <div class="domain-item d-consumer">
                <div class="domain-icon">👤</div>
                <div>
                  <div class="domain-name">Consumer Domain Layer</div>
                  <div class="domain-desc">Validates purchase proof segments to eliminate duplicate refund arbitrage across all member tiers.</div>
                </div>
              </div>
              <div class="domain-item d-employee">
                <div class="domain-icon">🖥</div>
                <div>
                  <div class="domain-name">Employee Operational Hub</div>
                  <div class="domain-desc">Assigns clerk workspace signatures onto transaction logs and enforces terminal node binding.</div>
                </div>
              </div>
              <div class="domain-item d-business">
                <div class="domain-icon">🏭</div>
                <div>
                  <div class="domain-name">Business Domain Operations</div>
                  <div class="domain-desc">Evaluates hardware defect logs and auto-restricts low-integrity vendor supply chains.</div>
                </div>
              </div>
            </div>
          </div>

          <!-- Hash Log Preview -->
          <div class="panel">
            <div class="panel-hd">
              <div class="panel-title">Flagged Hash Audit Log</div>
              <span class="panel-badge crimson" id="flaggedBadge">0 Events</span>
            </div>
            <div id="ownerFlagLog" style="font-family:var(--font-mono);font-size:0.75rem;color:var(--txt-muted);display:flex;flex-direction:column;gap:0.5rem;">
              <span>No flagged events on record.</span>
            </div>
          </div>

        </div>
      </div>
    </div>

  </main>

  <!-- ── FOOTER ────────────────────────────────────────────────────── -->
  <footer>
    <span>SENTINEL SAS ENTERPRISE FRAMEWORK © 2026 &nbsp;·&nbsp; AUTHORIZED: M. AHMAD ATIF &nbsp;·&nbsp; IMSC UNIVERSITY BS-AI S4</span>
    <span id="footerClock" style="font-size:0.68rem;"></span>
  </footer>

  <!-- ── FIRST-LOGIN MODAL ──────────────────────────────────────────── -->
  <div class="modal-backdrop" id="firstLoginModal">
    <div class="modal-box">
      <div class="modal-warn-band">
        <div class="modal-warn-icon">⚠</div>
        <div class="modal-warn-text">FIRST-LOGIN NODE VERIFICATION REQUIRED</div>
      </div>
      <div class="modal-title">Terminal Workspace Authorization</div>
      <div class="modal-body">
        This terminal record marks your initial authorization footprint on this physical workstation node.
        Confirm the assigned hardware node below to bind your operator credentials. This action is logged
        immutably against your employee profile.
      </div>
      <div class="form-group" style="margin-bottom:1.5rem;">
        <label>Assigned Terminal Node Signature</label>
        <div class="node-display">
          <div class="node-dot"></div>
          <span id="modalNodeID">NODE-WORKSTATION-X99 · SLOT-A</span>
        </div>
      </div>
      <div class="form-group" style="margin-bottom:1.5rem;">
        <label>Biometric Confirmation Code</label>
        <input type="password" id="modalConfirmPass" placeholder="Re-enter biometric key to authorize">
      </div>
      <div style="display:flex;gap:.75rem;">
        <button class="btn btn-warning btn-full" onclick="authorizeNode()">⬡ &nbsp;AUTHORIZE NODE WORKSPACE</button>
        <button class="btn btn-secondary" onclick="closeModal()" style="white-space:nowrap;">Dismiss</button>
      </div>
    </div>
  </div>

  <!-- ══════════════════════════════════════════════════════════════════
       JAVASCRIPT ENGINE
  ══════════════════════════════════════════════════════════════════ -->
  <script>
  'use strict';

  // ─── LOCAL STATE ─────────────────────────────────────────────────────────
  // SUPPLIERS is populated from the DB after login (used for real-time hash deconstruct)
  let SUPPLIERS = [];

  const TYPE_LABELS = {
    "99B1":"Solid-State Storage Units",   "44E2":"Optical Transceiver Arrays",
    "A1B2":"Neural Accelerator Modules",  "1122":"High-Density Memory Modules",
    "AAB0":"Peripheral Interface Controllers", "0099":"SATA Controller Units",
    "00C3":"Fiber Network Components",    "0000":"Standard Inventory Bundle"
  };

  let SESSION = { user: null, role: null, data: null };
  let defectLogEntries = [];

  // ─── API HELPER ──────────────────────────────────────────────────────────
  // All communication with api.php uses JSON POST
  async function api(action, data = {}) {
    try {
      const res = await fetch('api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'same-origin',
        body: JSON.stringify({ action, ...data })
      });
      if (!res.ok) {
        return { success: false, message: `Server error ${res.status}` };
      }
      return await res.json();
    } catch (err) {
      return { success: false, message: 'Network error: ' + err.message };
    }
  }

  // ─── AUTH ─────────────────────────────────────────────────────────────────
  async function handleLogin() {
    const username = document.getElementById('authUsername').value.trim();
    const password = document.getElementById('authPassword').value;
    const role     = document.getElementById('authRole').value;
    const errEl    = document.getElementById('authError');

    errEl.classList.remove('show');

    if (!username) {
      errEl.textContent = '⚠ Identity Handle cannot be empty.';
      errEl.classList.add('show');
      return;
    }
    if (!password) {
      errEl.textContent = '⚠ Biometric Key Mapping is required.';
      errEl.classList.add('show');
      return;
    }

    // Disable button to prevent double-submit
    const btn = document.querySelector('#authScreen .btn-primary');
    btn.disabled = true;
    btn.textContent = '⬡  VERIFYING…';

    const result = await api('login', { username, password, role });

    btn.disabled = false;
    btn.innerHTML = '⬡ &nbsp;VERIFY CREDENTIALS';

    if (!result.success) {
      errEl.textContent = `⚠ ${result.message}`;
      errEl.classList.add('show');
      return;
    }

    SESSION.user = username;
    SESSION.role = result.data.role;
    SESSION.data = result.data;

    // Pre-load suppliers into local cache for real-time hash deconstruct
    const supResult = await api('get_suppliers');
    if (supResult.success) SUPPLIERS = supResult.data;

    // Update header
    const dot = document.getElementById('roleDot');
    document.getElementById('userLabel').textContent = `${username} (${SESSION.role})`;
    document.getElementById('userBadge').classList.add('authenticated');
    document.getElementById('logoutBtn').style.display = 'block';
    dot.className = 'role-dot active-' + SESSION.role.toLowerCase();

    hideAllScreens();

    if (SESSION.role === 'Customer') {
      renderCustomer(result.data);
      showScreen('customerScreen');
      toast('info', 'Session Authorized', `Welcome back, ${username}. Loaded Consumer Domain.`);

    } else if (SESSION.role === 'Employee') {
      renderEmployee(username, result.data);
      showScreen('employeeScreen');
      openModal();
      toast('info', 'Operational Hub Active', `Workspace Agent ${username} — session started.`);

    } else if (SESSION.role === 'Owner') {
      await renderOwner();
      showScreen('ownerScreen');
      toast('info', 'Admin Core Loaded', `Administrative oversight session started.`);
    }
  }

  async function handleLogout() {
    await api('logout');
    SESSION = { user: null, role: null, data: null };
    defectLogEntries = [];
    SUPPLIERS = [];
    document.getElementById('userLabel').textContent = 'Awaiting Authentication';
    document.getElementById('userBadge').classList.remove('authenticated');
    document.getElementById('logoutBtn').style.display = 'none';
    document.getElementById('roleDot').className = 'role-dot';
    document.getElementById('authUsername').value = '';
    document.getElementById('authPassword').value = '';
    document.getElementById('authError').classList.remove('show');
    hideAllScreens();
    showScreen('authScreen');
    toast('warning', 'Session Terminated', 'All domain access has been revoked.');
  }

  function hideAllScreens() {
    document.querySelectorAll('.screen').forEach(s => s.classList.remove('active'));
  }
  function showScreen(id) {
    document.getElementById(id).classList.add('active');
  }

  // ─── CUSTOMER RENDER ──────────────────────────────────────────────────────
  function renderCustomer(c) {
    const tierMap = {
      Gold:   { cls: 'tier-gold',   emblem: '★' },
      Silver: { cls: 'tier-silver', emblem: '◈' },
      Basic:  { cls: 'tier-basic',  emblem: '◇' }
    };
    const t = tierMap[c.tier] || tierMap.Basic;

    const card = document.getElementById('tierCard');
    card.className = `tier-card ${t.cls}`;
    document.getElementById('custTierName').textContent  = c.tier + ' Privilege';
    document.getElementById('custTierDisc').textContent  = c.discount + '%';
    document.getElementById('tierEmblem').textContent    = t.emblem;

    document.getElementById('custBalance').textContent   = `Rs. ${parseFloat(c.balance).toLocaleString()}`;
    document.getElementById('custSpent').textContent     = `Total spent: Rs. ${parseFloat(c.total_spent).toLocaleString()}`;
    document.getElementById('custMemberStatus').textContent = c.status;
    document.getElementById('custMemberSub').textContent = `Renewal: ${formatDate(c.billing_date)}`;
    document.getElementById('custBillingDate').textContent  = formatDate(c.billing_date);

    // Billing countdown
    const daysLeft = Math.ceil((new Date(c.billing_date) - new Date()) / 86400000);
    const daysEl   = document.getElementById('custBillingDays');
    daysEl.className = 'billing-days';
    if (daysLeft <= 0) {
      daysEl.textContent = 'OVERDUE'; daysEl.classList.add('urgent');
    } else if (daysLeft <= 7) {
      daysEl.textContent = `${daysLeft} day${daysLeft === 1 ? '' : 's'} remaining`; daysEl.classList.add('urgent');
    } else if (daysLeft <= 21) {
      daysEl.textContent = `${daysLeft} days remaining`; daysEl.classList.add('soon');
    } else {
      daysEl.textContent = `${daysLeft} days remaining`; daysEl.classList.add('ok');
    }

    // Purchase history table
    const tbody = document.getElementById('custHistoryBody');
    tbody.innerHTML = (c.history || []).map(h => `
      <tr>
        <td><span class="mono txt-cyan">${h.asset_hash}</span></td>
        <td>${h.item_description}</td>
        <td><span class="mono txt-muted">${h.transaction_ref}</span></td>
        <td>${h.purchase_date}</td>
        <td class="mono fw-7">Rs. ${parseFloat(h.net_amount).toLocaleString()}</td>
        <td><span class="badge b-mint"><span class="badge-dot" style="background:var(--mint)"></span>Sold</span></td>
      </tr>
    `).join('');
    document.getElementById('custHistoryCount').textContent = (c.history?.length || 0) + ' Records';
  }

  // ─── EMPLOYEE RENDER ──────────────────────────────────────────────────────
  function renderEmployee(username, e) {
    document.getElementById('empName').textContent     = `Workspace Agent: ${username}`;
    document.getElementById('empRole').textContent     = `${e.role_title}  //  Node: ${e.node_id}`;
    document.getElementById('empTxCount').textContent  = e.tx_count;
    document.getElementById('empDefectCount').textContent = e.defect_count;
    document.getElementById('modalNodeID').textContent = `${e.node_id} · SLOT-A`;
    document.getElementById('defectLog').innerHTML     = '<span>No defects logged in this session.</span>';
    defectLogEntries = [];
    updateHashDeconstruct();
  }

  // ─── HASH DECONSTRUCTOR  (real-time, uses SUPPLIERS local cache) ──────────
  function updateHashDeconstruct() {
    const raw    = document.getElementById('empHashInput').value.toUpperCase();
    const padded = raw.padEnd(16, '·');
    document.getElementById('hashCharCount').textContent = `${raw.length}/16`;

    const sup = padded.substring(0, 4);
    const typ = padded.substring(4, 8);
    const ser = padded.substring(8, 12);
    const mem = padded.substring(12, 16);
    const isValidHex = /^[0-9A-Fa-f]{16}$/.test(raw);

    setChunk('hcSup', sup, isValidHex && raw.length >= 4);
    setChunk('hcTyp', typ, isValidHex && raw.length >= 8);
    setChunk('hcSer', ser, isValidHex && raw.length >= 12);
    setChunk('hcMem', mem, isValidHex && raw.length === 16);

    // Lookup from DB-sourced cache
    const supData = SUPPLIERS.find(s => s.hash_prefix === sup.substring(0, 4));
    const typCode = raw.substring(4, 8);
    const typName = TYPE_LABELS[typCode] || (raw.length >= 8 ? 'Custom Item Category' : '—');

    document.getElementById('lblSup').textContent = supData
      ? `${supData.name} (${supData.country})` : (raw.length >= 4 ? 'Unknown Vendor' : '—');
    document.getElementById('lblTyp').textContent = typName;
    document.getElementById('lblSer').textContent = raw.length >= 12
      ? `Batch Allocation #${parseInt(raw.substring(8, 12), 16)}` : '—';
    document.getElementById('lblMem').textContent = raw.length === 16
      ? `Member Suffix ${mem} — ${isValidHex ? 'Valid Hex Structure' : 'Non-Hex Detected'}` : '—';
  }

  function setChunk(id, text, active) {
    const el = document.getElementById(id);
    el.textContent = text;
    el.classList.toggle('valid', active);
    el.classList.toggle('empty', !active);
  }

  function updateDefectCount() {
    const v = document.getElementById('defectHash').value;
    document.getElementById('defectCharCount').textContent = `${v.length}/12`;
  }

  // ─── HASH VALIDATION  (Employee → POST to API) ────────────────────────────
  async function validateHash() {
    const raw     = document.getElementById('empHashInput').value.toUpperCase().trim();
    const alertEl = document.getElementById('hashValidationAlert');
    alertEl.style.display = 'none';

    if (!/^[0-9A-Fa-f]{16}$/.test(raw)) {
      alertEl.style.display = 'block';
      alertEl.textContent   = `⚠ Invalid: hash must be exactly 16 hexadecimal characters. Got: "${raw}"`;
      alertEl.classList.add('show');
      toast('error', 'Validation Failed', `"${raw}" is not a valid 16-char hex key.`);
      return;
    }

    const btn = document.querySelector('#employeeScreen .btn-primary');
    btn.disabled = true;
    btn.textContent = '⬡  VALIDATING…';

    const result = await api('validate_hash', { hash: raw });

    btn.disabled = false;
    btn.innerHTML = '⬡ &nbsp;SUBMIT HASH FOR VALIDATION';

    if (!result.success) {
      toast('error', 'API Error', result.message);
      return;
    }

    // Update tx counter (server already incremented; reflect in UI)
    const curr = parseInt(document.getElementById('empTxCount').textContent) || 0;
    document.getElementById('empTxCount').textContent = curr + 1;

    if (result.data?.restricted) {
      toast('warning', 'Vendor Alert',
        `Vendor ${result.data.vendor_name || 'Unknown'} is RESTRICTED. Hash logged for review.`);
    } else if (result.data?.duplicate) {
      toast('warning', 'Duplicate Detected',
        `Hash ${raw} already exists in purchase ledger. Flagged for audit.`);
    } else {
      toast('success', 'Hash Validated',
        `Asset ${raw} authenticated. ${result.data?.vendor_name || 'No vendor match'} — structure intact.`);
    }
  }

  // ─── DEFECT SUBMISSION  (Employee → POST to API) ──────────────────────────
  async function submitDefect() {
    const hash = document.getElementById('defectHash').value.toUpperCase().trim();
    const sev  = document.getElementById('defectSeverity').value;
    const note = document.getElementById('defectNotes').value.trim();

    if (!/^[0-9A-Fa-f]{12}$/.test(hash)) {
      toast('error', 'Defect Rejected', 'Unit hash must be exactly 12 valid hex characters.');
      return;
    }

    const btn = document.querySelector('#employeeScreen .btn-danger');
    btn.disabled    = true;
    btn.textContent = '⚠  SUBMITTING…';

    const result = await api('submit_defect', { hash, severity: sev, notes: note });

    btn.disabled    = false;
    btn.innerHTML   = '⚠ &nbsp;RECORD DEFECT &amp; APPLY SUPPLIER PENALTY';

    if (!result.success) {
      toast('error', 'Submission Failed', result.message);
      return;
    }

    // Update local supplier cache with fresh data from DB
    if (result.data?.supplier) {
      const idx = SUPPLIERS.findIndex(s => s.hash_prefix === hash.substring(0, 4));
      if (idx >= 0) SUPPLIERS[idx] = result.data.supplier;
      else SUPPLIERS.push(result.data.supplier);
    }

    const ts         = new Date().toLocaleTimeString('en-PK', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
    const vendorName = result.data?.supplier?.name || 'Unknown';

    defectLogEntries.unshift({ hash, sev, note: note || 'No notes.', ts, vendor: vendorName });

    const logEl = document.getElementById('defectLog');
    logEl.innerHTML = defectLogEntries.slice(0, 8).map(e => `
      <div style="background:var(--bg-elevated);border:1px solid var(--border);border-radius:4px;padding:.5rem .75rem;">
        <span class="txt-crimson">[${e.ts}]</span>
        <span class="txt-amber"> ${e.hash}</span>
        <span style="color:var(--txt);"> · ${e.sev}</span>
        <span class="txt-muted"> · ${e.vendor}</span>
      </div>
    `).join('');

    document.getElementById('empDefectCount').textContent =
      parseInt(document.getElementById('empDefectCount').textContent) + 1;

    document.getElementById('defectHash').value  = '';
    document.getElementById('defectNotes').value = '';
    document.getElementById('defectCharCount').textContent = '0/12';

    toast('success', 'Defect Recorded', `Hash ${hash} committed. Severity: ${sev}. Supplier score recalculated.`);

    if (result.data?.newly_restricted) {
      setTimeout(() => toast('warning', 'Vendor Restricted',
        `${vendorName} integrity dropped below 85%. Status auto-set to RESTRICTED.`), 800);
    }
  }

  // ─── OWNER RENDER ─────────────────────────────────────────────────────────
  async function renderOwner() {
    const result = await api('get_owner_stats');
    if (!result.success) {
      toast('error', 'Load Failed', result.message);
      return;
    }
    const stats = result.data;
    animateCount('ownerRevenue',    0, stats.revenue,    1200, v => `Rs. ${Math.round(v).toLocaleString()}`);
    animateCount('ownerFlagged',    0, stats.flagged,    800);
    document.getElementById('ownerRestricted').textContent = stats.restricted;

    await renderVendorTable();
    await renderFlagLog();
  }

  async function renderVendorTable() {
    const result = await api('get_suppliers');
    if (!result.success) return;

    SUPPLIERS = result.data;
    const tbody  = document.getElementById('ownerVendorBody');
    const sorted = [...SUPPLIERS].sort((a, b) => a.integrity_score - b.integrity_score);

    tbody.innerHTML = sorted.map(s => {
      const rate       = s.units_supplied > 0
        ? ((s.defect_count / s.units_supplied) * 100).toFixed(2) : '0.00';
      const scoreColor = s.integrity_score >= 95 ? 'var(--mint)' : s.integrity_score >= 85 ? 'var(--amber)' : 'var(--crimson)';
      const badge      = s.status === 'Active'
        ? `<span class="badge b-mint"><span class="badge-dot" style="background:var(--mint)"></span>Active</span>`
        : `<span class="badge b-crimson"><span class="badge-dot" style="background:var(--crimson)"></span>Restricted</span>`;
      return `
        <tr>
          <td><span class="mono txt-crimson fw-7">${s.hash_prefix}</span></td>
          <td>${s.name}<br><span style="font-size:0.72rem;color:var(--txt-muted);">${s.country}</span></td>
          <td class="mono">${s.units_supplied}</td>
          <td class="mono" style="color:${s.defect_count > 0 ? 'var(--amber)' : 'var(--mint)'}">${rate}%</td>
          <td>
            <div class="score-bar">
              <div class="score-track">
                <div class="score-fill" style="width:${s.integrity_score}%;background:${scoreColor}"></div>
              </div>
              <span class="score-num" style="color:${scoreColor}">${parseFloat(s.integrity_score).toFixed(2)}%</span>
            </div>
          </td>
          <td>${badge}</td>
        </tr>`;
    }).join('');
  }

  async function renderFlagLog() {
    const result = await api('get_flagged_hashes');
    if (!result.success) return;

    const flagged = result.data;
    const el      = document.getElementById('ownerFlagLog');

    if (flagged.length === 0) {
      el.innerHTML = '<span>No flagged events on record.</span>';
      document.getElementById('flaggedBadge').textContent = '0 Events';
      return;
    }

    el.innerHTML = flagged.map(f => `
      <div style="background:var(--crimson-bg);border:1px solid rgba(255,51,85,0.15);border-radius:4px;padding:.6rem .8rem;">
        <div class="mono txt-crimson" style="font-size:.72rem;font-weight:700;margin-bottom:3px;">${f.asset_hash}</div>
        <div style="font-size:.72rem;color:var(--txt-muted);line-height:1.4;">${f.reason}</div>
        <div class="mono" style="font-size:.65rem;color:var(--txt-dim);margin-top:3px;">${f.flagged_at}</div>
      </div>
    `).join('');

    document.getElementById('flaggedBadge').textContent = flagged.length + ' Events';
  }

  // ─── MODAL ────────────────────────────────────────────────────────────────
  function openModal() {
    document.getElementById('firstLoginModal').classList.add('open');
  }

  function closeModal() {
    document.getElementById('firstLoginModal').classList.remove('open');
    toast('warning', 'Node Auth Dismissed',
      'Terminal verification was skipped. Please authorize at next login.');
  }

  async function authorizeNode() {
    const pass = document.getElementById('modalConfirmPass').value;
    if (!pass) {
      toast('error', 'Authorization Failed', 'Biometric confirmation code is required.');
      return;
    }

    const result = await api('authorize_node', { password: pass });
    document.getElementById('firstLoginModal').classList.remove('open');
    document.getElementById('modalConfirmPass').value = '';

    if (result.success) {
      toast('success', 'Node Authorized',
        `Workspace ${result.data?.node_id || SESSION.data?.node_id || 'NODE-WORKSTATION-X99'} successfully bound to operator profile.`);
    } else {
      toast('warning', 'Authorization Warning', result.message);
    }
  }

  // ─── TOAST ────────────────────────────────────────────────────────────────
  const ICONS = { success: '✓', error: '✕', warning: '⚠', info: 'ℹ' };

  function toast(type, title, msg) {
    const container = document.getElementById('toastContainer');
    const el        = document.createElement('div');
    el.className    = `toast t-${type}`;
    el.innerHTML    = `
      <div class="toast-icon">${ICONS[type]}</div>
      <div class="toast-body">
        <div class="toast-title">${title}</div>
        <div class="toast-msg">${msg}</div>
      </div>
    `;
    container.appendChild(el);
    setTimeout(() => {
      el.classList.add('toast-out');
      setTimeout(() => el.remove(), 350);
    }, 4000);
  }

  // ─── UTILITIES ────────────────────────────────────────────────────────────
  function formatDate(str) {
    const d = new Date(str);
    return d.toLocaleDateString('en-GB', { day: 'numeric', month: 'long', year: 'numeric' });
  }

  function animateCount(id, from, to, dur, formatter) {
    const el    = document.getElementById(id);
    const start = performance.now();
    const fmt   = formatter || (v => Math.round(v));
    requestAnimationFrame(function step(now) {
      const p    = Math.min((now - start) / dur, 1);
      const ease = 1 - Math.pow(1 - p, 3);
      el.textContent = fmt(from + (to - from) * ease);
      if (p < 1) requestAnimationFrame(step);
    });
  }

  // ─── LIVE CLOCK ──────────────────────────────────────────────────────────
  function updateClock() {
    document.getElementById('footerClock').textContent =
      new Date().toLocaleString('en-PK', { dateStyle: 'medium', timeStyle: 'medium' });
  }
  updateClock();
  setInterval(updateClock, 1000);

  // ─── INIT ─────────────────────────────────────────────────────────────────
  // Pre-fill the login hint paragraph with real credentials
  document.addEventListener('DOMContentLoaded', () => {
    const hint = document.querySelector('#authScreen p[style]');
    if (hint) {
      hint.innerHTML =
        'Customers: <b>areeb / zainab / hamza</b> — pass: <code>password123</code><br>' +
        'Employees: <b>raza / sara / umar</b> — pass: <code>employee123</code><br>' +
        'Owner: <b>owner</b> — pass: <code>owner@secure1</code>';
    }
  });

  updateHashDeconstruct();
  </script>
</body>
</html>
