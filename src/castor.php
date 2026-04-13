<?php

namespace Tacman\CastorTools;

use Castor\Attribute\{AsTask, AsOption};
use Yosymfony\Toml\TomlBuilder;
use function Castor\{io,fs,capture,run};
use function Tacman\CastorTools\{ensure_env, remove_env, get_env};

require_once __DIR__ . '/functions.php';


const CASTOR_TOOLS_NAMESPACE = 'tacman';

const OPENCODE_CONFIG_FILE = 'opencode.json';
const CODEX_CONFIG_FILE = 'codex.toml';
const CLAUDE_PROJECT_MCP_FILE = '.mcp.json';
const CLAUDE_GLOBAL_MCP_FILE = '/.claude.json';
const CLAUDE_GLOBAL_MCP_SERVERS = ['chrome-devtools', 'context7', 'github'];
const SYMFONY_PROXY_CONFIG = '/.symfony5/proxy.json';
const OPENCODE_SCHEMA = 'https://opencode.ai/config.json';
const DEFAULT_MATE_TIMEOUT_MS = 10000;

#[AsTask(name: 'ez-locale', namespace: CASTOR_TOOLS_NAMESPACE, description: 'Add locale menu to easyadmin')]
function ez_locale(
    #[\Castor\Attribute\AsOption(description: 'Force overwriting template even if it exists')] bool $force=false
): void
{
    fs()->mkdir('templates/bundles/EasyAdminBundle');
    file_put_contents('templates/bundles/EasyAdminBundle/layout.html.twig', "{% extends '@SurvosEz/admin/layout.html.twig' %}");
}

#[AsTask(name: 'sqlite', namespace: CASTOR_TOOLS_NAMESPACE, description: 'Switch to sqlite')]
function switch_to_sqlite(
    #[\Castor\Attribute\AsOption(description: 'remove the DATABASE_URL key')] bool $remove=false
): void
{
    if ($remove) {
        remove_env('.env.local', 'DATABASE_URL');
    } else {
        ensure_env('.env.local', 'DATABASE_URL', 'sqlite:///%kernel.project_dir%/var/data.db');
        run('php bin/console doctrine:schema:validate');
    }
    $content = fs()->readFile($fn = '.env.local');
    io()->write($content);
}

#[AsTask(name: 'opencode', description: 'opencode web on the OPENCODE_PORT hash port', namespace: CASTOR_TOOLS_NAMESPACE)]
function opencode(): void
{
    $dir = getcwd();
    $hash = hexdec(substr(hash('xxh3', $dir), 0, 8));
    $port = 11000 + ($hash % 4000);
    $url = "http://127.0.0.1:$port";

    // Check if already running on this port
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 1,
        CURLOPT_TIMEOUT => 2,
        CURLOPT_NOBODY => true,
    ]);
    curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if ($httpCode > 0) {
        io()->note("opencode already running on port $port — opening $url");
        exec("xdg-open $url 2>/dev/null || open $url 2>/dev/null &");
        return;
    }

    io()->note("Starting opencode on port $port (project: " . basename($dir) . ")");
    run("opencode web --port=$port");
}

#[AsTask(name: 'agent:mate:install', namespace: CASTOR_TOOLS_NAMESPACE, description: 'Install Symfony Mate packages')]
function agent_mate_install(): void
{
    if (!is_file('composer.json')) {
        io()->error('composer.json not found in current directory.');

        return;
    }

    io()->section('Installing Symfony Mate packages');
    run('composer require --dev symfony/ai-mate symfony/ai-symfony-mate-extension');
}

#[AsTask(name: 'agent:mate:discover', namespace: CASTOR_TOOLS_NAMESPACE, description: 'Discover Symfony Mate extensions')]
function agent_mate_discover(): void
{
    $mateBin = getcwd() . '/vendor/bin/mate';

    if (!is_file($mateBin)) {
        io()->warning('vendor/bin/mate not found. Run tacman:agent:mate:install first.');

        return;
    }

    io()->section('Discovering Mate extensions');
    run([$mateBin, 'discover']);
}

