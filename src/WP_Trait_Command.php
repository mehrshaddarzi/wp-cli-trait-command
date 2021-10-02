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
     *      $ wp trait start
     *      Success: Created plugin files.
     *
     */
    public function start($_, $assoc)
    {
        # Force Prompt
        $before_command = $this->getCommandLog();
        if (!isset ($assoc['prompt']) and count($assoc) < 2) {
            if (empty($before_command) || (isset($before_command['command']) and $before_command['command'] != "start")) {
                $this->saveLastCommand('start', $_, $assoc);
                \WP_CLI::runcommand("trait start --prompt");
                return;
            }
        }
        $this->removeCommandLog();

        # Sanitize Slug
        if (!isset($assoc['slug'])) {
            \WP_CLI::error("Invalid plugin slug specified. please try again.");
        }
        if (in_array($assoc['slug'], ['.', '..'], true)) {
            \WP_CLI::error("Invalid plugin slug specified. The slug cannot be '.' or '..'.");
        }
        $plugin_slug = $assoc['slug'];
        $plugin_name = ucwords(str_replace('-', ' ', $plugin_slug));
        $plugin_package = str_replace(' ', '_', $plugin_name);

        # Sanitize NameSpace
        if (!isset($assoc['namespace']) || (isset($assoc['namespace']) and empty($assoc['namespace']))) {
            $plugin_namespace = str_replace(' ', '_', $plugin_name);
        } else {
            $plugin_namespace = trim($assoc['namespace']);
        }
        $plugin_namespace = str_ireplace("-", "_", $plugin_namespace);
        $plugin_namespace = str_ireplace(["/", "\\", "."], "", $plugin_namespace);

        # Run Scaffold Command
        $defaults = [
            'plugin_name' => $plugin_name,
            'plugin_description' => 'plugin description',
            'plugin_author' => 'plugin author name',
            'plugin_author_uri' => 'your site here',
            'plugin_uri' => 'plugin site here',
            'activate-network' => 0,
            'activate' => 0,
            'force' => 0,
            'skip-tests' => 1
        ];
        $data = wp_parse_args($assoc, $defaults);
        $wp_plugin_dir = str_ireplace("\\", "/", WP_PLUGIN_DIR);
        $plugin_dir = $wp_plugin_dir . "/{$plugin_slug}";
        $plugin_main_file = $wp_plugin_dir . "/{$plugin_slug}/{$plugin_slug}.php";
        \WP_CLI::run_command(['scaffold', 'plugin', $plugin_slug],
            array_diff_key($data, array_flip(['namespace', 'slug'])));

        # Add ComposerJson File
        $mustache = \WP_CLI_FileSystem::load_mustache(WP_CLI_TRAIT_TEMPLATE_PATH);
        \WP_CLI_FileSystem::file_put_content(
            $plugin_dir . '/composer.json',
            $mustache->render('composer', [
                'namespace' => (!empty($plugin_namespace) ? $plugin_namespace . '\\\\' : '')
            ])
        );

        # Add To Plugin File
        \WP_CLI_FileSystem::search_replace_file(
            $plugin_main_file,
            array(
                "// Your code starts here."
            ),
            array(
                $mustache->render('plugin', [
                    'define_namespace' => "",
                    'plugin_class' => (!empty($plugin_namespace) ? $plugin_namespace : $plugin_package),
                    'plugin_slug' => trim($assoc['slug']),
                ])
            ));

        # Run Composer Update
        \WP_CLI_Helper::run_composer($plugin_dir, array('update'));

        # Show Success
        \WP_CLI::success("Created `" . \WP_CLI_Helper::color(trim($assoc['slug']), "Y") . "` plugin files.");
    }

    protected function getLogFilePath()
    {
        return \WP_CLI_Helper::get_cache_dir('trait') . '/log.json';
    }

    protected function removeCommandLog()
    {
        //Command log file name
        $file = $this->getLogFilePath();
        if (file_exists($file)) {
            \WP_CLI_FileSystem::remove_file($file);
        }
    }

    protected function saveLastCommand($command, $args, $assoc_args)
    {
        //Command log file name
        $file = $this->getLogFilePath();

        //Get now Command
        $now = array(
            'command' => $command,
            'args' => $args,
            'assoc_args' => $assoc_args
        );

        //Add new Command to Log
        \WP_CLI_FileSystem::create_json_file($file, $now);
    }

    protected function getCommandLog()
    {
        //Command log file name
        $file = $this->getLogFilePath();
        if (file_exists($file)) {
            # Check time age cache [ 2 minute ]
            if (time() - filemtime($file) >= 120) {
                $this->removeCommandLog();
            } else {
                # get json parse
                $json = \WP_CLI_FileSystem::read_json_file($file);
                if ($json != false) {
                    return $json;
                }
            }
        }

        return array();
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
