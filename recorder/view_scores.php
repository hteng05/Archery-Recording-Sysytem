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

// Get filter parameters
$archerId = isset($_GET['archer_id']) ? intval($_GET['archer_id']) : 0;
$startDate = isset($_GET['start_date']) ? sanitizeInput($_GET['start_date']) : '';
$endDate = isset($_GET['end_date']) ? sanitizeInput($_GET['end_date']) : '';
$roundId = isset($_GET['round_id']) ? intval($_GET['round_id']) : 0;
$competitionId = isset($_GET['competition_id']) ? intval($_GET['competition_id']) : 0;
$isPractice = isset($_GET['is_practice']) ? intval($_GET['is_practice']) : -1;
$sortBy = isset($_GET['sort_by']) ? sanitizeInput($_GET['sort_by']) : 'date';
$sortOrder = isset($_GET['sort_order']) ? sanitizeInput($_GET['sort_order']) : 'desc';

// Get all archers, rounds, and competitions for filter
$archers = getAllArchers();
$rounds = getAllRounds();
$competitions = getAllCompetitions(true);

// Get scores with filters
$scores = [];
$conn = getDbConnection();

if ($conn) {
    $query = "SELECT s.*, r.RoundName, e.EquipmentName, c.CompetitionName, 
              a.FirstName, a.LastName, cl.ClassName, d.DivisionName
              FROM ScoreTable s
              JOIN ArcherTable a ON s.ArcherID = a.ArcherID
              JOIN RoundTable r ON s.RoundID = r.RoundID
              JOIN EquipmentTable e ON s.EquipmentID = e.EquipmentID
              JOIN ClassTable cl ON a.ClassID = cl.ClassID
              JOIN DivisionTable d ON a.DefaultDivisionID = d.DivisionID
              LEFT JOIN CompetitionTable c ON s.CompetitionID = c.CompetitionID
              WHERE s.IsApproved = 1";
    
    $params = [];
    $types = "";
    
    if ($archerId > 0) {
        $query .= " AND s.ArcherID = ?";
        $params[] = $archerId;
        $types .= "i";
    }
    
    if ($startDate) {
        $query .= " AND s.DateShot >= ?";
        $params[] = $startDate;
        $types .= "s";
    }
    
    if ($endDate) {
        $query .= " AND s.DateShot <= ?";
        $params[] = $endDate;
        $types .= "s";
    }
    
    if ($roundId > 0) {
        $query .= " AND s.RoundID = ?";
        $params[] = $roundId;
        $types .= "i";
    }
    
    if ($competitionId > 0) {
        $query .= " AND s.CompetitionID = ?";
        $params[] = $competitionId;
        $types .= "i";
    }
    
    if ($isPractice >= 0) {
        $query .= " AND s.IsPractice = ?";
        $params[] = $isPractice;
        $types .= "i";
    }
    
    if ($sortBy === 'date') {
        $query .= " ORDER BY s.DateShot " . ($sortOrder === 'asc' ? 'ASC' : 'DESC');
    } elseif ($sortBy === 'score') {
        $query .= " ORDER BY s.TotalScore " . ($sortOrder === 'asc' ? 'ASC' : 'DESC');
    } elseif ($sortBy === 'archer') {
        $query .= " ORDER BY a.LastName " . ($sortOrder === 'asc' ? 'ASC' : 'DESC') . ", a.FirstName " . ($sortOrder === 'asc' ? 'ASC' : 'DESC');
    } elseif ($sortBy === 'round') {
        $query .= " ORDER BY r.RoundName " . ($sortOrder === 'asc' ? 'ASC' : 'DESC');
    }
    
    if (!empty($params)) {
        $stmt = $conn->prepare($query);
        // Use call_user_func_array instead of spread operator
        call_user_func_array(array($stmt, 'bind_param'), array_merge(array($types), $params));
        $stmt->execute();
        $result = $stmt->get_result();
    } else {
        $result = $conn->query($query);
    }
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $scores[] = $row;
        }
    }
}

// Process action if any
$message = '';
$messageType = '';

