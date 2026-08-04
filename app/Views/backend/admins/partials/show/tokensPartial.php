<div class="card-body">
    <?php if(count($tokens)): ?>
        <table class="table table-bordered align-middle mb-0">
            <thead>
                <tr>
                    <th class="text-left" style="width: 37.5%;">
                        <i class="fa-solid fa-circle-arrow-down"></i><?= lang('backend/admins.labels.createdToken'); ?>
                    </th>
                    <th class="text-left" style="width: 37.5%;">
                        <i class="fa-solid fa-circle-arrow-down"></i><?= lang('backend/admins.labels.expiredToken'); ?>
                    </th>
                    <th class="text-left" style="width: 20%;">
                        <i class="fa-solid fa-circle-arrow-down"></i><?= lang('backend/admins.labels.typeToken'); ?>
                    </th>
                    <th class="text-center" style="width: 5%;">&nbsp;</th>
                </tr>
            </thead>
            <tbody>
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
                ?>

                <tr>
                    <td class="text-left fw-bold"><?= convertDate(esc($token->token_create)); ?></td>
                    <td class="text-left <?= $class; ?>"><?= convertDate($dateExpire->format('Y-m-d H:i:s')); ?></td>
                    <td class="text-left fw-bold"><?= esc($tokenType); ?></td>

                    <td class="text-center" rowspan="2">
                        <form class="deleteToken" data-message="<?= sprintf(lang('backend/admins.messages.areYouSureDeleteToken'), esc($admin->firstname), esc($admin->lastname)); ?>">
                            <input type="hidden" name="id" value="<?= esc($token->id); ?>">
                            <?= (isset($admin)) ? '<input type="hidden" name="uuid" value="' . esc($admin->uuid) . '">' : ''; ?>
                            <button type="submit" class="btn btn-link text-danger fw-bold shadow-none">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </form>
                    </td>
                </tr>

                <?php 
                    /* Parsing della stringa User Agent tramite l'istanza passata dal Controller */
                    $userAgent->parse(esc($token->user_agent)); 
                ?>
                <tr>
                    <td colspan="3">
                        <small class="fw-bold">

                            <?php
                                /* Inizializzazione corretta della variabile in camelCase */
                                $agentText = lang('backend/admins.labels.operatingSystem') . ' <span class="text-primary">' . $userAgent->getPlatform() . '</span>';
                            ?>

                            <?php
                                /* Valutazione con metodi nativi in camelCase di CodeIgniter 4 */
                                if($userAgent->isBrowser()):
                                    $agentText .= ' &bull; ' . lang('backend/admins.labels.browser') . ' <span class="text-primary">' . $userAgent->getBrowser() . '</span>';
                                elseif($userAgent->isMobile()):
                                    $agentText .= ' &bull; ' . lang('backend/admins.labels.mobile') . ' <span class="text-primary">' . $userAgent->getMobile() . '</span>';
                                elseif($userAgent->isRobot()):
                                    $agentText .= ' &bull; ' . lang('backend/admins.labels.robot') . ' Robot <span class="text-primary">' . $userAgent->getRobot() . '</span>';
                                endif;
                            ?>

                            <?= $agentText; ?>
                            &bull; 
                            <span><?= lang('backend/admins.labels.ipAddress'); ?></span>
                            <span class="text-primary"><?= esc($token->ip_address); ?></span>
                        </small>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="text-center text-danger fw-bold">
            <?= lang('backend/admins.messages.noTokensFound'); ?>
        </div>
    <?php endif; ?>
</div>