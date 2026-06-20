<?php
// Fetch footer settings from database
$footerSettings = [];
try {
    require_once __DIR__ . '/config.php';
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM footer_settings WHERE is_active = 1");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $footerSettings[$row['setting_key']] = $row['setting_value'];
    }
} catch (PDOException $e) {
    // Use default values if database fetch fails
    error_log("Error fetching footer settings: " . $e->getMessage());
}

// Helper function to get setting value with default
if (!function_exists('getFooterSetting')) {
    function getFooterSetting($key, $default = '') {
        global $footerSettings;
        return $footerSettings[$key] ?? $default;
    }
}
?>

<!-- Footer -->
<footer id="main-footer">
    <!-- Main Footer Content -->
    <div class="footer-main">
        <div class="container">
            <div class="row">
                <!-- About Section -->
                <div class="col-lg-4 col-md-6 footer-section">
                    <div class="footer-logo">
                        <img src="images/bg1.jpg" alt="Mavic's Resort">
                        <h3><?php echo htmlspecialchars(getFooterSetting('about_title', "Mavic's Resort")); ?></h3>
                    </div>
                    <p class="footer-description">
                        <?php echo htmlspecialchars(getFooterSetting('about_description', 'Your premier destination for unforgettable events. We provide exceptional venues and personalized service to make your special occasions truly memorable.')); ?>
                    </p>
                    
                    <!-- Social Media Links -->
                    <?php 
                    $hasSocial = false;
                    $socialLinks = [
                        'social_facebook' => ['icon' => 'fab fa-facebook-f', 'name' => 'Facebook'],
                        'social_instagram' => ['icon' => 'fab fa-instagram', 'name' => 'Instagram'],
                        'social_twitter' => ['icon' => 'fab fa-twitter', 'name' => 'Twitter']
                    ];
                    
                    foreach ($socialLinks as $key => $social) {
                        if (!empty(getFooterSetting($key))) {
                            $hasSocial = true;
                            break;
                        }
                    }
                    ?>
                    
                    <?php if ($hasSocial): ?>
                    <div class="social-links" style="margin-top: 1.5rem;">
                        <?php foreach ($socialLinks as $key => $social): ?>
                            <?php if (!empty(getFooterSetting($key))): ?>
                                <a href="<?php echo htmlspecialchars(getFooterSetting($key)); ?>" target="_blank" rel="noopener noreferrer" title="<?php echo $social['name']; ?>">
                                    <i class="<?php echo $social['icon']; ?>"></i>
                                </a>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- Quick Links -->
                <div class="col-lg-2 col-md-4 col-sm-6 footer-section">
                    <h4>Quick Links</h4>
                    <ul class="footer-links">
                        <li><a href="index.php">Home</a></li>
                        <li><a href="venues.php">Venues</a></li>
                        <li><a href="about.php">About Us</a></li>
                        <li><a href="contact.php">Contact</a></li>
                        <?php if ($isLoggedIn): ?>
                            <li><a href="dashboard.php">Dashboard</a></li>
                            <li><a href="booking.php">Book Now</a></li>
                        <?php endif; ?>
                    </ul>
                </div>
                
                <!-- Services -->
                <div class="col-lg-2 col-md-4 col-sm-6 footer-section">
                    <h4>Our Services</h4>
                    <ul class="footer-links">
                        <li><a href="venues.php#weddings">Wedding Receptions</a></li>
                        <li><a href="venues.php#corporate">Corporate Events</a></li>
                        <li><a href="venues.php#birthdays">Birthday Parties</a></li>
                        <li><a href="venues.php#conferences">Conferences</a></li>
                        <li><a href="venues.php#social">Social Gatherings</a></li>
                    </ul>
                </div>
                
                <!-- Contact Info -->
                <div class="col-lg-4 col-md-8 footer-section">
                    <h4>Contact Information</h4>
                    <div class="contact-info">
                        <div class="contact-item">
                            <i class="fas fa-map-marker-alt"></i>
                            <div class="contact-details">
                                <strong>Address:</strong>
                                <span><?php echo htmlspecialchars(getFooterSetting('contact_address', 'Purok 5 Sitio Labac Calangay 4207 San Nicolas, Philippines')); ?></span>
                            </div>
                        </div>
                        <div class="contact-item">
                            <i class="fas fa-phone"></i>
                            <div class="contact-details">
                                <strong>Phone:</strong>
                                <span><?php echo htmlspecialchars(getFooterSetting('contact_phone_1', '+63 961 306 7957')); ?></span>
                                <?php if (!empty(getFooterSetting('contact_phone_2'))): ?>
                                <br>
                                <span><?php echo htmlspecialchars(getFooterSetting('contact_phone_2')); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="contact-item">
                            <i class="fas fa-envelope"></i>
                            <div class="contact-details">
                                <strong>Email:</strong>
                                <span><?php echo htmlspecialchars(getFooterSetting('contact_email', 'info@mavicsresort.com')); ?></span>
                            </div>
                        </div>
                        <div class="contact-item">
                            <i class="fas fa-clock"></i>
                            <div class="contact-details">
                                <strong>Hours:</strong>
                                <span><?php echo htmlspecialchars(getFooterSetting('contact_hours', 'Mon-Sun: 8:00 AM - 10:00 PM')); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Footer Bottom -->
    <div class="footer-bottom">
        <div class="container">
            <div class="footer-bottom-content">
                <div class="copyright">
                    <p>&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars(getFooterSetting('copyright_text', "Mavic's Resort. All rights reserved.")); ?></p>
                </div>
                <div class="footer-bottom-links">
                    <a href="privacy-policy.php">Privacy Policy</a>
                    <a href="terms-conditions.php">Terms & Conditions</a>
                    <a href="About.php">Sitemap</a>
                </div>
            </div>
        </div>
    </div>
    
</footer>

<!-- Global JavaScript -->
<script src="js/global.js"></script>

<!-- Page-specific JavaScript -->
<?php if(isset($pageJSFiles) && is_array($pageJSFiles)): ?>
    <?php foreach($pageJSFiles as $jsFile): ?>
        <script src="<?php echo $jsFile; ?>"></script>
    <?php endforeach; ?>
<?php endif; ?>

<script>    
    
    // Initialize footer functionality
    document.addEventListener('DOMContentLoaded', function() {
        // Animate footer elements on scroll
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };
        
        const observer = new IntersectionObserver(function(entries) {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('animate-in');
                }
            });
        }, observerOptions);
        
        const footerElements = document.querySelectorAll('.footer-section');
        footerElements.forEach(element => {
            observer.observe(element);
        });
        
    });
</script>

<style>
    /* Social Links Styling */
    .social-links {
        display: flex;
        gap: 1rem;
        align-items: center;
    }
    
    .social-links a {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 40px;
        height: 40px;
        background: rgba(255, 255, 255, 0.1);
        border-radius: 50%;
        color: white;
        font-size: 1.1rem;
        transition: all 0.3s ease;
        text-decoration: none;
    }
    
    .social-links a:hover {
        background: rgba(255, 255, 255, 0.2);
        transform: translateY(-3px);
    }
    
    .social-links a:active {
        transform: translateY(-1px);
    }
</style>

</body>
</html>