#[AsTask(name: 'agent:mate:refresh', namespace: CASTOR_TOOLS_NAMESPACE, description: 'Refresh Mate extension and MCP visibility')]
function agent_mate_refresh(): void
{
    $mateBin = getcwd() . '/vendor/bin/mate';

    if (!is_file($mateBin)) {
        io()->warning('vendor/bin/mate not found. Run tacman:agent:mate:install first.');

        return;
    }

    io()->section('Refreshing Symfony Mate');
    run([$mateBin, 'discover']);
    run([$mateBin, 'debug:extensions', '--show-all']);
    run([$mateBin, 'mcp:tools:list']);
}

#[AsTask(name: 'agent:chrome:setup', namespace: CASTOR_TOOLS_NAMESPACE, description: 'Enable Chrome DevTools MCP in opencode config')]
function agent_chrome_setup(): void
{
    $config = read_opencode_config();
    $config['mcp']['chrome-devtools'] = [
        'type' => 'local',
        'command' => ['npx', '-y', 'chrome-devtools-mcp@latest'],
        'enabled' => true,
        'timeout' => DEFAULT_MATE_TIMEOUT_MS,
    ];

    write_opencode_config($config);
    $mcpArray = is_array($config['mcp'] ?? null) ? $config['mcp'] : [];
    write_codex_config_from_mcp($mcpArray);
    write_claude_config_from_mcp($mcpArray);

    io()->success('Configured Chrome DevTools MCP in opencode.json, codex.toml, and ~/.claude/settings.json (global).');
    io()->writeln('Docs: https://github.com/ChromeDevTools/chrome-devtools-mcp?tab=readme-ov-file#mcp');
}

#[AsTask(name: 'agent:context7:setup', namespace: CASTOR_TOOLS_NAMESPACE, description: 'Enable Context7 MCP for up-to-date library documentation')]
function agent_context7_setup(): void
{
    $config = read_opencode_config();
    $config['mcp']['context7'] = [
        'type' => 'local',
        'command' => ['npx', '-y', '@upstash/context7-mcp@latest'],
        'enabled' => true,
        'timeout' => DEFAULT_MATE_TIMEOUT_MS,
    ];

    write_opencode_config($config);
    $mcpArray = is_array($config['mcp'] ?? null) ? $config['mcp'] : [];
    write_codex_config_from_mcp($mcpArray);
    write_claude_config_from_mcp($mcpArray);

    io()->success('Configured Context7 MCP (library docs) in opencode.json, codex.toml, and ~/.claude/settings.json (global).');
}

#[AsTask(name: 'agent:github:setup', namespace: CASTOR_TOOLS_NAMESPACE, description: 'Enable GitHub MCP server (Claude Code only, HTTP transport)')]
function agent_github_mcp_setup(): void
{
    $config = read_opencode_config();
    $config['mcp']['github'] = [
        'type' => 'http',
        'url' => 'https://api.githubcopilot.com/mcp/',
        'enabled' => true,
        'timeout' => DEFAULT_MATE_TIMEOUT_MS,
    ];

    write_opencode_config($config);
    $mcpArray = is_array($config['mcp'] ?? null) ? $config['mcp'] : [];
    write_codex_config_from_mcp($mcpArray);
    write_claude_config_from_mcp($mcpArray);

    io()->success('Configured GitHub MCP in ~/.claude/settings.json (global, Claude Code only — HTTP transport).');
    io()->note('Run /mcp inside Claude Code to authenticate with GitHub.');
}

#[AsTask(name: 'agent:api:mcp:enable', namespace: CASTOR_TOOLS_NAMESPACE, description: 'Enable API Platform MCP server in opencode config when available')]
function agent_api_mcp_enable(
    #[AsOption(description: 'MCP endpoint URL to use (auto-detected from Symfony proxy if omitted)')] string $url = ''
): void
{
    if (!api_platform_installed()) {
        io()->warning('API Platform not detected in composer.json (api-platform/*). Skipping MCP config.');

        return;
    }

    if ($url === '') {
        $proxyDomain = detect_symfony_proxy_domain();
        if ($proxyDomain !== null) {
            $url = 'https://' . $proxyDomain . '/mcp';
        } else {
            $url = 'http://127.0.0.1:8000/mcp';
            io()->warning('Could not detect Symfony proxy domain. Falling back to ' . $url);
        }
    }

    $config = read_opencode_config();
    $config['mcp']['api-platform'] = [
        'type' => 'remote',
        'url' => $url,
        'enabled' => true,
        'timeout' => DEFAULT_MATE_TIMEOUT_MS,
    ];

    write_opencode_config($config);
    $mcpArray = is_array($config['mcp'] ?? null) ? $config['mcp'] : [];
    write_codex_config_from_mcp($mcpArray);
    write_claude_config_from_mcp($mcpArray);

    io()->success(sprintf('Configured API Platform MCP endpoint: %s', $url));
}

