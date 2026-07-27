<?php
$appearanceClass = $appearanceClass ?? '';
?>
<div class="appearance-controls d-flex align-items-center gap-2 <?= htmlspecialchars($appearanceClass) ?>">
    <button type="button"
            class="btn btn-light appearance-icon-btn"
            data-theme-toggle
            aria-label="Switch theme"
            title="Switch theme">
        <i class="bi bi-sun-fill" data-theme-icon></i>
    </button>

    <div class="dropdown">
        <button type="button"
                class="btn btn-light appearance-icon-btn"
                data-bs-toggle="dropdown"
                data-bs-auto-close="outside"
                aria-expanded="false"
                aria-label="Choose color palette"
                title="Choose color palette">
            <i class="bi bi-palette-fill"></i>
        </button>
        <div class="dropdown-menu dropdown-menu-end appearance-menu shadow">
            <div class="px-3 pt-3 pb-2">
                <div class="small text-uppercase fw-bold text-muted appearance-eyebrow">Appearance</div>
                <div class="fw-semibold mt-1">Color palette</div>
                <div class="small text-muted">Theme and palette are saved on this device.</div>
            </div>

            <div class="px-2 pb-2">
                <button type="button" class="dropdown-item palette-option" data-palette-value="aurora">
                    <span class="palette-swatch palette-swatch-aurora" aria-hidden="true">
                        <span></span><span></span><span></span>
                    </span>
                    <span class="flex-grow-1">
                        <span class="d-block fw-semibold">Aurora</span>
                        <span class="d-block small text-muted">Violet, indigo and teal</span>
                    </span>
                    <i class="bi bi-check-lg" data-palette-check></i>
                </button>

                <button type="button" class="dropdown-item palette-option" data-palette-value="ocean">
                    <span class="palette-swatch palette-swatch-ocean" aria-hidden="true">
                        <span></span><span></span><span></span>
                    </span>
                    <span class="flex-grow-1">
                        <span class="d-block fw-semibold">Original Ocean</span>
                        <span class="d-block small text-muted">Restore the previous colors</span>
                    </span>
                    <i class="bi bi-check-lg invisible" data-palette-check></i>
                </button>
            </div>

            <div class="appearance-menu-footer border-top px-3 py-2 small">
                <i class="bi bi-circle-half me-1"></i>
                <span data-theme-label>Dark theme</span>
                <span class="mx-1">·</span>
                <span data-palette-label>Aurora</span>
            </div>
        </div>
    </div>
</div>
