-- TABELLA admins_groups
create table if not exists `essential`.`admins_groups` (
    `id` int unsigned auto_increment,
    `name` varchar(50) not null unique,
    `description` varchar(255) null,
    `created_at` datetime not null,
    `updated_at` datetime null default null,
    primary key (`id`)
) engine = innodb default charset = utf8mb4 collate = utf8mb4_general_ci;

-- TABELLA admins_groups_permissions
create table if not exists `essential`.`admins_groups_permissions` (
    `id` int unsigned auto_increment,
    `group_id` int unsigned not null,
    `permission` varchar(50) not null,
    primary key (`id`),
    unique key `unique_group_permission` (`group_id`, `permission`),
    constraint `fk_group_permissions_group_id` 
        foreign key (`group_id`) references `essential`.`admins_groups`(`id`) on update cascade on delete cascade
) engine = innodb default charset = utf8mb4 collate = utf8mb4_general_ci;

-- TABELLA admins 
create table if not exists `essential`.`admins` (
    `id` int unsigned auto_increment unique,
    `uuid` char(36) not null unique,
    `firstname` varchar(255) not null,
    `lastname` varchar(255) not null,
    `email` varchar(255) not null unique,
    `phone` varchar(255) not null,
    `status` tinyint(1) default 0 not null,
    `superadmin` tinyint(1) default 0 not null,
    `group_id` int unsigned not null,
    `note` text null,
    `password_hash` varchar(255) null,
    `created_at` datetime not null,
    `updated_at` datetime null default null,
    `suspended_at` datetime null default null,
    `resetted_at` datetime null default null,
    `deleted_at` datetime null default null,
    `restored_at` datetime null default null,
    primary key (`uuid`),
    index (`id`),
    index (`firstname`),
    index (`lastname`),
    index (`email`),
    index (`phone`),
    index (`status`),
    index (`superadmin`),
    index (`group_id`),
    index `idx_admins_created_at` (`created_at`),
    index `idx_admins_updated_at` (`updated_at`),
    index `idx_admins_suspended_at` (`suspended_at`),
    index `idx_admins_resetted_at` (`resetted_at`),
    index `idx_admins_deleted_at` (`deleted_at`),
    index `idx_admins_restored_at` (`restored_at`),
    constraint `fk_admins_group_id` 
        foreign key (`group_id`) references `essential`.`admins_groups`(`id`) on update cascade on delete restrict
) engine = innodb default charset = utf8mb4 collate = utf8mb4_general_ci;

-- TABELLA admins_permissions
create table if not exists `essential`.`admins_permissions` (
    `id` int unsigned auto_increment,
    `permission` varchar(50) not null,
    `admin_uuid` char(36) not null,
    `allow` tinyint(1) not null default 1,
    primary key (`id`),
    unique key `unique_admin_permission` (`admin_uuid`, `permission`)
) engine = innodb default charset = utf8mb4 collate = utf8mb4_general_ci;

-- TABELLA admins_tokens
create table if not exists `essential`.`admins_tokens` (
    `id` int unsigned auto_increment,
    `admin_uuid` char(36) not null,
    `token_hash` varchar(64) not null,
    `token_create` datetime not null,
    `last_activity` datetime null default null,
    `token_expire` datetime not null,
    `token_type` enum('cookie', 'session', 'activation') not null,
    `user_agent` varchar(255) not null,
    `ip_address` varchar(45) not null,
    `created_at` datetime not null,
    primary key (`id`),
    index (`admin_uuid`),
    index (`token_hash`),
    index (`token_create`),
    index (`last_activity`),
    index (`token_expire`),
    index (`token_type`)
) engine = innodb default charset = utf8mb4 collate = utf8mb4_general_ci;

-- TABELLA admins_attempts
create table if not exists `essential`.`admins_attempts` (
    `id` int unsigned auto_increment,
    `admin_uuid` char(36) null,
    `email_hash` char(64) not null,
    `ip_address` varchar(45) not null,
    `timestamp` datetime not null,
    primary key (`id`),
    index `idx_admin_attempts_lookup` (`admin_uuid`, `timestamp`),
    index `idx_admin_attempts_email` (`email_hash`, `timestamp`),
    index `idx_admin_attempts_ip` (`ip_address`, `timestamp`),
    index `idx_admin_attempts_timestamp` (`timestamp`)
) engine = innodb default charset = utf8mb4 collate = utf8mb4_general_ci;

