<?php
// ==========================================
// HEADER INCLUDES
// File: includes/header.php
// ==========================================
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BayonBooking - Luxury Hotel Booking</title>

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Khmer Font -->
    <link href="https://fonts.googleapis.com/css2?family=Kdam+Thmor+Pro&display=swap" rel="stylesheet">

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/style.css">
    
    <style>
        /* Admin button special styling */
        .btn-admin-nav {
            background: #543414;
            color: white !important;
            border: none;
            font-weight: 600;
            transition: 0.3s ease;
            padding: 0.5rem 1.2rem !important;
            border-radius: 8px;
            margin-left: 10px;
        }
        
        .btn-admin-nav:hover {
            background: #3a2610;
            transform: translateY(-2px);
        }
        
        .btn-admin-nav i {
            margin-right: 5px;
        }
        
        /* Admin badge for username */
        .admin-user-badge {
            background: #543414;
            color: white;
            padding: 2px 8px;
            border-radius: 20px;
            font-size: 11px;
            margin-left: 8px;
            display: inline-block;
        }
        
        /* My Bookings button */
        .btn-my-bookings {
            background: transparent;
            color: #543414 !important;
            border: 2px solid #543414;
            padding: 0.5rem 1.2rem !important;
            border-radius: 8px;
            font-weight: 600;
            transition: 0.3s ease;
            margin-left: 10px;
        }
        
        .btn-my-bookings:hover {
            background: #543414;
            color: white !important;
            transform: translateY(-2px);
        }
        
        .btn-my-bookings i {
            margin-right: 5px;
        }
        
        /* User profile button */
        .user-profile-btn {
            cursor: pointer;
            transition: 0.3s ease;
        }
        
        .user-profile-btn:hover {
            opacity: 0.8;
        }
    </style>
</head>
<body>

<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is admin
$is_admin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
// Check if currently on admin page
$is_admin_page = strpos($_SERVER['PHP_SELF'], '/admin/') !== false;
?>

<nav class="navbar navbar-expand-lg glass-navbar fixed-top">
    <div class="container">

        <!-- Logo -->
        <img class="navbar-brand" style="width: 100px;" src="/bayon_logo.png" alt="">

        <!-- Brand -->
        <a class="navbar-brand" href="<?php echo $is_admin_page ? '../index.php' : 'index.php'; ?>">
            <span style="color: #543414;" class="brand-text">សណ្ឋាគារបាយ័ន</span>
        </a>

        <!-- Mobile Button -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- Navbar -->
        <div class="collapse navbar-collapse" id="navbarNav">

            <ul class="navbar-nav ms-auto align-items-lg-center">

                <!-- Navigation -->
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo $is_admin_page ? '../index.php' : 'index.php'; ?>">ទំព័រដើម</a>
                </li>

                <li class="nav-item">
                    <a class="nav-link" href="<?php echo $is_admin_page ? '../index.php#rooms' : 'index.php#rooms'; ?>">បន្ទប់</a>
                </li>

                <li class="nav-item">
                    <a class="nav-link" href="<?php echo $is_admin_page ? '../index.php#services' : 'index.php#services'; ?>">សេវាកម្ម</a>
                </li>

                <li class="nav-item">
                    <a class="nav-link" href="<?php echo $is_admin_page ? '../index.php#testimonials' : 'index.php#testimonials'; ?>">មតិ & ព័តិមាន</a>
                </li>
                
                <?php if(isset($_SESSION['user_id'])): ?>
                    
                    <!-- Show My Bookings for regular users (not on admin page) -->
                    <?php if(!$is_admin && !$is_admin_page): ?>
                        <li class="nav-item">
                            <a class="btn-my-bookings" href="my_bookings.php">
                                <i class="fas fa-calendar-alt"></i> ការកក់របស់ខ្ញុំ
                            </a>
                        </li>
                    <?php endif; ?>
                    
                    <!-- Show Admin Management Button only for admin users and not on admin page -->
                    <?php if($is_admin && !$is_admin_page): ?>
                        <li class="nav-item">
                            <a class="nav-link btn-admin-nav" href="admin/dashboard.php">
                                <i class="fas fa-crown"></i> គ្រប់គ្រងប្រព័ន្ធ
                            </a>
                        </li>
                    <?php endif; ?>
                    
                    <!-- Show Back to Site button when on admin page -->
                    <?php if($is_admin && $is_admin_page): ?>
                        <li class="nav-item">
                            <a class="nav-link btn-admin-nav" href="../index.php">
                                <i class="fas fa-home"></i> ត្រឡប់ទៅកាន់ទំព័រដើម
                            </a>
                        </li>
                    <?php endif; ?>

                    <li class="nav-item">
                        <a class="nav-link user-profile-btn" onclick="openProfileModal()">
                            <i class="fas fa-user-circle"></i>
                            <?php echo htmlspecialchars($_SESSION['username']); ?>
                            <?php if($is_admin): ?>
                                <span class="admin-user-badge">Admin</span>
                            <?php endif; ?>
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link btn-logout" href="<?php echo $is_admin_page ? '../logout.php' : 'logout.php'; ?>">
                            <i class="fas fa-sign-out-alt"></i>
                            ចាកចេញ
                        </a>
                    </li>

                <?php else: ?>

                    <li class="nav-item">
                        <a class="nav-link" href="login.php">
                            ចូលប្រើ
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link btn-register-nav" href="register.php">
                            ចុះឈ្មោះ
                        </a>
                    </li>

                <?php endif; ?>

            </ul>
        </div>
    </div>
