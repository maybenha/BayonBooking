<?php
// ==========================================
// ENHANCED INDEX PAGE WITH DATABASE IMAGES
// File: index.php
// ==========================================

session_start();
require_once 'config/database.php';
require_once 'classes/RoomManager.php';
require_once 'classes/BookingManager.php';

$database = new Database();
$db = $database->getConnection();
$roomManager = new RoomManager($db);
$bookingManager = new BookingManager($db);

// Get filter parameters
$room_type = isset($_GET['room_type']) ? $_GET['room_type'] : '';
$max_price = isset($_GET['max_price']) && $_GET['max_price'] !== '' ? (float)$_GET['max_price'] : null;
$capacity = isset($_GET['capacity']) && $_GET['capacity'] !== '' ? (int)$_GET['capacity'] : null;
$check_in = isset($_GET['check_in']) && $_GET['check_in'] !== '' ? $_GET['check_in'] : null;
$check_out = isset($_GET['check_out']) && $_GET['check_out'] !== '' ? $_GET['check_out'] : null;
$bed_type = isset($_GET['bed_type']) ? $_GET['bed_type'] : '';

// Get rooms with filters
$rooms = $roomManager->getAvailableRoomsWithFilters($room_type, $max_price, $capacity, $check_in, $check_out, $bed_type);
$roomTypes = $roomManager->getRoomTypes();
$is_admin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
?>

<?php include 'includes/header.php'; ?>

