/* ============================================================
   BARBERÍA ELITE — JS del Panel Admin / Barbero
   ============================================================ */

'use strict';

// ─── Sidebar toggle (mobile) ─────────────────────────────────────────────────
const sidebar  = document.getElementById('adminSidebar');
const toggle   = document.getElementById('sidebarToggle');
const overlay  = document.getElementById('sidebarOverlay');

function openSidebar() {
    sidebar?.classList.add('open');
    overlay?.classList.add('active');
    document.body.style.overflow = 'hidden';
}
function closeSidebar() {
    sidebar?.classList.remove('open');
    overlay?.classList.remove('active');
    document.body.style.overflow = '';
}

toggle?.addEventListener('click', openSidebar);
overlay?.addEventListener('click', closeSidebar);

// ─── Confirmaciones de eliminación ──────────────────────────────────────────
document.addEventListener('click', e => {
    const btn = e.target.closest('[data-confirm]');
    if (!btn) return;
    e.preventDefault();
    const msg  = btn.dataset.confirm || '¿Estás seguro de realizar esta acción?';
    const href = btn.href || btn.dataset.href;
    if (confirm(msg) && href) window.location.href = href;
});

// ─── Búsqueda en tablas ──────────────────────────────────────────────────────
document.querySelectorAll('[data-search-table]').forEach(input => {
    const tableId = input.dataset.searchTable;
    const table   = document.getElementById(tableId);
    if (!table) return;

    input.addEventListener('input', () => {
        const q = input.value.toLowerCase().trim();
        table.querySelectorAll('tbody tr').forEach(row => {
            row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
        });
    });
});

// ─── Toast admin ─────────────────────────────────────────────────────────────
function adminToast(msg, tipo = 'success') {
    let wrap = document.getElementById('admin-toast-wrap');
    if (!wrap) {
        wrap = document.createElement('div');
        wrap.id = 'admin-toast-wrap';
        wrap.style.cssText = 'position:fixed;top:5rem;right:1.5rem;z-index:9999;display:flex;flex-direction:column;gap:.5rem;';
        document.body.appendChild(wrap);
    }
    const colors = { success: '#4ade80', warning: '#c9a227', danger: '#f87171', info: '#7dd3f8' };
    const t = document.createElement('div');
    t.style.cssText = `background:#1e1e1e;border:1px solid ${colors[tipo]};color:#e8e8e8;
        padding:.75rem 1.25rem;border-radius:8px;font-size:.84rem;min-width:260px;
        box-shadow:0 6px 20px rgba(0,0,0,.6);`;
    t.innerHTML = `<i class="bi bi-check-circle me-2" style="color:${colors[tipo]}"></i>${msg}`;
    wrap.appendChild(t);
    setTimeout(() => { t.style.opacity = '0'; t.style.transition = 'opacity .4s'; setTimeout(() => t.remove(), 400); }, 3200);
}

// ─── Auto-dismiss alertas ─────────────────────────────────────────────────────
document.querySelectorAll('.alert.auto-dismiss').forEach(alert => {
    setTimeout(() => {
        const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
        bsAlert?.close();
    }, 4000);
});

// ─── Validación Bootstrap ─────────────────────────────────────────────────────
document.querySelectorAll('form.needs-validation').forEach(form => {
    form.addEventListener('submit', e => {
        if (!form.checkValidity()) {
            e.preventDefault();
            e.stopPropagation();
        }
        form.classList.add('was-validated');
    });
});

// ─── Cargador de slots de hora en el modal de nueva cita (admin) ─────────────
const modalFecha    = document.getElementById('modal_fecha');
const modalBarbero  = document.getElementById('modal_barbero_id');
const modalServicio = document.getElementById('modal_servicio_id');
const modalHora     = document.getElementById('modal_hora_inicio');

function recargarSlots() {
    if (!modalFecha || !modalBarbero || !modalServicio) return;
    const f = modalFecha.value, b = modalBarbero.value, s = modalServicio.value;
    if (!f || !b || !s) return;

    fetch(`/Barberia/api/disponibilidad.php?barbero_id=${b}&servicio_id=${s}&fecha=${f}`)
        .then(r => r.json())
        .then(data => {
            if (!modalHora) return;
            modalHora.innerHTML = '<option value="">-- Selecciona hora --</option>';
            (data.slots || []).forEach(slot => {
                if (slot.disponible) {
                    modalHora.innerHTML += `<option value="${slot.hora}">${slot.hora_display}</option>`;
                }
            });
        });
}

[modalFecha, modalBarbero, modalServicio].forEach(el => el?.addEventListener('change', recargarSlots));

// ─── Fecha mínima = hoy ──────────────────────────────────────────────────────
document.querySelectorAll('input[type="date"]').forEach(el => {
    if (!el.min) el.min = new Date().toISOString().split('T')[0];
});

// ─── Preview de imagen al subir ──────────────────────────────────────────────
document.querySelectorAll('input[type="file"][data-preview]').forEach(input => {
    const previewId = input.dataset.preview;
    const preview   = document.getElementById(previewId);
    if (!preview) return;
    input.addEventListener('change', () => {
        const file = input.files[0];
        if (file && file.type.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = e => { preview.src = e.target.result; preview.classList.remove('d-none'); };
            reader.readAsDataURL(file);
        }
    });
});

// ─── Highlight fila activa ───────────────────────────────────────────────────
document.querySelectorAll('.table tbody tr').forEach(row => {
    row.addEventListener('click', function(e) {
        if (e.target.closest('a,button,input,select')) return;
        document.querySelectorAll('.table tbody tr').forEach(r => r.classList.remove('table-active'));
        this.classList.add('table-active');
    });
});
