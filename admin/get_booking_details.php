<?php
// ==========================================
// GET BOOKING DETAILS FOR AJAX
// File: admin/get_booking_details.php
// ==========================================

session_start();
header('Content-Type: application/json');

require_once '../config/database.php';
require_once '../classes/BookingManager.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$database = new Database();
$db = $database->getConnection();
$bookingManager = new BookingManager($db);

$booking_id = isset($_GET['id']) ? $_GET['id'] : 0;

if ($booking_id) {
    $booking = $bookingManager->getBookingById($booking_id);
    if ($booking) {
        // Calculate number of nights
        $checkin = new DateTime($booking['checkin_date']);
        $checkout = new DateTime($booking['checkout_date']);
        $interval = $checkin->diff($checkout);
        $booking['nights'] = $interval->days;
        $booking['price_per_night'] = $booking['total_payment'] / $booking['nights'];
        
        echo json_encode($booking);
    } else {
        echo json_encode(['error' => 'Booking not found']);
    }
} else {
    echo json_encode(['error' => 'Invalid booking ID']);
}
?>