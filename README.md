# Common Castor Tasks

This repository contains functions and tasks I use during developing Symfony applications and using Castor.

## Installation

To install the package, you can use the following command:

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

```bash
castor composer require tacman/castor-tools
```

## In action

<pre>castor
 ✔ <font color="#00CD00">Remote packages imported</font>

 ▄████▄   ▄▄▄        ██████ ▄▄▄█████▓ ▒█████   ██▀███
▒██▀ ▀█  ▒████▄    ▒██    ▒ ▓  ██▒ ▓▒▒██▒  ██▒▓██ ▒ ██▒
▒▓█    ▄ ▒██  ▀█▄  ░ ▓██▄   ▒ ▓██░ ▒░▒██░  ██▒▓██ ░▄█ ▒
▒▓▓▄ ▄██▒░██▄▄▄▄██   ▒   ██▒░ ▓██▓ ░ ▒██   ██░▒██▀▀█▄
▒ ▓███▀ ░ ▓█   ▓██▒▒██████▒▒  ▒██▒ ░ ░ ████▓▒░░██▓ ▒██▒
░ ░▒ ▒  ░ ▒▒   ▓▒█░▒ ▒▓▒ ▒ ░  ▒ ░░   ░ ▒░▒░▒░ ░ ▒▓ ░▒▓░
  ░  ▒     ▒   ▒▒ ░░ ░▒  ░ ░    ░      ░ ▒ ▒░   ░▒ ░ ▒░
░          ░   ▒   ░  ░  ░    ░      ░ ░ ░ ▒    ░░   ░
░ ░            ░  ░      ░               ░ ░     ░
░

castor <font color="#00CD00">v1.0.0</font>

<font color="#CDCD00">Usage:</font>
  command [options] [arguments]

<font color="#CDCD00">Options:</font>
  <font color="#00CD00">-h, --help</font>            Display help for the given command. When no command is given display help for the <font color="#00CD00">list</font> command
  <font color="#00CD00">    --silent</font>          Do not output any message
  <font color="#00CD00">-q, --quiet</font>           Only errors are displayed. All other output is suppressed
  <font color="#00CD00">-V, --version</font>         Display this application version
  <font color="#00CD00">    --ansi|--no-ansi</font>  Force (or disable --no-ansi) ANSI output
  <font color="#00CD00">-n, --no-interaction</font>  Do not ask any interactive question
  <font color="#00CD00">    --no-remote</font>       Skip the import of all remote remote packages
  <font color="#00CD00">    --update-remotes</font>  Force the update of remote packages
  <font color="#00CD00">-v|vv|vvv, --verbose</font>  Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug

<font color="#CDCD00">Available commands:</font>
  <font color="#00CD00">completion</font>       Dump the shell completion script
  <font color="#00CD00">hello</font>            Welcome to Castor!
  <font color="#00CD00">help</font>             Display help for a command
  <font color="#00CD00">list</font>             List commands
 <font color="#CDCD00">castor</font>
  <font color="#00CD00">castor:composer</font>  [composer] Interact with built-in Composer for castor
  <font color="#00CD00">castor:execute</font>   [execute] Execute a remote task from a packagist directory
 <font color="#CDCD00">tacman</font>
  <font color="#00CD00">tacman:sqlite</font>    Switch to sqlite
<font color="#00FF00"><b>tac@system76-pc</b></font>:<font color="#5C5CFF"><b>~/sites/dummy</b></font>$ castor tacman:sqlite --help
<font color="#CDCD00">Description:</font>
  Switch to sqlite

<font color="#CDCD00">Usage:</font>
  survos:sqlite [options]

<font color="#CDCD00">Options:</font>
  <font color="#00CD00">    --remove</font>          remove the DATABASE_URL key
  <font color="#00CD00">-h, --help</font>            Display help for the given command. When no command is given display help for the <font color="#00CD00">list</font> command
  <font color="#00CD00">    --silent</font>          Do not output any message
  <font color="#00CD00">-q, --quiet</font>           Only errors are displayed. All other output is suppressed
  <font color="#00CD00">-V, --version</font>         Display this application version
  <font color="#00CD00">    --ansi|--no-ansi</font>  Force (or disable --no-ansi) ANSI output
  <font color="#00CD00">-n, --no-interaction</font>  Do not ask any interactive question
  <font color="#00CD00">    --no-remote</font>       Skip the import of all remote remote packages
  <font color="#00CD00">    --update-remotes</font>  Force the update of remote packages
  <font color="#00CD00">-v|vv|vvv, --verbose</font>  Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug
<font color="#00FF00"><b>tac@system76-pc</b></font>:<font color="#5C5CFF"><b>~/sites/dummy</b></font>$ 
</pre>

```bash
castor
```
```bash
castor tacman:sqlite
castor tacman:sqlite --remove
```

