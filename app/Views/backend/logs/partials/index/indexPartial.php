<?php use \CodeIgniter\I18n\Time; ?>

<div class="card-body py-0">
    <div class="row">
        <div class="col-md-12">

            <?php if(isset($data['records']) && count($data['records'])): ?>

                <?= $this->include('backend/template/paginationView'); ?>

                <div class="row">
                    <div class="col-md-12">
                        <div class="table-responsive border-top">

                            <!-- Icona da visualizzare a fianco al nome colonna, se ascendente o discendente -->
                            <?php $icon = ($posts['order'] == 'desc') ? '<i class="fa-solid fa-arrow-down"></i>' : '<i class="fa-solid fa-arrow-up"></i>'; ?>

                            <!-- Numero dei record visualizzati in una pagina. Serve al Trick per evitare che all'eliminazione
                            dell'ultimo record in una pagina che non è la prima, la visualizzazione rimanga bloccata e non passi alla pagina successiva. -->
                            <div id="lastItemPage" data-lastitempage="<?= $data['lastItemPage']; ?>"></div>

                            <table class="table table-condensed mb-0 text-nowrap">
                                <thead>
                                    <tr class="sorting">
                                        <th style="width: 2.5%;" class="text-center">&nbsp;</th>
                                        <th style="width: 20%;">
                                            <a class="sort" href="#" data-column="username" data-order="<?= (($posts['order'] == 'desc' && $posts['column'] == 'username') ? 'asc' : 'desc'); ?>">
                                                <?= lang('backend/logs.labels.username'); ?> <?= (($posts['column'] == 'username') ? '&nbsp;' . $icon : ''); ?>
                                            </a>
                                        </th>
                                        <th style="width: 20%;">
                                            <a class="sort" href="#" data-column="login" data-order="<?= (($posts['order'] == 'desc' && $posts['column'] == 'login') ? 'asc' : 'desc'); ?>">
                                                <?= lang('backend/logs.labels.login'); ?>&nbsp;<?= (($posts['column'] == 'login') ? '&nbsp;' . $icon : ''); ?>
                                            </a>
                                        </th>
                                        <th style="width: 20%;">
                                            <a class="sort" href="#" data-column="logout" data-order="<?= (($posts['order'] == 'desc' && $posts['column'] == 'logout') ? 'asc' : 'desc'); ?>">
                                                <?= lang('backend/logs.labels.logout'); ?>&nbsp;<?= (($posts['column'] == 'logout') ? '&nbsp;' . $icon : ''); ?>
                                            </a>
                                        </th>
                                        <th style="width: 15%;">
                                            <?= lang('backend/logs.labels.duration'); ?>
                                        </th>
                                        <th style="width: 17.5%;">
                                            <a class="sort" href="#" data-column="logout_reason" data-order="<?= (($posts['order'] == 'desc' && $posts['column'] == 'logout_reason') ? 'asc' : 'desc'); ?>">
                                                <?= lang('backend/logs.labels.logoutReason'); ?>&nbsp;<?= (($posts['column'] == 'logout_reason') ? '&nbsp;' . $icon : ''); ?>
                                            </a>
                                        </th>
                                        <th style="width: 5%;" class="text-center">&nbsp;</th>
                                    </tr>
                                </thead>
                                <tbody id="logsBody">

                                    <?php foreach($data['records'] as $log): ?>

                                        <?php
                                            $now = Time::now();

                                            $typeMap = [
                                                'manual' => lang('backend/logs.labels.manual'),
                                                'timeout' => lang('backend/logs.labels.timeout'),
                                                'banned' => lang('backend/logs.labels.banned'), 
                                                'deleted' => lang('backend/logs.labels.deleted'), 
                                            ];

                                            $isSuperadmin = (int) $log->superadmin === 1;

                                            /* 1. Sessione chiusa */
                                            if ( ! is_null($log->logout)):
                                                
                                                $logoutHtml = '<span class="fw-bold">' . convertDate(esc($log->logout), 'conversational') . '</span>';
                                                $reasonHtml = '<span class="fw-bold">' . ($typeMap[$log->logout_reason] ?? lang('backend/logs.labels.undefined')) . '</span>';
                                                
                                                $start = new \DateTime($log->login);
                                                $end   = new \DateTime($log->logout);
                                                $diff  = $start->diff($end);
                                                
                                                $durationStr = [];
                                                if ($diff->d > 0) $durationStr[] = $diff->d . 'd';
                                                if ($diff->h > 0) $durationStr[] = $diff->h . 'h';
                                                if ($diff->i > 0) $durationStr[] = $diff->i . 'm';
                                                $durationStr[] = $diff->s . 's'; 
                                                
                                                $durationHtml = '<span class="fw-bold">' . implode(' ', $durationStr) . '</span>';

                                            /* 2. Sessione attiva o indefinita */
                                            else:

                                                if ( ! empty($log->token_expire) && $now->isBefore($log->token_expire)):
                                                    
                                                    $logoutHtml   = '<span class="text-success fw-bold">' . lang('backend/logs.labels.pending') . '</span>';
                                                    $reasonHtml   = '<span class="text-success fw-bold">' . lang('backend/logs.labels.pending') . '</span>';
                                                    
                                                    $start = new \DateTime($log->login);
                                                    $end   = new \DateTime( ! empty($log->last_activity) ? $log->last_activity : $now->format('Y-m-d H:i:s'));
                                                    $diff  = $start->diff($end);
                                                    
                                                    $durationStr = [];
                                                    if ($diff->d > 0) $durationStr[] = $diff->d . 'd';
                                                    if ($diff->h > 0) $durationStr[] = $diff->h . 'h';
                                                    if ($diff->i > 0) $durationStr[] = $diff->i . 'm';
                                                    $durationStr[] = $diff->s . 's'; 
                                                    
                                                    $durationHtml = '<span class="text-success fw-bold">' . implode(' ', $durationStr) . '</span>';
                                                    
                                                else:
                                                    
                                                    $logoutHtml   = '<span class="text-danger fw-bold text-decoration-line-through">' . lang('backend/logs.labels.undefined') . '</span>';
                                                    $durationHtml = '<span class="text-danger fw-bold text-decoration-line-through">' . lang('backend/logs.labels.undefined') . '</span>';
                                                    $reasonHtml   = '<span class="text-danger fw-bold text-decoration-line-through">' . lang('backend/logs.labels.undefined') . '</span>';
                                                    
                                                endif;

                                            endif;
                                        ?>

                                        <tr class="border-end border-start table-row-60<?= $isSuperadmin ? ' table-bg-superadmin' : ''; ?>">
                                            <td class="align-middle fw-bold">
                                                <button class="toggle-meta-btn btn btn-sm btn-link p-0 me-1 text-secondary shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#meta-<?= esc($log->id); ?>" aria-expanded="false" title="Mostra dettagli di sistema">
                                                    <i class="fa-solid fa-chevron-right"></i>
                                                </button>
                                            </td>
                                            <td class="align-middle">
                                                <span class="fw-bold"><?= esc($log->username); ?></span>
                                            </td>
                                            <td class="align-middle">
                                                <span class="fw-bold"><?= convertDate(esc($log->login), 'conversational'); ?></span>
                                            </td>
                                            <td class="align-middle">
                                                <?= $logoutHtml; ?>
                                            </td>
                                            <td class="align-middle">
                                                <?= $durationHtml; ?>
                                            </td>
                                            <td class="align-middle">
                                                <?= $reasonHtml; ?>
                                            </td>

                                            <!-- Cella actions -->
                                            <td class="align-middle text-center text-md-end">

                                                <?php if( ! $isSuperadmin): ?>

                                                    <?php if (is_null($log->logout) && ! empty($log->token_id)): ?>

                                                        <div class="d-flex flex-column gap-1">

                                                            <!-- Pulsante Elimina Definitivamente (Hard Delete) -->
                                                            <form class="hardDeleteRecord m-0 p-0" data-message="<?= sprintf(lang('backend/logs.messages.areYouSureHardDelete'), esc($log->firstname), esc($log->lastname)); ?>">
                                                                <input type="hidden" name="tokenId" value="<?= esc($log->token_id_val); ?>">
                                                                <button type="submit" class="btn btn-link p-0 m-0 text-danger text-decoration-none action shadow-none">
                                                                    <i class="fa-solid fa-xmark fa-fw"></i> <?= lang('backend/logs.actions.hardDelete'); ?>
                                                                </button>
                                                            </form>

                                                        </div>

                                                    <?php endif; ?>

                                                <?php endif; ?>

                                            </td>

                                        </tr>

                                        <tr class="border-end border-start">
                                            <td colspan="7" class="p-0 border-0">
                                                <div class="collapse" id="meta-<?= esc($log->id); ?>">
                                                    <div class="p-2 border-bottom bg-light text-secondary small">
                                                        <?php 
                                                            $userAgent->parse(esc($log->user_agent)); 

                                                            $agentText = lang('backend/logs.labels.createdAt') . ' <span class="text-primary fw-bold">' . convertDate(esc($log->created_at), 'conversational') . '</span>';
                                                            $agentText .= ' &bull; ' . lang('backend/logs.labels.typeToken') . ' <span class="text-primary fw-bold">' . esc($log->token_type) . '</span>';
                                                            $agentText .= ' &bull; ' . lang('backend/logs.labels.operatingSystem') . ' <span class="text-primary fw-bold">' . $userAgent->getPlatform() . '</span>';
                                                        ?>
                                                        <?php
                                                            if($userAgent->isBrowser()):
                                                                $agentText .= ' &bull; ' . lang('backend/logs.labels.browser') . ' <span class="text-primary fw-bold">' . $userAgent->getBrowser() . '</span>';
                                                            elseif($userAgent->isMobile()):
                                                                $agentText .= ' &bull; ' . lang('backend/logs.labels.mobile') . ' <span class="text-primary fw-bold">' . $userAgent->getMobile() . '</span>';
                                                            elseif($userAgent->isRobot()):
                                                                $agentText .= ' &bull; ' . lang('backend/logs.labels.robot') . ' Robot <span class="text-primary fw-bold">' . $userAgent->getRobot() . '</span>';
                                                            endif;
                                                        ?>
                                                        <?= $agentText; ?>
                                                        &nbsp;&bull;&nbsp;
                                                        <span><?= lang('backend/logs.labels.ipAddress'); ?></span>
                                                        <span class="text-primary fw-bold"><?= esc($log->ip_address); ?></span>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>

                                    <?php endforeach; ?>

                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <?= $this->include('backend/template/paginationView'); ?>

            <?php else: ?>
                <div class="text-center py-3 fw-bold"><?= lang('backend/logs.messages.noLogsFound'); ?></div>
            <?php endif; ?>
        </div>
    </div>
</div>