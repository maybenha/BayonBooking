<?php
// ==========================================
// ENHANCED BOOKING PROCESSING
// File: booking_process.php
// ==========================================

session_start();
require_once 'config/database.php';
require_once 'classes/BookingManager.php';
require_once 'classes/RoomManager.php';
require_once 'classes/NotificationManager.php'; // You may need to create this

// Set JSON header for AJAX requests
$is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    if ($is_ajax) {
        echo json_encode(['success' => false, 'message' => 'Please login first', 'redirect' => 'login.php']);
        exit();
    } else {
        header("Location: login.php");
        exit();
    }
}

// Check if form submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    if ($is_ajax) {
        echo json_encode(['success' => false, 'message' => 'Invalid request method']);
        exit();
    } else {
        header("Location: index.php");
        exit();
    }
}

// Validate required fields
$required_fields = ['room_id', 'checkin_date', 'checkout_date'];
foreach ($required_fields as $field) {
    if (!isset($_POST[$field]) || empty($_POST[$field])) {
        $error_message = "Please fill in all required fields";
        if ($is_ajax) {
            echo json_encode(['success' => false, 'message' => $error_message]);
            exit();
        } else {
            $_SESSION['error'] = $error_message;
            header("Location: index.php");
            exit();
        }
    }
}

// Sanitize and validate inputs
$user_id = (int)$_SESSION['user_id'];
$room_id = (int)$_POST['room_id'];
$checkin_date = trim($_POST['checkin_date']);
$checkout_date = trim($_POST['checkout_date']);
$guests = isset($_POST['guests']) ? (int)$_POST['guests'] : 1;
$special_requests = isset($_POST['special_requests']) ? trim($_POST['special_requests']) : '';
$first_name = isset($_POST['first_name']) ? trim($_POST['first_name']) : '';
$last_name = isset($_POST['last_name']) ? trim($_POST['last_name']) : '';
$phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
$email = isset($_POST['email']) ? trim($_POST['email']) : '';

// Validate guests count
if ($guests < 1 || $guests > 10) {
    $error_message = "Number of guests must be between 1 and 10";
    if ($is_ajax) {
        echo json_encode(['success' => false, 'message' => $error_message]);
        exit();
    } else {
        $_SESSION['error'] = $error_message;
        header("Location: index.php");
        exit();
    }
}

// Validate date format and values
$today = date('Y-m-d');
$min_checkin = date('Y-m-d', strtotime('+1 day')); // Minimum next day
$max_checkout = date('Y-m-d', strtotime('+1 year')); // Maximum 1 year ahead

if (!preg_match("/^\d{4}-\d{2}-\d{2}$/", $checkin_date) || 
    !preg_match("/^\d{4}-\d{2}-\d{2}$/", $checkout_date)) {
    $error_message = "Invalid date format";
    if ($is_ajax) {
        echo json_encode(['success' => false, 'message' => $error_message]);
        exit();
    } else {
        $_SESSION['error'] = $error_message;
        header("Location: index.php");
        exit();
    }
}

if ($checkin_date < $min_checkin) {
    $error_message = "Check-in date must be at least tomorrow";
    if ($is_ajax) {
        echo json_encode(['success' => false, 'message' => $error_message]);
        exit();
    } else {
        $_SESSION['error'] = $error_message;
        header("Location: index.php");
        exit();
    }
}

if ($checkin_date >= $checkout_date) {
    $error_message = "Check-out date must be after check-in date";
    if ($is_ajax) {
        echo json_encode(['success' => false, 'message' => $error_message]);
        exit();
    } else {
        $_SESSION['error'] = $error_message;
        header("Location: index.php");
        exit();
    }
}

if ($checkout_date > $max_checkout) {
    $error_message = "Check-out date cannot be more than 1 year in advance";
    if ($is_ajax) {
        echo json_encode(['success' => false, 'message' => $error_message]);
        exit();
    } else {
        $_SESSION['error'] = $error_message;
        header("Location: index.php");
        exit();
    }
}

// Calculate number of nights
$checkin_obj = new DateTime($checkin_date);
$checkout_obj = new DateTime($checkout_date);
$nights = $checkin_obj->diff($checkout_obj)->days;

if ($nights > 30) {
    $error_message = "Maximum booking duration is 30 nights";
    if ($is_ajax) {
        echo json_encode(['success' => false, 'message' => $error_message]);
        exit();
    } else {
        $_SESSION['error'] = $error_message;
        header("Location: index.php");
        exit();
    }
}

