<?php
require_once 'config/database.php';
require_once 'config/auth.php';
requireLogin();

$db = new Database();
$conn = $db->getConnection();
$userId = getCurrentUserId();

// Get user total stats
$stmt = $conn->prepare("SELECT total_reading_minutes, total_books_finished, current_streak FROM users WHERE id = ?");
$stmt->execute([$userId]);
$totalStats = $stmt->fetch();

// Get today's stats
$today = date('Y-m-d');
$stmt = $conn->prepare("SELECT SUM(duration_minutes) as minutes, SUM(pages_read) as pages 
                        FROM reading_sessions WHERE user_id = ? AND session_date = ?");
$stmt->execute([$userId, $today]);
$todayStats = $stmt->fetch();

// Get this year's stats
$yearStart = date('Y-01-01');
$stmt = $conn->prepare("SELECT SUM(duration_minutes) as minutes, SUM(pages_read) as pages, COUNT(*) as sessions
                        FROM reading_sessions WHERE user_id = ? AND session_date >= ?");
$stmt->execute([$userId, $yearStart]);
$yearStats = $stmt->fetch();

// Get monthly stats for last 12 months
$monthlyStats = [];
for ($i = 11; $i >= 0; $i--) {
    $monthStart = date('Y-m-01', strtotime("-$i months"));
    $monthEnd = date('Y-m-t', strtotime("-$i months"));
    $monthName = date('M Y', strtotime("-$i months"));
    
    $stmt = $conn->prepare("SELECT SUM(duration_minutes) as minutes, SUM(pages_read) as pages
                            FROM reading_sessions WHERE user_id = ? AND session_date >= ? AND session_date <= ?");
    $stmt->execute([$userId, $monthStart, $monthEnd]);
    $monthData = $stmt->fetch();
    
    $monthlyStats[] = [
        'month' => $monthName,
        'minutes' => $monthData['minutes'] ?? 0,
        'pages' => $monthData['pages'] ?? 0
    ];
}

$pageTitle = 'Статистика';
include 'includes/header.php';
?>

<div class="grid grid-3">
    <div class="stat-card">
        <div class="stat-label">Сегодня</div>
        <div class="stat-value"><?php echo $todayStats['minutes'] ?? 0; ?></div>
        <div class="stat-label">минут</div>
        <div style="margin-top: 0.5rem; color: var(--text-secondary);">
            <?php echo $todayStats['pages'] ?? 0; ?> страниц
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-label">В этом году</div>
        <div class="stat-value"><?php echo number_format($yearStats['minutes'] ?? 0); ?></div>
        <div class="stat-label">минут</div>
        <div style="margin-top: 0.5rem; color: var(--text-secondary);">
            <?php echo number_format($yearStats['pages'] ?? 0); ?> страниц
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-label">Всего</div>
        <div class="stat-value"><?php echo number_format($totalStats['total_reading_minutes'] ?? 0); ?></div>
        <div class="stat-label">минут</div>
        <div style="margin-top: 0.5rem; color: var(--text-secondary);">
            <?php echo $totalStats['total_books_finished'] ?? 0; ?> книг
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">Статистика за последние 12 месяцев</h2>
    </div>
    
    <div style="overflow-x: auto;">
        <table class="table">
            <thead>
                <tr>
                    <th>Месяц</th>
                    <th>Минут чтения</th>
                    <th>Прочитано страниц</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($monthlyStats as $stat): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($stat['month']); ?></td>
                        <td><?php echo number_format($stat['minutes']); ?></td>
                        <td><?php echo number_format($stat['pages']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="grid grid-2">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Общая статистика</h3>
        </div>
        <div style="padding: 1rem;">
            <p style="margin-bottom: 1rem;">
                <strong>Всего минут чтения:</strong> 
                <span style="color: var(--accent-yellow);"><?php echo number_format($totalStats['total_reading_minutes'] ?? 0); ?></span>
            </p>
            <p style="margin-bottom: 1rem;">
                <strong>Прочитано книг:</strong> 
                <span style="color: var(--accent-yellow);"><?php echo $totalStats['total_books_finished'] ?? 0; ?></span>
            </p>
            <p style="margin-bottom: 1rem;">
                <strong>Текущий streak:</strong> 
                <span style="color: var(--accent-yellow);"><?php echo $totalStats['current_streak'] ?? 0; ?> дней</span>
            </p>
            <p>
                <strong>Сессий в этом году:</strong> 
                <span style="color: var(--accent-yellow);"><?php echo $yearStats['sessions'] ?? 0; ?></span>
            </p>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Ежедневная статистика</h3>
        </div>
        <div style="padding: 1rem;">
            <p style="margin-bottom: 1rem;">
                <strong>Сегодня прочитано:</strong> 
                <span style="color: var(--accent-yellow);"><?php echo $todayStats['minutes'] ?? 0; ?> минут</span>
            </p>
            <p>
                <strong>Сегодня страниц:</strong> 
                <span style="color: var(--accent-yellow);"><?php echo $todayStats['pages'] ?? 0; ?></span>
            </p>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

