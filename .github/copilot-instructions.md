# GitHub Copilot Instructions – spx-webp-converter

## Grundregeln
- Antworte immer auf Deutsch
- Code-Kommentare auf Englisch
- Commit-Messages auf Englisch (Conventional Commits: feat:, fix:, docs:, refactor:, style:)

## Stack
- PHP 8.0+
- WordPress Plugin API
- GD Library (Bildkonvertierung)

## Projektstruktur
- `spx-webp-converter.php` – Plugin-Einstiegspunkt, Header, Konstanten
- `includes/class-spx-webp-converter-admin.php` – Admin-Einstellungsseite
- `includes/class-spx-webp-converter-converter.php` – Konvertierungslogik
- `includes/functions-helpers.php` – Hilfsfunktionen (get_quality, get_max_width, get_max_height)

## Coding Standards
- PHP 8.0+, strikte Typen (`declare(strict_types=1)` wo sinnvoll)
- Kein `@`-Error-Suppressor – explizite Prüfungen bevorzugen
- WordPress Coding Standards: Escaping (`esc_html`, `esc_attr`), Nonces bei Forms
- Versionsnummer in Plugin-Header (`Version:`) UND in Konstante `SPX_WEBP_CONVERTER_VERSION` synchron halten

## Semantic Versioning (MAJOR.MINOR.PATCH)
| Typ | Wann |
|-----|------|
| MAJOR | Breaking Changes (geändertes Einstellungsschema, Umbenennung von Hooks/Filtern) |
| MINOR | Neue Features (neue Einstellungsoptionen, neue Bildformate, neue Filter) |
| PATCH | Bugfixes, Performance, Refactoring, Docs |

**Ausnahme:** Nur `docs:`-Commits → KEINE Versionserhöhung, KEIN Tag.

## Workflow-Befehle
- **"code commiten"** → Führe `.github/prompts/commit.prompt.md` aus
- **"version hochzählen"** → Nur Schritt 1 (kein Commit)
- **"git tag push"** → Tag erstellen und pushen (nur wenn bereits committet)
- **"git status"** → Staged/unstaged Änderungen auflisten
