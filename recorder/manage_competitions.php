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
    $competitionId = intval($_GET['id']);
    
    if ($action === 'delete') {
        // Check if competition has scores
        $conn = getDbConnection();
        if ($conn) {
            // Check if competition has scores
            $stmt = $conn->prepare("SELECT COUNT(*) as count FROM ScoreTable WHERE CompetitionID = ?");
            $stmt->bind_param("i", $competitionId);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            
            if ($row['count'] > 0) {
                $message = 'Cannot delete competition. It has ' . $row['count'] . ' scores associated with it.';
                $messageType = 'error';
            } else {
                // Delete competition
                $stmt = $conn->prepare("DELETE FROM CompetitionTable WHERE CompetitionID = ?");
                $stmt->bind_param("i", $competitionId);
                
                if ($stmt->execute()) {
                    $message = 'Competition has been deleted successfully.';
                    $messageType = 'success';
                } else {
                    $message = 'Error deleting competition. Please try again.';
                    $messageType = 'error';
                }
            }
        }
    } elseif ($action === 'toggle_championship') {
        // Toggle championship status
        $conn = getDbConnection();
        if ($conn) {
            $stmt = $conn->prepare("UPDATE CompetitionTable SET IsChampionship = NOT IsChampionship WHERE CompetitionID = ?");
            $stmt->bind_param("i", $competitionId);
            
            if ($stmt->execute()) {
                $message = 'Championship status has been updated successfully.';
                $messageType = 'success';
            } else {
                $message = 'Error updating championship status. Please try again.';
                $messageType = 'error';
            }
        }
    } elseif ($action === 'toggle_contribution') {
        // Toggle contribution to championship
        $conn = getDbConnection();
        if ($conn) {
            $stmt = $conn->prepare("UPDATE CompetitionTable SET ContributesToChampionship = NOT ContributesToChampionship WHERE CompetitionID = ?");
            $stmt->bind_param("i", $competitionId);
            
            if ($stmt->execute()) {
                $message = 'Championship contribution status has been updated successfully.';
                $messageType = 'success';
            } else {
                $message = 'Error updating championship contribution status. Please try again.';
                $messageType = 'error';
            }
        }
    }
}

// Get all competitions
$competitions = [];
$conn = getDbConnection();

