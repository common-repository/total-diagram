<?php  
/* 
Plugin Name: Total Diagram
Version: 1.4.0
Description: Total Diagram client + server plugin for WordPress. Shortcode: [total-diagram] or [total].
Author: Dariusz Dawidowski
Author URI: https://www.totaldiagram.com
Text Domain: total-diagram
*/

$total_diagram_version = '1.4.0';
$three_version = 'r100';

/* Direct access check */
defined('ABSPATH') or die('You do not have sufficient permissions to direct access this plugin.');

/* Version check */
global $wp_version;
  
if (version_compare($wp_version, '5.0', '<'))
{  
    exit("This plugin requires Wordpress 5.0 or newer. <a href='http://codex.wordpress.org/Upgrading_WordPress'>Click to update.</a>");
}

/**
 * Libs
 */

include_once('total-diagram-admin.php');
include_once('total-data-ajax.php');

/**
 * Plugin main class
 */

class TDG_Main_Plugin
{

    /**
     * Constructor
     */

    public function __construct()
    {
        // Hooks/Actions
        register_activation_hook(__FILE__, array($this, 'install'));
        add_action('upgrader_process_complete', array($this, 'update'), 10, 2);
        add_action('widgets_init', array($this, 'widgetsInit'));
        add_shortcode('total', array($this, 'shortcodeTotal'));
        add_shortcode('total-diagram', array($this, 'shortcodeTotal'));
        add_filter('plugin_action_links_' . plugin_basename( __FILE__ ), array($this, 'pluginSettingsLink'));
        // Ajax Proxy Services (only hooks/actions used here)
        $ajaxService = new TDG_Ajax_Services();
    }

    /**
     * Install plugin - create tables
     */

    function install()
    {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        /**
         * Table Nodes:
         * total_id: Unique total ID char(32)
         * parent_id: Unique database ID char(32)
         * folder: Position of node ('/' for root) text(65535)
         * x, y, z: Coords in world space (float -9999.9999 .. 9999.9999)
         * silent: silend mode enabled (1/0 true/false)
         * type: Plugin Class Name char(32)
         * links: Id's of linked nodes array encoded into json string text(65535)
         * meta: metadata encoded into json string text(65535)
         */
        $table_name = $wpdb->prefix . 'total_nodes';
        $sql_nodes = "CREATE TABLE IF NOT EXISTS $table_name (
            total_id VARCHAR(32) NOT NULL,
            parent_id VARCHAR(32),
            folder TEXT NOT NULL,
            x float(8,4) NOT NULL,
            y float(8,4) NOT NULL,
            z float(8,4) NOT NULL,
            silent BIT DEFAULT 0 NOT NULL,
            type VARCHAR(32) NOT NULL,
            links TEXT,
            meta TEXT
        ) $charset_collate;";
        dbDelta($sql_nodes);

        // Generate welcome tutorial
        $this->generateTutorial();
        
