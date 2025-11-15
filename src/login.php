<?php
require_once 'config/database.php';
require_once 'config/auth.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: /index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Введите email и пароль';
    } else {
        $db = new Database();
        $conn = $db->getConnection();

        if (!$conn) {
            $error = 'Ошибка подключения к базе данных. Проверьте настройки подключения.';
        } else {
            $stmt = $conn->prepare("SELECT id, name, email, password_hash FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password_hash'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email'] = $user['email'];
                header('Location: /index.php');
                exit;
            } else {
                $error = 'Неверный email или пароль';
            }
        }
    }
}

$pageTitle = 'Вход';
include 'includes/header.php';
?>

<div class="card" style="max-width: 500px; margin: 2rem auto;">
    <div class="card-header">
        <h2 class="card-title">Вход в систему</h2>
    </div>
    
    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form method="POST" action="" id="loginForm">
        <div class="form-group">
            <label class="form-label" for="email">Email</label>
            <input type="email" class="form-input" id="email" name="email" required
                   value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
        </div>

        <div class="form-group">
            <label class="form-label" for="password">Пароль</label>
            <input type="password" class="form-input" id="password" name="password" required>
        </div>

        <button type="submit" class="btn btn-primary" style="width: 100%;">Войти</button>
    </form>

    <p style="text-align: center; margin-top: 1rem;">
        Нет аккаунта? <a href="register.php" style="color: var(--accent-yellow);">Зарегистрироваться</a>
    </p>
</div>

<?php include 'includes/footer.php'; ?>

