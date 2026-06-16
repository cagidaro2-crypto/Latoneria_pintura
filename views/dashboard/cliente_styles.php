<style>
/* ══════════════════════════════════════════════════════════════════════════
   CLIENTE STYLES — Paleta gris claro + estándar
   Prefijo: cs- (client styles)
═══════════════════════════════════════════════════════════════════════════ */

body, #pageContent { background: #f1f4f8 !important; color: #000000; }

/* ── Tipografía base ──────────────────────────────────────────────────── */
.cs-title   { font-size:1.4rem; font-weight:700; color:#000000; margin-bottom:.15rem; }
.cs-sub     { font-size:.85rem; color:#64748b; }
.cs-muted   { color:#94a3b8; }
.cs-icon-blue { color:#000000; }

/* ── Botón negro ─────────────────────────────────────────────────────── */
.cs-btn-dark {
    background: #000000;
    color: #fff;
    border: none;
    border-radius: 9px;
    padding: .42rem 1rem;
    font-size: .82rem;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: .45rem;
    text-decoration: none;
    cursor: pointer;
    transition: background .18s, transform .15s;
    white-space: nowrap;
}
.cs-btn-dark:hover { background:#000000; color:#fff; transform:translateY(-1px); }

/* ── Botón naranja ───────────────────────────────────────────────────── */
.cs-btn-orange {
    background: #f97316;
    color: #ffffff;
    border: none;
    border-radius: 9px;
    padding: .42rem 1rem;
    font-size: .82rem;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: .45rem;
    text-decoration: none;
    cursor: pointer;
    transition: background .18s, transform .15s;
    white-space: nowrap;
}
.cs-btn-orange:hover { background:#ea580c; color:#ffffff; transform:translateY(-1px); }

/* ── Tarjeta genérica ────────────────────────────────────────────────── */
.cs-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    box-shadow: 0 1px 6px rgba(0,0,0,.06);
    overflow: hidden;
    transition: box-shadow .2s;
}
.cs-card:hover { box-shadow: 0 4px 18px rgba(0,0,0,.1); }

.cs-card-head {
    padding: 1rem 1.4rem;
    border-bottom: 1px solid #f1f4f8;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: .5rem;
}
.cs-card-body { padding: 1rem 1.4rem; }
.cs-card-foot { padding: .8rem 1.4rem 1.1rem; }

/* ── Placa / info ────────────────────────────────────────────────────── */
.cs-placa {
    font-family: monospace;
    font-size: 1.1rem;
    font-weight: 800;
    color: #000000;
    letter-spacing: .5px;
}
.cs-info { font-size:.88rem; color:#64748b; }

/* ── Badges de estado ────────────────────────────────────────────────── */
.cs-badge {
    font-size: .76rem; font-weight: 700;
    padding: .3em .8em; border-radius: 20px; letter-spacing: .2px;
}
.est-espera     { background:#fef9c3; color:#854d0e; }
.est-reparacion { background:#e2e8f0; color:#000000; }
.est-pintura    { background:#e0e7ff; color:#4338ca; }
.est-listo      { background:#dcfce7; color:#15803d; }
.est-cancelado  { background:#fee2e2; color:#b91c1c; }
.est-default    { background:#f1f5f9; color:#475569; }

/* ── Hitos de progreso ───────────────────────────────────────────────── */
.cs-hitos {
    display: flex;
    justify-content: space-between;
    margin-bottom: .45rem;
}
.cs-hito { display:flex; flex-direction:column; align-items:center; gap:.2rem; flex:1; }
.cs-hito-dot {
    width:11px; height:11px; border-radius:50%;
    background:#fff; border:2px solid #e2e8f0;
    transition:all .3s;
}
.cs-hito-dot.done   { background:#22c55e; border-color:#22c55e; }
.cs-hito-dot.active { background:#000000; border-color:#000000; box-shadow:0 0 0 3px rgba(0,0,0,.15); }
.cs-hito-label {
    font-size:.62rem; color:#94a3b8;
    text-align:center; line-height:1.2; font-weight:500;
}
.cs-hito-label.done   { color:#22c55e; }
.cs-hito-label.active { color:#000000; font-weight:700; }

/* ── Barra de progreso ───────────────────────────────────────────────── */
.cs-prog-track {
    height:7px; background:#e2e8f0; border-radius:99px; overflow:hidden;
}
.cs-prog-fill {
    height:100%; border-radius:99px; transition:width .7s ease;
}

/* ── Último registro ────────────────────────────────────────────────── */
.cs-last-reg {
    display:flex; align-items:flex-start; gap:.75rem;
    background:#f8fafc; border:1px solid #e2e8f0;
    border-radius:10px; padding:.75rem 1rem; margin-top:1rem;
}
.cs-reg-icon {
    width:32px; height:32px; background:#f1f5f9;
    border-radius:8px; display:flex; align-items:center;
    justify-content:center; color:#000000; font-size:.9rem; flex-shrink:0;
}
.cs-reg-label { font-size:.68rem; color:#94a3b8; font-weight:700; text-transform:uppercase; letter-spacing:.4px; }
.cs-reg-tipo  { font-size:.82rem; font-weight:700; color:#000000; }
.cs-reg-desc  { font-size:.8rem;  color:#475569; margin-top:.1rem; }
.cs-reg-meta  { font-size:.7rem;  color:#94a3b8; margin-top:.15rem; }

/* ── Botón historial ────────────────────────────────────────────────── */
.cs-btn-hist {
    background: transparent;
    border: 1px solid #e2e8f0;
    color: #000000;
    border-radius: 8px;
    font-size: .78rem; font-weight: 600;
    padding: .35rem .85rem;
    cursor: pointer;
    display: inline-flex; align-items: center; gap: .4rem;
    transition: all .18s;
}
.cs-btn-hist:hover { background:#f1f5f9; border-color:#cbd5e1; }

/* ── Timeline historial ──────────────────────────────────────────────── */
.cs-timeline-wrap {
    margin-top:.85rem; padding:.9rem 1rem;
    background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px;
}
.cs-tl-title {
    font-size:.68rem; font-weight:700; color:#000000;
    text-transform:uppercase; letter-spacing:.5px; margin-bottom:.8rem;
}
.cs-tl { border-left:2px solid #e2e8f0; padding-left:1rem; }
.cs-tl-item { position:relative; margin-bottom:.85rem; }
.cs-tl-dot  {
    position:absolute; left:-1.36rem; top:.3rem;
    width:10px; height:10px; border-radius:50%;
    background:#000000; border:2px solid #fff; box-shadow:0 0 0 2px #000000;
}
.cs-tl-tipo { font-size:.72rem; font-weight:700; color:#000000; background:#f1f5f9; padding:.15em .55em; border-radius:4px; display:inline-block; }
.cs-tl-desc { font-size:.82rem; color:#334155; margin-top:.2rem; }
.cs-tl-meta { font-size:.7rem;  color:#94a3b8; margin-top:.1rem; }

/* ── Panel vacío ────────────────────────────────────────────────────── */
.cs-empty {
    background:#fff; border:1px solid #e2e8f0;
    border-radius:14px; padding:4rem 2rem; text-align:center;
    box-shadow:0 1px 6px rgba(0,0,0,.05);
}
.cs-empty-icon  { color:#cbd5e1; display:block; margin-bottom:1rem; }
.cs-empty-title { color:#64748b; font-weight:600; font-size:1rem; margin-bottom:.4rem; }
.cs-empty-sub   { color:#94a3b8; font-size:.88rem; margin-bottom:1.5rem; }

/* ── Tabla estándar ─────────────────────────────────────────────────── */
.cs-table { width:100%; border-collapse:collapse; }
.cs-table thead th {
    background:#f8fafc; color:#64748b;
    font-size:.72rem; font-weight:700; text-transform:uppercase;
    letter-spacing:.4px; padding:.75rem 1rem;
    border-bottom:1px solid #e2e8f0;
}
.cs-table tbody td {
    padding:.85rem 1rem; vertical-align:middle;
    border-bottom:1px solid #f1f5f9; font-size:.88rem; color:#000000;
}
.cs-table tbody tr:last-child td { border-bottom:none; }
.cs-table tbody tr:hover { background:#f8fafc; }

/* ── Filtros ─────────────────────────────────────────────────────────── */
.cs-filter-bar {
    background:#fff; border:1px solid #e2e8f0;
    border-radius:14px; padding:1.2rem 1.5rem; margin-bottom:1.5rem;
    box-shadow:0 1px 4px rgba(0,0,0,.04);
}
.cs-filter-bar .form-control,
.cs-filter-bar .form-select {
    background:#f8fafc; border:1px solid #e2e8f0;
    color:#000000; border-radius:8px; font-size:.85rem;
}
.cs-filter-bar .form-control::placeholder { color:#94a3b8; }
.cs-filter-bar .form-control:focus,
.cs-filter-bar .form-select:focus {
    background:#fff; border-color:#000000; box-shadow:none; color:#000000;
}

/* ── Chips de categoría ──────────────────────────────────────────────── */
.cs-chip {
    display:inline-flex; align-items:center;
    padding:.3rem .85rem; border-radius:20px;
    border:1px solid #e2e8f0; background:#f8fafc;
    color:#64748b; font-size:.78rem; font-weight:500;
    text-decoration:none; transition:all .18s; white-space:nowrap;
}
.cs-chip:hover  { background:#f1f5f9; border-color:#000000; color:#000000; }
.cs-chip.active { background:#000000; border-color:#000000; color:#fff; font-weight:700; }

/* ── Modal claro ─────────────────────────────────────────────────────── */
.cs-modal .modal-content { background:#fff; border:1px solid #e2e8f0; border-radius:14px; }
.cs-modal .modal-header  { background:#f8fafc; border-bottom:1px solid #e2e8f0; border-radius:13px 13px 0 0; }
.cs-modal .modal-footer  { background:#f8fafc; border-top:1px solid #e2e8f0; }
.cs-modal .modal-title   { color:#000000; font-weight:700; }
.cs-modal .form-label    { color:#222222; font-size:.8rem; font-weight:600; }
.cs-modal .form-control,
.cs-modal .form-select   {
    background:#f8fafc; border:1px solid #e2e8f0;
    color:#000000; border-radius:8px;
}
.cs-modal .form-control:focus,
.cs-modal .form-select:focus { background:#fff; border-color:#000000; box-shadow:none; }
.cs-modal .form-text { color:#94a3b8; font-size:.73rem; }

/* ── Tarjeta producto ────────────────────────────────────────────────── */
.cs-product-card {
    background:#fff; border:1px solid #e2e8f0; border-radius:14px;
    overflow:hidden; transition:transform .2s, box-shadow .2s;
    height:100%; display:flex; flex-direction:column;
}
.cs-product-card:hover { transform:translateY(-4px); box-shadow:0 8px 24px rgba(0,0,0,.1); }
.cs-product-icon {
    background:#f8fafc; display:flex; align-items:center;
    justify-content:center; height:110px; font-size:2.8rem; color:#94a3b8;
}
.cs-product-body  { padding:1rem 1.1rem; flex:1; display:flex; flex-direction:column; }
.cs-product-cat   { font-size:.7rem; color:#000000; font-weight:700; text-transform:uppercase; letter-spacing:.4px; margin-bottom:.3rem; }
.cs-product-name  { font-size:.95rem; font-weight:700; color:#000000; margin-bottom:.3rem; }
.cs-product-desc  { font-size:.78rem; color:#64748b; flex:1; margin-bottom:.8rem;
                    display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden; }
.cs-product-foot  {
    padding:.75rem 1.1rem; border-top:1px solid #f1f5f9;
    display:flex; align-items:center; justify-content:space-between; background:#fafafa;
}
.cs-product-price { font-size:1.05rem; font-weight:800; color:#000000; }

/* Stock badges */
.cs-stock { font-size:.72rem; font-weight:600; padding:.25em .65em; border-radius:20px; }
.cs-stock-ok  { background:#dcfce7; color:#15803d; }
.cs-stock-low { background:#fef9c3; color:#854d0e; }
.cs-stock-out { background:#fee2e2; color:#b91c1c; }

/* ── Calendario ─────────────────────────────────────────────────────── */
.cs-cal-card { background:#fff; border:1px solid #e2e8f0; border-radius:14px; overflow:hidden; margin-bottom:1.5rem; }
.cs-cal-head { padding:.9rem 1.4rem; border-bottom:1px solid #f1f5f9; background:#f8fafc; }
.cs-cal-head h6 { font-weight:700; color:#000000; margin:0 0 .2rem; }

/* ── Separador de sección ─────────────────────────────────────────────── */
.cs-sep {
    display:flex; align-items:center; gap:1rem;
    margin-bottom:1rem;
}
.cs-sep span.label {
    font-size:.7rem; font-weight:700; text-transform:uppercase;
    letter-spacing:1px; color:#000000; white-space:nowrap;
}
.cs-sep span.line { flex:1; height:1px; background:#e2e8f0; }
.cs-sep span.count { font-size:.72rem; color:#94a3b8; }
</style>
