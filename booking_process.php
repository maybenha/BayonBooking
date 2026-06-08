<?php
// ==========================================
// ENHANCED BOOKING PROCESSING WITH PAYMENT
// File: booking_process.php
// ==========================================

session_start();
require_once 'config/database.php';
require_once 'classes/BookingManager.php';
require_once 'classes/RoomManager.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Check if form submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit();
}

// Get and validate inputs
$user_id = (int)$_SESSION['user_id'];
$room_id = (int)$_POST['room_id'];
$checkin_date = trim($_POST['checkin_date']);
$checkout_date = trim($_POST['checkout_date']);
$guests = isset($_POST['guests']) ? (int)$_POST['guests'] : 1;
$full_name = trim($_POST['full_name'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');

// Validate dates
if ($checkin_date >= $checkout_date) {
    $_SESSION['error'] = 'Check-out date must be after check-in date';
    header("Location: index.php");
    exit();
}

if ($checkin_date < date('Y-m-d')) {
    $_SESSION['error'] = 'Check-in date cannot be in the past';
    header("Location: index.php");
    exit();
}

// Initialize database and managers
$database = new Database();
$db = $database->getConnection();
$bookingManager = new BookingManager($db);
$roomManager = new RoomManager($db);

// Get room details
$room = $roomManager->getRoomById($room_id);
if (!$room) {
    $_SESSION['error'] = 'Room not found';
    header("Location: index.php");
    exit();
}

// Check room status
if ($room['room_status'] !== 'available') {
    $_SESSION['error'] = 'Room is not available for booking';
    header("Location: index.php");
    exit();
}

// Check availability
if (!$bookingManager->checkRoomAvailability($room_id, $checkin_date, $checkout_date)) {
    $_SESSION['error'] = 'Room is not available for selected dates';
    header("Location: index.php");
    exit();
}

// Calculate price
$price_result = $roomManager->calculateTotalPrice($room_id, $checkin_date, $checkout_date);
if (!$price_result['success']) {
    $_SESSION['error'] = $price_result['message'];
    header("Location: index.php");
    exit();
}

$total_payment = $price_result['total'];
$nights = $price_result['nights'];
$deposit = $price_result['deposit'];
$remaining = $price_result['remaining'];

// Create booking
$booking_result = $bookingManager->addBooking(
    $user_id,
    $room_id,
    $checkin_date,
    $checkout_date,
    $total_payment,
    $guests,
    '' // special requests
);

if ($booking_result['success']) {
    $booking_id = $booking_result['booking_id'];
    
    // Store booking info in session for confirmation
    $_SESSION['last_booking'] = [
        'booking_id' => $booking_id,
        'room_name' => $room['type_name'],
        'room_number' => $room['room_number'],
        'checkin_date' => $checkin_date,
        'checkout_date' => $checkout_date,
        'nights' => $nights,
        'guests' => $guests,
        'price_per_night' => $room['price_per_night'],
        'total_payment' => $total_payment,
        'deposit' => $deposit,
        'remaining' => $remaining
    ];
    
    // Redirect to confirmation page
    header("Location: booking_confirmation.php");
    exit();
} else {
    $_SESSION['error'] = $booking_result['message'];
    header("Location: index.php");
    exit();
}
?>