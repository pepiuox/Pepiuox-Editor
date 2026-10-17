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

## 2026-10-17 — Los requisitos de instalación sin Composer (FTD)

Feature document: `02-DOCS/wiki/ftd/instalacion-sin-composer.md`.

| Decision | Options considered | Chosen | Why |
|----------|--------------------|--------|-----|
| Which half of the contradiction is false | (a) the doc row · (b) principle 3 | (a) | Measured, not argued. There is no `composer.json`, no `composer.lock`, no `vendor/` anywhere, and no `vendor/autoload` or `Composer\` reference in the code: the app autoloads itself with PSR-4. A requirement nobody satisfies is a doc defect; rewriting the principle to match a tool the project has never used would have been backwards. |
| How much of the requirements table to touch | (a) only the Composer row · (b) also the PHP row directly above it | (a) | The PHP row is debt 3 and contradicts principle 1. It is a separate decision with its own change, and fixing it here would have quietly closed a debt the owner had not chosen. |
| What to do about `INSTALL.md` being a stump | (a) rewrite it in this change · (b) record it and remove only the false requirement | (b) | The owner asked to close one debt. A rewrite that invents nine sections is a change of its own. Recorded as debt 9, together with the sharper finding: the 500 message in the entry points sends the reader to a document that cannot answer the question it is being asked. |

Findings surfaced by the check, none of them new to this commit: `docs/INSTALL.md` has been 41 lines
since the initial commit, its index promises 11 sections and 2 exist, and it predates the `.env`.
`docs/README.md` has been 10 lines and ends mid-`sql`-block. `private/index.php` is a third front
controller that redirects to `install.php`, a file that never existed under `private/` — dead before
the installer was retired. Constitution moved to v1.0.4.

## 2026-10-17 — La instalación documentada y los puntos de entrada (FTD)

Feature document: `02-DOCS/wiki/ftd/instalacion-documentada-y-puntos-de-entrada.md`.

Requirements gathered before writing: what the guide has to be true about. That meant measuring the
machine (PHP 8.4.25, the nine extensions, MariaDB 12.3.3), the dump (54 `CREATE TABLE`, 30 `INSERT`),
the three collations in use, and what each entry point actually answers. The guide is written from
those measurements, and says explicitly what it does not cover (nginx, deployment) instead of
inventing it.

| Decision | Options considered | Chosen | Why |
|----------|--------------------|--------|-----|
| Where to point the 500 message | (a) the file that is missing, spelled out, plus the guide · (b) keep pointing only at `INSTALL.md` · (c) remove the guard as unreachable | (a) | The guard fires when `config/database.php` is gone, and it should say what to put back, not send the reader to a document. It stays because it is a real (if rare) condition — the file is tracked, so a clone always has it, which is why the fresh-install case is covered by the exception in `config/database.php` instead. Two mechanisms, each with one job. |
| What to do with `listarCarpeta()` in the root `index.php` | (a) remove it · (b) gate it behind `debug` · (c) move it to `01-TOOLS/diagnostics/` | (a) | Nothing called it but itself, and it ran at the end of every request: 39 lines of dev aid turning every page into a listing of the project's files. Gating it on `debug` would have left it on — `debug` is `true` in the repo — and a filesystem dump has no safe production form. `tree` and `ls -R` already do this job, outside the web server. |
| Whether to also repair `private/index.php` in this change | (a) record the new evidence and decide separately · (b) repair it · (c) delete it | (a) | It is not reachable under the root `.htaccess` and it fatally errors when it is. Repairing a dead, duplicated entry point without asking, or deleting the owner's file, are both decisions bigger than this change. Debt 10 rewritten with the measurements. |
| Whether to fix the collation mix now | (a) record it · (b) rebuild the dump | (a) | It is a schema migration of its own: 45 tables in one collation, 4 in another, 3 in `utf8mb3`. Correctly documented in the guide's requirements table instead of asserted as fine. Debt 11. |

Corrected while writing: the requirements table said "MySQL 8.0 / MariaDB 10.5" and PHP 8.1/8.2. The
dump cannot be imported into MySQL because of the `uca1400` collations, and principle 1 fixes PHP
8.3+. Both now say what is true. Constitution moved to v1.0.5.

## 2026-10-17 — Retirar el tercer punto de entrada (FTD)

Feature document: `02-DOCS/wiki/ftd/retirar-el-tercer-punto-de-entrada.md`.

The owner chose between repairing `private/index.php` and retiring it, once the measurements were on
the table. Retiring won: nothing referenced it, the root `.htaccess` never serves it, and it answered
200 with a stack trace when it was reached. It moved to `01-TOOLS/diagnostics/private-index.php` with
the same retirement header as the four scripts retired earlier, plus a row in that folder's README.

This is the second time the answer to a broken script has been "retire it, keep it for history"
rather than "fix it" — consistent, and worth recording as a decision rather than an accident.

| Decision | Options considered | Chosen | Why |
|----------|--------------------|--------|-----|
| Repair or retire `private/index.php` | (a) retire it to `01-TOOLS/diagnostics/` · (b) repair it (same guard as the others, plus the `ArgumentCountError`) · (c) leave it | (a) | Repairing means maintaining a third copy of an entry point that duplicates the other two and that no rule serves. Retiring removes the breakage without losing the file. |
| Whether to touch `private/assets/` in the same change | (a) leave it and record it · (b) delete it along with the entry point | (a) | The owner authorised retiring the entry point, not deleting 440 KB of assets. They are unreferenced — `asset(...)` resolves to `public/assets/` — so they become debt 12 and the owner decides. |

Checked before moving anything, because the working tree holds unrelated uncommitted work:
`private/index.php` was byte-identical to `HEAD`, so the move carried none of it. Constitution moved
to v1.0.6.

## 2026-10-17 — El índice del wiki (FTD)

Feature document: `02-DOCS/wiki/ftd/el-indice-del-wiki.md`.

Debt 5 was "no `02-DOCS/wiki/index.md` exists, so nothing points a reader at this file", noted as
"the `harness` skill owns the wiki index". Reading that skill turned up two different things under
one name:

- `references/wiki-index-template.md` describes the index of the OKF engine: topics holding
  articles, each with a `Score` produced by a Maintenance Pass, and `.base` views as the human
  navigation.
- `references/wiki-protocol.md`'s Initialization creates that engine's whole scaffold — `inbox/`,
  `raw/`, `log.md`, `gaps.md`, `scores.json`, `.ingested.json`, `.rscignore`, `attachments/`, the
  `.base` views.

None of it exists here: `02-DOCS/` holds only `wiki/{sdd,harness,ftd}`, which is the SDD/FTD
structure, not the OKF one. So the template could not be used verbatim — its own header block
describes `.base` views and scores that would have had to be invented.

| Decision | Options considered | Chosen | Why |
|----------|--------------------|--------|-----|
| Which index | (a) a knowledge map of what this wiki actually holds · (b) build the full OKF scaffold first · (c) leave debt 5 open | (a) | The debt asks for a way in, not for a knowledge engine. (b) is a behaviour change of its own — a drop zone, an ingest pipeline, a ledger, a sync — that the owner did not ask for and that the harness did not build at install. (c) leaves a wiki nobody can enter. |
| Where the pointer lives | (a) `docs/README.md`'s documentation table · (b) a `## Knowledge map` section in `AGENTS.md` · (c) both | (a) | `AGENTS.md` is managed by rsc: it is in `governedPaths` with an `artifactDigests` entry, so a hand-written section there risks being reported as drift or lost on update. `docs/README.md` is the project's own entry point and where a reader already lands. |

If the OKF engine is ever wanted, its Initialization "creates only what is missing and never
overwrites existing files", so this index survives and can be merged into that format then.
Constitution moved to v1.0.7.
