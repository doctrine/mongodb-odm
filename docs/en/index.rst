MongoDB ODM Documentation
=========================

The Doctrine MongoDB ODM documentation is comprised of tutorials, a reference section and
cookbook articles that explain different parts of the Object Document Mapper.

Getting Help
------------

If this documentation is not helping to answer questions you have about the
Doctrine MongoDB ODM, don't panic. You can get help from different sources:

-  Slack chat room `#mongodb-odm <https://www.doctrine-project.org/slack>`_
-  On `Stack Overflow <http://stackoverflow.com/questions/tagged/doctrine-odm>`_
-  The `Doctrine Mailing List <http://groups.google.com/group/doctrine-user>`_
-  Report a bug on `GitHub <https://github.com/doctrine/mongodb-odm/issues>`_.

Getting Started
---------------

The best way to get started is with the :doc:`Setup <reference/introduction#setup>` section
in the introduction tutorial. Use the sidebar to browse other tutorials and documentation
for the Doctrine PHP MongoDB ODM.

AI Agent Skill
--------------

This package ships a coding-agent skill under ``skills/mongodb-odm/`` that teaches an AI
assistant how to use the ODM correctly: mapping documents, querying and aggregating,
modeling references and embeds, and using Atlas Search, Vector Search, and Queryable
Encryption — while avoiding the mistakes that come from treating it like a SQL ORM.

The skill is declared in ``composer.json`` under ``extra.ai-mate.skills``, so projects
using `Symfony AI Mate <https://github.com/symfony/ai-mate>`_ discover it automatically,
with no configuration in the consuming project:

.. code-block:: bash

    composer require --dev symfony/ai-mate
    vendor/bin/mate init
    vendor/bin/mate discover

Mate installs the skill as ``mate-mongodb-odm`` and mirrors it into ``.claude/skills/``
and ``.agents/skills/``. Agents that read ``SKILL.md`` directly can be pointed at
``vendor/doctrine/mongodb-odm/skills/mongodb-odm/SKILL.md``.
