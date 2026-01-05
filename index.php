<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GymFit </title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Oswald:wght@500;700&family=Roboto:wght@400;500&display=swap"
        rel="stylesheet">
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
</head>

<body>

    <!-- Navbar -->
    <nav class="navbar" id="navbar">
        <div class="container">
            <a href="#" class="logo">
                <i class="fa-solid fa-dumbbell"></i>GymFit
            </a>
            <button class="menu-toggle" id="mobile-menu">
                <i class="fa-solid fa-bars"></i>
            </button>
            <ul class="nav-links">
                <li><a href="#home">Home</a></li>
                <li><a href="#about">About</a></li>
                <li><a href="#services">Services</a></li>
                <li><a href="#equipment-showcase">Equipments</a></li>
                <li><a href="#reviews">Reviews</a></li>
                <li><a href="#pricing">Pricing</a></li>
                <li><a href="#contact">Contact</a></li>
                <?php if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true): ?>
                    <li><a id="header-login" href="<?php
                    if ($_SESSION['role'] === 'admin')
                        echo 'dashboard_admin.php';
                    elseif ($_SESSION['role'] === 'staff')
                        echo 'dashboard_staff.php';
                    else
                        echo 'dashboard_member.php';
                    ?>" class="login-btn">Dashboard</a></li>
                <?php else: ?>
                    <li><a id="header-login" href="login.php" class="login-btn">Login</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </nav>

    <!-- Hero Section -->
    <section id="home" class="hero">
        <div class="hero-content">
            <h1 class="background-text">FITNESS</h1>

            <div class="hero-wave">
                <svg viewBox="0 0 1440 320" xmlns="http://www.w3.org/2000/svg">
                    <path fill="#a1d423" fill-opacity="1"
                        d="M0,192L48,197.3C96,203,192,213,288,229.3C384,245,480,267,576,250.7C672,235,768,181,864,181.3C960,181,1056,235,1152,234.7C1248,235,1344,181,1392,154.7L1440,128L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z">
                    </path>
                </svg>
            </div>
        </div>
    </section>

    <!-- Stats / Intro Section -->
    <section id="about" class="section stats-section">
        <div class="container split-container">
            <div class="stats-text">
                <h2 class="section-title">Your Fitness Journey Starts Here</h2>
                <p>We provide a world-class environment for athletes of all levels. Join a community dedicated to
                    strength, wellness, and progress. Whether you are a beginner or a pro, we have the tools you need.
                </p>


                <div class="stats-grid">
                    <div class="stat-item">
                        <i class="fa-solid fa-dumbbell"></i>
                        <h3>6+ Certified Trainers</h3>
                    </div>
                    <div class="stat-item">
                        <i class="fa-solid fa-users"></i>
                        <h3>1000+ Active Members</h3>
                    </div>
                    <div class="stat-item">
                        <i class="fa-solid fa-trophy"></i>
                        <h3>10+ Years Of Experience</h3>
                    </div>
                    <div class="stat-item">
                        <i class="fa-solid fa-comment"></i>
                        <h3>5k+ Google Reviews</h3>
                    </div>
                </div>
            </div>
            <div class="stats-image">
                <!-- Using the same hero image for now or a different one if available -->
                <!-- Placeholder for the shirtless kettlebell guy -->
                <img src="assets/images/trainer.png" alt="Trainer">
            </div>
        </div>
    </section>

    <!-- Services Section -->
    <section id="services" class="section services-section">
        <h2 class="section-title center">Our Services</h2>
        <div class="container services-grid">
            <!-- Service 1 -->
            <div class="service-card">
                <div class="icon-box">
                    <img src="assets/icons/muscle_custom.png" alt="Personal Training">
                </div>
                <h3>Personal Trainer</h3>
                <p>One-on-one customized workouts to smash your goals.</p>
                <a href="login.php" class="btn-text">Join Now</a>

            </div>
            <!-- Service 2 -->
            <div class="service-card">
                <div class="icon-box">
                    <img src="assets/icons/group.svg" alt="Group Training">
                </div>
                <h3>Group Training</h3>
                <p>High-energy classes to keep you motivated and moving.</p>
                <a href="login.php" class="btn-text">Join Now</a>

            </div>
            <!-- Service 3 (Highlighted) -->
            <div class="service-card active">
                <div class="icon-box">
                    <img src="assets/icons/treadmill.svg" alt="Treadmill">
                </div>
                <h3>Treadmill</h3>
                <p>State-of-the-art cardio equipment for endurance.</p>
                <a href="login.php" class="btn-text">Join Now</a>

            </div>
            <!-- Service 4 -->
            <div class="service-card">
                <div class="icon-box">
                    <img src="assets/icons/yoga_custom.png" alt="Yoga">
                </div>
                <h3>Yoga</h3>
                <p>Find your balance and improve flexibility with experts.</p>
                <a href="login.php" class="btn-text">Join Now</a>

            </div>
            <!-- Service 5 -->
            <div class="service-card">
                <div class="icon-box">
                    <img src="assets/icons/online.svg" alt="Online Training">
                </div>
                <h3>Workout Videos</h3>
                <p>Access expert-guided workout videos anytime, anywhere to stay fit and motivated.</p>
                <a href="login.php" class="btn-text">Join Now</a>

            </div>
            <!-- Service 6 -->
            <div class="service-card">
                <div class="icon-box">
                    <img src="assets/icons/tips.svg" alt="Diet And Tips">
                </div>
                <h3>Diet And Tips</h3>
                <p>Nutrition guidance ensuring you fuel your gains properly.</p>
                <a href="login.php" class="btn-text">Join Now</a>

            </div>
        </div>
    </section>

    <!-- Why Gyming (Before/After) -->
    <section class="section why-gyming">
        <div class="container split-container">
            <div class="why-text">
                <h2 class="section-title">WHY GYMING IS <br> <span class="highlight">GOOD FOR HEALTH?</span></h2>
                <p>Regular exercise boosts your immunity, improves mental health, and builds a resilient, powerful body.
                    Invest in yourself today.</p>

            </div>
            <div class="why-image">
                <!-- Comparison slider with user provided images -->
                <div class="comparison-slider">
                    <!-- Base image (Right side / Background) - Fat -->
                    <img src="assets/images/fat-man.png?v=300" alt="Before">
                    <!-- Overlay image (Left side / Foreground) - Fit -->
                    <div class="overlay-img" style="background-image: url('assets/images/skinny-man.png?v=300');"></div>
                    <div class="slider-handle">
                        <div class="handle-line"></div>
                        <div class="handle-circle"><i class="fa-solid fa-arrows-left-right"></i></div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Equipment Showcase Section -->
    <section id="equipment-showcase" class="section equipment-section">
        <h2 class="section-title center"><span class="highlight">Gym Equipment Showcase</span></h2>
        <div class="container equipment-grid">
            <!-- Item 1: Dumbbell -->
            <div class="equipment-card">
                <div class="equip-image"><img src="assets/images/dumble.png" alt="Dumbbell"></div>
                <h3>Dumbbell</h3>
                <p class="small-text">Adjustable weights</p>

            </div>
            <!-- Item 2: Cardio Bike -->
            <div class="equipment-card">
                <div class="equip-image"><img src="assets/images/product-bike.png" alt="Cardio Bike"></div>
                <!-- Sack as Bag -->
                <h3>Cardio Bike</h3>
                <p class="small-text">High-intensity cardio</p>

            </div>
            <!-- Item 3: Treadmill Elite -->
            <div class="equipment-card">
                <div class="equip-image"><img src="assets/images/product-treadmill.png" alt="Treadmill"></div>
                <h3>Treadmill Elite</h3>
                <p class="small-text">Smart incline run</p>

            </div>
            <!-- Item 4: Cable Machine -->
            <div class="equipment-card">
                <div class="equip-image"><img src="assets/images/product-cable.png" alt="Cable Machine"></div>
                <h3>Cable Machine</h3>
                <p class="small-text">Full body workout</p>

            </div>
            <!-- Item 5: Flat Bench -->
            <div class="equipment-card">
                <div class="equip-image"><img src="assets/images/bench.png" alt="Flat Bench"></div>
                <h3>Flat Bench</h3>
                <p class="small-text">Steel frame support</p>

            </div>
            <!-- Item 6 : Smith Machine -->
            <div class="equipment-card">
                <div class="equip-image"><img src="assets/images/smith.png" alt="Smith Machine"></div>
                <h3>Smith Machine</h3>
                <p class="small-text">Guided weight training</p>

            </div>
            <!-- Item 7 (Hidden): Kettlebells -->
            <div class="equipment-card hidden-item">
                <div class="equip-image"><img src="assets/images/kettlebell.png" alt="Kettlebells"></div>
                <h3>Kettlebells</h3>
                <p class="small-text">Explosive power</p>

            </div>
            <!-- Item 8 (Hidden): Pull-up Bar -->
            <div class="equipment-card hidden-item">
                <div class="equip-image"><img src="assets/images/pullup.png" alt="Pull-up Bar"></div>
                <h3>Pull-up Bar</h3>
                <p class="small-text">Upper body strength</p>

            </div>
            <!-- Item 9 (Hidden): Medicine Ball -->
            <div class="equipment-card hidden-item">
                <div class="equip-image"><img src="assets/images/medicineball.png" alt="Medicine Ball"></div>
                <h3>Medicine Ball</h3>
                <p class="small-text">Core plyometrics</p>

            </div>
            <!-- Item 10 (Hidden): Resistance Bands -->
            <div class="equipment-card hidden-item">
                <div class="equip-image"><img src="assets/images/bands.png" alt="Resistance Bands"></div>
                <h3>Resistance Bands</h3>
                <p class="small-text">Elastic resistance</p>

            </div>
            <!-- Item 11 (Hidden): Rowing Machine -->
            <div class="equipment-card hidden-item">
                <div class="equip-image"><img src="assets/images/rowing.png" alt="Rowing Machine"></div>
                <h3>Rowing Machine</h3>
                <p class="small-text">Full body cardio</p>

            </div>
            <!-- Item 12 (Hidden): Leg Press Machine -->
            <div class="equipment-card hidden-item">
                <div class="equip-image"><img src="assets/images/legpress.png" alt="Leg Press Machine"></div>
                <h3>Leg Press Machine</h3>
                <p class="small-text">Lower body strength</p>

            </div>
        </div>
        <div class="center-btn-container" style="text-align: center; margin-top: 40px;">
            <button id="load-more-equipment" class="btn-primary">See More Equipment</button>
            <button id="show-less-equipment" class="btn-primary" style="display: none; margin-left: 10px;">Show
                Less</button>
        </div>
    </section>

    <!-- BMI Section -->
    <section class="section bmi-section">
        <div class="container bmi-container">
            <div class="bmi-text">
                <h2>Know Your Fitness Level</h2>
                <p>Calculate Your Body Mass Index (BMI) To Monitor Your Fitness Progress And Health.</p>
                <div id="bmi-result-display" class="bmi-result-box" style="display:none;">
                    Your BMI: <span id="bmi-value">--</span>
                </div>
            </div>
            <div class="bmi-form">
                <input type="number" id="bmi-weight" class="bmi-input" placeholder="Weight (Kg)">
                <input type="number" id="bmi-height" class="bmi-input" placeholder="Height (Cm)">
                <button id="calculate-bmi" class="btn-skew">Calculate BMI</button>
            </div>
        </div>
    </section>

    <!-- Client Reviews Section -->
    <section id="reviews" class="section reviews-section">
        <h2 class="section-title center">Client's <span class="highlight">Reviews</span></h2>

        <div class="reviews-container">
            <!-- Navigation -->
            <div class="slider-nav">
                <button class="nav-btn" id="prev-review"><i class="fa-solid fa-chevron-left"></i></button>
                <button class="nav-btn" id="next-review"><i class="fa-solid fa-chevron-right"></i></button>
            </div>

            <div class="review-slider-wrapper" id="review-slider">
                <!-- Review 1 -->
                <div class="review-card">
                    <div class="stars">
                        <i class="fa-solid fa-star"></i>
                        <i class="fa-solid fa-star"></i>
                        <i class="fa-solid fa-star"></i>
                        <i class="fa-solid fa-star"></i>
                        <i class="fa-regular fa-star-half-stroke"></i>
                    </div>
                    <p class="review-text">"GymFit has completely transformed my life! The trainers are incredibly
                        knowledgeable and supportive. I've lost 15kg in 4 months and feel stronger than ever!"</p>
                    <div class="client-info">
                        <div class="client-img">
                            <img src="assets/images/avatar-1.png" alt="Client">
                        </div>
                        <h4>John Doe</h4>
                    </div>
                </div>
                <!-- Review 2 -->
                <div class="review-card">
                    <div class="stars">
                        <i class="fa-solid fa-star"></i>
                        <i class="fa-solid fa-star"></i>
                        <i class="fa-solid fa-star"></i>
                        <i class="fa-solid fa-star"></i>
                        <i class="fa-solid fa-star"></i>
                    </div>
                    <p class="review-text">"Best gym in the city! The equipment is top-notch, the atmosphere is
                        motivating, and the community is amazing. I've achieved goals I never thought possible!"</p>
                    <div class="client-info">
                        <div class="client-img">
                            <img src="assets/images/avatar-2.png" alt="Client">
                        </div>
                        <h4>Jane Rose</h4>
                    </div>
                </div>
                <!-- Review 3 -->
                <div class="review-card">
                    <div class="stars">
                        <i class="fa-solid fa-star"></i>
                        <i class="fa-solid fa-star"></i>
                        <i class="fa-solid fa-star"></i>
                        <i class="fa-solid fa-star"></i>
                        <i class="fa-solid fa-star"></i>
                    </div>
                    <p class="review-text">"Joining GymFit was the best decision I ever made. The personal training
                        sessions are worth every penny. I'm in the best shape of my life at 45!"</p>
                    <div class="client-info">
                        <div class="client-img">
                            <img src="assets/images/avatar-1.png" alt="Client">
                        </div>
                        <h4>Mike Smith</h4>
                    </div>
                </div>
                <!-- Review 4 -->
                <div class="review-card">
                    <div class="stars">
                        <i class="fa-solid fa-star"></i>
                        <i class="fa-solid fa-star"></i>
                        <i class="fa-solid fa-star"></i>
                        <i class="fa-solid fa-star"></i>
                        <i class="fa-solid fa-star"></i>
                    </div>
                    <p class="review-text">"The atmosphere here is electric! I've been to many gyms, but the dedication
                        of the staff at GymFit is unmatched. Highly recommend to anyone looking for real results."</p>
                    <div class="client-info">
                        <div class="client-img">
                            <img src="assets/images/avatar-4.png" alt="Client">
                        </div>
                        <h4>David Miller</h4>
                    </div>
                </div>
            </div>
            <!-- Central Image Decoration (Optional - replicating 'buff guy' overlay idea from image) -->
            <!-- <div class="center-buff-guy"><img src="assets/images/hero-new.png"></div> -->
        </div>
    </section>

    <!-- Pricing Section -->
    <section id="pricing" class="section pricing-section">
        <div class="pricing-toggle">
            <button class="toggle-btn active" data-period="monthly">Monthly</button>
            <button class="toggle-btn" data-period="yearly">Yearly</button>
        </div>

        <div class="container pricing-grid">
            <!-- Basic Plan -->
            <div class="pricing-card">
                <h3>Basic</h3>
                <div class="price-tag" data-monthly="₹399" data-yearly="₹2999">₹399 <span>/ Mo</span></div>
                <ul class="features-list">
                    <li>Gym Access <i class="fa-solid fa-check check"></i></li>
                    <li>Free Locker <i class="fa-solid fa-check check"></i></li>
                    <li>Group Class <i class="fa-solid fa-check check"></i></li>
                    <li>Personal Trainer <i class="fa-solid fa-xmark cross"></i></li>
                    <li>Protein Drinks <i class="fa-solid fa-xmark cross"></i></li>
                </ul>
                <a href="#" class="btn-primary">Join Now</a>
            </div>
            <!-- Standard Plan -->
            <div class="pricing-card ">
                <div class="badge">Popular</div>
                <h3>Standard</h3>
                <div class="price-tag" data-monthly="₹899" data-yearly="₹5999">₹899 <span>/ Mo</span></div>
                <ul class="features-list">
                    <li>Gym Access <i class="fa-solid fa-check check"></i></li>
                    <li>Free Locker <i class="fa-solid fa-check check"></i></li>
                    <li>Group Class <i class="fa-solid fa-check check"></i></li>
                    <li>Personal Trainer <i class="fa-solid fa-check check"></i></li>
                    <li>Protein Drinks <i class="fa-solid fa-xmark cross"></i></li>
                </ul>
                <a href="#" class="btn-primary">Join Now</a>
            </div>
            <!-- Premium Plan -->
            <div class="pricing-card">
                <h3>Premium</h3>
                <div class="price-tag" data-monthly="₹999" data-yearly="₹10999">₹999 <span>/ Mo</span></div>
                <ul class="features-list">
                    <li>Gym Access <i class="fa-solid fa-check check"></i></li>
                    <li>Free Locker <i class="fa-solid fa-check check"></i></li>
                    <li>Group Class <i class="fa-solid fa-check check"></i></li>
                    <li>Personal Trainer <i class="fa-solid fa-check check"></i></li>
                    <li>Protein Drinks <i class="fa-solid fa-check check"></i></li>
                    <li>Customized Workout Plan <i class="fa-solid fa-check check"></i></li>
                </ul>
                <a href="#" class="btn-primary">Join Now</a>
            </div>
        </div>
    </section>

    <!-- Get In Touch Section -->
    <section id="contact" class="section contact-section">
        <div class="container contact-container">
            <div class="contact-image">
                <img src="assets/images/Gymen.png" alt="Contact Trainer">
            </div>
            <div class="contact-form-wrapper">
                <h2>Get In Touch</h2>
                <form class="contact-form">
                    <div class="form-row">
                        <input type="text" placeholder="Name" class="form-input">
                        <input type="text" placeholder="Enter Number" class="form-input">
                    </div>
                    <div class="form-row">
                        <input type="email" placeholder="Enter Email" class="form-input">
                        <select class="form-input" style="color: #aaa;">
                            <option value="">Select Gender</option>
                            <option value="male">Male</option>
                            <option value="female">Female</option>
                        </select>
                    </div>
                    <textarea placeholder="Enter Message" class="form-input"></textarea>
                    <button type="submit" class="btn-skew">Send Message</button>
                </form>
            </div>
        </div>
    </section>

    <!-- New Footer -->
    <footer class="new-footer">
        <div class="container">
            <div class="footer-top">

                <div class="footer-info-item">
                    <i class="fa-solid fa-phone"></i>
                    <div>
                        <h4>Contact Number</h4>
                        <p>+91 9283754672</p>
                    </div>
                </div>
                <div class="footer-info-item">
                    <i class="fa-solid fa-envelope"></i>
                    <div>
                        <h4>Email</h4>
                        <p>GymFit@gmail.com</p>
                    </div>
                </div>
                <div class="footer-info-item">
                    <i class="fa-solid fa-comment"></i>
                    <div>
                        <h4>Any Suggestions?</h4>
                        <p>send feedback</p>
                    </div>
                </div>
            </div>

            <div class="footer-main">
                <div class="footer-title">
                    <h2>Stay Disciplined, Stay Strong</h2>

                </div>



            </div>
        </div>
        <div class="copyright">
            <p>Created By GymFit | All Rights Reserved!</p>
        </div>
    </footer>

    <script src="script.js?v=<?php echo time(); ?>"></script>
</body>

</html>