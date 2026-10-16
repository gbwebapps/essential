<!DOCTYPE html>
<html lang="it" class="h-100">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Essential - Gestione centralizzata delle attività amministrative">

    <title>Essential</title>

    <link rel="shortcut icon" type="image/png" href="<?= base_url('favicon.ico'); ?>">
    <link rel="stylesheet" href="<?= base_url('assets/vendor/bootstrap/css/bootstrap.min.css'); ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/frontend/frontend.css?v=4'); ?>">
</head>

<body class="d-flex h-100 text-center">
    <div class="cover-container d-flex w-100 h-100 p-3 mx-auto flex-column">
        <header class="mb-auto">
            <div>
                <h3 class="float-md-start mb-0">Essential</h3>

                <nav class="nav nav-masthead justify-content-center float-md-end" aria-label="Navigazione principale">
                    <a class="nav-link fw-bold py-1 px-0 active" aria-current="page" href="<?= base_url(); ?>">Home</a>
                    <a class="nav-link fw-bold py-1 px-0" href="<?= base_url('backend/auth'); ?>">Backend</a>
                </nav>
            </div>
        </header>

        <main class="px-3">
            <h1 class="main-logo-heading">
                <img class="main-logo" src="<?= base_url('assets/img/Essential_Chiaro_su_Scuro.png?v=2'); ?>" width="860" height="177" alt="Essential">
            </h1>

            <p class="lead main-slogan text-center">
                Gestione centralizzata, semplice e sicura delle attività amministrative.
            </p>

            <ul class="features-list" aria-label="Funzionalità principali">
                <li><span class="feature-check" aria-hidden="true">&#10003;</span><span>Gestione amministratori, gruppi e permessi</span></li>
                <li><span class="feature-check" aria-hidden="true">&#10003;</span><span>Autenticazione a due fattori</span></li>
                <li><span class="feature-check" aria-hidden="true">&#10003;</span><span>Impostazioni centralizzate e strumenti di manutenzione</span></li>
                <li><span class="feature-check" aria-hidden="true">&#10003;</span><span>CRUD con cestino e ripristino dati, importazione ed esportazione dati in CSV</span></li>
                <li><span class="feature-check" aria-hidden="true">&#10003;</span><span>Registro attività, accessi e gestione sessioni</span></li>
            </ul>
        </main>

        <footer class="mt-auto text-white-50">
            <a href="https://www.gbwebapps.com/" aria-label="GB Web Apps">
                <img class="footer-logo" src="<?= base_url('assets/img/GB_Web_Apps_Chiaro_su_Scuro.png?v=3'); ?>" width="1175" height="177" alt="GB Web Apps">
            </a>
        </footer>
    </div>
</body>

</html>
