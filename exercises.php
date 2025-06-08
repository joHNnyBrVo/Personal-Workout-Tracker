<?php
require_once 'config/database.php';
session_start();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    // If not logged in, redirect to login page
    header("Location: login.php");
    exit();
}

// Fetch exercises from the database
$exercises = [];
try {
    $stmt = $pdo->query("SELECT * FROM exercises ORDER BY name");
    $exercises = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $error = "Error fetching exercises: " . $e->getMessage();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exercises - Workout Tracker</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/sidebar.css">
    <link rel="stylesheet" href="assets/css/exercises_design.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <h3>Workout Tracker</h3>
        </div>
        <ul class="sidebar-nav">
            <li><a href="dashboard.php"><i class="fas fa-home"></i> <span>Dashboard</span></a></li>
            <li><a href="exercises.php" class="active"><i class="fas fa-dumbbell"></i> <span>Exercises</span></a></li>
            <li><a href="workout_log.php"><i class="fas fa-calendar-alt"></i> <span>Workout Log</span></a></li>
            <li><a href="statistics.php"><i class="fas fa-chart-line"></i> <span>Statistics</span></a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a></li>
        </ul>
    </div>

    <!-- Sidebar Toggle Button -->
    <button class="sidebar-toggle" id="sidebarToggle">
        <i class="fas fa-chevron-right"></i>
    </button>

    <!-- Main Content -->
    <div class="main-content" id="mainContent">
        <div class="container exercises-container">
            <div class="exercises-header">
                <h1>Exercises</h1>
            </div>

            <?php if (isset($error)): ?>
                <div class="alert alert-danger">
                    <p><?php echo htmlspecialchars($error); ?></p>
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success">
                    <p><?php echo htmlspecialchars($_GET['success']); ?></p>
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['error'])): ?>
                <div class="alert alert-danger">
                    <?php echo htmlspecialchars($_GET['error']); ?></p>
                </div>
            <?php endif; ?>

            <!-- Form to Add New Exercise -->
            <div class="add-exercise-form-section">
                <h2>Add New Exercise</h2>
                <form action="process_exercise.php" method="POST">
                    <div class="form-group">
                        <label for="name">Exercise Name:</label>
                        <input type="text" id="name" name="name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="description">Description:</label>
                        <textarea id="description" name="description" class="form-control" rows="3"></textarea>
                    </div>
                    <div class="form-group">
                        <label for="category">Category:</label>
                        <select id="category" name="category" class="form-control">
                            <option value="">Select Category</option>
                            <option value="Strength">Strength</option>
                            <option value="Cardio">Cardio</option>
                            <option value="Flexibility">Flexibility</option>
                            <option value="Bodyweight">Bodyweight</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">Add Exercise</button>
                </form>
            </div>

            <!-- List of Exercises -->
            <div class="exercise-list-section">
                <h2>Existing Exercises</h2>
                <?php if (empty($exercises)): ?>
                    <p>No exercises added yet.</p>
                <?php else: ?>
                    <div class="exercise-cards-grid">
                        <?php foreach ($exercises as $exercise): ?>
                            <div class="exercise-card">
                                <h3><?php echo htmlspecialchars($exercise['name']); ?></h3>
                                <p><strong>Description:</strong> <?php echo htmlspecialchars($exercise['description'] ?? 'No description'); ?></p>
                                <?php if (!empty($exercise['category'])): ?>
                                    <span class="category-tag"><?php echo htmlspecialchars($exercise['category']); ?></span>
                                <?php endif; ?>
                                <div class="exercise-card-actions">
                                    <a href="edit_exercise.php?id=<?php echo $exercise['id']; ?>" class="btn btn-info"><i class="fas fa-edit"></i> Edit</a>
                                    <a href="delete_exercise.php?id=<?php echo $exercise['id']; ?>" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this exercise?');"><i class="fas fa-trash-alt"></i> Delete</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </div>

    <script>
        const sidebar = document.getElementById('sidebar');
        const mainContent = document.getElementById('mainContent');
        const sidebarToggle = document.getElementById('sidebarToggle');
        const sidebarToggleIcon = sidebarToggle.querySelector('i');

        sidebarToggle.addEventListener('click', () => {
            sidebar.classList.toggle('collapsed');
            mainContent.classList.toggle('shifted');
            sidebarToggle.classList.toggle('shifted');

            // Toggle the icon based on the 'collapsed' class
            if (sidebar.classList.contains('collapsed')) {
                sidebarToggleIcon.classList.remove('fa-chevron-right');
                sidebarToggleIcon.classList.add('fa-chevron-left');
                mainContent.classList.add('overlay-active'); // Add overlay on mobile
            } else {
                sidebarToggleIcon.classList.remove('fa-chevron-left');
                sidebarToggleIcon.classList.add('fa-chevron-right');
                mainContent.classList.remove('overlay-active'); // Remove overlay
            }
        });

        // Close sidebar when clicking on the overlay (main content) on mobile
        mainContent.addEventListener('click', function() {
            if (window.innerWidth <= 768 && sidebar.classList.contains('collapsed')) {
                sidebar.classList.remove('collapsed');
                mainContent.classList.remove('shifted');
                mainContent.classList.remove('overlay-active');
                sidebarToggleIcon.classList.remove('fa-chevron-left');
                sidebarToggleIcon.classList.add('fa-chevron-right');
            }
        });
    </script>
</body>
</html> 