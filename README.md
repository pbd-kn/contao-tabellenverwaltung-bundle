# Contao Tabellenverwaltung Bundle

Eigenständige Tabellen und Verzeichnisse für Contao. Keine EFG-Abhängigkeit für Verwaltung oder Ausgabe.

## Backend-Menü

Die eigene Menügruppe **Tabellenverwaltung** bündelt **Tabellen**, **EFG-Daten übernehmen** und **CSV importieren / exportieren**. Die Importseiten führen mit getrennten Abschnitten durch Auswahl, Zuordnung, Vorschau und Bestätigung.

Beim Bearbeiten eines Eintrags nutzen die Feldwerte die verfügbare Breite. Lange Werte werden in mehrzeiligen Textfeldern umgebrochen; die Höhe passt sich beim Tippen an und lässt sich am unteren Rand manuell vergrößern. Schlüssel und Zeilenaktionen bleiben erhalten. Das gespeicherte Datenformat bleibt unverändert; es ist keine Datenbankmigration erforderlich.

## Insert-Tags für einzelne Einträge

Die ID eines veröffentlichten Eintrags anhand von Tabellenname und Bezeichnung ermitteln:

```text
{{direntry::Mitgliedsbetriebe::Hilzinger GmbH & Co. KG}}
```

Der zweite Parameter ist der Name der Tabelle (alternativ ihre numerische ID), der dritte die **Bezeichnung** des Eintrags im Backend. Bei unbekannten, unveröffentlichten oder mehrfach passenden Einträgen bleibt die Ausgabe leer. Bei einer Umbenennung die entsprechenden Tags anpassen. Namen mit `::` sind in dieser Schreibweise nicht verwendbar.

Für einen Detailseiten-Link in einem HTML-Inhaltselement beispielsweise:

```html
<a href="{{link_url::123}}?directory_entry={{direntry::Mitgliedsbetriebe::Hilzinger GmbH & Co. KG}}">Betrieb ansehen</a>
```

`123` durch die ID der Contao-Detailseite ersetzen. Auf dieser Seite ein Inhaltselement **Verzeichnistabelle** mit Tabelle **Mitgliedsbetriebe** und Darstellung **Details** einrichten. Der `direntry`-Tag gibt ausschließlich die Eintrags-ID aus, nicht die komplette URL oder den Datensatz. Bei leerem Ergebnis den Link nicht verwenden: Eine Detailseite ohne ausgewählte ID zeigt ihre vollständige Übersicht.

Einzelne Feldwerte lassen sich über die bekannte Eintrags-ID ausgeben:

```text
{{tabelle::17::FIRMA}}
{{tabelle::17::TELEFON}}
{{tabelle::17::_title}}
```

`17` ist hier eine Beispiel-ID. Feldschlüssel wie `FIRMA` exakt wie in der Tabelle schreiben; `_title` gibt die Backend-Bezeichnung aus. Werte werden als HTML-geschützter Text ausgegeben, ohne HTML aus dem Datenfeld auszuführen. Nicht veröffentlichte Einträge und unbekannte Felder liefern eine leere Ausgabe.

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

Datenbank vorher sichern und die Vorschau prüfen. Die Contao-Schemaprüfung kann auch Löschungen für andere Erweiterungen vorschlagen. Nur die gewünschten Änderungen ausführen. `install-directory.sql` enthält ausschließlich die 16 Schema-Ergänzungen dieses Bundles und ist für eine einmalige Neuinstallation vorgesehen.

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

Unter **Tabellenverwaltung → Tabellen** ein Verzeichnis anlegen und Spalten als eindeutige Schlüssel/Überschriften definieren. Bestehende Schlüssel nicht umbenennen. Über **Einträge verwalten** die Datensätze öffnen; nur veröffentlichte Einträge erscheinen im Frontend.

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

Tabellen, Partnerkarten und Detailansichten können als Inhaltselement oder Frontend-Modul eingebunden werden. Partnerkarten öffnen ihre Details auf derselben Seite und bieten einen Zurück-Link; ältere separate Detailansichten bleiben verfügbar; die Auswahl erfolgt über directory_entry, ältere numerische EFG-Parameter werden über legacyId unterstützt. Keine Seitennavigation. Suche/Sortierung erfolgen im Arbeitsspeicher; große Verzeichnisse benötigen eine Erweiterung. Beliebiges HTML und serialisierte EFG-Mehrfachwerte werden nicht übernommen. Menü und Seitenlayout sind Teil der Website.

## Lizenz

LGPL-3.0-or-later, siehe [LICENSE](LICENSE).

## Partnerkarten und Details

Bei Inhaltselementen und Modulen lässt sich die Darstellung Tabelle, Partnerkarten oder Details auswählen. Partnerkarten verwenden die Klasse partnerartikel für bestehende Rotationsskripte und zeigen die Ansprechpartner aus den Verzeichnisdaten. Bei Details werden nur veröffentlichte Einträge des gewählten Verzeichnisses angezeigt. Ohne ausgewählte Eintrags-ID erscheint die vollständige Detailübersicht.

## EFG-Import im Backend

