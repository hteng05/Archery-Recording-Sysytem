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

// Get competition ID from URL
$competitionId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($competitionId <= 0) {
    header('Location: competitions.php');
    exit;
}

// Get competition details
$competitions = getAllCompetitions(true);
$competition = null;
foreach ($competitions as $comp) {
    if ($comp['CompetitionID'] == $competitionId) {
        $competition = $comp;
        break;
    }
}

if (!$competition) {
    header('Location: competitions.php');
    exit;
}

// Get competition results
$results = getCompetitionResults($competitionId);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($competition['CompetitionName']); ?> Results - Archery Score Recording System</title>
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
                <a href="view_scores.php">My Scores</a>
                <a href="enter_score.php">Enter Score</a>
                <a href="rounds_info.php">Rounds Info</a>
                <a href="competitions.php" class="active">Competitions</a>
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
            <div class="results-container">
                <div class="results-header">
                    <div class="header-content">
                        <div class="back-link">
                            <a href="competitions.php"><i class="fas fa-arrow-left"></i> Back to Competitions</a>
                        </div>
                        
                        <h2><i class="fas fa-trophy"></i> <?php echo htmlspecialchars($competition['CompetitionName']); ?></h2>
                        
                        <div class="competition-info">
                            <div class="info-item">
                                <i class="fas fa-calendar"></i>
                                <span>
                                    <?php echo formatDate($competition['StartDate']); ?>
                                    <?php if ($competition['StartDate'] !== $competition['EndDate']): ?>
                                        - <?php echo formatDate($competition['EndDate']); ?>
                                    <?php endif; ?>
                                </span>
                            </div>
                            
                            <div class="info-item">
                                <i class="fas fa-map-marker-alt"></i>
                                <span><?php echo htmlspecialchars($competition['Location']); ?></span>
                            </div>
                            
                            <div class="info-badges">
                                <?php if ($competition['IsChampionship']): ?>
                                    <span class="badge badge-championship">Championship</span>
                                <?php endif; ?>
                                <?php if ($competition['IsOfficial']): ?>
                                    <span class="badge badge-official">Official</span>
                                <?php endif; ?>
                                <?php if ($competition['ContributesToChampionship']): ?>
                                    <span class="badge badge-contrib">Championship Points</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="results-content">
                    <?php if (empty($results)): ?>
                        <div class="no-results">
                            <i class="fas fa-trophy"></i>
                            <h3>No Results Available</h3>
                            <p>Results for this competition have not been published yet or no scores have been submitted.</p>
                        </div>
                    <?php else: ?>
                        <div class="results-summary">
                            <div class="summary-stats">
                                <div class="stat-item">
                                    <span class="stat-number"><?php echo count($results); ?></span>
                                    <span class="stat-label">Categories</span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-number">
                                        <?php 
                                        $totalArchers = 0;
                                        foreach ($results as $category => $archers) {
                                            $totalArchers += count($archers);
                                        }
                                        echo $totalArchers;
                                        ?>
                                    </span>
                                    <span class="stat-label">Participants</span>
                                </div>
                            </div>
                        </div>

                        <div class="category-tabs">
                            <div class="tabs-header">
                                <?php $firstCategory = true; ?>
                                <?php foreach ($results as $category => $archers): ?>
                                    <button class="tab-button <?php echo $firstCategory ? 'active' : ''; ?>" 
                                            onclick="showCategory('<?php echo htmlspecialchars($category); ?>')"
                                            data-category="<?php echo htmlspecialchars($category); ?>">
                                        <?php echo htmlspecialchars($category); ?>
                                        <span class="archer-count">(<?php echo count($archers); ?>)</span>
                                    </button>
                                    <?php $firstCategory = false; ?>
                                <?php endforeach; ?>
                            </div>

                            <div class="tabs-content">
                                <?php $firstCategory = true; ?>
                                <?php foreach ($results as $category => $archers): ?>
                                    <div class="tab-content <?php echo $firstCategory ? 'active' : ''; ?>" 
                                         id="category-<?php echo htmlspecialchars($category); ?>">
                                        
                                        <div class="category-header">
                                            <h3><?php echo htmlspecialchars($category); ?></h3>
                                            <p><?php echo count($archers); ?> participants</p>
                                        </div>

                                        <div class="results-table-wrapper">
                                            <table class="results-table">
                                                <thead>
                                                    <tr>
                                                        <th class="rank-col">Rank</th>
                                                        <th class="archer-col">Archer</th>
                                                        <th class="round-col">Round</th>
                                                        <th class="equipment-col">Equipment</th>
                                                        <th class="score-col">Score</th>
                                                        <th class="date-col">Date</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php $rank = 1; ?>
                                                    <?php foreach ($archers as $archer): ?>
                                                        <tr class="<?php echo ($archer['ArcherID'] == $archerId) ? 'current-archer' : ''; ?>">
                                                            <td class="rank-col">
                                                                <?php if ($rank <= 3): ?>
                                                                    <span class="medal medal-<?php echo $rank; ?>">
                                                                        <?php if ($rank == 1): ?>
                                                                            <i class="fas fa-trophy"></i>
                                                                        <?php elseif ($rank == 2): ?>
                                                                            <i class="fas fa-medal"></i>
                                                                        <?php else: ?>
                                                                            <i class="fas fa-award"></i>
                                                                        <?php endif; ?>
                                                                        <?php echo $rank; ?>
                                                                    </span>
                                                                <?php else: ?>
                                                                    <span class="rank-number"><?php echo $rank; ?></span>
                                                                <?php endif; ?>
                                                            </td>
                                                            <td class="archer-col">
                                                                <div class="archer-name">
                                                                    <?php echo htmlspecialchars($archer['FirstName'] . ' ' . $archer['LastName']); ?>
                                                                    <?php if ($archer['ArcherID'] == $archerId): ?>
                                                                        <span class="you-indicator">(You)</span>
                                                                    <?php endif; ?>
                                                                </div>
                                                            </td>
                                                            <td class="round-col"><?php echo htmlspecialchars($archer['RoundName']); ?></td>
                                                            <td class="equipment-col"><?php echo htmlspecialchars($archer['EquipmentName']); ?></td>
                                                            <td class="score-col">
                                                                <span class="score-value"><?php echo $archer['TotalScore']; ?></span>
                                                            </td>
                                                            <td class="date-col"><?php echo formatDate($archer['DateShot']); ?></td>
                                                        </tr>
                                                        <?php $rank++; ?>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                    <?php $firstCategory = false; ?>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>

        <footer>
            <p>&copy; 2025 Archery Club Database System. All rights reserved.</p>
        </footer>
    </div>

    <script>
        function showCategory(categoryName) {
            // Hide all tab contents
            const tabContents = document.querySelectorAll('.tab-content');
            tabContents.forEach(content => {
                content.classList.remove('active');
            });
            
            // Remove active class from all tab buttons
            const tabButtons = document.querySelectorAll('.tab-button');
            tabButtons.forEach(button => {
                button.classList.remove('active');
            });
            
            // Show selected tab content
            const selectedContent = document.getElementById('category-' + categoryName);
            if (selectedContent) {
                selectedContent.classList.add('active');
            }
            
            // Add active class to selected tab button
            const selectedButton = document.querySelector(`[data-category="${categoryName}"]`);
            if (selectedButton) {
                selectedButton.classList.add('active');
            }
        }
    </script>
    
    <style>
        .results-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }
        
        .results-header {
            margin-bottom: 2rem;
        }
        
        .back-link {
            margin-bottom: 1rem;
        }
        
        .back-link a {
            color: var(--primary-color);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 500;
        }
        
        .back-link a:hover {
            text-decoration: underline;
        }
        
        .header-content h2 {
            color: var(--primary-color);
            margin: 0 0 1rem 0;
            font-size: 1.75rem;
        }
        
        .header-content h2 i {
            margin-right: 0.75rem;
        }
        
        .competition-info {
            display: flex;
            flex-wrap: wrap;
            gap: 2rem;
            align-items: center;
        }
        
        .info-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #6b7280;
        }
        
        .info-item i {
            color: var(--primary-color);
        }
        
        .info-badges {
            display: flex;
            gap: 0.5rem;
        }
        
        .badge {
            padding: 0.25rem 0.75rem;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 500;
        }
        
        .badge-championship {
            background: #fef3c7;
            color: #d97706;
        }
        
        .badge-official {
            background: #ede9fe;
            color: #7c3aed;
        }
        
        .badge-contrib {
            background: #dcfce7;
            color: #059669;
        }
        
        .no-results {
            text-align: center;
            padding: 4rem 2rem;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        .no-results i {
            font-size: 3rem;
            color: #d1d5db;
            margin-bottom: 1rem;
        }
        
        .no-results h3 {
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }
        
        .results-summary {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            padding: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .summary-stats {
            display: flex;
            gap: 3rem;
            justify-content: center;
        }
        
        .stat-item {
            text-align: center;
        }
        
        .stat-number {
            display: block;
            font-size: 2rem;
            font-weight: bold;
            color: var(--primary-color);
        }
        
        .stat-label {
            color: #6b7280;
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        
        .category-tabs {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        
        .tabs-header {
            display: flex;
            background: #f9fafb;
            border-bottom: 1px solid #e5e7eb;
            overflow-x: auto;
        }
        
        .tab-button {
            padding: 1rem 1.5rem;
            border: none;
            background: transparent;
            cursor: pointer;
            font-weight: 500;
            color: #6b7280;
            white-space: nowrap;
            border-bottom: 3px solid transparent;
            transition: all 0.2s;
        }
        
        .tab-button:hover {
            background: #f3f4f6;
            color: var(--primary-color);
        }
        
        .tab-button.active {
            color: var(--primary-color);
            border-bottom-color: var(--primary-color);
            background: white;
        }
        
        .archer-count {
            font-size: 0.75rem;
            color: #9ca3af;
            font-weight: normal;
        }
        
        .tab-content {
            display: none;
            padding: 1.5rem;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .category-header {
            margin-bottom: 1.5rem;
        }
        
        .category-header h3 {
            margin: 0 0 0.25rem 0;
            color: var(--primary-color);
            font-size: 1.25rem;
        }
        
        .category-header p {
            margin: 0;
            color: #6b7280;
            font-size: 0.875rem;
        }
        
        .results-table-wrapper {
            overflow-x: auto;
        }
        
        .results-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
        }
        
        .results-table th {
            background: #f9fafb;
            padding: 0.75rem;
            text-align: left;
            font-weight: 600;
            color: #374151;
            border-bottom: 1px solid #e5e7eb;
            font-size: 0.875rem;
        }
        
        .results-table td {
            padding: 0.75rem;
            border-bottom: 1px solid #f3f4f6;
        }
        
        .results-table tr:hover {
            background: #f9fafb;
        }
        
        .current-archer {
            background: #ecfdf5 !important;
            border-left: 4px solid var(--primary-color);
        }
        
        .current-archer:hover {
            background: #d1fae5 !important;
        }
        
        .rank-col {
            width: 80px;
            text-align: center;
        }
        
        .archer-col {
            min-width: 180px;
        }
        
        .round-col {
            min-width: 120px;
        }
        
        .equipment-col {
            min-width: 120px;
        }
        
        .score-col {
            width: 100px;
            text-align: center;
        }
        
        .date-col {
            width: 120px;
        }
        
        .medal {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            padding: 0.25rem 0.5rem;
            border-radius: 12px;
            font-weight: bold;
            font-size: 0.75rem;
        }
        
        .medal-1 {
            background: linear-gradient(135deg, #ffd700, #ffed4a);
            color: #92400e;
        }
        
        .medal-2 {
            background: linear-gradient(135deg, #c0c0c0, #e5e7eb);
            color: #374151;
        }
        
        .medal-3 {
            background: linear-gradient(135deg, #cd7f32, #d97706);
            color: white;
        }
        
        .rank-number {
            font-weight: 600;
            color: #6b7280;
        }
        
        .archer-name {
            font-weight: 500;
        }
        
        .you-indicator {
            color: var(--primary-color);
            font-weight: 600;
            font-size: 0.75rem;
        }
        
        .score-value {
            font-weight: 600;
            font-size: 1.1rem;
            color: var(--primary-color);
        }
        
        @media (max-width: 768px) {
            .results-container {
                padding: 1rem;
            }
            
            .competition-info {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }
            
            .summary-stats {
                gap: 2rem;
            }
            
            .tabs-header {
                flex-wrap: nowrap;
            }
            
            .tab-button {
                padding: 0.75rem 1rem;
                font-size: 0.875rem;
            }
            
            .results-table {
                font-size: 0.875rem;
            }
            
            .results-table th,
            .results-table td {
                padding: 0.5rem;
            }
            
            .rank-col,
            .score-col,
            .date-col {
                width: auto;
                min-width: 60px;
            }
            
            .archer-col,
            .round-col,
            .equipment-col {
                min-width: 100px;
            }
        }
        
        @media (max-width: 480px) {
            .tab-content {
                padding: 1rem;
            }
            
            .results-table-wrapper {
                margin: 0 -1rem;
            }
            
            .results-table {
                font-size: 0.8rem;
            }
            
            .medal {
                font-size: 0.65rem;
                padding: 0.2rem 0.4rem;
            }
            
            .score-value {
                font-size: 1rem;
            }
        }
    </style>
</body>
</html>