if (isset($_GET['action']) && isset($_GET['id'])) {
    $action = $_GET['action'];
    $scoreId = intval($_GET['id']);
    
    if ($action === 'delete') {
        // Delete score
        $conn = getDbConnection();
        if ($conn) {
            // Begin transaction
            $conn->begin_transaction();
            
            try {
                // First delete related arrows and ends
                $stmt = $conn->prepare("SELECT EndID FROM EndTable WHERE ScoreID = ?");
                $stmt->bind_param("i", $scoreId);
                $stmt->execute();
                $result = $stmt->get_result();
                
                while ($row = $result->fetch_assoc()) {
                    $endId = $row['EndID'];
                    
                    // Delete arrows for this end
                    $stmt = $conn->prepare("DELETE FROM ArrowTable WHERE EndID = ?");
                    $stmt->bind_param("i", $endId);
                    $stmt->execute();
                }
                
                // Delete ends
                $stmt = $conn->prepare("DELETE FROM EndTable WHERE ScoreID = ?");
                $stmt->bind_param("i", $scoreId);
                $stmt->execute();
                
                // Delete score
                $stmt = $conn->prepare("DELETE FROM ScoreTable WHERE ScoreID = ?");
                $stmt->bind_param("i", $scoreId);
                $stmt->execute();
                
                // Commit transaction
                $conn->commit();
                
                $message = 'Score has been deleted successfully.';
                $messageType = 'success';
            } catch (Exception $e) {
                // Rollback on error
                $conn->rollback();
                $message = 'Error deleting score: ' . $e->getMessage();
                $messageType = 'error';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Scores - Archery Score Recording System</title>
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
                    <a href="#" class="dropdown-toggle active">Scores</a>
                    <div class="dropdown-menu">
                        <a href="pending_scores.php">Approve Pending Scores</a>
                        <a href="enter_score.php">Enter New Score</a>
                        <a href="view_scores.php" class="active">View All Scores</a>
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
            <div class="view-scores-container">
                <div class="page-header">
                    <h2><i class="fas fa-list"></i> View All Scores</h2>
                    <div class="action-buttons">
                        <a href="enter_score.php" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Enter New Score
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
                    <h3>Filter Scores</h3>
                    <form action="view_scores.php" method="get" class="filter-form">
                        <div class="filter-row">
                            <div class="form-group">
                                <label for="archer">Archer:</label>
                                <select id="archer" name="archer_id" class="form-control">
                                    <option value="">All Archers</option>
                                    <?php foreach ($archers as $archer): ?>
                                        <option value="<?php echo $archer['ArcherID']; ?>" <?php echo ($archerId === $archer['ArcherID']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($archer['FirstName'] . ' ' . $archer['LastName']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="round">Round:</label>
                                <select id="round" name="round_id" class="form-control">
                                    <option value="">All Rounds</option>
                                    <?php foreach ($rounds as $round): ?>
                                        <option value="<?php echo $round['RoundID']; ?>" <?php echo ($roundId === $round['RoundID']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($round['RoundName']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="competition">Competition:</label>
                                <select id="competition" name="competition_id" class="form-control">
                                    <option value="">All Competitions</option>
                                    <option value="-1" <?php echo ($competitionId === -1) ? 'selected' : ''; ?>>Practice Scores Only</option>
                                    <?php foreach ($competitions as $competition): ?>
                                        <option value="<?php echo $competition['CompetitionID']; ?>" <?php echo ($competitionId === $competition['CompetitionID']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($competition['CompetitionName']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="filter-row">
                            <div class="form-group">
                                <label for="start-date">Start Date:</label>
                                <input type="date" id="start-date" name="start_date" class="form-control" value="<?php echo $startDate; ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="end-date">End Date:</label>
                                <input type="date" id="end-date" name="end_date" class="form-control" value="<?php echo $endDate; ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="type">Score Type:</label>
                                <select id="type" name="is_practice" class="form-control">
                                    <option value="-1" <?php echo ($isPractice === -1) ? 'selected' : ''; ?>>All Types</option>
                                    <option value="1" <?php echo ($isPractice === 1) ? 'selected' : ''; ?>>Practice Only</option>
                                    <option value="0" <?php echo ($isPractice === 0) ? 'selected' : ''; ?>>Competition Only</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="filter-row">
                            <div class="form-group">
                                <label for="sort-by">Sort By:</label>
                                <select id="sort-by" name="sort_by" class="form-control">
                                    <option value="date" <?php echo ($sortBy === 'date') ? 'selected' : ''; ?>>Date</option>
                                    <option value="score" <?php echo ($sortBy === 'score') ? 'selected' : ''; ?>>Score</option>
                                    <option value="archer" <?php echo ($sortBy === 'archer') ? 'selected' : ''; ?>>Archer</option>
                                    <option value="round" <?php echo ($sortBy === 'round') ? 'selected' : ''; ?>>Round</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="sort-order">Sort Order:</label>
                                <select id="sort-order" name="sort_order" class="form-control">
                                    <option value="desc" <?php echo ($sortOrder === 'desc') ? 'selected' : ''; ?>>Highest/Latest First</option>
                                    <option value="asc" <?php echo ($sortOrder === 'asc') ? 'selected' : ''; ?>>Lowest/Oldest First</option>
                                </select>
                            </div>
                            
                            <div class="form-group filter-buttons">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-filter"></i> Apply Filters
                                </button>
                                <a href="view_scores.php" class="btn btn-secondary">
                                    <i class="fas fa-times"></i> Clear Filters
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
                
                <?php if (empty($scores)): ?>
                    <div class="no-scores">
                        <i class="fas fa-info-circle"></i>
                        <h3>No Scores Found</h3>
                        <p>No scores match the current filters.</p>
                        <?php if ($archerId || $roundId || $competitionId || $startDate || $endDate || $isPractice >= 0): ?>
                            <p>Try adjusting your filters or <a href="view_scores.php">view all scores</a>.</p>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="scores-table-container">
                        <table class="data-table scores-table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Archer</th>
                                    <th>Class/Division</th>
                                    <th>Round</th>
                                    <th>Equipment</th>
                                    <th>Score</th>
                                    <th>Type</th>
                                    <th>Records</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($scores as $score): ?>
                                    <tr>
                                        <td><?php echo formatDate($score['DateShot']); ?></td>
                                        <td><?php echo htmlspecialchars($score['FirstName'] . ' ' . $score['LastName']); ?></td>
                                        <td><?php echo htmlspecialchars($score['ClassName'] . ' ' . $score['DivisionName']); ?></td>
                                        <td><?php echo htmlspecialchars($score['RoundName']); ?></td>
                                        <td><?php echo htmlspecialchars($score['EquipmentName']); ?></td>
                                        <td class="score-value"><?php echo $score['TotalScore']; ?></td>
                                        <td>
                                            <?php if ($score['IsPractice']): ?>
                                                <span class="badge badge-practice">Practice</span>
                                            <?php else: ?>
                                                <span class="badge badge-competition"><?php echo htmlspecialchars($score['CompetitionName']); ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($score['IsPersonalBest']): ?>
                                                <span class="badge badge-pb" title="Personal Best">PB</span>
                                            <?php endif; ?>
                                            <?php if ($score['IsClubBest']): ?>
                                                <span class="badge badge-cb" title="Club Best">CB</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <a href="score_details.php?id=<?php echo $score['ScoreID']; ?>" class="btn btn-sm btn-secondary" title="View Details">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="edit_score.php?id=<?php echo $score['ScoreID']; ?>" class="btn btn-sm btn-primary" title="Edit Score">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="view_scores.php?action=delete&id=<?php echo $score['ScoreID']; ?>" class="btn btn-sm btn-danger" title="Delete Score" onclick="return confirm('Are you sure you want to delete this score? This action cannot be undone.');">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="export-buttons">
                        <button type="button" class="btn btn-secondary" onclick="exportTable('csv')">
                            <i class="fas fa-file-csv"></i> Export CSV
                        </button>
                        <button type="button" class="btn btn-secondary" onclick="exportTable('pdf')">
                            <i class="fas fa-file-pdf"></i> Export PDF
                        </button>
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
        function exportTable(format) {
            // This would connect to a server-side export script in a real implementation
            alert('Export to ' + format.toUpperCase() + ' would be implemented here.');
        }
    </script>
    
    <style>
        /* View Scores Page Specific Styles */
        .view-scores-container {
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
        }
        
        .filter-section h3 {
            color: var(--primary-color);
            margin-bottom: 1rem;
            font-size: 1.1rem;
        }
        
        .filter-row {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            margin-bottom: 1rem;
        }
        
        .filter-row:last-child {
            margin-bottom: 0;
        }
        
        .filter-row .form-group {
            flex: 1;
            min-width: 200px;
        }
        
        .filter-buttons {
            display: flex;
            gap: 0.5rem;
            align-items: flex-end;
        }
        
        .scores-table-container {
            background-color: white;
            border-radius: 8px;
            padding: 1.5rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            overflow-x: auto;
            margin-bottom: 1.5rem;
        }
        
        .scores-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .scores-table th, .scores-table td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid var(--gray-color);
        }
        
        .scores-table th {
            background-color: var(--light-color);
            font-weight: 600;
        }
        
        .scores-table tbody tr:hover {
            background-color: rgba(243, 244, 246, 0.5);
        }
        
        .score-value {
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
        
        .badge-practice {
            background-color: #e5e7eb;
            color: #4b5563;
        }
        
        .badge-competition {
            background-color: #dbeafe;
            color: #2563eb;
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
        
        .no-scores {
            background-color: white;
            border-radius: 8px;
            padding: 3rem 1.5rem;
            text-align: center;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        .no-scores i {
            font-size: 3rem;
            color: #d1d5db;
            margin-bottom: 1rem;
        }
        
        .no-scores h3 {
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }
        
        .no-scores p {
            margin-bottom: 0.5rem;
        }
        
        .no-scores a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
        }
        
        .no-scores a:hover {
            text-decoration: underline;
        }
        
        .export-buttons {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
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
            .filter-row {
                flex-direction: column;
                gap: 0.5rem;
            }
            
            .filter-buttons {
                flex-direction: column;
                align-items: stretch;
            }
            
            .page-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }
            
            .export-buttons {
                flex-direction: column;
                align-items: stretch;
            }
        }
    </style>
</body>
</html>