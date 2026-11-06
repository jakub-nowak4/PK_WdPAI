<!doctype html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
    <main class="page">
        <h1>Strona do prezentacji listy wszystkich projektów</h1>
        <!-- TODO: Lista projektów wyświetlona w pętli (kontener .projects to grid kart .project-card) -->
        <div class="projects"></div>
        <div class="actions">
            <a href="/">Powrót do strony głównej</a>
        </div>
    </main>
</body>
</html>
