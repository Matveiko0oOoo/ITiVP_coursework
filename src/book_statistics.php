<?php
require_once 'config/database.php';
require_once 'config/auth.php';
requireLogin();

$db = new Database();
$conn = $db->getConnection();
$userId = getCurrentUserId();

$bookId = $_GET['book_id'] ?? null;

if (!$bookId) {
    echo '<div class="alert alert-error">Книга не найдена</div>';
    exit;
}

$stmt = $conn->prepare("SELECT * FROM books WHERE id = ? AND user_id = ?");
$stmt->execute([$bookId, $userId]);
$book = $stmt->fetch();

if (!$book) {
    echo '<div class="alert alert-error">Книга не найдена</div>';
    exit;
}

$stmt = $conn->prepare("SELECT * FROM reading_sessions WHERE book_id = ? AND user_id = ? ORDER BY session_date DESC, created_at DESC");
$stmt->execute([$bookId, $userId]);
$sessions = $stmt->fetchAll();

$totalSessions = count($sessions);
$totalMinutes = 0;
$totalPages = 0;
$firstSessionDate = null;
$lastSessionDate = null;

foreach ($sessions as $session) {
    $totalMinutes += $session['duration_minutes'];
    $totalPages += $session['pages_read'];
    
    if (!$firstSessionDate || $session['session_date'] < $firstSessionDate) {
        $firstSessionDate = $session['session_date'];
    }
    if (!$lastSessionDate || $session['session_date'] > $lastSessionDate) {
        $lastSessionDate = $session['session_date'];
    }
}

$avgMinutesPerSession = $totalSessions > 0 ? round($totalMinutes / $totalSessions, 1) : 0;
$avgPagesPerSession = $totalSessions > 0 ? round($totalPages / $totalSessions, 1) : 0;
$avgPagesPerMinute = $totalMinutes > 0 ? round($totalPages / $totalMinutes, 2) : 0;
$totalHours = round($totalMinutes / 60, 1);
?>

<div style="padding: 1rem;">
    <div class="grid grid-3" style="margin-bottom: 2rem;">
        <div class="stat-card">
            <div class="stat-label">Всего сессий</div>
            <div class="stat-value"><?php echo $totalSessions; ?></div>
        </div>
        
        <div class="stat-card">
            <div class="stat-label">Всего времени</div>
            <div class="stat-value"><?php echo $totalHours; ?></div>
            <div class="stat-label">часов</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-label">Всего страниц</div>
            <div class="stat-value"><?php echo $totalPages; ?></div>
            <div class="stat-label">прочитано</div>
        </div>
    </div>

    <div class="card" style="margin-bottom: 1.5rem;">
        <div class="card-header">
            <h3 class="card-title">Дополнительная статистика</h3>
        </div>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
            <div>
                <p style="color: var(--text-secondary); margin-bottom: 0.5rem;">Среднее время на сессию</p>
                <p style="font-size: 1.5rem; color: var(--accent-yellow); font-weight: bold;">
                    <?php echo $avgMinutesPerSession; ?> мин
                </p>
            </div>
            <div>
                <p style="color: var(--text-secondary); margin-bottom: 0.5rem;">Среднее страниц на сессию</p>
                <p style="font-size: 1.5rem; color: var(--accent-yellow); font-weight: bold;">
                    <?php echo $avgPagesPerSession; ?>
                </p>
            </div>
            <div>
                <p style="color: var(--text-secondary); margin-bottom: 0.5rem;">Скорость чтения</p>
                <p style="font-size: 1.5rem; color: var(--accent-yellow); font-weight: bold;">
                    <?php echo $avgPagesPerMinute; ?> стр/мин
                </p>
            </div>
            <?php if ($firstSessionDate): ?>
            <div>
                <p style="color: var(--text-secondary); margin-bottom: 0.5rem;">Первая сессия</p>
                <p style="font-size: 1.2rem; color: var(--accent-yellow); font-weight: bold;">
                    <?php echo date('d.m.Y', strtotime($firstSessionDate)); ?>
                </p>
            </div>
            <?php endif; ?>
            <?php if ($lastSessionDate): ?>
            <div>
                <p style="color: var(--text-secondary); margin-bottom: 0.5rem;">Последняя сессия</p>
                <p style="font-size: 1.2rem; color: var(--accent-yellow); font-weight: bold;">
                    <?php echo date('d.m.Y', strtotime($lastSessionDate)); ?>
                </p>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">История сессий чтения</h3>
        </div>
        
        <?php if (empty($sessions)): ?>
            <p style="color: var(--text-secondary); text-align: center; padding: 2rem;">
                Пока нет сессий чтения для этой книги. Начните читать, чтобы отслеживать свой прогресс!
            </p>
        <?php else: ?>
            <div style="overflow-x: auto;">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Дата</th>
                            <th>Длительность</th>
                            <th>Страниц прочитано</th>
                            <th>Скорость</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($sessions as $session): 
                            $pagesPerMin = $session['duration_minutes'] > 0 ? round($session['pages_read'] / $session['duration_minutes'], 2) : 0;
                            $sessionDate = date('d.m.Y', strtotime($session['session_date']));
                            $isToday = $session['session_date'] === date('Y-m-d');
                        ?>
                            <tr>
                                <td>
                                    <?php echo $sessionDate; ?>
                                    <?php if ($isToday): ?>
                                        <span class="badge badge-green" style="margin-left: 0.5rem;">Сегодня</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $session['duration_minutes']; ?> мин</td>
                                <td><?php echo $session['pages_read']; ?> стр</td>
                                <td><?php echo $pagesPerMin; ?> стр/мин</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

