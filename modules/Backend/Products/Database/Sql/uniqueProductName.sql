-- Aggiornamento della tabella esistente: eseguire dopo un backup.
-- Il controllo comprende anche i prodotti cestinati e usa la collation della colonna.
-- Se questa query restituisce righe, risolvere prima i nomi duplicati.
select `product`, count(*) as `occurrences`
from `essential`.`products`
group by `product`
having count(*) > 1;

-- Nessuna cancellazione o modifica automatica dei prodotti.
-- Il vincolo sullo SKU resta invariato; il nome diventa univoco separatamente.
alter table `essential`.`products`
    drop index `idx_products_product`,
    add unique index `idx_products_product` (`product`);
