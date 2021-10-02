<?php

# Check Exist WP-CLI
if (!class_exists('WP_CLI')) {
    return;
}

# Register 'trait' Command
WP_CLI::add_command('trait', WP_Trait_Command::class);
