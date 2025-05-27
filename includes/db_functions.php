<?php
/**
 * Database functions for Archery Score Recording System
 */

require_once 'settings.php';

/**
 * Get all archers
 */
function getAllArchers() {
    $conn = getDbConnection();
    if (!$conn) {
        return [];
    }
    
    $query = "SELECT a.ArcherID, a.FirstName, a.LastName, c.ClassName, 
              d.DivisionName, e.EquipmentName 
              FROM ArcherTable a
              JOIN ClassTable c ON a.ClassID = c.ClassID
              JOIN DivisionTable d ON a.DefaultDivisionID = d.DivisionID
              JOIN EquipmentTable e ON a.DefaultEquipmentID = e.EquipmentID
              WHERE a.IsActive = 1
              ORDER BY a.LastName, a.FirstName";
    
    $result = $conn->query($query);
    
    if (!$result) {
        return [];
    }
    
    $archers = [];
    while ($row = $result->fetch_assoc()) {
        $archers[] = $row;
    }
    
    return $archers;
}

/**
 * Get archer by ID
 */
function getArcherById($archerId) {
    $conn = getDbConnection();
    if (!$conn) {
        return null;
    }
    
    $stmt = $conn->prepare("SELECT a.*, c.ClassName, d.DivisionName, e.EquipmentName 
                          FROM ArcherTable a
                          JOIN ClassTable c ON a.ClassID = c.ClassID
                          JOIN DivisionTable d ON a.DefaultDivisionID = d.DivisionID
                          JOIN EquipmentTable e ON a.DefaultEquipmentID = e.EquipmentID
                          WHERE a.ArcherID = ? AND a.IsActive = 1");
    $stmt->bind_param("i", $archerId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        return $result->fetch_assoc();
    } else {
        return null;
    }
}

/**
 * Get all rounds
 */
function getAllRounds() {
    $conn = getDbConnection();
    if (!$conn) {
        return [];
    }
    
    $currentDate = date('Y-m-d');
    $query = "SELECT RoundID, RoundName, TotalArrows, IsOfficial 
              FROM RoundTable 
              WHERE DateEffectiveFrom <= ? 
              AND (DateEffectiveTo IS NULL OR DateEffectiveTo >= ?)
              ORDER BY RoundName";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ss", $currentDate, $currentDate);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if (!$result) {
        return [];
    }
    
    $rounds = [];
    while ($row = $result->fetch_assoc()) {
        $rounds[] = $row;
    }
    
    return $rounds;
}

/**
 * Get round details by ID
 */
function getRoundById($roundId) {
    $conn = getDbConnection();
    if (!$conn) {
        return null;
    }
    
    $stmt = $conn->prepare("SELECT * FROM RoundTable WHERE RoundID = ?");
    $stmt->bind_param("i", $roundId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $round = $result->fetch_assoc();
        
        // Get the ranges for this round
        $stmt = $conn->prepare("SELECT * FROM RangeTable WHERE RoundID = ? ORDER BY RangeOrder");
        $stmt->bind_param("i", $roundId);
        $stmt->execute();
        $rangeResult = $stmt->get_result();
        
        $ranges = [];
        while ($rangeRow = $rangeResult->fetch_assoc()) {
            $ranges[] = $rangeRow;
        }
        
        $round['ranges'] = $ranges;
        return $round;
    } else {
        return null;
    }
}

/**
 * Get equivalent rounds
 */