-- TABELLA admins_reset_attempts
create table if not exists `essential`.`admins_reset_attempts` (
    `id` int unsigned auto_increment,
    `email_hash` char(64) not null,
    `ip_address` varchar(45) not null,
    `timestamp` datetime not null,
    primary key (`id`),
    index `idx_admins_reset_attempts_email` (`email_hash`, `timestamp`),
    index `idx_admins_reset_attempts_ip` (`ip_address`, `timestamp`),
    index `idx_admins_reset_attempts_timestamp` (`timestamp`)
) engine = innodb default charset = utf8mb4 collate = utf8mb4_general_ci;

-- TABELLA admins_2fa
create table if not exists `essential`.`admins_2fa` (
    `id` int unsigned auto_increment,
    `admin_uuid` char(36) not null,
    `method` enum('email', 'totp') not null default 'email',
    `secret` varchar(255) null,
    `enabled` tinyint(1) not null default 1,
    primary key (`id`),
    unique key `uq_admin_method` (`admin_uuid`,`method`),
    key `idx_admin` (`admin_uuid`),
    index `idx_admin_enabled` (`admin_uuid`,`enabled`)
) engine = innodb default character set = utf8mb4 collate = utf8mb4_general_ci;

-- TABELLA admins_2fa_codes
create table if not exists `essential`.`admins_2fa_codes` (
    `id` int unsigned auto_increment,
    `admin_uuid` char(36) not null,
    `code` char(6) not null,
    `expires_at` datetime not null,
    primary key (`id`),
    index(`admin_uuid`)
) engine = innodb default charset = utf8mb4 collate = utf8mb4_general_ci;

-- TABELLA admins_2fa_attempts
create table if not exists `essential`.`admins_2fa_attempts` (
    `id` int unsigned auto_increment,
    `admin_uuid` char(36) not null,
    `method` enum('email', 'totp') not null,
    `ip_address` varchar(45) not null,
    `timestamp` datetime not null,
    primary key (`id`),
    index `idx_admin_2fa_attempts_lookup` (`admin_uuid`, `method`, `timestamp`),
    index `idx_admin_2fa_attempts_ip` (`ip_address`, `timestamp`),
    index `idx_admin_2fa_attempts_timestamp` (`timestamp`)
) engine = innodb default charset = utf8mb4 collate = utf8mb4_general_ci;

-- TABELLA admins_audits
create table if not exists `essential`.`admins_audits` (
    `id` int unsigned auto_increment primary key,
    `admin_uuid` char(36) null default null,
    `username` varchar(100) not null default 'Guest',
    `action` varchar(50) not null,
    `section` varchar(100) not null,
    `details` text null default null,
    `ip_address` varchar(45) not null,
    `user_agent` varchar(255) not null,
    `created_at` datetime not null,
    index `idx_admin_uuid` (`admin_uuid`),
    index `idx_sezione` (`section`),
    index `idx_created_at` (`created_at`)
) engine = InnoDB default charset  =utf8mb4 collate = utf8mb4_general_ci;

-- TABELLA admins_logs
create table if not exists `essential`.`admins_logs` (
    `id` int unsigned auto_increment,
    `token_id` int unsigned null default null, -- Modificato per accettare null
    `admin_uuid` char(36) null default null,
    `username` varchar(100) not null default 'Guest',
    `login` datetime not null,
    `logout` datetime null default null, 
    `logout_reason` enum('manual', 'timeout', 'deleted', 'banned') null default null,
    `token_type` enum('cookie', 'session') not null,
    `user_agent` varchar(255) not null,
    `ip_address` varchar(45) not null,
    `created_at` datetime not null,
    primary key (`id`),
    index (`token_id`),
    index (`admin_uuid`),
    index (`username`),
    index (`login`),
    index (`logout`),
    index (`logout_reason`),
    -- Aggiunta del vincolo FK
    constraint `fk_logs_token` 
        foreign key (`token_id`) 
        references `essential`.`admins_tokens`(`id`) 
        on update cascade
        on delete set null
) engine = innodb default charset = utf8mb4 collate = utf8mb4_general_ci;

