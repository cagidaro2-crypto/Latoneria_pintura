<style>
/* ═══════════════════════════════════════════════════════════════════════
   SHARED STYLES — Todos los roles (Admin, Empleado, Cliente)
   Paleta: sidebar/topbar/footer negro, contenido gris claro
═══════════════════════════════════════════════════════════════════════ */

body, #pageContent { background: #f1f4f8 !important; color: #000000; }

/* ── Tipografía ──────────────────────────────────────────────────────── */
h1,h2,h3,h4,h5,h6 { color: #000000; }
.text-muted        { color: #64748b !important; }

/* ── Tarjeta base ────────────────────────────────────────────────────── */
.card {
    background: #ffffff !important;
    border: 1px solid #e2e8f0 !important;
    box-shadow: 0 1px 6px rgba(0,0,0,.06) !important;
    border-radius: 14px !important;
}
.card:hover { box-shadow: 0 4px 16px rgba(0,0,0,.09) !important; }

.card-header {
    background: #ffffff !important;
    border-bottom: 1px solid #f1f5f9 !important;
    border-radius: 13px 13px 0 0 !important;
    color: #000000 !important;
    font-weight: 700;
    padding: .9rem 1.4rem;
}

.card-body  { background: #ffffff !important; }
.card-footer{ background: #fafafa !important; border-top: 1px solid #f1f5f9 !important; }

/* ── Tablas ──────────────────────────────────────────────────────────── */
.table { color: #000000; }
.table thead th {
    background: #f8fafc !important;
    color: #64748b;
    font-size: .72rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .4px;
    border-bottom: 1px solid #e2e8f0;
    padding: .75rem 1rem;
}
.table tbody td {
    padding: .8rem 1rem;
    border-bottom: 1px solid #f1f5f9;
    vertical-align: middle;
    font-size: .88rem;
}
.table tbody tr:last-child td { border-bottom: none; }
.table-hover tbody tr:hover { background: #f8fafc !important; }
.table-light { background: #f8fafc !important; }

/* ── Formularios ─────────────────────────────────────────────────────── */
.form-control, .form-select {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    color: #000000;
    border-radius: 8px;
    font-size: .875rem;
}
.form-control:focus, .form-select:focus {
    background: #fff;
    border-color: #000000;
    box-shadow: 0 0 0 3px rgba(0,0,0,.12);
    color: #000000;
}
.form-control::placeholder { color: #94a3b8; }
.form-label { color: #222222; font-size: .8rem; font-weight: 600; }
.form-text  { color: #94a3b8; font-size: .73rem; }

.input-group-text {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    color: #64748b;
}

/* ── Botones ─────────────────────────────────────────────────────────── */
.btn-primary   { background: #000000; border-color: #000000; font-weight: 600; border-radius: 8px; }
.btn-primary:hover { background: #000000; border-color: #000000; }
.btn-secondary { background: #6b7280; border-color: #6b7280; border-radius: 8px; }
.btn-success   { background: #22c55e; border-color: #22c55e; border-radius: 8px; }
.btn-danger    { background: #ef4444; border-color: #ef4444; border-radius: 8px; }
.btn-warning   { background: #f59e0b; border-color: #f59e0b; border-radius: 8px; }

.btn-outline-primary   { color: #000000; border-color: #000000; border-radius: 8px; }
.btn-outline-primary:hover { background: #000000; color: #fff; }
.btn-outline-secondary { border-radius: 8px; }
.btn-outline-danger    { border-radius: 8px; }
.btn-outline-success   { border-radius: 8px; }
.btn-outline-warning   { border-radius: 8px; }

/* ── Badges ──────────────────────────────────────────────────────────── */
.badge { font-weight: 600; letter-spacing: .2px; padding: .35em .75em; border-radius: 20px; }
.bg-success { background: #22c55e !important; }
.bg-danger  { background: #ef4444 !important; }
.bg-warning { background: #f59e0b !important; }
.bg-info    { background: #06b6d4 !important; }
.bg-primary { background: #000000 !important; }
.bg-secondary { background: #6b7280 !important; }
.bg-light   { background: #f1f5f9 !important; color: #475569 !important; }

/* ── Modal ───────────────────────────────────────────────────────────── */
.modal-content {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    box-shadow: 0 20px 60px rgba(0,0,0,.15);
}
.modal-header {
    border-bottom: 1px solid #e2e8f0;
    border-radius: 13px 13px 0 0;
    padding: 1rem 1.4rem;
}
.modal-footer {
    border-top: 1px solid #e2e8f0;
    background: #f8fafc;
    border-radius: 0 0 13px 13px;
}
.modal-title { font-weight: 700; color: #000000; }

/* Modal header con gradiente (sobreescribe inline) */
.modal-header[style*="gradient"] .modal-title { color: #fff !important; }

/* ── Alertas ─────────────────────────────────────────────────────────── */
.alert { border-radius: 10px; border: none; }
.alert-info    { background: #f1f5f9; color: #000000; }
.alert-success { background: #f0fdf4; color: #15803d; }
.alert-warning { background: #fefce8; color: #854d0e; }
.alert-danger  { background: #fef2f2; color: #b91c1c; }
.alert-light   { background: #f8fafc; color: #475569; border: 1px solid #e2e8f0; }

/* ── Paginación ──────────────────────────────────────────────────────── */
.page-link { color: #000000; border-color: #e2e8f0; border-radius: 6px; }
.page-link:hover { background: #f1f5f9; color: #000000; }
.page-item.active .page-link { background: #000000; border-color: #000000; }

/* ── Encabezado de página ────────────────────────────────────────────── */
.page-title { font-size: 1.4rem; font-weight: 700; color: #000000; margin-bottom: .15rem; }
.page-sub   { font-size: .85rem; color: #64748b; margin: 0; }

/* ── Stat cards ──────────────────────────────────────────────────────── */
.stat-card {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    padding: 1.3rem 1.5rem;
    transition: transform .2s, box-shadow .2s;
    box-shadow: 0 1px 4px rgba(0,0,0,.05);
}
.stat-card:hover { transform: translateY(-3px); box-shadow: 0 6px 20px rgba(0,0,0,.09); }

/* ── Separador ───────────────────────────────────────────────────────── */
hr { border-color: #e2e8f0; }
</style>
