# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Projekt

**HugMailCockpit** — proprietäres Shopware-Admin-Plugin für manuellen E-Mail-Versand: freie E-Mails aus Bestell-/Kundendetail verfassen (F1), Mail-Historie-Tab (F2), Dokumente per Mail versenden (F3), Template-Preview mit echten Bestelldaten (F4). Das vollständige Detailkonzept inkl. Backend-Schnitt, UI-Entscheidungen und offener Risiken steht in `docs/konzept.md` — **vor Architekturentscheidungen immer dort nachlesen**, es ist die verbindliche Planungsgrundlage.

Zielversion: **Shopware ≥ 6.7 only** (`shopware/core: ~6.7.0`), kein 6.6-Support. Konsequenz für den Admin-Code: **ausschließlich Meteor-Komponenten (`mt-*`)**, keine `sw-*`-Wrapper oder Kompatibilitätsbrücken.

## Umgebung

- Shopware-Installation: `/home/shopware/sw7` (aktuell 6.7.10.2), Plugin liegt in `custom/plugins/HugMailCockpit`
- Ausführung via **DDEV** (Projekt `s7`, Workdir `/var/www/html`, PHP 8.2 im Container — **kein natives PHP auf dem Host**)
- `.claude/settings.local.json` setzt `PROJECT_ROOT=/home/shopware/sw7` — nötig, damit die dev-tooling-MCP-Server ihre Configs am Shopware-Root finden. Nicht entfernen.
- Dev-tooling-Scope `hug-mail-cockpit` ist als `default_scope` gepinnt (in `/home/shopware/sw7/.mcp-php-tooling.json` / `.mcp-js-tooling.json`)

## Werkzeuge & Befehle

PHP-/JS-Checks laufen **ausschließlich über die MCP-dev-tooling-Server**, nie direkt per Bash (Enforcement-Hooks blocken CLI-Aufrufe):

- PHP: `phpstan_analyze`, `ecs_check`/`ecs_fix`, `phpunit_run` (einzelner Test: `filter`-Parameter), `rector_check`/`rector_fix`, `console_run` (z. B. `plugin:refresh`, `plugin:install --activate HugMailCockpit`, `cache:clear`)
- Admin-JS: `eslint_check`/`fix`, `jest_run`, `tsc_check`, `vite_build`
- Für alles andere im Container: `ddev exec <cmd>` (vom sw7-Root aus)

Größere Check-Läufe an den Subagenten `dev-tooling-runner` delegieren.

## Architektur (Kurzfassung aus dem Konzept)

- **Backend-Services** (alle in §6 konzept.md spezifiziert): `HugMailSender` (wrappt `AbstractMailService`), `MailContextBuilder` (Order/Customer → Twig-Context, nutzt Core-`OrderConverter`; liefert auch die Variablen-Picker-Keys), `AttachmentResolver` (Dokumente via `DocumentGenerator::readDocument()` — Stream-Position beachten! — Uploads via `MediaService`), `TemplatePreviewRenderer` (`StringTemplateRenderer` + Twig-Fehler-Mapping), `MailReferenceWriter`, `DocumentMailTemplateMapper`
- **API-Routen:** `/api/_action/hug-mail-cockpit/` → `send`, `preview`, `variables`, `history`
- **Einzige eigene Entity:** `hug_mail_reference` (Zuordnung Mail ↔ Order/Dokument — bewusst *kein* Mail-Log)
- **FroshPlatformMailArchive** ist optionale Runtime-Dependency (composer `suggest` + Runtime-Guard): ohne MailArchive wird F2 ausgeblendet, F1/F3/F4 laufen weiter. Vor F2-Implementierung verifizieren, ob MailArchive eine `orderId` speichert (§1 konzept.md).
- **Admin-Overrides:** `sw-order-detail`, `sw-customer-detail`, `sw-order-document-card`, `sw-mail-template-detail`; eigene Komponenten `hug-mail-compose-modal`, `hug-mail-variable-picker`, `hug-mail-history-grid`, `hug-mail-preview-card`
- **ACL:** `hug_mail_cockpit.viewer` / `.sender` / `.free_sender` / `.twig_editor` (Twig-Editor ist ein eigenes Privileg — freier Mailversand ist ein Missbrauchsvektor)
- **Sprache im Mail-Context:** immer `order.languageId` bzw. `customer.languageId`, nie die Admin-Sprache

## Pflege-Pflichten (am Ende jeder Arbeitseinheit)

Bevor eine Aufgabe als abgeschlossen gemeldet wird:

1. **CHANGELOG.md** — jede nutzersichtbare Änderung unter `[Unreleased]` eintragen (Kategorien: Hinzugefügt/Geändert/Behoben)
2. **README.md** — aktualisieren, wenn sich Features, Anforderungen oder Installation geändert haben
3. **Memory** — neue, nicht aus dem Code ableitbare Erkenntnisse (Entscheidungen, Umgebungs-Eigenheiten, Nutzer-Feedback) im persistenten Memory ablegen bzw. bestehende Einträge korrigieren
4. **Git-Commit** — abgeschlossene Arbeitseinheiten lokal committen (deutsche Commit-Messages, prägnant)

## Git-Regeln

- Lokale Commits sind Teil des normalen Workflows.
- **Niemals pushen ohne ausdrückliche Aufforderung oder eindeutige Zustimmung des Nutzers.** Das gilt auch für `git push --force`, Tags und Remote-Operationen jeder Art.