-- TABELLA sessions
create table if not exists `essential`.`sessions` (
    `id` varchar(128) not null,
    `ip_address` varchar(45) not null,
    `timestamp` datetime not null default current_timestamp,
    `data` blob not null,
    primary key (`id`),
    key `sessions_timestamp` (`timestamp`)
) engine = innodb default charset = utf8mb4 collate = utf8mb4_general_ci;

-- TABELLA images
create table if not exists `essential`.`images` (
    `id` int unsigned auto_increment,
    `entity` varchar(255) not null,
    `entity_uuid` char(36) not null,
    `filename` varchar(255) not null,
    `is_cover` tinyint(1) not null default 0,
    `created_at` datetime not null,
    primary key (`id`),
    index idx_images_entity_uuid (entity, entity_uuid),
    index idx_images_cover (entity, entity_uuid, is_cover)
) engine = innodb default charset = utf8mb4 collate = utf8mb4_general_ci;

-- TABELLA brands
create table if not exists `essential`.`brands` (
    `id` int unsigned auto_increment unique,
    `uuid` char(36) not null,
    `brand` varchar(255) not null unique,
    `note` text null,
    `status` tinyint(1) default 0 not null,
    `created_at` datetime not null,
    `updated_at` datetime null default null,
    `deleted_at` datetime null default null,
    `restored_at` datetime null default null,
    primary key (`uuid`),
    index `idx_brands_status` (`status`),
    index `idx_brands_created_at` (`created_at`),
    index `idx_brands_updated_at` (`updated_at`),
    index `idx_brands_deleted_at` (`deleted_at`),
    index `idx_brands_restored_at` (`restored_at`)
) engine = innodb default charset = utf8mb4 collate = utf8mb4_general_ci;

-- TABELLA categories
create table if not exists `essential`.`categories` (
    `id` int unsigned auto_increment unique,
    `uuid` char(36) not null,
    `parent_id` int unsigned null default null,
    `category` varchar(255) not null,
    `note` text null,
    `status` tinyint(1) default 0 not null,
    `created_at` datetime not null,
    `updated_at` datetime null default null,
    `deleted_at` datetime null default null,
    `restored_at` datetime null default null,
    primary key (`uuid`),
    index `idx_categories_parent_id` (`parent_id`),
    index `idx_categories_category` (`category`),
    index `idx_categories_status` (`status`),
    index `idx_categories_created_at` (`created_at`),
    index `idx_categories_updated_at` (`updated_at`),
    index `idx_categories_deleted_at` (`deleted_at`),
    index `idx_categories_restored_at` (`restored_at`),
    constraint `fk_categories_parent_id`
        foreign key (`parent_id`)
        references `essential`.`categories` (`id`)
        on update cascade
        on delete restrict
) engine = innodb default charset = utf8mb4 collate = utf8mb4_general_ci;

-- TABELLA products
create table if not exists `essential`.`products` (
    `id` int unsigned auto_increment unique,
    `uuid` char(36) not null,
    `sku` varchar(100) not null unique,
    `product` varchar(255) not null,
    `note` text null,
    `quantity` int unsigned not null default 0,
    `price` decimal(12,2) not null default 0.00,
    `status` tinyint(1) default 0 not null,
    `created_at` datetime not null,
    `updated_at` datetime null default null,
    `deleted_at` datetime null default null,
    `restored_at` datetime null default null,
    primary key (`uuid`),
    unique index `idx_products_product` (`product`),
    index `idx_products_quantity` (`quantity`),
    index `idx_products_price` (`price`),
    index `idx_products_status` (`status`),
    index `idx_products_created_at` (`created_at`),
    index `idx_products_updated_at` (`updated_at`),
    index `idx_products_deleted_at` (`deleted_at`),
    index `idx_products_restored_at` (`restored_at`)
) engine = innodb default charset = utf8mb4 collate = utf8mb4_general_ci;

