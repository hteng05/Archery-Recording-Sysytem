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

// Get all archers
$archers = getAllArchers();

// Get selected archer
$selectedArcherId = isset($_GET['archer_id']) ? intval($_GET['archer_id']) : 0;

// If no archer selected and there are archers, use the first one
if ($selectedArcherId <= 0 && !empty($archers)) {
    $selectedArcherId = $archers[0]['ArcherID'];
}

$selectedArcher = null;
if ($selectedArcherId > 0) {
    $selectedArcher = getArcherById($selectedArcherId);
}

// Get personal bests
$personalBests = [];
if ($selectedArcher) {
    $personalBests = getPersonalBests($selectedArcherId);
}

// Process action if any
$message = '';
$messageType = '';

if (isset($_GET['action']) && isset($_GET['id'])) {
    $action = $_GET['action'];
    $scoreId = intval($_GET['id']);
    
    if ($action === 'reset_pb') {
        // Reset the personal best status for this score
        $conn = getDbConnection();
        if ($conn) {
            // Begin transaction
            $conn->begin_transaction();
            
            try {
                // First, get the personal best to remove
                $stmt = $conn->prepare("SELECT pb.ArcherID, pb.RoundID, pb.EquipmentID FROM PersonalBestTable pb 
                                      JOIN ScoreTable s ON pb.ScoreID = s.ScoreID 
                                      WHERE s.ScoreID = ?");
                $stmt->bind_param("i", $scoreId);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows === 1) {
                    $pb = $result->fetch_assoc();
                    
                    // Remove the personal best
                    $stmt = $conn->prepare("DELETE FROM PersonalBestTable WHERE ArcherID = ? AND RoundID = ? AND EquipmentID = ?");
                    $stmt->bind_param("iii", $pb['ArcherID'], $pb['RoundID'], $pb['EquipmentID']);
                    $stmt->execute();
                    
                    // Update the score to remove PB flag
                    $stmt = $conn->prepare("UPDATE ScoreTable SET IsPersonalBest = 0 WHERE ScoreID = ?");
                    $stmt->bind_param("i", $scoreId);
                    $stmt->execute();
                    
                    // Commit transaction
                    $conn->commit();
                    
                    $message = 'Personal best has been reset successfully.';
                    $messageType = 'success';
                } else {
                    throw new Exception('Personal best not found');
                }
            } catch (Exception $e) {
                // Rollback on error
                $conn->rollback();
                $message = 'Error resetting personal best: ' . $e->getMessage();
                $messageType = 'error';
            }
        }
    }
}