#[AsTask(name: 'agent:setup', namespace: CASTOR_TOOLS_NAMESPACE, description: 'Bootstrap agent MCP servers for OpenCode, Codex, and Claude Code')]
function agent_setup(
    #[AsOption(description: 'MCP endpoint URL for API Platform (auto-detected from Symfony proxy if omitted)')] string $apiMcpUrl = ''
): void
{
    io()->title('Agent surface setup');
    io()->writeln('Project: ' . getcwd());

    agent_mate_install();
    agent_mate_discover();
    configure_mate_mcp_server();
    agent_chrome_setup();
    agent_context7_setup();
    agent_github_mcp_setup();
    agent_api_mcp_enable($apiMcpUrl);

    io()->newLine();
    io()->success('Setup complete. Config written to opencode.json, codex.toml, .mcp.json, and ~/.claude.json.');
    io()->writeln('Verify with:');
    io()->writeln('  vendor/bin/mate debug:extensions --show-all');
    io()->writeln('  vendor/bin/mate mcp:tools:list');
    io()->writeln('  opencode mcp list');
    io()->writeln('  claude mcp list');
}

#[AsTask(name: 'agent:doctor', namespace: CASTOR_TOOLS_NAMESPACE, description: 'Check local agent surfaces and MCP config')]
function agent_doctor(): void
{
    $cwd = getcwd();
    $composerPath = $cwd . '/composer.json';
    $mateBin = $cwd . '/vendor/bin/mate';
    $config = read_opencode_config();
    $mcp = is_array($config['mcp'] ?? null) ? $config['mcp'] : [];

    io()->title('Agent doctor');
    io()->writeln('Project: ' . $cwd);
    io()->writeln('composer.json: ' . (is_file($composerPath) ? 'yes' : 'no'));
    io()->writeln('Mate installed: ' . (is_file($mateBin) ? 'yes' : 'no'));
    io()->writeln('API Platform present: ' . (api_platform_installed() ? 'yes' : 'no'));
    io()->writeln('codex.toml present: ' . (is_file($cwd . '/' . CODEX_CONFIG_FILE) ? 'yes' : 'no'));
    io()->writeln('.mcp.json present: ' . (is_file($cwd . '/' . CLAUDE_PROJECT_MCP_FILE) ? 'yes' : 'no'));
    io()->writeln('Chrome MCP configured: ' . (isset($mcp['chrome-devtools']) ? 'yes' : 'no'));
    io()->writeln('Symfony Mate MCP configured: ' . (isset($mcp['symfony-mate']) ? 'yes' : 'no'));
    io()->writeln('Context7 MCP configured: ' . (isset($mcp['context7']) ? 'yes' : 'no'));
    io()->writeln('GitHub MCP configured: ' . (isset($mcp['github']) ? 'yes' : 'no'));
    io()->writeln('API Platform MCP configured: ' . (isset($mcp['api-platform']) ? 'yes' : 'no'));

    if (empty($mcp)) {
        io()->warning('No MCP servers found in opencode.json.');
    } else {
        io()->section('Configured MCP servers');
        foreach (array_keys($mcp) as $serverName) {
            io()->writeln(' - ' . $serverName);
        }
    }

    if (is_file($mateBin)) {
        io()->section('Mate quick checks');
        run([$mateBin, 'debug:extensions', '--show-all']);
        run([$mateBin, 'mcp:tools:list']);
    }

    if (is_executable_on_path('opencode')) {
        io()->section('OpenCode MCP visibility');
        run('opencode mcp list');
    } else {
        io()->warning('opencode is not available on PATH; skipping `opencode mcp list`.');
    }
}

