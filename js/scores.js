/**
 * Score Entry and Management JavaScript
 */

let currentEnd = 1;
let totalEnds = 0;
let scoreData = {
    roundID: null,
    archerID: null,
    equipmentID: null,
    date: null,
    ends: []
};

document.addEventListener('DOMContentLoaded', function() {
    initializeScoreEntry();
    initializeScoreKeypad();
    loadRoundData();
});

/**
 * Initialize the score entry form and controls
 */
function initializeScoreEntry() {
    const scoreForm = document.getElementById('score-entry-form');
    const prevEndBtn = document.getElementById('prev-end');
    const nextEndBtn = document.getElementById('next-end');
    const submitScoreBtn = document.getElementById('submit-score');
    
    if (scoreForm) {
        // Load any saved data from session storage
        const savedData = sessionStorage.getItem('currentScoreData');
        if (savedData) {
            scoreData = JSON.parse(savedData);
            updateEndDisplay();
            populateCurrentEnd();
        }
        
        // Setup arrow input validation
        const arrowInputs = document.querySelectorAll('.arrow-input');
        arrowInputs.forEach(input => {
            input.addEventListener('input', function() {
                validateArrowInput(this);
                updateEndTotal();
            });
            
            input.addEventListener('keyup', function(e) {
                // Move to next input on valid entry
                if (this.value && this.checkValidity()) {
                    const currentIndex = Array.from(arrowInputs).indexOf(this);
                    if (currentIndex < arrowInputs.length - 1) {
                        arrowInputs[currentIndex + 1].focus();
                    }
                }
            });
        });
        
        // End navigation
        if (prevEndBtn) {
            prevEndBtn.addEventListener('click', function() {
                saveCurrentEnd();
                if (currentEnd > 1) {
                    currentEnd--;
                    updateEndDisplay();
                    populateCurrentEnd();
                }
            });
        }
        
        if (nextEndBtn) {
            nextEndBtn.addEventListener('click', function() {
                if (validateCurrentEnd()) {
                    saveCurrentEnd();
                    if (currentEnd < totalEnds) {
                        currentEnd++;
                        updateEndDisplay();
                        populateCurrentEnd();
                    } else if (currentEnd === totalEnds) {
                        // Show submission options
                        document.querySelector('.score-submission').classList.remove('hidden');
                        this.disabled = true;
                    }
                } else {
                    showError('Please enter valid scores for all arrows in this end.');
                }
            });
        }
        
        // Score submission
        if (submitScoreBtn) {
            submitScoreBtn.addEventListener('click', function() {
                submitScore();
            });
        }
    }
}

/**
 * Initialize the on-screen score keypad
 */
function initializeScoreKeypad() {
    const keypad = document.querySelector('.score-keypad');
    const arrowInputs = document.querySelectorAll('.arrow-input');
    
    if (keypad) {
        let activeInput = null;
        
        // Set initial active input
        if (arrowInputs.length > 0) {
            arrowInputs[0].focus();
            activeInput = arrowInputs[0];
        }
        
        // Set active input when an input is clicked
        arrowInputs.forEach(input => {
            input.addEventListener('focus', function() {
                activeInput = this;
            });
        });
        
        // Handle keypad button clicks
        const keypadButtons = keypad.querySelectorAll('.score-key');
        keypadButtons.forEach(button => {
            button.addEventListener('click', function() {
                if (!activeInput) return;
                
                const value = this.getAttribute('data-value');
                
                if (value === 'clear') {
                    activeInput.value = '';
                } else {
                    activeInput.value = value;
                    validateArrowInput(activeInput);
                    updateEndTotal();
                    
                    // Move to next input
                    const currentIndex = Array.from(arrowInputs).indexOf(activeInput);
                    if (currentIndex < arrowInputs.length - 1) {
                        arrowInputs[currentIndex + 1].focus();
                        activeInput = arrowInputs[currentIndex + 1];
                    }
                }
            });
        });
    }
}

/**
 * Validate arrow input (X, 10, 9, 8, 7, 6, 5, 4, 3, 2, 1, M)
 */
function validateArrowInput(input) {
    const value = input.value.toUpperCase();
    const validScores = ['X', '10', '9', '8', '7', '6', '5', '4', '3', '2', '1', 'M'];
    
    if (value && !validScores.includes(value)) {
        input.classList.add('error');
        return false;
    } else {
        input.classList.remove('error');
        
        // Sort arrows from highest to lowest scores
        sortArrowInputs();
        return true;
    }
}

/**
 * Sort arrow inputs from highest to lowest scores
 */
