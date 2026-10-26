# AGENTS.md

This file provides guidance to AI coding agents when working with code in this repository.

**Keep this file current.** If you change the PHP version floor, a dependency range, the tooling,
or any other assumption stated here, update the relevant section in the same pull request.

## Overview

Doctrine MongoDB ODM (`doctrine/mongodb-odm`) is a PHP object-document mapper: it persists
plain PHP objects to MongoDB, using `mongodb/mongodb` as the low-level driver and implementing
the Doctrine persistence interfaces. It is a mature library with a strict backward-compatibility
policy: additive API changes are fine, but removals and signature changes need a deprecation
cycle in a minor release first (see `UPGRADE-*.md` and `@deprecated` annotations). 3.0 is the
major release that applies the removals and the native type hints prepared in 2.x, all listed in
`UPGRADE-3.0.md`.

**This file is about *developing* the library.** For *using* it — mapping documents, querying,
aggregating, references vs. embeds, Atlas Search, Vector Search, Queryable Encryption — this
repository ships a coding-agent skill at [`skills/mongodb-odm/SKILL.md`](skills/mongodb-odm/SKILL.md)
with detail in `skills/mongodb-odm/references/`. Point end users at that skill, or load it
yourself, rather than answering from general Doctrine knowledge. The skill is declared in
`composer.json` under `extra.ai-mate.skills` so consuming projects discover it automatically.

**Keep the skill in sync.** When a change adds, removes, or alters user-facing behaviour covered
by the skill, update the relevant `skills/mongodb-odm/references/*.md` in the same pull request.
A new reference file needs a matching eval in `tests/Skills/mongodb-odm/evals.json`; a new gotcha
worth documenting is usually worth an assertion. See `tests/Skills/README.md`.

## Tech Stack

- **PHP `^8.4`** (minimum declared in `composer.json`); CI runs 8.4 and 8.5.
- **`ext-mongodb` `^2.2`**: the extension is required, not just the library. `ext-bcmath` is
  needed for Decimal128 and is a dev dependency.
- **`mongodb/mongodb` `^2.2`**: the driver library the ODM is built on.
- **Doctrine**: `persistence`, `collections`, `event-manager`, `instantiator`.
- **Symfony** components (`console`, `cache`, `service-contracts`, `deprecation-contracts`, plus
  `uid` and `var-dumper` for development), with wide version ranges. `symfony/polyfill-php86`
  provides the `SortDirection` enum that the sort APIs accept.
- **Lazy loading** uses the native lazy objects of PHP 8.4
  (`src/Proxy/Factory/NativeLazyObjectFactory.php`). 3.0 removed the ProxyManager factory, the
  Symfony VarExporter lazy ghosts, and the proxy directory configuration.
- **Testing**: PHPUnit 12.5, PHPBench. **Static analysis**: PHPStan 2.3.1, pinned in
  `composer.json`, at level 5 with a baseline. **Coding standards**: `doctrine/coding-standard`
  via PHP_CodeSniffer.
- **Docs**: Sphinx/reStructuredText under `docs/en/`, built with `doctrine/docs-builder`.

## Project Structure

- `src/` — library code, PSR-4 `Doctrine\ODM\MongoDB\` (mapping, persistence, query/aggregation
  builders, schema manager, console commands, types, proxies, persistent collections).
- `tests/Tests/` — the PHPUnit suite, mirroring `src/`. `tests/Documents/` holds mapped fixture
  documents; `tests/Documentation/` holds snippets kept in step with the docs; `tests/Skills/`
  holds the agent-skill evals.
- `docs/en/` — Sphinx docs (`reference/`, `cookbook/`, `tutorials/`). `docs/en/_theme` is a git
  submodule (`git submodule update --init` before building).
- `skills/mongodb-odm/` — the shipped agent skill (`SKILL.md` + `references/`).
- `tools/sandbox/` — a scratch playground for manual experiments; its generated proxies/hydrators
  are gitignored.
- `benchmark/` — PHPBench benchmarks.
- `.build/` — scripts and topology configs used by CI to launch MongoDB (via
  `mongodb-labs/drivers-evergreen-tools`).

## Commands

```bash
composer install

# Tests (require a running MongoDB)
vendor/bin/phpunit                              # full suite
vendor/bin/phpunit --filter <name>              # one test
vendor/bin/phpunit --exclude-group=atlas        # what CI runs on non-Atlas clusters

# Static analysis and style
vendor/bin/phpstan analyse                      # level 5, config in phpstan.neon.dist
vendor/bin/phpcs                                # doctrine/coding-standard, phpcs.xml.dist

# Benchmarks
vendor/bin/phpbench run --report=default --report=aggregate

# Docs
composer docs                                   # composer update -d docs && docs/vendor/bin/build-docs.sh
```

There is no `composer test` script — invoke the tools directly. CI mirrors these commands in
`.github/workflows/` (`continuous-integration.yml`, `static-analysis.yml`, `coding-standards.yml`,
`atlas-ci.yml`, `performance.yml`, `documentation.yml`).

## Testing

Tests need a **running MongoDB server**; the suite creates and drops collections in a test database.

- **Connection**: `DOCTRINE_MONGODB_SERVER` (default `mongodb://localhost:27017`) and
  `DOCTRINE_MONGODB_DATABASE` (default `doctrine_odm_tests`). Both are PHPUnit `<const>`s in
  `phpunit.xml.dist`; the server URI can additionally be overridden by an environment variable of
  the same name (`tests/Tests/BaseTestCase.php`). To change the database name, edit the const
  (via a local `phpunit.xml`, which is gitignored).
