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

// Check if form data is submitted via POST
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get and sanitize form data
    $log_date = trim($_POST['log_date']);
    $exercise_id = filter_var($_POST['exercise_id'], FILTER_VALIDATE_INT);
    $weight = filter_var($_POST['weight'], FILTER_VALIDATE_FLOAT);
    $sets = filter_var($_POST['sets'], FILTER_VALIDATE_INT);
    $reps = filter_var($_POST['reps'], FILTER_VALIDATE_INT);

    // Basic validation
    if (empty($log_date) || $exercise_id === false || $sets === false || $reps === false || $sets < 1 || $reps < 1) {
        echo json_encode(['error' => 'Invalid or missing form data.']);
        exit();
    }

    // Validate date format (basic check)
    if (!strtotime($log_date)) {
         echo json_encode(['error' => 'Invalid date format.']);
        exit();
    }

    // Handle optional weight field (allow null or empty string)
    if ($weight === false && $_POST['weight'] !== '' && $_POST['weight'] !== null) {
         echo json_encode(['error' => 'Invalid weight format.']);
         exit();
    }
    // Set weight to null if it's empty or validation failed (and it wasn't explicitly 0)
    if ($weight === false && ($_POST['weight'] === '' || $_POST['weight'] === null)) {
        $weight = null;
    }
     if ($weight !== null && $weight < 0) {
         echo json_encode(['error' => 'Weight cannot be negative.']);
         exit();
     }

    try {
        // Prepare an insert statement
        $stmt = $pdo->prepare("INSERT INTO workout_logs (user_id, exercise_id, weight, sets, reps, date) VALUES (?, ?, ?, ?, ?, ?)");

        // Execute the statement
        $stmt->execute([$user_id, $exercise_id, $weight, $sets, $reps, $log_date]);

        echo json_encode(['success' => true, 'message' => 'Workout logged successfully!']);

    } catch(PDOException $e) {
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }

} else {
    // If accessed directly without POST
    echo json_encode(['error' => 'Invalid request method.']);
}
?> 