<?php
require_once __DIR__ . '/../config/db.php';
unset($_SESSION['admin_id'], $_SESSION['admin_name']);
header('Location: ' . base_url('admin/login.php'));
exit;