// Initialize database and managers
try {
    $database = new Database();
    $db = $database->getConnection();
    $bookingManager = new BookingManager($db);
    $roomManager = new RoomManager($db);
} catch (Exception $e) {
    error_log("Database connection error: " . $e->getMessage());
    $error_message = "System error. Please try again later.";
    if ($is_ajax) {
        echo json_encode(['success' => false, 'message' => $error_message]);
        exit();
    } else {
        $_SESSION['error'] = $error_message;
        header("Location: index.php");
        exit();
    }
}

// Check if room exists and get details
$room = $roomManager->getRoomById($room_id);
if (!$room) {
    $error_message = "Room not found";
    if ($is_ajax) {
        echo json_encode(['success' => false, 'message' => $error_message]);
        exit();
    } else {
        $_SESSION['error'] = $error_message;
        header("Location: index.php");
        exit();
    }
}

// Check room status
if ($room['room_status'] !== 'available') {
    $error_message = "Room is currently not available for booking";
    if ($is_ajax) {
        echo json_encode(['success' => false, 'message' => $error_message]);
        exit();
    } else {
        $_SESSION['error'] = $error_message;
        header("Location: index.php");
        exit();
    }
}

// Check room availability for dates (double-check)
if (!$bookingManager->checkRoomAvailability($room_id, $checkin_date, $checkout_date)) {
    $error_message = "Room is not available for selected dates. Please choose different dates.";
    if ($is_ajax) {
        echo json_encode(['success' => false, 'message' => $error_message]);
        exit();
    } else {
        $_SESSION['error'] = $error_message;
        header("Location: index.php");
        exit();
    }
}

// Check guest capacity
if ($guests > $room['capacity']) {
    $error_message = "Room can only accommodate up to " . $room['capacity'] . " guests";
    if ($is_ajax) {
        echo json_encode(['success' => false, 'message' => $error_message]);
        exit();
    } else {
        $_SESSION['error'] = $error_message;
        header("Location: index.php");
        exit();
    }
}

// Calculate total price with possible discounts
$price_result = $roomManager->calculateTotalPrice($room_id, $checkin_date, $checkout_date);
if (!$price_result['success']) {
    $error_message = $price_result['message'];
    if ($is_ajax) {
        echo json_encode(['success' => false, 'message' => $error_message]);
        exit();
    } else {
        $_SESSION['error'] = $error_message;
        header("Location: index.php");
        exit();
    }
}

$total_payment = $price_result['total'];
$price_per_night = $room['price_per_night'];

// Apply early bird discount (if booking 7+ days in advance)
$days_advance = (strtotime($checkin_date) - strtotime($today)) / (60 * 60 * 24);
if ($days_advance >= 7) {
    $discount = $total_payment * 0.05; // 5% early bird discount
    $total_payment -= $discount;
    $_SESSION['discount_applied'] = 5;
}

// Apply long stay discount (if 7+ nights)
if ($nights >= 7) {
    $discount = $total_payment * 0.10; // 10% long stay discount
    $total_payment -= $discount;
    $_SESSION['long_stay_discount'] = 10;
}

// Round to 2 decimal places
$total_payment = round($total_payment, 2);

// Generate unique booking reference
$booking_reference = 'BK' . strtoupper(uniqid()) . date('Ymd');

// Create booking using the addBooking method (which returns array with success/error)
$booking_result = $bookingManager->addBooking(
    $user_id,
    $room_id,
    $checkin_date,
    $checkout_date,
    $total_payment,
    $guests,
    $special_requests
);

