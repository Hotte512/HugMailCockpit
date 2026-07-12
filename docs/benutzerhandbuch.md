# HugMailCockpit — Benutzerhandbuch

Willkommen beim **Mail-Cockpit**! Mit diesem Plugin verschickst du E-Mails an deine Kunden direkt aus der Shopware-Administration — ohne Umweg über ein externes Mailprogramm:

- **E-Mails schreiben** direkt aus einer Bestellung oder einem Kundenprofil heraus — mit fertigen Vorlagen, die automatisch mit den echten Bestelldaten ausgefüllt werden.
- **Mail-Historie einsehen:** Welche E-Mails hat dieser Kunde bzw. diese Bestellung schon bekommen? Der Tab „E-Mails" zeigt es dir — inklusive Vorschau und Antwort-Funktion.
- **Dokumente versenden:** Rechnung, Lieferschein & Co. mit zwei Klicks per E-Mail verschicken — auch mehrere Dokumente auf einmal in einer einzigen Mail.
- **Vorlagen testen** (für Fortgeschrittene): E-Mail-Vorlagen mit einer echten Bestellung durchspielen, bevor sie an Kunden gehen.

Alle E-Mails werden im Design deines Shops verschickt (Briefpapier des Verkaufskanals) und immer in der Sprache der Bestellung bzw. des Kunden — nicht in deiner Admin-Sprache.

---

## 1. Der Tab „E-Mails" — deine Zentrale

Der zentrale Ort für alles rund um E-Mails ist der **Tab „E-Mails"** — du findest ihn sowohl im **Bestelldetail** als auch im **Kundendetail**. Das Tab-Label zeigt dir gleich mit, wie viele E-Mails bereits archiviert sind, z. B. **„E-Mails (3)"**.

