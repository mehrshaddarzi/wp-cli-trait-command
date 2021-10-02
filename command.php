<?php

# Check Exist WP-CLI
if (!class_exists('WP_CLI')) {
    return;
}

# Define Constant
define("WP_CLI_TRAIT_PATH", dirname(__FILE__));
define("WP_CLI_TRAIT_TEMPLATE_PATH", WP_CLI_TRAIT_PATH . '/templates/');

# Register 'trait' Command
WP_CLI::add_command('trait', WP_Trait_Command::class);
