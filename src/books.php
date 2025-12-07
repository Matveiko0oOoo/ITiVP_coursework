<?php
require_once 'config/database.php';
require_once 'config/auth.php';
requireLogin();

$db = new Database();
$conn = $db->getConnection();
$userId = getCurrentUserId();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_book'])) {
    $title = trim($_POST['title'] ?? '');
    $author = trim($_POST['author'] ?? '');
    $totalPages = intval($_POST['total_pages'] ?? 0);
    $status = $_POST['status'] ?? 'To Read';

    if (empty($title) || empty($author) || $totalPages <= 0) {
        $error = 'Заполните все поля корректно';
    } else {
        $pdfPath = null;
        
        if (isset($_FILES['pdf_file']) && $_FILES['pdf_file']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/uploads/books/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            
            $fileName = uniqid() . '_' . basename($_FILES['pdf_file']['name']);
            $targetPath = $uploadDir . $fileName;
            
            if (move_uploaded_file($_FILES['pdf_file']['tmp_name'], $targetPath)) {
                $pdfPath = '/uploads/books/' . $fileName;
            }
        }

        $stmt = $conn->prepare("INSERT INTO books (user_id, title, author, total_pages, status, pdf_file_path) 
                                VALUES (?, ?, ?, ?, ?, ?)");
        if ($stmt->execute([$userId, $title, $author, $totalPages, $status, $pdfPath])) {
            $success = 'Книга успешно добавлена!';
        } else {
            $error = 'Ошибка при добавлении книги';
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_status'])) {
    $bookId = intval($_POST['book_id'] ?? 0);
    $newStatus = $_POST['new_status'] ?? '';

    if ($bookId && in_array($newStatus, ['Reading', 'To Read', 'Finished'])) {
        $stmt = $conn->prepare("UPDATE books SET status = ? WHERE id = ? AND user_id = ?");
        $stmt->execute([$newStatus, $bookId, $userId]);
        $success = 'Статус книги обновлен!';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_book'])) {
    $bookId = intval($_POST['book_id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $author = trim($_POST['author'] ?? '');
    $totalPages = intval($_POST['total_pages'] ?? 0);

    if ($bookId && !empty($title) && !empty($author) && $totalPages > 0) {

        $stmt = $conn->prepare("SELECT read_pages FROM books WHERE id = ? AND user_id = ?");
        $stmt->execute([$bookId, $userId]);
        $currentBook = $stmt->fetch();

        if ($currentBook) {
            $readPages = min($currentBook['read_pages'], $totalPages);
            
            $stmt = $conn->prepare("UPDATE books SET title = ?, author = ?, total_pages = ?, read_pages = ? WHERE id = ? AND user_id = ?");
            if ($stmt->execute([$title, $author, $totalPages, $readPages, $bookId, $userId])) {
                $success = 'Книга успешно обновлена!';
            } else {
                $error = 'Ошибка при обновлении книги';
            }
        }
    } else {
        $error = 'Заполните все поля корректно';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_book'])) {
    $bookId = intval($_POST['book_id'] ?? 0);

    if ($bookId) {
        $stmt = $conn->prepare("SELECT pdf_file_path FROM books WHERE id = ? AND user_id = ?");
        $stmt->execute([$bookId, $userId]);
        $book = $stmt->fetch();

        if ($book) {
            if ($book['pdf_file_path'] && file_exists(__DIR__ . $book['pdf_file_path'])) {
                unlink(__DIR__ . $book['pdf_file_path']);
            }

            $stmt = $conn->prepare("DELETE FROM books WHERE id = ? AND user_id = ?");
            if ($stmt->execute([$bookId, $userId])) {
                $success = 'Книга успешно удалена!';
            } else {
                $error = 'Ошибка при удалении книги';
            }
        }
    }
}

$stmt = $conn->prepare("SELECT * FROM books WHERE user_id = ? AND status = 'To Read' ORDER BY created_at DESC");
$stmt->execute([$userId]);
$booksToRead = $stmt->fetchAll();

$stmt = $conn->prepare("SELECT * FROM books WHERE user_id = ? ORDER BY status, created_at DESC");
$stmt->execute([$userId]);
$allBooks = $stmt->fetchAll();

$bookSessions = [];
foreach ($allBooks as $book) {
    $stmt = $conn->prepare("SELECT COUNT(*) as session_count, SUM(duration_minutes) as total_minutes, SUM(pages_read) as total_pages 
                           FROM reading_sessions WHERE book_id = ? AND user_id = ?");
    $stmt->execute([$book['id'], $userId]);
    $bookSessions[$book['id']] = $stmt->fetch();
}

$pageTitle = 'Управление книгами';
include 'includes/header.php';
?>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">Добавить новую книгу</h2>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <form method="POST" action="" enctype="multipart/form-data">
        <input type="hidden" name="add_book" value="1">
        
        <div class="form-group">
            <label class="form-label" for="title">Название книги</label>
            <input type="text" class="form-input" id="title" name="title" required>
        </div>

        <div class="form-group">
            <label class="form-label" for="author">Автор</label>
            <input type="text" class="form-input" id="author" name="author" required>
        </div>

        <div class="form-group">
            <label class="form-label" for="total_pages">Общее количество страниц</label>
            <input type="number" class="form-input" id="total_pages" name="total_pages" min="1" required>
        </div>

        <div class="form-group">
            <label class="form-label" for="status">Статус</label>
            <select class="form-select" id="status" name="status">
                <option value="To Read">To Read</option>
                <option value="Reading">Reading</option>
            </select>
        </div>

        <div class="form-group">
            <label class="form-label" for="pdf_file">PDF файл книги (опционально)</label>
            <input type="file" class="form-input" id="pdf_file" name="pdf_file" accept=".pdf">
        </div>

        <button type="submit" class="btn btn-primary">Добавить книгу</button>
    </form>
</div>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">📚 Book To Read Later (Список желаний)</h2>
    </div>

    <?php if (empty($booksToRead)): ?>
        <p style="color: var(--text-secondary); text-align: center; padding: 2rem;">
            Список желаний пуст. Добавьте книги, которые хотите прочитать позже.
        </p>
    <?php else: ?>
        <div class="book-list">
            <?php foreach ($booksToRead as $book): ?>
                <div class="book-item">
                    <div class="book-info">
                        <h3><?php echo htmlspecialchars($book['title']); ?></h3>
                        <p>Автор: <?php echo htmlspecialchars($book['author']); ?></p>
                        <p>Страниц: <?php echo $book['total_pages']; ?></p>
                        <?php if ($book['pdf_file_path']): ?>
                            <p><a href="<?php echo htmlspecialchars($book['pdf_file_path']); ?>" target="_blank" style="color: var(--accent-yellow);">📄 Открыть PDF</a></p>
                        <?php endif; ?>
                    </div>
                    <div style="display: flex; gap: 0.5rem; align-items: center;">
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="book_id" value="<?php echo $book['id']; ?>">
                            <input type="hidden" name="change_status" value="1">
                            <input type="hidden" name="new_status" value="Reading">
                            <button type="submit" class="btn btn-primary">Начать читать</button>
                        </form>
                        <button type="button" class="btn btn-secondary" style="padding: 0.5rem 0.75rem;" 
                                title="Редактировать книгу" onclick="showEditModal(<?php echo $book['id']; ?>, '<?php echo htmlspecialchars(addslashes($book['title'])); ?>', '<?php echo htmlspecialchars(addslashes($book['author'])); ?>', <?php echo $book['total_pages']; ?>)">
                            ✏️
                        </button>
                        <button type="button" class="btn btn-info" style="padding: 0.5rem 0.75rem;" 
                                title="Статистика" onclick="showStatsModal(<?php echo $book['id']; ?>, '<?php echo htmlspecialchars(addslashes($book['title'])); ?>')">
                            📊
                        </button>
                        <button type="button" class="btn btn-danger" style="padding: 0.5rem 0.75rem;" 
                                title="Удалить книгу" onclick="showDeleteModal(<?php echo $book['id']; ?>, '<?php echo htmlspecialchars(addslashes($book['title'])); ?>')">
                            🗑️
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">Все мои книги</h2>
    </div>

    <div class="book-list">
        <?php foreach ($allBooks as $book): ?>
            <div class="book-item">
                <div class="book-info">
                    <h3><?php echo htmlspecialchars($book['title']); ?></h3>
                    <p>Автор: <?php echo htmlspecialchars($book['author']); ?></p>
                    <p>Статус: <span class="badge badge-yellow"><?php echo $book['status']; ?></span></p>
                    <p>Прогресс: <?php echo $book['read_pages']; ?> / <?php echo $book['total_pages']; ?> страниц</p>
                    <?php if ($book['read_pages'] > 0): ?>
                        <div class="book-progress">
                            <div class="progress-bar" style="width: <?php echo ($book['read_pages'] / $book['total_pages']) * 100; ?>%"></div>
                        </div>
                    <?php endif; ?>
                </div>
                <div style="display: flex; gap: 0.5rem; flex-wrap: wrap; align-items: center;">
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="book_id" value="<?php echo $book['id']; ?>">
                        <input type="hidden" name="change_status" value="1">
                        <select name="new_status" class="form-select" style="width: auto; margin-bottom: 0.5rem;" onchange="this.form.submit()">
                            <option value="To Read" <?php echo $book['status'] === 'To Read' ? 'selected' : ''; ?>>To Read</option>
                            <option value="Reading" <?php echo $book['status'] === 'Reading' ? 'selected' : ''; ?>>Reading</option>
                            <option value="Finished" <?php echo $book['status'] === 'Finished' ? 'selected' : ''; ?>>Finished</option>
                        </select>
                    </form>
                    <?php if ($book['status'] === 'Reading'): ?>
                        <a href="reading_session.php?book_id=<?php echo $book['id']; ?>" class="btn btn-primary">Читать</a>
                    <?php endif; ?>
                    <button type="button" class="btn btn-secondary" style="padding: 0.5rem 0.75rem;" 
                            title="Редактировать книгу" onclick="showEditModal(<?php echo $book['id']; ?>, '<?php echo htmlspecialchars(addslashes($book['title'])); ?>', '<?php echo htmlspecialchars(addslashes($book['author'])); ?>', <?php echo $book['total_pages']; ?>)">
                        ✏️
                    </button>
                    <button type="button" class="btn btn-info" style="padding: 0.5rem 0.75rem;" 
                            title="Статистика" onclick="showStatsModal(<?php echo $book['id']; ?>, '<?php echo htmlspecialchars(addslashes($book['title'])); ?>')">
                        📊
                    </button>
                    <button type="button" class="btn btn-danger" style="padding: 0.5rem 0.75rem;" 
                            title="Удалить книгу" onclick="showDeleteModal(<?php echo $book['id']; ?>, '<?php echo htmlspecialchars(addslashes($book['title'])); ?>')">
                        🗑️
                    </button>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<div id="editModal" class="modal" tabindex="-1" style="display: none;">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Редактировать книгу</h5>
        <button type="button" class="btn-close" onclick="closeEditModal()" aria-label="Закрыть"></button>
      </div>
      <div class="modal-body">
        <form id="editBookForm" method="POST">
          <input type="hidden" name="book_id" id="editBookId" value="">
          <input type="hidden" name="edit_book" value="1">
          
          <div class="form-group">
            <label class="form-label" for="editTitle">Название книги</label>
            <input type="text" class="form-input" id="editTitle" name="title" required>
          </div>

          <div class="form-group">
            <label class="form-label" for="editAuthor">Автор</label>
            <input type="text" class="form-input" id="editAuthor" name="author" required>
          </div>

          <div class="form-group">
            <label class="form-label" for="editTotalPages">Общее количество страниц</label>
            <input type="number" class="form-input" id="editTotalPages" name="total_pages" min="1" required>
          </div>

          <div class="modal-footer" style="margin-top: 1.5rem; padding-top: 1rem; border-top: 1px solid var(--border-color);">
            <button type="button" class="btn btn-secondary" onclick="closeEditModal()">Отмена</button>
            <button type="submit" class="btn btn-primary">Сохранить изменения</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<div id="statsModal" class="modal" tabindex="-1" style="display: none;">
  <div class="modal-dialog" style="max-width: 800px;">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Статистика: <span id="statsBookTitle"></span></h5>
        <button type="button" class="btn-close" onclick="closeStatsModal()" aria-label="Закрыть"></button>
      </div>
      <div class="modal-body" id="statsModalBody">
        <div style="text-align: center; padding: 2rem;">
          <div class="spinner" style="border: 4px solid var(--border-color); border-top: 4px solid var(--accent-yellow); border-radius: 50%; width: 40px; height: 40px; animation: spin 1s linear infinite; margin: 0 auto;"></div>
          <p style="margin-top: 1rem; color: var(--text-secondary);">Загрузка статистики...</p>
        </div>
      </div>
    </div>
  </div>
</div>

<div id="deleteModal" class="modal" tabindex="-1" style="display: none;">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Подтверждение удаления</h5>
        <button type="button" class="btn-close" onclick="closeDeleteModal()" aria-label="Закрыть"></button>
      </div>
      <div class="modal-body">
        <p>Вы уверены, что хотите удалить книгу <strong id="bookTitleToDelete"></strong>?</p>
        <p style="color: var(--text-secondary); font-size: 0.9rem; margin-top: 0.5rem;">
          Это действие нельзя отменить. Все связанные данные (сессии чтения, заметки) также будут удалены.
        </p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="closeDeleteModal()">Отмена</button>
        <form id="deleteBookForm" method="POST" style="display: inline;">
          <input type="hidden" name="book_id" id="bookIdToDelete" value="">
          <input type="hidden" name="delete_book" value="1">
          <button type="submit" class="btn btn-danger">Удалить</button>
        </form>
      </div>
    </div>
  </div>
</div>

<script>
function showEditModal(bookId, bookTitle, bookAuthor, totalPages) {
    document.getElementById('editBookId').value = bookId;
    document.getElementById('editTitle').value = bookTitle;
    document.getElementById('editAuthor').value = bookAuthor;
    document.getElementById('editTotalPages').value = totalPages;
    document.getElementById('editModal').style.display = 'flex';
    document.getElementById('editModal').classList.add('show');
}

function closeEditModal() {
    document.getElementById('editModal').style.display = 'none';
    document.getElementById('editModal').classList.remove('show');
}

function showStatsModal(bookId, bookTitle) {
    document.getElementById('statsBookTitle').textContent = bookTitle;
    document.getElementById('statsModal').style.display = 'flex';
    document.getElementById('statsModal').classList.add('show');
    
    fetch('book_statistics.php?book_id=' + bookId)
        .then(response => response.text())
        .then(html => {
            document.getElementById('statsModalBody').innerHTML = html;
        })
        .catch(error => {
            document.getElementById('statsModalBody').innerHTML = 
                '<div class="alert alert-error">Ошибка при загрузке статистики</div>';
        });
}

function closeStatsModal() {
    document.getElementById('statsModal').style.display = 'none';
    document.getElementById('statsModal').classList.remove('show');
}

function showDeleteModal(bookId, bookTitle) {
    document.getElementById('bookIdToDelete').value = bookId;
    document.getElementById('bookTitleToDelete').textContent = bookTitle;
    document.getElementById('deleteModal').style.display = 'flex';
    document.getElementById('deleteModal').classList.add('show');
}

function closeDeleteModal() {
    document.getElementById('deleteModal').style.display = 'none';
    document.getElementById('deleteModal').classList.remove('show');
}

document.addEventListener('DOMContentLoaded', function() {
    var editModal = document.getElementById('editModal');
    if (editModal) {
        editModal.addEventListener('click', function(e) {
            if (e.target === this) {
                closeEditModal();
            }
        });
    }

    var statsModal = document.getElementById('statsModal');
    if (statsModal) {
        statsModal.addEventListener('click', function(e) {
            if (e.target === this) {
                closeStatsModal();
            }
        });
    }

    var deleteModal = document.getElementById('deleteModal');
    if (deleteModal) {
        deleteModal.addEventListener('click', function(e) {
            if (e.target === this) {
                closeDeleteModal();
            }
        });
    }

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            var editModalEl = document.getElementById('editModal');
            var statsModalEl = document.getElementById('statsModal');
            var deleteModalEl = document.getElementById('deleteModal');
            
            if (editModalEl && editModalEl.style.display === 'flex') {
                closeEditModal();
            }
            if (statsModalEl && statsModalEl.style.display === 'flex') {
                closeStatsModal();
            }
            if (deleteModalEl && deleteModalEl.style.display === 'flex') {
                closeDeleteModal();
            }
        }
    });
});
</script>

<?php include 'includes/footer.php'; ?>

