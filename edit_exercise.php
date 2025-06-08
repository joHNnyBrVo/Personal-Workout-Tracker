<?php
require_once 'config/database.php';
session_start();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    // If not logged in, redirect to login page
    header("Location: login.php");
    exit();
}

$exercise = null;
$error = null;
$success = null;

// Handle form submission for updating exercise FIRST
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id'])) {
    $id = $_POST['id'];
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $category = trim($_POST['category']);

    // Basic validation
    if (empty($name)) {
        // If name is empty on POST, set error and try to re-fetch exercise to show form again
        $error = "Exercise name is required.";
         try {
            $stmt = $pdo->prepare("SELECT * FROM exercises WHERE id = ?");
            $stmt->execute([$id]);
            $exercise = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            // If fetching fails during error handling, redirect to exercises with error
            header("Location: exercises.php?error=Error fetching exercise after validation error: " . urlencode($e->getMessage()));
             exit();
        }
    } else {
        try {
            // Prepare an update statement
            $stmt = $pdo->prepare("UPDATE exercises SET name = ?, description = ?, category = ? WHERE id = ?");

            // Execute the statement
            $stmt->execute([$name, $description, $category, $id]);

            // Check if a row was affected
            if ($stmt->rowCount()) {
                 // Redirect back to exercises page with success message on successful update
                 header("Location: exercises.php?success=Exercise updated successfully!");
                 exit();
            } else {
                 // If no changes or exercise not found on update, re-fetch and show form with message
                 $error = "No changes made or exercise not found.";
                 try {
                    $stmt = $pdo->prepare("SELECT * FROM exercises WHERE id = ?");
                    $stmt->execute([$id]);
                    $exercise = $stmt->fetch(PDO::FETCH_ASSOC);
                 } catch(PDOException $e) {
                     header("Location: exercises.php?error=Error fetching exercise after no changes: " . urlencode($e->getMessage()));
                      exit();
                 }
            }

        } catch(PDOException $e) {
             // On database error during update, set error and re-fetch to show form
            $error = "Error updating exercise: " . $e->getMessage();
             try {
                $stmt = $pdo->prepare("SELECT * FROM exercises WHERE id = ?");
                $stmt->execute([$id]);
                $exercise = $stmt->fetch(PDO::FETCH_ASSOC);
             } catch(PDOException $e_fetch) {
                  header("Location: exercises.php?error=Error fetching exercise after update error: " . urlencode($e_fetch->getMessage()));
                   exit();
             }
        }
    }
} elseif (isset($_GET['id']) && !empty($_GET['id'])) {
    // If accessed with GET and ID, fetch exercise details
    $id = $_GET['id'];

    try {
        $stmt = $pdo->prepare("SELECT * FROM exercises WHERE id = ?");
        $stmt->execute([$id]);
        $exercise = $stmt->fetch(PDO::FETCH_ASSOC);

        // If exercise not found, redirect to exercises page with an error
        if (!$exercise) {
             header("Location: exercises.php?error=Exercise not found.");
             exit();
        }

    } catch(PDOException $e) {
         // If fetching fails on GET, redirect to exercises with error
         header("Location: exercises.php?error=Error fetching exercise: " . urlencode($e->getMessage()));
         exit();
    }
} else {
    // If no ID is provided in GET or POST, redirect back to exercises page with an error
    header("Location: exercises.php?error=No exercise ID specified.");
    exit();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Exercise - Workout Tracker</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/sidebar.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
</head>
<body>
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <h3>Workout Tracker</h3>
        </div>
        <ul class="sidebar-nav">
            <li><a href="dashboard.php"><i class="fas fa-home"></i> <span>Dashboard</span></a></li>
            <li><a href="exercises.php"><i class="fas fa-dumbbell"></i> <span>Exercises</span></a></li>
            <li><a href="#"><i class="fas fa-calendar-alt"></i> <span>Workout Log</span></a></li>
            <li><a href="#"><i class="fas fa-chart-line"></i> <span>Statistics</span></a></li>
             <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a></li>
        </ul>
    </div>

    <button class="sidebar-toggle" id="sidebarToggle">
        <i class="fas fa-bars"></i>
    </button>

    <div class="main-content" id="mainContent">
        <div class="container">
            <h1>Edit Exercise</h1>

            <?php if (isset($error)): ?>
                <div class="alert alert-danger">
                    <p><?php echo htmlspecialchars($error); ?></p>
                </div>
            <?php endif; ?>

             <?php if (isset($success)): ?>
                <div class="alert alert-success">
                    <p><?php echo htmlspecialchars($success); ?></p>
                </div>
            <?php endif; ?>

            <?php if ($exercise): ?>
            <div class="form-container" style="max-width: 600px;">
                <h2>Edit Details for <?php echo htmlspecialchars($exercise['name']); ?></h2>
                <form action="edit_exercise.php" method="POST">
                    <input type="hidden" name="id" value="<?php echo htmlspecialchars($exercise['id']); ?>">
                    <div class="form-group">
                        <label for="name">Exercise Name:</label>
                        <input type="text" id="name" name="name" class="form-control" value="<?php echo htmlspecialchars($exercise['name']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="description">Description:</label>
                        <textarea id="description" name="description" class="form-control" rows="3"><?php echo htmlspecialchars($exercise['description'] ?? ''); ?></textarea>
                    </div>
                     <div class="form-group">
                        <label for="category">Category:</label>
                         <select id="category" name="category" class="form-control">
                            <option value="">Select Category</option>
                            <option value="Strength" <?php echo (isset($exercise['category']) && $exercise['category'] == 'Strength') ? 'selected' : ''; ?>>Strength</option>
                            <option value="Cardio" <?php echo (isset($exercise['category']) && $exercise['category'] == 'Cardio') ? 'selected' : ''; ?>>Cardio</option>
                            <option value="Flexibility" <?php echo (isset($exercise['category']) && $exercise['category'] == 'Flexibility') ? 'selected' : ''; ?>>Flexibility</option>
                            <option value="Bodyweight" <?php echo (isset($exercise['category']) && $exercise['category'] == 'Bodyweight') ? 'selected' : ''; ?>>Bodyweight</option>
                            <option value="Other" <?php echo (isset($exercise['category']) && $exercise['category'] == 'Other') ? 'selected' : ''; ?>>Other</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">Update Exercise</button>
                    <a href="exercises.php" class="btn btn-secondary">Cancel</a>
                </form>
            </div>
            <?php else: ?>
             <!-- If no exercise is loaded (e.g. after a failed update attempt), maybe show a message or redirect -->
             <!-- Currently, the error/success messages above will handle the feedback -->
            <?php endif; ?>

        </div>
    </div>

    <script>
        const sidebar = document.getElementById('sidebar');
        const mainContent = document.getElementById('mainContent');
        const sidebarToggle = document.getElementById('sidebarToggle');

        sidebarToggle.addEventListener('click', () => {
            sidebar.classList.toggle('collapsed');
            mainContent.classList.toggle('shifted');
            sidebarToggle.classList.toggle('shifted');
        });
    </script>
</body>
</html> 