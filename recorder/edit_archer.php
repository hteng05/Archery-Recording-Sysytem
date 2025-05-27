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

// Get archer ID from URL
$archerId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($archerId === 0) {
    header('Location: manage_archers.php');
    exit;
}

// Get archer data
$archer = getArcherById($archerId);

if (!$archer) {
    header('Location: manage_archers.php');
    exit;
}

// Get all classes, divisions, and equipment for dropdowns
$classes = getAllClasses();
$divisions = getAllDivisions();
$equipment = getAllEquipment();

// Process form submission
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstName = trim($_POST['first_name']);
    $lastName = trim($_POST['last_name']);
    $gender = $_POST['gender'];
    $dob = $_POST['dob'];
    $classId = intval($_POST['class_id']);
    $divisionId = intval($_POST['division_id']);
    $equipmentId = intval($_POST['equipment_id']);
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    
    // Basic validation
    if (empty($firstName) || empty($lastName) || empty($gender) || empty($dob) || 
        $classId === 0 || $divisionId === 0 || $equipmentId === 0) {
        $message = 'Please fill in all required fields.';
        $messageType = 'error';
    } else {
        // Validate date format
        $dateObj = DateTime::createFromFormat('Y-m-d', $dob);
        if (!$dateObj || $dateObj->format('Y-m-d') !== $dob) {
            $message = 'Please enter a valid date of birth.';
            $messageType = 'error';
        } else {
            // Update archer in database
            $conn = getDbConnection();
            if ($conn) {
                $stmt = $conn->prepare("UPDATE ArcherTable SET FirstName = ?, LastName = ?, Gender = ?, DOB = ?, 
                                      ClassID = ?, DefaultDivisionID = ?, DefaultEquipmentID = ?, IsActive = ? 
                                      WHERE ArcherID = ?");
                $stmt->bind_param("ssssiiiii", $firstName, $lastName, $gender, $dob, $classId, 
                                $divisionId, $equipmentId, $isActive, $archerId);
                
                if ($stmt->execute()) {
                    $message = 'Archer updated successfully.';
                    $messageType = 'success';
                    
                    // Refresh archer data
                    $archer = getArcherById($archerId);
                } else {
                    $message = 'Error updating archer. Please try again.';
                    $messageType = 'error';
                }
            } else {
                $message = 'Database connection failed. Please try again.';
                $messageType = 'error';
            }
        }
    }
}

// Calculate archer's age for class validation
function calculateAge($birthDate) {
    $today = new DateTime();
    $age = $today->diff(new DateTime($birthDate));
    return $age->y;
}