-- TABELLA brands_to_products
create table if not exists `essential`.`brands_to_products` (
    `brand_id` int unsigned not null,
    `product_id` int unsigned not null,
    `status` tinyint(1) not null default 1,
    primary key (`brand_id`, `product_id`),
    index `idx_brands_to_products_product_id` (`product_id`),
    index `idx_brands_to_products_status` (`status`),
    constraint `fk_brands_to_products_brand_id`
        foreign key (`brand_id`)
        references `essential`.`brands` (`id`)
        on update cascade
        on delete restrict,
    constraint `fk_brands_to_products_product_id`
        foreign key (`product_id`)
        references `essential`.`products` (`id`)
        on update cascade
        on delete cascade
) engine = innodb default charset = utf8mb4 collate = utf8mb4_general_ci;

-- TABELLA categories_to_products
create table if not exists `essential`.`categories_to_products` (
    `category_id` int unsigned not null,
    `product_id` int unsigned not null,
    `status` tinyint(1) not null default 1,
    primary key (`category_id`, `product_id`),
    index `idx_categories_to_products_product_id` (`product_id`),
    index `idx_categories_to_products_status` (`status`),
    constraint `fk_categories_to_products_category_id`
        foreign key (`category_id`)
        references `essential`.`categories` (`id`)
        on update cascade
        on delete restrict,
    constraint `fk_categories_to_products_product_id`
        foreign key (`product_id`)
        references `essential`.`products` (`id`)
        on update cascade
        on delete cascade
) engine = innodb default charset = utf8mb4 collate = utf8mb4_general_ci;

create table if not exists `essential`.`settings` (
    `id` int unsigned auto_increment primary key,
    `class` varchar(255) not null,
    `key` varchar(255) not null,
    `value` text null,
    `created_at` datetime default current_timestamp,
    `updated_at` datetime default current_timestamp on update current_timestamp,
    unique key `class_key_unique` (`class`, `key`),
    index `idx_class` (`class`)
) engine = InnoDB default charset = utf8mb4 collate = utf8mb4_general_ci;

-- VINCOLI foreign key ESTERNI (Puntati su essential)
alter table `essential`.`admins_permissions`
add constraint `fk_admins_permissions_admin_uuid`
foreign key (`admin_uuid`) references `essential`.`admins`(`uuid`) on update cascade on delete cascade;

alter table `essential`.`admins_tokens`
add constraint `fk_admins_tokens_admin_uuid` 
foreign key (`admin_uuid`) references `essential`.`admins`(`uuid`) on update cascade on delete cascade;

alter table `essential`.`admins_attempts`
add constraint `fk_admins_attempts_admin_uuid` 
foreign key (`admin_uuid`) references `essential`.`admins`(`uuid`) on update cascade on delete cascade;

alter table `essential`.`admins_2fa`
add constraint `fk_admins_2fa_admin_uuid`
foreign key (`admin_uuid`) references `essential`.`admins`(`uuid`) on update cascade on delete cascade;

alter table `essential`.`admins_2fa_codes`
add constraint `fk_admins_2fa_codes_admin_uuid`
foreign key (`admin_uuid`) references `essential`.`admins`(`uuid`) on update cascade on delete cascade;

alter table `essential`.`admins_2fa_attempts`
add constraint `fk_user_2fa_attempts_admin_uuid`
foreign key (`admin_uuid`) references `essential`.`admins`(`uuid`) on update cascade on delete cascade;


-- INSERIMENTO GRUPPI OPERATIVI STANDARD
insert into `essential`.`admins_groups` (`id`, `name`, `description`, `created_at`) values 
(1, 'Administrator', 'Gestione operativa completa, configurazioni e supervisione', NOW()),
(2, 'Editor Manager', 'Gestione e pubblicazione dei contenuti e delle schede informative', NOW()),
(3, 'Operator Support', 'Visualizzazione dati, assistenza clienti e gestione ticket', NOW());

-- SVUOTA LA TABELLA DEI PERMESSI DEI GRUPPI PER EVITARE DUPLICATI
TRUNCATE TABLE `essential`.`admins_groups_permissions`;

