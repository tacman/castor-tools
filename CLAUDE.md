# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

castor-tools is a **Castor plugin** (composer type: `castor-plugin`) that provides reusable tasks for Symfony application development. It is installed into consuming projects via `castor composer require tacman/castor-tools` and auto-discovered through glob imports in the project's root `castor.php`.

## Development Commands

```bash
composer install                    # Install dependencies
castor list tacman                  # List all tasks provided by this plugin
castor tacman:agent:doctor          # Health check for agent/MCP configuration
```

There are no test suites, linters, or CI pipelines configured for this project.

## Architecture

### Plugin Loading Chain

```
Consuming project's castor.php → glob(".castor/vendor/*/*/castor.php")
  → src/castor.php (task definitions, requires functions.php)
    → src/functions.php (shared helpers)
```

All tasks use `#[AsTask]` PHP 8 attributes and are namespaced under `tacman:` via the `CASTOR_TOOLS_NAMESPACE` constant.

### Key Files

- **`src/castor.php`** — All task definitions. Tasks fall into two groups:
  - **Symfony utilities**: `ez-locale`, `sqlite` (env/template manipulation)
  - **Agent/AI setup**: `agent:setup`, `agent:doctor`, `agent:mate:*`, `agent:chrome:setup`, `agent:context7:setup`, `agent:github:setup`, `agent:api:mcp:enable` (configure MCP servers for OpenCode, Codex, and Claude Code)
- **`src/functions.php`** — Helper functions for `.env` file management (`ensure_env`, `remove_env`, `get_env`) and config I/O (`read_opencode_config`, `write_opencode_config`, `write_codex_config_from_mcp`, `write_claude_config_from_mcp`)
- **`src/opencode.json`** — Template/example OpenCode MCP server configuration
- **`src/codex.toml`** — Template/example Codex configuration (generated from MCP config)

### Config File Generation

The agent setup tasks maintain three parallel config files in the consuming project's root:
- **`opencode.json`** — MCP server definitions for OpenCode (canonical source, read/write via helper functions)
- **`codex.toml`** — Generated from `opencode.json`'s MCP section using `yosymfony/toml`
- **`.claude/settings.json`** — Generated from `opencode.json`'s MCP section for Claude Code

All three are always written together when MCP configuration changes. The `http` transport type (used by GitHub MCP) is only supported by Claude Code — codex and opencode writers skip it.

## Conventions

- Tasks use Castor's `run()` for shell commands and `io()` for Symfony Console output
- MCP server timeout defaults to `DEFAULT_MATE_TIMEOUT_MS` (10000ms)
- The `opencode` task hashes the working directory to assign a deterministic port in range 11000–15000
- Target PHP 8.4 and Symfony 8. Do not write code for older PHP or Symfony versions. Use modern PHP 8.4 features (property hooks, asymmetric visibility, `new` in initializers, named arguments, enums, fibers, etc.) and Symfony 8 APIs exclusively.
