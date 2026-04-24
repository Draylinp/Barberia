<?php
/**
 * Reserva de citas — wizard de 4 pasos
 * Barbería Premium — public/reservar.php
 */
require_once __DIR__ . '/../includes/functions.php';
iniciarSesion();
$titulo = 'Reservar Cita';

// Si no está logueado, llevar al login y volver acá después
if (!sesionActiva() || $_SESSION['rol'] !== 'cliente') {
    header('Location: /Barberia/auth/login.php');
    exit;
}

$db        = getDB();
$clienteId = $_SESSION['cliente_id'] ?? null;
$error     = '';
$ok        = false;

// Preseleccionar servicio si viene por GET
$preServicio = sanitizeInt($_GET['servicio_id'] ?? 0);

$servicios = $db->query('SELECT * FROM servicios WHERE activo = 1 ORDER BY nombre ASC')->fetchAll();
$barberos  = $db->query(
    'SELECT b.*, u.nombre, u.apellido FROM barberos b
     JOIN usuarios u ON b.usuario_id = u.id WHERE b.activo = 1'
)->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $servicioId = sanitizeInt($_POST['servicio_id'] ?? 0);
    $barberoId  = sanitizeInt($_POST['barbero_id']  ?? 0);
    $fecha      = sanitizeStr($_POST['fecha']        ?? '');
    $hora       = sanitizeStr($_POST['hora_inicio']  ?? '');
    $notas      = sanitizeStr($_POST['notas']        ?? '');

    if (!$servicioId || !$barberoId || !$fecha || !$hora) {
        $error = 'Completa todos los pasos antes de confirmar.';
    } elseif (strtotime($fecha) < strtotime('today')) {
        $error = 'La fecha no puede ser en el pasado.';
    } else {
        // Obtener duración del servicio
        $srv = $db->prepare('SELECT duracion_min FROM servicios WHERE id = :id AND activo = 1');
        $srv->execute([':id' => $servicioId]);
        $servicio = $srv->fetch();

        if (!$servicio) {
            $error = 'Servicio no válido.';
        } else {
            $horaFin = date('H:i:s', strtotime($hora) + $servicio['duracion_min'] * 60);

            if (!barberoDisponible($barberoId, $fecha, $hora, $horaFin)) {
                $error = 'El horario seleccionado ya no está disponible. Por favor elige otro.';
            } else {
                $db->prepare(
                    'INSERT INTO citas (cliente_id, barbero_id, servicio_id, fecha, hora_inicio, hora_fin, notas_cliente, creada_por)
                     VALUES (:cl, :ba, :sv, :fe, :hi, :hf, :no, :cp)'
                )->execute([
                    ':cl' => $clienteId,
                    ':ba' => $barberoId,
                    ':sv' => $servicioId,
                    ':fe' => $fecha,
                    ':hi' => $hora,
                    ':hf' => $horaFin,
                    ':no' => $notas,
                    ':cp' => $_SESSION['usuario_id'],
                ]);
                $ok = true;
            }
        }
    }
}

require_once __DIR__ . '/../includes/header_public.php';
?>

