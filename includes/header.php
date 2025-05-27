<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - ' : ''; ?>Archery Score Recording System</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>css/main.css">
    <?php if (isset($additionalStyles)): ?>
        <?php foreach($additionalStyles as $style): ?>
            <link rel="stylesheet" href="<?php echo BASE_URL . $style; ?>">
        <?php endforeach; ?>
    <?php endif; ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="container">
        <header>
            <div class="logo">
                <img src="<?php echo BASE_URL; ?>images/archery-logo.png" alt="Archery Club Logo" onerror="this.src='<?php echo BASE_URL; ?>images/default-logo.png'">
                <h1>Archery Score Recording System</h1>
            </div>
        </header>