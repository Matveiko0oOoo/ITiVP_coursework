<?php
require_once 'config/database.php';
require_once 'config/auth.php';
requireLogin();

$db = new Database();
$conn = $db->getConnection();
$userId = getCurrentUserId();

$error = '';
$success = '';

// Handle memo addition
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_memo'])) {
    $bookId = intval($_POST['book_id'] ?? 0);
    $memoText = trim($_POST['memo_text'] ?? '');

    if (empty($bookId) || empty($memoText)) {
        $error = 'Выберите книгу и введите текст заметки';
    } else {
        // Verify book belongs to user and is finished
        $stmt = $conn->prepare("SELECT id FROM books WHERE id = ? AND user_id = ? AND status = 'Finished'");
        $stmt->execute([$bookId, $userId]);
        if (!$stmt->fetch()) {
            $error = 'Вы можете добавлять заметки только к прочитанным книгам';
        } else {
            $stmt = $conn->prepare("INSERT INTO memos (user_id, book_id, memo_text) VALUES (?, ?, ?)");
            if ($stmt->execute([$userId, $bookId, $memoText])) {
                $success = 'Заметка успешно добавлена!';
            } else {
                $error = 'Ошибка при добавлении заметки';
            }
        }
    }
}

// Get finished books for dropdown
$stmt = $conn->prepare("SELECT id, title, author FROM books WHERE user_id = ? AND status = 'Finished' ORDER BY updated_at DESC");
$stmt->execute([$userId]);
$finishedBooks = $stmt->fetchAll();

// Get all memos
$stmt = $conn->prepare("SELECT m.*, b.title, b.author 
                        FROM memos m 
                        JOIN books b ON m.book_id = b.id 
                        WHERE m.user_id = ? 
                        ORDER BY m.created_at DESC");
$stmt->execute([$userId]);
$memos = $stmt->fetchAll();

$pageTitle = 'Заметки (Memorize)';
include 'includes/header.php';
?>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">Добавить заметку</h2>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <form method="POST" action="">
        <input type="hidden" name="add_memo" value="1">
        
        <div class="form-group">
            <label class="form-label" for="book_id">Книга (только прочитанные)</label>
            <select class="form-select" id="book_id" name="book_id" required>
                <option value="">Выберите книгу</option>
                <?php foreach ($finishedBooks as $book): ?>
                    <option value="<?php echo $book['id']; ?>">
                        <?php echo htmlspecialchars($book['title'] . ' - ' . $book['author']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label class="form-label" for="memo_text">Текст заметки/цитаты</label>
            <textarea class="form-textarea" id="memo_text" name="memo_text" required 
                      placeholder="Введите вашу заметку, цитату или мысли о книге..."></textarea>
        </div>

        <button type="submit" class="btn btn-primary">Добавить заметку</button>
    </form>
</div>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">Мои заметки</h2>
    </div>

    <?php if (empty($memos)): ?>
        <p style="color: var(--text-secondary); text-align: center; padding: 2rem;">
            У вас пока нет заметок. Добавьте заметку о прочитанной книге!
        </p>
    <?php else: ?>
        <div class="book-list">
            <?php foreach ($memos as $memo): ?>
                <div class="book-item">
                    <div class="book-info" style="width: 100%;">
                        <h3 style="color: var(--accent-yellow); margin-bottom: 0.5rem;">
                            <?php echo htmlspecialchars($memo['title']); ?>
                        </h3>
                        <p style="color: var(--text-secondary); margin-bottom: 1rem;">
                            Автор: <?php echo htmlspecialchars($memo['author']); ?>
                        </p>
                        <div style="background: var(--bg-tertiary); padding: 1rem; border-radius: 5px; margin-bottom: 0.5rem;">
                            <p style="white-space: pre-wrap; color: var(--text-primary);">
                                <?php echo nl2br(htmlspecialchars($memo['memo_text'])); ?>
                            </p>
                        </div>
                        <p style="color: var(--text-secondary); font-size: 0.85rem;">
                            Добавлено: <?php echo date('d.m.Y H:i', strtotime($memo['created_at'])); ?>
                        </p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>