// Group personal bests by equipment type
$groupedBests = [];
if (!empty($personalBests)) {
    foreach ($personalBests as $pb) {
        $equipmentName = $pb['EquipmentName'];
        if (!isset($groupedBests[$equipmentName])) {
            $groupedBests[$equipmentName] = [];
        }
        $groupedBests[$equipmentName][] = $pb;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Personal Bests - Archery Score Recording System</title>
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
                    <a href="#" class="dropdown-toggle">Archers</a>
                    <div class="dropdown-menu">
                        <a href="manage_archers.php">Manage Archers</a>
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
                    <a href="#" class="dropdown-toggle active">Records</a>
                    <div class="dropdown-menu">
                        <a href="club_records.php">Club Records</a>
                        <a href="personal_bests.php" class="active">Personal Bests</a>
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
            <div class="personal-bests-container">
                <div class="page-header">
                    <h2><i class="fas fa-medal"></i> Personal Bests</h2>
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
                
                <div class="archer-selector">
                    <form action="personal_bests.php" method="get">
                        <div class="form-group">
                            <label for="archer">Select Archer:</label>
                            <select id="archer" name="archer_id" class="form-control" onchange="this.form.submit()">
                                <option value="">-- Select Archer --</option>
                                <?php foreach ($archers as $archer): ?>
                                    <option value="<?php echo $archer['ArcherID']; ?>" <?php echo ($selectedArcherId === $archer['ArcherID']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($archer['FirstName'] . ' ' . $archer['LastName']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </form>
                </div>
                
                <?php if ($selectedArcher): ?>
                    <div class="archer-info">
                        <div class="archer-details">
                            <h3><?php echo htmlspecialchars($selectedArcher['FirstName'] . ' ' . $selectedArcher['LastName']); ?></h3>
                            <div class="detail-row">
                                <span class="detail-label">Class:</span>
                                <span class="detail-value"><?php echo htmlspecialchars($selectedArcher['ClassName']); ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Division:</span>
                                <span class="detail-value"><?php echo htmlspecialchars($selectedArcher['DivisionName']); ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Default Equipment:</span>
                                <span class="detail-value"><?php echo htmlspecialchars($selectedArcher['EquipmentName']); ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <?php if (empty($personalBests)): ?>
                        <div class="no-pbs">
                            <i class="fas fa-medal"></i>
                            <h3>No Personal Bests</h3>
                            <p>This archer hasn't recorded any scores yet.</p>
                        </div>
                    <?php else: ?>
                        <div class="equipment-tabs-container">
                            <div class="equipment-tabs">
                                <ul class="nav-tabs">
                                    <?php $firstTab = true; ?>
                                    <?php foreach (array_keys($groupedBests) as $index => $equipment): ?>
                                        <li class="tab-item <?php echo $firstTab ? 'active' : ''; ?>">
                                            <a href="#equipment-<?php echo $index; ?>" class="tab-link">
                                                <?php echo htmlspecialchars($equipment); ?>
                                            </a>
                                        </li>
                                        <?php $firstTab = false; ?>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                            
                            <div class="tab-content">
                                <?php $firstTab = true; ?>
                                <?php foreach ($groupedBests as $equipment => $bests): ?>
                                    <div id="equipment-<?php echo array_search($equipment, array_keys($groupedBests)); ?>" class="tab-pane <?php echo $firstTab ? 'active' : ''; ?>">
                                        <div class="pbs-table-container">
                                            <table class="pbs-table">
                                                <thead>
                                                    <tr>
                                                        <th>Round</th>
                                                        <th>Score</th>
                                                        <th>Date Achieved</th>
                                                        <th>Status</th>
                                                        <th>Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($bests as $pb): ?>
                                                        <tr>
                                                            <td><?php echo htmlspecialchars($pb['RoundName']); ?></td>
                                                            <td class="score-cell"><?php echo $pb['TotalScore']; ?></td>
                                                            <td><?php echo formatDate($pb['DateAchieved']); ?></td>
                                                            <td>
                                                                <?php if (isset($pb['IsClubBest']) && $pb['IsClubBest']): ?>
                                                                    <span class="badge badge-cb">Club Best</span>
                                                                <?php else: ?>
                                                                    <span class="badge badge-pb">Personal Best</span>
                                                                <?php endif; ?>
                                                            </td>
                                                            <td>
                                                                <div class="action-buttons">
                                                                    <a href="score_details.php?id=<?php echo $pb['ScoreID']; ?>" class="btn btn-sm btn-secondary" title="View Details">
                                                                        <i class="fas fa-eye"></i>
                                                                    </a>
                                                                    <a href="personal_bests.php?action=reset_pb&id=<?php echo $pb['ScoreID']; ?>&archer_id=<?php echo $selectedArcherId; ?>" class="btn btn-sm btn-danger" title="Reset Personal Best" onclick="return confirm('Are you sure you want to reset this personal best? This action cannot be undone.');">
                                                                        <i class="fas fa-times-circle"></i>
                                                                    </a>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                    <?php $firstTab = false; ?>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        
                        <div class="export-buttons">
                            <button type="button" class="btn btn-secondary" onclick="exportPBs('csv')">
                                <i class="fas fa-file-csv"></i> Export to CSV
                            </button>
                            <button type="button" class="btn btn-secondary" onclick="exportPBs('pdf')">
                                <i class="fas fa-file-pdf"></i> Export to PDF
                            </button>
                            <button type="button" class="btn btn-secondary" onclick="printPBs()">
                                <i class="fas fa-print"></i> Print Records
                            </button>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="no-archer-selected">
                        <i class="fas fa-user"></i>
                        <h3>No Archer Selected</h3>
                        <p>Please select an archer from the dropdown to view their personal bests.</p>
                    </div>
                <?php endif; ?>
            </div>
        </main>

        <footer>
            <p>&copy; 2025 Archery Club Database System. All rights reserved.</p>
        </footer>
    </div>

    <script src="../js/main.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Tabs functionality
            const tabLinks = document.querySelectorAll('.tab-link');
            const tabPanes = document.querySelectorAll('.tab-pane');
            
            tabLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    // Remove active class from all tabs and panes
                    tabLinks.forEach(l => l.parentElement.classList.remove('active'));
                    tabPanes.forEach(p => p.classList.remove('active'));
                    
                    // Add active class to clicked tab and corresponding pane
                    this.parentElement.classList.add('active');
                    const targetId = this.getAttribute('href');
                    document.querySelector(targetId).classList.add('active');
                });
            });
        });
        
        function exportPBs(format) {
            // This would connect to a server-side export script in a real implementation
            alert('Export to ' + format.toUpperCase() + ' would be implemented here.');
        }
        
        function printPBs() {
            window.print();
        }
    </script>
    
    <style>
        /* Personal Bests Page Specific Styles */
        .personal-bests-container {
            padding: 1rem;
        }
        
        .page-header {
            margin-bottom: 1.5rem;
        }
        
        .page-header h2 {
            color: var(--primary-color);
            margin: 0;
        }
        
        .page-header h2 i {
            margin-right: 0.5rem;
        }
        
        .archer-selector {
            background-color: white;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        .archer-selector .form-group {
            margin: 0;
            display: flex;
            align-items: center;
        }
        
        .archer-selector label {
            margin-right: 1rem;
            margin-bottom: 0;
            font-weight: 500;
            min-width: 150px;
        }
        
        .archer-info {
            background-color: white;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
        }
        
        .archer-details h3 {
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }
        
        .detail-row {
            margin-bottom: 0.25rem;
        }
        
        .detail-label {
            font-weight: 500;
            margin-right: 0.5rem;
            color: #6b7280;
        }
        
        .equipment-tabs-container {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            margin-bottom: 1.5rem;
            overflow: hidden;
        }
        
        .equipment-tabs {
            padding: 0 1.5rem;
            background-color: #f9fafb;
            border-bottom: 1px solid var(--gray-color);
        }
        
        .nav-tabs {
            display: flex;
            list-style: none;
            padding: 0;
            margin: 0;
            overflow-x: auto;
            white-space: nowrap;
        }
        
        .tab-item {
            margin-right: 0.5rem;
        }
        
        .tab-link {
            display: block;
            padding: 1rem;
            text-decoration: none;
            color: var(--text-color);
            border-bottom: 2px solid transparent;
            transition: all 0.2s ease;
        }
        
        .tab-item.active .tab-link {
            color: var(--primary-color);
            border-bottom-color: var(--primary-color);
            font-weight: 500;
        }
        
        .tab-pane {
            display: none;
        }
        
        .tab-pane.active {
            display: block;
        }
        
        .pbs-table-container {
            padding: 1.5rem;
        }
        
        .pbs-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .pbs-table th, .pbs-table td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid var(--gray-color);
        }
        
        .pbs-table th {
            font-weight: 600;
            background-color: var(--light-color);
        }
        
        .score-cell {
            font-weight: 600;
            text-align: center;
        }
        
        .badge {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            font-size: 0.75rem;
            font-weight: 500;
            border-radius: 9999px;
        }
        
        .badge-pb {
            background-color: #fef3c7;
            color: #d97706;
        }
        
        .badge-cb {
            background-color: #dcfce7;
            color: #16a34a;
        }
        
        .action-buttons {
            display: flex;
            gap: 0.5rem;
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
        
        .no-pbs, .no-archer-selected {
            background-color: white;
            border-radius: 8px;
            padding: 3rem 1.5rem;
            text-align: center;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        .no-pbs i, .no-archer-selected i {
            font-size: 3rem;
            color: #d1d5db;
            margin-bottom: 1rem;
        }
        
        .no-pbs h3, .no-archer-selected h3 {
            color: var(--primary-color);
            margin-bottom: 1rem;
        }
        
        .export-buttons {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
            margin-top: 1.5rem;
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
            .archer-selector .form-group {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .archer-selector label {
                margin-bottom: 0.5rem;
            }
            
            .export-buttons {
                flex-direction: column;
                align-items: stretch;
            }
        }
        
        /* Print styles */
        @media print {
            .navbar, .page-header, .archer-selector, .equipment-tabs, .export-buttons, footer {
                display: none;
            }
            
            .personal-bests-container {
                padding: 0;
            }
            
            .tab-pane {
                display: block !important;
            }
            
            .tab-pane:not(:first-child) {
                margin-top: 2rem;
                border-top: 1px solid #ccc;
                padding-top: 2rem;
            }
            
            .tab-pane::before {
                content: attr(id);
                font-size: 1.2rem;
                font-weight: bold;
                margin-bottom: 1rem;
                display: block;
            }
            
            .action-buttons {
                display: none;
            }
        }
    </style>
</body>
</html>