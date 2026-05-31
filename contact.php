<?php
$pageTitle = 'Contact Us';
require_once 'includes/db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = sanitizeInput($_POST['name'] ?? '');
    $email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
    $subject = sanitizeInput($_POST['subject'] ?? '');
    $message = sanitizeInput($_POST['message'] ?? '');
    
    $stmt = $pdo->prepare("INSERT INTO contact_messages (name, email, subject, message) VALUES (?, ?, ?, ?)");
    if ($stmt->execute([$name, $email, $subject, $message])) {
        flashMessage('success', 'Message sent successfully! We will get back to you soon.');
    } else {
        flashMessage('danger', 'Failed to send message. Please try again.');
    }
}

require_once 'includes/header.php';
?>

<section class="section" style="padding-top: 100px;">
    <div style="max-width: 800px; margin: 0 auto;">
        <div class="section-header">
            <h2><i class="fas fa-envelope" style="color: var(--neon-blue);"></i> Contact Us</h2>
            <p>Have questions or feedback? We'd love to hear from you</p>
            <div class="section-line"></div>
        </div>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 30px; margin-bottom: 40px;">
            <div style="text-align: center; padding: 30px; background: var(--secondary-black); border-radius: 16px; border: 1px solid var(--border-color);">
                <i class="fas fa-envelope" style="font-size: 2.5rem; color: var(--neon-blue); margin-bottom: 15px;"></i>
                <h4>Email</h4>
                <p style="color: var(--text-secondary);">admin@gaminghub.com</p>
            </div>
            <div style="text-align: center; padding: 30px; background: var(--secondary-black); border-radius: 16px; border: 1px solid var(--border-color);">
                <i class="fas fa-discord" style="font-size: 2.5rem; color: var(--neon-purple); margin-bottom: 15px;"></i>
                <h4>Discord</h4>
                <p style="color: var(--text-secondary);">discord.gg/gaminghub</p>
            </div>
            <div style="text-align: center; padding: 30px; background: var(--secondary-black); border-radius: 16px; border: 1px solid var(--border-color);">
                <i class="fas fa-clock" style="font-size: 2.5rem; color: var(--accent-green); margin-bottom: 15px;"></i>
                <h4>Response Time</h4>
                <p style="color: var(--text-secondary);">Within 24 hours</p>
            </div>
        </div>
        
        <div class="form-container" style="max-width: 100%;">
            <form method="POST" action="">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div class="form-group">
                        <label class="form-label">Your Name</label>
                        <input type="text" name="name" class="form-control" required value="<?= isLoggedIn() ? sanitizeInput($_SESSION['user_name']) : '' ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email Address</label>
                        <input type="email" name="email" class="form-control" required value="<?= isLoggedIn() ? sanitizeInput($_SESSION['user_email']) : '' ?>">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Subject</label>
                    <input type="text" name="subject" class="form-control" required placeholder="What is this about?">
                </div>
                <div class="form-group">
                    <label class="form-label">Message</label>
                    <textarea name="message" class="form-control" required placeholder="Tell us more..."></textarea>
                </div>
                <button type="submit" class="btn btn-primary" style="width: 100%;">
                    <i class="fas fa-paper-plane"></i> Send Message
                </button>
            </form>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>