function getEquivalentRounds($roundId, $categoryId) {
    $conn = getDbConnection();
    if (!$conn) {
        return [];
    }
    
    $currentDate = date('Y-m-d');
    $stmt = $conn->prepare("SELECT er.*, r.RoundName 
                          FROM EquivalentRoundTable er
                          JOIN RoundTable r ON er.EquivalentToRoundID = r.RoundID
                          WHERE er.RoundID = ? 
                          AND er.CategoryID = ?
                          AND er.EffectiveFrom <= ?
                          AND (er.EffectiveTo IS NULL OR er.EffectiveTo >= ?)");
    $stmt->bind_param("iiss", $roundId, $categoryId, $currentDate, $currentDate);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if (!$result) {
        return [];
    }
    
    $equivalentRounds = [];
    while ($row = $result->fetch_assoc()) {
        $equivalentRounds[] = $row;
    }
    
    return $equivalentRounds;
}

/**
 * Get all equipment types
 */
function getAllEquipment() {
    $conn = getDbConnection();
    if (!$conn) {
        return [];
    }
    
    $query = "SELECT e.EquipmentID, e.EquipmentName, d.DivisionName 
              FROM EquipmentTable e
              JOIN DivisionTable d ON e.DivisionID = d.DivisionID
              ORDER BY e.EquipmentName";
    
    $result = $conn->query($query);
    
    if (!$result) {
        return [];
    }
    
    $equipment = [];
    while ($row = $result->fetch_assoc()) {
        $equipment[] = $row;
    }
    
    return $equipment;
}

/**
 * Get all competitions
 */
function getAllCompetitions($includeCompleted = true) {
    $conn = getDbConnection();
    if (!$conn) {
        return [];
    }
    
    $query = "SELECT * FROM CompetitionTable";
    
    if (!$includeCompleted) {
        $currentDate = date('Y-m-d');
        $query .= " WHERE EndDate <= '$currentDate'";
    }
    
    $query .= " ORDER BY StartDate DESC";
    
    $result = $conn->query($query);
    
    if (!$result) {
        return [];
    }
    
    $competitions = [];
    while ($row = $result->fetch_assoc()) {
        $competitions[] = $row;
    }
    
    return $competitions;
}

/**
 * Get scores for an archer
 */
function getArcherScores($archerId, $startDate = null, $endDate = null, $roundId = null) {
    $conn = getDbConnection();
    if (!$conn) {
        return [];
    }
    
    $query = "SELECT s.*, r.RoundName, e.EquipmentName, c.CompetitionName 
              FROM ScoreTable s
              JOIN RoundTable r ON s.RoundID = r.RoundID
              JOIN EquipmentTable e ON s.EquipmentID = e.EquipmentID
              LEFT JOIN CompetitionTable c ON s.CompetitionID = c.CompetitionID
              WHERE s.ArcherID = ? AND s.IsApproved = 1";
    
    $params = [$archerId];
    $types = "i";
    
    if ($startDate) {
        $query .= " AND s.DateShot >= ?";
        $params[] = $startDate;
        $types .= "s";
    }
    
    if ($endDate) {
        $query .= " AND s.DateShot <= ?";
        $params[] = $endDate;
        $types .= "s";
    }
    
    if ($roundId) {
        $query .= " AND s.RoundID = ?";
        $params[] = $roundId;
        $types .= "i";
    }
    
    $query .= " ORDER BY s.DateShot DESC, s.TotalScore DESC";
    
    $stmt = $conn->prepare($query);
    // Create references for each parameter
    $bindParams = array();
    $bindParams[] = &$types;
    foreach ($params as $key => $value) {
        $bindParams[] = &$params[$key];
    }
    // Use call_user_func_array with references
    call_user_func_array([$stmt, 'bind_param'], $bindParams);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if (!$result) {
        return [];
    }
    
    $scores = [];
    while ($row = $result->fetch_assoc()) {
        $scores[] = $row;
    }
    
    return $scores;
}

/**
 * Get competition results
 */
function getCompetitionResults($competitionId) {
    $conn = getDbConnection();
    if (!$conn) {
        return [];
    }
    
    $query = "SELECT s.ScoreID, s.TotalScore, s.DateShot, 
              a.ArcherID, a.FirstName, a.LastName,
              c.ClassName, d.DivisionName, 
              r.RoundName, e.EquipmentName
              FROM ScoreTable s
              JOIN ArcherTable a ON s.ArcherID = a.ArcherID
              JOIN ClassTable c ON a.ClassID = c.ClassID
              JOIN DivisionTable d ON a.DefaultDivisionID = d.DivisionID
              JOIN RoundTable r ON s.RoundID = r.RoundID
              JOIN EquipmentTable e ON s.EquipmentID = e.EquipmentID
              WHERE s.CompetitionID = ? AND s.IsApproved = 1
              ORDER BY c.ClassName, d.DivisionName, s.TotalScore DESC";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $competitionId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if (!$result) {
        return [];
    }
    
    $results = [];
    while ($row = $result->fetch_assoc()) {
        $category = $row['ClassName'] . ' ' . $row['DivisionName'];
        
        if (!isset($results[$category])) {
            $results[$category] = [];
        }
        
        $results[$category][] = $row;
    }
    
    return $results;
}

/**
 * Get championship competitions for a specific year
 */
function getChampionshipCompetitions($year) {
    $conn = getDbConnection();
    if (!$conn) {
        return [];
    }
    
    $query = "SELECT * FROM CompetitionTable 
              WHERE ContributesToChampionship = 1 
              AND YEAR(StartDate) = ?
              ORDER BY StartDate ASC";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $year);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $competitions = [];
    while ($row = $result->fetch_assoc()) {
        $competitions[] = $row;
    }
    
    return $competitions;
}

/**
 * Get available championship years
 */
function getChampionshipYears() {
    $conn = getDbConnection();
    if (!$conn) {
        return [date('Y')]; // Return current year as fallback
    }
    
    $query = "SELECT DISTINCT YEAR(StartDate) as Year 
              FROM CompetitionTable 
              WHERE ContributesToChampionship = 1 
              ORDER BY Year DESC";
    
    $result = $conn->query($query);
    
    $years = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $years[] = $row['Year'];
        }
    }
    
    // Always include current year if not already present
    $currentYear = intval(date('Y'));
    if (!in_array($currentYear, $years)) {
        array_unshift($years, $currentYear);
        sort($years);
        $years = array_reverse($years);
    }
    
    return $years;
}

/**
 * Calculate and update championship standings for a given year
 * This should be called after each championship competition is completed
 */
