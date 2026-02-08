<?php
require_once __DIR__ . '/../includes/init.php';

// Destroy session
session_destroy();

// Redirect to login
redirect('/auth/login.php');
