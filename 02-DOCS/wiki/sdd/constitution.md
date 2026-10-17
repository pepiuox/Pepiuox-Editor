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

25. Every significant decision is appended to `02-DOCS/wiki/harness/decisions.md` (date, options
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
| 1 | `config/database.php` was tracked in git with the cleartext MySQL password. | 15 | **Done (2026-10-17).** The credentials moved to `01-TOOLS/mysql/.env` (gitignored) and the code reads them from the environment; then the password was rotated, so the copy sitting in the git history no longer opens anything. Evidence in `02-DOCS/wiki/ftd/rotar-credenciales.md`. |
| 2 | `public/install.php`, `public/diagnose.php`, `public/test-files.php`, `public/test-trusted.php` were reachable over HTTP. | 16 | **Done (2026-10-17).** The four moved to `01-TOOLS/diagnostics/`; `GET /install.php` answers 404. The web installer is gone; install from `docs/INSTALL.md`. |
| 3 | `docs/README.md` declares PHP 8.1+ and `docs/INSTALL.md` says 8.1 min / 8.2+ recommended, while principle 1 fixes 8.3+. | 1 | **Done (2026-10-17).** Both docs now say PHP 8.3+. Evidence in `02-DOCS/wiki/ftd/instalacion-documentada-y-puntos-de-entrada.md`. |
| 4 | No automated tests, no CI, no linter. | 7 | Accepted risk for now; revisit when authentication or persistence changes. |
| 5 | No `02-DOCS/wiki/index.md` knowledge map exists yet, so nothing points a reader at this file. | 26 | **Done (2026-10-17).** The index exists: the read-first documents, then a row per feature document, and a closing section on how the wiki is maintained. `docs/README.md` now points at it. It is *not* the OKF catalogue the `harness` engine describes, because that engine's scaffold (`inbox/`, `raw/`, `log.md`, `gaps.md`, `scores.json`, `.base` views) was never built in this project — recorded as a decision, not hidden. Evidence in `02-DOCS/wiki/ftd/el-indice-del-wiki.md`. |
| 6 | `docs/INSTALL.md` lists Composer 2 as a requirement, and principle 3 says the project ships without Composer. | 3 | **Done (2026-10-17).** Checked first and removed second: there is no `composer.json`, no `composer.lock`, no `vendor/`, and no Composer autoload reference anywhere, so the requirement was the false half. Evidence in `02-DOCS/wiki/ftd/instalacion-sin-composer.md`. |
| 7 | `db/db-full.sql` (tracked) ships the bcrypt hashes of the seeded accounts `pepiuox`, `manager` and `admin`. A committed hash is crackable offline at leisure. | 15 | Replace the seeded hashes with a documented placeholder that forces a password change. Not started: it changes how the app is seeded, so it needs the owner's decision. |
| 8 | The application's MySQL user `pepiuox@%` holds `ALL PRIVILEGES ON *.* WITH GRANT OPTION`, while `01-TOOLS/mysql/CREDENTIALS.md` asks for `SELECT, INSERT, UPDATE, DELETE` on the project database and no access to `mysql.*`. | 15 | **Done (2026-10-17).** The application now connects as `web_editor_app`, which holds `SELECT, INSERT, UPDATE, DELETE` on `web_editor_db` and nothing else; the privilege was verified in both directions. `pepiuox@%` remains a superuser deliberately: it is the owner's administration account, and the application no longer uses it. Evidence in `02-DOCS/wiki/ftd/usuario-de-bd-con-permisos-minimos.md`. |
| 9 | `docs/INSTALL.md` is a stump: 41 lines since the initial commit, an index of 11 sections of which 2 exist, and no mention of the `.env` that now holds the credentials. `docs/README.md` is 10 lines and ends mid-code-block. The front controllers' 500 message sends the reader to `INSTALL.md` "to configure the database", which that document does not explain. | 26 | **Done (2026-10-17).** `INSTALL.md` rewritten as a real guide (requirements, `.env`, database and least-privilege user, dump, verification, first login, troubleshooting, hardening, and what it does not cover); `README.md` completed; the 500 message now names the file that is missing. Verifying it turned up something worse, fixed in the same change: the root `index.php` called a recursive `listarCarpeta(__DIR__)` at the end of **every request**, publishing the project's file tree on every page served with the repository root as document root (429 050 bytes → 5 972 bytes). Evidence in `02-DOCS/wiki/ftd/instalacion-documentada-y-puntos-de-entrada.md`. |
| 10 | `private/index.php`, a third front controller, redirects to `install.php`, a file that has never existed under `private/` and no longer exists anywhere. It is also unreachable under the root `.htaccess` — which rewrites everything to `public/` — and answers HTTP 200 with `Uncaught ArgumentCountError` plus a full stack trace when it is reached. | 16 | **Done (2026-10-17).** Retired, as the owner chose: moved to `01-TOOLS/diagnostics/private-index.php` with a retirement header and a row in that folder's README. Not repaired — nothing referenced it and it could not work as it stood. Evidence in `02-DOCS/wiki/ftd/retirar-el-tercer-punto-de-entrada.md`. |
| 11 | The schema mixes three collations: `utf8mb4_uca1400_ai_ci` in 45 tables, `utf8mb4_unicode_ci` in 4, and `utf8mb3_uca1400_ai_ci` in 3 (`users_permissions`, `users_roles`, `users_role_permissions`). The `uca1400` collations are MariaDB-only, so principle 2's "MySQL / MariaDB" is in practice MariaDB-only, and the three `utf8mb3` tables cannot store 4-byte characters. | 2, 4 | Standardise on `utf8mb4_uca1400_ai_ci` and regenerate `db/db-full.sql`. Not started: it is a schema change and needs its own migration. |
| 12 | `private/` keeps a stale copy of the front-end assets (`css/maker.css`, `css/builder.css`, `css/collaboration.css`, `js/app.js`, `js/collabotarion.js`, `js/comments.js`) that nothing references. The live ones are `public/assets/`, which is what `asset(...)` resolves to. | 4 | Decide whether to delete `private/` — it now holds only that copy and a `.htaccess` — or keep it for history. Not started: deleting the owner's files is the owner's call. |
| 13 | Two throwaway pages in the dev stack's Apache document root (`C:\ZampGUI\apache\htdocs\dbtest.php` and `test.php`) carry `root`/`root` in plain text and are reachable over HTTP: since the `root` password rotation they answer HTTP 200 with a fatal `Access denied` and the stack trace on screen. They sit outside this repository, but that stack is what serves this project. | 16 | Delete them. Not done: they are not the project's files, and deleting is the owner's call. Copying the new superuser password into them was rejected as the worse option — it would keep the server's superuser credential in a file Apache serves. |

## Amendment log (append-only)

| Date | Version | Change | Why |
|------|---------|--------|-----|
| 2026-10-17 | draft | Draft generated by `rsc onboard`. | The SDD floor needs a constitution; ratification is the `constitution` phase's. |
| 2026-10-17 | v1.0.0 | Ratified: 26 numbered principles, the Definition of Done, and 5 findings recorded as debt. | Project kickoff; first ratification after onboarding. |
| 2026-10-17 | v1.0.1 | Debt rows 1 and 2 updated, row 3 widened, row 6 added. No principle changed. | FTD change `credenciales-y-scripts-fuera-de-la-raiz-web`: credentials out of git, diagnostic scripts out of the web root. Verified against the running app. |
| 2026-10-17 | v1.0.2 | Debt row 1 closed, rows 7 and 8 added. Principle 25's path corrected from `wiki/sdd/decisions.md` to `wiki/harness/decisions.md`; the intent did not change, the cited file never existed and the harness owns that path. | FTD change `rotar-credenciales`: the MySQL password was rotated, verified dead-and-replaced against a fresh process. Rows 7 and 8 are the findings the rotation surfaced. |
| 2026-10-17 | v1.0.3 | Debt row 8 closed. No principle changed. | FTD change `usuario-de-bd-con-permisos-minimos`: the application stops connecting as a superuser and gets its own `SELECT, INSERT, UPDATE, DELETE`-only account. |
| 2026-10-17 | v1.0.4 | Debt row 6 closed, rows 9 and 10 added. No principle changed. | FTD change `instalacion-sin-composer`: the false Composer requirement is gone. Checking it surfaced that the install docs are stumps and that a third front controller still points at a retired installer, which is what rows 9 and 10 record. |
| 2026-10-17 | v1.0.5 | Debt rows 3 and 9 closed, row 10 rewritten with new evidence, row 11 added. No principle changed. | FTD change `instalacion-documentada-y-puntos-de-entrada`: the install guide is now real and the entry-point guards tell the truth. The root `index.php` no longer dumps the project's file tree into every page. |
| 2026-10-17 | v1.0.6 | Debt row 10 closed, row 12 added. No principle changed. | FTD change `retirar-el-tercer-punto-de-entrada`: `private/index.php` left the web root. One front controller fewer, and the one that went was the broken one. |
| 2026-10-17 | v1.0.7 | Debt row 5 closed. No principle changed. | FTD change `el-indice-del-wiki`: the wiki has an index, so the next session has a way in. |
| 2026-10-17 | v1.0.8 | Debt row 13 added. No principle changed. | FTD change `rotar-la-contrasena-de-root`: the MySQL `root` account no longer answers to `root`. The two web-reachable pages in the dev stack that still carry it are now recorded instead of quietly fixed. |
