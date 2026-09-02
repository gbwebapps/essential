<?php declare(strict_types = 1);

return [
	'panels' => [
		'main' => 'Importazione dati CSV nella tabella <span class="text-primary fw-bold">%s</span>', 
	], 
	'labels' => [
		'entity' => 'Tabella', 
		'columnName' => 'Colonna', 
		'dataType' => 'Tipo', 
		'maxLength' => 'Lunghezza', 
		'keysAndIndexes' => 'Chiave primaria e indici', 
		'uploadCsv' => 'Carica file', 
        'toggleVisibility' => 'Mostra/Nascondi', 
	], 
	'links' => [
		'downloadTemplate' => 'Scarica il file CSV di esempio per <span class="text-primary fw-bold">%s</span>', 
        'import' => 'Importa CSV',
	], 
    'buttons' => [
    	'close' => 'Chiudi', 
    	'import' => 'Importa', 
        'remove' => 'Rimuovi', 
    ], 
    'errors' => [
    	'uploaded' => 'Devi selezionare un file da importare.', 
    	'ext_in' => 'Il file caricato non è in un formato valido.', 
    ], 
    'messages' => [
        'validateToastErrors' => '%s', 
        'validationErrors' => '%d errori di validazione.', 
        'invalidEntity' => 'Nome tabella sconosciuto.', 
        'noStructure' => 'Non ci sono dati per la struttura di questa tabella.', 
        'fileReadError' => 'Errore di lettura del file.', 
        'headerMismatch' => 'Nomi colonne oppure ordine colonne non sono conformi.', 
        'noDataToPreview' => 'Nessun dato di anteprima disponibile.', 
        'previewInfo' => 'I dati del file sono corretti e pronti per l\'inserimento. Ecco un\'anteprima (massimo 10 righe). Conferma per importare l\'intero file nel database.', 
        'importSuccess' => 'Importazione conclusa con successo. %d records inseriti, %d records aggiornati.', 
        'noDataFound' => 'Non sono presenti dati da importare nel file.', 
        'noDataProcessed' => 'Non sono presenti dati da importare nel file.', 
        'backupError' => 'Errore durante la creazione del backup della tabella.', 
        'wrongColumnsNumber' => "Riga %d: numero errato di colonne (%d trovate, %d attese).", 
        'fileNotFoundError' => 'Non è stato trovato il file. Probabilmente è stato rimosso.', 
        'fileReadError' => 'Errore durante l\'apertura del file.', 
        'notDeterminedPrimaryKey' => "Impossibile determinare la Primary Key per l'entità %d.", 
        'importationUndone' => "Importazione annullata: rilevata riga con numero errato di colonne rispetto all'intestazione.", 
        'importTransactionError' => 'Errore durante l\'esecuzione del processo di importazione. ', 
        'importationNoRecordsModified' => 'Importazione completata: nessun record modificato poiché i dati sono già allineati.', 
        'processedRows' => 'Elaborate %d righe', 
    ],
];
