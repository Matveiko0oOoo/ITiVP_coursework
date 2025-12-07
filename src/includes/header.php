<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? 'BookMory - Планирование чтения'; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
    <?php if (isset($_SESSION['user_id'])): ?>
    
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid nav-container">
            
            <a class="navbar-brand nav-logo" href="/index.php">📚 BookMory</a>

            <button class="navbar-toggler" type="button" 
                    data-bs-toggle="collapse" 
                    data-bs-target="#mainNavbarContent" 
                    aria-controls="mainNavbarContent" 
                    aria-expanded="false" 
                    aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="mainNavbarContent">
                
                <ul class="navbar-nav me-auto mb-2 mb-lg-0 nav-menu-desktop">
                    <li class="nav-item"><a class="nav-link" href="/index.php">Дашборд</a></li>
                    <li class="nav-item"><a class="nav-link" href="/books.php">Книги</a></li>
                    <li class="nav-item"><a class="nav-link" href="/statistics.php">Статистика</a></li>
                    <li class="nav-item"><a class="nav-link" href="/memos.php">Заметки</a></li>
                    <li class="nav-item"><a class="nav-link" href="/achievements.php">Достижения</a></li>
                    <li class="nav-item"><a class="nav-link" href="/profile.php">Профиль</a></li>
                    <li class="nav-item"><a class="nav-link" href="/logout.php">Выход</a></li>
                </ul>
                
                </div>
        </div>
    </nav>
    <?php endif; ?>
    <main class="container">