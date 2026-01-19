<?php

// Set timezone to Indonesia
date_default_timezone_set('Asia/Jakarta');

// Database configuration
$host = 'localhost';
$dbname = 'sate_taichan';
$username = 'root';
$password = 'yogiprasojo11';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Base URL - cek dulu apakah sudah didefine
if (!defined('BASE_URL')) {
    define('BASE_URL', 'http://taichanwebsite.test:8081');
}

// Function untuk ambil content dari database
if (!function_exists('getContent')) {
    function getContent($key, $default = '') {
        global $pdo;
        try {
            $stmt = $pdo->prepare("SELECT content_value FROM website_content WHERE content_key = ?");
            $stmt->execute([$key]);
            $result = $stmt->fetchColumn();
            return $result ?: $default;
        } catch (Exception $e) {
            return $default;
        }
    }
}

// Function untuk ambil semua locations
if (!function_exists('getLocations')) {
    function getLocations() {
        global $pdo;
        try {
            $stmt = $pdo->query("SELECT * FROM locations WHERE is_active = TRUE");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }
}
?>