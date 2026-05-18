<?php
// ==========================================
// ADMIN DASHBOARD MAIN FILE
// File: admin/dashboard.php
// ==========================================
?>

<?php
session_start();
require_once '../config/database.php';
require_once '../classes/RoomManager.php';
require_once '../classes/BookingManager.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();

$roomManager = new RoomManager($db);
$bookingManager = new BookingManager($db);

// Handle room deletion
if (isset($_GET['delete_room'])) {
    $roomManager->deleteRoom($_GET['delete_room']);
    header("Location: dashboard.php?tab=rooms");
    exit();
}

// Handle room status update
if (isset($_POST['update_status'])) {
    $roomManager->updateRoomStatus($_POST['room_id'], $_POST['room_status']);
    header("Location: dashboard.php?tab=rooms");
    exit();
}

// Handle booking confirmation
if (isset($_POST['confirm_booking'])) {
    $bookingManager->updateBookingStatus($_POST['booking_id'], 'confirmed');
    header("Location: dashboard.php?tab=bookings");
    exit();
}

// Handle booking cancellation
if (isset($_POST['cancel_booking'])) {
    $bookingManager->updateBookingStatus($_POST['booking_id'], 'cancelled');
    header("Location: dashboard.php?tab=bookings");
    exit();
}

// Get current tab
$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'dashboard';
$rooms = $roomManager->getAllRooms();
$roomTypes = $roomManager->getRoomTypes();
$bookings = $bookingManager->getAllBookings();
$pendingBookings = $bookingManager->getBookingsByStatus('pending');
$confirmedBookings = $bookingManager->getBookingsByStatus('confirmed');
$stats = $bookingManager->getBookingStats();

