<?php
require 'core/bootstrap.php';
if (signed_in()) go('index.php');
$error='';
if ($_SERVER['REQUEST_METHOD']==='POST') {
    validate_csrf();
    $first=trim($_POST['first_name']??''); $last=trim($_POST['last_name']??'');
    $username=trim($_POST['username']??''); $email=trim($_POST['email']??''); $password=$_POST['password']??'';
    if (!$first || !$last || !$username || !filter_var($email,FILTER_VALIDATE_EMAIL) || strlen($password)<8) $error='Fill every field, enter a valid email, and use at least 8 password characters.';
    else {
        try {
            $s=$pdo->prepare('INSERT INTO users(first_name,last_name,username,email,password_hash) VALUES(?,?,?,?,?)');
            $s->execute([$first,$last,$username,$email,password_hash($password,PASSWORD_DEFAULT)]);
            alert('Registration successful. Log in to continue.'); go('login.php');
        } catch (PDOException $e) { if ($e->getCode()==='23000') $error='Username or email is already taken.'; else throw $e; }
    }
}
page_header('Register Account');
?>
<div class="auth-layout">
    <aside class="auth-story">
        <div>
            <span class="auth-stamp"><span class="stamp-icon"><?= ui_icon('user-plus', 21) ?></span> RAINHART'S PC REPAIR</span>
            <h2>Create your<br>user account.</h2>
            <p>Register to use the customer and device record system.</p>
        </div>
        <div class="auth-story-bottom"><?= ui_icon('shield', 15) ?> &nbsp;The system tracks changes made to records</div>
    </aside>
    <section class="auth-form-section">
        <div class="auth-kicker">GET STARTED</div>
        <h1>Create an account<span style="color:var(--earth-taupe)">.</span></h1>
        <p class="auth-description">Fill in your information to create an account.</p>
        <?php if ($error): ?><div class="error" role="alert"><?= h($error) ?></div><?php endif; ?>
        <form method="post" class="form">
            <input type="hidden" name="csrf" value="<?= h(csrf()) ?>">
            <div class="auth-pair">
                <label>First name<input name="first_name" placeholder="First name" maxlength="50" required autocomplete="given-name" value="<?= h($_POST['first_name'] ?? '') ?>"></label>
                <label>Last name<input name="last_name" placeholder="Last name" maxlength="50" required autocomplete="family-name" value="<?= h($_POST['last_name'] ?? '') ?>"></label>
            </div>
            <label>Username<input name="username" placeholder="Choose a username" maxlength="50" required autocomplete="username" value="<?= h($_POST['username'] ?? '') ?>"></label>
            <label>Email address<input type="email" name="email" placeholder="you@example.com" maxlength="120" required autocomplete="email" value="<?= h($_POST['email'] ?? '') ?>"></label>
            <label>Password<input type="password" name="password" placeholder="At least 8 characters" minlength="8" required autocomplete="new-password"></label>
            <button class="primary-btn auth-full-btn" type="submit">Create account <?= ui_icon('arrow-right', 16) ?></button>
        </form>
        <p class="auth-switch">Already have an account? <a href="login.php">Log in</a></p>
    </section>
</div>
<?php page_footer(); ?>