        // Migrate database
        $this->migrate();
    }

    /**
     * Run after update plugin to new version
     */

    function update($upgrader_object, $options)
    {
        $current_plugin_path_name = plugin_basename(__FILE__);
        if ($options['action'] == 'update' && $options['type'] == 'plugin')
        {
            foreach($options['plugins'] as $each_plugin)
            {
                if ($each_plugin == $current_plugin_path_name)
                {
                    // Migrate database
                    $this->migrate();
                }
            }
        }
    }

    function pluginSettingsLink($links)
    {
        $settings_link = '<a href="options-general.php?page=total-diagram-settings">' . __( 'Settings' ) . '</a>';
        array_push($links, $settings_link);
        return $links;
    }

    /**
     * Shortcode: [total folder="..."]
     */

    function shortcodeTotal($attr)
    {
        // Only for logged users
        if (!is_user_logged_in())
        {
            global $wp;
            $login_url = home_url() . '/wp-login.php?redirect_to=' . urlencode(home_url($wp->request)) . '&reauth=1';
            return "User is not logged in. You don't have access to this page. <a href='" . $login_url . "'>Login here.</a>";
        }
        // Optional folder
        $folder = isset($attr['folder']) ? sanitize_text_field($attr['folder']) : '/';
        $total = new TDG_Main_Render($folder);
        return $total->renderHtml();
    }

    /**
     * Widget
     */

    function widgetsInit()
    {
        register_widget('TDG_Main_Widget');
    }

    /**
     * Generate welcome tutorial
     */

    function generateTutorial()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'total_nodes';
        $results = $wpdb->get_results("SELECT * FROM $table_name");
        if (!count($results))
        {
            $wpdb->insert($table_name, array(
                'total_id' => 'node.1',
                'parent_id' => '',
                'folder' => '/',
                'x' => -5.4185,
                'y' => 15.6111,
                'z' => 0.0,
                'silent' => false,
                'type' => 'TotalNodeNote',
                'links' => '[]',
                'meta' => '{"text":"Hi!"}'
            ));
            $wpdb->insert($table_name, array(
                'total_id' => 'node.2',
                'parent_id' => '',
                'folder' => '/',
                'x' => -13.9919,
                'y' => 5.1790,
                'z' => 0.0,
                'silent' => false,
                'type' => 'TotalNodeNote',
                'links' => '[]',
                'meta' => '{"text":"Double-click\nto add note","color":"#b5ea3b"}'
            ));
            $wpdb->insert($table_name, array(
                'total_id' => 'node.3',
                'parent_id' => '',
                'folder' => '/',
                'x' => -14.3621,
                'y' => -6.0962,
                'z' => 0.0,
                'silent' => false,
                'type' => 'TotalNodeNote',
                'links' => '[]',
                'meta' => '{"text":"Right click\nto open menu","color":"#fca438"}'
            ));
            $wpdb->insert($table_name, array(
                'total_id' => 'node.4',
                'parent_id' => '',
                'folder' => '/',
                'x' => -5.5719,
                'y' => -5.6461,
                'z' => 0.0,
                'silent' => false,
                'type' => 'TotalNodeNote',
                'links' => '[]',
                'meta' => '{"text":"Scroll\nto pan view\n..or click and drag","color":"#05c9c0"}'
            ));
            $wpdb->insert($table_name, array(
                'total_id' => 'node.5',
                'parent_id' => '',
                'folder' => '/',
                'x' => 2.9631,
                'y' => -4.7641,
                'z' => 0.0,
                'silent' => false,
                'type' => 'TotalNodeNote',
                'links' => '[]',
                'meta' => '{"text":"Pinch\nto zoom view\n..or ctrl+scroll","color":"#b5ea3b"}'
            ));
            $wpdb->insert($table_name, array(
                'total_id' => 'node.6',
                'parent_id' => '',
                'folder' => '/',
                'x' => -5.3226,
                'y' => 5.3116,
                'z' => 0.0,
                'silent' => false,
                'type' => 'TotalNodeNote',
                'links' => '[]',
                'meta' => '{"text":"Then click on it\nto select and edit","color":"#05c9c0"}'
            ));
            $wpdb->insert($table_name, array(
                'total_id' => 'node.7',
                'parent_id' => '',
                'folder' => '/',
                'x' => 2.9876,
                'y' => 4.3222,
                'z' => 0.0,
                'silent' => false,
                'type' => 'TotalNodeNote',
                'links' => '[]',
                'meta' => '{"text":"Hold shift\nto multiple select","color":"#fca438"}'
            ));
        }

    }

    /**
     * Migrate plugin - update tables
     */

    function migrate()
    {
        global $wpdb;

        $total_db_version = '1.6';

        // Migrate 1.4 -> 1.5
        if (get_option('total_db_version') == '1.4')
        {
            $this->backupDB();
            $table_name = $wpdb->prefix . 'total_nodes';
            // Convert TotalNodeText -> TotalNodeLabel/TotalNodeNote/TotalNodePage
            $nodes = $wpdb->get_results("SELECT * FROM $table_name", OBJECT);
            foreach ($nodes as $node)
            {
                if ($node->type == 'TotalNodeText')
                {
                    $meta = json_decode($node->meta);
                    $newtype = 'TotalNodeNote';
                    $newmeta = json_encode(array('text' => $meta->text));
                    if (isset($meta->bg))
                    {
                        if ($meta->bg == 'Auto' or $meta->bg == 'Sticky') $newtype = 'TotalNodeNote';
                        else if ($meta->bg == 'Label') $newtype = 'TotalNodeLabel';
                        else if ($meta->bg == 'Notebook' or $meta->bg == 'Page') $newtype = 'TotalNodePage';
                    }
                    // Convert namespace to folder
                    $result = $wpdb->update($table_name, array(
                        'type' => $newtype,
                        'meta' => $newmeta,
                        ),
                        array('total_id' => $node->total_id,
                              'folder' => $node->folder)
                    );
                }
            }
        }

        // Migrate 1.5 -> 1.6
        if (get_option('total_db_version') == '1.5')
        {
            // Standard backup
            $this->backupDB();
            // Name
            $total_nodes = $wpdb->prefix . 'total_nodes';
            // Add new boolean field 'silent':
            $wpdb->query("ALTER TABLE $total_nodes ADD silent BIT DEFAULT 0 NOT NULL");
        }
        
        // Update to current version
        update_option('total_db_version', $total_db_version);
    }

    /**
     * Migrate plugin - backup tables
     */

    function backupDB()
    {
        global $wpdb;
        // $charset_collate = $wpdb->get_charset_collate();
        $tables = ['total_nodes', 'total_links'];
        $time = date('Y_m_d_h_i_s', time());
        foreach ($tables as $table) {
            $table_name = $wpdb->prefix . $table;
            $table_name_bq = $wpdb->prefix . $table . '_backup_' . $time;
            $wpdb->query("CREATE TABLE $table_name_bq AS SELECT * FROM $table_name"); // TODO: charset collate
        }
    }

    /**
     * Restore backup (at this moment used for manual copy-paste query)
     */

    function restoreDB()
    {
        /*
        UPDATE wp_total_options SET option_value='1.1' WHERE option_name='total_db_version';
        DROP TABLE wp_total_total_nodes;
        CREATE TABLE wp_total_total_nodes AS SELECT * FROM wp_total_total_nodes_backup_2018_07_01_03_39_18;
        */
    }

}

