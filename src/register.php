<?php
require_once 'config/database.php';
require_once 'config/auth.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($name) || empty($email) || empty($password)) {
        $error = 'Все поля обязательны для заполнения';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Некорректный email адрес';
    } elseif (strlen($password) < 8) {
        $error = 'Пароль должен содержать минимум 8 символов';
    } elseif (!preg_match('/[a-zA-Zа-яА-Я]/', $password)) {
        $error = 'Пароль должен содержать хотя бы одну букву';
    } elseif ($password !== $confirm_password) {
        $error = 'Пароли не совпадают';
    } else {
        $db = new Database();
        $conn = $db->getConnection();

        if (!$conn) {
            $error = 'Ошибка подключения к базе данных. Проверьте настройки подключения.';
        } else {
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $error = 'Пользователь с таким email уже существует';
            } else {
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("INSERT INTO users (name, email, password_hash) VALUES (?, ?, ?)");
                if ($stmt->execute([$name, $email, $password_hash])) {
                    $success = 'Регистрация успешна! Теперь вы можете войти.';
                    header('refresh:2;url=login.php');
                } else {
                    $error = 'Ошибка при регистрации. Попробуйте снова.';
                }
            }
        }
    }
}

$pageTitle = 'Регистрация';
include 'includes/header.php';
?>

<div class="card" style="max-width: 500px; margin: 2rem auto;">
    <div class="card-header">
        <h2 class="card-title">Регистрация</h2>
    </div>
    
    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <form method="POST" action="" id="registerForm">
        <div class="form-group">
            <label class="form-label" for="name">Имя</label>
            <input type="text" class="form-input" id="name" name="name" required 
                   value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>">
        </div>

        <div class="form-group">
            <label class="form-label" for="email">Email</label>
            <input type="email" class="form-input" id="email" name="email" required
                   value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
        </div>

        <div class="form-group">
            <label class="form-label" for="password">Пароль (минимум 8 символов, включая букву)</label>
            <input type="password" class="form-input" id="password" name="password" required minlength="8">
            <small style="color: var(--text-secondary);">Минимум 8 символов, должна быть хотя бы одна буква</small>
        </div>

        <div class="form-group">
            <label class="form-label" for="confirm_password">Подтвердите пароль</label>
            <input type="password" class="form-input" id="confirm_password" name="confirm_password" required>
        </div>

        <button type="submit" class="btn btn-primary" style="width: 100%;">Зарегистрироваться</button>
    </form>

    <p style="text-align: center; margin-top: 1rem;">
        Уже есть аккаунт? <a href="login.php" style="color: var(--accent-yellow);">Войти</a>
    </p>
</div>

<?php include 'includes/footer.php'; ?>

