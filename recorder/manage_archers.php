<?php
require_once '../includes/settings.php';
require_once '../includes/auth.php';
require_once '../includes/db_functions.php';

// Require recorder login
requireRecorderLogin();

// Check session timeout
checkSessionTimeout();

// Get recorder data
$recorder = getCurrentRecorder();

// Process action if any
$message = '';
$messageType = '';

if (isset($_GET['action']) && isset($_GET['id'])) {
    $action = $_GET['action'];
    $archerId = intval($_GET['id']);
    
    if ($action === 'deactivate') {
        // Deactivate archer
        $conn = getDbConnection();
        if ($conn) {
            $stmt = $conn->prepare("UPDATE ArcherTable SET IsActive = 0 WHERE ArcherID = ?");
            $stmt->bind_param("i", $archerId);
            
            if ($stmt->execute()) {
                $message = 'Archer has been deactivated successfully.';
                $messageType = 'success';
            } else {
                $message = 'Error deactivating archer. Please try again.';
                $messageType = 'error';
            }
        }
    } elseif ($action === 'activate') {
        // Activate archer
        $conn = getDbConnection();
        if ($conn) {
            $stmt = $conn->prepare("UPDATE ArcherTable SET IsActive = 1 WHERE ArcherID = ?");
            $stmt->bind_param("i", $archerId);
            
            if ($stmt->execute()) {
                $message = 'Archer has been activated successfully.';
                $messageType = 'success';
            } else {
                $message = 'Error activating archer. Please try again.';
                $messageType = 'error';
            }
        }
    }
}

// Get all archers
$archers = [];
$conn = getDbConnection();

