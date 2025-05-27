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

// Get all categories
$categories = getAllCategories();

// Get selected category for filtering
$selectedCategoryId = isset($_GET['category_id']) ? intval($_GET['category_id']) : 0;

// Get club records
$clubRecords = getClubBests($selectedCategoryId);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Club Records - Archery Score Recording System</title>
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
                <a href="competitions.php">Competitions</a>
                <a href="club_records.php" class="active">Club Records</a>
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
            <div class="records-container">
                <h2>Club Records</h2>
                <p>The best scores achieved by archers in each category and round.</p>
                
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
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($records as $record): ?>
                                                    <tr <?php echo ($record['ArcherID'] == $archerId) ? 'class="highlight-row"' : ''; ?>>
                                                        <td><?php echo htmlspecialchars($record['RoundName']); ?></td>
                                                        <td class="score-cell"><?php echo $record['TotalScore']; ?></td>
                                                        <td>
                                                            <?php echo htmlspecialchars($record['FirstName'] . ' ' . $record['LastName']); ?>
                                                            <?php if ($record['ArcherID'] == $archerId): ?>
                                                                <span class="badge badge-you">You</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td><?php echo formatDate($record['DateAchieved']); ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
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
                
                <div class="personal-bests-section">
                    <h3>Your Personal Bests</h3>
                    <p>View your own best scores for each round.</p>
                    <a href="personal_bests.php" class="btn btn-primary">
                        <i class="fas fa-medal"></i> View My Personal Bests
                    </a>
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
    </script>
    
    <style>
        /* Club Records Page Specific Styles */
        .records-container {
            padding: 1rem;
        }
        
        .records-container h2 {
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }
        
        .records-container > p {
            margin-bottom: 1.5rem;
            color: #6b7280;
        }
        
        .filter-section {
            background-color: white;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        .filter-form {
            display: flex;
            align-items: center;
        }
        
        .filter-form .form-group {
            flex: 1;
            margin: 0;
        }
        
        .filter-form label {
            margin-right: 1rem;
            font-weight: 500;
        }
        
        .categories-accordion {
            margin-bottom: 2rem;
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
        
        .highlight-row {
            background-color: rgba(209, 250, 229, 0.3) !important;
        }
        
        .badge-you {
            background-color: var(--primary-color);
            color: white;
            padding: 0.15rem 0.5rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            margin-left: 0.5rem;
        }
        
        .no-records {
            background-color: white;
            border-radius: 8px;
            padding: 3rem 1.5rem;
            text-align: center;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
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
        
        .personal-bests-section {
            background-color: white;
            border-radius: 8px;
            padding: 1.5rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
        }
        
        .personal-bests-section h3 {
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }
        
        .personal-bests-section p {
            margin-bottom: 1rem;
            color: #6b7280;
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .filter-form {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .filter-form label {
                margin-bottom: 0.5rem;
            }
        }
    </style>
</body>
</html>