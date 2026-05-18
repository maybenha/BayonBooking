<?php
// ==========================================
// CUSTOMER MY BOOKINGS PAGE
// File: my_bookings.php
// ==========================================
?>

<?php
session_start();
require_once 'config/database.php';
require_once 'classes/BookingManager.php';
require_once 'classes/RoomManager.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Redirect admin to dashboard instead
if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    header("Location: admin/dashboard.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();
$bookingManager = new BookingManager($db);
$roomManager = new RoomManager($db);

// Get user's bookings
$user_id = $_SESSION['user_id'];
$userBookings = $bookingManager->getBookingsByUser($user_id);
?>

<?php include 'includes/header.php'; ?>

<style>
    .page-header {
        background: linear-gradient(135deg, #543414 0%, #8B5E3C 100%);
        padding: 80px 0 50px;
        color: white;
        text-align: center;
        margin-top: 70px;
    }
    
    .page-header h1 {
        font-size: 2.5rem;
        margin-bottom: 10px;
    }
    
    .booking-card {
        background: white;
        border-radius: 15px;
        overflow: hidden;
        box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        margin-bottom: 25px;
        transition: transform 0.3s ease;
    }
    
    .booking-card:hover {
        transform: translateY(-5px);
    }
    
    .booking-header {
        background: #f8f9fa;
        padding: 15px 20px;
        border-bottom: 1px solid #e0e0e0;
        display: flex;
        justify-content: space-between;
        flex-wrap: wrap;
    }
    
    .booking-id {
        font-weight: bold;
        color: #543414;
    }
    
    .booking-id span {
        font-size: 1.1rem;
    }
    
    .booking-date {
        color: #666;
        font-size: 0.9rem;
    }
    
    .booking-body {
        padding: 20px;
    }
    
    .room-info {
        margin-bottom: 15px;
    }
    
    .room-name {
        font-size: 1.2rem;
        font-weight: bold;
        color: #543414;
        margin-bottom: 10px;
    }
    
    .booking-details {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
        gap: 10px;
        margin: 15px 0;
    }
    
    .detail-item {
        text-align: center;
    }
    
    .detail-label {
        font-size: 0.8rem;
        color: #666;
        margin-bottom: 5px;
    }
    
    .detail-value {
        font-weight: bold;
        color: #333;
    }
    
    .price-amount {
        font-size: 1.3rem;
        font-weight: bold;
        color: #543414;
    }
    
    .booking-status {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 0.85rem;
        font-weight: 500;
    }
    
    .status-pending {
        background: #fff3cd;
        color: #856404;
    }
    
    .status-confirmed {
        background: #d4edda;
        color: #155724;
    }
    
    .status-cancelled {
        background: #f8d7da;
        color: #721c24;
    }
    
    .payment-paid {
        background: #d4edda;
        color: #155724;
    }
    
    .payment-unpaid {
        background: #fff3cd;
        color: #856404;
    }
    
    .btn-cancel-booking {
        background: #dc3545;
        color: white;
        border: none;
        padding: 8px 20px;
        border-radius: 8px;
        font-size: 14px;
        transition: 0.3s ease;
    }
    
    .btn-cancel-booking:hover {
        background: #c82333;
        transform: translateY(-2px);
    }
    
    .btn-gold {
        background: #543414;
        color: white;
        border: none;
        padding: 8px 20px;
        border-radius: 8px;
        transition: 0.3s ease;
    }
    
    .btn-gold:hover {
        background: #3a2610;
        transform: translateY(-2px);
    }
    
    .empty-bookings {
        text-align: center;
        padding: 80px 20px;
        background: white;
        border-radius: 15px;
        margin: 40px 0;
    }
    
    .empty-bookings i {
        font-size: 80px;
        color: #ccc;
        margin-bottom: 20px;
    }
    
    .empty-bookings h3 {
        color: #543414;
        margin-bottom: 10px;
    }
    
    .modal-details-table {
        width: 100%;
    }
    
    .modal-details-table td {
        padding: 10px;
        border-bottom: 1px solid #eee;
    }
    
    .modal-details-table td:first-child {
        font-weight: bold;
        width: 40%;
        background: #f8f9fa;
    }
    
    @media (max-width: 768px) {
        .page-header h1 {
            font-size: 1.8rem;
        }
        
        .booking-details {
            grid-template-columns: 1fr;
        }
    }
</style>

<!-- Page Header -->
<section class="page-header">
    <div class="container">
        <h1><i class="fas fa-calendar-alt"></i> ការកក់បន្ទប់របស់ខ្ញុំ</h1>
        <p>មើលប្រវត្តិការកក់បន្ទប់ និងស្ថានភាពនៃការកក់របស់អ្នក</p>
    </div>
</section>

<div class="container mb-5" style="padding-top: 40px;">
    <?php if(count($userBookings) > 0): ?>
        <div class="row">
            <?php foreach($userBookings as $booking): 
                $nights = (strtotime($booking['checkout_date']) - strtotime($booking['checkin_date'])) / (60 * 60 * 24);
            ?>
                <div class="col-md-6 col-lg-4">
                    <div class="booking-card">
                        <div class="booking-header">
                            <div class="booking-id">
                                <i class="fas fa-receipt"></i> លេខកក់: <span>#<?php echo str_pad($booking['booking_id'], 6, '0', STR_PAD_LEFT); ?></span>
                            </div>
                            <div class="booking-date">
                                <i class="far fa-calendar-alt"></i> <?php echo date('d/m/Y', strtotime($booking['booking_at'])); ?>
                            </div>
                        </div>
                        <div class="booking-body">
                            <div class="room-info">
                                <div class="room-name">
                                    <i class="fas fa-bed"></i> <?php echo htmlspecialchars($booking['type_name']); ?> - បន្ទប់លេខ<?php echo htmlspecialchars($booking['room_number']); ?>
                                </div>
                                <div class="booking-details">
                                    <div class="detail-item">
                                        <div class="detail-label"><i class="fas fa-calendar-check"></i> ថ្ងៃចូល</div>
                                        <div class="detail-value"><?php echo date('d M Y', strtotime($booking['checkin_date'])); ?></div>
                                    </div>
                                    <div class="detail-item">
                                        <div class="detail-label"><i class="fas fa-calendar-times"></i> ថ្ងៃចេញ</div>
                                        <div class="detail-value"><?php echo date('d M Y', strtotime($booking['checkout_date'])); ?></div>
                                    </div>
                                    <div class="detail-item">
                                        <div class="detail-label"><i class="fas fa-moon"></i> ចំនួនយប់</div>
                                        <div class="detail-value"><?php echo $nights; ?> យប់</div>
                                    </div>
                                    <div class="detail-item">
                                        <div class="detail-label"><i class="fas fa-users"></i> ភ្ញៀវ</div>
                                        <div class="detail-value"><?php echo $booking['capacity']; ?> នាក់</div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row mt-3 align-items-center">
                                <div class="col-6">
                                    <div class="detail-label">តម្លៃសរុប</div>
                                    <div class="price-amount">$<?php echo number_format($booking['total_payment'], 2); ?></div>
                                </div>
                                <div class="col-6 text-end">
                                    <span class="booking-status status-<?php echo $booking['booking_status']; ?>">
                                        <i class="fas <?php echo $booking['booking_status'] == 'confirmed' ? 'fa-check-circle' : ($booking['booking_status'] == 'pending' ? 'fa-clock' : 'fa-times-circle'); ?>"></i>
                                        <?php 
                                        if($booking['booking_status'] == 'confirmed') echo 'បានបញ្ជាក់';
                                        elseif($booking['booking_status'] == 'pending') echo 'កំពុងរង់ចាំ';
                                        else echo 'បានបោះបង់';
                                        ?>
                                    </span>
                                </div>
                            </div>
                            
                            <div class="mt-3 text-end">
                                <?php if($booking['booking_status'] == 'pending'): ?>
                                    <button class="btn-cancel-booking" onclick="cancelBooking(<?php echo $booking['booking_id']; ?>)">
                                        <i class="fas fa-times"></i> បោះបង់ការកក់
                                    </button>
                                <?php endif; ?>
                                <button class="btn-gold ms-2" onclick="viewBookingDetails(<?php echo $booking['booking_id']; ?>)">
                                    <i class="fas fa-eye"></i> ព័ត៌មានលម្អិត
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="empty-bookings">
            <i class="fas fa-calendar-times"></i>
            <h3>អ្នកមិនទាន់មានការកក់បន្ទប់នៅឡើយទេ</h3>
            <p>សូមចាប់ផ្ដើមកក់បន្ទប់ដំបូងរបស់អ្នកឥឡូវនេះ</p>
            <a href="index.php#rooms" class="btn-gold" style="display: inline-block; margin-top: 20px;">
                <i class="fas fa-search"></i> ស្វែងរកបន្ទប់
            </a>
        </div>
    <?php endif; ?>
</div>

<!-- Booking Details Modal -->
<div class="modal fade" id="bookingDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="background: #543414; color: white;">
                <h5 class="modal-title"><i class="fas fa-receipt"></i> ព័ត៌មានលម្អិតការកក់</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="bookingDetailsContent">
                <!-- Dynamic content -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">បិទ</button>
                <button type="button" class="btn btn-gold" id="printInvoiceBtn">បោះពុម្ពវិក្កយបត្រ</button>
            </div>
        </div>
    </div>
</div>

<script>
    let currentBookingData = null;
    
    // View booking details via AJAX
    function viewBookingDetails(bookingId) {
        Swal.fire({
            title: 'កំពុងផ្ទុក...',
            text: 'សូមមេត្តារង់ចាំ',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });
        
        fetch(`get_booking_details.php?id=${bookingId}`)
            .then(response => response.json())
            .then(data => {
                Swal.close();
                if(data.success) {
                    currentBookingData = data.booking;
                    const booking = data.booking;
                    const nights = booking.nights;
                    const pricePerNight = (booking.total_payment / nights).toFixed(2);
                    
                    document.getElementById('bookingDetailsContent').innerHTML = `
                        <div class="row">
                            <div class="col-md-6">
                                <h6 style="color: #543414; margin-bottom: 15px;"><i class="fas fa-hotel"></i> ព័ត៌មានបន្ទប់</h6>
                                <table class="modal-details-table">
                                    <tr><td>ប្រភេទបន្ទប់：</td><td><strong>${escapeHtml(booking.type_name)}</strong></td></tr>
                                    <tr><td>លេខបន្ទប់：</td><td><strong>${escapeHtml(booking.room_number)}</strong></td></tr>
                                    <tr><td>សមត្ថភាព：</td><td>${booking.capacity} នាក់</td></tr>
                                    <tr><td>តម្លៃក្នុងមួយយប់：</td><td>$${pricePerNight}</td></tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <h6 style="color: #543414; margin-bottom: 15px;"><i class="fas fa-calendar-alt"></i> ព័ត៌មានកាលបរិច្ឆេទ</h6>
                                <table class="modal-details-table">
                                    <tr><td>ថ្ងៃចូលស្នាក់៖</td><td><strong>${new Date(booking.checkin_date).toLocaleDateString('km-KH')}</strong></td></tr>
                                    <tr><td>ថ្ងៃចេញ៖</td><td><strong>${new Date(booking.checkout_date).toLocaleDateString('km-KH')}</strong></td></tr>
                                    <tr><td>ចំនួនយប់៖</td><td>${nights} យប់</td></tr>
                                    <tr><td>ចំនួនភ្ញៀវ៖</td><td>${booking.guests || 1} នាក់</td></tr>
                                </table>
                            </div>
                        </div>
                        <hr>
                        <div class="row">
                            <div class="col-md-6">
                                <h6 style="color: #543414; margin-bottom: 15px;"><i class="fas fa-user"></i> ព័ត៌មានភ្ញៀវ</h6>
                                <table class="modal-details-table">
                                    <tr><td>ឈ្មោះភ្ញៀវ៖</td><td><strong>${escapeHtml(booking.user_name)}</strong></td></tr>
                                    <tr><td>អ៊ីមែល៖</td><td>${escapeHtml(booking.email || 'មិនមាន')}</td></tr>
                                    <tr><td>លេខទូរស័ព្ទ៖</td><td>${escapeHtml(booking.phone_number || 'មិនមាន')}</td></tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <h6 style="color: #543414; margin-bottom: 15px;"><i class="fas fa-credit-card"></i> ព័ត៌មានការបង់ប្រាក់</h6>
                                <table class="modal-details-table">
                                    <tr><td>តម្លៃសរុប៖</td><td><strong class="text-danger">$${parseFloat(booking.total_payment).toFixed(2)}</strong></td></tr>
                                    <tr><td>ស្ថានភាពកក់៖</td><td>
                                        <span class="badge ${booking.booking_status === 'confirmed' ? 'bg-success' : (booking.booking_status === 'pending' ? 'bg-warning' : 'bg-danger')}">
                                            ${booking.booking_status === 'confirmed' ? 'បានបញ្ជាក់' : (booking.booking_status === 'pending' ? 'កំពុងរង់ចាំ' : 'បានបោះបង់')}
                                        </span>
                                    </td></tr>
                                    <tr><td>ស្ថានភាពបង់ប្រាក់៖</td><td>
                                        <span class="badge ${booking.payment_status === 'paid' ? 'bg-success' : 'bg-warning'}">
                                            ${booking.payment_status === 'paid' ? 'បានបង់ប្រាក់' : 'មិនទាន់បង់'}
                                        </span>
                                    </td></tr>
                                    <tr><td>ថ្ងៃកក់៖</td><td>${new Date(booking.booking_at).toLocaleString('km-KH')}</td></tr>
                                </table>
                            </div>
                        </div>
                        <hr>
                        <div class="row">
                            <div class="col-12">
                                <h6 style="color: #543414; margin-bottom: 15px;"><i class="fas fa-file-invoice"></i> សង្ខេបការបង់ប្រាក់</h6>
                                <table class="table table-bordered">
                                    <thead style="background: #f8f9fa;">
                                        <tr><th>ការពិពណ៌នា</th><th class="text-end">ចំនួនទឹកប្រាក់</th></tr>
                                    </thead>
                                    <tbody>
                                        <tr><td>តម្លៃបន្ទប់ក្នុងមួយយប់ (${nights} យប់ × $${pricePerNight})</td><td class="text-end">$${parseFloat(booking.total_payment).toFixed(2)}</td></tr>
                                        <tr style="background: #f8f9fa; font-weight: bold;">
                                            <td>សរុបត្រូវបង់</td>
                                            <td class="text-end text-danger">$${parseFloat(booking.total_payment).toFixed(2)}</td>
                                        </tr>
                                    </tbody>
                                </table>
                                <div class="alert alert-info mt-3">
                                    <i class="fas fa-info-circle"></i> សូមចំណាំ៖ ការបង់ប្រាក់ត្រូវបង់នៅពេទឹកក់ដល់សណ្ឋាគារ (សាច់ប្រាក់ ឬ កាតឥណទាន)
                                </div>
                            </div>
                        </div>
                    `;
                    new bootstrap.Modal(document.getElementById('bookingDetailsModal')).show();
                } else {
                    Swal.fire('កំហុស!', data.message || 'មិនអាចទាញយកព័ត៌មានការកក់បាន', 'error');
                }
            })
            .catch(error => {
                Swal.close();
                console.error('Error:', error);
                Swal.fire('កំហុស!', 'មានបញ្ហាក្នុងការទាញយកព័ត៌មាន', 'error');
            });
    }
    
    // Cancel booking with SweetAlert confirmation
    function cancelBooking(bookingId) {
        Swal.fire({
            title: 'បញ្ជាក់ការបោះបង់ការកក់',
            text: 'តើអ្នកពិតជាចង់បោះបង់ការកក់នេះមែនទេ?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'បាទ/ចាស, បោះបង់!',
            cancelButtonText: 'មិនបោះបង់'
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    title: 'កំពុងដំណើរការ...',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });
                
                fetch('cancel_booking.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'id=' + bookingId
                })
                .then(response => response.json())
                .then(data => {
                    if(data.success) {
                        Swal.fire('ជោគជ័យ!', 'ការកក់ត្រូវបានបោះបង់ដោយជោគជ័យ', 'success').then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire('កំហុស!', data.message || 'មិនអាចបោះបង់ការកក់បាន', 'error');
                    }
                })
                .catch(error => {
                    Swal.fire('កំហុស!', 'មានបញ្ហាក្នុងការបោះបង់ការកក់', 'error');
                });
            }
        });
    }
    
    // Print invoice
    document.getElementById('printInvoiceBtn')?.addEventListener('click', function() {
        if (currentBookingData) {
            const booking = currentBookingData;
            const nights = booking.nights;
            const pricePerNight = (booking.total_payment / nights).toFixed(2);
            
            const printWindow = window.open('', '_blank');
            printWindow.document.write(`
                <html>
                <head>
                    <title>Invoice - Booking #${String(booking.booking_id).padStart(6, '0')}</title>
                    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
                    <style>
                        body { padding: 50px; font-family: 'Siemreap', Arial, sans-serif; }
                        .invoice-header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #543414; padding-bottom: 20px; }
                        .hotel-name { color: #543414; font-size: 28px; font-weight: bold; }
                        .invoice-title { font-size: 24px; margin-top: 20px; }
                        .invoice-footer { text-align: center; margin-top: 50px; border-top: 1px solid #ddd; padding-top: 20px; }
                        table { width: 100%; margin-top: 20px; }
                        th, td { padding: 10px; border: 1px solid #ddd; }
                        th { background: #f8f9fa; }
                    </style>
                </head>
                <body>
                    <div class="invoice-header">
                        <div class="hotel-name">សណ្ឋាគារបាយ័ន</div>
                        <p>សៀមរាប, កម្ពុជា | ទូរស័ព្ទ: 012 345 678 | អ៊ីមែល: info@bayonbooking.com</p>
                        <div class="invoice-title">វិក្កយបត្រកក់បន្ទប់</div>
                    </div>
                    
                    <div class="row">
                        <div class="col-6">
                            <strong>ព័ត៌មានកក់:</strong><br>
                            លេខកក់: #${String(booking.booking_id).padStart(6, '0')}<br>
                            ថ្ងៃកក់: ${new Date(booking.booking_at).toLocaleString('km-KH')}
                        </div>
                        <div class="col-6 text-end">
                            <strong>ស្ថានភាព:</strong><br>
                            <span class="badge ${booking.booking_status === 'confirmed' ? 'bg-success' : 'bg-warning'}">
                                ${booking.booking_status === 'confirmed' ? 'បានបញ្ជាក់' : (booking.booking_status === 'pending' ? 'កំពុងរង់ចាំ' : 'បានបោះបង់')}
                            </span>
                        </div>
                    </div>
                    
                    <h5 class="mt-4">ព័ត៌មានភ្ញៀវ</h5>
                    <table class="table table-bordered">
                        <tr><th>ឈ្មោះភ្ញៀវ</th><td>${booking.user_name}</td></tr>
                        <tr><th>អ៊ីមែល</th><td>${booking.email || 'មិនមាន'}</td></tr>
                        <tr><th>លេខទូរស័ព្ទ</th><td>${booking.phone_number || 'មិនមាន'}</td></tr>
                    </table>
                    
                    <h5 class="mt-4">ព័ត៌មានបន្ទប់</h5>
                    <table class="table table-bordered">
                        <tr><th>ប្រភេទបន្ទប់</th><td>${booking.type_name}</td></tr>
                        <tr><th>លេខបន្ទប់</th><td>${booking.room_number}</td></tr>
                        <tr><th>ថ្ងៃចូល</th><td>${new Date(booking.checkin_date).toLocaleDateString('km-KH')}</td></tr>
                        <tr><th>ថ្ងៃចេញ</th><td>${new Date(booking.checkout_date).toLocaleDateString('km-KH')}</td></tr>
                        <tr><th>ចំនួនយប់</th><td>${nights} យប់</td></tr>
                        <tr><th>ចំនួនភ្ញៀវ</th><td>${booking.guests || 1} នាក់</td></tr>
                    </table>
                    
                    <h5 class="mt-4">សេចក្តីសង្ខេបនៃការបង់ប្រាក់</h5>
                    <table class="table table-bordered">
                        <tr><th>តម្លៃក្នុងមួយយប់</th><td class="text-end">$${pricePerNight}</td></tr>
                        <tr><th>ចំនួនយប់</th><td class="text-end">${nights} យប់</td></tr>
                        <tr style="background: #f0f0f0;"><th>តម្លៃសរុប</th><td class="text-end"><strong>$${parseFloat(booking.total_payment).toFixed(2)}</strong></td></tr>
                        <tr><th>ស្ថានភាពបង់ប្រាក់</th><td class="text-end">${booking.payment_status === 'paid' ? 'បានបង់ប្រាក់រួច' : 'មិនទាន់បង់ប្រាក់'}</td></tr>
                    </table>
                    
                    <div class="invoice-footer">
                        <p>សូមអរគុណសម្រាប់ការជឿទុកចិត្តលើសណ្ឋាគារបាយ័ន!</p>
                        <p>សូមរីករាយជាមួយការស្នាក់នៅរបស់លោកអ្នក!</p>
                    </div>
                </body>
                </html>
            `);
            printWindow.document.close();
            printWindow.print();
        } else {
            const printContent = document.getElementById('bookingDetailsContent').innerHTML;
            const win = window.open('', '_blank');
            win.document.write(`
                <html>
                <head>
                    <title>Booking Invoice</title>
                    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
                    <style>
                        body { padding: 50px; font-family: Arial, sans-serif; }
                        .invoice-header { text-align: center; margin-bottom: 30px; }
                        .invoice-footer { text-align: center; margin-top: 50px; }
                    </style>
                </head>
                <body>
                    <div class="invoice-header">
                        <h2>BayonBooking Hotel</h2>
                        <p>Siem Reap, Cambodia</p>
                        <h4>Booking Invoice</h4>
                    </div>
                    ${printContent}
                    <div class="invoice-footer">
                        <p>Thank you for choosing BayonBooking!</p>
                    </div>
                </body>
                </html>
            `);
            win.document.close();
            win.print();
        }
    });
    
    function escapeHtml(text) {
        if(!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
</script>

<?php include 'includes/footer.php'; ?>