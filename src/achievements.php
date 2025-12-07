<?php
require_once 'config/database.php';
require_once 'config/auth.php';
requireLogin();

$db = new Database();
$conn = $db->getConnection();
$userId = getCurrentUserId();

$stmt = $conn->prepare("SELECT total_reading_minutes, total_books_finished, current_streak FROM users WHERE id = ?");
$stmt->execute([$userId]);
$userStats = $stmt->fetch();

$achievements = [
    ['id' => 'pages_100', 'name' => 'Первые 100 страниц', 'description' => 'Прочитано 100 страниц', 'condition' => function($stats, $conn, $userId) {
        $stmt = $conn->prepare("SELECT SUM(read_pages) as total FROM books WHERE user_id = ?");
        $stmt->execute([$userId]);
        $result = $stmt->fetch();
        return ($result['total'] ?? 0) >= 100;
    }],
    ['id' => 'pages_1000', 'name' => 'Тысяча страниц', 'description' => 'Прочитано 1000 страниц', 'condition' => function($stats, $conn, $userId) {
        $stmt = $conn->prepare("SELECT SUM(read_pages) as total FROM books WHERE user_id = ?");
        $stmt->execute([$userId]);
        $result = $stmt->fetch();
        return ($result['total'] ?? 0) >= 1000;
    }],
    ['id' => 'book_1', 'name' => 'Первая книга', 'description' => 'Завершена первая книга', 'condition' => function($stats) {
        return ($stats['total_books_finished'] ?? 0) >= 1;
    }],
    ['id' => 'book_10', 'name' => 'Десять книг', 'description' => 'Завершено 10 книг', 'condition' => function($stats) {
        return ($stats['total_books_finished'] ?? 0) >= 10;
    }],
    ['id' => 'streak_7', 'name' => 'Неделя чтения', 'description' => 'Streak 7 дней подряд', 'condition' => function($stats) {
        return ($stats['current_streak'] ?? 0) >= 7;
    }],
    ['id' => 'streak_30', 'name' => 'Месяц чтения', 'description' => 'Streak 30 дней подряд', 'condition' => function($stats) {
        return ($stats['current_streak'] ?? 0) >= 30;
    }],
    ['id' => 'minutes_1000', 'name' => 'Тысяча минут', 'description' => 'Прочитано 1000 минут', 'condition' => function($stats) {
        return ($stats['total_reading_minutes'] ?? 0) >= 1000;
    }],
    ['id' => 'minutes_10000', 'name' => 'Десять тысяч минут', 'description' => 'Прочитано 10000 минут', 'condition' => function($stats) {
        return ($stats['total_reading_minutes'] ?? 0) >= 10000;
    }],
];

$unlockedAchievements = [];
foreach ($achievements as $achievement) {
    if ($achievement['condition']($userStats, $conn, $userId)) {
        $unlockedAchievements[] = $achievement;
    }
}

$stmt = $conn->prepare("SELECT name, photo_url, total_reading_minutes 
                        FROM users 
                        ORDER BY total_reading_minutes DESC 
                        LIMIT 10");
$stmt->execute();
$topUsers = $stmt->fetchAll();

$pageTitle = 'Достижения и Рейтинг';
include 'includes/header.php';
?>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">🏆 Личные достижения</h2>
    </div>

    <?php if (empty($unlockedAchievements)): ?>
        <p style="color: var(--text-secondary); text-align: center; padding: 2rem;">
            У вас пока нет достижений. Продолжайте читать, чтобы их заработать!
        </p>
    <?php else: ?>
        <div class="grid grid-3">
            <?php foreach ($unlockedAchievements as $achievement): ?>
                <div class="stat-card">
                    <div style="font-size: 3rem; margin-bottom: 1rem;">🏅</div>
                    <h3 style="color: var(--accent-yellow); margin-bottom: 0.5rem;">
                        <?php echo htmlspecialchars($achievement['name']); ?>
                    </h3>
                    <p style="color: var(--text-secondary);">
                        <?php echo htmlspecialchars($achievement['description']); ?>
                    </p>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div style="margin-top: 2rem;">
        <h3 style="color: var(--accent-yellow); margin-bottom: 1rem;">Все достижения</h3>
        <div class="grid grid-2">
            <?php foreach ($achievements as $achievement): ?>
                <div class="book-item" style="opacity: <?php echo in_array($achievement, $unlockedAchievements) ? '1' : '0.5'; ?>;">
                    <div class="book-info">
                        <h3>
                            <?php if (in_array($achievement, $unlockedAchievements)): ?>
                                ✅ <?php echo htmlspecialchars($achievement['name']); ?>
                            <?php else: ?>
                                🔒 <?php echo htmlspecialchars($achievement['name']); ?>
                            <?php endif; ?>
                        </h3>
                        <p style="color: var(--text-secondary);">
                            <?php echo htmlspecialchars($achievement['description']); ?>
                        </p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">📊 Рейтинг активных пользователей (Топ-10)</h2>
    </div>

    <?php if (empty($topUsers)): ?>
        <p style="color: var(--text-secondary); text-align: center; padding: 2rem;">
            Пока нет пользователей в рейтинге.
        </p>
    <?php else: ?>
        <div style="overflow-x: auto;">
            <table class="table">
                <thead>
                    <tr>
                        <th>Место</th>
                        <th>Фото</th>
                        <th>Имя</th>
                        <th>Общее время чтения (минуты)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($topUsers as $index => $user): ?>
                        <tr>
                            <td>
                                <span class="badge <?php echo $index < 3 ? 'badge-yellow' : 'badge-blue'; ?>">
                                    #<?php echo $index + 1; ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($user['photo_url']): ?>
                                    <img src="<?php echo htmlspecialchars($user['photo_url']); ?>" 
                                         alt="Photo" 
                                         style="width: 50px; height: 50px; border-radius: 50%; object-fit: cover;">
                                <?php else: ?>
                                    <div style="width: 50px; height: 50px; border-radius: 50%; background: var(--bg-tertiary); display: flex; align-items: center; justify-content: center;">
                                        👤
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($user['name']); ?></td>
                            <td style="color: var(--accent-yellow); font-weight: bold;">
                                <?php echo number_format($user['total_reading_minutes']); ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>