function sortArrowInputs() {
    const arrowsContainer = document.querySelector('.arrows-container');
    const arrowInputs = document.querySelectorAll('.arrow-input');
    const values = [];
    
    // Collect non-empty values
    arrowInputs.forEach(input => {
        if (input.value) {
            values.push({
                element: input,
                value: input.value.toUpperCase()
            });
        }
    });
    
    // Sort values based on archery scoring order
    values.sort((a, b) => {
        return getArrowValue(b.value) - getArrowValue(a.value);
    });
    
    // Clear all inputs
    arrowInputs.forEach(input => {
        input.value = '';
    });
    
    // Fill inputs in sorted order
    values.forEach((item, index) => {
        arrowInputs[index].value = item.value;
    });
}

/**
 * Get numeric value for an arrow score (for sorting)
 */
function getArrowValue(value) {
    if (value === 'X') return 11; // X is highest
    if (value === 'M') return 0;  // M (miss) is lowest
    return parseInt(value);
}

/**
 * Update the total score for the current end
 */
function updateEndTotal() {
    const arrowInputs = document.querySelectorAll('.arrow-input');
    const endTotalElement = document.getElementById('end-total');
    
    if (!endTotalElement) return;
    
    let total = 0;
    
    arrowInputs.forEach(input => {
        const value = input.value.toUpperCase();
        
        if (value === 'X') {
            total += 10; // X counts as 10 for total
        } else if (value === 'M') {
            total += 0;  // M (miss) counts as 0
        } else if (value) {
            total += parseInt(value);
        }
    });
    
    endTotalElement.textContent = total;
}

/**
 * Validate that all arrows in the current end have valid scores
 */
function validateCurrentEnd() {
    const arrowInputs = document.querySelectorAll('.arrow-input');
    let isValid = true;
    
    arrowInputs.forEach(input => {
        if (!input.value) {
            input.classList.add('error');
            isValid = false;
        } else if (!validateArrowInput(input)) {
            isValid = false;
        }
    });
    
    return isValid;
}

/**
 * Save the current end's scores to memory
 */
function saveCurrentEnd() {
    const arrowInputs = document.querySelectorAll('.arrow-input');
    const rangeKey = document.getElementById('current-range') ? document.getElementById('current-range').value : '0';
    
    if (!scoreData.ends[rangeKey]) {
        scoreData.ends[rangeKey] = {};
    }
    
    const arrowValues = [];
    arrowInputs.forEach(input => {
        arrowValues.push(input.value.toUpperCase());
    });
    
    scoreData.ends[rangeKey][currentEnd] = arrowValues;
    
    // Save to session storage for persistence
    sessionStorage.setItem('currentScoreData', JSON.stringify(scoreData));
    
    // Update running total
    updateRunningTotal();
}

/**
 * Populate the current end with saved scores (if any)
 */
function populateCurrentEnd() {
    const arrowInputs = document.querySelectorAll('.arrow-input');
    const rangeKey = document.getElementById('current-range') ? document.getElementById('current-range').value : '0';
    
    if (scoreData.ends[rangeKey] && scoreData.ends[rangeKey][currentEnd]) {
        const arrowValues = scoreData.ends[rangeKey][currentEnd];
        
        arrowInputs.forEach((input, index) => {
            if (index < arrowValues.length) {
                input.value = arrowValues[index];
            } else {
                input.value = '';
            }
        });
    } else {
        // Clear inputs if no saved data
        arrowInputs.forEach(input => {
            input.value = '';
        });
    }
    
    // Update end total
    updateEndTotal();
}

/**
 * Update the end number display
 */
function updateEndDisplay() {
    const currentEndElement = document.getElementById('current-end');
    const totalEndsElement = document.getElementById('total-ends');
    
    if (currentEndElement) {
        currentEndElement.textContent = currentEnd;
    }
    
    if (totalEndsElement && totalEnds > 0) {
        totalEndsElement.textContent = totalEnds;
    }
    
    // Update navigation buttons
    const prevEndBtn = document.getElementById('prev-end');
    const nextEndBtn = document.getElementById('next-end');
    
    if (prevEndBtn) {
        prevEndBtn.disabled = currentEnd <= 1;
    }
    
    if (nextEndBtn) {
        nextEndBtn.disabled = false;
        document.querySelector('.score-submission').classList.add('hidden');
    }
}

/**
 * Calculate and update the running total for all ends
 */
function updateRunningTotal() {
    const runningTotalElement = document.getElementById('running-total');
    const finalScoreElement = document.getElementById('final-score');
    
    if (!runningTotalElement) return;
    
    let total = 0;
    
    // Sum up all ends in all ranges
    for (const rangeKey in scoreData.ends) {
        const rangeEnds = scoreData.ends[rangeKey];
        
        for (const endKey in rangeEnds) {
            const arrowValues = rangeEnds[endKey];
            
            arrowValues.forEach(value => {
                if (value === 'X') {
                    total += 10;
                } else if (value === 'M') {
                    total += 0;
                } else if (value) {
                    total += parseInt(value);
                }
            });
        }
    }
    
    runningTotalElement.textContent = total;
    
    if (finalScoreElement) {
        finalScoreElement.textContent = total;
    }
}

