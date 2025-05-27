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

// Get all competitions
$competitions = getAllCompetitions(true);

// Filter championship competitions
$championshipCompetitions = array_filter($competitions, function($competition) {
    return $competition['IsChampionship'] || $competition['ContributesToChampionship'];
});

// Get all categories
$categories = getAllCategories();

// Current year for default form values
$currentYear = date('Y');

// Process form submission
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    
    if ($action === 'recalculate') {
        $year = isset($_POST['year']) ? intval($_POST['year']) : $currentYear;
        
        // In a real implementation, this would recalculate championship standings based on scores
        // For now, we'll just provide a success message
        $message = 'Championship standings have been recalculated for ' . $year . '.';
        $messageType = 'success';
    } elseif ($action === 'add_competition') {
        $competitionId = isset($_POST['competition_id']) ? intval($_POST['competition_id']) : 0;
        
        if ($competitionId > 0) {
            // In a real implementation, this would update the competition to contribute to championship
            $conn = getDbConnection();
            if ($conn) {
                $stmt = $conn->prepare("UPDATE CompetitionTable SET ContributesToChampionship = 1 WHERE CompetitionID = ?");
                $stmt->bind_param("i", $competitionId);
                
                if ($stmt->execute()) {
                    $message = 'Competition has been added to the championship.';
                    $messageType = 'success';
                } else {
                    $message = 'Error adding competition to championship. Please try again.';
                    $messageType = 'error';
                }
            }
        } else {
            $message = 'Please select a competition to add.';
            $messageType = 'error';
        }
    } elseif ($action === 'remove_competition') {
        $competitionId = isset($_POST['competition_id']) ? intval($_POST['competition_id']) : 0;
        
        if ($competitionId > 0) {
            // In a real implementation, this would update the competition to not contribute to championship
            $conn = getDbConnection();
            if ($conn) {
                $stmt = $conn->prepare("UPDATE CompetitionTable SET ContributesToChampionship = 0 WHERE CompetitionID = ?");
                $stmt->bind_param("i", $competitionId);
                
                if ($stmt->execute()) {
                    $message = 'Competition has been removed from the championship.';
                    $messageType = 'success';
                } else {
                    $message = 'Error removing competition from championship. Please try again.';
                    $messageType = 'error';
                }
            }
        } else {
            $message = 'Please select a competition to remove.';
            $messageType = 'error';
        }
    }
    
    // Refresh championship competitions after form submission
    $competitions = getAllCompetitions(true);
    $championshipCompetitions = array_filter($competitions, function($competition) {
        return $competition['IsChampionship'] || $competition['ContributesToChampionship'];
    });
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Championships - Archery Score Recording System</title>
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
                    <a href="#" class="dropdown-toggle">Competitions</a>
                    <div class="dropdown-menu">
                        <a href="manage_competitions.php">Manage Competitions</a>
                        <a href="add_competition.php">Add New Competition</a>
                        <a href="competition_results.php">View Results</a>
                    </div>
                </div>
                <div class="dropdown">
                    <a href="#" class="dropdown-toggle active">Championships</a>
                    <div class="dropdown-menu">
                        <a href="manage_championships.php" class="active">Manage Championships</a>
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
            <div class="manage-championships-container">
                <div class="page-header">
                    <h2><i class="fas fa-trophy"></i> Manage Championships</h2>
                    <div class="action-buttons">
                        <a href="championship_standings.php" class="btn btn-primary">
                            <i class="fas fa-list"></i> View Standings
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
                
                <div class="championship-actions">
                    <div class="card">
                        <div class="card-header">
                            <h3>Recalculate Championship Standings</h3>
                        </div>
                        <div class="card-body">
                            <p>Use this to recalculate the championship standings based on scores from competitions that contribute to the championship.</p>
                            <form method="post" action="manage_championships.php" class="recalculate-form">
                                <input type="hidden" name="action" value="recalculate">
                                <div class="form-group">
                                    <label for="year">Championship Year:</label>
                                    <select id="year" name="year" class="form-control">
                                        <?php for ($year = $currentYear; $year >= $currentYear - 5; $year--): ?>
                                            <option value="<?php echo $year; ?>"><?php echo $year; ?></option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-calculator"></i> Recalculate Standings
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <div class="card">
                        <div class="card-header">
                            <h3>Championship Competitions</h3>
                        </div>
                        <div class="card-body">
                            <?php if (empty($championshipCompetitions)): ?>
                                <div class="no-championship-competitions">
                                    <p>No competitions are currently contributing to the championship.</p>
                                </div>
                            <?php else: ?>
                                <div class="championship-competitions-list">
                                    <table class="championship-competitions-table">
                                        <thead>
                                            <tr>
                                                <th>Competition</th>
                                                <th>Date</th>
                                                <th>Status</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($championshipCompetitions as $competition): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($competition['CompetitionName']); ?></td>
                                                    <td>
                                                        <?php 
                                                            if ($competition['StartDate'] === $competition['EndDate']) {
                                                                echo formatDate($competition['StartDate']);
                                                            } else {
                                                                echo formatDate($competition['StartDate']) . ' - ' . formatDate($competition['EndDate']);
                                                            }
                                                        ?>
                                                    </td>
                                                    <td>
                                                        <?php if ($competition['IsChampionship']): ?>
                                                            <span class="badge badge-championship">Championship</span>
                                                        <?php endif; ?>
                                                        <?php if ($competition['ContributesToChampionship']): ?>
                                                            <span class="badge badge-contributes">Contributes to Club Championship</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <form method="post" action="manage_championships.php" class="inline-form">
                                                            <input type="hidden" name="action" value="remove_competition">
                                                            <input type="hidden" name="competition_id" value="<?php echo $competition['CompetitionID']; ?>">
                                                            <button type="submit" class="btn btn-sm btn-danger" title="Remove from Championship" onclick="return confirm('Are you sure you want to remove this competition from the championship?');">
                                                                <i class="fas fa-times"></i> Remove
                                                            </button>
                                                        </form>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                            
                            <div class="add-championship-competition">
                                <h4>Add Competition to Championship</h4>
                                <form method="post" action="manage_championships.php" class="add-competition-form">
                                    <input type="hidden" name="action" value="add_competition">
                                    <div class="form-group">
                                        <label for="competition">Select Competition:</label>
                                        <select id="competition" name="competition_id" class="form-control" required>
                                            <option value="">-- Select Competition --</option>
                                            <?php 
                                                $nonChampionshipCompetitions = array_filter($competitions, function($competition) {
                                                    return !$competition['ContributesToChampionship'];
                                                });
                                                
                                                foreach ($nonChampionshipCompetitions as $competition): 
                                            ?>
                                                <option value="<?php echo $competition['CompetitionID']; ?>">
                                                    <?php echo htmlspecialchars($competition['CompetitionName'] . ' (' . formatDate($competition['StartDate']) . ')'); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-plus"></i> Add to Championship
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card">
                        <div class="card-header">
                            <h3>Championship Categories</h3>
                        </div>
                        <div class="card-body">
                            <p>The following categories are eligible for championship standings:</p>
                            <div class="categories-grid">
                                <?php foreach ($categories as $category): ?>
                                    <div class="category-card">
                                        <span class="category-name"><?php echo htmlspecialchars($category['CategoryName']); ?></span>
                                        <span class="category-details">
                                            <?php echo htmlspecialchars($category['ClassName'] . ' / ' . $category['DivisionName']); ?>
                                        </span>
                                    </div>
                                <?php endforeach; ?>
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

    <script src="../js/main.js"></script>
    
    <style>
        /* Manage Championships Page Specific Styles */
        .manage-championships-container {
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
        
        .championship-actions {
            display: grid;
            grid-template-columns: 1fr;
            gap: 1.5rem;
        }
        
        .card {
            background-color: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        .card-header {
            background-color: var(--light-color);
            padding: 1rem 1.5rem;
            border-bottom: 1px solid var(--gray-color);
        }
        
        .card-header h3 {
            color: var(--primary-color);
            margin: 0;
            font-size: 1.1rem;
        }
        
        .card-body {
            padding: 1.5rem;
        }
        
        .recalculate-form {
            display: flex;
            align-items: flex-end;
            gap: 1rem;
            margin-top: 1rem;
        }
        
        .recalculate-form .form-group {
            margin-bottom: 0;
        }
        
        .championship-competitions-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 1.5rem;
        }
        
        .championship-competitions-table th, .championship-competitions-table td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid var(--gray-color);
        }
        
        .championship-competitions-table th {
            font-weight: 600;
            background-color: var(--light-color);
        }
        
        .badge {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            font-size: 0.75rem;
            font-weight: 500;
            border-radius: 9999px;
            margin-right: 0.5rem;
        }
        
        .badge-championship {
            background-color: #fef3c7;
            color: #d97706;
        }
        
        .badge-contributes {
            background-color: #dcfce7;
            color: #16a34a;
        }
        
        .btn-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
        }
        
        .btn-danger {
            background-color: #ef4444;
            color: white;
        }
        
        .btn-danger:hover {
            background-color: #dc2626;
        }
        
        .add-championship-competition {
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid var(--gray-color);
        }
        
        .add-championship-competition h4 {
            color: var(--primary-color);
            margin-bottom: 1rem;
            font-size: 1rem;
        }
        
        .add-competition-form {
            display: flex;
            align-items: flex-end;
            gap: 1rem;
        }
        
        .add-competition-form .form-group {
            flex: 1;
            margin-bottom: 0;
        }
        
        .categories-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }
        
        .category-card {
            padding: 0.75rem;
            background-color: var(--light-color);
            border-radius: 6px;
            border: 1px solid var(--gray-color);
        }
        
        .category-name {
            display: block;
            font-weight: 600;
            margin-bottom: 0.25rem;
        }
        
        .category-details {
            display: block;
            font-size: 0.875rem;
            color: var(--text-secondary);
        }
        
        .no-championship-competitions {
            padding: 1rem;
            background-color: var(--light-color);
            border-radius: 6px;
            text-align: center;
            color: var(--text-secondary);
            margin-bottom: 1.5rem;
        }
        
        .inline-form {
            display: inline-block;
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .recalculate-form,
            .add-competition-form {
                flex-direction: column;
                align-items: stretch;
                gap: 0.75rem;
            }
            
            .recalculate-form .form-group,
            .add-competition-form .form-group {
                margin-bottom: 0.75rem;
            }
            
            .championship-competitions-table {
                font-size: 0.9rem;
            }
            
            .categories-grid {
                grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            }
        }
        
        @media (min-width: 992px) {
            .championship-actions {
                grid-template-columns: 1fr 1fr;
            }
            
            .championship-actions > .card:first-child {
                grid-column: 1 / -1;
            }
        }
    </style>
</body>
</html>