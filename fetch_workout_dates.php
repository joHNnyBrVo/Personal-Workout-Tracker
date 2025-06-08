<?php
require_once 'config/database.php';
session_start();

header('Content-Type: application/json');

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'User not logged in.']);
    exit();
}

$user_id = $_SESSION['user_id'];

// Get month and year from GET data
$month = filter_var($_GET['month'] ?? null, FILTER_VALIDATE_INT);
$year = filter_var($_GET['year'] ?? null, FILTER_VALIDATE_INT);

// Use current month/year if not provided or invalid
if ($month === false || $month < 1 || $month > 12) {
    $month = date('m');
}
if ($year === false || $year < 1900) { // Basic year validation
    $year = date('Y');
}

// Determine the start and end dates of the month
$start_date = date('Y-m-01', strtotime("$year-$month-01"));
$end_date = date('Y-m-t', strtotime("$year-$month-01"));

try {
    // Fetch distinct dates with workout logs for the user within the current month
    $stmt = $pdo->prepare("SELECT DISTINCT date FROM workout_logs WHERE user_id = ? AND date BETWEEN ? AND ?");
    $stmt->execute([$user_id, $start_date, $end_date]);
    $workout_dates_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Extract dates into a simple array
    $workout_dates = array_column($workout_dates_data, 'date');

    echo json_encode(['success' => true, 'workout_dates' => $workout_dates]);

} catch(PDOException $e) {
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?> 