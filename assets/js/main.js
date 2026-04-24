/* ============================================================
   BARBERÍA ELITE — JS del sitio público
   ============================================================ */

'use strict';

// ─── Navbar scroll effect ────────────────────────────────────────────────────
const mainNav = document.getElementById('mainNav');
if (mainNav) {
    window.addEventListener('scroll', () => {
        mainNav.classList.toggle('scrolled', window.scrollY > 60);
    }, { passive: true });
}

// ─── Animaciones al hacer scroll (Intersection Observer) ────────────────────
const observeEls = document.querySelectorAll('[data-aos]');
if (observeEls.length) {
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(e => {
            if (e.isIntersecting) {
                e.target.classList.add('fade-in-up');
                observer.unobserve(e.target);
            }
        });
    }, { threshold: 0.12 });
    observeEls.forEach(el => observer.observe(el));
}

// ─── Reserva — Paso a paso ───────────────────────────────────────────────────
const ReservaWizard = {
    paso: 1,

    init() {
        this.mostrarPaso(1);
        document.querySelectorAll('[data-next]').forEach(btn => {
            btn.addEventListener('click', () => this.siguiente(parseInt(btn.dataset.next)));
        });
        document.querySelectorAll('[data-prev]').forEach(btn => {
            btn.addEventListener('click', () => this.anterior(parseInt(btn.dataset.prev)));
        });
    },

    mostrarPaso(n) {
        document.querySelectorAll('.wizard-step').forEach(el => el.classList.add('d-none'));
        const step = document.getElementById('step-' + n);
        if (step) { step.classList.remove('d-none'); step.classList.add('fade-in-up'); }

        // Actualizar indicador
        document.querySelectorAll('.step-item').forEach((el, i) => {
            el.classList.toggle('active', i + 1 === n);
            el.classList.toggle('done', i + 1 < n);
        });
        this.paso = n;
    },

    siguiente(n) {
        if (!this.validarPaso(this.paso)) return;
        this.mostrarPaso(n);
    },

    anterior(n) {
        this.mostrarPaso(n);
    },

    validarPaso(n) {
        if (n === 1) {
            const servicio = document.querySelector('input[name="servicio_id"]:checked');
            if (!servicio) { mostrarToast('Selecciona un servicio para continuar.', 'warning'); return false; }
        }
        if (n === 2) {
            const barbero = document.querySelector('input[name="barbero_id"]:checked');
            if (!barbero) { mostrarToast('Selecciona un barbero para continuar.', 'warning'); return false; }
        }
        if (n === 3) {
            const fecha = document.getElementById('fecha')?.value;
            const hora  = document.querySelector('.time-slot.selected')?.dataset.hora;
            if (!fecha) { mostrarToast('Selecciona una fecha.', 'warning'); return false; }
            if (!hora)  { mostrarToast('Selecciona un horario disponible.', 'warning'); return false; }
            document.getElementById('hora_input').value = hora;
        }
        return true;
    }
};

if (document.getElementById('step-1')) ReservaWizard.init();

// ─── Slots de horario ────────────────────────────────────────────────────────
document.addEventListener('click', e => {
    if (e.target.classList.contains('time-slot') && !e.target.classList.contains('occupied')) {
        document.querySelectorAll('.time-slot').forEach(s => s.classList.remove('selected'));
        e.target.classList.add('selected');
    }
});

// ─── Cargar slots disponibles vía AJAX ──────────────────────────────────────
function cargarSlots(barberoId, servicioId, fecha) {
    const contenedor = document.getElementById('slots-container');
    if (!contenedor) return;

    contenedor.innerHTML = '<div class="text-muted small py-3"><div class="spinner-border spinner-border-sm me-2"></div>Cargando horarios…</div>';

    fetch(`/Barberia/api/disponibilidad.php?barbero_id=${barberoId}&servicio_id=${servicioId}&fecha=${fecha}`)
        .then(r => r.json())
        .then(data => {
            if (!data.slots || data.slots.length === 0) {
                contenedor.innerHTML = '<p class="text-muted small">No hay horarios disponibles para esta fecha.</p>';
                return;
            }
            contenedor.innerHTML = data.slots.map(s =>
                `<span class="time-slot ${s.disponible ? '' : 'occupied'}" data-hora="${s.hora}">
                    ${s.hora_display}
                </span>`
            ).join('');
        })
        .catch(() => {
            contenedor.innerHTML = '<p class="text-danger small">Error al cargar horarios.</p>';
        });
}

// Escuchar cambios en fecha, barbero o servicio
['fecha', 'barbero_id_hidden', 'servicio_id_hidden'].forEach(id => {
    document.getElementById(id)?.addEventListener('change', triggerSlotLoad);
});

function triggerSlotLoad() {
    const barberoId  = document.getElementById('barbero_id_hidden')?.value;
    const servicioId = document.getElementById('servicio_id_hidden')?.value;
    const fecha      = document.getElementById('fecha')?.value;
    if (barberoId && servicioId && fecha) cargarSlots(barberoId, servicioId, fecha);
}

// Selección de barbero (cards radio)
document.querySelectorAll('.barber-card').forEach(card => {
    card.addEventListener('click', () => {
        document.querySelectorAll('.barber-card').forEach(c => c.classList.remove('selected'));
        card.classList.add('selected');
        const input = card.querySelector('input[type="radio"]');
        if (input) {
            input.checked = true;
            const hidden = document.getElementById('barbero_id_hidden');
            if (hidden) { hidden.value = input.value; triggerSlotLoad(); }
        }
    });
});

// Selección de servicio (cards radio)
document.querySelectorAll('.service-select-card').forEach(card => {
    card.addEventListener('click', () => {
        document.querySelectorAll('.service-select-card').forEach(c => c.classList.remove('selected'));
        card.classList.add('selected');
        const input = card.querySelector('input[type="radio"]');
        if (input) {
            input.checked = true;
            const hidden = document.getElementById('servicio_id_hidden');
            if (hidden) { hidden.value = input.value; triggerSlotLoad(); }
        }
    });
});

// ─── Toast genérico ──────────────────────────────────────────────────────────
function mostrarToast(msg, tipo = 'info') {
    let wrap = document.getElementById('toast-wrap');
    if (!wrap) {
        wrap = document.createElement('div');
        wrap.id = 'toast-wrap';
        wrap.style.cssText = 'position:fixed;bottom:1.5rem;right:1.5rem;z-index:9999;display:flex;flex-direction:column;gap:.5rem;';
        document.body.appendChild(wrap);
    }
    const colors = { success: '#4ade80', warning: '#c9a227', danger: '#f87171', info: '#7dd3f8' };
    const t = document.createElement('div');
    t.style.cssText = `background:#1e1e1e;border:1px solid ${colors[tipo]||colors.info};color:#e8e8e8;
        padding:.75rem 1.25rem;border-radius:10px;font-size:.86rem;max-width:320px;
        box-shadow:0 6px 20px rgba(0,0,0,.5);animation:fadeInUp .3s ease;`;
    t.textContent = msg;
    wrap.appendChild(t);
    setTimeout(() => t.remove(), 3800);
}

// ─── Validación de formularios Bootstrap ────────────────────────────────────
document.querySelectorAll('form.needs-validation').forEach(form => {
    form.addEventListener('submit', e => {
        if (!form.checkValidity()) {
            e.preventDefault();
            e.stopPropagation();
        }
        form.classList.add('was-validated');
    });
});

// ─── Fecha mínima = hoy ──────────────────────────────────────────────────────
document.querySelectorAll('input[type="date"].min-today').forEach(el => {
    el.min = new Date().toISOString().split('T')[0];
});
