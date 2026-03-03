# VIS Externes Datenbankschema (Startpunkt)

Dieses Schema dient als **homogene Benutzerverwaltung** für alle Clients (WordPress-Web, iOS, Android).

## Kernprinzip
- Eine zentrale Benutzerstruktur (`users`) für alle Benutzerarten.
- Rollen und Rechte werden über relationale Zuordnungen vergeben.
- Modulzugriff erfolgt nur, wenn **Modulzuweisung** und **Berechtigung** vorhanden sind.

## Tabellen (empfohlen)
1. `vis_users`
   - `id`, `login`, `email`, `password_hash`, `display_name`, `club_id`, `status`, `created_at`, `updated_at`
2. `vis_clubs`
   - `id`, `name`, `number`, `status`
3. `vis_roles`
   - `id`, `role_key`, `label`, `is_active`
4. `vis_user_roles`
   - `id`, `user_id`, `role_id`, `is_active`
5. `vis_permissions`
   - `id`, `permission_key`, `label`, `is_active`
6. `vis_role_permissions`
   - `id`, `role_id`, `permission_key`, `is_granted`
7. `vis_user_permissions`
   - `id`, `user_id`, `permission_key`, `is_granted`
8. `vis_modules`
   - `id`, `module_key`, `label`, `description`, `required_permission`, `is_enabled`, `sort_order`
9. `vis_user_modules`
   - `id`, `user_id`, `module_id`, `is_enabled`
10. `vis_audit_log`
   - `id`, `actor_wp_user_id`, `action`, `entity_type`, `entity_id`, `details_json`, `created_at`
11. `vis_education_offers`
   - `id`, `title`, `offer_date`, `location`, `is_active`, `created_at`, `updated_at`
12. `vis_education_registrations`
   - `id`, `external_user_id`, `offer_id`, `created_at`

## Zugriffslogik
Ein Benutzer sieht ein Modul nur dann, wenn:
1. in `vis_user_modules` ein aktiver Eintrag vorhanden ist, und
2. in `vis_modules.required_permission` entweder kein Recht gefordert ist oder das Recht über
   - `vis_user_permissions` (direkt) oder
   - `vis_role_permissions` via `vis_user_roles` (vererbt)
   erteilt wurde.


## SQL-Startschema
Ein initiales SQL-Setup ist unter `docs/sql/vis_external_schema.sql` hinterlegt.


## Betrieb über Plugin
Die Einrichtung und Aktualisierung des Schemas erfolgt über das Plugin-Backend:
- `Einstellungen > VIS Einstellungen`
- Aktion: **Schema einrichten/aktualisieren**

Der Plugin-Stand verwaltet eine interne Schema-Version und führt die SQL-Migration gegen die konfigurierte externe Datenbank aus.


Audit-Logging dient der Nachvollziehbarkeit von Rechte-, Rollen- und Moduländerungen.