/**
 * Load round data and initialize the score entry form
 */
function loadRoundData() {
    const roundSelect = document.getElementById('round');
    const roundDetailsElement = document.querySelector('.round-details');
    
    if (roundSelect && roundDetailsElement) {
        const selectedRoundId = roundSelect.value;
        
        if (selectedRoundId) {
            // Set round ID in score data
            scoreData.roundID = selectedRoundId;
            
            // In a real implementation, this would use AJAX to fetch the round data
            // For now, we'll use the data already embedded in the page
            
            // Get ranges for the selected round
            const ranges = [];
            document.querySelectorAll('.ranges-list li').forEach(rangeItem => {
                const rangeText = rangeItem.textContent;
                
                // Parse range details
                const distanceMatch = rangeText.match(/(\d+)m/);
                const endsMatch = rangeText.match(/(\d+)\s+ends/);
                const arrowsMatch = rangeText.match(/(\d+)\s+arrows per end/);
                const faceMatch = rangeText.match(/(\d+)cm face/);
                
                if (distanceMatch && endsMatch && arrowsMatch && faceMatch) {
                    ranges.push({
                        distance: parseInt(distanceMatch[1]),
                        numberOfEnds: parseInt(endsMatch[1]),
                        arrowsPerEnd: parseInt(arrowsMatch[1]),
                        targetFaceSize: parseInt(faceMatch[1])
                    });
                }
            });
            
            // Set total ends
            if (ranges.length > 0) {
                // Sum of all ends in all ranges
                totalEnds = ranges.reduce((sum, range) => sum + range.numberOfEnds, 0);
            }
        }
    }
}

/**
 * Submit the score to the server
 */
function submitScore() {
    // In a real implementation, this would use AJAX to submit the score
    // For now, we'll just show a confirmation
    
    if (confirm('Are you sure you want to submit this score?')) {
        const form = document.createElement('form');
        form.method = 'post';
        form.action = window.location.href;
        
        // Add score data as hidden fields
        const roundIdInput = document.createElement('input');
        roundIdInput.type = 'hidden';
        roundIdInput.name = 'round_id';
        roundIdInput.value = scoreData.roundID;
        form.appendChild(roundIdInput);
        
        // Add archer ID
        const archerIdInput = document.createElement('input');
        archerIdInput.type = 'hidden';
        archerIdInput.name = 'archer_id';
        archerIdInput.value = document.querySelector('[name="archer_id"]').value;
        form.appendChild(archerIdInput);
        
        // Add equipment ID
        const equipmentIdInput = document.createElement('input');
        equipmentIdInput.type = 'hidden';
        equipmentIdInput.name = 'equipment_id';
        equipmentIdInput.value = document.getElementById('equipment').value;
        form.appendChild(equipmentIdInput);
        
        // Add total score
        const totalScoreInput = document.createElement('input');
        totalScoreInput.type = 'hidden';
        totalScoreInput.name = 'total_score';
        totalScoreInput.value = document.getElementById('running-total').textContent;
        form.appendChild(totalScoreInput);
        
        // Add to document and submit
        document.body.appendChild(form);
        form.submit();
    }
}

/**
 * Show an error message
 */
function showError(message) {
    const errorElement = document.createElement('div');
    errorElement.className = 'alert alert-error';
    errorElement.innerHTML = `<i class="fas fa-exclamation-circle"></i> ${message}`;
    
    const form = document.getElementById('score-entry-form');
    form.insertBefore(errorElement, form.firstChild);
    
    // Remove after 3 seconds
    setTimeout(() => {
        errorElement.remove();
    }, 3000);
}

/**
 * Check if all ranges are complete
 */
function checkAllRangesComplete() {
    // Check if we have scores for all ends in all ranges
    let allComplete = true;
    
    // In a real implementation, this would check against the round definition
    // For now, we'll just check if we have the expected number of ends
    
    let totalEnteredEnds = 0;
    
    for (const rangeKey in scoreData.ends) {
        totalEnteredEnds += Object.keys(scoreData.ends[rangeKey]).length;
    }
    
    return totalEnteredEnds >= totalEnds;
}

/**
 * Reset the score entry form
 */
function resetScoreForm() {
    // Clear score data
    scoreData = {
        roundID: scoreData.roundID, // Keep the round ID
        archerID: scoreData.archerID, // Keep the archer ID
        equipmentID: scoreData.equipmentID, // Keep the equipment ID
        date: null,
        ends: []
    };
    
    // Reset current end
    currentEnd = 1;
    
    // Clear inputs
    const arrowInputs = document.querySelectorAll('.arrow-input');
    arrowInputs.forEach(input => {
        input.value = '';
        input.classList.remove('error');
    });
    
    // Update display
    updateEndDisplay();
    updateEndTotal();
    updateRunningTotal();
    
    // Clear session storage
    sessionStorage.removeItem('currentScoreData');
}