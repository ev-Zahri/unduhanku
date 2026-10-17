<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Console — Unduhanku</title>
    <meta name="robots" content="noindex, nofollow">
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        :root {
            --bg-base: #09090b;
            --bg-surface: #121215;
            --bg-surface-elevated: #18181c;
            --bg-input: #121215;
            
            --border-subtle: #222227;
            --border-medium: #2e2e36;
            --border-focus: #52525b;

            --text-primary: #f4f4f5;
            --text-secondary: #a1a1aa;
            --text-muted: #71717a;

            --btn-primary-bg: #ffffff;
            --btn-primary-text: #09090b;
            --btn-primary-hover: #e4e4e7;

            --accent: #6366f1;
            --accent-glow: rgba(99, 102, 241, 0.15);
            --success: #10b981;
            --danger: #ef4444;
            --danger-bg: rgba(239, 68, 68, 0.1);

            --radius-sm: 6px;
            --radius-md: 10px;
            --radius-lg: 14px;

            --font-sans: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            --font-mono: 'JetBrains Mono', monospace;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: var(--font-sans);
            background-color: var(--bg-base);
            color: var(--text-primary);
            min-height: 100vh;
            line-height: 1.5;
            -webkit-font-smoothing: antialiased;
        }

        a {
            color: inherit;
            text-decoration: none;
        }

        /* Container Layout */
        .admin-layout {
            max-width: 1360px;
            width: 100%;
            margin: 0 auto;
            padding: 28px 32px 80px;
        }

        /* Header / Navbar */
        .admin-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-bottom: 20px;
            margin-bottom: 32px;
            border-bottom: 1px solid var(--border-subtle);
        }

        .header-brand {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .brand-title {
            font-size: 18px;
            font-weight: 600;
            letter-spacing: -0.02em;
        }

        .brand-badge {
            font-size: 11px;
            font-weight: 600;
            background: var(--bg-surface-elevated);
            color: var(--accent);
            border: 1px solid var(--border-medium);
            padding: 2px 8px;
            border-radius: var(--radius-sm);
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        /* Nav Tabs Switcher */
        .header-nav {
            display: flex;
            align-items: center;
            gap: 6px;
            background: var(--bg-surface);
            padding: 4px;
            border-radius: var(--radius-md);
            border: 1px solid var(--border-subtle);
        }

        .nav-tab-btn {
            background: transparent;
            border: none;
            outline: none;
            color: var(--text-secondary);
            font-size: 13px;
            font-weight: 500;
            padding: 8px 18px;
            border-radius: var(--radius-sm);
            cursor: pointer;
            transition: all 0.15s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .nav-tab-btn:hover {
            color: var(--text-primary);
        }

        .nav-tab-btn.active {
            background: var(--bg-surface-elevated);
            color: var(--text-primary);
            border: 1px solid var(--border-medium);
        }

        .header-actions {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .btn-minimal {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 7px 14px;
            background: transparent;
            color: var(--text-secondary);
            border: 1px solid var(--border-subtle);
            border-radius: var(--radius-sm);
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.15s ease;
        }

        .btn-minimal:hover {
            color: var(--text-primary);
            border-color: var(--border-medium);
            background: var(--bg-surface);
        }

        .btn-danger-subtle {
            color: var(--danger);
            border-color: rgba(239, 68, 68, 0.2);
        }

        .btn-danger-subtle:hover {
            background: var(--danger-bg);
            border-color: var(--danger);
            color: #fff;
        }

        /* KPI Cards Grid */
        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
            margin-bottom: 28px;
        }

        .kpi-card {
            background: var(--bg-surface);
            border: 1px solid var(--border-subtle);
            border-radius: var(--radius-md);
            padding: 20px;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .kpi-title {
            font-size: 12px;
            font-weight: 500;
            color: var(--text-muted);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .kpi-value {
            font-size: 26px;
            font-weight: 600;
            letter-spacing: -0.02em;
            color: var(--text-primary);
        }

        .kpi-icon {
            font-size: 14px;
            color: var(--text-secondary);
        }

        /* Content Card */
        .admin-card {
            background: var(--bg-surface);
            border: 1px solid var(--border-subtle);
            border-radius: var(--radius-lg);
            padding: 24px;
            margin-bottom: 28px;
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .card-title {
            font-size: 16px;
            font-weight: 600;
            letter-spacing: -0.01em;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .card-subtitle {
            font-size: 13px;
            color: var(--text-muted);
            margin-top: 3px;
        }

        /* Segmented Filter (Mingguan, Bulanan, Tahunan) */
        .filter-group {
            display: inline-flex;
            background: var(--bg-surface-elevated);
            border: 1px solid var(--border-subtle);
            border-radius: var(--radius-sm);
            padding: 3px;
            gap: 2px;
        }

        .btn-filter {
            background: transparent;
            border: none;
            outline: none;
            color: var(--text-secondary);
            font-size: 12px;
            font-weight: 500;
            padding: 6px 14px;
            border-radius: var(--radius-sm);
            cursor: pointer;
            transition: all 0.15s ease;
        }

        .btn-filter.active {
            background: var(--border-medium);
            color: var(--text-primary);
            font-weight: 600;
        }

        .chart-wrapper {
            position: relative;
            width: 100%;
            height: 300px;
        }

        /* Table Styling */
        .table-responsive {
            width: 100%;
            overflow-x: auto;
            border: 1px solid var(--border-subtle);
            border-radius: var(--radius-md);
            background: var(--bg-surface-elevated);
        }

        .admin-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
            text-align: left;
        }

        .admin-table th {
            padding: 12px 16px;
            background: var(--bg-surface);
            color: var(--text-secondary);
            font-weight: 500;
            font-size: 12px;
            border-bottom: 1px solid var(--border-subtle);
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .admin-table td {
            padding: 14px 16px;
            border-bottom: 1px solid var(--border-subtle);
            color: var(--text-primary);
            vertical-align: middle;
        }

        .admin-table tr:last-child td {
            border-bottom: none;
        }

        .admin-table tr:hover td {
            background: rgba(255, 255, 255, 0.02);
        }

        .ip-badge {
            font-family: var(--font-mono);
            font-size: 12px;
            background: var(--bg-surface);
            border: 1px solid var(--border-subtle);
            padding: 3px 8px;
            border-radius: var(--radius-sm);
            color: var(--text-secondary);
            display: inline-block;
        }

        .link-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 12px;
            font-weight: 500;
            color: var(--accent);
            background: var(--accent-glow);
            border: 1px solid rgba(99, 102, 241, 0.25);
            padding: 4px 10px;
            border-radius: var(--radius-sm);
            transition: all 0.15s ease;
            max-width: 280px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .link-badge:hover {
            border-color: var(--accent);
            color: #fff;
        }

        .platform-badge {
            font-size: 11px;
            font-weight: 600;
            padding: 3px 8px;
            border-radius: var(--radius-sm);
            background: var(--bg-surface);
            border: 1px solid var(--border-subtle);
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        /* Pagination Bar */
        .pagination-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 18px;
            font-size: 13px;
            color: var(--text-muted);
        }

        .pagination-actions {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn-page {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            background: var(--bg-surface-elevated);
            border: 1px solid var(--border-subtle);
            border-radius: var(--radius-sm);
            color: var(--text-primary);
            font-size: 12px;
            cursor: pointer;
            transition: all 0.15s ease;
        }

        .btn-page:hover:not(:disabled) {
            border-color: var(--border-medium);
            background: var(--bg-surface);
        }

        .btn-page:disabled {
            opacity: 0.4;
            cursor: not-allowed;
        }

        /* Monitor DB Page */
        .db-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
        }

        .stat-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
            margin-bottom: 24px;
        }

        .stat-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 14px;
            background: var(--bg-surface-elevated);
            border: 1px solid var(--border-subtle);
            border-radius: var(--radius-sm);
            font-size: 13px;
        }

        .stat-label {
            color: var(--text-muted);
        }

        .stat-val {
            font-family: var(--font-mono);
            font-weight: 500;
            color: var(--text-primary);
        }

        .form-group {
            margin-bottom: 18px;
            width: 100%;
        }

        .form-label {
            display: block;
            font-size: 13px;
            font-weight: 500;
            color: var(--text-secondary);
            margin-bottom: 8px;
        }

        .form-row {
            display: flex;
            gap: 14px;
            width: 100%;
        }

        .form-input {
            width: 100%;
            display: block;
            box-sizing: border-box;
            background: var(--bg-surface-elevated);
            border: 1px solid var(--border-subtle);
            border-radius: var(--radius-sm);
            padding: 12px 16px;
            height: 46px;
            color: var(--text-primary);
            font-family: inherit;
            font-size: 14px;
            outline: none;
            transition: all 0.15s ease;
        }

        .form-input:focus {
            border-color: var(--border-focus);
            background: var(--bg-surface);
            box-shadow: 0 0 0 1px var(--border-focus);
        }

        .btn-action-primary {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            background: var(--btn-primary-bg);
            color: var(--btn-primary-text);
            border: none;
            border-radius: var(--radius-sm);
            padding: 9px 18px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: opacity 0.15s ease;
            white-space: nowrap;
        }

        .btn-action-primary:hover {
            opacity: 0.9;
        }

        .btn-action-danger {
            background: var(--danger);
            color: #fff;
        }

        .btn-action-danger:hover {
            opacity: 0.9;
        }

        /* Log Files Table */
        .btn-dl-log {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 5px 12px;
            background: var(--bg-surface);
            border: 1px solid var(--border-subtle);
            border-radius: var(--radius-sm);
            color: var(--text-primary);
            font-size: 12px;
            font-weight: 500;
            transition: all 0.15s ease;
        }

        .btn-dl-log:hover {
            background: var(--btn-primary-bg);
            color: var(--btn-primary-text);
            border-color: var(--btn-primary-bg);
        }

        /* Login Modal / Card */
        .login-wrap {
            min-height: 80vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .login-card {
            background: var(--bg-surface);
            border: 1px solid var(--border-subtle);
            border-radius: var(--radius-lg);
            padding: 42px 38px;
            width: 100%;
            max-width: 480px;
            text-align: center;
            box-shadow: 0 12px 36px rgba(0, 0, 0, 0.45);
        }

        .login-icon {
            width: 52px;
            height: 52px;
            background: var(--bg-surface-elevated);
            border: 1px solid var(--border-medium);
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            color: var(--text-primary);
            margin-bottom: 20px;
        }

        .login-title {
            font-size: 22px;
            font-weight: 600;
            letter-spacing: -0.02em;
            margin-bottom: 8px;
        }

        .login-desc {
            font-size: 13px;
            color: var(--text-muted);
            margin-bottom: 26px;
            line-height: 1.5;
        }

        .login-form {
            width: 100%;
            text-align: left;
            display: flex;
            flex-direction: column;
            gap: 18px;
        }

        .alert-box {
            padding: 10px 14px;
            border-radius: var(--radius-sm);
            font-size: 13px;
            margin-bottom: 16px;
            display: none;
        }

        .alert-error {
            background: var(--danger-bg);
            color: #fca5a5;
            border: 1px solid rgba(239, 68, 68, 0.3);
        }

        .alert-success {
            background: rgba(16, 185, 129, 0.1);
            color: #6ee7b7;
            border: 1px solid rgba(16, 185, 129, 0.3);
        }

        /* Toast Container */
        .toast-msg {
            position: fixed;
            bottom: 24px;
            right: 24px;
            padding: 12px 18px;
            background: var(--bg-surface-elevated);
            border: 1px solid var(--border-medium);
            border-radius: var(--radius-sm);
            color: var(--text-primary);
            font-size: 13px;
            box-shadow: 0 4px 14px rgba(0,0,0,0.4);
            z-index: 9999;
            transition: all 0.25s ease;
        }

        /* Responsive */
        @media screen and (max-width: 1024px) {
            .kpi-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            .db-grid {
                grid-template-columns: 1fr;
            }
        }

        @media screen and (max-width: 768px) {
            .admin-layout {
                padding: 20px 16px 60px;
            }
            .admin-header {
                flex-direction: column;
                align-items: stretch;
                gap: 14px;
            }
            .header-nav {
                justify-content: center;
            }
            .header-actions {
                justify-content: flex-end;
            }
            .kpi-grid {
                grid-template-columns: 1fr;
            }
            .form-row {
                flex-direction: column;
            }
        }

        /* Modal Styles */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background: rgba(0, 0, 0, 0.75);
            backdrop-filter: blur(4px);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 10000;
            padding: 20px;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.2s ease;
        }

        .modal-overlay.active {
            opacity: 1;
            pointer-events: auto;
        }

        .modal-dialog {
            background: var(--bg-surface);
            border: 1px solid var(--border-medium);
            border-radius: var(--radius-lg);
            width: 100%;
            max-width: 620px;
            max-height: 90vh;
            display: flex;
            flex-direction: column;
            box-shadow: 0 20px 48px rgba(0,0,0,0.6);
            overflow: hidden;
            transform: scale(0.97);
            transition: transform 0.2s ease;
        }

        .modal-dialog.modal-dialog-large {
            max-width: 920px;
        }

        .modal-overlay.active .modal-dialog {
            transform: scale(1);
        }

        .modal-header {
            padding: 18px 24px;
            border-bottom: 1px solid var(--border-subtle);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .modal-title {
            font-size: 16px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .modal-close {
            background: transparent;
            border: none;
            color: var(--text-muted);
            font-size: 16px;
            cursor: pointer;
            padding: 4px;
            border-radius: var(--radius-sm);
            transition: color 0.15s;
        }

        .modal-close:hover {
            color: var(--text-primary);
        }

        .modal-body {
            padding: 24px;
            overflow-y: auto;
            flex: 1;
        }

        .modal-footer {
            padding: 16px 24px;
            border-top: 1px solid var(--border-subtle);
            display: flex;
            justify-content: flex-end;
            gap: 12px;
            background: var(--bg-surface-elevated);
        }

        /* Ad Toggle Buttons */
        .btn-toggle-link {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 4px 10px;
            border-radius: var(--radius-sm);
            font-size: 11px;
            font-weight: 600;
            cursor: pointer;
            border: 1px solid var(--border-medium);
            background: var(--bg-surface-elevated);
            color: var(--text-secondary);
            transition: all 0.15s ease;
            white-space: nowrap;
        }

        .btn-toggle-link:hover {
            border-color: var(--text-secondary);
            color: var(--text-primary);
        }

        .btn-toggle-link.active-link {
            background: rgba(16, 185, 129, 0.15);
            border-color: var(--success);
            color: var(--success);
        }

        /* Ad Badges */
        .badge-issue {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 3px 8px;
            border-radius: var(--radius-sm);
            font-size: 11px;
            font-weight: 600;
            white-space: nowrap;
        }

        .badge-issue-safe {
            background: rgba(16, 185, 129, 0.12);
            color: #34d399;
            border: 1px solid rgba(16, 185, 129, 0.3);
        }

        .badge-issue-warn {
            background: rgba(245, 158, 11, 0.12);
            color: #fbbf24;
            border: 1px solid rgba(245, 158, 11, 0.3);
        }

        .badge-issue-danger {
            background: var(--danger-bg);
            color: #fca5a5;
            border: 1px solid rgba(239, 68, 68, 0.3);
        }

        .link-short-wrap {
            display: flex;
            flex-direction: column;
            gap: 6px;
            align-items: flex-start;
        }

        .link-url-text {
            font-family: var(--font-mono);
            font-size: 11px;
            color: var(--text-secondary);
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .link-url-text:hover {
            color: var(--accent);
            text-decoration: underline;
        }

        .preview-box {
            background: var(--bg-base);
            border: 1px solid var(--border-subtle);
            border-radius: var(--radius-md);
            padding: 14px;
            margin-bottom: 20px;
            max-height: 220px;
            overflow-x: auto;
            overflow-y: auto;
        }
    </style>
</head>
<body>

    <div class="admin-layout">
        <?php if (!$isLoggedIn): ?>
            <!-- ======================================================= -->
            <!-- 1. LOGIN VIEW (JIKA BELUM LOGIN)                       -->
            <!-- ======================================================= -->
            <div class="login-wrap">
                <div class="login-card">
                    <div class="login-icon">
                        <i class="fa-solid fa-shield-halved"></i>
                    </div>
                    <h1 class="login-title">Admin Console</h1>
                    <p class="login-desc">Masukkan kredensial otorisasi untuk mengakses Dashboard Analitics & Monitor DB.</p>

                    <div id="login-alert" class="alert-box alert-error"></div>

                    <form id="login-form" class="login-form">
                        <div class="form-group">
                            <label class="form-label" for="login-username">Username Administrator</label>
                            <input type="text" id="login-username" class="form-input" placeholder="Masukan Username" required autocomplete="username">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="login-password">Password</label>
                            <input type="password" id="login-password" class="form-input" placeholder="Masukan Password" required autocomplete="current-password">
                        </div>
                        <button type="submit" id="btn-submit-login" class="btn-action-primary" style="width:100%; height:46px; margin-top:10px; font-size:14px;">
                            <i class="fa-solid fa-arrow-right-to-bracket"></i>
                            <span>Masuk ke Dashboard</span>
                        </button>
                    </form>
                </div>
            </div>

        <?php else: ?>
            <!-- ======================================================= -->
            <!-- 2. DASHBOARD VIEW (KETIKA SUDAH LOGIN)                 -->
            <!-- ======================================================= -->
            <header class="admin-header">
                <div class="header-brand">
                    <a href="<?php echo htmlspecialchars($adminPath); ?>" class="brand-title">Unduhanku</a>
                    <span class="brand-badge">Admin</span>
                </div>

                <!-- Page Switcher Tabs -->
                <nav class="header-nav">
                    <button class="nav-tab-btn active" id="tab-btn-analytics" data-target="page-analytics">
                        <i class="fa-solid fa-chart-line"></i>
                        <span>Analitics</span>
                    </button>
                    <button class="nav-tab-btn" id="tab-btn-monitor" data-target="page-monitor">
                        <i class="fa-solid fa-database"></i>
                        <span>Monitor DB</span>
                    </button>
                    <button class="nav-tab-btn" id="tab-btn-ads" data-target="page-ads">
                        <i class="fa-solid fa-bullhorn"></i>
                        <span>Advertisment</span>
                    </button>
                </nav>

                <div class="header-actions">
                    <a href="/" target="_blank" class="btn-minimal" title="Lihat Website Publik">
                        <i class="fa-solid fa-globe"></i>
                        <span>Web Publik</span>
                    </a>
                    <button id="btn-admin-logout" class="btn-minimal btn-danger-subtle" title="Keluar dari sesi admin">
                        <i class="fa-solid fa-power-off"></i>
                        <span>Logout</span>
                    </button>
                </div>
            </header>

            <!-- ======================================================= -->
            <!-- PAGE 1: ANALITICS                                       -->
            <!-- ======================================================= -->
            <div id="page-analytics" class="page-container">
                <!-- KPI Summary Grid -->
                <section class="kpi-grid">
                    <div class="kpi-card">
                        <div class="kpi-title">
                            <span>TOTAL UNDUHAN</span>
                            <i class="fa-solid fa-cloud-arrow-down kpi-icon"></i>
                        </div>
                        <div class="kpi-value" id="kpi-total-downloads">-</div>
                    </div>
                    <div class="kpi-card">
                        <div class="kpi-title">
                            <span>UNDUHAN HARI INI</span>
                            <i class="fa-solid fa-calendar-day kpi-icon"></i>
                        </div>
                        <div class="kpi-value" id="kpi-today-downloads">-</div>
                    </div>
                    <div class="kpi-card">
                        <div class="kpi-title">
                            <span>BANDWIDTH / UKURAN</span>
                            <i class="fa-solid fa-hard-drive kpi-icon"></i>
                        </div>
                        <div class="kpi-value" id="kpi-total-bandwidth">-</div>
                    </div>
                    <div class="kpi-card">
                        <div class="kpi-title">
                            <span>PLATFORM TERATAS</span>
                            <i class="fa-solid fa-fire kpi-icon"></i>
                        </div>
                        <div class="kpi-value" id="kpi-top-platform">-</div>
                    </div>
                </section>

                <!-- Chart Card with 3 Options: Mingguan, Bulanan, Tahunan -->
                <section class="admin-card">
                    <div class="card-header">
                        <div>
                            <h2 class="card-title">
                                <i class="fa-solid fa-chart-area" style="color:var(--accent);"></i>
                                <span>Aktivitas Pengunduhan</span>
                            </h2>
                            <p class="card-subtitle">Frekuensi unduhan media dari waktu ke waktu</p>
                        </div>
                        <!-- 3 Filter Opsi Grafik -->
                        <div class="filter-group">
                            <button class="btn-filter active" data-range="weekly">Mingguan</button>
                            <button class="btn-filter" data-range="monthly">Bulanan</button>
                            <button class="btn-filter" data-range="yearly">Tahunan</button>
                        </div>
                    </div>
                    <div class="chart-wrapper">
                        <canvas id="analyticsChart"></canvas>
                    </div>
                </section>

                <!-- Download History Table (Pagination Limit 10) -->
                <section class="admin-card">
                    <div class="card-header">
                        <div>
                            <h2 class="card-title">
                                <i class="fa-solid fa-table-list" style="color:var(--text-secondary);"></i>
                                <span>Riwayat Unduhan Klien</span>
                            </h2>
                            <p class="card-subtitle">Log riwayat unduhan pengunjung dengan paginasi limit 10</p>
                        </div>
                        <button id="btn-refresh-history" class="btn-minimal" title="Segarkan Data">
                            <i class="fa-solid fa-arrows-rotate"></i>
                            <span>Refresh</span>
                        </button>
                    </div>

                    <div class="table-responsive">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th style="width:170px;">IP Address</th>
                                    <th>Link Video</th>
                                    <th style="width:150px;">Sumber</th>
                                    <th style="width:130px;">Ukuran</th>
                                    <th style="width:180px;">Waktu</th>
                                </tr>
                            </thead>
                            <tbody id="history-table-body">
                                <tr>
                                    <td colspan="5" style="text-align:center; padding:30px; color:var(--text-muted);">
                                        Memuat data riwayat unduhan...
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination Controls -->
                    <div class="pagination-bar">
                        <div id="pagination-info">Menampilkan halaman 1 dari 1</div>
                        <div class="pagination-actions">
                            <button id="btn-page-prev" class="btn-page" disabled>
                                <i class="fa-solid fa-chevron-left"></i>
                                <span>Sebelumnya</span>
                            </button>
                            <button id="btn-page-next" class="btn-page" disabled>
                                <span>Selanjutnya</span>
                                <i class="fa-solid fa-chevron-right"></i>
                            </button>
                        </div>
                    </div>
                </section>
            </div>

            <!-- ======================================================= -->
            <!-- PAGE 2: MONITOR DB                                      -->
            <!-- ======================================================= -->
            <div id="page-monitor" class="page-container" style="display:none;">
                <div class="db-grid">
                    <!-- Kolom 1: Manajemen Riwayat SQLite -->
                    <section class="admin-card">
                        <div class="card-header">
                            <div>
                                <h2 class="card-title">
                                    <i class="fa-solid fa-database" style="color:var(--accent);"></i>
                                    <span>Pembersihan Database</span>
                                </h2>
                                <p class="card-subtitle">Kelola dan bersihkan riwayat unduhan di SQLite</p>
                            </div>
                        </div>

                        <!-- Status DB Specs -->
                        <div class="stat-list">
                            <div class="stat-row">
                                <span class="stat-label">File Database</span>
                                <span class="stat-val" style="font-size:12px;">storage/history.sqlite</span>
                            </div>
                            <div class="stat-row">
                                <span class="stat-label">Ukuran Database</span>
                                <span class="stat-val" id="db-file-size">-</span>
                            </div>
                            <div class="stat-row">
                                <span class="stat-label">Total Baris Riwayat</span>
                                <span class="stat-val" id="db-total-rows">-</span>
                            </div>
                            <div class="stat-row">
                                <span class="stat-label">Data Terlama</span>
                                <span class="stat-val" id="db-first-record">-</span>
                            </div>
                            <div class="stat-row">
                                <span class="stat-label">Data Terbaru</span>
                                <span class="stat-val" id="db-latest-record">-</span>
                            </div>
                        </div>

                        <!-- Opsi 1: Hapus Rentang Tanggal -->
                        <div style="margin-top:24px; padding-top:20px; border-top:1px solid var(--border-subtle);">
                            <h3 style="font-size:14px; font-weight:600; margin-bottom:6px;">Hapus Riwayat Berdasarkan Rentang</h3>
                            <p style="font-size:12px; color:var(--text-muted); margin-bottom:14px;">
                                Hapus riwayat unduhan pada jangka waktu spesifik untuk menghemat ruang penyimpanan.
                            </p>
                            <form id="form-delete-range">
                                <div class="form-row" style="margin-bottom:14px;">
                                    <div class="form-group" style="flex:1;">
                                        <label class="form-label" for="del-start-date">Tanggal Mulai</label>
                                        <input type="date" id="del-start-date" class="form-input" required>
                                    </div>
                                    <div class="form-group" style="flex:1;">
                                        <label class="form-label" for="del-end-date">Tanggal Selesai</label>
                                        <input type="date" id="del-end-date" class="form-input" required>
                                    </div>
                                </div>
                                <button type="submit" class="btn-action-primary" style="width:100%;">
                                    <i class="fa-solid fa-calendar-xmark"></i>
                                    <span>Hapus Riwayat Rentang Ini</span>
                                </button>
                            </form>
                        </div>

                        <!-- Opsi 2: Hapus Semua Riwayat -->
                        <div style="margin-top:24px; padding-top:20px; border-top:1px solid var(--border-subtle);">
                            <h3 style="font-size:14px; font-weight:600; color:var(--danger); margin-bottom:6px;">Hapus Semua Riwayat</h3>
                            <p style="font-size:12px; color:var(--text-muted); margin-bottom:14px;">
                                Mengosongkan seluruh tabel riwayat unduhan dan melakukan rekonstruksi database SQLite (VACUUM). Tindakan ini tidak dapat dibatalkan.
                            </p>
                            <button type="button" id="btn-delete-all" class="btn-action-primary btn-action-danger" style="width:100%;">
                                <i class="fa-solid fa-trash-can"></i>
                                <span>Kosongkan Semua Riwayat</span>
                            </button>
                        </div>
                    </section>

                    <!-- Kolom 2: Download File .logs dari Server -->
                    <section class="admin-card">
                        <div class="card-header">
                            <div>
                                <h2 class="card-title">
                                    <i class="fa-solid fa-file-code" style="color:var(--text-secondary);"></i>
                                    <span>Download File Log Server</span>
                                </h2>
                                <p class="card-subtitle">Unduh arsip catatan log (.log) yang dihasilkan oleh server</p>
                            </div>
                            <button id="btn-refresh-logs" class="btn-minimal" title="Segarkan Berkas Log">
                                <i class="fa-solid fa-arrows-rotate"></i>
                            </button>
                        </div>

                        <div class="table-responsive">
                            <table class="admin-table">
                                <thead>
                                    <tr>
                                        <th>Nama Berkas .log</th>
                                        <th style="width:100px;">Ukuran</th>
                                        <th style="width:140px;">Dimodifikasi</th>
                                        <th style="width:110px; text-align:center;">Unduh</th>
                                    </tr>
                                </thead>
                                <tbody id="logs-table-body">
                                    <tr>
                                        <td colspan="4" style="text-align:center; padding:30px; color:var(--text-muted);">
                                            Memuat daftar berkas log...
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </section>
                </div>
            </div>

            <!-- ======================================================= -->
            <!-- PAGE 3: ADVERTISMENT                                    -->
            <!-- ======================================================= -->
            <div id="page-ads" class="page-container" style="display:none;">
                <!-- KPI Ads Summary Grid -->
                <section class="kpi-grid">
                    <div class="kpi-card">
                        <div class="kpi-title">
                            <span>TOTAL IKLAN</span>
                            <i class="fa-solid fa-bullhorn kpi-icon"></i>
                        </div>
                        <div class="kpi-value" id="kpi-total-ads">-</div>
                    </div>
                    <div class="kpi-card">
                        <div class="kpi-title">
                            <span>IKLAN AKTIF TAYANG</span>
                            <i class="fa-solid fa-toggle-on kpi-icon" style="color:var(--success);"></i>
                        </div>
                        <div class="kpi-value" id="kpi-active-ads" style="color:var(--success);">-</div>
                    </div>
                    <div class="kpi-card">
                        <div class="kpi-title">
                            <span>TOTAL KLIK IKLAN</span>
                            <i class="fa-solid fa-arrow-pointer kpi-icon"></i>
                        </div>
                        <div class="kpi-value" id="kpi-total-clicks">-</div>
                    </div>
                </section>

                <!-- Tabel Iklan Card -->
                <section class="admin-card">
                    <div class="card-header" style="flex-wrap:wrap; gap:16px;">
                        <div>
                            <h2 class="card-title">
                                <i class="fa-solid fa-rectangle-ad" style="color:var(--accent);"></i>
                                <span>Katalog Iklan Produk</span>
                            </h2>
                            <p class="card-subtitle">Dalam 1 produk hanya 1 link yang bisa tayang (Link Produk ATAU Link Komisi Ekstra)</p>
                        </div>
                        <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
                            <input type="text" id="ads-search-input" class="form-input" placeholder="Cari produk / komisi..." style="width:220px; height:36px; font-size:12px;">
                            <button id="btn-open-create-ad" class="btn-action-primary" style="height:36px; padding:0 14px; font-size:12px;">
                                <i class="fa-solid fa-plus"></i>
                                <span>Iklan Manual</span>
                            </button>
                            <button id="btn-open-bulk-ad" class="btn-minimal" style="height:36px; padding:0 14px; font-size:12px; border:1px solid var(--border-medium); background:var(--bg-surface-elevated);" title="Upload File CSV/Excel">
                                <i class="fa-solid fa-file-csv" style="color:#38bdf8;"></i>
                                <span>Upload Bulk</span>
                            </button>
                            <button id="btn-refresh-ads" class="btn-minimal" style="height:36px;" title="Segarkan Data Iklan">
                                <i class="fa-solid fa-arrows-rotate"></i>
                            </button>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th style="min-width:240px;">Nama Produk</th>
                                    <th style="width:130px;">Komisi (Rp.)</th>
                                    <th style="min-width:180px;">Link Product</th>
                                    <th style="min-width:180px;">Link Komisi Ekstra</th>
                                    <th style="width:110px; text-align:center;">Jumlah Click</th>
                                    <th style="width:130px; text-align:center;">Potensi Issue</th>
                                    <th style="width:70px; text-align:center;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="ads-table-body">
                                <tr>
                                    <td colspan="7" style="text-align:center; padding:36px; color:var(--text-muted);">
                                        <i class="fa-solid fa-circle-notch fa-spin"></i> Memuat katalog iklan...
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="pagination-footer">
                        <div class="pagination-info" id="ads-pagination-info">
                            Menampilkan 0 dari 0 iklan
                        </div>
                        <div class="pagination-controls">
                            <button id="btn-ads-prev" class="btn-page" disabled>
                                <i class="fa-solid fa-chevron-left"></i>
                                <span>Sebelumnya</span>
                            </button>
                            <span class="page-current" id="ads-page-current">1</span>
                            <button id="btn-ads-next" class="btn-page" disabled>
                                <span>Berikutnya</span>
                                <i class="fa-solid fa-chevron-right"></i>
                            </button>
                        </div>
                    </div>
                </section>
            </div>

            <!-- ======================================================= -->
            <!-- MODAL 1: TAMBAH IKLAN MANUAL                           -->
            <!-- ======================================================= -->
            <div id="modal-create-ad" class="modal-overlay">
                <div class="modal-dialog">
                    <div class="modal-header">
                        <div class="modal-title">
                            <i class="fa-solid fa-plus-circle" style="color:var(--accent);"></i>
                            <span>Tambah Iklan Manual</span>
                        </div>
                        <button type="button" class="modal-close" data-close="modal-create-ad">&times;</button>
                    </div>
                    <form id="form-create-ad">
                        <div class="modal-body">
                            <div class="form-group" style="margin-bottom:16px;">
                                <label class="form-label" for="manual-ad-name">Nama Produk <span style="color:var(--danger);">*</span></label>
                                <input type="text" id="manual-ad-name" class="form-input" placeholder="Contoh: Sepatu Sneakers Pria Original" required>
                            </div>
                            <div class="form-group" style="margin-bottom:16px;">
                                <label class="form-label" for="manual-ad-comm">Komisi (Rp) <span style="color:var(--danger);">*</span></label>
                                <input type="text" id="manual-ad-comm" class="form-input" placeholder="Contoh: Rp13.000 atau 13000" required>
                            </div>
                            <div class="form-group" style="margin-bottom:16px;">
                                <label class="form-label" for="manual-ad-prod">Link Produk</label>
                                <input type="url" id="manual-ad-prod" class="form-input" placeholder="https://shopee.co.id/product/...">
                            </div>
                            <div class="form-group" style="margin-bottom:16px;">
                                <label class="form-label" for="manual-ad-extra">Link Komisi Ekstra</label>
                                <input type="url" id="manual-ad-extra" class="form-input" placeholder="https://s.shopee.co.id/...">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Link yang Langsung Ditayangkan</label>
                                <select id="manual-ad-active" class="form-input" style="height:40px;">
                                    <option value="none">Belum Ditayangkan (Nonaktif)</option>
                                    <option value="product">Link Produk</option>
                                    <option value="extra" selected>Link Komisi Ekstra (Rekomendasi)</option>
                                </select>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn-minimal" data-close="modal-create-ad">Batal</button>
                            <button type="submit" id="btn-save-manual-ad" class="btn-action-primary">
                                <i class="fa-solid fa-floppy-disk"></i>
                                <span>Simpan Iklan</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- ======================================================= -->
            <!-- MODAL 2: UPLOAD BULK CSV/EXCEL DENGAN PREVIEW & MAPPING -->
            <!-- ======================================================= -->
            <div id="modal-bulk-ad" class="modal-overlay">
                <div class="modal-dialog modal-dialog-large">
                    <div class="modal-header">
                        <div class="modal-title">
                            <i class="fa-solid fa-file-csv" style="color:#38bdf8;"></i>
                            <span>Upload Iklan Massal (Bulk CSV/Excel)</span>
                        </div>
                        <button type="button" class="modal-close" data-close="modal-bulk-ad">&times;</button>
                    </div>
                    <div class="modal-body">
                        <!-- Step 1: Upload File -->
                        <div id="bulk-step-upload" style="margin-bottom:20px;">
                            <label class="form-label">Pilih Berkas CSV / Excel Produk</label>
                            <div style="border:2px dashed var(--border-medium); border-radius:var(--radius-md); padding:28px 20px; text-align:center; background:var(--bg-base); cursor:pointer;" id="drop-zone-csv">
                                <i class="fa-solid fa-cloud-arrow-up" style="font-size:32px; color:var(--text-muted); margin-bottom:10px;"></i>
                                <p style="font-size:13px; font-weight:600; margin-bottom:4px;">Klik untuk memilih berkas atau seret file ke sini</p>
                                <p style="font-size:12px; color:var(--text-muted);">Mendukung format .csv, .tsv, atau berkas tabel teks (cth: sample-iklan.csv)</p>
                                <input type="file" id="input-file-csv" accept=".csv,.txt,.tsv" style="display:none;">
                            </div>
                        </div>

                        <!-- Step 2: Live Preview & Column Mapping (Hidden sampai file dipilih) -->
                        <div id="bulk-step-preview" style="display:none;">
                            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">
                                <div style="font-size:13px; font-weight:600; color:var(--text-primary);" id="bulk-preview-summary">
                                    Preview Berkas (5 Baris Pertama)
                                </div>
                                <button type="button" id="btn-reupload-csv" class="btn-minimal" style="font-size:11px; padding:3px 8px;">
                                    <i class="fa-solid fa-arrow-rotate-left"></i> Ganti File
                                </button>
                            </div>

                            <div class="preview-box">
                                <table class="admin-table" style="font-size:11px;" id="table-csv-preview">
                                    <thead id="csv-preview-head"></thead>
                                    <tbody id="csv-preview-body"></tbody>
                                </table>
                            </div>

                            <!-- Column Mapping Selectors -->
                            <div style="background:var(--bg-surface-elevated); border:1px solid var(--border-medium); border-radius:var(--radius-md); padding:18px; margin-bottom:10px;">
                                <h3 style="font-size:13px; font-weight:600; margin-bottom:12px; display:flex; align-items:center; gap:8px;">
                                    <i class="fa-solid fa-arrows-split-up-and-left" style="color:var(--accent);"></i>
                                    <span>Tentukan Pemetaan Kolom (Column Mapping)</span>
                                </h3>
                                <div class="form-row" style="margin-bottom:12px;">
                                    <div class="form-group" style="flex:1;">
                                        <label class="form-label" for="map-col-name">Kolom "Nama Produk" <span style="color:var(--danger);">*</span></label>
                                        <select id="map-col-name" class="form-input" style="height:38px; font-size:12px;"></select>
                                    </div>
                                    <div class="form-group" style="flex:1;">
                                        <label class="form-label" for="map-col-comm">Kolom "Komisi (Rp.)" <span style="color:var(--danger);">*</span></label>
                                        <select id="map-col-comm" class="form-input" style="height:38px; font-size:12px;"></select>
                                    </div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group" style="flex:1;">
                                        <label class="form-label" for="map-col-prod">Kolom "Link Produk"</label>
                                        <select id="map-col-prod" class="form-input" style="height:38px; font-size:12px;"></select>
                                    </div>
                                    <div class="form-group" style="flex:1;">
                                        <label class="form-label" for="map-col-extra">Kolom "Link Komisi Ekstra"</label>
                                        <select id="map-col-extra" class="form-input" style="height:38px; font-size:12px;"></select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn-minimal" data-close="modal-bulk-ad">Tutup</button>
                        <button type="button" id="btn-submit-bulk-import" class="btn-action-primary" style="display:none;">
                            <i class="fa-solid fa-file-import"></i>
                            <span id="btn-bulk-text">Mulai Impor Produk</span>
                        </button>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Script Interaktif Dashboard Admin -->
    <script>
    document.addEventListener('DOMContentLoaded', () => {
        const isLoggedIn = <?php echo $isLoggedIn ? 'true' : 'false'; ?>;

        if (!isLoggedIn) {
            initLoginFlow();
        } else {
            initDashboardFlow();
        }

        // -------------------------------------------------------------
        // Alur Autentikasi / Login
        // -------------------------------------------------------------
        function initLoginFlow() {
            const form = document.getElementById('login-form');
            const alertBox = document.getElementById('login-alert');
            const btnSubmit = document.getElementById('btn-submit-login');

            form.addEventListener('submit', async (e) => {
                e.preventDefault();
                alertBox.style.display = 'none';

                const username = document.getElementById('login-username').value.trim();
                const password = document.getElementById('login-password').value.trim();

                btnSubmit.disabled = true;
                btnSubmit.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i> <span>Memvalidasi...</span>';

                try {
                    const res = await fetch('api/admin/login', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ username, password })
                    });

                    const data = await res.json();
                    if (!data.success) {
                        throw new Error(data.message || 'Login gagal.');
                    }

                    // Berhasil login
                    window.location.reload();
                } catch (err) {
                    alertBox.textContent = err.message;
                    alertBox.style.display = 'block';
                    btnSubmit.disabled = false;
                    btnSubmit.innerHTML = '<i class="fa-solid fa-arrow-right-to-bracket"></i> <span>Masuk ke Dashboard</span>';
                }
            });
        }

        // -------------------------------------------------------------
        // Alur Dashboard (Analitics & Monitor DB)
        // -------------------------------------------------------------
        function initDashboardFlow() {
            // Tab Switcher
            const tabButtons = document.querySelectorAll('.nav-tab-btn');
            const pageAnalytics = document.getElementById('page-analytics');
            const pageMonitor = document.getElementById('page-monitor');
            const pageAds = document.getElementById('page-ads');

            tabButtons.forEach(btn => {
                btn.addEventListener('click', () => {
                    tabButtons.forEach(b => b.classList.remove('active'));
                    btn.classList.add('active');

                    const target = btn.getAttribute('data-target');
                    pageAnalytics.style.display = (target === 'page-analytics') ? 'block' : 'none';
                    pageMonitor.style.display   = (target === 'page-monitor') ? 'block' : 'none';
                    pageAds.style.display       = (target === 'page-ads') ? 'block' : 'none';

                    if (target === 'page-monitor') {
                        loadDbMonitorData();
                    } else if (target === 'page-ads') {
                        window.loadAdsData(1);
                    }
                });
            });

            // Logout
            const btnLogout = document.getElementById('btn-admin-logout');
            if (btnLogout) {
                btnLogout.addEventListener('click', async () => {
                    if (confirm('Apakah Anda yakin ingin keluar dari sesi admin?')) {
                        await fetch('api/admin/logout', { method: 'POST' });
                        window.location.reload();
                    }
                });
            }

            // Inisialisasi Chart & Data Awal
            let chartInstance = null;
            let currentRange = 'weekly';
            let currentPage = 1;

            initChart();
            loadAnalyticsData(currentRange);
            loadHistoryTable(currentPage);

            // Filter Grafik (Mingguan, Bulanan, Tahunan)
            document.querySelectorAll('.btn-filter').forEach(btn => {
                btn.addEventListener('click', () => {
                    document.querySelectorAll('.btn-filter').forEach(b => b.classList.remove('active'));
                    btn.classList.add('active');

                    currentRange = btn.getAttribute('data-range');
                    loadAnalyticsData(currentRange);
                });
            });

            // Paginasi Riwayat
            const btnPrev = document.getElementById('btn-page-prev');
            const btnNext = document.getElementById('btn-page-next');
            const btnRefreshHist = document.getElementById('btn-refresh-history');

            btnPrev.addEventListener('click', () => {
                if (currentPage > 1) {
                    currentPage--;
                    loadHistoryTable(currentPage);
                }
            });

            btnNext.addEventListener('click', () => {
                currentPage++;
                loadHistoryTable(currentPage);
            });

            btnRefreshHist.addEventListener('click', () => {
                loadHistoryTable(currentPage);
                loadAnalyticsData(currentRange);
            });

            // Inisialisasi Chart.js
            function initChart() {
                const ctx = document.getElementById('analyticsChart').getContext('2d');
                chartInstance = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: [],
                        datasets: [
                            {
                                label: 'Jumlah Unduhan',
                                data: [],
                                borderColor: '#6366f1',
                                backgroundColor: 'rgba(99, 102, 241, 0.1)',
                                borderWidth: 2,
                                fill: true,
                                tension: 0.35,
                                pointBackgroundColor: '#6366f1',
                                pointBorderColor: '#09090b',
                                pointBorderWidth: 2,
                                pointRadius: 4,
                                pointHoverRadius: 6,
                                yAxisID: 'y'
                            },
                            {
                                label: 'Ukuran Media (MB)',
                                data: [],
                                borderColor: '#10b981',
                                backgroundColor: 'transparent',
                                borderWidth: 1.5,
                                borderDash: [4, 4],
                                fill: false,
                                tension: 0.35,
                                pointRadius: 3,
                                yAxisID: 'y1'
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        interaction: {
                            mode: 'index',
                            intersect: false,
                        },
                        plugins: {
                            legend: {
                                labels: {
                                    color: '#a1a1aa',
                                    font: { family: 'Inter', size: 12 },
                                    boxWidth: 14
                                }
                            },
                            tooltip: {
                                backgroundColor: '#18181c',
                                titleColor: '#f4f4f5',
                                bodyColor: '#a1a1aa',
                                borderColor: '#2e2e36',
                                borderWidth: 1,
                                padding: 10
                            }
                        },
                        scales: {
                            x: {
                                grid: { color: 'rgba(255, 255, 255, 0.04)' },
                                ticks: { color: '#71717a', font: { family: 'Inter', size: 11 } }
                            },
                            y: {
                                type: 'linear',
                                display: true,
                                position: 'left',
                                beginAtZero: true,
                                grid: { color: 'rgba(255, 255, 255, 0.04)' },
                                ticks: { color: '#71717a', font: { family: 'Inter', size: 11 }, precision: 0 }
                            },
                            y1: {
                                type: 'linear',
                                display: true,
                                position: 'right',
                                beginAtZero: true,
                                grid: { drawOnChartArea: false },
                                ticks: { color: '#10b981', font: { family: 'Inter', size: 10 } }
                            }
                        }
                    }
                });
            }

            // Ambil Data Analitik & Update Chart
            async function loadAnalyticsData(range) {
                try {
                    const res = await fetch(`api/admin/analytics?range=${range}`);
                    const json = await res.json();

                    if (!json.success) return;

                    const summary = json.data.summary || {};
                    const chart = json.data.chart || {};

                    // Update KPI Cards
                    document.getElementById('kpi-total-downloads').textContent = summary.total_downloads.toLocaleString();
                    document.getElementById('kpi-today-downloads').textContent = summary.today_downloads.toLocaleString();
                    document.getElementById('kpi-total-bandwidth').textContent = summary.total_bytes_fmt || '0 B';
                    document.getElementById('kpi-top-platform').textContent = summary.top_platform || '-';

                    // Update Chart
                    if (chartInstance) {
                        chartInstance.data.labels = chart.labels || [];
                        chartInstance.data.datasets[0].data = chart.counts || [];
                        chartInstance.data.datasets[1].data = chart.sizes_mb || [];
                        chartInstance.update();
                    }
                } catch (err) {
                    console.error('Gagal memuat analitik:', err);
                }
            }

            // Ambil Riwayat Unduhan dengan Paginasi Limit 10
            async function loadHistoryTable(page) {
                const tbody = document.getElementById('history-table-body');
                tbody.innerHTML = `<tr><td colspan="5" style="text-align:center; padding:24px; color:var(--text-muted);"><i class="fa-solid fa-spinner fa-spin"></i> Memuat data riwayat...</td></tr>`;

                try {
                    const res = await fetch(`api/admin/history?page=${page}`);
                    const json = await res.json();

                    if (!json.success) throw new Error(json.message);

                    const data = json.data;
                    const items = data.items || [];
                    const totalPages = data.total_pages || 1;
                    const totalRecords = data.total || 0;

                    // Update Paginasi
                    document.getElementById('pagination-info').textContent = `Halaman ${page} dari ${totalPages} (Total ${totalRecords} unduhan)`;
                    btnPrev.disabled = page <= 1;
                    btnNext.disabled = page >= totalPages;

                    if (items.length === 0) {
                        tbody.innerHTML = `<tr><td colspan="5" style="text-align:center; padding:30px; color:var(--text-muted);">Belum ada riwayat unduhan yang tercatat.</td></tr>`;
                        return;
                    }

                    tbody.innerHTML = '';
                    items.forEach(item => {
                        const tr = document.createElement('tr');
                        tr.innerHTML = `
                            <td><span class="ip-badge">${escapeHtml(item.ip_address)}</span></td>
                            <td>
                                <a href="${escapeHtml(item.url)}" target="_blank" rel="noopener noreferrer" class="link-badge" title="${escapeHtml(item.title || item.url)}">
                                    <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                    <span>${escapeHtml(item.title || 'link')}</span>
                                </a>
                            </td>
                            <td>
                                <span class="platform-badge">
                                    <i class="fa-solid fa-circle" style="font-size:6px; color:var(--accent);"></i>
                                    <span>${escapeHtml(item.sumber)}</span>
                                </span>
                            </td>
                            <td style="font-family:var(--font-mono); font-size:12px;">${escapeHtml(item.ukuran_fmt)}</td>
                            <td style="font-size:12px; color:var(--text-muted);">${escapeHtml(item.waktu_fmt)}</td>
                        `;
                        tbody.appendChild(tr);
                    });
                } catch (err) {
                    tbody.innerHTML = `<tr><td colspan="5" style="text-align:center; padding:20px; color:var(--danger);">${err.message}</td></tr>`;
                }
            }

            // -------------------------------------------------------------
            // Monitor DB Handlers
            // -------------------------------------------------------------
            async function loadDbMonitorData() {
                loadDbStats();
                loadLogFiles();
            }

            async function loadDbStats() {
                try {
                    const res = await fetch('api/admin/db-stats');
                    const json = await res.json();
                    if (!json.success) return;

                    const s = json.data;
                    document.getElementById('db-file-size').textContent = s.file_size_fmt;
                    document.getElementById('db-total-rows').textContent = s.total_rows.toLocaleString() + ' baris';
                    document.getElementById('db-first-record').textContent = s.first_record;
                    document.getElementById('db-latest-record').textContent = s.latest_record;
                } catch (err) {
                    console.error('Gagal mengambil status DB:', err);
                }
            }

            async function loadLogFiles() {
                const tbody = document.getElementById('logs-table-body');
                tbody.innerHTML = `<tr><td colspan="4" style="text-align:center; padding:20px; color:var(--text-muted);"><i class="fa-solid fa-spinner fa-spin"></i> Memuat berkas log...</td></tr>`;

                try {
                    const res = await fetch('api/admin/logs');
                    const json = await res.json();

                    if (!json.success || !json.data || json.data.length === 0) {
                        tbody.innerHTML = `<tr><td colspan="4" style="text-align:center; padding:24px; color:var(--text-muted);">Tidak ada berkas log yang ditemukan di folder logs/.</td></tr>`;
                        return;
                    }

                    tbody.innerHTML = '';
                    json.data.forEach(log => {
                        const tr = document.createElement('tr');
                        tr.innerHTML = `
                            <td><strong style="font-family:var(--font-mono); font-size:12px;">${escapeHtml(log.filename)}</strong></td>
                            <td style="font-family:var(--font-mono); font-size:12px;">${escapeHtml(log.size_fmt)}</td>
                            <td style="font-size:12px; color:var(--text-muted);">${escapeHtml(log.modified_fmt)}</td>
                            <td style="text-align:center;">
                                <a href="api/admin/logs/download?file=${encodeURIComponent(log.filename)}" class="btn-dl-log" title="Unduh berkas log ini">
                                    <i class="fa-solid fa-download"></i>
                                    <span>Download</span>
                                </a>
                            </td>
                        `;
                        tbody.appendChild(tr);
                    });
                } catch (err) {
                    tbody.innerHTML = `<tr><td colspan="4" style="text-align:center; padding:20px; color:var(--danger);">Gagal memuat log: ${err.message}</td></tr>`;
                }
            }

            // Hapus Riwayat Berdasarkan Rentang
            const formDelRange = document.getElementById('form-delete-range');
            formDelRange.addEventListener('submit', async (e) => {
                e.preventDefault();
                const startDate = document.getElementById('del-start-date').value;
                const endDate = document.getElementById('del-end-date').value;

                if (!startDate || !endDate) {
                    alert('Harap pilih tanggal mulai dan tanggal selesai.');
                    return;
                }

                if (!confirm(`Konfirmasi: Hapus seluruh riwayat unduhan dari tanggal ${startDate} hingga ${endDate}?`)) {
                    return;
                }

                try {
                    const res = await fetch('api/admin/history/delete', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ mode: 'range', start_date: startDate, end_date: endDate })
                    });
                    const data = await res.json();

                    if (!data.success) throw new Error(data.message);

                    showToast(data.message);
                    loadDbStats();
                    loadAnalyticsData(currentRange);
                    loadHistoryTable(1);
                    formDelRange.reset();
                } catch (err) {
                    alert('Gagal menghapus: ' + err.message);
                }
            });

            // Hapus Semua Riwayat
            const btnDelAll = document.getElementById('btn-delete-all');
            btnDelAll.addEventListener('click', async () => {
                const conf = prompt('PERINGATAN: Seluruh riwayat unduhan akan dikosongkan secara permanen.\nKetik "HAPUS" untuk mengonfirmasi:');
                if (conf !== 'HAPUS') {
                    return;
                }

                try {
                    const res = await fetch('api/admin/history/delete', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ mode: 'all' })
                    });
                    const data = await res.json();

                    if (!data.success) throw new Error(data.message);

                    showToast(data.message);
                    loadDbStats();
                    loadAnalyticsData(currentRange);
                    loadHistoryTable(1);
                } catch (err) {
                    alert('Gagal menghapus: ' + err.message);
                }
            });

            // Tombol Refresh Logs
            const btnRefreshLogs = document.getElementById('btn-refresh-logs');
            btnRefreshLogs.addEventListener('click', loadLogFiles);

            // Inisialisasi Alur Advertisment
            initAdsFlow();
        }

        // -------------------------------------------------------------
        // Alur Advertisment (Katalog Iklan, Toggle Link, Bulk Import)
        // -------------------------------------------------------------
        function initAdsFlow() {
            let adsCurrentPage = 1;
            let adsSearchQuery = '';
            let bulkUploadToken = null;

            // Elements
            const tableBody = document.getElementById('ads-table-body');
            const kpiTotalAds = document.getElementById('kpi-total-ads');
            const kpiActiveAds = document.getElementById('kpi-active-ads');
            const kpiTotalClicks = document.getElementById('kpi-total-clicks');
            const paginationInfo = document.getElementById('ads-pagination-info');
            const pageCurrent = document.getElementById('ads-page-current');
            const btnPrev = document.getElementById('btn-ads-prev');
            const btnNext = document.getElementById('btn-ads-next');
            const searchInput = document.getElementById('ads-search-input');
            const btnRefresh = document.getElementById('btn-refresh-ads');

            // Modals
            const modalCreate = document.getElementById('modal-create-ad');
            const modalBulk = document.getElementById('modal-bulk-ad');
            const btnOpenCreate = document.getElementById('btn-open-create-ad');
            const btnOpenBulk = document.getElementById('btn-open-bulk-ad');
            const formCreate = document.getElementById('form-create-ad');

            // Bulk elements
            const dropZone = document.getElementById('drop-zone-csv');
            const inputFileCsv = document.getElementById('input-file-csv');
            const stepUpload = document.getElementById('bulk-step-upload');
            const stepPreview = document.getElementById('bulk-step-preview');
            const btnReupload = document.getElementById('btn-reupload-csv');
            const btnSubmitBulk = document.getElementById('btn-submit-bulk-import');
            const btnBulkText = document.getElementById('btn-bulk-text');
            const previewHead = document.getElementById('csv-preview-head');
            const previewBody = document.getElementById('csv-preview-body');
            const previewSummary = document.getElementById('bulk-preview-summary');

            const mapColName = document.getElementById('map-col-name');
            const mapColComm = document.getElementById('map-col-comm');
            const mapColProd = document.getElementById('map-col-prod');
            const mapColExtra = document.getElementById('map-col-extra');

            // Open Modals
            btnOpenCreate.addEventListener('click', () => {
                formCreate.reset();
                modalCreate.classList.add('active');
            });

            btnOpenBulk.addEventListener('click', () => {
                resetBulkModal();
                modalBulk.classList.add('active');
            });

            // Close Modals
            document.querySelectorAll('[data-close]').forEach(btn => {
                btn.addEventListener('click', () => {
                    const targetId = btn.getAttribute('data-close');
                    const m = document.getElementById(targetId);
                    if (m) m.classList.remove('active');
                });
            });

            [modalCreate, modalBulk].forEach(m => {
                m.addEventListener('click', (e) => {
                    if (e.target === m) m.classList.remove('active');
                });
            });

            // Load Ads Function
            window.loadAdsData = async function(page = 1) {
                adsCurrentPage = page;
                tableBody.innerHTML = `
                    <tr>
                        <td colspan="7" style="text-align:center; padding:36px; color:var(--text-muted);">
                            <i class="fa-solid fa-circle-notch fa-spin"></i> Memuat katalog iklan...
                        </td>
                    </tr>
                `;

                try {
                    const q = encodeURIComponent(adsSearchQuery);
                    const res = await fetch(`api/admin/ads?page=${page}&search=${q}`);
                    const json = await res.json();

                    if (!json.success) throw new Error(json.message);

                    const data = json.data;
                    const items = data.items || [];
                    const pag = data.pagination || {};
                    const stats = data.stats || {};

                    // Update KPIs
                    kpiTotalAds.textContent = Number(stats.total_ads || 0).toLocaleString('id-ID');
                    kpiActiveAds.textContent = Number(stats.active_ads || 0).toLocaleString('id-ID');
                    kpiTotalClicks.textContent = Number(stats.total_clicks || 0).toLocaleString('id-ID');

                    // Update Pagination
                    const start = pag.total_items > 0 ? (pag.page - 1) * pag.limit + 1 : 0;
                    const end = Math.min(pag.page * pag.limit, pag.total_items);
                    paginationInfo.textContent = `Menampilkan ${start} - ${end} dari ${pag.total_items} iklan`;
                    pageCurrent.textContent = pag.page;
                    btnPrev.disabled = !pag.has_prev;
                    btnNext.disabled = !pag.has_next;

                    // Render Table
                    if (items.length === 0) {
                        tableBody.innerHTML = `
                            <tr>
                                <td colspan="7" style="text-align:center; padding:48px 20px; color:var(--text-muted);">
                                    <i class="fa-regular fa-folder-open" style="font-size:32px; margin-bottom:10px; display:block;"></i>
                                    <span>Tidak ada iklan ditemukan. Klik tombol <strong>+ Iklan Manual</strong> atau <strong>Upload Bulk</strong> untuk menambahkan iklan.</span>
                                </td>
                            </tr>
                        `;
                        return;
                    }

                    tableBody.innerHTML = '';
                    items.forEach(ad => {
                        const tr = document.createElement('tr');

                        let issueBadgeClass = 'badge-issue-safe';
                        let issueIcon = 'fa-circle-check';
                        if (ad.potential_issue === 'Belum Aktif') {
                            issueBadgeClass = 'badge-issue-warn';
                            issueIcon = 'fa-circle-pause';
                        } else if (ad.potential_issue === 'Format Tidak Valid' || ad.potential_issue === 'Link Kosong') {
                            issueBadgeClass = 'badge-issue-danger';
                            issueIcon = 'fa-triangle-exclamation';
                        }

                        tr.innerHTML = `
                            <td>
                                <div style="font-weight:600; font-size:13px; margin-bottom:3px; max-width:280px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;" title="${escapeHtml(ad.product_name)}">
                                    ${escapeHtml(ad.product_name)}
                                </div>
                                <div style="font-size:11px; color:var(--text-muted); font-family:var(--font-mono);">
                                    ID: #${ad.id} &bull; ${escapeHtml(ad.created_at || '')}
                                </div>
                            </td>
                            <td>
                                <span style="font-weight:600; color:#34d399; font-size:13px;">${escapeHtml(ad.commission_fmt)}</span>
                            </td>
                            <td>
                                <div class="link-short-wrap">
                                    ${ad.product_link ? `
                                        <a href="${escapeHtml(ad.product_link)}" target="_blank" class="link-url-text" title="${escapeHtml(ad.product_link)}">
                                            <span>${escapeHtml(ad.product_link_short)}</span>
                                            <i class="fa-solid fa-arrow-up-right-from-square" style="font-size:9px;"></i>
                                        </a>
                                        <button type="button" class="btn-toggle-link ${ad.is_product_active ? 'active-link' : ''}" data-id="${ad.id}" data-type="product" title="Klik untuk mengaktifkan / menonaktifkan link ini">
                                            <i class="fa-solid ${ad.is_product_active ? 'fa-circle-check' : 'fa-circle'}"></i>
                                            <span>${ad.is_product_active ? 'Tayang Aktif' : 'Aktifkan'}</span>
                                        </button>
                                    ` : '<span style="color:var(--text-muted); font-size:11px;">(kosong)</span>'}
                                </div>
                            </td>
                            <td>
                                <div class="link-short-wrap">
                                    ${ad.extra_commission_link ? `
                                        <a href="${escapeHtml(ad.extra_commission_link)}" target="_blank" class="link-url-text" title="${escapeHtml(ad.extra_commission_link)}">
                                            <span>${escapeHtml(ad.extra_commission_link_short)}</span>
                                            <i class="fa-solid fa-arrow-up-right-from-square" style="font-size:9px;"></i>
                                        </a>
                                        <button type="button" class="btn-toggle-link ${ad.is_extra_active ? 'active-link' : ''}" data-id="${ad.id}" data-type="extra" title="Klik untuk mengaktifkan / menonaktifkan link ini">
                                            <i class="fa-solid ${ad.is_extra_active ? 'fa-circle-check' : 'fa-circle'}"></i>
                                            <span>${ad.is_extra_active ? 'Tayang Aktif' : 'Aktifkan'}</span>
                                        </button>
                                    ` : '<span style="color:var(--text-muted); font-size:11px;">(kosong)</span>'}
                                </div>
                            </td>
                            <td style="text-align:center;">
                                <span style="background:var(--bg-surface-elevated); border:1px solid var(--border-medium); padding:3px 8px; border-radius:var(--radius-sm); font-size:12px; font-weight:600; display:inline-flex; align-items:center; gap:5px;">
                                    <i class="fa-solid fa-arrow-pointer" style="font-size:10px; color:var(--accent);"></i>
                                    <span>${ad.click_count}</span>
                                </span>
                            </td>
                            <td style="text-align:center;">
                                <span class="badge-issue ${issueBadgeClass}">
                                    <i class="fa-solid ${issueIcon}"></i>
                                    <span>${escapeHtml(ad.potential_issue)}</span>
                                </span>
                            </td>
                            <td style="text-align:center;">
                                <button type="button" class="btn-minimal btn-delete-ad" data-id="${ad.id}" style="color:var(--danger); padding:6px 10px;" title="Hapus Iklan">
                                    <i class="fa-solid fa-trash-can"></i>
                                </button>
                            </td>
                        `;

                        tableBody.appendChild(tr);
                    });

                    // Pasang event toggle link
                    tableBody.querySelectorAll('.btn-toggle-link').forEach(btn => {
                        btn.addEventListener('click', async (e) => {
                            const id = btn.getAttribute('data-id');
                            const targetType = btn.getAttribute('data-type');
                            btn.disabled = true;

                            try {
                                const toggleRes = await fetch('api/admin/ads/toggle', {
                                    method: 'POST',
                                    headers: { 'Content-Type': 'application/json' },
                                    body: JSON.stringify({ id, target_type: targetType })
                                });
                                const toggleJson = await toggleRes.json();
                                if (!toggleJson.success) throw new Error(toggleJson.message);

                                showToast(toggleJson.message);
                                window.loadAdsData(adsCurrentPage);
                            } catch (err) {
                                alert(err.message);
                                btn.disabled = false;
                            }
                        });
                    });

                    // Pasang event delete
                    tableBody.querySelectorAll('.btn-delete-ad').forEach(btn => {
                        btn.addEventListener('click', async () => {
                            const id = btn.getAttribute('data-id');
                            if (!confirm('Apakah Anda yakin ingin menghapus iklan ini?')) return;

                            try {
                                const delRes = await fetch('api/admin/ads/delete', {
                                    method: 'POST',
                                    headers: { 'Content-Type': 'application/json' },
                                    body: JSON.stringify({ id })
                                });
                                const delJson = await delRes.json();
                                if (!delJson.success) throw new Error(delJson.message);

                                showToast(delJson.message);
                                window.loadAdsData(adsCurrentPage);
                            } catch (err) {
                                alert(err.message);
                            }
                        });
                    });

                } catch (err) {
                    tableBody.innerHTML = `
                        <tr>
                            <td colspan="7" style="text-align:center; padding:30px; color:var(--danger);">
                                Gagal memuat data iklan: ${err.message}
                            </td>
                        </tr>
                    `;
                }
            };

            // Pagination Events
            btnPrev.addEventListener('click', () => {
                if (adsCurrentPage > 1) window.loadAdsData(adsCurrentPage - 1);
            });
            btnNext.addEventListener('click', () => {
                window.loadAdsData(adsCurrentPage + 1);
            });

            // Search Event dengan Debounce
            let searchTimeout = null;
            searchInput.addEventListener('input', (e) => {
                clearTimeout(searchTimeout);
                adsSearchQuery = e.target.value.trim();
                searchTimeout = setTimeout(() => {
                    window.loadAdsData(1);
                }, 350);
            });

            // Refresh Event
            btnRefresh.addEventListener('click', () => {
                window.loadAdsData(adsCurrentPage);
            });

            // Submit Manual Ad
            formCreate.addEventListener('submit', async (e) => {
                e.preventDefault();
                const btnSubmit = document.getElementById('btn-save-manual-ad');
                btnSubmit.disabled = true;
                btnSubmit.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i> Menyimpan...';

                const payload = {
                    product_name: document.getElementById('manual-ad-name').value.trim(),
                    commission: document.getElementById('manual-ad-comm').value.trim(),
                    product_link: document.getElementById('manual-ad-prod').value.trim(),
                    extra_commission_link: document.getElementById('manual-ad-extra').value.trim(),
                    active_link_type: document.getElementById('manual-ad-active').value
                };

                try {
                    const res = await fetch('api/admin/ads/create', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(payload)
                    });
                    const json = await res.json();
                    if (!json.success) throw new Error(json.message);

                    showToast(json.message);
                    modalCreate.classList.remove('active');
                    formCreate.reset();
                    window.loadAdsData(1);
                } catch (err) {
                    alert('Gagal menambah iklan: ' + err.message);
                } finally {
                    btnSubmit.disabled = false;
                    btnSubmit.innerHTML = '<i class="fa-solid fa-floppy-disk"></i> <span>Simpan Iklan</span>';
                }
            });

            // Drag and Drop & File Upload for Bulk
            dropZone.addEventListener('click', () => inputFileCsv.click());
            dropZone.addEventListener('dragover', (e) => {
                e.preventDefault();
                dropZone.style.borderColor = 'var(--accent)';
            });
            dropZone.addEventListener('dragleave', () => {
                dropZone.style.borderColor = 'var(--border-medium)';
            });
            dropZone.addEventListener('drop', (e) => {
                e.preventDefault();
                dropZone.style.borderColor = 'var(--border-medium)';
                if (e.dataTransfer.files.length > 0) {
                    handleCsvUpload(e.dataTransfer.files[0]);
                }
            });

            inputFileCsv.addEventListener('change', (e) => {
                if (e.target.files.length > 0) {
                    handleCsvUpload(e.target.files[0]);
                }
            });

            btnReupload.addEventListener('click', () => {
                resetBulkModal();
                inputFileCsv.click();
            });

            function resetBulkModal() {
                bulkUploadToken = null;
                stepUpload.style.display = 'block';
                stepPreview.style.display = 'none';
                btnSubmitBulk.style.display = 'none';
                inputFileCsv.value = '';
                previewHead.innerHTML = '';
                previewBody.innerHTML = '';
                dropZone.innerHTML = `
                    <i class="fa-solid fa-cloud-arrow-up" style="font-size:32px; color:var(--text-muted); margin-bottom:10px;"></i>
                    <p style="font-size:13px; font-weight:600; margin-bottom:4px;">Klik untuk memilih berkas atau seret file ke sini</p>
                    <p style="font-size:12px; color:var(--text-muted);">Mendukung format .csv, .tsv, atau berkas tabel teks (cth: sample-iklan.csv)</p>
                `;
            }

            async function handleCsvUpload(file) {
                const formData = new FormData();
                formData.append('file', file);

                dropZone.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin" style="font-size:32px; color:var(--accent); margin-bottom:10px;"></i><p>Membaca dan memvalidasi berkas CSV...</p>';

                try {
                    const res = await fetch('api/admin/ads/bulk-preview', {
                        method: 'POST',
                        body: formData
                    });
                    const json = await res.json();
                    if (!json.success) throw new Error(json.message);

                    const data = json.data;
                    bulkUploadToken = data.token;

                    // Render Preview Table
                    previewSummary.textContent = `Preview Berkas: ${data.total_rows} baris data ditemukan (5 sampel teratas):`;

                    // Render Headers
                    let headHtml = '<tr>';
                    data.headers.forEach((h, idx) => {
                        headHtml += `<th>[Kolom ${idx+1}] ${escapeHtml(h)}</th>`;
                    });
                    headHtml += '</tr>';
                    previewHead.innerHTML = headHtml;

                    // Render Sample Rows
                    let bodyHtml = '';
                    data.preview_rows.forEach(row => {
                        bodyHtml += '<tr>';
                        data.headers.forEach((h, colIdx) => {
                            const cell = row[colIdx] !== undefined ? String(row[colIdx]) : '';
                            const shortCell = cell.length > 30 ? cell.substring(0, 30) + '...' : cell;
                            bodyHtml += `<td title="${escapeHtml(cell)}">${escapeHtml(shortCell)}</td>`;
                        });
                        bodyHtml += '</tr>';
                    });
                    previewBody.innerHTML = bodyHtml;

                    // Populate Select Options
                    const populateSelect = (selectEl, selectedIndex) => {
                        selectEl.innerHTML = '<option value="">-- Lewati / Kosongkan --</option>';
                        data.headers.forEach((h, idx) => {
                            const opt = document.createElement('option');
                            opt.value = idx;
                            opt.textContent = `Kolom ${idx+1}: ${h}`;
                            if (selectedIndex !== null && Number(selectedIndex) === idx) {
                                opt.selected = true;
                            }
                            selectEl.appendChild(opt);
                        });
                    };

                    const sug = data.suggested_mapping || {};
                    populateSelect(mapColName, sug.product_name);
                    populateSelect(mapColComm, sug.commission);
                    populateSelect(mapColProd, sug.product_link);
                    populateSelect(mapColExtra, sug.extra_commission_link);

                    // Switch view to preview
                    stepUpload.style.display = 'none';
                    stepPreview.style.display = 'block';
                    btnSubmitBulk.style.display = 'inline-flex';
                    btnBulkText.textContent = `Mulai Impor (${data.total_rows} Produk)`;

                } catch (err) {
                    alert('Gagal memproses berkas: ' + err.message);
                    resetBulkModal();
                }
            }

            // Submit Bulk Import
            btnSubmitBulk.addEventListener('click', async () => {
                if (!bulkUploadToken) return;

                const nameIdx = mapColName.value;
                const commIdx = mapColComm.value;

                if (nameIdx === '') {
                    alert('Kolom "Nama Produk" wajib dipetakan!');
                    mapColName.focus();
                    return;
                }
                if (commIdx === '') {
                    alert('Kolom "Komisi (Rp.)" wajib dipetakan!');
                    mapColComm.focus();
                    return;
                }

                const mapping = {
                    product_name: Number(nameIdx),
                    commission: Number(commIdx),
                    product_link: mapColProd.value !== '' ? Number(mapColProd.value) : null,
                    extra_commission_link: mapColExtra.value !== '' ? Number(mapColExtra.value) : null
                };

                btnSubmitBulk.disabled = true;
                btnSubmitBulk.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i> Mengimpor ke database...';

                try {
                    const res = await fetch('api/admin/ads/bulk-import', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ token: bulkUploadToken, mapping })
                    });
                    const json = await res.json();
                    if (!json.success) throw new Error(json.message);

                    showToast(json.message);
                    modalBulk.classList.remove('active');
                    resetBulkModal();
                    window.loadAdsData(1);
                } catch (err) {
                    alert('Gagal mengimpor data: ' + err.message);
                } finally {
                    btnSubmitBulk.disabled = false;
                    btnSubmitBulk.innerHTML = '<i class="fa-solid fa-file-import"></i> <span id="btn-bulk-text">Mulai Impor Produk</span>';
                }
            });
        }

        // -------------------------------------------------------------
        // Utility
        // -------------------------------------------------------------
        function showToast(msg) {
            const toast = document.createElement('div');
            toast.className = 'toast-msg';
            toast.textContent = msg;
            document.body.appendChild(toast);
            setTimeout(() => {
                toast.style.opacity = '0';
                setTimeout(() => toast.remove(), 300);
            }, 3500);
        }

        function escapeHtml(str) {
            if (!str) return '';
            return String(str)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }
    });
    </script>
</body>
</html>
