---
name: install-php-fixer
description: Install, update, or verify nette/coding-standard globally for PHP code style checking and automatic fixing. Use when the user asks to install PHP fixer, Nette Coding Standard, ECS, code style tooling, or troubleshoot missing PHP style fixing.
---

# Install PHP Fixer

Install `nette/coding-standard` globally so the `ecs` tool is available for PHP code style checks and fixes.

## Pre-Flight

Check Composer first:

```powershell
composer --version
```

Check whether the package is already installed:

```powershell
composer global show nette/coding-standard
```

If Composer is missing, stop and tell the user to install Composer. If `nette/coding-standard` is already installed, ask whether to update or leave it.

## Install Or Update

Allow the required Composer plugin:

```powershell
composer global config allow-plugins.dealerdirect/phpcodesniffer-composer-installer true
```

Install or update the coding standard:

```powershell
composer global require nette/coding-standard
```

Network access is required. If Composer fails because of sandboxed network access, rerun the needed Composer command with escalation.

## Verify

Find Composer's global home:

```powershell
composer global config home
```

Verify that `vendor/bin/ecs` exists under that Composer home. On Windows, also check for `vendor/bin/ecs.bat`.

If available on PATH, verify directly:

```powershell
ecs --version
```

## Usage

From the project root, typical checks are:

```powershell
ecs check app
ecs check app --fix
```

If the project has `ncs.xml` or `ncs.php`, let that config drive the rules. Otherwise use the Nette Coding Standard defaults or an explicit preset compatible with the installed package.

## Troubleshooting

- `composer: command not found`: install Composer and reopen the terminal.
- Permission denied during global install: on Windows, use an elevated terminal if needed.
- PHP version conflict: check `php --version` and the package requirements.
- `ecs` not found after install: use the Composer global home path or add Composer global `vendor/bin` to PATH.
- Preset not found: inspect installed package docs/version and use a supported preset.
