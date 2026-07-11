# Changelog

Alle nennenswerten Änderungen an HugMailCockpit werden in dieser Datei dokumentiert.

Format angelehnt an [Keep a Changelog](https://keepachangelog.com/de/1.1.0/); Versionierung nach [SemVer](https://semver.org/lang/de/).

## [Unreleased]

### Hinzugefügt
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
