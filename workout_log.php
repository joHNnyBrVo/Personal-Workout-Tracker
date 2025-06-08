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

// Fetch exercises for the dropdown
$exercises = [];
try {
    $stmt = $pdo->prepare("SELECT id, name FROM exercises ORDER BY name");
    $stmt->execute();
    $exercises = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    // Handle error fetching exercises if necessary
    // For now, just log or set an error message, won't stop the page from loading
     error_log("Error fetching exercises for workout log form: " . $e->getMessage());
}

// Get current month and year from GET or use current date
$current_month = isset($_GET['month']) ? $_GET['month'] : date('m');
$current_year = isset($_GET['year']) ? $_GET['year'] : date('Y');

// Fetch dates with workouts for the current month
$workout_dates = [];
try {
    $start_date = date('Y-m-01', strtotime("$current_year-$current_month-01"));
    $end_date = date('Y-m-t', strtotime("$current_year-$current_month-01"));
    $stmt = $pdo->prepare("SELECT DISTINCT date FROM workout_logs WHERE user_id = ? AND date BETWEEN ? AND ?");
    $stmt->execute([$user_id, $start_date, $end_date]);
    $workout_dates_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $workout_dates = array_column($workout_dates_data, 'date');
} catch(PDOException $e) {
     error_log("Error fetching workout dates: " . $e->getMessage());
     // $workout_dates will remain empty, which is fine
}

// Function to generate calendar HTML
function generate_calendar($month, $year, $workout_dates = []) {
    $first_day_of_month = mktime(0, 0, 0, $month, 1, $year);
    $days_in_month = date('t', $first_day_of_month);
    $day_of_week = date('w', $first_day_of_month);
    $calendar = '';

    // Add calendar header with month and year
    $calendar .= '<div class="calendar-header-new">';
    $calendar .= '<div class="calendar-nav">';
    $calendar .= '<a href="?month=' . date('m', strtotime("$year-$month-01 -1 month")) . '&year=' . date('Y', strtotime("$year-$month-01 -1 month")) . '"><i class="fas fa-chevron-left"></i></a>';
    $calendar .= '</div>';
    $calendar .= '<h2>' . date('F Y', $first_day_of_month) . '</h2>';
     $calendar .= '<div class="calendar-nav">';
     $calendar .= '<a href="?month=' . date('m', strtotime("$year-$month-01 +1 month")) . '&year=' . date('Y', strtotime("$year-$month-01 +1 month")) . '"><i class="fas fa-chevron-right"></i></a>';
     $calendar .= '</div>';
    $calendar .= '</div>';

    $calendar .= '<table class="calendar-table-new">';
    $calendar .= '<thead><tr>';
    $week_days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
    foreach ($week_days as $day) {
        $calendar .= '<th>' . $day . '</th>';
    }
    $calendar .= '</tr></thead>';
    $calendar .= '<tbody>';

    $calendar .= '<tr>';

    // Add empty cells for the days before the first day of the month
    for ($i = 0; $i < $day_of_week; $i++) {
        $calendar .= '<td></td>';
    }

    // Add the days of the month
    for ($day = 1; $day <= $days_in_month; $day++) {
        if ($day_of_week == 7) {
            $calendar .= '</tr><tr>';
            $day_of_week = 0;
        }

        $current_date_str = date('Y-m-d', strtotime("$year-$month-$day"));
        $current_date_ts = strtotime($current_date_str);
        $today_ts = strtotime(date('Y-m-d'));

        $classes = [];
        if ($current_date_ts == $today_ts) {
            $classes[] = 'today';
        }
        
        // Add class for days with workouts
        if (in_array($current_date_str, $workout_dates)) {
            $classes[] = 'has-workout';
        }

        $calendar .= '<td class="' . implode(' ', $classes) . '" data-date="' . $current_date_str . '">' . $day . '</td>';

        $day_of_week++;
    }

    // Add empty cells for the remaining days of the last week
    while ($day_of_week < 7) {
        $calendar .= '<td></td>';
        $day_of_week++;
    }

    $calendar .= '</tr>';

    $calendar .= '</tbody>';
    $calendar .= '</table>';

    return $calendar;
}


