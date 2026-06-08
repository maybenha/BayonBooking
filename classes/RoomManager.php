<?php
// ==========================================
// ROOM MANAGER CLASS - Compatible with bopha_hotel
// File: classes/RoomManager.php
// ==========================================

class RoomManager {
    private $conn;
    private $table_rooms = "rooms";
    private $table_room_types = "room_types";

    public function __construct($db) {
        $this->conn = $db;
    }

    // Get floor based on room number
    public function getFloorFromRoomNumber($room_number) {
        $num = intval(preg_replace('/[^0-9]/', '', $room_number));
        if ($num <= 10) return 'A';
        if ($num <= 20) return 'B';
        if ($num <= 30) return 'C';
        if ($num <= 40) return 'D';
        return 'E';
    }

    // Get all rooms with room type information
    public function getAllRooms() {
        try {
            $query = "SELECT r.*, rt.type_name, rt.description as type_description 
                      FROM " . $this->table_rooms . " r
                      INNER JOIN " . $this->table_room_types . " rt ON r.room_type_id = rt.room_types_id
                      ORDER BY CAST(r.room_number AS UNSIGNED) ASC";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            $rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($rooms as &$room) {
                $room['floor'] = $this->getFloorFromRoomNumber($room['room_number']);
            }
            return $rooms;
        } catch(PDOException $e) {
            error_log("Error in getAllRooms: " . $e->getMessage());
            return [];
        }
    }

    // Get all rooms with pagination
    public function getAllRoomsPaginated($limit = 10, $offset = 0) {
        try {
            $query = "SELECT r.*, rt.type_name, rt.description as type_description 
                      FROM " . $this->table_rooms . " r
                      INNER JOIN " . $this->table_room_types . " rt ON r.room_type_id = rt.room_types_id
                      ORDER BY CAST(r.room_number AS UNSIGNED) ASC
                      LIMIT :limit OFFSET :offset";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            $rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($rooms as &$room) {
                $room['floor'] = $this->getFloorFromRoomNumber($room['room_number']);
            }
            return $rooms;
        } catch(PDOException $e) {
            error_log("Error in getAllRoomsPaginated: " . $e->getMessage());
            return [];
        }
    }

    // Get total rooms count
    public function getTotalRoomsCount() {
        try {
            $query = "SELECT COUNT(*) as total FROM " . $this->table_rooms;
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['total'];
        } catch(PDOException $e) {
            error_log("Error in getTotalRoomsCount: " . $e->getMessage());
            return 0;
        }
    }

    // Get room by ID with full details
    public function getRoomById($room_id) {
        try {
            $query = "SELECT r.*, rt.type_name, rt.description as type_description 
                      FROM " . $this->table_rooms . " r
                      INNER JOIN " . $this->table_room_types . " rt ON r.room_type_id = rt.room_types_id
                      WHERE r.room_id = :room_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':room_id', $room_id, PDO::PARAM_INT);
            $stmt->execute();
            $room = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($room) {
                $room['floor'] = $this->getFloorFromRoomNumber($room['room_number']);
            }
            return $room;
        } catch(PDOException $e) {
            error_log("Error in getRoomById: " . $e->getMessage());
            return null;
        }
    }

    // Get room by room number
    public function getRoomByNumber($room_number) {
        try {
            $query = "SELECT r.*, rt.type_name 
                      FROM " . $this->table_rooms . " r
                      INNER JOIN " . $this->table_room_types . " rt ON r.room_type_id = rt.room_types_id
                      WHERE r.room_number = :room_number";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':room_number', $room_number);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            error_log("Error in getRoomByNumber: " . $e->getMessage());
            return null;
        }
    }

    // Check if room number exists
    public function roomNumberExists($room_number, $exclude_room_id = null) {
        try {
            $query = "SELECT COUNT(*) as count FROM " . $this->table_rooms . " WHERE room_number = :room_number";
            if ($exclude_room_id) {
                $query .= " AND room_id != :room_id";
            }
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':room_number', $room_number);
            if ($exclude_room_id) {
                $stmt->bindParam(':room_id', $exclude_room_id, PDO::PARAM_INT);
            }
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['count'] > 0;
        } catch(PDOException $e) {
            error_log("Error in roomNumberExists: " . $e->getMessage());
            return false;
        }
    }

    // Add new room
    public function addRoom($room_number, $room_type_id, $price_per_night, $capacity, $equipments, $room_description, $room_image = '') {
        try {
            if ($this->roomNumberExists($room_number)) {
                return ['success' => false, 'message' => 'Room number already exists', 'error_code' => 'DUPLICATE_ROOM_NUMBER'];
            }
            
            $query = "INSERT INTO " . $this->table_rooms . " 
                      (room_number, room_type_id, price_per_night, capacity, equipments, room_description, room_image, room_status) 
                      VALUES (:room_number, :room_type_id, :price_per_night, :capacity, :equipments, :room_description, :room_image, 'available')";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':room_number', $room_number);
            $stmt->bindParam(':room_type_id', $room_type_id, PDO::PARAM_INT);
            $stmt->bindParam(':price_per_night', $price_per_night);
            $stmt->bindParam(':capacity', $capacity, PDO::PARAM_INT);
            $stmt->bindParam(':equipments', $equipments);
            $stmt->bindParam(':room_description', $room_description);
            $stmt->bindParam(':room_image', $room_image);
            
            if ($stmt->execute()) {
                return ['success' => true, 'message' => 'Room added successfully', 'room_id' => $this->conn->lastInsertId()];
            }
            return ['success' => false, 'message' => 'Failed to add room'];
        } catch(PDOException $e) {
            if ($e->errorInfo[1] == 1062) {
                return ['success' => false, 'message' => 'Room number already exists', 'error_code' => 'DUPLICATE_ROOM_NUMBER'];
            }
            error_log("Error in addRoom: " . $e->getMessage());
            return ['success' => false, 'message' => 'Database error occurred'];
        }
    }

    // Update room
    public function updateRoom($room_id, $room_number, $room_type_id, $price_per_night, $capacity, $equipments, $room_description, $room_image = '') {
        try {
            if ($this->roomNumberExists($room_number, $room_id)) {
                return ['success' => false, 'message' => 'Room number already exists'];
            }
            
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
            $stmt->bindParam(':room_id', $room_id, PDO::PARAM_INT);
            $stmt->bindParam(':room_number', $room_number);
            $stmt->bindParam(':room_type_id', $room_type_id, PDO::PARAM_INT);
            $stmt->bindParam(':price_per_night', $price_per_night);
            $stmt->bindParam(':capacity', $capacity, PDO::PARAM_INT);
            $stmt->bindParam(':equipments', $equipments);
            $stmt->bindParam(':room_description', $room_description);
            
            if (!empty($room_image)) {
                $stmt->bindParam(':room_image', $room_image);
            }
            
            if ($stmt->execute()) {
                return ['success' => true, 'message' => 'Room updated successfully'];
            }
            return ['success' => false, 'message' => 'Failed to update room'];
        } catch(PDOException $e) {
            error_log("Error in updateRoom: " . $e->getMessage());
            return ['success' => false, 'message' => 'Database error occurred'];
        }
    }

    // Update room status
    public function updateRoomStatus($room_id, $status) {
        try {
            $allowed_status = ['available', 'booked', 'maintenance'];
            if (!in_array($status, $allowed_status)) {
                return ['success' => false, 'message' => 'Invalid status'];
            }
            
            $query = "UPDATE " . $this->table_rooms . " SET room_status = :status WHERE room_id = :room_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':status', $status);
            $stmt->bindParam(':room_id', $room_id, PDO::PARAM_INT);
            
            if ($stmt->execute()) {
                return ['success' => true, 'message' => 'Room status updated successfully'];
            }
            return ['success' => false, 'message' => 'Failed to update room status'];
        } catch(PDOException $e) {
            error_log("Error in updateRoomStatus: " . $e->getMessage());
            return ['success' => false, 'message' => 'Database error occurred'];
        }
    }

    // Delete room with safety check
    public function deleteRoom($room_id) {
        try {
            if ($this->hasFutureBookings($room_id)) {
                return ['success' => false, 'message' => 'Cannot delete room with existing or future bookings'];
            }
            
            // Get room image to delete
            $room = $this->getRoomById($room_id);
            
            $query = "DELETE FROM " . $this->table_rooms . " WHERE room_id = :room_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':room_id', $room_id, PDO::PARAM_INT);
            
            if ($stmt->execute()) {
                // Delete image file if exists
                if ($room && !empty($room['room_image']) && file_exists('../' . $room['room_image'])) {
                    unlink('../' . $room['room_image']);
                }
                return ['success' => true, 'message' => 'Room deleted successfully'];
            }
            return ['success' => false, 'message' => 'Failed to delete room'];
        } catch(PDOException $e) {
            error_log("Error in deleteRoom: " . $e->getMessage());
            return ['success' => false, 'message' => 'Database error occurred'];
        }
    }

    // Check if room has future bookings
    private function hasFutureBookings($room_id) {
        try {
            $query = "SELECT COUNT(*) as count 
                      FROM bookings 
                      WHERE room_id = :room_id 
                      AND checkout_date >= CURDATE()
                      AND booking_status IN ('pending', 'confirmed')";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':room_id', $room_id, PDO::PARAM_INT);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['count'] > 0;
        } catch(PDOException $e) {
            error_log("Error in hasFutureBookings: " . $e->getMessage());
            return true; // Assume it has bookings to be safe
        }
    }

    // Get all room types
    public function getRoomTypes() {
        try {
            $query = "SELECT * FROM " . $this->table_room_types . " ORDER BY type_name";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            error_log("Error in getRoomTypes: " . $e->getMessage());
            return [];
        }
    }

    // Get room type by ID
    public function getRoomTypeById($type_id) {
        try {
            $query = "SELECT * FROM " . $this->table_room_types . " WHERE room_types_id = :type_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':type_id', $type_id, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            error_log("Error in getRoomTypeById: " . $e->getMessage());
            return null;
        }
    }

    // Get available rooms with filters
    public function getAvailableRoomsWithFilters($room_type = '', $max_price = null, $capacity = null, $check_in = null, $check_out = null, $bed_type = '') {
        try {
            $query = "SELECT r.*, rt.type_name, rt.description as type_description 
                      FROM " . $this->table_rooms . " r
                      INNER JOIN " . $this->table_room_types . " rt ON r.room_type_id = rt.room_types_id
                      WHERE r.room_status = 'available'";
            
            $params = [];
            
            if (!empty($room_type)) {
                $query .= " AND rt.type_name = :room_type";
                $params[':room_type'] = $room_type;
            }
            
            if ($max_price !== null && $max_price > 0) {
                $query .= " AND r.price_per_night <= :max_price";
                $params[':max_price'] = $max_price;
            }
            
            if ($capacity !== null && $capacity > 0) {
                $query .= " AND r.capacity >= :capacity";
                $params[':capacity'] = $capacity;
            }
            
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
            
            foreach ($availableRooms as &$room) {
                $room['floor'] = $this->getFloorFromRoomNumber($room['room_number']);
            }
            
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
        } catch(PDOException $e) {
            error_log("Error in getAvailableRoomsWithFilters: " . $e->getMessage());
            return [];
        }
    }

    // Check if room is available for specific dates
    public function isRoomAvailableForDates($room_id, $check_in, $check_out) {
        try {
            $query = "SELECT COUNT(*) as count 
                      FROM bookings 
                      WHERE room_id = :room_id 
                      AND booking_status IN ('pending', 'confirmed')
                      AND ((checkin_date BETWEEN :check_in AND :check_out) 
                           OR (checkout_date BETWEEN :check_in AND :check_out)
                           OR (:check_in BETWEEN checkin_date AND checkout_date)
                           OR (:check_out BETWEEN checkin_date AND checkout_date))";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':room_id', $room_id, PDO::PARAM_INT);
            $stmt->bindParam(':check_in', $check_in);
            $stmt->bindParam(':check_out', $check_out);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['count'] == 0;
        } catch(PDOException $e) {
            error_log("Error in isRoomAvailableForDates: " . $e->getMessage());
            return false;
        }
    }

    // Get available rooms (basic)
    public function getAvailableRooms() {
        try {
            $query = "SELECT r.*, rt.type_name 
                      FROM " . $this->table_rooms . " r
                      INNER JOIN " . $this->table_room_types . " rt ON r.room_type_id = rt.room_types_id
                      WHERE r.room_status = 'available'
                      ORDER BY r.price_per_night ASC";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            $rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($rooms as &$room) {
                $room['floor'] = $this->getFloorFromRoomNumber($room['room_number']);
            }
            return $rooms;
        } catch(PDOException $e) {
            error_log("Error in getAvailableRooms: " . $e->getMessage());
            return [];
        }
    }

    // Calculate total price with deposit
    public function calculateTotalPrice($room_id, $checkin_date, $checkout_date) {
        try {
            $room = $this->getRoomById($room_id);
            if (!$room) {
                return ['success' => false, 'message' => 'Room not found'];
            }
            
            $checkin = new DateTime($checkin_date);
            $checkout = new DateTime($checkout_date);
            $nights = $checkin->diff($checkout)->days;
            
            if ($nights <= 0) {
                return ['success' => false, 'message' => 'Invalid date range'];
            }
            
            $total = $room['price_per_night'] * $nights;
            $deposit = $total * 0.30;
            $remaining = $total - $deposit;
            
            return [
                'success' => true, 
                'total' => $total, 
                'nights' => $nights,
                'deposit' => $deposit,
                'remaining' => $remaining,
                'price_per_night' => $room['price_per_night']
            ];
        } catch(Exception $e) {
            error_log("Error in calculateTotalPrice: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error calculating price'];
        }
    }

    // Get room statistics
    public function getRoomStats() {
        try {
            $query = "SELECT 
                        COUNT(*) as total_rooms,
                        SUM(CASE WHEN room_status = 'available' THEN 1 ELSE 0 END) as available_rooms,
                        SUM(CASE WHEN room_status = 'booked' THEN 1 ELSE 0 END) as booked_rooms,
                        SUM(CASE WHEN room_status = 'maintenance' THEN 1 ELSE 0 END) as maintenance_rooms,
                        COALESCE(AVG(price_per_night), 0) as avg_price
                      FROM " . $this->table_rooms;
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            error_log("Error in getRoomStats: " . $e->getMessage());
            return null;
        }
    }

    // Search rooms
    public function searchRooms($keyword) {
        try {
            $query = "SELECT r.*, rt.type_name 
                      FROM " . $this->table_rooms . " r
                      INNER JOIN " . $this->table_room_types . " rt ON r.room_type_id = rt.room_types_id
                      WHERE r.room_number LIKE :keyword 
                      OR rt.type_name LIKE :keyword
                      OR r.equipments LIKE :keyword
                      OR r.room_description LIKE :keyword
                      ORDER BY CAST(r.room_number AS UNSIGNED) ASC";
            $stmt = $this->conn->prepare($query);
            $searchTerm = "%{$keyword}%";
            $stmt->bindParam(':keyword', $searchTerm);
            $stmt->execute();
            $rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($rooms as &$room) {
                $room['floor'] = $this->getFloorFromRoomNumber($room['room_number']);
            }
            return $rooms;
        } catch(PDOException $e) {
            error_log("Error in searchRooms: " . $e->getMessage());
            return [];
        }
    }

    // Get room types count for dashboard
    public function getRoomTypesCount() {
        try {
            $query = "SELECT rt.type_name, COUNT(r.room_id) as room_count
                      FROM " . $this->table_room_types . " rt
                      LEFT JOIN " . $this->table_rooms . " r ON rt.room_types_id = r.room_type_id
                      GROUP BY rt.room_types_id";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            error_log("Error in getRoomTypesCount: " . $e->getMessage());
            return [];
        }
    }
}
?>