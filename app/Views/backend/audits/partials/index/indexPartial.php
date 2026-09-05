<div class="card-body py-0">
    <div class="row">
        <div class="col-md-12">

            <!-- Se esiste l'array e contiene almeno un record... -->
            <?php if(isset($data['records']) && count($data['records'])): ?>

                <!-- Paginazione superiore -->
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

                                        <!-- Colonna username -->
                                        <th style="width: 15%;">
                                            <a class="sort" href="#" data-column="username" data-order="<?= (($posts['order'] == 'desc' && $posts['column'] == 'username') ? 'asc' : 'desc'); ?>">
                                                <?= lang('backend/audits.labels.username'); ?> <?= (($posts['column'] == 'username') ? '&nbsp;' . $icon : ''); ?>
                                            </a>
                                        </th>

                                        <!-- Colonna action -->
                                        <th style="width: 15%;">
                                            <a class="sort" href="#" data-column="action" data-order="<?= (($posts['order'] == 'desc' && $posts['column'] == 'action') ? 'asc' : 'desc'); ?>">
                                                <?= lang('backend/audits.labels.action'); ?>&nbsp;<?= (($posts['column'] == 'action') ? '&nbsp;' . $icon : ''); ?>
                                            </a>
                                        </th>

                                        <!-- Colonna section -->
                                        <th style="width: 12.5%;">
                                            <a class="sort" href="#" data-column="section" data-order="<?= (($posts['order'] == 'desc' && $posts['column'] == 'section') ? 'asc' : 'desc'); ?>">
                                                <?= lang('backend/audits.labels.section'); ?>&nbsp;<?= (($posts['column'] == 'section') ? '&nbsp;' . $icon : ''); ?>
                                            </a>
                                        </th>

                                        <!-- Colonna details -->
                                        <th style="width: 45%;">
                                            <a class="sort" href="#" data-column="details" data-order="<?= (($posts['order'] == 'desc' && $posts['column'] == 'details') ? 'asc' : 'desc'); ?>">
                                                <?= lang('backend/audits.labels.details'); ?>&nbsp;<?= (($posts['column'] == 'details') ? '&nbsp;' . $icon : ''); ?>
                                            </a>
                                        </th>

                                        <!-- Colonna created At -->
                                        <th style="width: 10%;">
                                            <a class="sort" href="#" data-column="created_at" data-order="<?= (($posts['order'] == 'desc' && $posts['column'] == 'created_at') ? 'asc' : 'desc'); ?>">
                                                <?= lang('backend/audits.labels.createdAt'); ?>&nbsp;<?= (($posts['column'] == 'created_at') ? '&nbsp;' . $icon : ''); ?>
                                            </a>
                                        </th>

                                    </tr>
                                </thead>
                                <tbody id="auditsBody">

                                    <!-- Ciclo i dati -->
                                    <?php foreach($data['records'] as $audit): ?>

                                        <tr class="border-end border-start table-row-60">
                                            
                                            <!-- Cella chevron -->
                                            <td class="align-middle fw-bold">
                                                <button class="toggle-meta-btn btn btn-sm btn-link p-0 me-1 text-secondary shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#meta-<?= esc($audit->id); ?>" aria-expanded="false" title="Mostra dettagli di sistema">
                                                    <i class="fa-solid fa-chevron-right"></i>
                                                </button>
                                            </td>

                                            <!-- Cella username -->
                                            <td class="align-middle">
                                                <span class="fw-bold"><?= esc($audit->username); ?></span>
                                            </td>

                                            <!-- Cella action -->
                                            <td class="align-middle">
                                                <span class="fw-bold"><?= esc($audit->action); ?></span>
                                            </td>

                                            <!-- Cella section -->
                                            <td class="align-middle">
                                                <span class="fw-bold"><?= esc($audit->section); ?></span>
                                            </td>

                                            <!-- Cella details -->
                                            <td class="align-middle">
                                                <span class="fw-bold"><?= esc($audit->details); ?></span>
                                            </td>

                                            <!-- Cella created At -->
                                            <td class="align-middle">
                                                <span class="fw-bold"><?= convertDate(esc($audit->created_at)); ?></span>
                                            </td>
                                        </tr>

                                        <!-- Riga inferiore (Metadati a comparsa fluida) -->
                                        <tr class="border-end border-start">

                                            <!-- Colspan 6 copre l'intera tabella. Nessun padding sul td per evitare scatti nell'animazione -->
                                            <td colspan="6" class="p-0 border-0">
                                                
                                                <!-- Contenitore collassabile puntato dal bottone -->
                                                <div class="collapse" id="meta-<?= esc($audit->id); ?>">
                                                    
                                                    <!-- Box effettivo dei metadati: spazioso, con background neutro -->
                                                    <div class="p-2 border-bottom bg-light text-secondary small">
                                                        
                                                        <!-- Parte useragent -->
                                                        <?php 
                                                            /* Parsing della stringa User Agent tramite l'istanza passata dal Controller */
                                                            $userAgent->parse(esc($audit->user_agent)); 

                                                            /* Inizializzazione corretta della variabile in camelCase */
                                                            $agentText = lang('backend/audits.labels.operatingSystem') . ' <span class="text-primary fw-bold">' . $userAgent->getPlatform() . '</span>';
                                                        ?>

                                                        <?php
                                                            /* Valutazione con metodi nativi in camelCase di CodeIgniter 4 */
                                                            if($userAgent->isBrowser()):
                                                                $agentText .= ' &bull; ' . lang('backend/audits.labels.browser') . ' <span class="text-primary fw-bold">' . $userAgent->getBrowser() . '</span>';
                                                            elseif($userAgent->isMobile()):
                                                                $agentText .= ' &bull; ' . lang('backend/audits.labels.mobile') . ' <span class="text-primary fw-bold">' . $userAgent->getMobile() . '</span>';
                                                            elseif($userAgent->isRobot()):
                                                                $agentText .= ' &bull; ' . lang('backend/audits.labels.robot') . ' Robot <span class="text-primary fw-bold">' . $userAgent->getRobot() . '</span>';
                                                            endif;
                                                        ?>

                                                        <?= $agentText; ?>
                                                        &nbsp;&bull;&nbsp;
                                                        <span><?= lang('backend/audits.labels.ipAddress'); ?></span>
                                                        <span class="text-primary fw-bold"><?= esc($audit->ip_address); ?></span>
                                                        <!-- End parte useragent -->

                                                    </div>
                                                </div>

                                            </td>
                                        </tr>
                                        <!-- Fine Riga inferiore -->

                                    <?php endforeach; ?>

                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Paginazione inferiore -->
                <?= $this->include('backend/template/paginationView'); ?>

            <!-- ...altrimenti visualizzo messaggio adeguato. -->
            <?php else: ?>
                <div class="text-center py-3 fw-bold"><?= lang('backend/audits.messages.noAuditsFound'); ?></div>
            <?php endif; ?>
        </div>
    </div>
</div>
