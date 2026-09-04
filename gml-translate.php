<?php
/**
 * Plugin Name: GML Translate
 * Plugin URI: https://huwencai.com/gml-translate
 * Description: AI multilingual translation for WordPress with stable language URLs, editable translations, glossary, queue controls, hreflang, and sitemap integration.
 * Version: 2.11.1-rc.16
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

// WordPress activation can include this file after the embedded host is loaded.
if ( defined( 'GML_TRANSLATION_HOST' ) && GML_TRANSLATION_HOST !== 'standalone' ) {
    register_activation_hook( __FILE__, static function() {
        wp_die(
            esc_html__( 'GML SEO is already providing multilingual translation. Use its Translation page, or deactivate GML SEO before activating GML Translate. Existing translation data has not been deleted.', 'gml-translate' ),
            esc_html__( 'Translation activation blocked', 'gml-translate' ),
            [ 'back_link' => true, 'response' => 409 ]
        );
    } );
    return;
}

// Define plugin constants
define('GML_VERSION', '2.11.1-rc.16');
define('GML_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('GML_PLUGIN_URL', plugin_dir_url(__FILE__));
define('GML_PLUGIN_FILE', __FILE__);
if ( ! defined( 'GML_TRANSLATION_HOST' ) ) {
    define( 'GML_TRANSLATION_HOST', 'standalone' );
}

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
        add_action('admin_init', [$this, 'maybe_flush_rewrite_rules']);
    }
    
    /**
     * Plugin activation
     */
    public function activate() {
        // Create database tables
        require_once GML_PLUGIN_DIR . 'includes/vendor/gml-translation-core/src/class-installer.php';
        $result = GML_Installer::activate();
        if ( is_wp_error( $result ) ) {
            wp_die( esc_html( $result->get_error_message() ), '', [ 'back_link' => true ] );
        }
        
        // Activation can run after init, so register before persisting routes.
        GML_Translation_Rewrite::register();
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
        // Only a permitted admin request may perform bounded database setup.
        GML_Installer::register_hooks();
        // Machine-readiness discovery is a provider-free shadow process. It
        // remains available when AI is disabled and does not alter public SEO.
        if ( class_exists( 'GML_Resource_Manifest_Manager' ) ) {
            GML_Resource_Manifest_Manager::register_hooks();
        }

        // Cron context: only background workers are needed. The crawler remains
        // registered while multilingual output is enabled so incremental source
        // changes can be discovered even when a full crawl is not running.
        // Skip all frontend components (Output Buffer, SEO Router, SEO Hreflang,
        // Language Switcher) to avoid unnecessary work and reduce the surface area
        // that triggers third-party plugin hooks (e.g. Elementor Pro Notes module).
        if ( defined( 'DOING_CRON' ) && DOING_CRON ) {
            if ( GML_Translation_State::work_enabled() ) {
                new GML_Queue_Processor();
            }
            if ( GML_Translation_State::multilingual_enabled() ) {
                new GML_Content_Crawler();
            }
            return;
        }

        // Always initialize admin (if in admin)
        if (is_admin()) {
            new GML_Admin_Settings();
            new GML_Translation_Editor();
        }

        // Keep generation-based invalidation active while multilingual output
        // is disabled so stale Redis/transient HTML cannot return on re-enable.
        new GML_Page_Cache();

        // Multilingual routing and existing translations do not depend on AI.
        if ( ! GML_Translation_State::multilingual_enabled() ) {
            if (is_admin()) {
                add_action('admin_notices', [$this, 'admin_notice_configure']);
            }
            return;
        }

        // This is a lightweight save/cron listener. Saving source content only
        // records a dirty object; discovery happens asynchronously and never
        // starts AI work or resumes a paused translation queue.
        new GML_Content_Crawler();

        // Nav menu switcher — needed in admin (meta box) and frontend (rendering)
        new GML_Nav_Menu_Switcher();
        
        // Initialize output buffer (hybrid interceptor)
        new GML_Output_Buffer();
        
        // Initialize gettext filter (runtime i18n string translation)
        // Must be initialized BEFORE template loading so header/footer/sidebar
        // strings are translated at PHP output time, not just in the output buffer.
        new GML_Gettext_Filter();
        
        // Initialize SEO router
        new GML_SEO_Router();
        
        // Translation Core supplies language relationships; the standalone
        // adapter owns the minimum multilingual SEO markup.
        $translation_provider = new GML_Translation_Provider();
        new GML_SEO_Hreflang( $translation_provider );
        
        // Initialize language switcher
        new GML_Language_Switcher();

        // Initialize language detector (auto-redirect based on browser language)
        new GML_Language_Detector();

        // Initialize multilingual sitemap
        new GML_Sitemap( $translation_provider );

        // AI workers are optional. Existing translations remain available when
        // credentials are removed, quota is exhausted, or AI is switched off.
        if ( GML_Translation_State::work_enabled() ) {
            new GML_Queue_Processor();
        }
    }
    
    /**
     * Check if plugin is configured
     */
    private function is_configured() {
        return GML_Translation_State::ai_available();
    }

    public function maybe_flush_rewrite_rules() {
        GML_Translation_Rewrite::maybe_repair();
    }
    
    /**
     * Admin notice for configuration
     */
    public function admin_notice_configure() {
        ?>
        <div class="notice notice-warning">
            <p>
                <strong><?php _e('GML Translate:', 'gml-translate'); ?></strong>
                <?php _e('Enable the multilingual site after choosing your languages. An AI API key is only required to generate new translations.', 'gml-translate'); ?>
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
