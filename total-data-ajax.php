<?php
/**
 * Total Data Ajax v 0.17.1
 */

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

include_once('total-data-json.php');
include_once('total-data-db.php');

/**
 * Ajax proxy services
 */

class TDG_Ajax_Services
{

    // Data source
    private $totalJSON;

    /**
     * Constructor
     */

    public function __construct()
    {
        // Hooks/Actions
        add_action('wp_ajax_total_set', array($this, 'ajaxSet'));
        add_action('wp_ajax_total_del', array($this, 'ajaxDel'));
        add_action('wp_ajax_total_get_nodes', array($this, 'getNodes'));
        add_action('wp_ajax_total_get_media', array($this, 'getMedia'));
        add_action('wp_ajax_total_fetch', array($this, 'getHtml'));
        add_action('wp_ajax_total_get_file', array($this, 'getFile'));
        add_action('wp_ajax_total_set_file', array($this, 'setFile'));
        add_action('wp_ajax_total_upload_file', array($this, 'uploadFile'));
        add_action('wp_ajax_total_delete_file', array($this, 'deleteFile'));
        // TotalJSON
        $this->totalJSON = new TDG_Json();
        // TotalDB
        $this->db = new TDG_DB_Services();
    }

    /**
     * Set key:value
     */

    function ajaxSet()
    {
        check_ajax_referer('total-ajax-nonce', 'security');
        $key = isset($_POST['key']) ? substr(sanitize_text_field($_POST['key']), 0, 4) : '';
        $value = isset($_POST['value']) ? json_decode(str_replace('%2B', '+', str_replace('%26', '&', stripcslashes($_POST['value'])))) : '';
        $folder = isset($_POST['folder']) ? sanitize_text_field($_POST['folder']) : '/';
        if ($key == 'node' and $value)
        {
            $result = $this->db->setNode($value->id, $value->parent, $folder, $value->type, $value->x, $value->y, $value->z, json_encode($value->links), json_encode($value->meta), $value->silent);
            if ($result) echo 'status=OK';
            wp_die();
        }
        echo 'status=ERROR';
        wp_die();
    }

    /**
     * Del key
     */

    function ajaxDel()
    {
        check_ajax_referer('total-ajax-nonce', 'security');
        $id = isset($_POST['key']) ? sanitize_text_field($_POST['key']) : '';
        $key = isset($_POST['key']) ? substr(sanitize_text_field($_POST['key']), 0, 4) : '';
        $folder = isset($_POST['folder']) ? sanitize_text_field($_POST['folder']) : '/';
        if ($key == 'node')
        {
            $this->db->delNode($id, $folder);
            echo 'status=OK';
            wp_die();
        }
        echo 'status=ERROR';
        wp_die();
    }

    /**
     * Response with list of nodes
     */

    function getNodes()
    {
        check_ajax_referer('total-ajax-nonce', 'security');
        if (!is_user_logged_in()) wp_die('ERROR');
        $folder = isset($_POST['folder']) ? sanitize_text_field($_POST['folder']) : '/';
        echo $this->totalJSON->get($folder);
        wp_die();
    }

    /**
     * Response with list of gallery images
     * [{url: '..', dir: '..', file: '..', thumb: '..', mimetype: '..'}, ...]
     */

    function getMedia()
    {
        check_ajax_referer('total-ajax-nonce', 'security');
        if (!is_user_logged_in()) wp_die('ERROR:AUTH');
        $query_images_args = array(
            'post_type'      => 'attachment',
            'post_mime_type' => 'image',
            'post_status'    => 'inherit',
            'posts_per_page' => -1,
        );
        $query_images = new WP_Query($query_images_args);
        $images = array();
        foreach ($query_images->posts as $post)
        {
            $baseurl = wp_upload_dir()['baseurl'];
            $url_thumb = wp_get_attachment_image_url($post->ID, 'thumbnail');
            $url_full = wp_get_attachment_image_url($post->ID, 'full');
            $path_thumb = str_replace($baseurl, '', $url_thumb);
            $path_full = str_replace($baseurl, '', $url_full);
            $image = array(
                'url' => $url_thumb,
                'dir' => dirname($path_thumb) . '/',
                'file' => basename($path_full),
                'thumb' => basename($path_thumb)
            );
            $images[] = $image;
        }
        echo json_encode($images);
        wp_die();
    }

    /**
     * Response with data from third party site
     */

    function getHtml()
    {
        check_ajax_referer('total-ajax-nonce', 'security');
        if (!is_user_logged_in()) wp_die('ERROR:AUTH');
        $url = isset($_POST['url']) ? sanitize_text_field($_POST['url']) : '';
        if (!empty($url))
        {
            $doc = new DOMDocument('1.0', 'UTF-8');
            $loaded = $doc->loadHTMLFile($url);
            if ($loaded)
            {
                $doc->removeChild($doc->firstChild);            
                $body = $doc->getElementsByTagName('body'); 
                $doc->replaceChild($body->item(0), $doc->firstChild);
                wp_die($doc->saveHTML());
            }
        }
        wp_die('ERROR');
    }