Backend-Administratoren können unter **Tabellenverwaltung → EFG-Daten übernehmen** ein Quellverzeichnis wählen, eine Vorschau prüfen und die bestätigte Übernahme starten. Quelldaten bleiben erhalten; importierte Einträge sind unveröffentlicht. Bestehende Zielnamen verhindern Doppelimporte. EFG kann unabhängig davon weiterhin für Kontaktformular-Mails installiert bleiben.

## CSV-Import und CSV-Export im Backend

Unter **Tabellenverwaltung → CSV importieren / exportieren** können Administratoren ein vorhandenes Verzeichnis auswählen und dessen Einträge als CSV herunterladen oder gesammelt einlesen. Das Verzeichnis und seine Spalten zuerst unter **Tabellenverwaltung → Tabellen** anlegen. Auch ein leeres Verzeichnis lässt sich als Vorlage exportieren. Dafür sind weder SSH noch FTP oder Datenbankänderungen erforderlich.

1. Verzeichnis auswählen und **Auswählen** anklicken.
2. Mit **CSV herunterladen** alle Einträge einschließlich unveröffentlichter Einträge exportieren.
3. Die Datei beispielsweise in Excel über **Daten → Aus Text/CSV** öffnen. PLZ, Telefonnummern und IDs als Text behandeln, damit führende Nullen erhalten bleiben. Wieder als CSV UTF-8 speichern.
4. Zum Einlesen die Datei auswählen und **Datei laden und Spalten zuordnen** anklicken.
5. Die CSV-Überschriften den Verzeichnisfeldern zuordnen. Passende Feldschlüssel oder Beschriftungen werden automatisch vorgeschlagen.
6. **Vorschau erstellen** anklicken. Sie nennt die Anzahl neuer und zu aktualisierender Einträge und zeigt die ersten zehn Datensätze.
7. Die Vorschau bestätigen und **Jetzt importieren** anklicken. Erst jetzt wird gespeichert.

Die Exportdatei enthält `_id` (Eintrags-ID), `_title` (Bezeichnung), `_csv_format` (technische Formatkennung) und die Feldschlüssel als Überschriften. `_id` und `_title` sind optional beim Einlesen. Für neue Einträge `_id` leer lassen; eine bestehende ID muss zum ausgewählten Verzeichnis gehören. Ohne ID entstehen immer neue Einträge, auch beim erneuten Einlesen derselben Datei. Beim Übertragen in ein anderes Verzeichnis die IDs leeren. `_csv_format` bei unveränderten Exportdateien beibehalten; die Kennung ermöglicht die Rückwandlung des Excel-Formelschutzes.

Neue Einträge sind unveröffentlicht. Bei Aktualisierungen bleiben Veröffentlichungsstatus, Sortierung und EFG-Herkunfts-ID erhalten. Nicht zugeordnete Felder behalten ihre bisherigen Werte; leere zugeordnete Zellen leeren den jeweiligen Wert. Bei fehlender Bezeichnung wird für neue Einträge Firma bzw. Vor-/Nachname verwendet. Es werden keine Einträge gelöscht. Ein Import ist eine gemeinsame Datenbanktransaktion; ungültige IDs oder zwischenzeitlich veränderte Einträge führen zum Abbruch ohne Teilimport. Direkte CSV-Änderungen erzeugen keine Contao-Versionen; vor größeren Änderungen eine Datenbanksicherung erstellen.

Import: maximal 5 MB und 10.000 Datensätze, zusätzlich gilt das PHP-Uploadlimit. Kopfzeile erforderlich; Semikolon, Komma und Tabulator (automatisch oder auswählbar), UTF-8 mit/ohne BOM sowie Windows-1252 werden unterstützt. Mehrzeilige und durch Anführungszeichen geschützte Werte bleiben erhalten. Export: UTF-8 mit BOM und Semikolon. Potenzielle Excel-Formeln werden mit einem Apostroph entschärft und beim Rückimport einer Datei mit Formatkennung wiederhergestellt. Behalte die Kennung nur bei, wenn die Datei auf diesem Export beruht. Dateien enthalten auch nicht veröffentlichte Daten und sind entsprechend vertraulich zu behandeln.

Die CSV-Tests prüfen Neuanlage, Vorschau ohne Schreibzugriff, Aktualisierung ohne Duplikate, Teilaktualisierungen, Status-Erhaltung, Kodierung, Trennzeichen, Zeilenumbrüche, Formelschutz mit Rückimport, ungültige/fremde/doppelte IDs, veraltete Vorschauen und vollständiges Zurückrollen bei Fehlern.


## Mehrere Einträge veröffentlichen

In der Eintragsliste einer Tabelle **Mehrere bearbeiten** öffnen, die gewünschten Einträge oder **Alle auswählen** markieren und **Überschreiben** anklicken. Anschließend nur das Feld **Veröffentlicht** auswählen, mit **Weiter** bestätigen, das Häkchen **Veröffentlicht** setzen und speichern. Der Wert wird gemeinsam für alle ausgewählten Einträge gesetzt. Zum gemeinsamen Zurücknehmen der Veröffentlichung das Häkchen entfernen. Mit **Bearbeiten** lassen sich die ausgewählten Einträge stattdessen einzeln auf einer gemeinsamen Formularseite ändern. Die Änderungen nutzen Contaos reguläre Datensatzversionierung.
