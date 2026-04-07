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

// Load gallery data
$gallery_file = '../data/gallery.json';
$gallery_data = file_exists($gallery_file) ? json_decode(file_get_contents($gallery_file), true) : [];

// Insert gallery after the first header if it exists
if (!empty($gallery_data)) {
    $gallery_html = '<section class="photo-gallery">
        <div class="gallery-container">
            <div class="gallery-track">';
    foreach ($gallery_data as $photo) {
        $gallery_html .= '<div class="gallery-item">
            <img src="' . htmlspecialchars($photo['image']) . '" alt="' . htmlspecialchars($photo['caption']) . '">
            ' . (!empty($photo['caption']) ? '<p class="caption">' . htmlspecialchars($photo['caption']) . '</p>' : '') . '
        </div>';
    }
    $gallery_html .= '</div>
            <button class="gallery-prev" aria-label="Previous">&larr;</button>
            <button class="gallery-next" aria-label="Next">&rarr;</button>
        </div>
    </section>';

    // Try to insert after the first </h1>
    $pos = strpos($parsed_content, '</h1>');
    if ($pos !== false) {
        $parsed_content = substr_replace($parsed_content, $gallery_html, $pos + 5, 0);
    } else {
        // Fallback: prepend to content
        $parsed_content = $gallery_html . $parsed_content;
    }
}
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

<style>
.photo-gallery {
    margin: 3rem 0;
    width: 100%;
    overflow: hidden;
    position: relative;
}

.gallery-container {
    position: relative;
    max-width: 1000px;
    margin: 0 auto;
}

.gallery-track {
    display: flex;
    transition: transform 0.5s ease;
}

.gallery-item {
    min-width: 100%;
    text-align: center;
}

.gallery-item img {
    max-width: 100%;
    height: auto;
    border-radius: 8px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
}

.gallery-item .caption {
    margin-top: 1rem;
    font-style: italic;
    color: #666;
}

.gallery-prev, .gallery-next {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    background: rgba(255, 255, 255, 0.8);
    border: none;
    border-radius: 50%;
    width: 40px;
    height: 40px;
    cursor: pointer;
    font-size: 1.2rem;
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 10;
    transition: background 0.3s;
}

.gallery-prev:hover, .gallery-next:hover {
    background: #fff;
}

.gallery-prev { left: 10px; }
.gallery-next { right: 10px; }

@media (max-width: 768px) {
    .gallery-prev, .gallery-next {
        width: 30px;
        height: 30px;
        font-size: 1rem;
    }
}
</style>

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

    // Photo Gallery Logic
    const track = document.querySelector('.gallery-track');
    const items = document.querySelectorAll('.gallery-item');
    const prevBtn = document.querySelector('.gallery-prev');
    const nextBtn = document.querySelector('.gallery-next');
    
    if (track && items.length > 0) {
        let currentIndex = 0;
        
        function updateGallery() {
            track.style.transform = `translateX(-${currentIndex * 100}%)`;
        }
        
        function nextSlide() {
            currentIndex = (currentIndex + 1) % items.length;
            updateGallery();
        }
        
        function prevSlide() {
            currentIndex = (currentIndex - 1 + items.length) % items.length;
            updateGallery();
        }
        
        if (nextBtn) nextBtn.addEventListener('click', nextSlide);
        if (prevBtn) prevBtn.addEventListener('click', prevSlide);
        
        // Auto rotate
        let interval = setInterval(nextSlide, 5000);
        
        // Pause on hover
        const container = document.querySelector('.gallery-container');
        if (container) {
            container.addEventListener('mouseenter', () => clearInterval(interval));
            container.addEventListener('mouseleave', () => interval = setInterval(nextSlide, 5000));
        }
    }
});
</script>

<?php include '../page_footer.php'; ?>
