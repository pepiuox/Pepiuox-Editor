---
type: constitution
title: PepiuoX Editor — Constitution
description: The non-negotiable principles every rsc-sdd phase obeys.
tags: [sdd, constitution]
timestamp: 2026-10-17T03:27:32Z
topic: sdd
version: v1.0.1
---

# PepiuoX Editor — Constitution

> Version: v1.0.1 · Ratified: 2026-10-17 · Last amended: 2026-10-17
> The non-negotiable principles every rsc-sdd phase obeys. Stack mechanics live in
> `02-DOCS/wiki/stack/*`; this file ratifies the principle and links the detail.

## 1. Stack canon

1. PHP 8.3 or newer. Every new file under `app/` opens with `declare(strict_types=1)`.
   Checkable: `php -v` and the first lines of a changed file.
2. MySQL / MariaDB is the only datastore, reached through `app/Core/Database.php` (PDO,
   `ERRMODE_EXCEPTION`, `EMULATE_PREPARES => false`). No second connection style.
3. The project ships as plain PHP with its own PSR-4 autoloader in `index.php`. No Composer,
   no runtime dependency added without an amendment.
4. `db/db-full.sql` is the reference schema. A schema change updates it in the same change.

## 2. Quality bar

5. `php -l` passes with no error on every changed PHP file.
6. Every change is verified by running the affected flow. The feature document records the
   evidence and names what was left uncovered.
7. Automated tests are not required today; this is an accepted risk. A change that touches
   authentication, authorization or a destructive data migration must state how it was
   verified by hand and which risk remains open.

## 3. Conventions

8. The layers are fixed: `app/Controllers`, `app/Models`, `app/Services`, `app/Core`,
   `app/Middleware`, `app/Helpers`; views in `views/`; configuration in `config/*.php`
   returning arrays. New code lands in an existing layer.
9. Class names are PascalCase under the `App\` namespace and the file name matches the class,
   resolved by the autoloader in `index.php`.
10. Every route is declared in `config/routes.php` with its HTTP verb. A route that needs a
    session or a permission goes behind the existing middleware, never behind a check written
    inside a view.
11. Comments and user-facing copy are in Spanish. Identifiers, class names and table names are
    in English.
12. Commit messages are gitmoji + Conventional Commits: `✨ feat(pages): añade versionado`.

## 4. Branching & shipping

13. Code lands straight on `main`. No branch, no pull request (decided at install).
14. **Git authorship is the human's.** No `Co-Authored-By` an AI, no "generated with" footer.
    Enforced at the `ship` phase.

## 5. Security & privacy floor

15. No secret is ever committed. Credentials come from `01-TOOLS/<provider>/.env` (gitignored)
    and are read from the environment.
16. Nothing that installs, diagnoses or tests is reachable from the web root.
17. Queries use prepared statements. A query built by concatenating user input is a defect, not
    a style question. Baseline: the `secure-coding` skill.
18. Passwords are stored only through `password_hash()` with `PASSWORD_DEFAULT` and
    `cost => 12` (`config/config.php`).
19. Every state-changing request carries a CSRF token (`app/Core/CSRF.php`); new forms include it.
20. Sessions keep `httponly` and `samesite`. `secure` becomes true once the site runs on HTTPS.
21. Authorization is enforced on the server: an admin route passes `AuthMiddleware` and
    `PermissionMiddleware`, and the controller re-checks the permission it needs. A hidden
    button is not a permission.
22. Uploads respect the extension whitelist and the size cap in `config/config.php`.
23. Logs never contain passwords, tokens, CSRF tokens or complete personal data.

## 6. UX / accessibility floor

24. WCAG 2.1 AA is the floor for every screen in `views/`: reachable by keyboard, visible focus,
    sufficient contrast, every form field with a label.

## 7. Knowledge & decisions

25. Every significant decision is appended to `02-DOCS/wiki/sdd/decisions.md` (date, options
    considered, why). This constitution is the highest-order decision record.
26. A change that alters behaviour updates its document under `02-DOCS/` in the same change.
    The wiki is the memory the next session reads.

## Definition of Done (the merge bar `verify` runs against)

A change is done only when ALL hold:

- [ ] `php -l` clean on every changed PHP file (principle 5).
- [ ] The affected flow was exercised; the evidence and the gaps are written down (principles 6-7).
- [ ] Layers, naming and route conventions respected (principles 8-11).
- [ ] Commit message in gitmoji + Conventional Commits (principle 12).
- [ ] Straight on `main`, authored by the human (principles 13-14).
- [ ] No secret committed (principle 15); no new web-reachable diagnostic (principle 16).
- [ ] Prepared statements (17), CSRF on state-changing requests (19), authorization re-checked
      on the server (21).
- [ ] WCAG 2.1 AA on the screens touched (principle 24).
- [ ] Decision logged and document updated (principles 25-26).

## Known debt (findings recorded at ratification)

These contradict the principles above. They are recorded, not silently fixed. Each one needs its
own change.

| # | Finding | Principle | Resolution |
|---|---------|-----------|------------|
| 1 | `config/database.php` was tracked in git with the cleartext MySQL password. | 15 | **Half done (2026-10-17).** The credentials moved to `01-TOOLS/mysql/.env`, gitignored, and the code reads them from the environment. The old password is still in the git history: **rotation pending**, steps in `01-TOOLS/mysql/CREDENTIALS.md`. |
| 2 | `public/install.php`, `public/diagnose.php`, `public/test-files.php`, `public/test-trusted.php` were reachable over HTTP. | 16 | **Done (2026-10-17).** The four moved to `01-TOOLS/diagnostics/`; `GET /install.php` answers 404. The web installer is gone; install from `docs/INSTALL.md`. |
| 3 | `docs/README.md` declares PHP 8.1+ and `docs/INSTALL.md` says 8.1 min / 8.2+ recommended, while principle 1 fixes 8.3+. | 1 | Update both docs. Not started. |
| 4 | No automated tests, no CI, no linter. | 7 | Accepted risk for now; revisit when authentication or persistence changes. |
| 5 | No `02-DOCS/wiki/index.md` knowledge map exists yet, so nothing points a reader at this file. | 26 | The `harness` skill owns the wiki index. Not started. |
| 6 | `docs/INSTALL.md` lists Composer 2 as a requirement, and principle 3 says the project ships without Composer. | 3 | Update `docs/INSTALL.md`. Not started. |

## Amendment log (append-only)

| Date | Version | Change | Why |
|------|---------|--------|-----|
| 2026-10-17 | draft | Draft generated by `rsc onboard`. | The SDD floor needs a constitution; ratification is the `constitution` phase's. |
| 2026-10-17 | v1.0.0 | Ratified: 26 numbered principles, the Definition of Done, and 5 findings recorded as debt. | Project kickoff; first ratification after onboarding. |
| 2026-10-17 | v1.0.1 | Debt rows 1 and 2 updated, row 3 widened, row 6 added. No principle changed. | FTD change `credenciales-y-scripts-fuera-de-la-raiz-web`: credentials out of git, diagnostic scripts out of the web root. Verified against the running app. |
