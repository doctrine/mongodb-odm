# Doctrine MongoDB Object Document Mapper

[![Build Status](https://github.com/doctrine/mongodb-odm/workflows/Continuous%20Integration/badge.svg)](https://github.com/doctrine/mongodb-odm/actions?query=workflow%3A%22Continuous+Integration%22)
[![Code Coverage](https://codecov.io/gh/doctrine/mongodb-odm/branch/2.2.x/graph/badge.svg)](https://codecov.io/gh/doctrine/mongodb-odm/branch/2.2.x)
[![Gitter](https://badges.gitter.im/doctrine/mongodb-odm.svg)](https://gitter.im/doctrine/mongodb-odm)


The Doctrine MongoDB ODM project is a library that provides a PHP object mapping functionality for MongoDB.

## AI agent skill

This package ships a coding-agent skill at [`skills/mongodb-odm/`](skills/mongodb-odm/SKILL.md).
It teaches an agent how to map documents, query and aggregate, model references and
embeds, and use Atlas Search, Vector Search, and Queryable Encryption correctly — and
how to avoid the mistakes that come from treating the ODM like a SQL ORM.

The skill is declared in `composer.json` under `extra.ai-mate.skills`, so projects using
[Symfony AI Mate](https://github.com/symfony/ai-mate) discover it automatically, with no
configuration in the consuming project:

```bash
composer require --dev symfony/ai-mate
vendor/bin/mate init
vendor/bin/mate discover
```

Mate installs the skill as `mate-mongodb-odm` and mirrors it into `.claude/skills/` and
`.agents/skills/`. Other agents that read `SKILL.md` directly can be pointed at
`vendor/doctrine/mongodb-odm/skills/mongodb-odm/SKILL.md`.

## More resources:

* [Website](https://www.doctrine-project.org/projects/mongodb-odm.html)
* [Documentation](https://www.doctrine-project.org/projects/doctrine-mongodb-odm/en/stable/)
* [Issue Tracker](https://github.com/doctrine/mongodb-odm/issues)
* [Releases](https://github.com/doctrine/mongodb-odm/releases)
