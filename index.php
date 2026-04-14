<?php
session_start();
if (isset($_SESSION['admin_id'])) {
    header("Location: Dashboard/dashboard.php");
} else {
    header("Location: Admin/auth.php");
}
exit();
?>
