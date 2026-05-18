<?php
// ==========================================
// PROCESS ROOM - HANDLE ALL ROOM ACTIONS
// File: admin/process_room.php
// ==========================================

session_start();
header('Content-Type: application/json');

require_once '../config/database.php';
require_once '../classes/RoomManager.php';

// Error reporting for debugging (remove in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

$database = new Database();
$db = $database->getConnection();
$roomManager = new RoomManager($db);

$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';

// Also check POST data if action not in REQUEST
if (empty($action) && isset($_POST['action'])) {
    $action = $_POST['action'];
}

// Handle different actions
switch($action) {
    case 'add_room':
        addRoom($roomManager);
        break;
        
    case 'update_room':
        updateRoom($roomManager);
        break;
        
    case 'update_status':
        updateRoomStatus($roomManager);
        break;
        
    case 'delete_room':
        deleteRoom($roomManager);
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action: ' . $action]);
        break;
}

function addRoom($roomManager) {
    // Get form data
    $room_number = trim($_POST['room_number'] ?? '');
    $room_type_id = $_POST['room_type_id'] ?? '';
    $price_per_night = $_POST['price_per_night'] ?? '';
    $capacity = $_POST['capacity'] ?? '';
    $equipments = trim($_POST['equipments'] ?? '');
    $room_description = trim($_POST['room_description'] ?? '');
    
    // Validate inputs
    $errors = [];
    if (empty($room_number)) {
        $errors[] = "សូមបញ្ចូលលេខបន្ទប់";
    }
    if (empty($room_type_id)) {
        $errors[] = "សូមជ្រើសរើសប្រភេទបន្ទប់";
    }
    if (empty($price_per_night) || $price_per_night <= 0) {
        $errors[] = "សូមបញ្ចូលតម្លៃត្រឹមត្រូវ";
    }
    if (empty($capacity) || $capacity <= 0) {
        $errors[] = "សូមបញ្ចូលសមត្ថភាពបន្ទប់";
    }
    
    if (!empty($errors)) {
        echo json_encode(['success' => false, 'message' => implode(', ', $errors)]);
        return;
    }
    
    // Handle image upload
    $room_image = '';
    if (isset($_FILES['room_image']) && $_FILES['room_image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../uploads/rooms/';
        
        // Create directory if not exists
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        // Check file size (max 5MB)
        if ($_FILES['room_image']['size'] > 5 * 1024 * 1024) {
            echo json_encode(['success' => false, 'message' => 'រូបភាពមានទំហំធំពេក (អតិបរមា 5MB)']);
            return;
        }
        
        // Check file type
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/jpg'];
        $file_type = mime_content_type($_FILES['room_image']['tmp_name']);
        if (!in_array($file_type, $allowed_types)) {
            echo json_encode(['success' => false, 'message' => 'សូមប្រើរូបភាពប្រភេទ JPG, JPEG, PNG ឬ GIF']);
            return;
        }
        
        $file_extension = strtolower(pathinfo($_FILES['room_image']['name'], PATHINFO_EXTENSION));
        $file_name = 'room_' . time() . '_' . uniqid() . '.' . $file_extension;
        $upload_path = $upload_dir . $file_name;
        
        if (move_uploaded_file($_FILES['room_image']['tmp_name'], $upload_path)) {
            $room_image = 'uploads/rooms/' . $file_name;
        } else {
            echo json_encode(['success' => false, 'message' => 'មិនអាចផ្ទុករូបភាពបានទេ']);
            return;
        }
    }
    
    // Add room to database
    $result = $roomManager->addRoom($room_number, $room_type_id, $price_per_night, $capacity, $equipments, $room_description, $room_image);
    
    if ($result) {
        echo json_encode(['success' => true, 'message' => 'បន្ថែមបន្ទប់ដោយជោគជ័យ']);
    } else {
        echo json_encode(['success' => false, 'message' => 'មានបញ្ហាក្នុងការបន្ថែមបន្ទប់។ សូមពិនិត្យលេខបន្ទប់មិនឲ្យមានដដែល']);
    }
}

function updateRoom($roomManager) {
    $room_id = $_POST['room_id'] ?? '';
    $room_number = trim($_POST['room_number'] ?? '');
    $room_type_id = $_POST['room_type_id'] ?? '';
    $price_per_night = $_POST['price_per_night'] ?? '';
    $capacity = $_POST['capacity'] ?? '';
    $equipments = trim($_POST['equipments'] ?? '');
    $room_description = trim($_POST['room_description'] ?? '');
    
    // Validate inputs
    $errors = [];
    if (empty($room_id)) {
        $errors[] = "Invalid room ID";
    }
    if (empty($room_number)) {
        $errors[] = "សូមបញ្ចូលលេខបន្ទប់";
    }
    if (empty($room_type_id)) {
        $errors[] = "សូមជ្រើសរើសប្រភេទបន្ទប់";
    }
    if (empty($price_per_night) || $price_per_night <= 0) {
        $errors[] = "សូមបញ្ចូលតម្លៃត្រឹមត្រូវ";
    }
    if (empty($capacity) || $capacity <= 0) {
        $errors[] = "សូមបញ្ចូលសមត្ថភាពបន្ទប់";
    }
    
    if (!empty($errors)) {
        echo json_encode(['success' => false, 'message' => implode(', ', $errors)]);
        return;
    }
    
    // Get existing room data
    $existingRoom = $roomManager->getRoomById($room_id);
    if (!$existingRoom) {
        echo json_encode(['success' => false, 'message' => 'រកមិនឃើញបន្ទប់នេះទេ']);
        return;
    }
    
    $room_image = $existingRoom['room_image'] ?? '';
    
    // Handle image upload
    if (isset($_FILES['room_image']) && $_FILES['room_image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../uploads/rooms/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_extension = strtolower(pathinfo($_FILES['room_image']['name'], PATHINFO_EXTENSION));
        $file_name = 'room_' . time() . '_' . uniqid() . '.' . $file_extension;
        $upload_path = $upload_dir . $file_name;
        
        if (move_uploaded_file($_FILES['room_image']['tmp_name'], $upload_path)) {
            // Delete old image if exists
            if (!empty($existingRoom['room_image']) && file_exists('../' . $existingRoom['room_image'])) {
                unlink('../' . $existingRoom['room_image']);
            }
            $room_image = 'uploads/rooms/' . $file_name;
        }
    }
    
    $result = $roomManager->updateRoom($room_id, $room_number, $room_type_id, $price_per_night, $capacity, $equipments, $room_description, $room_image);
    
    if ($result) {
        echo json_encode(['success' => true, 'message' => 'កែប្រែព័ត៌មានបន្ទប់ដោយជោគជ័យ']);
    } else {
        echo json_encode(['success' => false, 'message' => 'មានបញ្ហាក្នុងការកែប្រែព័ត៌មានបន្ទប់']);
    }
}

function updateRoomStatus($roomManager) {
    $room_id = $_POST['room_id'] ?? '';
    $room_status = $_POST['room_status'] ?? '';
    
    if (empty($room_id) || empty($room_status)) {
        echo json_encode(['success' => false, 'message' => 'Invalid data']);
        return;
    }
    
    $result = $roomManager->updateRoomStatus($room_id, $room_status);
    
    if ($result) {
        echo json_encode(['success' => true, 'message' => 'ប្តូរស្ថានភាពបន្ទប់ដោយជោគជ័យ']);
    } else {
        echo json_encode(['success' => false, 'message' => 'មានបញ្ហាក្នុងការប្តូរស្ថានភាពបន្ទប់']);
    }
}

function deleteRoom($roomManager) {
    $room_id = $_POST['room_id'] ?? '';
    
    if (empty($room_id)) {
        echo json_encode(['success' => false, 'message' => 'Invalid room ID']);
        return;
    }
    
    // Get room image to delete
    $room = $roomManager->getRoomById($room_id);
    if (!$room) {
        echo json_encode(['success' => false, 'message' => 'រកមិនឃើញបន្ទប់នេះទេ']);
        return;
    }
    
    $result = $roomManager->deleteRoom($room_id);
    
    if ($result) {
        // Delete image file if exists
        if (!empty($room['room_image']) && file_exists('../' . $room['room_image'])) {
            unlink('../' . $room['room_image']);
        }
        echo json_encode(['success' => true, 'message' => 'លុបបន្ទប់ដោយជោគជ័យ']);
    } else {
        echo json_encode(['success' => false, 'message' => 'មានបញ្ហាក្នុងការលុបបន្ទប់']);
    }
}
?>