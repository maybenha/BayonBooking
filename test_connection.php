<?php
// ==========================================
// TEST DATABASE CONNECTION
// File: test_connection.php
// ==========================================

require_once 'config/database.php';
require_once 'classes/RoomManager.php';

echo "<h1>Database Connection Test</h1>";

$database = new Database();
$db = $database->getConnection();

if ($db) {
    echo "<p style='color:green'>✓ Database connected successfully!</p>";
    
    $roomManager = new RoomManager($db);
    
    // Test room types
    $roomTypes = $roomManager->getRoomTypes();
    echo "<h2>Room Types in Database:</h2>";
    if (count($roomTypes) > 0) {
        echo "<ul>";
        foreach ($roomTypes as $type) {
            echo "<li>ID: {$type['room_types_id']} - Name: {$type['type_name']}</li>";
        }
        echo "</ul>";
    } else {
        echo "<p style='color:red'>No room types found! Please run the database SQL script.</p>";
    }
    
    // Test rooms
    $rooms = $roomManager->getAllRooms();
    echo "<h2>Rooms in Database:</h2>";
    if (count($rooms) > 0) {
        echo "<ul>";
        foreach ($rooms as $room) {
            echo "<li>Room #{$room['room_number']} - Type: {$room['type_name']} - Status: {$room['room_status']} - Floor: {$room['floor']}</li>";
        }
        echo "</ul>";
    } else {
        echo "<p style='color:red'>No rooms found!</p>";
    }
    
    // Test available rooms
    $availableRooms = $roomManager->getAvailableRooms();
    echo "<h2>Available Rooms:</h2>";
    echo "<p>Total available: " . count($availableRooms) . "</p>";
    
} else {
    echo "<p style='color:red'>✗ Database connection failed! Please check your config/database.php settings.</p>";
}
?>