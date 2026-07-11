# HugMailCockpit — Detailkonzept

Stand: 11.07.2026 · Ergebnis der Planungssession · Basis: session-briefing HugMailCockpit

**Getroffene Grundentscheidungen**
- Zielversion: **Shopware ≥ 6.7** — kein 6.6-Support (`composer.json`: `shopware/core: ~6.7.0`)
- MVP 1.0 = F1–F4 (voller Scope)
- F1-Editor: Dual-Mode (WYSIWYG ⇄ Twig), umschaltbar
- MailArchive: harte optionale Dependency → **kein eigenes Mail-Log**

---

## 1. Kopplung an FroshPlatformMailArchive

**Modell:** `composer.json` → `suggest` + Runtime-Guard (`PluginIdProvider` / aktive Plugin-Liste). Kein Adapter-Interface, direkter Zugriff auf das `frosh_mail_archive`-Repository.

| Zustand | Verhalten |
|---|---|
| MailArchive aktiv | F2-Tab sichtbar, Historie aus `frosh_mail_archive` |
| MailArchive fehlt/inaktiv | F2-Tab ausgeblendet, Hinweis-Card in der Plugin-Config; F1/F3/F4 laufen weiter |

**Verifiziert (Phase 0, 11.07.2026):** `frosh_mail_archive` speichert `order_id` (+ `order_version_id`, seit ~v3.2) und ab **v3.6.0** auch `mail_template_id`; `customerId` ebenfalls vorhanden. Der Runtime-Guard prüft daher auf **MailArchive ≥ 3.6**. Mails über `AbstractMailService` landen automatisch im Archiv (Subscriber auf `MailBeforeSentEvent` / Symfony `SentMessageEvent`) — **aber nur, wenn `orderId`/`customerId`/`templateId` im `$data`-Array von `send()` mitgegeben werden** (Transport als `X-Frosh-*`-Header). `HugMailSender` setzt diese Keys immer.

**Entscheidung (Phase 0):** `hug_mail_reference` bleibt trotzdem — minimal, als **Audit-/Zuordnungs-Layer** für Daten, die MailArchive nicht speichert (wer hat gesendet, welches Dokument, welcher Einstieg). F2-Historie liest primär direkt `frosh_mail_archive` (Filter über `orderId`/`customerId`).

```
hug_mail_reference
  id, mail_archive_id (nullable, keine harte FK — MailArchive ist optional),
  order_id (nullable), document_id (nullable),
  source (free|document|preview), sent_by_user_id, created_at
```

Das ist **kein Log-Duplikat** — Inhalte bleiben in MailArchive, wir speichern nur Audit-Metadaten und die Dokument-Zuordnung.

**Weitere Entity:** keine. Signatur/Briefpapier → Core `mail_header_footer` (pro Sales Channel bereits vorhanden, wird von `MailService` automatisch angewandt). → *Offene Frage aus §7 damit beantwortet: kein eigenes Signatur-Feature.*

---

## 2. Feature F1 — Freie E-Mail verfassen

### UI-Platzierung
- **Bestelldetail:** Button in der Smart Bar (`sw-order-detail__smart-bar-actions`), Label „E-Mail senden". Kein Kontextmenü — die Aktion ist zu prominent für ein Untermenü.
- **Kundendetail:** identischer Button in `sw-customer-detail__actions`. Gleiches Modal, Kontext = Customer (ohne Order-Variablen).
- **Modal statt eigener Route:** Bestellung ist bereits geladen, kein Kontextverlust, kein Zurück-Navigations-Problem. Größe: `full` (Editor braucht Platz).

### Modal-Aufbau
```
┌─ E-Mail senden — Bestellung 10024 ─────────────────────┐
│ Empfänger  [max@kunde.de          ] (+ CC/BCC ausklappbar)
│ Vorlage    [Entity-Select: mail_template ▾] → lädt als Kopie
│ Betreff    [                                          ]
│ ─────────────────────────────────────────────────────
│ [ Einfach | Twig ]  ← Toggle                          │
│ ┌──────────────────────────┬────────────────────────┐ │
│ │ Editor (mt-text-editor   │ Variablen-Picker       │ │
│ │ bzw. sw-code-editor)     │ ▸ order                │ │
│ │                          │   orderNumber          │ │
│ │                          │   amountTotal ...      │ │
│ └──────────────────────────┴────────────────────────┘ │
│ Anhänge  ☑ Rechnung 2026-1042.pdf                     │
│          ☐ Lieferschein …                             │
│          [+ Datei hochladen]                          │
│ Sprache: DE (aus Bestellung)                          │
│                          [Vorschau] [Abbrechen] [Senden]
└────────────────────────────────────────────────────────┘
```

