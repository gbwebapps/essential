<div class="card-body">
    <div class="row mb-3">

        <!-- Categoria, categoria padre e stato -->
        <div class="col-12 col-lg-4">
            <ul class="list-group list-group-flush">
                <li class="list-group-item"><i class="fa-solid fa-arrow-circle-down"></i><?= ui_text('backend/categories.labels.category', [], false); ?></li>
                <li class="list-group-item fw-bold"><?= esc($category->category); ?></li>
            </ul>
        </div>
        <div class="col-12 col-lg-4">
            <ul class="list-group list-group-flush">
                <li class="list-group-item"><i class="fa-solid fa-arrow-circle-down"></i><?= ui_text('backend/categories.labels.parentCategory', [], false); ?></li>
                <li class="list-group-item fw-bold"><?= $category->parent_category !== null ? esc($category->parent_category) : ui_text('backend/categories.labels.rootCategory'); ?></li>
            </ul>
        </div>
        <div class="col-12 col-lg-4">
            <ul class="list-group list-group-flush">
                <li class="list-group-item"><i class="fa-solid fa-arrow-circle-down"></i><?= ui_text('backend/categories.labels.status', [], false); ?></li>
                <li class="list-group-item">
                    <div id="changeStatusPartial">
                        <?= $this->include(\Modules\Backend\Categories\Config\Views::CHANGE_STATUS_PARTIAL, $this->data); ?>
                    </div>
                </li>
            </ul>
        </div>
        <!-- End categoria, categoria padre e stato -->

    </div>

    <?php if( ! empty($category->note)): ?>
        <div class="row">

            <!-- Campo note -->
            <div class="col-12">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item"><i class="fa-solid fa-arrow-circle-down"></i><?= ui_text('backend/categories.labels.note', [], false); ?></li>
                    <li class="list-group-item fw-bold"><?= esc($category->note); ?></li>
                </ul>
            </div>
            <!-- End Campo note -->

        </div>
    <?php endif; ?>
</div>