function calculateChampionshipStandings($year) {
    $conn = getDbConnection();
    if (!$conn) {
        return false;
    }
    
    // Get all championship competitions for this year
    $championshipComps = getChampionshipCompetitions($year);
    
    if (empty($championshipComps)) {
        return true; // No competitions to calculate
    }
    
    // Begin transaction
    $conn->autocommit(FALSE);
    
    try {
        // Clear existing standings for this year
        $stmt = $conn->prepare("DELETE FROM ChampionshipStandingTable WHERE Year = ?");
        $stmt->bind_param("i", $year);
        $stmt->execute();
        
        // Get all categories
        $categories = getAllCategories();
        
        foreach ($categories as $category) {
            $categoryId = $category['CategoryID'];
            $archerPoints = [];
            
            // Calculate points for each championship competition
            foreach ($championshipComps as $comp) {
                $competitionId = $comp['CompetitionID'];
                $results = getCompetitionResults($competitionId);
                
                // Find results for this category
                $categoryName = $category['CategoryName'];
                if (isset($results[$categoryName])) {
                    $categoryResults = $results[$categoryName];
                    
                    // Award points based on placement
                    foreach ($categoryResults as $index => $archer) {
                        $archerId = $archer['ArcherID'];
                        $placement = $index + 1; // 1st, 2nd, 3rd, etc.
                        $points = calculatePlacementPoints($placement);
                        
                        if (!isset($archerPoints[$archerId])) {
                            $archerPoints[$archerId] = 0;
                        }
                        $archerPoints[$archerId] += $points;
                    }
                }
            }
            
            // Sort archers by total points (descending)
            arsort($archerPoints);
            
            // Insert standings into database
            $rank = 1;
            foreach ($archerPoints as $archerId => $totalPoints) {
                // Check minimum participation requirement (50% of competitions)
                $participationCount = getArcherChampionshipParticipationCount($archerId, $year);
                $minRequired = ceil(count($championshipComps) * 0.5);
                
                if ($participationCount >= $minRequired) {
                    $stmt = $conn->prepare("INSERT INTO ChampionshipStandingTable 
                                          (ChampionshipID, Year, CategoryID, ArcherID, TotalPoints, Rank) 
                                          VALUES (1, ?, ?, ?, ?, ?)");
                    $stmt->bind_param("iiiii", $year, $categoryId, $archerId, $totalPoints, $rank);
                    $stmt->execute();
                    $rank++;
                }
            }
        }
        
        // Commit transaction
        $conn->commit();
        $conn->autocommit(TRUE);
        return true;
        
    } catch (Exception $e) {
        // Rollback on error
        $conn->rollback();
        $conn->autocommit(TRUE);
        error_log("Error calculating championship standings: " . $e->getMessage());
        return false;
    }
}

/**
 * Calculate points based on placement
 */
function calculatePlacementPoints($placement) {
    $pointsTable = [
        1 => 25,  // 1st place
        2 => 18,  // 2nd place
        3 => 15,  // 3rd place
        4 => 12,  // 4th place
        5 => 10,  // 5th place
        6 => 8,   // 6th place
        7 => 6,   // 7th place
        8 => 4,   // 8th place
        9 => 2,   // 9th place
    ];
    
    return isset($pointsTable[$placement]) ? $pointsTable[$placement] : 1; // 10th and below get 1 point
}

/**
 * Get archer's participation in championship competitions for a year
 */
function getArcherChampionshipParticipation($archerId, $year) {
    $conn = getDbConnection();
    if (!$conn) {
        return [];
    }
    
    $query = "SELECT DISTINCT c.CompetitionID, c.CompetitionName, c.StartDate, s.TotalScore
              FROM CompetitionTable c
              JOIN ScoreTable s ON c.CompetitionID = s.CompetitionID
              WHERE c.ContributesToChampionship = 1 
              AND YEAR(c.StartDate) = ?
              AND s.ArcherID = ?
              AND s.IsApproved = 1
              ORDER BY c.StartDate";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $year, $archerId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $competitions = [];
    while ($row = $result->fetch_assoc()) {
        $competitions[] = $row;
    }
    
    return $competitions;
}

/**
 * Get count of archer's participation in championship competitions
 */
function getArcherChampionshipParticipationCount($archerId, $year) {
    $participation = getArcherChampionshipParticipation($archerId, $year);
    return count($participation);
}

/**
 * Get archer's best score from championship competitions in a year
 */
function getArcherBestChampionshipScore($archerId, $year) {
    $conn = getDbConnection();
    if (!$conn) {
        return null;
    }
    
    $query = "SELECT MAX(s.TotalScore) as BestScore
              FROM CompetitionTable c
              JOIN ScoreTable s ON c.CompetitionID = s.CompetitionID
              WHERE c.ContributesToChampionship = 1 
              AND YEAR(c.StartDate) = ?
              AND s.ArcherID = ?
              AND s.IsApproved = 1";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $year, $archerId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return $row['BestScore'];
    }
    
    return null;
}

/**
 * Get championship standings for a specific year (enhanced version)
 */
function getChampionshipStandings($year = null) {
    $conn = getDbConnection();
    if (!$conn) {
        return [];
    }
    
    if (!$year) {
        $year = date('Y');
    }
    
    $query = "SELECT cs.*, a.FirstName, a.LastName, cat.CategoryName, cat.CategoryID
              FROM ChampionshipStandingTable cs
              JOIN ArcherTable a ON cs.ArcherID = a.ArcherID
              JOIN CategoryTable cat ON cs.CategoryID = cat.CategoryID
              WHERE cs.Year = ?
              ORDER BY cs.CategoryID, cs.Rank";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $year);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if (!$result) {
        return [];
    }
    
    $standings = [];
    while ($row = $result->fetch_assoc()) {
        $category = $row['CategoryName'];
        
        if (!isset($standings[$category])) {
            $standings[$category] = [];
        }
        
        $standings[$category][] = $row;
    }
    
    return $standings;
}

/**
 * Automatically update championship standings when a competition score is approved
 * Call this function after approving any score from a championship competition
 */
