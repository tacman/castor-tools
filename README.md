# Common Castor Tasks

This repository contains functions and tasks I use during developing Symfony applications and using Castor.

## Auto-install castor 
For a flex-like experience for castor, replace castor.php with this


```php
<?php

use Castor\Attribute\AsTask;

use function Castor\{io,import,capture,run};

foreach (glob(__DIR__ . '/.castor/vendor/*/*/castor.php') as $castorFile) {
    import($castorFile);
}

```

## Installation

To install the package, you can use the following command:

```bash
castor composer require tacman/castor-tools
```

## In action

<pre>castor

   █████████                     █████
  ███░░░░░███                   ░░███
 ███     ░░░   ██████    █████  ███████    ██████  ████████
░███          ░░░░░███  ███░░  ░░░███░    ███░░███░░███░░███
░███           ███████ ░░█████   ░███    ░███ ░███ ░███ ░░░
░░███     ███ ███░░███  ░░░░███  ░███ ███░███ ░███ ░███
 ░░█████████ ░░████████ ██████   ░░█████ ░░██████  █████
  ░░░░░░░░░   ░░░░░░░░ ░░░░░░     ░░░░░   ░░░░░░  ░░░░░

castor <font color="#00AA00">v1.1.0</font>

<font color="#AA5500">Usage:</font>
  command [options] [arguments]

<font color="#AA5500">Options:</font>
  <font color="#00AA00">-h, --help</font>                       Display help for the given command. When no command is given display help for the <font color="#00AA00">list</font> command
  <font color="#00AA00">    --silent</font>                     Do not output any message
  <font color="#00AA00">-q, --quiet</font>                      Only errors are displayed. All other output is suppressed
  <font color="#00AA00">-V, --version</font>                    Display this application version
  <font color="#00AA00">    --ansi|--no-ansi</font>             Force (or disable --no-ansi) ANSI output
  <font color="#00AA00">-n, --no-interaction</font>             Do not ask any interactive question
  <font color="#00AA00">    --no-remote</font>                  Skip the import of all remote remote packages
  <font color="#00AA00">    --update-remotes</font>             Force the update of remote packages
  <font color="#00AA00">    --castor-file[=CASTOR-FILE]</font>  Specify an alternative castor file to use instead of the default &quot;castor.php&quot;
  <font color="#00AA00">-v|vv|vvv, --verbose</font>             Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug

<font color="#AA5500">Available commands:</font>
  <font color="#00AA00">completion</font>       Dump the shell completion script
  <font color="#00AA00">hello</font>            Welcome to Castor!
  <font color="#00AA00">help</font>             Display help for a command
  <font color="#00AA00">list</font>             List commands
  <font color="#00AA00">reset-database</font>   Purge and re-create the database
  <font color="#00AA00">start-services</font>   Start local docker services
 <font color="#AA5500">castor</font>
  <font color="#00AA00">castor:composer</font>  [composer] Interact with built-in Composer for castor
  <font color="#00AA00">castor:execute</font>   [execute] Execute a remote task from a packagist directory
 <font color="#AA5500">tacman</font>
  <font color="#00AA00">tacman:sqlite</font>    Switch to sqlite
</pre>

```bash
castor
```
```bash
castor tacman:sqlite
castor tacman:sqlite --remove
```

