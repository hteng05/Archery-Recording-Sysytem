<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Archery Score Recording System</title>
    <link rel="stylesheet" href="css/main.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="container">
        <header>
            <div class="logo">
                <img src="images/archery-logo.png" alt="Archery Club Logo" onerror="this.src='images/default-logo.png'">
                <h1>Archery Score Recording System</h1>
            </div>
        </header>

        <main class="welcome-screen">
            <div class="role-selection">
                <h2>Welcome to the Archery Score Recording System</h2>
                <p>Please select your role to continue:</p>
                
                <div class="role-buttons">
                    <a href="archer/dashboard.php" class="btn btn-primary">
                        <i class="fas fa-bullseye"></i> I am an Archer
                    </a>
                    <a href="recorder/login.php" class="btn btn-secondary">
                        <i class="fas fa-clipboard-list"></i> I am a Recorder
                    </a>
                </div>
            </div>
        </main>

        <footer>
            <p>&copy; 2025 Archery Club Database System. All rights reserved.</p>
        </footer>
    </div>

    <script src="js/main.js"></script>
</body>
</html>