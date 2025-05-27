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

// Get classes, divisions, and equipment types
$classes = getAllClasses();
$divisions = getAllDivisions();
$equipment = getAllEquipment();

// Process form submission
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstName = isset($_POST['first_name']) ? sanitizeInput($_POST['first_name']) : '';
    $lastName = isset($_POST['last_name']) ? sanitizeInput($_POST['last_name']) : '';
    $gender = isset($_POST['gender']) ? sanitizeInput($_POST['gender']) : '';
    $dob = isset($_POST['dob']) ? sanitizeInput($_POST['dob']) : '';
    $classId = isset($_POST['class_id']) ? intval($_POST['class_id']) : 0;
    $divisionId = isset($_POST['division_id']) ? intval($_POST['division_id']) : 0;
    $equipmentId = isset($_POST['equipment_id']) ? intval($_POST['equipment_id']) : 0;
    
    // Validate input
    $errors = [];
    
    if (empty($firstName)) {
        $errors[] = 'First name is required';
    }
    
    if (empty($lastName)) {
        $errors[] = 'Last name is required';
    }
    
    if (!in_array($gender, ['Male', 'Female'])) {
        $errors[] = 'Gender is required';
    }
    
    if (empty($dob)) {
        $errors[] = 'Date of birth is required';
    }
    
    if ($classId <= 0) {
        $errors[] = 'Class is required';
    }
    
    if ($divisionId <= 0) {
        $errors[] = 'Division is required';
    }
    
    if ($equipmentId <= 0) {
        $errors[] = 'Equipment is required';
    }
    
    if (empty($errors)) {
        // Add archer to database
        $result = addArcher($firstName, $lastName, $gender, $dob, $classId, $divisionId, $equipmentId);
        
        if ($result) {
            $message = 'Archer added successfully.';
            $messageType = 'success';
            
            // Clear form data
            $firstName = $lastName = $gender = $dob = '';
            $classId = $divisionId = $equipmentId = 0;
        } else {
            $message = 'Error adding archer. Please try again.';
            $messageType = 'error';
        }
    } else {
        $message = 'Please fix the following errors: ' . implode(', ', $errors);
        $messageType = 'error';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Archer - Archery Score Recording System</title>
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
                        <a href="add_archer.php" class="active">Add New Archer</a>
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
            <div class="add-archer-container">
                <div class="page-header">
                    <h2><i class="fas fa-user-plus"></i> Add New Archer</h2>
                    <div class="action-buttons">
                        <a href="manage_archers.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back to Archers
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
                    <form method="post" action="add_archer.php" class="archer-form">
                        <div class="form-section">
                            <h3>Personal Information</h3>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="first-name">First Name <span class="required">*</span></label>
                                    <input type="text" id="first-name" name="first_name" class="form-control" value="<?php echo isset($firstName) ? htmlspecialchars($firstName) : ''; ?>" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="last-name">Last Name <span class="required">*</span></label>
                                    <input type="text" id="last-name" name="last_name" class="form-control" value="<?php echo isset($lastName) ? htmlspecialchars($lastName) : ''; ?>" required>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Gender <span class="required">*</span></label>
                                    <div class="radio-group">
                                        <label class="radio-label">
                                            <input type="radio" name="gender" value="Male" <?php echo (isset($gender) && $gender === 'Male') ? 'checked' : ''; ?> required>
                                            Male
                                        </label>
                                        <label class="radio-label">
                                            <input type="radio" name="gender" value="Female" <?php echo (isset($gender) && $gender === 'Female') ? 'checked' : ''; ?> required>
                                            Female
                                        </label>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="dob">Date of Birth <span class="required">*</span></label>
                                    <input type="date" id="dob" name="dob" class="form-control" value="<?php echo isset($dob) ? htmlspecialchars($dob) : ''; ?>" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-section">
                            <h3>Archery Classification</h3>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="class">Class <span class="required">*</span></label>
                                    <select id="class" name="class_id" class="form-control" required>
                                        <option value="">-- Select Class --</option>
                                        <?php foreach ($classes as $class): ?>
                                            <option value="<?php echo $class['ClassID']; ?>" <?php echo (isset($classId) && $classId === $class['ClassID']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($class['ClassName']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label for="division">Division <span class="required">*</span></label>
                                    <select id="division" name="division_id" class="form-control" required>
                                        <option value="">-- Select Division --</option>
                                        <?php foreach ($divisions as $division): ?>
                                            <option value="<?php echo $division['DivisionID']; ?>" <?php echo (isset($divisionId) && $divisionId === $division['DivisionID']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($division['DivisionName']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="equipment">Default Equipment <span class="required">*</span></label>
                                <select id="equipment" name="equipment_id" class="form-control" required>
                                    <option value="">-- Select Equipment --</option>
                                    <?php foreach ($equipment as $eq): ?>
                                        <option value="<?php echo $eq['EquipmentID']; ?>" <?php echo (isset($equipmentId) && $equipmentId === $eq['EquipmentID']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($eq['EquipmentName']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-buttons">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Add Archer
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
            // Auto-select class based on DOB
            const dobInput = document.getElementById('dob');
            const classSelect = document.getElementById('class');
            const genderRadios = document.querySelectorAll('input[name="gender"]');
            
            dobInput.addEventListener('change', function() {
                if (this.value) {
                    // Calculate age
                    const dob = new Date(this.value);
                    const today = new Date();
                    let age = today.getFullYear() - dob.getFullYear();
                    const monthDiff = today.getMonth() - dob.getMonth();
                    
                    if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < dob.getDate())) {
                        age--;
                    }
                    
                    // Get selected gender
                    let gender = null;
                    genderRadios.forEach(radio => {
                        if (radio.checked) {
                            gender = radio.value;
                        }
                    });
                    
                    // Auto-select class based on age and gender
                    if (gender) {
                        let selectedClassId = null;
                        
                        // Find appropriate class
                        const classes = classSelect.options;
                        for (let i = 0; i < classes.length; i++) {
                            const option = classes[i];
                            const className = option.textContent.trim();
                            
                            if (age < 14 && className.includes('Under 14') && className.includes(gender)) {
                                selectedClassId = option.value;
                                break;
                            } else if (age < 16 && className.includes('Under 16') && className.includes(gender)) {
                                selectedClassId = option.value;
                                break;
                            } else if (age < 18 && className.includes('Under 18') && className.includes(gender)) {
                                selectedClassId = option.value;
                                break;
                            } else if (age < 21 && className.includes('Under 21') && className.includes(gender)) {
                                selectedClassId = option.value;
                                break;
                            } else if (age >= 70 && className.includes('70+') && className.includes(gender)) {
                                selectedClassId = option.value;
                                break;
                            } else if (age >= 60 && className.includes('60+') && className.includes(gender)) {
                                selectedClassId = option.value;
                                break;
                            } else if (age >= 50 && className.includes('50+') && className.includes(gender)) {
                                selectedClassId = option.value;
                                break;
                            } else if (className.includes('Open') && className.includes(gender)) {
                                selectedClassId = option.value;
                            }
                        }
                        
                        if (selectedClassId) {
                            classSelect.value = selectedClassId;
                        }
                    }
                }
            });
            
            // Update class when gender is changed
            genderRadios.forEach(radio => {
                radio.addEventListener('change', function() {
                    if (dobInput.value) {
                        // Trigger DOB change event to update class
                        const event = new Event('change');
                        dobInput.dispatchEvent(event);
                    }
                });
            });
        });
    </script>
    
    <style>
        /* Add Archer Page Specific Styles */
        .add-archer-container {
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
        
        .radio-group {
            display: flex;
            gap: 1.5rem;
        }
        
        .radio-label {
            display: flex;
            align-items: center;
            cursor: pointer;
        }
        
        .radio-label input {
            margin-right: 0.5rem;
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