/**
 * Widget
 */

class TDG_Main_Widget extends WP_Widget
{
     
    function __construct()
    {
        parent::__construct(
            // slug
            'total-diagram-widget',
            // name
            __('Total Diagram', 'total-diagram'),
            // options
            array('description' => __('Use this widget or shortcode to insert Total Diagram into page.', 'total-diagram'))
        );
    }
     
    // Settings form
    function form($instance)
    {
    }
     
    // Saving form data
    function update($new_instance, $old_instance)
    {
    }
     
    // Rendering view
    function widget($args, $instance)
    {
        // Only for logged users
        if (!is_user_logged_in())
        {
            global $wp;
            $login_url = home_url() . '/wp-login.php?redirect_to=' . urlencode(home_url($wp->request)) . '&reauth=1';
            echo "User is not logged in. You don't have access to this page. <a href='" . $login_url . "'>Login here.</a>";
        }
        // Logged in
        else
        {
            // Render
            $total = new TDG_Main_Render();
            echo $total->renderHtml();
        }
    }
     
}

/**
 * Rendering code
 */

class TDG_Main_Render
{

    private $folder = '';

    public function __construct($folder = '/')
    {
        global $total_diagram_version, $three_version;
        $this->folder = $folder;
        // Render script in footer
        add_action('wp_footer' , array($this, 'script'), $priority = 21);

        /* Common resources */
        // Three
        wp_enqueue_script('three-main', plugin_dir_url(__FILE__) . 'three/three.min.js', $deps = array(), $ver = $three_version);
        wp_enqueue_script('three-obj-loader', plugin_dir_url(__FILE__) . 'three/OBJLoader.js', $deps = array('three-main'), $ver = $three_version);
        // Three Text Texture
        wp_enqueue_script('three-text-texture', plugin_dir_url(__FILE__) . 'three.texttexture/THREE.TextTexture.js', $deps = array('three-main'), $ver = '18.7.6');

        /* Development resources */
        if (!$this->production())
        {
            // Utils
            wp_enqueue_script('total-utils', plugin_dir_url(__FILE__) . 'total-utils.js', $deps = array(), $ver = $this->plugin_version('total-utils.js'));
            wp_enqueue_script('total-data-json', plugin_dir_url(__FILE__) . 'total-data-json.js', $deps = array(), $ver = $this->plugin_version('total-data-json.js'));
            // Graph
            wp_enqueue_script('total-node', plugin_dir_url(__FILE__) . 'total-node.js', $deps = array(), $ver = $this->plugin_version('total-node.js'));
            wp_enqueue_script('total-link', plugin_dir_url(__FILE__) . 'total-link.js', $deps = array(), $ver = $this->plugin_version('total-link.js'));
            wp_enqueue_script('total-graph', plugin_dir_url(__FILE__) . 'total-graph-three.js', $deps = array('three-main', 'total-utils', 'total-node', 'total-link'), $ver = $this->plugin_version('total-graph-three.js'));
            wp_enqueue_script('total-text', plugin_dir_url(__FILE__) . 'total-text-three.js', $deps = array('total-graph', 'three-text-texture', 'total-utils'), $ver = $this->plugin_version('total-text-three.js'));
            // Data
            wp_enqueue_script('total-data', plugin_dir_url(__FILE__) . 'total-data-ajax.js', $deps = array('total-utils'), $ver = $this->plugin_version('total-data-ajax.js'));
            // Editor
            wp_enqueue_style('total-editor-css', plugin_dir_url(__FILE__) . 'total-editor-browser.css', $deps = array(), $ver = $this->plugin_version('total-editor-browser.css'));
            wp_enqueue_script('total-menu', plugin_dir_url(__FILE__) . 'total-menu-browser.js', $deps = array(), $ver = $this->plugin_version('total-menu-browser.js'));
            wp_enqueue_script('total-overlay', plugin_dir_url(__FILE__) . 'total-overlay-browser.js', $deps = array(), $ver = $this->plugin_version('total-overlay-browser.js'));
            wp_enqueue_script('total-nodes', plugin_dir_url(__FILE__) . 'total-editor-nodes.js', $deps = array('total-graph'), $ver = $this->plugin_version('total-editor-nodes.js'));
            wp_enqueue_script('total-transfer', plugin_dir_url(__FILE__) . 'total-transfer.js', $deps = array(), $ver = $this->plugin_version('total-transfer.js'));
            wp_enqueue_script('total-editor', plugin_dir_url(__FILE__) . 'total-editor-browser.js', $deps = array('total-graph', 'total-text', 'total-data', 'total-menu', 'total-overlay', 'total-utils'), $ver = $this->plugin_version('total-editor-browser.js'));
            // Nodes plugins
            foreach ($this->getPlugins() as $plugin)
            {
                wp_enqueue_script('total-node-' . $plugin, plugin_dir_url(__FILE__) . 'nodes/' . $plugin . '/total-node-' . $plugin . '.js', $deps = array('total-graph', 'total-text'), $ver = $this->plugin_version('nodes/' . $plugin . '/total-node-' . $plugin . '.js'));
            }
        }

        /* Production resources */
        else
        {
            // Total Diagram css
            wp_enqueue_style('total-editor-css', plugin_dir_url(__FILE__) . 'total-diagram-' . $total_diagram_version . '.min.css', $deps = array());
            // Total Diagram minimized bundle
            wp_enqueue_script('total-diagram', plugin_dir_url(__FILE__) . 'total-diagram-' . $total_diagram_version . '.min.js', $deps = array('three-main', 'three-obj-loader', 'three-text-texture'));
        }
    }

