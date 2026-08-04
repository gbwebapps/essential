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
                            <?php $icon = ($posts['order'] == 'desc') ? '<i class="fa-solid fa-arrow-circle-down"></i>' : '<i class="fa-solid fa-arrow-circle-up"></i>'; ?>

                            <!-- Numero dei record visualizzati in una pagina. Serve al Trick per evitare che all'eliminazione
                            dell'ultimo record in una pagina che non è la prima, la visualizzazione rimanga bloccata e non passi alla pagina successiva. -->
                            <div id="lastItemPage" data-lastitempage="<?= $data['lastItemPage']; ?>"></div>

                            <table class="table table-condensed mb-0">
                                <thead>
                                    <tr class="sorting">

                                        <!-- Colonna email -->
                                        <th style="width: 25%;">
                                            <a class="sort" href="#" data-column="email" data-order="<?= (($posts['order'] == 'desc' && $posts['column'] == 'email') ? 'asc' : 'desc'); ?>">
                                                <?= lang('backend/tokens.labels.username'); ?> <?= (($posts['column'] == 'email') ? '&nbsp;' . $icon : ''); ?>
                                            </a>
                                        </th>

                                        <!-- Colonna data inizio -->
                                        <th style="width: 25%;">
                                            <a class="sort" href="#" data-column="token_create" data-order="<?= (($posts['order'] == 'desc' && $posts['column'] == 'token_create') ? 'asc' : 'desc'); ?>">
                                                <?= lang('backend/tokens.labels.tokenCreate'); ?>&nbsp;<?= (($posts['column'] == 'token_create') ? '&nbsp;' . $icon : ''); ?>
                                            </a>
                                        </th>

                                        <!-- Colonna data fine -->
                                        <th style="width: 25%;">
                                            <a class="sort" href="#" data-column="token_expire" data-order="<?= (($posts['order'] == 'desc' && $posts['column'] == 'token_expire') ? 'asc' : 'desc'); ?>">
                                                <?= lang('backend/tokens.labels.tokenExpire'); ?>&nbsp;<?= (($posts['column'] == 'token_expire') ? '&nbsp;' . $icon : ''); ?>
                                            </a>
                                        </th>

                                        <!-- Colonna token_type -->
                                        <th style="width: 12.5%;">
                                            <a class="sort" href="#" data-column="token_type" data-order="<?= (($posts['order'] == 'desc' && $posts['column'] == 'token_type') ? 'asc' : 'desc'); ?>">
                                                <?= lang('backend/tokens.labels.tokenType'); ?>&nbsp;<?= (($posts['column'] == 'token_type') ? '&nbsp;' . $icon : ''); ?>
                                            </a>
                                        </th>

                                        <!-- Colonna created_at -->
                                        <th style="width: 12.5%;">
                                            <a class="sort" href="#" data-column="created_at" data-order="<?= (($posts['order'] == 'desc' && $posts['column'] == 'created_at') ? 'asc' : 'desc'); ?>">
                                                <?= lang('backend/tokens.labels.createdAt'); ?>&nbsp;<?= (($posts['column'] == 'created_at') ? '&nbsp;' . $icon : ''); ?>
                                            </a>
                                        </th>

                                        <!-- Colonna azioni -->
                                        <th style="width: 5%;">&nbsp;</th>

                                    </tr>
                                </thead>
                                <tbody id="tokensBody">

                                    <!-- Ciclo i dati -->
                                    <?php foreach($data['records'] as $token): ?>

                                        <?php
                                            /* Creiamo l'oggetto DateTime partendo dalla data stringa del database */
                                            $dateExpire = new \DateTime($token->token_expire);

                                            /* Creiamo l'oggetto DateTime con l'ora attuale per fare il confronto */
                                            $now = new \DateTime();

                                            /* Applichiamo le classi in base al confronto (se adesso è maggiore della scadenza, è scaduto) */
                                            $class = ($now > $dateExpire) ? 'text-danger fw-bold text-decoration-line-through' : 'text-success fw-bold';

                                            /* Mappatura delle traduzioni per i tipi di token */
                                            $typeMap = [
                                                'session'    => lang('backend/admins.labels.session'),
                                                'activation' => lang('backend/admins.labels.activation'),
                                                'cookie'     => lang('backend/admins.labels.rememberMe')
                                            ];

                                            /* Assegnazione con fallback automatico se la chiave non esiste */
                                            $tokenType = $typeMap[$token->token_type] ?? lang('backend/admins.labels.unknown');
                                        ?>

                                        <tr>
                                            <!-- Cella email -->
                                            <td class="align-middle">
                                                <span class="fw-bold"><?= esc($token->email); ?></span>
                                            </td>

                                            <!-- Cella token_create -->
                                            <td class="align-middle">
                                                <span class="fw-bold"><?= convertDate(esc($token->token_create)); ?></span>
                                            </td>

                                            <!-- Cella token_expire -->
                                            <td class="align-middle">
                                                <span class="<?= $class; ?>"><?= convertDate($dateExpire->format('Y-m-d H:i:s')); ?></span>
                                            </td>

                                            <!-- Cella token_type -->
                                            <td class="align-middle">
                                                <span class="fw-bold"><?= esc($tokenType); ?></span>
                                            </td>

                                            <!-- Cella created_at -->
                                            <td class="align-middle">
                                                <span class="fw-bold"><?= convertDate(esc($token->created_at)); ?></span>
                                            </td>

                                            <!-- Cella actions -->
                                            <!-- Cella Dropdown Menu -->
                                            <td class="align-middle text-end">
                                                <!-- Pulsante Azioni -->
                                                <button type="button" class="btn btn-secondary btn-sm dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                                    <?= lang('backend/tokens.buttons.actions'); ?>
                                                </button>

                                                <!-- Corpo Dropdown -->
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <!-- Pulsante Elimina Definitivamente (Hard Delete) -->
                                                    <li>
                                                        <form class="hardDeleteRecord" data-message="<?= sprintf(lang('backend/tokens.messages.areYouSureHardDelete'), esc($token->firstname), esc($token->lastname)); ?>">
                                                            <input type="hidden" name="uuid" value="<?= esc($token->uuid); ?>">
                                                            <input type="hidden" name="id" value="<?= esc($token->id); ?>">
                                                            <button type="submit" class="dropdown-item btn-link text-danger">
                                                                <i class="fa-solid fa-trash"></i> <?= lang('backend/tokens.actions.hardDelete'); ?>
                                                            </button>
                                                        </form>
                                                    </li>
                                                </ul>
                                            </td>
                                        </tr>

                                        <!-- Riga inferiore -->
                                        <tr>
                                            <td colspan="6" class="align-middle small">

                                                <!-- Parte useragent -->
                                                <?php 
                                                    /* Parsing della stringa User Agent tramite l'istanza passata dal Controller */
                                                    $userAgent->parse(esc($token->user_agent)); 

                                                    /* Inizializzazione corretta della variabile in camelCase */
                                                    $agentText = lang('backend/tokens.labels.operatingSystem') . ' <span class="text-primary fw-bold">' . $userAgent->getPlatform() . '</span>';
                                                ?>

                                                <?php
                                                    /* Valutazione con metodi nativi in camelCase di CodeIgniter 4 */
                                                    if($userAgent->isBrowser()):
                                                        $agentText .= ' &bull; ' . lang('backend/tokens.labels.browser') . ' <span class="text-primary fw-bold">' . $userAgent->getBrowser() . '</span>';
                                                    elseif($userAgent->isMobile()):
                                                        $agentText .= ' &bull; ' . lang('backend/tokens.labels.mobile') . ' <span class="text-primary fw-bold">' . $userAgent->getMobile() . '</span>';
                                                    elseif($userAgent->isRobot()):
                                                        $agentText .= ' &bull; ' . lang('backend/tokens.labels.robot') . ' Robot <span class="text-primary fw-bold">' . $userAgent->getRobot() . '</span>';
                                                    endif;
                                                ?>

                                                <?= $agentText; ?>
                                                &nbsp;&bull;&nbsp;
                                                <span><?= lang('backend/tokens.labels.ipAddress'); ?></span>
                                                <span class="text-primary fw-bold"><?= esc($token->ip_address); ?></span>
                                                <!-- End parte useragent -->

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
                <div class="text-center text-danger py-3 fw-bold"><?= lang('backend/tokens.messages.noTokensFound'); ?></div>
            <?php endif; ?>
        </div>
    </div>
</div>
