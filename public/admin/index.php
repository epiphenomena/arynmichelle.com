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
                <p>Update the content for the about page using Markdown</p>
            </div>

            <div class="admin-card">
                <a href="music/" class="btn btn-primary">Edit Music Page</a>
                <p>Update the content for the music page using Markdown</p>
            </div>

            <div class="admin-card">
                <a href="features/" class="btn btn-primary">Edit Features Page</a>
                <p>Update the content for the features page using Markdown</p>
            </div>

            <div class="admin-card">
                <a href="resources/" class="btn btn-primary">Edit Resources Page</a>
                <p>Update the content for the resources page using Markdown</p>
            </div>
        </div>
    </div>
</main>

<?php
include 'footer.php';
?>