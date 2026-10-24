/* Backup tabella: products - Data: 2026-10-23 15:23:50 */
set foreign_key_checks = 0;
truncate table `products`;
insert into `products` (`id`, `uuid`, `sku`, `product`, `note`, `quantity`, `price`, `status`, `created_at`, `updated_at`, `deleted_at`, `restored_at`) values
('10', '42be9649-689e-471c-a5f4-e972eab743ca', 'K078565', 'sciaff brutto fico', NULL, '90', '29.99', '1', '2026-10-22 20:56:32', NULL, NULL, NULL),
('1', 'cd5919e1-7771-416a-8c2d-03f9b1e04628', 'K078564', 'sciaff brutto brutto', NULL, '90', '29.99', '1', '2026-10-21 19:08:50', '2026-10-22 15:04:37', NULL, NULL);
set foreign_key_checks = 1;
