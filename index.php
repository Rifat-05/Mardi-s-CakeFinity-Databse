<?php
require __DIR__ . '/includes/init.php';
redirect(empty($_SESSION['admin_id']) ? 'login.php' : 'dashboard.php');
