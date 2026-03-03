# WP_Plugin_VIS

Dieses Repository enthält den Start für das WordPress-Plugin **VIS (Verbandsinformationssystem)**.

## Architekturgrundsatz
VIS nutzt **keine WordPress-Benutzer und kein WordPress-Fachdatenmodell** für die Geschäftslogik.
Stattdessen greift das Plugin auf eine **externe MySQL-Datenbank** zu, damit dieselbe Daten- und Fachlogik parallel für Web (WordPress), iOS und Android genutzt werden kann.

## Aktueller Stand
- Plugin-Basis unter `vis/`
- Externe DB-Konfiguration über WordPress-Admin (`Einstellungen > VIS Einstellungen`)
- Eigener VIS-Login im Frontend über `[vis_portal]` (nicht WP-Login)
- Homogene externe Benutzerstruktur (`users`) für alle Clients
- Granulares Rechtesystem über Rollen- und Benutzerrechte für Modulzugriffe
- Modulauflistung pro externem Benutzer inkl. Rechteprüfung
- Admin-Maske `VIS Benutzerrechte` für direkte Rechte- und Modulzuweisungen pro Benutzer
- Admin-Maske `VIS Rollenverwaltung` für Rollen, Rollenrechte und Benutzer-Rollen
- Admin-Maske `VIS Audit-Log` für revisionssichere Nachvollziehbarkeit von Änderungen
- Erster Integrationsstart für Bildungsportal (Angebotsliste + Anmeldung)

## Migration bestehender Lösungen
Für **Bildungsportal** und **KidsCup** werden aus den bestehenden GitHub-Repositories
- die **Funktionalität** und
- die **Datenbankstrukturen**
übernommen und in die VIS-Architektur integriert.

Es wird **nicht** angestrebt, die Repository-Codes unverändert 1:1 zu kopieren.

Weitere Planung und offene Fragen: `docs/vis-roadmap.md`.
DB-Schema-Startpunkt: `docs/external-db-schema.md`.
Initiales SQL-Schema: `docs/sql/vis_external_schema.sql`.


## Installation & DB-Einrichtung
1. Plugin in WordPress installieren und aktivieren.
2. In **Einstellungen > VIS Einstellungen** die externe DB-Verbindung eintragen und speichern.
3. **Externe DB-Verbindung testen** ausführen.
4. **Schema einrichten/aktualisieren** ausführen, damit das Plugin die benötigten Tabellen automatisch erstellt bzw. aktualisiert.

Bei späteren Plugin-Updates führt das Plugin zusätzlich beim Admin-Start einen automatischen Migrationscheck aus; die manuelle Aktion bleibt verfügbar.
