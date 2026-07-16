# HugMailCockpit

Manueller E-Mail-Versand aus der Shopware-6-Administration: freie E-Mails direkt aus Bestellung und Kunde verfassen, Dokumente (Rechnungen, Lieferscheine, …) per Mail verschicken, die komplette Mail-Historie je Bestellung/Kunde einsehen und Mail-Templates mit echten Bestelldaten testen.

Das Herzstück ist der Tab **„E-Mails"** im Bestell- und Kundendetail — mit Mail-Client-Layout (Liste + Vorschau) und dem „gerendert bearbeiten"-Flow: Vorlagen werden beim Auswählen sofort mit den echten Daten in der Sprache des Empfängers gerendert, bearbeitet wird das fertige Ergebnis. Kein Twig für normale Nutzer.

📖 **[Benutzeranleitung](docs/benutzerhandbuch.md)** · 🧭 [Detailkonzept](docs/konzept.md)

> **Kurz vorab:** Dieses Plugin wurde überwiegend **KI-gestützt** entwickelt
> („Agentic Coding" / „Vibe Coding" mit Claude Code) und wird **ohne
> Gewährleistung** bereitgestellt (GPL-3.0). Es versendet E-Mails an echte Kunden —
> die Verantwortung für Konfiguration, Inhalte und Empfänger liegt beim
> Shop-Betreiber. Details: [KI-gestützte Entwicklung](#entstehung-ki-gestützte-entwicklung-agentic-coding)
> und [Haftungsausschluss](#haftungsausschluss).

## Features

- **Textvorlagen** — wiederverwendbare Textbausteine (Grußformeln, Standardantworten), gepflegt in der Plugin-Konfiguration, eingefügt per Dropdown direkt im Editor.
- **Freie E-Mails verfassen** — aus Bestell- oder Kundendetail; Empfänger vorbefüllt, CC/BCC, Variablen-Picker mit echten Werten (an der Cursorposition), Dokument-Anhänge und Datei-Upload, serverseitige Vorschau inkl. Briefpapier des Verkaufskanals. Twig-Rohmodus als separat berechtigtes Experten-Feature.
- **Mail-Historie** — je Bestellung und Kunde im Tab „E-Mails" (Anzahl im Tab-Label): Liste links, Live-Vorschau rechts, Doppelklick für die Großansicht; „Antwort verfassen" (vorbefüllt) und Deep-Link ins Mail-Archiv (EML-Download, erneut senden). Basiert auf [FroshPlatformMailArchive](https://github.com/FriendsOfShopware/FroshPlatformMailArchive).
- **Dokumente mailen** — Kontextmenü-Aktion im Dokumenten-Grid sowie Mehrfachauswahl („Markierte per E-Mail senden" = eine Mail mit n Anhängen); Vorlage je Dokumenttyp konfigurierbar, wird sofort gerendert vorbelegt.
- **Template-Test mit echten Daten** — Card „Test mit echter Bestellung" in der Mail-Template-Detailseite: Rendern in der Bestellsprache, Twig-Fehler mit Zeilenangabe, fehlende Flow-Variablen blockieren nicht, Testversand des gerenderten Ergebnisses.
- **Sicherheit** — vier getrennte ACL-Berechtigungen, serverseitig in jeder API-Route durchgesetzt; ohne Twig-Recht sind nur einfache Variablen erlaubt (Missbrauchsschutz); Audit-Trail `hug_mail_reference` (wer hat was von wo versendet).
- **Sprach-Garantie** — Mails werden immer in der Sprache der Bestellung bzw. des Kunden gerendert, nie in der Admin-Sprache.

## Anforderungen

- Shopware **≥ 6.7** (`shopware/core: ~6.7.0`) — kein 6.6-Support
- PHP ≥ 8.2
- Optional: [FroshPlatformMailArchive](https://github.com/FriendsOfShopware/FroshPlatformMailArchive) **≥ 3.6** für die Mail-Historie — ohne dieses Plugin laufen alle übrigen Funktionen weiter, der Historie-Bereich zeigt einen Hinweis

## Installation

Plugin nach `custom/plugins/HugMailCockpit` legen (Git-Clone, Composer-VCS-Repository oder ZIP), dann:

```bash
bin/console plugin:refresh
bin/console plugin:install --activate HugMailCockpit
bin/console cache:clear
```

Bei der Installation wird die Tabelle `hug_mail_reference` (Audit-Trail) und der Media-Ordner „Mail-Cockpit Anhänge" angelegt. Die Deinstallation räumt beides wieder auf (außer bei „Daten behalten").

## Konfiguration

Unter *Erweiterungen → Meine Erweiterungen → Mail-Cockpit → Konfiguration*:

- **Feature-Schalter** für alle vier Funktionsbereiche, einzeln abschaltbar (Standard: alle aktiv)
- **Vorlagen-Zuordnung je Dokumenttyp**: welche Mail-Vorlage beim Dokumentversand vorbelegt wird (Fallback: leere E-Mail)

💡 **Tipp:** Lege ein Briefpapier an (*Einstellungen → E-Mail-Templates → Kopf- und Fußzeilen*) und weise es deinen Verkaufskanälen zu — dann bekommen auch frei geschriebene Mails automatisch das Shop-Design. Die Vorschau des Plugins zeigt es mit an.

## Berechtigungen (ACL)

In der Rollenverwaltung unter „Zusätzliche Berechtigungen → Mail-Cockpit":

| Berechtigung | Bedeutung |
|---|---|
| Mail-Historie sehen (`viewer`) | Tab „E-Mails" mit Historie und Vorschau |
| Dokumente per E-Mail versenden (`sender`) | Dokumentversand aus dem Dokumenten-Grid |
| Freie E-Mails verfassen (`free_sender`) | „E-Mail senden" aus Bestellung/Kunde |
| Twig-Modus im Editor verwenden (`twig_editor`) | Roh-Twig im Editor — bewusst separat (Missbrauchsschutz) |

Empfehlung für eine Kundenservice-Rolle: die ersten drei, **ohne** `twig_editor`.

## Entwicklung

```bash
composer install          # Tooling (PHPUnit, PHPStan, ECS, Rector)
composer phpunit          # 72 Tests
composer phpstan          # Level max
composer ecs
npm ci                    # Admin-Tooling (Jest, ESLint)
npm run unit              # 48 Tests inkl. Snapshots
npm run lint -- src/Resources/app/administration/src tests/administration
```

CI (Forgejo/GitHub Actions-kompatibel): composer validate, ECS, PHPStan, PHPUnit (PHP 8.2/8.3), ESLint + Jest (Node 20) — siehe [.forgejo/workflows/ci.yml](.forgejo/workflows/ci.yml).

## Entstehung: KI-gestützte Entwicklung (Agentic Coding)

Dieses Plugin wurde überwiegend **KI-gestützt** entwickelt („Agentic
Coding" bzw. „Vibe Coding" mit [Claude Code](https://claude.com/claude-code)):
Ein KI-Agent hat Konzeption, Implementierung, Tests und Dokumentation unter
menschlicher Anleitung und Review erstellt. Der Code wurde funktional
getestet (Unit-Tests für Backend und Admin-Komponenten, End-to-End-Tests im
Browser gegen eine echte Shopware-Installation), aber nicht Zeile für Zeile
von Hand geschrieben.

Was das für dich bedeutet:

- Prüfe den Code vor dem Produktiveinsatz selbst — wie bei jedem
  Fremd-Plugin, hier aber ausdrücklich empfohlen.
- Trotz sorgfältiger Tests können Fehler enthalten sein, die ein
  menschlicher Autor so nicht gemacht hätte (und umgekehrt).
- Issues und Pull Requests sind willkommen, es besteht aber **kein
  Anspruch auf Support, Fehlerbehebung oder Weiterentwicklung**.

## Haftungsausschluss

- **E-Mail-Versand an echte Empfänger:** Dieses Plugin verschickt E-Mails
  an Kunden. Die Verantwortung für Inhalte, Empfängerauswahl, Anhänge
  (inkl. personenbezogener Daten in Rechnungen & Co.), die Vergabe der
  ACL-Berechtigungen sowie die datenschutzkonforme Nutzung liegt allein
  beim Shop-Betreiber. Vor dem Produktiveinsatz in einer Testumgebung
  prüfen (z. B. mit deaktivierter Zustellung / Mail-Catcher).
- **Keine Gewährleistung:** Die Software wird „wie besehen" bereitgestellt,
  ohne Gewährleistung jeglicher Art und mit Haftungsbeschränkung gemäß
  **GPL-3.0 §§ 15–16** (siehe [LICENSE](LICENSE)). Einsatz auf eigenes Risiko —
  insbesondere gibt es keine Garantie für die Zustellung, Darstellung oder
  rechtliche Eignung der versendeten E-Mails im konkreten Shop.

## Lizenz

GNU General Public License v3.0 oder später (GPL-3.0-or-later) —
Volltext in [LICENSE](LICENSE). Copyright (C) 2026 Hotte512.
