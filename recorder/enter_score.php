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

// Get archers, rounds, equipment, and competitions for dropdowns
$archers = getAllArchers();
$rounds = getAllRounds();
$equipment = getAllEquipment();
$competitions = getAllCompetitions(false);

// Process form submission
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $archerId = isset($_POST['archer_id']) ? intval($_POST['archer_id']) : 0;
    $roundId = isset($_POST['round_id']) ? intval($_POST['round_id']) : 0;
    $equipmentId = isset($_POST['equipment_id']) ? intval($_POST['equipment_id']) : 0;
    $date = isset($_POST['date']) ? sanitizeInput($_POST['date']) : date('Y-m-d');
    $time = isset($_POST['time']) ? sanitizeInput($_POST['time']) : date('H:i:s');
    $isPractice = isset($_POST['is_practice']) ? 1 : 0;
    $isCompetition = $isPractice ? 0 : 1;
    $competitionId = ($isCompetition && isset($_POST['competition_id'])) ? intval($_POST['competition_id']) : null;
    $totalScore = isset($_POST['total_score']) ? intval($_POST['total_score']) : 0;
    
    // Validate input
    $errors = [];
    
    if ($archerId <= 0) {
        $errors[] = 'Archer is required';
    }
    
    if ($roundId <= 0) {
        $errors[] = 'Round is required';
    }
    
    if ($equipmentId <= 0) {
        $errors[] = 'Equipment is required';
    }
    
    if (empty($date)) {
        $errors[] = 'Date is required';
    }
    
    if (empty($time)) {
        $errors[] = 'Time is required';
    }
    
    if ($isCompetition && empty($competitionId)) {
        $errors[] = 'Competition is required for competition scores';
    }
    
    if ($totalScore < 0) {
        $errors[] = 'Total score must be a positive number';
    }
    
    if (empty($errors)) {
        // Create a staged score entry
        $stageId = addStagedScore($archerId, $roundId, $equipmentId, $date, $time, $isPractice, $isCompetition, $competitionId);
        
        if ($stageId) {
            // Approve the score immediately
            $result = approveScore($stageId, $totalScore);
            
            if ($result) {
                $message = 'Score has been entered and approved successfully.';
                $messageType = 'success';
                
                // Clear form data
                $archerId = $roundId = $equipmentId = $competitionId = 0;
                $date = date('Y-m-d');
                $time = date('H:i:s');
                $isPractice = 1;
                $totalScore = 0;
            } else {
                $message = 'Error approving score. Please try again.';
                $messageType = 'error';
            }
        } else {
            $message = 'Error adding score to staging. Please try again.';
            $messageType = 'error';
        }
    } else {
        $message = 'Please fix the following errors: ' . implode(', ', $errors);
        $messageType = 'error';
    }
}

// Get selected archer and round details for auto-populating equipment
$selectedArcher = null;
$selectedRound = null;

if (isset($_POST['archer_id']) && $_POST['archer_id'] > 0) {
    $selectedArcher = getArcherById($_POST['archer_id']);
}

