-- Wert auf Fundament CMS – initial schema (MySQL 5.7+/8, MariaDB 10.4+)
-- Every statement ends with ";" at the end of a line (required by the migrator).

CREATE TABLE admins (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(190) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  role VARCHAR(20) NOT NULL DEFAULT 'editor',
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  last_login_at DATETIME NULL,
  password_changed_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_admins_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE password_resets (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  admin_id INT UNSIGNED NOT NULL,
  token_hash CHAR(64) NOT NULL,
  expires_at DATETIME NOT NULL,
  used_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_password_resets_token (token_hash),
  CONSTRAINT fk_password_resets_admin FOREIGN KEY (admin_id) REFERENCES admins (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE rate_limits (
  bucket VARCHAR(40) NOT NULL,
  key_hash CHAR(64) NOT NULL,
  hits INT UNSIGNED NOT NULL DEFAULT 0,
  window_start DATETIME NOT NULL,
  PRIMARY KEY (bucket, key_hash),
  KEY idx_rate_limits_window (window_start)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE settings (
  setting_key VARCHAR(80) NOT NULL,
  value TEXT NULL,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE media (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  path VARCHAR(80) NOT NULL COMMENT 'yyyy/mm/<24 hex> without size suffix',
  ext VARCHAR(5) NOT NULL,
  sizes VARCHAR(60) NOT NULL COMMENT 'comma list of generated variants with widths, e.g. sm:480,md:960',
  original_name VARCHAR(190) NOT NULL,
  mime VARCHAR(40) NOT NULL,
  bytes INT UNSIGNED NOT NULL DEFAULT 0,
  width INT UNSIGNED NOT NULL,
  height INT UNSIGNED NOT NULL,
  alt VARCHAR(255) NOT NULL DEFAULT '',
  caption VARCHAR(255) NOT NULL DEFAULT '',
  credit VARCHAR(255) NOT NULL DEFAULT '',
  license VARCHAR(80) NOT NULL DEFAULT '',
  source_url VARCHAR(500) NOT NULL DEFAULT '',
  is_placeholder TINYINT(1) NOT NULL DEFAULT 0,
  uploaded_by INT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_media_path (path),
  KEY idx_media_created (created_at),
  CONSTRAINT fk_media_admin FOREIGN KEY (uploaded_by) REFERENCES admins (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE galleries (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(160) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE gallery_items (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  gallery_id INT UNSIGNED NOT NULL,
  media_id INT UNSIGNED NOT NULL,
  caption VARCHAR(255) NOT NULL DEFAULT '',
  sort_order INT NOT NULL DEFAULT 0,
  PRIMARY KEY (id),
  KEY idx_gallery_items_order (gallery_id, sort_order),
  CONSTRAINT fk_gallery_items_gallery FOREIGN KEY (gallery_id) REFERENCES galleries (id) ON DELETE CASCADE,
  CONSTRAINT fk_gallery_items_media FOREIGN KEY (media_id) REFERENCES media (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE pages (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  slug VARCHAR(120) NOT NULL,
  title VARCHAR(190) NOT NULL,
  template VARCHAR(20) NOT NULL DEFAULT 'standard',
  status VARCHAR(12) NOT NULL DEFAULT 'draft',
  published_at DATETIME NULL,
  meta_title VARCHAR(190) NOT NULL DEFAULT '',
  meta_description VARCHAR(320) NOT NULL DEFAULT '',
  og_media_id INT UNSIGNED NULL,
  noindex TINYINT(1) NOT NULL DEFAULT 0,
  system_key VARCHAR(30) NULL,
  updated_by INT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_pages_slug (slug),
  UNIQUE KEY uq_pages_system (system_key),
  KEY idx_pages_status (status, published_at),
  CONSTRAINT fk_pages_og FOREIGN KEY (og_media_id) REFERENCES media (id) ON DELETE SET NULL,
  CONSTRAINT fk_pages_admin FOREIGN KEY (updated_by) REFERENCES admins (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE services (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  parent_id INT UNSIGNED NULL,
  slug VARCHAR(120) NOT NULL,
  title VARCHAR(190) NOT NULL,
  teaser TEXT NULL,
  intro TEXT NULL,
  icon VARCHAR(40) NOT NULL DEFAULT '',
  image_media_id INT UNSIGNED NULL,
  status VARCHAR(12) NOT NULL DEFAULT 'draft',
  is_featured TINYINT(1) NOT NULL DEFAULT 0,
  sort_order INT NOT NULL DEFAULT 0,
  meta_title VARCHAR(190) NOT NULL DEFAULT '',
  meta_description VARCHAR(320) NOT NULL DEFAULT '',
  admin_note VARCHAR(500) NOT NULL DEFAULT '',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_services_slug (slug),
  KEY idx_services_parent (parent_id, sort_order),
  KEY idx_services_status (status),
  CONSTRAINT fk_services_parent FOREIGN KEY (parent_id) REFERENCES services (id) ON DELETE RESTRICT,
  CONSTRAINT fk_services_image FOREIGN KEY (image_media_id) REFERENCES media (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE service_relations (
  service_id INT UNSIGNED NOT NULL,
  related_id INT UNSIGNED NOT NULL,
  PRIMARY KEY (service_id, related_id),
  CONSTRAINT fk_service_rel_a FOREIGN KEY (service_id) REFERENCES services (id) ON DELETE CASCADE,
  CONSTRAINT fk_service_rel_b FOREIGN KEY (related_id) REFERENCES services (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE content_blocks (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  page_id INT UNSIGNED NULL,
  service_id INT UNSIGNED NULL,
  library_name VARCHAR(160) NULL COMMENT 'set for reusable library blocks (page_id and service_id are NULL)',
  type VARCHAR(40) NOT NULL,
  data LONGTEXT NOT NULL,
  sort_order INT NOT NULL DEFAULT 0,
  is_visible TINYINT(1) NOT NULL DEFAULT 1,
  admin_note VARCHAR(500) NOT NULL DEFAULT '',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_blocks_page (page_id, sort_order),
  KEY idx_blocks_service (service_id, sort_order),
  KEY idx_blocks_library (library_name),
  CONSTRAINT fk_blocks_page FOREIGN KEY (page_id) REFERENCES pages (id) ON DELETE CASCADE,
  CONSTRAINT fk_blocks_service FOREIGN KEY (service_id) REFERENCES services (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE content_block_media (
  block_id INT UNSIGNED NOT NULL,
  media_id INT UNSIGNED NOT NULL,
  PRIMARY KEY (block_id, media_id),
  KEY idx_cbm_media (media_id),
  CONSTRAINT fk_cbm_block FOREIGN KEY (block_id) REFERENCES content_blocks (id) ON DELETE CASCADE,
  CONSTRAINT fk_cbm_media FOREIGN KEY (media_id) REFERENCES media (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE page_revisions (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  page_id INT UNSIGNED NOT NULL,
  admin_id INT UNSIGNED NULL,
  note VARCHAR(190) NOT NULL DEFAULT '',
  snapshot LONGTEXT NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_revisions_page (page_id, created_at),
  CONSTRAINT fk_revisions_page FOREIGN KEY (page_id) REFERENCES pages (id) ON DELETE CASCADE,
  CONSTRAINT fk_revisions_admin FOREIGN KEY (admin_id) REFERENCES admins (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE projects (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  slug VARCHAR(120) NOT NULL,
  title VARCHAR(190) NOT NULL,
  service_id INT UNSIGNED NULL,
  location VARCHAR(160) NOT NULL DEFAULT '',
  period VARCHAR(80) NOT NULL DEFAULT '',
  summary TEXT NULL,
  body LONGTEXT NULL,
  cover_media_id INT UNSIGNED NULL,
  gallery_id INT UNSIGNED NULL,
  status VARCHAR(12) NOT NULL DEFAULT 'draft',
  is_featured TINYINT(1) NOT NULL DEFAULT 0,
  sort_order INT NOT NULL DEFAULT 0,
  meta_title VARCHAR(190) NOT NULL DEFAULT '',
  meta_description VARCHAR(320) NOT NULL DEFAULT '',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_projects_slug (slug),
  KEY idx_projects_status (status, sort_order),
  CONSTRAINT fk_projects_service FOREIGN KEY (service_id) REFERENCES services (id) ON DELETE SET NULL,
  CONSTRAINT fk_projects_cover FOREIGN KEY (cover_media_id) REFERENCES media (id) ON DELETE RESTRICT,
  CONSTRAINT fk_projects_gallery FOREIGN KEY (gallery_id) REFERENCES galleries (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE nav_items (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  menu VARCHAR(20) NOT NULL,
  parent_id INT UNSIGNED NULL,
  label VARCHAR(120) NOT NULL,
  link_type VARCHAR(12) NOT NULL DEFAULT 'page',
  page_id INT UNSIGNED NULL,
  service_id INT UNSIGNED NULL,
  url VARCHAR(500) NOT NULL DEFAULT '',
  auto_children TINYINT(1) NOT NULL DEFAULT 0,
  new_tab TINYINT(1) NOT NULL DEFAULT 0,
  is_visible TINYINT(1) NOT NULL DEFAULT 1,
  sort_order INT NOT NULL DEFAULT 0,
  PRIMARY KEY (id),
  KEY idx_nav_menu (menu, parent_id, sort_order),
  CONSTRAINT fk_nav_parent FOREIGN KEY (parent_id) REFERENCES nav_items (id) ON DELETE CASCADE,
  CONSTRAINT fk_nav_page FOREIGN KEY (page_id) REFERENCES pages (id) ON DELETE RESTRICT,
  CONSTRAINT fk_nav_service FOREIGN KEY (service_id) REFERENCES services (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE faq_categories (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(120) NOT NULL,
  sort_order INT NOT NULL DEFAULT 0,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE faqs (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  category_id INT UNSIGNED NULL,
  question VARCHAR(300) NOT NULL,
  answer TEXT NOT NULL,
  status VARCHAR(12) NOT NULL DEFAULT 'draft',
  sort_order INT NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_faqs_cat (category_id, status, sort_order),
  CONSTRAINT fk_faqs_category FOREIGN KEY (category_id) REFERENCES faq_categories (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE enquiries (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(190) NOT NULL,
  phone VARCHAR(40) NOT NULL DEFAULT '',
  project_type VARCHAR(120) NOT NULL DEFAULT '',
  preferred_contact VARCHAR(12) NOT NULL DEFAULT '',
  location VARCHAR(120) NOT NULL DEFAULT '',
  message TEXT NOT NULL,
  status VARCHAR(12) NOT NULL DEFAULT 'new',
  mail_sent TINYINT(1) NOT NULL DEFAULT 0,
  privacy_ack_at DATETIME NOT NULL,
  handled_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_enquiries_status (status, created_at),
  KEY idx_enquiries_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE enquiry_notes (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  enquiry_id INT UNSIGNED NOT NULL,
  admin_id INT UNSIGNED NULL,
  note TEXT NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_enquiry_notes (enquiry_id),
  CONSTRAINT fk_enquiry_notes_enquiry FOREIGN KEY (enquiry_id) REFERENCES enquiries (id) ON DELETE CASCADE,
  CONSTRAINT fk_enquiry_notes_admin FOREIGN KEY (admin_id) REFERENCES admins (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE audit_log (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  admin_id INT UNSIGNED NULL,
  admin_name VARCHAR(120) NOT NULL DEFAULT '',
  action VARCHAR(60) NOT NULL,
  entity_type VARCHAR(40) NOT NULL DEFAULT '',
  entity_id INT UNSIGNED NULL,
  summary VARCHAR(255) NOT NULL DEFAULT '',
  ip_anon VARCHAR(45) NOT NULL DEFAULT '',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_audit_created (created_at),
  KEY idx_audit_action (action),
  CONSTRAINT fk_audit_admin FOREIGN KEY (admin_id) REFERENCES admins (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
