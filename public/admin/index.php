<?php
// Admin Dashboard
$title = "Admin Dashboard";
include 'header.php';
?>

<main>
    <div class="container">
        <h1>Admin Dashboard</h1>

        <div class="admin-options">
            <div class="admin-card">
                <a href="settings/" class="btn btn-primary">Website Settings</a>
                <p>Configure website settings</p>
            </div>
            
            <div class="admin-card">
                <a href="home/" class="btn btn-primary">Home Page Quadrants</a>
                <p>Manage the content for the four quadrants on the home page</p>
            </div>
            
            <div class="admin-card">
                <a href="social/" class="btn btn-primary">Social Media Links</a>
                <p>Manage social media links displayed on the site</p>
            </div>
            
            <div class="admin-card">
                <a href="about/" class="btn btn-primary">Edit About Page</a>
                <a href="about/gallery/" class="btn btn-secondary">Manage Gallery</a>
                <p>Update the content and photo gallery for the about page</p>
            </div>

            <div class="admin-card">
                <a href="music/" class="btn btn-primary">Edit Music Page</a>
                <a href="music/singles/" class="btn btn-secondary">Manage Singles</a>
                <p>Update the content for the music page and manage singles</p>
            </div>

            <div class="admin-card">
                <a href="features/" class="btn btn-primary">Edit Features Page</a>
                <p>Update the content for the features page</p>
            </div>

            <div class="admin-card">
                <a href="resources/" class="btn btn-primary">Edit Resources Page</a>
                <a href="resources/chord-charts/" class="btn btn-secondary">Manage Chord Charts</a>
                <p>Update the content for the resources page and manage chord charts</p>
            </div>
        </div>
    </div>
</main>

<?php
include 'footer.php';
?>