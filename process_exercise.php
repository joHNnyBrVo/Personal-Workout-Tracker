<?php
ob_start(); // Start output buffering
require_once 'config/database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $category = trim($_POST['category']);

    // Basic validation
    if (empty($name)) {
        // Redirect back to exercises page with an error message
        header("Location: exercises.php?error=Exercise name is required");
        exit();
    }

    try {
        // Prepare an insert statement
        $stmt = $pdo->prepare("INSERT INTO exercises (name, description, category) VALUES (?, ?, ?)");

        // Execute the statement
        $stmt->execute([$name, $description, $category]);

        // Redirect back to exercises page with a success message
        header("Location: exercises.php?success=Exercise added successfully!");
        exit();

    } catch(PDOException $e) {
        // Redirect back to exercises page with an error message
        header("Location: exercises.php?error=Error adding exercise: " . urlencode($e->getMessage()));
        exit();
    }
} else {
    // If accessed directly without POST, redirect to exercises page
    header("Location: exercises.php");
    exit();
}
ob_end_flush(); // Flush the output buffer
?> 