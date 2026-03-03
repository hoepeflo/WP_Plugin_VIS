CREATE TABLE IF NOT EXISTS vis_clubs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    number VARCHAR(50) DEFAULT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS vis_users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    login VARCHAR(120) NOT NULL UNIQUE,
    email VARCHAR(255) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    display_name VARCHAR(255) NOT NULL,
    club_id BIGINT UNSIGNED DEFAULT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_vis_users_club FOREIGN KEY (club_id) REFERENCES vis_clubs(id)
);

CREATE TABLE IF NOT EXISTS vis_roles (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    role_key VARCHAR(120) NOT NULL UNIQUE,
    label VARCHAR(255) NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1
);

CREATE TABLE IF NOT EXISTS vis_permissions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    permission_key VARCHAR(120) NOT NULL UNIQUE,
    label VARCHAR(255) NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1
);

CREATE TABLE IF NOT EXISTS vis_user_roles (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    role_id BIGINT UNSIGNED NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    UNIQUE KEY uniq_user_role (user_id, role_id),
    CONSTRAINT fk_vis_user_roles_user FOREIGN KEY (user_id) REFERENCES vis_users(id),
    CONSTRAINT fk_vis_user_roles_role FOREIGN KEY (role_id) REFERENCES vis_roles(id)
);

CREATE TABLE IF NOT EXISTS vis_role_permissions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    role_id BIGINT UNSIGNED NOT NULL,
    permission_key VARCHAR(120) NOT NULL,
    is_granted TINYINT(1) NOT NULL DEFAULT 1,
    UNIQUE KEY uniq_role_perm (role_id, permission_key),
    CONSTRAINT fk_vis_role_permissions_role FOREIGN KEY (role_id) REFERENCES vis_roles(id)
);

CREATE TABLE IF NOT EXISTS vis_user_permissions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    permission_key VARCHAR(120) NOT NULL,
    is_granted TINYINT(1) NOT NULL DEFAULT 1,
    UNIQUE KEY uniq_user_perm (user_id, permission_key),
    CONSTRAINT fk_vis_user_permissions_user FOREIGN KEY (user_id) REFERENCES vis_users(id)
);

CREATE TABLE IF NOT EXISTS vis_modules (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    module_key VARCHAR(120) NOT NULL UNIQUE,
    label VARCHAR(255) NOT NULL,
    description TEXT,
    required_permission VARCHAR(120) DEFAULT NULL,
    is_enabled TINYINT(1) NOT NULL DEFAULT 1,
    sort_order INT NOT NULL DEFAULT 100
);

CREATE TABLE IF NOT EXISTS vis_user_modules (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    module_id BIGINT UNSIGNED NOT NULL,
    is_enabled TINYINT(1) NOT NULL DEFAULT 1,
    UNIQUE KEY uniq_user_module (user_id, module_id),
    CONSTRAINT fk_vis_user_modules_user FOREIGN KEY (user_id) REFERENCES vis_users(id),
    CONSTRAINT fk_vis_user_modules_module FOREIGN KEY (module_id) REFERENCES vis_modules(id)
);

CREATE TABLE IF NOT EXISTS vis_audit_log (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    actor_wp_user_id BIGINT UNSIGNED NULL,
    action VARCHAR(120) NOT NULL,
    entity_type VARCHAR(120) NOT NULL,
    entity_id BIGINT NOT NULL DEFAULT 0,
    details_json LONGTEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_vis_audit_action (action),
    KEY idx_vis_audit_entity (entity_type, entity_id),
    KEY idx_vis_audit_created (created_at)
);

CREATE TABLE IF NOT EXISTS vis_education_offers (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    offer_date DATE NOT NULL,
    location VARCHAR(255) DEFAULT '',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS vis_education_registrations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    external_user_id BIGINT UNSIGNED NOT NULL,
    offer_id BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_user_offer (external_user_id, offer_id),
    CONSTRAINT fk_vis_education_registrations_user FOREIGN KEY (external_user_id) REFERENCES vis_users(id),
    CONSTRAINT fk_vis_education_registrations_offer FOREIGN KEY (offer_id) REFERENCES vis_education_offers(id)
);
