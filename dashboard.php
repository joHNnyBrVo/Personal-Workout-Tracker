<?php
require_once 'config/database.php';
session_start();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    // If not logged in, redirect to login page
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];

// Fetch statistics
try {
    // Total workouts
    $stmt = $pdo->prepare("SELECT COUNT(DISTINCT date) as total_workouts FROM workout_logs WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $total_workouts = $stmt->fetch(PDO::FETCH_ASSOC)['total_workouts'];

    // Total exercises performed
    $stmt = $pdo->prepare("SELECT COUNT(*) as total_exercises FROM workout_logs WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $total_exercises = $stmt->fetch(PDO::FETCH_ASSOC)['total_exercises'];

    // Recent workouts
    $stmt = $pdo->prepare("
        SELECT DISTINCT date, COUNT(*) as exercise_count 
        FROM workout_logs 
        WHERE user_id = ? 
        GROUP BY date 
        ORDER BY date DESC 
        LIMIT 5
    ");
    $stmt->execute([$user_id]);
    $recent_workouts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Exercise progress data (Max weight lifted per day)
    $stmt = $pdo->prepare("
        SELECT date, MAX(weight) as max_weight_lifted
        FROM workout_logs
        WHERE user_id = ?
        GROUP BY date
        ORDER BY date ASC
        LIMIT 50
    ");
    $stmt->execute([$user_id]);
    $progress_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Suggested exercises (fetch a few general exercises with descriptions)
    $stmt = $pdo->prepare("
        SELECT id, name, description
        FROM exercises
        ORDER BY id ASC -- Or any other ordering for general suggestions
        LIMIT 3
    ");
    $stmt->execute(); // No user_id filter needed for general suggestions
    $suggested_exercises = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch(PDOException $e) {
    $error = "Error fetching dashboard data: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Workout Tracker</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/sidebar.css">
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <link rel="stylesheet" href="assets/css/suggested_exercises.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <h3>Workout Tracker</h3>
        </div>
        <ul class="sidebar-nav">
            <li><a href="dashboard.php" class="active"><i class="fas fa-home"></i> <span>Dashboard</span></a></li>
            <li><a href="exercises.php"><i class="fas fa-dumbbell"></i> <span>Exercises</span></a></li>
            <li><a href="workout_log.php"><i class="fas fa-calendar-alt"></i> <span>Workout Log</span></a></li>
            <li><a href="statistics.php"><i class="fas fa-chart-line"></i> <span>Statistics</span></a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a></li>
        </ul>
    </div>

    <button class="sidebar-toggle" id="sidebarToggle">
        <i class="fas fa-chevron-right"></i>
    </button>

    <div class="main-content" id="mainContent">
        <div class="container">
            <h1>Welcome, <?php echo htmlspecialchars($username); ?>!</h1>

            <?php if (isset($error)): ?>
                <div class="alert alert-danger">
                    <p><?php echo htmlspecialchars($error); ?></p>
                </div>
            <?php endif; ?>

            <!-- Quick Stats -->
            <div class="dashboard-grid">
                <div class="dashboard-card">
                    <h2><i class="fas fa-dumbbell"></i> Quick Stats</h2>
                    <div class="quick-stats">
                        <div class="stat-item">
                            <div class="stat-number"><?php echo $total_workouts; ?></div>
                            <div class="stat-label">Total Workouts</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number"><?php echo $total_exercises; ?></div>
                            <div class="stat-label">Exercises Performed</div>
                        </div>
                    </div>
                </div>

                <!-- Recent Workouts -->
                <div class="dashboard-card">
                    <h2><i class="fas fa-history"></i> Recent Workouts</h2>
                    <div class="recent-workouts">
                        <ul class="workout-list">
                            <?php foreach ($recent_workouts as $workout): ?>
                                <li class="workout-item">
                                    <div class="workout-icon">
                                        <i class="fas fa-calendar-check"></i>
                                    </div>
                                    <div class="workout-details">
                                        <div class="workout-date">
                                            <?php echo date('F j, Y', strtotime($workout['date'])); ?>
                                        </div>
                                        <div class="workout-exercises">
                                            <?php echo $workout['exercise_count']; ?> exercises completed
                                        </div>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Exercise Progress -->
            <div class="exercise-progress">
                <div class="dashboard-card">
                    <h2><i class="fas fa-chart-line"></i> Exercise Progress</h2>
                    <div class="progress-chart">
                        <canvas id="progressChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Suggested Exercises -->
            <div class="suggested-exercises">
                <div class="dashboard-card">
                    <h2><i class="fas fa-lightbulb"></i> Suggested Exercises</h2>
                    <?php if (!empty($suggested_exercises)): ?>
                        <div class="suggested-exercises-grid">
                            <?php foreach ($suggested_exercises as $exercise): ?>
                                <div class="suggested-item">
                                    <h4><?php echo htmlspecialchars($exercise['name']); ?></h4>
                                    <p><?php echo htmlspecialchars($exercise['description']); ?></p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p>No new exercise suggestions at the moment. Keep up the great work!</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Add New Exercise -->
            <div class="add-exercise-section">
                <a href="exercises.php" class="btn"><i class="fas fa-plus-circle"></i> Add Your Own Exercise</a>
            </div>

        </div>
    </div>

    <script>
        // Sidebar toggle functionality
        const sidebar = document.getElementById('sidebar');
        const mainContent = document.getElementById('mainContent');
        const sidebarToggle = document.getElementById('sidebarToggle');
        const sidebarToggleIcon = sidebarToggle.querySelector('i');

        sidebarToggle.addEventListener('click', () => {
            sidebar.classList.toggle('collapsed');
            mainContent.classList.toggle('shifted');
            sidebarToggle.classList.toggle('shifted');

            if (sidebar.classList.contains('collapsed')) {
                sidebarToggleIcon.classList.remove('fa-chevron-right');
                sidebarToggleIcon.classList.add('fa-chevron-left');
                mainContent.classList.add('overlay-active');
            } else {
                sidebarToggleIcon.classList.remove('fa-chevron-left');
                sidebarToggleIcon.classList.add('fa-chevron-right');
                mainContent.classList.remove('overlay-active');
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

        // Exercise Progress Chart
        const progressData = <?php echo json_encode($progress_data); ?>;
        const dates = [...new Set(progressData.map(item => item.date))].sort();
        const weights = dates.map(date => progressData.find(item => item.date === date)?.max_weight_lifted || null);

        new Chart(document.getElementById('progressChart'), {
            type: 'line',
            data: {
                labels: dates.map(date => new Date(date).toLocaleDateString('en-US', { month: 'short', day: 'numeric' })),
                datasets: [{
                    label: 'Max Weight Lifted',
                    data: weights,
                    borderColor: '#3B82F6',
                    backgroundColor: 'rgba(59, 130, 246, 0.2)',
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                interaction: {
                    intersect: false,
                    mode: 'index'
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: false
                        },
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)',
                            borderColor: 'rgba(0, 0, 0, 0.1)'
                        },
                        ticks: {
                            color: 'var(--light-text-color)'
                        }
                    },
                    x: {
                        ticks: {
                            maxRotation: 45,
                            minRotation: 45,
                            callback: function(value, index, ticks) {
                                const date = new Date(this.getLabelForValue(value));
                                return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
                            },
                            color: 'var(--light-text-color)'
                        },
                        grid: {
                            display: false
                        },
                        border: {
                            color: 'rgba(0, 0, 0, 0.1)'
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false,
                    },
                    tooltip: {
                        callbacks: {
                            title: function(context) {
                                const originalDates = <?php echo json_encode(array_column($progress_data, 'date')); ?>;
                                const date = new Date(originalDates[context[0].dataIndex]);
                                return date.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
                            },
                            label: function(context) {
                                return `Max Weight: ${context.raw !== null ? context.raw + ' kg' : 'N/A'}`;
                            }
                        }
                    }
                },
                elements: {
                    line: {
                        borderWidth: 3
                    },
                    point: {
                        radius: 4,
                        hoverRadius: 6,
                        backgroundColor: 'white',
                        borderWidth: 2,
                        borderColor: '#3B82F6'
                    }
                }
            }
        });
    </script>
</body>
</html> 