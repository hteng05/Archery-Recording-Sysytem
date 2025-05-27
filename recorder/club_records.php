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

// Get all categories
$categories = getAllCategories();

// Get selected category for filtering
$selectedCategoryId = isset($_GET['category_id']) ? intval($_GET['category_id']) : 0;

// Get club records
$clubRecords = getClubBests($selectedCategoryId);

// Process action if any
$message = '';
$messageType = '';

if (isset($_GET['action']) && isset($_GET['id'])) {
    $action = $_GET['action'];
    $scoreId = intval($_GET['id']);
    
    if ($action === 'reset_record') {
        // Reset the club record status for this score
        $conn = getDbConnection();
        if ($conn) {
            // Begin transaction
            $conn->begin_transaction();
            
            try {
                // First, get the record to remove
                $stmt = $conn->prepare("SELECT cb.RoundID, cb.CategoryID, cb.ArcherID FROM ClubBestTable cb 
                                      JOIN ScoreTable s ON cb.ScoreID = s.ScoreID 
                                      WHERE s.ScoreID = ?");
                $stmt->bind_param("i", $scoreId);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows === 1) {
                    $record = $result->fetch_assoc();
                    
                    // Remove the record
                    $stmt = $conn->prepare("DELETE FROM ClubBestTable WHERE CategoryID = ? AND RoundID = ?");
                    $stmt->bind_param("ii", $record['CategoryID'], $record['RoundID']);
                    $stmt->execute();
                    
                    // Update the score to remove club best flag
                    $stmt = $conn->prepare("UPDATE ScoreTable SET IsClubBest = 0 WHERE ScoreID = ?");
                    $stmt->bind_param("i", $scoreId);
                    $stmt->execute();
                    
                    // Commit transaction
                    $conn->commit();
                    
                    $message = 'Club record has been reset successfully.';
                    $messageType = 'success';
                } else {
                    throw new Exception('Record not found');
                }
            } catch (Exception $e) {
                // Rollback on error
                $conn->rollback();
                $message = 'Error resetting club record: ' . $e->getMessage();
                $messageType = 'error';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Club Records - Archery Score Recording System</title>
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
                    <a href="#" class="dropdown-toggle">Championships</a>
                    <div class="dropdown-menu">
                        <a href="manage_championships.php">Manage Championships</a>
                        <a href="championship_standings.php">View Standings</a>
                    </div>
                </div>
                <div class="dropdown">
                    <a href="#" class="dropdown-toggle active">Records</a>
                    <div class="dropdown-menu">
                        <a href="club_records.php" class="active">Club Records</a>
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
            <div class="club-records-container">
                <div class="page-header">
                    <h2><i class="fas fa-trophy"></i> Club Records</h2>
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
                
                <div class="filter-section">
                    <form action="club_records.php" method="get" class="filter-form">
                        <div class="form-group">
                            <label for="category">Filter by Category:</label>
                            <select id="category" name="category_id" class="form-control" onchange="this.form.submit()">
                                <option value="">All Categories</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['CategoryID']; ?>" <?php echo ($selectedCategoryId === $category['CategoryID']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($category['CategoryName']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </form>
                </div>
                
                <?php if (!empty($clubRecords)): ?>
                    <div class="categories-accordion">
                        <?php foreach ($clubRecords as $category => $records): ?>
                            <div class="category-section">
                                <div class="category-header">
                                    <h3><?php echo htmlspecialchars($category); ?></h3>
                                    <i class="fas fa-chevron-down"></i>
                                </div>
                                <div class="category-content">
                                    <div class="records-table-container">
                                        <table class="records-table">
                                            <thead>
                                                <tr>
                                                    <th>Round</th>
                                                    <th>Score</th>
                                                    <th>Archer</th>
                                                    <th>Date Achieved</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($records as $record): ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($record['RoundName']); ?></td>
                                                        <td class="score-cell"><?php echo $record['TotalScore']; ?></td>
                                                        <td><?php echo htmlspecialchars($record['FirstName'] . ' ' . $record['LastName']); ?></td>
                                                        <td><?php echo formatDate($record['DateAchieved']); ?></td>
                                                        <td>
                                                            <div class="action-buttons">
                                                                <a href="score_details.php?id=<?php echo $record['ScoreID']; ?>" class="btn btn-sm btn-secondary" title="View Details">
                                                                    <i class="fas fa-eye"></i>
                                                                </a>
                                                                <a href="club_records.php?action=reset_record&id=<?php echo $record['ScoreID']; ?>" class="btn btn-sm btn-danger" title="Reset Record" onclick="return confirm('Are you sure you want to reset this club record? This action cannot be undone.');">
                                                                    <i class="fas fa-times-circle"></i>
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
                        <button type="button" class="btn btn-secondary" onclick="exportRecords('csv')">
                            <i class="fas fa-file-csv"></i> Export to CSV
                        </button>
                        <button type="button" class="btn btn-secondary" onclick="exportRecords('pdf')">
                            <i class="fas fa-file-pdf"></i> Export to PDF
                        </button>
                        <button type="button" class="btn btn-secondary" onclick="printRecords()">
                            <i class="fas fa-print"></i> Print Records
                        </button>
                    </div>
                <?php else: ?>
                    <div class="no-records">
                        <i class="fas fa-trophy"></i>
                        <h3>No Records Found</h3>
                        <p>No club records found for the selected category.</p>
                        <?php if ($selectedCategoryId): ?>
                            <p><a href="club_records.php">View all categories</a></p>
                        <?php endif; ?>
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
        
        function exportRecords(format) {
            // This would connect to a server-side export script in a real implementation
            alert('Export to ' + format.toUpperCase() + ' would be implemented here.');
        }
        
        function printRecords() {
            window.print();
        }
    </script>
    
    <style>
        /* Club Records Page Specific Styles */
        .club-records-container {
            padding: 1rem;
        }
        
        .page-header {
            margin-bottom: 1.5rem;
        }
        
        .page-header h2 {
            color: var(--primary-color);
            margin: 0;
        }
        
        .page-header h2 i {
            margin-right: 0.5rem;
        }
        
        .filter-section {
            background-color: white;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        .filter-form {
            display: flex;
            align-items: center;
        }
        
        .filter-form .form-group {
            margin: 0;
            display: flex;
            align-items: center;
            flex: 1;
        }
        
        .filter-form label {
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
        
        .records-table-container {
            padding: 1.5rem;
        }
        
        .records-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .records-table th, .records-table td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid var(--gray-color);
        }
        
        .records-table th {
            font-weight: 600;
            background-color: var(--light-color);
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
        
        .btn-danger {
            background-color: #ef4444;
            color: white;
        }
        
        .btn-danger:hover {
            background-color: #dc2626;
        }
        
        .no-records {
            background-color: white;
            border-radius: 8px;
            padding: 3rem 1.5rem;
            text-align: center;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        .no-records i {
            font-size: 3rem;
            color: #d1d5db;
            margin-bottom: 1rem;
        }
        
        .no-records h3 {
            color: var(--primary-color);
            margin-bottom: 1rem;
        }
        
        .no-records p {
            margin-bottom: 0.5rem;
        }
        
        .no-records a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
        }
        
        .no-records a:hover {
            text-decoration: underline;
        }
        
        .export-buttons {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
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
        @media (max-width: 992px) {
            .filter-form {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .filter-form label {
                margin-bottom: 0.5rem;
            }
            
            .export-buttons {
                flex-direction: column;
                align-items: stretch;
            }
        }
        
        /* Print styles */
        @media print {
            .navbar, .page-header, .filter-section, .export-buttons, footer {
                display: none;
            }
            
            .club-records-container {
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