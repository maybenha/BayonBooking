<?php
// ==========================================
// BOOKING CONFIRMATION PAGE
// File: booking_confirmation.php
// ==========================================

session_start();
require_once 'config/database.php';
require_once 'classes/BookingManager.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if (!isset($_SESSION['last_booking'])) {
    header("Location: my_bookings.php");
    exit();
}

$booking_data = $_SESSION['last_booking'];
?>

<?php include 'includes/header.php'; ?>

<style>
    .confirmation-header {
        background: var(--accent);
        padding: 80px 0 50px;
        color: white;
        text-align: center;
        margin-top: 70px;
    }
    .confirmation-card {
        background: var(--secondary-bg);
        border-radius: 16px;
        padding: 40px;
        box-shadow: var(--shadow);
        margin: -30px 0 40px;
    }
    .success-icon {
        width: 80px;
        height: 80px;
        background: #28a745;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 20px;
    }
    .success-icon i { font-size: 40px; color: white; }
    .booking-reference {
        background: var(--primary-bg);
        padding: 15px;
        border-radius: 10px;
        text-align: center;
        margin: 20px 0;
    }
    .booking-reference p {
        font-size: 24px;
        font-weight: bold;
        letter-spacing: 2px;
        margin: 0;
        color: var(--accent);
    }
    .details-table {
        width: 100%;
        margin: 20px 0;
    }
    .details-table td {
        padding: 12px;
        border-bottom: 1px solid #eee;
    }
    .details-table td:first-child {
        font-weight: bold;
        width: 40%;
        background: var(--primary-bg);
    }
    .btn-print {
        background: #6c757d;
        color: white;
        border: none;
        padding: 10px 30px;
        border-radius: 8px;
        margin: 10px;
    }
    .btn-my-bookings {
        background: var(--accent);
        color: white;
        border: none;
        padding: 10px 30px;
        border-radius: 8px;
        margin: 10px;
    }
    @media print {
        .no-print { display: none; }
        .confirmation-header { margin-top: 0; }
    }
</style>

<section class="confirmation-header">
    <div class="container">
        <h1><i class="fas fa-check-circle"></i> ការកក់បានជោគជ័យ</h1>
        <p>សូមអរគុណសម្រាប់ការកក់បន្ទប់ជាមួយពួកយើង</p>
    </div>
</section>

<div class="container">
    <div class="confirmation-card">
        <div class="success-icon">
            <i class="fas fa-check"></i>
        </div>
        <h3 class="text-center" style="color: var(--accent);">ការកក់របស់អ្នកត្រូវបានបញ្ជាក់!</h3>
        <p class="text-center">សូមរក្សាទុកព័ត៌មាននេះសម្រាប់ការប្រើប្រាស់នាពេលអនាគត</p>
        
        <div class="booking-reference">
            <h4>លេខសម្គាល់ការកក់របស់អ្នក</h4>
            <p>#<?php echo str_pad($booking_data['booking_id'], 6, '0', STR_PAD_LEFT); ?></p>
        </div>
        
        <h4 style="color: var(--accent); margin-top: 30px;">ព័ត៌មានលម្អិតការកក់</h4>
        <table class="details-table">
            <tr><td><i class="fas fa-hotel"></i> ប្រភេទបន្ទប់</td><td><?php echo htmlspecialchars($booking_data['room_name']); ?></td></tr>
            <tr><td><i class="fas fa-door-open"></i> លេខបន្ទប់</td><td><?php echo htmlspecialchars($booking_data['room_number']); ?></td></tr>
            <tr><td><i class="fas fa-calendar-check"></i> ថ្ងៃចូល</td><td><?php echo date('l, d F Y', strtotime($booking_data['checkin_date'])); ?></td></tr>
            <tr><td><i class="fas fa-calendar-times"></i> ថ្ងៃចេញ</td><td><?php echo date('l, d F Y', strtotime($booking_data['checkout_date'])); ?></td></tr>
            <tr><td><i class="fas fa-moon"></i> ចំនួនយប់</td><td><?php echo $booking_data['nights']; ?> យប់</td></tr>
            <tr><td><i class="fas fa-users"></i> ចំនួនភ្ញៀវ</td><td><?php echo $booking_data['guests']; ?> នាក់</td></tr>
            <tr><td><i class="fas fa-dollar-sign"></i> តម្លៃក្នុងមួយយប់</td><td>$<?php echo number_format($booking_data['price_per_night'], 2); ?></td></tr>
            <tr style="background: var(--primary-bg); font-weight: bold;">
                <td><i class="fas fa-receipt"></i> តម្លៃសរុប</td>
                <td style="color: #dc3545; font-size: 20px;">$<?php echo number_format($booking_data['total_payment'], 2); ?></td>
            </tr>
            <?php if (!empty($booking_data['special_requests'])): ?>
            <tr><td><i class="fas fa-comment"></i> សំណើពិសេស</td><td><?php echo htmlspecialchars($booking_data['special_requests']); ?></td></tr>
            <?php endif; ?>
        </table>
        
        <div class="alert alert-info mt-3">
            <i class="fas fa-info-circle"></i> 
            <strong>សូមចំណាំ៖</strong> សូមបង្ហាញលេខសម្គាល់ការកក់ និងអត្តសញ្ញាណប័ណ្ណនៅពេទឹកដល់សណ្ឋាគារ។ ការបង់ប្រាក់អាចបង់នៅពេទឹក់ដល់សណ្ឋាគារ។
        </div>
        
        <div class="alert alert-warning mt-2">
            <i class="fas fa-clock"></i>
            <strong>ពេលវេលា Check-in/Check-out:</strong><br>
            Check-in: ចាប់ពីម៉ោង 2:00 PM<br>
            Check-out: មុនម៉ោង 12:00 PM
        </div>
        
        <div class="text-center no-print">
            <button onclick="window.print()" class="btn-print"><i class="fas fa-print"></i> បោះពុម្ព</button>
            <a href="my_bookings.php" class="btn-my-bookings"><i class="fas fa-calendar-alt"></i> មើលការកក់របស់ខ្ញុំ</a>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>