function configure_mate_mcp_server(): void
{
    $cwd = getcwd();
    $mateBin = $cwd . '/vendor/bin/mate';

    if (!is_file($mateBin)) {
        io()->warning('Skipping symfony-mate MCP config because vendor/bin/mate is missing.');

        return;
    }

    $config = read_opencode_config();
    $config['mcp']['symfony-mate'] = [
        'type' => 'local',
        'command' => ['php', $mateBin, 'serve'],
        'enabled' => true,
        'timeout' => DEFAULT_MATE_TIMEOUT_MS,
    ];

    write_opencode_config($config);
    $mcpArray = is_array($config['mcp'] ?? null) ? $config['mcp'] : [];
    write_codex_config_from_mcp($mcpArray);
    write_claude_config_from_mcp($mcpArray);
    io()->success('Configured Symfony Mate MCP in opencode.json, codex.toml, and .mcp.json.');
}

function read_opencode_config(): array
{
    $configPath = getcwd() . '/' . OPENCODE_CONFIG_FILE;
    if (!is_file($configPath)) {
        return [
            '$schema' => OPENCODE_SCHEMA,
            'mcp' => [],
        ];
    }

    $raw = file_get_contents($configPath);
    if ($raw === false || trim($raw) === '') {
        return [
            '$schema' => OPENCODE_SCHEMA,
            'mcp' => [],
        ];
    }

    $decoded = json_decode($raw, true);
    if (!is_array($decoded)) {
        io()->warning('opencode.json is not valid JSON. Recreating a minimal config.');

        return [
            '$schema' => OPENCODE_SCHEMA,
            'mcp' => [],
        ];
    }

    if (!isset($decoded['$schema']) || !is_string($decoded['$schema'])) {
        $decoded['$schema'] = OPENCODE_SCHEMA;
    }

    if (!isset($decoded['mcp']) || !is_array($decoded['mcp'])) {
        $decoded['mcp'] = [];
    }

    return $decoded;
}

function write_opencode_config(array $config): void
{
    if (!isset($config['$schema']) || !is_string($config['$schema'])) {
        $config['$schema'] = OPENCODE_SCHEMA;
    }
    if (!isset($config['mcp']) || !is_array($config['mcp'])) {
        $config['mcp'] = [];
    }

    $json = json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    if (!is_string($json)) {
        io()->error('Failed to encode opencode config as JSON.');

        return;
    }
    $json .= "\n";

    file_put_contents(getcwd() . '/' . OPENCODE_CONFIG_FILE, $json);
}

function write_codex_config_from_mcp(array $mcp): void
{
    $builder = new TomlBuilder();
    $builder
        ->addValue('model', 'gpt-5-codex')
        ->addValue('model_reasoning_effort', 'high')
        ->addValue('approval_policy', 'on-failure')
        ->addValue('sandbox_mode', 'workspace-write');

    foreach ($mcp as $name => $server) {
        if (!is_string($name) || !is_array($server)) {
            continue;
        }

        $type = is_string($server['type'] ?? null) ? $server['type'] : null;
        // Codex only supports local (stdio) and remote — skip http-only servers
        if ($type === 'http') {
            continue;
        }

        $builder->addTable('mcp_servers.' . $name);

        $type = is_string($server['type'] ?? null) ? $server['type'] : null;
        if ($type === 'remote' && is_string($server['url'] ?? null)) {
            $builder->addValue('url', $server['url']);
        } elseif ($type === 'local' && is_array($server['command'] ?? null) && isset($server['command'][0]) && is_string($server['command'][0])) {
            $command = $server['command'];
            $binary = array_shift($command);
            $builder->addValue('command', $binary);

            $args = [];
            foreach ($command as $arg) {
                if (is_string($arg)) {
                    $args[] = $arg;
                }
            }
            $builder->addValue('args', $args);
        }

        if (isset($server['enabled']) && is_bool($server['enabled'])) {
            $builder->addValue('enabled', $server['enabled']);
        }

        if (isset($server['timeout']) && is_int($server['timeout'])) {
            $builder->addValue('startup_timeout_ms', $server['timeout']);
            $builder->addValue('tool_timeout_ms', $server['timeout']);
        }
    }

    $toml = $builder->getTomlString();
    if (!str_ends_with($toml, "\n")) {
        $toml .= "\n";
    }

    file_put_contents(getcwd() . '/' . CODEX_CONFIG_FILE, $toml);
}

