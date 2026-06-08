<?php
// ==========================================
// ENHANCED ADMIN DASHBOARD
// File: admin/dashboard.php
// ==========================================

session_start();
require_once '../config/database.php';
require_once '../classes/RoomManager.php';
require_once '../classes/BookingManager.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Auto-complete expired bookings
$database = new Database();
$db = $database->getConnection();
$bookingManager = new BookingManager($db);
$bookingManager->autoCompleteExpiredBookings();

$roomManager = new RoomManager($db);

// Handle room deletion
if (isset($_GET['delete_room'])) {
    $result = $roomManager->deleteRoom($_GET['delete_room']);
    $_SESSION['message'] = $result['message'];
    $_SESSION['message_type'] = $result['success'] ? 'success' : 'error';
    header("Location: dashboard.php?tab=rooms");
    exit();
}

// Handle booking confirmation
if (isset($_POST['confirm_booking'])) {
    $result = $bookingManager->updateBookingStatus($_POST['booking_id'], 'confirmed');
    if ($result) {
        // Update room status to booked
        $booking = $bookingManager->getBookingById($_POST['booking_id']);
        if ($booking) {
            $roomManager->updateRoomStatus($booking['room_id'], 'booked');
        }
        $_SESSION['message'] = 'បានបញ្ជាក់ការកក់ដោយជោគជ័យ';
        $_SESSION['message_type'] = 'success';
    } else {
        $_SESSION['message'] = 'មិនអាចបញ្ជាក់ការកក់បានទេ';
        $_SESSION['message_type'] = 'error';
    }
    header("Location: dashboard.php?tab=bookings");
    exit();
}

// Handle booking cancellation
if (isset($_POST['cancel_booking'])) {
    $result = $bookingManager->updateBookingStatus($_POST['booking_id'], 'cancelled');
    if ($result) {
        // Update room status back to available
        $booking = $bookingManager->getBookingById($_POST['booking_id']);
        if ($booking && $booking['booking_status'] === 'confirmed') {
            $roomManager->updateRoomStatus($booking['room_id'], 'available');
        }
        $_SESSION['message'] = 'បានបោះបង់ការកក់ដោយជោគជ័យ';
        $_SESSION['message_type'] = 'success';
    } else {
        $_SESSION['message'] = 'មិនអាចបោះបង់ការកក់បានទេ';
        $_SESSION['message_type'] = 'error';
    }
    header("Location: dashboard.php?tab=bookings");
    exit();
}

// Handle payment verification
if (isset($_POST['verify_payment'])) {
    $result = $bookingManager->updatePaymentStatus($_POST['booking_id'], 'paid');
    if ($result) {
        $_SESSION['message'] = 'បានបញ្ជាក់ការបង់ប្រាក់ដោយជោគជ័យ';
        $_SESSION['message_type'] = 'success';
    } else {
        $_SESSION['message'] = 'មិនអាចបញ្ជាក់ការបង់ប្រាក់បានទេ';
        $_SESSION['message_type'] = 'error';
    }
    header("Location: dashboard.php?tab=bookings");
    exit();
}

// Get current tab
$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'dashboard';

// Pagination for rooms
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;
$rooms = $roomManager->getAllRoomsPaginated($limit, $offset);
$totalRooms = $roomManager->getTotalRoomsCount();
$totalPages = ceil($totalRooms / $limit);

$roomTypes = $roomManager->getRoomTypes();
$bookings = $bookingManager->getAllBookings();
$stats = $bookingManager->getBookingStats();

