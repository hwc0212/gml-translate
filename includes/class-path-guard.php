<?php
/**
 * Path Guard class - Ensures Hostinger compatibility
 *
 * @package Gemini_Dynamic_Translate
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Path Guard class
 */
class Gemini_Path_Guard {
    
    /**
     * Single instance
     */
    private static $instance = null;
    
    /**
     * Base paths
     */
    private $base_paths = [];
    
    /**
     * Get instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->init_base_paths();
    }
    
    /**
     * Initialize base paths
     */
    private function init_base_paths() {
        $upload_dir = wp_upload_dir();
        
        $this->base_paths = [
            'plugin'  => GEMINI_TRANSLATE_PLUGIN_DIR,
            'content' => WP_CONTENT_DIR,
            'root'    => ABSPATH,
            'upload'  => $upload_dir['basedir'],
            'cache'   => $this->get_cache_dir(),
        ];
    }
    
    /**
     * Get cache directory (Hostinger safe)
     */
    private function get_cache_dir() {
        $upload_dir = wp_upload_dir();
        $cache_dir = $upload_dir['basedir'] . '/gemini-translate-cache';
        
        // Create directory if not exists
        if (!file_exists($cache_dir)) {
            $this->create_directory($cache_dir);
        }
        
        return $cache_dir;
    }
    
    /**
     * Create directory safely (Hostinger compatible)
     */
    private function create_directory($path) {
        global $wp_filesystem;
        
        // Initialize WP_Filesystem
        if (empty($wp_filesystem)) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
            WP_Filesystem();
        }
        
        // Create directory
        if (!$wp_filesystem->exists($path)) {
            $wp_filesystem->mkdir($path, FS_CHMOD_DIR);
            
            // Create .htaccess for security
            $htaccess = $path . '/.htaccess';
            $wp_filesystem->put_contents($htaccess, 'Deny from all', FS_CHMOD_FILE);
            
            // Create index.php for security
            $index = $path . '/index.php';
            $wp_filesystem->put_contents($index, '<?php // Silence is golden', FS_CHMOD_FILE);
        }
    }
    
    /**
     * Validate path safety
     *
     * @param string $path Path to validate
     * @return bool
     * @throws Exception If path is unsafe
     */
    public function validate_path($path) {
        // Prohibit hardcoded absolute paths
        if (strpos($path, '/home/') === 0) {
            throw new Exception('Hardcoded absolute paths are not allowed');
        }
        
        // Must be within allowed base paths
        $real_path = realpath($path);
        
        if ($real_path === false) {
            // Path doesn't exist yet, check parent
            $real_path = realpath(dirname($path));
        }
        
        foreach ($this->base_paths as $base) {
            $real_base = realpath($base);
            if ($real_base && strpos($real_path, $real_base) === 0) {
                return true;
            }
        }
        
        throw new Exception('Path is outside allowed directories');
    }
    
    /**
     * Get safe path
     *
     * @param string $type Path type (plugin, content, root, upload, cache)
     * @param string $relative Relative path (optional)
     * @return string
     * @throws Exception If type is unknown
     */
    public function get_path($type, $relative = '') {
        if (!isset($this->base_paths[$type])) {
            throw new Exception("Unknown path type: $type");
        }
        
        $base = $this->base_paths[$type];
        
        if ($relative) {
            return trailingslashit($base) . ltrim($relative, '/');
        }
        
        return $base;
    }
    
    /**
     * Get cache directory
     *
     * @return string
     */
    public function get_cache_directory() {
        return $this->base_paths['cache'];
    }
    
    /**
     * Ensure cache directory exists
     *
     * @return bool
     */
    public function ensure_cache_directory() {
        try {
            $cache_dir = $this->get_cache_directory();
            
            if (!file_exists($cache_dir)) {
                $this->create_directory($cache_dir);
            }
            
            // Check if writable
            if (!is_writable($cache_dir)) {
                throw new Exception('Cache directory is not writable');
            }
            
            return true;
            
        } catch (Exception $e) {
            error_log('Gemini Translate - Cache directory error: ' . $e->getMessage());
            
            // Show admin notice
            add_action('admin_notices', function() use ($e) {
                echo '<div class="notice notice-error"><p>';
                echo '<strong>' . esc_html__('Gemini Translate:', 'gemini-translate') . '</strong> ';
                echo esc_html($e->getMessage());
                echo '</p></div>';
            });
            
            return false;
        }
    }
}
