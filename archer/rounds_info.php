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

// Get all rounds
$rounds = getAllRounds();

// Get archer's category for equivalent rounds
$conn = getDbConnection();
$categoryId = 0;

if ($conn) {
    $stmt = $conn->prepare("SELECT CategoryID FROM CategoryTable 
                          WHERE ClassID = ? AND DivisionID = ?");
    $stmt->bind_param("ii", $archer['ClassID'], $archer['DefaultDivisionID']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $categoryId = $result->fetch_assoc()['CategoryID'];
    }
}

// Get selected round details
$selectedRoundId = isset($_GET['round_id']) ? intval($_GET['round_id']) : 0;
$selectedRound = null;
$equivalentRounds = [];

if ($selectedRoundId > 0) {
    $selectedRound = getRoundById($selectedRoundId);
    
    if ($selectedRound && $categoryId > 0) {
        $equivalentRounds = getEquivalentRounds($selectedRoundId, $categoryId);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rounds Information - Archery Score Recording System</title>
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
                <a href="rounds_info.php" class="active">Rounds Info</a>
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
            <div class="rounds-info-container">
                <h2>Rounds Information</h2>
                <p>Select a round to view its details and find equivalent rounds for your category.</p>
                
                <div class="rounds-grid">
                    <div class="rounds-list">
                        <h3>Available Rounds</h3>
                        <div class="search-box">
                            <input type="text" id="round-search" placeholder="Search rounds..." class="form-control">
                            <i class="fas fa-search"></i>
                        </div>
                        <ul id="rounds-list">
                            <?php foreach ($rounds as $round): ?>
                                <li class="round-item <?php echo ($selectedRoundId === $round['RoundID']) ? 'active' : ''; ?>">
                                    <a href="rounds_info.php?round_id=<?php echo $round['RoundID']; ?>">
                                        <span class="round-name"><?php echo htmlspecialchars($round['RoundName']); ?></span>
                                        <span class="round-arrows"><?php echo $round['TotalArrows']; ?> arrows</span>
                                        <?php if ($round['IsOfficial']): ?>
                                            <span class="round-badge">Official</span>
                                        <?php endif; ?>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    
                    <div class="round-details">
                        <?php if ($selectedRound): ?>
                            <h3><?php echo htmlspecialchars($selectedRound['RoundName']); ?></h3>
                            
                            <div class="round-summary">
                                <div class="summary-item">
                                    <span class="item-label">Total Arrows:</span>
                                    <span class="item-value"><?php echo $selectedRound['TotalArrows']; ?></span>
                                </div>
                                <div class="summary-item">
                                    <span class="item-label">Status:</span>
                                    <span class="item-value">
                                        <?php echo $selectedRound['IsOfficial'] ? 'Official' : 'Non-Official'; ?>
                                    </span>
                                </div>
                                <div class="summary-item">
                                    <span class="item-label">Effective From:</span>
                                    <span class="item-value"><?php echo formatDate($selectedRound['DateEffectiveFrom']); ?></span>
                                </div>
                                <?php if ($selectedRound['DateEffectiveTo']): ?>
                                    <div class="summary-item">
                                        <span class="item-label">Effective To:</span>
                                        <span class="item-value"><?php echo formatDate($selectedRound['DateEffectiveTo']); ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <?php if (!empty($selectedRound['ranges'])): ?>
                                <div class="ranges-section">
                                    <h4>Ranges</h4>
                                    <div class="ranges-table-container">
                                        <table class="ranges-table">
                                            <thead>
                                                <tr>
                                                    <th>Distance</th>
                                                    <th>Target Face</th>
                                                    <th>Ends</th>
                                                    <th>Arrows per End</th>
                                                    <th>Total Arrows</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($selectedRound['ranges'] as $range): ?>
                                                    <tr>
                                                        <td><?php echo $range['Distance']; ?>m</td>
                                                        <td><?php echo $range['TargetFaceSize']; ?>cm</td>
                                                        <td><?php echo $range['NumberOfEnds']; ?></td>
                                                        <td><?php echo $range['ArrowsPerEnd']; ?></td>
                                                        <td><?php echo $range['NumberOfEnds'] * $range['ArrowsPerEnd']; ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($equivalentRounds)): ?>
                                <div class="equivalent-rounds">
                                    <h4>Equivalent Rounds for Your Category</h4>
                                    <p class="category-info">
                                        Your Category: <strong><?php echo htmlspecialchars($archer['ClassName'] . ' ' . $archer['DivisionName']); ?></strong>
                                    </p>
                                    <ul class="equivalent-list">
                                        <?php foreach ($equivalentRounds as $equivRound): ?>
                                            <li>
                                                <a href="rounds_info.php?round_id=<?php echo $equivRound['EquivalentToRoundID']; ?>">
                                                    <?php echo htmlspecialchars($equivRound['RoundName']); ?>
                                                </a>
                                                <span class="equiv-dates">
                                                    (<?php echo formatDate($equivRound['EffectiveFrom']); ?>
                                                    <?php echo $equivRound['EffectiveTo'] ? ' to ' . formatDate($equivRound['EffectiveTo']) : ''; ?>)
                                                </span>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php else: ?>
                                <?php if ($selectedRound): ?>
                                    <div class="equivalent-rounds">
                                        <h4>Equivalent Rounds</h4>
                                        <p>No equivalent rounds found for your category.</p>
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>
                            
                        <?php else: ?>
                            <div class="no-round-selected">
                                <i class="fas fa-bullseye"></i>
                                <h3>Round Information</h3>
                                <p>Select a round from the list to view its details.</p>
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

    <script src="../js/main.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Round search functionality
            const searchInput = document.getElementById('round-search');
            const roundsList = document.getElementById('rounds-list');
            const roundItems = roundsList.querySelectorAll('.round-item');
            
            searchInput.addEventListener('input', function() {
                const searchTerm = this.value.toLowerCase();
                
                roundItems.forEach(item => {
                    const roundName = item.querySelector('.round-name').textContent.toLowerCase();
                    
                    if (roundName.includes(searchTerm)) {
                        item.style.display = '';
                    } else {
                        item.style.display = 'none';
                    }
                });
            });
        });
    </script>
    
    <style>
        /* Rounds Info Page Specific Styles */
        .rounds-info-container {
            padding: 1rem;
        }
        
        .rounds-info-container h2 {
            color: var(--primary-color);
            margin-bottom: 1rem;
        }
        
        .rounds-grid {
            display: grid;
            grid-template-columns: 300px 1fr;
            gap: 2rem;
            margin-top: 2rem;
        }
        
        .rounds-list {
            background-color: white;
            border-radius: 8px;
            padding: 1.5rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        .rounds-list h3 {
            color: var(--primary-color);
            margin-bottom: 1rem;
        }
        
        .search-box {
            position: relative;
            margin-bottom: 1rem;
        }
        
        .search-box input {
            padding-right: 2.5rem;
        }
        
        .search-box i {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #6b7280;
        }
        
        #rounds-list {
            list-style: none;
            padding: 0;
            margin: 0;
            max-height: 500px;
            overflow-y: auto;
        }
        
        .round-item {
            margin-bottom: 0.5rem;
        }
        
        .round-item a {
            display: block;
            padding: 0.75rem;
            background-color: #f9fafb;
            border-radius: 4px;
            text-decoration: none;
            color: var(--text-color);
            transition: all 0.2s ease;
        }
        
        .round-item a:hover {
            background-color: #f3f4f6;
        }
        
        .round-item.active a {
            background-color: var(--primary-color);
            color: white;
        }
        
        .round-name {
            display: block;
            font-weight: 500;
            margin-bottom: 0.25rem;
        }
        
        .round-arrows {
            display: block;
            font-size: 0.85rem;
            color: #6b7280;
        }
        
        .round-item.active .round-arrows {
            color: rgba(255, 255, 255, 0.8);
        }
        
        .round-badge {
            display: inline-block;
            padding: 0.15rem 0.5rem;
            background-color: #dbeafe;
            color: #2563eb;
            border-radius: 9999px;
            font-size: 0.75rem;
            margin-top: 0.25rem;
        }
        
        .round-item.active .round-badge {
            background-color: rgba(255, 255, 255, 0.2);
            color: white;
        }
        
        .round-details {
            background-color: white;
            border-radius: 8px;
            padding: 1.5rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        .round-details h3 {
            color: var(--primary-color);
            margin-bottom: 1.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid var(--gray-color);
        }
        
        .round-summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .summary-item {
            display: flex;
            flex-direction: column;
        }
        
        .item-label {
            font-size: 0.9rem;
            color: #6b7280;
        }
        
        .item-value {
            font-size: 1.1rem;
            font-weight: 500;
        }
        
        .ranges-section {
            margin-bottom: 2rem;
        }
        
        .ranges-section h4 {
            color: var(--primary-color);
            margin-bottom: 1rem;
        }
        
        .ranges-table-container {
            overflow-x: auto;
        }
        
        .ranges-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .ranges-table th, .ranges-table td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid var(--gray-color);
        }
        
        .ranges-table th {
            background-color: var(--light-color);
            font-weight: 600;
        }
        
        .equivalent-rounds h4 {
            color: var(--primary-color);
            margin-bottom: 1rem;
        }
        
        .category-info {
            margin-bottom: 1rem;
            padding: 0.5rem;
            background-color: var(--light-color);
            border-radius: 4px;
        }
        
        .equivalent-list {
            list-style: none;
            padding: 0;
        }
        
        .equivalent-list li {
            padding: 0.75rem;
            border-bottom: 1px solid var(--gray-color);
        }
        
        .equivalent-list li:last-child {
            border-bottom: none;
        }
        
        .equivalent-list a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
        }
        
        .equivalent-list a:hover {
            text-decoration: underline;
        }
        
        .equiv-dates {
            font-size: 0.85rem;
            color: #6b7280;
            margin-left: 0.5rem;
        }
        
        .no-round-selected {
            text-align: center;
            padding: 3rem 1rem;
        }
        
        .no-round-selected i {
            font-size: 3rem;
            color: var(--primary-color);
            margin-bottom: 1rem;
        }
        
        .no-round-selected h3 {
            color: var(--primary-color);
            margin-bottom: 1rem;
            border-bottom: none;
        }
        
        /* Responsive adjustments */
        @media (max-width: 900px) {
            .rounds-grid {
                grid-template-columns: 1fr;
            }
            
            .rounds-list {
                margin-bottom: 2rem;
            }
            
            #rounds-list {
                max-height: 300px;
            }
        }
    </style>
</body>
</html>