- **Dual-Editor:** beide Modi schreiben auf dasselbe `contentHtml`. Umschalten HTML→Twig verlustfrei; Twig→WYSIWYG mit Warnung („Twig-Blöcke werden als Text angezeigt"). `contentPlain` wird beim Senden aus HTML generiert.
- **Variablen-Picker:** Sidebar-Panel, Klick fügt `{{ order.orderNumber }}` an Cursorposition ein. Quelle: Root-Keys des tatsächlich gebauten Twig-Contexts (§5, `MailContextBuilder`) — nicht hartkodiert.
- **Vorlagenauswahl = Kopie**, keine Live-Bindung. Änderungen im Modal ändern nie die Vorlage.
- **Vorschau-Button** rendert den aktuellen Editor-Inhalt gegen den echten Order-Context (teilt Code mit F4).

### Bulk-Mail aus der Bestellliste
**Nein in 1.0.** Begründung: DSGVO/Newsletter-Abgrenzung, Missbrauchsrisiko bei Store-Verkauf, braucht MessageQueue + Rate-Limiting. Kandidat für 1.1.

---

## 3. Feature F2 — Historie-Tab

- **Eigener Tab**, keine Card: `sw-order-detail` → Child-Route `sw.order.detail.mails`; analog `sw.customer.detail.mails`. Tab-Label mit Badge (Anzahl).
- Card im General-Tab wäre bei >5 Mails unbrauchbar.

**Spalten:** Datum · Betreff · Empfänger · Vorlage (MailArchive ≥3.6: `mailTemplateId`) · Status/Fehler · 📎

**Zeilen-Aktionen:**
| Aktion | Umsetzung |
|---|---|
| Ansehen | Modal mit HTML-Preview (iframe, sandboxed) |
| EML herunterladen | Deep-Link MailArchive |
| Erneut senden | Deep-Link ins MailArchive-Modul (nicht nachbauen) |
| Antwort verfassen | öffnet F1-Modal, vorbefüllt (Empfänger, `Re:`-Betreff) |

---

## 4. Feature F3 — Dokumente einzeln mailen

- **Kontextmenü in der Dokumenten-Grid-Zeile** (`sw-order-document-card`) → „Per E-Mail senden (Cockpit)" → öffnet **das F1-Modal** mit vorselektiertem Anhang und vorbelegter Vorlage.
- **Verifiziert + Entscheidung (Phase 0):** Der Core hat im Kontextmenü bereits eine eigene „Senden"-Aktion (Block `sw_order_document_card_grid_action_send`, eigenes Modal). Unsere Aktion kommt **zusätzlich daneben** (klar benannt, kein Eingriff ins Core-Verhalten); Einhängepunkt: Block `sw_order_document_card_grid_actions`.
- **Bulk:** Grid-Multiselect + Aktion „Markierte senden" → **eine** Mail mit n Anhängen (nicht n Mails). **Verifiziert (Phase 0):** Das Core-Grid (`sw-entity-listing`) hat `:show-selection="false"` hartkodiert — wir aktivieren die Selection per Template-Override (bewusst akzeptiertes, kleines Update-Risiko).
- **Config:** Mapping-Card in der Plugin-Konfiguration, `documentType` → `mailTemplateId` (Dokumenttypen dynamisch geladen). Fallback: leere Vorlage.
- F3 ist damit fast nur UI — der ganze Versandpfad ist F1.

---

## 5. Feature F4 — Preview mit echten Daten

- **Erweiterung von `sw-mail-template-detail`** (kein eigenes Modul): Card „Test mit echter Bestellung".
- Felder: Entity-Single-Select `order` (sortiert `createdAt DESC`, Suche über Bestellnummer) + optional Event-Auswahl (MailAware-Events, in 1.0 nur Order-basierte) + Empfänger-Feld für Testversand.
- Buttons: **Vorschau** (Modal, gerendertes HTML) · **Testmail senden**.
- **Twig-Fehler sichtbar machen:** Renderer fängt `Twig\Error\Error`, gibt Message + Zeile zurück, Anzeige als roter Alert mit Codezeile.
- **Knackpunkt:** Templates erwarten je nach Flow-Event unterschiedliche Root-Variablen. 1.0: Order-Context + Dummy-Fallback für unbekannte Keys (undefined → sichtbarer Platzhalter statt Exception, via `StringLoader` + strict_variables=false-Fallback-Pass, der die fehlenden Variablen *meldet*).

---

## 6. Backend-Schnitt (PHP)

| Service | Aufgabe |
|---|---|
| `HugMailSender` | Wrappt **`AbstractMailService`**. Baut MailData: recipients (E-Mail⇒Name-Map)/cc/bcc, subject, contentHtml/Plain, `salesChannelId`, binAttachments. **Verifiziert (Phase 0):** `MailService` kennt keinen `documentIds`-Key — Dokumente werden vorab vom `AttachmentResolver` zu binAttachments aufgelöst. Setzt zusätzlich immer `orderId`/`customerId`/`templateId` in `$data`, damit MailArchive die Verknüpfung schreibt (§1). |
| `MailContextBuilder` | Order/Customer → Twig-Context. Wiederverwendung von `OrderConverter` (Core) für die Order-Struktur. Liefert auch die Key-Liste für den Variablen-Picker. |
| `AttachmentResolver` | `documentId` → `DocumentGenerator::readDocument(..., fileType: null)`. **Verifiziert (Phase 0):** liefert `RenderedDocument` mit String-Blob (`getContent()`), **kein Stream/Rewind in 6.7**; generiert fehlende PDFs on-the-fly. Muster wie Core `MailAttachmentsBuilder::mappingAttachments()`: `['content','fileName','mimeType']`. Uploads → `MediaService::getAttachment()`, privater Ordner `hug_mail_attachment`. |
| `TemplatePreviewRenderer` | `StringTemplateRenderer` + Twig-Fehler-Mapping (F1-Vorschau + F4). |
| `MailReferenceWriter` | Post-Send: schreibt `hug_mail_reference` (Verknüpfung Mail ↔ Order/Dokument). |
| `DocumentMailTemplateMapper` | Config-Mapping documentType → mailTemplate. |

**API-Routen (`/api/_action/hug-mail-cockpit/`):** `send`, `preview`, `variables`, `history`.

**Sprache:** Mail-Context immer mit `order.languageId` bzw. `customer.languageId` — **nie** Admin-Sprache. Anzeige im Modal („Sprache: DE (aus Bestellung)"). Kein manuelles Override in 1.0.

---

## 7. Admin-Extension-Punkte & 6.7-Strategie

- **Vue-Overrides** für Tabs/Smart-Bar (Admin Extension SDK deckt Detail-Seiten-Tabs noch nicht sauber ab). **Komponenten-Regel (Entscheidung Phase 0, pragmatisch):** Meteor (`mt-*`) überall, wo eine Meteor-Komponente im Admin verdrahtet ist (`mt-button`, `mt-card`, `mt-modal`, `mt-text-editor`, `mt-banner`, …) — strukturelle `sw-*`-Komponenten **ohne** Meteor-Pendant bleiben erlaubt (`sw-tabs-item`, `sw-context-menu-item`, `sw-entity-single-select`, `sw-entity-listing`, `sw-code-editor` — Letzterer ist in 6.7 „ready", nicht deprecated). Keine Kompatibilitätsbrücken Richtung 6.6; die Core-Seiten selbst mischen in 6.7.10 genauso.
- **Verifizierte Einhängepunkte (Phase 0):**
  - Order Smart Bar: Block `sw_order_detail_actions_slot_smart_bar_actions`
  - Customer Smart Bar: Block `sw_customer_detail_actions` (mit `{% parent %}`)
  - Order-Tab: leerer Extension-Block `sw_order_detail_content_tabs_extension` + Kind-Route via `routeMiddleware` (Tabs sind Router-Children unter `sw.order.detail`)
  - Customer-Tab: Block `sw_customer_detail_content_tab_after` + Kind-Route analog
  - Dokumenten-Kontextmenü: Block `sw_order_document_card_grid_actions`
  - F4-Card: Card-Blöcke in `sw_mail_template_detail_content` per `{% parent %}` erweitern (kein dedizierter Extension-Block vorhanden)
  - Hinweis 6.8: `sw-tabs` rendert hinter Feature-Flag `V6_8_0_0` intern `mt-tabs` (Items-Prop statt Slots) — in 6.7.10 funktioniert der Slot-Weg, Migration bei 6.8 einplanen.
- Überschriebene Komponenten:
  - `sw-order-detail` (Smart Bar, Tab-Registrierung)
  - `sw-customer-detail` (dito)
  - `sw-order-document-card` (Grid-Kontextmenü)
  - `sw-mail-template-detail` (F4-Card)
- Eigene Komponenten: `hug-mail-compose-modal`, `hug-mail-variable-picker`, `hug-mail-history-grid`, `hug-mail-preview-card`.

**ACL-Privileges:**
| Privilege | Bedeutung |
|---|---|
| `hug_mail_cockpit.viewer` | Historie sehen |
| `hug_mail_cockpit.sender` | Dokumente versenden (F3) |
| `hug_mail_cockpit.free_sender` | freie Mails verfassen (F1) |
| `hug_mail_cockpit.twig_editor` | Twig-Modus im Editor |

Rolle „Kundenservice" = viewer + sender + free_sender, **ohne** twig_editor. → deckt die „Chefin"-Persona sauber ab.

**Feature-Toggles:** F1–F4 einzeln abschaltbar (HugAdcell-Muster).

---

## 8. Aufwandsschätzung

| Paket | PT |
|---|---|
| Skeleton, Config, ACL, Forgejo-CI | 1,5 |
| F1 Backend (Sender, ContextBuilder, AttachmentResolver, Routen) | 3,0 |
| F1 Admin (Modal, Dual-Editor, Variablen-Picker, Anhänge) | 4,5 |
| F3 (Grid-Aktion, Mapping-Config, Bulk) | 1,5 |
| F2 (Tabs Order+Customer, Grid, Reference-Layer) | 2,5 |
| F4 (Preview-Route, Renderer, Card) | 2,5 |
| Tests (PHPUnit + Jest-Snapshots) | 2,0 |
| i18n, Doku, Store-Vorbereitung | 2,0 |
| **Summe** | **19 PT** |
| + Store-Review-Iteration & Puffer | **≈ 23 PT** |

*(6.7-only spart ggü. Dual-Support ca. 1 PT Test-/Kompatibilitätsaufwand.)*

**Reihenfolge:** Skeleton → F1 Backend → F1 Admin → **F3** (fällt fast ab, sobald F1-Modal steht) → F2 → F4 → Tests/Store.

---

## 9. Für Max

- Admin-Komponenten: `hug-mail-variable-picker` + Jest-Snapshots
- Review des ACL-/Sicherheits-Layers (freier Mailversand = Missbrauchsvektor)
- Gegenlesen des Dual-Editor-Verhaltens (HTML ⇄ Twig-Umschaltung, Datenverlust-Kanten)

## 10. Risiken

1. ~~MailArchive-Schema (`orderId`?)~~ → **geklärt (Phase 0):** orderId/mailTemplateId vorhanden, Guard auf ≥ 3.6; `hug_mail_reference` bleibt als Audit-Layer (§1)
2. ~~Stream-Handling bei `DocumentGenerator`~~ → **entfällt (Phase 0):** `readDocument()` liefert in 6.7 einen String-Blob, kein Stream (§6)
3. Twig-Templates, die Flow-Event-Variablen erwarten (F4)
3a. Neu: Template-Override auf `sw-order-document-card` für Bulk-Multiselect (`show-selection`) — kleines Bruchrisiko bei Core-Updates (§4)
4. Store-Reichweite: 6.7-only schließt Shops auf 6.6 aus — bewusst akzeptiert (schnellere Entwicklung, sauberer Meteor-Code)

## 11. Lizenz & Preis (Empfehlung, Portfolio-Entscheidung)

- **Proprietär**, nicht AGPL (HugDatevExport-Muster hier nicht übernehmen).
- **Einmalpreis + optionale Wartung** — die Marktlücke ist gerade „nur Abo-Konkurrenz". Einmalpreis ist das Differenzierungsmerkmal.
- Der Fehler-Logging-PR an MailArchive (Issue #36) bleibt davon unberührt → Community-Goodwill, kein Feature-Verlust.
