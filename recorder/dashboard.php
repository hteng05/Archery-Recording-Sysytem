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

// Get important statistics
$conn = getDbConnection();
$pendingScores = 0;
$totalArchers = 0;
$activeCompetitions = 0;

if ($conn) {
    // Count pending scores
    $result = $conn->query("SELECT COUNT(*) as count FROM ScoreStaging");
    if ($result) {
        $pendingScores = $result->fetch_assoc()['count'];
    }
    
    // Count active archers
    $result = $conn->query("SELECT COUNT(*) as count FROM ArcherTable WHERE IsActive = 1");
    if ($result) {
        $totalArchers = $result->fetch_assoc()['count'];
    }
    
    // Count active competitions
    $currentDate = date('Y-m-d');
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM CompetitionTable WHERE EndDate >= ?");
    $stmt->bind_param("s", $currentDate);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result) {
        $activeCompetitions = $result->fetch_assoc()['count'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recorder Dashboard - Archery Score Recording System</title>
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
                <a href="dashboard.php" class="active">Dashboard</a>
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
            <div class="dashboard-header">
                <h2>Recorder Dashboard</h2>
                <p>Welcome back, <?php echo htmlspecialchars($recorder['FirstName'] . ' ' . $recorder['LastName']); ?>!</p>
            </div>
            
            <div class="stats-overview">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-clipboard-list"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $pendingScores; ?></h3>
                        <p>Pending Scores</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $totalArchers; ?></h3>
                        <p>Active Archers</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-trophy"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $activeCompetitions; ?></h3>
                        <p>Active Competitions</p>
                    </div>
                </div>
            </div>
            
            <div class="dashboard-grid">
                <div class="dashboard-card">
                    <i class="fas fa-user-plus"></i>
                    <h3>Add New Archer</h3>
                    <p>Register a new archer in the system</p>
                    <a href="add_archer.php" class="card-link">Add Archer</a>
                </div>
                
                <div class="dashboard-card">
                    <i class="fas fa-check-circle"></i>
                    <h3>Approve Scores</h3>
                    <p>Review and approve pending archer scores</p>
                    <a href="pending_scores.php" class="card-link">View Pending</a>
                </div>
                
                <div class="dashboard-card">
                    <i class="fas fa-edit"></i>
                    <h3>Enter New Score</h3>
                    <p>Manually enter a new score for an archer</p>
                    <a href="enter_score.php" class="card-link">Enter Score</a>
                </div>
                
                <div class="dashboard-card">
                    <i class="fas fa-calendar-plus"></i>
                    <h3>Add Competition</h3>
                    <p>Create a new archery competition</p>
                    <a href="add_competition.php" class="card-link">Add Competition</a>
                </div>
                
                <div class="dashboard-card">
                    <i class="fas fa-medal"></i>
                    <h3>Competition Results</h3>
                    <p>View results from all competitions</p>
                    <a href="competition_results.php" class="card-link">View Results</a>
                </div>
                
                <div class="dashboard-card">
                    <i class="fas fa-chart-line"></i>
                    <h3>Club Records</h3>
                    <p>View and manage club record scores</p>
                    <a href="club_records.php" class="card-link">View Records</a>
                </div>
            </div>
        </main>

        <footer>
            <p>&copy; 2025 Archery Club Database System. All rights reserved.</p>
        </footer>
    </div>

    <script src="../js/main.js"></script>
    
    <style>
        
        /* Dashboard Styles */
        .dashboard-header {
            margin-bottom: 2rem;
        }
        
        .dashboard-header h2 {
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }
        
        .stats-overview {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background-color: white;
            border-radius: 8px;
            padding: 1.5rem;
            display: flex;
            align-items: center;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background-color: var(--primary-color);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 1rem;
        }
        
        .stat-icon i {
            font-size: 1.5rem;
        }
        
        .stat-info h3 {
            font-size: 1.75rem;
            margin-bottom: 0.25rem;
            color: var(--dark-color);
        }
        
        .stat-info p {
            color: #6b7280;
            margin: 0;
        }
        
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
        }
        
        .dashboard-card {
            background-color: white;
            border-radius: 8px;
            padding: 1.5rem;
            text-align: center;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }
        
        .dashboard-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .dashboard-card i {
            font-size: 2.5rem;
            color: var(--primary-color);
            margin-bottom: 1rem;
        }
        
        .dashboard-card h3 {
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }
        
        .dashboard-card p {
            color: #6b7280;
            margin-bottom: 1.5rem;
        }
        
        .card-link {
            background-color: var(--primary-color);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            text-decoration: none;
            transition: background-color 0.3s ease;
            margin-top: auto;
        }
        
        .card-link:hover {
            background-color: var(--primary-light);
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .navbar {
                flex-direction: column;
            }
            
            .navbar-left, .navbar-right {
                flex-direction: column;
                width: 100%;
            }
            
            .dropdown-menu {
                position: static;
                width: 100%;
                box-shadow: none;
            }
        }
    </style>
</body>
</html>