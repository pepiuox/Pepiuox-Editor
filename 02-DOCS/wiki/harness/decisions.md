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

## 2026-10-17 — Credenciales fuera de git y scripts fuera de la raíz web (FTD)

Feature document: `02-DOCS/wiki/ftd/credenciales-y-scripts-fuera-de-la-raiz-web.md`.

| Decision | Options considered | Chosen | Why |
|----------|--------------------|--------|-----|
| Where the credentials live | (a) `.env` inside `01-TOOLS`, read by `config/env.php` · (b) Composer + a dotenv library · (c) secrets only as process environment variables | (a) | Principle 3 forbids Composer. `env.php` is about 60 lines with no dependency. The process wins over the file, so production needs no `.env` at all. |
| What to do with `install.php` | (a) retire it to `01-TOOLS/diagnostics/` · (b) repair it · (c) delete it | (a) | It is broken (it reads `db/db.sql` and `db/migrations.sql`, which do not exist) and its own success message asked to delete it. Retiring it satisfies principle 16 without losing the code. |
| What happens when `config/database.php` is missing | (a) HTTP 500 with instructions · (b) remove the check · (c) keep redirecting to a web installer that no longer exists | (a) | With no web installer, a redirect to nothing is worse than a message. `docs/INSTALL.md` is the real guide and stays current. |

Finding discovered during the change: `docs/INSTALL.md` lists Composer as a requirement, against
principle 3. Recorded as debt 6. The constitution moved to v1.0.1 (debt status only; no principle
changed).

## 2026-10-17 — Rotar la contraseña de MySQL (FTD)

Feature document: `02-DOCS/wiki/ftd/rotar-credenciales.md`.

| Decision | Options considered | Chosen | Why |
|----------|--------------------|--------|-----|
| Rotate, or settle for moving the file | (a) rotate now · (b) only move the file out of git · (c) rewrite git history to erase the password | (a) | The history is already published; rewriting it touches eight commits and every clone anyone holds. A rotated password makes the old copy worthless without surgery. |
| Which account to change | (a) `pepiuox@%` · (b) `pepiuox@localhost` | (a) | No `pepiuox@localhost` account exists — the server lists `pepiuox@%` only. The client enters from localhost and matches the `%` wildcard, so changing `@localhost` would have silently done nothing. |
| Shape of the new password | (a) 32 characters of `A-Za-z0-9-_` from `random_int` · (b) a human passphrase · (c) raw random bytes | (a) | Roughly 190 bits, and it leaves out the characters that break a `.env` or a shell (`"`, `'`, `=`, `#`, space). |

Surfaced while rotating, each left open with its own decision:

- `root` of the local MySQL answers to the password `root`. Not in git, but it is the whole server.
  Changing it can break other local tools that have it stored, so it is the owner's call.
- `pepiuox@%` holds `ALL PRIVILEGES ON *.* WITH GRANT OPTION`, exactly what
  `01-TOOLS/mysql/CREDENTIALS.md` asks not to do. Recorded as debt 8.
- `db/db-full.sql`, which is tracked, ships the bcrypt hashes of the seeded accounts. Recorded as
  debt 7.

Corrected in the same pass: principle 25 pointed at `02-DOCS/wiki/sdd/decisions.md`, a file that
never existed. The harness fixes that path as `02-DOCS/wiki/harness/decisions.md` — this file.
Constitution moved to v1.0.2.

## 2026-10-17 — Usuario de base de datos con permisos mínimos (FTD)

Feature document: `02-DOCS/wiki/ftd/usuario-de-bd-con-permisos-minimos.md`.

Requirements gathered before choosing: what the application actually asks of the database. The
answer decides the grants, so the search came first — `app/` contains no runtime DDL, `BackupService`
only reads (`SHOW CREATE TABLE`, `SELECT *`), and the four triggers and five routines are
`definer=root@localhost`, so they run with `root`'s privileges and need nothing from the app's user.

| Decision | Options considered | Chosen | Why |
|----------|--------------------|--------|-----|
| What to grant | (a) `SELECT, INSERT, UPDATE, DELETE` on the project database · (b) the same plus `CREATE, ALTER, DROP, INDEX` for schema work · (c) leave `ALL PRIVILEGES` and only change the user name | (a) | The app never alters the schema; migrations are run by hand with the admin account. Anything beyond DML would be privilege the code cannot use and an attacker would inherit. |
| Which hosts the user needs | (a) `localhost` and `127.0.0.1` · (b) only `%` · (c) only `localhost` | (a) | Two rows for one identity. `%` would also accept connections from anywhere; `localhost` alone breaks if the server ever runs with `skip-name-resolve`, because the client would come in as `127.0.0.1`. |
| What to do with `pepiuox@%` | (a) leave it as the owner's admin account, unused by the app · (b) downgrade it to the same DML set · (c) drop it | (a) | It is the account that imports dumps and runs migrations; downgrading it silently would break the owner's own workflow. The risk this change addressed was the *application* connecting as a superuser, and that is gone. |
| How to prove it | (a) attempt every operation and record success or denial · (b) read `SHOW GRANTS` only | (a) | A grant table says what was intended; an attempted `DROP TABLE` says what actually happens. Writes ran inside a rolled-back transaction with `WHERE 1=0`, so the proof left no trace in the data. |

Measured result: `web_editor_app@localhost` sees two databases (`information_schema` and
`web_editor_db`) where the old account saw nine; `mysql.user`, `finanzapp`, `CREATE`, `DROP`, `ALTER`
and `GRANT` are all denied; the app answers 200. `test_connection.php` switched from `pepiuox` to
`web_editor_app` without any other change. Constitution moved to v1.0.3.

Noted but not acted on: every user on this MySQL inherits a `PUBLIC` grant of full rights over
databases named `test` or `test_%`. It is local server configuration, outside the repository, and
none of those databases exist.
