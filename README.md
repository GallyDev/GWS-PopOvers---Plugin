# GWS PopOvers 1.0.1

## Fix in 1.0.1
JS wird erst nach dem Parsen des DOM initialisiert. Damit werden Popovers auch dann gefunden, wenn WordPress Footer-Scripts vor dem HTML der Popovers ausgibt.

## Installation
In WordPress unter Plugins > Installieren > Plugin hochladen das ZIP auswählen und aktivieren.
Anschliessend unter **GWS PopOvers > Neu hinzufügen** ein PopOver anlegen, Titel setzen, Gutenberg-Inhalt erfassen, Auslöser sowie optionale Zielseiten auswählen und veröffentlichen.

## Architektur
- `gws-popovers.php`: Bootstrap und Plugin-Header
- `includes/config.php`: CPT `gws_popover`, SCF/ACF-Feldgruppe oder native Metaboxen
- `includes/frontend.php`: Targeting, Gutenberg-Rendering, Asset-Enqueue
- `assets/popovers.js`: Exit Intent, Timer, Cookie und Popover-Queue
- `assets/popovers.css`: Design auf Basis von theme.json-CSS-Presets

## Verhalten
- Ohne Zielseiten-Auswahl ist das PopOver global (auch Archive/Startseite).
- Mit Zielseiten-Auswahl nur auf den angegebenen Seiten oder Beiträgen.
- Mehrere PopOvers können dieselbe Seite adressieren, werden jedoch nacheinander statt übereinander angezeigt.
- Cookie pro PopOver (nicht websiteweit): Schliessen blendet es standardmässig 7 Tage aus.
- Bei 0 Tagen ist es ein Session-Cookie (bis Browser-Sitzungsende).
- Timer startet beim vollständigen `window.load`, nicht bei DOMContentLoaded.
- Exit Intent wird bei Desktop-Mausbewegung über die obere Browserkante erkannt. Kein Touch-Exit-Intent.
- Popovers sind nicht-modal und bieten einen Schliessen-Button und Escape.
- SCF/ACF werden zur Laufzeit erkannt; ist keine der APIs vorhanden, aktiviert sich der native Metabox-Fallback. Änderungen am Plugin-Status von SCF/ACF benötigen keinen Reinstall.

## Theme Anpassung
CSS referenziert standardmässig `--wp--preset--color--base`, `--wp--preset--color--contrast`, `--wp--preset--color--primary`, `--wp--preset--font-family--body`, `--wp--preset--font-size--medium` und optional `--wp--custom--border--radius`. Eigene Themes können `.gws-po`, `.gws-po__content`, `.gws-po__close` überschreiben.

## Hinweise
Die native Popover API ist nicht modal. Für verbindliche Modals wäre `<dialog>` geeigneter. Ein serverseitiger Full-Page-Cache ist unproblematisch, da die Cookie-Sperre clientseitig geprüft wird. In Content-Blöcken eingebundene Formulare oder Scripts müssen separat kompatibel mit dem Theme sein. Für Analytics/Consent keine Daten vor Einwilligung erfassen.
