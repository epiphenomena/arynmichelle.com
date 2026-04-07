<?php
include '../../page_header.php';

// Read content from JSON file
$charts_file = '../../data/chord-charts.json';
$charts_data = file_exists($charts_file) ? json_decode(file_get_contents($charts_file), true) : [];
?>

<main>
    <section class="back-link">
        <a href="../">&larr; Back to Resources</a>
    </section>

    <h1>Chord Charts</h1>
    
    <div class="charts-grid">
        <?php foreach ($charts_data as $album): ?>
            <div class="chart-item">
                <div class="chart-image">
                    <img src="<?php echo htmlspecialchars($album['cover_image']); ?>" alt="<?php echo htmlspecialchars($album['title']); ?> Cover">
                </div>
                <div class="chart-info">
                    <h3><?php echo htmlspecialchars($album['title']); ?></h3>
                    <?php if (!empty($album['download_url'])): ?>
                        <a href="<?php echo htmlspecialchars($album['download_url']); ?>" class="download-button" download>Download Charts</a>
                    <?php else: ?>
                        <span class="coming-soon">Coming Soon</span>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</main>

<style>
.back-link {
    margin-bottom: 2rem;
}
.back-link a {
    color: #666;
    text-decoration: none;
    font-size: 0.9rem;
    text-transform: uppercase;
    letter-spacing: 1px;
}
.back-link a:hover {
    color: #000;
}

.charts-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 3rem;
    margin-top: 2rem;
}

.chart-item {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.chart-image img {
    width: 100%;
    aspect-ratio: 1/1;
    object-fit: cover;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.chart-info {
    text-align: center;
}

.chart-info h3 {
    margin-bottom: 1rem;
    text-transform: uppercase;
    letter-spacing: 2px;
}

.download-button {
    display: inline-block;
    padding: 1rem 2rem;
    background: #000;
    color: #fff;
    text-decoration: none;
    text-transform: uppercase;
    font-size: 0.9rem;
    letter-spacing: 1px;
    transition: all 0.3s ease;
}

.download-button:hover {
    background: #333;
    transform: translateY(-2px);
}

.coming-soon {
    color: #999;
    font-style: italic;
}
</style>

<?php include '../../page_footer.php'; ?>
