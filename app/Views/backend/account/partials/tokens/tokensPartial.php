<div class="card-body">
    <?php if(count($tokens)): ?>
        <ul class="list-group">
            <?php foreach($tokens as $token): ?>

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

                    /* Parsing della stringa User Agent tramite l'istanza passata dal Controller */
                    $userAgent->parse(esc($token->user_agent)); 
                    
                    /* Inizializzazione corretta della variabile in camelCase */
                    $agentText = lang('backend/account.labels.operatingSystem') . ' <span class="text-primary">' . $userAgent->getPlatform() . '</span>';
                    
                    /* Valutazione con metodi nativi in camelCase di CodeIgniter 4 */
                    if($userAgent->isBrowser()):
                        $agentText .= ' &bull; ' . lang('backend/account.labels.browser') . ' <span class="text-primary">' . $userAgent->getBrowser() . '</span>';
                    elseif($userAgent->isMobile()):
                        $agentText .= ' &bull; ' . lang('backend/account.labels.mobile') . ' <span class="text-primary">' . $userAgent->getMobile() . '</span>';
                    elseif($userAgent->isRobot()):
                        $agentText .= ' &bull; ' . lang('backend/account.labels.robot') . ' Robot <span class="text-primary">' . $userAgent->getRobot() . '</span>';
                    endif;
                ?>

                <li class="list-group-item py-3">
                    <div class="row align-items-center">
                        
                        <!-- Dati Token (Creazione, Scadenza, Tipo) e User Agent -->
                        <div class="col-12 col-lg">
                            
                            <div class="row">
                                <div class="col-12 col-md-4 mb-3 mb-md-0 text-start">
                                    <small class="text-muted fw-bold d-block mb-1"><i class="fa-solid fa-circle-arrow-down me-1"></i><?= lang('backend/account.labels.createdToken'); ?></small>
                                    <span class="fw-bold"><?= convertDate(esc($token->token_create)); ?></span>
                                </div>
                                <div class="col-12 col-md-4 mb-3 mb-md-0 text-start">
                                    <small class="text-muted fw-bold d-block mb-1"><i class="fa-solid fa-circle-arrow-down me-1"></i><?= lang('backend/account.labels.expiredToken'); ?></small>
                                    <span class="<?= $class; ?>"><?= convertDate($dateExpire->format('Y-m-d H:i:s')); ?></span>
                                </div>
                                <div class="col-12 col-md-4 mb-3 mb-md-0 text-start">
                                    <small class="text-muted fw-bold d-block mb-1"><i class="fa-solid fa-circle-arrow-down me-1"></i><?= lang('backend/account.labels.typeToken'); ?></small>
                                    <span class="fw-bold"><?= esc($tokenType); ?></span>
                                </div>
                            </div>
                            
                            <!-- User Agent e IP -->
                            <div class="row mt-lg-3">
                                <div class="col-12 text-start">
                                    <hr class="d-md-none my-2 text-secondary">
                                    <small class="fw-bold">
                                        <?= $agentText; ?>
                                        &bull; 
                                        <span><?= lang('backend/account.labels.ipAddress'); ?></span>
                                        <span class="text-primary"><?= esc($token->ip_address); ?></span>
                                    </small>
                                </div>
                            </div>

                        </div>
                        
                        <!-- Azione (Elimina) -->
                        <div class="col-12 col-lg-auto mt-3 mt-lg-0 text-start text-lg-end">
                            <form class="deleteToken d-grid d-lg-block" data-message="<?= sprintf(lang('backend/account.messages.areYouSureDeleteToken'), esc($currentAdmin->firstname), esc($currentAdmin->lastname)); ?>">
                                <input type="hidden" name="id" value="<?= esc($token->id); ?>">
                                <button type="submit" class="btn btn-danger btn-sm shadow-none">
                                    <i class="fa-solid fa-trash me-1"></i> <?= lang('backend/account.buttons.delete'); ?>
                                </button>
                            </form>
                        </div>
                        
                    </div>
                </li>

            <?php endforeach; ?>
        </ul>
    <?php else: ?>
        <div class="text-center py-3 fw-bold">
            <?= lang('backend/account.messages.noTokensFound'); ?>
        </div>
    <?php endif; ?>
</div>