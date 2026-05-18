<?php
// ==========================================
// 6. LOGIN PAGE
// File: login.php
// ==========================================
?>

<?php
session_start();
require_once 'config/database.php';

$error = '';

// If already logged in
if (isset($_SESSION['user_id'])) {

    // Redirect based on role
    if ($_SESSION['role'] == 'admin') {
        header("Location: admin/dashboard.php");
    } else {
        header("Location: index.php");
    }
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $login_input = trim($_POST['login_input'] ?? '');
    $password = $_POST['password'] ?? '';

    // Validate input
    if (empty($login_input) || empty($password)) {

        $error = 'Please enter username/email and password.';

    } else {

        $database = new Database();
        $db = $database->getConnection();

        // Query by username or email
        $query = "SELECT user_id, user_name, email, password, role 
                  FROM user 
                  WHERE user_name = :login_input 
                  OR email = :login_input";

        $stmt = $db->prepare($query);
        $stmt->bindParam(':login_input', $login_input);
        $stmt->execute();

        if ($stmt->rowCount() == 1) {

            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            // Verify password
            if (password_verify($password, $user['password'])) {

                // Store session
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['username'] = $user['user_name'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];

                // Redirect based on role
                if ($user['role'] == 'admin') {

                    header("Location: admin/dashboard.php");

                } else {

                    header("Location: index.php");
                }

                exit();

            } else {

                $error = 'Invalid password.';
            }

        } else {

            $error = 'No account found with that username/email.';
        }
    }
}
?>

<?php include 'includes/header.php'; ?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/sovanphum/fonts-khmer@master/fonts.css">
<link href="https://fonts.googleapis.com/css2?family=Hanuman:wght@100;300;400;700;900&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Siemreap&display=swap" rel="stylesheet">
<div class="auth-container">
    <div class="auth-card">
        <h2><i class="gold-text"></i>ការកក់សណ្ថាគារបាយ័ន</h2>

        <p class="text-center">សូមស្វាគមន៍មកកាន់សណ្ឋាគារបាយ័ន</p>
        
        <?php if($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="mb-3">
                <label class="form-label">ឈ្មោះអ្នកប្រើប្រាស់ ឬអុីមែល</label>
                <input type="text" name="login_input" class="form-control" placeholder="" required>
            </div>
            <div class="mb-3">
                <label class="form-label">ពាក្យសម្ងាត់</label>
                <input type="password" name="password" class="form-control" placeholder="" required>
            </div>
            <button type="submit" class="btn btn-gold w-100">ចូល</button>
        </form>
        <div class="text-center mt-3">
            <p>មិនមានគណនី? <a href="register.php" class="gold-text">ចុះឈ្មោះ</a></p>
        </div>
    </div>
</div>
<?php include 'includes/footer.php'; ?>