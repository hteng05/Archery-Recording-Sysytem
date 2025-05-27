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

// Get all rounds
$rounds = getAllRounds();

// Get equipment types
$equipment = getAllEquipment();

// Get active competitions
$competitions = getAllCompetitions(false);

// Process form submission
$message = '';
$messageType = '';

// Check if this is an AJAX request for round details
if (isset($_GET['action']) && $_GET['action'] === 'get_round_details' && isset($_GET['round_id'])) {
    $roundId = intval($_GET['round_id']);
    $round = getRoundById($roundId);
    
    if ($round) {
        // Return round details as JSON
        header('Content-Type: application/json');
        echo json_encode($round);
        exit;
    } else {
        // Return error as JSON
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Round not found']);
        exit;
    }
}

// Only process form submission when the submit button is clicked
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_form'])) {
    $roundId = isset($_POST['round_id']) ? intval($_POST['round_id']) : 0;
    $equipmentId = isset($_POST['equipment_id']) ? intval($_POST['equipment_id']) : 0;
    $date = isset($_POST['date']) ? sanitizeInput($_POST['date']) : date('Y-m-d');
    $time = isset($_POST['time']) ? sanitizeInput($_POST['time']) : date('H:i:s');
    $isPractice = isset($_POST['is_practice']) ? 1 : 0;
    $isCompetition = $isPractice ? 0 : 1;
    $competitionId = ($isCompetition && isset($_POST['competition_id'])) ? intval($_POST['competition_id']) : null;
    
    // Get additional scoring data if this is from the scoring interface
    $totalScore = isset($_POST['total_score']) ? intval($_POST['total_score']) : null;
    $arrowScores = null;
    
    if (isset($_POST['arrow_scores'])) {
        $arrowScoresJson = $_POST['arrow_scores'];
        $arrowScores = json_decode($arrowScoresJson, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            $message = 'Error processing arrow scores data.';
            $messageType = 'error';
            $arrowScores = null;
        }
    }
    
    if ($roundId <= 0 || $equipmentId <= 0) {
        $message = 'Please select both a round and equipment type.';
        $messageType = 'error';
    } else {
        // Add to staging table with detailed arrow scores
        $stageId = addStagedScore($archerId, $roundId, $equipmentId, $date, $time, $isPractice, $isCompetition, $competitionId, $arrowScores, $totalScore);
        
        if ($stageId) {
            if ($arrowScores && count($arrowScores) > 0) {
                $message = 'Score with ' . count($arrowScores) . ' arrows has been staged successfully! A recorder will review and approve it.';
            } else {
                $message = 'Score setup has been staged successfully! A recorder will review and approve it.';
            }
            $messageType = 'success';
        } else {
            $message = 'Error adding score to staging. Please try again.';
            $messageType = 'error';
        }
    }
}

// Get selected round details (for JavaScript)
$selectedRoundId = isset($_POST['round_id']) ? intval($_POST['round_id']) : 0;
$selectedRound = null;