-- 1. PERMESSI PER IL GRUPPO: Administrator (ID: 1) -> Gestione totale
insert into `essential`.`admins_groups_permissions` (`group_id`, `permission`) values
(1, 'brands_add'),
(1, 'brands_edit'),
(1, 'brands_show'),
(1, 'brands_show_all'),
(1, 'brands_delete'),
(1, 'brands_change_status'),
(1, 'categories_add'),
(1, 'categories_edit'),
(1, 'categories_show'),
(1, 'categories_show_all'),
(1, 'categories_delete'),
(1, 'categories_change_status'),
(1, 'products_add'),
(1, 'products_edit'),
(1, 'products_show'),
(1, 'products_show_all'),
(1, 'products_delete'),
(1, 'products_change_status');

-- 2. PERMESSI PER IL GRUPPO: Editor / Manager (ID: 2) -> Gestione contenuti, niente eliminazioni hard
insert into `essential`.`admins_groups_permissions` (`group_id`, `permission`) values
(2, 'brands_add'),
(2, 'brands_edit'),
(2, 'brands_show'),
(2, 'brands_show_all'),
(2, 'brands_change_status'),
(2, 'categories_add'),
(2, 'categories_edit'),
(2, 'categories_show'),
(2, 'categories_show_all'),
(2, 'categories_change_status'),
(2, 'products_add'),
(2, 'products_edit'),
(2, 'products_show'),
(2, 'products_show_all'),
(2, 'products_change_status');

-- 3. PERMESSI PER IL GRUPPO: Operator / Support (ID: 3) -> Solo visualizzazione base
insert into `essential`.`admins_groups_permissions` (`group_id`, `permission`) values
(3, 'brands_show'),
(3, 'brands_show_all'),
(3, 'categories_show'),
(3, 'categories_show_all'),
(3, 'products_show'),
(3, 'products_show_all');

insert into `essential`.`admins` (
    `id`, `uuid`, `firstname`, `lastname`, `email`, `phone`, `status`, `superadmin`, `group_id`, `password_hash`, `created_at`
) values (
    null, 
    'e4eaaaf2-d142-4f8b-8cf6-4d8a1fc27b31',
    'giorgio',
    'barone',
    'gbwebapps@gmail.com',
    '+393519081161',
    1,
    1,
    1,
    '$2y$10$gFNsG60FJ6rJhdlMtd0h6.gX.rSbYfjf7oxLpKqgcP5v6M/c6Ud62',
    NOW()
);

-- insert into `essential`.`admins_2fa` (
--     `id`, `admin_uuid`, `method`, `secret`, `enabled`
-- ) values
--     (1, 'e4eaaaf2-d142-4f8b-8cf6-4d8a1fc27b31', 'email', null, 1);

