<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

logout_user();

// Restart session so we can flash a message on the login page.
session_start();
flash_set('info', 'You have been signed out.');
redirect(APP_URL . '/login.php');
