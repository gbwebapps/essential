<li class="nav-item d-flex flex-column flex-lg-row" id="interface-preferences" data-url="<?= esc(base_url('backend/interface/change'), 'attr'); ?>" data-active-language="<?= esc(backend_language(), 'attr'); ?>" data-active-theme="<?= esc(backend_theme(), 'attr'); ?>">
    <div class="dropdown" id="interface-language-dropdown">
        <button class="nav-link dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" <?= ui_binding('backend/settings.labels.language', 'aria-label'); ?> aria-label="<?= esc(lang('backend/settings.labels.language'), 'attr'); ?>">
            <i class="fa-solid fa-globe" aria-hidden="true"></i> <?= ui_text('backend/settings.labels.language'); ?>
        </button>
        <ul class="dropdown-menu dropdown-menu-end">
            <?php foreach (['it' => 'italian', 'en' => 'english', 'es' => 'spanish', 'fr' => 'franch', 'de' => 'german', 'zh' => 'chinese'] as $code => $name): ?>
                <li><button type="button" class="dropdown-item<?= backend_language() === $code ? ' active' : ''; ?>" data-interface-language="<?= esc($code, 'attr'); ?>" aria-pressed="<?= backend_language() === $code ? 'true' : 'false'; ?>"><?= ui_text('backend/settings.languages.' . $name); ?></button></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <div class="dropdown" id="interface-theme-dropdown">
        <button class="nav-link dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" <?= ui_binding('backend/global.interface.themes', 'aria-label'); ?> aria-label="<?= esc(lang('backend/global.interface.themes'), 'attr'); ?>">
            <i class="fa-solid fa-palette" aria-hidden="true"></i> <?= ui_text('backend/global.interface.themes'); ?>
        </button>
        <ul class="dropdown-menu dropdown-menu-end">
            <?php foreach (\App\Libraries\Backend\InterfacePreferences::THEMES as $theme): ?>
                <li><button type="button" class="dropdown-item<?= backend_theme() === $theme ? ' active' : ''; ?>" data-interface-theme="<?= esc($theme, 'attr'); ?>" aria-pressed="<?= backend_theme() === $theme ? 'true' : 'false'; ?>"><?= ui_text('backend/settings.themes.' . $theme); ?></button></li>
            <?php endforeach; ?>
        </ul>
    </div>
</li>
