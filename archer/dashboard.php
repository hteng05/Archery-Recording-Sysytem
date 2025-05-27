<?php
require_once '../includes/settings.php';
require_once '../includes/db_functions.php';
// Get all archers for the selection
$archers = getAllArchers();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Archer Dashboard - Archery Score Recording System</title>
    <link rel="stylesheet" href="../css/main.css">
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
        <div class="archer-selection">
            <h2>Welcome to the Archer Dashboard</h2>
            <p>Search for your name to continue:</p>
            <form action="archer_home.php" method="get" class="archer-select-form">
                <div class="form-group">
                    <label for="archer-search">Search Archer:</label>
                    <input type="text" id="archer-search" class="form-control" placeholder="Type name to search..." autocomplete="off">
                </div>
                
                <div id="search-results" class="search-results"></div>
                
                <!-- Hidden input to store selected archer ID -->
                <input type="hidden" id="archer_id" name="archer_id" required>
                
                <button type="submit" class="btn btn-primary" id="continue-btn" disabled>
                    <i class="fas fa-arrow-right"></i> Continue
                </button>
            </form>
            <div class="back-link">
                <a href="../index.php">
                    <i class="fas fa-arrow-left"></i> Back to Home
                </a>
            </div>
        </div>
        <footer>
            <p>&copy; 2025 Archery Club Database System. All rights reserved.</p>
        </footer>
    </div>
    <style>
        .archer-selection {
            max-width: 600px;
            margin: 3rem auto;
            padding: 2rem;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .archer-selection h2 {
            color: var(--primary-color);
            margin-bottom: 1rem;
            text-align: center;
        }
        .archer-selection p {
            margin-bottom: 2rem;
            text-align: center;
        }
        .archer-select-form {
            margin-bottom: 2rem;
        }
        .back-link {
            text-align: center;
            margin-top: 1rem;
        }
        .back-link a {
            color: var(--primary-color);
            text-decoration: none;
        }
        .back-link a:hover {
            text-decoration: underline;
        }
        /* New styles for search functionality */
        .search-results {
            max-height: 200px;
            overflow-y: auto;
            margin-top: 10px;
            margin-bottom: 20px;
            border: 1px solid #ddd;
            border-radius: 4px;
            display: none;
        }
        .search-results.active {
            display: block;
        }
        .archer-item {
            padding: 10px 15px;
            cursor: pointer;
            border-bottom: 1px solid #eee;
        }
        .archer-item:hover, .archer-item.selected {
            background-color: #f0f8ff;
        }
        .archer-item:last-child {
            border-bottom: none;
        }
        .selected-archer {
            margin-top: 10px;
            padding: 10px;
            background-color: #f0f8ff;
            border-radius: 4px;
            display: none;
        }
        .selected-archer.active {
            display: block;
        }
    </style>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Convert PHP array to JavaScript array
            const archers = <?php echo json_encode($archers); ?>;
            const searchInput = document.getElementById('archer-search');
            const searchResults = document.getElementById('search-results');
            const archerIdInput = document.getElementById('archer_id');
            const continueBtn = document.getElementById('continue-btn');
            
            // Function to display search results
            function displayResults(query) {
                // Clear previous results
                searchResults.innerHTML = '';
                
                if (query.length < 2) {
                    searchResults.classList.remove('active');
                    return;
                }
                
                // Filter archers based on search query
                const filteredArchers = archers.filter(archer => {
                    const fullName = `${archer.FirstName} ${archer.LastName}`.toLowerCase();
                    return fullName.includes(query.toLowerCase());
                });
                
                // Display results
                if (filteredArchers.length > 0) {
                    searchResults.classList.add('active');
                    
                    filteredArchers.forEach(archer => {
                        const archerItem = document.createElement('div');
                        archerItem.className = 'archer-item';
                        archerItem.textContent = `${archer.FirstName} ${archer.LastName}`;
                        archerItem.dataset.id = archer.ArcherID;
                        
                        archerItem.addEventListener('click', function() {
                            // Remove selected class from all items
                            document.querySelectorAll('.archer-item').forEach(item => {
                                item.classList.remove('selected');
                            });
                            
                            // Add selected class to clicked item
                            this.classList.add('selected');
                            
                            // Set the selected archer ID and name
                            archerIdInput.value = this.dataset.id;
                            searchInput.value = this.textContent;
                            
                            // Enable continue button
                            continueBtn.disabled = false;
                            
                            // Hide search results
                            searchResults.classList.remove('active');
                        });
                        
                        searchResults.appendChild(archerItem);
                    });
                } else {
                    searchResults.classList.add('active');
                    searchResults.innerHTML = '<div class="archer-item">No archers found</div>';
                }
            }
            
            // Search input event listener
            searchInput.addEventListener('input', function() {
                displayResults(this.value);
                // Reset selection when input changes
                archerIdInput.value = '';
                continueBtn.disabled = true;
            });
            
            // Close results when clicking outside
            document.addEventListener('click', function(e) {
                if (e.target !== searchInput && e.target !== searchResults && !searchResults.contains(e.target)) {
                    searchResults.classList.remove('active');
                }
            });
            
            // Prevent form submission if no archer is selected
            document.querySelector('.archer-select-form').addEventListener('submit', function(e) {
                if (!archerIdInput.value) {
                    e.preventDefault();
                    alert('Please select an archer to continue');
                }
            });
        });
    </script>
</body>
</html>