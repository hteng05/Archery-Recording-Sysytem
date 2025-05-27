<?php
error_log("=== APPROVAL DEBUG SESSION STARTED ===");
require_once '../includes/settings.php';
require_once '../includes/auth.php';
require_once '../includes/db_functions.php';

// Require recorder login
requireRecorderLogin();

// Check session timeout
checkSessionTimeout();

// Get recorder data
$recorder = getCurrentRecorder();

// Process approval if submitted
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['approve'])) {
        $stageId = intval($_POST['stage_id']);
        $totalScore = isset($_POST['total_score']) ? intval($_POST['total_score']) : null;
        $overrideEquipment = isset($_POST['override_equipment']) ? true : false;
        $overrideReason = isset($_POST['override_reason']) ? sanitizeInput($_POST['override_reason']) : '';
        
        if ($overrideEquipment) {
            // Approve with equipment validation override
            $result = approveScoreWithEquipmentOverride($stageId, $totalScore, $overrideReason);
        } else {
            // Normal approval with equipment validation
            $result = approveScore($stageId, $totalScore);
        }
        
        if (is_array($result) && isset($result['error']) && $result['error'] === 'equipment_mismatch') {
            // Equipment mismatch - show warning to recorder
            $message = 'Equipment validation failed: ' . $result['message'] . '. Please verify the equipment is correct or use override option.';
            $messageType = 'warning';
        } elseif ($result !== false) {
            $scoreIdMsg = is_numeric($result) ? " (Score ID: $result)" : "";
            $overrideMsg = $overrideEquipment ? " [Equipment validation overridden: $overrideReason]" : "";
            $message = 'Score has been approved successfully and is now visible to the archer.' . $scoreIdMsg . $overrideMsg;
            $messageType = 'success';
        } else {
            $message = 'Error approving score. Please try again.';
            $messageType = 'error';
        }
    } elseif (isset($_POST['reject'])) {
        $stageId = intval($_POST['stage_id']);
        $reason = isset($_POST['reason']) ? sanitizeInput($_POST['reason']) : 'No reason provided';
        
        $result = rejectStagedScore($stageId, $reason);
        
        if ($result) {
            $message = 'Score has been rejected and removed from staging.';
            $messageType = 'success';
        } else {
            $message = 'Error rejecting score. Please try again.';
            $messageType = 'error';
        }
    }
}