    /**
     * List plugins
     */

    private function getPlugins()
    {
        if (!$this->production())
        {
            // Development version: automatically read all plugins
            return array_diff(scandir(plugin_dir_path(__FILE__) . 'nodes'), array('..', '.', '.DS_Store'));
        }
        else
        {
            if ($this->experimental())
            {
                // Production version: with experimental plugins
                return ['note', 'label', 'page', 'point', 'folder', 'group', 'image', 'file', 'indicator', 'clipart'];
            }
            else
            {
                // Production version: with official plugins
                return ['note', 'label', 'page', 'point', 'image', 'file', 'clipart'];
            }
        }
    }

    /**
     * Script
     */

    public function renderHtml()
    {
        global $total_diagram_version;
        // Render base
        ob_start();
        ?>
            <!-- Total Diagram -->
            <div id="total-diagram" class="total" data-prevent-cache="<?php echo uniqid(); ?>"></div>
            <script>
                var total = {
                    version: '<?php echo $total_diagram_version; ?>',
                    folder: '<?php echo $this->folder; ?>',
                    config: {
                        production: <?php echo ($this->production() ? 'true' : 'false'); ?>,
                        experimental: <?php echo ($this->experimental() ? 'true' : 'false'); ?>,
                        root: '', // #obsolete
                        endpoint: '<?php echo admin_url("admin-ajax.php"); ?>', // #obsolete
                        nodes: [],
                    },
                    url: {
                        root: '',
                        endpoint: '<?php echo admin_url("admin-ajax.php"); ?>',
                        upload: '<?php echo wp_upload_dir()["baseurl"]; ?>',
                    },
                    session: {
                        nonce: '<?php echo wp_create_nonce("total-ajax-nonce"); ?>',
                    },
                    data: null,
                    graph: null,
                    editor: null,
                    transfer: null,
                    utils: null
                };
            </script>
        <?php
        return ob_get_clean();
    }

