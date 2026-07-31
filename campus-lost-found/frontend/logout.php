<?php
require_once 'config.php';
$_SESSION = [];
session_destroy();
header('Location: login.php?msg=logged_out');
exit;
?>
