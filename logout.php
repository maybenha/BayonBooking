<?php
// ==========================================
// 7. LOGOUT PAGE
// File: logout.php
// ==========================================
?>

<?php
session_start();
session_destroy();
header("Location: login.php");
exit();
?>