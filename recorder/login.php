<?php
require_once '../includes/settings.php';
require_once '../includes/auth.php';

// Check if already logged in
if (isRecorderLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

// Process login form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = isset($_POST['username']) ? sanitizeInput($_POST['username']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    
    if (empty($username) || empty($password)) {
        $error = 'Username and password are required';
    } else {
        if (verifyRecorderLogin($username, $password)) {
            header('Location: dashboard.php');
            exit;
        } else {
            $error = 'Invalid username or password';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recorder Login - Archery Score Recording System</title>
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

        <main>
            <div class="auth-container">
                <div class="auth-card">
                    <h2><i class="fas fa-user-lock"></i> Recorder Login</h2>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-error">
                            <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                        </div>
                    <?php endif; ?>
                    
                    <form action="login.php" method="post" class="auth-form">
                        <div class="form-group">
                            <label for="username">Username</label>
                            <input type="text" id="username" name="username" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="password">Password</label>
                            <input type="password" id="password" name="password" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary btn-block">
                                <i class="fas fa-sign-in-alt"></i> Login
                            </button>
                        </div>
                    </form>
                    
                    <div class="auth-links">
                        <a href="../index.php">
                            <i class="fas fa-arrow-left"></i> Back to Home
                        </a>
                    </div>
                </div>
            </div>
        </main>

        <footer>
            <p>&copy; 2025 Archery Club Database System. All rights reserved.</p>
        </footer>
    </div>

    <style>
        .auth-container {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 80vh;
        }
        
        .auth-card {
            background-color: white;
            border-radius: 8px;
            padding: 2rem;
            width: 100%;
            max-width: 400px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .auth-card h2 {
            color: var(--primary-color);
            margin-bottom: 1.5rem;
            text-align: center;
        }
        
        .auth-form {
            margin-bottom: 1.5rem;
        }
        
        .btn-block {
            width: 100%;
        }
        
        .auth-links {
            text-align: center;
            margin-top: 1rem;
            font-size: 0.9rem;
        }
        
        .auth-links a {
            color: var(--primary-color);
            text-decoration: none;
        }
        
        .auth-links a:hover {
            text-decoration: underline;
        }
        
        .alert {
            padding: 0.75rem 1rem;
            margin-bottom: 1rem;
            border-radius: 4px;
            font-size: 0.9rem;
        }
        
        .alert-error {
            background-color: #fee2e2;
            color: var(--error-color);
            border-left: 4px solid var(--error-color);
        }
        
        .alert i {
            margin-right: 0.5rem;
        }
    </style>
</body>
</html>