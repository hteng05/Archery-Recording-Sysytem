/**
 * Main JavaScript file for Archery Score Recording System
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialize any components that need JavaScript functionality
    initializeDropdowns();
    initializeModals();
    initializeDataTables();
    initializeScoreEntry();
    setupFormValidation();
});

/**
 * Initialize dropdown menus
 */
function initializeDropdowns() {
    const dropdowns = document.querySelectorAll('.dropdown-toggle');
    
    dropdowns.forEach(dropdown => {
        dropdown.addEventListener('click', function(e) {
            e.preventDefault();
            const dropdownMenu = this.nextElementSibling;
            dropdownMenu.classList.toggle('show');
            
            // Close other open dropdowns
            dropdowns.forEach(otherDropdown => {
                if (otherDropdown !== this) {
                    otherDropdown.nextElementSibling.classList.remove('show');
                }
            });
        });
    });
    
    // Close dropdowns when clicking outside
    document.addEventListener('click', function(e) {
        if (!e.target.matches('.dropdown-toggle') && !e.target.closest('.dropdown-menu')) {
            const openDropdowns = document.querySelectorAll('.dropdown-menu.show');
            openDropdowns.forEach(dropdown => {
                dropdown.classList.remove('show');
            });
        }
    });
}

/**
 * Initialize modal dialogs
 */
function initializeModals() {
    const modalTriggers = document.querySelectorAll('[data-toggle="modal"]');
    
    modalTriggers.forEach(trigger => {
        trigger.addEventListener('click', function(e) {
            e.preventDefault();
            const targetModal = document.querySelector(this.getAttribute('data-target'));
            if (targetModal) {
                targetModal.classList.add('active');
                document.body.classList.add('modal-open');
            }
        });
    });
    
    // Close modal when clicking on close button or backdrop
    const closeButtons = document.querySelectorAll('.modal-close, .modal-backdrop');
    closeButtons.forEach(button => {
        button.addEventListener('click', function() {
            const modal = this.closest('.modal');
            if (modal) {
                modal.classList.remove('active');
                document.body.classList.remove('modal-open');
            }
        });
    });
}

/**
 * Initialize data tables with sorting and filtering
 */
function initializeDataTables() {
    const tables = document.querySelectorAll('.data-table');
    
    tables.forEach(table => {
        const headers = table.querySelectorAll('th[data-sort]');
        
        headers.forEach(header => {
            header.addEventListener('click', function() {
                const sortBy = this.getAttribute('data-sort');
                const isAsc = this.classList.contains('sort-asc');
                
                // Remove sort classes from all headers
                headers.forEach(h => {
                    h.classList.remove('sort-asc', 'sort-desc');
                });
                
                // Add appropriate sort class to clicked header
                this.classList.add(isAsc ? 'sort-desc' : 'sort-asc');
                
                // Sort the table (this would be replaced with AJAX in a real implementation)
                sortTable(table, sortBy, !isAsc);
            });
        });
    });
}

/**
 * Sort a table by a specific column
 */
function sortTable(table, sortBy, ascending) {
    const tbody = table.querySelector('tbody');
    const rows = Array.from(tbody.querySelectorAll('tr'));
    
    rows.sort((a, b) => {
        const aValue = a.querySelector(`td[data-${sortBy}]`).getAttribute(`data-${sortBy}`);
        const bValue = b.querySelector(`td[data-${sortBy}]`).getAttribute(`data-${sortBy}`);
        
        if (ascending) {
            return aValue.localeCompare(bValue);
        } else {
            return bValue.localeCompare(aValue);
        }
    });
    
    // Clear and re-append rows in new order
    while (tbody.firstChild) {
        tbody.removeChild(tbody.firstChild);
    }
    
    rows.forEach(row => {
        tbody.appendChild(row);
    });
}

/**
 * Initialize score entry functionality
 */
function initializeScoreEntry() {
    const scoreEntryForm = document.getElementById('score-entry-form');
    
    if (scoreEntryForm) {
        const arrowInputs = scoreEntryForm.querySelectorAll('.arrow-input');
        
        arrowInputs.forEach((input, index) => {
            input.addEventListener('change', function() {
                validateArrowScore(this);
                if (this.value && index < arrowInputs.length - 1) {
                    arrowInputs[index + 1].focus();
                }
            });
        });
        
        scoreEntryForm.addEventListener('submit', function(e) {
            e.preventDefault();
            if (validateScoreEntry()) {
                submitScore(this);
            }
        });
    }
}

/**
 * Validate arrow score (X, 10, 9, 8, 7, 6, 5, 4, 3, 2, 1, M)
 */
function validateArrowScore(input) {
    const value = input.value.toUpperCase();
    const validScores = ['X', '10', '9', '8', '7', '6', '5', '4', '3', '2', '1', 'M'];
    
    if (!validScores.includes(value)) {
        input.classList.add('error');
        return false;
    } else {
        input.classList.remove('error');
        return true;
    }
}

/**
 * Validate the entire score entry form
 */
function validateScoreEntry() {
    const arrowInputs = document.querySelectorAll('.arrow-input');
    let isValid = true;
    
    arrowInputs.forEach(input => {
        if (!validateArrowScore(input)) {
            isValid = false;
        }
    });
    
    return isValid;
}

/**
 * Submit score to the server
 */
function submitScore(form) {
    // In a real implementation, this would use AJAX to submit to the server
    console.log('Submitting score...');
    
    // Simulate successful submission
    const successMessage = document.createElement('div');
    successMessage.className = 'alert alert-success';
    successMessage.textContent = 'Score submitted successfully!';
    
    form.appendChild(successMessage);
    
    setTimeout(() => {
        successMessage.remove();
        form.reset();
    }, 3000);
}

/**
 * Setup form validation
 */
function setupFormValidation() {
    const forms = document.querySelectorAll('.needs-validation');
    
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            if (!form.checkValidity()) {
                e.preventDefault();
                e.stopPropagation();
            }
            
            form.classList.add('was-validated');
        });
    });
}