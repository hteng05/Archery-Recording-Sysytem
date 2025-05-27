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