<?php
// ==========================================
// CANCEL BOOKING API
// File: cancel_booking.php
// ==========================================

session_start();
header('Content-Type: application/json');

require_once 'config/database.php';
require_once 'classes/BookingManager.php';
require_once 'classes/RoomManager.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login first']);
    exit();
}

if (!isset($_POST['id']) || empty($_POST['id'])) {
    echo json_encode(['success' => false, 'message' => 'Booking ID is required']);
    exit();
}

$booking_id = (int)$_POST['id'];
$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'] ?? 'customer';

$database = new Database();
$db = $database->getConnection();
$bookingManager = new BookingManager($db);
$roomManager = new RoomManager($db);

// Get booking details
$booking = $bookingManager->getBookingById($booking_id);

if (!$booking) {
    echo json_encode(['success' => false, 'message' => 'Booking not found']);
    exit();
}

// Check permission
if ($role !== 'admin' && $booking['user_id'] != $user_id) {
    echo json_encode(['success' => false, 'message' => 'You do not have permission to cancel this booking']);
    exit();
}

// Check if booking can be cancelled
if (!in_array($booking['booking_status'], ['pending', 'confirmed'])) {
    echo json_encode(['success' => false, 'message' => 'This booking cannot be cancelled']);
    exit();
}

if ($booking['checkin_date'] < date('Y-m-d')) {
    echo json_encode(['success' => false, 'message' => 'Cannot cancel past or ongoing bookings']);
    exit();
}

// Cancel booking
$result = $bookingManager->updateBookingStatus($booking_id, 'cancelled');

if ($result) {
    // Update room status back to available
    $roomManager->updateRoomStatus($booking['room_id'], 'available');
    echo json_encode(['success' => true, 'message' => 'Booking cancelled successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to cancel booking']);
}
?>