/* --- 1. POPOLAMENTO TABELLA ADMINS --- */
insert into `essential`.`admins` (
    `id`, `uuid`, `firstname`, `lastname`, `email`, `phone`, `status`, `superadmin`, `group_id`, `password_hash`, `created_at`
) values 
(null, '7c4d5b92-f1a8-4e36-9b54-cf210a48b3e9', 'Mario', 'Rossi', 'mario.rossi@example.com', '+393001234561', 1, 0, 2, '$2y$10$gFNsG60FJ6rJhdlMtd0h6.gX.rSbYfjf7oxLpKqgcP5v6M/c6Ud62', NOW()),
(null, 'a83f9e1c-5d2b-4681-ad4e-72bf05c1d63a', 'Giuseppe', 'Verdi', 'giuseppe.verdi@example.com', '+393001234562', 1, 0, 2, '$2y$10$gFNsG60FJ6rJhdlMtd0h6.gX.rSbYfjf7oxLpKqgcP5v6M/c6Ud62', NOW()),
(null, '3d6b2f8a-9c4e-4172-8b1a-d0f3c5e7b649', 'Francesca', 'Bianchi', 'francesca.bianchi@example.com', '+393001234563', 1, 0, 3, '$2y$10$gFNsG60FJ6rJhdlMtd0h6.gX.rSbYfjf7oxLpKqgcP5v6M/c6Ud62', NOW()),
(null, 'f2c5a1e8-73b9-4d62-9a4f-1c8b5d3e76fa', 'Alessandro', 'Ferrari', 'alessandro.ferrari@example.com', '+393001234564', 1, 0, 2, '$2y$10$gFNsG60FJ6rJhdlMtd0h6.gX.rSbYfjf7oxLpKqgcP5v6M/c6Ud62', NOW()),
(null, 'b0e3f7d1-c6a4-4893-bc2e-51da9f8b4c72', 'Anna', 'Russo', 'anna.russo@example.com', '+393001234565', 1, 0, 3, '$2y$10$gFNsG60FJ6rJhdlMtd0h6.gX.rSbYfjf7oxLpKqgcP5v6M/c6Ud62', NOW()),
(null, '6d2a8f4c-1e9b-4375-ae82-93b5c1d6f47a', 'Roberto', 'Esposito', 'roberto.esposito@example.com', '+393001234566', 1, 0, 2, '$2y$10$gFNsG60FJ6rJhdlMtd0h6.gX.rSbYfjf7oxLpKqgcP5v6M/c6Ud62', NOW()),
(null, 'c1f5b9d3-e2a7-4b61-8d4f-5c9a2e8b637d', 'Elena', 'Romano', 'elena.romano@example.com', '+393001234567', 1, 0, 3, '$2y$10$gFNsG60FJ6rJhdlMtd0h6.gX.rSbYfjf7oxLpKqgcP5v6M/c6Ud62', NOW()),
(null, '4e8b3c1d-fa62-4935-bc71-2e5a9f4b8d63', 'Stefano', 'Colombo', 'stefano.colombo@example.com', '+393001234568', 1, 0, 2, '$2y$10$gFNsG60FJ6rJhdlMtd0h6.gX.rSbYfjf7oxLpKqgcP5v6M/c6Ud62', NOW()),
(null, '9a5f2c8b-d3e1-4674-bf9e-1c7d5a3b62f4', 'Laura', 'Ricci', 'laura.ricci@example.com', '+393001234569', 1, 0, 3, '$2y$10$gFNsG60FJ6rJhdlMtd0h6.gX.rSbYfjf7oxLpKqgcP5v6M/c6Ud62', NOW()),
(null, '1b7d6e4a-c5f9-4283-9b4e-8d2a1c5f3b76', 'Giovanni', 'Marini', 'giovanni.marini@example.com', '+393001234570', 1, 0, 2, '$2y$10$gFNsG60FJ6rJhdlMtd0h6.gX.rSbYfjf7oxLpKqgcP5v6M/c6Ud62', NOW()),
(null, 'e3c9a5b7-2f1d-4862-ad7e-6b4c1f9e2a5d', 'Silvia', 'Greco', 'silvia.greco@example.com', '+393001234571', 1, 0, 3, '$2y$10$gFNsG60FJ6rJhdlMtd0h6.gX.rSbYfjf7oxLpKqgcP5v6M/c6Ud62', NOW()),
(null, '5f2a1d7c-e9b4-4361-8c5f-9d3a7b6e84fa', 'Fabio', 'Bruno', 'fabio.bruno@example.com', '+393001234572', 1, 0, 2, '$2y$10$gFNsG60FJ6rJhdlMtd0h6.gX.rSbYfjf7oxLpKqgcP5v6M/c6Ud62', NOW()),
(null, 'a6d4e2b8-f1c5-4973-bc8a-4d2e1f5b937c', 'Chiara', 'Gallo', 'chiara.gallo@example.com', '+393001234573', 1, 0, 3, '$2y$10$gFNsG60FJ6rJhdlMtd0h6.gX.rSbYfjf7oxLpKqgcP5v6M/c6Ud62', NOW()),
(null, '2b8c5e9f-7a4d-4162-9b3f-e8c5a1d7f642', 'Marco', 'Conti', 'marco.conti@example.com', '+393001234574', 1, 0, 2, '$2y$10$gFNsG60FJ6rJhdlMtd0h6.gX.rSbYfjf7oxLpKqgcP5v6M/c6Ud62', NOW()),
(null, 'fd3a7c1e-9b5f-4628-ad12-5e4c8a7b3f91', 'Giulia', 'De Luca', 'giulia.deluca@example.com', '+393001234575', 1, 0, 3, '$2y$10$gFNsG60FJ6rJhdlMtd0h6.gX.rSbYfjf7oxLpKqgcP5v6M/c6Ud62', NOW()),
(null, '7e1b5d9c-3a6f-4275-8b9e-c4a2f8d1e3b6', 'Davide', 'Costa', 'davide.costa@example.com', '+393001234576', 1, 0, 2, '$2y$10$gFNsG60FJ6rJhdlMtd0h6.gX.rSbYfjf7oxLpKqgcP5v6M/c6Ud62', NOW()),
(null, 'c4a2f8d1-e3b6-4927-bc5f-9e1b5d9c3a6f', 'Sara', 'Giordano', 'sara.giordano@example.com', '+393001234577', 1, 0, 3, '$2y$10$gFNsG60FJ6rJhdlMtd0h6.gX.rSbYfjf7oxLpKqgcP5v6M/c6Ud62', NOW()),
(null, '8b9e1c7d-5a3b-462f-ad4c-1e9b5f2a7c1e', 'Luca', 'Rizzo', 'luca.rizzo@example.com', '+393001234578', 1, 0, 2, '$2y$10$gFNsG60FJ6rJhdlMtd0h6.gX.rSbYfjf7oxLpKqgcP5v6M/c6Ud62', NOW()),
(null, '3a6f4275-8b9e-41c4-bc2f-d3e1b7d6e4a5', 'Martina', 'Moretti', 'martina.moretti@example.com', '+393001234579', 1, 0, 3, '$2y$10$gFNsG60FJ6rJhdlMtd0h6.gX.rSbYfjf7oxLpKqgcP5v6M/c6Ud62', NOW()),
(null, 'bc5f9e1b-5d9c-43a6-8f42-758b9e1c7d5a', 'Andrea', 'Mancini', 'andrea.mancini@example.com', '+393001234580', 1, 0, 2, '$2y$10$gFNsG60FJ6rJhdlMtd0h6.gX.rSbYfjf7oxLpKqgcP5v6M/c6Ud62', NOW());


