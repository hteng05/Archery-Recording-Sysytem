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

// Get score ID from URL
$scoreId = isset($_GET['score_id']) ? intval($_GET['score_id']) : 0;

// If no score ID provided, redirect to scores page
if ($scoreId <= 0) {
    header('Location: view_scores.php');
    exit;
}

// Get score details
$conn = getDbConnection();
$score = null;
$endScores = [];

if ($conn) {
    // Get score details
    $stmt = $conn->prepare("SELECT s.*, r.RoundName, e.EquipmentName, c.CompetitionName 
                          FROM ScoreTable s
                          JOIN RoundTable r ON s.RoundID = r.RoundID
                          JOIN EquipmentTable e ON s.EquipmentID = e.EquipmentID
                          LEFT JOIN CompetitionTable c ON s.CompetitionID = c.CompetitionID
                          WHERE s.ScoreID = ? AND s.ArcherID = ? AND s.IsApproved = 1");
    $stmt->bind_param("ii", $scoreId, $archerId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $score = $result->fetch_assoc();
        
        // Get end scores
        $endScores = getEndScores($scoreId);
    } else {
        // Score not found or doesn't belong to this archer
        header('Location: view_scores.php');
        exit;
    }
}

// If score not found, redirect to scores page
if (!$score) {
    header('Location: view_scores.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Score Details - Archery Score Recording System</title>
    <link rel="stylesheet" href="../css/main.css">
    <link rel="stylesheet" href="../css/scores.css">
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
            <div class="score-details-container">
                <div class="back-button">
                    <a href="view_scores.php" class="btn btn-sm btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Scores
                    </a>
                </div>
                
                <h2>Score Details</h2>
                
                <div class="score-header">
                    <div class="score-info">
                        <div class="score-summary">
                            <h3><?php echo htmlspecialchars($score['RoundName']); ?></h3>
                            <div class="score-badges">
                                <?php if ($score['CompetitionID']): ?>
                                    <span class="badge badge-competition"><?php echo htmlspecialchars($score['CompetitionName']); ?></span>
                                <?php else: ?>
                                    <span class="badge badge-practice">Practice</span>
                                <?php endif; ?>
                                
                                <?php if ($score['IsPersonalBest']): ?>
                                    <span class="badge badge-pb">Personal Best</span>
                                <?php endif; ?>
                                
                                <?php if ($score['IsClubBest']): ?>
                                    <span class="badge badge-cb">Club Best</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="score-value">
                            <span class="total-score"><?php echo $score['TotalScore']; ?></span>
                        </div>
                    </div>
                    
                    <div class="score-details">
                        <div class="detail-item">
                            <span class="detail-label">Date:</span>
                            <span class="detail-value"><?php echo formatDate($score['DateShot']); ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Equipment:</span>
                            <span class="detail-value"><?php echo htmlspecialchars($score['EquipmentName']); ?></span>
                        </div>
                    </div>
                </div>
                
                <?php if (!empty($endScores)): ?>
                    <div class="score-breakdown">
                        <h3>Score Breakdown</h3>
                        
                        <div class="ranges-accordion">
                            <?php 
                                $currentRangeId = -1;
                                $currentRangeEnds = [];
                                $ranges = [];
                                
                                // Group ends by range
                                foreach ($endScores as $end) {
                                    if ($end['RangeID'] != $currentRangeId) {
                                        if ($currentRangeId != -1) {
                                            $ranges[] = [
                                                'RangeID' => $currentRangeId,
                                                'Distance' => $currentRangeDistance,
                                                'TargetFaceSize' => $currentRangeTargetSize,
                                                'Ends' => $currentRangeEnds
                                            ];
                                        }
                                        $currentRangeId = $end['RangeID'];
                                        $currentRangeDistance = $end['Distance'];
                                        $currentRangeTargetSize = $end['TargetFaceSize'];
                                        $currentRangeEnds = [];
                                    }
                                    $currentRangeEnds[] = $end;
                                }
                                
                                // Add the last range
                                if ($currentRangeId != -1) {
                                    $ranges[] = [
                                        'RangeID' => $currentRangeId,
                                        'Distance' => $currentRangeDistance,
                                        'TargetFaceSize' => $currentRangeTargetSize,
                                        'Ends' => $currentRangeEnds
                                    ];
                                }
                                
                                foreach ($ranges as $rangeIndex => $range):
                            ?>
                                <div class="range-section">
                                    <div class="range-header" data-range="<?php echo $rangeIndex; ?>">
                                        <div class="range-name">
                                            <h4><?php echo $range['Distance']; ?>m - <?php echo $range['TargetFaceSize']; ?>cm Face</h4>
                                            <span class="range-ends"><?php echo count($range['Ends']); ?> ends</span>
                                        </div>
                                        <i class="fas fa-chevron-down"></i>
                                    </div>
                                    <div class="range-content" id="range-content-<?php echo $rangeIndex; ?>">
                                        <div class="ends-table-container">
                                            <table class="ends-table">
                                                <thead>
                                                    <tr>
                                                        <th>End</th>
                                                        <th>Arrows</th>
                                                        <th>End Total</th>
                                                        <th>Running Total</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php 
                                                        $runningTotal = 0;
                                                        foreach ($range['Ends'] as $end):
                                                            $endTotal = 0;
                                                            $arrowScores = [];
                                                            
                                                            // Get arrow scores
                                                            foreach ($end['arrows'] as $arrow) {
                                                                $arrowValue = $arrow['Score'];
                                                                $arrowScores[] = $arrowValue;
                                                                
                                                                // Calculate end total
                                                                $endTotal += $arrowValue;
                                                            }
                                                            
                                                            $runningTotal += $endTotal;
                                                    ?>
                                                        <tr>
                                                            <td class="end-number"><?php echo $end['EndNumber']; ?></td>
                                                            <td class="arrows-cell">
                                                                <?php foreach ($arrowScores as $arrowScore): ?>
                                                                    <span class="arrow-score"><?php echo $arrowScore; ?></span>
                                                                <?php endforeach; ?>
                                                            </td>
                                                            <td class="end-total"><?php echo $endTotal; ?></td>
                                                            <td class="running-total"><?php echo $runningTotal; ?></td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="no-details">
                        <i class="fas fa-info-circle"></i>
                        <p>Detailed arrow scores are not available for this score.</p>
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
            // Range accordion functionality
            const rangeHeaders = document.querySelectorAll('.range-header');
            
            rangeHeaders.forEach(header => {
                header.addEventListener('click', function() {
                    const rangeIndex = this.getAttribute('data-range');
                    const content = document.getElementById('range-content-' + rangeIndex);
                    const icon = this.querySelector('i');
                    
                    // Toggle active class
                    this.classList.toggle('active');
                    
                    // Toggle icon
                    if (icon.classList.contains('fa-chevron-down')) {
                        icon.classList.remove('fa-chevron-down');
                        icon.classList.add('fa-chevron-up');
                    } else {
                        icon.classList.remove('fa-chevron-up');
                        icon.classList.add('fa-chevron-down');
                    }
                    
                    // Toggle content visibility
                    if (content.style.maxHeight) {
                        content.style.maxHeight = null;
                    } else {
                        content.style.maxHeight = content.scrollHeight + 'px';
                    }
                });
            });
            
            // Open the first range by default
            if (rangeHeaders.length > 0) {
                rangeHeaders[0].click();
            }
        });
    </script>
    
    <style>
        /* Score Details Page Specific Styles */
        .score-details-container {
            padding: 1rem;
        }
        
        .back-button {
            margin-bottom: 1rem;
        }
        
        .score-details-container h2 {
            color: var(--primary-color);
            margin-bottom: 1.5rem;
        }
        
        .score-header {
            background-color: white;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        .score-info {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1.5rem;
        }
        
        .score-summary h3 {
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }
        
        .score-badges {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
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
        
        .score-value {
            text-align: center;
        }
        
        .total-score {
            font-size: 3rem;
            font-weight: 700;
            color: var(--primary-color);
        }
        
        .score-details {
            display: flex;
            flex-wrap: wrap;
            gap: 2rem;
            padding-top: 1rem;
            border-top: 1px solid var(--gray-color);
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
        
        .score-breakdown {
            margin-bottom: 2rem;
        }
        
        .score-breakdown h3 {
            color: var(--primary-color);
            margin-bottom: 1rem;
        }
        
        .ranges-accordion {
            margin-bottom: 1rem;
        }
        
        .range-section {
            margin-bottom: 1rem;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        .range-header {
            background-color: white;
            padding: 1rem 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .range-header:hover {
            background-color: #f9fafb;
        }
        
        .range-header.active {
            background-color: var(--primary-color);
            color: white;
        }
        
        .range-name {
            display: flex;
            flex-direction: column;
        }
        
        .range-name h4 {
            margin: 0;
            font-size: 1.1rem;
        }
        
        .range-ends {
            font-size: 0.9rem;
            opacity: 0.8;
        }
        
        .range-content {
            background-color: white;
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease;
        }
        
        .ends-table-container {
            padding: 1.5rem;
        }
        
        .ends-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .ends-table th, .ends-table td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid var(--gray-color);
        }
        
        .ends-table th {
            font-weight: 600;
            background-color: var(--light-color);
        }
        
        .end-number {
            font-weight: 600;
            text-align: center;
            width: 50px;
        }
        
        .arrows-cell {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
        }
        
        .arrow-score {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background-color: var(--light-color);
            font-weight: 600;
        }
        
        .end-total, .running-total {
            font-weight: 600;
            text-align: center;
        }
        
        .no-details {
            background-color: white;
            border-radius: 8px;
            padding: 2rem 1.5rem;
            text-align: center;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        .no-details i {
            font-size: 2rem;
            color: #d1d5db;
            margin-bottom: 1rem;
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .score-info {
                flex-direction: column;
            }
            
            .score-value {
                margin-top: 1rem;
                text-align: left;
            }
            
            .score-details {
                flex-direction: column;
                gap: 1rem;
            }
        }
    </style>
</body>
</html>