<?php

namespace Tacman\CastorTools;

use Castor\Attribute\{AsTask, AsOption};
use function Castor\{io,fs,capture,run};
use function Tacman\CastorTools\{ensure_env, remove_env};

const CASTOR_TOOLS_NAMESPACE = 'survos';

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
