# GWS PopOvers 1.2.1

Vollständiges WordPress-Plugin auf Basis des bereitgestellten Projekts.

## Installation

ZIP im WordPress-Backend unter Plugins → Installieren → Plugin hochladen installieren. Beim Upgrade bestehendes Plugin ersetzen, nicht zusätzlich parallel aktivieren. Cache/CDN nach dem Update leeren.

## Einstellungen

Unter **GWS PopOvers** einen Eintrag erstellen und Gutenberg-Inhalte eingeben.

- **Auslöser:** Exit Intent (Desktop, oberer Viewport-Rand) oder Zeitgesteuert (Millisekunden nach `window.load`).
- **Darstellung:** Modal (`showModal()`, blockiert Hintergrund, Fokus im Dialog) oder Nicht-modal (`show()`, Hintergrund bedienbar, rechts unten fixiert).
- **Zielseiten:** Keine Auswahl bedeutet global; sonst ausgewählte Seiten/Beiträge.
- **Cookie-Dauer:** Tage, 0 = Sessioncookie. Wird bei jeder Schliessmethode gesetzt (auch Escape).
- **SCF/ACF:** Feldgruppe wird bei `acf/init` registriert, ansonsten werden WordPress-Metaboxen angeboten. Bestehende Meta-Keys und Popover-Beiträge bleiben erhalten.

## Priorität und Ablauf

Nur ein GWS-Dialog zugleich. Exit-Intent-Ereignisse werden **vor wartenden Timern** einsortiert. Ein bereits geöffnetes Fenster wird **nicht** unterbrochen. Jede Instanz wird pro Page-Load einmal gezeigt; nach dem Schliessen setzt das Plugin ein Cookie pro Popover-ID.

## Barrierefreiheit und SEO

Die Dialoge besitzen eine zugängliche Bezeichnung aus dem Beitragstitel (`aria-labelledby`), einen sichtbaren Schliessen-Button sowie eine Schliessmöglichkeit über Escape für modale Dialoge. Beide Varianten besitzen ein explizites Schliessverhalten. Bei modalen Dialogen (`showModal()`) übernimmt der Browser wesentliche Aufgaben der Fokusbegrenzung und des Fokusmanagements; für nicht-modale Dialoge (`show()`) wird der Fokus bei Schliessung soweit sinnvoll wiederhergestellt. Die CSS-Regeln berücksichtigen `prefers-reduced-motion`.

**Wichtig:** Die Verwendung von `<dialog>` ist an sich kein SEO-Problem. Entscheidend sind **Darstellung, Auslösezeitpunkt, Grösse und Zugänglichkeit des Hauptinhalts**. Google empfiehlt, störende Interstitials und Dialoge zu vermeiden, die Inhalte verdecken oder deren Zugriff erschweren. Eine Google-Abwertung erfolgt nicht automatisch bei jedem modalen Dialog. Es gibt auch keine allgemeine Garantie, dass nicht-modale Dialoge SEO-neutral sind: Ein grosses, ablenkendes oder den Inhalt verdeckendes nicht-modales Fenster kann ebenfalls störend sein.

### Was bedeutet das für SEO-sensitive Landingpages?

Beispiel: Ein Besucher kommt aus Google auf eine Angebotsseite. Öffnet sich sofort oder nach wenigen Sekunden ein grosser modaler Newsletter-Dialog, kann der Nutzer den eigentlich gesuchten Inhalt zunächst nicht bedienen. Auf Smartphones ist dieses Risiko besonders hoch, weil schon mittelgrosse Overlays einen grossen Teil des sichtbaren Bereichs einnehmen. Solche Unterbrechungen können die Nutzererfahrung verschlechtern und laut Google das Verstehen von Seiteninhalten durch Suchmaschinen erschweren; dadurch kann auch die Suchleistung leiden.

### Empfehlungen für die beiden Darstellungen

| Situation | Empfehlung | Warum? |
| --- | --- | --- |
| Organische SEO-Landingpage / Angebotsseite | Bevorzugt **nicht-modal**, klein und zurückhaltend | Hauptinhalt bleibt zugänglich und bedienbar |
| Längere Blogartikel | Kleiner nicht-modaler Hinweis, sparsam eingesetzt | Verhindert abrupte Unterbrechungen beim Lesen |
| Exit Intent auf Desktop | Modal **möglich**, aber aufdringliche Gestaltung vermeiden | Auf den tatsächlichen Nutzungskontext achten; keine Ranking-Garantie |
| Zeitgesteuerte Marketingkampagne | Möglichst nicht-modal oder nach bewusster Nutzeraktion | Ein Timer allein macht ein Overlay nicht unaufdringlich |
| Rechtlich erforderliche Einwilligung / Alterskontrolle | Je nach Anforderung modal | Google beschreibt für erforderliche Interstitials gesonderte Ausnahmen und Empfehlungen |

**Wichtig für mobile Geräte:** Exit Intent per Mausbewegung funktioniert nur mit geeigneten Zeigegeräten; es ist kein verlässlicher Trigger für Touchgeräte. Zeitgesteuerte Kampagnen können dagegen auch mobil erscheinen. Sie sollten dort besonders sorgfältig konfiguriert werden.

### Barrierefreiheit: Prüfpunkte vor dem Livegang

- Aussagekräftigen PopOver-Titel vergeben; er wird als zugängliche Dialogbezeichnung verwendet.
- Tastaturtest durchführen: Öffnen, Tab/Shift+Tab, Schliessen-Button und Escape testen; bei nicht-modalen Dialogen auch die weiterhin erreichbare Seite prüfen.
- Fokus vor und nach dem Öffnen/Schliessen prüfen, besonders bei automatischen Timern und komplexen Gutenberg-Inhalten.
- Auf ausreichende Farbkontraste, gut sichtbare Fokusmarkierungen und verständliche Formulare achten.
- Auf Smartphone und mit Screenreadern testen (z. B. NVDA/VoiceOver); auch die tatsächlichen Gutenberg-Blöcke beeinflussen die Barrierefreiheit.
- Nicht davon ausgehen, dass natives `<dialog>` allein eine vollständige WCAG-Konformität garantiert.

### Offizielle Quellen

- Google Search Central: **Störende Interstitials und Dialogfelder vermeiden**  
  https://developers.google.com/search/docs/appearance/avoid-intrusive-interstitials?hl=de
- Google Search Central: **Page Experience in Google Search**  
  https://developers.google.com/search/docs/appearance/page-experience
- web.dev: **HTML dialog (modal und nicht-modal)**  
  https://web.dev/learn/html/dialog

**SEO-Fazit:** Nicht automatisch erscheinende oder zurückhaltende, kleinflächige Hinweise sind für inhaltsorientierte Seiten häufig die bessere Wahl. Die konkrete Ausgestaltung ist entscheidender als die Wahl von `show()` oder `showModal()` allein. Ranking- oder WCAG-Garantien können daraus nicht abgeleitet werden.

## Dateien

- `gws-popovers.php`: Plugin Bootstrap
- `includes/config.php`: CPT, Meta und SCF/ACF/Metaboxen
- `includes/frontend.php`: Seitenfilter, Assets, Dialog-HTML
- `assets/popovers.js`: Timer, Exit Intent, Queue, Dismissal, Fokus
- `assets/popovers.css`: Styling mit `theme.json`-Variablen

## Voraussetzungen

WordPress 6.3+, PHP 7.4+, Browser mit Unterstützung für `HTMLDialogElement.showModal()`.