if (isset($_POST['round_id']) && $_POST['round_id'] > 0) {
    $selectedRound = getRoundById($_POST['round_id']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enter Score - Archery Score Recording System</title>
    <link rel="stylesheet" href="../css/main.css">
    <link rel="stylesheet" href="../css/scores.css">
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
                        <a href="enter_score.php" class="active">Enter New Score</a>
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
            <div class="enter-score-container">
                <div class="page-header">
                    <h2><i class="fas fa-pencil-alt"></i> Enter New Score</h2>
                    <div class="action-buttons">
                        <a href="enter_detailed_score.php" class="btn btn-secondary">
                            <i class="fas fa-list-ol"></i> Enter Detailed Score
                        </a>
                    </div>
                </div>
                
                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $messageType; ?>">
                        <?php if ($messageType === 'success'): ?>
                            <i class="fas fa-check-circle"></i>
                        <?php else: ?>
                            <i class="fas fa-exclamation-circle"></i>
                        <?php endif; ?>
                        <?php echo $message; ?>
                    </div>
                <?php endif; ?>
                
                <div class="form-container">
                    <form method="post" action="enter_score.php" class="score-form">
                        <div class="form-section">
                            <h3>Score Information</h3>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="archer">Archer <span class="required">*</span></label>
                                    <select id="archer" name="archer_id" class="form-control" required>
                                        <option value="">-- Select Archer --</option>
                                        <?php foreach ($archers as $archer): ?>
                                            <?php if ($archer['IsActive']): ?>
                                                <option value="<?php echo $archer['ArcherID']; ?>" <?php echo (isset($archerId) && $archerId === $archer['ArcherID']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($archer['FirstName'] . ' ' . $archer['LastName']); ?>
                                                </option>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label for="equipment">Equipment <span class="required">*</span></label>
                                    <select id="equipment" name="equipment_id" class="form-control" required>
                                        <option value="">-- Select Equipment --</option>
                                        <?php foreach ($equipment as $eq): ?>
                                            <option value="<?php echo $eq['EquipmentID']; ?>" <?php echo (isset($equipmentId) && $equipmentId === $eq['EquipmentID']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($eq['EquipmentName']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="round">Round <span class="required">*</span></label>
                                    <select id="round" name="round_id" class="form-control" required>
                                        <option value="">-- Select Round --</option>
                                        <?php foreach ($rounds as $round): ?>
                                            <option value="<?php echo $round['RoundID']; ?>" <?php echo (isset($roundId) && $roundId === $round['RoundID']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($round['RoundName']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label for="total-score">Total Score <span class="required">*</span></label>
                                    <input type="number" id="total-score" name="total_score" class="form-control" value="<?php echo isset($totalScore) ? $totalScore : ''; ?>" required min="0">
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="date">Date <span class="required">*</span></label>
                                    <input type="date" id="date" name="date" class="form-control" value="<?php echo isset($date) ? $date : date('Y-m-d'); ?>" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="time">Time <span class="required">*</span></label>
                                    <input type="time" id="time" name="time" class="form-control" value="<?php echo isset($time) ? date('H:i', strtotime($time)) : date('H:i'); ?>" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-section">
                            <div class="form-group">
                                <div class="checkbox-group">
                                    <input type="checkbox" id="is-practice" name="is_practice" <?php echo (isset($isPractice) && $isPractice) ? 'checked' : ''; ?>>
                                    <label for="is-practice">This is a practice score (not part of a competition)</label>
                                </div>
                            </div>
                            
                            <div id="competition-section" class="form-group <?php echo (isset($isPractice) && $isPractice) ? 'hidden' : ''; ?>">
                                <label for="competition">Competition <span class="required">*</span></label>
                                <select id="competition" name="competition_id" class="form-control" <?php echo (isset($isPractice) && !$isPractice) ? 'required' : ''; ?>>
                                    <option value="">-- Select Competition --</option>
                                    <?php foreach ($competitions as $competition): ?>
                                        <option value="<?php echo $competition['CompetitionID']; ?>" <?php echo (isset($competitionId) && $competitionId === $competition['CompetitionID']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($competition['CompetitionName'] . ' (' . formatDate($competition['StartDate']) . ')'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <?php if ($selectedRound): ?>
                            <div class="round-details">
                                <h4>Round Details:</h4>
                                <p><strong>Total Arrows:</strong> <?php echo $selectedRound['TotalArrows']; ?></p>
                                <?php if (!empty($selectedRound['ranges'])): ?>
                                    <ul class="ranges-list">
                                        <?php foreach ($selectedRound['ranges'] as $range): ?>
                                            <li>
                                                <?php echo $range['Distance']; ?>m, 
                                                <?php echo $range['NumberOfEnds']; ?> ends, 
                                                <?php echo $range['ArrowsPerEnd']; ?> arrows per end, 
                                                <?php echo $range['TargetFaceSize']; ?>cm face
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="form-buttons">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Save Score
                            </button>
                            <button type="reset" class="btn btn-secondary">
                                <i class="fas fa-undo"></i> Reset Form
                            </button>
                        </div>
                    </form>
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
            // Toggle competition section based on practice checkbox
            const isPracticeCheckbox = document.getElementById('is-practice');
            const competitionSection = document.getElementById('competition-section');
            const competitionSelect = document.getElementById('competition');
            
            isPracticeCheckbox.addEventListener('change', function() {
                competitionSection.classList.toggle('hidden', this.checked);
                competitionSelect.required = !this.checked;
            });
            
            // Auto-select default equipment for archer
            const archerSelect = document.getElementById('archer');
            const equipmentSelect = document.getElementById('equipment');
            
            archerSelect.addEventListener('change', function() {
                const archerId = this.value;
                
                if (archerId) {
                    // In a real implementation, this would use AJAX to fetch archer's default equipment
                    // For now, we'll just use the data available on the page
                    <?php if (!empty($archers)): ?>
                    const archers = <?php echo json_encode($archers); ?>;
                    const selectedArcher = archers.find(archer => archer.ArcherID == archerId);
                    
                    if (selectedArcher && selectedArcher.DefaultEquipmentID) {
                        equipmentSelect.value = selectedArcher.DefaultEquipmentID;
                    }
                    <?php endif; ?>
                }
            });
            
            // Round details setup
            const roundSelect = document.getElementById('round');
            
            roundSelect.addEventListener('change', function() {
                // In a real implementation, this would use AJAX to fetch round details
                // For now, we'll just submit the form to reload the page with selected round
                document.querySelector('.score-form').submit();
            });
        });
    </script>
    
    <style>
        /* Enter Score Page Specific Styles */
        .enter-score-container {
            padding: 1rem;
        }
        
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        
        .page-header h2 {
            color: var(--primary-color);
            margin: 0;
        }
        
        .page-header h2 i {
            margin-right: 0.5rem;
        }
        
        .form-container {
            background-color: white;
            border-radius: 8px;
            padding: 1.5rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        .form-section {
            margin-bottom: 2rem;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid var(--gray-color);
        }
        
        .form-section:last-of-type {
            margin-bottom: 1rem;
            padding-bottom: 0;
            border-bottom: none;
        }
        
        .form-section h3 {
            color: var(--primary-color);
            margin-bottom: 1rem;
            font-size: 1.2rem;
        }
        
        .form-row {
            display: flex;
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .form-row .form-group {
            flex: 1;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }
        
        .required {
            color: var(--error-color);
        }
        
        .checkbox-group {
            display: flex;
            align-items: center;
        }
        
        .checkbox-group input {
            margin-right: 0.5rem;
        }
        
        .checkbox-group label {
            margin-bottom: 0;
        }
        
        .hidden {
            display: none;
        }
        
        .round-details {
            background-color: var(--light-color);
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .round-details h4 {
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }
        
        .ranges-list {
            margin-top: 0.5rem;
            padding-left: 1.5rem;
        }
        
        .ranges-list li {
            margin-bottom: 0.25rem;
        }
        
        .form-buttons {
            display: flex;
            gap: 1rem;
        }
        
        .alert {
            padding: 0.75rem 1rem;
            margin-bottom: 1rem;
            border-radius: 4px;
        }
        
        .alert-success {
            background-color: #dcfce7;
            color: #16a34a;
            border-left: 4px solid #16a34a;
        }
        
        .alert-error {
            background-color: #fee2e2;
            color: #dc2626;
            border-left: 4px solid #dc2626;
        }
        
        .alert i {
            margin-right: 0.5rem;
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .page-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }
            
            .form-row {
                flex-direction: column;
                gap: 0;
            }
            
            .form-buttons {
                flex-direction: column;
            }
            
            .form-buttons button {
                width: 100%;
            }
        }
    </style>
</body>
</html>