<style>
    :root {
        --primary-dark: #02850D;
        --primary-green: rgb(21, 141, 0);
        --dark-green: #02850D;
        --light-green: #ffffff;
        --bg-light: #F1F0E7;
        --text-dark: #02850D;
        --white: #ffffff;
        --light-gray: #ffffff;
        --highlight: #ffffff;
        --shadow: 0 10px 30px rgba(255, 255, 255, 0.09);
    }

    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        font-family: 'Siemreap', sans-serif;
        background-color: var(--white);
        color: var(--text-dark);
        overflow-x: hidden;
    }

    /* Language Button */
    .language-btn {
        display: flex;
        align-items: center;
        gap: 8px;
        color: white !important;
        font-weight: 500;
    }

    .language-flag {
        width: 28px;
        height: 28px;
        border-radius: 50%;
        object-fit: cover;
        border: 2px solid white;
    }

    .dropdown-flag {
        width: 30px;
        height: 30px;
        border-radius: 50%;
        object-fit: cover;
        margin-right: 10px;
        border: 1px solid #ddd;
    }

    .language-menu {
        border-radius: 14px;
        padding: 10px;
        min-width: 180px;
    }

    .dropdown-item {
        padding: 10px 14px;
        border-radius: 10px;
        transition: 0.3s;
        font-weight: 500;
    }

    .dropdown-item:hover {
        background: #d5c0b5;
        color: white;
    }

    /* Navbar */
    .glass-navbar {
        background: rgba(255, 255, 255, 0.97);
        backdrop-filter: blur(10px);
        box-shadow: var(--shadow);
        padding: 1rem 0;
        transition: 0.3s ease;
    }

    .navbar-brand {
        font-size: 1.8rem;
        font-weight: bold;
        color: var(--primary-green) !important;
    }

    .navbar-brand i {
        color: var(--primary-green);
    }

    .nav-link {
        color: #252424 !important;
        margin: 0 0.5rem;
        font-weight: 500;
        transition: 0.3s ease;
    }

    .nav-link:hover {
        color: #252424;
        transform: translateY(-2px);
    }

    /* Buttons */
    .btn-register-nav,
    .btn-logout,
    .btn-gold,
    .btn-book {
        background: var(--highlight);
        color: var(--primary-green) !important;
        border: none;
        font-weight: 600;
        transition: 0.3s ease;
        border: #252424 solid 2px;
    }

    .btn-register-nav,
    .btn-logout {
        padding: 0.5rem 1.2rem !important;
    }

    .btn-gold {
        padding: 12px 30px;
        text-decoration: none;
        display: inline-block;
        font-size: 1.1rem;
    }

    .btn-book {
        padding: 8px 20px;
        text-decoration: none;
        display: inline-block;
    }

    /* Hero Section */
    .hero-section {
        height: 85vh;
        background: linear-gradient(rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.6)),
            url('https://phgcdn.com/images/uploads/REPGH/masthead/2.png');
        background-size: cover;
        background-position: center;
        background-attachment: fixed;
        display: flex;
        align-items: center;
        justify-content: center;
        text-align: center;
        margin-top: 0;
        position: relative;
    }

    .hero-content h1 {

        font-size: 5rem;
        font-weight: 700;
        text-transform: uppercase;

        margin-bottom: 1rem;
        color: #ffb700;
        /* animation: glowText 3s ease-in-out infinite alternate, fadeInUp 1.2s ease;
        transition: transform 0.4s ease; */
    }

    .hero-content h1:hover {
        transform: scale(1.05);
    }

    @keyframes glowText {
        from {
            text-shadow: 0 0 5px rgba(0, 0, 0, 0.6), 0 0 10px rgba(0, 0, 0, 0.4), 3px 3px 10px rgba(0, 0, 0, 0.4);
        }

        to {
            text-shadow: 0 0 10px rgba(255, 255, 255, 0.5), 0 0 20px rgba(255, 255, 255, 0.5), 0 0 30px rgba(255, 255, 255, 0.5), 4px 4px 15px rgba(255, 255, 255, 0.5);
        }
    }

    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(40px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .hero-content p {
        font-size: 1.3rem;
        font-family: 'Koulen', sans-serif;
        margin-bottom: 2rem;
        animation: fadeInUp 1s ease 0.2s forwards;
        opacity: 0;
        color: white;
    }

    /* Section Title */
    .section-title {
        text-align: center;
        margin: 4rem 0 2rem;
        font-size: 2.5rem;
        color: var(--primary-green);
        position: relative;
    }

    .section-title::after {
        content: '';
        display: block;
        width: 80px;
        height: 4px;
        background: var(--primary-green);
        margin: 15px auto 0;
    }

    /* Room Cards */
    .room-card {
        background: var(--white);
        overflow: hidden;
        box-shadow: var(--shadow);
        transition: 0.3s ease;
        margin-bottom: 30px;
        border: 1px solid rgba(35, 114, 39, 0.1);
        border-radius: 5px;
    }

    .room-card:hover {
        transform: translateY(-10px);
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
    }

    .room-img {
        height: 250px;
        object-fit: cover;
        width: 100%;
    }

    .rating {
        color: var(--primary-green);
        margin: 10px 0;
    }

    .room-info {
        margin: 15px 0;
        font-size: 0.9rem;
    }

    .room-info i {
        width: 25px;
        color: var(--primary-green);
    }

    .price {
        font-size: 1.3rem;
        font-weight: bold;
        color: var(--primary-green);
        margin: 10px 0;
    }

    /* Services Section */
    .services-section {
        background: #ffffff;
        padding: 60px 0;
    }

    .service-card {
        text-align: center;
        padding: 30px;
        background: var(--white);
        box-shadow: var(--shadow);
        transition: 0.3s ease;
        height: 100%;
        border-radius: 12px;
    }

    .service-card:hover {
        transform: translateY(-8px);
    }

    .service-icon {
        font-size: 3rem;
        color: var(--primary-green);
        margin-bottom: 1rem;
    }

    /* Testimonials Section */
    .testimonials-section {
        background: white;
        padding: 60px 0;
    }

    .testimonial-card {
        background: var(--white);
        padding: 30px;
        box-shadow: var(--shadow);
        text-align: center;
        margin: 20px;
        border-radius: 12px;
    }

    .testimonial-img {
        width: 120px;
        height: 120px;
        border-radius: 5%;
        margin-bottom: 15px;
        object-fit: cover;
        border: 3px solid #02850D;
    }

    .testimonial-text {
        color: #666;
    }

    /* About Banner Section */
    .about-banner {
        background-color: #ffffff;
        display: flex;
        align-items: center;
        gap: 20px;
        max-width: 1300px;
        margin: 50px auto;
        padding: 40px;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        flex-wrap: wrap;
    }

    .about-banner img {
        width: 400px;
        height: auto;
        border-radius: 2px;
        object-fit: cover;
    }

    .about-text {
        flex: 1;
    }

    .about-text p {
        margin: 20px;
        font-family: 'Siemreap', 'Lucida Sans', sans-serif;
        text-align: center;
        line-height: 1.6;
        color: #555;
    }

    .about-text h2 {
        margin: 20px;
        text-align: center;
        color: var(--primary-green);
    }

    /* Filter Section */
    .filter-section {
        padding: 40px 0 20px 0;
        background: white;
        position: relative;
        overflow: hidden;
    }

    .filter-card {
        background: white;
        padding: 30px;
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        border-radius: 20px;
        position: relative;
        z-index: 1;
    }

    .filter-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 25px 50px rgba(0, 0, 0, 0.2);
    }

    .filter-title {
        text-align: center;
        color: #333;
        margin-bottom: 25px;
        font-size: 28px;
        font-weight: 700;
        position: relative;
        padding-bottom: 15px;
    }

    .filter-title::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 50%;
        transform: translateX(-50%);
        width: 60px;
        height: 3px;
        background: linear-gradient(90deg, #c9a84c, #ffd700);
    }

    .btn-filter {
        background: #252424;
        color: white;
        border: none;
        padding: 12px 30px;
        border-radius: 8px;
        font-weight: 600;
        transition: all 0.3s ease;
        margin-right: 10px;
    }

    .btn-filter:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
    }

    .btn-filter-reset {
        background: #6c757d;
        color: white;
        border: none;
        padding: 12px 30px;
        border-radius: 8px;
        font-weight: 600;
        transition: all 0.3s ease;
    }

    .btn-filter-reset:hover {
        background: #5a6268;
        transform: translateY(-2px);
    }

    /* Booking Modal */
    .booking-modal .modal-content {
        border-radius: 20px;
    }

    .price-breakdown {
        background: #f8f9fa;
        padding: 15px;
        border-radius: 8px;
        margin: 15px 0;
    }

    .qr-section {
        text-align: center;
        padding: 15px;
        background: #f8f9fa;
        border-radius: 8px;
        margin: 15px 0;
    }

    .qr-section img {
        width: 150px;
        margin: 10px 0;
    }

    /* Footer */
    .footer {
        background: white;
        font-family: 'Siemreap', sans-serif;
        color: #252424;
        position: relative;
        overflow: hidden;
        margin-top: 60px;
    }

    .footer::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 5px;
        background: linear-gradient(90deg, #c9a84c, #ffd700, #c9a84c);
    }

    .footer-widget {
        margin-bottom: 20px;
    }

    .footer-title {
        font-size: 18px;
        font-weight: 700;
        margin-bottom: 25px;
        position: relative;
        padding-bottom: 12px;
        color: #252424;
    }

    .footer-title::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 0;
        width: 50px;
        height: 2px;
        background: linear-gradient(90deg, #c9a84c, transparent);
    }

    .footer-links {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .footer-links li {
        margin-bottom: 12px;
    }

    .footer-links a {
        color: #252424;
        text-decoration: none;
        transition: all 0.3s ease;
        font-size: 14px;
        display: inline-block;
    }

    .footer-links a i {
        font-size: 10px;
        margin-right: 8px;
        transition: transform 0.3s ease;
    }

    .footer-links a:hover {
        color: #c9a84c;
        transform: translateX(5px);
    }

    .contact-item {
        display: flex;
        margin-bottom: 20px;
    }

    .contact-item i {
        font-size: 18px;
        color: #c9a84c;
        margin-right: 15px;
        margin-top: 3px;
    }

    .social-icons {
        display: flex;
        gap: 12px;
        flex-wrap: wrap;
        align-items: center;
    }

    .social-icon {
        width: 38px;
        height: 38px;
        background: rgba(0, 0, 0, 0.1);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        color: #252424;
        transition: all 0.3s ease;
        text-decoration: none;
    }

    .social-icon:hover {
        transform: translateY(-3px);
        color: white;
    }

    .social-icon.facebook:hover {
        background: #1877f2;
        color: white;
    }

    .social-icon.instagram:hover {
        background: #e4405f;
        color: white;
    }

    .social-icon.twitter:hover {
        background: #1da1f2;
        color: white;
    }

    .footer-bottom {
        padding: 20px 0;
        background: rgba(0, 0, 0, 0.03);
        text-align: center;
    }

    .back-to-top {
        position: fixed;
        bottom: 30px;
        right: 30px;
        background: #252424;
        color: white;
        width: 45px;
        height: 45px;
        border-radius: 50%;
        border: none;
        cursor: pointer;
        display: none;
        align-items: center;
        justify-content: center;
        transition: all 0.3s ease;
        z-index: 1000;
    }

    .back-to-top:hover {
        background: #c9a84c;
        transform: translateY(-5px);
    }

    /* Responsive */
    @media (max-width: 768px) {
        .hero-content h1 {
            font-size: 2.5rem;
        }

        .hero-content p {
            font-size: 1rem;
        }

        .room-card {
            margin: 0 15px 30px;
        }

        .about-banner {
            flex-direction: column;
            text-align: center;
            padding: 20px;
        }

        .about-banner img {
            width: 100%;
            max-width: 300px;
        }

        .filter-title {
            font-size: 22px;
        }

        .back-to-top {
            bottom: 20px;
            right: 20px;
        }
    }

    /* Animation for filter card */
    @keyframes slideInDown {
        from {
            opacity: 0;
            transform: translateY(-30px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .filter-card {
        animation: slideInDown 0.6s ease-out;
    }

    .container h2 {
        font-family: 'Moul', sans-serif;
        color: white;
        font-size: clamp(4rem, 10vw, 5rem);
        
        text-align: center;
        margin-bottom: 20px;
    }
</style>

<!-- Hero Section -->
<section class="hero-section">
    <div class="container">
        <div class="hero-content">
            <h2 >
                សណ្ឋាគារបុប្ផាខ្មែរ
            </h2>
            <p>ទទួលយកបទពិសោធន៍ប្រណីតភាព និងផាសុកភាពនៅចំកណ្តាលក្រុងសៀមរាប</p>
            <a href="#rooms" class="btn-gold">កក់បន្ទប់ស្នាក់នៅ <i class="fas fa-arrow-right"></i></a>
        </div>
    </div>
</section>

<!-- Filter Section -->
<section class="filter-section">
    <div class="container">
        <div class="filter-card">
            <h3 class="filter-title">ស្វែងរកបន្ទប់</h3>
            <form method="GET" action="" id="filterForm">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Room Type</label>
                        <select name="room_type" class="form-select filter-input">
                            <option value="">All Types</option>
                            <?php foreach ($roomTypes as $type): ?>
                                <option value="<?php echo $type['type_name']; ?>" <?php echo ($room_type == $type['type_name']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($type['type_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Max Price (USD)</label>
                        <input type="number" name="max_price" class="form-control filter-input" placeholder="Any" value="<?php echo $max_price; ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Capacity</label>
                        <select name="capacity" class="form-select filter-input">
                            <option value="">Any</option>
                            <option value="1" <?php echo $capacity == 1 ? 'selected' : ''; ?>>1 Person</option>
                            <option value="2" <?php echo $capacity == 2 ? 'selected' : ''; ?>>2 Persons</option>
                            <option value="3" <?php echo $capacity == 3 ? 'selected' : ''; ?>>3 Persons</option>
                            <option value="4" <?php echo $capacity == 4 ? 'selected' : ''; ?>>4+ Persons</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Bed Type</label>
                        <select name="bed_type" class="form-select filter-input">
                            <option value="">Any</option>
                            <option value="single" <?php echo $bed_type == 'single' ? 'selected' : ''; ?>>Single Bed</option>
                            <option value="double" <?php echo $bed_type == 'double' ? 'selected' : ''; ?>>Double Bed</option>
                            <option value="queen" <?php echo $bed_type == 'queen' ? 'selected' : ''; ?>>Queen Bed</option>
                            <option value="king" <?php echo $bed_type == 'king' ? 'selected' : ''; ?>>King Bed</option>
                        </select>
                    </div>
                </div>
                <div class="row g-3 mt-2">
                    <div class="col-md-3">
                        <label class="form-label">Check-in Date</label>
                        <input type="date" name="check_in" class="form-control filter-input" value="<?php echo $check_in; ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Check-out Date</label>
                        <input type="date" name="check_out" class="form-control filter-input" value="<?php echo $check_out; ?>">
                    </div>
                    <div class="col-md-6 d-flex align-items-end gap-2">
                        <button type="submit" class="btn-filter"><i class="fas fa-search"></i> Search Rooms</button>
                        <button type="button" class="btn-filter-reset" onclick="resetFilters()"><i class="fas fa-undo-alt"></i> Reset</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</section>

<!-- Rooms Section -->
<div class="container" id="rooms">
    <p style="color: #333;" class="section-title">ជ្រើសរើសបន្ទប់ដែលសាកសមនឹងអ្នក</p>

    <?php if (count($rooms) > 0): ?>
        <div class="row">
            <?php foreach ($rooms as $room): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="room-card">
                        <?php if ($room['room_image'] && file_exists($room['room_image'])): ?>
                            <img src="<?php echo $room['room_image']; ?>" class="room-img" alt="<?php echo htmlspecialchars($room['type_name']); ?> Room">
                        <?php else: ?>
                            <img src="assets/images/default-room.jpg" class="room-img" alt="Room">
                        <?php endif; ?>
                        <div class="card-body p-4">
                            <h5 class="card-title"><?php echo htmlspecialchars($room['type_name']); ?> - Room <?php echo htmlspecialchars($room['room_number']); ?></h5>
                            <div class="rating">
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star-half-alt"></i>
                                <span class="text-muted">(4.5/5)</span>
                            </div>
                            <div class="room-info">
                                <div><i class="fas fa-layer-group"></i> Floor <?php echo $room['floor']; ?></div>
                                <div><i class="fas fa-user-friends"></i> Max <?php echo $room['capacity']; ?> guests</div>
                                <div><i class="fas fa-wifi"></i> <?php echo htmlspecialchars(substr($room['equipments'] ?? 'Standard amenities', 0, 50)); ?></div>
                            </div>
                            <div class="price">$<?php echo number_format($room['price_per_night'], 2); ?> <small>/ night</small></div>

                            <?php if (!$is_admin): ?>
                                <button class="btn-book w-100" onclick="openBookingModal(
                                    <?php echo $room['room_id']; ?>,
                                    '<?php echo htmlspecialchars($room['room_number']); ?>',
                                    <?php echo $room['price_per_night']; ?>,
                                    '<?php echo htmlspecialchars($room['type_name']); ?>',
                                    <?php echo $room['capacity']; ?>
                                )">
                                    <i class="fas fa-calendar-check"></i> Book Now
                                </button>
                            <?php else: ?>
                                <button class="btn-book w-100" onclick="alert('Admin cannot book rooms. Please use customer account.')" style="background: #6c757d; border-color: #6c757d;">
                                    <i class="fas fa-eye"></i> View Only
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="alert alert-info text-center p-5">
            <i class="fas fa-bed fa-3x mb-3"></i>
            <h4>No rooms available matching your criteria</h4>
            <p>Please try different filters or check back later.</p>
            <button class="btn-gold mt-3" onclick="resetFilters()">Reset Filters</button>
        </div>
    <?php endif; ?>
</div>

<!-- Services Section -->
<section class="services-section" id="services-section">
    <div class="container">
        <h4 class="section-title">សេវាកម្មរបស់យើងខ្ញុំ</h4>
        <div class="row g-4">
            <div class="col-md-3 col-sm-6">
                <div class="service-card">
                    <div class="service-icon"><i class="fas fa-utensils"></i></div>
                    <h5>Restaurant & Bar</h5>
                    <p>Exquisite local and international cuisine</p>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="service-card">
                    <div class="service-icon"><i class="fas fa-spa"></i></div>
                    <h5>Spa & Wellness</h5>
                    <p>Relaxing traditional Khmer massage</p>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="service-card">
                    <div class="service-icon"><i class="fas fa-swimmer"></i></div>
                    <h5>Swimming Pool</h5>
                    <p>Infinity pool with city view</p>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="service-card">
                    <div class="service-icon"><i class="fas fa-concierge-bell"></i></div>
                    <h5>24/7 Concierge</h5>
                    <p>Dedicated service at any hour</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- About Banner Section -->
<div class="about-banner">
    <img style="width: 700px; height: 700px;" src="https://phgcdn.com/images/uploads/REPGH/masthead/2.png" alt="Bayon Hotel">
    <div class="about-text">
        <h2>Discover Bayon Hotel</h2>
        <p>Located in the heart of Siem Reap, Bayon Hotel offers a perfect blend of traditional Khmer hospitality and modern luxury. Just minutes away from the magnificent Angkor Wat temple complex, our hotel provides an unforgettable experience for both leisure and business travelers. With elegantly designed rooms, world-class amenities, and exceptional service, we ensure every stay is memorable.</p>
        <p>Our commitment to excellence has made us one of the top-rated hotels in Siem Reap, recognized for our attention to detail and warm Cambodian hospitality.</p>
    </div>
</div>

<!-- Testimonials Section -->
<section class="testimonials-section" id="testimonials-section">
    <div class="container">
        <h2 style="color: #02850D;​​font-family: 'Moul', sans-serif;​font-size: 24px;" class="section-title">មតិ របស់អតិថិជន</h2>
        <div class="row">
            <div class="col-md-4">
                <div class="testimonial-card">
                    <img src="/assets/images/1692972101890.jpg" alt="Guest" class="testimonial-img">
                    <h5>Sophia Chen</h5>
                    <div class="rating mb-2">
                        <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
                    </div>
                    <p class="testimonial-text">"ការស្នាក់នៅដ៏អស្ចារ្យមែន! បុគ្គលិកមានចិត្តជួយយ៉ាងខ្លាំង ហើយបន្ទប់ក៏ស្រស់ស្អាតផងដែរ។ ទីតាំងនេះល្អឥតខ្ចោះសម្រាប់ការរុករកប្រាសាទអង្គរវត្ត។"</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="testimonial-card">
                    <img src="/assets/images/1731486748249.jpg" alt="Guest" class="testimonial-img">
                    <h5>David Williams</h5>
                    <div class="rating mb-2">
                        <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
                    </div>
                    <p class="testimonial-text">"បទពិសោធន៍សណ្ឋាគារល្អបំផុតនៅក្នុងប្រទេសកម្ពុជា! អាងហែលទឹកពិតជាអស្ចារ្យ ហើយអាហារពេលព្រឹកបែបប៊ូហ្វេក៏មានជម្រើសច្រើនដែរ។ សូមណែនាំយ៉ាងខ្លាំង!"</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="testimonial-card">
                    <img src="/assets/images/1775894429201.png" alt="Guest" class="testimonial-img">
                    <h5>Emma Thompson</h5>
                    <div class="rating mb-2">
                        <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star-half-alt"></i>
                    </div>
                    <p class="testimonial-text">"ចូលចិត្តការរចនាបែបប្រពៃណីខ្មែរ រួមផ្សំជាមួយនឹងគ្រឿងបរិក្ខារទំនើបៗ។ ការព្យាបាលស្ប៉ាគឺអស្ចារ្យណាស់។ ពិតជានឹងត្រលប់មកវិញ!"</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Booking Modal -->
<div class="modal fade booking-modal" id="bookingModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-calendar-check"></i> Book Your Stay</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="booking_process.php" id="bookingForm">
                <div class="modal-body">
                    <input type="hidden" name="room_id" id="modal_room_id">

                    <div class="row">
                        <div class="col-md-6">
                            <h6>Room Information</h6>
                            <table class="table table-sm">
                                <tr>
                                    <td>Room Number:</td>
                                    <td><strong id="modal_room_number"></strong></td>
                                </tr>
                                <tr>
                                    <td>Room Type:</td>
                                    <td><strong id="modal_room_type"></strong></td>
                                </tr>
                                <tr>
                                    <td>Price per Night:</td>
                                    <td><strong id="modal_price"></strong></td>
                                </tr>
                                <tr>
                                    <td>Max Capacity:</td>
                                    <td><strong id="modal_capacity"></strong> persons</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h6>Your Information</h6>
                            <div class="mb-3">
                                <input type="text" name="full_name" class="form-control" placeholder="Full Name" required value="<?php echo $_SESSION['user_name'] ?? ''; ?>">
                            </div>
                            <div class="mb-3">
                                <input type="email" name="email" class="form-control" placeholder="Email Address" required>
                            </div>
                            <div class="mb-3">
                                <input type="tel" name="phone" class="form-control" placeholder="Phone Number" required>
                            </div>
                        </div>
                    </div>

                    <hr>

                    <div class="row">
                        <div class="col-md-6">
                            <h6>Booking Dates</h6>
                            <div class="mb-3">
                                <label class="form-label">Check-in Date</label>
                                <input type="date" name="checkin_date" id="modal_checkin" class="form-control" required min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Check-out Date</label>
                                <input type="date" name="checkout_date" id="modal_checkout" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <h6>Stay Details</h6>
                            <div class="mb-3">
                                <label class="form-label">Number of Guests</label>
                                <select name="guests" id="modal_guests" class="form-select" required>
                                    <option value="1">1 Guest</option>
                                    <option value="2">2 Guests</option>
                                    <option value="3">3 Guests</option>
                                    <option value="4">4 Guests</option>
                                    <option value="5">5 Guests</option>
                                    <option value="6">6 Guests</option>
                                </select>
                            </div>
                            <div class="price-breakdown">
                                <table class="w-100">
                                    <tr>
                                        <td>Price per night:</td>
                                        <td class="text-end">$<span id="display_price_per_night">0</span></td>
                                    </tr>
                                    <tr>
                                        <td>Number of nights:</td>
                                        <td class="text-end"><span id="display_nights">0</span> nights</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Total Price:</strong></td>
                                        <td class="text-end"><strong>$<span id="display_total">0</span></strong></td>
                                    </tr>
                                    <tr style="color: #dc3545;">
                                        <td>Deposit (30%):</td>
                                        <td class="text-end">$<span id="display_deposit">0</span></td>
                                    </tr>
                                    <tr>
                                        <td>Remaining at Check-in:</td>
                                        <td class="text-end">$<span id="display_remaining">0</span></td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="qr-section">
                        <i class="fas fa-qrcode fa-2x mb-2"></i>
                        <p><strong>Reservation Deposit Payment</strong></p>
                        <img src="assets/images/qr.png" alt="Payment QR Code">
                        <p class="small text-muted">Please scan the QR code and pay 30% deposit to confirm your reservation.<br>
                            The remaining amount will be paid during check-in.</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-gold">Confirm Booking</button>
                </div>
            </form>
        </div>
    </div>
</div>

<button id="backToTop" class="back-to-top">
    <i class="fas fa-arrow-up"></i>
</button>

<script>
    let currentRoomPrice = 0;
    let currentCapacity = 0;

    function resetFilters() {
        window.location.href = 'index.php';
    }

    function openBookingModal(roomId, roomNumber, price, roomType, capacity) {
        currentRoomPrice = price;
        currentCapacity = capacity;

        document.getElementById('modal_room_id').value = roomId;
        document.getElementById('modal_room_number').innerHTML = roomNumber;
        document.getElementById('modal_room_type').innerHTML = roomType;
        document.getElementById('modal_price').innerHTML = '$' + price.toFixed(2) + ' / night';
        document.getElementById('modal_capacity').innerHTML = capacity;
        document.getElementById('display_price_per_night').innerHTML = price.toFixed(2);

        // Set max guests based on room capacity
        const guestsSelect = document.getElementById('modal_guests');
        guestsSelect.innerHTML = '';
        for (let i = 1; i <= capacity; i++) {
            const option = document.createElement('option');
            option.value = i;
            option.textContent = i + ' Guest' + (i > 1 ? 's' : '');
            guestsSelect.appendChild(option);
        }

        const today = new Date();
        const minCheckin = new Date(today);
        minCheckin.setDate(today.getDate() + 1);

        const checkinInput = document.getElementById('modal_checkin');
        const checkoutInput = document.getElementById('modal_checkout');

        // Set minimum dates
        checkinInput.min = minCheckin.toISOString().split('T')[0];
        checkinInput.value = '';
        checkoutInput.value = '';

        // Reset display
        document.getElementById('display_nights').innerHTML = '0';
        document.getElementById('display_total').innerHTML = '0';
        document.getElementById('display_deposit').innerHTML = '0';
        document.getElementById('display_remaining').innerHTML = '0';

        // Remove any existing error messages
        removeErrorMessages();

        // Set up event listeners
        checkinInput.onchange = validateAndUpdatePrice;
        checkoutInput.onchange = validateAndUpdatePrice;

        const modal = new bootstrap.Modal(document.getElementById('bookingModal'));
        modal.show();
    }

    function validateAndUpdatePrice() {
        const checkin = document.getElementById('modal_checkin').value;
        const checkout = document.getElementById('modal_checkout').value;

        // Remove existing error messages
        removeErrorMessages();

        if (!checkin && !checkout) {
            return;
        }

        if (checkin && !checkout) {
            // Only check-in selected, set minimum checkout date
            const checkinDate = new Date(checkin);
            const minCheckout = new Date(checkinDate);
            minCheckout.setDate(checkinDate.getDate() + 1);
            document.getElementById('modal_checkout').min = minCheckout.toISOString().split('T')[0];
            return;
        }

        if (!checkin && checkout) {
            showError('Please select check-in date first', 'checkin');
            document.getElementById('modal_checkout').value = '';
            return;
        }

        if (checkin && checkout) {
            const checkinDate = new Date(checkin);
            const checkoutDate = new Date(checkout);
            const today = new Date();
            today.setHours(0, 0, 0, 0);

            // Validation 1: Check-in cannot be in the past
            if (checkinDate < today) {
                showError('Check-in date cannot be in the past. Please select a future date.', 'checkin');
                document.getElementById('modal_checkin').value = '';
                document.getElementById('modal_checkout').value = '';
                resetPriceDisplay();
                return;
            }

            // Validation 2: Check-in cannot be today (needs at least 1 day advance)
            const tomorrow = new Date(today);
            tomorrow.setDate(today.getDate() + 1);
            if (checkinDate < tomorrow) {
                showError('Check-in date must be at least 1 day from today. Please select a future date.', 'checkin');
                document.getElementById('modal_checkin').value = '';
                document.getElementById('modal_checkout').value = '';
                resetPriceDisplay();
                return;
            }

            // Validation 3: Check-out must be after check-in
            if (checkoutDate <= checkinDate) {
                showError('Check-out date must be after check-in date. Please select a valid check-out date.', 'checkout');
                document.getElementById('modal_checkout').value = '';
                resetPriceDisplay();
                return;
            }

            // Validation 4: Maximum stay duration (optional - 30 days max)
            const maxStay = 30;
            const nights = Math.ceil((checkoutDate - checkinDate) / (1000 * 60 * 60 * 24));
            if (nights > maxStay) {
                showError(`Maximum stay is ${maxStay} nights. Please adjust your dates.`, 'checkout');
                document.getElementById('modal_checkout').value = '';
                resetPriceDisplay();
                return;
            }

            // All validations passed - calculate price
            updatePrice(checkinDate, checkoutDate);
        }
    }

    function updatePrice(checkinDate, checkoutDate) {
        const nights = Math.ceil((checkoutDate - checkinDate) / (1000 * 60 * 60 * 24));
        const total = currentRoomPrice * nights;
        const deposit = total * 0.30;
        const remaining = total - deposit;

        document.getElementById('display_nights').innerHTML = nights;
        document.getElementById('display_total').innerHTML = total.toFixed(2);
        document.getElementById('display_deposit').innerHTML = deposit.toFixed(2);
        document.getElementById('display_remaining').innerHTML = remaining.toFixed(2);

        // Show success message
        showSuccessMessage(nights, total);
    }

    function resetPriceDisplay() {
        document.getElementById('display_nights').innerHTML = '0';
        document.getElementById('display_total').innerHTML = '0';
        document.getElementById('display_deposit').innerHTML = '0';
        document.getElementById('display_remaining').innerHTML = '0';
    }

    function showError(message, fieldId) {
        // Create error message element
        const errorDiv = document.createElement('div');
        errorDiv.className = 'alert alert-danger alert-dismissible fade show mt-2';
        errorDiv.role = 'alert';
        errorDiv.innerHTML = `
            <i class="fas fa-exclamation-triangle"></i> 
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;

        // Insert error message after the relevant field
        const field = document.getElementById('modal_' + fieldId);
        const parent = field.parentElement;

        // Remove any existing error messages for this field
        const existingErrors = parent.querySelectorAll('.alert-danger');
        existingErrors.forEach(error => error.remove());

        parent.appendChild(errorDiv);

        // Auto-dismiss after 5 seconds
        setTimeout(() => {
            if (errorDiv.parentElement) {
                errorDiv.remove();
            }
        }, 5000);

        // Highlight the field
        field.style.borderColor = '#dc3545';
        field.style.backgroundColor = '#fff8f8';

        // Remove highlight when user starts typing
        field.onfocus = () => {
            field.style.borderColor = '';
            field.style.backgroundColor = '';
            const errors = parent.querySelectorAll('.alert-danger');
            errors.forEach(error => error.remove());
        };
    }

    function showSuccessMessage(nights, total) {
        // Remove any existing success messages
        const existingSuccess = document.querySelectorAll('.alert-success');
        existingSuccess.forEach(msg => msg.remove());

        // Create success message in the price breakdown section
        const priceBreakdown = document.querySelector('.price-breakdown');
        const successDiv = document.createElement('div');
        successDiv.className = 'alert alert-success mt-3 mb-0';
        successDiv.innerHTML = `
            <i class="fas fa-check-circle"></i> 
            <strong>✓ Valid Dates!</strong> Your stay of ${nights} night(s) totals $${total.toFixed(2)}. 
            Please proceed with the 30% deposit to confirm.
        `;

        priceBreakdown.appendChild(successDiv);

        // Auto-remove after 3 seconds
        setTimeout(() => {
            if (successDiv.parentElement) {
                successDiv.remove();
            }
        }, 3000);
    }

    function removeErrorMessages() {
        const errors = document.querySelectorAll('.alert-danger');
        errors.forEach(error => error.remove());

        // Reset field styles
        const checkinField = document.getElementById('modal_checkin');
        const checkoutField = document.getElementById('modal_checkout');
        if (checkinField) {
            checkinField.style.borderColor = '';
            checkinField.style.backgroundColor = '';
        }
        if (checkoutField) {
            checkoutField.style.borderColor = '';
            checkoutField.style.backgroundColor = '';
        }
    }

    // Add form submission validation
    document.getElementById('bookingForm')?.addEventListener('submit', function(e) {
        const checkin = document.getElementById('modal_checkin').value;
        const checkout = document.getElementById('modal_checkout').value;
        const guests = parseInt(document.getElementById('modal_guests').value);

        if (!checkin || !checkout) {
            e.preventDefault();
            showError('Please select both check-in and check-out dates before booking.', 'checkin');
            return false;
        }

        const checkinDate = new Date(checkin);
        const checkoutDate = new Date(checkout);
        const today = new Date();
        today.setHours(0, 0, 0, 0);

        // Re-validate dates before submission
        if (checkinDate < today) {
            e.preventDefault();
            showError('Check-in date cannot be in the past.', 'checkin');
            return false;
        }

        if (checkoutDate <= checkinDate) {
            e.preventDefault();
            showError('Check-out date must be after check-in date.', 'checkout');
            return false;
        }

        if (guests > currentCapacity) {
            e.preventDefault();
            showError(`Maximum capacity for this room is ${currentCapacity} guest(s). Please select a different room or reduce number of guests.`, 'guests');
            return false;
        }

        // If all validations pass, show confirmation
        return confirm('Please confirm your booking details are correct. You will be required to pay the 30% deposit to complete the reservation.');
    });

    // Add real-time guest validation
    document.getElementById('modal_guests')?.addEventListener('change', function() {
        const guests = parseInt(this.value);
        if (guests > currentCapacity) {
            showError(`This room can only accommodate up to ${currentCapacity} guest(s).`, 'guests');
            this.value = currentCapacity;
        }
    });

    // Back to top button
    const backToTop = document.getElementById('backToTop');
    window.addEventListener('scroll', () => {
        if (window.scrollY > 300) {
            backToTop.style.display = 'flex';
        } else {
            backToTop.style.display = 'none';
        }
    });

    backToTop.addEventListener('click', () => {
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    });

    // Smooth scrolling for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                const navbarHeight = 80;
                const targetPosition = target.getBoundingClientRect().top;
                const offsetPosition = targetPosition + window.pageYOffset - navbarHeight;
                window.scrollTo({
                    top: offsetPosition,
                    behavior: 'smooth'
                });
            }
        });
    });
</script>

<?php include 'includes/footer.php'; ?>