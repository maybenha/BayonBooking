<?php
// ==========================================
// 8. INDEX (LANDING PAGE) - WITH DATABASE INTEGRATION
// File: index.php
// Rooms are loaded dynamically from database with filtering
// ==========================================
?>

<?php
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

// Get available rooms with filters
$rooms = $roomManager->getAvailableRoomsWithFilters($room_type, $max_price, $capacity, $check_in, $check_out, $bed_type);
$roomTypes = $roomManager->getRoomTypes();
?>

<?php include 'includes/header.php'; ?>
<link href="https://fonts.googleapis.com/css2?family=Siemreap&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Koulen&display=swap" rel="stylesheet">

<style>
    /* Additional styles for filter section */
    .filter-section {
        padding: 60px 0 30px;
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    }
    
    .filter-card {
        background: white;
        border-radius: 20px;
        padding: 30px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    }
    
    .filter-title {
        text-align: center;
        margin-bottom: 25px;
        color: var(--dark-blue);
        font-weight: 600;
    }
    
    .filter-label {
        font-weight: 500;
        margin-bottom: 8px;
        color: var(--dark-blue);
        font-size: 14px;
    }
    
    .filter-input {
        border-radius: 10px;
        border: 1px solid #ddd;
        padding: 10px 15px;
        transition: all 0.3s ease;
    }
    
    .filter-input:focus {
        border-color: var(--gold);
        box-shadow: 0 0 0 0.2rem rgba(198,164,63,0.25);
    }
    
    .btn-filter-submit {
        background: linear-gradient(135deg, var(--gold), #a07e2e);
        color: var(--dark-blue);
        border: none;
        padding: 12px 30px;
        border-radius: 50px;
        font-weight: bold;
        margin-right: 10px;
        transition: all 0.3s ease;
    }
    
    .btn-filter-submit:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(198,164,63,0.3);
    }
    
    .btn-filter-reset {
        background: #6c757d;
        color: white;
        border: none;
        padding: 12px 30px;
        border-radius: 50px;
        font-weight: bold;
        transition: all 0.3s ease;
    }
    
    .btn-filter-reset:hover {
        background: #5a6268;
        transform: translateY(-2px);
    }
    
    .filter-buttons {
        display: flex;
        align-items: flex-end;
        gap: 10px;
    }
    
    .containerbanner {
        display: flex;
        align-items: center;
        gap: 40px;
        padding: 60px 20px;
        max-width: 1200px;
        margin: 0 auto;
        background: #f8f9fa;
    }
    
    .containerbanner img {
        width: 50%;
        border-radius: 20px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    }
    
    .containerbanner .text {
        width: 50%;
    }
    
    .containerbanner .text h2 {
        color: var(--dark-blue);
        margin-bottom: 20px;
        font-size: 28px;
    }
    
    .containerbanner .text p {
        color: #555;
        line-height: 1.8;
    }
    
    .running-text {
        animation: runningLight 2s linear infinite;
    }
    
    @keyframes runningLight {
        0% {
            text-shadow: 0 0 5px var(--gold), 0 0 10px var(--gold);
        }
        50% {
            text-shadow: 0 0 20px var(--gold), 0 0 30px var(--gold);
        }
        100% {
            text-shadow: 0 0 5px var(--gold), 0 0 10px var(--gold);
        }
    }
    
    .no-rooms-message {
        text-align: center;
        padding: 60px;
        background: #f8f9fa;
        border-radius: 20px;
        margin: 30px 0;
    }
    
    @media (max-width: 768px) {
        .containerbanner {
            flex-direction: column;
        }
        .containerbanner img, .containerbanner .text {
            width: 100%;
        }
        .filter-buttons {
            flex-direction: column;
        }
        .btn-filter-submit, .btn-filter-reset {
            width: 100%;
            margin-right: 0;
            margin-bottom: 10px;
        }
    }
</style>

<!-- Hero Section -->
<section class="hero-section">
    <div class="hero-content">
        <h1 class="running-text">សូមស្វាគមន៍មកកាន់សណ្ឋាគារបាយ័ន</h1>
        <p>ទទួលបទពិសោធន៍ការរសនៅដ៏មានសុភមង្គល​ និងផាសុខភាពក្នុងសណ្ឋាគារបាយ័ន</p>
        <a href="#rooms" class="btn-gold">កក់ឥឡូវនេះ <i class="fas fa-arrow-right"></i></a>
    </div>
