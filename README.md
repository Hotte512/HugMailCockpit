# HugMailCockpit

Shopware-6-Plugin für manuellen E-Mail-Versand aus der Administration — freie E-Mails, Dokumentversand, Mail-Historie und Template-Preview mit echten Bestelldaten.

> **Status: in Entwicklung** — noch kein lauffähiger Code. Verbindliche Planungsgrundlage: [docs/konzept.md](docs/konzept.md)

## Features (geplanter MVP 1.0)

- **F1 — Freie E-Mail verfassen:** Button im Bestell- und Kundendetail öffnet ein Modal mit Dual-Editor (WYSIWYG ⇄ Twig), Variablen-Picker, Vorlagen als Kopie, Dokument-Anhängen und Vorschau
- **F2 — Historie-Tab:** Mail-Historie je Bestellung/Kunde (erfordert das Plugin FroshPlatformMailArchive; ohne dieses wird der Tab ausgeblendet)
- **F3 — Dokumente einzeln mailen:** „Per E-Mail senden" direkt aus dem Dokumenten-Grid, inkl. Bulk (eine Mail mit n Anhängen) und konfigurierbarem Vorlagen-Mapping je Dokumenttyp
- **F4 — Preview mit echten Daten:** Mail-Templates gegen echte Bestellungen rendern, Twig-Fehler mit Zeilenangabe sichtbar, Testversand

## Anforderungen

- Shopware **≥ 6.7** (`shopware/core: ~6.7.0`) — kein 6.6-Support
- Optional: [FroshPlatformMailArchive](https://github.com/FriendsOfShopware/FroshPlatformMailArchive) für die Mail-Historie (F2)

## Installation

```bash
bin/console plugin:refresh
bin/console plugin:install --activate HugMailCockpit
bin/console cache:clear
```

## Berechtigungen (ACL)

| Privileg | Bedeutung |
|---|---|
| `hug_mail_cockpit.viewer` | Mail-Historie sehen |
| `hug_mail_cockpit.sender` | Dokumente versenden |
| `hug_mail_cockpit.free_sender` | freie E-Mails verfassen |
| `hug_mail_cockpit.twig_editor` | Twig-Modus im Editor |

## Lizenz

Proprietär. Alle Rechte vorbehalten.