/* --- 2. POPOLAMENTO TABELLA ADMINS_2FA --- */
insert into `essential`.`admins_2fa` (
    `admin_uuid`, `method`, `secret`, `enabled`
) values
('7c4d5b92-f1a8-4e36-9b54-cf210a48b3e9', 'email', null, 1),
('a83f9e1c-5d2b-4681-ad4e-72bf05c1d63a', 'email', null, 1),
('3d6b2f8a-9c4e-4172-8b1a-d0f3c5e7b649', 'email', null, 1),
('f2c5a1e8-73b9-4d62-9a4f-1c8b5d3e76fa', 'email', null, 1),
('b0e3f7d1-c6a4-4893-bc2e-51da9f8b4c72', 'email', null, 1),
('6d2a8f4c-1e9b-4375-ae82-93b5c1d6f47a', 'email', null, 1),
('c1f5b9d3-e2a7-4b61-8d4f-5c9a2e8b637d', 'email', null, 1),
('4e8b3c1d-fa62-4935-bc71-2e5a9f4b8d63', 'email', null, 1),
('9a5f2c8b-d3e1-4674-bf9e-1c7d5a3b62f4', 'email', null, 1),
('1b7d6e4a-c5f9-4283-9b4e-8d2a1c5f3b76', 'email', null, 1),
('e3c9a5b7-2f1d-4862-ad7e-6b4c1f9e2a5d', 'email', null, 1),
('5f2a1d7c-e9b4-4361-8c5f-9d3a7b6e84fa', 'email', null, 1),
('a6d4e2b8-f1c5-4973-bc8a-4d2e1f5b937c', 'email', null, 1),
('2b8c5e9f-7a4d-4162-9b3f-e8c5a1d7f642', 'email', null, 1),
('fd3a7c1e-9b5f-4628-ad12-5e4c8a7b3f91', 'email', null, 1),
('7e1b5d9c-3a6f-4275-8b9e-c4a2f8d1e3b6', 'email', null, 1),
('c4a2f8d1-e3b6-4927-bc5f-9e1b5d9c3a6f', 'email', null, 1),
('8b9e1c7d-5a3b-462f-ad4c-1e9b5f2a7c1e', 'email', null, 1),
('3a6f4275-8b9e-41c4-bc2f-d3e1b7d6e4a5', 'email', null, 1);
