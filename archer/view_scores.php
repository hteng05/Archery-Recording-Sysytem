<?php
require_once '../includes/settings.php';
require_once '../includes/db_functions.php';

// Check if archer ID is in session
if (!isset($_SESSION['current_archer_id'])) {
    header('Location: dashboard.php');
    exit;
}

$archerId = $_SESSION['current_archer_id'];
$archer = getArcherById($archerId);

// If archer not found, redirect to selection page
if (!$archer) {
    header('Location: dashboard.php');
    exit;
}

// Get filter parameters
$startDate = isset($_GET['start_date']) ? sanitizeInput($_GET['start_date']) : '';
$endDate = isset($_GET['end_date']) ? sanitizeInput($_GET['end_date']) : '';
$roundId = isset($_GET['round_id']) ? intval($_GET['round_id']) : 0;
$sortBy = isset($_GET['sort_by']) ? sanitizeInput($_GET['sort_by']) : 'date';
$sortOrder = isset($_GET['sort_order']) ? sanitizeInput($_GET['sort_order']) : 'desc';

// Get all rounds for filter
$rounds = getAllRounds();

// Get scores with filters
$scores = getArcherScores($archerId, $startDate, $endDate, $roundId);

// Sort scores based on user selection
if ($scores && count($scores) > 0) {
    if ($sortBy === 'date') {
        usort($scores, function($a, $b) use ($sortOrder) {
            return $sortOrder === 'asc' 
                ? strtotime($a['DateShot']) - strtotime($b['DateShot'])
                : strtotime($b['DateShot']) - strtotime($a['DateShot']);
        });
    } elseif ($sortBy === 'score') {
        usort($scores, function($a, $b) use ($sortOrder) {
            return $sortOrder === 'asc' 
                ? $a['TotalScore'] - $b['TotalScore']
                : $b['TotalScore'] - $a['TotalScore'];
        });
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Scores - Archery Score Recording System</title>
    <link rel="stylesheet" href="../css/main.css">
    <link rel="stylesheet" href="../css/navbar_archer.css">
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

        <div class="archer-navbar">
            <div class="navbar-left">
                <a href="archer_home.php?archer_id=<?php echo $archerId; ?>">Home</a>
                <a href="view_scores.php" class="active">My Scores</a>
                <a href="enter_score.php">Enter Score</a>
                <a href="rounds_info.php">Rounds Info</a>
                <a href="competitions.php">Competitions</a>
                <a href="club_records.php">Club Records</a>
            </div>
            <div class="navbar-right">
                <div class="archer-info">
                    <span class="archer-name">
                        <i class="fas fa-user"></i> <?php echo htmlspecialchars($archer['FirstName'] . ' ' . $archer['LastName']); ?>
                    </span>
                    <a href="dashboard.php" class="switch-archer">
                        <i class="fas fa-exchange-alt"></i> Switch Archer
                    </a>
                </div>
            </div>
        </div>

        <main>
            <div class="scores-container">
                <h2>My Scores</h2>
                
                <div class="filter-section">
                    <h3>Filter Scores</h3>
                    <form action="view_scores.php" method="get" class="filter-form">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="start-date">Start Date:</label>
                                <input type="date" id="start-date" name="start_date" class="form-control" value="<?php echo $startDate; ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="end-date">End Date:</label>
                                <input type="date" id="end-date" name="end_date" class="form-control" value="<?php echo $endDate; ?>">
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
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="sort-by">Sort By:</label>
                                <select id="sort-by" name="sort_by" class="form-control">
                                    <option value="date" <?php echo ($sortBy === 'date') ? 'selected' : ''; ?>>Date</option>
                                    <option value="score" <?php echo ($sortBy === 'score') ? 'selected' : ''; ?>>Score</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="sort-order">Order:</label>
                                <select id="sort-order" name="sort_order" class="form-control">
                                    <option value="desc" <?php echo ($sortOrder === 'desc') ? 'selected' : ''; ?>>Highest First</option>
                                    <option value="asc" <?php echo ($sortOrder === 'asc') ? 'selected' : ''; ?>>Lowest First</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <button type="submit" class="btn btn-primary filter-btn">
                                    <i class="fas fa-filter"></i> Apply Filters
                                </button>
                                <a href="view_scores.php" class="btn btn-secondary clear-filter-btn">
                                    <i class="fas fa-times"></i> Clear Filters
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
                
                <?php if (!empty($scores)): ?>
                    <div class="scores-table-container">
                        <table class="data-table scores-table">
                            <thead>
                                <tr>
                                    <th data-sort="date">Date</th>
                                    <th data-sort="round">Round</th>
                                    <th data-sort="equipment">Equipment</th>
                                    <th data-sort="score">Score</th>
                                    <th data-sort="type">Type</th>
                                    <th data-sort="pb">PB/CB</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($scores as $score): ?>
                                    <tr>
                                        <td data-date="<?php echo $score['DateShot']; ?>"><?php echo formatDate($score['DateShot']); ?></td>
                                        <td><?php echo htmlspecialchars($score['RoundName']); ?></td>
                                        <td><?php echo htmlspecialchars($score['EquipmentName']); ?></td>
                                        <td data-score="<?php echo $score['TotalScore']; ?>"><?php echo $score['TotalScore']; ?></td>
                                        <td>
                                            <?php if ($score['CompetitionID']): ?>
                                                <span class="badge badge-competition"><?php echo htmlspecialchars($score['CompetitionName']); ?></span>
                                            <?php else: ?>
                                                <span class="badge badge-practice">Practice</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($score['IsPersonalBest']): ?>
                                                <span class="badge badge-pb">PB</span>
                                            <?php endif; ?>
                                            <?php if ($score['IsClubBest']): ?>
                                                <span class="badge badge-cb">CB</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a href="score_details.php?score_id=<?php echo $score['ScoreID']; ?>" class="btn btn-sm btn-secondary">
                                                Details
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="no-scores">
                        <i class="fas fa-info-circle"></i>
                        <p>No scores found with the current filters.</p>
                        <?php if ($startDate || $endDate || $roundId): ?>
                            <p>Try adjusting your filters or <a href="view_scores.php">view all scores</a>.</p>
                        <?php else: ?>
                            <p>Start recording your scores by <a href="enter_score.php">entering a new score</a>.</p>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </main>

        <footer>
            <p>&copy; 2025 Archery Club Database System. All rights reserved.</p>
        </footer>
    </div>

    <script src="../js/main.js"></script>
    
    <style>
        /* Scores Page Specific Styles */
        .scores-container {
            padding: 1rem;
        }
        
        .scores-container h2 {
            color: var(--primary-color);
            margin-bottom: 1rem;
        }
        
        .filter-section {
            background-color: white;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        .filter-section h3 {
            color: var(--primary-color);
            margin-bottom: 1rem;
            font-size: 1.1rem;
        }
        
        .filter-form {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        
        .form-row {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
        }
        
        .form-row .form-group {
            flex: 1;
            min-width: 200px;
        }
        
        .filter-btn {
            margin-top: 1.5rem;
        }
        
        .clear-filter-btn {
            margin-top: 1.5rem;
            margin-left: 0.5rem;
        }
        
        .scores-table-container {
            background-color: white;
            border-radius: 8px;
            padding: 1.5rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            overflow-x: auto;
        }
        
        .scores-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .scores-table th, .scores-table td {
            padding: 0.75rem 1rem;
            text-align: left;
        }
        
        .scores-table th {
            background-color: var(--light-color);
            font-weight: 600;
            cursor: pointer;
        }
        
        .scores-table th:hover {
            background-color: #e2f1e8;
        }
        
        .scores-table th.sort-asc::after {
            content: " ↑";
        }
        
        .scores-table th.sort-desc::after {
            content: " ↓";
        }
        
        .scores-table tbody tr:nth-child(even) {
            background-color: rgba(243, 244, 246, 0.5);
        }
        
        .scores-table tbody tr:hover {
            background-color: rgba(243, 244, 246, 0.8);
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
        
        .btn-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
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
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .form-row {
                flex-direction: column;
                gap: 0.5rem;
            }
            
            .scores-table th, .scores-table td {
                padding: 0.5rem;
            }
        }
    </style>
</body>
</html>