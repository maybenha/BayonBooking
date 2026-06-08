<?php
// ==========================================
// GET BOOKING DETAILS API
// File: get_booking_details.php
// ==========================================

session_start();
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login first']);
    exit();
}

require_once 'config/database.php';
require_once 'classes/BookingManager.php';

if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'Booking ID is required']);
    exit();
}

$booking_id = (int)$_GET['id'];
$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'] ?? 'customer';

$database = new Database();
$db = $database->getConnection();
$bookingManager = new BookingManager($db);

$booking = $bookingManager->getBookingById($booking_id);

if (!$booking) {
    echo json_encode(['success' => false, 'message' => 'Booking not found']);
    exit();
}

// Check if user has permission to view this booking
if ($role !== 'admin' && $booking['user_id'] != $user_id) {
    echo json_encode(['success' => false, 'message' => 'You do not have permission to view this booking']);
    exit();
}

// Remove sensitive data if needed
unset($booking['user_id']);

echo json_encode(['success' => true, 'booking' => $booking]);
