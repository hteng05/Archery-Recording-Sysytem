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

// Get year for filter
$currentYear = date('Y');
$selectedYear = isset($_GET['year']) ? intval($_GET['year']) : $currentYear;

// Get available years with championship data
$availableYears = [];
$conn = getDbConnection();

if ($conn) {
    $result = $conn->query("SELECT DISTINCT Year FROM ChampionshipStandingTable ORDER BY Year DESC");
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $availableYears[] = $row['Year'];
        }
    }
}

// If no years available, add current year
if (empty($availableYears)) {
    $availableYears[] = $currentYear;
}

// Get championship standings
$standings = getChampionshipStandings($selectedYear);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Championship Standings - Archery Score Recording System</title>
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
                        <a href="manage_championships.php">Manage Championships</a>
                        <a href="championship_standings.php" class="active">View Standings</a>
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
            <div class="championship-standings-container">
                <div class="page-header">
                    <h2><i class="fas fa-trophy"></i> Championship Standings</h2>
                    <div class="action-buttons">
                        <a href="manage_championships.php" class="btn btn-primary">
                            <i class="fas fa-cog"></i> Manage Championships
                        </a>
                    </div>
                </div>
                
                <div class="year-selector">
                    <form action="championship_standings.php" method="get">
                        <div class="form-group">
                            <label for="year">Select Year:</label>
                            <select id="year" name="year" class="form-control" onchange="this.form.submit()">
                                <?php foreach ($availableYears as $year): ?>
                                    <option value="<?php echo $year; ?>" <?php echo ($selectedYear === $year) ? 'selected' : ''; ?>>
                                        <?php echo $year; ?> Championship
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </form>
                </div>
                
                <?php if (empty($standings)): ?>
                    <div class="no-standings">
                        <i class="fas fa-trophy"></i>
                        <h3>No Championship Data</h3>
                        <p>There are no championship standings available for the selected year.</p>
                        <a href="manage_championships.php" class="btn btn-primary">
                            <i class="fas fa-cog"></i> Set Up Championship
                        </a>
                    </div>
                <?php else: ?>
                    <div class="categories-accordion">
                        <?php foreach ($standings as $category => $categoryStandings): ?>
                            <div class="category-section">
                                <div class="category-header">
                                    <h3><?php echo htmlspecialchars($category); ?></h3>
                                    <i class="fas fa-chevron-down"></i>
                                </div>
                                <div class="category-content">
                                    <div class="standings-table-container">
                                        <table class="standings-table">
                                            <thead>
                                                <tr>
                                                    <th>Rank</th>
                                                    <th>Archer</th>
                                                    <th>Points</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($categoryStandings as $standing): ?>
                                                    <tr>
                                                        <td class="rank-cell">
                                                            <?php echo $standing['Rank']; ?>
                                                            <?php if ($standing['Rank'] === 1): ?>
                                                                <i class="fas fa-crown champion-icon"></i>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td><?php echo htmlspecialchars($standing['FirstName'] . ' ' . $standing['LastName']); ?></td>
                                                        <td class="points-cell"><?php echo $standing['TotalPoints']; ?></td>
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
                        <button type="button" class="btn btn-secondary" onclick="exportStandings('csv')">
                            <i class="fas fa-file-csv"></i> Export to CSV
                        </button>
                        <button type="button" class="btn btn-secondary" onclick="exportStandings('pdf')">
                            <i class="fas fa-file-pdf"></i> Export to PDF
                        </button>
                        <button type="button" class="btn btn-secondary" onclick="printStandings()">
                            <i class="fas fa-print"></i> Print Standings
                        </button>
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
        
        function exportStandings(format) {
            // This would connect to a server-side export script in a real implementation
            alert('Export to ' + format.toUpperCase() + ' would be implemented here.');
        }
        
        function printStandings() {
            window.print();
        }
    </script>
    
    <style>
        /* Championship Standings Page Specific Styles */
        .championship-standings-container {
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
        
        .year-selector {
            background-color: white;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        .year-selector .form-group {
            margin: 0;
            display: flex;
            align-items: center;
        }
        
        .year-selector label {
            margin-right: 1rem;
            margin-bottom: 0;
            font-weight: 500;
            min-width: 150px;
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
        
        .category-header h3 {
            margin: 0;
            font-size: 1.1rem;
        }
        
        .category-content {
            background-color: white;
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease;
        }
        
        .standings-table-container {
            padding: 1.5rem;
        }
        
        .standings-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .standings-table th, .standings-table td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid var(--gray-color);
        }
        
        .standings-table th {
            font-weight: 600;
            background-color: var(--light-color);
        }
        
        .rank-cell {
            font-weight: 600;
            text-align: center;
            width: 80px;
            position: relative;
        }
        
        .champion-icon {
            color: gold;
            margin-left: 0.5rem;
        }
        
        .points-cell {
            font-weight: 600;
            text-align: center;
        }
        
        .no-standings {
            background-color: white;
            border-radius: 8px;
            padding: 3rem 1.5rem;
            text-align: center;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        .no-standings i {
            font-size: 3rem;
            color: #d1d5db;
            margin-bottom: 1rem;
        }
        
        .no-standings h3 {
            color: var(--primary-color);
            margin-bottom: 1rem;
        }
        
        .no-standings p {
            margin-bottom: 1.5rem;
        }
        
        .export-buttons {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
        }
        
        /* Responsive adjustments */
        @media (max-width: 992px) {
            .year-selector .form-group {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .year-selector label {
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
            .navbar, .page-header, .year-selector, .export-buttons, footer {
                display: none;
            }
            
            .championship-standings-container {
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
        }
    </style>
</body>
</html>