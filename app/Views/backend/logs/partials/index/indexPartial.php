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

                                        <!-- Colonna email -->
                                        <th style="width: 20%;">
                                            <a class="sort" href="#" data-column="email" data-order="<?= (($posts['order'] == 'desc' && $posts['column'] == 'email') ? 'asc' : 'desc'); ?>">
                                                <?= lang('backend/logs.labels.username'); ?> <?= (($posts['column'] == 'email') ? '&nbsp;' . $icon : ''); ?>
                                            </a>
                                        </th>

                                        <!-- Colonna data inizio -->
                                        <th style="width: 22.5%;">
                                            <a class="sort" href="#" data-column="login" data-order="<?= (($posts['order'] == 'desc' && $posts['column'] == 'login') ? 'asc' : 'desc'); ?>">
                                                <?= lang('backend/logs.labels.login'); ?>&nbsp;<?= (($posts['column'] == 'login') ? '&nbsp;' . $icon : ''); ?>
                                            </a>
                                        </th>

                                        <!-- Colonna data fine -->
                                        <th style="width: 22.5%;">
                                            <a class="sort" href="#" data-column="logout" data-order="<?= (($posts['order'] == 'desc' && $posts['column'] == 'logout') ? 'asc' : 'desc'); ?>">
                                                <?= lang('backend/logs.labels.logout'); ?>&nbsp;<?= (($posts['column'] == 'logout') ? '&nbsp;' . $icon : ''); ?>
                                            </a>
                                        </th>

                                        <!-- Colonna logout_reason -->
                                        <th style="width: 15%;">
                                            <a class="sort" href="#" data-column="logout_reason" data-order="<?= (($posts['order'] == 'desc' && $posts['column'] == 'logout_reason') ? 'asc' : 'desc'); ?>">
                                                <?= lang('backend/logs.labels.logoutReason'); ?>&nbsp;<?= (($posts['column'] == 'logout_reason') ? '&nbsp;' . $icon : ''); ?>
                                            </a>
                                        </th>

                                        <!-- Colonna created_at -->
                                        <th style="width: 17.5%;">
                                            <a class="sort" href="#" data-column="created_at" data-order="<?= (($posts['order'] == 'desc' && $posts['column'] == 'created_at') ? 'asc' : 'desc'); ?>">
                                                <?= lang('backend/logs.labels.createdAt'); ?>&nbsp;<?= (($posts['column'] == 'created_at') ? '&nbsp;' . $icon : ''); ?>
                                            </a>
                                        </th>

                                    </tr>
                                </thead>
                                <tbody id="logsBody">

                                    <!-- Ciclo i dati -->
                                    <?php foreach($data['records'] as $log): ?>

                                        <?php
                                            /* Creiamo l'oggetto DateTime partendo dalla data stringa del database */
                                            // $logout = new \DateTime($log->logout);

                                            /* Creiamo l'oggetto DateTime con l'ora attuale per fare il confronto */
                                            // $now = new \DateTime();

                                            /* Applichiamo le classi in base al confronto (se adesso è maggiore della scadenza, è scaduto) */
                                            // $class = ($now > $logout) ? 'text-danger fw-bold text-decoration-line-through' : 'text-success fw-bold';

                                            /* Mappatura delle traduzioni per i tipi di log */
                                            // $typeMap = [
                                                // 'manual' => lang('backend/logs.labels.manual'),
                                                // 'timeout' => lang('backend/logs.labels.timeout'),
                                            // ];

                                            /* Assegnazione con fallback automatico se la chiave non esiste */
                                            $logoutReason = $typeMap[$log->logout_reason] ?? lang('backend/logs.labels.unknown');
                                        ?>

                                        <tr class="border-end border-start table-row-60">
                                            <!-- Cella chevron -->
                                            <td class="align-middle fw-bold">
                                                <button class="toggle-meta-btn btn btn-sm btn-link p-0 me-1 text-secondary shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#meta-<?= esc($log->id); ?>" aria-expanded="false" title="Mostra dettagli di sistema">
                                                    <i class="fa-solid fa-chevron-right"></i>
                                                </button>
                                            </td>

                                            <!-- Cella email -->
                                            <td class="align-middle">
                                                <span class="fw-bold"><?= esc($log->email); ?></span>
                                            </td>

                                            <!-- Cella login -->
                                            <td class="align-middle">
                                                <span class="fw-bold"><?= convertDate(esc($log->login), 'conversational'); ?></span>
                                            </td>

                                            <!-- Cella logout -->
                                            <td class="align-middle">
                                                <span class="fw-bold"><?= convertDate(esc($log->logout), 'conversational'); ?></span>
                                            </td>

                                            <!-- Cella logout_reason -->
                                            <td class="align-middle">
                                                <span class="fw-bold"><?= esc($logoutReason); ?></span>
                                            </td>

                                            <!-- Cella created_at -->
                                            <td class="align-middle">
                                                <span class="fw-bold"><?= convertDate(esc($log->created_at), 'conversational'); ?></span>
                                            </td>
                                        </tr>

                                        <!-- Riga inferiore (Metadati a comparsa fluida) -->
                                        <tr class="border-end border-start">

                                            <!-- Colspan 6 copre l'intera tabella. Nessun padding sul td per evitare scatti nell'animazione -->
                                            <td colspan="6" class="p-0 border-0">
                                                
                                                <!-- Contenitore collassabile puntato dal bottone -->
                                                <div class="collapse" id="meta-<?= esc($log->id); ?>">
                                                    
                                                    <!-- Box effettivo dei metadati: spazioso, con background neutro -->
                                                    <div class="p-2 border-bottom bg-light text-secondary small">
                                                        
                                                        <!-- Parte useragent -->
                                                        <?php 
                                                            /* Parsing della stringa User Agent tramite l'istanza passata dal Controller */
                                                            $userAgent->parse(esc($log->user_agent)); 

                                                            /* Inizializzazione corretta della variabile in camelCase */
                                                            $agentText = lang('backend/logs.labels.operatingSystem') . ' <span class="text-primary fw-bold">' . $userAgent->getPlatform() . '</span>';
                                                        ?>

                                                        <?php
                                                            /* Valutazione con metodi nativi in camelCase di CodeIgniter 4 */
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
                <div class="text-center py-3 fw-bold"><?= lang('backend/logs.messages.noLogsFound'); ?></div>
            <?php endif; ?>
        </div>
    </div>
</div>
