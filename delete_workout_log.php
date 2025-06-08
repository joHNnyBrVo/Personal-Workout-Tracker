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

// Get workout log ID from POST data
$log_id = filter_var($_POST['log_id'] ?? null, FILTER_VALIDATE_INT);

// Basic validation
if ($log_id === false || $log_id === null) {
    echo json_encode(['error' => 'Invalid or missing workout log ID.']);
    exit();
}

try {
    // Prepare a delete statement, ensuring the log belongs to the logged-in user
    $stmt = $pdo->prepare("DELETE FROM workout_logs WHERE id = ? AND user_id = ?");

    // Execute the statement
    $stmt->execute([$log_id, $user_id]);

    // Check if a row was affected (meaning a workout log was deleted)
    if ($stmt->rowCount()) {
        echo json_encode(['success' => true, 'message' => 'Workout log deleted successfully!']);
    } else {
        // This could mean the ID was valid but didn't belong to the user, or wasn't found
        echo json_encode(['error' => 'Workout log not found or does not belong to the user.']);
    }

} catch(PDOException $e) {
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?> 