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
        <h2 id="contact-title">Contact / Booking</h2>
        <form id="contact-form" action="/contact.php" method="POST">
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
        <div id="contact-response" class="contact-response"></div>
    </section>

    <?php echo $parsed_content; ?>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const contactForm = document.getElementById('contact-form');
    const contactResponse = document.getElementById('contact-response');
    const contactTitle = document.getElementById('contact-title');

    if (contactForm) {
        contactForm.addEventListener('submit', function(e) {
            e.preventDefault();

            // Change button state
            const submitButton = contactForm.querySelector('.submit-button');
            const originalButtonText = submitButton.textContent;
            submitButton.disabled = true;
            submitButton.textContent = 'Sending...';

            // Get form data
            const formData = new FormData(contactForm);

            // Submit using fetch
            fetch(contactForm.action, {
                method: 'POST',
                body: formData
            })
            .then(async response => {
                const text = await response.text();
                
                if (response.ok) {
                    // Success
                    contactForm.style.display = 'none';
                    contactResponse.textContent = text;
                    contactResponse.className = 'contact-response success';
                    contactResponse.style.display = 'block';
                } else {
                    // Server error
                    throw new Error(text || 'Oops! Something went wrong.');
                }
            })
            .catch(error => {
                // Network or other error
                contactResponse.textContent = error.message;
                contactResponse.className = 'contact-response error';
                contactResponse.style.display = 'block';
                
                // Re-enable button if error
                submitButton.disabled = false;
                submitButton.textContent = originalButtonText;
            });
        });
    }
});
</script>

<?php include '../page_footer.php'; ?>
