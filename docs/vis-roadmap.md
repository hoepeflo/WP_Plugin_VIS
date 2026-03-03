# VIS Plugin – Umsetzungsplan (aktualisiert)

## 1. Zielbild
Das WordPress-Plugin **VIS (Verbandsinformationssystem)** dient als Weboberfläche und nutzt eine **externe MySQL-Datenbank** als zentrale Datenquelle für Benutzer, Berechtigungen und Fachmodule.
Damit bleibt die Fachlogik identisch und wiederverwendbar für WordPress-Weboberfläche sowie spätere iOS- und Android-App.

## 2. Architekturprinzipien (verbindlich)
1. **Kein WordPress-Usermodell für Fachzugang**
   - Login und Session für VIS erfolgen über externe Benutzerdaten.
2. **Kein WordPress-Fachdatenmodell als Quelle der Wahrheit**
   - Fachdaten liegen in externer DB.
3. **Homogene Benutzerverwaltung**
   - Eine zentrale Benutzerstruktur für alle Kanäle (Web, iOS, Android).
4. **Granulares Rechtesystem**
   - Zugriff auf Module (z. B. KidsCup, Bildung) erfolgt über Rollen- und Einzelrechte.
5. **Funktions- und Schema-Migration aus Bestands-Repos**
   - Aus bestehenden GitHub-Repositories werden Funktionalität und Datenbankstruktur übernommen und in VIS integriert.

## 3. Phasenplan
1. **Foundation (abgeschlossen/gestartet)**
   - Plugin-Bootstrap
   - Externe DB-Konfiguration
   - VIS-Login und Session-Basis im Frontend
   - Modulauflistung pro externem Benutzer
2. **Identity & Access (V1)**
   - Externes Rollen- und Rechteschema stabilisieren
   - Verein-zu-Benutzer-Zuordnung in externer DB
   - Audit-Logging für Logins, Änderungen, Freigaben
3. **Migration Bildungsportal (funktional + Schema)**
   - Führendes Repo/Branch fixieren
   - Fachlogik und Datenstruktur in VIS-Domänenmodell überführen
   - UI/Flows in VIS-Weboberfläche integrieren
4. **Migration KidsCup (funktional + Schema)**
   - Führendes Repo/Branch fixieren
   - Fachlogik und Datenstruktur in VIS-Domänenmodell überführen
   - Wettkampfprozesse/Ergebnisse integrieren
5. **Cross-Client API für Web + Apps**
   - Gemeinsame API-Schicht für WordPress, iOS, Android
   - Token-basierte Authentifizierung/Autorisierung

## 4. Offene Fragen (jetzt priorisieren)
1. Welche konkreten GitHub-Repositories und Branches sind für **Bildungsportal** und **KidsCup** führend?
2. Welche Tabellenstruktur existiert bereits in der externen MySQL-DB (oder soll initial bereitgestellt werden)?
3. Welche Rechte-Matrix wird für Vereine/Vertreter/Verbandsrollen benötigt?
4. Soll für externe Authentifizierung mittelfristig JWT/OAuth2 eingesetzt werden?
5. Welche Datenschutz- und Löschfristen gelten für personenbezogene Daten?

## 5. Nächste konkrete Implementierungsschritte
1. Externes DB-Schema über Plugin-Migrationen versionieren (manuell + automatischer Check bei Admin-Start)
2. Rechteservice für Rollen- und Benutzerrechte erweitern (Audit-Logging + Bulk-Workflows umgesetzt, nächste Ausbaustufe: Fachmodulintegration Bildungsportal)
3. Bildungsportal-Funktionalität und DB-Struktur aus Bestandssystem integrieren (Start: Angebotsliste + Anmeldung umgesetzt)
4. KidsCup-Funktionalität und DB-Struktur aus Bestandssystem integrieren
