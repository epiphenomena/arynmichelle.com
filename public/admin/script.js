document.addEventListener('DOMContentLoaded', function () {
    const mainForm = document.getElementById('main-form');
    const saveButton = document.getElementById('save-btn');

    // Enable save button on any change within the form
    // Using 'change' is more reliable across all input types
    mainForm.addEventListener('change', function () {
        saveButton.disabled = false;
    });
    mainForm.addEventListener('input', function () {
        saveButton.disabled = false;
    });

    // Show toast on load if it has a message
    const toast = document.getElementById('toast');
    // Example: To show toast, uncomment the line below or add the 'show' class in HTML
    // toast.classList.add('show');
    if (toast.classList.contains('show')) {
        setTimeout(() => {
            toast.classList.remove('show');
        }, 3000); // Hide after 3 seconds
    }

    // Social media card image preview handler
    const socialMediaCardUpload = document.getElementById('social-media-card');
    if (socialMediaCardUpload) {
        socialMediaCardUpload.addEventListener('change', function (event) {
            if (event.target.files && event.target.files[0]) {
                const reader = new FileReader();
                reader.onload = function (e) {
                    // Update the preview image if it exists
                    const socialPreviewImg = document.querySelector('.social-card-preview');
                    if (socialPreviewImg) {
                        socialPreviewImg.src = e.target.result;
                        socialPreviewImg.style.display = 'block';
                    }
                };
                reader.readAsDataURL(event.target.files[0]);
                saveButton.disabled = false;
            }
        });
    }

    // Image preview handler (for other pages)
    const imageUpload = document.getElementById('image-upload');
    const imagePreview = document.getElementById('image-preview');
    const removeImageBtn = document.getElementById('remove-image-btn');

    if (imageUpload && imagePreview) {
        imageUpload.addEventListener('change', function (event) {
            if (event.target.files && event.target.files[0]) {
                const reader = new FileReader();
                reader.onload = function (e) {
                    imagePreview.src = e.target.result;
                    imagePreview.style.display = 'block';
                    saveButton.disabled = false;
                };
                reader.readAsDataURL(event.target.files[0]);
            }
        });
    }

    if (removeImageBtn && imagePreview) {
        removeImageBtn.addEventListener('click', function () {
            if (imageUpload) {
                imageUpload.value = ''; // Clear the file input
            }
            imagePreview.src = 'https://placehold.co/600x400/E2E8F0/4A5568?text=No+Image';
            saveButton.disabled = false;
        });
    }

    // File name handler (for other pages)
    const fileUpload = document.getElementById('file-upload');
    const fileNameDisplay = document.getElementById('file-name');
    const removeFileBtn = document.getElementById('remove-file-btn');

    if (fileUpload && fileNameDisplay) {
        fileUpload.addEventListener('change', function (event) {
            if (event.target.files && event.target.files[0]) {
                fileNameDisplay.textContent = event.target.files[0].name;
                saveButton.disabled = false;
            } else {
                fileNameDisplay.textContent = 'No File Selected';
            }
        });
    }

    if (removeFileBtn && fileNameDisplay) {
        removeFileBtn.addEventListener('click', function () {
            if (fileUpload) {
                fileUpload.value = ''; // Clear the file input
            }
            fileNameDisplay.textContent = 'No File Selected';
            saveButton.disabled = false;
        });
    }
});