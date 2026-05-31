<?php
$pageTitle = 'Register';
require_once 'includes/db.php';

if (isLoggedIn()) redirect('index.php');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $fullName = sanitizeInput($_POST['full_name'] ?? '');
    $username = sanitizeInput($_POST['username'] ?? '');
    $email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'] ?? '';
    
    // Validation
    if (strlen($password) < 6) {
        flashMessage('danger', 'Password must be at least 6 characters.');
    } else {
        // Check existing
        $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ? OR username = ?");
        $stmt->execute([$email, $username]);
        
        if ($stmt->fetch()) {
            flashMessage('danger', 'Email or username already exists.');
        } else {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("INSERT INTO users (full_name, username, email, password) VALUES (?, ?, ?, ?)");
            
            if ($stmt->execute([$fullName, $username, $email, $hash])) {
                flashMessage('success', 'Account created! Please login.');
                redirect('login.php');
            } else {
                flashMessage('danger', 'Registration failed. Try again.');
            }
        }
    }
}

require_once 'includes/header.php';
?>

<section class="section" style="min-height: 100vh; display: flex; align-items: center; justify-content: center; padding-top: 100px;">
    <div class="form-container" style="width: 100%;">
        <div style="text-align: center; margin-bottom: 30px;">
            <h2 style="font-size: 2rem;"><i class="fas fa-user-plus" style="color: var(--neon-purple);"></i> Create Account</h2>
            <p style="color: var(--text-secondary);">Join the Gaming Hub community</p>
        </div>
        
        <form method="POST" action="">
            <div class="form-group">
                <label class="form-label">Full Name</label>
                <input type="text" name="full_name" class="form-control" placeholder="Enter your full name" required>
            </div>
            
            <div class="form-group">
                <label class="form-label">Username</label>
                <input type="text" name="username" class="form-control" placeholder="Choose a username" required>
            </div>
            
            <div class="form-group">
                <label class="form-label">Email Address</label>
                <input type="email" name="email" class="form-control" placeholder="Enter your email" required>
            </div>
            
            <div class="form-group">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" placeholder="Create a password (min 6 chars)" required minlength="6">
            </div>
            
            <button type="submit" class="btn btn-primary" style="width: 100%; margin-bottom: 20px;">
                <i class="fas fa-rocket"></i> Create Account
            </button>
            
            <div style="text-align: center; color: var(--text-secondary);">
                Already have an account? <a href="login.php" style="color: var(--neon-blue);">Login here</a>
            </div>
        </form>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>