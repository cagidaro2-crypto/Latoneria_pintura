# Documentación: footer.php

## Descripción general
Layout de pie de página compartido por todas las vistas del dashboard. Cierra las etiquetas abiertas por `header.php` (`</section>`, `</div>`, `</div>`), renderiza el footer negro con copyright y versión del sistema, incluye el JavaScript de Bootstrap 5 y cierra los tags `</body>` y `</html>`. Es siempre el último archivo incluido en las vistas del dashboard.

## Dependencias
- Bootstrap 5.3.3 JS (CDN) — Para componentes interactivos (modales, tabs, etc.)

## Código documentado línea por línea

```html
<!-- Línea 1: Cierra el tag <section id="pageContent"> abierto en header.php -->
    </section><!-- /pageContent -->

    <!-- ── FOOTER DEL SISTEMA ──────────────────────────────────────────── -->

    <!-- Líneas 4-13: Elemento <footer> con estilos inline -->
    <!-- Fondo negro (#000000), borde superior sutil blanco, padding vertical compacto -->
    <!-- margin-top: auto empuja el footer al final si el contenido es corto (flexbox) -->
    <!-- flex-shrink: 0 evita que el footer se comprima en pantallas pequeñas -->
    <footer style="
        background: #000000;
        border-top: 1px solid rgba(255,255,255,.06);
        padding: .85rem 0;
        margin-top: auto;
        flex-shrink: 0;
    ">
        <!-- Contenedor fluid con padding horizontal para alinear el contenido -->
        <div class="container-fluid px-4">
            <!-- Fila flex que separa copyright (izquierda) y enlaces (derecha) -->
            <!-- En móvil, cambia a columna con gap -->
            <div class="d-flex align-items-center justify-content-between flex-column flex-sm-row gap-2">

                <!-- Copyright con año dinámico generado por PHP date('Y') -->
                <span style="font-size:.82rem; color:#6b7280;">
                    &copy; <?= date('Y') ?>  <!-- Año actual automático -->
                    <strong style="color:#f9fafb;">Taller de Latonería y Pintura</strong>.
                    Todos los derechos reservados.
                </span>

                <!-- Bloque de enlaces secundarios + versión del sistema -->
                <div class="d-flex align-items-center gap-3">
                    <!-- Enlace Soporte: color gris, hover aclarado con JavaScript inline -->
                    <a href="#" style="font-size:.82rem; color:#6b7280; text-decoration:none; transition:color .2s;"
                       onmouseover="this.style.color='#9ca3af'"
                       onmouseout="this.style.color='#6b7280'">Soporte</a>

                    <!-- Enlace Términos: mismo estilo que Soporte -->
                    <a href="#" style="font-size:.82rem; color:#6b7280; text-decoration:none; transition:color .2s;"
                       onmouseover="this.style.color='#9ca3af'"
                       onmouseout="this.style.color='#6b7280'">Términos</a>

                    <!-- Versión del sistema hardcodeada -->
                    <span style="font-size:.82rem; color:#6b7280;">v2.1.0</span>
                </div>
            </div>
        </div>
    </footer>

<!-- Línea 36: Cierra el div #mainContent abierto en header.php -->
</div><!-- /mainContent -->

<!-- Línea 37: Cierra el div wrapper .d-flex abierto en header.php -->
</div><!-- /d-flex wrapper -->

<!-- Línea 39: Bootstrap 5 JavaScript Bundle (incluye Popper.js) desde CDN -->
<!-- Necesario para modales, tooltips, dropdowns y otros componentes interactivos -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- Línea 40: Cierre del body -->
</body>
<!-- Línea 41: Cierre del documento HTML -->
</html>
```

## Resumen de componentes renderizados

| Elemento | Descripción |
|---------|-------------|
| `</section>` | Cierra el área de contenido de la página |
| `<footer>` | Pie de página negro con copyright dinámico |
| Copyright | Año generado dinámicamente con `date('Y')` |
| Soporte / Términos | Enlaces de navegación secundaria (sin destino definido aún) |
| Versión `v2.1.0` | Indicador de versión del sistema |
| Bootstrap JS Bundle | Script necesario para componentes interactivos de Bootstrap |
| `</div>` × 2 | Cierre de `#mainContent` y del wrapper `.d-flex` |

## Flujo de ejecución
1. Se incluye con `require_once __DIR__ . '/../layouts/footer.php'` al final de cada vista.
2. Cierra el `</section>` del contenido de página.
3. Renderiza el footer con año dinámico y versión.
4. Cierra los `</div>` del layout principal (mainContent y wrapper flex).
5. Carga Bootstrap 5 JS (necesario para cualquier modal que la vista haya definido).
6. Cierra `</body>` y `</html>` completando el documento.
