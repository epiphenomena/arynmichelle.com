<?php
include '../page_header.php';

// Include Parsedown for converting markdown to HTML
require_once '../Parsedown.php';

// Read content from markdown file
$content_file = '../data/about.md';
$content = file_exists($content_file) ? file_get_contents($content_file) : '# About Page\n\nDefault content.';

// Convert markdown to HTML
$parsedown = new Parsedown();
$parsed_content = $parsedown->text($content);
?>

<main>
    <section class="contact-form">
        <h2>Contact / Booking</h2>
        <form action="/contact.php" method="POST">
            <div class="form-group">
                <label for="name">Name</label>
                <input type="text" id="name" name="name" required>
            </div>
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required>
            </div>
            <!-- Honeypot field - should be hidden from users -->
            <div class="form-group" style="display:none;">
                <label for="website">Website (Leave blank)</label>
                <input type="text" id="website" name="website" tabindex="-1" autocomplete="off">
            </div>
            <div class="form-group">
                <label for="message">Message</label>
                <textarea id="message" name="message" rows="5" required></textarea>
            </div>
            <button type="submit" class="submit-button">Send Message</button>
        </form>
    </section>

    <?php echo $parsed_content; ?>
</main>

<?php include '../page_footer.php'; ?>