</nav>

<div class="page-content">

<!-- User Profile Modal -->
<div class="modal fade" id="userProfileModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header" style="background: #543414; color: white;">
                <h5 class="modal-title"><i class="fas fa-user-circle"></i> ព័ត៌មានគណនី</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="userProfileContent">
                <div class="text-center">
                    <div class="spinner-border text-gold" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">បិទ</button>
            </div>
        </div>
    </div>
</div>

<script>
    // Load user profile via AJAX
    function loadUserProfile() {
        fetch('get_user_profile.php')
            .then(response => response.json())
            .then(data => {
                if(data.success) {
                    const user = data.user;
                    const profileImage = user.profile_image || 'https://ui-avatars.com/api/?background=543414&color=fff&name=' + encodeURIComponent(user.user_name);
                    document.getElementById('userProfileContent').innerHTML = `
                        <div class="text-center">
                            <img src="${profileImage}" style="width: 100px; height: 100px; border-radius: 50%; object-fit: cover; border: 3px solid #543414;" alt="Profile">
                            <h4 class="mt-3">${escapeHtml(user.user_name)}</h4>
                            <span class="badge" style="background: #543414;">${user.role === 'admin' ? 'អ្នកគ្រប់គ្រង' : 'អតិថិជន'}</span>
                        </div>
                        <div class="mt-4">
                            <div class="row mb-3">
                                <div class="col-5 fw-bold">ឈ្មោះ៖</div>
                                <div class="col-7">${escapeHtml(user.user_name)}</div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-5 fw-bold">អ៊ីមែល៖</div>
                                <div class="col-7">${escapeHtml(user.email)}</div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-5 fw-bold">លេខទូរស័ព្ទ៖</div>
                                <div class="col-7">${escapeHtml(user.phone_number || 'មិនមាន')}</div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-5 fw-bold">សមាជិកតាំងពី៖</div>
                                <div class="col-7">${new Date(user.created_at).toLocaleDateString('km-KH')}</div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-5 fw-bold">ស្ថានភាពគណនី៖</div>
                                <div class="col-7">
                                    ${user.account_status === 'active' ? '<span class="text-success">សកម្ម</span>' : '<span class="text-danger">បិទ</span>'}
                                </div>
                            </div>
                        </div>
                    `;
                } else {
                    document.getElementById('userProfileContent').innerHTML = `
                        <div class="text-center text-danger">
                            <i class="fas fa-exclamation-triangle" style="font-size: 48px;"></i>
                            <p class="mt-3">មិនអាចទាញយកព័ត៌មានបាន</p>
                        </div>
                    `;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                document.getElementById('userProfileContent').innerHTML = `
                    <div class="text-center text-danger">
                        <i class="fas fa-exclamation-triangle" style="font-size: 48px;"></i>
                        <p class="mt-3">មានបញ្ហាក្នុងការទាញយកព័ត៌មាន</p>
                    </div>
                `;
            });
    }
    
    function escapeHtml(text) {
        if(!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    function openProfileModal() {
        loadUserProfile();
        new bootstrap.Modal(document.getElementById('userProfileModal')).show();
    }
</script>