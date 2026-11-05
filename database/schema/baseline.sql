SET FOREIGN_KEY_CHECKS=0;

CREATE TABLE `admin_role_permissions` (
  `role_id` int(10) unsigned NOT NULL,
  `permission` varchar(80) NOT NULL,
  PRIMARY KEY (`role_id`,`permission`),
  CONSTRAINT `admin_role_permissions_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `admin_roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE `admin_roles` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` varchar(500) NOT NULL DEFAULT '',
  `is_super` tinyint(4) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE `admins` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `email` varchar(190) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `name` varchar(100) NOT NULL,
  `role_id` int(10) unsigned DEFAULT NULL,
  `is_active` tinyint(4) NOT NULL DEFAULT 1,
  `last_login_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `admins_role_fk` (`role_id`),
  CONSTRAINT `admins_role_fk` FOREIGN KEY (`role_id`) REFERENCES `admin_roles` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE `blog_categories` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(180) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `blog_posts` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(180) COLLATE utf8mb4_unicode_ci NOT NULL,
  `excerpt` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `content` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `cover` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `cover_alt` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `category_id` int(10) unsigned DEFAULT NULL,
  `tags` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `author` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `status` enum('draft','published','trash') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `seo_title` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `seo_description` varchar(300) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `published_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `blog_public` (`status`,`published_at`),
  KEY `blog_category_fk` (`category_id`),
  CONSTRAINT `blog_category_fk` FOREIGN KEY (`category_id`) REFERENCES `blog_categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `blog_slugs` (
  `slug` varchar(180) COLLATE utf8mb4_unicode_ci NOT NULL,
  `post_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`slug`),
  KEY `blog_slug_post_fk` (`post_id`),
  CONSTRAINT `blog_slug_post_fk` FOREIGN KEY (`post_id`) REFERENCES `blog_posts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `booking_events` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `booking_id` int(10) unsigned NOT NULL,
  `message` varchar(1000) NOT NULL,
  `actor_id` int(10) unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `booking_id` (`booking_id`),
  CONSTRAINT `booking_events_ibfk_1` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE `booking_passports` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `booking_id` int(10) unsigned NOT NULL,
  `traveler_number` int(10) unsigned NOT NULL,
  `file_name` varchar(80) NOT NULL,
  `uploaded_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `booking_traveler` (`booking_id`,`traveler_number`),
  CONSTRAINT `booking_passports_ibfk_1` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE `bookings` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `reference` varchar(30) NOT NULL,
  `tour_id` int(10) unsigned NOT NULL,
  `tour_title` varchar(200) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `group_size` smallint(5) unsigned NOT NULL,
  `price_per_person` decimal(12,2) NOT NULL,
  `total_price` decimal(14,2) NOT NULL,
  `customer_name` varchar(120) NOT NULL,
  `phone` varchar(30) NOT NULL,
  `email` varchar(190) NOT NULL DEFAULT '',
  `line_id` varchar(100) NOT NULL DEFAULT '',
  `note` text NOT NULL,
  `status` enum('new','contacted','confirmed','cancelled') NOT NULL DEFAULT 'new',
  `admin_note` text NOT NULL,
  `consent_at` datetime NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  `request_token` varchar(64) NOT NULL,
  `traveler_count` smallint(5) unsigned DEFAULT NULL,
  `departure_id` int(10) unsigned DEFAULT NULL,
  `room_type` enum('twin','double','single','none','mixed','triple') DEFAULT NULL,
  `single_supplement` decimal(12,2) NOT NULL DEFAULT 0.00,
  `supplement_total` decimal(14,2) NOT NULL DEFAULT 0.00,
  `twin_rooms` smallint(5) unsigned DEFAULT NULL,
  `double_rooms` smallint(5) unsigned DEFAULT NULL,
  `single_rooms` smallint(5) unsigned DEFAULT NULL,
  `access_token` char(64) DEFAULT NULL,
  `triple_rooms` smallint(5) unsigned DEFAULT NULL,
  `package_snapshot` mediumtext DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `reference` (`reference`),
  UNIQUE KEY `request_token` (`request_token`),
  UNIQUE KEY `booking_access` (`access_token`),
  KEY `queue` (`status`,`created_at`),
  KEY `tour_id` (`tour_id`),
  KEY `departure_status` (`departure_id`,`status`),
  CONSTRAINT `bookings_departure_fk` FOREIGN KEY (`departure_id`) REFERENCES `departures` (`id`),
  CONSTRAINT `bookings_ibfk_1` FOREIGN KEY (`tour_id`) REFERENCES `tours` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE `departure_prices` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `departure_id` int(10) unsigned NOT NULL,
  `group_size` smallint(5) unsigned NOT NULL,
  `price_per_person` decimal(12,2) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `group_rate` (`departure_id`,`group_size`),
  CONSTRAINT `departure_prices_ibfk_1` FOREIGN KEY (`departure_id`) REFERENCES `departures` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE `departures` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `tour_id` int(10) unsigned NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `active` tinyint(4) NOT NULL DEFAULT 1,
  `capacity` smallint(5) unsigned DEFAULT NULL,
  `price_per_person` decimal(12,2) DEFAULT NULL,
  `single_supplement` decimal(12,2) DEFAULT NULL,
  `deposit_enabled` tinyint(4) NOT NULL DEFAULT 0,
  `deposit_cutoff_days` int(11) NOT NULL DEFAULT 30,
  `balance_due_days` int(11) NOT NULL DEFAULT 14,
  `private_rates` mediumtext DEFAULT NULL,
  `lead_days` int(11) NOT NULL DEFAULT 0,
  `blackout_dates` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tour_id` (`tour_id`),
  KEY `date_search` (`start_date`,`active`),
  CONSTRAINT `departures_ibfk_1` FOREIGN KEY (`tour_id`) REFERENCES `tours` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE `destination_nodes` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `kind` enum('zone','country','route') NOT NULL,
  `parent_id` int(10) unsigned DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `slug` varchar(150) NOT NULL,
  `description` text NOT NULL,
  `meta_title` varchar(200) NOT NULL DEFAULT '',
  `meta_description` varchar(300) NOT NULL DEFAULT '',
  `meta_keywords` varchar(300) NOT NULL DEFAULT '',
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  `flag_path` varchar(255) NOT NULL DEFAULT '',
  `source_pdf` varchar(255) NOT NULL DEFAULT '',
  `onpage_content` mediumtext DEFAULT NULL,
  `cover_path` varchar(255) NOT NULL DEFAULT '',
  `cover_alt` varchar(200) NOT NULL DEFAULT '',
  `image_suggestions` mediumtext DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `destination_slug` (`kind`,`slug`),
  KEY `destination_parent` (`parent_id`,`sort_order`),
  CONSTRAINT `destination_nodes_ibfk_1` FOREIGN KEY (`parent_id`) REFERENCES `destination_nodes` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE `gallery_albums` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(180) COLLATE utf8mb4_unicode_ci NOT NULL,
  `category` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `location` varchar(180) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('draft','published') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `sort_order` int(10) unsigned NOT NULL DEFAULT 0,
  `revision` int(10) unsigned NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `gallery_listing` (`status`,`sort_order`,`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `gallery_photos` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `album_id` int(10) unsigned NOT NULL,
  `path` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `caption` varchar(250) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `sort_order` int(10) unsigned NOT NULL DEFAULT 0,
  `is_cover` tinyint(3) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `gallery_photo_order` (`album_id`,`sort_order`,`id`),
  CONSTRAINT `gallery_photos_ibfk_1` FOREIGN KEY (`album_id`) REFERENCES `gallery_albums` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `invoices` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `booking_id` int(10) unsigned NOT NULL,
  `reference` varchar(40) NOT NULL,
  `snapshot` longtext NOT NULL,
  `total_amount` decimal(14,2) NOT NULL,
  `deposit_amount` decimal(14,2) NOT NULL,
  `deposit_enabled` tinyint(4) NOT NULL DEFAULT 0,
  `deposit_cutoff_days` int(11) NOT NULL DEFAULT 30,
  `payment_due_at` datetime NOT NULL,
  `balance_due_at` datetime NOT NULL,
  `issued_at` datetime NOT NULL,
  `issued_by` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `booking_id` (`booking_id`),
  UNIQUE KEY `reference` (`reference`),
  CONSTRAINT `invoices_ibfk_1` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE `login_attempts` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `ip` varchar(64) NOT NULL,
  `attempted_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `throttle` (`ip`,`attempted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE `package_levels` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE `payment_slip_claims` (
  `slip_hash` char(64) NOT NULL,
  `payment_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`slip_hash`),
  UNIQUE KEY `payment_id` (`payment_id`),
  CONSTRAINT `payment_slip_claims_ibfk_1` FOREIGN KEY (`payment_id`) REFERENCES `payments` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE `payments` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `booking_id` int(10) unsigned NOT NULL,
  `reference` varchar(40) NOT NULL,
  `kind` varchar(20) NOT NULL,
  `expected_amount` decimal(14,2) NOT NULL,
  `reported_amount` decimal(14,2) DEFAULT NULL,
  `status` enum('awaiting_upload','pending','approved','rejected','expired','cancelled') NOT NULL,
  `account_snapshot` text NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` datetime NOT NULL,
  `transferred_at` datetime DEFAULT NULL,
  `channel` varchar(30) DEFAULT NULL,
  `submitted_at` datetime DEFAULT NULL,
  `slip_path` varchar(255) DEFAULT NULL,
  `slip_hash` char(64) DEFAULT NULL,
  `review_note` text DEFAULT NULL,
  `reviewed_by` int(10) unsigned DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `receipt_reference` varchar(40) DEFAULT NULL,
  `receipt_snapshot` longtext DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `reference` (`reference`),
  UNIQUE KEY `receipt_reference` (`receipt_reference`),
  KEY `booking_status` (`booking_id`,`status`),
  KEY `slip_lookup` (`slip_hash`,`status`),
  CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE `settings` (
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text NOT NULL,
  PRIMARY KEY (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE `tour_code_aliases` (
  `code` varchar(30) NOT NULL,
  `tour_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`code`),
  KEY `tour_id` (`tour_id`),
  CONSTRAINT `tour_code_aliases_ibfk_1` FOREIGN KEY (`tour_id`) REFERENCES `tours` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE `tour_days` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `tour_id` int(10) unsigned NOT NULL,
  `day_number` smallint(5) unsigned NOT NULL,
  `title` varchar(200) NOT NULL,
  `description` text NOT NULL,
  `hotel` varchar(200) NOT NULL DEFAULT '',
  `breakfast` tinyint(4) DEFAULT 0,
  `lunch` tinyint(4) DEFAULT 0,
  `dinner` tinyint(4) DEFAULT 0,
  `teaser` varchar(300) NOT NULL DEFAULT '',
  `image_path` varchar(255) NOT NULL DEFAULT '',
  `image_caption` varchar(200) NOT NULL DEFAULT '',
  `images_json` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tour_day` (`tour_id`,`day_number`),
  CONSTRAINT `tour_days_ibfk_1` FOREIGN KEY (`tour_id`) REFERENCES `tours` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE `tour_images` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `tour_id` int(10) unsigned NOT NULL,
  `path` varchar(255) NOT NULL,
  `caption` varchar(200) NOT NULL DEFAULT '',
  `sort_order` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `tour_id` (`tour_id`),
  CONSTRAINT `tour_images_ibfk_1` FOREIGN KEY (`tour_id`) REFERENCES `tours` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE `tour_routes` (
  `tour_id` int(10) unsigned NOT NULL,
  `route_id` int(10) unsigned NOT NULL,
  `sort_order` smallint(5) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`tour_id`,`route_id`),
  KEY `tour_route_order` (`tour_id`,`sort_order`),
  KEY `route_id` (`route_id`),
  CONSTRAINT `tour_routes_ibfk_1` FOREIGN KEY (`tour_id`) REFERENCES `tours` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tour_routes_ibfk_2` FOREIGN KEY (`route_id`) REFERENCES `destination_nodes` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE `tours` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(30) NOT NULL,
  `slug` varchar(190) NOT NULL,
  `title` varchar(200) NOT NULL,
  `destination` varchar(100) NOT NULL,
  `days` smallint(5) unsigned NOT NULL DEFAULT 1,
  `nights` smallint(5) unsigned NOT NULL DEFAULT 0,
  `summary` text NOT NULL,
  `highlights` text NOT NULL,
  `cover` varchar(255) NOT NULL DEFAULT '',
  `hotel_status` enum('included','extra','none') DEFAULT 'included',
  `hotel_details` text NOT NULL,
  `transport_status` enum('included','extra','none') DEFAULT 'included',
  `transport_details` text NOT NULL,
  `guide_status` enum('included','extra','none') DEFAULT 'included',
  `guide_details` text NOT NULL,
  `photographer_status` enum('included','extra','none') DEFAULT 'included',
  `photographer_details` text NOT NULL,
  `exclusions` text NOT NULL,
  `terms` text NOT NULL,
  `status` enum('draft','published','archived') NOT NULL DEFAULT 'draft',
  `featured` tinyint(4) NOT NULL DEFAULT 0,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `seo_description` varchar(300) NOT NULL DEFAULT '',
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  `deposit_per_person` decimal(12,2) NOT NULL DEFAULT 0.00,
  `route_id` int(10) unsigned DEFAULT NULL,
  `seo_title` varchar(200) NOT NULL DEFAULT '',
  `seo_keywords` varchar(300) NOT NULL DEFAULT '',
  `program_type` varchar(20) NOT NULL DEFAULT 'fixed',
  `visual_highlights` text DEFAULT NULL,
  `cover_position` varchar(20) NOT NULL DEFAULT 'center',
  `airline` varchar(255) NOT NULL DEFAULT '',
  `source_pdf` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`),
  UNIQUE KEY `slug` (`slug`),
  KEY `listing` (`status`,`featured`,`sort_order`),
  KEY `tours_destination_route` (`route_id`),
  CONSTRAINT `tours_destination_route` FOREIGN KEY (`route_id`) REFERENCES `destination_nodes` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS=1;