</section>

<!-- Filter Section -->
<section class="filter-section">
    <div class="container">
        <div class="filter-card">
            <h3 class="filter-title">ស្វែករកបន្ទប់ដែលសាកសមនឹងអ្នក</h3>
            <form method="GET" action="" class="filter-form" id="filterForm">
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label for="room_type" class="filter-label">ប្រភេទបន្ទប់</label>
                        <select name="room_type" id="room_type" class="form-control filter-input">
                            <option value="">ជ្រើសរើសបន្ទប់</option>
                            <?php foreach($roomTypes as $type): ?>
                                <option value="<?php echo $type['type_name']; ?>" <?php echo ($room_type == $type['type_name']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($type['type_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-3 mb-3">
                        <label for="max_price" class="filter-label">ជ្រើសរើសតម្លែ (USD)</label>
                        <input type="number" name="max_price" id="max_price" class="form-control filter-input" 
                               placeholder="Enter max price" step="10" min="0" value="<?php echo $max_price; ?>">
                    </div>
                    
                    <div class="col-md-3 mb-3">
                        <label for="bed_type" class="filter-label">ប្រភេទគ្រែ</label>
                        <select name="bed_type" id="bed_type" class="form-control filter-input">
                            <option value="">Any Bed Type</option>
                            <option value="single" <?php echo ($bed_type == 'single') ? 'selected' : ''; ?>>Single Bed</option>
                            <option value="double" <?php echo ($bed_type == 'double') ? 'selected' : ''; ?>>Double Bed</option>
                            <option value="queen" <?php echo ($bed_type == 'queen') ? 'selected' : ''; ?>>Queen Bed</option>
                            <option value="king" <?php echo ($bed_type == 'king') ? 'selected' : ''; ?>>King Bed</option>
                        </select>
                    </div>
                    
                    <div class="col-md-3 mb-3">
                        <label for="capacity" class="filter-label">ចំនួនភ្ញៀវ</label>
                        <select name="capacity" id="capacity" class="form-control filter-input">
                            <option value="">Any Capacity</option>
                            <option value="1" <?php echo ($capacity == 1) ? 'selected' : ''; ?>>1 Guest</option>
                            <option value="2" <?php echo ($capacity == 2) ? 'selected' : ''; ?>>2 Guests</option>
                            <option value="3" <?php echo ($capacity == 3) ? 'selected' : ''; ?>>3 Guests</option>
                            <option value="4" <?php echo ($capacity == 4) ? 'selected' : ''; ?>>4+ Guests</option>
                        </select>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label for="check_in" class="filter-label">ថ្ងៃ Check-in</label>
                        <input type="date" name="check_in" id="check_in" class="form-control filter-input" value="<?php echo $check_in; ?>">
                    </div>
                    
                    <div class="col-md-3 mb-3">
                        <label for="check_out" class="filter-label">ថ្ងៃ Check-out</label>
                        <input type="date" name="check_out" id="check_out" class="form-control filter-input" value="<?php echo $check_out; ?>">
                    </div>
                    
                    <div class="col-md-6 mb-3 filter-buttons">
                        <button type="submit" class="btn-filter-submit">
                            <i class="fas fa-search"></i> ស្វែងរកបន្ទប់
                        </button>
                        <button type="button" class="btn-filter-reset" onclick="resetFilters()">
                            <i class="fas fa-undo-alt"></i> កំណត់ឡើងវិញ
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</section>

<!-- Featured Rooms Section - Dynamic from Database -->
<div class="container" id="rooms">
    <h2 class="section-title">បន្ទប់​ដែល​មាន​លក្ខណៈ​ពិសេស</h2>
    
    <?php if(count($rooms) > 0): ?>
        <div class="row">
            <?php foreach($rooms as $room): ?>
                <div class="col-md-6 col-lg-3 mb-4">
                    <div class="room-card card">
                        <?php 
                            // Random image based on room type
                            $roomImages = [
                                'Luxury' => 'https://images.unsplash.com/photo-1618773928121-c32242e63f39?ixlib=rb-4.0.3&w=600&h=400&fit=crop',
                                'VIP' => 'https://images.unsplash.com/photo-1566665797739-1674de7a421a?ixlib=rb-4.0.3&w=600&h=400&fit=crop',
                                'Normal' => 'https://images.unsplash.com/photo-1582719478250-c89cae4dc85b?ixlib=rb-4.0.3&w=600&h=400&fit=crop',
                                'Family' => 'https://images.unsplash.com/photo-1631049307264-da0ec9d70304?ixlib=rb-4.0.3&w=600&h=400&fit=crop'
                            ];
                            $imgUrl = isset($roomImages[$room['type_name']]) ? $roomImages[$room['type_name']] : $roomImages['Normal'];
                        ?>
                        <img src="<?php echo $imgUrl; ?>" class="room-img" alt="<?php echo htmlspecialchars($room['type_name']); ?> Room">
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($room['type_name']); ?> <?php echo htmlspecialchars($room['room_number']); ?></h5>
                            <div class="rating">
                                <?php 
                                    $stars = rand(4,5);
                                    for($i = 1; $i <= 5; $i++) {
                                        if($i <= $stars) {
                                            echo '★';
                                        } else {
                                            echo '☆';
                                        }
                                    }
                                ?>
                            </div>
                            <p class="card-text">
                                <?php 
                                    $desc = htmlspecialchars($room['room_description']);
                                    echo strlen($desc) > 100 ? substr($desc, 0, 100) . '...' : $desc;
                                ?>
                            </p>
                            <div class="room-info mb-2">
                                <small><i class="fas fa-user"></i> Max: <?php echo $room['capacity']; ?> guests</small><br>
                                <small><i class="fas fa-dollar-sign"></i> $<?php echo number_format($room['price_per_night'], 2); ?>/night</small>
                            </div>
                            <a href="#" class="btn-book" onclick="showBookingModal(<?php echo $room['room_id']; ?>, '<?php echo htmlspecialchars($room['room_number']); ?>', <?php echo $room['price_per_night']; ?>)">
                                View & Booking now
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="no-rooms-message">
            <i class="fas fa-bed" style="font-size: 48px; color: var(--gold); margin-bottom: 20px;"></i>
            <h4>No rooms available matching your criteria</h4>
            <p>Please try different filters or check back later.</p>
            <button onclick="resetFilters()" class="btn-gold" style="margin-top: 20px;">Reset Filters</button>
        </div>
    <?php endif; ?>
</div>

<!-- Banner Section -->
<div class="containerbanner">
    <img src="https://m.ahstatic.com/is/image/accorhotels/aja_p_7254-21?qlt=82&wid=1920&ts=1739266486274&dpr=off" alt="Hotel Banner">
    <div class="text">
        <h2>Beautiful Place</h2>
        <p>
            Every stay at Raffles Grand Hotel d'Angkor is accompanied by a personal butler.
            It's one of the many traditions we're planning to hold onto forever. Always discreetly attentive, 
            our butlers are here to assist you 24 hours a day. They are always ready with a warm smile and thoughtful 
            touches whenever you need them – and sometimes when you don't even think you do. You'll feel relieved of 
            responsibilities – imagine that – and free to let go and delve into your fascinating surroundings, not a care in the world.
        </p>
    </div>
</div>

<!-- Services Section -->
<section class="services-section" id="services">
    <div class="container">
        <h2 class="section-title">Our Services</h2>
        <div class="row">
            <div class="col-md-4 mb-4">
                <div class="service-card">
                    <i class="fas fa-swimming-pool service-icon"></i>
                    <h4>Infinity Pool</h4>
                    <p>Enjoy our stunning rooftop infinity pool with panoramic city views.</p>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="service-card">
                    <i class="fas fa-utensils service-icon"></i>
                    <h4>Fine Dining</h4>
                    <p>Exquisite local and international cuisine prepared by world-class chefs.</p>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="service-card">
                    <i class="fas fa-spa service-icon"></i>
                    <h4>Luxury Spa</h4>
                    <p>Rejuvenate your body and mind with our traditional Khmer spa treatments.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Testimonials Section -->
<section id="testimonials">
    <div class="container">
        <h2 class="section-title">What Our Guests Say</h2>
        <div class="row">
            <div class="col-md-4">
                <div class="testimonial-card">
                    <img src="https://media.licdn.com/dms/image/v2/D5603AQGi83hsi48T1w/profile-displayphoto-shrink_400_400/profile-displayphoto-shrink_400_400/0/1726236214065?e=1779926400&v=beta&t=ElQ2jPYR2Z-sivufrS87f447fZz_kLAGM9uCgXBqJV4" class="testimonial-img" alt="Guest">
                    <h5>Chan Sopheak</h5>
                    <div class="rating">★★★★★</div>
                    <p class="testimonial-text">"Absolutely amazing experience! The staff was friendly and the room was perfect."</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="testimonial-card">
                    <img src="https://media.licdn.com/dms/image/v2/D5603AQGnuzjp_CJqAg/profile-displayphoto-shrink_400_400/profile-displayphoto-shrink_400_400/0/1710333394361?e=1779926400&v=beta&t=WFSuhqxY3uPQ3ZfyjS_9ec9y-bPzDnx2SPE36tCxGdg" class="testimonial-img" alt="Guest">
                    <h5>Keo Sokheng</h5>
                    <div class="rating">★★★★☆</div>
                    <p class="testimonial-text">"Best hotel in Cambodia! The views are breathtaking and the service is world-class."</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="testimonial-card">
                    <img src="https://media.licdn.com/dms/image/v2/D4E03AQGSQOrmTvpTJA/profile-displayphoto-scale_400_400/B4EZoPIfyYKkAg-/0/1761190479135?e=1779926400&v=beta&t=S25RHICg2mYRMYhkoU5fvrtyIcHtSuGeohw8uj03A0E" class="testimonial-img" alt="Guest">
                    <h5>Sophan Vuthy</h5>
                    <div class="rating">★★★☆☆</div>
                    <p class="testimonial-text">"Incredible value for money. The luxury suite exceeded all our expectations!"</p>
                </div>
            </div>
        </div>
        <div class="row mt-4">
            <div class="col-12">
                <p style="font-weight: 600; margin-bottom: 10px;">ទីតាំងរបស់សណ្ឋាគារបាយ័ន</p>
                <iframe
                    src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3881.9097088040517!2d103.84589537572889!3d13.355890186995998!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x311017689c47572d%3A0x3e7960f6ebbbe7c!2sGrand%20Bayon%20Siem%20Reap%20Hotel!5e0!3m2!1skm!2skh!4v1778120234059!5m2!1skm!2skh"
                    width="100%"
                    height="250"
                    style="border:0; border-radius: 15px;"
                    allowfullscreen=""
                    loading="lazy"
                    referrerpolicy="no-referrer-when-downgrade">
                </iframe>
            </div>
        </div>
    </div>
</section>

<!-- Booking Modal -->
<div class="modal fade" id="bookingModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-calendar-check"></i> Book Your Stay</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="process_booking.php">
                <div class="modal-body">
                    <input type="hidden" name="room_id" id="modal_room_id">
                    <div class="mb-3">
                        <label class="form-label">Room Number</label>
                        <input type="text" class="form-control" id="modal_room_number" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Price per Night</label>
                        <input type="text" class="form-control" id="modal_price" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Check-in Date</label>
                        <input type="date" name="check_in" class="form-control" required min="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Check-out Date</label>
                        <input type="date" name="check_out" class="form-control" required min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Number of Guests</label>
                        <select name="guests" class="form-control" required>
                            <option value="1">1 Guest</option>
                            <option value="2">2 Guests</option>
                            <option value="3">3 Guests</option>
                            <option value="4">4 Guests</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-gold">Proceed to Book</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function resetFilters() {
        window.location.href = 'index.php';
    }
    
    function showBookingModal(roomId, roomNumber, price) {
        document.getElementById('modal_room_id').value = roomId;
        document.getElementById('modal_room_number').value = roomNumber;
        document.getElementById('modal_price').value = '$' + parseFloat(price).toFixed(2) + ' per night';
        
        var myModal = new bootstrap.Modal(document.getElementById('bookingModal'));
        myModal.show();
    }
    
    // Set min date for check-out based on check-in selection
    document.addEventListener('DOMContentLoaded', function() {
        const checkIn = document.querySelector('input[name="check_in"]');
        const checkOut = document.querySelector('input[name="check_out"]');
        
        if(checkIn && checkOut) {
            checkIn.addEventListener('change', function() {
                const checkInDate = new Date(this.value);
                const minCheckOut = new Date(checkInDate);
                minCheckOut.setDate(checkInDate.getDate() + 1);
                checkOut.min = minCheckOut.toISOString().split('T')[0];
                
                if(checkOut.value && new Date(checkOut.value) <= checkInDate) {
                    checkOut.value = minCheckOut.toISOString().split('T')[0];
                }
            });
        }
    });
</script>

<?php include 'includes/footer.php'; ?>