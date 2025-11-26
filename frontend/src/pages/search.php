<?php
require_once '../../backend/config/database.php';

$searchQuery = $_GET['q'] ?? '';
$searchType = $_GET['type'] ?? 'all';
$searchResults = [];

if (!empty($searchQuery)) {
    // Realizar búsqueda en la base de datos
    $searchTerm = '%' . $searchQuery . '%';
    
    // Búsqueda en capítulos
    if (in_array($searchType, ['all', 'chapters'])) {
        $stmt = $pdo->prepare("
            SELECT c.*, s.title as season_title, s.id as season_id 
            FROM chapters c 
            JOIN seasons s ON c.season_id = s.id 
            WHERE (c.title LIKE ? OR c.subtitle LIKE ? OR c.content LIKE ?) 
            AND c.is_published = 1 
            ORDER BY c.chapter_number ASC
        ");
        $stmt->execute([$searchTerm, $searchTerm, $searchTerm]);
        $searchResults['chapters'] = $stmt->fetchAll();
    }
    
    // Búsqueda en temporadas
    if (in_array($searchType, ['all', 'seasons'])) {
        $stmt = $pdo->prepare("
            SELECT s.*, COUNT(c.id) as chapter_count 
            FROM seasons s 
            LEFT JOIN chapters c ON s.id = c.season_id 
            WHERE (s.title LIKE ? OR s.subtitle LIKE ? OR s.description LIKE ?) 
            AND s.is_published = 1 
            GROUP BY s.id 
            ORDER BY s.display_order ASC
        ");
        $stmt->execute([$searchTerm, $searchTerm, $searchTerm]);
        $searchResults['seasons'] = $stmt->fetchAll();
    }
    
    // Búsqueda en categorías
    if (in_array($searchType, ['all', 'categories'])) {
        $stmt = $pdo->prepare("
            SELECT cat.*, COUNT(cc.chapter_id) as usage_count 
            FROM categories cat 
            LEFT JOIN chapter_categories cc ON cat.id = cc.category_id 
            WHERE cat.name LIKE ? OR cat.description LIKE ? 
            GROUP BY cat.id 
            ORDER BY cat.name ASC
        ");
        $stmt->execute([$searchTerm, $searchTerm]);
        $searchResults['categories'] = $stmt->fetchAll();
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Búsqueda - El Camino del Whisky</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../styles/main.css">
    <link rel="icon" type="image/x-icon" href="../../../uploads/favicon.png">

</head>
<body>
    <?php include '../includes/navbar.php'; ?>
    
    <div class="container py-5 mt-5">
        <!-- Barra de búsqueda -->
        <div class="row mb-5">
            <div class="col-12">
                <div class="whisky-card p-4">
                    <h1 class="title-font mb-4">
                        <i class="bi bi-search me-2"></i>Buscar Contenido
                    </h1>
                    
                    <form method="GET" class="row g-3">
                        <div class="col-md-8">
                            <div class="input-group">
                                <input type="text" class="form-control" name="q" 
                                       value="<?= htmlspecialchars($searchQuery) ?>" 
                                       placeholder="Buscar en temporadas, capítulos, categorías..." required>
                                <button class="btn btn-warning" type="submit">
                                    <i class="bi bi-search"></i>
                                </button>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <select class="form-select" name="type">
                                <option value="all" <?= $searchType === 'all' ? 'selected' : '' ?>>Todo el contenido</option>
                                <option value="seasons" <?= $searchType === 'seasons' ? 'selected' : '' ?>>Solo temporadas</option>
                                <option value="chapters" <?= $searchType === 'chapters' ? 'selected' : '' ?>>Solo capítulos</option>
                                <option value="categories" <?= $searchType === 'categories' ? 'selected' : '' ?>>Solo categorías</option>
                            </select>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Resultados de búsqueda -->
        <?php if (!empty($searchQuery)): ?>
            <div class="row">
                <div class="col-12">
                    <h2 class="title-font mb-4">
                        Resultados para "<?= htmlspecialchars($searchQuery) ?>"
                    </h2>
                    
                    <?php
                    $totalResults = 0;
                    foreach ($searchResults as $type => $results) {
                        $totalResults += count($results);
                    }
                    ?>
                    
                    <p class="text-muted mb-4">Se encontraron <?= $totalResults ?> resultados</p>
                    
                    <!-- Resultados de Temporadas -->
                    <?php if (!empty($searchResults['seasons'])): ?>
                    <div class="mb-5">
                        <h4 class="text-warning mb-4">
                            <i class="bi bi-collection-play me-2"></i>Temporadas (<?= count($searchResults['seasons']) ?>)
                        </h4>
                        <div class="row g-4">
                            <?php foreach ($searchResults['seasons'] as $season): ?>
                            <div class="col-md-6">
                                <div class="whisky-card p-4 h-100">
                                    <h5 class="text-warning"><?= htmlspecialchars($season['title']) ?></h5>
                                    <?php if ($season['subtitle']): ?>
                                        <p class="mb-3"><?= htmlspecialchars($season['subtitle']) ?></p>
                                    <?php endif; ?>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="badge bg-info"><?= $season['chapter_count'] ?> capítulos</span>
                                        <a href="../public/index.php#temporadas" class="btn btn-sm btn-outline-warning">
                                            Ver Temporada
                                        </a>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Resultados de Capítulos -->
                    <?php if (!empty($searchResults['chapters'])): ?>
                    <div class="mb-5">
                        <h4 class="text-warning mb-4">
                            <i class="bi bi-play-btn me-2"></i>Capítulos (<?= count($searchResults['chapters']) ?>)
                        </h4>
                        <div class="row g-3">
                            <?php foreach ($searchResults['chapters'] as $chapter): ?>
                            <div class="col-md-6 col-lg-4">
                                <div class="whisky-card p-3 h-100">
                                    <h6 class="text-warning"><?= htmlspecialchars($chapter['title']) ?></h6>
                                    <?php if ($chapter['subtitle']): ?>
                                        <p class="small text-muted mb-2"><?= htmlspecialchars($chapter['subtitle']) ?></p>
                                    <?php endif; ?>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <small class="text-muted"><?= htmlspecialchars($chapter['season_title']) ?></small>
                                        <button class="btn btn-sm btn-outline-warning" 
                                                onclick="loadChapter(<?= $chapter['id'] ?>)">
                                            Ver
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Resultados de Categorías -->
                    <?php if (!empty($searchResults['categories'])): ?>
                    <div class="mb-5">
                        <h4 class="text-warning mb-4">
                            <i class="bi bi-tags me-2"></i>Categorías (<?= count($searchResults['categories']) ?>)
                        </h4>
                        <div class="d-flex flex-wrap gap-3">
                            <?php foreach ($searchResults['categories'] as $category): ?>
                            <div class="whisky-card p-3">
                                <h6 class="text-warning mb-2"><?= htmlspecialchars($category['name']) ?></h6>
                                <?php if ($category['description']): ?>
                                    <p class="small text-muted mb-2"><?= htmlspecialchars($category['description']) ?></p>
                                <?php endif; ?>
                                <span class="badge bg-warning text-dark">
                                    <?= $category['usage_count'] ?> capítulos
                                </span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($totalResults === 0): ?>
                    <div class="text-center py-5">
                        <i class="bi bi-search display-1 text-muted mb-3"></i>
                        <h4 class="text-muted">No se encontraron resultados</h4>
                        <p class="text-muted">Intenta con otros términos de búsqueda</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php else: ?>
            <div class="text-center py-5">
                <i class="bi bi-search display-1 text-warning mb-3"></i>
                <h4 class="text-warning">Busca en nuestro contenido</h4>
                <p class="text-muted">Encuentra temporadas, capítulos y categorías específicas</p>
            </div>
        <?php endif; ?>
    </div>
    
    <?php include '../includes/modals.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../src/js/main.js"></script>

</body>
</html>