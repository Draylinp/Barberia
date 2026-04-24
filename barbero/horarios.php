<?php
/**
 * Bloqueo de horarios del barbero
 * Barbería Premium — barbero/horarios.php
 */
$titulo = 'Bloquear Horarios';
require_once __DIR__ . '/../includes/header_barbero.php';

$db        = getDB();
$barberoId = $_SESSION['barbero_id'] ?? 0;

// ── Eliminar bloqueo ──────────────────────────────────────────────────────────
if (isset($_GET['eliminar'])) {
    verifyCsrf();
    $db->prepare('DELETE FROM horarios_bloqueados WHERE id=:id AND barbero_id=:b')
       ->execute([':id'=>sanitizeInt($_GET['eliminar']), ':b'=>$barberoId]);
    setFlash('success', 'Bloqueo eliminado.');
    header('Location: /Barberia/barbero/horarios.php'); exit;
}

// ── Crear bloqueo ─────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $fecha = sanitizeStr($_POST['fecha'] ?? '');
    $hi    = sanitizeStr($_POST['hora_inicio'] ?? '');
    $hf    = sanitizeStr($_POST['hora_fin']    ?? '');
    $motivo= sanitizeStr($_POST['motivo']      ?? '');

    if (!$fecha || !$hi || !$hf || $hi >= $hf) {
        setFlash('error', 'Completa los campos y verifica que la hora de fin sea posterior a la de inicio.');
    } else {
        $db->prepare('INSERT INTO horarios_bloqueados (barbero_id,fecha,hora_inicio,hora_fin,motivo) VALUES (:b,:f,:hi,:hf,:m)')
           ->execute([':b'=>$barberoId,':f'=>$fecha,':hi'=>$hi,':hf'=>$hf,':m'=>$motivo]);
        setFlash('success', 'Horario bloqueado correctamente.');
        header('Location: /Barberia/barbero/horarios.php'); exit;
    }
}

// ── Listar bloqueos ───────────────────────────────────────────────────────────
$bloqueos = $db->prepare(
    'SELECT * FROM horarios_bloqueados WHERE barbero_id=:b AND fecha >= CURDATE() ORDER BY fecha, hora_inicio'
);
$bloqueos->execute([':b'=>$barberoId]);
$bloqueos = $bloqueos->fetchAll();
?>

<div class="row g-4">
    <!-- Formulario nuevo bloqueo -->
    <div class="col-lg-4">
        <div class="admin-card">
            <div class="admin-card-title"><i class="bi bi-clock-history"></i>Nuevo bloqueo</div>
            <form method="POST" class="needs-validation" novalidate>
                <?= csrfField() ?>
                <div class="mb-3">
                    <label class="form-label">Fecha <span class="text-gold">*</span></label>
                    <input type="date" name="fecha" class="form-control" required
                           min="<?= date('Y-m-d') ?>">
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="form-label">Hora inicio <span class="text-gold">*</span></label>
                        <input type="time" name="hora_inicio" class="form-control" required>
                    </div>
                    <div class="col-6">
                        <label class="form-label">Hora fin <span class="text-gold">*</span></label>
                        <input type="time" name="hora_fin" class="form-control" required>
                    </div>
                </div>
                <div class="mb-4">
                    <label class="form-label">Motivo (opcional)</label>
                    <input type="text" name="motivo" class="form-control"
                           placeholder="Ej: Almuerzo, Cita médica…">
                </div>
                <button type="submit" class="btn btn-gold w-100">
                    <i class="bi bi-lock me-2"></i>Bloquear horario
                </button>
            </form>
        </div>
    </div>

    <!-- Lista de bloqueos -->
    <div class="col-lg-8">
        <div class="admin-table-wrap">
            <div class="admin-table-header">
                <h6><i class="bi bi-slash-circle me-2 text-gold"></i>Horarios bloqueados (<?= count($bloqueos) ?>)</h6>
            </div>
            <?php if (empty($bloqueos)): ?>
            <div class="text-center py-5 text-muted">
                <i class="bi bi-calendar-check" style="font-size:2.5rem;opacity:.3;"></i>
                <p class="mt-3">No tienes horarios bloqueados.</p>
            </div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead><tr><th>Fecha</th><th>Desde</th><th>Hasta</th><th>Motivo</th><th></th></tr></thead>
                    <tbody>
                        <?php foreach ($bloqueos as $bl): ?>
                        <tr>
                            <td class="fw-semibold"><?= formatFecha($bl['fecha']) ?></td>
                            <td><?= formatHora($bl['hora_inicio']) ?></td>
                            <td><?= formatHora($bl['hora_fin']) ?></td>
                            <td class="text-muted small"><?= e($bl['motivo'] ?: '—') ?></td>
                            <td>
                                <a href="?eliminar=<?= $bl['id'] ?>&csrf_token=<?= csrfToken() ?>"
                                   class="btn btn-sm btn-outline-danger"
                                   data-confirm="¿Eliminar este bloqueo?">
                                    <i class="bi bi-trash"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer_admin.php'; ?>
