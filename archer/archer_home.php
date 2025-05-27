<?php
require_once '../includes/settings.php';
require_once '../includes/db_functions.php';

// Get archer ID from URL
$archerId = isset($_GET['archer_id']) ? intval($_GET['archer_id']) : 0;

// If no archer ID provided, redirect to selection page
if ($archerId <= 0) {
    header('Location: dashboard.php');
    exit;
}

// Get archer details
$archer = getArcherById($archerId);

// If archer not found, redirect to selection page
if (!$archer) {
    header('Location: dashboard.php');
    exit;
}

// Store archer ID in session for other pages
$_SESSION['current_archer_id'] = $archerId;

// Get recent scores for this archer
$recentScores = getArcherScores($archerId, null, null, null, 5);

// Get personal bests
$personalBests = getPersonalBests($archerId);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Archer Home - Archery Score Recording System</title>
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
                <a href="archer_home.php?archer_id=<?php echo $archerId; ?>" class="active">Home</a>
                <a href="view_scores.php">My Scores</a>
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
            <div class="archer-welcome">
                <h2>Welcome, <?php echo htmlspecialchars($archer['FirstName']); ?>!</h2>
                <div class="archer-details">
                    <div class="detail-item">
                        <span class="detail-label">Class:</span>
                        <span class="detail-value"><?php echo htmlspecialchars($archer['ClassName']); ?></span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Division:</span>
                        <span class="detail-value"><?php echo htmlspecialchars($archer['DivisionName']); ?></span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Default Equipment:</span>
                        <span class="detail-value"><?php echo htmlspecialchars($archer['EquipmentName']); ?></span>
                    </div>
                </div>
            </div>
            
            <div class="dashboard-grid">
                <div class="dashboard-card">
                    <i class="fas fa-edit"></i>
                    <h3>Enter New Score</h3>
                    <p>Record your latest practice or competition score</p>
                    <a href="enter_score.php" class="card-link">Enter Score</a>
                </div>
                
                <div class="dashboard-card">
                    <i class="fas fa-history"></i>
                    <h3>View My Scores</h3>
                    <p>See all your historical scores and track your progress</p>
                    <a href="view_scores.php" class="card-link">View Scores</a>
                </div>
                
                <div class="dashboard-card">
                    <i class="fas fa-bullseye"></i>
                    <h3>Round Information</h3>
                    <p>Learn about different rounds and their requirements</p>
                    <a href="rounds_info.php" class="card-link">View Rounds</a>
                </div>
                
                <div class="dashboard-card">
                    <i class="fas fa-trophy"></i>
                    <h3>Competition Results</h3>
                    <p>View results from club competitions</p>
                    <a href="competitions.php" class="card-link">View Competitions</a>
                </div>
                
                <div class="dashboard-card">
                    <i class="fas fa-medal"></i>
                    <h3>Personal Bests</h3>
                    <p>Check your personal best scores for each round</p>
                    <a href="personal_bests.php" class="card-link">View PBs</a>
                </div>
                
                <div class="dashboard-card">
                    <i class="fas fa-chart-line"></i>
                    <h3>Club Records</h3>
                    <p>See the best scores achieved at the club</p>
                    <a href="club_records.php" class="card-link">View Records</a>
                </div>
            </div>
            
            <?php if (!empty($recentScores)): ?>
            <div class="recent-scores">
                <h3>Recent Scores</h3>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Round</th>
                                <th>Equipment</th>
                                <th>Score</th>
                                <th>Type</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentScores as $score): ?>
                            <tr>
                                <td><?php echo formatDate($score['DateShot']); ?></td>
                                <td><?php echo htmlspecialchars($score['RoundName']); ?></td>
                                <td><?php echo htmlspecialchars($score['EquipmentName']); ?></td>
                                <td><?php echo $score['TotalScore']; ?></td>
                                <td>
                                    <?php if ($score['CompetitionID']): ?>
                                        <span class="badge badge-competition">Competition</span>
                                    <?php else: ?>
                                        <span class="badge badge-practice">Practice</span>
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
                <div class="view-all-link">
                    <a href="view_scores.php">View All Scores <i class="fas fa-arrow-right"></i></a>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($personalBests)): ?>
            <div class="personal-bests">
                <h3>Personal Bests</h3>
                <div class="pb-cards">
                    <?php foreach (array_slice($personalBests, 0, 3) as $pb): ?>
                    <div class="pb-card">
                        <div class="pb-round"><?php echo htmlspecialchars($pb['RoundName']); ?></div>
                        <div class="pb-score"><?php echo $pb['TotalScore']; ?></div>
                        <div class="pb-equipment"><?php echo htmlspecialchars($pb['EquipmentName']); ?></div>
                        <div class="pb-date"><?php echo formatDate($pb['DateAchieved']); ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php if (count($personalBests) > 3): ?>
                <div class="view-all-link">
                    <a href="personal_bests.php">View All Personal Bests <i class="fas fa-arrow-right"></i></a>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </main>

        <footer>
            <p>&copy; 2025 Archery Club Database System. All rights reserved.</p>
        </footer>
    </div>

    <script src="../js/main.js"></script>
    
    <style>
        
        /* Archer Welcome */
        .archer-welcome {
            background-color: white;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        .archer-welcome h2 {
            color: var(--primary-color);
            margin-bottom: 1rem;
        }
        
        .archer-details {
            display: flex;
            flex-wrap: wrap;
            gap: 2rem;
        }
        
        .detail-item {
            display: flex;
            flex-direction: column;
        }
        
        .detail-label {
            font-size: 0.9rem;
            color: #6b7280;
        }
        
        .detail-value {
            font-size: 1.1rem;
            font-weight: 500;
        }
        
        /* Recent Scores */
        .recent-scores, .personal-bests {
            background-color: white;
            border-radius: 8px;
            padding: 1.5rem;
            margin-top: 2rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        .recent-scores h3, .personal-bests h3 {
            color: var(--primary-color);
            margin-bottom: 1rem;
            border-bottom: 1px solid var(--gray-color);
            padding-bottom: 0.5rem;
        }
        
        .table-responsive {
            overflow-x: auto;
        }
        
        .data-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .data-table th, .data-table td {
            padding: 0.75rem 1rem;
            text-align: left;
        }
        
        .data-table th {
            background-color: var(--light-color);
            font-weight: 600;
        }
        
        .data-table tbody tr:nth-child(even) {
            background-color: rgba(243, 244, 246, 0.5);
        }
        
        .data-table tbody tr:hover {
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
        
        .btn-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
        }
        
        .view-all-link {
            text-align: right;
            margin-top: 1rem;
        }
        
        .view-all-link a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
        }
        
        .view-all-link a:hover {
            text-decoration: underline;
        }
        
        /* Personal Bests */
        .pb-cards {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 1rem;
        }
        
        .pb-card {
            background-color: var(--light-color);
            border-radius: 8px;
            padding: 1rem;
            text-align: center;
        }
        
        .pb-round {
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        
        .pb-score {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }
        
        .pb-equipment {
            font-size: 0.9rem;
            color: #4b5563;
            margin-bottom: 0.25rem;
        }
        
        .pb-date {
            font-size: 0.8rem;
            color: #6b7280;
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .archer-navbar {
                flex-direction: column;
            }
            
            .navbar-left, .navbar-right {
                width: 100%;
                flex-wrap: wrap;
            }
            
            .archer-info {
                flex-direction: column;
                width: 100%;
                padding: 0.5rem 0;
            }
            
            .switch-archer {
                margin: 0.5rem 0;
            }
            
            .archer-details {
                flex-direction: column;
                gap: 1rem;
            }
            
            .pb-cards {
                grid-template-columns: 1fr;
            }
        }
    </style>
</body>
</html>