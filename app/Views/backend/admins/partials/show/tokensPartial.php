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
                /* Se il token è una sessione, aggiungo alla data di scadenza il tempo definito nel file di configurazione */
                if ($token->token_type === 'session'):
                    $tokenExpire = $token->token_expire + config('BackendAuth')->sessionTime;
                else:
                    $tokenExpire = $token->token_expire;
                endif;

                /* Formattazione visiva della scadenza */
                if(time() < $tokenExpire):
                    $expireDate = '<span class="text-success">' . convertDate($tokenExpire) . '</span>';
                else:
                    $expireDate = '<span class="text-danger"><s>' . convertDate($tokenExpire) . '</s></span>';
                endif;

                /* Identificazione del tipo con fallback di sicurezza finale */
                $tokenType = '';
                if($token->token_type === 'session'):
                    $tokenType = lang('backend/admins.labels.session');
                elseif($token->token_type === 'activation'):
                    $tokenType = lang('backend/admins.labels.activation');
                elseif($token->token_type === 'cookie'):
                    $tokenType = lang('backend/admins.labels.rememberMe');
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
                        <form class="deleteToken" data-message="<?= sprintf(lang('backend/admins.messages.areYouSureDeleteToken'), esc($admin->firstname), esc($admin->lastname)); ?>">
                            <input type="hidden" name="id" value="<?= esc($token->id); ?>">
                            <input type="hidden" name="uuid" value="<?= esc($admin->uuid); ?>">
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
                            <span class="text-primary"><?= lang('backend/admins.labels.ipAddress'); ?></span>
                            <span class="text-success"><?= esc($token->ip); ?></span>
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