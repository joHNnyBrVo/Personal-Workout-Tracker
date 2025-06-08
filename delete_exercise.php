<?php
ob_start(); // Start output buffering
require_once 'config/database.php';

if (isset($_GET['id']) && !empty($_GET['id'])) {
    $id = $_GET['id'];

    try {
        // Prepare a delete statement
        $stmt = $pdo->prepare("DELETE FROM exercises WHERE id = ?");

        // Execute the statement
        $stmt->execute([$id]);

        // Check if a row was affected (meaning an exercise was deleted)
        if ($stmt->rowCount()) {
            // Redirect back to exercises page with a success message
            header("Location: exercises.php?success=Exercise deleted successfully!");
            exit();
        } else {
            // Redirect back to exercises page if no exercise found with that ID
            header("Location: exercises.php?error=Exercise not found.");
            exit();
        }

    } catch(PDOException $e) {
        // Redirect back to exercises page with an error message
        header("Location: exercises.php?error=Error deleting exercise: " . urlencode($e->getMessage()));
        exit();
    }
} else {
    // If no ID is provided, redirect back to exercises page with an error
    header("Location: exercises.php?error=No exercise ID specified.");
    exit();
}
ob_end_flush(); // Flush the output buffer
?> 