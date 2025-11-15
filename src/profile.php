<?php
require_once 'config/database.php';
require_once 'config/auth.php';
requireLogin();

$db = new Database();
$conn = $db->getConnection();
$userId = getCurrentUserId();

$error = '';
$success = '';

// Get user data
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

if (!$user) {
    header('Location: /logout.php');
    exit;
}

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if (empty($name) || empty($email)) {
        $error = 'Имя и email обязательны';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Некорректный email адрес';
    } else {
        // Check if email is taken by another user
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt->execute([$email, $userId]);
        if ($stmt->fetch()) {
            $error = 'Этот email уже используется другим пользователем';
        } else {
            $photoUrl = $user['photo_url'];

            // Handle photo upload
            if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = __DIR__ . '/uploads/photos/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }

                $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
                if (in_array($_FILES['photo']['type'], $allowedTypes)) {
                    $fileName = uniqid() . '_' . basename($_FILES['photo']['name']);
                    $targetPath = $uploadDir . $fileName;

                    if (move_uploaded_file($_FILES['photo']['tmp_name'], $targetPath)) {
                        // Delete old photo if exists
                        if ($photoUrl && file_exists(__DIR__ . $photoUrl)) {
                            unlink(__DIR__ . $photoUrl);
                        }
                        $photoUrl = '/uploads/photos/' . $fileName;
                    }
                }
            }

            $stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, description = ?, photo_url = ? WHERE id = ?");
            if ($stmt->execute([$name, $email, $description, $photoUrl, $userId])) {
                $success = 'Профиль успешно обновлен!';
                $_SESSION['user_name'] = $name;
                $_SESSION['user_email'] = $email;
                
                // Reload user data
                $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
                $stmt->execute([$userId]);
                $user = $stmt->fetch();
            } else {
                $error = 'Ошибка при обновлении профиля';
            }
        }
    }
}

$pageTitle = 'Профиль';
include 'includes/header.php';
?>

<div class="card" style="max-width: 800px; margin: 2rem auto;">
    <div class="card-header">
        <h2 class="card-title">Личный профиль</h2>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <div class="profile-header">
        <?php if ($user['photo_url']): ?>
            <img src="<?php echo htmlspecialchars($user['photo_url']); ?>" alt="Profile Photo" class="profile-photo">
        <?php else: ?>
            <div class="profile-photo" style="background: var(--bg-tertiary); display: flex; align-items: center; justify-content: center; font-size: 4rem;">
                👤
            </div>
        <?php endif; ?>
        <h2 style="color: var(--accent-yellow); margin-bottom: 0.5rem;">
            <?php echo htmlspecialchars($user['name']); ?>
        </h2>
        <p style="color: var(--text-secondary);"><?php echo htmlspecialchars($user['email']); ?></p>
    </div>

    <form method="POST" action="" enctype="multipart/form-data">
        <div class="form-group">
            <label class="form-label" for="name">Имя</label>
            <input type="text" class="form-input" id="name" name="name" required
                   value="<?php echo htmlspecialchars($user['name']); ?>">
        </div>

        <div class="form-group">
            <label class="form-label" for="email">Email</label>
            <input type="email" class="form-input" id="email" name="email" required
                   value="<?php echo htmlspecialchars($user['email']); ?>">
        </div>

        <div class="form-group">
            <label class="form-label" for="description">Описание</label>
            <textarea class="form-textarea" id="description" name="description" 
                      placeholder="Расскажите о себе..."><?php echo htmlspecialchars($user['description'] ?? ''); ?></textarea>
        </div>

        <div class="form-group">
            <label class="form-label" for="photo">Фотография профиля</label>
            <input type="file" class="form-input" id="photo" name="photo" accept="image/jpeg,image/png,image/gif">
            <small style="color: var(--text-secondary);">Максимальный размер: 5MB. Форматы: JPG, PNG, GIF</small>
        </div>

        <button type="submit" class="btn btn-primary" style="width: 100%;">Сохранить изменения</button>
    </form>
</div>

<div class="card" style="max-width: 800px; margin: 2rem auto;">
    <div class="card-header">
        <h3 class="card-title">Итоговая статистика</h3>
    </div>
    
    <div class="grid grid-3">
        <div class="stat-card">
            <div class="stat-label">Всего минут</div>
            <div class="stat-value"><?php echo number_format($user['total_reading_minutes']); ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Прочитано книг</div>
            <div class="stat-value"><?php echo $user['total_books_finished']; ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Текущий streak</div>
            <div class="stat-value"><?php echo $user['current_streak']; ?></div>
            <div class="stat-label">дней</div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

