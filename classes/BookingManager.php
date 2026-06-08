<?php
// ==========================================
// ENHANCED BOOKING MANAGER CLASS
// File: classes/BookingManager.php
// ==========================================

class BookingManager {
    
    private $conn;
    private $table_bookings = "bookings";

    public function __construct($db) {
        $this->conn = $db;
    }

    // Get all bookings with pagination
    public function getAllBookings($limit = null, $offset = 0) {
        try {
            $query = "SELECT b.*, u.user_name, u.email, u.phone_number, 
                             r.room_number, rt.type_name, r.capacity, r.price_per_night,
                             CASE 
                                WHEN b.checkout_date < CURDATE() AND b.booking_status = 'confirmed' THEN 'completed'
                                WHEN b.checkout_date < CURDATE() AND b.booking_status = 'pending' THEN 'expired'
                                ELSE b.booking_status
                             END as display_status
                      FROM " . $this->table_bookings . " b
                      JOIN user u ON b.user_id = u.user_id
                      JOIN rooms r ON b.room_id = r.room_id
                      JOIN room_types rt ON r.room_type_id = rt.room_types_id
                      ORDER BY b.booking_at DESC";
            
            if ($limit) {
                $query .= " LIMIT :limit OFFSET :offset";
                $stmt = $this->conn->prepare($query);
                $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
                $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
            } else {
                $stmt = $this->conn->prepare($query);
            }
            
            $stmt->execute();
            $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Calculate nights for each booking
            foreach ($bookings as &$booking) {
                $checkin = new DateTime($booking['checkin_date']);
                $checkout = new DateTime($booking['checkout_date']);
                $booking['nights'] = $checkin->diff($checkout)->days;
            }
            
            return $bookings;
        } catch(PDOException $e) {
            error_log("Error in getAllBookings: " . $e->getMessage());
            return [];
        }
    }

    // Get total bookings count
    public function getTotalBookingsCount() {
        try {
            $query = "SELECT COUNT(*) as total FROM " . $this->table_bookings;
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['total'];
        } catch(PDOException $e) {
            error_log("Error in getTotalBookingsCount: " . $e->getMessage());
            return 0;
        }
    }

