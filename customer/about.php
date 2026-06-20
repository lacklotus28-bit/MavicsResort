<?php
// About Page - Mavic's Resort
$pageTitle = "About Us - Mavic's Resort";
$pageDescription = "Learn about Mavic's Resort and Events Place - Your premier destination for memorable celebrations, corporate events, and special occasions in a beautiful natural setting.";
$pageCSSFiles = ['styles/header.css', 'styles/footer.css', 'styles/about.css'];
$pageJSFiles = [];

// Include header
include 'includes/header.php';

// Fetch about page content from database
$aboutContent = [];
try {
    $stmt = $conn->query("SELECT * FROM about_page_content WHERE is_active = 1 ORDER BY display_order ASC");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $row['data'] = json_decode($row['section_data'], true);
        $aboutContent[$row['section_name']] = $row;
    }
} catch (PDOException $e) {
    // If table doesn't exist or error, use default content
    error_log("About content fetch error: " . $e->getMessage());
}

// Helper function to get content
function getAboutContent($section, $key = null, $default = '') {
    global $aboutContent;
    if (isset($aboutContent[$section])) {
        if ($key === null) {
            return $aboutContent[$section];
        }
        if (isset($aboutContent[$section]['data'][$key])) {
            return $aboutContent[$section]['data'][$key];
        }
    }
    return $default;
}

// Helper function to get section image
function getSectionImage($section, $default = 'images/bg1.jpg') {
    global $aboutContent;
    if (isset($aboutContent[$section]) && !empty($aboutContent[$section]['section_image'])) {
        // Check if file exists
        $imagePath = '../admin/' . $aboutContent[$section]['section_image'];
        if (file_exists($imagePath)) {
            return $imagePath;
        }
    }
    return $default;
}
?>

