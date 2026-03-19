# WP_Plugin_VIS

Dieses Repository enthält den Start für das WordPress-Plugin **VIS (Verbandsinformationssystem)**.

## Aktueller Stand
- Plugin-Basis unter `vis/`
- Geschützter Portal-Shortcode `[vis_portal]`
- Rollen-/Rechte-Start für `vis_vereinsvertreter`
- Modulliste mit `Bildungsportal` und `KidsCup`
- Admin-Einstellungen zur globalen Modulfreigabe

Weitere Planung und offene Fragen: `docs/vis-roadmap.md`.

## Installationsdatei für WordPress
Die installierbare Plugin-Datei kann mit dem Build-Skript erzeugt werden:

```bash
./scripts/build-wordpress-package.sh
```

Das Skript liest die Plugin-Version aus `vis/vis.php` und erstellt lokal ein versioniertes ZIP-Paket unter `dist/vis-<version>.zip` (aktuell z. B. `dist/vis-0.1.0.zip`). Das Build-Artefakt wird bewusst nicht versioniert und kann anschließend in WordPress über **Plugins → Installieren → Plugin hochladen** installiert werden.
