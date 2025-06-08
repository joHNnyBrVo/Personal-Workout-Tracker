<?php
require_once 'config/database.php';
session_start();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$selected_range = isset($_GET['range']) ? $_GET['range'] : 'all_time';

$date_condition = "";
switch ($selected_range) {
    case 'last_7_days':
        $date_condition = "AND date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
        break;
    case 'last_30_days':
        $date_condition = "AND date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
        break;
    case 'last_90_days':
        $date_condition = "AND date >= DATE_SUB(CURDATE(), INTERVAL 90 DAY)";
        break;
    case 'all_time':
    default:
        $date_condition = "";
        break;
}

// Fetch statistics
try {
    // Total workouts
    $stmt = $pdo->prepare("SELECT COUNT(DISTINCT date) as total_workouts FROM workout_logs WHERE user_id = ? " . $date_condition);
    $stmt->execute([$user_id]);
    $total_workouts = $stmt->fetch(PDO::FETCH_ASSOC)['total_workouts'];

    // Total exercises performed
    $stmt = $pdo->prepare("SELECT COUNT(*) as total_exercises FROM workout_logs WHERE user_id = ? " . $date_condition);
    $stmt->execute([$user_id]);
    $total_exercises = $stmt->fetch(PDO::FETCH_ASSOC)['total_exercises'];

    // Most frequent exercises
    $stmt = $pdo->prepare("
        SELECT e.name, COUNT(*) as frequency 
        FROM workout_logs wl 
        JOIN exercises e ON wl.exercise_id = e.id 
        WHERE wl.user_id = ? " . $date_condition . " 
        GROUP BY e.id 
        ORDER BY frequency DESC 
        LIMIT 5
    ");
    $stmt->execute([$user_id]);
    $top_exercises = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Workout frequency by day of week
    $stmt = $pdo->prepare("
        SELECT DAYOFWEEK(date) as day_of_week, COUNT(*) as count 
        FROM workout_logs 
        WHERE user_id = ? " . $date_condition . " 
        GROUP BY DAYOFWEEK(date) 
        ORDER BY day_of_week
    ");
    $stmt->execute([$user_id]);
    $workout_frequency = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Progress data for charts (Max weight lifted per day)
    $stmt = $pdo->prepare("
        SELECT date, MAX(weight) as max_weight_lifted
        FROM workout_logs
        WHERE user_id = ? " . $date_condition . " 
        GROUP BY date
        ORDER BY date ASC
        LIMIT 50
    ");
    $stmt->execute([$user_id]);
    $progress_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch(PDOException $e) {
    $error = "Error fetching statistics: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Statistics - Workout Tracker</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/sidebar.css">
    <link rel="stylesheet" href="assets/css/statistics_design.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <h3>Workout Tracker</h3>
        </div>
        <ul class="sidebar-nav">
            <li><a href="dashboard.php"><i class="fas fa-home"></i> <span>Dashboard</span></a></li>
            <li><a href="exercises.php"><i class="fas fa-dumbbell"></i> <span>Exercises</span></a></li>
            <li><a href="workout_log.php"><i class="fas fa-calendar-alt"></i> <span>Workout Log</span></a></li>
            <li><a href="statistics.php" class="active"><i class="fas fa-chart-line"></i> <span>Statistics</span></a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a></li>
        </ul>
    </div>

    <!-- Sidebar Toggle Button -->
    <button class="sidebar-toggle" id="sidebarToggle">
        <i class="fas fa-chevron-right"></i>
    </button>

    <!-- Main Content -->
    <div class="main-content" id="mainContent">
        <div class="container statistics-container">
            <div class="statistics-header">
                <h1>Workout Statistics</h1>
                <div class="date-range-selector">
                    <label for="dateRange">View:</label>
                    <select id="dateRange" onchange="window.location.href='statistics.php?range=' + this.value">
                        <option value="last_7_days" <?php echo ($selected_range == 'last_7_days') ? 'selected' : ''; ?>>Last 7 Days</option>
                        <option value="last_30_days" <?php echo ($selected_range == 'last_30_days') ? 'selected' : ''; ?>>Last 30 Days</option>
                        <option value="last_90_days" <?php echo ($selected_range == 'last_90_days') ? 'selected' : ''; ?>>Last 90 Days</option>
                        <option value="all_time" <?php echo ($selected_range == 'all_time') ? 'selected' : ''; ?>>All Time</option>
                    </select>
                </div>
            </div>

            <?php if (isset($error)): ?>
                <div class="alert alert-danger">
                    <p><?php echo htmlspecialchars($error); ?></p>
                </div>
            <?php endif; ?>

            <!-- Statistics Cards -->
            <div class="stat-cards-grid">
                <div class="stat-card-new">
                    <i class="fas fa-dumbbell"></i>
                    <h3>Total Workouts</h3>
                    <p class="stat-number-new"><?php echo $total_workouts; ?></p>
                </div>
                <div class="stat-card-new">
                    <i class="fas fa-list"></i>
                    <h3>Total Exercises</h3>
                    <p class="stat-number-new"><?php echo $total_exercises; ?></p>
                </div>
            </div>

            <!-- Charts Section -->
            <div class="charts-section">
                <h2>Workout Analysis</h2>
                <div class="chart-grid-new">
                    <!-- Workout Frequency Chart -->
                    <div class="chart-container-new">
                        <h3>Workout Frequency by Day</h3>
                        <canvas id="workoutFrequencyChart"></canvas>
                    </div>

                    <!-- Top Exercises Chart -->
                    <div class="chart-container-new">
                        <h3>Most Frequent Exercises</h3>
                        <canvas id="topExercisesChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Progress Charts (modified for dynamic progress) -->
            <div class="charts-section">
                <h2>Exercise Progress</h2>
                <div class="chart-grid-new">
                    <div class="chart-container-new">
                        <h3>Weight Progress Over Time</h3>
                        <canvas id="progressChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Initialize sidebar toggle
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

        // Workout Frequency Chart
        const workoutFrequencyData = <?php echo json_encode($workout_frequency); ?>;
        const days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
        const frequencyData = new Array(7).fill(0);
        
        workoutFrequencyData.forEach(item => {
            frequencyData[item.day_of_week - 1] = item.count;
        });

        new Chart(document.getElementById('workoutFrequencyChart'), {
            type: 'bar',
            data: {
                labels: days,
                datasets: [{
                    label: 'Number of Workouts',
                    data: frequencyData,
                    backgroundColor: '#1E3A8A',
                    borderColor: '#152a65',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1,
                            color: 'var(--light-text-color)'
                        },
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)',
                            borderColor: 'rgba(0, 0, 0, 0.1)'
                        }
                    },
                    x: {
                        ticks: {
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
                        display: false
                    }
                }
            }
        });

        // Top Exercises Chart
        const topExercisesData = <?php echo json_encode($top_exercises); ?>;
        new Chart(document.getElementById('topExercisesChart'), {
            type: 'pie',
            data: {
                labels: topExercisesData.map(item => item.name),
                datasets: [{
                    data: topExercisesData.map(item => item.frequency),
                    backgroundColor: [
                        '#1E3A8A',
                        '#2563EB',
                        '#3B82F6',
                        '#60A5FA',
                        '#93C5FD'
                    ],
                    borderColor: 'var(--light-background)',
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            boxWidth: 20,
                            padding: 15,
                            color: 'var(--text-color)'
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                if (context.raw !== null) {
                                    label += context.raw + ' workouts';
                                }
                                return label;
                            }
                        }
                    }
                }
            }
        });

        // Progress Chart
        const progressDataRaw = <?php echo json_encode($progress_data); ?>;
        const dates = [...new Set(progressDataRaw.map(item => item.date))].sort();
        const weights = dates.map(date => progressDataRaw.find(item => item.date === date)?.max_weight_lifted || null);

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
                        title: {
                            display: false
                        },
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