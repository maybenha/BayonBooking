<?php
// ==========================================
// 3. BOOKING MANAGER CLASS
// File: classes/BookingManager.php
// ==========================================
?>

<?php
class BookingManager {
    
    private $conn;
    private $table_bookings = "bookings";

    public function __construct($db) {
        $this->conn = $db;
    }

    // Get all bookings with user and room information
    public function getAllBookings() {
        $query = "SELECT b.*, u.user_name, u.email, u.phone_number, r.room_number, rt.type_name, r.capacity 
                  FROM " . $this->table_bookings . " b
                  JOIN user u ON b.user_id = u.user_id
                  JOIN rooms r ON b.room_id = r.room_id
                  JOIN room_types rt ON r.room_type_id = rt.room_types_id
                  ORDER BY b.booking_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get booking by ID
    public function getBookingById($booking_id) {
        $query = "SELECT b.*, u.user_name, u.email, u.phone_number, r.room_number, r.price_per_night, r.capacity, rt.type_name 
                  FROM " . $this->table_bookings . " b
                  JOIN user u ON b.user_id = u.user_id
                  JOIN rooms r ON b.room_id = r.room_id
                  JOIN room_types rt ON r.room_type_id = rt.room_types_id
                  WHERE b.booking_id = :booking_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':booking_id', $booking_id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Get full booking details with user verification
    public function getFullBookingDetails($booking_id, $user_id) {
        $query = "SELECT b.*, u.user_name, u.email, u.phone_number, r.room_number, r.price_per_night, r.capacity, rt.type_name 
                  FROM " . $this->table_bookings . " b
                  JOIN user u ON b.user_id = u.user_id
                  JOIN rooms r ON b.room_id = r.room_id
                  JOIN room_types rt ON r.room_type_id = rt.room_types_id
                  WHERE b.booking_id = :booking_id AND b.user_id = :user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':booking_id', $booking_id);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Get user information
    public function getUserInfo($user_id) {
        $query = "SELECT user_id, user_name, email, phone_number, role, profile_image, account_status, created_at 
                  FROM user WHERE user_id = :user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Get bookings by user ID
    public function getBookingsByUser($user_id) {
        $query = "SELECT b.*, r.room_number, rt.type_name, r.capacity 
                  FROM " . $this->table_bookings . " b
                  JOIN rooms r ON b.room_id = r.room_id
                  JOIN room_types rt ON r.room_type_id = rt.room_types_id
                  WHERE b.user_id = :user_id
                  ORDER BY b.booking_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get bookings by status
    public function getBookingsByStatus($status) {
        $query = "SELECT b.*, u.user_name, r.room_number 
                  FROM " . $this->table_bookings . " b
                  JOIN user u ON b.user_id = u.user_id
                  JOIN rooms r ON b.room_id = r.room_id
                  WHERE b.booking_status = :status
                  ORDER BY b.booking_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':status', $status);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Add new booking
    public function addBooking($user_id, $room_id, $checkin_date, $checkout_date, $total_payment) {
        $query = "INSERT INTO " . $this->table_bookings . " 
                  (user_id, room_id, checkin_date, checkout_date, total_payment, booking_status, payment_status) 
                  VALUES (:user_id, :room_id, :checkin_date, :checkout_date, :total_payment, 'pending', 'unpaid')";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':room_id', $room_id);
        $stmt->bindParam(':checkin_date', $checkin_date);
        $stmt->bindParam(':checkout_date', $checkout_date);
        $stmt->bindParam(':total_payment', $total_payment);
        return $stmt->execute();
    }

    // Update booking status
    public function updateBookingStatus($booking_id, $status) {
        $query = "UPDATE " . $this->table_bookings . " SET booking_status = :status WHERE booking_id = :booking_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':booking_id', $booking_id);
        return $stmt->execute();
    }

    // Update payment status
    public function updatePaymentStatus($booking_id, $payment_status) {
        $query = "UPDATE " . $this->table_bookings . " SET payment_status = :payment_status WHERE booking_id = :booking_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':payment_status', $payment_status);
        $stmt->bindParam(':booking_id', $booking_id);
        return $stmt->execute();
    }

    // Cancel booking
    public function cancelBooking($booking_id) {
        $query = "UPDATE " . $this->table_bookings . " SET booking_status = 'cancelled' WHERE booking_id = :booking_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':booking_id', $booking_id);
        return $stmt->execute();
    }

    // Get booking statistics
    public function getBookingStats() {
        $query = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN booking_status = 'pending' THEN 1 ELSE 0 END) as pending,
                    SUM(CASE WHEN booking_status = 'confirmed' THEN 1 ELSE 0 END) as confirmed,
                    SUM(CASE WHEN booking_status = 'cancelled' THEN 1 ELSE 0 END) as cancelled,
                    SUM(CASE WHEN payment_status = 'paid' THEN 1 ELSE 0 END) as paid
                  FROM " . $this->table_bookings;
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Check room availability for dates
    public function checkRoomAvailability($room_id, $checkin_date, $checkout_date) {
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
    }
}
?>