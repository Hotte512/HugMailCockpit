# Changelog

Alle nennenswerten Änderungen an HugMailCockpit werden in dieser Datei dokumentiert.

Format angelehnt an [Keep a Changelog](https://keepachangelog.com/de/1.1.0/); Versionierung nach [SemVer](https://semver.org/lang/de/).

## [Unreleased]

### Hinzugefügt
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
- Konzept nach Code-Verifikation (Phase 0) präzisiert: MailArchive ≥ 3.6 als Anforderung für F2, `hug_mail_reference` als reiner Audit-Layer, kein Stream-Handling bei Dokument-Anhängen, pragmatische Meteor-Komponenten-Regel
