<?php

require_once __DIR__ . '/config.php';

// Create database connection
$mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if ($mysqli->connect_errno) {
    error_log("Database connection failed: " . $mysqli->connect_error);
    die("Database connection failed.");
}

// Set charset
$mysqli->set_charset("utf8mb4");