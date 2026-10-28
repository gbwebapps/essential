<?php declare(strict_types = 1);

/* Helper riservato alla CLI: nessuna esecuzione tramite richieste HTTP. */
if (PHP_SAPI !== 'cli'):
    exit(1);
endif;

$action = $argv[1] ?? '';
$uuid = $argv[2] ?? '';

if ( ! in_array($action, ['create', 'delete'], true)
    || ! preg_match('/\A[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}\z/', $uuid)):
    fwrite(STDERR, "Comando o UUID della fixture non valido.\n");
    exit(1);
endif;

chdir(dirname(__DIR__, 2));
$_SERVER['CI_ENVIRONMENT'] = 'e2e';
require 'vendor/codeigniter4/framework/system/util_bootstrap.php';

$config = config('Database');
$expectedDatabase = (string) $config->tests['database'];
$db = \Config\Database::connect('tests');

/* La doppia verifica impedisce scritture fuori dal database locale configurato. */
if (ENVIRONMENT !== 'e2e' || $expectedDatabase === '' || $db->getDatabase() !== $expectedDatabase
    || $db->query('select database() as db')->getRow()->db !== $expectedDatabase):
    fwrite(STDERR, "La fixture richiede CI_ENVIRONMENT=e2e e il database locale configurato.\n");
    exit(1);
endif;

$email = 'e2e-admin-' . $uuid . '@example.invalid';
$groupName = 'e2e-' . $uuid;
$owner = 'Playwright fixture ' . $uuid;

if ( ! $db->transBegin()):
    fwrite(STDERR, "Impossibile iniziare la transazione della fixture.\n");
    exit(1);
endif;

try {
    if ($action === 'create'):
        if ($db->table('admins')->where('uuid', $uuid)->countAllResults() !== 0
            || $db->table('admins_groups')->where('name', $groupName)->countAllResults() !== 0):
            throw new \RuntimeException('Identificativo della fixture gia presente: nessun record sovrascritto.');
        endif;

        $now = date('Y-m-d H:i:s');

        if ( ! $db->table('admins_groups')->insert([
            'name' => $groupName,
            'description' => $owner,
            'created_at' => $now,
        ])):
            throw new \RuntimeException('Creazione del gruppo di prova non riuscita.');
        endif;

        $groupId = (int) $db->insertID();

        if ( ! $db->table('admins')->insert([
            'uuid' => $uuid,
            'firstname' => 'Playwright',
            'lastname' => 'Fixture',
            'email' => $email,
            'phone' => '+390000000000',
            'status' => 1,
            'superadmin' => 0,
            'group_id' => $groupId,
            'note' => $owner,
            'password_hash' => null,
            'created_at' => $now,
        ])):
            throw new \RuntimeException('Creazione dell’amministratore di prova non riuscita.');
        endif;
    else:
        $admin = $db->table('admins')->where('uuid', $uuid)->get()->getRowArray();

        if ($admin !== null):
            if ($admin['email'] !== $email || $admin['note'] !== $owner || (int) $admin['superadmin'] !== 0):
                throw new \RuntimeException('Record non posseduto dalla fixture: cancellazione rifiutata.');
            endif;

            if ( ! $db->table('admins')->where('uuid', $uuid)->where('email', $email)->where('note', $owner)->delete()):
                throw new \RuntimeException('Rimozione dell’amministratore di prova non riuscita.');
            endif;
        endif;

        if ( ! $db->table('admins_groups')->where('name', $groupName)->where('description', $owner)->delete()):
            throw new \RuntimeException('Rimozione del gruppo di prova non riuscita.');
        endif;
    endif;

    if ( ! $db->transStatus() || ! $db->transCommit()):
        throw new \RuntimeException('Transazione della fixture non riuscita.');
    endif;

    echo json_encode(['result' => true]);
} catch (\Throwable $error) {
    $db->transRollback();
    fwrite(STDERR, $error->getMessage() . PHP_EOL);
    exit(1);
}