    /**
     * Script
     */

    function script()
    {
        ?>
            <!-- Total Diagram -->
            <script>
                if ((typeof total !== 'undefined'))
                {
                    window.addEventListener('load', function() {
                        total.config.root = window.location.href.replaceLast(total.folder, '');
                        total.url.root = window.location.href.replaceLast(total.folder, '');
                        total.utils = new TotalUtils();
                        total.transfer = new TotalTransfer(document.getElementById('total-diagram'));
                        total.data = new TotalData(total.folder);
                        total.graph = new TotalGraph(document.getElementById('total-diagram'));
                        total.editor = new TotalEditor(document.getElementById('total-diagram'));
                        total.editor.lock();
                        total.editor.busy();
                        <?php foreach ($this->getPlugins() as $plugin): ?>
                            total.config.nodes.push({name: '<?php echo ucfirst($plugin); ?>', class: TotalNode<?php echo ucfirst($plugin); ?>, dir: '<?php echo plugin_dir_url(__FILE__) . ($this->production() ? '' : 'nodes/' . $plugin . '/'); ?>'});
                        <?php endforeach; ?>
                        // Register standard types of nodes
                        total.editor.nodes.register();
                        // Load starting folder nodes
                        total.editor.nodes.load('dummy').then(() =>
                        {
                            // Load external mesh shapes
                            total.graph.loadMeshes().then(() =>
                            {
                                // Recreate meshes
                                total.graph.assignMeshes();
                                // Update all nodes
                                total.graph.updateNodes();
                                // Start edit
                                total.editor.unlock();
                                total.editor.idle();
                            });
                        });
                        /* OLD
                        total.graph.loadMeshes().then(() =>
                        {
                            total.editor.nodes.load().then(() =>
                            {
                                total.editor.unlock();
                                total.editor.idle();
                            });
                        });
                        */
                    });
                }
            </script>
        <?php
    }

    /**
     * Utils
     */

    function production()
    {
        if (defined('TDG_DEVELOPMENT') && constant('TDG_DEVELOPMENT') == true) return false;
        return true;
    }

    function experimental()
    {
        if (defined('TDG_EXPERIMENTAL') && constant('TDG_EXPERIMENTAL') == true) return true;
        return false;
    }

    function plugin_version($filename)
    {
        if (!$this->production())
        {
            $filepath = plugin_dir_path(__FILE__) . $filename;
            if (is_readable($filepath)) return filemtime($filepath);
        }
        return false;
    }

}

// Main run
$total_diagram_plugin = new TDG_Main_Plugin();
$total_diagram_admin = new TDG_Admin_Settings();

?>