// Calculate available rooms
$availableRooms = 0;
foreach ($rooms as $room) {
    if ($room['room_status'] == 'available') {
        $availableRooms++;
    }
}
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
    <link href="https://fonts.googleapis.com/css2?family=Kdam+Thmor+Pro&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Hanuman:wght@100;300;400;700;900&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Siemreap&display=swap" rel="stylesheet">
    
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <style>
        :root {
            --primary-dark: #543414;
            --secondary-light: #d5c0b5;
            --bg-light: #ffffff;
            --text-dark: #252424;
            --shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Siemreap', sans-serif;
            background-color: #f5f5f5;
            color: var(--text-dark);
        }

        /* Navbar Styles */
        .glass-navbar {
            background: rgba(255, 255, 255, 0.97);
            backdrop-filter: blur(10px);
            box-shadow: var(--shadow);
            padding: 1rem 0;
            transition: 0.3s ease;
        }

        .navbar-brand {
            font-size: 1.8rem;
            font-weight: bold;
            color: var(--primary-dark) !important;
        }

        .navbar-brand i {
            color: var(--primary-dark);
        }

        .nav-link {
            color: var(--text-dark) !important;
            margin: 0 0.5rem;
            font-weight: 500;
            transition: 0.3s ease;
        }

        .nav-link:hover {
            color: var(--primary-dark) !important;
            transform: translateY(-2px);
        }

        .nav-link.active {
            color: var(--primary-dark) !important;
            border-bottom: 2px solid var(--primary-dark);
        }

        .btn-logout {
            background: transparent;
            color: var(--text-dark) !important;
            border: 2px solid var(--text-dark);
            padding: 0.5rem 1.2rem !important;
            border-radius: 8px;
            font-weight: 600;
            transition: 0.3s ease;
        }

        .btn-logout:hover {
            background: var(--text-dark);
            color: white !important;
            transform: translateY(-2px);
        }

        .btn-gold {
            background: var(--primary-dark);
            color: white !important;
            border: none;
            padding: 10px 25px;
            font-weight: 600;
            transition: 0.3s ease;
            border-radius: 8px;
        }

        .btn-gold:hover {
            background: #3a2610;
            transform: translateY(-2px);
        }

        /* Admin Wrapper */
        .admin-wrapper {
            padding-top: 90px;
            min-height: 100vh;
        }

        /* Stat Cards */
        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: var(--shadow);
            margin-bottom: 20px;
            transition: transform 0.3s ease;
            border-left: 5px solid var(--primary-dark);
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0,0,0,0.15);
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            color: var(--primary-dark);
        }

        .stat-label {
            color: #666;
            font-size: 0.9rem;
            margin-top: 5px;
        }

        .stat-icon {
            font-size: 3rem;
            color: var(--secondary-light);
        }

        /* Data Table */
        .data-table {
            background: white;
            border-radius: 15px;
            box-shadow: var(--shadow);
            overflow: hidden;
            margin-bottom: 30px;
        }

        .data-table .table {
            margin-bottom: 0;
        }

        .data-table thead th {
            background: var(--secondary-light);
            color: var(--primary-dark);
            border-bottom: none;
            padding: 15px;
            font-weight: 600;
        }

        .data-table tbody tr:hover {
            background: #f9f9f9;
        }

        /* Badges */
        .badge-status {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
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

        /* Buttons */
        .btn-action {
            margin: 0 3px;
            padding: 5px 12px;
            border-radius: 6px;
        }

        /* Welcome Banner */
        .welcome-banner {
            background: linear-gradient(135deg, var(--secondary-light) 0%, #f0e6e0 100%);
            padding: 20px 25px;
            border-radius: 15px;
            margin-top: 90px;
            margin-bottom: 30px;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .navbar-brand {
                font-size: 1.2rem;
            }
            
            .nav-link {
                margin: 0 0.2rem;
                font-size: 0.9rem;
            }
            
            .stat-number {
                font-size: 1.8rem;
            }
            
            .stat-icon {
                font-size: 2rem;
            }
        }
        
        /* Modal Styles */
        .room-image-preview {
            max-width: 100%;
            height: auto;
            border-radius: 10px;
            margin-top: 10px;
        }
        
        .current-image {
            max-width: 150px;
            border-radius: 8px;
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg glass-navbar fixed-top">
        <div class="container">
        <a class="navbar-brand d-flex align-items-center" 
            href="dashboard.php?tab=dashboard"
            style="font-family: 'Siemreap', sans-serif;">

               
                <span>សណ្ឋាគារបាយ័ន</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center">
                    <li class="nav-item">
                        <a class="nav-link <?php echo $active_tab == 'dashboard' ? 'active' : ''; ?>" href="dashboard.php?tab=dashboard">
                            <i class="fas fa-tachometer-alt"></i> ផ្ទាំងគ្រប់គ្រង
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $active_tab == 'rooms' ? 'active' : ''; ?>" href="dashboard.php?tab=rooms">
                            <i class="fas fa-bed"></i> គ្រប់គ្រង់បន្ទប់
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $active_tab == 'bookings' ? 'active' : ''; ?>" href="dashboard.php?tab=bookings">
                            <i class="fas fa-calendar-check"></i> គ្រប់គ្រងការកក់
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../index.php">
                            <i class="fas fa-home"></i> មើលទំព័រដើម
                        </a>
                    </li>
                    <li class="nav-item ms-2">
                        <a class="btn btn-logout" href="../logout.php">
                            <i class="fas fa-sign-out-alt"></i> ចាកចេញ
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="admin-wrapper">
        <div class="container">
            <!-- Welcome Banner -->
            <div class="welcome-banner">
                <div class="d-flex justify-content-between align-items-center flex-wrap">
                    <div>
                        <h4><i class="fas fa-user-shield"></i> សូមស្វាគមន៍, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h4>
                        <p class="mb-0">គ្រប់គ្រងបន្ទប់សណ្ឋាគាររបស់អ្នក ការកក់ និងតាមដានសកម្មភាពទាំងអស់ពីទីនេះ។</p>
                    </div>
                    <div class="mt-2 mt-sm-0">
                        <span class="badge bg-dark p-2">
                            <i class="fas fa-calendar"></i> <?php echo date('F j, Y'); ?>
                        </span>
                    </div>
                </div>
            </div>

            <?php if($active_tab == 'dashboard'): ?>
            <!-- Dashboard Tab -->
            <div class="row">
                <div class="col-md-3 col-sm-6">
                    <div class="stat-card">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="stat-number"><?php echo count($rooms); ?></div>
                                <div class="stat-label">សរុបបន្ទប់</div>
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
                                <div class="stat-label">បន្ទប់ទំនេរ</div>
                            </div>
                            <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="stat-card">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="stat-number"><?php echo $stats['total']; ?></div>
                                <div class="stat-label">សរុបការកក់</div>
                            </div>
                            <div class="stat-icon"><i class="fas fa-calendar-check"></i></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="stat-card">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="stat-number"><?php echo $stats['pending']; ?></div>
                                <div class="stat-label">កំពុងរង់ចាំ</div>
                            </div>
                            <div class="stat-icon"><i class="fas fa-clock"></i></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Bookings -->
            <div class="data-table">
                <div class="p-3 bg-white border-bottom">
                    <h5 class="mb-0"><i class="fas fa-receipt"></i> បញ្ជីការកក់ថ្មីៗ</h5>
                </div>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>លេខសម្គាល់</th>
                                <th>ភ្ញៀវ</th>
                                <th>បន្ទប់</th>
                                <th>ថ្ងៃចូល</th>
                                <th>ថ្ងៃចេញ</th>
                                <th>តម្លៃសរុប</th>
                                <th>ស្ថានភាព</th>
                            
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $recentBookings = array_slice($bookings, 0, 10);
                            if(empty($recentBookings)): 
                            ?>
                            <tr>
                                <td colspan="8" class="text-center">មិនមានការកក់</td>
                            </tr>
                            <?php else: ?>
                                <?php foreach($recentBookings as $booking): ?>
                                <tr>
                                    <td>#<?php echo $booking['booking_id']; ?></td>
                                    <td><?php echo htmlspecialchars($booking['user_name']); ?></td>
                                    <td><?php echo htmlspecialchars($booking['room_number']); ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($booking['checkin_date'])); ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($booking['checkout_date'])); ?></td>
                                    <td>$<?php echo number_format($booking['total_payment'], 2); ?></td>
                                    <td>
                                        <span class="badge-status <?php echo $booking['booking_status'] == 'pending' ? 'badge-pending' : ($booking['booking_status'] == 'confirmed' ? 'badge-confirmed' : 'badge-cancelled'); ?>">
                                            <?php echo $booking['booking_status'] == 'pending' ? 'កំពុងរង់ចាំ' : ($booking['booking_status'] == 'confirmed' ? 'បានបញ្ជាក់' : 'បានបោះបង់'); ?>
                                        </span>
                                    </td>
                                    <td>
                                        
                                     </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>

            <?php if($active_tab == 'rooms'): ?>
            <!-- Manage Rooms Tab -->
            <div class="mb-3">
                <button class="btn btn-gold" data-bs-toggle="modal" data-bs-target="#addRoomModal">
                    <i class="fas fa-plus"></i> បន្ថែមបន្ទប់ថ្មី
                </button>
            </div>

            <div class="data-table">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>លេខសម្គាល់</th>
                                <th>លេខបន្ទប់</th>
                                <th>ប្រភេទ</th>
                                <th>តម្លៃ/យប់</th>
                                <th>សមត្ថភាព</th>
                                <th>ស្ថានភាព</th>
                                <th>សកម្មភាព</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($rooms as $room): ?>
                            <tr id="room-row-<?php echo $room['room_id']; ?>">
                                <td><?php echo $room['room_id']; ?></td>
                                <td><strong><?php echo htmlspecialchars($room['room_number']); ?></strong></td>
                                <td><?php echo htmlspecialchars($room['type_name']); ?></div>
                                <td>$<?php echo number_format($room['price_per_night'], 2); ?></div>
                                <td><i class="fas fa-user"></i> <?php echo $room['capacity']; ?> នាក់</div>
                                <td>
                                    <select name="room_status" class="form-select form-select-sm status-select" data-id="<?php echo $room['room_id']; ?>" style="width: auto; display: inline-block;">
                                        <option value="available" <?php echo $room['room_status'] == 'available' ? 'selected' : ''; ?>>ទំនេរ</option>
                                        <option value="booked" <?php echo $room['room_status'] == 'booked' ? 'selected' : ''; ?>>បានកក់</option>
                                        <option value="maintenance" <?php echo $room['room_status'] == 'maintenance' ? 'selected' : ''; ?>>ថែទាំ</option>
                                    </select>
                                 </div>
                                <td>
                                    <button class="btn btn-sm btn-info btn-action view-room" data-id="<?php echo $room['room_id']; ?>">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="btn btn-sm btn-warning btn-action edit-room" data-id="<?php echo $room['room_id']; ?>">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-sm btn-danger btn-action delete-room" data-id="<?php echo $room['room_id']; ?>">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                 </div>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Add Room Modal -->
            <div class="modal fade" id="addRoomModal" tabindex="-1">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title"><i class="fas fa-plus-circle"></i> បន្ថែមបន្ទប់ថ្មី</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <form id="addRoomForm" enctype="multipart/form-data">
                            <div class="modal-body">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">លេខបន្ទប់ *</label>
                                        <input type="text" name="room_number" class="form-control" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">ប្រភេទបន្ទប់ *</label>
                                        <select name="room_type_id" class="form-select" required>
                                            <option value="">ជ្រើសរើសប្រភេទ</option>
                                            <?php foreach($roomTypes as $type): ?>
                                                <option value="<?php echo $type['room_types_id']; ?>"><?php echo htmlspecialchars($type['type_name']); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">តម្លៃក្នុងមួយយប់ ($) *</label>
                                        <input type="number" step="0.01" name="price_per_night" class="form-control" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">សមត្ថភាព (នាក់) *</label>
                                        <input type="number" name="capacity" class="form-control" required>
                                    </div>
                                    <div class="col-12 mb-3">
                                        <label class="form-label">គ្រឿងបរិក្ខារ</label>
                                        <textarea name="equipments" class="form-control" rows="2" placeholder="ទូរទស្សន៍, Wifi, ម៉ាស៊ីនត្រជាក់, ទូទឹកកក..."></textarea>
                                    </div>
                                    <div class="col-12 mb-3">
                                        <label class="form-label">ការពិពណ៌នាបន្ទប់</label>
                                        <textarea name="room_description" class="form-control" rows="3"></textarea>
                                    </div>
                                    <div class="col-12 mb-3">
                                        <label class="form-label">រូបភាពបន្ទប់</label>
                                        <input type="file" name="room_image" class="form-control" accept="image/*">
                                        <small class="text-muted">អាចទទួលយកបាន៖ JPG, JPEG, PNG, GIF (អតិបរមា 5MB)</small>
                                        <div id="imagePreview" class="mt-2"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">បោះបង់</button>
                                <button type="submit" class="btn btn-gold">បន្ថែមបន្ទប់</button>
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
                            <h5 class="modal-title"><i class="fas fa-info-circle"></i> ព័ត៌មានលម្អិតបន្ទប់</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body" id="viewRoomContent">
                            <!-- Dynamic content -->
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">បិទ</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Edit Room Modal -->
            <div class="modal fade" id="editRoomModal" tabindex="-1">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title"><i class="fas fa-edit"></i> កែប្រែព័ត៌មានបន្ទប់</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <form id="editRoomForm" enctype="multipart/form-data">
                            <input type="hidden" name="room_id" id="edit_room_id">
                            <div class="modal-body">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">លេខបន្ទប់ *</label>
                                        <input type="text" name="room_number" id="edit_room_number" class="form-control" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">ប្រភេទបន្ទប់ *</label>
                                        <select name="room_type_id" id="edit_room_type_id" class="form-select" required>
                                            <option value="">ជ្រើសរើសប្រភេទ</option>
                                            <?php foreach($roomTypes as $type): ?>
                                                <option value="<?php echo $type['room_types_id']; ?>"><?php echo htmlspecialchars($type['type_name']); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">តម្លៃក្នុងមួយយប់ ($) *</label>
                                        <input type="number" step="0.01" name="price_per_night" id="edit_price_per_night" class="form-control" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">សមត្ថភាព (នាក់) *</label>
                                        <input type="number" name="capacity" id="edit_capacity" class="form-control" required>
                                    </div>
                                    <div class="col-12 mb-3">
                                        <label class="form-label">គ្រឿងបរិក្ខារ</label>
                                        <textarea name="equipments" id="edit_equipments" class="form-control" rows="2"></textarea>
                                    </div>
                                    <div class="col-12 mb-3">
                                        <label class="form-label">ការពិពណ៌នាបន្ទប់</label>
                                        <textarea name="room_description" id="edit_room_description" class="form-control" rows="3"></textarea>
                                    </div>
                                    <div class="col-12 mb-3">
                                        <label class="form-label">រូបភាពបច្ចុប្បន្ន</label>
                                        <div id="current_image_container"></div>
                                        <label class="form-label mt-2">ផ្លាស់ប្តូររូបភាព</label>
                                        <input type="file" name="room_image" class="form-control" accept="image/*">
                                        <small class="text-muted">ទុកចោលប្រសិនបើមិនចង់ផ្លាស់ប្តូររូបភាព</small>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">បោះបង់</button>
                                <button type="submit" class="btn btn-gold">រក្សាទុកការផ្លាស់ប្តូរ</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <?php if($active_tab == 'bookings'): ?>
            <!-- Manage Bookings Tab -->
            <div class="row mb-4">
                <div class="col-md-4">
                    <select class="form-select" id="bookingFilter">
                        <option value="all">ការកក់ទាំងអស់</option>
                        <option value="pending">កំពុងរង់ចាំ</option>
                        <option value="confirmed">បានបញ្ជាក់</option>
                        <option value="cancelled">បានបោះបង់</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <input type="text" id="searchBooking" class="form-control" placeholder="ស្វែងរកតាមឈ្មោះភ្ញៀវ...">
                </div>
            </div>

            <div class="data-table">
                <div class="table-responsive">
                    <table class="table" id="bookingsTable">
                        <thead>
                            <tr>
                                <th>លេខសម្គាល់</th>
                                <th>ឈ្មោះភ្ញៀវ</th>
                                <th>បន្ទប់</th>
                                <th>ថ្ងៃចូល</th>
                                <th>ថ្ងៃចេញ</th>
                                <th>តម្លៃសរុប</th>
                                <th>ស្ថានភាពកក់</th>
                                <th>ស្ថានភាពបង់ប្រាក់</th>
                                <th>សកម្មភាព</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($bookings as $booking): ?>
                            <tr data-status="<?php echo $booking['booking_status']; ?>">
                                <td>#<?php echo $booking['booking_id']; ?></div>
                                <td><?php echo htmlspecialchars($booking['user_name']); ?></div>
                                <td><?php echo htmlspecialchars($booking['room_number']); ?></div>
                                <td><?php echo date('d/m/Y', strtotime($booking['checkin_date'])); ?></div>
                                <td><?php echo date('d/m/Y', strtotime($booking['checkout_date'])); ?></div>
                                <td>$<?php echo number_format($booking['total_payment'], 2); ?></div>
                                <td>
                                    <span class="badge-status <?php echo $booking['booking_status'] == 'pending' ? 'badge-pending' : ($booking['booking_status'] == 'confirmed' ? 'badge-confirmed' : 'badge-cancelled'); ?>">
                                        <?php echo $booking['booking_status'] == 'pending' ? 'កំពុងរង់ចាំ' : ($booking['booking_status'] == 'confirmed' ? 'បានបញ្ជាក់' : 'បានបោះបង់'); ?>
                                    </span>
                                 </div>
                                <td>
                                    <span class="badge-status <?php echo $booking['payment_status'] == 'paid' ? 'badge-confirmed' : 'badge-pending'; ?>">
                                        <?php echo $booking['payment_status'] == 'paid' ? 'បានបង់ប្រាក់' : 'មិនទាន់បង់'; ?>
                                    </span>
                                 </div>
                                <td>
                                    <button class="btn btn-sm btn-info btn-action view-booking-details" data-id="<?php echo $booking['booking_id']; ?>">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <?php if($booking['booking_status'] == 'pending'): ?>
                                    <button class="btn btn-sm btn-success btn-action confirm-booking-btn" data-id="<?php echo $booking['booking_id']; ?>">
                                        <i class="fas fa-check"></i>
                                    </button>
                                    <?php endif; ?>
                                    <?php if($booking['booking_status'] == 'pending' || $booking['booking_status'] == 'confirmed'): ?>
                                    <button class="btn btn-sm btn-danger btn-action cancel-booking-btn" data-id="<?php echo $booking['booking_id']; ?>">
                                        <i class="fas fa-times"></i>
                                    </button>
                                    <?php endif; ?>
                                    <?php if($booking['payment_status'] == 'unpaid' && $booking['booking_status'] == 'confirmed'): ?>
                                    <button class="btn btn-sm btn-warning btn-action update-payment-btn" data-id="<?php echo $booking['booking_id']; ?>">
                                        <i class="fas fa-money-bill"></i>
                                    </button>
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
                            <h5 class="modal-title"><i class="fas fa-receipt"></i> ព័ត៌មានលម្អិតការកក់</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body" id="bookingDetailsContent">
                            <!-- Dynamic content -->
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">បិទ</button>
                            <button type="button" class="btn btn-gold" id="printBooking">បោះពុម្ពវិក្កយបត្រ</button>
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
        // Add Room with AJAX - Updated version
        document.getElementById('addRoomForm')?.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            // Validate required fields
            const roomNumber = this.querySelector('[name="room_number"]').value.trim();
            const roomType = this.querySelector('[name="room_type_id"]').value;
            const price = this.querySelector('[name="price_per_night"]').value;
            const capacity = this.querySelector('[name="capacity"]').value;
            
            if (!roomNumber) {
                Swal.fire('កំហុស!', 'សូមបញ្ចូលលេខបន្ទប់', 'error');
                return;
            }
            if (!roomType) {
                Swal.fire('កំហុស!', 'សូមជ្រើសរើសប្រភេទបន្ទប់', 'error');
                return;
            }
            if (!price || price <= 0) {
                Swal.fire('កំហុស!', 'សូមបញ្ចូលតម្លៃត្រឹមត្រូវ', 'error');
                return;
            }
            if (!capacity || capacity <= 0) {
                Swal.fire('កំហុស!', 'សូមបញ្ចូលសមត្ថភាពបន្ទប់', 'error');
                return;
            }
            
            const result = await Swal.fire({
                title: 'បញ្ជាក់ការបន្ថែម',
                text: 'តើអ្នកពិតជាចង់បន្ថែមបន្ទប់ថ្មីមែនទេ?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#543414',
                cancelButtonColor: '#d33',
                confirmButtonText: 'បាទ/ចាស',
                cancelButtonText: 'បោះបង់'
            });
            
            if (result.isConfirmed) {
                Swal.fire({
                    title: 'កំពុងដំណើរការ...',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });
                
                const formData = new FormData(this);
                formData.append('action', 'add_room');
                
                try {
                    const response = await fetch('process_room.php', {
                        method: 'POST',
                        body: formData
                    });
                    const data = await response.json();
                    
                    if (data.success) {
                        Swal.fire('ជោគជ័យ!', data.message, 'success').then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire('កំហុស!', data.message, 'error');
                    }
                } catch (error) {
                    console.error('Error:', error);
                    Swal.fire('កំហុស!', 'មានបញ្ហាក្នុងការបន្ថែមបន្ទប់', 'error');
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
                        const modalContent = `
                            <div class="row">
                                <div class="col-md-6">
                                    ${room.room_image ? `<img src="../${room.room_image}" class="img-fluid rounded mb-3" alt="Room Image">` : '<div class="alert alert-secondary">គ្មានរូបភាព</div>'}
                                </div>
                                <div class="col-md-6">
                                    <table class="table table-bordered">
                                        <tr><th>លេខបន្ទប់:</th><td>${room.room_number}</td></tr>
                                        <tr><th>ប្រភេទ:</th><td>${room.type_name}</td></tr>
                                        <tr><th>តម្លៃក្នុងមួយយប់:</th><td>$${parseFloat(room.price_per_night).toFixed(2)}</td></tr>
                                        <tr><th>សមត្ថភាព:</th><td>${room.capacity} នាក់</td></tr>
                                        <tr><th>ស្ថានភាព:</th><td>${room.room_status == 'available' ? 'ទំនេរ' : (room.room_status == 'booked' ? 'បានកក់' : 'ថែទាំ')}</td></tr>
                                        <tr><th>គ្រឿងបរិក្ខារ:</th><td>${room.equipments || 'មិនមាន'}</td></tr>
                                        <tr><th>ការពិពណ៌នា:</th><td>${room.room_description || 'មិនមាន'}</td></tr>
                                    </table>
                                </div>
                            </div>
                        `;
                        document.getElementById('viewRoomContent').innerHTML = modalContent;
                        new bootstrap.Modal(document.getElementById('viewRoomModal')).show();
                    }
                } catch (error) {
                    Swal.fire('កំហុស!', 'មិនអាចទាញយកព័ត៌មានបន្ទប់បាន', 'error');
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
                            imgContainer.innerHTML = '<p class="text-muted">គ្មានរូបភាព</p>';
                        }
                        
                        new bootstrap.Modal(document.getElementById('editRoomModal')).show();
                    }
                } catch (error) {
                    Swal.fire('កំហុស!', 'មិនអាចទាញយកព័ត៌មានបន្ទប់បាន', 'error');
                }
            });
        });
        
        // Edit Room - Submit
        document.getElementById('editRoomForm')?.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const result = await Swal.fire({
                title: 'បញ្ជាក់ការកែប្រែ',
                text: 'តើអ្នកពិតជាចង់កែប្រែព័ត៌មានបន្ទប់នេះមែនទេ?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#543414',
                cancelButtonColor: '#d33',
                confirmButtonText: 'បាទ/ចាស',
                cancelButtonText: 'បោះបង់'
            });
            
            if (result.isConfirmed) {
                const formData = new FormData(this);
                formData.append('action', 'update_room');
                
                try {
                    const response = await fetch('process_room.php', {
                        method: 'POST',
                        body: formData
                    });
                    const data = await response.json();
                    
                    if (data.success) {
                        Swal.fire('ជោគជ័យ!', data.message, 'success').then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire('កំហុស!', data.message, 'error');
                    }
                } catch (error) {
                    Swal.fire('កំហុស!', 'មានបញ្ហាក្នុងការកែប្រែបន្ទប់', 'error');
                }
            }
        });
        
        // Update Room Status with confirmation
        // Update Room Status with confirmation - Updated version
            document.querySelectorAll('.status-select').forEach(select => {
                select.addEventListener('change', async function() {
                    const roomId = this.dataset.id;
                    const newStatus = this.value;
                    const statusText = newStatus === 'available' ? 'ទំនេរ' : (newStatus === 'booked' ? 'បានកក់' : 'ថែទាំ');
                    
                    const result = await Swal.fire({
                        title: 'បញ្ជាក់ការផ្លាស់ប្តូរ',
                        text: `តើអ្នកចង់ប្តូរស្ថានភាពបន្ទប់ទៅជា "${statusText}" មែនទេ?`,
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonColor: '#543414',
                        cancelButtonColor: '#d33',
                        confirmButtonText: 'បាទ/ចាស',
                        cancelButtonText: 'បោះបង់'
                    });
                    
                    if (result.isConfirmed) {
                        Swal.fire({
                            title: 'កំពុងដំណើរការ...',
                            allowOutsideClick: false,
                            didOpen: () => {
                                Swal.showLoading();
                            }
                        });
                        
                        try {
                            const response = await fetch('process_room.php', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/x-www-form-urlencoded',
                                },
                                body: `action=update_status&room_id=${roomId}&room_status=${newStatus}`
                            });
                            const data = await response.json();
                            
                            Swal.close();
                            
                            if (data.success) {
                                Swal.fire('ជោគជ័យ!', data.message, 'success');
                            } else {
                                Swal.fire('កំហុស!', data.message, 'error');
                                // Reload to revert select value
                                location.reload();
                            }
                        } catch (error) {
                            Swal.close();
                            Swal.fire('កំហុស!', 'មានបញ្ហាក្នុងការផ្លាស់ប្តូរស្ថានភាព', 'error');
                            location.reload();
                        }
                    } else {
                        // Reload to revert select value
                        location.reload();
                    }
                });
            });

        // Delete Room with confirmation
        document.querySelectorAll('.delete-room').forEach(btn => {
            btn.addEventListener('click', async function() {
                const roomId = this.dataset.id;
                
                const result = await Swal.fire({
                    title: 'បញ្ជាក់ការលុប',
                    text: 'តើអ្នកពិតជាចង់លុបបន្ទប់នេះមែនទេ? សកម្មភាពនេះមិនអាចត្រឡប់វិញបានទេ!',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'បាទ/ចាស, លុប!',
                    cancelButtonText: 'បោះបង់'
                });
                
                if (result.isConfirmed) {
                    try {
                        const response = await fetch('process_room.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                            },
                            body: `action=delete_room&room_id=${roomId}`
                        });
                        const data = await response.json();
                        
                        if (data.success) {
                            Swal.fire('លុបដោយជោគជ័យ!', data.message, 'success').then(() => {
                                document.getElementById(`room-row-${roomId}`)?.remove();
                            });
                        } else {
                            Swal.fire('កំហុស!', data.message, 'error');
                        }
                    } catch (error) {
                        Swal.fire('កំហុស!', 'មានបញ្ហាក្នុងការលុបបន្ទប់', 'error');
                    }
                }
            });
        });
        
        // ============================================
        // BOOKING MANAGEMENT FUNCTIONS
        // ============================================
        
        // Booking Filter
        const filterSelect = document.getElementById('bookingFilter');
        const searchInput = document.getElementById('searchBooking');
        const tableRows = document.querySelectorAll('#bookingsTable tbody tr');
        
        if(filterSelect) {
            filterSelect.addEventListener('change', function() {
                const filter = this.value;
                tableRows.forEach(row => {
                    if(filter === 'all' || row.dataset.status === filter) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            });
        }
        
        if(searchInput) {
            searchInput.addEventListener('keyup', function() {
                const search = this.value.toLowerCase();
                tableRows.forEach(row => {
                    const guestName = row.cells[1].innerText.toLowerCase();
                    if(guestName.includes(search)) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            });
        }
        
        // View Booking Details
        document.querySelectorAll('.view-booking-details').forEach(btn => {
            btn.addEventListener('click', async function() {
                const bookingId = this.dataset.id;
                
                try {
                    const response = await fetch(`get_booking_details.php?id=${bookingId}`);
                    const booking = await response.json();
                    
                    if (booking) {
                        const modalContent = `
                            <div class="row">
                                <div class="col-md-6">
                                    <table class="table table-bordered">
                                        <tr><th>លេខសម្គាល់កក់:</th><td>#${booking.booking_id}</td></tr>
                                        <tr><th>ឈ្មោះភ្ញៀវ:</th><td>${booking.user_name}</td></tr>
                                        <tr><th>អ៊ីមែល:</th><td>${booking.email || 'មិនមាន'}</td></tr>
                                        <tr><th>លេខទូរស័ព្ទ:</th><td>${booking.phone_number || 'មិនមាន'}</td></tr>
                                        <tr><th>លេខបន្ទប់:</th><td>${booking.room_number}</td></tr>
                                        <tr><th>ប្រភេទបន្ទប់:</th><td>${booking.type_name}</td></tr>
                                    </table>
                                </div>
                                <div class="col-md-6">
                                    <table class="table table-bordered">
                                        <tr><th>ថ្ងៃចូល:</th><td>${new Date(booking.checkin_date).toLocaleDateString()}</td></tr>
                                        <tr><th>ថ្ងៃចេញ:</th><td>${new Date(booking.checkout_date).toLocaleDateString()}</td></tr>
                                        <tr><th>ចំនួនយប់:</th><td>${booking.nights} យប់</td></tr>
                                        <tr><th>តម្លៃសរុប:</th><td>$${parseFloat(booking.total_payment).toFixed(2)}</td></tr>
                                        <tr><th>ស្ថានភាពកក់:</th><td>${booking.booking_status == 'pending' ? 'កំពុងរង់ចាំ' : (booking.booking_status == 'confirmed' ? 'បានបញ្ជាក់' : 'បានបោះបង់')}</td></tr>
                                        <tr><th>ស្ថានភាពបង់ប្រាក់:</th><td>${booking.payment_status == 'paid' ? 'បានបង់ប្រាក់' : 'មិនទាន់បង់'}</td></tr>
                                        <tr><th>ថ្ងៃកក់:</th><td>${new Date(booking.booking_at).toLocaleString()}</td></tr>
                                    </table>
                                </div>
                            </div>
                        `;
                        document.getElementById('bookingDetailsContent').innerHTML = modalContent;
                        window.currentBookingData = booking;
                        new bootstrap.Modal(document.getElementById('bookingDetailsModal')).show();
                    }
                } catch (error) {
                    Swal.fire('កំហុស!', 'មិនអាចទាញយកព័ត៌មានការកក់បាន', 'error');
                }
            });
        });
        
        // Confirm Booking with confirmation
        document.querySelectorAll('.confirm-booking-btn').forEach(btn => {
            btn.addEventListener('click', async function() {
                const bookingId = this.dataset.id;
                
                const result = await Swal.fire({
                    title: 'បញ្ជាក់ការកក់',
                    text: 'តើអ្នកពិតជាចង់បញ្ជាក់ការកក់នេះមែនទេ?',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#28a745',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'បាទ/ចាស, បញ្ជាក់!',
                    cancelButtonText: 'បោះបង់'
                });
                
                if (result.isConfirmed) {
                    try {
                        const response = await fetch('process_booking.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                            },
                            body: `action=confirm_booking&booking_id=${bookingId}`
                        });
                        const data = await response.json();
                        
                        if (data.success) {
                            Swal.fire('ជោគជ័យ!', data.message, 'success').then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire('កំហុស!', data.message, 'error');
                        }
                    } catch (error) {
                        Swal.fire('កំហុស!', 'មានបញ្ហាក្នុងការបញ្ជាក់ការកក់', 'error');
                    }
                }
            });
        });
        
        // Cancel Booking with confirmation
        document.querySelectorAll('.cancel-booking-btn').forEach(btn => {
            btn.addEventListener('click', async function() {
                const bookingId = this.dataset.id;
                
                const result = await Swal.fire({
                    title: 'បោះបង់ការកក់',
                    text: 'តើអ្នកពិតជាចង់បោះបង់ការកក់នេះមែនទេ?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'បាទ/ចាស, បោះបង់!',
                    cancelButtonText: 'មិនបោះបង់'
                });
                
                if (result.isConfirmed) {
                    try {
                        const response = await fetch('process_booking.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                            },
                            body: `action=cancel_booking&booking_id=${bookingId}`
                        });
                        const data = await response.json();
                        
                        if (data.success) {
                            Swal.fire('បោះបង់ដោយជោគជ័យ!', data.message, 'success').then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire('កំហុស!', data.message, 'error');
                        }
                    } catch (error) {
                        Swal.fire('កំហុស!', 'មានបញ្ហាក្នុងការបោះបង់ការកក់', 'error');
                    }
                }
            });
        });
        
        // Update Payment Status with confirmation
        document.querySelectorAll('.update-payment-btn').forEach(btn => {
            btn.addEventListener('click', async function() {
                const bookingId = this.dataset.id;
                
                const result = await Swal.fire({
                    title: 'បញ្ជាក់ការបង់ប្រាក់',
                    text: 'តើអ្នកប្រាកដថាភ្ញៀវបានបង់ប្រាក់រួចរាល់មែនទេ?',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#ffc107',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'បាទ/ចាស, បានបង់ប្រាក់!',
                    cancelButtonText: 'មិនទាន់'
                });
                
                if (result.isConfirmed) {
                    try {
                        const response = await fetch('process_booking.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                            },
                            body: `action=update_payment&booking_id=${bookingId}&payment_status=paid`
                        });
                        const data = await response.json();
                        
                        if (data.success) {
                            Swal.fire('ជោគជ័យ!', data.message, 'success').then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire('កំហុស!', data.message, 'error');
                        }
                    } catch (error) {
                        Swal.fire('កំហុស!', 'មានបញ្ហាក្នុងការកំណត់ស្ថានភាពបង់ប្រាក់', 'error');
                    }
                }
            });
        });
        
        // Print Invoice
        document.getElementById('printBooking')?.addEventListener('click', function() {
            if (window.currentBookingData) {
                const booking = window.currentBookingData;
                const printWindow = window.open('', '_blank');
                printWindow.document.write(`
                    <html>
                    <head>
                        <title>Invoice - Booking #${booking.booking_id}</title>
                        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
                        <style>
                            body { padding: 50px; font-family: 'Siemreap', Arial, sans-serif; }
                            .invoice-header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #543414; padding-bottom: 20px; }
                            .invoice-footer { text-align: center; margin-top: 50px; border-top: 1px solid #ddd; padding-top: 20px; }
                            .hotel-name { color: #543414; font-size: 28px; font-weight: bold; }
                            .invoice-title { font-size: 24px; margin-top: 20px; }
                            table { width: 100%; margin-top: 20px; }
                            th, td { padding: 10px; border: 1px solid #ddd; }
                            th { background: #d5c0b5; }
                        </style>
                    </head>
                    <body>
                        <div class="invoice-header">
                            <img src="../bayon_logo.png" 
                            alt="Bayon Hotel Logo" 
                            style="width: 100px; margin-right: 10px;">
                            <div class="hotel-name">សណ្ឋាគារបាយ័ន</div>
                            <p>សៀមរាប, កម្ពុជា</p>
                            <div class="invoice-title">វិក្កយបត្រកក់បន្ទប់</div>
                        </div>
                        
                        <div class="row">
                            <div class="col-6">
                                <strong>ព័ត៌មានកក់:</strong><br>
                                លេខសម្គាល់កក់: #${booking.booking_id}<br>
                                ថ្ងៃកក់: ${new Date(booking.booking_at).toLocaleString()}
                            </div>
                            <div class="col-6 text-end">
                                <strong>ស្ថានភាព:</strong><br>
                                <span class="badge ${booking.booking_status === 'confirmed' ? 'bg-success' : 'bg-warning'}">${booking.booking_status === 'confirmed' ? 'បានបញ្ជាក់' : 'កំពុងរង់ចាំ'}</span>
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
                            <tr><th>លេខបន្ទប់</th><td>${booking.room_number}</td></tr>
                            <tr><th>ប្រភេទបន្ទប់</th><td>${booking.type_name}</td></tr>
                            <tr><th>ថ្ងៃចូល</th><td>${new Date(booking.checkin_date).toLocaleDateString()}</td></tr>
                            <tr><th>ថ្ងៃចេញ</th><td>${new Date(booking.checkout_date).toLocaleDateString()}</td></tr>
                            <tr><th>ចំនួនយប់</th><td>${booking.nights} យប់</td></tr>
                        </table>
                        
                        <h5 class="mt-4">សេចក្តីសង្ខេបនៃការបង់ប្រាក់</h5>
                        <table class="table table-bordered">
                            <tr><th>តម្លៃក្នុងមួយយប់</th><td>$${parseFloat(booking.price_per_night).toFixed(2)}</td></tr>
                            <tr><th>ចំនួនយប់</th><td>${booking.nights} យប់</td></tr>
                            <tr style="background: #f0f0f0;"><th>តម្លៃសរុប</th><td><strong>$${parseFloat(booking.total_payment).toFixed(2)}</strong></td></tr>
                            <tr><th>ស្ថានភាពបង់ប្រាក់</th><td>${booking.payment_status === 'paid' ? 'បានបង់ប្រាក់រួច' : 'មិនទាន់បង់ប្រាក់'}</td></tr>
                        </table>
                        
                        <div class="invoice-footer">
                            <p>សូមអរគុណសម្រាប់ការជឿទុកចិត្តលើសណ្ឋាគារបាយ័ន!</p>
                            <p>ទំនាក់ទំនង: 012 345 678 | Email: info@bayonbooking.com</p>
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
    </script>
</body>
</html>