function updateChampionshipStandingsIfNeeded($competitionId) {
    $conn = getDbConnection();
    if (!$conn) {
        return false;
    }
    
    // Check if this competition contributes to championship
    $stmt = $conn->prepare("SELECT ContributesToChampionship, YEAR(StartDate) as CompYear 
                          FROM CompetitionTable WHERE CompetitionID = ?");
    $stmt->bind_param("i", $competitionId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result && $result->num_rows > 0) {
        $comp = $result->fetch_assoc();
        
        if ($comp['ContributesToChampionship']) {
            // Recalculate standings for this year
            return calculateChampionshipStandings($comp['CompYear']);
        }
    }
    
    return true;
}

/**
 * Get championship summary for an archer
 */
function getArcherChampionshipSummary($archerId, $year = null) {
    if (!$year) {
        $year = date('Y');
    }
    
    $summary = [
        'year' => $year,
        'total_points' => 0,
        'rank' => null,
        'category' => null,
        'competitions_participated' => 0,
        'total_competitions' => 0,
        'best_score' => null,
        'participation_percentage' => 0
    ];
    
    // Get archer's category
    $archer = getArcherById($archerId);
    if (!$archer) {
        return $summary;
    }
    
    // Get total championship competitions for the year
    $totalComps = count(getChampionshipCompetitions($year));
    $summary['total_competitions'] = $totalComps;
    
    // Get archer's participation
    $participation = getArcherChampionshipParticipation($archerId, $year);
    $summary['competitions_participated'] = count($participation);
    
    if ($totalComps > 0) {
        $summary['participation_percentage'] = round(($summary['competitions_participated'] / $totalComps) * 100);
    }
    
    // Get best score
    $summary['best_score'] = getArcherBestChampionshipScore($archerId, $year);
    
    // Get standings info
    $standings = getChampionshipStandings($year);
    foreach ($standings as $category => $archers) {
        foreach ($archers as $standing) {
            if ($standing['ArcherID'] == $archerId) {
                $summary['total_points'] = $standing['TotalPoints'];
                $summary['rank'] = $standing['Rank'];
                $summary['category'] = $category;
                break 2;
            }
        }
    }
    
    return $summary;
}

/**
 * Enhanced approveScore function that updates championship standings
 * (This replaces the previous approveScore function)
 */
function approveScoreWithChampionshipUpdate($stageId, $totalScore = null, $validateEquipment = true) {
    // First approve the score using the existing function
    $result = approveScore($stageId, $totalScore, $validateEquipment);
    
    if ($result !== false && is_numeric($result)) {
        // Get the staged score to find the competition ID
        $stagedScore = getStagedScoreWithDetails($stageId);
        if ($stagedScore && $stagedScore['CompetitionID']) {
            // Update championship standings if this was a championship competition
            updateChampionshipStandingsIfNeeded($stagedScore['CompetitionID']);
        }
    }
    
    return $result;
}

/**
 * Get personal best scores
 */
function getPersonalBests($archerId) {
    $conn = getDbConnection();
    if (!$conn) {
        return [];
    }
    
    $query = "SELECT pb.*, r.RoundName, e.EquipmentName, s.TotalScore
              FROM PersonalBestTable pb
              JOIN RoundTable r ON pb.RoundID = r.RoundID
              JOIN EquipmentTable e ON pb.EquipmentID = e.EquipmentID
              JOIN ScoreTable s ON pb.ScoreID = s.ScoreID
              WHERE pb.ArcherID = ?
              ORDER BY r.RoundName, e.EquipmentName";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $archerId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if (!$result) {
        return [];
    }
    
    $personalBests = [];
    while ($row = $result->fetch_assoc()) {
        $personalBests[] = $row;
    }
    
    return $personalBests;
}

/**
 * Get club best scores
 */
function getClubBests($categoryId = null) {
    $conn = getDbConnection();
    if (!$conn) {
        return [];
    }
    
    $query = "SELECT cb.*, r.RoundName, cat.CategoryName, 
              a.FirstName, a.LastName, s.TotalScore
              FROM ClubBestTable cb
              JOIN RoundTable r ON cb.RoundID = r.RoundID
              JOIN CategoryTable cat ON cb.CategoryID = cat.CategoryID
              JOIN ArcherTable a ON cb.ArcherID = a.ArcherID
              JOIN ScoreTable s ON cb.ScoreID = s.ScoreID";
    
    if ($categoryId) {
        $query .= " WHERE cb.CategoryID = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $categoryId);
    } else {
        $query .= " ORDER BY cat.CategoryName, r.RoundName";
        $stmt = $conn->prepare($query);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    if (!$result) {
        return [];
    }
    
    $clubBests = [];
    while ($row = $result->fetch_assoc()) {
        $category = $row['CategoryName'];
        
        if (!isset($clubBests[$category])) {
            $clubBests[$category] = [];
        }
        
        $clubBests[$category][] = $row;
    }
    
    return $clubBests;
}

/**
 * Convert score value (X, 10, 9, M, etc.) to numeric score
 */
function convertScoreValueToNumeric($scoreValue) {
    $scoreValue = strtoupper(trim($scoreValue));
    
    switch ($scoreValue) {
        case 'X':
            return 10;
        case 'M':
        case '':
            return 0;
        default:
            return intval($scoreValue);
    }
}

/**
 * Get staged scores awaiting approval
 */
function getStagedScores() {
    $conn = getDbConnection();
    if (!$conn) {
        return [];
    }
    
    $query = "SELECT ss.*, a.FirstName, a.LastName, r.RoundName, e.EquipmentName,
              COUNT(ssd.DetailID) as ArrowCount
              FROM ScoreStagingTable ss
              JOIN ArcherTable a ON ss.ArcherID = a.ArcherID
              JOIN RoundTable r ON ss.RoundID = r.RoundID
              JOIN EquipmentTable e ON ss.EquipmentID = e.EquipmentID
              LEFT JOIN ScoreStagingDetailTable ssd ON ss.StageID = ssd.StageID
              GROUP BY ss.StageID
              ORDER BY ss.Date DESC, ss.Time DESC";
    
    $result = $conn->query($query);
    
    if (!$result) {
        return [];
    }
    
    $stagedScores = [];
    while ($row = $result->fetch_assoc()) {
        $stagedScores[] = $row;
    }
    
    return $stagedScores;
}

/**
 * Add new archer
 */
function addArcher($firstName, $lastName, $gender, $dob, $classId, $divisionId, $equipmentId) {
    $conn = getDbConnection();
    if (!$conn) {
        return false;
    }
    
    $stmt = $conn->prepare("INSERT INTO ArcherTable (FirstName, LastName, Gender, DOB, ClassID, 
                          DefaultDivisionID, DefaultEquipmentID, IsActive) 
                          VALUES (?, ?, ?, ?, ?, ?, ?, 1)");
    $stmt->bind_param("ssssiii", $firstName, $lastName, $gender, $dob, $classId, $divisionId, $equipmentId);
    
    return $stmt->execute();
}

/**
 * Add new staged score
 */

/**
 * Add new staged score with detailed arrow data
 */
function addStagedScore($archerId, $roundId, $equipmentId, $date, $time, $isPractice, $isCompetition, $competitionId = null, $arrowScores = null, $totalScore = null) {
    $conn = getDbConnection();
    if (!$conn) {
        return false;
    }
    
    // Begin transaction
    $conn->autocommit(FALSE);
    
    try {
        // Insert basic staged score
        $stmt = $conn->prepare("INSERT INTO ScoreStagingTable (ArcherID, RoundID, EquipmentID, Date, Time, 
                              IsPractice, IsCompetition, CompetitionID, TotalScore) 
                              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        if (!$stmt) {
            throw new Exception("Prepare statement failed: " . $conn->error);
        }
        
        $stmt->bind_param("iiissiiis", $archerId, $roundId, $equipmentId, $date, $time, $isPractice, $isCompetition, $competitionId, $totalScore);
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to insert staged score: " . $stmt->error);
        }
        
        $stageId = $conn->insert_id;
        
        // If arrow scores are provided, save them to staging details table
        if ($arrowScores && is_array($arrowScores)) {
            $stmt = $conn->prepare("INSERT INTO ScoreStagingDetailTable (StageID, RangeIndex, RangeDistance, 
                                  RangeFaceSize, EndNumber, ArrowNumber, ScoreValue) 
                                  VALUES (?, ?, ?, ?, ?, ?, ?)");
            if (!$stmt) {
                throw new Exception("Prepare statement for arrow scores failed: " . $conn->error);
            }
            
            foreach ($arrowScores as $arrow) {
                $stmt->bind_param("iiiiiss", 
                    $stageId,
                    $arrow['range_index'],
                    $arrow['range_distance'],
                    $arrow['range_face_size'],
                    $arrow['end_number'],
                    $arrow['arrow_number'],
                    $arrow['score_value']
                );
                
                if (!$stmt->execute()) {
                    throw new Exception("Failed to insert arrow score: " . $stmt->error);
                }
            }
        }
        
        // Commit transaction
        $conn->commit();
        $conn->autocommit(TRUE);
        return $stageId;
        
    } catch (Exception $e) {
        // Rollback on error
        $conn->rollback();
        $conn->autocommit(TRUE);
        error_log("Error adding staged score: " . $e->getMessage());
        return false;
    }
}

