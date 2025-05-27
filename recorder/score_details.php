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

// Get score ID from URL
$scoreId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($scoreId === 0) {
    header('Location: view_scores.php');
    exit;
}

// Get score details
$conn = getDbConnection();
if (!$conn) {
    header('Location: view_scores.php');
    exit;
}

// Get main score information
$stmt = $conn->prepare("SELECT s.*, a.FirstName, a.LastName, a.Gender, a.DOB,
                      r.RoundName, r.TotalArrows, e.EquipmentName, 
                      c.CompetitionName, c.StartDate as CompStartDate,
                      cl.ClassName, d.DivisionName
                      FROM ScoreTable s
                      JOIN ArcherTable a ON s.ArcherID = a.ArcherID
                      JOIN RoundTable r ON s.RoundID = r.RoundID
                      JOIN EquipmentTable e ON s.EquipmentID = e.EquipmentID
                      LEFT JOIN CompetitionTable c ON s.CompetitionID = c.CompetitionID
                      JOIN ClassTable cl ON a.ClassID = cl.ClassID
                      JOIN DivisionTable d ON a.DefaultDivisionID = d.DivisionID
                      WHERE s.ScoreID = ?");
$stmt->bind_param("i", $scoreId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    header('Location: view_scores.php');
    exit;
}

$score = $result->fetch_assoc();

// Get round details with ranges
$round = getRoundById($score['RoundID']);
if (!$round) {
    header('Location: view_scores.php');
    exit;
}

// Get end and arrow details
$endScores = getEndScores($scoreId);

// Calculate archer's age at time of shooting
function calculateAgeAtDate($birthDate, $shotDate) {
    $birth = new DateTime($birthDate);
    $shot = new DateTime($shotDate);
    $age = $birth->diff($shot);
    return $age->y;
}

$archerAgeAtShot = calculateAgeAtDate($score['DOB'], $score['DateShot']);

// Calculate statistics
$totalArrowsShot = 0;
$scoreBreakdown = [];
$endTotals = [];
$rangeScores = [];

foreach ($endScores as $end) {
    $endTotal = 0;
    $arrowCount = count($end['arrows']);
    $totalArrowsShot += $arrowCount;
    
    // Calculate end total
    foreach ($end['arrows'] as $arrow) {
        $endTotal += $arrow['Score'];
    }
    
    $endTotals[] = $endTotal;
    
    // Group by range for range totals
    $rangeKey = $end['Distance'] . 'm';
    if (!isset($rangeScores[$rangeKey])) {
        $rangeScores[$rangeKey] = [
            'distance' => $end['Distance'],
            'face_size' => $end['TargetFaceSize'],
            'total_score' => 0,
            'total_arrows' => 0,
            'ends' => 0
        ];
    }
    
    $rangeScores[$rangeKey]['total_score'] += $endTotal;
    $rangeScores[$rangeKey]['total_arrows'] += $arrowCount;
    $rangeScores[$rangeKey]['ends']++;
    
    // Score breakdown for analysis
    foreach ($end['arrows'] as $arrow) {
        $scoreValue = $arrow['Score'];
        if (!isset($scoreBreakdown[$scoreValue])) {
            $scoreBreakdown[$scoreValue] = 0;
        }
        $scoreBreakdown[$scoreValue]++;
    }
}

// Calculate averages
$averagePerArrow = $totalArrowsShot > 0 ? round($score['TotalScore'] / $totalArrowsShot, 2) : 0;
$averagePerEnd = count($endTotals) > 0 ? round(array_sum($endTotals) / count($endTotals), 2) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Score Details - Archery Score Recording System</title>
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
                        <a href="view_scores.php">View All Scores</a>
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
            <div class="score-details-container">
                <div class="back-button">
                    <a href="javascript:history.back()" class="btn btn-sm btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back
                    </a>
                </div>
                
                <h2>Score Details</h2>
                
                <div class="score-header">
                    <div class="score-info">
                        <div class="score-summary">
                            <h3><?php echo htmlspecialchars($score['RoundName']); ?></h3>
                            <div class="archer-info">
                                <span class="archer-name"><?php echo htmlspecialchars($score['FirstName'] . ' ' . $score['LastName']); ?></span>
                                <span class="archer-details"><?php echo htmlspecialchars($score['ClassName'] . ' ' . $score['DivisionName']); ?></span>
                            </div>
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
                            <span class="score-label">Total Score</span>
                        </div>
                    </div>
                    
                    <div class="score-details">
                        <div class="detail-item">
                            <span class="detail-label">Date Shot:</span>
                            <span class="detail-value"><?php echo formatDate($score['DateShot']); ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Equipment:</span>
                            <span class="detail-value"><?php echo htmlspecialchars($score['EquipmentName']); ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Age at shot:</span>
                            <span class="detail-value"><?php echo $archerAgeAtShot; ?> years</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Average per arrow:</span>
                            <span class="detail-value"><?php echo $averagePerArrow; ?></span>
                        </div>
                        <?php if (count($endTotals) > 0): ?>
                        <div class="detail-item">
                            <span class="detail-label">Average per end:</span>
                            <span class="detail-value"><?php echo $averagePerEnd; ?></span>
                        </div>
                        <?php endif; ?>
                        <div class="detail-item">
                            <span class="detail-label">Status:</span>
                            <span class="detail-value">
                                <?php if ($score['IsApproved']): ?>
                                    <span class="status-approved">Approved</span>
                                <?php else: ?>
                                    <span class="status-pending">Pending</span>
                                <?php endif; ?>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Round Definition -->
                <div class="round-definition">
                    <h3>Round Definition</h3>
                    <div class="round-info">
                        <p><strong><?php echo htmlspecialchars($round['RoundName']); ?></strong> - <?php echo $round['TotalArrows']; ?> arrows total</p>
                        <?php if ($round['IsOfficial']): ?>
                            <span class="official-badge">Official Round</span>
                        <?php endif; ?>
                    </div>
                    <div class="ranges-table-container">
                        <table class="ranges-table">
                            <thead>
                                <tr>
                                    <th>Range</th>
                                    <th>Distance</th>
                                    <th>Face Size</th>
                                    <th>Ends</th>
                                    <th>Arrows per End</th>
                                    <th>Total Arrows</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($round['ranges'] as $index => $range): ?>
                                <tr>
                                    <td><?php echo $index + 1; ?></td>
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

                <!-- Range Scores Summary -->
                <?php if (!empty($rangeScores)): ?>
                <div class="range-summary">
                    <h3>Range Breakdown</h3>
                    <div class="range-cards">
                        <?php foreach ($rangeScores as $range): ?>
                        <div class="range-card">
                            <div class="range-distance"><?php echo $range['distance']; ?>m</div>
                            <div class="range-score"><?php echo $range['total_score']; ?></div>
                            <div class="range-details">
                                <div><?php echo $range['face_size']; ?>cm face</div>
                                <div><?php echo $range['total_arrows']; ?> arrows</div>
                                <div>Avg: <?php echo round($range['total_score'] / $range['total_arrows'], 2); ?></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                
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
                                                            
                                                            // Get arrow scores and sort them highest to lowest
                                                            foreach ($end['arrows'] as $arrow) {
                                                                $arrowValue = $arrow['Score'];
                                                                $arrowScores[] = $arrowValue;
                                                                $endTotal += $arrowValue;
                                                            }
                                                            
                                                            // Sort arrows highest to lowest as per archery convention
                                                            rsort($arrowScores);
                                                            $runningTotal += $endTotal;
                                                    ?>
                                                        <tr>
                                                            <td class="end-number"><?php echo $end['EndNumber']; ?></td>
                                                            <td class="arrows-cell">
                                                                <?php foreach ($arrowScores as $arrowScore): ?>
                                                                    <span class="arrow-score score-<?php echo $arrowScore; ?>">
                                                                        <?php echo $arrowScore == 10 ? 'X' : ($arrowScore == 0 ? 'M' : $arrowScore); ?>
                                                                    </span>
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
                        <p>This may be an older score that was entered as a total only.</p>
                    </div>
                <?php endif; ?>

                <!-- Actions -->
                <div class="score-actions">
                    <a href="view_archer_scores.php?id=<?php echo $score['ArcherID']; ?>" class="btn btn-primary">
                        <i class="fas fa-user"></i> View Archer's Scores
                    </a>
                    <a href="edit_archer.php?id=<?php echo $score['ArcherID']; ?>" class="btn btn-secondary">
                        <i class="fas fa-user-edit"></i> Edit Archer
                    </a>
                    <button onclick="window.print()" class="btn btn-info">
                        <i class="fas fa-print"></i> Print Score Card
                    </button>
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
            max-width: 1200px;
            margin: 0 auto;
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
        
        .archer-info {
            margin-bottom: 0.75rem;
        }
        
        .archer-name {
            display: block;
            font-size: 1.1rem;
            font-weight: 600;
            color: #374151;
            margin-bottom: 0.25rem;
        }
        
        .archer-details {
            display: block;
            font-size: 0.9rem;
            color: #6b7280;
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
            min-width: 120px;
        }
        
        .total-score {
            display: block;
            font-size: 3rem;
            font-weight: 700;
            color: var(--primary-color);
            line-height: 1;
        }
        
        .score-label {
            display: block;
            font-size: 0.9rem;
            color: #6b7280;
            margin-top: 0.25rem;
        }
        
        .score-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
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
            margin-bottom: 0.25rem;
        }
        
        .detail-value {
            font-size: 1rem;
            font-weight: 500;
            color: #374151;
        }
        
        .status-approved {
            color: #16a34a;
            font-weight: 600;
        }
        
        .status-pending {
            color: #d97706;
            font-weight: 600;
        }
        
        /* Round Definition */
        .round-definition {
            background-color: white;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        .round-definition h3 {
            color: var(--primary-color);
            margin-bottom: 1rem;
        }
        
        .round-info {
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .official-badge {
            background-color: #10b981;
            color: white;
            padding: 0.25rem 0.5rem;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .ranges-table-container {
            overflow-x: auto;
        }
        
        .ranges-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.9rem;
        }
        
        .ranges-table th, .ranges-table td {
            padding: 0.5rem;
            text-align: left;
            border-bottom: 1px solid var(--gray-color);
        }
        
        .ranges-table th {
            background-color: var(--light-color);
            font-weight: 600;
        }
        
        /* Range Summary */
        .range-summary {
            background-color: white;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        .range-summary h3 {
            color: var(--primary-color);
            margin-bottom: 1rem;
        }
        
        .range-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
        }
        
        .range-card {
            background-color: var(--light-color);
            border-radius: 6px;
            padding: 1rem;
            text-align: center;
        }
        
        .range-distance {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }
        
        .range-score {
            font-size: 1.5rem;
            font-weight: bold;
            color: #374151;
            margin-bottom: 0.5rem;
        }
        
        .range-details {
            font-size: 0.8rem;
            color: #6b7280;
        }
        
        .range-details div {
            margin-bottom: 0.2rem;
        }
        
        .score-breakdown {
            background-color: white;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
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
            font-weight: 600;
            font-size: 0.8rem;
            color: white;
        }
        
        /* Arrow score colors - traditional archery scoring colors */
        .score-10, .score-9 { background-color: #10b981; } /* Green for 9-10 */
        .score-8, .score-7 { background-color: #f59e0b; } /* Yellow for 7-8 */
        .score-6, .score-5 { background-color: #f97316; } /* Orange for 5-6 */
        .score-4, .score-3, .score-2, .score-1 { background-color: #ef4444; } /* Red for 1-4 */
        .score-0 { background-color: #6b7280; } /* Gray for miss */
        
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
            margin-bottom: 2rem;
        }
        
        .no-details i {
            font-size: 2rem;
            color: #d1d5db;
            margin-bottom: 1rem;
        }
        
        .no-details p {
            color: #6b7280;
            margin-bottom: 0.5rem;
        }
        
        /* Score Actions */
        .score-actions {
            background-color: white;
            border-radius: 8px;
            padding: 1.5rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .btn {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 4px;
            text-decoration: none;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            color: white;
        }
        
        .btn-primary:hover {
            background-color: #1e40af;
        }
        
        .btn-secondary {
            background-color: #6b7280;
            color: white;
        }
        
        .btn-secondary:hover {
            background-color: #4b5563;
        }
        
        .btn-info {
            background-color: #0ea5e9;
            color: white;
        }
        
        .btn-info:hover {
            background-color: #0284c7;
        }
        
        .btn-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
        }
        
        /* Print Styles */
        @media print {
            .navbar, .back-button, .score-actions, footer {
                display: none !important;
            }
            
            .container {
                max-width: none;
                margin: 0;
                padding: 0;
            }
            
            .score-details-container {
                padding: 1rem;
            }
            
            .range-content {
                max-height: none !important;
                overflow: visible !important;
            }
            
            .range-header i {
                display: none;
            }
            
            .range-header {
                background-color: #f9fafb !important;
                color: black !important;
                cursor: default;
            }
            
            .range-section, .score-header, .round-definition, .range-summary {
                page-break-inside: avoid;
                margin-bottom: 1rem;
            }
            
            .score-header {
                border: 2px solid #e5e7eb;
            }
            
            .total-score {
                font-size: 2rem !important;
            }
        }
        
        /* Responsive adjustments */
        @media (max-width: 992px) {
            .score-info {
                flex-direction: column;
                gap: 1rem;
            }
            
            .score-value {
                text-align: left;
                min-width: auto;
            }
            
            .score-details {
                grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            }
            
            .range-cards {
                grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            }
            
            .score-actions {
                flex-direction: column;
                align-items: stretch;
            }
        }
        
        @media (max-width: 768px) {
            .score-details {
                grid-template-columns: 1fr;
            }
            
            .arrows-cell {
                gap: 0.25rem;
            }
            
            .arrow-score {
                width: 25px;
                height: 25px;
                font-size: 0.7rem;
            }
            
            .ranges-table-container, .ends-table-container {
                font-size: 0.8rem;
            }
            
            .total-score {
                font-size: 2.5rem;
            }
        }
        
        @media (max-width: 480px) {
            .score-header {
                padding: 1rem;
            }
            
            .round-definition, .range-summary, .score-breakdown {
                padding: 1rem;
            }
            
            .range-cards {
                grid-template-columns: 1fr;
            }
            
            .arrows-cell {
                justify-content: center;
            }
        }
    </style>
</body>
</html>