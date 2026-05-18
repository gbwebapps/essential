<!DOCTYPE html>
<html lang="it">

<head>
    <!-- Configurazione meta e charset -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Titolo della pagina dinamico -->
    <title><?= esc($title); ?> | <?= esc($siteName); ?></title>

    <?php /* Ciclo per l'inclusione dei file CSS specifici della pagina */ ?>
    <?php if(isset($assets['css'])): foreach($assets['css'] as $css): ?>
        <link rel="stylesheet" href="<?= base_url($css['path']); ?>">
    <?php endforeach; endif; ?>
</head>

<body>
    <!-- Overlay per backdrop personalizzati (es. loader o modali) -->
    <div id="customBackdrop"></div>

    <!-- Sezione Navbar Superiore -->
    <div id="navbar-top-view">
        <?= $this->include('backend/template/navbarTopView'); ?>
    </div>

    <!-- Contenitore per il loader di caricamento AJAX -->
    <div id="show_loader"></div>

    <!-- Contenitore globale per le notifiche Toast di Bootstrap -->
    <div class="toast-container position-fixed top-0 end-0 p-3" id="toast-container"></div>

    <main class="container-fluid">
        <div class="row">
            <!-- Colonna principale Mobile First -->
            <div class="col-12"> 

                <!-- Inclusione testata della sezione corrente -->
                <div id="section-view">
                    <?= $this->include('backend/template/sectionView'); ?>
                </div>

                <!-- Inclusione eventuale messaggio -->
                <div id="message-view">
                    <?= $this->include('backend/template/messageView'); ?>
                </div>

                <!-- Inclusione barra dei link di navigazione interna -->
                <div id="links-bar-view">
                    <?= $this->include('backend/template/linksBarView'); ?>
                </div>

                <!-- Punto di iniezione del contenuto specifico della vista -->
                <?= $this->renderSection('content'); ?>
                
            </div>
        </div>
    </main>

    <!-- Sezione Navbar Inferiore -->
    <div id="navbar-bottom-view">
        <?= $this->include('backend/template/navbarBottomView'); ?>
    </div>

    <!-- Pulsante per lo scroll rapido verso l'alto -->
    <button type="button" class="scrollup btn btn-secondary btn-sm">
        <i class="fa-solid fa-arrow-circle-up"></i> <?= lang('backend/global.buttons.back_to_top'); ?>
    </button>

    <?php /* Passaggio del nome controller al comparto Javascript */ ?>
    <?php if(isset($controller)): ?>
        <div id="controller" data-controller="<?= esc($controller); ?>"></div>
    <?php endif; ?>

    <!-- Passaggio del nome action al comparto Javascript -->
    <?php if(isset($action)): ?>
        <div id="action" data-action="<?= esc($action); ?>"></div>
    <?php endif; ?>

    <!-- URL base del sito per le chiamate AJAX in JS -->
    <div id="hidden-urlbase" data-urlbase="<?= base_url(); ?>"></div>

    <!-- Ciclo per l'inclusione dei file Javascript con supporto ai moduli ES6 -->
    <?php if(isset($assets['js'])): foreach($assets['js'] as $js): ?>
        <script 
            <?= ($js['isModule'] ?? false) ? 'type="module"' : 'type="text/javascript"'; ?> 
            src="<?= base_url($js['path']); ?>">
        </script>
    <?php endforeach; endif; ?>
    
</body>

</html>