 
<?php
// No specific PHP logic needed usually, but ensure the file exists
// This file primarily contains closing HTML tags and JS includes.
?>
    </main> <footer class="bg-gray-200 text-gray-600 text-center text-xs py-4 mt-10 border-t border-gray-300">
        <div class="container mx-auto px-4">
            Copyright &copy; <?php echo date('Y'); ?> <?php echo defined('STORE_NAME') ? htmlspecialchars(STORE_NAME) : 'Your Company'; ?>. All Rights Reserved.
            <p class="mt-1">Simple POS System v1.0</p>
            </div>
    </footer>

    <script src="<?php echo defined('BASE_URL') ? BASE_URL : ''; ?>assets/js/main.js"></script>

</body>
</html>