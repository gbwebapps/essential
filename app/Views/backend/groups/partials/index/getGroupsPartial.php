<div class="row py-2">
    <div class="col-12">
        
        <div class="accordion" id="groupsAccordion">

            <?php if ( ! empty($groups)): ?>
                <?php foreach($groups as $group): ?>

                    <div class="accordion-item mb-3 border">
                        <h2 class="accordion-header" id="heading_group_<?= $group->id; ?>">
                            <button class="accordion-button collapsed shadow-none bg-light text-secondary group-toggle-btn" 
                                    type="button" 
                                    data-id="<?= $group->id; ?>" 
                                    data-bs-target="#collapse_group_<?= $group->id; ?>" 
                                    aria-expanded="false" 
                                    aria-controls="collapse_group_<?= $group->id; ?>">
                                <span class="fw-bold text-dark"><?= esc($group->name); ?></span>
                            </button>
                        </h2>
                        <div id="collapse_group_<?= $group->id; ?>" class="accordion-collapse collapse" aria-labelledby="heading_group_<?= $group->id; ?>" data-bs-parent="#groupsAccordion">
                            <div class="accordion-body bg-white border-top template-container"></div>
                        </div>
                    </div>

                <?php endforeach; ?>
            <?php else: ?>
                <div class="text-center text-muted py-3">
                    Nessun gruppo presente nel sistema.
                </div>
            <?php endif; ?>

        </div>

    </div>
</div>