- **Lazy loading**: 3.0 always uses the native lazy objects of PHP 8.4, so there is no proxy
  strategy to select.
- **Suite-specific env vars**:
  - `CRYPT_SHARED_LIB_PATH` — Queryable Encryption tests (`tests/Tests/Functional/QueryableEncryptionTest.php`).
  - `VOYAGE_API_KEY` (with `EMBEDDING_PROVIDER_ENDPOINT`) — Automated Embeddings / Vector Search
    tests; they skip themselves when the cluster has no registered embedding model.
- **Groups**: tests tagged `#[Group('atlas')]` (Atlas Search, Vector Search, search-index waits)
  require Atlas and are excluded in CI with `--exclude-group=atlas`. Tests tagged
  `#[Group('performance')]` are excluded from the default suite and run through PHPBench.
- **Strictness**: `phpunit.xml.dist` fails the run on PHPUnit deprecations and warnings, and on
  deprecations, notices, warnings, risky, incomplete, and empty-suite. Fix the cause, do not
  silence it. (Only the lowest-dependency CI leg relaxes this, with
  `--do-not-fail-on-deprecation --do-not-fail-on-warning --do-not-fail-on-notice`.)
- **Topologies**: replica-set and sharded tests detect the topology of the configured server and
  skip when it does not match. CI runs a standalone server and a replica set; the sharded variant
  is commented out.
- **Docker**: `docker compose up` starts Atlas Local on port `27018` (see `docker-compose.yaml`
  and `.env.example`) for the Atlas-gated suites.

## Code Conventions

- **Comments.** Add a comment only where the code is genuinely tricky or confusing, and explain
  *why*, not *what*. Don't narrate the next line, restate a method name, or leave commented-out
  code behind. The maintainers feel strongly about this.
- **Deprecations.** Introduce them with `trigger_deprecation()` from `symfony/deprecation-contracts`
  (not a bare `@deprecated` tag), and describe the change in the `UPGRADE-X.Y.md` for the release
  that adds it.
- **Types.** A type is resolved through the `TypeRegistry` held by the `Configuration`, and
  `ClassMetadata::getFieldType()` returns the type of a field. The static `Type::*` methods are
  deprecated shims that delegate to the shared registry; don't build new code on them.
- **Mapping options must stay in parity.** A new or changed mapping option has to be added
  everywhere it can be declared, across all the drivers. Attributes are the only PHP mapping
  driver, since 3.0 removed the annotations driver and the `src/Mapping/Annotations/` namespace.
  The places to update are:
  - the attribute classes in `src/Mapping/Attribute/`;
  - the XML drivers (`src/Mapping/Driver/XmlDriver.php`, `SimplifiedXmlDriver.php`);
  - the XSD, `doctrine-mongo-mapping.xsd`;
  - the driver fixtures in `tests/Tests/Mapping/Driver/fixtures/` (PHP) and `fixtures/xml/`;
  - the docs (`docs/en/reference/attributes-reference.rst`, `xml-mapping.rst`, and the relevant
    topic page).

  Missing one of these is a common source of bugs.

### Backward compatibility

Backward compatibility is the top priority within a release line; prefer additive changes. 3.0 is
the major release that removes the API deprecated in 2.x, as listed in `UPGRADE-3.0.md`. In
particular:

- Don't add native parameter or return types to an existing public or protected method outside a
  major release: callers that pass a value the type rejects get a `TypeError`.
- Don't add parameters to interface methods.
- Soft-mark classes as `@final` in a docblock instead of using the `final` keyword, so existing
  subclasses keep working. `ClassMetadata` is the exception: 3.0 marks it final with the keyword.
- Don't rename classes.
- `@internal` code may change without a deprecation cycle, but still mention it in the relevant
  `UPGRADE-X.Y.md`.

## Commit and PR Conventions

- Prefix commit messages and PR titles with the JIRA key when there is one:
  `PHPORM-478: Add AGENTS.md`. External contributors don't have JIRA access, so a plain descriptive
  title is fine for them — don't invent a key. Squash merges append the PR number, e.g.
  `PHPORM-517: Declare the agent skill for Symfony AI Mate (#3072)`.
- Branch/bookmark names are lowercase slugs that usually include the JIRA key
  (`phporm-472-autoembed-community-test`).
- Target the branch the change belongs to. Fixes and additive changes go to the current release
  line (`2.N.x`, for example `2.18.x`), which is merged up into `3.0.x` regularly. Only the
  changes that belong to the next major target `3.0.x` directly.
- Fill in the PR template (`.github/PULL_REQUEST_TEMPLATE.md`): type, BC break yes/no, fixed issue.
- Document user-facing changes in the appropriate `UPGRADE-X.Y.md`.
- **Agent etiquette.** Don't open pull requests, push branches, or post review comments on your own
  initiative — prepare the change and let the human decide. Keep contributions small and
  reviewable; speculative or low-value ("slop") PRs are closed.

## Gotchas

- **Generated code is gitignored.** `tests/Proxies/`, `tests/Hydrators/`, `tests/PersistentCollections/`,
  and their `tools/sandbox/` equivalents are produced at runtime. Never edit or commit them; PHPCS
  already excludes them.
- **PHPStan baseline.** The project runs at level 5 with `phpstan-baseline.neon` and
  `reportUnmatchedIgnoredErrors: false`. When you fix a baseline error, delete its entry rather
  than leaving it behind; add new ignores only as a last resort.
- **PHP version floor is 8.4.** Don't use syntax or functions newer than 8.4 in `src/`; the
  project pulls in `symfony/polyfill-php86` for the `SortDirection` enum.