*[Screenshot: Bestelldetail mit Tab „E-Mails (3)"]*

So ist der Tab aufgebaut:

- **Links** siehst du die Liste aller archivierten E-Mails mit den Spalten **Datum**, **Betreff**, **Empfänger**, Vorlage und **Status** („Gesendet", „Ausstehend", „Fehlgeschlagen") sowie einem Büroklammer-Symbol bei Anhängen.
- **Rechts** ist die Vorschau: *„E-Mail in der Liste anklicken, um die Vorschau zu sehen — Doppelklick öffnet die Großansicht."*
- Oben rechts im Card-Header sitzt der Button **„E-Mail senden"** — damit schreibst du eine neue E-Mail (siehe Abschnitt 2).

### E-Mails ansehen

1. Öffne eine Bestellung (oder einen Kunden) und wechsle in den Tab **„E-Mails"**.
2. Klicke eine Zeile in der Liste an → rechts erscheint die Vorschau der E-Mail.
3. **Doppelklick** auf eine Zeile öffnet die Großansicht.

### Aktionen je E-Mail (Kontextmenü)

Jede Zeile hat rechts ein Kontextmenü („…") mit drei Aktionen:

- **„Ansehen"** — öffnet die E-Mail in der Großansicht.
- **„Antwort verfassen"** — öffnet das Schreiben-Fenster, bereits vorbefüllt mit dem Empfänger und dem Betreff mit vorangestelltem „Re:". Praktisch, wenn du auf eine bereits verschickte Mail Bezug nehmen willst.
- **„Im Mail-Archiv öffnen (EML, erneut senden)"** — springt in das Mail-Archiv. Dort kannst du die E-Mail als EML-Datei herunterladen oder unverändert erneut senden.

> **Hinweis:** Die Historie stammt aus dem Mail-Archiv-Plugin (FroshPlatformMailArchive). Ist es nicht installiert, siehst du stattdessen den Hinweis *„Mail-Historie nicht verfügbar"* — E-Mails schreiben und Dokumente versenden funktioniert trotzdem. Mehr dazu im Admin-Abschnitt.

---

## 2. Eine E-Mail schreiben

1. Öffne die Bestellung (oder den Kunden) und wechsle in den Tab **„E-Mails"**.
2. Klicke oben rechts auf **„E-Mail senden"**. Es öffnet sich ein großes Fenster.
3. **Empfänger:** ist bereits mit der E-Mail-Adresse aus der Bestellung bzw. dem Kundenkonto vorbefüllt. Über **„CC/BCC"** klappst du bei Bedarf weitere Adressfelder auf. Mehrere Adressen trennst du mit Komma.
4. **Vorlage (optional):** Wähle eine E-Mail-Vorlage aus. Sie wird **sofort** mit den echten Daten der Bestellung ausgefüllt — in der Sprache der Bestellung. Du siehst und bearbeitest also direkt das fertige Ergebnis, keine Platzhalter. Keine Sorge: *„Die Vorlage wird als Kopie geladen – Änderungen hier verändern die Vorlage nicht."*
5. **Betreff:** wird von der Vorlage vorbefüllt oder frei eingetippt.
6. **Text schreiben:** Im Editor-Modus **„Einfach"** schreibst du wie in einem normalen Textprogramm (fett, kursiv, Listen …). Den Modus **„Twig"** gibt es nur für Nutzer mit Spezialrecht — für den Alltag brauchst du ihn nicht.
7. **Variablen einfügen:** Rechts neben dem Editor sitzt der Variablen-Picker. Ein Klick auf einen Eintrag (z. B. die Bestellnummer) **fügt den echten Wert aus der Bestellung an der Cursorposition ein**. Mit dem Suchfeld *„Variable suchen …"* findest du schnell die passende Variable. Standardmäßig siehst du die wichtigsten Variablen mit verständlichen Namen (Bestellnummer, Gesamtbetrag, …) — der Haken „Alle Variablen anzeigen (Expertenansicht)" blendet bei Bedarf alle weiteren ein.
8. **Anhänge:** Unter „Anhänge" kannst du vorhandene Bestell-Dokumente (Rechnung, Lieferschein …) einfach ankreuzen. Über **„Datei hochladen"** hängst du eigene Dateien an — sie landen im Medien-Ordner „Mail-Cockpit Anhänge".
9. **Sprache:** Das Feld „Sprache" zeigt dir, in welcher Sprache die Mail rausgeht — sie kommt aus der Bestellung bzw. dem Kunden, nicht aus deinen Admin-Einstellungen.
10. **„Vorschau"** zeigt dir die E-Mail so, wie sie beim Kunden ankommt — inklusive Kopf- und Fußzeile (Briefpapier) deines Verkaufskanals, sofern im Shop eingerichtet.
11. Klicke auf **„Senden"**. Bei Erfolg erscheint *„Die E-Mail wurde versendet."* — und die Mail taucht sofort in der Historie auf. Mit **„Abbrechen"** verwirfst du den Entwurf.

*[Screenshot: Compose-Fenster mit Editor, Variablen-Picker und Anhängen]*

> **Tipp:** Empfänger, Betreff und Inhalt müssen ausgefüllt sein — sonst meldet das Fenster *„Empfänger, Betreff und Inhalt dürfen nicht leer sein."*

---

## Textvorlagen: Textbausteine wiederverwenden

Für wiederkehrende Formulierungen (Grußformeln, Standardantworten, Rückgabe-Hinweise) gibt es **Textvorlagen**:

1. Öffne beim Schreiben einer E-Mail das Dropdown **„Textvorlage einfügen …"** (rechts neben den Editor-Schaltflächen „Einfach"/„Twig").
2. Wähle eine Vorlage aus — der Text wird an der aktuellen Cursorposition eingefügt.
3. Du kannst den eingefügten Text danach frei bearbeiten.

**Eigene Textvorlage direkt beim Schreiben anlegen:** Klicke unten im E-Mail-Fenster auf „Als Textvorlage speichern", vergib einen Namen — fertig. Achtung: Der Inhalt wird genau so gespeichert, wie er gerade im Editor steht; bereits eingefügte Werte (z. B. eine Bestellnummer) stehen dann wörtlich in der Vorlage.

💡 Textvorlagen dürfen einfache Variablen enthalten (z. B. `{{ order.orderNumber }}`) — sie werden beim Versand automatisch durch die echten Werte ersetzt.

**Textvorlagen anlegen und pflegen** (Admin): *Erweiterungen → Meine Erweiterungen → Mail-Cockpit → Konfiguration* → Bereich **„Textvorlagen (Textbausteine)"**. Dort kannst du Vorlagen anlegen („Neue Textvorlage"), bearbeiten und löschen.

---

## 3. Dokumente per E-Mail versenden

Rechnung oder Lieferschein an den Kunden schicken — direkt aus der Dokumentenliste:

### Ein einzelnes Dokument

1. Öffne die Bestellung und wechsle in den Tab **„Dokumente"**.
2. Öffne das Kontextmenü („…") in der Zeile des Dokuments.
3. Klicke auf **„Per E-Mail senden (Cockpit)"**.
4. Das bekannte Schreiben-Fenster öffnet sich: Das Dokument ist bereits als Anhang angekreuzt, und die zum Dokumenttyp passende Vorlage ist schon geladen und mit den Bestelldaten ausgefüllt (welche Vorlage das ist, legt dein Admin in der Plugin-Konfiguration fest — ohne Zuordnung startest du mit einer leeren E-Mail).
5. Prüfen, ggf. anpassen, **„Senden"** — fertig.

### Mehrere Dokumente auf einmal

1. Kreuze im Dokumenten-Grid die gewünschten Zeilen an.
2. Klicke auf den Button **„Markierte per E-Mail senden (n)"** — n ist die Anzahl der markierten Dokumente.
3. Es wird **eine einzige E-Mail** mit allen markierten Dokumenten als Anhängen vorbereitet — der Kunde bekommt also nicht drei Mails, sondern eine mit drei Anhängen.

*[Screenshot: Dokumenten-Grid mit Mehrfachauswahl und Button „Markierte per E-Mail senden (2)"]*

---

## 4. E-Mail-Vorlagen mit echten Daten testen

Dieser Abschnitt richtet sich an alle, die E-Mail-Vorlagen pflegen. Statt zu raten, wie eine Vorlage beim Kunden aussieht, testest du sie mit einer echten Bestellung:

1. Öffne die gewünschte Vorlage unter *Einstellungen → E-Mail-Templates*.
2. Scrolle zur Card **„Test mit echter Bestellung"**.
3. Wähle im Feld **„Bestellung"** eine Bestellung aus — einfach die Bestellnummer ins Suchfeld tippen (*„Bestellnummer suchen …"*).
4. Klicke auf **„Vorschau"**: Die Vorlage wird mit den echten Daten dieser Bestellung gerendert — in der Sprache der Bestellung.
   - Enthält die Vorlage einen Twig-Fehler, wird er dir mit Zeilenangabe angezeigt (*„Twig-Fehler in Zeile …"*).
   - Fehlende Flow-Variablen (Werte, die nur beim automatischen Versand existieren) werden gemeldet, **blockieren die Vorschau aber nicht** — sie erscheinen einfach als Leerstelle.
5. Trage unter **„Empfänger für Testversand"** eine E-Mail-Adresse ein (z. B. deine eigene) und klicke auf **„Testmail senden"**. Du erhältst genau das gerenderte Ergebnis in dein Postfach — *„Die Testmail wurde versendet."*

*[Screenshot: Card „Test mit echter Bestellung" auf der Vorlagen-Detailseite]*

---

## 5. Häufige Fragen (FAQ)

**Warum sehe ich den Tab „E-Mails" nicht?**
Drei mögliche Gründe:
1. Dir fehlt das Recht — bitte deinen Admin, dir die Berechtigung **„Mail-Historie sehen"** (und zum Schreiben **„Freie E-Mails verfassen"**) zu geben.
2. Die Funktion ist in der Plugin-Konfiguration abgeschaltet (Feature-Schalter F1/F2).
3. Für die Historie fehlt das Plugin **FroshPlatformMailArchive** (Version ≥ 3.6) — dann erscheint im Tab der Hinweis *„Mail-Historie nicht verfügbar"*.

**Warum ist die Mail-Liste leer, obwohl der Kunde schon Mails bekommen hat?**
Das Archiv sammelt E-Mails erst ab dem Zeitpunkt, an dem das Mail-Archiv-Plugin aktiv ist. Ältere Mails tauchen nicht rückwirkend auf. Es erscheint dann *„Noch keine E-Mails"*.

**Warum hat meine E-Mail kein Shop-Design (Logo, Fußzeile)?**
Das Design kommt aus dem **Briefpapier** (Kopf-/Fußzeile) des Verkaufskanals. Ist im Shop keines eingerichtet oder dem Verkaufskanal nicht zugewiesen, geht die Mail „nackt" raus. Das richtet dein Admin ein — siehe Admin-Abschnitt.

**Warum sehe ich den Modus „Twig" im Editor nicht?**
Der Twig-Modus ist ein Expertenmodus und an ein eigenes Recht gebunden (**„Twig-Modus im Editor verwenden"**). Für normale E-Mails brauchst du ihn nicht — der Modus „Einfach" plus Variablen-Picker deckt den Alltag ab.

**Ich habe eine Vorlage im Schreiben-Fenster verändert — ist die Vorlage jetzt kaputt?**
Nein. Die Vorlage wird immer als Kopie geladen; deine Änderungen betreffen nur die eine E-Mail, die du gerade schreibst.

**Kann der Kunde meine Mail in seiner Sprache bekommen, obwohl mein Admin auf Deutsch steht?**
Ja, das passiert automatisch: Vorlagen werden immer in der **Sprache der Bestellung bzw. des Kunden** gerendert. Das Feld „Sprache" im Schreiben-Fenster zeigt dir, welche das ist.

**Warum fehlt der Button „Per E-Mail senden (Cockpit)" im Dokumenten-Grid?**
Entweder fehlt dir das Recht **„Dokumente per E-Mail versenden"**, oder die Funktion F3 ist in der Plugin-Konfiguration deaktiviert.

**Was bedeutet der Status „Fehlgeschlagen" in der Historie?**
Die E-Mail konnte nicht zugestellt werden (z. B. Mailserver-Problem oder ungültige Adresse). Über das Kontextmenü → **„Im Mail-Archiv öffnen (EML, erneut senden)"** kannst du sie im Mail-Archiv erneut senden.

---

## 6. Für Admins: Rechte & Konfiguration

### Berechtigungen (Rollenverwaltung)

Unter *Einstellungen → System → Benutzer & Rechte → Rollen* findest du im Bereich **Zusätzliche Berechtigungen** die Gruppe **„Mail-Cockpit"** mit vier Rechten:

| Berechtigung | Erlaubt |
|---|---|
| **Mail-Historie sehen** | Tab „E-Mails" mit Historie und Vorschau nutzen |
| **Dokumente per E-Mail versenden** | Dokumente aus dem Dokumenten-Grid mailen (F3) |
| **Freie E-Mails verfassen** | Das Schreiben-Fenster nutzen (F1) |
| **Twig-Modus im Editor verwenden** | Experten-Modus „Twig" im Editor |

**Empfehlung für die Kundenservice-Rolle:** *Mail-Historie sehen* + *Dokumente per E-Mail versenden* + *Freie E-Mails verfassen* — **ohne** *Twig-Modus im Editor verwenden*. Der Twig-Modus ist bewusst ein separates Recht: Freier Twig-Code im Mailversand ist ein potenzieller Missbrauchsvektor und gehört nur in die Hände von Nutzern, die Vorlagen pflegen.

### Plugin-Konfiguration

Unter *Erweiterungen → Meine Erweiterungen → HugMailCockpit → Konfiguration* (Card **„Funktionen"**):

| Option | Wirkung |
|---|---|
| **F1: Freie E-Mails verfassen** | Schaltet das Schreiben von freien E-Mails an/aus |
| **F2: Mail-Historie-Tab** | Schaltet die Historie im Tab „E-Mails" an/aus |
| **F3: Dokumente per E-Mail versenden** | Schaltet die Aktionen im Dokumenten-Grid an/aus |
| **Vorlagen-Zuordnung je Dokumenttyp (F3)** | Legt je Dokumenttyp (Rechnung, Lieferschein …) fest, welche E-Mail-Vorlage beim Dokumentversand vorbelegt wird. Ohne Zuordnung startet der Versand mit leerer E-Mail. |
| **F4: Template-Vorschau mit echten Bestelldaten** | Schaltet die Card „Test mit echter Bestellung" an/aus |

Alle Feature-Schalter stehen standardmäßig auf **aktiv**.

**Voraussetzung für die Historie (F2):** das Plugin **FroshPlatformMailArchive in Version ≥ 3.6**. Ohne dieses Plugin bleibt die Historie ausgeblendet und im Tab erscheint der Hinweis *„Mail-Historie nicht verfügbar"* — F1, F3 und F4 funktionieren unabhängig davon.

### Tipp: Briefpapier einrichten

Damit freie E-Mails automatisch das Shop-Design (Logo, Kopf- und Fußzeile) bekommen: Unter *Einstellungen → E-Mail-Templates* eine **Kopf-/Fußzeile** (Briefpapier) anlegen und den Verkaufskanälen zuweisen. Das Mail-Cockpit nutzt das Briefpapier des Verkaufskanals automatisch — sowohl in der Vorschau als auch beim Versand.