if ($booking_result['success']) {
    $booking_id = $booking_result['booking_id'];
    
    // Store booking details in session for confirmation page
    $_SESSION['last_booking'] = [
        'booking_id' => $booking_id,
        'booking_reference' => $booking_reference,
        'room_name' => $room['type_name'] ?? $room['room_number'],
        'room_number' => $room['room_number'],
        'checkin_date' => $checkin_date,
        'checkout_date' => $checkout_date,
        'nights' => $nights,
        'guests' => $guests,
        'price_per_night' => $price_per_night,
        'total_payment' => $total_payment,
        'special_requests' => $special_requests
    ];
    
    // Optional: Send confirmation email if email is provided
    if (!empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
        sendBookingConfirmationEmail($email, [
            'booking_id' => $booking_id,
            'booking_reference' => $booking_reference,
            'user_name' => $first_name . ' ' . $last_name,
            'room_name' => $room['type_name'] ?? $room['room_number'],
            'room_number' => $room['room_number'],
            'checkin_date' => $checkin_date,
            'checkout_date' => $checkout_date,
            'nights' => $nights,
            'guests' => $guests,
            'total_payment' => $total_payment,
            'special_requests' => $special_requests
        ]);
    }
    
    // Log successful booking
    error_log("Booking created successfully - User ID: $user_id, Booking ID: $booking_id, Room ID: $room_id");
    
    // Return success response
    if ($is_ajax) {
        echo json_encode([
            'success' => true,
            'message' => 'Booking created successfully!',
            'booking_id' => $booking_id,
            'booking_reference' => $booking_reference,
            'redirect' => 'booking_confirmation.php'
        ]);
        exit();
    } else {
        $_SESSION['success'] = "Booking created successfully! Your booking reference is: " . $booking_reference;
        header("Location: booking_confirmation.php");
        exit();
    }
} else {
    // Log failed booking attempt
    error_log("Booking failed - User ID: $user_id, Room ID: $room_id, Error: " . ($booking_result['message'] ?? 'Unknown error'));
    
    $error_message = $booking_result['message'] ?? "Failed to create booking. Please try again.";
    
    if ($is_ajax) {
        echo json_encode(['success' => false, 'message' => $error_message]);
        exit();
    } else {
        $_SESSION['error'] = $error_message;
        header("Location: index.php");
        exit();
    }
}

/**
 * Send booking confirmation email
 */
function sendBookingConfirmationEmail($email, $booking_data) {
    $subject = "Booking Confirmation - " . $booking_data['booking_reference'];
    
    $message = "
    <html>
    <head>
        <title>Booking Confirmation</title>
        <style>
            body { font-family: Arial, sans-serif; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #543414; color: white; padding: 20px; text-align: center; }
            .content { padding: 20px; border: 1px solid #ddd; }
            .details { margin: 20px 0; }
            .details table { width: 100%; border-collapse: collapse; }
            .details td { padding: 8px; border-bottom: 1px solid #eee; }
            .footer { text-align: center; padding: 20px; font-size: 12px; color: #666; }
            .total { font-size: 18px; font-weight: bold; color: #543414; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h2>Booking Confirmation</h2>
                <p>Thank you for choosing our hotel!</p>
            </div>
            <div class='content'>
                <p>Dear <strong>" . htmlspecialchars($booking_data['user_name']) . "</strong>,</p>
                <p>Your booking has been confirmed. Please find the details below:</p>
                
                <div class='details'>
                    <table>
                        <tr><td><strong>Booking Reference:</strong></td><td>" . htmlspecialchars($booking_data['booking_reference']) . "</td></tr>
                        <tr><td><strong>Room Type:</strong></td><td>" . htmlspecialchars($booking_data['room_name']) . "</td></tr>
                        <tr><td><strong>Room Number:</strong></td><td>" . htmlspecialchars($booking_data['room_number']) . "</td></tr>
                        <tr><td><strong>Check-in Date:</strong></td><td>" . date('d M Y', strtotime($booking_data['checkin_date'])) . "</td></tr>
                        <tr><td><strong>Check-out Date:</strong></td><td>" . date('d M Y', strtotime($booking_data['checkout_date'])) . "</td></tr>
                        <tr><td><strong>Number of Nights:</strong></td><td>" . $booking_data['nights'] . " nights</td></tr>
                        <tr><td><strong>Number of Guests:</strong></td><td>" . $booking_data['guests'] . "</td></tr>
                        <tr><td><strong>Total Payment:</strong></td><td class='total'>$" . number_format($booking_data['total_payment'], 2) . "</td></tr>
                    </table>
                </div>
                
                <p><strong>Important Information:</strong></p>
                <ul>
                    <li>Check-in time: 2:00 PM</li>
                    <li>Check-out time: 12:00 PM</li>
                    <li>Please present your ID upon check-in</li>
                    <li>Payment can be made at the hotel</li>
                </ul>
                
                <p>If you need to modify or cancel your booking, please contact us at least 48 hours in advance.</p>
            </div>
            <div class='footer'>
                <p>© " . date('Y') . " Bayon Hotel. All rights reserved.</p>
                <p>This is an automated message, please do not reply.</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: bookings@bayonbooking.com" . "\r\n";
    
    // Uncomment to actually send email
    // mail($email, $subject, $message, $headers);
    
    return true;
}
?>