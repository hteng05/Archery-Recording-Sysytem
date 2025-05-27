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

// Get all competitions
$competitions = getAllCompetitions(true); // Include completed competitions
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Club Competitions - Archery Score Recording System</title>
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
            <div class="competitions-container">
                <h2><i class="fas fa-trophy"></i> Club Competitions</h2>
                <p>View competition information and results. Click on any competition to see detailed results.</p>
                
                <?php if (empty($competitions)): ?>
                    <div class="no-competitions">
                        <i class="fas fa-calendar-alt"></i>
                        <h3>No Competitions Available</h3>
                        <p>There are currently no competitions scheduled or completed.</p>
                    </div>
                <?php else: ?>
                    <div class="competitions-list">
                        <div class="search-section">
                            <h3>Competitions</h3>
                            <div class="search-box">
                                <input type="text" id="searchCompetitions" placeholder="Search competitions..." onkeyup="filterCompetitions()">
                                <i class="fas fa-search search-icon"></i>
                            </div>
                        </div>

                        <div class="competitions-grid" id="competitionsGrid">
                            <?php foreach ($competitions as $competition): ?>
                                <?php
                                $startDate = new DateTime($competition['StartDate']);
                                $endDate = new DateTime($competition['EndDate']);
                                $today = new DateTime();
                                
                                // Determine competition status
                                $status = '';
                                $statusClass = '';
                                if ($today < $startDate) {
                                    $status = 'Upcoming';
                                    $statusClass = 'status-upcoming';
                                } elseif ($today >= $startDate && $today <= $endDate) {
                                    $status = 'In Progress';
                                    $statusClass = 'status-active';
                                } else {
                                    $status = 'Completed';
                                    $statusClass = 'status-completed';
                                }
                                
                                // Check if there are results available
                                $hasResults = ($status === 'Completed' || $status === 'In Progress');
                                ?>
                                
                                <div class="competition-card searchable-item" data-name="<?php echo strtolower($competition['CompetitionName']); ?>">
                                    <div class="competition-header">
                                        <h4><?php echo htmlspecialchars($competition['CompetitionName']); ?></h4>
                                        <div class="competition-badges">
                                            <span class="badge <?php echo $statusClass; ?>"><?php echo $status; ?></span>
                                            <?php if ($competition['IsChampionship']): ?>
                                                <span class="badge badge-championship">Championship</span>
                                            <?php endif; ?>
                                            <?php if ($competition['IsOfficial']): ?>
                                                <span class="badge badge-official">Official</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                    <div class="competition-details">
                                        <div class="detail-row">
                                            <i class="fas fa-calendar"></i>
                                            <span>
                                                <?php echo formatDate($competition['StartDate']); ?>
                                                <?php if ($competition['StartDate'] !== $competition['EndDate']): ?>
                                                    - <?php echo formatDate($competition['EndDate']); ?>
                                                <?php endif; ?>
                                            </span>
                                        </div>
                                        
                                        <div class="detail-row">
                                            <i class="fas fa-map-marker-alt"></i>
                                            <span><?php echo htmlspecialchars($competition['Location']); ?></span>
                                        </div>
                                        
                                        <?php if ($competition['ContributesToChampionship']): ?>
                                            <div class="detail-row">
                                                <i class="fas fa-star"></i>
                                                <span>Contributes to Championship</span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="competition-actions">
                                        <?php if ($hasResults): ?>
                                            <a href="competition_results.php?id=<?php echo $competition['CompetitionID']; ?>" class="btn btn-primary">
                                                <i class="fas fa-trophy"></i> View Results
                                            </a>
                                        <?php else: ?>
                                            <button class="btn btn-secondary" disabled>
                                                <i class="fas fa-clock"></i> No Results Yet
                                            </button>
                                        <?php endif; ?>
                                        
                                        <a href="competition_details.php?id=<?php echo $competition['CompetitionID']; ?>" class="btn btn-outline">
                                            <i class="fas fa-info-circle"></i> Details
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
                <div class="championship-section">
                    <h3>Club Championship Standings</h3>
                    <a href="championship_standings.php" class="btn btn-primary">
                        <i class="fas fa-medal"></i> View Championship Standings
                    </a>
                </div>
            </div>
        </main>

        <footer>
            <p>&copy; 2025 Archery Club Database System. All rights reserved.</p>
        </footer>
    </div>

    <script>
        function filterCompetitions() {
            const searchInput = document.getElementById('searchCompetitions');
            const filter = searchInput.value.toLowerCase();
            const competitions = document.querySelectorAll('.searchable-item');
            
            competitions.forEach(competition => {
                const name = competition.getAttribute('data-name');
                if (name.includes(filter)) {
                    competition.style.display = 'block';
                } else {
                    competition.style.display = 'none';
                }
            });
        }
    </script>
    
    <style>
        .competitions-container {
            padding: 2rem;
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .competitions-container h2 {
            color: var(--primary-color);
            margin-bottom: 1rem;
        }
        
        .competitions-container h2 i {
            margin-right: 0.5rem;
        }
        
        .competitions-container > p {
            color: #6b7280;
            margin-bottom: 2rem;
        }
        
        .no-competitions {
            text-align: center;
            padding: 3rem;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        .no-competitions i {
            font-size: 3rem;
            color: #9ca3af;
            margin-bottom: 1rem;
        }
        
        .no-competitions h3 {
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }
        
        .search-section {
            background: white;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
        }
        
        .search-section h3 {
            margin: 0 0 1rem 0;
            color: var(--primary-color);
        }
        
        .search-box {
            position: relative;
            max-width: 400px;
        }
        
        .search-box input {
            width: 100%;
            padding: 0.75rem 2.5rem 0.75rem 1rem;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 1rem;
        }
        
        .search-box input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        
        .search-icon {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #6b7280;
        }
        
        .competitions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 1.5rem;
        }
        
        .competition-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .competition-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
        }
        
        .competition-header {
            padding: 1.5rem;
            border-bottom: 1px solid #f3f4f6;
        }
        
        .competition-header h4 {
            margin: 0 0 1rem 0;
            color: var(--primary-color);
            font-size: 1.25rem;
        }
        
        .competition-badges {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
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
        
        .competition-details {
            padding: 1rem 1.5rem;
            background: #f9fafb;
        }
        
        .detail-row {
            display: flex;
            align-items: center;
            margin-bottom: 0.75rem;
            font-size: 0.9rem;
            color: #4b5563;
        }
        
        .detail-row:last-child {
            margin-bottom: 0;
        }
        
        .detail-row i {
            width: 1.25rem;
            margin-right: 0.75rem;
            color: #6b7280;
        }
        
        .competition-actions {
            padding: 1.5rem;
            display: flex;
            gap: 1rem;
        }
        
        .btn {
            padding: 0.625rem 1rem;
            border: none;
            border-radius: 6px;
            font-size: 0.875rem;
            font-weight: 500;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            cursor: pointer;
            transition: all 0.2s;
            flex: 1;
            justify-content: center;
        }
        
        .btn-primary {
            background: var(--primary-color);
            color: white;
        }
        
        .btn-primary:hover {
            background: #065f46;
        }
        
        .btn-secondary {
            background: #f3f4f6;
            color: #6b7280;
            cursor: not-allowed;
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
        .championship-section {
            margin-top: 2rem;
            background-color: white;
            border-radius: 8px;
            padding: 1.5rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .championship-section h3 {
            color: var(--primary-color);
            margin: 0;
        }
        
        @media (max-width: 768px) {
            .competitions-container {
                padding: 1rem;
            }
            
            .competitions-grid {
                grid-template-columns: 1fr;
            }
            
            .competition-actions {
                flex-direction: column;
            }
            
            .search-box {
                max-width: none;
            }
            .championship-section {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }
        }
    </style>
</body>
</html>