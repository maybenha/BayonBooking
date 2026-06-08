<?php
// ==========================================
// ENHANCED MY BOOKINGS PAGE
// File: my_bookings.php
// ==========================================

session_start();
require_once 'config/database.php';
require_once 'classes/BookingManager.php';
require_once 'classes/RoomManager.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Redirect admin to dashboard
if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    header("Location: admin/dashboard.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();
$bookingManager = new BookingManager($db);

// Get user's bookings
$user_id = $_SESSION['user_id'];
$userBookings = $bookingManager->getBookingsByUser($user_id);
?>

<?php include 'includes/header.php'; ?>

<style>
    :root {
        --primary-dark: #543414;
        --bg-light: #F1F0E7;
    }
    
    .page-header {
        background: linear-gradient(135deg, #543414 0%, #8B5E3C 100%);
        padding: 80px 0 50px;
        color: white;
        text-align: center;
        margin-top: 70px;
    }
    
    .booking-card {
        background: white;
        border-radius: 12px;
        overflow: hidden;
        border: 1px solid #e0e0e0;
        margin-bottom: 25px;
        transition: 0.3s ease;
    }
    
    .booking-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
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
        font-weight: 600;
        color: var(--primary-dark);
    }
    
    .booking-body {
        padding: 20px;
    }
    
    .room-name {
        font-size: 1.2rem;
        font-weight: 600;
        color: var(--primary-dark);
        margin-bottom: 15px;
    }
    
    .booking-details {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 15px;
        margin: 15px 0;
    }
    
    .detail-label {
        font-size: 0.8rem;
        color: #666;
    }
    
    .detail-value {
        font-weight: 500;
    }
    
    .price-amount {
        font-size: 1.3rem;
        font-weight: bold;
        color: var(--primary-dark);
    }
    
    .badge-status {
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 0.75rem;
    }
    
    .status-pending { background: #fff3cd; color: #856404; }
    .status-confirmed { background: #d4edda; color: #155724; }
    .status-cancelled { background: #f8d7da; color: #721c24; }
    .status-completed { background: #d1ecf1; color: #0c5460; }
    
    .btn-cancel {
        background: #dc3545;
        color: white;
        border: none;
        padding: 6px 15px;
        border-radius: 6px;
        font-size: 0.85rem;
    }
    
    .btn-view {
        background: var(--primary-dark);
        color: white;
        border: none;
        padding: 6px 15px;
        border-radius: 6px;
        font-size: 0.85rem;
    }
    
    .empty-bookings {
        text-align: center;
        padding: 60px;
        background: white;
        border-radius: 12px;
        border: 1px solid #e0e0e0;
    }
    .page-header p {
        font-size: 1.1rem;
        margin-top: 10px;
        color: #f0e9e0;
    }
</style>

<section class="page-header">
    <div class="container">
        <h1><i class="fas fa-calendar-alt"></i>ការកក់របស់ខ្ញុំ</h1>
        <p>មើលប្រវត្តិកក់របស់អ្នក និងតាមដានស្ថានភាពកក់</p>
    </div>
</section>

<div class="container mb-5" style="padding-top: 40px;">
    <?php if(count($userBookings) > 0): ?>
        <div class="row">
            <?php foreach($userBookings as $booking): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="booking-card">
                        <div class="booking-header">
                            <div class="booking-id">
                                <i class="fas fa-receipt"></i> #<?php echo str_pad($booking['booking_id'], 6, '0', STR_PAD_LEFT); ?>
                            </div>
                            <div class="booking-date">
                                <?php echo date('d M Y', strtotime($booking['booking_at'])); ?>
                            </div>
                        </div>
                        <div class="booking-body">
                            <div class="room-name">
                                <i class="fas fa-bed"></i> <?php echo htmlspecialchars($booking['type_name']); ?> - Room <?php echo htmlspecialchars($booking['room_number']); ?>
                            </div>
                            
                            <div class="booking-details">
                                <div>
                                    <div class="detail-label"><i class="fas fa-calendar-check"></i> Check-in</div>
                                    <div class="detail-value"><?php echo date('d M Y', strtotime($booking['checkin_date'])); ?></div>
                                </div>
                                <div>
                                    <div class="detail-label"><i class="fas fa-calendar-times"></i> Check-out</div>
                                    <div class="detail-value"><?php echo date('d M Y', strtotime($booking['checkout_date'])); ?></div>
                                </div>
                                <div>
                                    <div class="detail-label"><i class="fas fa-moon"></i> Nights</div>
                                    <div class="detail-value"><?php echo $booking['nights']; ?></div>
                                </div>
                                <div>
                                    <div class="detail-label"><i class="fas fa-users"></i> Guests</div>
                                    <div class="detail-value"><?php echo $booking['guests'] ?? 1; ?></div>
                                </div>
                            </div>
                            
                            <div class="d-flex justify-content-between align-items-center mt-3">
                                <div>
                                    <div class="detail-label">Total Price</div>
                                    <div class="price-amount">$<?php echo number_format($booking['total_payment'], 2); ?></div>
                                </div>
                                <div>
                                    <span class="badge-status status-<?php echo $booking['display_status'] ?? $booking['booking_status']; ?>">
                                        <?php 
                                        $status = $booking['display_status'] ?? $booking['booking_status'];
                                        echo $status == 'pending' ? 'Pending' : 
                                            ($status == 'confirmed' ? 'Confirmed' : 
                                            ($status == 'cancelled' ? 'Cancelled' : 'Completed'));
                                        ?>
                                    </span>
                                </div>
                            </div>
                            
                            <div class="mt-3 text-end">
                                <?php if(($booking['booking_status'] == 'pending' || $booking['booking_status'] == 'confirmed') && $booking['checkin_date'] > date('Y-m-d')): ?>
                                    <button class="btn-cancel me-2" onclick="cancelBooking(<?php echo $booking['booking_id']; ?>)">
                                        <i class="fas fa-times"></i> Cancel
                                    </button>
                                <?php endif; ?>
                                <button class="btn-view" onclick="viewBookingDetails(<?php echo $booking['booking_id']; ?>)">
                                    <i class="fas fa-eye"></i> Details
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="empty-bookings">
            <i class="fas fa-calendar-times fa-3x mb-3" style="color: #ccc;"></i>
            <h3>No Bookings Yet</h3>
            <p>You haven't made any room bookings yet.</p>
            <a href="index.php#rooms" class="btn-gold" style="display: inline-block; margin-top: 20px;">
                Browse Rooms
            </a>
        </div>
    <?php endif; ?>
</div>

<!-- Booking Details Modal -->
<div class="modal fade" id="bookingDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="background: var(--primary-dark); color: white;">
                <h5 class="modal-title"><i class="fas fa-receipt"></i> Booking Details</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="bookingDetailsContent"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-gold" id="printInvoice">Print Invoice</button>
            </div>
        </div>
    </div>
</div>

<script>
    let currentBookingData = null;
    
    function viewBookingDetails(bookingId) {
        Swal.fire({ title: 'Loading...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
        
        fetch(`get_booking_details.php?id=${bookingId}`)
            .then(response => response.json())
            .then(data => {
                Swal.close();
                if(data.success) {
                    currentBookingData = data.booking;
                    const booking = data.booking;
                    const nights = booking.nights;
                    const pricePerNight = (booking.total_payment / nights).toFixed(2);
                    const deposit = booking.total_payment * 0.30;
                    const remaining = booking.total_payment - deposit;
                    
                    document.getElementById('bookingDetailsContent').innerHTML = `
                        <div class="row">
                            <div class="col-md-6">
                                <h6>Room Information</h6>
                                <table class="table table-bordered">
                                    <tr><th>Room Number:</th><td>${booking.room_number}</td></tr>
                                    <tr><th>Room Type:</th><td>${booking.type_name}</td></tr>
                                    <tr><th>Capacity:</th><td>${booking.capacity} persons</td></tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <h6>Guest Information</h6>
                                <table class="table table-bordered">
                                    <tr><th>Name:</th><td>${booking.user_name}</td></tr>
                                    <tr><th>Email:</th><td>${booking.email || 'N/A'}</td></tr>
                                    <tr><th>Phone:</th><td>${booking.phone_number || 'N/A'}</td></tr>
                                </table>
                            </div>
                        </div>
                        <h6>Booking Details</h6>
                        <table class="table table-bordered">
                            <tr><th>Check-in Date:</th><td>${new Date(booking.checkin_date).toLocaleDateString()}</td></tr>
                            <tr><th>Check-out Date:</th><td>${new Date(booking.checkout_date).toLocaleDateString()}</td></tr>
                            <tr><th>Number of Nights:</th><td>${nights} nights</td></tr>
                            <tr><th>Number of Guests:</th><td>${booking.guests || 1}</td></tr>
                            <tr><th>Total Payment:</th><td><strong>$${parseFloat(booking.total_payment).toFixed(2)}</strong></td></tr>
                            <tr><th>Deposit (30%):</th><td>$${deposit.toFixed(2)}</td></tr>
                            <tr><th>Remaining at Check-in:</th><td>$${remaining.toFixed(2)}</td></tr>
                            <tr><th>Booking Status:</th><td>
                                <span class="badge-status status-${booking.booking_status}">
                                    ${booking.booking_status === 'pending' ? 'Pending' : 
                                      (booking.booking_status === 'confirmed' ? 'Confirmed' : 
                                      (booking.booking_status === 'cancelled' ? 'Cancelled' : 'Completed'))}
                                </span>
                            </td></tr>
                            <tr><th>Payment Status:</th><td>
                                <span class="badge-status ${booking.payment_status === 'paid' ? 'status-confirmed' : 'status-pending'}">
                                    ${booking.payment_status === 'paid' ? 'Paid' : 'Unpaid'}
                                </span>
                            </td></tr>
                            <tr><th>Booking Date:</th><td>${new Date(booking.booking_at).toLocaleString()}</td></tr>
                        </table>
                        ${booking.special_requests ? `<div class="alert alert-info"><strong>Special Requests:</strong><br>${booking.special_requests}</div>` : ''}
                    `;
                    new bootstrap.Modal(document.getElementById('bookingDetailsModal')).show();
                } else {
                    Swal.fire('Error!', data.message || 'Could not fetch booking details', 'error');
                }
            })
            .catch(error => {
                Swal.close();
                Swal.fire('Error!', 'Failed to load booking details', 'error');
            });
    }
    
    function cancelBooking(bookingId) {
        Swal.fire({
            title: 'Cancel Booking',
            text: 'Are you sure you want to cancel this booking?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            confirmButtonText: 'Yes, Cancel',
            cancelButtonText: 'No'
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({ title: 'Processing...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
                
                fetch('cancel_booking.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'id=' + bookingId
                })
                .then(response => response.json())
                .then(data => {
                    Swal.close();
                    if(data.success) {
                        Swal.fire('Success!', 'Booking cancelled successfully', 'success').then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire('Error!', data.message || 'Could not cancel booking', 'error');
                    }
                })
                .catch(error => {
                    Swal.close();
                    Swal.fire('Error!', 'Failed to cancel booking', 'error');
                });
            }
        });
    }
    
    document.getElementById('printInvoice')?.addEventListener('click', function() {
        if (currentBookingData) {
            const booking = currentBookingData;
            const nights = booking.nights;
            const pricePerNight = (booking.total_payment / nights).toFixed(2);
            const deposit = booking.total_payment * 0.30;
            
            const printWindow = window.open('', '_blank');
            printWindow.document.write(`
                <html>
                <head>
                    <title>Invoice - Booking #${booking.booking_id}</title>
                    <style>
                        body { padding: 50px; font-family: Arial, sans-serif; }
                        .invoice-header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #543414; }
                        .hotel-name { color: #543414; font-size: 28px; font-weight: bold; }
                        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
                        th, td { padding: 10px; border: 1px solid #ddd; text-align: left; }
                        th { background: #f5f5f5; }
                        .total { font-size: 18px; font-weight: bold; color: #543414; }
                        .footer { text-align: center; margin-top: 50px; padding-top: 20px; border-top: 1px solid #ddd; }
                    </style>
                </head>
                <body>
                    <div class="invoice-header">
                        <div class="hotel-name">Bayon Hotel</div>
                        <p>Siem Reap, Cambodia</p>
                        <h3>Booking Invoice</h3>
                    </div>
                    <p><strong>Invoice Date:</strong> ${new Date().toLocaleDateString()}</p>
                    <p><strong>Booking ID:</strong> #${booking.booking_id}</p>
                    
                    <h4>Guest Information</h4>
                    <table>
                        <tr><th>Name</th><td>${booking.user_name}</td></tr>
                        <tr><th>Email</th><td>${booking.email || 'N/A'}</td></tr>
                        <tr><th>Phone</th><td>${booking.phone_number || 'N/A'}NonNullable\x0c
                    </table>
                    
                    <h4>Room Information</h4>
                    <table>
                        <tr><th>Room Number</th><td>${booking.room_number}</td></tr>
                        <tr><th>Room Type</th><td>${booking.type_name}</td></tr>
                        <tr><th>Check-in</th><td>${new Date(booking.checkin_date).toLocaleDateString()}</td></tr>
                        <tr><th>Check-out</th><td>${new Date(booking.checkout_date).toLocaleDateString()}NonNullable\x0c
                        <tr><th>Nights</th><td>${nights} nights</td></tr>
                        <tr><th>Guests</th><td>${booking.guests || 1} persons</td></tr>
                    </table>
                    
                    <h4>Payment Summary</h4>
                    <table>
                        <tr><th>Price per night</th><td>$${pricePerNight}</td></tr>
                        <tr><th>Total Payment</th><td class="total">$${parseFloat(booking.total_payment).toFixed(2)}</td></tr>
                        <tr><th>Deposit (30%)</th><td>$${deposit.toFixed(2)}</td></tr>
                        <tr><th>Remaining at Check-in</th><td>$${(booking.total_payment - deposit).toFixed(2)}</td></tr>
                        <tr><th>Payment Status</th><td>${booking.payment_status === 'paid' ? 'Paid' : 'Unpaid'}</td></tr>
                    </table>
                    
                    <div class="footer">
                        <p>Thank you for choosing Bayon Hotel!</p>
                        <p>For inquiries, please contact: info@bayonhotel.com | Tel: 012 345 678</p>
                    </div>
                </body>
                </html>
            `);
            printWindow.document.close();
            printWindow.print();
        }
    });
</script>

<?php include 'includes/footer.php'; ?>