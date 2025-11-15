<?php
require_once 'config/database.php';
require_once 'config/auth.php';
requireLogin();

$db = new Database();
$conn = $db->getConnection();
$userId = getCurrentUserId();

// Get current reading book
$stmt = $conn->prepare("SELECT * FROM books WHERE user_id = ? AND status = 'Reading' ORDER BY updated_at DESC LIMIT 1");
$stmt->execute([$userId]);
$currentBook = $stmt->fetch();

// Get user stats
$stmt = $conn->prepare("SELECT total_reading_minutes, total_books_finished, current_streak FROM users WHERE id = ?");
$stmt->execute([$userId]);
$userStats = $stmt->fetch();

// Get today's reading stats
$today = date('Y-m-d');
$stmt = $conn->prepare("SELECT SUM(duration_minutes) as total_minutes, SUM(pages_read) as total_pages 
                        FROM reading_sessions WHERE user_id = ? AND session_date = ?");
$stmt->execute([$userId, $today]);
$todayStats = $stmt->fetch();

$pageTitle = 'Дашборд';
include 'includes/header.php';
?>

<div class="grid grid-3">
    <div class="stat-card">
        <div class="stat-label">Текущий Streak</div>
        <div class="stat-value"><?php echo $userStats['current_streak'] ?? 0; ?></div>
        <div class="stat-label">дней подряд</div>
    </div>

    <div class="stat-card">
        <div class="stat-label">Сегодня минут</div>
        <div class="stat-value"><?php echo $todayStats['total_minutes'] ?? 0; ?></div>
        <div class="stat-label">время чтения</div>
    </div>

    <div class="stat-card">
        <div class="stat-label">Сегодня страниц</div>
        <div class="stat-value"><?php echo $todayStats['total_pages'] ?? 0; ?></div>
        <div class="stat-label">прочитано</div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">Сейчас читаю</h2>
    </div>
    
    <?php if ($currentBook): ?>
        <div class="book-item">
            <div class="book-info">
                <h3><?php echo htmlspecialchars($currentBook['title']); ?></h3>
                <p>Автор: <?php echo htmlspecialchars($currentBook['author']); ?></p>
                <p>Прогресс: <?php echo $currentBook['read_pages']; ?> / <?php echo $currentBook['total_pages']; ?> страниц</p>
                <div class="book-progress">
                    <div class="progress-bar" style="width: <?php echo ($currentBook['read_pages'] / $currentBook['total_pages']) * 100; ?>%"></div>
                </div>
            </div>
            <a href="reading_session.php?book_id=<?php echo $currentBook['id']; ?>" class="btn btn-primary">
                Начать/Продолжить чтение
            </a>
        </div>
    <?php else: ?>
        <p style="color: var(--text-secondary); text-align: center; padding: 2rem;">
            У вас нет активных книг. <a href="books.php" style="color: var(--accent-yellow);">Добавьте книгу</a> или начните читать из списка "To Read".
        </p>
    <?php endif; ?>
</div>

<div class="grid grid-2">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Общая статистика</h3>
        </div>
        <div class="stat-card">
            <div class="stat-label">Всего минут чтения</div>
            <div class="stat-value"><?php echo number_format($userStats['total_reading_minutes'] ?? 0); ?></div>
        </div>
        <div class="stat-card" style="margin-top: 1rem;">
            <div class="stat-label">Прочитано книг</div>
            <div class="stat-value"><?php echo $userStats['total_books_finished'] ?? 0; ?></div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Быстрые действия</h3>
        </div>
        <div style="display: flex; flex-direction: column; gap: 1rem;">
            <a href="books.php" class="btn btn-primary">Управление книгами</a>
            <a href="statistics.php" class="btn btn-secondary">Подробная статистика</a>
            <a href="memos.php" class="btn btn-secondary">Мои заметки</a>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

