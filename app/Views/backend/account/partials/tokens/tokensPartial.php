<div class="card-body">
    <?php if(count($tokens)): ?>
        <table class="table table-bordered align-middle mb-0">
            <thead>
            <tr>
                <th class="text-left" style="width: 37.5%;"><?= lang('backend/admins.labels.createdToken'); ?></th>
                <th class="text-left" style="width: 37.5%;"><?= lang('backend/admins.labels.expiredToken'); ?></th>
                <th class="text-left" style="width: 20%;"><?= lang('backend/admins.labels.typeToken'); ?></th>
                <th class="text-center" style="width: 5%;">&nbsp;</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach($tokens as $token): ?>

                <?php
                    /* Creiamo l'oggetto DateTime partendo dalla data stringa del database */
                    $dateExpire = new \DateTime($token->token_expire);

                    /* Se è una sessione, modifichiamo la data aggiungendo i secondi del config */
                    if ($token->token_type === 'session'):
                        $dateExpire->modify('+' . (int) config(\Config\Backend\Auth::class)->sessionTime . ' seconds');
                    endif;

                    /* Creiamo l'oggetto DateTime con l'ora attuale per fare il confronto */
                    $now = new \DateTime();

                    /* Confrontiamo i due oggetti DateTime in modo nativo e sicuro */
                    if ($now < $dateExpire):
                        $expireDate = '<span class="text-success">' . convertDate($dateExpire->format('Y-m-d H:i:s')) . '</span>';
                    else:
                        $expireDate = '<span class="text-danger"><s>' . convertDate($dateExpire->format('Y-m-d H:i:s')) . '</s></span>';
                    endif;

                    /* Identificazione del tipo con fallback di sicurezza finale */
                    $tokenType = '';
                    if ($token->token_type === 'session'):
                        $tokenType = lang('backend/admins.labels.session');
                    elseif ($token->token_type === 'activation'):
                        $tokenType = lang('backend/admins.labels.activation');
                    elseif ($token->token_type === 'cookie'):
                        $tokenType = lang('backend/admins.labels.remember_me');
                    else:
                        $tokenType = lang('backend/admins.labels.unknown');
                    endif;
                ?>

                <tr>
                    <td class="text-left"><?= convertDate(esc($token->token_create)); ?></td>
                    <td class="text-left"><?= $expireDate; ?></td>
                    <td class="text-left">
                        <span class="text-success fw-bold"><?= $tokenType; ?></span>
                    </td>
                    <td class="text-center" rowspan="2">
                        <form class="deleteToken" data-message="<?= sprintf(lang('backend/admins.messages.areYouSureDeleteToken'), esc($currentAdmin->firstname), esc($currentAdmin->lastname)); ?>">
                            <input type="hidden" name="id" value="<?= esc($token->id); ?>">
                            <input type="hidden" name="uuid" value="<?= esc($currentAdmin->uuid); ?>">
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
                            &nbsp;&bull;&nbsp;
                            <span><?= lang('backend/admins.labels.ipAddress'); ?></span>
                            <span class="text-primary"><?= esc($token->ip); ?></span>
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