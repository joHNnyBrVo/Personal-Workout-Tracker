<?php
require_once 'config/database.php';

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Validation
    if (empty($username)) {
        $errors[] = "Username is required";
    }

    if (empty($email)) {
        $errors[] = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    }

    if (empty($password)) {
        $errors[] = "Password is required";
    } elseif (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters long";
    } elseif (!preg_match("/[A-Z]/", $password)) {
        $errors[] = "Password must contain at least one uppercase letter";
    } elseif (!preg_match("/[0-9]/", $password)) {
        $errors[] = "Password must contain at least one number";
    } elseif (!preg_match("/[!@#$%^&*()\-_=+{};:,<.>]/", $password)) {
        $errors[] = "Password must contain at least one special character";
    }

    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match";
    }

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
            $stmt->execute([$username, $email, $password]);
            $success = true;
        } catch(PDOException $e) {
            $errors[] = "Registration failed: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Workout Tracker</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/auth.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
</head>
<body class="auth-page">
    <div class="auth-container">
        <h1>Create Your Account</h1>
            
        <?php if ($success): ?>
            <div class="alert alert-success">
                Registration successful! <a href="login.php">Login here</a>
            </div>
        <?php endif; ?>

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
                <input type="text" id="username" name="username" class="form-control" value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
            </div>

            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" class="form-control" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
            </div>

            <div class="form-group">
                <label for="password">Password:</label>
                <div class="password-input-container">
                    <input type="password" id="password" name="password" class="form-control" required>
                    <span class="toggle-password"><i class="fas fa-eye"></i></span>
                </div>
                <div class="password-strength-indicator"></div>
                <small>Password must be at least 8 characters long and contain uppercase, number, and special character</small>
            </div>

            <div class="form-group">
                <label for="confirm_password">Confirm Password:</label>
                <div class="password-input-container">
                    <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
                    <span class="toggle-password"><i class="fas fa-eye"></i></span>
                </div>
            </div>

            <button type="submit" class="btn btn-primary">Register</button>
        </form>

        <p class="auth-links">Already have an account? <a href="login.php">Login here</a></p>
    </div>

    <script>
        const passwordInput = document.getElementById('password');
        const confirmPasswordInput = document.getElementById('confirm_password');
        const strengthIndicator = document.querySelector('.password-strength-indicator');
        const togglePasswordIcons = document.querySelectorAll('.toggle-password i');

        if (passwordInput) {
            passwordInput.addEventListener('input', updatePasswordStrength);
        }

        function updatePasswordStrength() {
            const password = passwordInput.value;
            let strength = 0;

            // Check for length
            if (password.length >= 8) {
                strength += 1;
            }

            // Check for uppercase letters
            if (/[A-Z]/.test(password)) {
                strength += 1;
            }

            // Check for numbers
            if (/[0-9]/.test(password)) {
                strength += 1;
            }

            // Check for special characters
            if (/[!@#$%^&*()\-_=+{};:,<.>]/g.test(password)) {
                strength += 1;
            }

            // Update indicator
            strengthIndicator.className = 'password-strength-indicator'; // Reset classes
            if (strength === 0) {
                strengthIndicator.style.width = '0%';
            } else if (strength === 1) {
                strengthIndicator.classList.add('weak');
            } else if (strength === 2 || strength === 3) {
                strengthIndicator.classList.add('medium');
            } else if (strength === 4) {
                strengthIndicator.classList.add('strong');
            }
        }

        // Toggle password visibility
        togglePasswordIcons.forEach(icon => {
            if (icon) {
                icon.parentElement.addEventListener('click', () => {
                    const input = icon.parentElement.previousElementSibling;
                    if (input.type === 'password') {
                        input.type = 'text';
                        icon.classList.remove('fa-eye');
                        icon.classList.add('fa-eye-slash');
                    } else {
                        input.type = 'password';
                        icon.classList.remove('fa-eye-slash');
                        icon.classList.add('fa-eye');
                    }
                });
            }
        });
    </script>
</body>
</html> 