---
agent: agent
description: Vollständiger Commit-Workflow für spx-webp-converter
---

# Commit-Workflow: spx-webp-converter

## Schritt 1: Versioning
Bestimme den Versions-Typ nach diesen Regeln basierend auf `git diff --cached`:
- **MAJOR** = breaking changes (z.B. geändertes Einstellungsschema, Umbenennung von Hooks/Filtern)
- **MINOR** = neue Features (neue Einstellungsoptionen, neue Bildformate, neue Filter/Actions)
- **PATCH** = Bugfixes, kleine Anpassungen, Refactoring, Docs

Versiosnsnummer an **zwei Stellen** synchron erhöhen:
1. `spx-webp-converter.php` → Plugin-Header: `* Version: X.X.X`
2. `spx-webp-converter.php` → Konstante: `define('SPX_WEBP_CONVERTER_VERSION', 'X.X.X')`

**Ausnahme:** Nur `docs:`-Commit → überspringe Schritt 1 vollständig (keine Versionserhöhung) und überspringe in Schritt 2 die Befehle `git tag vX.X.X` und `git push origin vX.X.X`.

## Schritt 2: Stage, Commit, Tag, Push
Vor dem Ausführen von `git add`: Führe `git status` aus. Gibt es unstaged Änderungen außerhalb von `spx-webp-converter.php`? → Frage den Benutzer, ob diese ebenfalls in diesen Commit aufgenommen werden sollen.

```bash
git add spx-webp-converter.php
git commit -m "feat: [Beschreibung] vX.X.X

[Emoji] Haupt-Feature:
- Änderung 1
- Änderung 2

🔧 Technical Enhancements:
- Verbesserung 1"

git tag vX.X.X
git push origin main
git push origin vX.X.X
```

Emojis: ✨ Feature · 🔧 Fix · 🖼️ Image · ⚡ Performance · 🏗️ Refactoring · 🔒 Security · 📚 Docs

Bei Push-Problemen: Führe `git remote -v` aus, um die Remote-URL zu verifizieren. Wenn der Push mit "rejected" fehlschlägt, informiere den Benutzer und führe **KEINEN** Force-Push aus. Schlage vor: `git pull --rebase origin main` und frage den Benutzer um Bestätigung vor der Ausführung.
