<?php

namespace Tacman\CastorTools;

use Castor\Attribute\{AsTask, AsOption};
use function Castor\{io,fs,capture,run};
use function Tacman\CastorTools\{ensure_env, remove_env, get_env};

require_once __DIR__ . '/functions.php';


const CASTOR_TOOLS_NAMESPACE = 'tacman';

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
function opencode(

): void
{
    $dir = getcwd();
    $hash = hexdec(substr(hash('xxh3', $dir), 0, 8));
    $port = 11000 + ($hash % 4000);

    io()->note("Starting opencode on port $port (project: " . basename($dir) . ")");
    run("opencode web --port=$port" );
}

