<?php $numOfPages = ceil($data['pagination']['totalRows'] / $data['pagination']['limit']); ?>

<div class="row">
    <div class="col-md-4">
        <?php $left = ($data['pagination']['totalRows'] == 0) ? '' : sprintf(lang('backend/global.pagination.messageLeft'), $data['pagination']['page'], $numOfPages); ?>
        <div class="pagination-left"><?= $left ?></div>
    </div>
    <div class="col-md-4">
        <ul class="fw-bold pagination pagination-center pagination-sm justify-content-center">

            <?php if ($data['pagination']['totalRows'] > $data['pagination']['limit']): ?>
                <?php if($data['pagination']['page'] == 1): ?>

                    <li class="page-item"></li>

                <?php else: ?>
                    <?php $pageprev = $data['pagination']['page'] - 1; ?>

                    <li class="page-item"><a class="page-link" href="#" data-page="1"><?= lang('backend/global.pagination.first'); ?></a></li>
                    <li class="page-item"><a class="page-link" href="#" data-page="<?= $pageprev ?>"><?= lang('backend/global.pagination.previous'); ?></a></li>

                <?php endif; ?>

                <?php $range = 3; ?>

                <?php if ($range == '' || $range == 0): ?>
                    <?php $range = 7; ?>
                <?php endif; ?>

                <?php $lrange = max(1, $data['pagination']['page'] - (($range - 1) / 2)); ?>
                <?php $rrange = min($numOfPages, $data['pagination']['page'] + (($range - 1) / 2)); ?>

                <?php if (($rrange - $lrange) < ($range - 1)): ?>
                    <?php if ($lrange == 1): ?>
                        <?php $rrange = min($lrange + ($range - 1), $numOfPages); ?>
                    <?php else: ?>
                        <?php $lrange = max($rrange - ($range - 1), 0); ?>
                    <?php endif; ?>
                <?php endif; ?>

                <?php if ($lrange > 1): ?>
                    <li class="page-item"><span class="page-link">...</span></li>
                <?php endif; ?>

                <?php for($i = 1; $i <= $numOfPages; $i++): ?>
                    <?php if ($i == $data['pagination']['page']): ?>
                        <li class="page-item active"><span class="page-link"><?= $i ?></span></li>
                    <?php else: ?>
                        <?php if ($lrange <= $i and $i <= $rrange): ?>
                            <li class="page-item"><a class="page-link" href="#" data-page="<?= $i ?>"><?= $i ?></a></li>
                        <?php endif; ?>
                    <?php endif; ?>
                <?php endfor; ?>

                <?php if ($rrange < $numOfPages): ?>
                    <li class="page-item"><span class="page-link">...</span></li>
                <?php endif; ?>

                <?php if(($data['pagination']['totalRows'] - ($data['pagination']['limit'] * $data['pagination']['page'])) > 0): ?>
                    <?php $pagenext = $data['pagination']['page'] + 1; ?>
                    <li class="page-item"><a class="page-link" href="#" data-page="<?= $pagenext ?>"><?= lang('backend/global.pagination.next'); ?></a></li>
                    <li class="page-item"><a class="page-link" href="#" data-page="<?= $numOfPages ?>"><?= lang('backend/global.pagination.last'); ?></a></li>
                <?php endif; ?>

            <?php else: ?>
                <?php if($data['pagination']['totalRows'] == 0): ?>
                    <li class="page-item disabled"><span class="page-link">1</span></li>
                <?php else: ?>
                    <li class="page-item"><span class="page-link">1</span></li>
                <?php endif; ?>
            <?php endif; ?>

        </ul>
    </div>
    <div class="col-md-4">
        <?php $resultStart = ($data['pagination']['page'] - 1) * $data['pagination']['limit'] + 1;
        if ($resultStart == 0) $resultStart = 1;
        $resultEnd = $resultStart + $data['pagination']['limit'] - 1;
        if ($resultEnd < $data['pagination']['limit']):
            $resultEnd = $data['pagination']['limit'];
        elseif ($resultEnd > $data['pagination']['totalRows']):
            $resultEnd = $data['pagination']['totalRows'];
        endif;
        if ($resultEnd > $data['pagination']['totalRows']):
            $resultEnd = $data['pagination']['totalRows'];
        endif;
        $right = ($data['pagination']['totalRows'] == 0) ? '' : sprintf(lang('backend/global.pagination.messageRight'), $resultStart, $resultEnd, $data['pagination']['totalRows']); ?>
        <div class="pagination-right"><?= $right ?></div>
    </div>
</div>