<div style="padding:100px 0 0;min-height:100vh;">
    <div class="container py-5">

        <?php if ($ok): ?>
        <!-- CONFIRMACIÓN -->
        <div class="text-center py-5">
            <div style="width:100px;height:100px;background:rgba(34,197,94,.1);border:2px solid #4ade80;
                        border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 2rem;">
                <i class="bi bi-check-lg" style="font-size:2.5rem;color:#4ade80;"></i>
            </div>
            <h2 style="font-family:var(--font-serif);">¡Cita reservada!</h2>
            <p class="text-muted mt-2 mb-4">Tu cita ha sido registrada exitosamente. Te esperamos.</p>
            <a href="/Barberia/cliente/citas.php" class="btn btn-gold me-3">
                <i class="bi bi-calendar-check me-2"></i>Ver mis citas
            </a>
            <a href="/Barberia/public/index.php" class="btn btn-outline-gold">
                Volver al inicio
            </a>
        </div>

        <?php else: ?>

        <div class="text-center mb-5">
            <h2 style="font-family:var(--font-serif);">Reservar Cita</h2>
            <p class="text-muted">Sigue los pasos para agendar tu visita</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger mb-4 text-center">
                <i class="bi bi-exclamation-triangle me-2"></i><?= e($error) ?>
            </div>
        <?php endif; ?>

        <!-- Indicador de pasos -->
        <div class="step-indicator mb-5 justify-content-center" style="max-width:600px;margin:0 auto;">
            <div class="step-item active" id="indicator-1">
                <div class="step-circle">1</div>
                <div class="step-label">Servicio</div>
            </div>
            <div class="step-item" id="indicator-2">
                <div class="step-circle">2</div>
                <div class="step-label">Barbero</div>
            </div>
            <div class="step-item" id="indicator-3">
                <div class="step-circle">3</div>
                <div class="step-label">Fecha y hora</div>
            </div>
            <div class="step-item" id="indicator-4">
                <div class="step-circle">4</div>
                <div class="step-label">Confirmar</div>
            </div>
        </div>

        <form method="POST" action="" id="reservaForm" novalidate>
            <?= csrfField() ?>
            <input type="hidden" name="servicio_id"  id="servicio_id_hidden">
            <input type="hidden" name="barbero_id"   id="barbero_id_hidden">
            <input type="hidden" name="hora_inicio"  id="hora_input">

            <div style="max-width:760px;margin:0 auto;">

                <!-- PASO 1: Servicio -->
                <div class="wizard-step" id="step-1">
                    <h5 class="text-center mb-4">¿Qué servicio deseas?</h5>
                    <div class="row g-3">
                        <?php foreach ($servicios as $s): ?>
                        <div class="col-md-6">
                            <label class="service-select-card w-100 <?= $preServicio == $s['id'] ? 'selected' : '' ?>"
                                   style="cursor:pointer;">
                                <input type="radio" name="_srv" value="<?= $s['id'] ?>"
                                       <?= $preServicio == $s['id'] ? 'checked' : '' ?>
                                       style="display:none;">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <div class="fw-semibold"><?= e($s['nombre']) ?></div>
                                        <div class="text-muted small mt-1"><?= e($s['descripcion']) ?></div>
                                    </div>
                                    <div class="text-end ms-3 flex-shrink-0">
                                        <div class="text-gold fw-bold"><?= formatMoney($s['precio']) ?></div>
                                        <div class="text-muted" style="font-size:.75rem;">
                                            <i class="bi bi-clock me-1"></i><?= (int)$s['duracion_min'] ?> min
                                        </div>
                                    </div>
                                </div>
                            </label>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="text-center mt-4">
                        <button type="button" class="btn btn-gold px-5" data-next="2">
                            Continuar <i class="bi bi-arrow-right ms-2"></i>
                        </button>
                    </div>
                </div>

                <!-- PASO 2: Barbero -->
                <div class="wizard-step d-none" id="step-2">
                    <h5 class="text-center mb-4">¿Con qué barbero deseas tu cita?</h5>
                    <div class="row g-3 justify-content-center">
                        <?php foreach ($barberos as $b): ?>
                        <div class="col-sm-6 col-md-4">
                            <div class="barber-card" data-barbero-id="<?= $b['id'] ?>">
                                <input type="radio" name="_bar" value="<?= $b['id'] ?>" style="display:none;">
                                <div class="barber-avatar mx-auto mb-2">
                                    <?= strtoupper(substr($b['nombre'], 0, 1)) ?>
                                </div>
                                <div class="fw-semibold"><?= e($b['nombre'] . ' ' . $b['apellido']) ?></div>
                                <div class="text-muted small"><?= e($b['especialidad'] ?? 'Barbero') ?></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="d-flex justify-content-center gap-3 mt-4">
                        <button type="button" class="btn btn-outline-gold px-4" data-prev="1">
                            <i class="bi bi-arrow-left me-2"></i>Atrás
                        </button>
                        <button type="button" class="btn btn-gold px-5" data-next="3">
                            Continuar <i class="bi bi-arrow-right ms-2"></i>
                        </button>
                    </div>
                </div>

                <!-- PASO 3: Fecha y hora -->
                <div class="wizard-step d-none" id="step-3">
                    <h5 class="text-center mb-4">Selecciona fecha y horario</h5>
                    <div class="row g-4">
                        <div class="col-md-5">
                            <label class="form-label">Fecha de la cita</label>
                            <input type="date" id="fecha" name="fecha" class="form-control min-today" required>
                        </div>
                        <div class="col-md-7">
                            <label class="form-label">Horarios disponibles</label>
                            <div id="slots-container" class="text-muted small py-2">
                                Selecciona barbero, servicio y fecha para ver horarios disponibles.
                            </div>
                        </div>
                    </div>
                    <div class="d-flex justify-content-center gap-3 mt-4">
                        <button type="button" class="btn btn-outline-gold px-4" data-prev="2">
                            <i class="bi bi-arrow-left me-2"></i>Atrás
                        </button>
                        <button type="button" class="btn btn-gold px-5" data-next="4">
                            Continuar <i class="bi bi-arrow-right ms-2"></i>
                        </button>
                    </div>
                </div>

                <!-- PASO 4: Confirmación -->
                <div class="wizard-step d-none" id="step-4">
                    <h5 class="text-center mb-4">Confirma tu reserva</h5>
                    <div class="form-card mb-4" id="resumen-cita">
                        <p class="text-muted small text-center">Completa los pasos anteriores para ver el resumen.</p>
                    </div>
                    <div class="mb-4">
                        <label class="form-label">Notas adicionales (opcional)</label>
                        <textarea name="notas" class="form-control" rows="3"
                                  placeholder="Ej: Prefiero el cabello muy corto en los lados..."></textarea>
                    </div>
                    <div class="d-flex justify-content-center gap-3">
                        <button type="button" class="btn btn-outline-gold px-4" data-prev="3">
                            <i class="bi bi-arrow-left me-2"></i>Atrás
                        </button>
                        <button type="submit" class="btn btn-gold px-5">
                            <i class="bi bi-calendar-check me-2"></i>Confirmar cita
                        </button>
                    </div>
                </div>

            </div><!-- /max-width -->
        </form>
        <?php endif; ?>
    </div>
