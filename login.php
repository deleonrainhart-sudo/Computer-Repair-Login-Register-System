<?php
require 'core/bootstrap.php';
if (signed_in()) go('index.php');
$error='';
if ($_SERVER['REQUEST_METHOD']==='POST') {
    validate_csrf();
    $s=$pdo->prepare('SELECT user_id,username,password_hash FROM users WHERE username=?');
    $s->execute([trim($_POST['username']??'')]); $user=$s->fetch();
    if ($user && password_verify($_POST['password']??'', $user['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['user_id']=(int)$user['user_id']; $_SESSION['username']=$user['username'];
        alert('Successfully logged in.'); go('index.php');
    }
    $error='Invalid username or password.';
}
page_header('Login');
?>
<div class="auth-layout">
    <aside class="auth-story">
        <div>
            <span class="auth-stamp"><span class="stamp-icon"><?= ui_icon('tool', 20) ?></span> RAINHART'S PC REPAIR</span>
            <h2>Computer repair<br>record system.</h2>
            <p>A simple system to save customer details and their devices.</p>
        </div>
        <div class="auth-story-bottom"><?= ui_icon('shield', 15) ?> &nbsp;User login · Record activity</div>
    </aside>
    <section class="auth-form-section">
        <div class="auth-kicker">WELCOME BACK</div>
        <h1>Log in to your account</h1>
        <p class="auth-description">Enter your username and password to manage records.</p>
        <?php if ($error): ?><div class="error" role="alert"><?= h($error) ?></div><?php endif; ?>
        <form method="post" class="form">
            <input type="hidden" name="csrf" value="<?= h(csrf()) ?>">
            <label>Username
                <input name="username" placeholder="Enter your username" required autocomplete="username" value="<?= h($_POST['username'] ?? '') ?>">
            </label>
            <label>Password
                <input type="password" name="password" placeholder="Enter your password" required autocomplete="current-password">
            </label>
            <button class="primary-btn auth-full-btn" type="submit">Log in <?= ui_icon('arrow-right', 16) ?></button>
        </form>
        <p class="auth-switch">No account yet? <a href="register.php">Create an account</a></p>
        <p class="auth-legal">Log in first to access customer and device records.</p>
    </section>
</div>
<?php page_footer(); ?>
