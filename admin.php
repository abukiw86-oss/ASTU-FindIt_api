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
            max-width: 1200px;
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
            gap: 10px;
        }

        .nav-tabs button {
            padding: 15px 25px;
            border: none;
            background: none;
            cursor: pointer;
            font-size: 15px;
            font-weight: 500;
            color: #666;
            position: relative;
            transition: all 0.3s;
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
            border-radius: 3px 3px 0 0;
        }

        .content {
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            transition: transform 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 15px;
        }

        .stat-icon i {
            color: white;
            font-size: 24px;
        }

        .stat-label {
            color: #666;
            font-size: 14px;
            margin-bottom: 5px;
        }

        .stat-number {
            font-size: 32px;
            font-weight: bold;
            color: #333;
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

        .item-date {
            color: #999;
            font-size: 12px;
        }

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

        .item-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
            flex-wrap: wrap;
        }

        button {
            padding: 10px 18px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            font-size: 13px;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        button.primary {
            background: #3498db;
            color: white;
        }

        button.success {
            background: #27ae60;
            color: white;
        }

        button.warning {
            background: #f39c12;
            color: white;
        }

        button.danger {
            background: #e74c3c;
            color: white;
        }

        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 10px rgba(0,0,0,0.1);
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
            animation: modalSlide 0.3s ease;
        }

        @keyframes modalSlide {
            from {
                opacity: 0;
                transform: translateY(-50px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .modal h3 {
            margin-bottom: 20px;
            color: #333;
            font-size: 22px;
        }

        .claim-card {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 10px;
            border-left: 3px solid;
        }

        .claim-card.pending { border-left-color: #f39c12; }
        .claim-card.approved { border-left-color: #27ae60; }
        .claim-card.rejected { border-left-color: #e74c3c; }

        .claim-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }

        .claim-status {
            padding: 3px 10px;
            border-radius: 15px;
            font-size: 11px;
            font-weight: bold;
            color: white;
        }

        .status-pending { background: #f39c12; }
        .status-approved { background: #27ae60; }
        .status-rejected { background: #e74c3c; }

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
            animation: slideIn 0.3s ease;
            z-index: 2000;
        }

        .toast.success { border-left: 4px solid #27ae60; }
        .toast.error { border-left: 4px solid #e74c3c; }

        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        .loading-spinner {
            text-align: center;
            padding: 40px;
            color: #666;
        }

        .empty-state {
            text-align: center;
            padding: 40px;
            color: #999;
        }

        .empty-state i {
            font-size: 50px;
            margin-bottom: 15px;
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="dashboard" id="dashboard">
        <div class="header">
            <div class="header-content">
                <h2 style="color: #333;"><i class="fas fa-shield-alt" style="color: #667eea; margin-right: 10px;"></i>Lost & Found Mediator</h2>
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
            <button class="active" onclick="switchTab('pending')" id="tabPending">
                <i class="fas fa-clock"></i> Pending Review
            </button>
            <button onclick="switchTab('claims')" id="tabClaims">
                <i class="fas fa-hand-holding-heart"></i> Item Claims
            </button>
            <button onclick="switchTab('matches')" id="tabMatches">
                <i class="fas fa-link"></i> Matches
            </button>
            <button onclick="switchTab('resolved')" id="tabResolved">
                <i class="fas fa-check-circle"></i> Resolved
            </button>
        </div>

        <div class="content">
            <!-- Stats -->
            <div class="stats-grid" id="statsGrid">
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-clock"></i></div>
                    <div class="stat-label">Pending Items</div>
                    <div class="stat-number" id="pendingCount">0</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-hand-holding-heart"></i></div>
                    <div class="stat-label">Active Claims</div>
                    <div class="stat-number" id="claimsCount">0</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-link"></i></div>
                    <div class="stat-label">Active Matches</div>
                    <div class="stat-number" id="matchCount">0</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
                    <div class="stat-label">Resolved Items</div>
                    <div class="stat-number" id="resolvedCount">0</div>
                </div>
            </div>

            <!-- Pending Items View -->
            <div id="pendingView">
                <h2 style="margin-bottom: 20px;"><i class="fas fa-clock" style="color: #f39c12;"></i> Items Awaiting Review</h2>
                <div class="items-grid" id="pendingItems"></div>
            </div>

            <!-- Claims View -->
            <div id="claimsView" style="display: none;">
                <h2 style="margin-bottom: 20px;"><i class="fas fa-hand-holding-heart" style="color: #3498db;"></i> Item Claims</h2>
                <div id="claimsList"></div>
            </div>

            <!-- Matches View -->
            <div id="matchesView" style="display: none;">
                <h2 style="margin-bottom: 20px;"><i class="fas fa-link" style="color: #27ae60;"></i> Potential Matches</h2>
                <div id="matchesList"></div>
            </div>

            <!-- Resolved View -->
            <div id="resolvedView" style="display: none;">
                <h2 style="margin-bottom: 20px;"><i class="fas fa-check-circle" style="color: #27ae60;"></i> Resolved Items</h2>
                <div class="items-grid" id="resolvedItems"></div>
            </div>
        </div>
    </div>

    <!-- Review Modal -->
    <div class="modal" id="reviewModal">
        <div class="modal-content">
            <h3><i class="fas fa-clipboard-check"></i> Review Item</h3>
            <div id="reviewItemDetails"></div>
            <div class="form-group" style="margin-top: 20px;">
                <label>Admin Notes</label>
                <textarea id="adminNotes" rows="4" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 8px;" placeholder="Add notes about this review..."></textarea>
            </div>
            <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px;">
                <button class="danger" onclick="reviewItem('reject')"><i class="fas fa-times"></i> Reject</button>
                <button class="success" onclick="reviewItem('approve')"><i class="fas fa-check"></i> Approve</button>
                <button onclick="closeModal('reviewModal')">Cancel</button>
            </div>
        </div>
    </div>

    <!-- Claim Review Modal -->
    <div class="modal" id="claimModal">
        <div class="modal-content">
            <h3><i class="fas fa-gavel"></i> Review Claim</h3>
            <div id="claimDetails"></div>
            <div class="form-group" style="margin-top: 20px;">
                <label>Admin Notes</label>
                <textarea id="claimAdminNotes" rows="4" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 8px;" placeholder="Add notes about this claim..."></textarea>
            </div>
            <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px;">
                <button class="danger" onclick="reviewClaim('reject')"><i class="fas fa-times"></i> Reject</button>
                <button class="success" onclick="reviewClaim('approve')"><i class="fas fa-check"></i> Approve</button>
                <button onclick="closeModal('claimModal')">Cancel</button>
            </div>
        </div>
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

        // Check if logged in
        const savedAdmin = localStorage.getItem('adminData');
        if (!savedAdmin) {
            window.location.href = 'admin.php';
        } else {
            currentAdmin = JSON.parse(savedAdmin);
            document.getElementById('adminName').textContent = currentAdmin.full_name;
            document.getElementById('adminRole').textContent = 'Administrator';
            loadDashboardData();
        }

        function handleLogout() {
            localStorage.removeItem('adminData');
            window.location.href = 'admin.php';
        }

      async function loadDashboardData() {
    if (!currentAdmin) return;
    
    try {
        console.log('Loading dashboard data for:', currentAdmin.email);
        
        // Load stats first
        const statsResponse = await fetch(`${API_URL}?action=admin-get-stats&admin_email=${currentAdmin.email}`);
        const statsData = await statsResponse.json();
        console.log('Stats response:', statsData);
        
        if (statsData.success) {
            document.getElementById('pendingCount').textContent = statsData.stats.pending_items || 0;
            document.getElementById('claimsCount').textContent = statsData.stats.pending_claims || 0;
            document.getElementById('matchCount').textContent = statsData.stats.pending_matches || 0;
            document.getElementById('resolvedCount').textContent = statsData.stats.resolved_items || 0;
        }

        // Load pending items
        const pendingResponse = await fetch(`${API_URL}?action=admin-pending-items&admin_email=${currentAdmin.email}`);
        const pendingData = await pendingResponse.json();
        console.log('Pending items response:', pendingData);
        
        if (pendingData.success) {
            displayPendingItems(pendingData.items);
        } else {
            console.error('Failed to load pending items:', pendingData.message);
        }

        // Load claims
        const claimsResponse = await fetch(`${API_URL}?action=admin-get-claims&admin_email=${currentAdmin.email}`);
        const claimsData = await claimsResponse.json();
        console.log('Claims response:', claimsData);
        
        if (claimsData.success) {
            displayClaims(claimsData.claims);
        }

        // Load matches
        const matchesResponse = await fetch(`${API_URL}?action=admin-get-matches&admin_email=${currentAdmin.email}`);
        const matchesData = await matchesResponse.json();
        console.log('Matches response:', matchesData);
        
        if (matchesData.success) {
            displayMatches(matchesData.matches);
        }

    } catch (error) {
        console.error('Error loading dashboard:', error);
        showToast('Failed to load dashboard data: ' + error.message, 'error');
    }
}

        function displayPendingItems(items) {
            const container = document.getElementById('pendingItems');
            if (!items || items.length === 0) {
                container.innerHTML = '<div class="empty-state"><i class="fas fa-inbox"></i><br>No pending items to review</div>';
                return;
            }

            container.innerHTML = items.map(item => `
                <div class="item-card ${item.type}">
                    <div class="item-header">
                        <span class="item-type type-${item.type}">${item.type.toUpperCase()}</span>
                        <span class="item-date"><i class="far fa-calendar-alt"></i> ${new Date(item.created_at).toLocaleDateString()}</span>
                    </div>
                    <div class="item-title">${item.title}</div>
                    <div class="item-location"><i class="fas fa-map-marker-alt"></i> ${item.location || 'Unknown location'}</div>
                    <div class="item-reporter"><i class="fas fa-user"></i> ${item.reporter_name} | <i class="fas fa-phone"></i> ${item.reporter_phone}</div>
                    <p style="margin: 10px 0; color: #666; font-size: 14px;">${item.description.substring(0, 100)}${item.description.length > 100 ? '...' : ''}</p>
                    ${item.image_path ? `<div style="margin: 10px 0;"><img src="https://astufindit.x10.mx/index/${item.image_path}" style="width: 100%; height: 150px; object-fit: cover; border-radius: 8px;"></div>` : ''}
                    <div class="item-actions">
                        <button class="primary" onclick='openReviewModal(${JSON.stringify(item).replace(/'/g, "&apos;")})'><i class="fas fa-eye"></i> Review</button>
                        <button class="warning" onclick="findMatches(${item.id}, '${item.type}')"><i class="fas fa-link"></i> Find Matches</button>
                    </div>
                </div>
            `).join('');
        }

        function displayClaims(claims) {
            const container = document.getElementById('claimsList');
            if (!claims || claims.length === 0) {
                container.innerHTML = '<div class="empty-state"><i class="fas fa-inbox"></i><br>No claims to review</div>';
                return;
            }

            container.innerHTML = claims.map(claim => `
                <div class="claim-card ${claim.status}">
                    <div class="claim-header">
                        <span class="claim-status status-${claim.status}">${claim.status.toUpperCase()}</span>
                        <span class="item-date"><i class="far fa-calendar-alt"></i> ${new Date(claim.created_at).toLocaleDateString()}</span>
                    </div>
                    <h4 style="margin: 10px 0;">${claim.item_title}</h4>
                    <p style="color: #666; font-size: 14px;"><strong>Claimant:</strong> ${claim.claimant_name} (${claim.claimant_email})</p>
                    <p style="color: #666; font-size: 14px;"><strong>Phone:</strong> ${claim.claimant_phone || 'N/A'}</p>
                    <p style="color: #666; font-size: 14px; background: #f0f0f0; padding: 10px; border-radius: 5px; margin: 10px 0;">
                        <strong>Message:</strong> ${claim.message}
                    </p>
                    ${claim.proof_description ? `<p style="color: #666; font-size: 14px;"><strong>Proof:</strong> ${claim.proof_description}</p>` : ''}
                    ${claim.admin_notes ? `<p style="color: #f39c12; font-size: 13px;"><strong>Admin Notes:</strong> ${claim.admin_notes}</p>` : ''}
                    ${claim.status === 'pending' ? `
                        <div class="item-actions">
                            <button class="primary" onclick='openClaimModal(${JSON.stringify(claim).replace(/'/g, "&apos;")})'><i class="fas fa-gavel"></i> Review Claim</button>
                        </div>
                    ` : ''}
                </div>
            `).join('');
        }

        function displayMatches(matches) {
            const container = document.getElementById('matchesList');
            if (!matches || matches.length === 0) {
                container.innerHTML = '<div class="empty-state"><i class="fas fa-inbox"></i><br>No matches found</div>';
                return;
            }

            container.innerHTML = matches.map(match => `
                <div class="item-card">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                        <span class="item-type type-${match.status}">${match.status.toUpperCase()}</span>
                        <span style="background: #f39c12; color: white; padding: 3px 10px; border-radius: 15px; font-size: 12px;">
                            <i class="fas fa-chart-line"></i> ${match.match_confidence}% match
                        </span>
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div style="border-right: 1px solid #ddd; padding-right: 15px;">
                            <h4 style="color: #e74c3c;"><i class="fas fa-search"></i> Lost Item</h4>
                            <p><strong>${match.lost_title}</strong></p>
                            <p style="color: #666; font-size: 13px;">Reporter: ${match.lost_reporter}</p>
                            <p style="color: #666; font-size: 13px;">Phone: ${match.lost_phone}</p>
                        </div>
                        <div>
                            <h4 style="color: #27ae60;"><i class="fas fa-check-circle"></i> Found Item</h4>
                            <p><strong>${match.found_title}</strong></p>
                            <p style="color: #666; font-size: 13px;">Reporter: ${match.found_reporter}</p>
                            <p style="color: #666; font-size: 13px;">Phone: ${match.found_phone}</p>
                        </div>
                    </div>
                    ${match.status === 'pending' ? `
                        <div style="margin-top: 15px; display: flex; gap: 10px;">
                            <button class="success" onclick="updateMatchStatus(${match.id}, 'confirmed')"><i class="fas fa-check"></i> Confirm Match</button>
                            <button class="danger" onclick="updateMatchStatus(${match.id}, 'rejected')"><i class="fas fa-times"></i> Reject</button>
                        </div>
                    ` : ''}
                </div>
            `).join('');
        }

        function openReviewModal(item) {
            currentItem = item;
            const details = document.getElementById('reviewItemDetails');
            details.innerHTML = `
                <div style="background: #f8f9fa; padding: 15px; border-radius: 10px;">
                    <p><strong>Type:</strong> <span style="color: ${item.type === 'lost' ? '#e74c3c' : '#27ae60'};">${item.type.toUpperCase()}</span></p>
                    <p><strong>Title:</strong> ${item.title}</p>
                    <p><strong>Description:</strong> ${item.description}</p>
                    <p><strong>Location:</strong> ${item.location || 'Unknown'}</p>
                    <p><strong>Reporter:</strong> ${item.reporter_name} (${item.reporter_phone})</p>
                    <p><strong>Submitted:</strong> ${new Date(item.created_at).toLocaleString()}</p>
                </div>
            `;
            document.getElementById('reviewModal').style.display = 'block';
        }

        function openClaimModal(claim) {
            currentClaim = claim;
            const details = document.getElementById('claimDetails');
            details.innerHTML = `
                <div style="background: #f8f9fa; padding: 15px; border-radius: 10px;">
                    <h4 style="margin-bottom: 10px; color: #333;">Item: ${claim.item_title}</h4>
                    <p><strong>Claimant:</strong> ${claim.claimant_name}</p>
                    <p><strong>Email:</strong> ${claim.claimant_email}</p>
                    <p><strong>Phone:</strong> ${claim.claimant_phone || 'N/A'}</p>
                    <p><strong>Claim Message:</strong></p>
                    <p style="background: white; padding: 10px; border-radius: 5px;">${claim.message}</p>
                    ${claim.proof_description ? `
                        <p><strong>Proof Provided:</strong></p>
                        <p style="background: white; padding: 10px; border-radius: 5px;">${claim.proof_description}</p>
                    ` : ''}
                    <p><strong>Submitted:</strong> ${new Date(claim.created_at).toLocaleString()}</p>
                </div>
            `;
            document.getElementById('claimModal').style.display = 'block';
        }

async function reviewItem(action) {
    const notes = document.getElementById('adminNotes').value;
    
    try {
        console.log('Reviewing item:', currentItem.id, 'Action:', action);
        
        const response = await fetch(`${API_URL}?action=admin-review-item`, {
            method: 'POST',
            headers: { 
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                admin_email: currentAdmin.email,
                item_id: currentItem.id,
                review_action: action,
                admin_notes: notes
            })
        });
        
        const data = await response.json();
        console.log('Review response:', data);
        
        if (data.success) {
            closeModal('reviewModal');
            loadDashboardData();
            showToast(`Item ${action}d successfully`, 'success');
        } else {
            showToast(data.message || 'Failed to process review', 'error');
        }
    } catch (error) {
        console.error('Review error:', error);
        showToast('Error: ' + error.message, 'error');
    }
}

async function reviewClaim(action) {
    const notes = document.getElementById('claimAdminNotes').value;
    
    try {
        console.log('Reviewing claim:', currentClaim.id, 'Action:', action);
        
        const response = await fetch(`${API_URL}?action=admin-review-claim`, {
            method: 'POST',
            headers: { 
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                admin_email: currentAdmin.email,
                claim_id: currentClaim.id,
                claim_action: action,
                admin_notes: notes
            })
        });
        
        const data = await response.json();
        console.log('Claim review response:', data);
        
        if (data.success) {
            closeModal('claimModal');
            loadDashboardData();
            showToast(`Claim ${action}d successfully`, 'success');
        } else {
            showToast(data.message || 'Failed to process claim', 'error');
        }
    } catch (error) {
        console.error('Claim review error:', error);
        showToast('Error: ' + error.message, 'error');
    }
}

async function updateMatchStatus(matchId, status) {
    try {
        console.log('Updating match:', matchId, 'Status:', status);
        
        const response = await fetch(`${API_URL}?action=admin-update-match`, {
            method: 'POST',
            headers: { 
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                admin_email: currentAdmin.email,
                match_id: matchId,
                status: status
            })
        });
        
        const data = await response.json();
        console.log('Match update response:', data);
        
        if (data.success) {
            loadDashboardData();
            showToast(`Match ${status}`, 'success');
        } else {
            showToast(data.message || 'Failed to update match', 'error');
        }
    } catch (error) {
        console.error('Match update error:', error);
        showToast('Error: ' + error.message, 'error');
    }
}

        async function updateMatchStatus(matchId, status) {
            try {
                const response = await fetch(`${API_URL}?action=admin-update-match`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        admin_email: currentAdmin.email,
                        match_id: matchId,
                        status: status
                    })
                });
                
                const data = await response.json();
                if (data.success) {
                    loadDashboardData();
                    showToast(`Match ${status}`, 'success');
                } else {
                    showToast(data.message, 'error');
                }
            } catch (error) {
                showToast('Failed to update match', 'error');
            }
        }

        function switchTab(tab) {
            document.querySelectorAll('.nav-tabs button').forEach(btn => btn.classList.remove('active'));
            document.getElementById(`tab${tab.charAt(0).toUpperCase() + tab.slice(1)}`).classList.add('active');
            
            document.getElementById('pendingView').style.display = tab === 'pending' ? 'block' : 'none';
            document.getElementById('claimsView').style.display = tab === 'claims' ? 'block' : 'none';
            document.getElementById('matchesView').style.display = tab === 'matches' ? 'block' : 'none';
            document.getElementById('resolvedView').style.display = tab === 'resolved' ? 'block' : 'none';
        }

        function findMatches(itemId, type) {
            // Implement find matches functionality
            showToast('Finding matches...', 'success');
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
            document.getElementById('adminNotes').value = '';
            document.getElementById('claimAdminNotes').value = '';
        }

        function showToast(message, type = 'success') {
            const toast = document.getElementById('toast');
            const icon = document.getElementById('toastIcon');
            const msg = document.getElementById('toastMessage');
            
            icon.className = `fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'}`;
            msg.textContent = message;
            toast.className = `toast ${type}`;
            toast.style.display = 'flex';
            
            setTimeout(() => {
                toast.style.display = 'none';
            }, 3000);
        }

        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
            }
        }
    </script>
</body>
</html>