$archerAge = calculateAge($archer['DOB']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Archer - Archery Score Recording System</title>
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
                    <a href="#" class="dropdown-toggle active">Archers</a>
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
            <div class="edit-archer-container">
                <div class="page-header">
                    <div>
                        <h2><i class="fas fa-user-edit"></i> Edit Archer</h2>
                        <p class="page-subtitle">Editing: <?php echo htmlspecialchars($archer['FirstName'] . ' ' . $archer['LastName']); ?></p>
                    </div>
                    <div class="action-buttons">
                        <a href="manage_archers.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back to Archers
                        </a>
                        <a href="view_archer_scores.php?id=<?php echo $archerId; ?>" class="btn btn-primary">
                            <i class="fas fa-chart-line"></i> View Scores
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
                    <form method="POST" class="archer-form">
                        <div class="form-section">
                            <h3><i class="fas fa-user"></i> Personal Information</h3>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="first_name">First Name *</label>
                                    <input type="text" id="first_name" name="first_name" class="form-control" 
                                           value="<?php echo htmlspecialchars($archer['FirstName']); ?>" required>
                                </div>
                                <div class="form-group">
                                    <label for="last_name">Last Name *</label>
                                    <input type="text" id="last_name" name="last_name" class="form-control" 
                                           value="<?php echo htmlspecialchars($archer['LastName']); ?>" required>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="gender">Gender *</label>
                                    <select id="gender" name="gender" class="form-control" required>
                                        <option value="">Select Gender</option>
                                        <option value="Male" <?php echo $archer['Gender'] === 'Male' ? 'selected' : ''; ?>>Male</option>
                                        <option value="Female" <?php echo $archer['Gender'] === 'Female' ? 'selected' : ''; ?>>Female</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="dob">Date of Birth *</label>
                                    <input type="date" id="dob" name="dob" class="form-control" 
                                           value="<?php echo $archer['DOB']; ?>" required>
                                    <small class="form-help">Current age: <?php echo $archerAge; ?> years</small>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-section">
                            <h3><i class="fas fa-tag"></i> Classification</h3>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="class_id">Class *</label>
                                    <select id="class_id" name="class_id" class="form-control" required>
                                        <option value="">Select Class</option>
                                        <?php foreach ($classes as $class): ?>
                                            <option value="<?php echo $class['ClassID']; ?>" 
                                                    <?php echo $class['ClassID'] == $archer['ClassID'] ? 'selected' : ''; ?>
                                                    data-gender="<?php echo $class['Gender']; ?>"
                                                    data-min-age="<?php echo $class['MinAge']; ?>"
                                                    data-max-age="<?php echo $class['MaxAge']; ?>">
                                                <?php echo htmlspecialchars($class['ClassName']); ?>
                                                (<?php echo $class['Gender']; ?>, <?php echo $class['MinAge']; ?>-<?php echo $class['MaxAge']; ?> years)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="division_id">Division *</label>
                                    <select id="division_id" name="division_id" class="form-control" required>
                                        <option value="">Select Division</option>
                                        <?php foreach ($divisions as $division): ?>
                                            <option value="<?php echo $division['DivisionID']; ?>" 
                                                    <?php echo $division['DivisionID'] == $archer['DefaultDivisionID'] ? 'selected' : ''; ?>
                                                    data-gender="<?php echo $division['Gender']; ?>">
                                                <?php echo htmlspecialchars($division['DivisionName']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="equipment_id">Default Equipment *</label>
                                    <select id="equipment_id" name="equipment_id" class="form-control" required>
                                        <option value="">Select Equipment</option>
                                        <?php foreach ($equipment as $equip): ?>
                                            <option value="<?php echo $equip['EquipmentID']; ?>" 
                                                    <?php echo $equip['EquipmentID'] == $archer['DefaultEquipmentID'] ? 'selected' : ''; ?>
                                                    data-division="<?php echo $equip['DivisionName']; ?>">
                                                <?php echo htmlspecialchars($equip['EquipmentName']); ?>
                                                (<?php echo htmlspecialchars($equip['DivisionName']); ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="is_active" class="checkbox-label">
                                        <input type="checkbox" id="is_active" name="is_active" 
                                               <?php echo $archer['IsActive'] ? 'checked' : ''; ?>>
                                        Active Archer
                                    </label>
                                    <small class="form-help">Uncheck to deactivate this archer</small>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-actions">
                            <button type="button" class="btn btn-secondary" onclick="window.location.href='manage_archers.php'">
                                <i class="fas fa-times"></i> Cancel
                            </button>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Save Changes
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
            const genderSelect = document.getElementById('gender');
            const dobInput = document.getElementById('dob');
            const classSelect = document.getElementById('class_id');
            const divisionSelect = document.getElementById('division_id');
            const equipmentSelect = document.getElementById('equipment_id');
            
            // Function to calculate age
            function calculateAge(birthDate) {
                const today = new Date();
                const birth = new Date(birthDate);
                let age = today.getFullYear() - birth.getFullYear();
                const monthDiff = today.getMonth() - birth.getMonth();
                
                if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birth.getDate())) {
                    age--;
                }
                
                return age;
            }
            
            // Function to filter classes based on gender and age
            function filterClasses() {
                const selectedGender = genderSelect.value;
                const selectedDOB = dobInput.value;
                
                if (!selectedGender || !selectedDOB) return;
                
                const age = calculateAge(selectedDOB);
                
                Array.from(classSelect.options).forEach(option => {
                    if (option.value === '') return; // Skip the default option
                    
                    const optionGender = option.dataset.gender;
                    const minAge = parseInt(option.dataset.minAge);
                    const maxAge = parseInt(option.dataset.maxAge);
                    
                    const isValidGender = optionGender === selectedGender;
                    const isValidAge = age >= minAge && age <= maxAge;
                    
                    if (isValidGender && isValidAge) {
                        option.style.display = '';
                        option.disabled = false;
                    } else {
                        option.style.display = 'none';
                        option.disabled = true;
                    }
                });
                
                // If current selection is no longer valid, clear it
                const currentOption = classSelect.options[classSelect.selectedIndex];
                if (currentOption && currentOption.disabled) {
                    classSelect.value = '';
                }
            }
            
            // Function to filter divisions based on gender
            function filterDivisions() {
                const selectedGender = genderSelect.value;
                
                if (!selectedGender) return;
                
                Array.from(divisionSelect.options).forEach(option => {
                    if (option.value === '') return; // Skip the default option
                    
                    const optionGender = option.dataset.gender;
                    
                    if (optionGender === selectedGender) {
                        option.style.display = '';
                        option.disabled = false;
                    } else {
                        option.style.display = 'none';
                        option.disabled = true;
                    }
                });
                
                // If current selection is no longer valid, clear it
                const currentOption = divisionSelect.options[divisionSelect.selectedIndex];
                if (currentOption && currentOption.disabled) {
                    divisionSelect.value = '';
                }
            }
            
            // Function to filter equipment based on selected division
            function filterEquipment() {
                const selectedDivisionOption = divisionSelect.options[divisionSelect.selectedIndex];
                
                if (!selectedDivisionOption || selectedDivisionOption.value === '') {
                    // Show all equipment if no division selected
                    Array.from(equipmentSelect.options).forEach(option => {
                        option.style.display = '';
                        option.disabled = false;
                    });
                    return;
                }
                
                const selectedDivisionName = selectedDivisionOption.textContent.trim();
                
                Array.from(equipmentSelect.options).forEach(option => {
                    if (option.value === '') return; // Skip the default option
                    
                    const equipmentDivision = option.dataset.division;
                    
                    if (equipmentDivision === selectedDivisionName) {
                        option.style.display = '';
                        option.disabled = false;
                    } else {
                        option.style.display = 'none';
                        option.disabled = true;
                    }
                });
                
                // If current selection is no longer valid, clear it
                const currentOption = equipmentSelect.options[equipmentSelect.selectedIndex];
                if (currentOption && currentOption.disabled) {
                    equipmentSelect.value = '';
                }
            }
            
            // Event listeners
            genderSelect.addEventListener('change', function() {
                filterClasses();
                filterDivisions();
                filterEquipment();
            });
            
            dobInput.addEventListener('change', function() {
                filterClasses();
                // Update age display
                if (this.value) {
                    const age = calculateAge(this.value);
                    const helpText = this.parentNode.querySelector('.form-help');
                    helpText.textContent = `Current age: ${age} years`;
                }
            });
            
            divisionSelect.addEventListener('change', filterEquipment);
            
            // Initial filtering based on current values
            filterClasses();
            filterDivisions();
            filterEquipment();
        });
    </script>
    
    <style>
        /* Edit Archer Page Specific Styles */
        .edit-archer-container {
            padding: 1rem;
            max-width: 1000px;
            margin: 0 auto;
        }
        
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1.5rem;
        }
        
        .page-header h2 {
            color: var(--primary-color);
            margin: 0;
        }
        
        .page-header h2 i {
            margin-right: 0.5rem;
        }
        
        .page-subtitle {
            color: #6b7280;
            margin: 0.25rem 0 0 0;
            font-size: 0.9rem;
        }
        
        .form-container {
            background-color: white;
            border-radius: 8px;
            padding: 2rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        .form-section {
            margin-bottom: 2rem;
        }
        
        .form-section:last-of-type {
            margin-bottom: 1rem;
        }
        
        .form-section h3 {
            color: var(--primary-color);
            margin: 0 0 1rem 0;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid var(--light-color);
        }
        
        .form-section h3 i {
            margin-right: 0.5rem;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-bottom: 1rem;
        }
        
        .form-group {
            display: flex;
            flex-direction: column;
        }
        
        .form-group label {
            font-weight: 600;
            margin-bottom: 0.25rem;
            color: #374151;
        }
        
        .checkbox-label {
            flex-direction: row;
            align-items: center;
            margin-top: 1.5rem;
        }
        
        .checkbox-label input {
            margin-right: 0.5rem;
            margin-bottom: 0;
        }
        
        .form-control {
            padding: 0.5rem;
            border: 1px solid #d1d5db;
            border-radius: 4px;
            font-size: 1rem;
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        
        .form-help {
            font-size: 0.8rem;
            color: #6b7280;
            margin-top: 0.25rem;
        }
        
        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
            margin-top: 2rem;
            padding-top: 1rem;
            border-top: 1px solid var(--gray-color);
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
                gap: 1rem;
            }
            
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .form-actions {
                flex-direction: column;
            }
            
            .action-buttons {
                display: flex;
                flex-direction: column;
                gap: 0.5rem;
            }
        }
    </style>
</body>
</html>