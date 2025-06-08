<?php
session_start(); // Start the session
require_once 'config/database.php';

$errors = [];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    if (empty($username) || empty($password)) {
        $errors[] = "Both username and password are required";
    } else {
        try {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND password = ?");
            $stmt->execute([$username, $password]);
            $user = $stmt->fetch();

            if ($user) {
                // Login successful, set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                // Redirect to dashboard page
                header("Location: dashboard.php");
                exit();
            } else {
                $errors[] = "Invalid username or password";
            }
        } catch(PDOException $e) {
            $errors[] = "Login failed: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Workout Tracker</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/auth.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
</head>
<body class="auth-page">
    <div class="auth-container">
        <h1>Login to Your Account</h1>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <?php foreach ($errors as $error): ?>
                    <p><?php echo htmlspecialchars($error); ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="" class="auth-form">
            <div class="form-group">
                <label for="username">Username:</label>
                <input type="text" id="username" name="username" class="form-control" value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" required>
            </div>

            <div class="form-group">
                <label for="password">Password:</label>
                <div class="password-input-container">
                    <input type="password" id="password" name="password" class="form-control" required>
                    <span class="toggle-password"><i class="fas fa-eye"></i></span>
                </div>
            </div>

            <button type="submit" class="btn btn-primary">Login</button>
        </form>

        <p class="auth-links">Don't have an account? <a href="register.php">Register here</a></p>
    </div>

    <script>
        const togglePasswordIcon = document.querySelector('.toggle-password i');

        if (togglePasswordIcon) {
            togglePasswordIcon.parentElement.addEventListener('click', () => {
                const input = togglePasswordIcon.parentElement.previousElementSibling;
                if (input.type === 'password') {
                    input.type = 'text';
                    togglePasswordIcon.classList.remove('fa-eye');
                    togglePasswordIcon.classList.add('fa-eye-slash');
                } else {
                    input.type = 'password';
                    togglePasswordIcon.classList.remove('fa-eye-slash');
                    togglePasswordIcon.classList.add('fa-eye');
                }
            });
        }
    </script>
</body>
</html> 