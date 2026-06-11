<?php
require_once __DIR__ . '/includes/config.php';

// Route all traffic to the home page
header('Location: ' . SITE_URL . '/pages/home.php');
exit;
