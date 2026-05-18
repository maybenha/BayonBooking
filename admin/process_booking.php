<?php
// ==========================================
// PROCESS BOOKING - HANDLE ALL BOOKING ACTIONS
// File: admin/process_booking.php
// ==========================================

session_start();
header('Content-Type: application/json');

require_once '../config/database.php';
require_once '../classes/BookingManager.php';
require_once '../classes/RoomManager.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

$database = new Database();
$db = $database->getConnection();
$bookingManager = new BookingManager($db);
$roomManager = new RoomManager($db);

$action = isset($_POST['action']) ? $_POST['action'] : '';

switch($action) {
    case 'confirm_booking':
        $booking_id = $_POST['booking_id'] ?? '';
        
        // Get booking details
        $booking = $bookingManager->getBookingById($booking_id);
        
        if ($booking) {
            // Update booking status
            $result = $bookingManager->updateBookingStatus($booking_id, 'confirmed');
            
            if ($result) {
                // Update room status to booked
                $roomManager->updateRoomStatus($booking['room_id'], 'booked');
                echo json_encode(['success' => true, 'message' => 'បញ្ជាក់ការកក់ដោយជោគជ័យ']);
            } else {
                echo json_encode(['success' => false, 'message' => 'មានបញ្ហាក្នុងការបញ្ជាក់ការកក់']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'មិនឃើញការកក់នេះទេ']);
        }
        break;
        
    case 'cancel_booking':
        $booking_id = $_POST['booking_id'] ?? '';
        
        // Get booking details
        $booking = $bookingManager->getBookingById($booking_id);
        
        if ($booking) {
            $result = $bookingManager->updateBookingStatus($booking_id, 'cancelled');
            
            if ($result) {
                // Update room status back to available if it was confirmed
                if ($booking['booking_status'] === 'confirmed') {
                    $roomManager->updateRoomStatus($booking['room_id'], 'available');
                }
                echo json_encode(['success' => true, 'message' => 'បោះបង់ការកក់ដោយជោគជ័យ']);
            } else {
                echo json_encode(['success' => false, 'message' => 'មានបញ្ហាក្នុងការបោះបង់ការកក់']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'មិនឃើញការកក់នេះទេ']);
        }
        break;
        
    case 'update_payment':
        $booking_id = $_POST['booking_id'] ?? '';
        $payment_status = $_POST['payment_status'] ?? '';
        
        $result = $bookingManager->updatePaymentStatus($booking_id, $payment_status);
        
        if ($result) {
            echo json_encode(['success' => true, 'message' => 'កំណត់ស្ថានភាពបង់ប្រាក់ដោយជោគជ័យ']);
        } else {
            echo json_encode(['success' => false, 'message' => 'មានបញ្ហាក្នុងការកំណត់ស្ថានភាពបង់ប្រាក់']);
        }
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}
?>