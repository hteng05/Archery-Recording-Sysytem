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

// Get archer ID from URL
$archerId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($archerId === 0) {
    header('Location: manage_archers.php');
    exit;
}

// Get archer data
$archer = getArcherById($archerId);

if (!$archer) {
    header('Location: manage_archers.php');
    exit;
}

// Get filter parameters
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : '';
$roundId = isset($_GET['round_id']) ? intval($_GET['round_id']) : null;
$sortBy = isset($_GET['sort']) ? $_GET['sort'] : 'date_desc';

// Validate dates
if ($startDate && !DateTime::createFromFormat('Y-m-d', $startDate)) {
    $startDate = '';
}
if ($endDate && !DateTime::createFromFormat('Y-m-d', $endDate)) {
    $endDate = '';
}

// Get scores
$scores = getArcherScores($archerId, $startDate ?: null, $endDate ?: null, $roundId ?: null);

// Sort scores based on selected option
switch ($sortBy) {
    case 'date_asc':
        usort($scores, function($a, $b) {
            return strcmp($a['DateShot'], $b['DateShot']);
        });
        break;
    case 'score_asc':
        usort($scores, function($a, $b) {
            return $a['TotalScore'] - $b['TotalScore'];
        });
        break;
    case 'score_desc':
        usort($scores, function($a, $b) {
            return $b['TotalScore'] - $a['TotalScore'];
        });
        break;
    case 'round':
        usort($scores, function($a, $b) {
            return strcmp($a['RoundName'], $b['RoundName']);
        });
        break;
    default: // date_desc
        usort($scores, function($a, $b) {
            return strcmp($b['DateShot'], $a['DateShot']);
        });
        break;
}

// Get all rounds for filter dropdown
$allRounds = getAllRounds();

// Get personal bests for this archer
$personalBests = getPersonalBests($archerId);

// Get archer's championship summary for current year
$championshipSummary = getArcherChampionshipSummary($archerId);

// Calculate statistics
$totalScores = count($scores);
$practiceScores = count(array_filter($scores, function($score) { return $score['IsPractice']; }));
$competitionScores = $totalScores - $practiceScores;
$personalBestCount = count($personalBests);

// Calculate average score by round
$roundAverages = [];
foreach ($scores as $score) {
    $round = $score['RoundName'];
    if (!isset($roundAverages[$round])) {
        $roundAverages[$round] = ['total' => 0, 'count' => 0];
    }
    $roundAverages[$round]['total'] += $score['TotalScore'];
    $roundAverages[$round]['count']++;
}

