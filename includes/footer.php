</main>

<!-- Footer -->
<<footer class="footer">
    <div class="footer-grid">
        <div class="footer-brand">
            <a href="index.php" class="logo">
                <i class="fas fa-gamepad"></i>
                GAMING HUB
            </a>
            <p>Your ultimate destination for game discovery, downloads, and gaming news. Join millions of gamers worldwide.</p>
            <div class="social-links">
                <a href="#"><i class="fab fa-discord"></i></a>
                <a href="#"><i class="fab fa-twitter"></i></a>
                <a href="#"><i class="fab fa-youtube"></i></a>
                <a href="#"><i class="fab fa-twitch"></i></a>
                <a href="#"><i class="fab fa-steam"></i></a>
            </div>
        </div>
        
        <div class="footer-links">
            <h4>Quick Links</h4>
            <ul>
                <li><a href="games.php">All Games</a></li>
                <li><a href="games.php?category=new">New Releases</a></li>
                <li><a href="games.php?category=trending">Trending</a></li>
                <li><a href="games.php?category=featured">Featured</a></li>
                <li><a href="news.php">Gaming News</a></li>
            </ul>
        </div>
        
        <div class="footer-links">
            <h4>Categories</h4>
            <ul>
                <?php foreach (array_slice($categories, 0, 5) as $cat): ?>
                <li><a href="games.php?category=<?= $cat['category_slug'] ?>"><?= $cat['category_name'] ?></a></li>
                <?php endforeach; ?>
            </ul>
        </div>
        
        <div class="footer-links">
            <h4>Support</h4>
            <ul>
                <li><a href="contact.php">Contact Us</a></li>
                <li><a href="request-game.php">Request a Game</a></li>
                <li><a href="#">FAQ</a></li>
                <li><a href="#">Privacy Policy</a></li>
                <li><a href="#">Terms of Service</a></li>
            </ul>
        </div>
    </div>
    
    <div class="footer-bottom">
        <p>&copy; <?= date('Y') ?> <?= SITE_NAME ?>. All rights reserved. Designed for gamers, by gamers.</p>
    </div>
</footer>

<!-- Lightbox -->
<div class="lightbox">
    <span class="lightbox-close"><i class="fas fa-times"></i></span>
    <img src="" alt="Screenshot">
</div>

<!-- Scripts -->
<script src="assets/js/main.js"></script>
</body>
</html>