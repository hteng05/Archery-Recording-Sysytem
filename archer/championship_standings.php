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

// Get selected year (default to current year)
$selectedYear = isset($_GET['year']) ? intval($_GET['year']) : date('Y');

// Get championship standings for the selected year
$standings = getChampionshipStandings($selectedYear);

// Get championship competitions for the selected year
$championshipCompetitions = getChampionshipCompetitions($selectedYear);

// Get available years
$availableYears = getChampionshipYears();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Club Championship Standings <?php echo $selectedYear; ?> - Archery Score Recording System</title>
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
                <a href="competitions.php">Competitions</a>
                <a href="club_records.php" class="active">Club Records</a>
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
            <div class="championship-container">
                <div class="championship-header">
                    <div class="header-content">
                        <h2><i class="fas fa-crown"></i> Club Championship Standings</h2>
                        <p>Annual championship rankings based on competition performance throughout the year</p>
                    </div>
                    
                    <div class="year-selector">
                        <label for="year-select">Championship Year:</label>
                        <select id="year-select" onchange="changeYear(this.value)">
                            <?php foreach ($availableYears as $year): ?>
                                <option value="<?php echo $year; ?>" <?php echo ($year == $selectedYear) ? 'selected' : ''; ?>>
                                    <?php echo $year; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="championship-content">
                    <?php if (!empty($championshipCompetitions)): ?>
                        <div class="competitions-overview">
                            <h3><i class="fas fa-trophy"></i> Championship Competitions for <?php echo $selectedYear; ?></h3>
                            <div class="competitions-grid">
                                <?php foreach ($championshipCompetitions as $comp): ?>
                                    <div class="competition-item">
                                        <div class="comp-header">
                                            <span class="comp-name"><?php echo htmlspecialchars($comp['CompetitionName']); ?></span>
                                            <span class="comp-date"><?php echo formatDate($comp['StartDate']); ?></span>
                                        </div>
                                        <div class="comp-details">
                                            <span class="comp-location"><?php echo htmlspecialchars($comp['Location']); ?></span>
                                            <?php 
                                            $endDate = new DateTime($comp['EndDate']);
                                            $today = new DateTime();
                                            $isCompleted = $today > $endDate;
                                            ?>
                                            <span class="comp-status <?php echo $isCompleted ? 'completed' : 'pending'; ?>">
                                                <?php echo $isCompleted ? 'Completed' : 'In Progress'; ?>
                                            </span>
                                        </div>
                                        <div class="comp-actions">
                                            <?php if ($isCompleted): ?>
                                                <a href="competition_results.php?id=<?php echo $comp['CompetitionID']; ?>" class="btn-small btn-primary">
                                                    <i class="fas fa-trophy"></i> Results
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (empty($standings)): ?>
                        <div class="no-standings">
                            <i class="fas fa-crown"></i>
                            <h3>No Championship Standings Available</h3>
                            <p>Championship standings for <?php echo $selectedYear; ?> have not been calculated yet.</p>
                            <?php if (!empty($championshipCompetitions)): ?>
                                <p>Standings will be updated as championship competitions are completed.</p>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div class="standings-section">
                            <h3><i class="fas fa-ranking-star"></i> Final Standings - <?php echo $selectedYear; ?></h3>
                            
                            <div class="category-tabs">
                                <div class="tabs-header">
                                    <?php $firstCategory = true; ?>
                                    <?php foreach ($standings as $category => $archers): ?>
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
                                    <?php foreach ($standings as $category => $archers): ?>
                                        <div class="tab-content <?php echo $firstCategory ? 'active' : ''; ?>" 
                                             id="category-<?php echo htmlspecialchars($category); ?>">
                                            
                                            <div class="category-header">
                                                <h4><?php echo htmlspecialchars($category); ?></h4>
                                                <p><?php echo count($archers); ?> participants</p>
                                            </div>

                                            <div class="standings-table-wrapper">
                                                <table class="standings-table">
                                                    <thead>
                                                        <tr>
                                                            <th class="rank-col">Rank</th>
                                                            <th class="archer-col">Archer</th>
                                                            <th class="points-col">Total Points</th>
                                                            <th class="competitions-col">Competitions</th>
                                                            <th class="best-score-col">Best Score</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php foreach ($archers as $archer): ?>
                                                            <tr class="<?php echo ($archer['ArcherID'] == $archerId) ? 'current-archer' : ''; ?>">
                                                                <td class="rank-col">
                                                                    <?php if ($archer['Rank'] <= 3): ?>
                                                                        <span class="medal medal-<?php echo $archer['Rank']; ?>">
                                                                            <?php if ($archer['Rank'] == 1): ?>
                                                                                <i class="fas fa-crown"></i>
                                                                            <?php elseif ($archer['Rank'] == 2): ?>
                                                                                <i class="fas fa-medal"></i>
                                                                            <?php else: ?>
                                                                                <i class="fas fa-award"></i>
                                                                            <?php endif; ?>
                                                                            <?php echo $archer['Rank']; ?>
                                                                        </span>
                                                                    <?php else: ?>
                                                                        <span class="rank-number"><?php echo $archer['Rank']; ?></span>
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
                                                                <td class="points-col">
                                                                    <span class="points-value"><?php echo $archer['TotalPoints']; ?></span>
                                                                    <span class="points-label">pts</span>
                                                                </td>
                                                                <td class="competitions-col">
                                                                    <?php 
                                                                    // Get this archer's competition participation
                                                                    $participatedComps = getArcherChampionshipParticipation($archer['ArcherID'], $selectedYear);
                                                                    echo count($participatedComps) . ' / ' . count($championshipCompetitions);
                                                                    ?>
                                                                </td>
                                                                <td class="best-score-col">
                                                                    <?php 
                                                                    // Get archer's best score from championship competitions
                                                                    $bestScore = getArcherBestChampionshipScore($archer['ArcherID'], $selectedYear);
                                                                    echo $bestScore ? $bestScore : '-';
                                                                    ?>
                                                                </td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                        <?php $firstCategory = false; ?>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="championship-rules">
                        <h3><i class="fas fa-info-circle"></i> Championship Scoring System</h3>
                        <div class="rules-content">
                            <div class="rule-item">
                                <h4>Points Calculation</h4>
                                <p>Championship points are awarded based on your placement in each championship competition:</p>
                                <ul>
                                    <li><strong>1st Place:</strong> 25 points</li>
                                    <li><strong>2nd Place:</strong> 18 points</li>
                                    <li><strong>3rd Place:</strong> 15 points</li>
                                    <li><strong>4th Place:</strong> 12 points</li>
                                    <li><strong>5th Place:</strong> 10 points</li>
                                    <li><strong>6th Place:</strong> 8 points</li>
                                    <li><strong>7th Place:</strong> 6 points</li>
                                    <li><strong>8th Place:</strong> 4 points</li>
                                    <li><strong>9th Place:</strong> 2 points</li>
                                    <li><strong>10th Place & below:</strong> 1 point</li>
                                </ul>
                            </div>
                            <div class="rule-item">
                                <h4>Eligibility</h4>
                                <p>To be eligible for championship standings, archers must:</p>
                                <ul>
                                    <li>Participate in at least 50% of championship competitions</li>
                                    <li>Compete in their designated age and equipment category</li>
                                    <li>Have scores approved by club recorders</li>
                                </ul>
                            </div>
                            <div class="rule-item">
                                <h4>Final Rankings</h4>
                                <p>Final championship rankings are determined by total points accumulated across all championship competitions during the calendar year. In case of ties, the archer with the higher best single score wins.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>

        <footer>
            <p>&copy; 2025 Archery Club Database System. All rights reserved.</p>
        </footer>
    </div>

    <script>
        function changeYear(year) {
            window.location.href = 'championship_standings.php?year=' + year;
        }
        
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
        .championship-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }
        
        .championship-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 2rem;
            gap: 2rem;
        }
        
        .header-content h2 {
            color: var(--primary-color);
            margin: 0 0 0.5rem 0;
            font-size: 1.75rem;
        }
        
        .header-content h2 i {
            margin-right: 0.75rem;
            color: #ffd700;
        }
        
        .header-content p {
            color: #6b7280;
            margin: 0;
        }
        
        .year-selector {
            display: flex;
            align-items: center;
            gap: 1rem;
            background: white;
            padding: 1rem;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            white-space: nowrap;
        }
        
        .year-selector label {
            font-weight: 500;
            color: #374151;
        }
        
        .year-selector select {
            padding: 0.5rem;
            border: 1px solid #d1d5db;
            border-radius: 4px;
            background: white;
            font-size: 1rem;
        }
        
        .competitions-overview {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            padding: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .competitions-overview h3 {
            margin: 0 0 1rem 0;
            color: var(--primary-color);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .competitions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1rem;
        }
        
        .competition-item {
            background: #f9fafb;
            border-radius: 6px;
            padding: 1rem;
            border-left: 4px solid var(--primary-color);
        }
        
        .comp-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 0.5rem;
        }
        
        .comp-name {
            font-weight: 600;
            color: #111827;
        }
        
        .comp-date {
            font-size: 0.875rem;
            color: #6b7280;
        }
        
        .comp-details {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.75rem;
        }
        
        .comp-location {
            font-size: 0.875rem;
            color: #6b7280;
        }
        
        .comp-status {
            font-size: 0.75rem;
            padding: 0.25rem 0.5rem;
            border-radius: 12px;
            font-weight: 500;
        }
        
        .comp-status.completed {
            background: #d1fae5;
            color: #065f46;
        }
        
        .comp-status.pending {
            background: #fef3c7;
            color: #92400e;
        }
        
        .comp-actions {
            display: flex;
            gap: 0.5rem;
        }
        
        .btn-small {
            padding: 0.375rem 0.75rem;
            font-size: 0.75rem;
            border-radius: 4px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
        }
        
        .btn-primary {
            background: var(--primary-color);
            color: white;
        }
        
        .btn-primary:hover {
            background: #065f46;
        }
        
        .no-standings {
            text-align: center;
            padding: 4rem 2rem;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        .no-standings i {
            font-size: 3rem;
            color: #ffd700;
            margin-bottom: 1rem;
        }
        
        .no-standings h3 {
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }
        
        .standings-section {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            margin-bottom: 2rem;
        }
        
        .standings-section > h3 {
            background: #f9fafb;
            padding: 1.5rem;
            margin: 0;
            color: var(--primary-color);
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .category-tabs {
            /* Using existing tab styles from competition results */
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
        
        .category-header h4 {
            margin: 0 0 0.25rem 0;
            color: var(--primary-color);
            font-size: 1.125rem;
        }
        
        .category-header p {
            margin: 0;
            color: #6b7280;
            font-size: 0.875rem;
        }
        
        .standings-table-wrapper {
            overflow-x: auto;
        }
        
        .standings-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
        }
        
        .standings-table th {
            background: #f9fafb;
            padding: 0.75rem;
            text-align: left;
            font-weight: 600;
            color: #374151;
            border-bottom: 1px solid #e5e7eb;
            font-size: 0.875rem;
        }
        
        .standings-table td {
            padding: 0.75rem;
            border-bottom: 1px solid #f3f4f6;
        }
        
        .standings-table tr:hover {
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
        
        .points-col {
            width: 120px;
            text-align: center;
        }
        
        .competitions-col {
            width: 120px;
            text-align: center;
        }
        
        .best-score-col {
            width: 120px;
            text-align: center;
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
        
        .points-value {
            font-weight: 600;
            font-size: 1.1rem;
            color: var(--primary-color);
        }
        
        .points-label {
            font-size: 0.75rem;
            color: #6b7280;
        }
        
        .championship-rules {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            padding: 1.5rem;
        }
        
        .championship-rules h3 {
            margin: 0 0 1rem 0;
            color: var(--primary-color);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .rules-content {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
        }
        
        .rule-item h4 {
            color: #111827;
            margin: 0 0 0.5rem 0;
        }
        
        .rule-item p {
            color: #6b7280;
            margin-bottom: 0.75rem;
        }
        
        .rule-item ul {
            margin: 0;
            padding-left: 1.25rem;
        }
        
        .rule-item li {
            color: #6b7280;
            margin-bottom: 0.25rem;
        }
        
        @media (max-width: 768px) {
            .championship-container {
                padding: 1rem;
            }
            
            .championship-header {
                flex-direction: column;
                gap: 1rem;
            }
            
            .year-selector {
                align-self: stretch;
            }
            
            .competitions-grid {
                grid-template-columns: 1fr;
            }
            
            .rules-content {
                grid-template-columns: 1fr;
            }
            
            .standings-table {
                font-size: 0.875rem;
            }
            
            .standings-table th,
            .standings-table td {
                padding: 0.5rem;
            }
        }
    </style>
</body>
</html>