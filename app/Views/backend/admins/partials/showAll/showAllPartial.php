<!-- Contenitore controlli tabella: Gestione dinamica posizionamento -->
<!-- d-grid: i bottoni vanno uno sotto l'altro a piena larghezza su mobile -->
<!-- d-md-flex: i bottoni tornano in riga su schermi medi (tablet/desktop) -->
<!-- justify-content-md-center: centra i bottoni orizzontalmente su schermi medi -->
<div class="table-controls mb-3 mt-1 d-grid gap-2 d-md-flex justify-content-md-center">
    
    <button type="button" id="btn-table-reload" class="btn btn-sm btn-secondary">
        <i class="fa-solid fa-sync"></i> Ricarica Elenco
    </button>
    
    <button type="button" id="btn-table-reset-order" class="btn btn-sm btn-secondary">
        <i class="fa-solid fa-sort"></i> Reset Ordinamento
    </button>
    
    <button type="button" id="btn-table-reset-all" class="btn btn-sm btn-danger">
        <i class="fa-solid fa-filter-circle-xmark"></i> Reset Totale
    </button>

</div>

<!-- Wrapper per rendere la tabella scrollabile orizzontalmente su schermi piccoli -->
<div class="table-responsive">
    <!-- Tabella amministratori: configurata per l'aggancio di DataTables -->
    <table id="adminsTable" class="table table-striped table-hover border w-100">
        <thead>
            <tr>
                <th>ID</th>
                <th>Nome</th>
                <th>Cognome</th>
                <th>Email</th>
                <th>Stato</th>
                <th class="text-end">Azioni</th>
            </tr>
        </thead>
        <tbody>
            <!-- Il corpo della tabella viene popolato dinamicamente via AJAX da DataTables -->
        </tbody>
    </table>
</div>