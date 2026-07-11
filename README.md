# HugMailCockpit

Shopware-6-Plugin für manuellen E-Mail-Versand aus der Administration — freie E-Mails, Dokumentversand, Mail-Historie und Template-Preview mit echten Bestelldaten.

> **Status: MVP-Funktionsumfang (F1–F4) umgesetzt** — es folgen Feinschliff, i18n-Review und Store-Vorbereitung. Verbindliche Planungsgrundlage: [docs/konzept.md](docs/konzept.md)

## Features (geplanter MVP 1.0)

- **F1 — Freie E-Mail verfassen:** Tab „E-Mails" im Bestell- und Kundendetail; Vorlagen werden sofort mit echten Daten gerendert und das Ergebnis im WYSIWYG bearbeitet („gerendert bearbeiten"), Variablen-Picker fügt echte Werte ein, Dokument-Anhänge, Upload, Vorschau inkl. Briefpapier; Twig-Rohmodus als Experten-Feature
- **F2 — Historie:** Mail-Historie je Bestellung/Kunde im selben Tab (erfordert FroshPlatformMailArchive; ohne dieses erscheint ein Hinweis)
- **F3 — Dokumente mailen:** „Per E-Mail senden (Cockpit)" direkt aus dem Dokumenten-Grid, inkl. Mehrfachauswahl (eine Mail mit n Anhängen) und konfigurierbarem Vorlagen-Mapping je Dokumenttyp in der Plugin-Konfiguration
- **F4 — Preview mit echten Daten:** Card „Test mit echter Bestellung" in der Mail-Template-Detailseite — Templates gegen echte Bestellungen rendern (in der Bestellsprache), Twig-Fehler mit Zeilenangabe, fehlende Flow-Variablen werden gemeldet statt zu blockieren, Testversand des gerenderten Ergebnisses

## Anforderungen

- Shopware **≥ 6.7** (`shopware/core: ~6.7.0`) — kein 6.6-Support
- PHP ≥ 8.2
- Optional: [FroshPlatformMailArchive](https://github.com/FriendsOfShopware/FroshPlatformMailArchive) **≥ 3.6** für die Mail-Historie (F2) — ab 3.6 speichert das Archiv `order_id` und `mail_template_id`

## Installation

```bash
bin/console plugin:refresh
bin/console plugin:install --activate HugMailCockpit
bin/console cache:clear
```

## Konfiguration

Unter *Einstellungen → System → Plugins → Mail-Cockpit* lassen sich die Features F1–F4 einzeln aktivieren/deaktivieren (Standard: alle aktiv). F2 setzt zusätzlich FroshPlatformMailArchive ≥ 3.6 voraus und bleibt ohne dieses Plugin ausgeblendet.

## Berechtigungen (ACL)

| Privileg | Bedeutung |
|---|---|
| `hug_mail_cockpit.viewer` | Mail-Historie sehen |
| `hug_mail_cockpit.sender` | Dokumente versenden |
| `hug_mail_cockpit.free_sender` | freie E-Mails verfassen |
| `hug_mail_cockpit.twig_editor` | Twig-Modus im Editor |

## Lizenz

Proprietär. Alle Rechte vorbehalten.