if ($conn) {
    $query = "SELECT a.*, c.ClassName, d.DivisionName, e.EquipmentName 
              FROM ArcherTable a
              JOIN ClassTable c ON a.ClassID = c.ClassID
              JOIN DivisionTable d ON a.DefaultDivisionID = d.DivisionID
              JOIN EquipmentTable e ON a.DefaultEquipmentID = e.EquipmentID
              ORDER BY a.IsActive DESC, a.LastName, a.FirstName";
    
    $result = $conn->query($query);
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $archers[] = $row;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Archers - Archery Score Recording System</title>
    <link rel="stylesheet" href="../css/main.css">
    <link rel="stylesheet" href="../css/navbar_recorder.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="container">
        <header>
            <div class="logo">
                <img src="../images/archery-logo.png" alt="Archery Club Logo" onerror="this.src='../images/default-logo.png'">
                <h1>Archery Score Recording System</h1>
            </div>
        </header>

        <div class="navbar">
            <div class="navbar-left">
                <a href="dashboard.php">Dashboard</a>
                <div class="dropdown">
                    <a href="#" class="dropdown-toggle active">Archers</a>
                    <div class="dropdown-menu">
                        <a href="manage_archers.php" class="active">Manage Archers</a>
                        <a href="add_archer.php">Add New Archer</a>
                    </div>
                </div>
                <div class="dropdown">
                    <a href="#" class="dropdown-toggle">Scores</a>
                    <div class="dropdown-menu">
                        <a href="pending_scores.php">Approve Pending Scores</a>
                        <a href="enter_score.php">Enter New Score</a>
                        <a href="view_scores.php">View All Scores</a>
                    </div>
                </div>
                <div class="dropdown">
                    <a href="#" class="dropdown-toggle">Competitions</a>
                    <div class="dropdown-menu">
                        <a href="manage_competitions.php">Manage Competitions</a>
                        <a href="add_competition.php">Add New Competition</a>
                        <a href="competition_results.php">View Results</a>
                    </div>
                </div>
                <div class="dropdown">
                    <a href="#" class="dropdown-toggle">Championships</a>
                    <div class="dropdown-menu">
                        <a href="manage_championships.php">Manage Championships</a>
                        <a href="championship_standings.php">View Standings</a>
                    </div>
                </div>
                <div class="dropdown">
                    <a href="#" class="dropdown-toggle">Records</a>
                    <div class="dropdown-menu">
                        <a href="club_records.php">Club Records</a>
                        <a href="personal_bests.php">Personal Bests</a>
                    </div>
                </div>
            </div>
            <div class="navbar-right">
                <div class="dropdown">
                    <a href="#" class="dropdown-toggle">
                        <i class="fas fa-user"></i> <?php echo htmlspecialchars($recorder['FirstName']); ?>
                    </a>
                    <div class="dropdown-menu dropdown-menu-right">
                        <a href="profile.php">My Profile</a>
                        <a href="change_password.php">Change Password</a>
                        <a href="logout.php">Logout</a>
                    </div>
                </div>
            </div>
        </div>

        <main>
            <div class="manage-archers-container">
                <div class="page-header">
                    <h2><i class="fas fa-users"></i> Manage Archers</h2>
                    <div class="action-buttons">
                        <a href="add_archer.php" class="btn btn-primary">
                            <i class="fas fa-user-plus"></i> Add New Archer
                        </a>
                    </div>
                </div>
                
                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $messageType; ?>">
                        <?php if ($messageType === 'success'): ?>
                            <i class="fas fa-check-circle"></i>
                        <?php else: ?>
                            <i class="fas fa-exclamation-circle"></i>
                        <?php endif; ?>
                        <?php echo $message; ?>
                    </div>
                <?php endif; ?>
                
                <div class="filter-section">
                    <div class="search-box">
                        <input type="text" id="archer-search" placeholder="Search archers..." class="form-control">
                        <i class="fas fa-search"></i>
                    </div>
                    <div class="filter-controls">
                        <div class="form-check">
                            <input type="checkbox" id="show-inactive" class="form-check-input">
                            <label for="show-inactive" class="form-check-label">Show inactive archers only</label>
                        </div>
                        <div class="form-check">
                            <input type="checkbox" id="show-active" class="form-check-input" checked>
                            <label for="show-active" class="form-check-label">Show active archers only</label>
                        </div>
                    </div>
                </div>
                
                <div class="archers-table-container">
                    <table class="data-table archers-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Gender</th>
                                <th>DOB</th>
                                <th>Class</th>
                                <th>Division</th>
                                <th>Default Equipment</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($archers as $archer): ?>
                                <tr class="<?php echo $archer['IsActive'] ? 'active-archer' : 'inactive-archer'; ?>">
                                    <td><?php echo htmlspecialchars($archer['FirstName'] . ' ' . $archer['LastName']); ?></td>
                                    <td><?php echo $archer['Gender']; ?></td>
                                    <td><?php echo formatDate($archer['DOB']); ?></td>
                                    <td><?php echo htmlspecialchars($archer['ClassName']); ?></td>
                                    <td><?php echo htmlspecialchars($archer['DivisionName']); ?></td>
                                    <td><?php echo htmlspecialchars($archer['EquipmentName']); ?></td>
                                    <td>
                                        <?php if ($archer['IsActive']): ?>
                                            <span class="status-badge status-active">Active</span>
                                        <?php else: ?>
                                            <span class="status-badge status-inactive">Inactive</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="edit_archer.php?id=<?php echo $archer['ArcherID']; ?>" class="btn btn-sm btn-secondary" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <?php if ($archer['IsActive']): ?>
                                                <a href="manage_archers.php?action=deactivate&id=<?php echo $archer['ArcherID']; ?>" class="btn btn-sm btn-danger" title="Deactivate" onclick="return confirm('Are you sure you want to deactivate this archer?');">
                                                    <i class="fas fa-user-times"></i>
                                                </a>
                                            <?php else: ?>
                                                <a href="manage_archers.php?action=activate&id=<?php echo $archer['ArcherID']; ?>" class="btn btn-sm btn-success" title="Activate">
                                                    <i class="fas fa-user-check"></i>
                                                </a>
                                            <?php endif; ?>
                                            <a href="view_archer_scores.php?id=<?php echo $archer['ArcherID']; ?>" class="btn btn-sm btn-primary" title="View Scores">
                                                <i class="fas fa-chart-line"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>

        <footer>
            <p>&copy; 2025 Archery Club Database System. All rights reserved.</p>
        </footer>
    </div>

    <script src="../js/main.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Search functionality
            const searchInput = document.getElementById('archer-search');
            const archerRows = document.querySelectorAll('.archers-table tbody tr');
            
            searchInput.addEventListener('input', function() {
                const searchTerm = this.value.toLowerCase();
                
                archerRows.forEach(row => {
                    const rowText = row.textContent.toLowerCase();
                    const shouldShow = rowText.includes(searchTerm);
                    
                    if (shouldShow) {
                        filterByStatus(row);
                    } else {
                        row.style.display = 'none';
                    }
                });
            });
            
            // Status filter functionality
            const showInactiveCheckbox = document.getElementById('show-inactive');
            const showActiveCheckbox = document.getElementById('show-active');
            
            function filterByStatus(row) {
                const isActive = row.classList.contains('active-archer');
                const showActive = showActiveCheckbox.checked;
                const showInactive = showInactiveCheckbox.checked;
                
                if ((isActive && showActive) || (!isActive && showInactive)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            }
            
            showInactiveCheckbox.addEventListener('change', function() {
                archerRows.forEach(row => filterByStatus(row));
            });
            
            showActiveCheckbox.addEventListener('change', function() {
                archerRows.forEach(row => filterByStatus(row));
            });
            
            // Initial filter
            archerRows.forEach(row => filterByStatus(row));
        });
    </script>
    
    <style>
        /* Manage Archers Page Specific Styles */
        .manage-archers-container {
            padding: 1rem;
        }
        
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        
        .page-header h2 {
            color: var(--primary-color);
            margin: 0;
        }
        
        .page-header h2 i {
            margin-right: 0.5rem;
        }
        
        .filter-section {
            background-color: white;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            align-items: center;
        }
        
        .search-box {
            position: relative;
            flex: 1;
            min-width: 250px;
        }
        
        .search-box input {
            padding-left: 2.5rem;
        }
        
        .search-box i {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #6b7280;
        }
        
        .filter-controls {
            display: flex;
            gap: 1.5rem;
        }
        
        .form-check {
            display: flex;
            align-items: center;
            margin: 0;
        }
        
        .form-check-input {
            margin-right: 0.5rem;
        }
        
        .archers-table-container {
            background-color: white;
            border-radius: 8px;
            padding: 1.5rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            overflow-x: auto;
        }
        
        .archers-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .archers-table th, .archers-table td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid var(--gray-color);
        }
        
        .archers-table th {
            background-color: var(--light-color);
            font-weight: 600;
        }
        
        .status-badge {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 500;
        }
        
        .status-active {
            background-color: #dcfce7;
            color: #16a34a;
        }
        
        .status-inactive {
            background-color: #fee2e2;
            color: #dc2626;
        }
        
        .action-buttons {
            display: flex;
            gap: 0.5rem;
        }
        
        .inactive-archer {
            background-color: rgba(243, 244, 246, 0.5);
        }
        
        .btn-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
        }
        
        .btn-danger {
            background-color: #ef4444;
            color: white;
        }
        
        .btn-danger:hover {
            background-color: #dc2626;
        }
        
        .btn-success {
            background-color: #22c55e;
            color: white;
        }
        
        .btn-success:hover {
            background-color: #16a34a;
        }
        
        .alert {
            padding: 0.75rem 1rem;
            margin-bottom: 1rem;
            border-radius: 4px;
        }
        
        .alert-success {
            background-color: #dcfce7;
            color: #16a34a;
            border-left: 4px solid #16a34a;
        }
        
        .alert-error {
            background-color: #fee2e2;
            color: #dc2626;
            border-left: 4px solid #dc2626;
        }
        
        .alert i {
            margin-right: 0.5rem;
        }
        
        /* Responsive adjustments */
        @media (max-width: 992px) {
            .filter-section {
                flex-direction: column;
                align-items: stretch;
            }
            
            .filter-controls {
                flex-direction: column;
                gap: 0.5rem;
            }
            
            .action-buttons {
                flex-wrap: wrap;
            }
        }
    </style>
</body>
</html>