// Calculate available rooms
$availableRooms = 0;
$bookedRooms = 0;
$maintenanceRooms = 0;
foreach ($roomManager->getAllRooms() as $room) {
    if ($room['room_status'] == 'available') $availableRooms++;
    elseif ($room['room_status'] == 'booked') $bookedRooms++;
    elseif ($room['room_status'] == 'maintenance') $maintenanceRooms++;
}
$is_admin_page = true;
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - BayonBooking</title>

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Siemreap&display=swap" rel="stylesheet">

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- DataTables -->
    <link href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>

    <style>
        :root {
            --primary-dark: #543414;
            --bg-light: #F1F0E7;
            --text-dark: #1a1a1a;
            --border-light: #e0e0e0;
            --success: #28a745;
            --warning: #ffc107;
            --danger: #dc3545;
            --info: #17a2b8;
            --secondary: #6c757d;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Siemreap', sans-serif;
            background-color: var(--bg-light);
            color: var(--text-dark);
        }

        /* Navbar Styles */
        .navbar {
            background: white !important;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            padding: 0.75rem 0;
        }

        .navbar-brand {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--primary-dark) !important;
        }

        .nav-link {
            color: var(--text-dark) !important;
            margin: 0 0.5rem;
            font-weight: 500;
            transition: 0.2s ease;
        }

        .nav-link:hover {
            color: var(--primary-dark) !important;
        }

        .nav-link.active {
            color: var(--primary-dark) !important;
            border-bottom: 2px solid var(--primary-dark);
        }

        .btn-logout {
            background: transparent;
            color: var(--text-dark);
            border: 1px solid var(--border-light);
            padding: 0.4rem 1rem !important;
            border-radius: 6px;
            transition: 0.2s ease;
        }

        .btn-logout:hover {
            background: var(--primary-dark);
            color: white;
            border-color: var(--primary-dark);
        }

        /* Admin Wrapper */
        .admin-wrapper {
            padding-top: 80px;
            min-height: 100vh;
        }

        /* Stat Cards - Clean Design */
        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            border: 1px solid var(--border-light);
            transition: 0.2s ease;
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }

        .stat-number {
            font-size: 2rem;
            font-weight: 600;
            color: var(--primary-dark);
        }

        .stat-label {
            color: #666;
            font-size: 0.85rem;
            margin-top: 5px;
        }

        .stat-icon {
            font-size: 2rem;
            color: #ccc;
        }

        /* Data Table */
        .data-card {
            background: white;
            border-radius: 2px;
            border: 1px solid var(--border-light);
            overflow: hidden;
            margin-bottom: 30px;
        }

        .data-card-header {
            padding: 15px 20px;
            border-bottom: 1px solid var(--border-light);
            background: white;
        }

        .data-card-header h5 {
            margin: 0;
            font-weight: 600;
        }

        .data-table {
            width: 100%;
        }

        .data-table thead th {
            background: #f8f9fa;
            border-bottom: 1px solid var(--border-light);
            padding: 12px 15px;
            font-weight: 600;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .data-table tbody td {
            padding: 12px 15px;
            vertical-align: middle;
            border-bottom: 1px solid #f0f0f0;
        }

        .data-table tbody tr:hover {
            background: #fafafa;
        }

        /* Badges */
        .badge-status {
            padding: 4px 10px;
            border-radius: 5px;
            font-size: 0.75rem;
            font-weight: 500;
            display: inline-block;
        }

        .badge-pending {
            background: #fff3cd;
            color: #856404;
        }

        .badge-confirmed {
            background: #d4edda;
            color: #155724;
        }

        .badge-cancelled {
            background: #f8d7da;
            color: #721c24;
        }

        .badge-completed {
            background: #d1ecf1;
            color: #0c5460;
        }

        .badge-available {
            background: #d4edda;
            color: #155724;
        }

        .badge-booked {
            background: #f8d7da;
            color: #721c24;
        }

        .badge-maintenance {
            background: #fff3cd;
            color: #856404;
        }

        /* Buttons - Clean */
        .btn-action {
            padding: 5px 10px;
            border-radius: 2px;
            font-size: 0.8rem;
            margin: 0 2px;
            border: 1px solid var(--border-light);
            background: white;
            transition: 0.2s ease;
        }

        .btn-action:hover {
            background: var(--primary-dark);
            color: white;
            border-color: var(--primary-dark);
        }

        .btn-primary-custom {
            background: var(--primary-dark);
            color: white;
            border: none;
            padding: 8px 20px;
            border-radius: 2px;
            font-weight: 500;
            transition: 0.2s ease;
        }

        .btn-primary-custom:hover {
            background: #3a2610;
            transform: translateY(-1px);
        }

        .btn-outline-custom {
            background: transparent;
            color: var(--text-dark);
            border: 1px solid var(--border-light);
            padding: 8px 20px;
            border-radius: 2px;
            transition: 0.2s ease;
        }

        .btn-outline-custom:hover {
            background: var(--primary-dark);
            color: white;
            border-color: var(--primary-dark);
        }

        /* Welcome Banner */
        .welcome-banner {
            background: white;
            border: 1px solid var(--border-light);
            padding: 20px 25px;
            border-radius: 2px;
            margin-top: 60px;
            margin-bottom: 30px;
        }

        /* Modal Styles */
        .modal-content {
            border-radius: 2px;
            border: none;
        }

        .modal-header {
            border-bottom: 1px solid var(--border-light);
            background: white;
            padding: 15px 20px;
        }

        .modal-footer {
            border-top: 1px solid var(--border-light);
            padding: 15px 20px;
        }

        .form-control,
        .form-select {
            border-radius: 2px;
            border: 1px solid var(--border-light);
            padding: 8px 12px;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: var(--primary-dark);
            box-shadow: none;
        }

        .room-image-preview {
            max-width: 100%;
            height: auto;
            border-radius: 8px;
            margin-top: 10px;
        }

        .current-image {
            max-width: 150px;
            border-radius: 8px;
            margin: 10px 0;
            border: 1px solid var(--border-light);
        }

        /* Pagination */
        .pagination {
            margin: 20px 0 0;
        }

        .page-link {
            color: var(--text-dark);
            border: 1px solid var(--border-light);
            margin: 0 2px;
            border-radius: 6px;
        }

        .page-link:hover {
            background: var(--primary-dark);
            color: white;
            border-color: var(--primary-dark);
        }

        .page-item.active .page-link {
            background: var(--primary-dark);
            border-color: var(--primary-dark);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .admin-wrapper {
                padding-top: 70px;
            }

            .stat-number {
                font-size: 1.5rem;
            }

            .data-table thead th {
                font-size: 0.7rem;
            }

            .data-table tbody td {
                font-size: 0.8rem;
            }
        }
    </style>
</head>

<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg fixed-top">
        <div class="container">
            <img class="navbar-brand" style="width: 100px;" src="/assets/images/Bopha1.png" alt="">

            <!-- Brand -->
            <a class="navbar-brand" href="<?php echo $is_admin_page ? '../index.php' : 'index.php'; ?>">
                <span style="color: #02850D; font-family: khmer os moul , sans-serif;" class="brand-text">សណ្ឋាគារបុប្ផាខ្មែរ</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center">
                    <li class="nav-item">
                        <a class="nav-link <?php echo $active_tab == 'dashboard' ? 'active' : ''; ?>"
                            href="dashboard.php?tab=dashboard"
                            style="display:flex; align-items:center; gap:8px;">
                            <img src="/assets/images/5432747.png"
                                alt="Dashboard"
                                style="width:40px; height:40px; object-fit:contain;">
                            Dashboard
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link <?php echo $active_tab == 'rooms' ? 'active' : ''; ?>"
                            href="dashboard.php?tab=rooms"
                            style="display:flex; align-items:center; gap:8px;">
                            <img src="/assets/images/room.png"
                                alt="Rooms"
                                style="width:40px; height:40px; object-fit:contain;">
                            Rooms
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link <?php echo $active_tab == 'bookings' ? 'active' : ''; ?>"
                            href="dashboard.php?tab=bookings"
                            style="display:flex; align-items:center; gap:8px;">
                            <img src="/assets/images/book.png"
                                alt="Bookings"
                                style="width:40px; height:40px; object-fit:contain;">
                            Bookings
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link"
                            href="../index.php"
                            style="display:flex; align-items:center; gap:8px;">
                            <img src="/assets/images/home.png"
                                alt="View Site"
                                style="width:40px; height:40px; object-fit:contain;">
                            View Site
                        </a>
                    </li>

                    <li class="nav-item ms-2">
                        <a class="btn btn-logout"
                            href="../logout.php"
                            style="display:flex; align-items:center; gap:8px;">
                            <img src="/assets/images/logout.png"
                                alt="Logout"
                                style="width:40px; height:40px; object-fit:contain;">
                            Logout
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="admin-wrapper">
        <div class="container">
            <!-- Messages -->
            <?php if (isset($_SESSION['message'])): ?>
                <div class="alert alert-<?php echo $_SESSION['message_type'] == 'success' ? 'success' : 'danger'; ?> alert-dismissible fade show" role="alert">
                    <?php
                    echo $_SESSION['message'];
                    unset($_SESSION['message']);
                    unset($_SESSION['message_type']);
                    ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Welcome Banner -->
            <div class="welcome-banner">
                <div class="d-flex justify-content-between align-items-center flex-wrap">
                    <div>
                        <h5><i class="fas fa-user-shield"></i> Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></h5>
                        <p class="mb-0 text-muted">Manage your hotel rooms, bookings, and monitor all activities from here.</p>
                    </div>
                    <div class="mt-2 mt-sm-0">
                        <span class="text-muted">
                            <i class="far fa-calendar-alt"></i> <?php echo date('F j, Y'); ?>
                        </span>
                    </div>
                </div>
            </div>

            <?php if ($active_tab == 'dashboard'): ?>
                <!-- Dashboard Tab -->
                <div class="row">
                    <div class="col-md-3 col-sm-6">
                        <div class="stat-card">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="stat-number"><?php echo $totalRooms; ?></div>
                                    <div class="stat-label">Total Rooms</div>
                                </div>
                                <div class="stat-icon"><i class="fas fa-bed"></i></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <div class="stat-card">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="stat-number"><?php echo $availableRooms; ?></div>
                                    <div class="stat-label">Available Rooms</div>
                                </div>
                                <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <div class="stat-card">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="stat-number"><?php echo isset($stats['total']) ? $stats['total'] : 0; ?></div>
                                    <div class="stat-label">Total Bookings</div>
                                </div>
                                <div class="stat-icon"><i class="fas fa-calendar-check"></i></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <div class="stat-card">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="stat-number"><?php echo isset($stats['pending']) ? $stats['pending'] : 0; ?></div>
                                    <div class="stat-label">Pending Bookings</div>
                                </div>
                                <div class="stat-icon"><i class="fas fa-clock"></i></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Bookings -->
                <div class="data-card">
                    <div class="data-card-header">
                        <h5><i class="fas fa-receipt"></i> Recent Bookings</h5>
                    </div>
                    <div class="table-responsive">
                        <table class="table data-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Guest</th>
                                    <th>Room</th>
                                    <th>Check-in</th>
                                    <th>Check-out</th>
                                    <th>Total</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $recentBookings = array_slice($bookings, 0, 10);
                                if (empty($recentBookings)):
                                ?>
                                    <tr>
                                        <td colspan="8" class="text-center">No bookings found</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($recentBookings as $booking): ?>
                                        <tr>
                                            <td>#<?php echo $booking['booking_id']; ?></td>
                                            <td><?php echo htmlspecialchars($booking['user_name']); ?></td>
                                            <td><?php echo htmlspecialchars($booking['room_number']); ?></td>
                                            <td><?php echo date('d/m/Y', strtotime($booking['checkin_date'])); ?></td>
                                            <td><?php echo date('d/m/Y', strtotime($booking['checkout_date'])); ?></td>
                                            <td>$<?php echo number_format($booking['total_payment'], 2); ?></td>
                                            <td>
                                                <span class="badge-status <?php
                                                                            echo $booking['booking_status'] == 'pending' ? 'badge-pending' : ($booking['booking_status'] == 'confirmed' ? 'badge-confirmed' : ($booking['booking_status'] == 'cancelled' ? 'badge-cancelled' : 'badge-completed'));
                                                                            ?>">
                                                    <?php
                                                    echo $booking['booking_status'] == 'pending' ? 'Pending' : ($booking['booking_status'] == 'confirmed' ? 'Confirmed' : ($booking['booking_status'] == 'cancelled' ? 'Cancelled' : 'Completed'));
                                                    ?>
                                                </span>
                    </div>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
            </table>
                </div>
        </div>
    <?php endif; ?>

    <?php if ($active_tab == 'rooms'): ?>
        <!-- Manage Rooms Tab -->
        <div class="mb-3">
            <button class="btn btn-primary-custom" data-bs-toggle="modal" data-bs-target="#addRoomModal">
                <i class="fas fa-plus"></i> Add New Room
            </button>
        </div>

        <div class="data-card">
            <div class="data-card-header">
                <h5><i class="fas fa-door-open"></i> Room Management</h5>
            </div>
            <div class="table-responsive">
                <table class="table data-table" id="roomsTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Room No.</th>
                            <th>Floor</th>
                            <th>Type</th>
                            <th>Price/Night</th>
                            <th>Capacity</th>
                            <th>Status</th>
                            <th>Image</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rooms as $room): ?>
                            <tr id="room-row-<?php echo $room['room_id']; ?>">
                                <td><?php echo $room['room_id']; ?></td>
                                <td><strong><?php echo htmlspecialchars($room['room_number']); ?></strong></td>
                                <td>Floor <?php echo $room['floor']; ?></td>
                                <td><?php echo htmlspecialchars($room['type_name']); ?></td>
                                <td>$<?php echo number_format($room['price_per_night'], 2); ?></td>
                                <td><i class="fas fa-user"></i> <?php echo $room['capacity']; ?></td>
                                <td>
                                    <select class="form-select form-select-sm status-select" data-id="<?php echo $room['room_id']; ?>" style="width: 120px;">
                                        <option value="available" <?php echo $room['room_status'] == 'available' ? 'selected' : ''; ?>>Available</option>
                                        <option value="booked" <?php echo $room['room_status'] == 'booked' ? 'selected' : ''; ?>>Booked</option>
                                        <option value="maintenance" <?php echo $room['room_status'] == 'maintenance' ? 'selected' : ''; ?>>Maintenance</option>
                                    </select>
            </div>
            <td>
                <?php if ($room['room_image']): ?>
                    <img src="../<?php echo $room['room_image']; ?>" style="width: 40px; height: 40px; object-fit: cover; border-radius: 6px;">
                <?php else: ?>
                    <span class="text-muted">No image</span>
                <?php endif; ?>
        </div>
        <td>
            <button class="btn-action view-room" data-id="<?php echo $room['room_id']; ?>" title="View">
                <i class="fas fa-eye"></i>
            </button>
            <button class="btn-action edit-room" data-id="<?php echo $room['room_id']; ?>" title="Edit">
                <i class="fas fa-edit"></i>
            </button>
            <button class="btn-action delete-room" data-id="<?php echo $room['room_id']; ?>" title="Delete">
                <i class="fas fa-trash"></i>
            </button>
    </div>
    </tr>