// Get pending scores
$pendingScores = getStagedScores();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pending Scores - Archery Score Recording System</title>
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
                    <a href="#" class="dropdown-toggle active">Scores</a>
                    <div class="dropdown-menu">
                        <a href="pending_scores.php" class="active">Approve Pending Scores</a>
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
            <div class="pending-scores-container">
                <div class="page-header">
                    <h2><i class="fas fa-clipboard-check"></i> Approve Pending Scores</h2>
                    <p>Review and approve scores submitted by archers. Equipment will be validated against archer's default.</p>
                </div>
                
                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $messageType; ?>">
                        <?php if ($messageType === 'success'): ?>
                            <i class="fas fa-check-circle"></i>
                        <?php elseif ($messageType === 'warning'): ?>
                            <i class="fas fa-exclamation-triangle"></i>
                        <?php else: ?>
                            <i class="fas fa-exclamation-circle"></i>
                        <?php endif; ?>
                        <?php echo $message; ?>
                    </div>
                <?php endif; ?>
                
                <?php if (empty($pendingScores)): ?>
                    <div class="no-pending-scores">
                        <i class="fas fa-check"></i>
                        <h3>No Pending Scores</h3>
                        <p>There are no pending scores waiting for approval.</p>
                    </div>
                <?php else: ?>
                    <div class="pending-scores-list">
                        <?php foreach ($pendingScores as $score): ?>
                            <?php
                            // Get detailed score data if available
                            $detailedScore = getStagedScoreWithDetails($score['StageID']);
                            $hasArrowDetails = $detailedScore && !empty($detailedScore['arrow_scores']);
                            
                            // Get equipment validation
                            $equipmentValidation = getEquipmentValidationForStaging($score['StageID']);
                            ?>
                            
                            <div class="score-card <?php echo $hasArrowDetails ? 'has-details' : 'basic-only'; ?> <?php echo !$equipmentValidation['valid'] ? 'equipment-warning' : ''; ?>">
                                <div class="score-header">
                                    <div class="archer-info">
                                        <h3><?php echo htmlspecialchars($score['FirstName'] . ' ' . $score['LastName']); ?></h3>
                                        <span class="score-date"><?php echo formatDate($score['Date']); ?> at <?php echo date('H:i', strtotime($score['Time'])); ?></span>
                                    </div>
                                    <div class="score-badges">
                                        <?php if ($score['IsPractice']): ?>
                                            <span class="badge badge-practice">Practice</span>
                                        <?php elseif ($score['CompetitionID']): ?>
                                            <span class="badge badge-competition">Competition</span>
                                        <?php endif; ?>
                                        
                                        <?php if ($hasArrowDetails): ?>
                                            <span class="badge badge-detailed">
                                                <i class="fas fa-bullseye"></i> Detailed Arrows
                                            </span>
                                        <?php else: ?>
                                            <span class="badge badge-basic">
                                                <i class="fas fa-clipboard"></i> Setup Only
                                            </span>
                                        <?php endif; ?>
                                        
                                        <?php if (!$equipmentValidation['valid']): ?>
                                            <span class="badge badge-equipment-warning">
                                                <i class="fas fa-exclamation-triangle"></i> Equipment Issue
                                            </span>
                                        <?php elseif (isset($equipmentValidation['warning'])): ?>
                                            <span class="badge badge-equipment-caution">
                                                <i class="fas fa-info-circle"></i> Equipment Note
                                            </span>
                                        <?php else: ?>
                                            <span class="badge badge-equipment-ok">
                                                <i class="fas fa-check"></i> Equipment OK
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <!-- Equipment Validation Section -->
                                <div class="equipment-validation <?php echo !$equipmentValidation['valid'] ? 'validation-error' : (isset($equipmentValidation['warning']) ? 'validation-warning' : 'validation-ok'); ?>">
                                    <div class="validation-header">
                                        <h4>
                                            <?php if (!$equipmentValidation['valid']): ?>
                                                <i class="fas fa-exclamation-triangle"></i> Equipment Validation Failed
                                            <?php elseif (isset($equipmentValidation['warning'])): ?>
                                                <i class="fas fa-info-circle"></i> Equipment Note
                                            <?php else: ?>
                                                <i class="fas fa-check-circle"></i> Equipment Validated
                                            <?php endif; ?>
                                        </h4>
                                    </div>
                                    <div class="validation-details">
                                        <p><strong>Reason:</strong> <?php echo htmlspecialchars($equipmentValidation['reason']); ?></p>
                                        <?php if (isset($equipmentValidation['default_equipment'])): ?>
                                            <div class="equipment-comparison">
                                                <div class="equipment-item">
                                                    <span class="equipment-label">Default Equipment:</span>
                                                    <span class="equipment-value"><?php echo htmlspecialchars($equipmentValidation['default_equipment']); ?></span>
                                                    <?php if (isset($equipmentValidation['default_division'])): ?>
                                                        <span class="division-info">(<?php echo htmlspecialchars($equipmentValidation['default_division']); ?>)</span>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="equipment-item">
                                                    <span class="equipment-label">Used Equipment:</span>
                                                    <span class="equipment-value"><?php echo htmlspecialchars($equipmentValidation['selected_equipment']); ?></span>
                                                    <?php if (isset($equipmentValidation['selected_division'])): ?>
                                                        <span class="division-info">(<?php echo htmlspecialchars($equipmentValidation['selected_division']); ?>)</span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                        <?php if (isset($equipmentValidation['suggestion'])): ?>
                                            <p class="validation-suggestion"><em><?php echo htmlspecialchars($equipmentValidation['suggestion']); ?></em></p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <div class="score-details">
                                    <div class="detail-item">
                                        <span class="detail-label">Round:</span>
                                        <span class="detail-value"><?php echo htmlspecialchars($score['RoundName']); ?></span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="detail-label">Equipment:</span>
                                        <span class="detail-value"><?php echo htmlspecialchars($score['EquipmentName']); ?></span>
                                    </div>
                                    <?php if ($hasArrowDetails): ?>
                                        <div class="detail-item">
                                            <span class="detail-label">Arrows Recorded:</span>
                                            <span class="detail-value"><?php echo count($detailedScore['arrow_scores']); ?> arrows</span>
                                        </div>
                                        <?php if ($detailedScore['TotalScore']): ?>
                                            <div class="detail-item">
                                                <span class="detail-label">Calculated Score:</span>
                                                <span class="detail-value highlight"><?php echo $detailedScore['TotalScore']; ?></span>
                                            </div>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                                
                                <?php if ($hasArrowDetails): ?>
                                    <!-- Detailed arrow scores display (keeping existing code) -->
                                    <div class="arrow-details-section">
                                        <div class="arrow-details-header">
                                            <h4>Arrow-by-Arrow Scores</h4>
                                            <button type="button" class="btn-toggle-details" onclick="toggleArrowDetails(<?php echo $score['StageID']; ?>)">
                                                <i class="fas fa-chevron-down"></i> Show Details
                                            </button>
                                        </div>
                                        
                                        <div class="arrow-details-content" id="arrow-details-<?php echo $score['StageID']; ?>" style="display: none;">
                                            <!-- Arrow details content (keeping existing code) -->
                                            <?php
                                            // Group arrows by range and end (existing code)
                                            $arrowsByRange = [];
                                            foreach ($detailedScore['arrow_scores'] as $arrow) {
                                                $rangeKey = $arrow['RangeIndex'] . '_' . $arrow['RangeDistance'] . 'm_' . $arrow['RangeFaceSize'] . 'cm';
                                                $endKey = $arrow['EndNumber'];
                                                
                                                if (!isset($arrowsByRange[$rangeKey])) {
                                                    $arrowsByRange[$rangeKey] = [
                                                        'distance' => $arrow['RangeDistance'],
                                                        'face_size' => $arrow['RangeFaceSize'],
                                                        'ends' => []
                                                    ];
                                                }
                                                
                                                if (!isset($arrowsByRange[$rangeKey]['ends'][$endKey])) {
                                                    $arrowsByRange[$rangeKey]['ends'][$endKey] = [];
                                                }
                                                
                                                $arrowsByRange[$rangeKey]['ends'][$endKey][] = $arrow;
                                            }
                                            ?>
                                            
                                            <?php foreach ($arrowsByRange as $rangeKey => $rangeData): ?>
                                                <div class="range-section">
                                                    <div class="range-header">
                                                        <?php echo $rangeData['distance']; ?>m - <?php echo $rangeData['face_size']; ?>cm Face
                                                    </div>
                                                    
                                                    <?php foreach ($rangeData['ends'] as $endNumber => $arrows): ?>
                                                        <div class="end-row">
                                                            <span class="end-label">End <?php echo $endNumber; ?>:</span>
                                                            <div class="arrow-scores">
                                                                <?php 
                                                                usort($arrows, function($a, $b) {
                                                                    return $a['ArrowNumber'] - $b['ArrowNumber'];
                                                                });
                                                                
                                                                $endTotal = 0;
                                                                foreach ($arrows as $arrow): 
                                                                    $scoreValue = $arrow['ScoreValue'];
                                                                    $numericScore = ($scoreValue === 'X') ? 10 : (($scoreValue === 'M') ? 0 : intval($scoreValue));
                                                                    $endTotal += $numericScore;
                                                                    
                                                                    $cssClass = '';
                                                                    if ($scoreValue === 'X') $cssClass = 'x-score';
                                                                    elseif ($scoreValue === '10') $cssClass = 'ten-score';
                                                                    elseif ($scoreValue === 'M') $cssClass = 'miss-score';
                                                                ?>
                                                                    <span class="arrow-score <?php echo $cssClass; ?>"><?php echo htmlspecialchars($scoreValue); ?></span>
                                                                <?php endforeach; ?>
                                                            </div>
                                                            <span class="end-total">Total: <?php echo $endTotal; ?></span>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="score-entry-section">
                                    <?php if ($hasArrowDetails): ?>
                                        <!-- For detailed scores, auto-approve with calculated total -->
                                        <div class="calculated-score-info">
                                            <div class="calculated-total">
                                                Total Score: <strong><?php echo isset($detailedScore['TotalScore']) ? $detailedScore['TotalScore'] : 0; ?></strong>
                                                <small>(Calculated from individual arrow scores)</small>
                                            </div>
                                        </div>
                                        
                                        <div class="action-buttons">
                                            <?php if ($equipmentValidation['valid']): ?>
                                                <!-- Normal approval for valid equipment -->
                                                <form method="post" action="pending_scores.php" style="display: inline;">
                                                    <input type="hidden" name="stage_id" value="<?php echo $score['StageID']; ?>">
                                                    <input type="hidden" name="total_score" value="<?php echo isset($detailedScore['TotalScore']) ? $detailedScore['TotalScore'] : 0; ?>">
                                                    <button type="submit" name="approve" class="btn btn-primary" onclick="return confirm('Approve this score with calculated total of <?php echo isset($detailedScore['TotalScore']) ? $detailedScore['TotalScore'] : 0; ?>?')">
                                                        <i class="fas fa-check"></i> Approve Score
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <!-- Equipment override approval -->
                                                <button type="button" class="btn btn-warning" onclick="openEquipmentOverrideModal(<?php echo $score['StageID']; ?>, <?php echo isset($detailedScore['TotalScore']) ? $detailedScore['TotalScore'] : 0; ?>)">
                                                    <i class="fas fa-exclamation-triangle"></i> Override & Approve
                                                </button>
                                            <?php endif; ?>
                                            
                                            <button type="button" class="btn btn-secondary" onclick="openRejectModal(<?php echo $score['StageID']; ?>)">
                                                <i class="fas fa-times"></i> Reject
                                            </button>
                                        </div>
                                    <?php else: ?>
                                        <!-- For basic scores, require manual total entry -->
                                        <div class="manual-score-info">
                                            <div class="info-message">
                                                <i class="fas fa-info-circle"></i>
                                                This score contains only basic setup information. Please enter the total score manually.
                                            </div>
                                        </div>
                                        
                                        <?php if ($equipmentValidation['valid']): ?>
                                            <!-- Normal approval form -->
                                            <form method="post" action="pending_scores.php" class="score-approval-form">
                                                <input type="hidden" name="stage_id" value="<?php echo $score['StageID']; ?>">
                                                
                                                <div class="form-group score-input-group">
                                                    <label for="total-score-<?php echo $score['StageID']; ?>">Total Score:</label>
                                                    <input type="number" id="total-score-<?php echo $score['StageID']; ?>" name="total_score" class="form-control" required min="0" max="999">
                                                </div>
                                                
                                                <div class="action-buttons">
                                                    <button type="submit" name="approve" class="btn btn-primary">
                                                        <i class="fas fa-check"></i> Approve Score
                                                    </button>
                                                    <button type="button" class="btn btn-secondary" onclick="openRejectModal(<?php echo $score['StageID']; ?>)">
                                                        <i class="fas fa-times"></i> Reject
                                                    </button>
                                                </div>
                                            </form>
                                        <?php else: ?>
                                            <!-- Equipment override form for manual scores -->
                                            <div class="equipment-override-section">
                                                <div class="form-group score-input-group">
                                                    <label for="total-score-override-<?php echo $score['StageID']; ?>">Total Score:</label>
                                                    <input type="number" id="total-score-override-<?php echo $score['StageID']; ?>" class="form-control" required min="0" max="999">
                                                </div>
                                                
                                                <div class="action-buttons">
                                                    <button type="button" class="btn btn-warning" onclick="openEquipmentOverrideModalWithScore(<?php echo $score['StageID']; ?>)">
                                                        <i class="fas fa-exclamation-triangle"></i> Override & Approve
                                                    </button>
                                                    <button type="button" class="btn btn-secondary" onclick="openRejectModal(<?php echo $score['StageID']; ?>)">
                                                        <i class="fas fa-times"></i> Reject
                                                    </button>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </main>

        <footer>
            <p>&copy; 2025 Archery Club Database System. All rights reserved.</p>
        </footer>
    </div>

    <!-- Equipment Override Modal -->
    <div id="equipmentOverrideModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Equipment Validation Override</h3>
                <span class="close" onclick="closeEquipmentOverrideModal()">&times;</span>
            </div>
            <form method="post" action="pending_scores.php">
                <div class="modal-body">
                    <input type="hidden" name="stage_id" id="override-stage-id">
                    <input type="hidden" name="total_score" id="override-total-score">
                    <input type="hidden" name="override_equipment" value="1">
                    
                    <div class="override-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        <p><strong>Warning:</strong> You are about to approve a score where the equipment used does not match the archer's default equipment.</p>
                    </div>
                    
                    <div class="form-group">
                        <label for="override-reason">Reason for equipment override:</label>
                        <textarea id="override-reason" name="override_reason" class="form-control" rows="4" required placeholder="Please explain why this equipment difference is acceptable (e.g., 'Archer borrowed equipment', 'Testing new bow', 'Equipment change approved')..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeEquipmentOverrideModal()">Cancel</button>
                    <button type="submit" name="approve" class="btn btn-warning">Override & Approve Score</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Rejection Modal (existing) -->
    <div id="rejectModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Reject Score</h3>
                <span class="close" onclick="closeRejectModal()">&times;</span>
            </div>
            <form method="post" action="pending_scores.php">
                <div class="modal-body">
                    <input type="hidden" name="stage_id" id="reject-stage-id">
                    <div class="form-group">
                        <label for="reject-reason">Reason for rejection:</label>
                        <textarea id="reject-reason" name="reason" class="form-control" rows="4" required placeholder="Please provide a reason for rejecting this score..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeRejectModal()">Cancel</button>
                    <button type="submit" name="reject" class="btn btn-danger">Reject Score</button>
                </div>
            </form>
        </div>
    </div>

    <script src="../js/main.js"></script>
    
    <script>
        function toggleArrowDetails(stageId) {
            const detailsContent = document.getElementById('arrow-details-' + stageId);
            const toggleBtn = detailsContent.previousElementSibling.querySelector('.btn-toggle-details');
            const icon = toggleBtn.querySelector('i');
            
            if (detailsContent.style.display === 'none') {
                detailsContent.style.display = 'block';
                icon.className = 'fas fa-chevron-up';
                toggleBtn.innerHTML = '<i class="fas fa-chevron-up"></i> Hide Details';
            } else {
                detailsContent.style.display = 'none';
                icon.className = 'fas fa-chevron-down';
                toggleBtn.innerHTML = '<i class="fas fa-chevron-down"></i> Show Details';
            }
        }
        
        function openRejectModal(stageId) {
            document.getElementById('reject-stage-id').value = stageId;
            document.getElementById('rejectModal').style.display = 'block';
        }
        
        function closeRejectModal() {
            document.getElementById('rejectModal').style.display = 'none';
            document.getElementById('reject-reason').value = '';
        }
        
        function openEquipmentOverrideModal(stageId, totalScore) {
            document.getElementById('override-stage-id').value = stageId;
            document.getElementById('override-total-score').value = totalScore;
            document.getElementById('equipmentOverrideModal').style.display = 'block';
        }
        
        function openEquipmentOverrideModalWithScore(stageId) {
            const scoreInput = document.getElementById('total-score-override-' + stageId);
            const totalScore = scoreInput.value;
            
            if (!totalScore || totalScore < 0) {
                alert('Please enter a valid total score first.');
                return;
            }
            
            openEquipmentOverrideModal(stageId, totalScore);
        }
        
        function closeEquipmentOverrideModal() {
            document.getElementById('equipmentOverrideModal').style.display = 'none';
            document.getElementById('override-reason').value = '';
        }
        
        // Close modal when clicking outside of it
        window.onclick = function(event) {
            const rejectModal = document.getElementById('rejectModal');
            const overrideModal = document.getElementById('equipmentOverrideModal');
            
            if (event.target == rejectModal) {
                closeRejectModal();
            } else if (event.target == overrideModal) {
                closeEquipmentOverrideModal();
            }
        }
    </script>
    
    <style>
        /* Enhanced Pending Scores Page Styles */
        .pending-scores-container {
            padding: 1rem;
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .page-header {
            margin-bottom: 2rem;
        }
        
        .page-header h2 {
            color: var(--primary-color);
            margin: 0 0 0.5rem 0;
        }
        
        .page-header h2 i {
            margin-right: 0.5rem;
        }
        
        .page-header p {
            color: #6b7280;
            margin: 0;
        }
        
        .no-pending-scores {
            background-color: white;
            border-radius: 8px;
            padding: 3rem 1.5rem;
            text-align: center;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        .no-pending-scores i {
            font-size: 3rem;
            color: var(--success-color);
            margin-bottom: 1rem;
        }
        
        .no-pending-scores h3 {
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }
        
        .pending-scores-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
            gap: 1.5rem;
        }
        
        .score-card {
            background-color: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            border: 2px solid transparent;
        }
        
        .score-card.has-details {
            border-color: #10b981;
        }
        
        .score-card.basic-only {
            border-color: #f59e0b;
        }
        
        .score-card.equipment-warning {
            border-color: #ef4444;
            box-shadow: 0 4px 8px rgba(239, 68, 68, 0.2);
        }
        
        .score-header {
            padding: 1.5rem;
            border-bottom: 1px solid var(--gray-color);
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }
        
        .archer-info h3 {
            color: var(--primary-color);
            margin: 0 0 0.25rem 0;
            font-size: 1.1rem;
        }
        
        .score-date {
            font-size: 0.9rem;
            color: #6b7280;
        }
        
        .score-badges {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            align-items: flex-end;
        }
        
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            padding: 0.25rem 0.5rem;
            font-size: 0.75rem;
            font-weight: 500;
            border-radius: 9999px;
            white-space: nowrap;
        }
        
        .badge-practice {
            background-color: #e5e7eb;
            color: #4b5563;
        }
        
        .badge-competition {
            background-color: #dbeafe;
            color: #2563eb;
        }
        
        .badge-detailed {
            background-color: #d1fae5;
            color: #065f46;
        }
        
        .badge-basic {
            background-color: #fef3c7;
            color: #92400e;
        }
        
        .badge-equipment-ok {
            background-color: #d1fae5;
            color: #065f46;
        }
        
        .badge-equipment-caution {
            background-color: #fef3c7;
            color: #92400e;
        }
        
        .badge-equipment-warning {
            background-color: #fee2e2;
            color: #dc2626;
        }
        
        /* Equipment validation styles */
        .equipment-validation {
            padding: 1rem 1.5rem;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .validation-ok {
            background-color: #f0fdf4;
            border-left: 4px solid #22c55e;
        }
        
        .validation-warning {
            background-color: #fffbeb;
            border-left: 4px solid #f59e0b;
        }
        
        .validation-error {
            background-color: #fef2f2;
            border-left: 4px solid #ef4444;
        }
        
        .validation-header h4 {
            margin: 0 0 0.75rem 0;
            font-size: 1rem;
        }
        
        .validation-ok h4 {
            color: #16a34a;
        }
        
        .validation-warning h4 {
            color: #d97706;
        }
        
        .validation-error h4 {
            color: #dc2626;
        }
        
        .validation-details p {
            margin: 0 0 0.5rem 0;
            font-size: 0.9rem;
        }
        
        .equipment-comparison {
            margin: 0.75rem 0;
        }
        
        .equipment-item {
            display: flex;
            align-items: center;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
        }
        
        .equipment-label {
            min-width: 130px;
            font-weight: 500;
            color: #6b7280;
        }
        
        .equipment-value {
            font-weight: 600;
            margin-right: 0.5rem;
        }
        
        .division-info {
            color: #6b7280;
            font-style: italic;
        }
        
        .validation-suggestion {
            font-style: italic;
            color: #6b7280;
            margin-top: 0.5rem;
        }
        
        .score-details {
            padding: 1rem 1.5rem;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
            background-color: #f9fafb;
        }
        
        .detail-item {
            display: flex;
            flex-direction: column;
        }
        
        .detail-label {
            font-size: 0.9rem;
            color: #6b7280;
            margin-bottom: 0.25rem;
        }
        
        .detail-value {
            font-weight: 500;
        }
        
        .detail-value.highlight {
            color: var(--primary-color);
            font-size: 1.1rem;
        }
        
        .arrow-details-section {
            border-top: 1px solid #e5e7eb;
        }
        
        .arrow-details-header {
            padding: 1rem 1.5rem;
            background-color: #f3f4f6;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .arrow-details-header h4 {
            margin: 0;
            color: var(--primary-color);
        }
        
        .btn-toggle-details {
            background: none;
            border: 1px solid #d1d5db;
            border-radius: 4px;
            padding: 0.5rem 1rem;
            cursor: pointer;
            font-size: 0.875rem;
            color: #374151;
        }
        
        .btn-toggle-details:hover {
            background-color: #f9fafb;
        }
        
        .arrow-details-content {
            padding: 1rem 1.5rem;
        }
        
        .range-section {
            margin-bottom: 1.5rem;
        }
        
        .range-header {
            background: var(--primary-color);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            font-weight: bold;
            margin-bottom: 0.75rem;
            font-size: 0.9rem;
        }
        
        .end-row {
            display: flex;
            align-items: center;
            margin-bottom: 0.5rem;
            padding: 0.5rem;
            background: #f9fafb;
            border-radius: 4px;
        }
        
        .end-label {
            font-weight: bold;
            width: 60px;
            margin-right: 1rem;
            font-size: 0.9rem;
        }
        
        .arrow-scores {
            display: flex;
            gap: 0.5rem;
            flex: 1;
        }
        
        .arrow-score {
            background: white;
            border: 1px solid #d1d5db;
            padding: 0.25rem 0.5rem;
            border-radius: 3px;
            min-width: 25px;
            text-align: center;
            font-weight: bold;
            font-size: 0.875rem;
        }
        
        .arrow-score.x-score {
            background: #fbbf24;
            color: #000;
            border-color: #f59e0b;
        }
        
        .arrow-score.ten-score {
            background: #fde047;
            color: #000;
            border-color: #eab308;
        }
        
        .arrow-score.miss-score {
            background: #f87171;
            color: white;
            border-color: #ef4444;
        }
        
        .end-total {
            font-weight: bold;
            margin-left: 1rem;
            color: var(--primary-color);
            font-size: 0.9rem;
        }
        
        .score-entry-section {
            padding: 1.5rem;
        }
        
        .calculated-score-info {
            background: #d1fae5;
            border: 1px solid #10b981;
            border-radius: 4px;
            padding: 1rem;
            margin-bottom: 1rem;
        }
        
        .calculated-total {
            text-align: center;
            font-size: 1.1rem;
        }
        
        .calculated-total strong {
            font-size: 1.3rem;
            color: var(--primary-color);
        }
        
        .calculated-total small {
            display: block;
            color: #065f46;
            margin-top: 0.25rem;
        }
        
        .manual-score-info {
            background: #fef3c7;
            border: 1px solid #f59e0b;
            border-radius: 4px;
            padding: 1rem;
            margin-bottom: 1rem;
        }
        
        .info-message {
            text-align: center;
            color: #92400e;
            font-size: 0.9rem;
        }
        
        .info-message i {
            margin-right: 0.5rem;
        }
        
        .score-input-group {
            display: flex;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        
        .score-input-group label {
            margin-right: 1rem;
            margin-bottom: 0;
            font-weight: 500;
            min-width: 100px;
        }
        
        .equipment-override-section {
            background: #fef2f2;
            border: 1px solid #fecaca;
            border-radius: 4px;
            padding: 1rem;
            margin-bottom: 1rem;
        }
        
        .action-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
        }
        
        .alert {
            padding: 0.75rem 1rem;
            margin-bottom: 1.5rem;
            border-radius: 4px;
        }
        
        .alert-success {
            background-color: #dcfce7;
            color: #16a34a;
            border-left: 4px solid #16a34a;
        }
        
        .alert-warning {
            background-color: #fef3c7;
            color: #d97706;
            border-left: 4px solid #f59e0b;
        }
        
        .alert-error {
            background-color: #fee2e2;
            color: #dc2626;
            border-left: 4px solid #dc2626;
        }
        
        .alert i {
            margin-right: 0.5rem;
        }
        
        /* Modal styles */
        .modal {
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }
        
        .modal-content {
            background-color: white;
            margin: 15% auto;
            border-radius: 8px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .modal-header {
            padding: 1rem 1.5rem;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .modal-header h3 {
            margin: 0;
            color: var(--primary-color);
        }
        
        .close {
            font-size: 1.5rem;
            cursor: pointer;
            color: #6b7280;
        }
        
        .close:hover {
            color: #374151;
        }
        
        .modal-body {
            padding: 1.5rem;
        }
        
        .modal-footer {
            padding: 1rem 1.5rem;
            border-top: 1px solid #e5e7eb;
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
        }
        
        .override-warning {
            background-color: #fef3c7;
            border: 1px solid #f59e0b;
            border-radius: 4px;
            padding: 1rem;
            margin-bottom: 1rem;
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
        }
        
        .override-warning i {
            color: #d97706;
            margin-top: 0.125rem;
            flex-shrink: 0;
        }
        
        .override-warning p {
            margin: 0;
            color: #92400e;
        }
        
        .btn-warning {
            background-color: #f59e0b;
            color: white;
        }
        
        .btn-warning:hover {
            background-color: #d97706;
        }
        
        .btn-danger {
            background-color: #dc2626;
            color: white;
        }
        
        .btn-danger:hover {
            background-color: #b91c1c;
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .pending-scores-list {
                grid-template-columns: 1fr;
            }
            
            .score-details {
                grid-template-columns: 1fr;
            }
            
            .score-input-group {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .score-input-group label {
                margin-bottom: 0.5rem;
            }
            
            .action-buttons {
                flex-direction: column;
            }
            
            .action-buttons .btn {
                width: 100%;
            }
            
            .arrow-details-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }
            
            .end-row {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.5rem;
            }
            
            .end-label {
                width: auto;
                margin-right: 0;
            }
            
            .modal-content {
                margin: 10% auto;
                width: 95%;
            }
            
            .modal-footer {
                flex-direction: column-reverse;
            }
            
            .modal-footer .btn {
                width: 100%;
            }
            
            .equipment-item {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .equipment-label {
                min-width: auto;
                margin-bottom: 0.25rem;
            }
        }
        
        @media (max-width: 480px) {
            .score-badges {
                align-items: flex-start;
            }
            
            .arrow-scores {
                flex-wrap: wrap;
            }
        }
    </style>
</body>
</html>