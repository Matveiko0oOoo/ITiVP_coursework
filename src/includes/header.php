<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? 'BookMory - Планирование чтения'; ?></title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
    <?php if (isset($_SESSION['user_id'])): ?>
    <nav class="navbar">
        <div class="nav-container">
            <a href="/index.php" class="nav-logo">📚 BookMory</a>
            <ul class="nav-menu">
                <li><a href="/index.php">Дашборд</a></li>
                <li><a href="/books.php">Книги</a></li>
                <li><a href="/statistics.php">Статистика</a></li>
                <li><a href="/memos.php">Заметки</a></li>
                <li><a href="/achievements.php">Достижения</a></li>
                <li><a href="/profile.php">Профиль</a></li>
                <li><a href="/logout.php">Выход</a></li>
            </ul>
        </div>
    </nav>
    <?php endif; ?>
    <main class="container">

