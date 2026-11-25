<?php

// Creates runtime directories after extracting the project.
foreach (['bootstrap/cache', 'storage/app/private', 'storage/framework/cache/data', 'storage/framework/sessions', 'storage/framework/views', 'storage/logs'] as $dir) {
    if (! is_dir(__DIR__.'/'.$dir)) {
        mkdir(__DIR__.'/'.$dir, 0770, true);
    }
}
if (! file_exists(__DIR__.'/.env')) {
    copy(__DIR__.'/.env.example', __DIR__.'/.env');
}
echo "Diretórios preparados. Configure backend/.env antes de migrar.\n";
