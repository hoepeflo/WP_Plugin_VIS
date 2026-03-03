# VIS Plugin – Umsetzungsplan (Start)

## 1. Zielbild
Das WordPress-Plugin **VIS (Verbandsinformationssystem)** stellt Vereinsvertretern einen geschützten Portalbereich bereit und bindet Funktionsmodule (z. B. Bildungsportal, KidsCup) ein, die pro Verein/Benutzer granular freigeschaltet werden.

## 2. Phasenplan
1. **Plugin-Foundation (MVP)**
   - Plugin-Bootstrap, Rollen-/Rechte-Grundmodell
   - Geschützter Portalbereich via Shortcode `[vis_portal]`
   - Modulregister und globale Modulfreischaltung im Admin
2. **Identity & Rechte (V1)**
   - Rollenmodell (Vereinsvertreter, Vereinsadmin, Verbandsadmin)
   - Zuordnung Benutzer ↔ Verein
   - Rechte auf Modul- und Datensatzebene
3. **Migration Standalone Bildungsportal**
   - Fachlogik übernehmen
   - Datenmodell in WP-Struktur integrieren
   - UI und Workflows im VIS-Portal verfügbar machen
4. **Migration Standalone KidsCup**
   - Übernahme bestehender Funktionalität
   - Teilnehmer-/Melde- und Auswertungsprozesse integrieren
5. **Erweiterungsframework**
   - Modul-Schnittstellen standardisieren (Hooks/REST/Services)
   - Weitere Module ergänzen

## 3. Offene Fragen für die Grundfunktionalität
1. **Login-Flow**: Sollen Vereinsvertreter ausschließlich über WordPress-Accounts arbeiten oder wird externes SSO benötigt?
2. **Mandantenmodell**: Ist ein Benutzer genau einem Verein zugeordnet oder mehreren?
3. **Rechtegranularität**: Freigaben pro Benutzer, pro Verein oder beides?
4. **Datenhaltung**: Sollen Moduldaten in eigenen DB-Tabellen liegen oder als Custom Post Types/Meta?
5. **UI-Konzept**: Soll das Portal primär im Frontend (Shortcodes/Blocks) oder im WP-Backend genutzt werden?
6. **Migration**: Welche Branches/Commits der beiden Standalone-Repos gelten als führender Stand?
7. **Datenschutz/Revision**: Welche Audit-Logs und Aufbewahrungsfristen sind verpflichtend?
8. **Mehrsprachigkeit**: Deutsch-only oder perspektivisch i18n?

## 4. Bereits umgesetzter Start in diesem Repository
- Erstes Plugin-Grundgerüst unter `vis/`
- Rollen- und Capability-Initialisierung
- Modulregister für Bildungsportal und KidsCup
- Admin-Seite zur globalen Modulfreischaltung
- Portal-Shortcode zur Anzeige freigeschalteter Module

## 5. Nächste konkrete Schritte
1. Offene Fragen abstimmen und entscheiden
2. Vereinsdatenmodell festlegen (CPT vs. eigene Tabellen)
3. Benutzer-Vereins-Zuordnung inkl. Admin-Maske umsetzen
4. Modul-Berechtigungen pro Benutzer/Verein umsetzen
5. Bildungsportal als erstes echtes Fachmodul migrieren
