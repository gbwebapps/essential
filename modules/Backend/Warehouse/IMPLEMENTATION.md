# Warehouse XLSX — punto di ripresa

## Decisioni confermate

- Modulo autonomo: modules/Backend/Warehouse, Gestione magazzino, non Tools.
- Un XLSX, cinque fogli: brands, categories, products, brands_to_products, categories_to_products.
- Aggiunge e aggiorna; conserva entità e associazioni assenti dal file.
- UUID = identità; UUID vuoto = inserimento con UUID generato. UUID presente e sconosciuto = inserimento, come Import.
- ID numerici del file = riferimenti tra fogli, rimappati agli ID locali. Nessuna fusione implicita per marca/SKU.
- SKU e nome prodotto univoci separatamente, inclusi i record cestinati. Regole CRUD/CSV e controllo duplicati XLSX allineati.
- DDL root allineato e modules/Backend/Products/Database/Sql/uniqueProductName.sql applicato al database locale essential il 22 ottobre 2026. Indici UNIQUE separati su sku e product confermati; nessun record modificato (tabella vuota).
- Backup precedente all'ALTER: writable/backups/database/products_before_unique_name_2026-10-22_18-10-59_f08efb52.sql.
- Stesso modale, preview, errori e gestore Import condivisi; trasporto XLSX e transazione unica sono le varianti.
- Metadati del ciclo di vita non modificabili dal file; created_at/updated_at gestiti dal server.
- ID nel file: duplicati segnalati come tali; ID già nel database ammesso solo con lo stesso UUID, e ID fornito in aggiornamento deve coincidere con quello del record. ID obbligatorio per nuovi prodotti. Nessuna modifica degli ID esistenti; quelli dei nuovi inserimenti restano generati da auto_increment.
- Modale CSV/XLSX condiviso senza scroll interno: pulsanti identici in testata e nel footer già all'apertura, sincronizzati anche dopo la preview. Errori segnalati anche nell'alert globale.
- Un solo errore per campo/riga quando unicità nel database e nel workbook falliscono insieme. Pulsante Warehouse verde soltanto in grey/dark, bordi rispettivamente #6c757d e #222222.
- Flusso consigliato: esportare di nuovo dopo un import riuscito, per ottenere UUID e ID definitivi dei nuovi record. Nessun nuovo test eseguito per questi adeguamenti.

## Lavoro corrente, 22 ottobre 2026

### Promemoria applicato, 23 ottobre 2026

- Confermato dall'utente: "checkbox" indicava i chunk. Nessuna selezione di colonne o tabelle introdotta in Warehouse.
- Warehouse usa già Package::CHUNK_SIZE per la verifica Import e l'Export XLSX. Valore mantenuto a 5.
- Componenti CSV: dimensioni dei blocchi centralizzate in Config\\Backend\\Import::CHUNK_SIZE e Config\\Backend\\Export::CHUNK_SIZE, entrambe a 5. I motori leggono le costanti di configurazione; nessuna modifica al protocollo o ai backup.
- Modale albero categorie: Chiudi rosso e hover inverso solo in Grey/Dark; bordo Grey #6c757d. In Dark sfondo modale #444444 e bordi del modale e del pulsante #222222. White/Black e gli altri modali invariati.
- Verifica browser isolata sui quattro temi: 4 test superati, incluso il mantenimento della tastiera disabilitata.
- Verifiche finali di questa modifica: PHPStan senza errori sul codice applicativo e sui moduli (viste escluse); PHPUnit OK, 1153 test e 4012 asserzioni.

### Adeguamento implementato: date, chunk e modale export

- Approvato: niente created_at/updated_at/deleted_at/restored_at nei workbook o nei template. created_at/updated_at assegnati con now() server-side; gli skip non modificano date.
- Approvato: export solo delle entità non cestinate e delle pivot che collegano entità incluse.
- Scelta di sicurezza comunicata: import in blocchi di 5 per la ri-validazione, poi unica transazione finale. Il progresso è esplicitamente di verifica, non di righe già committed.
- Già impostati Package::CHUNK_SIZE=5, schema di scambio senza date, snapshot export coerente e filtrato, preflight import persistito e staging v3.
- WarehouseXlsxExport.php: snapshot coerente congelato, preparazione progressiva in prepared.jsonl, checksum e rollback dei byte non confermati, finalizzazione XLSX, download protetto e annullamento con lock.
- Vista Export condivisa; WarehouseExportManager autonomo nel modulo, con comandi dedicati. Nessuna ereditarietà da ExportCsvManager, selezione colonne, filtri CRUD o singleton CSV nel flusso XLSX. I CSV mantengono la scelta delle colonne.
- Export: cinque record preparati per richiesta, poi finalizzazione del file XLSX. Import: cinque operazioni ri-validate per richiesta, poi nuova validazione globale, backup e singolo commit.
- Pausa dimostrativa di 350 ms tra le richieste, soltanto nei manager Warehouse. Costante server Package::CHUNK_SIZE=5.
- Nuovi endpoint POST showExportModal/removeExport, endpoint export progressivo; endpoint download comune a job e template.
- Lingue allineate nelle sei localizzazioni e asset sorgente/pubblico sincronizzati.
- Correzione avvio export: pulsanti warehouse-export-start-btn/warehouse-export-cancel-btn nella vista condivisa tramite controlsPrefix, separati dagli handler CSV. Rimosso l'adattamento selectColumns dal componente CSV. Il ciclo XLSX invia solo entity alla prima richiesta ed exportId alle successive; annullamento atteso dopo la risposta prima della pulizia del job, ID conservato se la pulizia fallisce.
- L'importazione confermata non viene nascosta dal pulsante Chiudi mentre la catena di richieste è in corso; dopo un errore la preview torna disponibile.
- I vecchi workbook con date tecniche non sono accettati: esportare nuovamente o usare il nuovo template. Gli staging precedenti a v3 non vengono convertiti/cancellati automaticamente.
- La sospensione dei test di questa fase è superata dall'autorizzazione successiva del 22 ottobre 2026. Nessun import/esportazione applicativo sul database operativo durante i test.

