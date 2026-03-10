<?php
/**
 * Plugin Name: GML - Gemini Dynamic Translate
 * Plugin URI: https://huwencai.com/gml-translate
 * Description: AI-powered dynamic translation using Google Gemini API with Weglot-style architecture and native i18n hybrid mode
 * Version: 2.8.2
 * Author: huwencai.com
 * Author URI: https://huwencai.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: gml-translate
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 7.4
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('GML_VERSION', '2.8.2');
define('GML_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('GML_PLUGIN_URL', plugin_dir_url(__FILE__));
define('GML_PLUGIN_FILE', __FILE__);

/**
 * Main GML plugin class
 */
class GML_Translate {
    
    /**
     * Single instance
     */
    private static $instance = null;
    
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
        $this->load_dependencies();
        $this->init_hooks();
    }
    
    /**
     * Load dependencies
     */
    private function load_dependencies() {
        // Autoloader
        require_once GML_PLUGIN_DIR . 'includes/class-autoloader.php';
        GML_Autoloader::register();
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Activation/Deactivation
        register_activation_hook(__FILE__, [$this, 'activate']);
        register_deactivation_hook(__FILE__, [$this, 'deactivate']);
        
        // Load text domain
        add_action('plugins_loaded', [$this, 'load_textdomain']);
        
        // Initialize components
        add_action('plugins_loaded', [$this, 'init_components'], 20);
    }
    
    /**
     * Plugin activation
     */
    public function activate() {
        // Create database tables
        require_once GML_PLUGIN_DIR . 'includes/class-installer.php';
        GML_Installer::activate();
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        GML_Installer::deactivate();
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Load text domain
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            'gml-translate',
            false,
            dirname(plugin_basename(__FILE__)) . '/languages'
        );
    }
    
    /**
     * Initialize components
     */
    public function init_components() {
        // Auto-upgrade DB schema if version changed (e.g. after plugin file update)
        $this->maybe_upgrade_db();

        // Cron context — only the queue processor is needed.
        // Skip all frontend components (Output Buffer, SEO Router, SEO Hreflang,
        // Language Switcher) to avoid unnecessary work and reduce the surface area
        // that triggers third-party plugin hooks (e.g. Elementor Pro Notes module).
        if ( defined( 'DOING_CRON' ) && DOING_CRON ) {
            if ( $this->is_configured() ) {
                new GML_Queue_Processor();
                new GML_Content_Crawler();
            }
            return;
        }

        // Always initialize admin (if in admin)
        if (is_admin()) {
            new GML_Admin_Settings();
            new GML_Translation_Editor();
        }
        
        // Only initialize frontend components if plugin is configured
        if (!$this->is_configured()) {
            // Show admin notice
            if (is_admin()) {
                add_action('admin_notices', [$this, 'admin_notice_configure']);
            }
            return;
        }
        
        // Initialize output buffer (hybrid interceptor)
        new GML_Output_Buffer();
        
        // Initialize gettext filter (runtime i18n string translation)
        // Must be initialized BEFORE template loading so header/footer/sidebar
        // strings are translated at PHP output time, not just in the output buffer.
        new GML_Gettext_Filter();
        
        // Initialize SEO router
        new GML_SEO_Router();
        
        // Initialize SEO hreflang
        new GML_SEO_Hreflang();
        
        // Initialize queue processor
        new GML_Queue_Processor();
        
        // Initialize content crawler (auto-translate without page visits)
        new GML_Content_Crawler();
        
        // Initialize language switcher
        new GML_Language_Switcher();

        // Initialize language detector (auto-redirect based on browser language)
        new GML_Language_Detector();

        // Initialize multilingual sitemap
        new GML_Sitemap();
    }
    
    /**
     * Check if plugin is configured
     */
    private function is_configured() {
        $api_key = get_option('gml_api_key_encrypted');
        return !empty($api_key);
    }

    /**
     * Auto-upgrade DB schema when plugin files are updated but activate() wasn't called.
     * WordPress doesn't re-run activation hooks on plugin file updates.
     */
    private function maybe_upgrade_db() {
        $current = get_option( 'gml_db_version', '0' );
        if ( version_compare( $current, GML_Installer::DB_VERSION, '<' ) ) {
            require_once GML_PLUGIN_DIR . 'includes/class-installer.php';
            GML_Installer::activate();
        }
    }
    
    /**
     * Admin notice for configuration
     */
    public function admin_notice_configure() {
        ?>
        <div class="notice notice-warning">
            <p>
                <strong><?php _e('GML Translate:', 'gml-translate'); ?></strong>
                <?php _e('Please configure your Gemini API key to start translating.', 'gml-translate'); ?>
                <a href="<?php echo admin_url('admin.php?page=gml-translate'); ?>">
                    <?php _e('Configure Now', 'gml-translate'); ?>
                </a>
            </p>
        </div>
        <?php
    }
}

/**
 * Initialize plugin
 */
function gml_translate() {
    return GML_Translate::get_instance();
}

// Start the plugin
gml_translate();
