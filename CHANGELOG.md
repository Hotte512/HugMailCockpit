# Changelog

Alle nennenswerten Änderungen an HugMailCockpit werden in dieser Datei dokumentiert.

Format angelehnt an [Keep a Changelog](https://keepachangelog.com/de/1.1.0/); Versionierung nach [SemVer](https://semver.org/lang/de/).

## [Unreleased]

### Geändert
- Plugin-Icon neu gestaltet: Umschlag in einem Instrumenten-Ring („Cockpit"), Grün/Amber — als SVG und PNG (transparente Ecken)

## [1.0.0] - 2026-07-16

### Sicherheit
- **Dokument-Anhänge an die Bestellung gebunden:** Es lassen sich nur noch Dokumente anhängen, die zur adressierten Bestellung gehören — vorher konnte ein Nutzer mit Versandrecht per Dokument-ID beliebige fremde Belege (z. B. Rechnungen anderer Bestellungen) anhängen und versenden.
- **Datei-Anhänge auf den Plugin-Upload-Ordner beschränkt:** Als hochgeladener Anhang werden nur Medien aus dem dedizierten „Mail-Cockpit Anhänge"-Ordner akzeptiert — vorher war jede beliebige Media-ID der Installation anhängbar.
- **Bestell-/Kundendaten erfordern jetzt `order:read`/`customer:read`:** Vorschau, Variablen, Template-Rendern, Historie und Versand prüfen serverseitig die Lese-Berechtigung für die adressierte Bestellung bzw. den Kunden; die vier Cockpit-Rollen bringen diese Rechte automatisch mit. Vorher waren fremde Bestell-PII und der komplette Mail-Verlauf über die reine ID abrufbar.
- **Benötigtes Versandrecht wird aus dem Inhalt abgeleitet, nicht aus einem Client-Feld:** Freitext-Mails erfordern immer `free_sender`; das Umgehen über ein manipuliertes `source=document`-Feld ohne Dokument ist nicht mehr möglich.

### Hinzugefügt
- README: Abschnitte „Entstehung: KI-gestützte Entwicklung (Agentic Coding)" und „Haftungsausschluss" (inkl. Kurzhinweis am Seitenanfang) für die Veröffentlichung
- Variablen-Picker: Schalter „Alle Variablen anzeigen (Expertenansicht)" blendet bei Bedarf sämtliche Variablen mit technischem Namen ein — eingefügt wird weiterhin der echte Wert (kein Twig-Recht nötig)
- „Als Textvorlage speichern" im E-Mail-Fenster: aktuellen Inhalt unter einem Namen als Textbaustein ablegen (mit Hinweis, dass bereits eingefügte Werte wörtlich übernommen werden); die neue Vorlage steht sofort im Einfüge-Dropdown bereit
- Variablen-Picker zeigt im einfachen Modus verständliche, übersetzte Namen (Bestellnummer, Gesamtbetrag, Versandkosten, …) statt technischer Feldnamen — nur kuratierte, für den Alltag sinnvolle Variablen; der Twig-Expertenmodus behält weiterhin alle technischen Variablen
- Textvorlagen (Textbausteine): Pflege in der Plugin-Konfiguration (anlegen, bearbeiten, löschen) und Einfüge-Dropdown im E-Mail-Editor — Bausteine landen an der Cursorposition; einfache Variablen wie {{ order.orderNumber }} werden beim Versand ersetzt
- Benutzeranleitung (docs/benutzerhandbuch.md) für Shop-Mitarbeiter und Admins; README für Veröffentlichung überarbeitet
- Mail-Tab mit integriertem Vorschau-Bereich (Mail-Client-Layout): Liste links, Vorschau rechts; Klick auf eine Zeile zeigt die Mail sofort an, Doppelklick öffnet die Großansicht; die neueste Mail ist vorausgewählt
- Uploads aus dem Compose-Modal landen im dedizierten Media-Ordner „Mail-Cockpit Anhänge" (wird bei Installation/Update angelegt) statt im Wurzelverzeichnis der Medienverwaltung
- Plugin-Icon (Store-Vorbereitung)
- F4 — Template-Vorschau mit echten Daten: Card „Test mit echter Bestellung" auf der Mail-Template-Detailseite; Bestellung wählen (Suche über Bestellnummer), Vorschau in der Bestellsprache, Twig-Fehler mit Zeilenangabe; fehlende Flow-Variablen werden gemeldet, verhindern die Vorschau aber nicht; „Testmail senden" verschickt exakt das gerenderte Ergebnis an eine frei wählbare Adresse
- F2 komplett: „Antwort verfassen" öffnet das Compose-Modal vorbefüllt (Empfänger, „Re:"-Betreff); „Im Mail-Archiv öffnen" verlinkt in die MailArchive-Detailansicht (EML-Download, erneut senden); der Tab „E-Mails" zeigt die Anzahl archivierter Mails im Label
- F3 — Dokumente per E-Mail versenden: Kontextmenü-Aktion „Per E-Mail senden (Cockpit)" im Dokumenten-Grid der Bestellung sowie Mehrfachauswahl mit „Markierte per E-Mail senden" (eine Mail mit n Anhängen); öffnet das Compose-Modal mit vorselektierten Anhängen und der laut Zuordnung vorbelegten, sofort gerenderten Vorlage
- Plugin-Konfiguration: Zuordnung Dokumenttyp → Mail-Vorlage (dynamisch aus den vorhandenen Dokumenttypen, Fallback: leere E-Mail)
- Tab „E-Mails" an Bestell- und Kundendetail als zentrale Mail-Stelle: Mail-Historie aus FroshPlatformMailArchive (Datum, Betreff, Empfänger, Status, Anhänge; Detail-Ansicht als geschützte HTML-Vorschau; Hinweis, wenn MailArchive fehlt) plus „E-Mail senden"-Einstieg
- Neue API-Route `render-template`: rendert eine Vorlage serverseitig mit echten Bestell-/Kundendaten in der Sprache des Empfängers
- Vorschau zeigt jetzt das Briefpapier (Kopf-/Fußzeile) des Sales Channels — exakt wie beim Versand
- F1-Admin: Button „E-Mail senden" in der Smart Bar von Bestell- und Kundendetail (sichtbar nur mit Berechtigung „Freie E-Mails verfassen" und aktivem Feature-Toggle F1)
- Compose-Modal mit Dual-Editor (WYSIWYG ⇄ Twig, Twig-Modus nur mit entsprechender Berechtigung), Variablen-Picker mit Suche, Vorlagen-Übernahme als Kopie in der Sprache der Bestellung, CC/BCC, Auswahl vorhandener Bestell-Dokumente als Anhang, Datei-Upload und serverseitiger Vorschau mit Twig-Fehleranzeige inkl. Zeilenangabe
- Jest-Testsuite für die Admin-Komponenten (16 Tests inkl. Snapshots für Compose-Modal und Variablen-Picker)
- F1-Backend: kompletter Versandpfad für manuelle E-Mails aus der Administration — Versand ausschließlich über den Core-Mailservice, Mail-Sprache immer aus der Bestellung/dem Kunden (nie Admin-Sprache), Dokumente als Anhänge, Audit-Eintrag nach jedem Versand, automatische Verknüpfung ins MailArchive (orderId/customerId/templateId)
- Admin-API-Routen `/api/_action/hug-mail-cockpit/` → `send`, `preview` (mit Twig-Fehlern inkl. Zeilenangabe), `variables` (Variablen-Picker-Daten + Empfänger-Vorbelegung), `history` (liest FroshPlatformMailArchive)
- Serverseitige Twig-Richtlinie: ohne das Privileg `twig_editor` sind nur einfache Variablen (`{{ order.orderNumber }}`) erlaubt — Tags, Filter und Funktionsaufrufe werden abgelehnt (Missbrauchsschutz, auch in der Vorschau)
- 55 PHPUnit-Tests für alle Backend-Services und den API-Controller
- Plugin-Grundgerüst (installier- und aktivierbar): Composer-Paket `hug/mail-cockpit`, Plugin-Klasse mit Datenbank-Cleanup bei Deinstallation, Service-Container-Konfiguration
- Plugin-Konfiguration mit einzeln abschaltbaren Feature-Toggles für F1–F4
- ACL-Berechtigungen `hug_mail_cockpit.viewer` / `.sender` / `.free_sender` / `.twig_editor` (als zusätzliche Berechtigungen in der Admin-Rollenverwaltung, inkl. deutscher und englischer Beschriftung)
- Entity `hug_mail_reference` inkl. Migration — Audit-Layer für versendete Mails (wer, welche Quelle, welches Dokument); Inhalte verbleiben in FroshPlatformMailArchive
- Forgejo-CI-Workflow (composer validate, ECS, PHPStan Level max, PHPUnit auf PHP 8.2/8.3)
- Tooling-Konfiguration: ECS, PHPStan (Level max), Rector, PHPUnit inkl. erstem Migrations-Test
- Detailkonzept (`docs/konzept.md`) mit Featureschnitt F1–F4, Backend-Architektur und Aufwandsschätzung
- Projekt-Setup: CLAUDE.md, README, Changelog, Git-Repository, dev-tooling-Anbindung (DDEV/MCP)

