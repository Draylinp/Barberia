<?php
/**
 * Galería de trabajos
 * Barbería Premium — public/galeria.php
 */
require_once __DIR__ . '/../includes/functions.php';
$titulo = 'Galería';
$db     = getDB();

$categoria = sanitizeStr($_GET['cat'] ?? '');
$categorias = ['corte', 'barba', 'tratamiento', 'otro'];

$sql    = 'SELECT * FROM galeria WHERE activa = 1';
$params = [];
if ($categoria && in_array($categoria, $categorias)) {
    $sql .= ' AND categoria = :cat';
    $params[':cat'] = $categoria;
}
$sql .= ' ORDER BY orden ASC, id DESC';
$stmt = $db->prepare($sql);
$stmt->execute($params);
$imagenes = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header_public.php';
?>

<div style="padding:130px 0 60px;background:linear-gradient(135deg,var(--dark-1),var(--dark-2));border-bottom:1px solid var(--border);">
    <div class="container text-center">
        <span class="hero-badge"><i class="bi bi-images"></i> Portafolio</span>
        <h1 class="section-title mt-3">Galería de Trabajos</h1>
        <div class="gold-divider mb-3"></div>
        <p class="text-muted">Cada corte cuenta una historia</p>
    </div>
</div>

<section class="section-pad">
    <div class="container">
        <!-- Filtros -->
        <div class="d-flex justify-content-center gap-2 flex-wrap mb-5">
            <a href="/Barberia/public/galeria.php"
               class="btn btn-sm <?= !$categoria ? 'btn-gold' : 'btn-outline-gold' ?>">
                Todos
            </a>
            <?php foreach ($categorias as $cat): ?>
            <a href="/Barberia/public/galeria.php?cat=<?= urlencode($cat) ?>"
               class="btn btn-sm <?= $categoria === $cat ? 'btn-gold' : 'btn-outline-gold' ?>">
                <?= ucfirst($cat) ?>
            </a>
            <?php endforeach; ?>
        </div>

        <?php if (empty($imagenes)): ?>
            <div class="text-center py-5 text-muted">
                <i class="bi bi-images" style="font-size:3rem;opacity:.3;"></i>
                <p class="mt-3">No hay imágenes en esta categoría todavía.</p>
            </div>
        <?php else: ?>
            <div class="gallery-grid">
                <?php foreach ($imagenes as $img): ?>
                <div class="gallery-item">
                    <img src="/Barberia/assets/img/galeria/<?= e($img['imagen']) ?>"
                         alt="<?= e($img['titulo'] ?? 'Trabajo') ?>"
                         loading="lazy"
                         onerror="this.src='https://placehold.co/400x300/1a1a1a/c9a227?text=Barbería+Elite'">
                    <div class="gallery-overlay">
                        <div>
                            <?php if ($img['titulo']): ?>
                                <div class="fw-semibold"><?= e($img['titulo']) ?></div>
                            <?php endif; ?>
                            <span class="badge" style="background:rgba(201,162,39,.3);color:var(--gold);font-size:.7rem;">
                                <?= ucfirst(e($img['categoria'])) ?>
                            </span>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="text-center mt-5">
            <a href="/Barberia/public/reservar.php" class="btn btn-gold btn-lg">
                <i class="bi bi-calendar-plus me-2"></i>Quiero un corte así
            </a>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer_public.php'; ?>