// If round ID is provided, fetch the details
if ($selectedRoundId > 0) {
    $selectedRound = getRoundById($selectedRoundId);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enter Score - Archery Score Recording System</title>
    <link rel="stylesheet" href="../css/main.css">
    <link rel="stylesheet" href="../css/scores.css">
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
                <a href="enter_score.php" class="active">Enter Score</a>
                <a href="rounds_info.php">Rounds Info</a>
                <a href="competitions.php">Competitions</a>
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
            <div class="score-entry-container">
                <h2>Enter New Score</h2>
                
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
                
                <div class="score-setup">
                    <h3>Score Setup</h3>
                    <form id="score-setup-form" method="post" action="enter_score.php">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="round">Round:</label>
                                <select id="round" name="round_id" class="form-control" required>
                                    <option value="">-- Select Round --</option>
                                    <?php foreach ($rounds as $round): ?>
                                        <option value="<?php echo $round['RoundID']; ?>">
                                            <?php echo htmlspecialchars($round['RoundName']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="equipment">Equipment:</label>
                                <select id="equipment" name="equipment_id" class="form-control" required>
                                    <option value="">-- Select Equipment --</option>
                                    <option value="<?php echo $archer['DefaultEquipmentID']; ?>" selected>
                                        <?php echo htmlspecialchars($archer['EquipmentName'] . ' (Default)'); ?>
                                    </option>
                                    <?php foreach ($equipment as $eq): ?>
                                        <?php if ($eq['EquipmentID'] !== $archer['DefaultEquipmentID']): ?>
                                            <option value="<?php echo $eq['EquipmentID']; ?>">
                                                <?php echo htmlspecialchars($eq['EquipmentName']); ?>
                                            </option>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="date">Date:</label>
                                <input type="date" id="date" name="date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="time">Time:</label>
                                <input type="time" id="time" name="time" class="form-control" value="<?php echo date('H:i'); ?>" required>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <div class="checkbox-group">
                                <input type="checkbox" id="is_practice" name="is_practice" checked>
                                <label for="is_practice">This is a practice score (not part of a competition)</label>
                            </div>
                        </div>
                        
                        <div id="competition-section" class="form-group hidden">
                            <label for="competition">Competition:</label>
                            <select id="competition" name="competition_id" class="form-control">
                                <option value="">-- Select Competition --</option>
                                <?php foreach ($competitions as $competition): ?>
                                    <option value="<?php echo $competition['CompetitionID']; ?>">
                                        <?php echo htmlspecialchars($competition['CompetitionName'] . ' (' . formatDate($competition['StartDate']) . ')'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div id="round-details-section" class="round-details hidden">
                            <h4>Round Details:</h4>
                            <p><strong>Total Arrows:</strong> <span id="total-arrows">-</span></p>
                            <ul id="ranges-list" class="ranges-list">
                                <!-- Ranges will be populated by JavaScript -->
                            </ul>
                        </div>
                        
                        <div class="form-buttons">
                            <button type="button" id="start-scoring" class="btn btn-primary hidden">
                                <i class="fas fa-bullseye"></i> Start Scoring
                            </button>
                            
                            <!-- Hidden input to indicate basic form submission -->
                            <input type="hidden" name="submit_form" value="1">
                            
                            <button type="submit" class="btn btn-secondary">
                                <i class="fas fa-check"></i> Submit Setup Only
                            </button>
                        </div>
                    </form>
                </div>
                
                <div id="score-entry" class="score-entry hidden">
                    <div id="score-entry-header">
                        <h3>Enter Scores</h3>
                        <button type="button" id="back-to-setup" class="btn btn-sm btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back to Setup
                        </button>
                    </div>
                    
                    <div class="round-info">
                        <h3 id="current-range-info">Loading...</h3>
                        <p id="current-range-details"></p>
                    </div>
                    
                    <div class="end-counter">
                        End <span id="current-end">1</span> of <span id="total-ends">6</span>
                    </div>
                    
                    <form id="score-entry-form">
                        <div class="arrows-container" id="arrows-container">
                            <!-- Arrow inputs will be generated dynamically -->
                        </div>
                        
                        <div class="end-summary">
                            <h3>End Total: <span id="end-total">0</span></h3>
                        </div>
                        
                        <div class="running-total">
                            <h3>Running Total</h3>
                            <div class="total-score" id="running-total">0</div>
                        </div>
                        
                        <div class="score-keypad">
                            <button type="button" class="score-key" data-value="X">X</button>
                            <button type="button" class="score-key" data-value="10">10</button>
                            <button type="button" class="score-key" data-value="9">9</button>
                            <button type="button" class="score-key" data-value="8">8</button>
                            <button type="button" class="score-key" data-value="7">7</button>
                            <button type="button" class="score-key" data-value="6">6</button>
                            <button type="button" class="score-key" data-value="5">5</button>
                            <button type="button" class="score-key" data-value="4">4</button>
                            <button type="button" class="score-key" data-value="3">3</button>
                            <button type="button" class="score-key" data-value="2">2</button>
                            <button type="button" class="score-key" data-value="1">1</button>
                            <button type="button" class="score-key miss-key" data-value="M">M</button>
                            <button type="button" class="score-key clear-key" data-value="clear">Clear</button>
                        </div>
                        
                        <div class="end-navigation">
                            <button type="button" id="prev-end" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Previous End
                            </button>
                            <button type="button" id="next-end" class="btn btn-primary">
                                Next End <i class="fas fa-arrow-right"></i>
                            </button>
                        </div>
                        
                        <div class="score-submission hidden">
                            <h3>Score Submission</h3>
                            <p>You have completed all ends for this round. Please review your scores before submitting.</p>
                            <p class="final-score">Final Score: <strong id="final-score">0</strong></p>
                            <div class="submission-buttons">
                                <button type="button" id="submit-score" class="btn btn-primary">
                                    <i class="fas fa-paper-plane"></i> Submit Complete Score
                                </button>
                            </div>
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
    <script src="../js/scores.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
    // Toggle competition section based on practice checkbox
    const isPracticeCheckbox = document.getElementById('is_practice');
    const competitionSection = document.getElementById('competition-section');
    
    isPracticeCheckbox.addEventListener('change', function() {
        competitionSection.classList.toggle('hidden', this.checked);
    });
    
    // Round details fetching with AJAX
    const roundSelect = document.getElementById('round');
    const roundDetailsSection = document.getElementById('round-details-section');
    const totalArrowsElement = document.getElementById('total-arrows');
    const rangesListElement = document.getElementById('ranges-list');
    const startScoringBtn = document.getElementById('start-scoring');
    
    // Define window.selectedRound globally so it's accessible
    window.selectedRound = null;
    
    roundSelect.addEventListener('change', function() {
        const roundId = this.value;
        
        if (roundId) {
            // Show loading indicator
            totalArrowsElement.textContent = 'Loading...';
            roundDetailsSection.classList.remove('hidden');
            
            // Use fetch API to get round details
            fetch(`enter_score.php?action=get_round_details&round_id=${roundId}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    console.log('Round data received:', data);
                    
                    if (data.error) {
                        console.error('Error:', data.error);
                        roundDetailsSection.classList.add('hidden');
                        alert('Error loading round details: ' + data.error);
                        return;
                    }
                    
                    // Store round details globally
                    window.selectedRound = data;
                    
                    // Update round details on the page
                    totalArrowsElement.textContent = data.TotalArrows || '0';
                    
                    // Clear existing ranges
                    rangesListElement.innerHTML = '';
                    
                    // Add each range
                    if (data.ranges && data.ranges.length > 0) {
                        data.ranges.forEach(range => {
                            const li = document.createElement('li');
                            li.textContent = `${range.Distance}m, ${range.NumberOfEnds} ends, ${range.ArrowsPerEnd} arrows per end, ${range.TargetFaceSize}cm face`;
                            rangesListElement.appendChild(li);
                        });
                    } else {
                        const li = document.createElement('li');
                        li.textContent = 'No range details available';
                        rangesListElement.appendChild(li);
                    }
                    
                    // Show round details and start scoring button
                    roundDetailsSection.classList.remove('hidden');
                    startScoringBtn.classList.remove('hidden');
                })
                .catch(error => {
                    console.error('Fetch error:', error);
                    alert('Error loading round details. Please try again.');
                    roundDetailsSection.classList.add('hidden');
                });
        } else {
            // Hide round details and start scoring button if no round selected
            roundDetailsSection.classList.add('hidden');
            startScoringBtn.classList.add('hidden');
        }
    });
    
    // Start scoring button
    const scoreSetup = document.querySelector('.score-setup');
    const scoreEntry = document.getElementById('score-entry');
    
    startScoringBtn.addEventListener('click', function() {
        if (!window.selectedRound) {
            alert('Please select a round first');
            return;
        }
        
        scoreSetup.classList.add('hidden');
        scoreEntry.classList.remove('hidden');
        
        // Initialize scoring interface
        initializeScoring();
    });
    
    // Back to setup button
    const backToSetupBtn = document.getElementById('back-to-setup');
    
    backToSetupBtn.addEventListener('click', function() {
        if (confirm('Going back will reset your current score entry. Continue?')) {
            scoreEntry.classList.add('hidden');
            scoreSetup.classList.remove('hidden');
        }
    });
    
    // Initialize scoring interface
    function initializeScoring() {
        console.log('Initializing scoring with round data:', window.selectedRound);
        
        // Check if we have round details
        if (!window.selectedRound || !window.selectedRound.ranges || !window.selectedRound.ranges.length) {
            alert('Round details not available. Please select a round.');
            scoreEntry.classList.add('hidden');
            scoreSetup.classList.remove('hidden');
            return;
        }
        
        // Set up variables
        const ranges = window.selectedRound.ranges;
        let currentRangeIndex = 0;
        let currentEnd = 1;
        let totalScore = 0;
        let scoresByEnd = {}; // Changed to object for clearer key-value storage
        
        // Get elements
        const currentRangeInfo = document.getElementById('current-range-info');
        const currentRangeDetails = document.getElementById('current-range-details');
        const currentEndEl = document.getElementById('current-end');
        const totalEndsEl = document.getElementById('total-ends');
        const arrowsContainer = document.getElementById('arrows-container');
        const endTotalEl = document.getElementById('end-total');
        const runningTotalEl = document.getElementById('running-total');
        const prevEndBtn = document.getElementById('prev-end');
        const nextEndBtn = document.getElementById('next-end');
        const finalScoreEl = document.getElementById('final-score');
        const submitScoreBtn = document.getElementById('submit-score');
        const scoreSubmissionSection = document.querySelector('.score-submission');
        
        // Reset UI state
        scoreSubmissionSection.classList.add('hidden');
        nextEndBtn.disabled = false;
        prevEndBtn.disabled = currentEnd === 1;
        
        // Set initial range info
        updateRangeInfo();
        
        // Create arrow inputs
        createArrowInputs();
        
        // Set up navigation buttons
        prevEndBtn.addEventListener('click', goToPreviousEnd);
        nextEndBtn.addEventListener('click', goToNextEnd);
        
        // Set up score submission
        submitScoreBtn.addEventListener('click', submitFinalScore);
        
        // Set up keypad
        setupKeypad();
        
        function updateRangeInfo() {
            const range = ranges[currentRangeIndex];
            if (!range) {
                console.error('No range found at index', currentRangeIndex);
                return;
            }
            
            currentRangeInfo.textContent = `${range.Distance}m - ${range.TargetFaceSize}cm Face`;
            currentRangeDetails.textContent = `${range.NumberOfEnds} ends × ${range.ArrowsPerEnd} arrows per end`;
            
            totalEndsEl.textContent = range.NumberOfEnds;
            currentEndEl.textContent = currentEnd;
            
            // Update button states
            prevEndBtn.disabled = currentEnd === 1 && currentRangeIndex === 0;
        }
        
        function createArrowInputs() {
            const range = ranges[currentRangeIndex];
            if (!range) {
                console.error('Cannot create arrow inputs: No range found at index', currentRangeIndex);
                return;
            }
            
            const arrowsPerEnd = range.ArrowsPerEnd;
            console.log(`Creating ${arrowsPerEnd} arrow inputs for range ${currentRangeIndex}, end ${currentEnd}`);
            
            // Clear container
            arrowsContainer.innerHTML = '';
            
            // Create inputs
            for (let i = 0; i < arrowsPerEnd; i++) {
                // Create input container
                const inputContainer = document.createElement('div');
                inputContainer.className = 'arrow-input-container';
                
                // Create label
                const label = document.createElement('span');
                label.className = 'arrow-label';
                label.textContent = `Arrow ${i + 1}:`;
                
                // Create input
                const input = document.createElement('input');
                input.type = 'text';
                input.className = 'arrow-input';
                input.maxLength = 2;
                input.dataset.index = i;
                input.readOnly = true; // Make it read-only to force keypad usage
                
                // Add click handler to focus the input
                input.addEventListener('click', function() {
                    // Mark this as the active input
                    document.querySelectorAll('.arrow-input').forEach(inp => {
                        inp.classList.remove('active-input');
                    });
                    this.classList.add('active-input');
                });
                
                // Append elements to container
                inputContainer.appendChild(label);
                inputContainer.appendChild(input);
                arrowsContainer.appendChild(inputContainer);
            }
            
            // Focus first input by simulating a click
            const firstInput = arrowsContainer.querySelector('.arrow-input');
            if (firstInput) {
                firstInput.click();
            }
        }
        
        function validateArrowInput(input, value) {
            const validScores = ['X', '10', '9', '8', '7', '6', '5', '4', '3', '2', '1', 'M', ''];
            
            if (validScores.includes(value)) {
                input.classList.remove('error');
                input.value = value;
                return true;
            } else {
                input.classList.add('error');
                return false;
            }
        }
        
        function updateEndTotal() {
            const inputs = arrowsContainer.querySelectorAll('.arrow-input');
            let endTotal = 0;
            
            inputs.forEach(input => {
                const value = input.value.toUpperCase();
                
                if (value === 'X') {
                    endTotal += 10;
                } else if (value === 'M') {
                    endTotal += 0;
                } else if (value !== '') {
                    endTotal += parseInt(value) || 0;
                }
            });
            
            endTotalEl.textContent = endTotal;
            
            // Update running total after updating the end total
            updateRunningTotal();
        }
        
        function goToPreviousEnd() {
            // Save current end scores
            saveCurrentEndScores();
            
            // Go to previous end
            if (currentEnd > 1) {
                currentEnd--;
                currentEndEl.textContent = currentEnd;
                loadEndScores();
            } else if (currentRangeIndex > 0) {
                // Go to previous range, last end
                currentRangeIndex--;
                const range = ranges[currentRangeIndex];
                currentEnd = range.NumberOfEnds;
                updateRangeInfo();
                createArrowInputs();
                loadEndScores();
            }
            
            // Hide submission section when navigating
            scoreSubmissionSection.classList.add('hidden');
            nextEndBtn.disabled = false;
        }
        
        function goToNextEnd() {
            // Validate all arrows are entered
            if (!validateAllArrowsEntered()) {
                alert('Please enter scores for all arrows in this end before continuing.');
                return;
            }
            
            // Save current end scores
            saveCurrentEndScores();
            
            // Go to next end
            const range = ranges[currentRangeIndex];
            
            if (currentEnd < range.NumberOfEnds) {
                currentEnd++;
                currentEndEl.textContent = currentEnd;
                loadEndScores();
            } else if (currentRangeIndex < ranges.length - 1) {
                // Go to next range, first end
                currentRangeIndex++;
                currentEnd = 1;
                updateRangeInfo();
                createArrowInputs();
                loadEndScores();
            } else {
                // End of round - show submission section
                scoreSubmissionSection.classList.remove('hidden');
                nextEndBtn.disabled = true;
                // Update final score
                finalScoreEl.textContent = totalScore;
            }
        }
        
        function validateAllArrowsEntered() {
            const inputs = arrowsContainer.querySelectorAll('.arrow-input');
            let allEntered = true;
            
            inputs.forEach(input => {
                if (input.value === '') {
                    allEntered = false;
                    input.classList.add('error');
                }
            });
            
            return allEntered;
        }
        
        function saveCurrentEndScores() {
            const inputs = arrowsContainer.querySelectorAll('.arrow-input');
            const endScores = [];
            
            inputs.forEach(input => {
                endScores.push(input.value.toUpperCase());
            });
            
            const key = `range_${currentRangeIndex}_end_${currentEnd}`;
            scoresByEnd[key] = endScores;
            
            console.log(`Saved scores for ${key}:`, endScores);
        }
        
        function loadEndScores() {
            const key = `range_${currentRangeIndex}_end_${currentEnd}`;
            const endScores = scoresByEnd[key] || [];
            const inputs = arrowsContainer.querySelectorAll('.arrow-input');
            
            console.log(`Loading scores for ${key}:`, endScores);
            
            inputs.forEach((input, index) => {
                input.value = endScores[index] || '';
                input.classList.remove('error');
            });
            
            // Focus the first empty input, or the last input if all are filled
            let focusSet = false;
            inputs.forEach(input => {
                if (!focusSet && input.value === '') {
                    input.click(); // Simulate click to focus
                    focusSet = true;
                }
            });
            
            if (!focusSet && inputs.length > 0) {
                inputs[inputs.length - 1].click(); // Focus the last input
            }
            
            updateEndTotal();
        }
        
        function updateRunningTotal() {
            let total = 0;
            
            for (const key in scoresByEnd) {
                const scores = scoresByEnd[key];
                
                scores.forEach(score => {
                    if (score === 'X') {
                        total += 10;
                    } else if (score === 'M') {
                        total += 0;
                    } else if (score !== '') {
                        total += parseInt(score) || 0;
                    }
                });
            }
            
            totalScore = total;
            runningTotalEl.textContent = total;
            finalScoreEl.textContent = total;
        }
        
        function setupKeypad() {
            const keypadButtons = document.querySelectorAll('.score-key');
            
            keypadButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const value = this.dataset.value;
                    const activeInput = document.querySelector('.arrow-input.active-input');
                    
                    if (activeInput) {
                        if (value === 'clear') {
                            activeInput.value = '';
                        } else {
                            if (validateArrowInput(activeInput, value)) {
                                // Move to next input automatically if this isn't a 'clear' action
                                const inputs = Array.from(arrowsContainer.querySelectorAll('.arrow-input'));
                                const currentIndex = inputs.indexOf(activeInput);
                                
                                if (currentIndex < inputs.length - 1) {
                                    inputs[currentIndex + 1].click(); // Focus next input
                                }
                            }
                        }
                        
                        updateEndTotal();
                    } else {
                        // If no input is active, focus the first one
                        const firstInput = arrowsContainer.querySelector('.arrow-input');
                        if (firstInput) {
                            firstInput.click();
                        }
                    }
                });
            });
        }
        
        function submitFinalScore() {
            // Make sure all ends are filled
            for (let r = 0; r < ranges.length; r++) {
                const range = ranges[r];
                for (let e = 1; e <= range.NumberOfEnds; e++) {
                    const key = `range_${r}_end_${e}`;
                    if (!scoresByEnd[key] || scoresByEnd[key].some(score => score === '')) {
                        if (!confirm(`Not all ends have scores entered. Continue anyway?`)) {
                            return;
                        }
                        break;
                    }
                }
            }
            
            if (confirm('Are you sure you want to submit this score?')) {
                // Create a form to submit the data
                const form = document.createElement('form');
                form.method = 'post';
                form.action = 'enter_score.php';
                
                // Basic score data
                const roundIdInput = document.createElement('input');
                roundIdInput.type = 'hidden';
                roundIdInput.name = 'round_id';
                roundIdInput.value = document.getElementById('round').value;
                form.appendChild(roundIdInput);
                
                const equipmentIdInput = document.createElement('input');
                equipmentIdInput.type = 'hidden';
                equipmentIdInput.name = 'equipment_id';
                equipmentIdInput.value = document.getElementById('equipment').value;
                form.appendChild(equipmentIdInput);
                
                const dateInput = document.createElement('input');
                dateInput.type = 'hidden';
                dateInput.name = 'date';
                dateInput.value = document.getElementById('date').value;
                form.appendChild(dateInput);
                
                const timeInput = document.createElement('input');
                timeInput.type = 'hidden';
                timeInput.name = 'time';
                timeInput.value = document.getElementById('time').value;
                form.appendChild(timeInput);
                
                const isPracticeInput = document.createElement('input');
                isPracticeInput.type = 'hidden';
                isPracticeInput.name = 'is_practice';
                isPracticeInput.value = document.getElementById('is_practice').checked ? '1' : '0';
                form.appendChild(isPracticeInput);
                
                if (!document.getElementById('is_practice').checked) {
                    const competitionIdInput = document.createElement('input');
                    competitionIdInput.type = 'hidden';
                    competitionIdInput.name = 'competition_id';
                    competitionIdInput.value = document.getElementById('competition').value;
                    form.appendChild(competitionIdInput);
                }
                
                // Add total score
                const totalScoreInput = document.createElement('input');
                totalScoreInput.type = 'hidden';
                totalScoreInput.name = 'total_score';
                totalScoreInput.value = totalScore;
                form.appendChild(totalScoreInput);
                
                // Add detailed arrow scores as JSON
                const arrowScores = [];
                
                // Prepare scores in the format needed for the database
                for (let r = 0; r < ranges.length; r++) {
                    const range = ranges[r];
                    for (let e = 1; e <= range.NumberOfEnds; e++) {
                        const key = `range_${r}_end_${e}`;
                        const endScores = scoresByEnd[key] || [];
                        
                        // Add each arrow score with all relevant metadata
                        endScores.forEach((score, arrowIndex) => {
                            arrowScores.push({
                                range_index: r,
                                range_distance: range.Distance,
                                range_face_size: range.TargetFaceSize,
                                end_number: e, 
                                arrow_number: arrowIndex + 1,
                                score_value: score
                            });
                        });
                    }
                }
                
                // Add arrow scores as JSON
                const arrowScoresInput = document.createElement('input');
                arrowScoresInput.type = 'hidden';
                arrowScoresInput.name = 'arrow_scores';
                arrowScoresInput.value = JSON.stringify(arrowScores);
                form.appendChild(arrowScoresInput);
                
                // Add submit flag
                const submitFormInput = document.createElement('input');
                submitFormInput.type = 'hidden';
                submitFormInput.name = 'submit_form';
                submitFormInput.value = '1';
                form.appendChild(submitFormInput);
                
                // Add a flag to indicate this is coming from the scoring interface
                const scoreSubmitInput = document.createElement('input');
                scoreSubmitInput.type = 'hidden';
                scoreSubmitInput.name = 'score_submit';
                scoreSubmitInput.value = '1';
                form.appendChild(scoreSubmitInput);
                
                // Submit the form
                document.body.appendChild(form);
                form.submit();
            }
        }
    }
});
    </script>
    
    <style>
        /* Score Entry Page Specific Styles */
        .score-entry-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 1rem;
        }
        
        .score-entry-container h2 {
            color: var(--primary-color);
            margin-bottom: 1.5rem;
        }
        
        .form-row {
            display: flex;
            gap: 1rem;
            margin-bottom: 1rem;
        }
        
        .form-row .form-group {
            flex: 1;
        }
        
        .checkbox-group {
            display: flex;
            align-items: center;
            margin: 0.5rem 0;
        }
        
        .checkbox-group input[type="checkbox"] {
            margin-right: 0.5rem;
        }
        
        .round-details {
            background-color: var(--light-color);
            border-radius: 4px;
            padding: 1rem;
            margin-top: 1rem;
        }
        
        .round-details h4 {
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }
        
        .ranges-list {
            margin: 0;
            padding-left: 1.5rem;
        }
        
        .ranges-list li {
            margin-bottom: 0.25rem;
        }
        
        .form-buttons {
            display: flex;
            justify-content: space-between;
            margin-top: 1.5rem;
        }
        
        #score-entry-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }
        
        .hidden {
            display: none !important;
        }
        
        .alert {
            padding: 0.75rem 1rem;
            margin-bottom: 1rem;
            border-radius: 4px;
        }
        
        .alert-success {
            background-color: #d1fae5;
            color: var(--success-color);
            border-left: 4px solid var(--success-color);
        }
        
        .alert-error {
            background-color: #fee2e2;
            color: var(--error-color);
            border-left: 4px solid var(--error-color);
        }
        
        .alert i {
            margin-right: 0.5rem;
        }
        
        .score-submission {
            margin-top: 2rem;
            background-color: var(--light-color);
            border-radius: 8px;
            padding: 1.5rem;
            text-align: center;
        }
        
        .score-submission h3 {
            color: var(--primary-color);
            margin-bottom: 1rem;
        }
        
        .final-score {
            font-size: 1.25rem;
            margin: 1rem 0;
        }
        
        .final-score strong {
            font-size: 1.5rem;
            color: var(--primary-color);
        }
        
        .submission-buttons {
            margin-top: 1.5rem;
        }
        
        /* Responsive adjustments */
        @media (max-width: 600px) {
            .form-row {
                flex-direction: column;
                gap: 0.5rem;
            }
            
            .form-buttons {
                flex-direction: column;
                gap: 1rem;
            }
            
            #score-entry-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.5rem;
            }
        }
    </style>
</body>
</html>