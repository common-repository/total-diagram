<?php

/**
 * Total admin panel v 0.2.0
 * (c) 2018 Dariusz Dawidowski, All Rights Reserved.
 */

/* Direct access check */
defined('ABSPATH') or die('You do not have sufficient permissions to direct access this plugin.');

/**
 * Libs
 */

include_once('total-data-json.php');

/**
 * The admin-specific functionality of the plugin.
 */

class TDG_Admin_Settings
{

    // Data source
    private $totalJSON;

    /**
     * Constructor
     */

    public function __construct()
    {
        // Hooks/Actions
        add_action('admin_menu', array($this, 'menu'));
        add_action('admin_post_total_diagram_import', array($this, 'import'));
        add_action('admin_post_total_diagram_export', array($this, 'export'));
        // TotalJSON
        $this->totalJSON = new TDG_Json();
    }

    /**
     * Menu
     */

    function menu()
    {
        add_options_page('Total Diagram', 'Total Diagram', 'manage_options', 'total-diagram-settings', array($this, 'settings'));
    }

    /**
     * Settings panel
     */

    function settings()
    {
        if (!current_user_can('manage_options')) wp_die(__('You do not have sufficient permissions to access this page.'));
        ?>
        <div class="wrap">
            <h1>Total Diagram Settings</h1>
            <table class="form-table">
                <tr>
                    <th>BACKUP</th>
                </tr>
                <tr>
                    <th>Import data:</th>
                    <td>
                        <form method="post" action="admin-post.php" enctype="multipart/form-data">
                            <input type="hidden" name="action" value="total_diagram_import">
                            <?php wp_nonce_field('total_diagram_verify_import'); ?>
                            <input type="file" name="import-data"> <button>Upload File</button> <input type="checkbox" name="incremental">Incremental
                            <p class="description">Check incremental if you want to add new nodes instead of overwrite everything.</p>
                        </form>
                    </td>
                </tr>
                <tr>
                    <th>Export data:</th>
                    <td>
                        <form method="post" action="admin-post.php" enctype="multipart/form-data">
                            <input type="hidden" name="action" value="total_diagram_export">
                            <?php wp_nonce_field('total_diagram_verify_export'); ?>
                            <button>Generate &amp; Download File</button>
                            <p class="description">Important note: Files like images, documents are NOT exported.</p>
                        </form>
                    </td>
                </tr>
                <tr>
                    <th>SUPPORT</th>
                </tr>
                <tr>
                    <th>Project donations:</th>
                    <td><a href="https://www.patreon.com/bePatron?u=13163927" data-patreon-widget-type="become-patron-button">Become a Patron!</a><script async src="https://c6.patreon.com/becomePatronButton.bundle.js"></script></td>
                </tr>
            </table>
        </div>
        <?php
    }

    /**
     * Import backup data
     */

    function import()
    {
        if (!current_user_can('manage_options')) wp_die(__('You do not have sufficient permissions to access this page.'));
        // Check nonce field
        check_admin_referer('total_diagram_verify_import');
        // Gather options
        if (isset($_FILES['import-data']) and ($_FILES['import-data']['size'] > 0))
        {
            $file = fopen($_FILES['import-data']['tmp_name'], 'r') or die('Unable to open file!');
            $json_content = json_decode(fread($file, $_FILES['import-data']['size']));
            fclose($file);
            if (isset($_POST['incremental']))
            {
                $this->totalJSON->add($json_content);
            }
            else
            {
                $this->totalJSON->set($json_content);
            }
        }
        // Redirect back
        wp_redirect(admin_url('options-general.php?page=total-diagram-settings'));
        exit;
    }

    /**
     * Export backup data
     */

    function export()
    {
        if (!current_user_can('manage_options')) wp_die(__('You do not have sufficient permissions to access this page.'));
        // Check nonce field
        check_admin_referer('total_diagram_verify_export');
        // Header
        $datetime = date('Y-m-d-H\hi', time());
        header("Expires: 0");
        header("Cache-Control: no-cache, no-store, must-revalidate");
        header('Cache-Control: pre-check=0, post-check=0, max-age=0', false);
        header("Pragma: no-cache");
        header("Content-type: application/json");
        header("Content-Disposition:attachment; filename=total-diagram-backup-$datetime.json");
        header("Content-Type: application/force-download");
        // Echo contents
        echo $this->totalJSON->get();
        exit;
    }

}

?>