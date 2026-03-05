<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Lost & Found Mediator</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
        }

        .dashboard {
            width: 100%;
            min-height: 100vh;
        }

        .header {
            background: white;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .header-content {
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .user-details {
            text-align: right;
        }

        .user-name {
            font-weight: bold;
            color: #333;
        }

        .user-role {
            font-size: 12px;
            color: #667eea;
            font-weight: 600;
        }

        .logout-btn {
            padding: 8px 15px;
            background: #f0f0f0;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .logout-btn:hover {
            background: #e0e0e0;
        }

        .nav-tabs {
            background: white;
            padding: 0 20px;
            border-bottom: 1px solid #ddd;
            display: flex;
            gap: 5px;
            overflow-x: auto;
        }

        .nav-tabs button {
            padding: 15px 25px;
            border: none;
            background: none;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            color: #666;
            position: relative;
            transition: all 0.3s;
            white-space: nowrap;
        }

        .nav-tabs button:hover {
            color: #667eea;
        }

        .nav-tabs button.active {
            color: #667eea;
        }

        .nav-tabs button.active::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .content {
            max-width: 1400px;
            margin: 20px auto;
            padding: 20px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            transition: transform 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
        }

        .stat-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .stat-icon i {
            color: white;
            font-size: 20px;
        }

        .stat-label {
            color: #666;
            font-size: 13px;
        }

        .stat-number {
            font-size: 24px;
            font-weight: bold;
            color: #333;
        }

        .filters {
            background: white;
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 20px;
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            align-items: center;
        }

        .filters input, .filters select {
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
            min-width: 200px;
        }

        .filters button {
            padding: 10px 20px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .filters button:hover {
            background: #5a67d8;
        }

        .items-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .item-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            border-left: 5px solid;
            transition: transform 0.3s;
        }

        .item-card:hover {
            transform: translateY(-5px);
        }

        .item-card.lost { border-left-color: #e74c3c; }
        .item-card.found { border-left-color: #27ae60; }

        .item-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            flex-wrap: wrap;
            gap: 10px;
        }

        .item-type {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            color: white;
        }

        .type-lost { background: #e74c3c; }
        .type-found { background: #27ae60; }

        .item-status {
            padding: 3px 10px;
            border-radius: 15px;
            font-size: 11px;
            font-weight: bold;
            background: #f0f0f0;
        }

        .status-pending { background: #f39c12; color: white; }
        .status-open { background: #3498db; color: white; }
        .status-approved { background: #27ae60; color: white; }
        .status-rejected { background: #e74c3c; color: white; }
        .status-resolved { background: #2ecc71; color: white; }
        .status-admin_approval { background: #9b59b6; color: white; }
        .status-claimed { background: #f39c12; color: white; }
        .status-matching { background: #3498db; color: white; }
        .status-pending_match { background: #9b59b6; color: white; }

        .item-title {
            font-size: 18px;
            font-weight: bold;
            margin: 10px 0;
            color: #333;
        }

        .item-location {
            color: #666;
            font-size: 14px;
            margin: 5px 0;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .item-reporter {
            color: #999;
            font-size: 13px;
            margin: 5px 0;
        }

        .item-images {
            display: flex;
            gap: 5px;
            margin: 10px 0;
            overflow-x: auto;
            padding: 5px 0;
        }

        .item-thumbnail {
            width: 60px;
            height: 60px;
            border-radius: 5px;
            object-fit: cover;
            cursor: pointer;
            border: 1px solid #ddd;
        }

        .item-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
            flex-wrap: wrap;
        }

        button {
            padding: 8px 15px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 500;
            font-size: 12px;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        button.primary { background: #3498db; color: white; }
        button.success { background: #27ae60; color: white; }
        button.warning { background: #f39c12; color: white; }
        button.danger { background: #e74c3c; color: white; }
        button.info { background: #00bcd4; color: white; }
        button.secondary { background: #95a5a6; color: white; }

        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 10px rgba(0,0,0,0.1);
        }

        .table-container {
            background: white;
            border-radius: 15px;
            padding: 20px;
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            background: #f8f9fa;
            padding: 12px;
            text-align: left;
            font-weight: 600;
            color: #333;
        }

        td {
            padding: 12px;
            border-bottom: 1px solid #eee;
        }

        tr:hover {
            background: #f8f9fa;
        }

        .badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: bold;
            color: white;
            display: inline-block;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            backdrop-filter: blur(5px);
        }

        .modal-content {
            background: white;
            max-width: 600px;
            margin: 50px auto;
            padding: 30px;
            border-radius: 20px;
            max-height: 80vh;
            overflow-y: auto;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
            color: #333;
        }

        .form-group input, .form-group select, .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
        }

        .form-row {
            display: flex;
            gap: 15px;
            margin-bottom: 15px;
        }

        .form-row .form-group {
            flex: 1;
            margin-bottom: 0;
        }

        .toast {
            position: fixed;
            bottom: 20px;
            right: 20px;
            padding: 15px 25px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.2);
            display: none;
            align-items: center;
            gap: 10px;
            z-index: 2000;
        }

        .toast.success { border-left: 4px solid #27ae60; }
        .toast.error { border-left: 4px solid #e74c3c; }
        .toast.warning { border-left: 4px solid #f39c12; }
        .toast.info { border-left: 4px solid #3498db; }

        .loading-spinner {
            text-align: center;
            padding: 40px;
        }

        .empty-state {
            text-align: center;
            padding: 40px;
            color: #999;
        }

        .pagination {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 20px;
        }

        .pagination button {
            padding: 8px 12px;
            background: white;
            border: 1px solid #ddd;
            border-radius: 5px;
            cursor: pointer;
        }

        .pagination button.active {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }

        .pagination button:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .search-box {
            position: relative;
            flex: 1;
        }

        .search-box i {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #999;
        }

        .search-box input {
            width: 100%;
            padding: 10px 10px 10px 35px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
        }

        .tab-badge {
            background: #e74c3c;
            color: white;
            border-radius: 10px;
            padding: 2px 6px;
            font-size: 10px;
            margin-left: 5px;
        }

        .message-preview {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 8px;
            margin: 10px 0;
            max-height: 100px;
            overflow-y: auto;
            font-size: 13px;
            border-left: 3px solid #667eea;
        }

        .image-gallery {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin: 10px 0;
        }

        .gallery-image {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 5px;
            cursor: pointer;
            border: 2px solid #ddd;
            transition: all 0.3s;
        }

        .gallery-image:hover {
            transform: scale(1.05);
            border-color: #667eea;
        }

        .fullscreen-image {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.9);
            z-index: 3000;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .fullscreen-image img {
            max-width: 90%;
            max-height: 90%;
            object-fit: contain;
        }

        .fullscreen-close {
            position: absolute;
            top: 20px;
            right: 20px;
            color: white;
            font-size: 30px;
            cursor: pointer;
        }

        .stats-mini {
            display: flex;
            gap: 15px;
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }

        .progress-bar {
            width: 100%;
            height: 4px;
            background: #f0f0f0;
            border-radius: 2px;
            margin: 5px 0;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 2px;
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="dashboard" id="dashboard">
        <div class="header">
            <div class="header-content">
                <h2 style="color: #333;">
                    <i class="fas fa-shield-alt" style="color: #667eea; margin-right: 10px;"></i>
                    Lost & Found Mediator - Admin Panel
                </h2>
                <div class="user-info">
                    <div class="user-details">
                        <div class="user-name" id="adminName">Loading...</div>
                        <div class="user-role" id="adminRole">Administrator</div>
                    </div>
                    <button class="logout-btn" onclick="handleLogout()">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </button>
                </div>
            </div>
        </div>

        <div class="nav-tabs">
            <button class="active" onclick="switchTab('dashboard')" id="tabDashboard">
                <i class="fas fa-chart-pie"></i> Dashboard
            </button>
            <button onclick="switchTab('items')" id="tabItems">
                <i class="fas fa-box"></i> Items
                <span class="tab-badge" id="pendingItemsBadge" style="display: none;">0</span>
            </button>
            <button onclick="switchTab('claims')" id="tabClaims">
                <i class="fas fa-hand-holding-heart"></i> Claims
                <span class="tab-badge" id="pendingClaimsBadge" style="display: none;">0</span>
            </button>
            <button onclick="switchTab('matches')" id="tabMatches">
                <i class="fas fa-link"></i> Matches
                <span class="tab-badge" id="pendingMatchesBadge" style="display: none;">0</span>
            </button>
            <button onclick="switchTab('users')" id="tabUsers">
                <i class="fas fa-users"></i> Users
            </button>
            <button onclick="switchTab('messages')" id="tabMessages">
                <i class="fas fa-envelope"></i> Messages
            </button>
            <button onclick="switchTab('reports')" id="tabReports">
                <i class="fas fa-flag"></i> Reports
            </button>
            <button onclick="switchTab('logs')" id="tabLogs">
                <i class="fas fa-history"></i> Activity Logs
            </button>
            <button onclick="switchTab('settings')" id="tabSettings">
                <i class="fas fa-cog"></i> Settings
            </button>
        </div>

        <div class="content">
            <div id="dashboardView">
                <div class="stats-grid" id="dashboardStats">
                </div>
                
                <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px;">
                    <div class="table-container">
                        <h3><i class="fas fa-clock"></i> Recent Pending Items</h3>
                        <div id="recentPendingItems"></div>
                    </div>
                    <div class="table-container">
                        <h3><i class="fas fa-chart-line"></i> Quick Actions</h3>
                        <div style="padding: 15px;">
                            <button class="success" style="width: 100%; margin-bottom: 10px;" onclick="switchTab('items')">
                                <i class="fas fa-box"></i> Review Pending Items
                            </button>
                            <button class="warning" style="width: 100%; margin-bottom: 10px;" onclick="switchTab('claims')">
                                <i class="fas fa-hand-holding-heart"></i> Review Claims
                            </button>
                            <button class="info" style="width: 100%; margin-bottom: 10px;" onclick="switchTab('matches')">
                                <i class="fas fa-link"></i> Review Matches
                            </button>
                            <button class="primary" style="width: 100%;" onclick="window.open('https://astufindit.x10.mx/index/phpmyadmin', '_blank')">
                                <i class="fas fa-database"></i> Database Admin
                            </button>
                        </div>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 20px;">
                    <div class="table-container">
                        <h3><i class="fas fa-calendar"></i> Recent Activity</h3>
                        <div id="recentActivity"></div>
                    </div>
                    <div class="table-container">
                        <h3><i class="fas fa-chart-pie"></i> System Overview</h3>
                        <div id="systemOverview"></div>
                    </div>
                </div>
            </div>

            <div id="itemsView" style="display: none;">
                <div class="filters">
                    <select id="itemTypeFilter">
                        <option value="">All Types</option>
                        <option value="lost">Lost</option>
                        <option value="found">Found</option>
                    </select>
                    <select id="itemStatusFilter">
                        <option value="">All Status</option>
                        <option value="pending">Pending</option>
                        <option value="admin_approval">Admin Approval</option>
                        <option value="open">Open</option>
                        <option value="claimed">Claimed</option>
                        <option value="resolved">Resolved</option>
                        <option value="rejected">Rejected</option>
                        <option value="matching">Matching</option>
                        <option value="pending_match">Pending Match</option>
                    </select>
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" id="itemSearch" placeholder="Search by title, description...">
                    </div>
                    <button onclick="loadItems(1)"><i class="fas fa-search"></i> Search</button>
                    <button class="secondary" onclick="resetItemFilters()"><i class="fas fa-undo"></i> Reset</button>
                </div>
                <div id="itemsList"></div>
                <div id="itemsPagination" class="pagination"></div>
            </div>

            <div id="claimsView" style="display: none;">
                <div class="filters">
                    <select id="claimStatusFilter">
                        <option value="">All Status</option>
                        <option value="pending">Pending</option>
                        <option value="approved">Approved</option>
                        <option value="rejected">Rejected</option>
                    </select>
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" id="claimSearch" placeholder="Search by item title, claimant...">
                    </div>
                    <button onclick="loadClaims(1)"><i class="fas fa-search"></i> Search</button>
                    <button class="secondary" onclick="resetClaimFilters()"><i class="fas fa-undo"></i> Reset</button>
                </div>
                <div id="claimsList"></div>
                <div id="claimsPagination" class="pagination"></div>
            </div>
<!-- matches -->
            <div id="matchesView" style="display: none;">
                <div class="filters">
                    <select id="matchStatusFilter">
                        <option value="">All Status</option>
                        <option value="pending">Pending</option>
                        <option value="confirmed">Confirmed</option>
                        <option value="rejected">Rejected</option>
                    </select>
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" id="matchSearch" placeholder="Search by item titles...">
                    </div>
                    <button onclick="loadMatches(1)"><i class="fas fa-search"></i> Search</button>
                    <button class="secondary" onclick="resetMatchFilters()"><i class="fas fa-undo"></i> Reset</button>
                </div>
                <div id="matchesList"></div>
                <div id="matchesPagination" class="pagination"></div>
            </div>

            <div id="usersView" style="display: none;">
                <div class="filters">
                    <select id="userRoleFilter">
                        <option value="">All Roles</option>
                        <option value="student">Student</option>
                        <option value="admin">Admin</option>
                    </select>
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" id="userSearch" placeholder="Search by name, student ID, phone...">
                    </div>
                    <button onclick="loadUsers(1)"><i class="fas fa-search"></i> Search</button>
                    <button class="secondary" onclick="resetUserFilters()"><i class="fas fa-undo"></i> Reset</button>
                    <button class="success" onclick="openAddUserModal()"><i class="fas fa-plus"></i> Add User</button>
                </div>
                <div id="usersList"></div>
                <div id="usersPagination" class="pagination"></div>
            </div>

            <!-- ========== MESSAGES VIEW ========== -->
            <div id="messagesView" style="display: none;">
                <div class="filters">
                    <select id="messageStatusFilter">
                        <option value="">All Messages</option>
                        <option value="unread">Unread Only</option>
                    </select>
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" id="messageSearch" placeholder="Search messages...">
                    </div>
                    <button onclick="loadMessages(1)"><i class="fas fa-search"></i> Search</button>
                    <button class="success" onclick="openSendMessageModal()"><i class="fas fa-plus"></i> New Message</button>
                </div>
                <div id="messagesList"></div>
                <div id="messagesPagination" class="pagination"></div>
            </div>

            <!-- ========== REPORTS VIEW ========== -->
            <div id="reportsView" style="display: none;">
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-icon"><i class="fas fa-calendar-day"></i></div>
                            <div>
                                <div class="stat-label">Today</div>
                                <div class="stat-number" id="todayCount">0</div>
                            </div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-icon"><i class="fas fa-calendar-week"></i></div>
                            <div>
                                <div class="stat-label">This Week</div>
                                <div class="stat-number" id="weekCount">0</div>
                            </div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-icon"><i class="fas fa-calendar-alt"></i></div>
                            <div>
                                <div class="stat-label">This Month</div>
                                <div class="stat-number" id="monthCount">0</div>
                            </div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-icon"><i class="fas fa-chart-bar"></i></div>
                            <div>
                                <div class="stat-label">Total</div>
                                <div class="stat-number" id="totalCount">0</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="table-container">
                    <h3>Generate Reports</h3>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin-top: 20px;">
                        <div class="form-group">
                            <label>Report Type</label>
                            <select id="reportType">
                                <option value="items">Items Report</option>
                                <option value="users">Users Report</option>
                                <option value="claims">Claims Report</option>
                                <option value="matches">Matches Report</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Date Range</label>
                            <select id="reportDateRange">
                                <option value="today">Today</option>
                                <option value="yesterday">Yesterday</option>
                                <option value="week">This Week</option>
                                <option value="month">This Month</option>
                                <option value="year">This Year</option>
                                <option value="custom">Custom Range</option>
                            </select>
                        </div>
                        <div class="form-group" id="customDateRange" style="display: none;">
                            <label>From - To</label>
                            <div style="display: flex; gap: 10px;">
                                <input type="date" id="dateFrom">
                                <input type="date" id="dateTo">
                            </div>
                        </div>
                    </div>
                    <div style="display: flex; gap: 10px; margin-top: 20px;">
                        <button class="primary" onclick="generateReport('preview')">
                            <i class="fas fa-eye"></i> Preview Report
                        </button>
                        <button class="success" onclick="generateReport('csv')">
                            <i class="fas fa-file-csv"></i> Download CSV
                        </button>
                        <button class="info" onclick="generateReport('pdf')">
                            <i class="fas fa-file-pdf"></i> Download PDF
                        </button>
                        <button class="secondary" onclick="printReport()">
                            <i class="fas fa-print"></i> Print
                        </button>
                    </div>
                </div>

                <div class="table-container" id="reportPreview" style="margin-top: 20px; display: none;">
                    <h3>Report Preview</h3>
                    <div id="reportData"></div>
                </div>
            </div>

            <!-- ========== LOGS VIEW ========== -->
            <div id="logsView" style="display: none;">
                <div class="filters">
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" id="logSearch" placeholder="Search logs...">
                    </div>
                    <button onclick="loadLogs(1)"><i class="fas fa-search"></i> Search</button>
                    <button class="danger" onclick="clearLogs()"><i class="fas fa-trash"></i> Clear Logs</button>
                </div>
                <div id="logsList"></div>
                <div id="logsPagination" class="pagination"></div>
            </div>

            <!-- ========== SETTINGS VIEW ========== -->
            <div id="settingsView" style="display: none;">
                <div class="table-container">
                    <h3><i class="fas fa-user-cog"></i> Profile Settings</h3>
                    <div style="max-width: 500px; margin: 20px 0;">
                        <div class="form-group">
                            <label>Full Name</label>
                            <input type="text" id="settingsFullName" value="">
                        </div>
                        <div class="form-group">
                            <label>Student ID</label>
                            <input type="text" id="settingsStudentId" value="" disabled>
                        </div>
                        <div class="form-group">
                            <label>Phone</label>
                            <input type="text" id="settingsPhone" value="">
                        </div>
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" id="settingsEmail" value="">
                        </div>
                        <button class="primary" onclick="updateProfile()"><i class="fas fa-save"></i> Update Profile</button>
                    </div>
                </div>

                <div class="table-container" style="margin-top: 20px;">
                    <h3><i class="fas fa-lock"></i> Change Password</h3>
                    <div style="max-width: 500px; margin: 20px 0;">
                        <div class="form-group">
                            <label>Current Password</label>
                            <input type="password" id="currentPassword">
                        </div>
                        <div class="form-group">
                            <label>New Password</label>
                            <input type="password" id="newPassword">
                        </div>
                        <div class="form-group">
                            <label>Confirm New Password</label>
                            <input type="password" id="confirmPassword">
                        </div>
                        <button class="warning" onclick="changePassword()"><i class="fas fa-key"></i> Change Password</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Review Item Modal -->
    <div class="modal" id="reviewItemModal">
        <div class="modal-content">
            <h3><i class="fas fa-clipboard-check"></i> Review Item</h3>
            <div id="reviewItemDetails"></div>
            
            <div class="form-group">
                <label>Admin Notes <small>(will be sent to user)</small></label>
                <textarea id="itemAdminNotes" rows="4" placeholder="Add notes about this review..."></textarea>
            </div>

            <div class="form-group" style="display: flex; align-items: center; gap: 10px;">
                <input type="checkbox" id="notifyUser" checked>
                <label for="notifyUser">Send notification to user</label>
            </div>

            <div id="itemMessagePreview" class="message-preview" style="display: none;"></div>

            <div style="display: flex; gap: 10px; justify-content: flex-end;">
                <button class="danger" onclick="reviewItem('reject')"><i class="fas fa-times"></i> Reject</button>
                <button class="warning" onclick="reviewItem('pending')"><i class="fas fa-clock"></i> Keep Pending</button>
                <button class="success" onclick="reviewItem('approve')"><i class="fas fa-check"></i> Approve</button>
                <button onclick="closeModal('reviewItemModal')">Cancel</button>
            </div>
        </div>
    </div>

    <!-- Review Claim Modal -->
    <div class="modal" id="reviewClaimModal">
        <div class="modal-content">
            <h3><i class="fas fa-gavel"></i> Review Claim</h3>
            <div id="reviewClaimDetails"></div>
            
            <div class="form-group">
                <label>Admin Notes</label>
                <textarea id="claimAdminNotes" rows="4" placeholder="Add notes about this claim..."></textarea>
            </div>

            <div style="display: flex; gap: 10px; justify-content: flex-end;">
                <button class="danger" onclick="reviewClaim('reject')"><i class="fas fa-times"></i> Reject</button>
                <button class="success" onclick="reviewClaim('approve')"><i class="fas fa-check"></i> Approve</button>
                <button onclick="closeModal('reviewClaimModal')">Cancel</button>
            </div>
        </div>
    </div>

    <!-- Review Match Modal -->
    <div class="modal" id="reviewMatchModal">
        <div class="modal-content">
            <h3><i class="fas fa-link"></i> Review Match</h3>
            <div id="reviewMatchDetails"></div>
            
            <div class="form-group">
                <label>Admin Notes</label>
                <textarea id="matchAdminNotes" rows="4" placeholder="Add notes about this match..."></textarea>
            </div>

            <div style="display: flex; gap: 10px; justify-content: flex-end;">
                <button class="danger" onclick="updateMatch('rejected')"><i class="fas fa-times"></i> Reject</button>
                <button class="warning" onclick="updateMatch('pending')"><i class="fas fa-clock"></i> Keep Pending</button>
                <button class="success" onclick="updateMatch('confirmed')"><i class="fas fa-check"></i> Confirm</button>
                <button onclick="closeModal('reviewMatchModal')">Cancel</button>
            </div>
        </div>
    </div>

    <!-- Edit User Modal -->
    <div class="modal" id="editUserModal">
        <div class="modal-content">
            <h3><i class="fas fa-user-edit"></i> Edit User</h3>
            <form id="editUserForm">
                <input type="hidden" id="editUserId">
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" id="editFullName" required>
                </div>
                <div class="form-group">
                    <label>Student ID</label>
                    <input type="text" id="editStudentId" required>
                </div>
                <div class="form-group">
                    <label>Phone</label>
                    <input type="text" id="editPhone">
                </div>
                <div class="form-group">
                    <label>Role</label>
                    <select id="editRole">
                        <option value="student">Student</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>New Password (leave blank to keep current)</label>
                    <input type="password" id="editPassword">
                </div>
            </form>
            <div style="display: flex; gap: 10px; justify-content: flex-end;">
                <button class="danger" onclick="deleteUser()"><i class="fas fa-trash"></i> Delete</button>
                <button class="success" onclick="updateUser()"><i class="fas fa-save"></i> Save</button>
                <button onclick="closeModal('editUserModal')">Cancel</button>
            </div>
        </div>
    </div>

    <!-- Add User Modal -->
    <div class="modal" id="addUserModal">
        <div class="modal-content">
            <h3><i class="fas fa-user-plus"></i> Add New User</h3>
            <form id="addUserForm">
                <div class="form-group">
                    <label>Full Name *</label>
                    <input type="text" id="addFullName" required>
                </div>
                <div class="form-group">
                    <label>Student ID *</label>
                    <input type="text" id="addStudentId" required>
                </div>
                <div class="form-group">
                    <label>Phone</label>
                    <input type="text" id="addPhone">
                </div>
                <div class="form-group">
                    <label>Password *</label>
                    <input type="password" id="addPassword" required>
                </div>
                <div class="form-group">
                    <label>Confirm Password *</label>
                    <input type="password" id="addConfirmPassword" required>
                </div>
                <div class="form-group">
                    <label>Role</label>
                    <select id="addRole">
                        <option value="student">Student</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
            </form>
            <div style="display: flex; gap: 10px; justify-content: flex-end;">
                <button class="success" onclick="addUser()"><i class="fas fa-user-plus"></i> Add User</button>
                <button onclick="closeModal('addUserModal')">Cancel</button>
            </div>
        </div>
    </div>

    <!-- Send Message Modal -->
    <div class="modal" id="sendMessageModal">
        <div class="modal-content">
            <h3><i class="fas fa-envelope"></i> Send Message</h3>
            
            <div class="form-group">
                <label>Select User</label>
                <select id="messageUserSelect" style="width: 100%;">
                    <option value="">Loading users...</option>
                </select>
            </div>

            <div class="form-group">
                <label>Subject</label>
                <input type="text" id="messageSubject" placeholder="Message subject" value="Message from Admin">
            </div>

            <div class="form-group">
                <label>Message</label>
                <textarea id="messageContent" rows="5" placeholder="Type your message here..."></textarea>
            </div>

            <div class="form-group">
                <label>Message Type</label>
                <select id="messageType">
                    <option value="general">General Message</option>
                    <option value="notification">Notification</option>
                    <option value="alert">Alert</option>
                </select>
            </div>

            <div style="display: flex; gap: 10px; justify-content: flex-end;">
                <button class="primary" onclick="sendMessage()"><i class="fas fa-paper-plane"></i> Send</button>
                <button onclick="closeModal('sendMessageModal')">Cancel</button>
            </div>
        </div>
    </div>

    <!-- View Message Modal -->
    <div class="modal" id="viewMessageModal">
        <div class="modal-content">
            <h3><i class="fas fa-envelope-open"></i> Message Details</h3>
            <div id="messageDetails"></div>
            <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px;">
                <button class="danger" onclick="deleteMessage()"><i class="fas fa-trash"></i> Delete</button>
                <button onclick="closeModal('viewMessageModal')">Close</button>
            </div>
        </div>
    </div>

    <!-- View Item Modal -->
    <div class="modal" id="viewItemModal">
        <div class="modal-content">
            <h3><i class="fas fa-box"></i> Item Details</h3>
            <div id="itemDetails"></div>
            <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px;">
                <button class="primary" onclick="closeModal('viewItemModal')">Close</button>
            </div>
        </div>
    </div>

    <!-- Confirmation Modal -->
    <div class="modal" id="confirmModal">
        <div class="modal-content" style="max-width: 400px;">
            <h3><i class="fas fa-question-circle"></i> Confirm Action</h3>
            <p id="confirmMessage">Are you sure you want to proceed?</p>
            <div style="display: flex; gap: 10px; justify-content: flex-end;">
                <button class="danger" id="confirmBtn" onclick="executeConfirm()">Yes</button>
                <button onclick="closeModal('confirmModal')">No</button>
            </div>
        </div>
    </div>

    <!-- Fullscreen Image Viewer -->
    <div class="fullscreen-image" id="fullscreenImage" style="display: none;" onclick="closeFullscreen()">
        <span class="fullscreen-close">&times;</span>
        <img id="fullscreenImg" src="" alt="">
    </div>

    <!-- Toast Notification -->
    <div class="toast" id="toast">
        <i class="fas" id="toastIcon"></i>
        <span id="toastMessage"></span>
    </div>

    <script> 
        const API_URL = 'https://astufindit.x10.mx/index/api.php';
        let currentAdmin = null;
        let currentItem = null;
        let currentClaim = null;
        let currentMatch = null;
        let currentUser = null;
        let currentMessage = null;
 
        let currentPage = {
            items: 1,
            claims: 1,
            matches: 1,
            users: 1,
            messages: 1,
            logs: 1
        };

        let totalPages = {
            items: 1,
            claims: 1,
            matches: 1,
            users: 1,
            messages: 1,
            logs: 1
        };

        let confirmCallback = null;
        
        function getStoredAdmin() {
            const local = localStorage.getItem('adminData');
            const session = sessionStorage.getItem('adminData');
            return local ? JSON.parse(local) : session ? JSON.parse(session) : null;
        }
        const savedAdmin = getStoredAdmin();
        if (!savedAdmin) {
            window.location.href = 'login.php';
        } else {
            currentAdmin = savedAdmin;
            document.getElementById('adminName').textContent = currentAdmin.full_name;
            document.getElementById('adminRole').textContent = 'Administrator';
             
            loadDashboardData();
            updateBadges();
             
            document.getElementById('settingsFullName').value = currentAdmin.full_name || '';
            document.getElementById('settingsStudentId').value = currentAdmin.student_id || '';
            document.getElementById('settingsPhone').value = currentAdmin.phone || '';
            document.getElementById('settingsEmail').value = currentAdmin.email || '';
        }
        function buildUrl(action, params = {}) {
            const url = new URL(`${API_URL}?action=${action}`);
             
            if (currentAdmin) {
                if (currentAdmin.student_id) {
                    url.searchParams.append('admin_id', currentAdmin.student_id);
                } else if (currentAdmin.id) {
                    url.searchParams.append('admin_id', currentAdmin.id);
                } else if (currentAdmin.user_string_id) {
                    url.searchParams.append('admin_id', currentAdmin.user_string_id);
                }
            } 
            Object.keys(params).forEach(key => {
                if (params[key] !== undefined && params[key] !== null && params[key] !== '') {
                    url.searchParams.append(key, params[key]);
                }
            });
            
            return url.toString();
        }

        function showToast(message, type = 'success') {
            const toast = document.getElementById('toast');
            const icon = document.getElementById('toastIcon');
            const msg = document.getElementById('toastMessage');
            
            const icons = {
                success: 'fa-check-circle',
                error: 'fa-exclamation-circle',
                warning: 'fa-exclamation-triangle',
                info: 'fa-info-circle'
            };
            
            icon.className = `fas ${icons[type] || icons.info}`;
            msg.textContent = message;
            toast.className = `toast ${type}`;
            toast.style.display = 'flex';
            
            setTimeout(() => {
                toast.style.display = 'none';
            }, 3000);
        }

        function showLoading() { 
        }

        function hideLoading() { 
        }

        function formatDate(dateString) {
            if (!dateString) return 'N/A';
            try {
                const date = new Date(dateString);
                return date.toLocaleString();
            } catch {
                return dateString;
            }
        }

        function formatDateShort(dateString) {
            if (!dateString) return 'N/A';
            try {
                const date = new Date(dateString);
                return date.toLocaleDateString();
            } catch {
                return dateString;
            }
        }

        function timeAgo(dateString) {
            if (!dateString) return 'N/A';
            try {
                const date = new Date(dateString);
                const now = new Date();
                const seconds = Math.floor((now - date) / 1000);
                
                if (seconds < 60) return 'just now';
                if (seconds < 3600) return Math.floor(seconds / 60) + ' minutes ago';
                if (seconds < 86400) return Math.floor(seconds / 3600) + ' hours ago';
                if (seconds < 604800) return Math.floor(seconds / 86400) + ' days ago';
                return formatDateShort(dateString);
            } catch {
                return dateString;
            }
        }

        function processImagePath(imagePath) {
            if (!imagePath || imagePath === 'NULL') return [];
            
            let cleanPath = imagePath;
            if (cleanPath.startsWith("'") && cleanPath.endsWith("'")) {
                cleanPath = cleanPath.substring(1, cleanPath.length - 1);
            }
            
            return cleanPath.split('|').filter(p => p && p !== 'NULL');
        }

        function showFullscreenImage(src) {
            document.getElementById('fullscreenImg').src = src;
            document.getElementById('fullscreenImage').style.display = 'flex';
        }

        function closeFullscreen() {
            document.getElementById('fullscreenImage').style.display = 'none';
        }

        function confirmAction(message, callback) {
            document.getElementById('confirmMessage').textContent = message;
            confirmCallback = callback;
            document.getElementById('confirmModal').style.display = 'block';
        }

        function executeConfirm() {
            if (confirmCallback) {
                confirmCallback();
            }
            closeModal('confirmModal');
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        function handleLogout() {
            localStorage.removeItem('adminData');
            sessionStorage.removeItem('adminData');
            window.location.href = 'login.php';
        } 
        async function loadDashboardData() {
            try {
                showLoading();
                
                const response = await fetch(buildUrl('admin-dashboard-stats'));
                const data = await response.json();
                
                if (data.success) {
                    displayStats(data.stats);
                    
                    if (data.stats.recent_items) {
                        displayRecentItems(data.stats.recent_items);
                    }
                    
                    if (data.stats.recent_claims) {
                        displayRecentClaims(data.stats.recent_claims);
                    }
                    
                    displayRecentActivity(data.stats);
                    displaySystemOverview(data.stats);
                     
                    document.getElementById('pendingItemsBadge').textContent = data.stats.items?.pending_items || 0;
                    document.getElementById('pendingItemsBadge').style.display = data.stats.items?.pending_items ? 'inline' : 'none';
                    
                    document.getElementById('pendingClaimsBadge').textContent = data.stats.claims?.pending_claims || 0;
                    document.getElementById('pendingClaimsBadge').style.display = data.stats.claims?.pending_claims ? 'inline' : 'none';
                    
                    document.getElementById('pendingMatchesBadge').textContent = data.stats.matches?.pending_matches || 0;
                    document.getElementById('pendingMatchesBadge').style.display = data.stats.matches?.pending_matches ? 'inline' : 'none';
                } else {
                    showToast(data.message || 'Failed to load dashboard', 'error');
                }
                
                hideLoading();
            } catch (error) {
                hideLoading();
                showToast('Network error: ' + error.message, 'error');
            }
        }

        function displayStats(stats) {
            const container = document.getElementById('dashboardStats');
            
            container.innerHTML = `
                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-icon"><i class="fas fa-box"></i></div>
                        <div>
                            <div class="stat-label">Total Items</div>
                            <div class="stat-number">${stats.items?.total_items || 0}</div>
                        </div>
                    </div>
                    <div class="stats-mini">
                        <span>Lost: ${stats.items?.total_lost || 0}</span> | 
                        <span>Found: ${stats.items?.total_found || 0}</span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-icon"><i class="fas fa-clock"></i></div>
                        <div>
                            <div class="stat-label">Pending Review</div>
                            <div class="stat-number">${stats.items?.pending_items || 0}</div>
                        </div>
                    </div>
                    <div class="stats-mini">
                        <span>Admin Approval: ${stats.items?.admin_approval_items || 0}</span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-icon"><i class="fas fa-hand-holding-heart"></i></div>
                        <div>
                            <div class="stat-label">Claims</div>
                            <div class="stat-number">${stats.claims?.total_claims || 0}</div>
                        </div>
                    </div>
                    <div class="stats-mini">
                        <span>Pending: ${stats.claims?.pending_claims || 0}</span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-icon"><i class="fas fa-link"></i></div>
                        <div>
                            <div class="stat-label">Matches</div>
                            <div class="stat-number">${stats.matches?.total_matches || 0}</div>
                        </div>
                    </div>
                    <div class="stats-mini">
                        <span>Pending: ${stats.matches?.pending_matches || 0}</span> | 
                        <span>Avg: ${Math.round(stats.matches?.avg_confidence || 0)}%</span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-icon"><i class="fas fa-users"></i></div>
                        <div>
                            <div class="stat-label">Users</div>
                            <div class="stat-number">${stats.users?.total_users || 0}</div>
                        </div>
                    </div>
                    <div class="stats-mini">
                        <span>Students: ${stats.users?.students || 0}</span> | 
                        <span>Admins: ${stats.users?.admins || 0}</span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
                        <div>
                            <div class="stat-label">Resolved</div>
                            <div class="stat-number">${stats.items?.resolved_items || 0}</div>
                        </div>
                    </div>
                </div>
            `;
        }

        function displayRecentItems(items) {
            const container = document.getElementById('recentPendingItems');
            
            if (!items || items.length === 0) {
                container.innerHTML = '<div class="empty-state">No pending items</div>';
                return;
            }

            let html = '<table>';
            html += '<tr><th>Title</th><th>Type</th><th>Status</th><th>Date</th></tr>';
            
            items.slice(0, 5).forEach(item => {
                html += `
                    <tr>
                        <td>${item.title}</td>
                        <td><span class="badge" style="background: ${item.type === 'lost' ? '#e74c3c' : '#27ae60'}">${item.type}</span></td>
                        <td><span class="badge" style="background: #f39c12">${item.status}</span></td>
                        <td>${timeAgo(item.created_at)}</td>
                    </tr>
                `;
            });
            
            html += '</table>';
            container.innerHTML = html;
        }

        function displayRecentClaims(claims) {
            const container = document.getElementById('recentClaims');
            
            if (!claims || claims.length === 0) {
                container.innerHTML = '<div class="empty-state">No pending claims</div>';
                return;
            }

            let html = '<table>';
            html += '<tr><th>Item</th><th>Status</th><th>Date</th></tr>';
            
            claims.slice(0, 5).forEach(claim => {
                html += `
                    <tr>
                        <td>${claim.title || 'Unknown'}</td>
                        <td><span class="badge" style="background: #f39c12">${claim.status}</span></td>
                        <td>${timeAgo(claim.created_at)}</td>
                    </tr>
                `;
            });
            
            html += '</table>';
            container.innerHTML = html;
        }

        function displayRecentActivity(stats) {
            const container = document.getElementById('recentActivity');
            
            let activities = [];
            
            if (stats.recent_items) {
                stats.recent_items.forEach(item => {
                    activities.push({
                        type: 'item',
                        title: item.title,
                        status: item.status,
                        time: item.created_at,
                        icon: 'fa-box'
                    });
                });
            }
            
            if (stats.recent_claims) {
                stats.recent_claims.forEach(claim => {
                    activities.push({
                        type: 'claim',
                        title: claim.title || 'Claim',
                        status: claim.status,
                        time: claim.created_at,
                        icon: 'fa-hand-holding-heart'
                    });
                });
            }
            
            activities.sort((a, b) => new Date(b.time) - new Date(a.time));
            activities = activities.slice(0, 10);
            
            if (activities.length === 0) {
                container.innerHTML = '<div class="empty-state">No recent activity</div>';
                return;
            }

            let html = '<table>';
            html += '<tr><th>Activity</th><th>Status</th><th>Time</th></tr>';
            
            activities.forEach(act => {
                html += `
                    <tr>
                        <td><i class="fas ${act.icon}" style="margin-right: 5px;"></i> ${act.title}</td>
                        <td><span class="badge" style="background: ${act.status === 'pending' ? '#f39c12' : '#3498db'}">${act.status}</span></td>
                        <td>${timeAgo(act.time)}</td>
                    </tr>
                `;
            });
            
            html += '</table>';
            container.innerHTML = html;
        }

        function displaySystemOverview(stats) {
            const container = document.getElementById('systemOverview');
            
            const total = stats.items?.total_items || 0;
            const lost = stats.items?.total_lost || 0;
            const found = stats.items?.total_found || 0;
            const resolved = stats.items?.resolved_items || 0;
            
            const lostPercent = total ? (lost / total * 100) : 0;
            const foundPercent = total ? (found / total * 100) : 0;
            const resolvedPercent = total ? (resolved / total * 100) : 0;
            
            container.innerHTML = `
                <div style="padding: 10px;">
                    <p><strong>System Health:</strong> <span style="color: #27ae60;">✓ Online</span></p>
                    <p><strong>Database:</strong> Connected</p>
                    <p><strong>API Status:</strong> Operational</p>
                    
                    <div style="margin-top: 20px;">
                        <p><strong>Items Distribution</strong></p>
                        <div>Lost (${lost}) <div class="progress-bar"><div class="progress-fill" style="width: ${lostPercent}%; background: #e74c3c;"></div></div></div>
                        <div>Found (${found}) <div class="progress-bar"><div class="progress-fill" style="width: ${foundPercent}%; background: #27ae60;"></div></div></div>
                        <div>Resolved (${resolved}) <div class="progress-bar"><div class="progress-fill" style="width: ${resolvedPercent}%; background: #2ecc71;"></div></div></div>
                    </div>
                    
                    <div style="margin-top: 20px;">
                        <p><strong>Quick Stats</strong></p>
                        <p>Total Users: ${stats.users?.total_users || 0}</p>
                        <p>Total Claims: ${stats.claims?.total_claims || 0}</p>
                        <p>Total Matches: ${stats.matches?.total_matches || 0}</p>
                    </div>
                </div>
            `;
        }

        function updateBadges() {
            fetch(buildUrl('admin-dashboard-stats'))
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('pendingItemsBadge').textContent = data.stats.items?.pending_items || 0;
                        document.getElementById('pendingItemsBadge').style.display = data.stats.items?.pending_items ? 'inline' : 'none';
                        
                        document.getElementById('pendingClaimsBadge').textContent = data.stats.claims?.pending_claims || 0;
                        document.getElementById('pendingClaimsBadge').style.display = data.stats.claims?.pending_claims ? 'inline' : 'none';
                        
                        document.getElementById('pendingMatchesBadge').textContent = data.stats.matches?.pending_matches || 0;
                        document.getElementById('pendingMatchesBadge').style.display = data.stats.matches?.pending_matches ? 'inline' : 'none';
                    }
                })
                .catch(err => console.error('Failed to update badges:', err));
        } 
        async function loadItems(page = 1) {
            try {
                showLoading();
                
                const type = document.getElementById('itemTypeFilter').value;
                const status = document.getElementById('itemStatusFilter').value;
                const search = document.getElementById('itemSearch').value;
                
                const params = { page };
                if (type) params.type = type;
                if (status) params.status = status;
                if (search) params.search = search;
                
                const response = await fetch(buildUrl('admin-get-items', params));
                const data = await response.json();
                
                if (data.success) {
                    displayItems(data.items);
                    displayPagination('items', data.pagination || { current_page: page, total_pages: 1 });
                } else {
                    showToast(data.message || 'Failed to load items', 'error');
                }
                
                hideLoading();
            } catch (error) {
                hideLoading();
                showToast('Error: ' + error.message, 'error');
            }
        }

        function displayItems(items) {
            const container = document.getElementById('itemsList');
            
            if (!items || items.length === 0) {
                container.innerHTML = '<div class="empty-state"><i class="fas fa-box-open fa-3x"></i><br>No items found</div>';
                return;
            }

            container.innerHTML = '<div class="items-grid">' + items.map(item => {
                const images = processImagePath(item.image_path);
                const imageHtml = images.length > 0 ? 
                    `<div class="item-images">
                        ${images.slice(0, 3).map(img => 
                            `<img src="https://astufindit.x10.mx/index/${img}" class="item-thumbnail" onclick="showFullscreenImage('https://astufindit.x10.mx/index/${img}')">`
                        ).join('')}
                        ${images.length > 3 ? `<span class="badge">+${images.length - 3}</span>` : ''}
                    </div>` : '';

                return `
                    <div class="item-card ${item.type}">
                        <div class="item-header">
                            <span class="item-type type-${item.type}">${item.type.toUpperCase()}</span>
                            <span class="item-status status-${item.status}">${item.status}</span>
                        </div>
                        <div class="item-title">${item.title}</div>
                        <div class="item-location"><i class="fas fa-map-marker-alt"></i> ${item.location || 'Unknown'}</div>
                        <div class="item-reporter">
                            <i class="fas fa-user"></i> ${item.reporter_name}<br>
                            <i class="fas fa-phone"></i> ${item.reporter_phone}
                        </div>
                        ${imageHtml}
                        <div class="stats-mini">
                            <span>ID: ${item.item_string_id}</span>
                        </div>
                        <div class="item-actions">
                            <button class="primary" onclick='viewItemDetails(${JSON.stringify(item).replace(/'/g, "&apos;")})'><i class="fas fa-eye"></i> View</button>
                            <button class="warning" onclick='openReviewItemModal(${JSON.stringify(item).replace(/'/g, "&apos;")})'><i class="fas fa-check-circle"></i> Review</button>
                            <button class="danger" onclick="deleteItem(${item.id})"><i class="fas fa-trash"></i> Delete</button>
                        </div>
                    </div>
                `;
            }).join('') + '</div>';
        }

        function viewItemDetails(item) {
            const images = processImagePath(item.image_path);
            const imageHtml = images.length > 0 ? 
                `<div class="image-gallery">
                    ${images.map(img => 
                        `<img src="https://astufindit.x10.mx/index/${img}" class="gallery-image" onclick="showFullscreenImage('https://astufindit.x10.mx/index/${img}')">`
                    ).join('')}
                </div>` : '<p>No images</p>';

            const details = document.getElementById('itemDetails');
            details.innerHTML = `
                <div style="background: #f8f9fa; padding: 15px; border-radius: 10px;">
                    <p><strong>ID:</strong> ${item.id}</p>
                    <p><strong>Item String ID:</strong> ${item.item_string_id}</p>
                    <p><strong>Type:</strong> <span style="color: ${item.type === 'lost' ? '#e74c3c' : '#27ae60'};">${item.type.toUpperCase()}</span></p>
                    <p><strong>Status:</strong> <span class="badge" style="background: ${item.status === 'pending' ? '#f39c12' : item.status === 'admin_approval' ? '#9b59b6' : '#3498db'}">${item.status}</span></p>
                    <p><strong>Title:</strong> ${item.title}</p>
                    <p><strong>Description:</strong> ${item.description}</p>
                    <p><strong>Location:</strong> ${item.location || 'Unknown'}</p>
                    <p><strong>Category:</strong> ${item.category}</p>
                    <p><strong>Found Property:</strong> ${item.found_item_property || 'N/A'}</p>
                    <p><strong>Reporter:</strong> ${item.reporter_name} (${item.reporter_phone})</p>
                    <p><strong>When Lost:</strong> ${item.when_lost || 'N/A'}</p>
                    <p><strong>Admin Notes:</strong> ${item.admin_notes || 'None'}</p>
                    <p><strong>Created:</strong> ${formatDate(item.created_at)}</p>
                    ${imageHtml}
                </div>
            `;
            document.getElementById('viewItemModal').style.display = 'block';
        }

        function openReviewItemModal(item) {
            currentItem = item;
            const details = document.getElementById('reviewItemDetails');
            
            const images = processImagePath(item.image_path);
            const imageHtml = images.length > 0 ? 
                `<div style="display: flex; gap: 5px; margin: 10px 0; overflow-x: auto;">
                    ${images.map(img => 
                        `<img src="https://astufindit.x10.mx/index/${img}" style="width: 80px; height: 80px; object-fit: cover; border-radius: 5px; cursor: pointer;" onclick="showFullscreenImage('https://astufindit.x10.mx/index/${img}')">`
                    ).join('')}
                </div>` : '';
            
            details.innerHTML = `
                <div style="background: #f8f9fa; padding: 15px; border-radius: 10px;">
                    <p><strong>Type:</strong> <span style="color: ${item.type === 'lost' ? '#e74c3c' : '#27ae60'};">${item.type.toUpperCase()}</span></p>
                    <p><strong>Status:</strong> <span class="badge" style="background: ${item.status === 'pending' ? '#f39c12' : item.status === 'admin_approval' ? '#9b59b6' : '#3498db'}">${item.status}</span></p>
                    <p><strong>Title:</strong> ${item.title}</p>
                    <p><strong>Description:</strong> ${item.description}</p>
                    <p><strong>Location:</strong> ${item.location || 'Unknown'}</p>
                    <p><strong>Reporter:</strong> ${item.reporter_name} (${item.reporter_phone})</p>
                    <p><strong>Submitted:</strong> ${formatDate(item.created_at)}</p>
                    ${imageHtml}
                    ${item.admin_notes ? `<p><strong>Previous Notes:</strong> ${item.admin_notes}</p>` : ''}
                </div>
            `;
            
            document.getElementById('itemAdminNotes').value = '';
            document.getElementById('itemMessagePreview').style.display = 'none';
            document.getElementById('reviewItemModal').style.display = 'block';
        }

        async function reviewItem(action) {
            const notes = document.getElementById('itemAdminNotes').value;
            const notify = document.getElementById('notifyUser').checked;
            
            if (!currentItem) return;
            
            try {
                showLoading();
                
                const response = await fetch(buildUrl('admin-review-item'), {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        item_id: currentItem.item_string_id,
                        review_action: action,
                        admin_notes: notes,
                        notify_user: notify
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    closeModal('reviewItemModal');
                    loadItems(currentPage.items);
                    loadDashboardData();
                    updateBadges();
                    showToast(`Item ${action}d successfully`, 'success');
                } else {
                    showToast(data.message || 'Failed to review item', 'error');
                }
                
                hideLoading();
            } catch (error) {
                hideLoading();
                showToast('Error: ' + error.message, 'error');
            }
        }

        async function deleteItem(itemId) {
            confirmAction('Are you sure you want to delete this item? This action cannot be undone.', async () => {
                try {
                    showLoading();
                    
                    const response = await fetch(buildUrl('admin-delete-item'), {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ item_id: itemId })
                    });
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        loadItems(currentPage.items);
                        loadDashboardData();
                        updateBadges();
                        showToast('Item deleted successfully', 'success');
                    } else {
                        showToast(data.message || 'Failed to delete item', 'error');
                    }
                    
                    hideLoading();
                } catch (error) {
                    hideLoading();
                    showToast('Error: ' + error.message, 'error');
                }
            });
        }

        function resetItemFilters() {
            document.getElementById('itemTypeFilter').value = '';
            document.getElementById('itemStatusFilter').value = '';
            document.getElementById('itemSearch').value = '';
            loadItems(1);
        } 
        async function loadClaims(page = 1) {
            try {
                showLoading();
                
                const status = document.getElementById('claimStatusFilter').value;
                const search = document.getElementById('claimSearch').value;
                
                const params = { page };
                if (status) params.status = status;
                if (search) params.search = search;
                
                const response = await fetch(buildUrl('admin-get-claims', params));
                const data = await response.json();
                
                if (data.success) {
                    displayClaims(data.claims);
                    displayPagination('claims', data.pagination || { current_page: page, total_pages: 1 });
                } else {
                    showToast(data.message || 'Failed to load claims', 'error');
                }
                
                hideLoading();
            } catch (error) {
                hideLoading();
                showToast('Error: ' + error.message, 'error');
            }
        }

        function displayClaims(claims) {
            const container = document.getElementById('claimsList');
            
            if (!claims || claims.length === 0) {
                container.innerHTML = '<div class="empty-state"><i class="fas fa-inbox fa-3x"></i><br>No claims found</div>';
                return;
            }

            container.innerHTML = '<div class="table-container"><table><tr><th>Item</th><th>Claimant</th><th>Description</th><th>Lost Location</th><th>Status</th><th>Date</th><th>Action</th></tr>' + 
                claims.map(claim => `
                    <tr>
                        <td>${claim.item_title || 'Unknown'}</td>
                        <td>${claim.claimant_name || 'Unknown'}<br><small>${claim.claimant_student_id || ''}</small></td>
                        <td><small>${claim.description?.substring(0, 50)}${claim.description?.length > 50 ? '...' : ''}</small></td>
                        <td>${claim.lost_location || 'Not specified'}</td>
                        <td><span class="badge" style="background: ${claim.status === 'pending' ? '#f39c12' : claim.status === 'approved' ? '#27ae60' : '#e74c3c'}">${claim.status}</span></td>
                        <td>${formatDateShort(claim.created_at)}</td>
                        <td>
                            <button class="primary small" onclick='openReviewClaimModal(${JSON.stringify(claim).replace(/'/g, "&apos;")})'><i class="fas fa-gavel"></i></button>
                            ${claim.attachments?.length ? `<button class="info small" onclick="viewAttachments(${claim.id})"><i class="fas fa-paperclip"></i></button>` : ''}
                        </td>
                    </tr>
                `).join('') + '</table></div>';
        }

        function openReviewClaimModal(claim) {
            currentClaim = claim;
            const details = document.getElementById('reviewClaimDetails');
            
            const attachments = claim.attachments && claim.attachments.length > 0 ? 
                `<p><strong>Attachments:</strong> ${claim.attachments.length} file(s)</p>` : '';
            
            details.innerHTML = `
                <div style="background: #f8f9fa; padding: 15px; border-radius: 10px;">
                    <h4 style="margin-bottom: 10px;">Item: ${claim.item_title || 'Unknown'}</h4>
                    <p><strong>Claimant:</strong> ${claim.claimant_name}</p>
                    <p><strong>Student ID:</strong> ${claim.claimant_student_id || 'N/A'}</p>
                    <p><strong>Phone:</strong> ${claim.claimant_phone || 'N/A'}</p>
                    <p><strong>Status:</strong> <span class="badge" style="background: ${claim.status === 'pending' ? '#f39c12' : claim.status === 'approved' ? '#27ae60' : '#e74c3c'}">${claim.status}</span></p>
                    <p><strong>Lost Location:</strong> ${claim.lost_location || 'Not specified'}</p>
                    <p><strong>Description:</strong></p>
                    <p style="background: white; padding: 10px; border-radius: 5px;">${claim.description || 'No description'}</p>
                    ${attachments}
                    <p><strong>Submitted:</strong> ${formatDate(claim.created_at)}</p>
                    ${claim.reviewed_at ? `<p><strong>Reviewed:</strong> ${formatDate(claim.reviewed_at)}</p>` : ''}
                    ${claim.admin_notes ? `<p><strong>Previous Notes:</strong> ${claim.admin_notes}</p>` : ''}
                </div>
            `;
            
            document.getElementById('claimAdminNotes').value = '';
            document.getElementById('reviewClaimModal').style.display = 'block';
        }

        async function reviewClaim(action) {
            const notes = document.getElementById('claimAdminNotes').value;
            
            try {
                const response = await fetch(buildUrl('admin-review-claim'), {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        claim_id: currentClaim.id,
                        claim_action: action,
                        admin_notes: notes
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    closeModal('reviewClaimModal');
                    loadClaims(currentPage.claims);
                    loadDashboardData();
                    updateBadges();
                    showToast(`Claim ${action}d successfully`, 'success');
                } else {
                    showToast(data.message || 'Failed to review claim', 'error');
                }
            } catch (error) {
                showToast('Error: ' + error.message, 'error');
            }
        }

        function resetClaimFilters() {
            document.getElementById('claimStatusFilter').value = '';
            document.getElementById('claimSearch').value = '';
            loadClaims(1);
        } 
        async function loadMatches(page = 1) {
            try {
                showLoading();
                
                const status = document.getElementById('matchStatusFilter').value;
                const search = document.getElementById('matchSearch').value;
                
                const params = { page };
                if (status) params.status = status;
                if (search) params.search = search;
                
                const response = await fetch(buildUrl('admin-get-matches', params));
                const data = await response.json();
                
                if (data.success) {
                    displayMatches(data.matches);
                    displayPagination('matches', data.pagination || { current_page: page, total_pages: 1 });
                } else {
                    showToast(data.message || 'Failed to load matches', 'error');
                }
                
                hideLoading();
            } catch (error) {
                hideLoading();
                showToast('Error: ' + error.message, 'error');
            }
        }

        function displayMatches(matches) {
            const container = document.getElementById('matchesList');
            
            if (!matches || matches.length === 0) {
                container.innerHTML = '<div class="empty-state"><i class="fas fa-link fa-3x"></i><br>No matches found</div>';
                return;
            }

            container.innerHTML = '<div class="items-grid">' + matches.map(match => {
                const lostImages = processImagePath(match.lost_image);
                const foundImages = processImagePath(match.found_image);
                
                return `
                    <div class="item-card">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                            <span class="badge" style="background: ${match.status === 'pending' ? '#f39c12' : match.status === 'confirmed' ? '#27ae60' : '#e74c3c'}">${match.status}</span>
                            <span style="background: #3498db; color: white; padding: 3px 8px; border-radius: 12px; font-size: 11px;">
                                <i class="fas fa-chart-line"></i> ${match.match_confidence || 80}% match
                            </span>
                        </div>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin: 10px 0;">
                            <div style="border-right: 1px solid #ddd; padding-right: 10px;">
                                <small style="color: #e74c3c;">LOST</small>
                                <div><strong>${match.lost_title || 'Unknown'}</strong></div>
                                <small>${match.lost_reporter || 'Unknown'}</small>
                                ${lostImages.length > 0 ? '<br><small>' + lostImages.length + ' image(s)</small>' : ''}
                            </div>
                            <div>
                                <small style="color: #27ae60;">FOUND</small>
                                <div><strong>${match.found_title || 'Unknown'}</strong></div>
                                <small>${match.found_reporter || 'Unknown'}</small>
                                ${foundImages.length > 0 ? '<br><small>' + foundImages.length + ' image(s)</small>' : ''}
                            </div>
                        </div>
                        <div class="item-actions">
                            <button class="primary" onclick='openReviewMatchModal(${JSON.stringify(match).replace(/'/g, "&apos;")})'><i class="fas fa-eye"></i> Review</button>
                        </div>
                    </div>
                `;
            }).join('') + '</div>';
        }

        function openReviewMatchModal(match) {
            currentMatch = match;
            const details = document.getElementById('reviewMatchDetails');
            
            details.innerHTML = `
                <div style="background: #f8f9fa; padding: 15px; border-radius: 10px;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 15px;">
                        <span class="badge" style="background: ${match.status === 'pending' ? '#f39c12' : match.status === 'confirmed' ? '#27ae60' : '#e74c3c'}">${match.status}</span>
                        <span style="background: #3498db; color: white; padding: 3px 10px; border-radius: 12px;">${match.match_confidence || 80}% Match</span>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                        <div style="border-right: 1px solid #ddd; padding-right: 15px;">
                            <h4 style="color: #e74c3c;">LOST ITEM</h4>
                            <p><strong>Title:</strong> ${match.lost_title || 'Unknown'}</p>
                            <p><strong>Description:</strong> ${match.lost_description || 'N/A'}</p>
                            <p><strong>Location:</strong> ${match.lost_location || 'N/A'}</p>
                            <p><strong>Reporter:</strong> ${match.lost_reporter || 'Unknown'}</p>
                            <p><strong>Phone:</strong> ${match.lost_phone || 'N/A'}</p>
                            ${match.lost_owner_name ? `<p><strong>Owner:</strong> ${match.lost_owner_name}</p>` : ''}
                        </div>
                        <div>
                            <h4 style="color: #27ae60;">FOUND ITEM</h4>
                            <p><strong>Title:</strong> ${match.found_title || 'Unknown'}</p>
                            <p><strong>Description:</strong> ${match.found_description || 'N/A'}</p>
                            <p><strong>Location:</strong> ${match.found_location || 'N/A'}</p>
                            <p><strong>Reporter:</strong> ${match.found_reporter || 'Unknown'}</p>
                            <p><strong>Phone:</strong> ${match.found_phone || 'N/A'}</p>
                            ${match.found_owner_name ? `<p><strong>Finder:</strong> ${match.found_owner_name}</p>` : ''}
                        </div>
                    </div>
                    
                    <p><strong>Created By:</strong> ${match.created_by || 'System'}</p>
                    <p><strong>Created:</strong> ${formatDate(match.created_at)}</p>
                </div>
            `;
            
            document.getElementById('matchAdminNotes').value = '';
            document.getElementById('reviewMatchModal').style.display = 'block';
        }

        async function updateMatch(status) {
            const notes = document.getElementById('matchAdminNotes').value;
            
            try {
                const response = await fetch(buildUrl('admin-update-match'), {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        match_id: currentMatch.id,
                        status: status,
                        admin_notes: notes
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    closeModal('reviewMatchModal');
                    loadMatches(currentPage.matches);
                    loadDashboardData();
                    updateBadges();
                    showToast(`Match updated to ${status}`, 'success');
                } else {
                    showToast(data.message || 'Failed to update match', 'error');
                }
            } catch (error) {
                showToast('Error: ' + error.message, 'error');
            }
        }

        function resetMatchFilters() {
            document.getElementById('matchStatusFilter').value = '';
            document.getElementById('matchSearch').value = '';
            loadMatches(1);
        }
 
        async function loadUsers(page = 1) {
            try {
                showLoading();
                
                const role = document.getElementById('userRoleFilter').value;
                const search = document.getElementById('userSearch').value;
                
                const params = { page };
                if (role) params.role = role;
                if (search) params.search = search;
                
                const response = await fetch(buildUrl('admin-get-users', params));
                const data = await response.json();
                
                if (data.success) {
                    displayUsers(data.users);
                    displayPagination('users', data.pagination || { current_page: page, total_pages: 1 });
                } else {
                    showToast(data.message || 'Failed to load users', 'error');
                }
                
                hideLoading();
            } catch (error) {
                hideLoading();
                showToast('Error: ' + error.message, 'error');
            }
        }

        function displayUsers(users) {
            const container = document.getElementById('usersList');
            
            if (!users || users.length === 0) {
                container.innerHTML = '<div class="empty-state"><i class="fas fa-users fa-3x"></i><br>No users found</div>';
                return;
            }

            container.innerHTML = '<div class="table-container"><table><tr><th>Name</th><th>Student ID</th><th>Phone</th><th>Role</th><th>Items</th><th>Claims</th><th>Joined</th><th>Actions</th></tr>' + 
                users.map(user => `
                    <tr>
                        <td>${user.full_name}</td>
                        <td>${user.student_id}</td>
                        <td>${user.phone || 'N/A'}</td>
                        <td><span class="badge" style="background: ${user.role === 'admin' ? '#667eea' : '#3498db'}">${user.role}</span></td>
                        <td>${user.items_count || 0}</td>
                        <td>${user.claims_count || 0}</td>
                        <td>${formatDateShort(user.created_at)}</td>
                        <td>
                            <button class="primary small" onclick='openEditUserModal(${JSON.stringify(user).replace(/'/g, "&apos;")})'><i class="fas fa-edit"></i></button>
                            <button class="info small" onclick="openSendMessageModal('${user.user_string_id}', '${user.full_name}')"><i class="fas fa-envelope"></i></button>
                            ${user.role !== 'admin' ? `<button class="danger small" onclick="deleteUser(${user.id})"><i class="fas fa-trash"></i></button>` : ''}
                        </td>
                    </tr>
                `).join('') + '</table></div>';
        }

        function openEditUserModal(user) {
            currentUser = user;
            document.getElementById('editUserId').value = user.id;
            document.getElementById('editFullName').value = user.full_name || '';
            document.getElementById('editStudentId').value = user.student_id || '';
            document.getElementById('editPhone').value = user.phone || '';
            document.getElementById('editRole').value = user.role || 'student';
            document.getElementById('editPassword').value = '';
            document.getElementById('editUserModal').style.display = 'block';
        }

        function openAddUserModal() {
            document.getElementById('addFullName').value = '';
            document.getElementById('addStudentId').value = '';
            document.getElementById('addPhone').value = '';
            document.getElementById('addPassword').value = '';
            document.getElementById('addConfirmPassword').value = '';
            document.getElementById('addRole').value = 'student';
            document.getElementById('addUserModal').style.display = 'block';
        }

        async function updateUser() {
            const userData = {
                user_id: document.getElementById('editUserId').value,
                full_name: document.getElementById('editFullName').value,
                student_id: document.getElementById('editStudentId').value,
                phone: document.getElementById('editPhone').value,
                role: document.getElementById('editRole').value,
                password: document.getElementById('editPassword').value
            };
            
            if (!userData.full_name || !userData.student_id) {
                showToast('Name and Student ID are required', 'error');
                return;
            }
            
            try {
                showLoading();
                
                const response = await fetch(buildUrl('admin-update-user'), {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(userData)
                });
                
                const data = await response.json();
                
                if (data.success) {
                    closeModal('editUserModal');
                    loadUsers(currentPage.users);
                    showToast('User updated successfully', 'success');
                } else {
                    showToast(data.message || 'Failed to update user', 'error');
                }
                
                hideLoading();
            } catch (error) {
                hideLoading();
                showToast('Error: ' + error.message, 'error');
            }
        }

        async function addUser() {
            const password = document.getElementById('addPassword').value;
            const confirm = document.getElementById('addConfirmPassword').value;
            
            if (password !== confirm) {
                showToast('Passwords do not match', 'error');
                return;
            }
            
            const userData = {
                full_name: document.getElementById('addFullName').value,
                student_id: document.getElementById('addStudentId').value,
                phone: document.getElementById('addPhone').value,
                role: document.getElementById('addRole').value,
                password: password
            };
            
            if (!userData.full_name || !userData.student_id || !password) {
                showToast('All fields are required', 'error');
                return;
            }
            
            try {
                showLoading();
                
                const response = await fetch(buildUrl('admin-add-user'), {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(userData)
                });
                
                const data = await response.json();
                
                if (data.success) {
                    closeModal('addUserModal');
                    loadUsers(currentPage.users);
                    showToast('User added successfully', 'success');
                } else {
                    showToast(data.message || 'Failed to add user', 'error');
                }
                
                hideLoading();
            } catch (error) {
                hideLoading();
                showToast('Error: ' + error.message, 'error');
            }
        }

        async function deleteUser(userId) {
            confirmAction('Are you sure you want to delete this user? All their items and claims will also be deleted.', async () => {
                try {
                    showLoading();
                    
                    const response = await fetch(buildUrl('admin-delete-user'), {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ user_id: userId })
                    });
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        loadUsers(currentPage.users);
                        loadDashboardData();
                        showToast('User deleted successfully', 'success');
                    } else {
                        showToast(data.message || 'Failed to delete user', 'error');
                    }
                    
                    hideLoading();
                } catch (error) {
                    hideLoading();
                    showToast('Error: ' + error.message, 'error');
                }
            });
        }

        function resetUserFilters() {
            document.getElementById('userRoleFilter').value = '';
            document.getElementById('userSearch').value = '';
            loadUsers(1);
        }
 
        async function loadMessages(page = 1) {
            try {
                showLoading();
                
                const status = document.getElementById('messageStatusFilter').value;
                const search = document.getElementById('messageSearch').value;
                
                const params = { page };
                if (status) params.status = status;
                if (search) params.search = search;
                
                const response = await fetch(buildUrl('admin-get-messages', params));
                const data = await response.json();
                
                if (data.success) {
                    displayMessages(data.messages);
                    displayPagination('messages', data.pagination || { current_page: page, total_pages: 1 });
                } else {
                    showToast(data.message || 'Failed to load messages', 'error');
                }
                
                hideLoading();
            } catch (error) {
                hideLoading();
                showToast('Error: ' + error.message, 'error');
            }
        }

        function displayMessages(messages) {
            const container = document.getElementById('messagesList');
            
            if (!messages || messages.length === 0) {
                container.innerHTML = '<div class="empty-state"><i class="fas fa-envelope fa-3x"></i><br>No messages found</div>';
                return;
            }

            container.innerHTML = '<div class="table-container"><table><tr><th>To</th><th>Subject</th><th>Message</th><th>Status</th><th>Date</th><th>Action</th></tr>' + 
                messages.map(msg => `
                    <tr>
                        <td>${msg.recipient_name || 'Unknown'}</td>
                        <td>${msg.subject}</td>
                        <td><small>${msg.message?.substring(0, 50)}${msg.message?.length > 50 ? '...' : ''}</small></td>
                        <td><span class="badge" style="background: ${msg.is_read ? '#27ae60' : '#f39c12'}">${msg.is_read ? 'Read' : 'Unread'}</span></td>
                        <td>${timeAgo(msg.created_at)}</td>
                        <td>
                            <button class="primary small" onclick='viewMessage(${JSON.stringify(msg).replace(/'/g, "&apos;")})'><i class="fas fa-eye"></i></button>
                        </td>
                    </tr>
                `).join('') + '</table></div>';
        }

        function viewMessage(message) {
            currentMessage = message;
            const details = document.getElementById('messageDetails');
            
            details.innerHTML = `
                <div style="background: #f8f9fa; padding: 15px; border-radius: 10px;">
                    <p><strong>To:</strong> ${message.recipient_name} (${message.recipient_id})</p>
                    <p><strong>Subject:</strong> ${message.subject}</p>
                    <p><strong>Message:</strong></p>
                    <p style="background: white; padding: 15px; border-radius: 5px;">${message.message}</p>
                    <p><strong>Sent:</strong> ${formatDate(message.created_at)}</p>
                    <p><strong>Status:</strong> ${message.is_read ? 'Read' : 'Unread'}</p>
                    ${message.read_at ? `<p><strong>Read at:</strong> ${formatDate(message.read_at)}</p>` : ''}
                </div>
            `;
            
            document.getElementById('viewMessageModal').style.display = 'block';
        }

        function deleteMessage() {
            if (!currentMessage) return;
            
            confirmAction('Delete this message?', async () => {
                try {
                    const response = await fetch(buildUrl('admin-delete-message'), {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ message_id: currentMessage.id })
                    });
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        closeModal('viewMessageModal');
                        loadMessages(currentPage.messages);
                        showToast('Message deleted', 'success');
                    } else {
                        showToast(data.message || 'Failed to delete message', 'error');
                    }
                } catch (error) {
                    showToast('Error: ' + error.message, 'error');
                }
            });
        }

        function openSendMessageModal(userId = '', userName = '') { 
            fetch(buildUrl('admin-get-users', { limit: 100 }))
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        const select = document.getElementById('messageUserSelect');
                        select.innerHTML = '<option value="">Select a user...</option>';
                        
                        data.users.forEach(user => {
                            const selected = (user.user_string_id === userId) ? 'selected' : '';
                            select.innerHTML += `<option value="${user.user_string_id}" ${selected}>${user.full_name} (${user.student_id})</option>`;
                        });
                    }
                });
            
            document.getElementById('messageSubject').value = 'Message from Admin';
            document.getElementById('messageContent').value = '';
            document.getElementById('messageType').value = 'general';
            document.getElementById('sendMessageModal').style.display = 'block';
        }

        async function sendMessage() {
            const userId = document.getElementById('messageUserSelect').value;
            const subject = document.getElementById('messageSubject').value;
            const message = document.getElementById('messageContent').value;
            const type = document.getElementById('messageType').value;
            
            if (!userId) {
                showToast('Please select a user', 'error');
                return;
            }
            
            if (!message) {
                showToast('Please enter a message', 'error');
                return;
            }
            
            try {
                showLoading();
                
                const response = await fetch(buildUrl('admin-send-message'), {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        user_id: userId,
                        subject: subject,
                        message: message,
                        message_type: type
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    closeModal('sendMessageModal');
                    showToast('Message sent successfully', 'success');
                    if (document.getElementById('messagesView').style.display !== 'none') {
                        loadMessages(currentPage.messages);
                    }
                } else {
                    showToast(data.message || 'Failed to send message', 'error');
                }
                
                hideLoading();
            } catch (error) {
                hideLoading();
                showToast('Error: ' + error.message, 'error');
            }
        }
 
        async function loadLogs(page = 1) {
            try {
                showLoading();
                
                const search = document.getElementById('logSearch').value;
                const params = { page };
                if (search) params.search = search;
                
                const response = await fetch(buildUrl('admin-get-logs', params));
                const data = await response.json();
                
                if (data.success) {
                    displayLogs(data.logs);
                    displayPagination('logs', data.pagination || { current_page: page, total_pages: 1 });
                } else {
                    showToast(data.message || 'Failed to load logs', 'error');
                }
                
                hideLoading();
            } catch (error) {
                hideLoading();
                showToast('Error: ' + error.message, 'error');
            }
        }

        function displayLogs(logs) {
            const container = document.getElementById('logsList');
            
            if (!logs || logs.length === 0) {
                container.innerHTML = '<div class="empty-state"><i class="fas fa-history fa-3x"></i><br>No logs found</div>';
                return;
            }

            container.innerHTML = '<div class="table-container"><table><tr><th>Admin</th><th>Action</th><th>Details</th><th>Target</th><th>Time</th></tr>' + 
                logs.map(log => `
                    <tr>
                        <td>${log.admin_name || 'Unknown'}</td>
                        <td><span class="badge" style="background: #667eea">${log.action || 'Action'}</span></td>
                        <td>${log.details || ''}</td>
                        <td>${log.user_name || log.target_user || '—'}</td>
                        <td>${formatDate(log.created_at)}</td>
                    </tr>
                `).join('') + '</table></div>';
        }

        function clearLogs() {
            confirmAction('Clear all activity logs? This cannot be undone.', async () => {
                try {
                    const response = await fetch(buildUrl('admin-clear-logs'), {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' }
                    });
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        loadLogs(1);
                        showToast('Logs cleared', 'success');
                    } else {
                        showToast(data.message || 'Failed to clear logs', 'error');
                    }
                } catch (error) {
                    showToast('Error: ' + error.message, 'error');
                }
            });
        } 
        document.getElementById('reportDateRange').addEventListener('change', function() {
            const customRange = document.getElementById('customDateRange');
            customRange.style.display = this.value === 'custom' ? 'block' : 'none';
        });

        function getDateRange() {
            const range = document.getElementById('reportDateRange').value;
            const today = new Date();
            let from = new Date(), to = new Date();
            
            switch(range) {
                case 'today':
                    from = today;
                    to = today;
                    break;
                case 'yesterday':
                    from.setDate(today.getDate() - 1);
                    to.setDate(today.getDate() - 1);
                    break;
                case 'week':
                    from.setDate(today.getDate() - 7);
                    to = today;
                    break;
                case 'month':
                    from.setMonth(today.getMonth() - 1);
                    to = today;
                    break;
                case 'year':
                    from.setFullYear(today.getFullYear() - 1);
                    to = today;
                    break;
                case 'custom':
                    from = new Date(document.getElementById('dateFrom').value);
                    to = new Date(document.getElementById('dateTo').value);
                    break;
            }
            
            return {
                from: from.toISOString().split('T')[0],
                to: to.toISOString().split('T')[0]
            };
        }

        async function generateReport(format) {
            const type = document.getElementById('reportType').value;
            const dateRange = getDateRange();
            
            if (format === 'preview') {
                try {
                    showLoading();
                    
                    const params = {
                        report_type: type,
                        date_from: dateRange.from,
                        date_to: dateRange.to
                    };
                    
                    const response = await fetch(buildUrl('admin-generate-report', params));
                    const data = await response.json();
                    
                    if (data.success) {
                        displayReportPreview(data);
                    } else {
                        showToast(data.message || 'Failed to generate report', 'error');
                    }
                    
                    hideLoading();
                } catch (error) {
                    hideLoading();
                    showToast('Error: ' + error.message, 'error');
                }
            } else {
                const url = buildUrl('admin-generate-report', {
                    report_type: type,
                    date_from: dateRange.from,
                    date_to: dateRange.to,
                    format: format
                });
                window.open(url, '_blank');
            }
        }

        function displayReportPreview(data) {
            const container = document.getElementById('reportPreview');
            const dataContainer = document.getElementById('reportData');
            
            let html = '<table><tr>';
            
            if (data.data && data.data.length > 0) {
                
                Object.keys(data.data[0]).forEach(key => {
                    html += `<th>${key}</th>`;
                });
                html += '</tr>';
                
                data.data.forEach(row => {
                    html += '<tr>';
                    Object.values(row).forEach(val => {
                        html += `<td>${val || '—'}</td>`;
                    });
                    html += '</tr>';
                });
            } else {
                html = '<p>No data found for selected period</p>';
            }
            
            html += '</table>';
            dataContainer.innerHTML = html;
            container.style.display = 'block';
        }

        function printReport() {
            window.print();
        }
        async function updateProfile() {
            const profileData = {
                full_name: document.getElementById('settingsFullName').value,
                phone: document.getElementById('settingsPhone').value,
                email: document.getElementById('settingsEmail').value
            };
            
            try {
                showLoading();
                
                const response = await fetch(buildUrl('admin-update-profile'), {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(profileData)
                });
                
                const data = await response.json();
                
                if (data.success) {
                    currentAdmin.full_name = profileData.full_name;
                    document.getElementById('adminName').textContent = profileData.full_name;
                    showToast('Profile updated', 'success');
                } else {
                    showToast(data.message || 'Failed to update profile', 'error');
                }
                
                hideLoading();
            } catch (error) {
                hideLoading();
                showToast('Error: ' + error.message, 'error');
            }
        }

        async function changePassword() {
            const current = document.getElementById('currentPassword').value;
            const newPass = document.getElementById('newPassword').value;
            const confirm = document.getElementById('confirmPassword').value;
            
            if (!current || !newPass || !confirm) {
                showToast('All fields are required', 'error');
                return;
            }
            
            if (newPass !== confirm) {
                showToast('New passwords do not match', 'error');
                return;
            }
            
            if (newPass.length < 6) {
                showToast('Password must be at least 6 characters', 'error');
                return;
            }
            
            try {
                showLoading();
                
                const response = await fetch(buildUrl('admin-change-password'), {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        current_password: current,
                        new_password: newPass
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    document.getElementById('currentPassword').value = '';
                    document.getElementById('newPassword').value = '';
                    document.getElementById('confirmPassword').value = '';
                    showToast('Password changed', 'success');
                } else {
                    showToast(data.message || 'Failed to change password', 'error');
                }
                
                hideLoading();
            } catch (error) {
                hideLoading();
                showToast('Error: ' + error.message, 'error');
            }
        }
        function displayPagination(type, pagination) {
            const container = document.getElementById(`${type}Pagination`);
            if (!pagination || pagination.total_pages <= 1) {
                container.innerHTML = '';
                return;
            }

            let html = '';
            const current = pagination.current_page;
            const total = pagination.total_pages;

            html += `<button onclick="${type}Page(${current - 1})" ${current === 1 ? 'disabled' : ''}><i class="fas fa-chevron-left"></i></button>`;

            for (let i = 1; i <= total; i++) {
                if (i === 1 || i === total || (i >= current - 2 && i <= current + 2)) {
                    html += `<button onclick="${type}Page(${i})" class="${i === current ? 'active' : ''}">${i}</button>`;
                } else if (i === current - 3 || i === current + 3) {
                    html += `<button disabled>...</button>`;
                }
            }

            html += `<button onclick="${type}Page(${current + 1})" ${current === total ? 'disabled' : ''}><i class="fas fa-chevron-right"></i></button>`;

            container.innerHTML = html;
        }

        function itemsPage(page) {
            currentPage.items = page;
            loadItems(page);
        }

        function claimsPage(page) {
            currentPage.claims = page;
            loadClaims(page);
        }

        function matchesPage(page) {
            currentPage.matches = page;
            loadMatches(page);
        }

        function usersPage(page) {
            currentPage.users = page;
            loadUsers(page);
        }

        function messagesPage(page) {
            currentPage.messages = page;
            loadMessages(page);
        }

        function logsPage(page) {
            currentPage.logs = page;
            loadLogs(page);
        }
        function switchTab(tab) {
            document.querySelectorAll('.nav-tabs button').forEach(btn => btn.classList.remove('active'));
            document.getElementById(`tab${tab.charAt(0).toUpperCase() + tab.slice(1)}`).classList.add('active');
            document.getElementById('dashboardView').style.display = tab === 'dashboard' ? 'block' : 'none';
            document.getElementById('itemsView').style.display = tab === 'items' ? 'block' : 'none';
            document.getElementById('claimsView').style.display = tab === 'claims' ? 'block' : 'none';
            document.getElementById('matchesView').style.display = tab === 'matches' ? 'block' : 'none';
            document.getElementById('usersView').style.display = tab === 'users' ? 'block' : 'none';
            document.getElementById('messagesView').style.display = tab === 'messages' ? 'block' : 'none';
            document.getElementById('reportsView').style.display = tab === 'reports' ? 'block' : 'none';
            document.getElementById('logsView').style.display = tab === 'logs' ? 'block' : 'none';
            document.getElementById('settingsView').style.display = tab === 'settings' ? 'block' : 'none';
            
            if (tab === 'items') loadItems(1);
            if (tab === 'claims') loadClaims(1);
            if (tab === 'matches') loadMatches(1);
            if (tab === 'users') loadUsers(1);
            if (tab === 'messages') loadMessages(1);
            if (tab === 'logs') loadLogs(1);
        }
        document.getElementById('itemAdminNotes').addEventListener('input', function(e) {
            const preview = document.getElementById('itemMessagePreview');
            if (e.target.value.trim()) {
                preview.innerHTML = `<strong>Preview:</strong> ${e.target.value}`;
                preview.style.display = 'block';
            } else {
                preview.style.display = 'none';
            }
        });

        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
            }
        }
        setInterval(updateBadges, 30000);
    </script>
</body>
</html>