Revisione implementata per allineare il vecchio import Warehouse al componente Import CSV.
Le verifiche della prima implementazione NON coprono questa revisione.
Vincolo iniziale, poi superato: test rinviati fino all'autorizzazione esplicita. L'utente ha successivamente autorizzato PHPStan, PHPUnit e la correzione dei risultati.

- Config/Package.php: whitelist delle cinque tabelle, provider e chiavi delle pivot.
- Models/WarehouseImportModel.php: schema reale, regole dei CRUD, piano insert/update/skip, stato finale relazionale, backup SQL e transazione.
- Models/WarehouseModel.php: lettura per export e template; vecchio percorso di fusione rimosso.
- Libraries/WarehouseXlsxImport.php: parser, staging v3, proprietà di sessione, checksum, lock, esito idempotente e recovery.
- Controllers/WarehouseController.php: apertura modale, template, preparazione, conferma, annullamento e ripresa.
- Viste condivise Import: parametri opzionali per più schemi e upload XLSX; valori predefiniti CSV invariati.
- Assets/js/modules/Warehouse.js: adattatore del gestore ImportCsvManager.
- Sei lingue aggiornate; asset sorgente/pubblico sincronizzati e pubblicazione del nuovo modulo JS configurata.
- UUID, ID del file e chiavi pivot duplicati bloccati. Marca e SKU confrontati anche tra righe del file con la collation reale MariaDB.
- Preview fino a 50 differenze per foglio; colonne parziali e riordinate, limiti XLSX/ZIP e formule vietate.
- Verifica dello stato risultante prima del backup e di quello effettivo prima del commit.
- Verifiche inizialmente rinviate; vedere la fase di correzione autorizzata qui sotto per gli esiti aggiornati.
- Test Warehouse adeguati a WarehouseImportModel, manifest v3, backup.sql, preflight da cinque, commit atomico e date server-side. Fixture SQL su tabelle temporanee della sola connessione essential_test, condivisa anche dalle regole is_unique.

### Correzioni autorizzate dopo PHPStan/PHPUnit

- Rimossi fallback ridondanti dopo i controlli di integrità; provider di dominio già tipizzato tramite la mappa esplicita. Nessun ignore/baseline PHPStan aggiunto.
- BaseController verifica il tipo di richiesta effettivamente supportato dal framework; i mock dei test di inizializzazione usano IncomingRequest.
- Fixture Account inizializza modello e amministratore; test delle regole include GroupsRules; test della lingua imposta e ripristina il servizio Language.
- Test Import coprono anche file identico senza staging eseguibile, assenza di scritture durante i chunk, backup prima della prima scrittura, no replay, duplicati, formule, colonne tecniche vietate, gerarchie e pivot. Test transazionali verificano rollback e rifiuto di uno stato cambiato dopo la preparazione.
- PHPStan livello 5: zero errori sul codice applicativo configurato e su Controllers/Models/Libraries dei quattro moduli. Le viste restano fuori da questo perimetro, come le viste app nella configurazione di progetto; le loro segnalazioni non sono state soppresse o corrette in questa fase.
- PHPUnit completo dopo le correzioni: OK, 1150 test e 4006 asserzioni, nessun errore/fallimento/test rischioso. Report: writable/phpunit-20261022-corrected.xml. Eseguito con --do-not-cache-result --no-coverage.

## Flusso e conservazione

1. Link -> modale condiviso con cinque schemi e upload.
2. Upload -> validazione completa, piano e preview. Nessuna scrittura applicativa e nessun backup.
3. Sincronizzazione perfetta -> nessuna conferma e nessuno staging eseguibile.
4. Conferma -> lock, nuovo controllo schema/dati/regole, backup SQL verificato, poi unica transazione.
5. Successo persistito -> richieste ripetute restituiscono lo stesso esito senza riscrivere.
6. Interruzione incerta -> niente riesecuzione automatica; conservare staging e backup.

Staging: writable/uploads/warehouse/<importId>/.
Backup: writable/backups/warehouse/<importId>/backup.sql.
Stati v3: staged (con cursore preflight), processing, completed, failed.
Gli staging v1/v2 non sono convertiti o eliminati automaticamente.
Staging scaduti staged/completed eliminabili; processing/failed e backup conservati.
Limiti: upload 10 MB, ZIP 1.000 elementi e 100 MB decompressi, 50.000 righe/foglio e 100.000 celle complessive.

## Perimetro

Il worktree contiene modifiche precedenti a Products, account/auth, configurazioni, Import CSV,
Tools, test e immagini: preservarle. Non modificare o eseguire i test durante questa fase.
