<?php
$DB_HOST = 'localhost';      // সাধারণত localhost ই থাকে
$DB_USER = 'root';           // XAMPP এ default user: root
$DB_PASS = '';               // XAMPP এ default password: ফাঁকা
$DB_NAME = 'foodhub_db';     // phpMyAdmin এ যে database বানিয়েছ, সেই নাম

$conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);

if ($conn->connect_errno) {
    die('Database connection failed: ' . $conn->connect_error);
}

$conn->set_charset('utf8mb4');