foreach ($roundAverages as $round => &$avg) {
    $avg['average'] = round($avg['total'] / $avg['count'], 1);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Archer Scores - Archery Score Recording System</title>
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
            <div class="archer-scores-container">
                <div class="page-header">
                    <div>
                        <h2><i class="fas fa-chart-line"></i> Archer Scores</h2>
                        <p class="page-subtitle">
                            <?php echo htmlspecialchars($archer['FirstName'] . ' ' . $archer['LastName']); ?> - 
                            <?php echo htmlspecialchars($archer['ClassName'] . ' ' . $archer['DivisionName']); ?>
                        </p>
                    </div>
                    <div class="action-buttons">
                        <a href="manage_archers.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back to Archers
                        </a>
                        <a href="edit_archer.php?id=<?php echo $archerId; ?>" class="btn btn-primary">
                            <i class="fas fa-user-edit"></i> Edit Archer
                        </a>
                    </div>
                </div>

                <!-- Statistics Dashboard -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-bullseye"></i>
                        </div>
                        <div class="stat-content">
                            <h3><?php echo $totalScores; ?></h3>
                            <p>Total Scores</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-trophy"></i>
                        </div>
                        <div class="stat-content">
                            <h3><?php echo $competitionScores; ?></h3>
                            <p>Competition Scores</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-medal"></i>
                        </div>
                        <div class="stat-content">
                            <h3><?php echo $personalBestCount; ?></h3>
                            <p>Personal Bests</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-star"></i>
                        </div>
                        <div class="stat-content">
                            <h3><?php echo $championshipSummary['rank'] ? '#' . $championshipSummary['rank'] : 'N/A'; ?></h3>
                            <p>Championship Rank</p>
                        </div>
                    </div>
                </div>

                <!-- Championship Summary -->
                <?php if ($championshipSummary['total_points'] > 0): ?>
                <div class="championship-summary">
                    <h3><i class="fas fa-crown"></i> <?php echo $championshipSummary['year']; ?> Championship Summary</h3>
                    <div class="championship-details">
                        <div class="championship-item">
                            <strong>Category:</strong> <?php echo htmlspecialchars($championshipSummary['category']); ?>
                        </div>
                        <div class="championship-item">
                            <strong>Total Points:</strong> <?php echo $championshipSummary['total_points']; ?>
                        </div>
                        <div class="championship-item">
                            <strong>Participation:</strong> <?php echo $championshipSummary['competitions_participated']; ?>/<?php echo $championshipSummary['total_competitions']; ?> competitions (<?php echo $championshipSummary['participation_percentage']; ?>%)
                        </div>
                        <?php if ($championshipSummary['best_score']): ?>
                        <div class="championship-item">
                            <strong>Best Championship Score:</strong> <?php echo $championshipSummary['best_score']; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Personal Bests -->
                <?php if (!empty($personalBests)): ?>
                <div class="personal-bests-section">
                    <h3><i class="fas fa-medal"></i> Personal Bests</h3>
                    <div class="personal-bests-grid">
                        <?php foreach ($personalBests as $pb): ?>
                        <div class="pb-card">
                            <div class="pb-round"><?php echo htmlspecialchars($pb['RoundName']); ?></div>
                            <div class="pb-score"><?php echo $pb['TotalScore']; ?></div>
                            <div class="pb-equipment"><?php echo htmlspecialchars($pb['EquipmentName']); ?></div>
                            <div class="pb-date"><?php echo formatDate($pb['DateAchieved']); ?></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Filters -->
                <div class="filter-section">
                    <form method="GET" class="filter-form">
                        <input type="hidden" name="id" value="<?php echo $archerId; ?>">
                        
                        <div class="filter-row">
                            <div class="filter-group">
                                <label for="start_date">Start Date</label>
                                <input type="date" id="start_date" name="start_date" class="form-control" 
                                       value="<?php echo htmlspecialchars($startDate); ?>">
                            </div>
                            <div class="filter-group">
                                <label for="end_date">End Date</label>
                                <input type="date" id="end_date" name="end_date" class="form-control" 
                                       value="<?php echo htmlspecialchars($endDate); ?>">
                            </div>
                            <div class="filter-group">
                                <label for="round_id">Round</label>
                                <select id="round_id" name="round_id" class="form-control">
                                    <option value="">All Rounds</option>
                                    <?php foreach ($allRounds as $round): ?>
                                        <option value="<?php echo $round['RoundID']; ?>" 
                                                <?php echo $round['RoundID'] == $roundId ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($round['RoundName']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="filter-group">
                                <label for="sort">Sort By</label>
                                <select id="sort" name="sort" class="form-control">
                                    <option value="date_desc" <?php echo $sortBy === 'date_desc' ? 'selected' : ''; ?>>Date (Newest First)</option>
                                    <option value="date_asc" <?php echo $sortBy === 'date_asc' ? 'selected' : ''; ?>>Date (Oldest First)</option>
                                    <option value="score_desc" <?php echo $sortBy === 'score_desc' ? 'selected' : ''; ?>>Score (Highest First)</option>
                                    <option value="score_asc" <?php echo $sortBy === 'score_asc' ? 'selected' : ''; ?>>Score (Lowest First)</option>
                                    <option value="round" <?php echo $sortBy === 'round' ? 'selected' : ''; ?>>Round Name</option>
                                </select>
                            </div>
                            <div class="filter-actions">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-filter"></i> Filter
                                </button>
                                <a href="view_archer_scores.php?id=<?php echo $archerId; ?>" class="btn btn-secondary">
                                    <i class="fas fa-times"></i> Clear
                                </a>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Round Averages -->
                <?php if (!empty($roundAverages)): ?>
                <div class="round-averages-section">
                    <h3><i class="fas fa-chart-bar"></i> Round Averages</h3>
                    <div class="averages-grid">
                        <?php foreach ($roundAverages as $roundName => $avg): ?>
                        <div class="average-card">
                            <div class="average-round"><?php echo htmlspecialchars($roundName); ?></div>
                            <div class="average-score"><?php echo $avg['average']; ?></div>
                            <div class="average-count"><?php echo $avg['count']; ?> score<?php echo $avg['count'] != 1 ? 's' : ''; ?></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Scores Table -->
                <div class="scores-section">
                    <div class="section-header">
                        <h3><i class="fas fa-list"></i> Score History</h3>
                        <div class="score-count">
                            Showing <?php echo count($scores); ?> score<?php echo count($scores) != 1 ? 's' : ''; ?>
                        </div>
                    </div>
                    
                    <?php if (empty($scores)): ?>
                        <div class="no-scores">
                            <i class="fas fa-info-circle"></i>
                            <p>No scores found for the selected criteria.</p>
                        </div>
                    <?php else: ?>
                        <div class="scores-table-container">
                            <table class="data-table scores-table">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Round</th>
                                        <th>Equipment</th>
                                        <th>Score</th>
                                        <th>Type</th>
                                        <th>Competition</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($scores as $score): ?>
                                        <tr class="<?php echo $score['IsPersonalBest'] ? 'personal-best-row' : ''; ?> <?php echo $score['IsClubBest'] ? 'club-best-row' : ''; ?>">
                                            <td><?php echo formatDate($score['DateShot']); ?></td>
                                            <td><?php echo htmlspecialchars($score['RoundName']); ?></td>
                                            <td><?php echo htmlspecialchars($score['EquipmentName']); ?></td>
                                            <td class="score-value">
                                                <strong><?php echo $score['TotalScore']; ?></strong>
                                                <?php if ($score['IsPersonalBest']): ?>
                                                    <span class="badge badge-pb" title="Personal Best">PB</span>
                                                <?php endif; ?>
                                                <?php if ($score['IsClubBest']): ?>
                                                    <span class="badge badge-cb" title="Club Best">CB</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($score['IsPractice']): ?>
                                                    <span class="type-badge type-practice">Practice</span>
                                                <?php else: ?>
                                                    <span class="type-badge type-competition">Competition</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php echo $score['CompetitionName'] ? htmlspecialchars($score['CompetitionName']) : '-'; ?>
                                            </td>
                                            <td>
                                                <?php if ($score['IsApproved']): ?>
                                                    <span class="status-badge status-approved">Approved</span>
                                                <?php else: ?>
                                                    <span class="status-badge status-pending">Pending</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="action-buttons">
                                                    <a href="score_details.php?id=<?php echo $score['ScoreID']; ?>" class="btn btn-sm btn-primary" title="View Details">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <?php if (!$score['IsApproved']): ?>
                                                    <a href="approve_score.php?id=<?php echo $score['ScoreID']; ?>" class="btn btn-sm btn-success" title="Approve">
                                                        <i class="fas fa-check"></i>
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
            </div>
        </main>

        <footer>
            <p>&copy; 2025 Archery Club Database System. All rights reserved.</p>
        </footer>
    </div>

    <script src="../js/main.js"></script>
    
    <style>
        /* Archer Scores Page Specific Styles */
        .archer-scores-container {
            padding: 1rem;
            max-width: 1400px;
            margin: 0 auto;
        }
        
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1.5rem;
        }
        
        .page-header h2 {
            color: var(--primary-color);
            margin: 0;
        }
        
        .page-header h2 i {
            margin-right: 0.5rem;
        }
        
        .page-subtitle {
            color: #6b7280;
            margin: 0.25rem 0 0 0;
            font-size: 0.9rem;
        }
        
        /* Statistics Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .stat-card {
            background-color: white;
            border-radius: 8px;
            padding: 1.5rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .stat-icon {
            background-color: var(--primary-color);
            color: white;
            width: 3rem;
            height: 3rem;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
        }
        
        .stat-content h3 {
            margin: 0;
            font-size: 2rem;
            color: var(--primary-color);
        }
        
        .stat-content p {
            margin: 0;
            color: #6b7280;
            font-size: 0.9rem;
        }
        
        /* Championship Summary */
        .championship-summary {
            background-color: white;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            border-left: 4px solid #f59e0b;
        }
        
        .championship-summary h3 {
            color: var(--primary-color);
            margin: 0 0 1rem 0;
        }
        
        .championship-summary h3 i {
            margin-right: 0.5rem;
            color: #f59e0b;
        }
        
        .championship-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
        }
        
        .championship-item {
            font-size: 0.9rem;
        }
        
        /* Personal Bests */
        .personal-bests-section {
            background-color: white;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        .personal-bests-section h3 {
            color: var(--primary-color);
            margin: 0 0 1rem 0;
        }
        
        .personal-bests-section h3 i {
            margin-right: 0.5rem;
            color: #f59e0b;
        }
        
        .personal-bests-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 1rem;
        }
        
        .pb-card {
            background-color: var(--light-color);
            border-radius: 6px;
            padding: 1rem;
            text-align: center;
            border: 2px solid #f59e0b;
        }
        
        .pb-round {
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }
        
        .pb-score {
            font-size: 1.5rem;
            font-weight: bold;
            color: #f59e0b;
            margin-bottom: 0.25rem;
        }
        
        .pb-equipment {
            font-size: 0.8rem;
            color: #6b7280;
            margin-bottom: 0.25rem;
        }
        
        .pb-date {
            font-size: 0.8rem;
            color: #6b7280;
        }
        
        /* Filters */
        .filter-section {
            background-color: white;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        .filter-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)) auto;
            gap: 1rem;
            align-items: end;
        }
        
        .filter-group {
            display: flex;
            flex-direction: column;
        }
        
        .filter-group label {
            font-weight: 600;
            margin-bottom: 0.25rem;
            color: #374151;
        }
        
        .filter-actions {
            display: flex;
            gap: 0.5rem;
        }
        
        /* Round Averages */
        .round-averages-section {
            background-color: white;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        .round-averages-section h3 {
            color: var(--primary-color);
            margin: 0 0 1rem 0;
        }
        
        .round-averages-section h3 i {
            margin-right: 0.5rem;
        }
        
        .averages-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 1rem;
        }
        
        .average-card {
            background-color: var(--light-color);
            border-radius: 6px;
            padding: 1rem;
            text-align: center;
        }
        
        .average-round {
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
        }
        
        .average-score {
            font-size: 1.25rem;
            font-weight: bold;
            color: var(--primary-color);
            margin-bottom: 0.25rem;
        }
        
        .average-count {
            font-size: 0.8rem;
            color: #6b7280;
        }
        
        /* Scores Section */
        .scores-section {
            background-color: white;
            border-radius: 8px;
            padding: 1.5rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }
        
        .section-header h3 {
            color: var(--primary-color);
            margin: 0;
        }
        
        .section-header h3 i {
            margin-right: 0.5rem;
        }
        
        .score-count {
            color: #6b7280;
            font-size: 0.9rem;
        }
        
        .no-scores {
            text-align: center;
            padding: 2rem;
            color: #6b7280;
        }
        
        .no-scores i {
            font-size: 2rem;
            margin-bottom: 1rem;
            display: block;
        }
        
        .scores-table-container {
            overflow-x: auto;
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
        
        .score-value {
            font-weight: 600;
        }
        
        .badge {
            display: inline-block;
            padding: 0.2rem 0.4rem;
            border-radius: 12px;
            font-size: 0.65rem;
            font-weight: 600;
            margin-left: 0.25rem;
        }
        
        .badge-pb {
            background-color: #f59e0b;
            color: white;
        }
        
        .badge-cb {
            background-color: #10b981;
            color: white;
        }
        
        .type-badge {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 500;
        }
        
        .type-practice {
            background-color: #e5e7eb;
            color: #374151;
        }
        
        .type-competition {
            background-color: #dbeafe;
            color: #1e40af;
        }
        
        .status-badge {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 500;
        }
        
        .status-approved {
            background-color: #dcfce7;
            color: #16a34a;
        }
        
        .status-pending {
            background-color: #fef3c7;
            color: #d97706;
        }
        
        .personal-best-row {
            background-color: rgba(245, 158, 11, 0.1);
        }
        
        .club-best-row {
            background-color: rgba(16, 185, 129, 0.1);
        }
        
        .action-buttons {
            display: flex;
            gap: 0.5rem;
        }
        
        .btn-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
        }
        
        .form-control {
            padding: 0.5rem;
            border: 1px solid #d1d5db;
            border-radius: 4px;
            font-size: 1rem;
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        
        /* Responsive adjustments */
        @media (max-width: 992px) {
            .page-header {
                flex-direction: column;
                gap: 1rem;
            }
            
            .action-buttons {
                display: flex;
                gap: 0.5rem;
            }
            
            .filter-row {
                grid-template-columns: 1fr;
            }
            
            .filter-actions {
                justify-content: center;
            }
            
            .championship-details {
                grid-template-columns: 1fr;
            }
        }
        
        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .personal-bests-grid {
                grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            }
            
            .averages-grid {
                grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            }
        }
    </style>
</body>
</html>