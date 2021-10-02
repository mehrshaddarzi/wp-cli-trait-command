<?php

/**
 * WP-CLI Trait Package Command.
 *
 * ## EXAMPLES
 *
 *      # Create new WordPress Plugin
 *      $ wp trait start
 *      Success: Created plugin files.
 *
 *      # Create Model
 *      $ wp trait make model Admin
 *      Success: Created Model.
 *
 *      # Create New PostType
 *      $ wp trait make post-type Forms
 *      Success: Created `Forms` PostType.
 *
 *      # Create New Taxonomy
 *      $ wp trait make taxonomy City
 *      Success: Created `City` Taxonomy.
 *
 */
class WP_Trait_Command extends WP_CLI_Command
{
    /**
     * Generates starter code for a plugin
     *
     * ## OPTIONS
     *
     * [--slug=<slug>]
     * : The internal name of the plugin.
     *
     * [--namespace=<namespace>]
     * : The namespace of php plugins.
     *
     * [--plugin_name=<title>]
     * : What to put in the 'Plugin Name:' header.
     *
     * [--plugin_description=<description>]
     * : What to put in the 'Description:' header.
     *
     * [--plugin_author=<author>]
     * : What to put in the 'Author:' header.
     *
     * [--plugin_author_uri=<url>]
     * : What to put in the 'Author URI:' header.
     *
     * [--plugin_uri=<url>]
     * : What to put in the 'Plugin URI:' header.
     *
     * [--skip-tests]
     * : Don't generate files for unit testing.
     *
     * [--ci=<provider>]
     * : Choose a configuration file for a continuous integration provider.
     * ---
     * default: travis
     * options:
     *   - travis
     *   - circle
     *   - gitlab
     * ---
     *
     * [--activate]
     * : Activate the newly generated plugin.
     *
     * [--activate-network]
     * : Network activate the newly generated plugin.
     *
     * [--force]
     * : Overwrite files that already exist.
     *
     * ## EXAMPLES
     *
     *      # Set new config
     *      $ wp global-config set path ~/wp-cli/site
     *      Success: Saved path config.
     *
     */
    public function start($_, $assoc)
    {
        WP_CLI::success("Saved " . WP_CLI_Helper::color("Salam", "Y") . " config.");
    }

    /**
     * Generate Model Files.
     *
     * ## OPTIONS
     *
     * <type>
     * : Type of file
     * ---
     * default: model
     * options:
     *   - model
     *   - post-type
     *   - taxonomy
     * ---
     *
     * <src>
     * : Source of file
     *
     * ## EXAMPLES
     *
     *      # Create Model
     *      $ wp trait make model Admin
     *      Success: Created Model.
     *
     *      # Create New PostType
     *      $ wp trait make post-type Forms
     *      Success: Created `Forms` PostType.
     *
     *      # Create New Taxonomy
     *      $ wp trait make taxonomy City
     *      Success: Created `City` Taxonomy.
     */
    public function make($_, $assoc)
    {
        //Check local or global config file
        $type = (isset($assoc['local']) ? 'local' : 'global');

        //Load WP-CLI-CONFIG class
        $wp_cli_config = new WP_CLI_CONFIG($type);

        //Load config File
        $current_config = $wp_cli_config->load_config_file();

        //sanitize value
        $key = $_[0];

        //check nested
        if (stristr($_[0], ":") != false) {
            $exp = explode(":", $_[0]);
            $exp = array_filter(
                $exp,
                function ($value) {
                    return $value !== '';
                }
            );

            $count = count($exp);
            if ($count == 2) {
                if (isset($current_config[$exp[0]][$exp[1]])) {
                    unset($current_config[$exp[0]][$exp[1]]);
                } else {
                    $error = true;
                }
            } elseif ($count == 3) {
                if (isset($current_config[$exp[0]][$exp[1]][$exp[2]])) {
                    unset($current_config[$exp[0]][$exp[1]][$exp[2]]);
                } else {
                    $error = true;
                }
            } elseif ($count == 4) {
                if (isset($current_config[$exp[0]][$exp[1]][$exp[2]][$exp[3]])) {
                    unset($current_config[$exp[0]][$exp[1]][$exp[2]][$exp[3]]);
                } else {
                    $error = true;
                }
            }

            if (isset($error)) {
                WP_CLI::error("The " . WP_CLI_Helper::color($key, "Y") . " parameter not found.");
            }
        } elseif (isset($current_config[$key])) {
            unset($current_config[$key]);
        } else {
            WP_CLI::error("The " . WP_CLI_Helper::color($key, "Y") . " parameter not found.");
        }

        if ($wp_cli_config->save_config_file($current_config)) {
            WP_CLI::success("Removed " . WP_CLI_Helper::color($_[0], "Y") . " config.");
        } else {
            WP_CLI::error("Failed to update the config yaml file.");
        }
    }

}
