<?php
// ==========================================
// GET USER PROFILE (AJAX)
// File: get_user_profile.php
// ==========================================
?>

<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

$database = new Database();
$db = $database->getConnection();

$query = "SELECT user_id, user_name, email, phone_number, role, profile_image, account_status, created_at 
          FROM user WHERE user_id = :user_id";
$stmt = $db->prepare($query);
$stmt->bindParam(':user_id', $_SESSION['user_id']);
$stmt->execute();

$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user) {
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'user' => $user]);
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'User not found']);
}
?>