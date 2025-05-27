<footer>
            <p>&copy; <?php echo date('Y'); ?> Archery Club Database System. All rights reserved.</p>
        </footer>
    </div>

    <script src="<?php echo BASE_URL; ?>js/main.js"></script>
    <?php if (isset($additionalScripts)): ?>
        <?php foreach($additionalScripts as $script): ?>
            <script src="<?php echo BASE_URL . $script; ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
</body>
</html>