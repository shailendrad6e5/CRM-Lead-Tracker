(function () {
    'use strict';

    const storageKeys = {
        theme: 'crm_appearance_theme',
        palette: 'crm_appearance_palette'
    };
    const allowedThemes = ['light', 'dark'];
    const allowedPalettes = ['aurora', 'ocean'];
    const root = document.documentElement;

    function readPreference(key, allowed, fallback) {
        try {
            const value = localStorage.getItem(key);
            return allowed.includes(value) ? value : fallback;
        } catch (error) {
            return fallback;
        }
    }

    function savePreference(key, value) {
        try {
            localStorage.setItem(key, value);
        } catch (error) {
            // Appearance still works for this page when storage is unavailable.
        }
    }

    function currentAppearance() {
        return {
            theme: allowedThemes.includes(root.dataset.theme) ? root.dataset.theme : 'dark',
            palette: allowedPalettes.includes(root.dataset.palette) ? root.dataset.palette : 'aurora'
        };
    }

    function syncControls() {
        const appearance = currentAppearance();
        const isDark = appearance.theme === 'dark';

        document.querySelectorAll('[data-theme-toggle]').forEach((button) => {
            button.setAttribute('aria-label', isDark ? 'Switch to light theme' : 'Switch to dark theme');
            button.setAttribute('title', isDark ? 'Switch to light theme' : 'Switch to dark theme');
        });
        document.querySelectorAll('[data-theme-icon]').forEach((icon) => {
            icon.className = 'bi ' + (isDark ? 'bi-sun-fill' : 'bi-moon-stars-fill');
        });
        document.querySelectorAll('[data-theme-label]').forEach((label) => {
            label.textContent = isDark ? 'Dark theme' : 'Light theme';
        });
        document.querySelectorAll('[data-palette-label]').forEach((label) => {
            label.textContent = appearance.palette === 'aurora' ? 'Aurora' : 'Original Ocean';
        });
        document.querySelectorAll('[data-palette-value]').forEach((button) => {
            const active = button.dataset.paletteValue === appearance.palette;
            button.classList.toggle('active', active);
            button.setAttribute('aria-pressed', active ? 'true' : 'false');
            const check = button.querySelector('[data-palette-check]');
            if (check) check.classList.toggle('invisible', !active);
        });
    }

    function applyAppearance(theme, palette, persist) {
        const safeTheme = allowedThemes.includes(theme) ? theme : 'dark';
        const safePalette = allowedPalettes.includes(palette) ? palette : 'aurora';

        root.dataset.theme = safeTheme;
        root.dataset.palette = safePalette;
        root.setAttribute('data-bs-theme', safeTheme);
        root.style.colorScheme = safeTheme;

        if (persist) {
            savePreference(storageKeys.theme, safeTheme);
            savePreference(storageKeys.palette, safePalette);
        }

        syncControls();
        window.dispatchEvent(new CustomEvent('crmappearancechange', {
            detail: { theme: safeTheme, palette: safePalette }
        }));
    }

    const initialTheme = readPreference(storageKeys.theme, allowedThemes, 'dark');
    const initialPalette = readPreference(storageKeys.palette, allowedPalettes, 'aurora');
    applyAppearance(initialTheme, initialPalette, false);

    document.addEventListener('DOMContentLoaded', function () {
        syncControls();

        document.querySelectorAll('[data-theme-toggle]').forEach((button) => {
            button.addEventListener('click', function () {
                const appearance = currentAppearance();
                applyAppearance(appearance.theme === 'dark' ? 'light' : 'dark', appearance.palette, true);
            });
        });

        document.querySelectorAll('[data-palette-value]').forEach((button) => {
            button.addEventListener('click', function () {
                const appearance = currentAppearance();
                applyAppearance(appearance.theme, button.dataset.paletteValue, true);
            });
        });
    });

    window.CRMTheme = {
        get: currentAppearance,
        apply: function (theme, palette) {
            applyAppearance(theme, palette, true);
        }
    };
}());
