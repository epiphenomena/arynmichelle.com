<!-- Footer -->
<footer>
</footer>

<script>
    <?php
    // Include JavaScript from an external file
    $js_file = __DIR__ . '/script.js';
    if (file_exists($js_file)) {
        echo file_get_contents($js_file);
    }
    ?>
</script>
</body>
</html>