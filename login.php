<?php
$pageTitle = 'Login';
require_once 'includes/db.php';

if (isLoggedIn()) redirect('index.php');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND status = 'active'");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['user_name'] = $user['full_name'];
        $_SESSION['user_username'] = $user['username'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_avatar'] = $user['avatar'];
        $_SESSION['user_role'] = $user['role'];
        
        flashMessage('success', 'Welcome back, ' . $user['full_name'] . '!');
        
        if ($user['role'] == 'admin') {
            redirect('admin/index.php');
        }
        redirect('index.php');
    } else {
        flashMessage('danger', 'Invalid email or password.');
    }
}

require_once 'includes/header.php';
?>

<section class="section" style="min-height: 100vh; display: flex; align-items: center; justify-content: center; padding-top: 100px;">
    <div class="form-container" style="width: 100%;">
        <div style="text-align: center; margin-bottom: 30px;">
            <h2 style="font-size: 2rem;"><i class="fas fa-sign-in-alt" style="color: var(--neon-blue);"></i> Login</h2>
            <p style="color: var(--text-secondary);">Welcome back, gamer!</p>
        </div>
        
        <form method="POST" action="">
            <div class="form-group">
                <label class="form-label">Email Address</label>
                <input type="email" name="email" class="form-control" placeholder="Enter your email" required>
            </div>
            
            <div class="form-group">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" placeholder="Enter your password" required>
            </div>
            
            <button type="submit" class="btn btn-primary" style="width: 100%; margin-bottom: 20px;">
                <i class="fas fa-sign-in-alt"></i> Login
            </button>
            
            <div style="text-align: center; color: var(--text-secondary);">
                Don't have an account? <a href="register.php" style="color: var(--neon-blue);">Register here</a>
            </div>
        </form>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>