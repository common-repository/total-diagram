<?php
/**
 * Total Data DB v 0.4.0
 */

/* Direct access check */
defined('ABSPATH') or die('You do not have sufficient permissions to direct access this plugin.');

class TDG_DB_Services
{

    /**
     * Add or modify node in database
     */

    function setNode($id, $parent, $folder, $type, $x, $y, $z, $links, $meta, $silent)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'total_nodes';
        $exist = $wpdb->get_row("SELECT * FROM $table_name WHERE total_id='$id' AND folder='$folder'");
        // Update
        if ($exist !== null)
        {
            $result = $wpdb->update($table_name, array(
                'parent_id' => $parent,
                'folder' => $folder,
                'type' => $type,
                'x' => $x,
                'y' => $y,
                'z' => $z,
                'silent' => $silent,
                'links' => $links,
                'meta' => $meta
                ),
                array('total_id' => $id,
                      'folder' => $folder)
            );
            if ($result === false) return false;
            return true;
        }
        // Create
        else
        {
            $result = $wpdb->insert($table_name, array(
                'total_id' => $id,
                'parent_id' => $parent,
                'folder' => $folder,
                'type' => $type,
                'x' => $x,
                'y' => $y,
                'z' => $z,
                'silent' => $silent,
                'links' => $links,
                'meta' => $meta
            ));
            if ($result === false) return false;
            return true;
        }
        return false;
    }

    /**
     * Delete node from database
     */

    function delNode($id, $folder)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'total_nodes';
        $wpdb->delete($table_name, array('total_id' => $id, 'folder' => $folder));
    }

    /**
     * Clear all table
     */

    function delAll()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'total_nodes';
        $wpdb->query("DELETE FROM $table_name"); // Sqlite3
        // $wpdb->query("TRUNCATE TABLE $table_name");
    }

    /**
     * Get highest ID
     */

    function getLastID()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'total_nodes';
        $last_id = 0;
        $results = $wpdb->get_results("SELECT * FROM $table_name");
        foreach ($results as $node)
        {
            $total_id = intval(explode('.', $node->total_id)[1]);
            if ($total_id > $last_id) $last_id = $total_id;
        }
        return $last_id;
    }

}

?>