function write_claude_config_from_mcp(array $mcp): void
{
    $globalServers = [];
    $projectServers = [];

    foreach ($mcp as $name => $server) {
        if (!is_string($name) || !is_array($server)) {
            continue;
        }

        $entry = mcp_to_claude_entry($server);
        if ($entry === null) {
            continue;
        }

        if (in_array($name, CLAUDE_GLOBAL_MCP_SERVERS, true)) {
            $globalServers[$name] = $entry;
        } else {
            $projectServers[$name] = $entry;
        }
    }

    $home = getenv('HOME') ?: ($_SERVER['HOME'] ?? '');
    if ($home !== '') {
        write_claude_mcp_file($home . CLAUDE_GLOBAL_MCP_FILE, $globalServers);
    }

    write_claude_mcp_file(getcwd() . '/' . CLAUDE_PROJECT_MCP_FILE, $projectServers);
}

function mcp_to_claude_entry(array $server): ?array
{
    $type = is_string($server['type'] ?? null) ? $server['type'] : null;

    if ($type === 'http' && is_string($server['url'] ?? null)) {
        return ['type' => 'http', 'url' => $server['url']];
    }

    if ($type === 'remote' && is_string($server['url'] ?? null)) {
        return ['type' => 'sse', 'url' => $server['url']];
    }

    if ($type === 'local' && is_array($server['command'] ?? null) && isset($server['command'][0]) && is_string($server['command'][0])) {
        $command = $server['command'];
        $binary = array_shift($command);
        $args = [];
        foreach ($command as $arg) {
            if (is_string($arg)) {
                $args[] = $arg;
            }
        }

        return ['type' => 'stdio', 'command' => $binary, 'args' => $args];
    }

    return null;
}

function write_claude_mcp_file(string $path, array $mcpServers): void
{
    $config = [];
    if (is_file($path)) {
        $raw = file_get_contents($path);
        if (is_string($raw) && trim($raw) !== '') {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                $config = $decoded;
            }
        }
    }

    // Merge new servers into existing, preserving any manually added ones
    $existing = is_array($config['mcpServers'] ?? null) ? $config['mcpServers'] : [];
    $config['mcpServers'] = array_merge($existing, $mcpServers);

    $json = json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    if (!is_string($json)) {
        io()->error('Failed to encode Claude config as JSON: ' . $path);

        return;
    }
    $json .= "\n";

    file_put_contents($path, $json);
}

function detect_symfony_proxy_domain(): ?string
{
    $home = getenv('HOME') ?: ($_SERVER['HOME'] ?? '');
    if ($home === '') {
        return null;
    }

    $proxyPath = $home . SYMFONY_PROXY_CONFIG;
    if (!is_file($proxyPath)) {
        return null;
    }

    $raw = file_get_contents($proxyPath);
    if (!is_string($raw) || trim($raw) === '') {
        return null;
    }

    $proxy = json_decode($raw, true);
    if (!is_array($proxy)) {
        return null;
    }

    $tld = is_string($proxy['tld'] ?? null) ? $proxy['tld'] : 'wip';
    $domains = is_array($proxy['domains'] ?? null) ? $proxy['domains'] : [];
    $cwd = getcwd();

    foreach ($domains as $domain => $dir) {
        if (!is_string($domain) || !is_string($dir)) {
            continue;
        }
        // Resolve ~ in paths
        if (str_starts_with($dir, '~/')) {
            $dir = $home . substr($dir, 1);
        }
        if (realpath($dir) === realpath($cwd)) {
            return $domain . '.' . $tld;
        }
    }

    return null;
}

function api_platform_installed(): bool
{
    $composerPath = getcwd() . '/composer.json';
    if (!is_file($composerPath)) {
        return false;
    }

    $decoded = json_decode((string) file_get_contents($composerPath), true);
    if (!is_array($decoded)) {
        return false;
    }

    $packages = array_merge(
        is_array($decoded['require'] ?? null) ? array_keys($decoded['require']) : [],
        is_array($decoded['require-dev'] ?? null) ? array_keys($decoded['require-dev']) : [],
    );

    foreach ($packages as $package) {
        if (str_starts_with($package, 'api-platform/')) {
            return true;
        }
    }

    return false;
}

function is_executable_on_path(string $binary): bool
{
    $result = shell_exec(sprintf('command -v %s 2>/dev/null', escapeshellarg($binary)));

    return is_string($result) && trim($result) !== '';
}
