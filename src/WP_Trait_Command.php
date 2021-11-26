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
     * [<slug>]
     * : Post-type or Taxonomy Slug
     *
     * [<name>]
     * : Post-type or Taxonomy Singular Name
     *
     * [--var=<var>]
     * : Variable of Class
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
        # Check Trait Package
        $Trait = $this->checkTraitPackage();
        $autoload = $Trait['autoload'];
        $plugin_main_file = $Trait['plugin_main_file'];
        $plugin_dir = $Trait['plugin_dir'];

        # Get NameSpace
        $namespace = rtrim(key($autoload['psr-4']), "\\");
        $folder = reset($autoload['psr-4']);
        $srcDir = \WP_CLI_Util::getcwd($folder);

        # Check Class
        $class = preg_replace('#^/#', '', str_ireplace("\\", "/", trim($_[1], "/")));
        $explode_class = array_filter(explode("/", $class));

        # Check Variable
        if (isset($assoc['var']) and !empty($assoc['var'])) {
            $variable = trim($assoc['var']);
        } else {
            $variable = end($explode_class);
        }

        # Generate NameSpace
        $namespace_text = 'namespace ';
        if (count($explode_class) < 2) {
            $namespace_text .= $namespace . ';';
        } else {
            # Generate Namespace
            $_list = $explode_class;
            array_pop($_list);
            $namespace_text .= $namespace;
            foreach ($_list as $folder_name) {
                $namespace_text .= '\\' . $folder_name;
            }

            $namespace_text .= ';';
        }

        # Check Directory Of Class
        $mkdir = $srcDir;
        if (count($explode_class) > 1) {

            # Generate Folder
            $_list = $explode_class;
            array_pop($_list);
            foreach ($_list as $folder_name) {
                $mkdir = rtrim($mkdir, "/") . '/' . $folder_name . '/';
            }
        }

        # File Name
        $php_file = end($explode_class) . '.php';
        $php_full_path = rtrim($mkdir, "/") . '/' . $php_file;
        if (file_exists($php_full_path)) {
            \WP_CLI::error("The php file is exists. `" . \WP_CLI_Helper::color($php_full_path, "Y") . "`");
        }

        # Post-Type or Taxonomy Slug
        $slug = strtolower(end($explode_class));
        $singular_name = ucfirst(end($explode_class));
        if (in_array($_[0], ['post-type', 'taxonomy'])) {
            if (isset($_[2]) and !empty($_[2])) {
                $slug = trim($_[2]);
            }

            if (isset($_[3]) and !empty($_[3])) {
                $singular_name = trim($_[3]);
            }
        }

        # Load Mustache
        $mustache = \WP_CLI_FileSystem::load_mustache(WP_CLI_TRAIT_TEMPLATE_PATH);

        # Create Model File
        $text = $mustache->render($_[0], [
            'namespace' => $namespace_text,
            'class' => end($explode_class),
        ]);

        # Created File
        $create_php = \WP_CLI_FileSystem::file_put_content($mkdir . '/' . $php_file, $text);
        if (isset($create_php['status']) and $create_php['status'] === false) {
            \WP_CLI::error($create_php['message']);
        }

        # Add Variable to Main Class
        $addVariable = $this->addVariableToMainClass(
            strtolower($_[0]),
            $plugin_main_file,
            $namespace,
            $this->sanitizeVariableName($variable),
            $explode_class,
            $slug,
            $singular_name,
            $plugin_dir
        );
        if ($addVariable === false) {
            @unlink($mkdir . '/' . $php_file);
            \WP_CLI::error("Error adding variable in main plugin file.");
        }

        # Return Success
        \WP_CLI::success("Created `" . \WP_CLI_Helper::color(end($explode_class), "Y") . "` " . $_[0] . ".");
    }

    protected function checkTraitPackage()
    {

        # Check Json File in Path
        if (file_exists(\WP_CLI_Util::getcwd('wp-config.php'))) {
            \WP_CLI::error("You seem to be running the command in the root of WordPress.\nplease change the directory of your terminal to your Plugin directory.");
        }

        # Composer.Json File
        $composer = \WP_CLI_Util::getcwd('composer.json');
        if (!file_exists($composer)) {
            \WP_CLI::error("composer.json file not found");
        }

        # Read Json File
        $json = \WP_CLI_FileSystem::read_json_file($composer);
        if ($json === false) {
            \WP_CLI::error("composer.json file syntax is wrong.");
        }

        # Check Plugin Main File
        $current_dir = \WP_CLI_Util::getcwd();
        $plugin_dir = basename($current_dir);
        $plugin_main_file = $current_dir . '/' . $plugin_dir . '.php';
        if (!file_exists($plugin_main_file)) {
            \WP_CLI::error("the plugin main file is not found. `" .
                \WP_CLI_Helper::color($plugin_dir . '/' . $plugin_dir . '.php', "Y") . "`");
        }

        # Sanitize To str to lower
        $json = array_change_key_case($json, CASE_LOWER);

        # Check Not Found Trait Package
        if (!isset($json["require"]["mehrshaddarzi/wp-trait"])) {
            \WP_CLI::error("wp-trait package is not installed in your composer.json file.");
        }

        # Check Autoload
        if (!isset($json['autoload'])) {
            \WP_CLI::error("autoload is not found in your composer.json file.");
        }

        # Check PSR-4
        $autoload = array_change_key_case($json['autoload'], CASE_LOWER);
        if (!isset($autoload['psr-4'])) {
            \WP_CLI::error("psr-4 autoload is not found in your composer.json file.");
        }

        return [
            'json' => $json,
            'autoload' => $autoload,
            'current_dir' => $current_dir,
            'plugin_dir' => $plugin_dir,
            'plugin_main_file' => $plugin_main_file
        ];
    }

    protected function addVariableToMainClass($type = 'model', $php_file = '', $namespace = '', $variable = '', $array = [], $slug = '', $singular_name = '', $plugin_slug = '')
    {

        # namespace
        $namespace = '\\' . $namespace;
        foreach ($array as $class) {
            $namespace .= '\\' . $class;
        }

        # Generate Object Line
        $class_line = '        $this->' . $variable . ' = new ' . $namespace . '($this->plugin);' . "\n";
        switch ($type) {
            case "post-type":
                $class_line = '        $this->' . $variable . ' = new ' . $namespace . '("' . $slug . '", __("' . $singular_name . '", "' . $plugin_slug . '"), $args = [], $this->plugin);' . "\n";
                break;
            case "taxonomy":
                $class_line = '        $this->' . $variable . ' = new ' . $namespace . '("' . $slug . '", __("' . $singular_name . '", "' . $plugin_slug . '"), $post_types = ["post"], $args = [], $this->plugin);' . "\n";
                break;
        }

        # Generate Property
        $property = "    public $" . $variable . ";\n";

        # Get Main File
        $file = file($php_file);
        $instantiate = null;
        $public_variable = null;
        foreach ($file as $line => $value) {

            # Get For Add Object Class
            if (stristr($value, "function instantiate()") != false) {
                $instantiate = $line;
            }

            # Get For Add public Variable
            if (stristr($value, "public function __construct") != false) {
                $public_variable = $line - 1;
            }
        }

        # Get Last Line Of Method
        $_line = null;
        foreach ($file as $line => $value) {
            if ($line > $instantiate and trim($value) == "}") {
                $_line = $line;
                break;
            }
        }

        # Added Items
        $_content = array();
        foreach ($file as $line => $value) {
            # Add Property
            if ($line == $public_variable) {
                $_content[] = $property;
            }

            # Add Class Object
            if ($line == $_line) {
                $_content[] = $class_line;
            }

            $_content[] = $value;
        }

        # Replace Content
        $_file_content = '';
        foreach ($_content as $text) {
            $_file_content .= $text;
        }

        # Save File
        $save = \WP_CLI_FileSystem::file_put_content(
            $php_file,
            $_file_content
        );
        if (isset($save['status']) and $save['status'] === false) {
            return false;
        }

        return $save;
    }

    protected function sanitizeVariableName($variable)
    {
        $forbidden = [
            'db',
            'wp',
            'plugin',
            'pagenow',
            'post',
            'term',
            'attachment',
            'user',
            'option',
            'request',
            'comment',
            'nonce',
            'transient',
            'cache',
            'event',
            'error',
            'rest',
            'log',
            'route',
            'filter',
            'action',
            'cookie',
            'response',
            'file'
        ];
        if (in_array($variable, $forbidden)) {
            $variable = ucfirst($variable);
        }

        return str_ireplace([" ", "-"], "_", $variable);
    }

    /**
     * Add Custom Package.
     *
     * ## OPTIONS
     *
     * <name>
     * : Name of package
     *
     * ## EXAMPLES
     *
     *      # Add ide-helper Class e.g. PHPStorm
     *      $ wp trait add ide-helper
     *      Success: Added ide-helper.
     *
     *      # add CMB2 to Plugin
     *      $ wp trait add cmb2
     *      Success: Added CMB2.
     */
    public function add($_, $assoc)
    {
        # Check Trait Package
        $Trait = $this->checkTraitPackage();

        # Package List
        $_package = $this->packageList();

        # Check Package
        $package = strtolower($_[0]);
        if (!isset($_package[$package])) {
            \WP_CLI::error("The package name is not valid");
        }

        # Create ide-helper
        switch ($package) {
            case "ide-helper":
                $this->add_ide_helper($Trait);
                break;
            case "cmb2":
                $this->add_cmb2($Trait);
                break;
        }

        # Return Success
        \WP_CLI::success("Added " . \WP_CLI_Helper::color($_package[$package]['title'], "Y") . ".");
    }

    /**
     * Remove Custom Package.
     *
     * ## OPTIONS
     *
     * <name>
     * : Name of package
     *
     * ## EXAMPLES
     *
     *      # Remove ide-helper Class e.g. PHPStorm
     *      $ wp trait remove ide-helper
     *      Success: Removed ide-helper.
     *
     *      # Remove CMB2 to Plugin
     *      $ wp trait remove cmb2
     *      Success: Removed CMB2.
     */
    public function remove($_, $assoc)
    {
        # Check Trait Package
        $Trait = $this->checkTraitPackage();

        # Package List
        $_package = $this->packageList();

        # Check Package
        $package = strtolower($_[0]);
        if (!isset($_package[$package])) {
            \WP_CLI::error("The package name is not valid");
        }

        # Create ide-helper
        switch ($package) {
            case "ide-helper":
                $this->remove_ide_helper($Trait);
                break;
            case "cmb2":
                $this->remove_cmb2($Trait);
                break;
        }

        # Return Success
        \WP_CLI::success("Removed " . \WP_CLI_Helper::color($_package[$package]['title'], "Y") . ".");
    }

    protected function packageList()
    {
        return
            [
                'ide-helper' => ['title' => 'IDE-Helper'],
                'cmb2' => ['title' => 'CMB2']
            ];
    }

    protected function add_ide_helper($Trait)
    {
        # Remove If ide-helper is exist
        $ide_helper_file = $Trait['current_dir'] . '/ide-helper.php';
        if (file_exists($ide_helper_file)) {
            @unlink($ide_helper_file);
        }

        # Plugin Slug
        $slug = str_ireplace("-", "_", trim(basename($Trait['current_dir'])));
        $php_file = $Trait['current_dir'] . '/' . trim(basename($Trait['current_dir'])) . '.php';

        # Generate again File
        $mustache = \WP_CLI_FileSystem::load_mustache(WP_CLI_TRAIT_TEMPLATE_PATH);
        $text = $mustache->render('ide-helper', [
            'plugin_slug' => $slug
        ]);
        $create_php = \WP_CLI_FileSystem::file_put_content($ide_helper_file, $text);
        if (isset($create_php['status']) and $create_php['status'] === false) {
            \WP_CLI::error($create_php['message']);
        }

        # Create Property
        $file = file($php_file);
        $_file_content = '';
        $_line = false;
        foreach ($file as $line => $value) {
            if (substr(trim($value), 0, 3) == "new") {
                $_file_content .= '$' . $slug . ' = ' . $value;
                $_line = true;
            } else {
                $_file_content .= $value;
            }
        }
        if ($_line === true) {
            $save = \WP_CLI_FileSystem::file_put_content(
                $php_file,
                $_file_content
            );
            if (isset($save['status']) and $save['status'] === false) {
                \WP_CLI::error($save['message']);
            }
        }

        return true;
    }

    protected function remove_ide_helper($Trait)
    {
        # Remove If ide-helper is exist
        $ide_helper_file = $Trait['current_dir'] . '/ide-helper.php';
        if (file_exists($ide_helper_file)) {
            @unlink($ide_helper_file);
        }

        # Plugin Slug
        $slug = str_ireplace("-", "_", trim(basename($Trait['current_dir'])));
        $php_file = $Trait['current_dir'] . '/' . trim(basename($Trait['current_dir'])) . '.php';

        # Create Property
        $file = file($php_file);
        $_file_content = '';
        $_line = false;
        foreach ($file as $line => $value) {
            $prefix = '$' . $slug . ' = ';
            if (stristr($value, $prefix) != false) {
                $explode = explode("=", $value);
                $_file_content .= trim($explode[1]);
                $_line = true;
            } else {
                $_file_content .= $value;
            }
        }
        if ($_line === true) {
            $save = \WP_CLI_FileSystem::file_put_content(
                $php_file,
                $_file_content
            );
            if (isset($save['status']) and $save['status'] === false) {
                \WP_CLI::error($save['message']);
            }
        }

        return true;
    }

    protected function add_cmb2($Trait)
    {
        $composer = $Trait['json'];

        # Add require
        # @see https://github.com/CMB2/CMB2/wiki/Installation#if-including-the-library-via-composer-in-psr-4-format-example-antonella-framework
        if (isset($composer['require']['cmb2/cmb2'])) {
            \WP_CLI::error('The CMB2 is currently installed.');
        }
        $composer['require']['cmb2/cmb2'] = 'dev-master';

        # Add Files
        $composer['autoload']['files'][] = 'vendor/cmb2/cmb2/init.php';

        # Save Composer File
        $saved = \WP_CLI_FileSystem::create_json_file($Trait['current_dir'] . '/composer.json', $composer, $JSON_PRETTY = true);
        if ($saved === false) {
            \WP_CLI::error('composer.json file not saved');
        }

        # Run composer Update
        \WP_CLI_Helper::run_composer($Trait['current_dir'], array('update'));
    }

    protected function remove_cmb2($Trait)
    {
        $composer = $Trait['json'];

        # Remove require
        if (!isset($composer['require']['cmb2/cmb2'])) {
            \WP_CLI::error('The CMB2 is not currently installed.');
        }
        unset($composer['require']['cmb2/cmb2']);

        # Remove Files
        if (isset($composer['autoload']['files'])) {
            if (($key = array_search('vendor/cmb2/cmb2/init.php', $composer['autoload']['files'])) !== false) {
                unset($composer['autoload']['files'][$key]);
            }
        }

        # Check Empty Files
        if (empty($composer['autoload']['files'])) {
            unset($composer['autoload']['files']);
        }

        # Save Composer File
        $saved = \WP_CLI_FileSystem::create_json_file($Trait['current_dir'] . '/composer.json', $composer, $JSON_PRETTY = true);
        if ($saved === false) {
            \WP_CLI::error('composer.json file not saved');
        }

        # Run composer Update
        \WP_CLI_Helper::run_composer($Trait['current_dir'], array('update'));
    }
}