<!-- Main Content -->
<main id="main-content">
    <!-- Hero Section -->
    <section class="about-hero">
        <div class="hero-overlay"></div>
        <div class="container">
            <div class="hero-content">
                <h1 class="page-title"><?php echo htmlspecialchars(getAboutContent('hero', 'title', 'About Mavic\'s Resort')); ?></h1>
                <p class="page-subtitle"><?php echo htmlspecialchars(getAboutContent('hero', 'subtitle', 'Your Premier Events Place for Unforgettable Celebrations')); ?></p>
            </div>
        </div>
    </section>

    <!-- Introduction Section -->
    <section class="about-intro py-5">
        <div class="container">
            <div class="intro-content">
                <div class="intro-text">
                    <h2><?php echo htmlspecialchars(getAboutContent('intro', 'section_title', 'Welcome to Mavic\'s Resort and Events Place')); ?></h2>
                    <p class="lead">
                        <?php echo htmlspecialchars(getAboutContent('intro', 'lead', 'Nestled in a serene and picturesque setting, Mavic\'s Resort and Events Place is your perfect destination for creating unforgettable memories.')); ?>
                    </p>
                    <p>
                        <?php echo htmlspecialchars(getAboutContent('intro', 'description', 'Our resort offers a beautiful blend of natural beauty and modern amenities.')); ?>
                    </p>
                </div>
                <div class="intro-stats">
                    <?php 
                    $stats = getAboutContent('statistics', 'stats', [
                        ['number' => '500+', 'label' => 'Happy Clients'],
                        ['number' => '1000+', 'label' => 'Events Hosted'],
                        ['number' => '5+', 'label' => 'Years of Service']
                    ]);
                    foreach ($stats as $stat): 
                    ?>
                    <div class="stat-box">
                        <div class="stat-number"><?php echo htmlspecialchars($stat['number']); ?></div>
                        <div class="stat-label"><?php echo htmlspecialchars($stat['label']); ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </section>

    <!-- Our Story Section -->
    <section class="our-story py-5">
        <div class="container">
            <div class="section-header text-center">
                <h2><?php echo htmlspecialchars(getAboutContent('story', 'section_title', 'Our Story')); ?></h2>
                <p><?php echo htmlspecialchars(getAboutContent('story', 'section_content', 'Creating Memorable Experiences Since Day One')); ?></p>
            </div>
            
            <div class="story-content">
                <div class="story-image">
                    <img src="<?php echo htmlspecialchars(getSectionImage('story')); ?>" alt="Mavic's Resort" class="img-fluid">
                </div>
                <div class="story-text">
                    <h3><?php echo htmlspecialchars(getAboutContent('story', 'title', 'A Dream Brought to Life')); ?></h3>
                    <?php 
                    $paragraphs = getAboutContent('story', 'paragraphs', [
                        "Mavic's Resort and Events Place was born from a passion to create a special venue where people can celebrate life's most precious moments."
                    ]);
                    foreach ($paragraphs as $paragraph): 
                    ?>
                    <p><?php echo htmlspecialchars($paragraph); ?></p>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </section>

    <!-- What We Offer Section -->
    <section class="what-we-offer py-5">
        <div class="container">
            <div class="section-header text-center">
                <h2>What We Offer</h2>
                <p>Everything you need for a perfect event</p>
            </div>

            <div class="offerings-grid">
                <div class="offering-card">
                    <div class="offering-icon">
                        <i class="fas fa-building"></i>
                    </div>
                    <h3>Beautiful Venues</h3>
                    <p>Multiple indoor and outdoor venues to suit events of all sizes, from intimate gatherings to grand celebrations.</p>
                </div>

                <div class="offering-card">
                    <div class="offering-icon">
                        <i class="fas fa-utensils"></i>
                    </div>
                    <h3>Catering Services</h3>
                    <p>Delicious cuisine prepared by our experienced culinary team, with customizable menus for every taste.</p>
                </div>

                <div class="offering-card">
                    <div class="offering-icon">
                        <i class="fas fa-music"></i>
                    </div>
                    <h3>Audio & Visual</h3>
                    <p>State-of-the-art sound systems and projection equipment to make your presentations and entertainment perfect.</p>
                </div>

                <div class="offering-card">
                    <div class="offering-icon">
                        <i class="fas fa-swimming-pool"></i>
                    </div>
                    <h3>Pool & Recreation</h3>
                    <p>Swimming pools and recreational areas for guests to enjoy during daytime events and celebrations.</p>
                </div>

                <div class="offering-card">
                    <div class="offering-icon">
                        <i class="fas fa-parking"></i>
                    </div>
                    <h3>Ample Parking</h3>
                    <p>Spacious parking area to accommodate all your guests comfortably and conveniently.</p>
                </div>

                <div class="offering-card">
                    <div class="offering-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <h3>Event Coordination</h3>
                    <p>Professional event coordinators to help plan and execute your event flawlessly from start to finish.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Types of Events Section -->
    <section class="event-types py-5">
        <div class="container">
            <div class="section-header text-center">
                <h2>Events We Host</h2>
                <p>Perfect for every occasion</p>
            </div>

            <div class="event-types-grid">
                <div class="event-type-card">
                    <div class="event-type-image">
                        <img src="images/bg1.jpg" alt="Wedding" class="img-fluid">
                        <div class="event-type-overlay">
                            <i class="fas fa-heart"></i>
                        </div>
                    </div>
                    <div class="event-type-content">
                        <h3>Weddings & Receptions</h3>
                        <p>Make your special day truly magical with our romantic venues and exceptional service.</p>
                    </div>
                </div>

                <div class="event-type-card">
                    <div class="event-type-image">
                        <img src="images/bg1.jpg" alt="Birthday" class="img-fluid">
                        <div class="event-type-overlay">
                            <i class="fas fa-birthday-cake"></i>
                        </div>
                    </div>
                    <div class="event-type-content">
                        <h3>Birthday Parties</h3>
                        <p>Celebrate another year with joy in our fun and festive party spaces.</p>
                    </div>
                </div>

                <div class="event-type-card">
                    <div class="event-type-image">
                        <img src="images/bg1.jpg" alt="Corporate" class="img-fluid">
                        <div class="event-type-overlay">
                            <i class="fas fa-briefcase"></i>
                        </div>
                    </div>
                    <div class="event-type-content">
                        <h3>Corporate Events</h3>
                        <p>Professional venues equipped with modern facilities for meetings, seminars, and team building.</p>
                    </div>
                </div>

                <div class="event-type-card">
                    <div class="event-type-image">
                        <img src="images/bg1.jpg" alt="Family" class="img-fluid">
                        <div class="event-type-overlay">
                            <i class="fas fa-users"></i>
                        </div>
                    </div>
                    <div class="event-type-content">
                        <h3>Family Reunions</h3>
                        <p>Gather your loved ones in our spacious facilities perfect for family celebrations.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Why Choose Us Section -->
    <section class="why-choose-us py-5">
        <div class="container">
            <div class="section-header text-center">
                <h2>Why Choose Mavic's Resort?</h2>
                <p>What sets us apart</p>
            </div>

            <div class="reasons-grid">
                <div class="reason-item">
                    <div class="reason-number">01</div>
                    <h3>Prime Location</h3>
                    <p>Easily accessible yet surrounded by nature, providing the perfect escape from the city while remaining convenient.</p>
                </div>

                <div class="reason-item">
                    <div class="reason-number">02</div>
                    <h3>Flexible Packages</h3>
                    <p>Customizable packages to fit your budget and preferences, ensuring you get exactly what you need.</p>
                </div>

                <div class="reason-item">
                    <div class="reason-number">03</div>
                    <h3>Experienced Team</h3>
                    <p>Our professional staff brings years of experience in event planning and hospitality services.</p>
                </div>

                <div class="reason-item">
                    <div class="reason-number">04</div>
                    <h3>Modern Facilities</h3>
                    <p>Well-maintained venues with contemporary amenities and regular upgrades to serve you better.</p>
                </div>

                <div class="reason-item">
                    <div class="reason-number">05</div>
                    <h3>Affordable Rates</h3>
                    <p>Competitive pricing without compromising on quality, making memorable events accessible to everyone.</p>
                </div>

                <div class="reason-item">
                    <div class="reason-number">06</div>
                    <h3>Personalized Service</h3>
                    <p>Dedicated attention to every detail, ensuring your vision comes to life exactly as you imagined.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Our Values Section -->
    <section class="our-values py-5">
        <div class="container">
            <div class="section-header text-center">
                <h2>Our Core Values</h2>
                <p>What guides everything we do</p>
            </div>

            <div class="values-grid">
                <div class="value-card">
                    <div class="value-icon">
                        <i class="fas fa-heart"></i>
                    </div>
                    <h3>Excellence</h3>
                    <p>We strive for excellence in every aspect of our service, from venue maintenance to customer care.</p>
                </div>

                <div class="value-card">
                    <div class="value-icon">
                        <i class="fas fa-handshake"></i>
                    </div>
                    <h3>Integrity</h3>
                    <p>Honesty and transparency in all our dealings, building trust with every client we serve.</p>
                </div>

                <div class="value-card">
                    <div class="value-icon">
                        <i class="fas fa-smile"></i>
                    </div>
                    <h3>Hospitality</h3>
                    <p>Warm, genuine hospitality that makes every guest feel welcome and valued.</p>
                </div>

                <div class="value-card">
                    <div class="value-icon">
                        <i class="fas fa-lightbulb"></i>
                    </div>
                    <h3>Innovation</h3>
                    <p>Continuously improving our services and facilities to exceed expectations.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Call to Action -->
    <section class="about-cta py-5">
        <div class="container">
            <div class="cta-content text-center">
                <h2>Ready to Plan Your Event?</h2>
                <p>Let us help you create an unforgettable celebration at Mavic's Resort</p>
                <div class="cta-buttons">
                    <?php if ($isLoggedIn): ?>
                        <a href="booking.php" class="btn btn-primary btn-large">
                            <i class="fas fa-calendar-plus"></i> Book Your Event
                        </a>
                    <?php else: ?>
                        <a href="register.php" class="btn btn-primary btn-large">
                            <i class="fas fa-user-plus"></i> Get Started
                        </a>
                    <?php endif; ?>
                    <a href="venues.php" class="btn btn-outline btn-large">
                        <i class="fas fa-eye"></i> View Our Venues
                    </a>
                    <a href="contact.php" class="btn btn-outline btn-large">
                        <i class="fas fa-phone"></i> Contact Us
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Social Media Section -->
    <section class="social-section py-5">
        <div class="container">
            <div class="section-header text-center">
                <h2>Follow Us on Social Media</h2>
                <p>Stay updated with our latest events and offerings</p>
            </div>
            
            <div class="social-links">
                <a href="https://www.facebook.com/p/Mavics-Resort-and-Events-Place-61550024396909/" target="_blank" class="social-link facebook">
                    <i class="fab fa-facebook-f"></i>
                    <span>Follow us on Facebook</span>
                </a>
                <!-- Add more social links as needed
                <a href="#" target="_blank" class="social-link instagram">
                    <i class="fab fa-instagram"></i>
                    <span>Follow us on Instagram</span>
                </a>
                <a href="#" target="_blank" class="social-link twitter">
                    <i class="fab fa-twitter"></i>
                    <span>Follow us on Twitter</span>
                </a>
                -->
            </div>
        </div>
    </section>

    <!-- Location Map Section -->
    <section class="location-map py-5">
        <div class="container">
            <div class="section-header text-center">
                <h2>Visit Us</h2>
                <p>Find us on the map and plan your visit</p>
            </div>
            
            <div class="map-container">
                <iframe 
                    src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3872.6151491410083!2d120.9393692!3d13.9219369!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x33bd0b3b619c22cd%3A0x1dce24db9e0c4a30!2sMavic&#39;s%20Resort%20and%20Events%20Place!5e0!3m2!1sen!2sph!4v1759283766984!5m2!1sen!2sph" 
                    width="100%" 
                    height="450" 
                    style="border:0;" 
                    allowfullscreen="" 
                    loading="lazy" 
                    referrerpolicy="no-referrer-when-downgrade">
                </iframe>
            </div>
            
            <div class="location-info">
                <div class="info-card">
                    <div class="info-icon">
                        <i class="fas fa-map-marker-alt"></i>
                    </div>
                    <h4>Address</h4>
                    <p>Mavic's Resort and Events Place<br>Purok 5 Sitio Labac Calangay 4207 San Nicolas, Philippines</p>
                </div>
                
                <div class="info-card">
                    <div class="info-icon">
                        <i class="fas fa-phone"></i>
                    </div>
                    <h4>Contact Number</h4>
                    <p>Call us for inquiries and reservations</p>
                </div>
                
                <div class="info-card">
                    <div class="info-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <h4>Operating Hours</h4>
                    <p>Available for events daily<br>By reservation</p>
                </div>
            </div>
        </div>
    </section>
</main>

<?php
// Include footer
include 'includes/footer.php';
?>
