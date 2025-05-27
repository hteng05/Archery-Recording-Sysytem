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

// Get personal bests
$personalBests = getPersonalBests($archerId);

// Group by equipment type
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
            <div class="personal-bests-container">
                <h2>Personal Bests</h2>
                <p>Your best scores for each round and equipment type.</p>
                
                <div class="archer-info-card">
                    <div class="archer-avatar">
                        <i class="fas fa-user-circle"></i>
                    </div>
                    <div class="archer-details">
                        <h3><?php echo htmlspecialchars($archer['FirstName'] . ' ' . $archer['LastName']); ?></h3>
                        <div class="detail-row">
                            <span class="detail-label">Class:</span>
                            <span class="detail-value"><?php echo htmlspecialchars($archer['ClassName']); ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Division:</span>
                            <span class="detail-value"><?php echo htmlspecialchars($archer['DivisionName']); ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Default Equipment:</span>
                            <span class="detail-value"><?php echo htmlspecialchars($archer['EquipmentName']); ?></span>
                        </div>
                    </div>
                </div>
                
                <?php if (!empty($groupedBests)): ?>
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
                                <div class="pb-grid">
                                    <?php foreach ($bests as $pb): ?>
                                        <div class="pb-card">
                                            <div class="pb-header">
                                                <h4><?php echo htmlspecialchars($pb['RoundName']); ?></h4>
                                            </div>
                                            <div class="pb-score">
                                                <span class="score-value"><?php echo $pb['TotalScore']; ?></span>
                                            </div>
                                            <div class="pb-details">
                                                <div class="detail-item">
                                                    <span class="item-label">Date Achieved:</span>
                                                    <span class="item-value"><?php echo formatDate($pb['DateAchieved']); ?></span>
                                                </div>
                                                <?php if ($pb['IsClubBest']): ?>
                                                    <div class="pb-badge">
                                                        <i class="fas fa-trophy"></i> Club Best
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            <a href="score_details.php?score_id=<?php echo $pb['ScoreID']; ?>" class="btn btn-sm btn-secondary view-details-btn">
                                                View Details
                                            </a>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <?php $firstTab = false; ?>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="no-pbs">
                        <i class="fas fa-medal"></i>
                        <h3>No Personal Bests</h3>
                        <p>You haven't recorded any scores yet.</p>
                        <a href="enter_score.php" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Enter Your First Score
                        </a>
                    </div>
                <?php endif; ?>
                
                <div class="club-records-section">
                    <h3>Club Records</h3>
                    <p>View the best scores achieved by all archers in the club.</p>
                    <a href="club_records.php" class="btn btn-primary">
                        <i class="fas fa-trophy"></i> View Club Records
                    </a>
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
    </script>
    
    <style>
        /* Personal Bests Page Specific Styles */
        .personal-bests-container {
            padding: 1rem;
        }
        
        .personal-bests-container h2 {
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }
        
        .personal-bests-container > p {
            margin-bottom: 1.5rem;
            color: #6b7280;
        }
        
        .archer-info-card {
            background-color: white;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
        }
        
        .archer-avatar {
            font-size: 3rem;
            color: var(--primary-color);
            margin-right: 1.5rem;
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
        
        .equipment-tabs {
            margin-bottom: 1.5rem;
        }
        
        .nav-tabs {
            display: flex;
            list-style: none;
            padding: 0;
            margin: 0;
            border-bottom: 1px solid var(--gray-color);
            overflow-x: auto;
            white-space: nowrap;
        }
        
        .tab-item {
            margin-right: 0.5rem;
        }
        
        .tab-link {
            display: block;
            padding: 0.75rem 1rem;
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
        
        .pb-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .pb-card {
            background-color: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            display: flex;
            flex-direction: column;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        
        .pb-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .pb-header {
            padding: 1rem;
            background-color: var(--primary-color);
            color: white;
        }
        
        .pb-header h4 {
            margin: 0;
            font-size: 1.1rem;
        }
        
        .pb-score {
            padding: 1.5rem 1rem;
            text-align: center;
            background-color: var(--light-color);
        }
        
        .score-value {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--primary-color);
        }
        
        .pb-details {
            padding: 1rem;
            flex-grow: 1;
        }
        
        .detail-item {
            margin-bottom: 0.5rem;
        }
        
        .item-label {
            font-weight: 500;
            color: #6b7280;
            display: block;
            font-size: 0.9rem;
        }
        
        .item-value {
            display: block;
        }
        
        .pb-badge {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            background-color: #fef3c7;
            color: #d97706;
            border-radius: 4px;
            font-size: 0.85rem;
            margin-top: 0.5rem;
        }
        
        .pb-badge i {
            margin-right: 0.25rem;
        }
        
        .view-details-btn {
            margin: 0;
            padding: 0.75rem;
            border-radius: 0;
            display: block;
            text-align: center;
            background-color: #f9fafb;
            font-weight: 500;
        }
        
        .view-details-btn:hover {
            background-color: var(--primary-light);
            color: white;
        }
        
        .no-pbs {
            background-color: white;
            border-radius: 8px;
            padding: 3rem 1.5rem;
            text-align: center;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
        }
        
        .no-pbs i {
            font-size: 3rem;
            color: #d1d5db;
            margin-bottom: 1rem;
        }
        
        .no-pbs h3 {
            color: var(--primary-color);
            margin-bottom: 1rem;
        }
        
        .no-pbs p {
            margin-bottom: 1.5rem;
        }
        
        .club-records-section {
            background-color: white;
            border-radius: 8px;
            padding: 1.5rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
        }
        
        .club-records-section h3 {
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }
        
        .club-records-section p {
            margin-bottom: 1rem;
            color: #6b7280;
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .archer-info-card {
                flex-direction: column;
                text-align: center;
            }
            
            .archer-avatar {
                margin-right: 0;
                margin-bottom: 1rem;
            }
            
            .pb-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</body>
</html>