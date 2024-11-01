<?php

/**
 * TotalJSON v 0.8.0 for PHP
 * (c) 2018 Dariusz Dawidowski, All Rights Reserved.
 */

/* Direct access check */
defined('ABSPATH') or die('You do not have sufficient permissions to direct access this plugin.');

/*
 *
 * {
 *   "format": "Total JSON",
 *   "version": 3,
 *   "nodes": [
 *     {"id": ..., "parent": ..., "folder": ..., "type": ..., "x": ..., "y": ..., "z": ..., "links": {...}, "meta": {...}, "silent": "true|false"},
 *     ...
 *   ]
 * }
 *
 */

/**
 * Libs
 */

include_once('total-data-db.php');

/**
 * Json data exchange format
 */

class TDG_Json
{

    private $db;

    /**
     * Constructor
     */

    public function __construct()
    {
        // Database Access
        $this->db = new TDG_DB_Services();
    }

    /**
     * Save nodes from json to database (overwrite)
     */

    public function set($json)
    {
        $this->db->delAll();
        if ($json->format == 'Total JSON' and $json->version == '3')
        {
            foreach ($json->nodes as $node)
            {
                $result = $this->db->setNode($node->id, $node->parent, $node->folder, $node->type, $node->x, $node->y, $node->z, json_encode($node->links), json_encode($node->meta), $node->silent);
            }
        }
    }

    /**
     * Save nodes from json to database (incremental)
     */

    public function add($json)
    {
        if ($json->format == 'Total JSON' and $json->version == '3')
        {
            $new_id_int = $this->db->getLastID() + 1;
            foreach ($json->nodes as $node)
            {
                for ($i = 0; $i < count($node->links); $i ++)
                { 
                    $node->links[$i] = $this->incID($node->links[$i], $new_id_int);
                }
                $result = $this->db->setNode($this->incID($node->id, $new_id_int), $this->incID($node->parent, $new_id_int), $node->folder, $node->type, $node->x, $node->y, 0.0, json_encode($node->links), json_encode($node->meta), $node->silent);
            }
        }
    }

    /**
     * Modify id string ('node.1' -> 'node.2')
     */

    private function incID($id, $value)
    {
        if (empty($id)) return '';
        $int_id = intval(explode('.', $id)[1]);
        return 'node.' . ($int_id + $value);
    }

    /**
     * Get list of nodes from database to json
     */

    public function get($folder = null)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'total_nodes';
        $results = null;
        if ($folder)
        {
            $results = $wpdb->get_results("SELECT * FROM $table_name WHERE folder='$folder'", OBJECT);
        }
        else
        {
            $results = $wpdb->get_results("SELECT * FROM $table_name", OBJECT);
        }
        $buffer = '{';
        $buffer .= '"format": "Total JSON",';
        $buffer .= '"version": 3,';
        $buffer .= '"nodes": [';
        if ($results)
        {
            foreach ($results as $node)
            {
                $buffer .= '{"id": "' . $node->total_id . '", "parent": "' . $node->parent_id . '", "folder": "' . $node->folder . '", "x": ' . $node->x . ', "y": ' . $node->y . ', "z": ' . $node->z . ', "type": "' . $node->type . '", "links": ' . $node->links . ', "meta": ' . $node->meta . ', "silent": ' . ($node->silent && $node->silent == "1" ? 'true' : 'false') . '}';
                if ($node !== end($results)) $buffer .= ',';
            }
        }
        $buffer .= ']';
        $buffer .= '}';
        return $buffer;
    }

}

?>