if ($conn) {
    $query = "SELECT c.*, COUNT(s.ScoreID) as ScoreCount
              FROM CompetitionTable c
              LEFT JOIN ScoreTable s ON c.CompetitionID = s.CompetitionID
              GROUP BY c.CompetitionID
              ORDER BY c.StartDate DESC";
    
    $result = $conn->query($query);
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $competitions[] = $row;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Competitions - Archery Score Recording System</title>
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
                    <a href="#" class="dropdown-toggle active">Competitions</a>
                    <div class="dropdown-menu">
                        <a href="manage_competitions.php" class="active">Manage Competitions</a>
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
            <div class="manage-competitions-container">
                <div class="page-header">
                    <h2><i class="fas fa-trophy"></i> Manage Competitions</h2>
                    <div class="action-buttons">
                        <a href="add_competition.php" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Add New Competition
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
                
                <?php if (empty($competitions)): ?>
                    <div class="no-competitions">
                        <i class="fas fa-trophy"></i>
                        <h3>No Competitions Found</h3>
                        <p>There are no competitions in the system.</p>
                        <a href="add_competition.php" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Add New Competition
                        </a>
                    </div>
                <?php else: ?>
                    <div class="filter-section">
                        <div class="search-box">
                            <input type="text" id="competition-search" placeholder="Search competitions..." class="form-control">
                            <i class="fas fa-search"></i>
                        </div>
                        <div class="filter-controls">
                            <div class="form-check">
                                <input type="checkbox" id="show-past" class="form-check-input" checked>
                                <label for="show-past" class="form-check-label">Show past competitions</label>
                            </div>
                            <div class="form-check">
                                <input type="checkbox" id="show-upcoming" class="form-check-input" checked>
                                <label for="show-upcoming" class="form-check-label">Show upcoming competitions</label>
                            </div>
                            <div class="form-check">
                                <input type="checkbox" id="show-championships" class="form-check-input" checked>
                                <label for="show-championships" class="form-check-label">Show championships only</label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="competitions-table-container">
                        <table class="data-table competitions-table">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Date</th>
                                    <th>Location</th>
                                    <th>Status</th>
                                    <th>Championship</th>
                                    <th>Scores</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                    $today = date('Y-m-d');
                                    foreach ($competitions as $competition): 
                                    $isPast = $competition['EndDate'] < $today;
                                    $isUpcoming = $competition['StartDate'] > $today;
                                    $isOngoing = !$isPast && !$isUpcoming;
                                ?>
                                    <tr class="<?php echo $isPast ? 'past-competition' : ($isUpcoming ? 'upcoming-competition' : 'ongoing-competition'); ?>" data-championship="<?php echo $competition['IsChampionship'] ? '1' : '0'; ?>">
                                        <td class="competition-name"><?php echo htmlspecialchars($competition['CompetitionName']); ?></td>
                                        <td>
                                            <?php 
                                                if ($competition['StartDate'] === $competition['EndDate']) {
                                                    echo formatDate($competition['StartDate']);
                                                } else {
                                                    echo formatDate($competition['StartDate']) . ' - ' . formatDate($competition['EndDate']);
                                                }
                                            ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($competition['Location']); ?></td>
                                        <td>
                                            <?php if ($isPast): ?>
                                                <span class="status-badge status-past">Completed</span>
                                            <?php elseif ($isUpcoming): ?>
                                                <span class="status-badge status-upcoming">Upcoming</span>
                                            <?php else: ?>
                                                <span class="status-badge status-ongoing">Ongoing</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($competition['IsChampionship']): ?>
                                                <span class="status-badge status-championship">Championship</span>
                                                <?php if ($competition['ContributesToChampionship']): ?>
                                                    <span class="status-badge status-contributes">Contributes to Club Championship</span>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="status-badge status-regular">Regular</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php echo $competition['ScoreCount']; ?> scores
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <a href="edit_competition.php?id=<?php echo $competition['CompetitionID']; ?>" class="btn btn-sm btn-secondary" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <?php if ($competition['IsChampionship']): ?>
                                                    <a href="manage_competitions.php?action=toggle_championship&id=<?php echo $competition['CompetitionID']; ?>" class="btn btn-sm btn-warning" title="Remove Championship Status" onclick="return confirm('Are you sure you want to remove championship status from this competition?');">
                                                        <i class="fas fa-trophy"></i>
                                                    </a>
                                                <?php else: ?>
                                                    <a href="manage_competitions.php?action=toggle_championship&id=<?php echo $competition['CompetitionID']; ?>" class="btn btn-sm btn-success" title="Set as Championship" onclick="return confirm('Are you sure you want to set this competition as a championship?');">
                                                        <i class="fas fa-trophy"></i>
                                                    </a>
                                                <?php endif; ?>
                                                
                                                <?php if ($competition['ContributesToChampionship']): ?>
                                                    <a href="manage_competitions.php?action=toggle_contribution&id=<?php echo $competition['CompetitionID']; ?>" class="btn btn-sm btn-warning" title="Remove Club Championship Contribution" onclick="return confirm('Are you sure you want to remove this competition\'s contribution to the club championship?');">
                                                        <i class="fas fa-medal"></i>
                                                    </a>
                                                <?php else: ?>
                                                    <a href="manage_competitions.php?action=toggle_contribution&id=<?php echo $competition['CompetitionID']; ?>" class="btn btn-sm btn-success" title="Set to Contribute to Club Championship" onclick="return confirm('Are you sure you want to set this competition to contribute to the club championship?');">
                                                        <i class="fas fa-medal"></i>
                                                    </a>
                                                <?php endif; ?>
                                                
                                                <a href="competition_results.php?id=<?php echo $competition['CompetitionID']; ?>" class="btn btn-sm btn-primary" title="View Results">
                                                    <i class="fas fa-list"></i>
                                                </a>
                                                
                                                <?php if ($competition['ScoreCount'] === 0): ?>
                                                    <a href="manage_competitions.php?action=delete&id=<?php echo $competition['CompetitionID']; ?>" class="btn btn-sm btn-danger" title="Delete" onclick="return confirm('Are you sure you want to delete this competition? This action cannot be undone.');">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
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
            // Search functionality
            const searchInput = document.getElementById('competition-search');
            const competitionRows = document.querySelectorAll('.competitions-table tbody tr');
            
            searchInput.addEventListener('input', function() {
                const searchTerm = this.value.toLowerCase();
                
                competitionRows.forEach(row => {
                    const competitionName = row.querySelector('.competition-name').textContent.toLowerCase();
                    const shouldShow = competitionName.includes(searchTerm);
                    
                    if (shouldShow) {
                        filterByStatus(row);
                    } else {
                        row.style.display = 'none';
                    }
                });
            });
            
            // Status filter functionality
            const showPastCheckbox = document.getElementById('show-past');
            const showUpcomingCheckbox = document.getElementById('show-upcoming');
            const showChampionshipsCheckbox = document.getElementById('show-championships');
            
            function filterByStatus(row) {
                const isPast = row.classList.contains('past-competition');
                const isUpcoming = row.classList.contains('upcoming-competition');
                const isChampionship = row.getAttribute('data-championship') === '1';
                
                const showPast = showPastCheckbox.checked;
                const showUpcoming = showUpcomingCheckbox.checked;
                const showChampionshipsOnly = showChampionshipsCheckbox.checked;
                
                let shouldShow = true;
                
                if (isPast && !showPast) shouldShow = false;
                if (isUpcoming && !showUpcoming) shouldShow = false;
                if (showChampionshipsOnly && !isChampionship) shouldShow = false;
                
                row.style.display = shouldShow ? '' : 'none';
            }
            
            showPastCheckbox.addEventListener('change', function() {
                competitionRows.forEach(row => filterByStatus(row));
            });
            
            showUpcomingCheckbox.addEventListener('change', function() {
                competitionRows.forEach(row => filterByStatus(row));
            });
            
            showChampionshipsCheckbox.addEventListener('change', function() {
                competitionRows.forEach(row => filterByStatus(row));
            });
        });
    </script>
    
    <style>
        /* Manage Competitions Page Specific Styles */
        .manage-competitions-container {
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
            flex-wrap: wrap;
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
        
        .competitions-table-container {
            background-color: white;
            border-radius: 8px;
            padding: 1.5rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            overflow-x: auto;
        }
        
        .competitions-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .competitions-table th, .competitions-table td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid var(--gray-color);
        }
        
        .competitions-table th {
            background-color: var(--light-color);
            font-weight: 600;
        }
        
        .status-badge {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 500;
            margin-right: 0.5rem;
            margin-bottom: 0.25rem;
        }
        
        .status-past {
            background-color: #e5e7eb;
            color: #4b5563;
        }
        
        .status-upcoming {
            background-color: #dbeafe;
            color: #2563eb;
        }
        
        .status-ongoing {
            background-color: #dcfce7;
            color: #16a34a;
        }
        
        .status-championship {
            background-color: #fef3c7;
            color: #d97706;
        }
        
        .status-contributes {
            background-color: #fce7f3;
            color: #db2777;
        }
        
        .status-regular {
            background-color: #f3f4f6;
            color: #6b7280;
        }
        
        .action-buttons {
            display: flex;
            flex-wrap: wrap;
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
        
        .btn-success {
            background-color: #22c55e;
            color: white;
        }
        
        .btn-success:hover {
            background-color: #16a34a;
        }
        
        .btn-warning {
            background-color: #f59e0b;
            color: white;
        }
        
        .btn-warning:hover {
            background-color: #d97706;
        }
        
        .past-competition {
            background-color: rgba(243, 244, 246, 0.3);
        }
        
        .no-competitions {
            background-color: white;
            border-radius: 8px;
            padding: 3rem 1.5rem;
            text-align: center;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        .no-competitions i {
            font-size: 3rem;
            color: #d1d5db;
            margin-bottom: 1rem;
        }
        
        .no-competitions h3 {
            color: var(--primary-color);
            margin-bottom: 1rem;
        }
        
        .no-competitions p {
            margin-bottom: 1.5rem;
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
            
            .page-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }
            
            .action-buttons {
                flex-wrap: wrap;
            }
        }
    </style>
</body>
</html>