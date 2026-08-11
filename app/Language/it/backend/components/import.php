<?php declare(strict_types = 1);

return [
	'panels' => [
		'main' => 'Importa dati da CSV', 
	], 
	'labels' => [
		'entity' => 'Tabella', 
		'columnName' => 'Colonna', 
		'dataType' => 'Tipo', 
		'maxLength' => 'Lunghezza', 
		'keysAndIndexes' => 'Chiave primaria e indici', 
		'uploadCsv' => 'Carica file', 
	], 
	'links' => [
		'downloadTemplate' => 'Scarica il file CSV di esempio'
	], 
    'buttons' => [
    	'undo' => 'Annulla', 
    	'import' => 'Importa', 
    ], 
    'messages' => [
        'validateToastErrors' => '%s',
        'invalidEntity' => 'Nome tabella sconosciuto.', 
        'noStructure' => 'Non ci sono dati per la struttura di questa tabella.', 
        'fileReadError' => 'Errore di lettura del file.', 
        'headerMismatch' => 'Nomi colonne oppure ordine colonne non sono conformi.', 
        'noDataToPreview' => 'Nessun dato di anteprima disponibile.', 
        'previewInfo' => 'I dati del file sono corretti e pronti per l\'inserimento. Ecco un\'anteprima (massimo 10 righe). Conferma per importare l\'intero file nel database.', 
        'importSuccess' => 'Importazione conclusacon successo. %d records inseriti, %d records aggiornati.', 
    ],
];
