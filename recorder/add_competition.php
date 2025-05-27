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

// Process form submission
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $competitionName = isset($_POST['competition_name']) ? sanitizeInput($_POST['competition_name']) : '';
    $startDate = isset($_POST['start_date']) ? sanitizeInput($_POST['start_date']) : '';
    $endDate = isset($_POST['end_date']) ? sanitizeInput($_POST['end_date']) : '';
    $location = isset($_POST['location']) ? sanitizeInput($_POST['location']) : '';
    $isOfficial = isset($_POST['is_official']) ? 1 : 0;
    $isChampionship = isset($_POST['is_championship']) ? 1 : 0;
    $contributesToChampionship = isset($_POST['contributes_to_championship']) ? 1 : 0;
    
    // Validate input
    $errors = [];
    
    if (empty($competitionName)) {
        $errors[] = 'Competition name is required';
    }
    
    if (empty($startDate)) {
        $errors[] = 'Start date is required';
    }
    
    if (empty($endDate)) {
        $errors[] = 'End date is required';
    }
    
    if (empty($location)) {
        $errors[] = 'Location is required';
    }
    
    if ($startDate > $endDate) {
        $errors[] = 'Start date cannot be after end date';
    }
    
    if (empty($errors)) {
        // Add competition to database
        $result = addCompetition($competitionName, $startDate, $endDate, $location, $isOfficial, $isChampionship, $contributesToChampionship);
        
        if ($result) {
            $message = 'Competition added successfully.';
            $messageType = 'success';
            
            // Clear form data
            $competitionName = $startDate = $endDate = $location = '';
            $isOfficial = $isChampionship = $contributesToChampionship = 0;
        } else {
            $message = 'Error adding competition. Please try again.';
            $messageType = 'error';
        }
    } else {
        $message = 'Please fix the following errors: ' . implode(', ', $errors);
        $messageType = 'error';
    }
}

// Set default dates if not set
if (!isset($startDate) || empty($startDate)) {
    $startDate = date('Y-m-d');
}

if (!isset($endDate) || empty($endDate)) {
    $endDate = date('Y-m-d');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Competition - Archery Score Recording System</title>
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
                    <a href="#" class="dropdown-toggle">Scores</a>
                    <div class="dropdown-menu">
                        <a href="pending_scores.php">Approve Pending Scores</a>
                        <a href="enter_score.php">Enter New Score</a>
                        <a href="view_scores.php">View All Scores</a>
                    </div>
                </div>
                <div class="dropdown">
                    <a href="#" class="dropdown-toggle active">Competitions</a>
                    <div class="dropdown-menu">
                        <a href="manage_competitions.php">Manage Competitions</a>
                        <a href="add_competition.php" class="active">Add New Competition</a>
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
            <div class="add-competition-container">
                <div class="page-header">
                    <h2><i class="fas fa-trophy"></i> Add New Competition</h2>
                    <div class="action-buttons">
                        <a href="manage_competitions.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back to Competitions
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
                    <form method="post" action="add_competition.php" class="competition-form">
                        <div class="form-section">
                            <h3>Competition Details</h3>
                            
                            <div class="form-group">
                                <label for="competition-name">Competition Name <span class="required">*</span></label>
                                <input type="text" id="competition-name" name="competition_name" class="form-control" value="<?php echo isset($competitionName) ? htmlspecialchars($competitionName) : ''; ?>" required>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="start-date">Start Date <span class="required">*</span></label>
                                    <input type="date" id="start-date" name="start_date" class="form-control" value="<?php echo $startDate; ?>" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="end-date">End Date <span class="required">*</span></label>
                                    <input type="date" id="end-date" name="end_date" class="form-control" value="<?php echo $endDate; ?>" required>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="location">Location <span class="required">*</span></label>
                                <input type="text" id="location" name="location" class="form-control" value="<?php echo isset($location) ? htmlspecialchars($location) : ''; ?>" required>
                            </div>
                        </div>
                        
                        <div class="form-section">
                            <h3>Competition Status</h3>
                            
                            <div class="form-group checkbox-group">
                                <input type="checkbox" id="is-official" name="is_official" class="form-check-input" <?php echo (isset($isOfficial) && $isOfficial) ? 'checked' : ''; ?>>
                                <label for="is-official">This is an official competition</label>
                            </div>
                            
                            <div class="form-group checkbox-group">
                                <input type="checkbox" id="is-championship" name="is_championship" class="form-check-input" <?php echo (isset($isChampionship) && $isChampionship) ? 'checked' : ''; ?>>
                                <label for="is-championship">This is a championship event</label>
                            </div>
                            
                            <div class="form-group checkbox-group">
                                <input type="checkbox" id="contributes-to-championship" name="contributes_to_championship" class="form-check-input" <?php echo (isset($contributesToChampionship) && $contributesToChampionship) ? 'checked' : ''; ?>>
                                <label for="contributes-to-championship">This competition contributes to the club championship</label>
                            </div>
                        </div>
                        
                        <div class="form-buttons">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Add Competition
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
            // Make end date same as start date by default
            const startDateInput = document.getElementById('start-date');
            const endDateInput = document.getElementById('end-date');
            
            startDateInput.addEventListener('change', function() {
                // Only update end date if it's before start date
                if (endDateInput.value < this.value) {
                    endDateInput.value = this.value;
                }
            });
            
            // Ensure end date is not before start date
            endDateInput.addEventListener('change', function() {
                if (this.value < startDateInput.value) {
                    alert('End date cannot be before start date');
                    this.value = startDateInput.value;
                }
            });
            
            // Championship setting should enable contributes to championship
            const isChampionshipCheckbox = document.getElementById('is-championship');
            const contributesToChampionshipCheckbox = document.getElementById('contributes-to-championship');
            
            isChampionshipCheckbox.addEventListener('change', function() {
                if (this.checked) {
                    contributesToChampionshipCheckbox.checked = true;
                }
            });
        });
    </script>
    
    <style>
        /* Add Competition Page Specific Styles */
        .add-competition-container {
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
        
        .form-section:last-child {
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
            margin-bottom: 1rem;
        }
        
        .checkbox-group input {
            margin-right: 0.5rem;
        }
        
        .checkbox-group label {
            margin-bottom: 0;
        }
        
        .form-buttons {
            display: flex;
            gap: 1rem;
            margin-top: 1.5rem;
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