// Generate the calendar HTML, passing workout dates
$calendar_html = generate_calendar($current_month, $current_year, $workout_dates);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Workout Log - Workout Tracker</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/sidebar.css">
    <link rel="stylesheet" href="assets/css/workout_log_design.css">
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
            <li><a href="exercises.php"><i class="fas fa-dumbbell"></i> <span>Exercises</span></a></li>
            <li><a href="workout_log.php" class="active"><i class="fas fa-calendar-alt"></i> <span>Workout Log</span></a></li>
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
        <div class="container workout-log-container">
            <div class="workout-log-header">
                <h1>Workout Log</h1>
            </div>

            <!-- Calendar Section -->
            <div class="calendar-section">
                <?php echo $calendar_html; ?>
            </div>

             <!-- Form to add workout for selected date -->
            <div class="workout-form-section" style="display: none;">
                 <h2>Add Workout for <span id="selectedDateTitle"></span></h2>
                 <form id="addWorkoutForm" action="process_workout_log.php" method="POST">
                     <input type="hidden" id="logDate" name="log_date" value="">
                    <div class="form-group">
                        <label for="exercise">Exercise:</label>
                        <select id="exercise" name="exercise_id" class="form-control" required>
                             <option value="">Select Exercise</option>
                             <?php foreach ($exercises as $exercise): ?>
                                 <option value="<?php echo htmlspecialchars($exercise['id']); ?>"><?php echo htmlspecialchars($exercise['name']); ?></option>
                             <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="weight">Weight (optional):</label>
                        <input type="number" id="weight" name="weight" class="form-control" step="0.01" min="0">
                    </div>
                     <div class="form-group">
                        <label for="sets">Sets:</label>
                        <input type="number" id="sets" name="sets" class="form-control" min="1" required>
                    </div>
                     <div class="form-group">
                        <label for="reps">Reps:</label>
                        <input type="number" id="reps" name="reps" class="form-control" min="1" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Log Workout</button>
                 </form>
            </div>

            <!-- Workout Log Display Section -->
            <div class="workout-display-section" id="workoutLogDisplay">
                <h2>Select a date to view workouts</h2>
                <table class="workout-entries-table" id="workoutLogTable">
                    <thead>
                        <tr>
                            <th>Exercise</th>
                            <th>Weight</th>
                            <th>Sets</th>
                            <th>Reps</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Workout logs will be loaded here via JavaScript -->
                         <tr>
                             <td colspan="5" style="text-align: center;">Select a date to view logs.</td>
                         </tr>
                    </tbody>
                </table>
            </div>

             <!-- Edit Workout Log Form -->
             <div class="workout-form-section edit-workout-log-form" style="display: none;">
                 <h2>Edit Workout Entry</h2>
                 <form id="editWorkoutLogForm" action="edit_workout_log.php" method="POST">
                     <input type="hidden" id="editLogId" name="log_id" value="">
                     <div class="form-group">
                         <label for="editExercise">Exercise:</label>
                         <select id="editExercise" name="exercise_id" class="form-control" required>
                              <option value="">Select Exercise</option>
                              <?php foreach ($exercises as $exercise): ?>
                                  <option value="<?php echo htmlspecialchars($exercise['id']); ?>"><?php echo htmlspecialchars($exercise['name']); ?></option>
                             <?php endforeach; ?>
                         </select>
                     </div>
                     <div class="form-group">
                         <label for="editWeight">Weight (optional):</label>
                         <input type="number" id="editWeight" name="weight" class="form-control" step="0.01" min="0">
                     </div>
                      <div class="form-group">
                         <label for="editSets">Sets:</label>
                         <input type="number" id="editSets" name="sets" class="form-control" min="1" required>
                     </div>
                      <div class="form-group">
                         <label for="editReps">Reps:</label>
                         <input type="number" id="editReps" name="reps" class="form-control" min="1" required>
                     </div>
                     <button type="submit" class="btn btn-primary">Update Workout</button>
                     <button type="button" class="btn btn-secondary" onclick="hideEditWorkoutLogForm()">Cancel</button>
                 </form>
             </div>

        </div>
    </div>

    <script>
        const sidebar = document.getElementById('sidebar');
        const mainContent = document.getElementById('mainContent');
        const sidebarToggle = document.getElementById('sidebarToggle');
        const sidebarToggleIcon = sidebarToggle.querySelector('i');
        const workoutLogDisplay = document.getElementById('workoutLogDisplay');
        const workoutLogTableBody = document.querySelector('#workoutLogTable tbody');
        const addWorkoutFormContainer = document.querySelector('.workout-form-section');
        const selectedDateTitle = document.getElementById('selectedDateTitle');
        const logDateInput = document.getElementById('logDate');

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

        // Handle calendar date clicks
        document.querySelectorAll('.calendar-table-new td[data-date]').forEach(dayCell => {
            dayCell.addEventListener('click', () => {
                const selectedDate = dayCell.getAttribute('data-date');
                // Remove existing selected class
                document.querySelectorAll('.calendar-table-new td.selected-date').forEach(cell => cell.classList.remove('selected-date'));
                // Add selected class to the clicked cell
                dayCell.classList.add('selected-date');

                // Show the add workout form and update the date
                selectedDateTitle.innerText = new Date(selectedDate).toDateString();
                logDateInput.value = selectedDate;
                 addWorkoutFormContainer.style.display = 'block';
                 document.querySelector('.edit-workout-log-form').style.display = 'none'; // Hide edit form if shown

                // Load workouts for this date
                loadWorkouts(selectedDate);
            });
        });

        // Function to load workouts for a selected date
        function loadWorkouts(date) {
            console.log('Loading workouts for date:', date);
            const workoutLogTableBody = document.querySelector('#workoutLogTable tbody');

            // Clear existing rows
            workoutLogTableBody.innerHTML = '<tr><td colspan="5" style="text-align: center;">Loading workouts...</td></tr>';

            fetch(`fetch_workouts.php?date=${date}&user_id=<?php echo $user_id; ?>`)
                .then(response => response.json())
                .then(data => {
                    workoutLogTableBody.innerHTML = ''; // Clear loading message
                    if (data.success) {
                        const workout_logs = data.workout_logs; // Access the workout_logs array
                        if (workout_logs.length > 0) {
                            workout_logs.forEach(log => {
                                const row = document.createElement('tr');
                                row.innerHTML = `
                                <td data-label="Exercise">${log.exercise_name}</td>
                                <td data-label="Weight">${log.weight ? log.weight + ' kg' : 'N/A'}</td>
                                <td data-label="Sets">${log.sets}</td>
                                <td data-label="Reps">${log.reps}</td>
                                <td data-label="Actions">
                                    <button class="btn btn-info btn-sm" onclick="showEditWorkoutLogForm(${log.id}, '${log.exercise_id}', ${log.weight}, ${log.sets}, ${log.reps})"><i class="fas fa-edit"></i> Edit</button>
                                    <a href="delete_workout_log.php?id=${log.id}" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this workout entry?');"><i class="fas fa-trash-alt"></i> Delete</a>
                                </td>
                            `;
                                workoutLogTableBody.appendChild(row);
                            });
                        } else {
                            workoutLogTableBody.innerHTML = '<tr><td colspan="5" style="text-align: center;">No workouts logged for this date.</td></tr>';
                        }
                    } else if (data.error) {
                        workoutLogTableBody.innerHTML = `<tr><td colspan="5" style="text-align: center; color: red;">${data.error}</td></tr>`;
                        console.error('Error from fetch_workouts.php:', data.error);
                    } else {
                        workoutLogTableBody.innerHTML = '<tr><td colspan="5" style="text-align: center; color: red;">Unknown error loading workouts.</td></tr>';
                    }
                })
                .catch(error => {
                    console.error('Error fetching workout logs:', error);
                    workoutLogTableBody.innerHTML = '<tr><td colspan="5" style="text-align: center; color: red;">Error loading workouts. Please check console for details.</td></tr>';
                });
        }

        // Function to show edit workout form
        function showEditWorkoutLogForm(logId, exerciseId, weight, sets, reps) {
            const addWorkoutFormContainer = document.querySelector('.workout-form-section');
            const editWorkoutLogFormContainer = document.querySelector('.edit-workout-log-form');
            
            addWorkoutFormContainer.style.display = 'none'; // Hide add form
            editWorkoutLogFormContainer.style.display = 'block'; // Show edit form

            document.getElementById('editLogId').value = logId;
            document.getElementById('editExercise').value = exerciseId;
            document.getElementById('editWeight').value = weight;
            document.getElementById('editSets').value = sets;
            document.getElementById('editReps').value = reps;
        }

        // Function to hide edit workout form
        function hideEditWorkoutLogForm() {
            document.querySelector('.edit-workout-log-form').style.display = 'none';
        }

        // Handle add workout form submission via AJAX
        const addWorkoutForm = document.getElementById('addWorkoutForm');
        addWorkoutForm.addEventListener('submit', function(event) {
            event.preventDefault(); // Prevent default form submission

            const formData = new FormData(this); // Get form data

            fetch('process_workout_log.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message); // Show success message (can be replaced with a better notification)
                    // Clear the form
                    addWorkoutForm.reset();
                    // Reload workouts for the current selected date
                    const selectedDate = logDateInput.value;
                    loadWorkouts(selectedDate);
                } else {
                    alert('Error: ' + data.error); // Show error message
                }
            })
            .catch(error => {
                console.error('Error logging workout:', error);
                alert('An error occurred while logging the workout.');
            });
        });

        // Handle edit workout log form submission via AJAX
        const editWorkoutLogForm = document.getElementById('editWorkoutLogForm');
        editWorkoutLogForm.addEventListener('submit', function(event) {
            event.preventDefault(); // Prevent default form submission

            const formData = new FormData(this); // Get form data

            fetch('edit_workout_log.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message); // Show success message
                    // Hide the edit form
                    hideEditWorkoutLogForm();
                    // Reload workouts for the current selected date
                    const selectedDate = document.querySelector('.calendar-table-new td.selected-date').getAttribute('data-date');
                    loadWorkouts(selectedDate);
                } else {
                    alert('Error: ' + data.error); // Show error message
                }
            })
            .catch(error => {
                console.error('Error updating workout log:', error);
                alert('An error occurred while updating the workout log.');
            });
        });

        // Optionally, load workouts for today's date when the page loads
         const todayCell = document.querySelector('.calendar-table-new td.today');
         if(todayCell) {
             // No need to add selected-date class here, loadWorkouts will do it
             loadWorkouts(todayCell.getAttribute('data-date'));
         } else {
              // If today's cell is not found (e.g., different month), default to the first day of the current month displayed
              const firstDayDisplayed = document.querySelector('.calendar-table-new td[data-date]');
              if(firstDayDisplayed) {
                  firstDayDisplayed.classList.add('selected-date');
                  loadWorkouts(firstDayDisplayed.getAttribute('data-date'));
              }
         }

    </script>
</body>
</html> 