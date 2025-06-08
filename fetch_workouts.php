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

// Check if the date is provided
if (!isset($_GET['date']) || empty($_GET['date'])) {
    echo json_encode(['error' => 'No date specified.']);
    exit();
}

$date = $_GET['date'];

// Validate date format (basic check)
if (!strtotime($date)) {
     echo json_encode(['error' => 'Invalid date format.']);
    exit();
}

try {
    // Fetch workout logs for the user and date, joining with exercises table
    $stmt = $pdo->prepare("SELECT wl.*, e.name as exercise_name FROM workout_logs wl JOIN exercises e ON wl.exercise_id = e.id WHERE wl.user_id = ? AND wl.date = ? ORDER BY wl.created_at");
    $stmt->execute([$user_id, $date]);
    $workout_logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'workout_logs' => $workout_logs]);

} catch(PDOException $e) {
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?> 