    // Get booking by ID with full details
    public function getBookingById($booking_id) {
        try {
            $query = "SELECT b.*, u.user_name, u.email, u.phone_number, 
                             r.room_number, r.price_per_night, r.capacity, rt.type_name 
                      FROM " . $this->table_bookings . " b
                      JOIN user u ON b.user_id = u.user_id
                      JOIN rooms r ON b.room_id = r.room_id
                      JOIN room_types rt ON r.room_type_id = rt.room_types_id
                      WHERE b.booking_id = :booking_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':booking_id', $booking_id);
            $stmt->execute();
            $booking = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($booking) {
                $checkin = new DateTime($booking['checkin_date']);
                $checkout = new DateTime($booking['checkout_date']);
                $booking['nights'] = $checkin->diff($checkout)->days;
            }
            
            return $booking;
        } catch(PDOException $e) {
            error_log("Error in getBookingById: " . $e->getMessage());
            return null;
        }
    }

    // Get bookings by user ID
    public function getBookingsByUser($user_id, $limit = null) {
        try {
            $query = "SELECT b.*, r.room_number, rt.type_name, r.capacity, r.price_per_night,
                             CASE 
                                WHEN b.checkout_date < CURDATE() AND b.booking_status = 'confirmed' THEN 'completed'
                                WHEN b.checkout_date < CURDATE() AND b.booking_status = 'pending' THEN 'expired'
                                ELSE b.booking_status
                             END as display_status
                      FROM " . $this->table_bookings . " b
                      JOIN rooms r ON b.room_id = r.room_id
                      JOIN room_types rt ON r.room_type_id = rt.room_types_id
                      WHERE b.user_id = :user_id
                      ORDER BY b.booking_at DESC";
            
            if ($limit) {
                $query .= " LIMIT :limit";
                $stmt = $this->conn->prepare($query);
                $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            } else {
                $stmt = $this->conn->prepare($query);
            }
            
            $stmt->bindParam(':user_id', $user_id);
            $stmt->execute();
            $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($bookings as &$booking) {
                $checkin = new DateTime($booking['checkin_date']);
                $checkout = new DateTime($booking['checkout_date']);
                $booking['nights'] = $checkin->diff($checkout)->days;
                $booking['deposit'] = $booking['total_payment'] * 0.30;
                $booking['remaining'] = $booking['total_payment'] - $booking['deposit'];
            }
            
            return $bookings;
        } catch(PDOException $e) {
            error_log("Error in getBookingsByUser: " . $e->getMessage());
            return [];
        }
    }

    // Get bookings by status
    public function getBookingsByStatus($status) {
        try {
            $query = "SELECT b.*, u.user_name, r.room_number, rt.type_name
                      FROM " . $this->table_bookings . " b
                      JOIN user u ON b.user_id = u.user_id
                      JOIN rooms r ON b.room_id = r.room_id
                      JOIN room_types rt ON r.room_type_id = rt.room_types_id
                      WHERE b.booking_status = :status
                      ORDER BY b.booking_at DESC";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':status', $status);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            error_log("Error in getBookingsByStatus: " . $e->getMessage());
            return [];
        }
    }

    // Add new booking with validation
    public function addBooking($user_id, $room_id, $checkin_date, $checkout_date, $total_payment, $guests = 1, $special_requests = '') {
        try {
            if ($checkin_date >= $checkout_date) {
                return ['success' => false, 'message' => 'ថ្ងៃចេញត្រូវតែក្រោយថ្ងៃចូល'];
            }
            
            if ($checkin_date < date('Y-m-d')) {
                return ['success' => false, 'message' => 'ថ្ងៃចូលមិនអាចនៅក្រោយថ្ងៃបច្ចុប្បន្នបានទេ'];
            }
            
            if (!$this->checkRoomAvailability($room_id, $checkin_date, $checkout_date)) {
                return ['success' => false, 'message' => 'បន្ទប់មិនទំនេរសម្រាប់កាលបរិច្ឆេទដែលបានជ្រើសរើសទេ'];
            }
            
            $query = "INSERT INTO " . $this->table_bookings . " 
                      (user_id, room_id, checkin_date, checkout_date, total_payment, guests, special_requests, booking_status, payment_status, booking_at) 
                      VALUES (:user_id, :room_id, :checkin_date, :checkout_date, :total_payment, :guests, :special_requests, 'pending', 'unpaid', NOW())";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->bindParam(':room_id', $room_id);
            $stmt->bindParam(':checkin_date', $checkin_date);
            $stmt->bindParam(':checkout_date', $checkout_date);
            $stmt->bindParam(':total_payment', $total_payment);
            $stmt->bindParam(':guests', $guests);
            $stmt->bindParam(':special_requests', $special_requests);
            
            if ($stmt->execute()) {
                $booking_id = $this->conn->lastInsertId();
                return ['success' => true, 'message' => 'កក់បន្ទប់ដោយជោគជ័យ', 'booking_id' => $booking_id];
            }
            return ['success' => false, 'message' => 'មិនអាចកក់បន្ទប់បានទេ'];
        } catch(PDOException $e) {
            error_log("Error in addBooking: " . $e->getMessage());
            return ['success' => false, 'message' => 'កំហុសក្នុងប្រព័ន្ធ'];
        }
    }

    // Update booking status
    public function updateBookingStatus($booking_id, $status) {
        try {
            $allowed_status = ['pending', 'confirmed', 'cancelled', 'completed'];
            if (!in_array($status, $allowed_status)) {
                return false;
            }
            
            $query = "UPDATE " . $this->table_bookings . " SET booking_status = :status WHERE booking_id = :booking_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':status', $status);
            $stmt->bindParam(':booking_id', $booking_id);
            
            return $stmt->execute();
        } catch(PDOException $e) {
            error_log("Error in updateBookingStatus: " . $e->getMessage());
            return false;
        }
    }

    // Update payment status
    public function updatePaymentStatus($booking_id, $payment_status) {
        try {
            $allowed_status = ['paid', 'unpaid', 'refunded'];
            if (!in_array($payment_status, $allowed_status)) {
                return false;
            }
            
            $query = "UPDATE " . $this->table_bookings . " SET payment_status = :payment_status WHERE booking_id = :booking_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':payment_status', $payment_status);
            $stmt->bindParam(':booking_id', $booking_id);
            
            if ($stmt->execute()) {
                if ($payment_status === 'paid') {
                    $update = "UPDATE " . $this->table_bookings . " SET payment_verified_at = NOW() WHERE booking_id = :booking_id";
                    $stmt2 = $this->conn->prepare($update);
                    $stmt2->bindParam(':booking_id', $booking_id);
                    $stmt2->execute();
                }
                return true;
            }
            return false;
        } catch(PDOException $e) {
            error_log("Error in updatePaymentStatus: " . $e->getMessage());
            return false;
        }
    }

    // Upload payment proof
    public function uploadPaymentProof($booking_id, $file_path) {
        try {
            $query = "UPDATE " . $this->table_bookings . " SET payment_proof = :payment_proof WHERE booking_id = :booking_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':payment_proof', $file_path);
            $stmt->bindParam(':booking_id', $booking_id);
            return $stmt->execute();
        } catch(PDOException $e) {
            error_log("Error in uploadPaymentProof: " . $e->getMessage());
            return false;
        }
    }

    // Check room availability
    public function checkRoomAvailability($room_id, $checkin_date, $checkout_date) {
        try {
            $query = "SELECT COUNT(*) as count 
                      FROM " . $this->table_bookings . " 
                      WHERE room_id = :room_id 
                      AND booking_status IN ('pending', 'confirmed')
                      AND ((checkin_date BETWEEN :checkin AND :checkout) 
                           OR (checkout_date BETWEEN :checkin AND :checkout)
                           OR (:checkin BETWEEN checkin_date AND checkout_date)
                           OR (:checkout BETWEEN checkin_date AND checkout_date))";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':room_id', $room_id);
            $stmt->bindParam(':checkin', $checkin_date);
            $stmt->bindParam(':checkout', $checkout_date);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['count'] == 0;
        } catch(PDOException $e) {
            error_log("Error in checkRoomAvailability: " . $e->getMessage());
            return false;
        }
    }

    // Get booking statistics
    public function getBookingStats() {
        try {
            $query = "SELECT 
                        COUNT(*) as total,
                        SUM(CASE WHEN booking_status = 'pending' THEN 1 ELSE 0 END) as pending,
                        SUM(CASE WHEN booking_status = 'confirmed' THEN 1 ELSE 0 END) as confirmed,
                        SUM(CASE WHEN booking_status = 'cancelled' THEN 1 ELSE 0 END) as cancelled,
                        SUM(CASE WHEN booking_status = 'completed' THEN 1 ELSE 0 END) as completed,
                        SUM(CASE WHEN payment_status = 'paid' THEN 1 ELSE 0 END) as paid,
                        SUM(CASE WHEN payment_status = 'unpaid' THEN 1 ELSE 0 END) as unpaid,
                        SUM(total_payment) as total_revenue
                      FROM " . $this->table_bookings . "
                      WHERE booking_status != 'cancelled'";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            $stats = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Monthly revenue
            $query2 = "SELECT 
                        DATE_FORMAT(booking_at, '%Y-%m') as month,
                        COUNT(*) as bookings,
                        SUM(total_payment) as revenue
                      FROM " . $this->table_bookings . "
                      WHERE booking_status != 'cancelled'
                      GROUP BY DATE_FORMAT(booking_at, '%Y-%m')
                      ORDER BY month DESC
                      LIMIT 6";
            $stmt2 = $this->conn->prepare($query2);
            $stmt2->execute();
            $stats['monthly_stats'] = $stmt2->fetchAll(PDO::FETCH_ASSOC);
            
            return $stats;
        } catch(PDOException $e) {
            error_log("Error in getBookingStats: " . $e->getMessage());
            return null;
        }
    }

    // Auto update completed bookings
    public function autoCompleteExpiredBookings() {
        try {
            $query = "UPDATE " . $this->table_bookings . " 
                      SET booking_status = 'completed' 
                      WHERE checkout_date < CURDATE() 
                      AND booking_status = 'confirmed'";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            
            // Also update room status back to available for completed bookings
            $query2 = "UPDATE rooms r
                       JOIN bookings b ON r.room_id = b.room_id
                       SET r.room_status = 'available'
                       WHERE b.checkout_date < CURDATE() 
                       AND b.booking_status = 'completed'
                       AND r.room_status = 'booked'";
            $stmt2 = $this->conn->prepare($query2);
            $stmt2->execute();
            
            return true;
        } catch(PDOException $e) {
            error_log("Error in autoCompleteExpiredBookings: " . $e->getMessage());
            return false;
        }
    }
}
?>