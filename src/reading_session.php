<?php
require_once 'config/database.php';
require_once 'config/auth.php';
requireLogin();

$db = new Database();
$conn = $db->getConnection();
$userId = getCurrentUserId();

$bookId = $_GET['book_id'] ?? null;
$error = '';
$success = '';

if (!$bookId) {
    header('Location: /index.php');
    exit;
}

// Get book info
$stmt = $conn->prepare("SELECT * FROM books WHERE id = ? AND user_id = ?");
$stmt->execute([$bookId, $userId]);
$book = $stmt->fetch();

if (!$book) {
    header('Location: /index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $duration = intval($_POST['duration_minutes'] ?? 0);
    $pages = intval($_POST['pages_read'] ?? 0);
    $sessionDate = $_POST['session_date'] ?? date('Y-m-d');

    if ($duration <= 0 || $pages < 0) {
        $error = 'Введите корректные значения';
    } elseif ($pages + $book['read_pages'] > $book['total_pages']) {
        $error = 'Количество прочитанных страниц не может превышать общее количество страниц';
    } else {
        // Create reading session
        $stmt = $conn->prepare("INSERT INTO reading_sessions (user_id, book_id, session_date, duration_minutes, pages_read) 
                                VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$userId, $bookId, $sessionDate, $duration, $pages]);

        // Update book progress
        $newReadPages = min($book['read_pages'] + $pages, $book['total_pages']);
        $stmt = $conn->prepare("UPDATE books SET read_pages = ? WHERE id = ?");
        $stmt->execute([$newReadPages, $bookId]);

        // Update book status if finished
        if ($newReadPages >= $book['total_pages'] && $book['status'] !== 'Finished') {
            $stmt = $conn->prepare("UPDATE books SET status = 'Finished' WHERE id = ?");
            $stmt->execute([$bookId]);

            // Update user stats
            $stmt = $conn->prepare("UPDATE users SET total_books_finished = total_books_finished + 1 WHERE id = ?");
            $stmt->execute([$userId]);
        }

        // Update user total reading minutes
        $stmt = $conn->prepare("UPDATE users SET total_reading_minutes = total_reading_minutes + ? WHERE id = ?");
        $stmt->execute([$duration, $userId]);

        // Update streak (simplified - check if reading today)
        if ($sessionDate === date('Y-m-d')) {
            $stmt = $conn->prepare("UPDATE users SET current_streak = current_streak + 1 WHERE id = ?");
            $stmt->execute([$userId]);
        }

        $success = 'Сессия чтения сохранена!';
        header('refresh:2;url=index.php');
    }
}

$pageTitle = 'Сессия чтения';
include 'includes/header.php';
?>

<div class="card" style="max-width: 600px; margin: 2rem auto;">
    <div class="card-header">
        <h2 class="card-title">Сессия чтения</h2>
    </div>

    <div class="book-item" style="margin-bottom: 2rem;">
        <div class="book-info">
            <h3><?php echo htmlspecialchars($book['title']); ?></h3>
            <p>Автор: <?php echo htmlspecialchars($book['author']); ?></p>
            <p>Прогресс: <?php echo $book['read_pages']; ?> / <?php echo $book['total_pages']; ?> страниц</p>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="form-group">
            <label class="form-label" for="session_date">Дата сессии</label>
            <input type="date" class="form-input" id="session_date" name="session_date" 
                   value="<?php echo date('Y-m-d'); ?>" required>
        </div>

        <div class="form-group">
            <label class="form-label" for="duration_minutes">Длительность (минуты)</label>
            <input type="number" class="form-input" id="duration_minutes" name="duration_minutes" 
                   min="1" required>
        </div>

        <div class="form-group">
            <label class="form-label" for="pages_read">Прочитано страниц</label>
            <input type="number" class="form-input" id="pages_read" name="pages_read" 
                   min="0" max="<?php echo $book['total_pages'] - $book['read_pages']; ?>" required>
        </div>

        <button type="submit" class="btn btn-primary" style="width: 100%;">Сохранить сессию</button>
    </form>

    <a href="index.php" class="btn btn-secondary" style="width: 100%; margin-top: 1rem;">Назад к дашборду</a>
</div>

<?php include 'includes/footer.php'; ?>