/**
 * Get staged score with arrow details
 */
function getStagedScoreWithDetails($stageId) {
    $conn = getDbConnection();
    if (!$conn) {
        return null;
    }
    
    // Get basic staged score info
    $stmt = $conn->prepare("SELECT ss.*, a.FirstName, a.LastName, r.RoundName, e.EquipmentName
                          FROM ScoreStagingTable ss
                          JOIN ArcherTable a ON ss.ArcherID = a.ArcherID
                          JOIN RoundTable r ON ss.RoundID = r.RoundID
                          JOIN EquipmentTable e ON ss.EquipmentID = e.EquipmentID
                          WHERE ss.StageID = ?");
    $stmt->bind_param("i", $stageId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows !== 1) {
        return null;
    }
    
    $stagedScore = $result->fetch_assoc();
    
    // Get arrow details if they exist
    $stmt = $conn->prepare("SELECT * FROM ScoreStagingDetailTable 
                          WHERE StageID = ? 
                          ORDER BY RangeIndex, EndNumber, ArrowNumber");
    $stmt->bind_param("i", $stageId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $arrowScores = [];
    while ($row = $result->fetch_assoc()) {
        $arrowScores[] = $row;
    }
    
    $stagedScore['arrow_scores'] = $arrowScores;
    return $stagedScore;
}

/**
 * Approve staged score
 */
function approveScore($stageId, $totalScore = null, $validateEquipment = true) {
    $conn = getDbConnection();
    if (!$conn) {
        return false;
    }
    
    // Get staged score details including arrow scores
    $stagedScore = getStagedScoreWithDetails($stageId);
    if (!$stagedScore) {
        error_log("approveScore: Could not find staged score with ID: $stageId");
        return false;
    }
    
    // EQUIPMENT VALIDATION - Check if equipment matches archer's default or is acceptable
    if ($validateEquipment) {
        $equipmentValidation = validateArcherEquipment($stagedScore['ArcherID'], $stagedScore['EquipmentID']);
        if (!$equipmentValidation['valid']) {
            error_log("approveScore: Equipment validation failed for archer {$stagedScore['ArcherID']}: " . $equipmentValidation['reason']);
            return ['error' => 'equipment_mismatch', 'message' => $equipmentValidation['reason'], 'details' => $equipmentValidation];
        }
    }
    
    // Use provided total score or calculate from staged data
    if ($totalScore === null) {
        $totalScore = isset($stagedScore['TotalScore']) ? $stagedScore['TotalScore'] : 0;
    }
    
    // Begin transaction
    $conn->autocommit(FALSE);
    
    try {
        // Insert into ScoreTable
        $stmt = $conn->prepare("INSERT INTO ScoreTable (ArcherID, RoundID, EquipmentID, CompetitionID, 
                              DateShot, TotalScore, IsPractice, IsApproved) 
                              VALUES (?, ?, ?, ?, ?, ?, ?, 1)");
        $stmt->bind_param("iiiisii", 
                        $stagedScore['ArcherID'], 
                        $stagedScore['RoundID'], 
                        $stagedScore['EquipmentID'], 
                        $stagedScore['CompetitionID'], 
                        $stagedScore['Date'], 
                        $totalScore, 
                        $stagedScore['IsPractice']);
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to insert score");
        }
        
        $scoreId = $conn->insert_id;
        
        // If we have arrow score details, save them
        if (!empty($stagedScore['arrow_scores'])) {
            // Get round details to map ranges
            $round = getRoundById($stagedScore['RoundID']);
            if (!$round || !isset($round['ranges'])) {
                throw new Exception("Could not get round details");
            }
            
            // Group arrow scores by range and end
            $endData = [];
            foreach ($stagedScore['arrow_scores'] as $arrow) {
                $rangeIndex = $arrow['RangeIndex'];
                $endNumber = $arrow['EndNumber'];
                $key = $rangeIndex . '_' . $endNumber;
                
                if (!isset($endData[$key])) {
                    $endData[$key] = [
                        'range_index' => $rangeIndex,
                        'end_number' => $endNumber,
                        'range_distance' => $arrow['RangeDistance'],
                        'range_face_size' => $arrow['RangeFaceSize'],
                        'arrows' => []
                    ];
                }
                
                $endData[$key]['arrows'][] = [
                    'arrow_number' => $arrow['ArrowNumber'],
                    'score_value' => $arrow['ScoreValue']
                ];
            }
            
            // Create ends and arrows
            foreach ($endData as $endInfo) {
                // Find the correct range ID
                $rangeId = null;
                if (isset($round['ranges'][$endInfo['range_index']])) {
                    $rangeId = $round['ranges'][$endInfo['range_index']]['RangeID'];
                }

                if (!$rangeId) {
                    throw new Exception("Could not find range ID for range index " . $endInfo['range_index']);
                }
                
                // Insert end
                $stmt = $conn->prepare("INSERT INTO EndTable (ScoreID, RangeID, EndNumber) VALUES (?, ?, ?)");
                $stmt->bind_param("iii", $scoreId, $rangeId, $endInfo['end_number']);
                
                if (!$stmt->execute()) {
                    throw new Exception("Failed to insert end");
                }
                
                $endId = $conn->insert_id;
                
                // Insert arrows for this end
                $stmt = $conn->prepare("INSERT INTO ArrowTable (EndID, Score) VALUES (?, ?)");
                
                foreach ($endInfo['arrows'] as $arrow) {
                    // Convert score value to numeric score
                    $numericScore = convertScoreValueToNumeric($arrow['score_value']);
                    
                    $stmt->bind_param("ii", $endId, $numericScore);
                    
                    if (!$stmt->execute()) {
                        throw new Exception("Failed to insert arrow score");
                    }
                }
            }
        }
        
        // Check if this is a personal best
        checkAndUpdatePersonalBest($stagedScore['ArcherID'], $stagedScore['RoundID'], $stagedScore['EquipmentID'], $scoreId, $totalScore, $stagedScore['Date'], $conn);
        
        // Check if this is a club best
        checkAndUpdateClubBest($stagedScore['ArcherID'], $stagedScore['RoundID'], $scoreId, $totalScore, $stagedScore['Date'], $conn);
        
        // Delete from staging tables
        $stmt = $conn->prepare("DELETE FROM ScoreStagingDetailTable WHERE StageID = ?");
        $stmt->bind_param("i", $stageId);
        $stmt->execute();
        
        $stmt = $conn->prepare("DELETE FROM ScoreStagingTable WHERE StageID = ?");
        $stmt->bind_param("i", $stageId);
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to delete staged score");
        }
        
        // Commit transaction
        $conn->commit();
        $conn->autocommit(TRUE);
        return $scoreId;
        
    } catch (Exception $e) {
        // Rollback on error
        $conn->rollback();
        $conn->autocommit(TRUE);
        error_log("Error approving score: " . $e->getMessage());
        return false;
    }
}

/**
 * Validate archer's equipment against their default or allowed equipment
 */
function validateArcherEquipment($archerId, $equipmentId) {
    $conn = getDbConnection();
    if (!$conn) {
        return ['valid' => false, 'reason' => 'Database connection failed'];
    }
    
    // Get archer's default equipment and division
    $stmt = $conn->prepare("SELECT a.DefaultEquipmentID, a.FirstName, a.LastName, a.DefaultDivisionID,
                          de.EquipmentName as DefaultEquipmentName, de.DivisionID as DefaultDivisionID,
                          se.EquipmentName as SelectedEquipmentName, se.DivisionID as SelectedDivisionID,
                          dd.DivisionName as DefaultDivisionName, sd.DivisionName as SelectedDivisionName
                          FROM ArcherTable a
                          JOIN EquipmentTable de ON a.DefaultEquipmentID = de.EquipmentID
                          JOIN EquipmentTable se ON se.EquipmentID = ?
                          JOIN DivisionTable dd ON de.DivisionID = dd.DivisionID
                          JOIN DivisionTable sd ON se.DivisionID = sd.DivisionID
                          WHERE a.ArcherID = ?");
    
    $stmt->bind_param("ii", $equipmentId, $archerId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows !== 1) {
        return ['valid' => false, 'reason' => 'Archer or equipment not found'];
    }
    
    $data = $result->fetch_assoc();
    
    // Check 1: Exact match with default equipment (always valid)
    if ($data['DefaultEquipmentID'] == $equipmentId) {
        return [
            'valid' => true, 
            'reason' => 'Equipment matches archer\'s default equipment',
            'match_type' => 'exact_default',
            'archer_name' => $data['FirstName'] . ' ' . $data['LastName'],
            'default_equipment' => $data['DefaultEquipmentName'],
            'selected_equipment' => $data['SelectedEquipmentName']
        ];
    }
    
    // Check 2: Same division (usually acceptable for practice scores)
    if ($data['DefaultDivisionID'] == $data['SelectedDivisionID']) {
        return [
            'valid' => true, 
            'reason' => 'Equipment is from the same division (' . $data['DefaultDivisionName'] . ')',
            'match_type' => 'same_division',
            'archer_name' => $data['FirstName'] . ' ' . $data['LastName'],
            'default_equipment' => $data['DefaultEquipmentName'],
            'selected_equipment' => $data['SelectedEquipmentName'],
            'warning' => 'Equipment differs from archer\'s default but is in the same division'
        ];
    }
    
    // Check 3: Different division - usually requires recorder approval
    return [
        'valid' => false,
        'reason' => sprintf(
            'Equipment mismatch: Archer %s\'s default is %s (%s) but scored with %s (%s)', 
            $data['FirstName'] . ' ' . $data['LastName'],
            $data['DefaultEquipmentName'],
            $data['DefaultDivisionName'],
            $data['SelectedEquipmentName'], 
            $data['SelectedDivisionName']
        ),
        'match_type' => 'different_division',
        'archer_name' => $data['FirstName'] . ' ' . $data['LastName'],
        'default_equipment' => $data['DefaultEquipmentName'],
        'default_division' => $data['DefaultDivisionName'],
        'selected_equipment' => $data['SelectedEquipmentName'],
        'selected_division' => $data['SelectedDivisionName'],
        'suggestion' => 'Recorder should verify this is correct before approving'
    ];
}

/**
 * Override equipment validation and approve score anyway
 */
function approveScoreWithEquipmentOverride($stageId, $totalScore = null, $overrideReason = '') {
    error_log("approveScore: Equipment validation overridden for stage ID $stageId. Reason: $overrideReason");
    
    // Call approve score with validation disabled
    return approveScore($stageId, $totalScore, false);
}

/**
 * Get equipment validation status for a staged score
 */
function getEquipmentValidationForStaging($stageId) {
    $stagedScore = getStagedScoreWithDetails($stageId);
    if (!$stagedScore) {
        return ['valid' => false, 'reason' => 'Staged score not found'];
    }
    
    return validateArcherEquipment($stagedScore['ArcherID'], $stagedScore['EquipmentID']);
}


/**
 * Check and update personal best
 */
function checkAndUpdatePersonalBest($archerId, $roundId, $equipmentId, $scoreId, $totalScore, $dateShot, $conn = null) {
    if (!$conn) {
        $conn = getDbConnection();
        if (!$conn) {
            return false;
        }
    }
    
    // Check if a personal best already exists
    $stmt = $conn->prepare("SELECT * FROM PersonalBestTable 
                          WHERE ArcherID = ? AND RoundID = ? AND EquipmentID = ?");
    $stmt->bind_param("iii", $archerId, $roundId, $equipmentId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        // No existing PB, insert new one
        $stmt = $conn->prepare("INSERT INTO PersonalBestTable (ArcherID, RoundID, EquipmentID, ScoreID, DateAchieved) 
                              VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("iiiis", $archerId, $roundId, $equipmentId, $scoreId, $dateShot);
        $stmt->execute();
        
        // Update ScoreTable to mark as personal best
        $stmt = $conn->prepare("UPDATE ScoreTable SET IsPersonalBest = 1 WHERE ScoreID = ?");
        $stmt->bind_param("i", $scoreId);
        $stmt->execute();
        
        return true;
    } else {
        $pb = $result->fetch_assoc();
        
        // Get the score of the existing PB
        $stmt = $conn->prepare("SELECT TotalScore FROM ScoreTable WHERE ScoreID = ?");
        $stmt->bind_param("i", $pb['ScoreID']);
        $stmt->execute();
        $scoreResult = $stmt->get_result();
        $existingScore = $scoreResult->fetch_assoc()['TotalScore'];
        
        if ($totalScore > $existingScore) {
            // New score is better, update PB
            $stmt = $conn->prepare("UPDATE PersonalBestTable 
                                  SET ScoreID = ?, DateAchieved = ? 
                                  WHERE ArcherID = ? AND RoundID = ? AND EquipmentID = ?");
            $stmt->bind_param("isiii", $scoreId, $dateShot, $archerId, $roundId, $equipmentId);
            $stmt->execute();
            
            // Update old score to remove PB flag
            $stmt = $conn->prepare("UPDATE ScoreTable SET IsPersonalBest = 0 WHERE ScoreID = ?");
            $stmt->bind_param("i", $pb['ScoreID']);
            $stmt->execute();
            
            // Update new score to add PB flag
            $stmt = $conn->prepare("UPDATE ScoreTable SET IsPersonalBest = 1 WHERE ScoreID = ?");
            $stmt->bind_param("i", $scoreId);
            $stmt->execute();
            
            return true;
        }
    }
    
    return false;
}

/**
 * Check and update club best
 */
function checkAndUpdateClubBest($archerId, $roundId, $scoreId, $totalScore, $dateShot, $conn = null) {
    if (!$conn) {
        $conn = getDbConnection();
        if (!$conn) {
            return false;
        }
    }
    
    // Get the archer's category
    $stmt = $conn->prepare("SELECT a.ClassID, a.DefaultDivisionID 
                          FROM ArcherTable a WHERE a.ArcherID = ?");
    $stmt->bind_param("i", $archerId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows !== 1) {
        return false;
    }
    
    $archer = $result->fetch_assoc();
    
    // Get the category ID
    $stmt = $conn->prepare("SELECT CategoryID FROM CategoryTable 
                          WHERE ClassID = ? AND DivisionID = ?");
    $stmt->bind_param("ii", $archer['ClassID'], $archer['DefaultDivisionID']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows !== 1) {
        return false;
    }
    
    $categoryId = $result->fetch_assoc()['CategoryID'];
    
    // Check if a club best already exists
    $stmt = $conn->prepare("SELECT * FROM ClubBestTable 
                          WHERE CategoryID = ? AND RoundID = ?");
    $stmt->bind_param("ii", $categoryId, $roundId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        // No existing club best, insert new one
        $stmt = $conn->prepare("INSERT INTO ClubBestTable 
                              (CategoryID, RoundID, ScoreID, ArcherID, DateAchieved) 
                              VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("iiiis", $categoryId, $roundId, $scoreId, $archerId, $dateShot);
        $stmt->execute();
        
        // Update ScoreTable to mark as club best
        $stmt = $conn->prepare("UPDATE ScoreTable SET IsClubBest = 1 WHERE ScoreID = ?");
        $stmt->bind_param("i", $scoreId);
        $stmt->execute();
        
        return true;
    } else {
        $cb = $result->fetch_assoc();
        
        // Get the score of the existing club best
        $stmt = $conn->prepare("SELECT TotalScore FROM ScoreTable WHERE ScoreID = ?");
        $stmt->bind_param("i", $cb['ScoreID']);
        $stmt->execute();
        $scoreResult = $stmt->get_result();
        $existingScore = $scoreResult->fetch_assoc()['TotalScore'];
        
        if ($totalScore > $existingScore) {
            // New score is better, update club best
            $stmt = $conn->prepare("UPDATE ClubBestTable 
                                  SET ScoreID = ?, ArcherID = ?, DateAchieved = ? 
                                  WHERE CategoryID = ? AND RoundID = ?");
            $stmt->bind_param("iisii", $scoreId, $archerId, $dateShot, $categoryId, $roundId);
            $stmt->execute();
            
            // Update old score to remove club best flag
            $stmt = $conn->prepare("UPDATE ScoreTable SET IsClubBest = 0 WHERE ScoreID = ?");
            $stmt->bind_param("i", $cb['ScoreID']);
            $stmt->execute();
            
            // Update new score to add club best flag
            $stmt = $conn->prepare("UPDATE ScoreTable SET IsClubBest = 1 WHERE ScoreID = ?");
            $stmt->bind_param("i", $scoreId);
            $stmt->execute();
            
            return true;
        }
    }
    
    return false;
}

/**
 * Get end scores for a score
 */
function getEndScores($scoreId) {
    $conn = getDbConnection();
    if (!$conn) {
        return [];
    }
    
    $query = "SELECT e.*, r.Distance, r.TargetFaceSize, r.NumberOfEnds, r.ArrowsPerEnd
              FROM EndTable e
              JOIN RangeTable r ON e.RangeID = r.RangeID
              WHERE e.ScoreID = ?
              ORDER BY r.RangeOrder, e.EndNumber";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $scoreId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if (!$result) {
        return [];
    }
    
    $ends = [];
    while ($row = $result->fetch_assoc()) {
        $endId = $row['EndID'];
        
        // Get arrow scores for this end
        $arrowStmt = $conn->prepare("SELECT * FROM ArrowTable WHERE EndID = ? ORDER BY Score DESC");
        $arrowStmt->bind_param("i", $endId);
        $arrowStmt->execute();
        $arrowResult = $arrowStmt->get_result();
        
        $arrows = [];
        while ($arrowRow = $arrowResult->fetch_assoc()) {
            $arrows[] = $arrowRow;
        }
        
        $row['arrows'] = $arrows;
        $ends[] = $row;
    }
    
    return $ends;
}

/**
 * Add a new competition
 */
function addCompetition($name, $startDate, $endDate, $location, $isOfficial, $isChampionship, $contributesToChampionship) {
    $conn = getDbConnection();
    if (!$conn) {
        return false;
    }
    
    $stmt = $conn->prepare("INSERT INTO CompetitionTable 
                          (CompetitionName, StartDate, EndDate, Location, IsOfficial, IsChampionship, ContributesToChampionship) 
                          VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssiis", $name, $startDate, $endDate, $location, $isOfficial, $isChampionship, $contributesToChampionship);
    
    return $stmt->execute() ? $conn->insert_id : false;
}

/**
 * Get all classes
 */
function getAllClasses() {
    $conn = getDbConnection();
    if (!$conn) {
        return [];
    }
    
    $query = "SELECT * FROM ClassTable ORDER BY Gender, MinAge";
    $result = $conn->query($query);
    
    if (!$result) {
        return [];
    }
    
    $classes = [];
    while ($row = $result->fetch_assoc()) {
        $classes[] = $row;
    }
    
    return $classes;
}

/**
 * Get all divisions
 */
function getAllDivisions() {
    $conn = getDbConnection();
    if (!$conn) {
        return [];
    }
    
    $query = "SELECT * FROM DivisionTable ORDER BY DivisionName";
    $result = $conn->query($query);
    
    if (!$result) {
        return [];
    }
    
    $divisions = [];
    while ($row = $result->fetch_assoc()) {
        $divisions[] = $row;
    }
    
    return $divisions;
}

/**
 * Get all categories
 */
function getAllCategories() {
    $conn = getDbConnection();
    if (!$conn) {
        return [];
    }
    
    $query = "SELECT c.*, cl.ClassName, d.DivisionName 
              FROM CategoryTable c
              JOIN ClassTable cl ON c.ClassID = cl.ClassID
              JOIN DivisionTable d ON c.DivisionID = d.DivisionID
              ORDER BY c.CategoryName";
    
    $result = $conn->query($query);
    
    if (!$result) {
        return [];
    }
    
    $categories = [];
    while ($row = $result->fetch_assoc()) {
        $categories[] = $row;
    }
    
    return $categories;
}

/**
 * Reject and remove a staged score
 */
function rejectStagedScore($stageId, $reason = '') {
    $conn = getDbConnection();
    if (!$conn) {
        return false;
    }
    
    // Begin transaction
    $conn->autocommit(FALSE);
    
    try {
        // Log the rejection (optional - you could create a rejection log table)
        // For now, we'll just delete the staged score
        
        // Delete detailed arrow scores first (if they exist)
        $stmt = $conn->prepare("DELETE FROM ScoreStagingDetailTable WHERE StageID = ?");
        $stmt->bind_param("i", $stageId);
        $stmt->execute();
        
        // Delete the main staged score
        $stmt = $conn->prepare("DELETE FROM ScoreStagingTable WHERE StageID = ?");
        $stmt->bind_param("i", $stageId);
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to delete staged score");
        }
        
        // Check if any rows were affected
        if ($stmt->affected_rows === 0) {
            throw new Exception("No staged score found with that ID");
        }
        
        // Commit transaction
        $conn->commit();
        $conn->autocommit(TRUE);
        return true;
        
    } catch (Exception $e) {
        // Rollback on error
        $conn->rollback();
        $conn->autocommit(TRUE);
        error_log("Error rejecting staged score: " . $e->getMessage());
        return false;
    }
}
