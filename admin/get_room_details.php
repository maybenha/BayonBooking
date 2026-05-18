<?php
// ==========================================
// GET ROOM DETAILS FOR AJAX
// File: admin/get_room_details.php
// ==========================================

session_start();
header('Content-Type: application/json');

require_once '../config/database.php';
require_once '../classes/RoomManager.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$database = new Database();
$db = $database->getConnection();
$roomManager = new RoomManager($db);

$room_id = isset($_GET['id']) ? $_GET['id'] : 0;

if ($room_id) {
    $room = $roomManager->getRoomById($room_id);
    if ($room) {
        // Get room type name
        $roomTypes = $roomManager->getRoomTypes();
        foreach ($roomTypes as $type) {
            if ($type['room_types_id'] == $room['room_type_id']) {
                $room['type_name'] = $type['type_name'];
                break;
            }
        }
        echo json_encode($room);
    } else {
        echo json_encode(['error' => 'Room not found']);
    }
} else {
    echo json_encode(['error' => 'Invalid room ID']);
}
?>