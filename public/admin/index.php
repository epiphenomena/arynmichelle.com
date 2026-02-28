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
                <h3>Settings</h3>
                <p>Configure website settings</p>
                <a href="settings/" class="btn btn-primary">Website Settings</a>
            </div>
            
            <div class="admin-card">
                <h3>Home Page Quadrants</h3>
                <p>Manage the content for the four quadrants on the home page</p>
                <a href="home/" class="btn btn-primary">Manage Quadrants</a>
            </div>
            
            <div class="admin-card">
                <h3>Social Media Links</h3>
                <p>Manage social media links displayed on the site</p>
                <a href="social/" class="btn btn-primary">Manage Social Links</a>
            </div>
            
            <div class="admin-card">
                <h3>About Page</h3>
                <p>Edit the content for the about page using Markdown</p>
                <a href="about/" class="btn btn-primary">Edit About Page</a>
            </div>

            <div class="admin-card">
                <h3>Music Page</h3>
                <p>Edit the content for the music page using Markdown</p>
                <a href="music/" class="btn btn-primary">Edit Music Page</a>
            </div>

            <div class="admin-card">
                <h3>Features Page</h3>
                <p>Edit the content for the features page using Markdown</p>
                <a href="features/" class="btn btn-primary">Edit Features Page</a>
            </div>

            <div class="admin-card">
                <h3>Resources Page</h3>
                <p>Edit the content for the resources page using Markdown</p>
                <a href="resources/" class="btn btn-primary">Edit Resources Page</a>
            </div>
        </div>
    </div>
</main>

<?php
include 'footer.php';
?>