</div>

<style>
.service-select-card {
    background: var(--dark-2);
    border: 2px solid var(--border);
    border-radius: 10px;
    padding: 1.1rem 1.25rem;
    transition: all .25s ease;
    display: block;
}
.service-select-card:hover, .service-select-card.selected {
    border-color: var(--gold);
    box-shadow: 0 0 0 3px rgba(201,162,39,.12);
}
</style>

<?php require_once __DIR__ . '/../includes/footer_public.php'; ?>

<script>
// Actualizar resumen en paso 4
function actualizarResumen() {
    const srv = document.querySelector('.service-select-card.selected');
    const bar = document.querySelector('.barber-card.selected');
    const fecha = document.getElementById('fecha')?.value;
    const hora  = document.querySelector('.time-slot.selected')?.dataset.hora;

    const div = document.getElementById('resumen-cita');
    if (!div) return;

    if (srv && bar && fecha && hora) {
        div.innerHTML = `
            <div class="row g-3 text-center">
                <div class="col-4">
                    <div class="text-muted small mb-1">Servicio</div>
                    <div class="fw-semibold">${srv.querySelector('.fw-semibold')?.textContent}</div>
                    <div class="text-gold small">${srv.querySelector('.text-gold')?.textContent}</div>
                </div>
                <div class="col-4">
                    <div class="text-muted small mb-1">Barbero</div>
                    <div class="fw-semibold">${bar.querySelector('.fw-semibold')?.textContent}</div>
                </div>
                <div class="col-4">
                    <div class="text-muted small mb-1">Fecha y hora</div>
                    <div class="fw-semibold">${fecha}</div>
                    <div class="text-gold small">${hora}</div>
                </div>
            </div>`;
    }
}

document.addEventListener('click', e => {
    if (e.target.closest('[data-next]')) setTimeout(actualizarResumen, 100);
});
</script>