### Geändert
- Lizenz von „proprietär" auf **GPL-3.0-or-later** umgestellt (LICENSE-Datei, composer.json, README)
- Herstellerangaben in composer.json auf die GitHub-Veröffentlichung umgestellt (Autor/Links); firmenspezifische Beispieldaten in Tests neutralisiert

### Geändert (Nutzer-Feedback 11.07.2026)
- Compose-Flow vereinfacht auf „gerendert bearbeiten": Vorlage wählen → sofort mit echten Daten gerendert → Ergebnis im Editor bearbeiten → 1:1 senden; kein Twig mehr im Editor für normale Nutzer
- Variablen-Picker fügt im einfachen Modus echte Werte aus der Bestellung ein (Twig-Expressions nur noch im Experten-Modus)
- Smart-Bar-Buttons entfernt — Einstieg ausschließlich über den Tab „E-Mails"
- Mail-Kontext enthält jetzt `salesChannel`/`salesChannelId` wie beim echten Versand (behebt Render-Fehler bei Standard-Templates)

### Behoben
- Card im E-Mails-Tab nutzt jetzt wirklich die volle Content-Breite (Meteor-Card-Deckel von 60rem via offizieller Escape-Klasse aufgehoben)
- Veralteter Smart-Bar-Hinweis im Hilfetext der Plugin-Konfiguration korrigiert
- Leerer Editor war nicht beschreibbar (Meteor-Editor-Gate bei TipTap-instabilem Inhalt) — Inhalt startet jetzt TipTap-stabil

### Geändert
- Konzept nach Code-Verifikation (Phase 0) präzisiert: MailArchive ≥ 3.6 als Anforderung für F2, `hug_mail_reference` als reiner Audit-Layer, kein Stream-Handling bei Dokument-Anhängen, pragmatische Meteor-Komponenten-Regel
