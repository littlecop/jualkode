<?php
// includes/auth.php
require_once __DIR__ . '/../config/db.php';

function admin_logged_in(): bool {
    return isset($_SESSION['admin_id']);
}

function require_admin() {
    if (!admin_logged_in()) {
        header('Location: ' . base_url('admin/login.php'));
        exit;
    }
}
