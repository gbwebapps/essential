<div class="row py-2">
    <div class="col-12">
        
        <div class="accordion" id="groupsAccordion">

            <?php if ( ! empty($groups)): ?>
                <?php foreach($groups as $key => $group): ?>

                    <?php $marginClass = ($key === array_key_last($groups)) ? '' : 'mb-3'; ?>

                    <div class="accordion-item <?= esc($marginClass, 'attr'); ?> border">
                        <h2 class="accordion-header" id="heading_group_<?= esc($group->id, 'attr'); ?>">
                            <button class="accordion-button collapsed shadow-none app-bg-surface app-text-muted group-toggle-btn"
                                    type="button" 
                                    data-id="<?= esc($group->id, 'attr'); ?>"
                                    data-bs-target="#collapse_group_<?= esc($group->id, 'attr'); ?>"
                                    aria-expanded="false" 
                                    aria-controls="collapse_group_<?= esc($group->id, 'attr'); ?>">
                                <span class="fw-bold app-text"><?= esc($group->name); ?></span>
                            </button>
                        </h2>
                        <div id="collapse_group_<?= esc($group->id, 'attr'); ?>" class="accordion-collapse collapse" aria-labelledby="heading_group_<?= esc($group->id, 'attr'); ?>" data-bs-parent="#groupsAccordion">
                            <div class="accordion-body app-bg-surface-elevated border-top template-container"></div>
                        </div>
                    </div>

                <?php endforeach; ?>
            <?php else: ?>
                <div class="text-center app-text-muted py-3">
                    <?= ui_text('backend/groups.messages.noGroupFound', [], false); ?>
                </div>
            <?php endif; ?>

        </div>

    </div>
</div>
