# Agent Skill evals

`skills/mongodb-odm/` is an [Agent Skill](https://code.claude.com/docs/en/skills):
Markdown that an AI coding assistant loads on demand when a user asks about
Doctrine MongoDB ODM. It ships in the Composer dist package, so a consuming
project can point its assistant at `vendor/doctrine/mongodb-odm/skills/`.

The `evals.json` file in each subdirectory here is that skill's test suite.

## What an eval is

Each entry pairs a `prompt` — a realistic question a user would ask an assistant
— with an `expected_output` describing, in prose, what a correct answer must
contain: real attribute and method names, stated version and platform
prerequisites, and the absence of plausible-sounding APIs that don't exist.

The suite deliberately favours questions a general-purpose model gets *wrong*
without the skill. Common mapping and querying is well represented in model
training data and scores the same either way, so it proves nothing about the
skill; those cases are kept only as a floor, to catch a regression where the
skill makes a previously correct answer worse.

Every reference file under `skills/mongodb-odm/references/` should be exercised
by at least one eval.

## Running them

There is no automated runner and these do not run in CI: grading a prose answer
against a prose expectation needs a model, which means an API key, which we
don't want as a requirement for contributing. Run them manually when changing
the skill.

For each eval:

1. Start a fresh assistant session with the skill available (for Claude Code,
   copy or symlink `skills/mongodb-odm/` into `~/.claude/skills/`, or run from a
   checkout where `.claude/skills/` points at it).
2. Send the `prompt` verbatim. Nothing else — no follow-ups, no hints.
3. Grade the reply against `expected_output`, treating each clause as a separate
   pass/fail assertion rather than forming a general impression.

To show a change to the skill actually helps, run the same prompts twice — once
with the skill loaded and once without — and compare per-assertion scores. A
result that is identical in both arms is not evidence for the skill.

## Changing the skill

Update the evals in the same pull request. A new reference file needs a new eval;
a new gotcha worth documenting is usually worth an assertion.
