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

// Handle POST request for updating workout log
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $log_id = filter_var($_POST['log_id'] ?? null, FILTER_VALIDATE_INT);
    $exercise_id = filter_var($_POST['exercise_id'] ?? null, FILTER_VALIDATE_INT);
    $weight = filter_var($_POST['weight'] ?? null, FILTER_VALIDATE_FLOAT);
    $sets = filter_var($_POST['sets'] ?? null, FILTER_VALIDATE_INT);
    $reps = filter_var($_POST['reps'] ?? null, FILTER_VALIDATE_INT);

    // Basic validation for update
    if ($log_id === false || $log_id === null || $exercise_id === false || $exercise_id === null || $sets === false || $sets === null || $reps === false || $reps === null || $sets < 1 || $reps < 1) {
        echo json_encode(['error' => 'Invalid or missing update data.']);
        exit();
    }

     // Handle optional weight field (allow null or empty string)
    if ($weight === false && ($_POST['weight'] !== '' && $_POST['weight'] !== null)) {
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
        // Prepare an update statement, ensuring the log belongs to the logged-in user
        $stmt = $pdo->prepare("UPDATE workout_logs SET exercise_id = ?, weight = ?, sets = ?, reps = ? WHERE id = ? AND user_id = ?");

        // Execute the statement
        $stmt->execute([$exercise_id, $weight, $sets, $reps, $log_id, $user_id]);

        // Check if a row was affected
        if ($stmt->rowCount()) {
            echo json_encode(['success' => true, 'message' => 'Workout log updated successfully!']);
        } else {
            echo json_encode(['error' => 'No changes made or workout log not found.']);
        }

    } catch(PDOException $e) {
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }

} elseif ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['id'])) {
    // Handle GET request for fetching workout log data
    $log_id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);

    // Basic validation for fetching
     if ($log_id === false || $log_id === null) {
        echo json_encode(['error' => 'Invalid or missing workout log ID.']);
        exit();
    }

    try {
        // Fetch workout log details, ensuring it belongs to the logged-in user
        $stmt = $pdo->prepare("SELECT wl.*, e.name as exercise_name FROM workout_logs wl JOIN exercises e ON wl.exercise_id = e.id WHERE wl.id = ? AND wl.user_id = ?");
        $stmt->execute([$log_id, $user_id]);
        $workout_log = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($workout_log) {
            echo json_encode(['success' => true, 'workout_log' => $workout_log]);
        } else {
            echo json_encode(['error' => 'Workout log not found or does not belong to the user.']);
        }

    } catch(PDOException $e) {
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }

} else {
    // Handle other request methods or missing ID in GET
    echo json_encode(['error' => 'Invalid request.']);
}
?> 