<?php endforeach; ?>
</tbody>
</table>
</div>

<!-- Pagination -->
<?php if ($totalPages > 1): ?>
    <div class="data-card-header">
        <nav>
            <ul class="pagination justify-content-end mb-0">
                <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                    <a class="page-link" href="?tab=rooms&page=<?php echo $page - 1; ?>">Previous</a>
                </li>
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                        <a class="page-link" href="?tab=rooms&page=<?php echo $i; ?>"><?php echo $i; ?></a>
                    </li>
                <?php endfor; ?>
                <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                    <a class="page-link" href="?tab=rooms&page=<?php echo $page + 1; ?>">Next</a>
                </li>
            </ul>
        </nav>
    </div>
<?php endif; ?>
</div>

<!-- Add Room Modal -->
<div class="modal fade" id="addRoomModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-plus-circle"></i> Add New Room</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="addRoomForm" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Room Number *</label>
                            <input type="text" name="room_number" class="form-control" required placeholder="e.g., 101, 102">
                            <small class="text-muted">Room numbers 1-10 = Floor A, 11-20 = Floor B, etc.</small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Room Type *</label>
                            <select name="room_type_id" class="form-select" required>
                                <option value="">Select Type</option>
                                <?php foreach ($roomTypes as $type): ?>
                                    <option value="<?php echo $type['room_types_id']; ?>"><?php echo htmlspecialchars($type['type_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Price per Night ($) *</label>
                            <input type="number" step="0.01" name="price_per_night" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Capacity (Persons) *</label>
                            <input type="number" name="capacity" class="form-control" required>
                        </div>
                        <div class="col-12 mb-3">
                            <label class="form-label">Amenities</label>
                            <textarea name="equipments" class="form-control" rows="2" placeholder="WiFi, Air Conditioner, TV, Mini Bar, Hot Water..."></textarea>
                        </div>
                        <div class="col-12 mb-3">
                            <label class="form-label">Room Description</label>
                            <textarea name="room_description" class="form-control" rows="3"></textarea>
                        </div>
                        <div class="col-12 mb-3">
                            <label class="form-label">Room Image</label>
                            <input type="file" name="room_image" class="form-control" accept="image/*">
                            <small class="text-muted">Accepted: JPG, JPEG, PNG, GIF (Max 5MB)</small>
                            <div id="imagePreview" class="mt-2"></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-custom" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary-custom">Add Room</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- View Room Modal -->
<div class="modal fade" id="viewRoomModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-info-circle"></i> Room Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="viewRoomContent"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-custom" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Edit Room Modal -->
<div class="modal fade" id="editRoomModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-edit"></i> Edit Room</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editRoomForm" enctype="multipart/form-data">
                <input type="hidden" name="room_id" id="edit_room_id">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Room Number *</label>
                            <input type="text" name="room_number" id="edit_room_number" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Room Type *</label>
                            <select name="room_type_id" id="edit_room_type_id" class="form-select" required>
                                <?php foreach ($roomTypes as $type): ?>
                                    <option value="<?php echo $type['room_types_id']; ?>"><?php echo htmlspecialchars($type['type_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Price per Night ($) *</label>
                            <input type="number" step="0.01" name="price_per_night" id="edit_price_per_night" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Capacity (Persons) *</label>
                            <input type="number" name="capacity" id="edit_capacity" class="form-control" required>
                        </div>
                        <div class="col-12 mb-3">
                            <label class="form-label">Amenities</label>
                            <textarea name="equipments" id="edit_equipments" class="form-control" rows="2"></textarea>
                        </div>
                        <div class="col-12 mb-3">
                            <label class="form-label">Room Description</label>
                            <textarea name="room_description" id="edit_room_description" class="form-control" rows="3"></textarea>
                        </div>
                        <div class="col-12 mb-3">
                            <label class="form-label">Current Image</label>
                            <div id="current_image_container"></div>
                            <label class="form-label mt-2">Change Image</label>
                            <input type="file" name="room_image" class="form-control" accept="image/*">
                            <small class="text-muted">Leave empty to keep current image</small>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-custom" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary-custom">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if ($active_tab == 'bookings'): ?>
    <!-- Manage Bookings Tab -->
    <div class="data-card">
        <div class="data-card-header">
            <h5><i class="fas fa-calendar-alt"></i> Booking Management</h5>
        </div>
        <div class="table-responsive">
            <table class="table data-table" id="bookingsTable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Guest</th>
                        <th>Room</th>
                        <th>Check-in</th>
                        <th>Check-out</th>
                        <th>Nights</th>
                        <th>Total</th>
                        <th>Booking Status</th>
                        <th>Payment</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($bookings as $booking):
                        $booking_status_display = $booking['booking_status'];
                        if ($booking['checkout_date'] < date('Y-m-d') && $booking['booking_status'] == 'confirmed') {
                            $booking_status_display = 'completed';
                        }
                    ?>
                        <tr data-status="<?php echo $booking['booking_status']; ?>">
                            <td>#<?php echo $booking['booking_id']; ?></td>
                            <td><?php echo htmlspecialchars($booking['user_name']); ?></td>
                            <td><?php echo htmlspecialchars($booking['room_number']); ?></td>
                            <td><?php echo date('d/m/Y', strtotime($booking['checkin_date'])); ?></td>
                            <td><?php echo date('d/m/Y', strtotime($booking['checkout_date'])); ?></td>
                            <td><?php echo $booking['nights']; ?> nights
        </div>
        <td>$<?php echo number_format($booking['total_payment'], 2); ?>
    </div>
    <td>
        <span class="badge-status <?php
                                    echo $booking_status_display == 'pending' ? 'badge-pending' : ($booking_status_display == 'confirmed' ? 'badge-confirmed' : ($booking_status_display == 'cancelled' ? 'badge-cancelled' : 'badge-completed'));
                                    ?>">
            <?php
                        echo $booking_status_display == 'pending' ? 'Pending' : ($booking_status_display == 'confirmed' ? 'Confirmed' : ($booking_status_display == 'cancelled' ? 'Cancelled' : 'Completed'));
            ?>
        </span>
        </div>
    <td>
        <span class="badge-status <?php echo $booking['payment_status'] == 'paid' ? 'badge-confirmed' : 'badge-pending'; ?>">
            <?php echo $booking['payment_status'] == 'paid' ? 'Paid' : 'Unpaid'; ?>
        </span>
        </div>
    <td>
        <button class="btn-action view-booking" data-id="<?php echo $booking['booking_id']; ?>" title="View Details">
            <i class="fas fa-eye"></i>
        </button>
        <?php if ($booking_status_display == 'pending'): ?>
            <form method="POST" style="display: inline-block;" class="confirm-form">
                <input type="hidden" name="booking_id" value="<?php echo $booking['booking_id']; ?>">
                <input type="hidden" name="confirm_booking" value="1">
                <button type="submit" class="btn-action" title="Confirm Booking">
                    <i class="fas fa-check" style="color: #28a745;"></i>
                </button>
            </form>
        <?php endif; ?>
        <?php if ($booking_status_display == 'pending' || $booking_status_display == 'confirmed'): ?>
            <form method="POST" style="display: inline-block;" class="cancel-form">
                <input type="hidden" name="booking_id" value="<?php echo $booking['booking_id']; ?>">
                <input type="hidden" name="cancel_booking" value="1">
                <button type="submit" class="btn-action" title="Cancel Booking">
                    <i class="fas fa-times" style="color: #dc3545;"></i>
                </button>
            </form>
        <?php endif; ?>
        <?php if ($booking['payment_status'] == 'unpaid' && $booking_status_display == 'confirmed'): ?>
            <form method="POST" style="display: inline-block;" class="payment-form">
                <input type="hidden" name="booking_id" value="<?php echo $booking['booking_id']; ?>">
                <input type="hidden" name="verify_payment" value="1">
                <button type="submit" class="btn-action" title="Verify Payment">
                    <i class="fas fa-money-bill" style="color: #ffc107;"></i>
                </button>
            </form>
        <?php endif; ?>
        </div>
        </tr>
    <?php endforeach; ?>
    </tbody>
    </table>
    </div>
    </div>

    <!-- Booking Details Modal -->
    <div class="modal fade" id="bookingDetailsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-receipt"></i> Booking Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="bookingDetailsContent"></div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-custom" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary-custom" id="printBooking">Print Invoice</button>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>
</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // ============================================
    // ROOM MANAGEMENT FUNCTIONS
    // ============================================

    // Preview image before upload
    document.querySelector('input[name="room_image"]')?.addEventListener('change', function(e) {
        const preview = document.getElementById('imagePreview');
        preview.innerHTML = '';
        if (this.files && this.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                const img = document.createElement('img');
                img.src = e.target.result;
                img.style.maxWidth = '200px';
                img.style.borderRadius = '8px';
                img.style.marginTop = '10px';
                preview.appendChild(img);
            };
            reader.readAsDataURL(this.files[0]);
        }
    });

    // Add Room with AJAX
    document.getElementById('addRoomForm')?.addEventListener('submit', async function(e) {
        e.preventDefault();

        const formData = new FormData(this);
        formData.append('action', 'add_room');

        const result = await Swal.fire({
            title: 'Confirm Add Room',
            text: 'Are you sure you want to add this room?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#543414',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, Add',
            cancelButtonText: 'Cancel'
        });

        if (result.isConfirmed) {
            Swal.fire({
                title: 'Processing...',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            try {
                const response = await fetch('process_room.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await response.json();

                Swal.close();

                if (data.success) {
                    Swal.fire('Success!', data.message, 'success').then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire('Error!', data.message, 'error');
                }
            } catch (error) {
                Swal.close();
                Swal.fire('Error!', 'Failed to add room', 'error');
            }
        }
    });

    // View Room Details
    document.querySelectorAll('.view-room').forEach(btn => {
        btn.addEventListener('click', async function() {
            const roomId = this.dataset.id;

            try {
                const response = await fetch(`get_room_details.php?id=${roomId}`);
                const room = await response.json();

                if (room) {
                    const deposit = room.price_per_night * 0.30;
                    const modalContent = `
                            <div class="row">
                                <div class="col-md-5">
                                    ${room.room_image ? `<img src="../${room.room_image}" class="img-fluid rounded mb-3" style="width: 100%;">` : '<div class="alert alert-secondary">No image available</div>'}
                                </div>
                                <div class="col-md-7">
                                    <table class="table table-bordered">
                                        <tr><th>Room Number:</th><td>${room.room_number} (Floor ${room.floor})</td></tr>
                                        <tr><th>Room Type:</th><td>${room.type_name}</td></tr>
                                        <tr><th>Price per Night:</th><td>$${parseFloat(room.price_per_night).toFixed(2)}</td></tr>
                                        <tr><th>Deposit (30%):</th><td>$${deposit.toFixed(2)}</td></tr>
                                        <tr><th>Capacity:</th><td>${room.capacity} persons</td></tr>
                                        <tr><th>Status:</th><td>${room.room_status == 'available' ? 'Available' : (room.room_status == 'booked' ? 'Booked' : 'Maintenance')}</td></tr>
                                        <tr><th>Amenities:</th><td>${room.equipments || 'None'}</td></tr>
                                        <tr><th>Description:</th><td>${room.room_description || 'None'}</td></tr>
                                    </table>
                                </div>
                            </div>
                        `;
                    document.getElementById('viewRoomContent').innerHTML = modalContent;
                    new bootstrap.Modal(document.getElementById('viewRoomModal')).show();
                }
            } catch (error) {
                Swal.fire('Error!', 'Could not fetch room details', 'error');
            }
        });
    });

    // Edit Room - Load data
    document.querySelectorAll('.edit-room').forEach(btn => {
        btn.addEventListener('click', async function() {
            const roomId = this.dataset.id;

            try {
                const response = await fetch(`get_room_details.php?id=${roomId}`);
                const room = await response.json();

                if (room) {
                    document.getElementById('edit_room_id').value = room.room_id;
                    document.getElementById('edit_room_number').value = room.room_number;
                    document.getElementById('edit_room_type_id').value = room.room_type_id;
                    document.getElementById('edit_price_per_night').value = room.price_per_night;
                    document.getElementById('edit_capacity').value = room.capacity;
                    document.getElementById('edit_equipments').value = room.equipments || '';
                    document.getElementById('edit_room_description').value = room.room_description || '';

                    const imgContainer = document.getElementById('current_image_container');
                    if (room.room_image) {
                        imgContainer.innerHTML = `<img src="../${room.room_image}" class="current-image" alt="Current Room Image">`;
                    } else {
                        imgContainer.innerHTML = '<p class="text-muted">No image</p>';
                    }

                    new bootstrap.Modal(document.getElementById('editRoomModal')).show();
                }
            } catch (error) {
                Swal.fire('Error!', 'Could not fetch room details', 'error');
            }
        });
    });

    // Edit Room - Submit
    document.getElementById('editRoomForm')?.addEventListener('submit', async function(e) {
        e.preventDefault();

        const result = await Swal.fire({
            title: 'Confirm Changes',
            text: 'Are you sure you want to update this room?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#543414',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, Update',
            cancelButtonText: 'Cancel'
        });

        if (result.isConfirmed) {
            const formData = new FormData(this);
            formData.append('action', 'update_room');

            Swal.fire({
                title: 'Processing...',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            try {
                const response = await fetch('process_room.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await response.json();

                Swal.close();

                if (data.success) {
                    Swal.fire('Success!', data.message, 'success').then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire('Error!', data.message, 'error');
                }
            } catch (error) {
                Swal.close();
                Swal.fire('Error!', 'Failed to update room', 'error');
            }
        }
    });

    // Update Room Status
    document.querySelectorAll('.status-select').forEach(select => {
        select.addEventListener('change', async function() {
            const roomId = this.dataset.id;
            const newStatus = this.value;
            const statusText = newStatus === 'available' ? 'Available' : (newStatus === 'booked' ? 'Booked' : 'Maintenance');

            const result = await Swal.fire({
                title: 'Confirm Status Change',
                text: `Change room status to "${statusText}"?`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#543414',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, Change',
                cancelButtonText: 'Cancel'
            });

            if (result.isConfirmed) {
                Swal.fire({
                    title: 'Processing...',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                try {
                    const response = await fetch('process_room.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: `action=update_status&room_id=${roomId}&room_status=${newStatus}`
                    });
                    const data = await response.json();

                    Swal.close();

                    if (data.success) {
                        Swal.fire('Success!', data.message, 'success');
                    } else {
                        Swal.fire('Error!', data.message, 'error');
                        location.reload();
                    }
                } catch (error) {
                    Swal.close();
                    Swal.fire('Error!', 'Failed to update status', 'error');
                    location.reload();
                }
            } else {
                location.reload();
            }
        });
    });

    // Delete Room
    document.querySelectorAll('.delete-room').forEach(btn => {
        btn.addEventListener('click', async function() {
            const roomId = this.dataset.id;

            const result = await Swal.fire({
                title: 'Confirm Delete',
                text: 'Are you sure you want to delete this room? This action cannot be undone!',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, Delete',
                cancelButtonText: 'Cancel'
            });

            if (result.isConfirmed) {
                try {
                    const response = await fetch('process_room.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: `action=delete_room&room_id=${roomId}`
                    });
                    const data = await response.json();

                    if (data.success) {
                        Swal.fire('Deleted!', data.message, 'success').then(() => {
                            document.getElementById(`room-row-${roomId}`)?.remove();
                        });
                    } else {
                        Swal.fire('Error!', data.message, 'error');
                    }
                } catch (error) {
                    Swal.fire('Error!', 'Failed to delete room', 'error');
                }
            }
        });
    });

    // Confirm forms with confirmation
    document.querySelectorAll('.confirm-form').forEach(form => {
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            const result = await Swal.fire({
                title: 'Confirm Booking',
                text: 'Are you sure you want to confirm this booking?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, Confirm',
                cancelButtonText: 'Cancel'
            });
            if (result.isConfirmed) form.submit();
        });
    });

    document.querySelectorAll('.cancel-form').forEach(form => {
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            const result = await Swal.fire({
                title: 'Cancel Booking',
                text: 'Are you sure you want to cancel this booking?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, Cancel',
                cancelButtonText: 'No'
            });
            if (result.isConfirmed) form.submit();
        });
    });

    document.querySelectorAll('.payment-form').forEach(form => {
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            const result = await Swal.fire({
                title: 'Verify Payment',
                text: 'Confirm that payment has been received?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#ffc107',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, Verify',
                cancelButtonText: 'Cancel'
            });
            if (result.isConfirmed) form.submit();
        });
    });

    // View Booking Details
    document.querySelectorAll('.view-booking').forEach(btn => {
        btn.addEventListener('click', async function() {
            const bookingId = this.dataset.id;

            try {
                const response = await fetch(`get_booking_details.php?id=${bookingId}`);
                const booking = await response.json();

                if (booking) {
                    const deposit = booking.total_payment * 0.30;
                    const remaining = booking.total_payment - deposit;

                    const modalContent = `
                            <div class="row">
                                <div class="col-md-6">
                                    <h6>Guest Information</h6>
                                    <table class="table table-bordered">
                                        <tr><th>Name:</th><td>${booking.user_name}</td></tr>
                                        <tr><th>Email:</th><td>${booking.email || 'N/A'}</td></tr>
                                        <tr><th>Phone:</th><td>${booking.phone_number || 'N/A'}</td></tr>
                                    </table>
                                </div>
                                <div class="col-md-6">
                                    <h6>Room Information</h6>
                                    <table class="table table-bordered">
                                        <tr><th>Room Number:</th><td>${booking.room_number}</td></tr>
                                        <tr><th>Room Type:</th><td>${booking.type_name}</td></tr>
                                        <tr><th>Capacity:</th><td>${booking.capacity} persons</td></tr>
                                    </table>
                                </div>
                            </div>
                            <h6 class="mt-3">Booking Details</h6>
                            <table class="table table-bordered">
                                <tr><th>Check-in:</th><td>${new Date(booking.checkin_date).toLocaleDateString()}</td></tr>
                                <tr><th>Check-out:</th><td>${new Date(booking.checkout_date).toLocaleDateString()}</td></tr>
                                <tr><th>Nights:</th><td>${booking.nights} nights</td></tr>
                                <tr><th>Total Payment:</th><td>$${parseFloat(booking.total_payment).toFixed(2)}</td></tr>
                                <tr><th>Deposit (30%):</th><td>$${deposit.toFixed(2)}</td></tr>
                                <tr><th>Remaining:</th><td>$${remaining.toFixed(2)}</td></tr>
                                <tr><th>Booking Status:</th><td>${booking.booking_status}</td></tr>
                                <tr><th>Payment Status:</th><td>${booking.payment_status}</td></tr>
                                <tr><th>Booking Date:</th><td>${new Date(booking.booking_at).toLocaleString()}</td></tr>
                            </table>
                            ${booking.special_requests ? `<div class="alert alert-info mt-3"><strong>Special Requests:</strong><br>${booking.special_requests}</div>` : ''}
                        `;
                    document.getElementById('bookingDetailsContent').innerHTML = modalContent;
                    window.currentBookingData = booking;
                    new bootstrap.Modal(document.getElementById('bookingDetailsModal')).show();
                }
            } catch (error) {
                Swal.fire('Error!', 'Could not fetch booking details', 'error');
            }
        });
    });

    // Print Invoice
    document.getElementById('printBooking')?.addEventListener('click', function() {
        if (window.currentBookingData) {
            const booking = window.currentBookingData;
            const deposit = booking.total_payment * 0.30;
            const remaining = booking.total_payment - deposit;

            const printWindow = window.open('', '_blank');
            printWindow.document.write(`
                    <html>
                    <head>
                        <title>Invoice - Booking #${booking.booking_id}</title>
                        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
                        <style>
                            body { padding: 50px; font-family: Arial, sans-serif; }
                            .invoice-header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #543414; padding-bottom: 20px; }
                            .hotel-name { color: #543414; font-size: 28px; font-weight: bold; }
                            .invoice-title { font-size: 24px; margin-top: 20px; }
                            .invoice-footer { text-align: center; margin-top: 50px; border-top: 1px solid #ddd; padding-top: 20px; }
                        </style>
                    </head>
                    <body>
                        <div class="invoice-header">
                            <div class="hotel-name">Bayon Hotel</div>
                            <p>Siem Reap, Cambodia</p>
                            <div class="invoice-title">Booking Invoice</div>
                        </div>
                        <div class="row">
                            <div class="col-6"><strong>Booking ID:</strong> #${booking.booking_id}</div>
                            <div class="col-6 text-end"><strong>Date:</strong> ${new Date().toLocaleDateString()}</div>
                        </div>
                        <h5 class="mt-4">Guest Information</h5>
                        <table class="table table-bordered">
                            <tr><th>Name</th><td>${booking.user_name}</td></tr>
                            <tr><th>Email</th><td>${booking.email || 'N/A'}</td></tr>
                            <tr><th>Phone</th><td>${booking.phone_number || 'N/A'}</td></tr>
                        </table>
                        <h5 class="mt-4">Room Information</h5>
                        <table class="table table-bordered">
                            <tr><th>Room Number</th><td>${booking.room_number}</td></tr>
                            <tr><th>Room Type</th><td>${booking.type_name}</td></tr>
                            <tr><th>Check-in</th><td>${new Date(booking.checkin_date).toLocaleDateString()}</td></tr>
                            <tr><th>Check-out</th><td>${new Date(booking.checkout_date).toLocaleDateString()}</td></tr>
                            <tr><th>Nights</th><td>${booking.nights} nights</td></tr>
                        </table>
                        <h5 class="mt-4">Payment Summary</h5>
                        <table class="table table-bordered">
                            <tr><th>Total Payment</th><td class="text-end">$${parseFloat(booking.total_payment).toFixed(2)}</td></tr>
                            <tr><th>Deposit (30%)</th><td class="text-end">$${deposit.toFixed(2)}</td></tr>
                            <tr><th>Remaining</th><td class="text-end">$${remaining.toFixed(2)}</td></tr>
                            <tr><th>Payment Status</th><td class="text-end">${booking.payment_status === 'paid' ? 'Paid' : 'Unpaid'}</td></tr>
                        </table>
                        <div class="invoice-footer">
                            <p>Thank you for choosing Bayon Hotel!</p>
                        </div>
                    </body>
                    </html>
                `);
            printWindow.document.close();
            printWindow.print();
        }
    });

    // ============================================
    // BOOKING MODAL DATE VALIDATION
    // ADD THIS TO YOUR INDEX.PHP BOOKING MODAL
    // ============================================
    
    // Variables for booking validation
    let currentRoomPrice = 0;
    let currentCapacity = 0;
    let currentRoomId = 0;
    
    // Function to open booking modal with validation
    window.openBookingModal = function(roomId, roomNumber, price, roomType, capacity) {
        currentRoomPrice = price;
        currentCapacity = capacity;
        currentRoomId = roomId;
        
        // Set modal fields
        const modalRoomId = document.getElementById('modal_room_id');
        const modalRoomNumber = document.getElementById('modal_room_number');
        const modalRoomType = document.getElementById('modal_room_type');
        const modalPrice = document.getElementById('modal_price');
        const modalCapacity = document.getElementById('modal_capacity');
        const displayPrice = document.getElementById('display_price_per_night');
        
        if (modalRoomId) modalRoomId.value = roomId;
        if (modalRoomNumber) modalRoomNumber.innerHTML = roomNumber;
        if (modalRoomType) modalRoomType.innerHTML = roomType;
        if (modalPrice) modalPrice.innerHTML = '$' + price.toFixed(2) + ' / night';
        if (modalCapacity) modalCapacity.innerHTML = capacity;
        if (displayPrice) displayPrice.innerHTML = price.toFixed(2);
        
        // Set max guests based on room capacity
        const guestsSelect = document.getElementById('modal_guests');
        if (guestsSelect) {
            guestsSelect.innerHTML = '';
            for(let i = 1; i <= capacity; i++) {
                const option = document.createElement('option');
                option.value = i;
                option.textContent = i + ' Guest' + (i > 1 ? 's' : '');
                guestsSelect.appendChild(option);
            }
        }
        
        // Set minimum check-in date (tomorrow)
        const today = new Date();
        const tomorrow = new Date(today);
        tomorrow.setDate(today.getDate() + 1);
        
        const checkinInput = document.getElementById('modal_checkin');
        const checkoutInput = document.getElementById('modal_checkout');
        
        if (checkinInput) {
            checkinInput.min = tomorrow.toISOString().split('T')[0];
            checkinInput.value = '';
        }
        if (checkoutInput) {
            checkoutInput.value = '';
            checkoutInput.min = '';
        }
        
        // Reset displays
        const nightsDisplay = document.getElementById('display_nights');
        const totalDisplay = document.getElementById('display_total');
        const depositDisplay = document.getElementById('display_deposit');
        const remainingDisplay = document.getElementById('display_remaining');
        
        if (nightsDisplay) nightsDisplay.innerHTML = '0';
        if (totalDisplay) totalDisplay.innerHTML = '0';
        if (depositDisplay) depositDisplay.innerHTML = '0';
        if (remainingDisplay) remainingDisplay.innerHTML = '0';
        
        // Remove any existing error messages
        removeErrorMessages();
        
        // Set up event listeners
        if (checkinInput) checkinInput.onchange = validateDates;
        if (checkoutInput) checkoutInput.onchange = validateDates;
        
        // Show modal
        const modalElement = document.getElementById('bookingModal');
        if (modalElement) {
            const modal = new bootstrap.Modal(modalElement);
            modal.show();
        }
    };
    
    // Validate dates function
    function validateDates() {
        const checkin = document.getElementById('modal_checkin')?.value;
        const checkout = document.getElementById('modal_checkout')?.value;
        
        // Remove existing error messages
        removeErrorMessages();
        
        if (!checkin && !checkout) {
            return;
        }
        
        if (checkin && !checkout) {
            // Only check-in selected - set minimum checkout date
            const checkinDate = new Date(checkin);
            const minCheckout = new Date(checkinDate);
            minCheckout.setDate(checkinDate.getDate() + 1);
            const checkoutInput = document.getElementById('modal_checkout');
            if (checkoutInput) {
                checkoutInput.min = minCheckout.toISOString().split('T')[0];
            }
            return;
        }
        
        if (!checkin && checkout) {
            // Check-out without check-in
            showErrorMessage('Please select check-in date first', 'checkin');
            const checkoutInput = document.getElementById('modal_checkout');
            if (checkoutInput) checkoutInput.value = '';
            return;
        }
        
        if (checkin && checkout) {
            const checkinDate = new Date(checkin);
            const checkoutDate = new Date(checkout);
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            const tomorrow = new Date(today);
            tomorrow.setDate(today.getDate() + 1);
            
            // Validation 1: Check-in cannot be in the past
            if (checkinDate < tomorrow) {
                showErrorMessage('Check-in date must be at least tomorrow. Please select a future date.', 'checkin');
                const checkinInput = document.getElementById('modal_checkin');
                const checkoutInput = document.getElementById('modal_checkout');
                if (checkinInput) checkinInput.value = '';
                if (checkoutInput) checkoutInput.value = '';
                resetPriceDisplay();
                return;
            }
            
            // Validation 2: Check-out must be after check-in
            if (checkoutDate <= checkinDate) {
                showErrorMessage('Check-out date must be after check-in date. Please select a valid check-out date.', 'checkout');
                const checkoutInput = document.getElementById('modal_checkout');
                if (checkoutInput) checkoutInput.value = '';
                resetPriceDisplay();
                return;
            }
            
            // Validation 3: Maximum stay limit (30 days)
            const nights = Math.ceil((checkoutDate - checkinDate) / (1000 * 60 * 60 * 24));
            if (nights > 30) {
                showErrorMessage('Maximum stay is 30 nights. Please reduce your stay duration.', 'checkout');
                const checkoutInput = document.getElementById('modal_checkout');
                if (checkoutInput) checkoutInput.value = '';
                resetPriceDisplay();
                return;
            }
            
            // Validation 4: Minimum stay (1 night)
            if (nights < 1) {
                showErrorMessage('Minimum stay is 1 night.', 'checkout');
                const checkoutInput = document.getElementById('modal_checkout');
                if (checkoutInput) checkoutInput.value = '';
                resetPriceDisplay();
                return;
            }
            
            // All validations passed
            updatePrice(checkinDate, checkoutDate, nights);
        }
    }
    
    // Update price display
    function updatePrice(checkinDate, checkoutDate, nights) {
        const total = currentRoomPrice * nights;
        const deposit = total * 0.30;
        const remaining = total - deposit;
        
        const nightsDisplay = document.getElementById('display_nights');
        const totalDisplay = document.getElementById('display_total');
        const depositDisplay = document.getElementById('display_deposit');
        const remainingDisplay = document.getElementById('display_remaining');
        const totalHidden = document.getElementById('modal_total_payment');
        
        if (nightsDisplay) nightsDisplay.innerHTML = nights;
        if (totalDisplay) totalDisplay.innerHTML = total.toFixed(2);
        if (depositDisplay) depositDisplay.innerHTML = deposit.toFixed(2);
        if (remainingDisplay) remainingDisplay.innerHTML = remaining.toFixed(2);
        if (totalHidden) totalHidden.value = total.toFixed(2);
        
        // Show success message
        showSuccessMessage(nights, total);
    }
    
    // Reset price display
    function resetPriceDisplay() {
        const nightsDisplay = document.getElementById('display_nights');
        const totalDisplay = document.getElementById('display_total');
        const depositDisplay = document.getElementById('display_deposit');
        const remainingDisplay = document.getElementById('display_remaining');
        
        if (nightsDisplay) nightsDisplay.innerHTML = '0';
        if (totalDisplay) totalDisplay.innerHTML = '0';
        if (depositDisplay) depositDisplay.innerHTML = '0';
        if (remainingDisplay) remainingDisplay.innerHTML = '0';
    }
    
    // Show error message
    function showErrorMessage(message, fieldId) {
        const field = document.getElementById('modal_' + fieldId);
        if (!field) return;
        
        const parent = field.parentElement;
        const existingError = parent.querySelector('.error-message');
        if (existingError) existingError.remove();
        
        const errorDiv = document.createElement('div');
        errorDiv.className = 'error-message alert alert-danger alert-dismissible fade show mt-2';
        errorDiv.setAttribute('role', 'alert');
        errorDiv.style.fontSize = '0.9rem';
        errorDiv.style.padding = '8px 12px';
        errorDiv.innerHTML = `
            <i class="fas fa-exclamation-triangle me-2"></i>
            ${message}
            <button type="button" class="btn-close btn-sm" data-bs-dismiss="alert" style="font-size: 0.7rem;"></button>
        `;
        
        parent.appendChild(errorDiv);
        field.style.borderColor = '#dc3545';
        field.style.backgroundColor = '#fff8f8';
        
        field.onfocus = () => {
            field.style.borderColor = '';
            field.style.backgroundColor = '';
            const error = parent.querySelector('.error-message');
            if (error) error.remove();
        };
        
        setTimeout(() => {
            if (errorDiv.parentElement) {
                errorDiv.remove();
                field.style.borderColor = '';
                field.style.backgroundColor = '';
            }
        }, 5000);
    }
    
    // Show success message
    function showSuccessMessage(nights, total) {
        const existingSuccess = document.querySelectorAll('.success-message');
        existingSuccess.forEach(msg => msg.remove());
        
        const priceBreakdown = document.querySelector('.price-breakdown');
        if (!priceBreakdown) return;
        
        const successDiv = document.createElement('div');
        successDiv.className = 'success-message alert alert-success mt-3 mb-0';
        successDiv.style.fontSize = '0.9rem';
        successDiv.style.padding = '10px 15px';
        successDiv.innerHTML = `
            <i class="fas fa-check-circle me-2"></i>
            <strong>✓ Valid Dates!</strong> Your stay of ${nights} night(s) totals $${total.toFixed(2)}. 
            Please proceed with the 30% deposit to confirm your reservation.
        `;
        
        priceBreakdown.appendChild(successDiv);
        
        setTimeout(() => {
            if (successDiv.parentElement) successDiv.remove();
        }, 4000);
    }
    
    // Remove all error messages
    function removeErrorMessages() {
        const errors = document.querySelectorAll('.error-message');
        errors.forEach(error => error.remove());
        
        const checkinField = document.getElementById('modal_checkin');
        const checkoutField = document.getElementById('modal_checkout');
        
        if (checkinField) {
            checkinField.style.borderColor = '';
            checkinField.style.backgroundColor = '';
        }
        if (checkoutField) {
            checkoutField.style.borderColor = '';
            checkoutField.style.backgroundColor = '';
        }
    }
    
    // Form submission validation for booking
    const bookingForm = document.getElementById('bookingForm');
    if (bookingForm) {
        bookingForm.addEventListener('submit', function(e) {
            const checkin = document.getElementById('modal_checkin')?.value;
            const checkout = document.getElementById('modal_checkout')?.value;
            const guests = parseInt(document.getElementById('modal_guests')?.value || 1);
            
            removeErrorMessages();
            
            if (!checkin) {
                e.preventDefault();
                showErrorMessage('Please select a check-in date.', 'checkin');
                return false;
            }
            
            if (!checkout) {
                e.preventDefault();
                showErrorMessage('Please select a check-out date.', 'checkout');
                return false;
            }
            
            const checkinDate = new Date(checkin);
            const checkoutDate = new Date(checkout);
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            const tomorrow = new Date(today);
            tomorrow.setDate(today.getDate() + 1);
            
            if (checkinDate < tomorrow) {
                e.preventDefault();
                showErrorMessage('Check-in date must be at least tomorrow.', 'checkin');
                return false;
            }
            
            if (checkoutDate <= checkinDate) {
                e.preventDefault();
                showErrorMessage('Check-out date must be after check-in date.', 'checkout');
                return false;
            }
            
            if (guests > currentCapacity) {
                e.preventDefault();
                showErrorMessage(`Maximum capacity is ${currentCapacity} guest(s).`, 'guests');
                return false;
            }
            
            const nights = Math.ceil((checkoutDate - checkinDate) / (1000 * 60 * 60 * 24));
            const total = currentRoomPrice * nights;
            
            const confirmMessage = `Please confirm your booking details:\n\n` +
                `📅 Check-in: ${checkinDate.toLocaleDateString()}\n` +
                `📅 Check-out: ${checkoutDate.toLocaleDateString()}\n` +
                `🌙 Nights: ${nights}\n` +
                `👥 Guests: ${guests}\n` +
                `💰 Total: $${total.toFixed(2)}\n` +
                `💵 Deposit (30%): $${(total * 0.30).toFixed(2)}\n\n` +
                `Click OK to proceed with the deposit payment.`;
            
            if (!confirm(confirmMessage)) {
                e.preventDefault();
                return false;
            }
            
            return true;
        });
    }
    
    // Guest validation
    const guestsSelect = document.getElementById('modal_guests');
    if (guestsSelect) {
        guestsSelect.addEventListener('change', function() {
            const guests = parseInt(this.value);
            if (guests > currentCapacity) {
                showErrorMessage(`This room can only accommodate up to ${currentCapacity} guest(s).`, 'guests');
                this.value = currentCapacity;
            } else {
                const parent = this.parentElement;
                const existingError = parent.querySelector('.error-message');
                if (existingError) {
                    existingError.remove();
                    this.style.borderColor = '';
                    this.style.backgroundColor = '';
                }
            }
        });
    }
</script>
</body>

</html>