<?php
// ==========================================
// UPDATED ROOM MANAGER CLASS
// File: classes/RoomManager.php
// Add these methods to the existing class
// ==========================================

class RoomManager {
    private $conn;
    private $table_rooms = "rooms";
    private $table_room_types = "room_types";

    public function __construct($db) {
        $this->conn = $db;
    }

    // Get all rooms with room type information
    public function getAllRooms() {
        $query = "SELECT r.*, rt.type_name 
                  FROM " . $this->table_rooms . " r
                  JOIN " . $this->table_room_types . " rt ON r.room_type_id = rt.room_types_id
                  ORDER BY r.room_id DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get available rooms with filters (for landing page)
    public function getAvailableRoomsWithFilters($room_type = '', $max_price = null, $capacity = null, $check_in = null, $check_out = null, $bed_type = '') {
        $query = "SELECT r.*, rt.type_name 
                  FROM " . $this->table_rooms . " r
                  JOIN " . $this->table_room_types . " rt ON r.room_type_id = rt.room_types_id
                  WHERE r.room_status = 'available'";
        
        $params = [];
        
        // Filter by room type
        if (!empty($room_type)) {
            $query .= " AND rt.type_name = :room_type";
            $params[':room_type'] = $room_type;
        }
        
        // Filter by max price
        if ($max_price !== null && $max_price > 0) {
            $query .= " AND r.price_per_night <= :max_price";
            $params[':max_price'] = $max_price;
        }
        
        // Filter by capacity
        if ($capacity !== null && $capacity > 0) {
            $query .= " AND r.capacity >= :capacity";
            $params[':capacity'] = $capacity;
        }
        
        // Filter by bed type (search in equipments text)
        if (!empty($bed_type)) {
            $query .= " AND LOWER(r.equipments) LIKE :bed_type";
            $params[':bed_type'] = '%' . strtolower($bed_type) . '%';
        }
        
        $query .= " ORDER BY r.price_per_night ASC";
        
        $stmt = $this->conn->prepare($query);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        
        $availableRooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // If check-in/out dates are provided, filter out booked rooms
        if ($check_in && $check_out && count($availableRooms) > 0) {
            $filteredRooms = [];
            foreach ($availableRooms as $room) {
                if ($this->isRoomAvailableForDates($room['room_id'], $check_in, $check_out)) {
                    $filteredRooms[] = $room;
                }
            }
            return $filteredRooms;
        }
        
        return $availableRooms;
    }
    
    // Check if room is available for specific dates
    public function isRoomAvailableForDates($room_id, $check_in, $check_out) {
        $query = "SELECT COUNT(*) as count 
                  FROM bookings 
                  WHERE room_id = :room_id 
                  AND booking_status IN ('pending', 'confirmed')
                  AND ((checkin_date BETWEEN :check_in AND :check_out) 
                       OR (checkout_date BETWEEN :check_in AND :check_out)
                       OR (:check_in BETWEEN checkin_date AND checkout_date)
                       OR (:check_out BETWEEN checkin_date AND checkout_date))";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':room_id', $room_id);
        $stmt->bindParam(':check_in', $check_in);
        $stmt->bindParam(':check_out', $check_out);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'] == 0;
    }

    // Get room by ID
    public function getRoomById($room_id) {
        $query = "SELECT * FROM " . $this->table_rooms . " WHERE room_id = :room_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':room_id', $room_id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Add new room
    public function addRoom($room_number, $room_type_id, $price_per_night, $capacity, $equipments, $room_description, $room_image = '') {
        $query = "INSERT INTO " . $this->table_rooms . " 
                  (room_number, room_type_id, price_per_night, capacity, equipments, room_description, room_image, room_status) 
                  VALUES (:room_number, :room_type_id, :price_per_night, :capacity, :equipments, :room_description, :room_image, 'available')";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':room_number', $room_number);
        $stmt->bindParam(':room_type_id', $room_type_id);
        $stmt->bindParam(':price_per_night', $price_per_night);
        $stmt->bindParam(':capacity', $capacity);
        $stmt->bindParam(':equipments', $equipments);
        $stmt->bindParam(':room_description', $room_description);
        $stmt->bindParam(':room_image', $room_image);
        return $stmt->execute();
    }

    // Update room
    public function updateRoom($room_id, $room_number, $room_type_id, $price_per_night, $capacity, $equipments, $room_description, $room_image = '') {
        $query = "UPDATE " . $this->table_rooms . " 
                  SET room_number = :room_number, 
                      room_type_id = :room_type_id, 
                      price_per_night = :price_per_night, 
                      capacity = :capacity, 
                      equipments = :equipments, 
                      room_description = :room_description";
        
        if (!empty($room_image)) {
            $query .= ", room_image = :room_image";
        }
        
        $query .= " WHERE room_id = :room_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':room_id', $room_id);
        $stmt->bindParam(':room_number', $room_number);
        $stmt->bindParam(':room_type_id', $room_type_id);
        $stmt->bindParam(':price_per_night', $price_per_night);
        $stmt->bindParam(':capacity', $capacity);
        $stmt->bindParam(':equipments', $equipments);
        $stmt->bindParam(':room_description', $room_description);
        
        if (!empty($room_image)) {
            $stmt->bindParam(':room_image', $room_image);
        }
        
        return $stmt->execute();
    }

    // Update room status
    public function updateRoomStatus($room_id, $status) {
        $query = "UPDATE " . $this->table_rooms . " SET room_status = :status WHERE room_id = :room_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':room_id', $room_id);
        return $stmt->execute();
    }

    // Delete room
    public function deleteRoom($room_id) {
        $query = "DELETE FROM " . $this->table_rooms . " WHERE room_id = :room_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':room_id', $room_id);
        return $stmt->execute();
    }

    // Get all room types
    public function getRoomTypes() {
        $query = "SELECT * FROM " . $this->table_room_types . " ORDER BY type_name";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get available rooms (basic)
    public function getAvailableRooms() {
        $query = "SELECT r.*, rt.type_name 
                  FROM " . $this->table_rooms . " r
                  JOIN " . $this->table_room_types . " rt ON r.room_type_id = rt.room_types_id
                  WHERE r.room_status = 'available'
                  ORDER BY r.price_per_night ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>