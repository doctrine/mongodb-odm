#!/bin/sh

# Copy the pre-commit hook to the current repository hooks directory.
cp contrib/pre-commit .git/hooks/pre-commit

# Add execution permission for pre-commit file.
chmod +x .git/hooks/pre-commit