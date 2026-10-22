# Security Policy

Please **do not report security vulnerabilities through public GitHub issues,
pull requests or discussions.** A public report exposes every user of the
library before a fix is available.

## Reporting a vulnerability

**Preferred: GitHub private vulnerability reporting**

1. Open the [Security tab](https://github.com/doctrine/mongodb-odm/security) of this repository.
2. Click **Report a vulnerability**, or go straight to the
   [report form](https://github.com/doctrine/mongodb-odm/security/advisories/new).
3. Describe the problem and submit.

Your report is visible only to the maintainers. It lets us discuss the issue
with you, prepare a fix in private and publish a security advisory once
patched releases are available.

**Fallback: email**

Only if you cannot use GitHub, send your report to
[security@doctrine-project.org](mailto:security@doctrine-project.org). Emails
sent to this address are forwarded to the Doctrine core team's private mailing
list.

Whichever way you report, the following details help us a lot:

- the affected version(s) of the ODM, and of PHP, `ext-mongodb` and
  `mongodb/mongodb` if relevant
- a description of the problem and its impact
- a minimal way to reproduce it
- a suggested fix, if you have one

## What is a security issue?

A security issue is a flaw in the ODM itself that lets an attacker compromise
the confidentiality, integrity or availability of an application, **even
though that application uses the ODM as documented**. Examples:

- **Injection:** user-controlled input is interpreted as a query operator or
  aggregation stage where the ODM treats it as a plain value.
- **Silently weakened protection:** a bug that causes encryption configuration
  (for example Queryable Encryption fields) to be dropped or ignored, so that
  data is stored unencrypted.
- **Unsafe instantiation or code execution:** the ODM instantiates arbitrary
  classes, or generated code (proxies, hydrators) can be made to include
  attacker-controlled code, based on data from the database or other untrusted
  input.
- **Unintended data access:** the ODM reads, returns or writes documents or
  fields the application did not ask for, for example because a filter is
  dropped or a reference resolves to the wrong collection.
- **Denial of service:** small, crafted input causes disproportionate resource
  consumption inside the ODM.

The following are usually **not** security issues:

- Ordinary bugs with no security consequence. Please use the
  [issue tracker](https://github.com/doctrine/mongodb-odm/issues) for these.
- An application passing unvalidated input to an API that is documented to
  accept raw MongoDB expressions.
- Vulnerabilities in MongoDB server, the PHP driver (`ext-mongodb`),
  `mongodb/mongodb` or other dependencies. Please report those to the
  respective projects. For MongoDB products, see
  [Create a Vulnerability Report](https://www.mongodb.com/docs/manual/tutorial/create-a-vulnerability-report/).
- Insecure database deployment, such as missing authentication or an exposed
  port.

**If you are unsure, report privately.** We would much rather triage a report
that turns out to be a regular bug than have a vulnerability discussed in
public. If we conclude that it is not a vulnerability, we will tell you, and
you are then welcome to open a public issue.

## Reported publicly by mistake?

If you have already opened a public issue or pull request for something that
may be a vulnerability, please
[report it privately](https://github.com/doctrine/mongodb-odm/security/advisories/new)
and reference it. We may edit, hide or lock the public item and continue in
private. Please do not add further exploit details in the meantime.

## What happens next

We follow the process described in the
[Doctrine security policy](https://www.doctrine-project.org/policies/security.html):
we confirm the issue, acknowledge your report, prepare a patch, release fixed
versions and publish an advisory crediting you, unless you prefer to remain
anonymous. Please do not disclose the issue publicly until we have published
the fix.