    /**
     * Response with content of file
     * Only for Editor and Administrator
     */

    function getFile()
    {
        check_ajax_referer('total-ajax-nonce', 'security');
        if (!is_user_logged_in()) wp_die('ERROR:AUTH');
        if (!current_user_can('edit_plugins')) wp_die('ERROR:ROLE');
        $filename = isset($_POST['filename']) ? sanitize_text_field($_POST['filename']) : null;
        if ($filename)
        {
            $content = file_get_contents(plugin_dir_path(__FILE__) . $filename);
            echo $content;
        }
        exit(200);
    }

    /**
     * Send content to file on server's disk
     * Only for Editor and Administrator
     */

    function setFile()
    {
        check_ajax_referer('total-ajax-nonce', 'security');
        if (!is_user_logged_in()) wp_die('ERROR:AUTH');
        if (!current_user_can('edit_plugins')) wp_die('ERROR:ROLE');
        $filename = isset($_POST['filename']) ? sanitize_text_field($_POST['filename']) : null;
        $content = isset($_POST['content']) ? str_replace('%2B', '+', str_replace('%26', '&', stripcslashes($_POST['content']))) : null;
        if ($filename and $content)
        {
            $result = file_put_contents(plugin_dir_path(__FILE__) . $filename, $content);
            if ($result == false)
            {
                echo 'ERROR:FILE';
                exit(400);
            }
            echo 'OK';
        }
        exit(200);
    }

    /**
     * Upload file to server
     */

    function uploadFile()
    {
        check_ajax_referer('total-ajax-nonce', 'security');
        if (!function_exists('wp_handle_upload')) require_once(ABSPATH . 'wp-admin/includes/file.php');
        $file = $_FILES['file'];
        $result = wp_handle_upload($file, array('action' => 'total_upload_file'));
        if ($result && !isset($result['error']))
        {
            $basename = str_replace(wp_upload_dir()['path'] . '/', '', $result['file']);
            $dir = str_replace($basename, '', str_replace(wp_upload_dir()['basedir'], '', $result['file']));
            $params = array(
                'dir' => $dir,
                'file' => sanitize_file_name($basename),
                'mimetype' => $result['type'],
                'thumb' => null
            );
            // If it's an image then add it to WP media library
            if (substr($params['mimetype'], 0, 6) == 'image/')
            {
                $attachment = array(
                    'post_mime_type' => $params['mimetype'],
                    'post_title' => pathinfo($params['file'])['filename'],
                    'post_content' => '',
                    'post_status' => 'inherit'
                );
                $attach_id = wp_insert_attachment($attachment, substr($params['dir'], 1) . $params['file']);
                require_once(ABSPATH . 'wp-admin/includes/image.php');
                $attach_data = wp_generate_attachment_metadata($attach_id, wp_upload_dir()['basedir'] . $params['dir'] . $params['file']);
                wp_update_attachment_metadata($attach_id, $attach_data);
                // Add thumbnail info
                if (array_key_exists('thumbnail', $attach_data['sizes']))
                {
                    $params['thumb'] = $attach_data['sizes']['thumbnail']['file'];
                }
                else
                {
                    // No tumbnail generated (too small image)
                    $params['thumb'] = $params['file'];
                }

            }
            // Response Success
            echo json_encode($params);
            exit(200);
        }
        else
        {
            // Response Error
            echo 'ERROR:Bad request:' . $result['error'];
            exit(400);
        }
    }

    /**
     * Delete file on server
     */

    function deleteFile()
    {
        check_ajax_referer('total-ajax-nonce', 'security');
        $dir = isset($_POST['dir']) ? $_POST['dir'] : null;
        $file = isset($_POST['file']) ? $_POST['file'] : null;
        $path = wp_upload_dir()['basedir'] . $dir . $file;
        if (file_exists($path))
        {
            $attachment = $this->getAttachment($file);
            // Remove from media library with rest of thumbnails
            if ($attachment)
            {
                wp_delete_attachment($attachment->ID);
            }
            else
            // Single file
            {
                unlink($path);
            }
            exit(200);
        }
        else
        {
            echo 'ERROR:File does not exist:' . $dir . $file;
            exit(404);
        }
    }

    function getAttachment($post_name)
    {
        $thumbnail_size = '-' . get_option('thumbnail_size_w') . 'x' . get_option('thumbnail_size_h');
        $name = str_replace($thumbnail_size, '', pathinfo($post_name)['filename']);
        $args = array(
            'posts_per_page' => 1,
            'post_type' => 'attachment',
            'name' => $name,
        );
        $attachment = new WP_Query($args);
        if (!$attachment || !isset($attachment->posts, $attachment->posts[0]))
        {
            return false;
        }
        return $attachment->posts[0];
    }

}

?>
