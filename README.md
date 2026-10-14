# Contao Tabellenverwaltung Bundle

Eigenständige Tabellen und Verzeichnisse für Contao. Keine EFG-Abhängigkeit für Verwaltung oder Ausgabe.

## Funktionen

- Verzeichnisse, Spalten und Einträge im Backend verwalten.
- Frei konfigurierbare Anzeige-, Such-, Sortier- und Detailfelder im Inhaltselement **Verzeichnistabelle**.
- Suche mit Feldauswahl, lokale Rasterbild-Logos sowie Website-, E-Mail- und Telefonlinks.
- Aufklappbare Details; HTML-Escaping der Textwerte.
- Optionaler EFG-Import mit Vorschau, Transaktion, Duplikatschutz und Bezeichnungsreparatur.

## Voraussetzungen

PHP 8.1+, Contao 4.13 oder 5.3+. In Contao 4.13 mit PHP 8.2 sind Backend, Ausgabe und Import geprüft. Die GitHub-Actions-Matrix prüft Paketinstallation und Tests mit Contao 4.13 und 5.3. Ein vollständiger Website-Test unter Contao 5 steht noch aus.

## Installation

Bis zur Registrierung auf Packagist dieses Repository in die Composer-Konfiguration der Website aufnehmen:

```json
"repositories": [
  {"type": "vcs", "url": "https://github.com/pbd-kn/contao-tabellenverwaltung-bundle"}
]
```

```sh
composer require pbd-kn/contao-tabellenverwaltung-bundle:dev-main
php vendor/bin/contao-console contao:migrate --dry-run
```

Datenbank vorher sichern und die Vorschau prüfen. Die Contao-Schemaprüfung kann auch Löschungen für andere Erweiterungen vorschlagen. Nur die gewünschten Änderungen ausführen. `install-directory.sql` enthält ausschließlich die sieben Schema-Ergänzungen dieses Bundles und ist für eine einmalige Neuinstallation vorgesehen.

## Lokale Entwicklung

```json
"repositories": [
  {"type": "path", "url": "../../Github Bundles/contao-tabellenverwaltung-bundle", "options": {"symlink": false}}
]
```

Eine gespiegelte Paketinstallation nach Quellcodeänderungen aktualisieren:

```sh
composer reinstall pbd-kn/contao-tabellenverwaltung-bundle --no-scripts
php vendor/bin/contao-console cache:clear --env=prod
```

## Verwendung

Unter **Inhalte → Verzeichnisse** ein Verzeichnis anlegen und Spalten als eindeutige Schlüssel/Überschriften definieren. Bestehende Schlüssel nicht umbenennen. Über **Einträge verwalten** die Datensätze öffnen; nur veröffentlichte Einträge erscheinen im Frontend.

In einem Artikel das Inhaltselement **Verzeichnistabelle** hinzufügen. Die Ausgabe-Einstellungen verwenden kommagetrennte Feldschlüssel:

| Einstellung | Beispiel | Wenn leer |
| --- | --- | --- |
| Sichtbare Spalten | `FIRMA,STRASSE,PLZ,ORT,TELEFON,EMAIL` | Alle Spalten |
| Durchsuchbare Spalten | `FIRMA,PLZ,ORT` | Keine Suche |
| Sortierung | `FIRMA,PLZ,ORT` | Backend-Reihenfolge |
| Detailfelder | `NAME,VORNAME,INTERNET,EMAIL` | Keine Details |

Die Suche bezieht sich auf das ausgewählte Feld. `INTERNET`/`INTERNET1`, `EMAIL`/`EMAIL1`, `TELEFON` und `MOBIL` werden passend verlinkt. `LOGO` akzeptiert einen vorhandenen Rasterbildpfad unter `files/`; andere Werte erscheinen als Klartext.

## Optionaler EFG-Import

Der Import benötigt die EFG-Quelltabellen, aber keine EFG-Klassen. Den tatsächlichen Formularnamen ermitteln:

```sql
SELECT form, COUNT(*) FROM tl_formdata GROUP BY form;
```

```sh
php vendor/bin/contao-console directory:import-efg "Mitgliedsbetriebe" "Mitgliedsbetriebe"
php vendor/bin/contao-console directory:import-efg "Mitgliedsbetriebe" "Mitgliedsbetriebe" --execute
```

Ohne `--execute` nur Vorschau. Die Quelle bleibt unverändert, Einträge werden unveröffentlicht kopiert und behalten die EFG-ID als `legacyId`. Bezeichnungen werden aus Firma, Bezeichnung oder Vor-/Nachname gebildet. Bei fehlenden Namen wird die Quell-ID verwendet. Ungültige/doppelte Feldschlüssel führen zum Abbruch; ein bestehender Verzeichnisname verhindert versehentlichen Doppelimport.

Bezeichnungen aus älteren Importversionen reparieren:

```sh
php vendor/bin/contao-console directory:import-efg "Mitgliedsbetriebe" "Mitgliedsbetriebe" --repair-titles
php vendor/bin/contao-console directory:import-efg "Mitgliedsbetriebe" "Mitgliedsbetriebe" --repair-titles --execute
```

Werte und Ausgabe vor Umstellung prüfen. EFG-Templates, Frontend-Bearbeitung, Module und Insert-Tags werden nicht automatisch ersetzt. EFG erst entfernen, wenn alle übrigen Einbindungen umgestellt sind.

## Tests

```sh
composer install --no-plugins --no-scripts
composer test
```

SQLite im Arbeitsspeicher prüft Import, Vorschau, UTF-8, Duplikatschutz und Reparatur sowie die Erhaltung manueller Bezeichnungen und Feldwerte. Weitere Tests prüfen sichere Link-/Bild-/Textformatierung. Keine Website-Daten oder Zugangsdaten sind im Repository enthalten.

## Grenzen

Keine Seitennavigation, kein CSV-Export und keine eigenständigen Detailseiten. Suche/Sortierung erfolgen im Arbeitsspeicher; große Verzeichnisse benötigen eine Erweiterung. Beliebiges HTML und serialisierte EFG-Mehrfachwerte werden nicht übernommen. Menü und Seitenlayout sind Teil der Website.

## Lizenz

LGPL-3.0-or-later, siehe [LICENSE](LICENSE).
