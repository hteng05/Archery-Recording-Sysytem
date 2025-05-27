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

// Get competition ID from URL
$competitionId = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Get all competitions for dropdown
$competitions = getAllCompetitions(true);

// Get selected competition details
$selectedCompetition = null;
if ($competitionId > 0) {
    $conn = getDbConnection();
    if ($conn) {
        $stmt = $conn->prepare("SELECT * FROM CompetitionTable WHERE CompetitionID = ?");
        $stmt->bind_param("i", $competitionId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $selectedCompetition = $result->fetch_assoc();
        }
    }
}

// If no competition selected, use the first one
if (!$selectedCompetition && !empty($competitions)) {
    $selectedCompetition = $competitions[0];
    $competitionId = $selectedCompetition['CompetitionID'];
}

// Get competition results
$results = getCompetitionResults($competitionId);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Competition Results - Archery Score Recording System</title>
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
                        <a href="add_competition.php">Add New Competition</a>
                        <a href="competition_results.php" class="active">View Results</a>
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
            <div class="competition-results-container">
                <div class="page-header">
                    <h2><i class="fas fa-trophy"></i> Competition Results</h2>
                    <div class="action-buttons">
                        <a href="enter_score.php" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Enter New Score
                        </a>
                    </div>
                </div>
                
                <div class="competition-selector">
                    <form action="competition_results.php" method="get">
                        <div class="form-group">
                            <label for="competition">Select Competition:</label>
                            <select id="competition" name="id" class="form-control" onchange="this.form.submit()">
                                <option value="">-- Select Competition --</option>
                                <?php foreach ($competitions as $competition): ?>
                                    <option value="<?php echo $competition['CompetitionID']; ?>" <?php echo ($competitionId === $competition['CompetitionID']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($competition['CompetitionName'] . ' (' . formatDate($competition['StartDate']) . ')'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </form>
                </div>
                
                <?php if ($selectedCompetition): ?>
                    <div class="competition-info">
                        <h3><?php echo htmlspecialchars($selectedCompetition['CompetitionName']); ?></h3>
                        <div class="info-items">
                            <div class="info-item">
                                <span class="info-label">Date:</span>
                                <span class="info-value">
                                    <?php 
                                        if ($selectedCompetition['StartDate'] === $selectedCompetition['EndDate']) {
                                            echo formatDate($selectedCompetition['StartDate']);
                                        } else {
                                            echo formatDate($selectedCompetition['StartDate']) . ' - ' . formatDate($selectedCompetition['EndDate']);
                                        }
                                    ?>
                                </span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Location:</span>
                                <span class="info-value"><?php echo htmlspecialchars($selectedCompetition['Location']); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Status:</span>
                                <span class="info-value">
                                    <?php if ($selectedCompetition['IsChampionship']): ?>
                                        Championship
                                    <?php else: ?>
                                        Regular Competition
                                    <?php endif; ?>
                                    
                                    <?php if ($selectedCompetition['ContributesToChampionship']): ?>
                                        (Contributes to Club Championship)
                                    <?php endif; ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <?php if (empty($results)): ?>
                        <div class="no-results">
                            <i class="fas fa-info-circle"></i>
                            <h3>No Results</h3>
                            <p>There are no scores recorded for this competition yet.</p>
                            <a href="enter_score.php" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Enter Scores
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="categories-accordion">
                            <?php foreach ($results as $category => $categoryResults): ?>
                                <div class="category-section">
                                    <div class="category-header">
                                        <h4><?php echo htmlspecialchars($category); ?></h4>
                                        <i class="fas fa-chevron-down"></i>
                                    </div>
                                    <div class="category-content">
                                        <div class="results-table-container">
                                            <table class="results-table">
                                                <thead>
                                                    <tr>
                                                        <th>Rank</th>
                                                        <th>Archer</th>
                                                        <th>Round</th>
                                                        <th>Equipment</th>
                                                        <th>Score</th>
                                                        <th>Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($categoryResults as $index => $result): ?>
                                                        <tr>
                                                            <td class="rank-cell"><?php echo $index + 1; ?></td>
                                                            <td><?php echo htmlspecialchars($result['FirstName'] . ' ' . $result['LastName']); ?></td>
                                                            <td><?php echo htmlspecialchars($result['RoundName']); ?></td>
                                                            <td><?php echo htmlspecialchars($result['EquipmentName']); ?></td>
                                                            <td class="score-cell"><?php echo $result['TotalScore']; ?></td>
                                                            <td>
                                                                <div class="action-buttons">
                                                                    <a href="score_details.php?id=<?php echo $result['ScoreID']; ?>" class="btn btn-sm btn-secondary" title="View Details">
                                                                        <i class="fas fa-eye"></i>
                                                                    </a>
                                                                    <a href="edit_score.php?id=<?php echo $result['ScoreID']; ?>" class="btn btn-sm btn-primary" title="Edit Score">
                                                                        <i class="fas fa-edit"></i>
                                                                    </a>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="export-buttons">
                            <button type="button" class="btn btn-secondary" onclick="exportResults('csv')">
                                <i class="fas fa-file-csv"></i> Export Results to CSV
                            </button>
                            <button type="button" class="btn btn-secondary" onclick="exportResults('pdf')">
                                <i class="fas fa-file-pdf"></i> Export Results to PDF
                            </button>
                            <button type="button" class="btn btn-secondary" onclick="printResults()">
                                <i class="fas fa-print"></i> Print Results
                            </button>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="no-competition-selected">
                        <i class="fas fa-trophy"></i>
                        <h3>No Competition Selected</h3>
                        <p>Please select a competition from the dropdown to view its results.</p>
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
            // Accordion functionality
            const categoryHeaders = document.querySelectorAll('.category-header');
            
            categoryHeaders.forEach(header => {
                header.addEventListener('click', function() {
                    const content = this.nextElementSibling;
                    const icon = this.querySelector('i');
                    
                    // Toggle active class on header
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
            
            // Open the first category by default
            if (categoryHeaders.length > 0) {
                categoryHeaders[0].click();
            }
        });
        
        function exportResults(format) {
            // This would connect to a server-side export script in a real implementation
            alert('Export to ' + format.toUpperCase() + ' would be implemented here.');
        }
        
        function printResults() {
            window.print();
        }
    </script>
    
    <style>
        /* Competition Results Page Specific Styles */
        .competition-results-container {
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
        
        .competition-selector {
            background-color: white;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        .competition-selector .form-group {
            margin: 0;
            display: flex;
            align-items: center;
        }
        
        .competition-selector label {
            margin-right: 1rem;
            margin-bottom: 0;
            font-weight: 500;
            min-width: 150px;
        }
        
        .competition-info {
            background-color: white;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        .competition-info h3 {
            color: var(--primary-color);
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid var(--gray-color);
        }
        
        .info-items {
            display: flex;
            flex-wrap: wrap;
            gap: 2rem;
        }
        
        .info-item {
            display: flex;
            flex-direction: column;
        }
        
        .info-label {
            font-size: 0.9rem;
            color: #6b7280;
        }
        
        .info-value {
            font-size: 1.1rem;
            font-weight: 500;
        }
        
        .categories-accordion {
            margin-bottom: 1.5rem;
        }
        
        .category-section {
            margin-bottom: 1rem;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        .category-header {
            background-color: white;
            padding: 1rem 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .category-header:hover {
            background-color: #f9fafb;
        }
        
        .category-header.active {
            background-color: var(--primary-color);
            color: white;
        }
        
        .category-header h4 {
            margin: 0;
            font-size: 1.1rem;
        }
        
        .category-content {
            background-color: white;
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease;
        }
        
        .results-table-container {
            padding: 1.5rem;
        }
        
        .results-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .results-table th, .results-table td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid var(--gray-color);
        }
        
        .results-table th {
            font-weight: 600;
            background-color: var(--light-color);
        }
        
        .rank-cell {
            font-weight: 600;
            text-align: center;
            width: 50px;
        }
        
        .score-cell {
            font-weight: 600;
            text-align: center;
        }
        
        .action-buttons {
            display: flex;
            gap: 0.5rem;
        }
        
        .btn-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
        }
        
        .no-results, .no-competition-selected {
            background-color: white;
            border-radius: 8px;
            padding: 3rem 1.5rem;
            text-align: center;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        .no-results i, .no-competition-selected i {
            font-size: 3rem;
            color: #d1d5db;
            margin-bottom: 1rem;
        }
        
        .no-results h3, .no-competition-selected h3 {
            color: var(--primary-color);
            margin-bottom: 1rem;
        }
        
        .no-results p, .no-competition-selected p {
            margin-bottom: 1.5rem;
        }
        
        .export-buttons {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
            margin-top: 1.5rem;
        }
        
        /* Responsive adjustments */
        @media (max-width: 992px) {
            .competition-selector .form-group {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .competition-selector label {
                margin-bottom: 0.5rem;
            }
            
            .page-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }
            
            .export-buttons {
                flex-direction: column;
                align-items: stretch;
            }
        }
        
        /* Print styles */
        @media print {
            .navbar, .page-header, .competition-selector, .export-buttons, footer {
                display: none;
            }
            
            .competition-results-container {
                padding: 0;
            }
            
            .category-content {
                max-height: none !important;
                overflow: visible !important;
            }
            
            .category-header i {
                display: none;
            }
            
            .category-header {
                background-color: #f9fafb !important;
                color: black !important;
                cursor: default;
            }
            
            .category-section {
                page-break-inside: avoid;
                margin-bottom: 2rem;
            }
            
            .action-buttons {
                display: none;
            }
        }
    </style>
</body>
</html>