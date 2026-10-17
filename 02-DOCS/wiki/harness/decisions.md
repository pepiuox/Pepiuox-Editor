# Harness decisions

- Accepted plan `7d476a5f7a42109068b20d017df033e5097d291d9e5973bf566871a1b6a6f0f5`.
- Project kind: software.
- SDD: selected.

## 2026-10-17 — Constitution v1.0.0 ratified

Artifact: `02-DOCS/wiki/sdd/constitution.md`. Interview answers given by the repo owner.

| Decision | Options considered | Chosen | Why |
|----------|--------------------|--------|-----|
| Quality bar | (a) `php -l` + manual verification · (b) Composer + PHPUnit for new logic · (c) formatter only | (a) | The project is 42 controllers and 24 models with no tests, no Composer and no CI. A test requirement now would block every change. The trade-off — authentication and persistence stay uncovered — is recorded as debt item 4, not hidden. |
| Secret in the repo | (a) rule + move credentials to `01-TOOLS` · (b) rule for new code only · (c) no rule | (a) | `config/database.php` is tracked with a cleartext password. Writing the rule without a path to comply would make principle 15 dead text. Debt item 1 tracks the move. |
| Web-reachable diagnostics | (a) rule banning them · (b) rule for new scripts only · (c) no rule | (a) | Four scripts sit in `public/`: `install.php`, `diagnose.php`, `test-files.php`, `test-trusted.php`. `install.php` on a live host is the worst of the four. Debt item 2 tracks the move. |
| Accessibility floor | (a) WCAG 2.1 AA · (b) WCAG 2.1 level A · (c) no rule | (a) | The admin panel is form-heavy: labels, focus and contrast are cheap to keep and expensive to retrofit. |
| Commit format | (a) gitmoji + Conventional Commits · (b) Conventional Commits without emoji · (c) free | (a) | The history is eight commits, almost all titled `Updated`, which carries no information. |
| PHP version | (a) 8.1+ as `docs/README.md` states · (b) 8.2+ · (c) 8.3+ | (c) | Owner's explicit choice, above what the docs declare. This contradicts `docs/README.md`; the drift is recorded as debt item 3 rather than resolved by picking a winner silently. |

Findings surfaced at ratification and left open, each with its own change: the tracked password
(1), the web-reachable scripts (2), the PHP version drift against `docs/README.md` (3), the absence
of tests and CI (4), and the missing `02-DOCS/wiki/index.md` knowledge map (5).
