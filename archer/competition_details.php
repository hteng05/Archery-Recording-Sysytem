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

// Check if user has participated in this competition
$userParticipated = false;
$userScore = null;
$results = getCompetitionResults($competitionId);
foreach ($results as $category => $archers) {
    foreach ($archers as $archerResult) {
        if ($archerResult['ArcherID'] == $archerId) {
            $userParticipated = true;
            $userScore = $archerResult;
            break 2;
        }
    }
}

// Determine competition status
$startDate = new DateTime($competition['StartDate']);
$endDate = new DateTime($competition['EndDate']);
$today = new DateTime();

$status = '';
$statusClass = '';
$canEnter = false;

if ($today < $startDate) {
    $status = 'Upcoming';
    $statusClass = 'status-upcoming';
    $canEnter = false;
} elseif ($today >= $startDate && $today <= $endDate) {
    $status = 'In Progress';
    $statusClass = 'status-active';
    $canEnter = true;
} else {
    $status = 'Completed';
    $statusClass = 'status-completed';
    $canEnter = false;
}

// Get total participants count
$totalParticipants = 0;
foreach ($results as $category => $archers) {
    $totalParticipants += count($archers);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($competition['CompetitionName']); ?> Details - Archery Score Recording System</title>
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
            <div class="details-container">
                <div class="details-header">
                    <div class="back-link">
                        <a href="competitions.php"><i class="fas fa-arrow-left"></i> Back to Competitions</a>
                    </div>
                    
                    <div class="header-content">
                        <div class="title-section">
                            <h2><i class="fas fa-trophy"></i> <?php echo htmlspecialchars($competition['CompetitionName']); ?></h2>
                            <div class="header-badges">
                                <span class="badge <?php echo $statusClass; ?>"><?php echo $status; ?></span>
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

                <div class="details-content">
                    <div class="main-details">
                        <div class="info-card">
                            <h3><i class="fas fa-info-circle"></i> Competition Information</h3>
                            <div class="info-grid">
                                <div class="info-item">
                                    <span class="info-label">Start Date:</span>
                                    <span class="info-value"><?php echo formatDate($competition['StartDate']); ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">End Date:</span>
                                    <span class="info-value"><?php echo formatDate($competition['EndDate']); ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Location:</span>
                                    <span class="info-value"><?php echo htmlspecialchars($competition['Location']); ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Duration:</span>
                                    <span class="info-value">
                                        <?php 
                                        $duration = $startDate->diff($endDate)->days + 1;
                                        echo $duration . ' day' . ($duration > 1 ? 's' : '');
                                        ?>
                                    </span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Type:</span>
                                    <span class="info-value">
                                        <?php if ($competition['IsChampionship']): ?>
                                            Championship Competition
                                        <?php elseif ($competition['IsOfficial']): ?>
                                            Official Competition
                                        <?php else: ?>
                                            Club Competition
                                        <?php endif; ?>
                                    </span>
                                </div>
                                <?php if ($totalParticipants > 0): ?>
                                    <div class="info-item">
                                        <span class="info-label">Participants:</span>
                                        <span class="info-value"><?php echo $totalParticipants; ?> archers</span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <?php if ($userParticipated && $userScore): ?>
                            <div class="info-card your-participation">
                                <h3><i class="fas fa-user-check"></i> Your Participation</h3>
                                <div class="participation-details">
                                    <div class="score-summary">
                                        <div class="score-item">
                                            <span class="score-label">Your Score:</span>
                                            <span class="score-value"><?php echo $userScore['TotalScore']; ?></span>
                                        </div>
                                        <div class="score-item">
                                            <span class="score-label">Round:</span>
                                            <span class="score-value"><?php echo htmlspecialchars($userScore['RoundName']); ?></span>
                                        </div>
                                        <div class="score-item">
                                            <span class="score-label">Equipment:</span>
                                            <span class="score-value"><?php echo htmlspecialchars($userScore['EquipmentName']); ?></span>
                                        </div>
                                        <div class="score-item">
                                            <span class="score-label">Date Shot:</span>
                                            <span class="score-value"><?php echo formatDate($userScore['DateShot']); ?></span>
                                        </div>
                                    </div>
                                    
                                    <?php 
                                    // Find user's rank in their category
                                    $userCategory = $userScore['ClassName'] . ' ' . $userScore['DivisionName'];
                                    $userRank = 1;
                                    if (isset($results[$userCategory])) {
                                        foreach ($results[$userCategory] as $index => $archer) {
                                            if ($archer['ArcherID'] == $archerId) {
                                                $userRank = $index + 1;
                                                break;
                                            }
                                        }
                                    }
                                    ?>
                                    
                                    <div class="ranking-info">
                                        <div class="rank-display">
                                            <?php if ($userRank <= 3): ?>
                                                <span class="medal medal-<?php echo $userRank; ?>">
                                                    <?php if ($userRank == 1): ?>
                                                        <i class="fas fa-trophy"></i> 1st Place
                                                    <?php elseif ($userRank == 2): ?>
                                                        <i class="fas fa-medal"></i> 2nd Place
                                                    <?php else: ?>
                                                        <i class="fas fa-award"></i> 3rd Place
                                                    <?php endif; ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="rank-position"><?php echo $userRank; ?><?php echo getOrdinalSuffix($userRank); ?> Place</span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="category-info">
                                            in <?php echo htmlspecialchars($userCategory); ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if ($competition['ContributesToChampionship']): ?>
                            <div class="info-card championship-info">
                                <h3><i class="fas fa-star"></i> Championship Information</h3>
                                <div class="championship-details">
                                    <p><i class="fas fa-trophy"></i> This competition contributes points toward the annual club championship standings.</p>
                                    <p><i class="fas fa-calculator"></i> Championship points are awarded based on your placement in your category.</p>
                                    <?php if ($status === 'Completed'): ?>
                                        <p><i class="fas fa-check-circle"></i> Championship points have been calculated and added to the standings.</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($results)): ?>
                            <div class="info-card categories-overview">
                                <h3><i class="fas fa-users"></i> Competition Categories</h3>
                                <div class="categories-grid">
                                    <?php foreach ($results as $category => $archers): ?>
                                        <div class="category-item">
                                            <span class="category-name"><?php echo htmlspecialchars($category); ?></span>
                                            <span class="category-count"><?php echo count($archers); ?> participants</span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="action-section">
                        <div class="action-card">
                            <h3><i class="fas fa-play-circle"></i> Actions</h3>
                            <div class="action-buttons">
                                <?php if ($canEnter && !$userParticipated): ?>
                                    <a href="enter_score.php" class="btn btn-primary">
                                        <i class="fas fa-bullseye"></i> Enter Score for This Competition
                                    </a>
                                <?php elseif ($userParticipated): ?>
                                    <div class="participated-notice">
                                        <i class="fas fa-check-circle"></i>
                                        <span>You have already participated in this competition</span>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($results)): ?>
                                    <a href="competition_results.php?id=<?php echo $competition['CompetitionID']; ?>" class="btn btn-secondary">
                                        <i class="fas fa-trophy"></i> View Results
                                    </a>
                                <?php endif; ?>
                                
                                <a href="competitions.php" class="btn btn-outline">
                                    <i class="fas fa-list"></i> All Competitions
                                </a>
                            </div>
                        </div>

                        <?php if ($status === 'Upcoming'): ?>
                            <div class="countdown-card">
                                <h3><i class="fas fa-clock"></i> Countdown</h3>
                                <div class="countdown-display">
                                    <div class="countdown-item">
                                        <span class="countdown-number" id="days">-</span>
                                        <span class="countdown-label">Days</span>
                                    </div>
                                    <div class="countdown-item">
                                        <span class="countdown-number" id="hours">-</span>
                                        <span class="countdown-label">Hours</span>
                                    </div>
                                    <div class="countdown-item">
                                        <span class="countdown-number" id="minutes">-</span>
                                        <span class="countdown-label">Minutes</span>
                                    </div>
                                </div>
                                <p class="countdown-text">Until competition begins</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>

        <footer>
            <p>&copy; 2025 Archery Club Database System. All rights reserved.</p>
        </footer>
    </div>

    <?php if ($status === 'Upcoming'): ?>
        <script>
            // Countdown timer for upcoming competitions
            const startDate = new Date('<?php echo $competition['StartDate']; ?>T00:00:00').getTime();
            
            function updateCountdown() {
                const now = new Date().getTime();
                const distance = startDate - now;
                
                if (distance > 0) {
                    const days = Math.floor(distance / (1000 * 60 * 60 * 24));
                    const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                    const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                    
                    document.getElementById('days').textContent = days;
                    document.getElementById('hours').textContent = hours;
                    document.getElementById('minutes').textContent = minutes;
                } else {
                    document.getElementById('days').textContent = '0';
                    document.getElementById('hours').textContent = '0';
                    document.getElementById('minutes').textContent = '0';
                }
            }
            
            updateCountdown();
            setInterval(updateCountdown, 60000); // Update every minute
        </script>
    <?php endif; ?>
    
    <style>
        .details-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }
        
        .details-header {
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
        
        .title-section h2 {
            color: var(--primary-color);
            margin: 0 0 1rem 0;
            font-size: 1.75rem;
        }
        
        .title-section h2 i {
            margin-right: 0.75rem;
        }
        
        .header-badges {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }
        
        .badge {
            padding: 0.25rem 0.75rem;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 500;
        }
        
        .status-upcoming {
            background: #dbeafe;
            color: #2563eb;
        }
        
        .status-active {
            background: #d1fae5;
            color: #059669;
        }
        
        .status-completed {
            background: #f3f4f6;
            color: #6b7280;
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
        
        .details-content {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 2rem;
        }
        
        .info-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .info-card h3 {
            margin: 0 0 1rem 0;
            color: var(--primary-color);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
        }
        
        .info-item {
            display: flex;
            justify-content: space-between;
            padding: 0.5rem 0;
            border-bottom: 1px solid #f3f4f6;
        }
        
        .info-item:last-child {
            border-bottom: none;
        }
        
        .info-label {
            font-weight: 500;
            color: #6b7280;
        }
        
        .info-value {
            font-weight: 600;
            color: #111827;
        }
        
        .your-participation {
            border-left: 4px solid var(--primary-color);
        }
        
        .participation-details {
            display: grid;
            gap: 1.5rem;
        }
        
        .score-summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
        }
        
        .score-item {
            text-align: center;
            padding: 1rem;
            background: #f9fafb;
            border-radius: 6px;
        }
        
        .score-label {
            display: block;
            font-size: 0.875rem;
            color: #6b7280;
            margin-bottom: 0.25rem;
        }
        
        .score-value {
            display: block;
            font-size: 1.125rem;
            font-weight: 600;
            color: var(--primary-color);
        }
        
        .ranking-info {
            text-align: center;
            padding: 1rem;
            background: #f0fdf4;
            border-radius: 6px;
        }
        
        .rank-display {
            margin-bottom: 0.5rem;
        }
        
        .medal {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: bold;
            font-size: 0.875rem;
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
        
        .rank-position {
            font-size: 1.125rem;
            font-weight: 600;
            color: var(--primary-color);
        }
        
        .category-info {
            color: #6b7280;
            font-size: 0.875rem;
        }
        
        .championship-details p {
            display: flex;
            align-items: flex-start;
            gap: 0.5rem;
            margin-bottom: 0.75rem;
            color: #374151;
        }
        
        .championship-details p:last-child {
            margin-bottom: 0;
        }
        
        .championship-details i {
            color: var(--primary-color);
            margin-top: 0.125rem;
        }
        
        .categories-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }
        
        .category-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem;
            background: #f9fafb;
            border-radius: 6px;
        }
        
        .category-name {
            font-weight: 500;
            color: #374151;
        }
        
        .category-count {
            font-size: 0.875rem;
            color: #6b7280;
            background: white;
            padding: 0.25rem 0.5rem;
            border-radius: 12px;
        }
        
        .action-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .action-card h3 {
            margin: 0 0 1rem 0;
            color: var(--primary-color);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .action-buttons {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        
        .btn {
            padding: 0.75rem 1rem;
            border: none;
            border-radius: 6px;
            font-weight: 500;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            cursor: pointer;
            transition: all 0.2s;
            text-align: center;
        }
        
        .btn-primary {
            background: var(--primary-color);
            color: white;
        }
        
        .btn-primary:hover {
            background: #065f46;
        }
        
        .btn-secondary {
            background: #6b7280;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #4b5563;
        }
        
        .btn-outline {
            background: transparent;
            color: var(--primary-color);
            border: 1px solid var(--primary-color);
        }
        
        .btn-outline:hover {
            background: var(--primary-color);
            color: white;
        }
        
        .participated-notice {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem;
            background: #d1fae5;
            color: #065f46;
            border-radius: 6px;
            font-weight: 500;
        }
        
        .countdown-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            padding: 1.5rem;
            text-align: center;
        }
        
        .countdown-card h3 {
            margin: 0 0 1rem 0;
            color: var(--primary-color);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }
        
        .countdown-display {
            display: flex;
            justify-content: center;
            gap: 1rem;
            margin-bottom: 1rem;
        }
        
        .countdown-item {
            text-align: center;
        }
        
        .countdown-number {
            display: block;
            font-size: 2rem;
            font-weight: bold;
            color: var(--primary-color);
        }
        
        .countdown-label {
            font-size: 0.875rem;
            color: #6b7280;
        }
        
        .countdown-text {
            color: #6b7280;
            margin: 0;
        }
        
        @media (max-width: 768px) {
            .details-container {
                padding: 1rem;
            }
            
            .details-content {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
            
            .info-grid {
                grid-template-columns: 1fr;
            }
            
            .score-summary {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .categories-grid {
                grid-template-columns: 1fr;
            }
            
            .countdown-display {
                gap: 0.5rem;
            }
            
            .countdown-number {
                font-size: 1.5rem;
            }
        }
    </style>
</body>
</html>

<?php
function getOrdinalSuffix($number) {
    if ($number % 100 >= 11 && $number % 100 <= 13) {
        return 'th';
    }
    switch ($number % 10) {
        case 1: return 'st';
        case 2: return 'nd';
        case 3: return 'rd';
        default: return 'th';
    }
}
?>