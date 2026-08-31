<?php
/**
 * Admin Settings class
 *
 * @package GML_Translate
 */

if (!defined('ABSPATH')) { exit; }

class GML_Admin_Settings {

    public function __construct() {
        add_action('admin_menu',             [$this, 'add_menu']);
        GML_Admin_Settings_Registration::register_hooks();
        add_action('admin_enqueue_scripts',  [$this, 'enqueue_admin_assets']);
    }

    // ── Menu ─────────────────────────────────────────────────────────────────

    public function add_menu() {
        add_menu_page(
            __('GML Translate', 'gml-translate'),
            __('GML Translate', 'gml-translate'),
            'manage_options',
            'gml-translate',
            [$this, 'render_page'],
            'dashicons-translation',
            80
        );
    }

    public function enqueue_admin_assets($hook) {
        if ($hook !== 'toplevel_page_gml-translate') return;
        wp_enqueue_style('gml-admin', GML_PLUGIN_URL . 'assets/css/admin.css', [], GML_VERSION);

        $tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'settings';

        // Settings tab needs jQuery UI Sortable for language reordering
        if ($tab === 'settings') {
            wp_enqueue_script('jquery-ui-sortable');
        }

        if ($tab === 'translations') {
            wp_enqueue_script('gml-editor', GML_PLUGIN_URL . 'assets/js/translation-editor.js', ['jquery'], GML_VERSION, true);
            wp_localize_script('gml-editor', 'gmlEditor', [
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce'   => wp_create_nonce('gml_editor_nonce'),
                'i18n'    => [
                    'scanFailed'     => __('Request failed. Refresh the status before retrying.', 'gml-translate'),
                    'save'           => __('Save', 'gml-translate'),
                    'cancel'         => __('Cancel', 'gml-translate'),
                    'edit'           => __('Edit', 'gml-translate'),
                    'delete'         => __('Delete', 'gml-translate'),
                    'search'         => __('Search translations...', 'gml-translate'),
                    'source'         => __('Source Text', 'gml-translate'),
                    'translation'    => __('Translation', 'gml-translate'),
                    'type'           => __('Type', 'gml-translate'),
                    'status'         => __('Status', 'gml-translate'),
                    'actions'        => __('Actions', 'gml-translate'),
                    'noResults'      => __('No translations found.', 'gml-translate'),
                    'loading'        => __('Loading...', 'gml-translate'),
                    'confirmDelete'  => __('Delete this translation?', 'gml-translate'),
                    'saved'          => __('Translation saved.', 'gml-translate'),
                    'all'            => __('All', 'gml-translate'),
                    'auto'           => __('Auto', 'gml-translate'),
                    'manual'         => __('Manual', 'gml-translate'),
                    'prev'           => __('← Previous', 'gml-translate'),
                    'next'           => __('Next →', 'gml-translate'),
                    'pageInfo'       => __('Page %1$s of %2$s (%3$s total)', 'gml-translate'),
                    'manageTranslations' => __('Manage Translations', 'gml-translate'),
                ],
            ]);
        }
    }

    // ── Single entry-point renderer ──────────────────────────────────────────

    public function render_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('Sorry, you are not allowed to access this page.', 'gml-translate'));
        }

        $tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'settings';
        $tabs = [
            'settings'     => __('Settings',           'gml-translate'),
            'switcher'     => __('Language Switcher',   'gml-translate'),
            'translations' => __('Translations',        'gml-translate'),
            'exclusions'   => __('Exclusion Rules',     'gml-translate'),
            'glossary'     => __('Glossary',            'gml-translate'),
        ];
        ?>
        <div class="wrap">
            <h1 style="margin-bottom:0;"><?php _e('GML Translate', 'gml-translate'); ?></h1>

            <nav class="nav-tab-wrapper" style="margin-bottom:20px;">
                <?php foreach ($tabs as $key => $label):
                    $url    = admin_url('admin.php?page=gml-translate&tab=' . $key);
                    $active = ($tab === $key) ? ' nav-tab-active' : '';
                ?>
                    <a href="<?php echo esc_url($url); ?>" class="nav-tab<?php echo $active; ?>"><?php echo esc_html($label); ?></a>
                <?php endforeach; ?>
            </nav>

            <?php
            if ($tab === 'settings')     $this->render_settings_tab();
            elseif ($tab === 'switcher') $this->render_switcher_tab();
            elseif ($tab === 'exclusions') $this->render_exclusions_tab();
            elseif ($tab === 'glossary') $this->render_glossary_tab();
            else                         $this->render_translations_tab();
            ?>
        </div>
        <?php
    }

    // ── Tab: Settings ────────────────────────────────────────────────────────

    private function render_settings_tab() {
        // Show Weglot import success notice (one-time)
        $weglot_imported = get_option( 'gml_weglot_imported', false );
        if ( $weglot_imported !== false ) {
            echo '<div class="notice notice-success is-dismissible"><p>';
            printf(
                __( '🎉 Detected Weglot configuration! Automatically imported source language and %d destination language(s). Please verify the settings below.', 'gml-translate' ),
                intval( $weglot_imported )
            );
            echo '</p></div>';
            delete_option( 'gml_weglot_imported' );
        }

        // Handle language reorder
        if (isset($_POST['action']) && $_POST['action'] === 'reorder_languages' && check_admin_referer('gml_language_action', 'gml_language_nonce')) {
            $order = array_map('sanitize_text_field', $_POST['lang_order'] ?? []);
            if (!empty($order)) {
                $languages = get_option('gml_languages', []);
                $indexed = [];
                foreach ($languages as $l) { $indexed[$l['code']] = $l; }
                $reordered = [];
                foreach ($order as $code) {
                    if (isset($indexed[$code])) $reordered[] = $indexed[$code];
                }
                // Append any languages not in the order list (safety)
                foreach ($languages as $l) {
                    if (!in_array($l['code'], $order)) $reordered[] = $l;
                }
                update_option('gml_languages', $reordered);
                $languages = $reordered;
                echo '<div class="notice notice-success"><p>' . __('Language order saved!', 'gml-translate') . '</p></div>';
            }
        }

        // Handle language add/remove
        if (isset($_POST['action']) && check_admin_referer('gml_language_action', 'gml_language_nonce')) {
            if ($_POST['action'] === 'add_language') {
                $this->add_language($_POST);
                echo '<div class="notice notice-success"><p>' . __('Language added successfully!', 'gml-translate') . '</p></div>';
            } elseif ($_POST['action'] === 'remove_language') {
                $this->remove_language($_POST['lang_code']);
                echo '<div class="notice notice-success"><p>' . __('Language removed successfully!', 'gml-translate') . '</p></div>';
            }
        }

        // Handle main settings save
        if (isset($_POST['gml_save_settings']) && check_admin_referer('gml_main_settings', 'gml_settings_nonce')) {
            $this->save_settings();
            settings_errors('gml_messages');
        }

        if (isset($_POST['gml_test_connection']) && check_admin_referer('gml_test_connection', 'gml_test_connection_nonce')) {
            $client = new GML_Gemini_API();
            $test = $client->test_connection();
            if ( ! empty( $test['valid'] ) ) {
                GML_Queue_Processor::clear_circuit_breaker();
                echo '<div class="notice notice-success is-dismissible"><p>' . esc_html( $test['message'] ) . ' ' . esc_html__( 'This test does not start or resume translation.', 'gml-translate' ) . '</p></div>';
            } else {
                GML_Translation_Controls::pause();
                echo '<div class="notice notice-error is-dismissible"><p>' . esc_html( $test['message'] ?? __( 'The saved AI connection could not be verified.', 'gml-translate' ) ) . '</p></div>';
            }
            echo '<p class="description">' . esc_html( sprintf( __( 'Tested saved configuration: %1$s / %2$s. Key contents are never displayed.', 'gml-translate' ), GML_Gemini_API::get_engine_label( $client->get_engine() ), $client->get_model() ) ) . '</p>';
        }

        // Handle advanced settings save
        if (isset($_POST['gml_save_advanced']) && check_admin_referer('gml_advanced_settings', 'gml_advanced_nonce')) {
            update_option('gml_auto_detect_language', isset($_POST['gml_auto_detect_language']));
            update_option('gml_tone', sanitize_text_field($_POST['gml_tone'] ?? 'professional and friendly'));
            echo '<div class="notice notice-success"><p>' . __('Advanced settings saved!', 'gml-translate') . '</p></div>';
        }

        $current_engine     = get_option('gml_translation_engine', 'gemini');
        $api_key_status     = GML_Translation_Credentials::status( 'gemini' );
        $deepseek_key_status = GML_Translation_Credentials::status( 'deepseek' );
        $api_key_set        = $api_key_status !== 'missing';
        $deepseek_key_set   = $deepseek_key_status !== 'missing';
        $multilingual_on    = GML_Translation_State::multilingual_enabled();
        $ai_translation_on  = GML_Translation_State::ai_translation_enabled();
        $wp_locale          = get_locale();
        $default_lang       = substr($wp_locale, 0, 2);
        $source_lang        = get_option('gml_source_lang', $default_lang);
        $languages          = get_option('gml_languages', []);
        $available_languages = $this->get_available_languages();
        ?>

        <form method="post" action="" id="gml-main-settings-form">
            <?php wp_nonce_field('gml_main_settings', 'gml_settings_nonce'); ?>
            <input type="hidden" name="gml_save_settings" value="1" />

            <h2><?php _e('Main Configuration', 'gml-translate'); ?></h2>
            <table class="form-table">
                <tr>
                    <th><label for="gml_multilingual_enabled"><?php _e('Multilingual Site', 'gml-translate'); ?></label></th>
                    <td>
                        <label>
                            <input type="checkbox" id="gml_multilingual_enabled" name="gml_multilingual_enabled" value="1" <?php checked( $multilingual_on ); ?> />
                            <?php _e('Enable multilingual URLs and saved translations', 'gml-translate'); ?>
                        </label>
                        <p class="description"><?php _e('Routes, the language switcher, hreflang, sitemap variants, and existing translations continue working without an AI API key.', 'gml-translate'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th><label for="gml_ai_translation_enabled"><?php _e('AI Translation', 'gml-translate'); ?></label></th>
                    <td>
                        <label>
                            <input type="checkbox" id="gml_ai_translation_enabled" name="gml_ai_translation_enabled" value="1" <?php checked( $ai_translation_on ); ?> />
                            <?php _e('Allow the queue and crawler to generate new translations', 'gml-translate'); ?>
                        </label>
                        <p class="description"><?php _e('Requires a valid key for the selected provider. Turning this off never removes or hides saved translations.', 'gml-translate'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th><label for="gml_translation_engine"><?php _e('Translation Engine', 'gml-translate'); ?></label></th>
                    <td>
                        <select id="gml_translation_engine" name="gml_translation_engine" style="min-width:200px;">
                            <option value="gemini" <?php selected($current_engine, 'gemini'); ?>>Google Gemini</option>
                            <option value="deepseek" <?php selected($current_engine, 'deepseek'); ?>>DeepSeek</option>
                        </select>
                        <p class="description"><?php _e('Choose the AI engine for translation.', 'gml-translate'); ?></p>
                    </td>
                </tr>
                <tr class="gml-engine-gemini" <?php echo $current_engine !== 'gemini' ? 'style="display:none;"' : ''; ?>>
                    <th><label for="gml_api_key"><?php _e('Gemini API Key', 'gml-translate'); ?></label></th>
                    <td>
                        <input type="password" id="gml_api_key" name="gml_api_key" class="regular-text" autocomplete="new-password"
                               value="" placeholder="<?php echo $api_key_set ? esc_attr__( 'Saved. Leave blank to keep unchanged.', 'gml-translate' ) : ''; ?>" />
                        <?php if ($api_key_status === 'unreadable'): ?>
                            <p class="description" style="color:#d63638;"><?php echo esc_html( GML_Translation_Credentials::error_message() ); ?></p>
                        <?php elseif ($api_key_set): ?>
                            <p class="description"><?php _e('Stored and readable. This is not a connection check.', 'gml-translate'); ?></p>
                        <?php else: ?>
                            <p class="description">
                                <?php _e('Get your key from', 'gml-translate'); ?>
                                <a href="https://aistudio.google.com/app/apikey" target="_blank">Google AI Studio</a>.
                            </p>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr class="gml-engine-deepseek" <?php echo $current_engine !== 'deepseek' ? 'style="display:none;"' : ''; ?>>
                    <th><label for="gml_deepseek_api_key"><?php _e('DeepSeek API Key', 'gml-translate'); ?></label></th>
                    <td>
                        <input type="password" id="gml_deepseek_api_key" name="gml_deepseek_api_key" class="regular-text" autocomplete="new-password"
                               value="" placeholder="<?php echo $deepseek_key_set ? esc_attr__( 'Saved. Leave blank to keep unchanged.', 'gml-translate' ) : ''; ?>" />
                        <?php if ($deepseek_key_status === 'unreadable'): ?>
                            <p class="description" style="color:#d63638;"><?php echo esc_html( GML_Translation_Credentials::error_message() ); ?></p>
                        <?php elseif ($deepseek_key_set): ?>
                            <p class="description"><?php _e('Stored and readable. This is not a connection check.', 'gml-translate'); ?></p>
                        <?php else: ?>
                            <p class="description">
                                <?php _e('Get your key from', 'gml-translate'); ?>
                                <a href="https://platform.deepseek.com/api_keys" target="_blank">DeepSeek Platform</a>.
                            </p>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr class="gml-engine-deepseek" <?php echo $current_engine !== 'deepseek' ? 'style="display:none;"' : ''; ?>>
                    <th><label for="gml_deepseek_model"><?php _e('DeepSeek Model', 'gml-translate'); ?></label></th>
                    <td>
                        <input type="text" id="gml_deepseek_model" name="gml_deepseek_model" class="regular-text"
                               value="<?php echo esc_attr(get_option('gml_deepseek_model', 'deepseek-chat')); ?>"
                               placeholder="deepseek-chat" />
                        <p class="description"><?php _e('Default: deepseek-chat. You can also use deepseek-reasoner.', 'gml-translate'); ?></p>
                    </td>
                </tr>
                <tr class="gml-engine-deepseek" <?php echo $current_engine !== 'deepseek' ? 'style="display:none;"' : ''; ?>>
                    <th><label for="gml_deepseek_api_base"><?php _e('API Base URL', 'gml-translate'); ?></label></th>
                    <td>
                        <input type="text" id="gml_deepseek_api_base" name="gml_deepseek_api_base" class="regular-text"
                               value="<?php echo esc_attr(get_option('gml_deepseek_api_base', '')); ?>"
                               placeholder="https://api.deepseek.com/v1" />
                        <p class="description"><?php _e('Optional. For security, only the official api.deepseek.com HTTPS host is accepted; leave empty to use the default endpoint.', 'gml-translate'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th><label for="gml_source_lang"><?php _e('Original Language', 'gml-translate'); ?></label></th>
                    <td>
                        <select id="gml_source_lang" name="gml_source_lang" style="min-width:300px;">
                            <?php foreach ($available_languages as $code => $lang): ?>
                                <option value="<?php echo esc_attr($code); ?>" <?php selected($source_lang, $code); ?>>
                                    <?php echo esc_html($lang['native']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="description"><?php _e('The current language of your website.', 'gml-translate'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th><label><?php _e('Destination Languages', 'gml-translate'); ?></label></th>
                    <td>
                        <div id="gml-destination-languages" style="margin-bottom:10px;display:flex;flex-wrap:wrap;gap:4px;">
                            <?php foreach ($languages as $lang): ?>
                                <span class="gml-lang-tag" data-code="<?php echo esc_attr($lang['code']); ?>"
                                      style="display:inline-flex;align-items:center;padding:5px 10px;background:#f0f0f0;border-radius:3px;cursor:grab;user-select:none;">
                                    <span class="dashicons dashicons-menu" style="font-size:14px;width:14px;height:14px;margin-right:4px;color:#999;"></span>
                                    <?php echo esc_html($lang['native_name']); ?>
                                    <button type="button" class="gml-remove-lang" data-lang="<?php echo esc_attr($lang['code']); ?>"
                                            style="margin-left:5px;background:none;border:none;color:#999;cursor:pointer;font-size:16px;">×</button>
                                </span>
                            <?php endforeach; ?>
                        </div>
                        <div id="gml-reorder-controls" style="display:none;">
                            <button type="submit" form="gml-reorder-form" class="button button-small" style="margin-bottom:10px;"><?php _e('Save Order', 'gml-translate'); ?></button>
                        </div>
                        <div id="gml-lang-search-wrap" style="position:relative;display:inline-block;min-width:300px;">
                            <input type="text" id="gml_lang_search" placeholder="<?php esc_attr_e('Type to search languages…', 'gml-translate'); ?>"
                                   autocomplete="off" style="width:100%;box-sizing:border-box;">
                            <ul id="gml_lang_results" style="display:none;position:absolute;z-index:999;background:#fff;border:1px solid #8c8f94;
                                border-top:none;max-height:250px;overflow-y:auto;width:100%;margin:0;padding:0;list-style:none;box-sizing:border-box;">
                            <?php
                            $used_codes = array_column($languages, 'code');
                            foreach ($available_languages as $code => $lang):
                                if ($code !== $source_lang && !in_array($code, $used_codes)):
                            ?>
                                <li data-code="<?php echo esc_attr($code); ?>"
                                    data-search="<?php echo esc_attr(strtolower($lang['name'] . ' ' . $lang['native'] . ' ' . $code)); ?>"
                                    style="padding:6px 10px;cursor:pointer;border-bottom:1px solid #f0f0f0;"
                                    onmouseover="this.style.background='#f0f7ff'" onmouseout="this.style.background='#fff'">
                                    <?php echo esc_html($lang['native'] . ' — ' . $lang['name']); ?>
                                </li>
                            <?php endif; endforeach; ?>
                            </ul>
                        </div>
                        <script>
                        (function(){
                            var wrap = document.getElementById('gml-lang-search-wrap');
                            var input = document.getElementById('gml_lang_search');
                            var list = document.getElementById('gml_lang_results');
                            if (!input || !list) return;
                            var items = list.querySelectorAll('li');
                            input.addEventListener('focus', function(){ list.style.display='block'; filter(); });
                            input.addEventListener('input', filter);
                            document.addEventListener('click', function(e){ if (!wrap.contains(e.target)) list.style.display='none'; });
                            function filter(){
                                var q = input.value.toLowerCase();
                                var any = false;
                                items.forEach(function(li){
                                    var match = !q || li.getAttribute('data-search').indexOf(q) !== -1;
                                    li.style.display = match ? '' : 'none';
                                    if (match) any = true;
                                });
                                list.style.display = any ? 'block' : 'none';
                            }
                            items.forEach(function(li){
                                li.addEventListener('click', function(){
                                    var code = this.getAttribute('data-code');
                                    list.style.display = 'none';
                                    input.value = '';
                                    var form = document.createElement('form');
                                    form.method = 'POST';
                                    form.innerHTML = '<?php echo wp_nonce_field('gml_language_action', 'gml_language_nonce', true, false); ?>'
                                        + '<input type="hidden" name="action" value="add_language">'
                                        + '<input type="hidden" name="lang_code" value="' + code + '">';
                                    document.body.appendChild(form);
                                    form.submit();
                                });
                            });
                        })();
                        </script>
                        <p class="description">
                            <?php _e('Languages to translate into.', 'gml-translate'); ?>
                        </p>
                    </td>
                </tr>
            </table>
            <?php submit_button(__('Save Changes', 'gml-translate')); ?>
        </form>

            <form method="post" id="gml-reorder-form" hidden>
                <?php wp_nonce_field('gml_language_action', 'gml_language_nonce'); ?>
                <input type="hidden" name="action" value="reorder_languages" />
                <div id="gml-reorder-inputs"></div>
            </form>
            <form method="post" action="" id="gml-test-connection-form" style="margin-bottom:20px;">
                <?php wp_nonce_field('gml_test_connection', 'gml_test_connection_nonce'); ?>
                <button type="submit" name="gml_test_connection" value="1" class="button button-secondary">
                    <?php _e('Test Saved AI Connection', 'gml-translate'); ?>
                </button>
            </form>

        <?php if (!empty($languages)): ?>
        <hr style="margin:30px 0;">
        <h2><?php _e('Advanced Settings', 'gml-translate'); ?></h2>
        <form method="post" action="">
            <?php wp_nonce_field('gml_advanced_settings', 'gml_advanced_nonce'); ?>
            <input type="hidden" name="gml_save_advanced" value="1" />
            <table class="form-table">
                <tr>
                    <th><label for="gml_auto_detect_language"><?php _e('Auto-Detect Language', 'gml-translate'); ?></label></th>
                    <td>
                        <input type="checkbox" id="gml_auto_detect_language" name="gml_auto_detect_language" value="1"
                               <?php checked(get_option('gml_auto_detect_language', false)); ?> />
                        <span class="description"><?php _e('Automatically redirect first-time visitors to their preferred language based on browser settings.', 'gml-translate'); ?></span>
                        <p class="description" style="margin-top:4px;color:#666;">
                            <?php _e('Only redirects on the homepage. Returning visitors keep their chosen language via cookie. Bots/crawlers are never redirected.', 'gml-translate'); ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th><label for="gml_tone"><?php _e('Translation Tone', 'gml-translate'); ?></label></th>
                    <td>
                        <input type="text" id="gml_tone" name="gml_tone" class="regular-text"
                               value="<?php echo esc_attr(get_option('gml_tone', 'professional and friendly')); ?>"
                               placeholder="professional and friendly" />
                        <p class="description"><?php _e('The tone/style for translations (e.g. "professional and friendly", "casual", "formal").', 'gml-translate'); ?></p>
                    </td>
                </tr>
            </table>
            <?php submit_button(__('Save Advanced Settings', 'gml-translate')); ?>
        </form>

        <hr style="margin:30px 0;">
        <h2><?php _e('Language URLs', 'gml-translate'); ?></h2>
        <table class="wp-list-table widefat fixed striped" style="margin-top:10px;">
            <thead><tr>
                <th style="width:35%;"><?php _e('Language', 'gml-translate'); ?></th>
                <th style="width:15%;"><?php _e('Code', 'gml-translate'); ?></th>
                <th><?php _e('Example URL', 'gml-translate'); ?></th>
            </tr></thead>
            <tbody>
            <?php foreach ($languages as $lang): ?>
                <tr>
                    <td><strong><?php echo esc_html($lang['native_name']); ?></strong></td>
                    <td><code><?php echo esc_html($lang['code']); ?></code></td>
                    <td><code><?php echo esc_html(home_url('/' . $lang['code'] . '/about/')); ?></code></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>

        <hr style="margin:30px 0;">
        <h2><?php _e('System Status', 'gml-translate'); ?></h2>
        <table class="form-table">
            <tr><th><?php _e('Plugin Version:', 'gml-translate'); ?></th><td><?php echo esc_html(GML_VERSION); ?></td></tr>
            <tr><th><?php _e('Translation Engine:', 'gml-translate'); ?></th><td><?php echo esc_html($current_engine === 'deepseek' ? 'DeepSeek' : 'Google Gemini'); ?></td></tr>
            <tr>
                <th><?php _e('API Status:', 'gml-translate'); ?></th>
                <td><?php
                    $engine_key_status = $current_engine === 'deepseek' ? $deepseek_key_status : $api_key_status;
                    if ($engine_key_status === 'unreadable'): ?>
                    <span style="color:#d63638;"><?php _e('Saved key is unreadable. Re-enter it.', 'gml-translate'); ?></span>
                <?php elseif ($engine_key_status === 'readable'): ?>
                    <span><?php _e('Stored and readable. This is not a connection check.', 'gml-translate'); ?></span>
                <?php else: ?>
                    <span style="color:#d63638;">✗ <?php _e('Not configured', 'gml-translate'); ?></span>
                <?php endif; ?></td>
            </tr>
            <tr><th><?php _e('Active Languages:', 'gml-translate'); ?></th><td><strong><?php echo count($languages); ?></strong></td></tr>
        </table>

        <script>
        jQuery(document).ready(function($) {
            // Sortable language tags
            $('#gml-destination-languages').sortable({
                items: '.gml-lang-tag',
                cursor: 'grabbing',
                tolerance: 'pointer',
                update: function() {
                    // Show save button and populate hidden inputs
                    var container = $('#gml-reorder-inputs');
                    container.empty();
                    $('#gml-destination-languages .gml-lang-tag').each(function() {
                        container.append('<input type="hidden" name="lang_order[]" value="' + $(this).data('code') + '">');
                    });
                    $('#gml-reorder-controls').show();
                }
            });

            $(document).on('click', '.gml-remove-lang', function() {
                if (!confirm('<?php _e('Remove this language?', 'gml-translate'); ?>')) return;
                var langCode = $(this).data('lang');
                var form = $('<form method="post"></form>');
                form.append('<?php echo wp_nonce_field('gml_language_action', 'gml_language_nonce', true, false); ?>');
                form.append('<input type="hidden" name="action" value="remove_language">');
                form.append('<input type="hidden" name="lang_code" value="' + langCode + '">');
                $('body').append(form); form.submit();
            });
            // Engine toggle: show/hide Gemini vs DeepSeek fields
            $('#gml_translation_engine').on('change', function() {
                var engine = $(this).val();
                $('.gml-engine-gemini').toggle(engine === 'gemini');
                $('.gml-engine-deepseek').toggle(engine === 'deepseek');
            });
        });
        </script>
        <style>.gml-lang-tag:hover{background:#e0e0e0!important;}.gml-remove-lang:hover{color:#d63638!important;}.gml-lang-tag.ui-sortable-helper{background:#fff!important;box-shadow:0 2px 8px rgba(0,0,0,.15);}.gml-lang-tag.ui-sortable-placeholder{visibility:visible!important;background:#e8f0fe!important;border:1px dashed #2271b1;border-radius:3px;}</style>
        <?php
    }

    // ── Tab: Language Switcher ────────────────────────────────────────────────

    private function render_switcher_tab() {
        if (isset($_POST['submit']) && check_admin_referer('gml_switcher_settings')) {
            $this->save_switcher_settings();
            echo '<div class="notice notice-success"><p>' . __('Language switcher settings saved!', 'gml-translate') . '</p></div>';
        }

        $is_dropdown      = get_option('gml_switcher_is_dropdown', true);
        $show_flags       = get_option('gml_switcher_show_flags', true);
        $flag_type        = get_option('gml_switcher_flag_type', 'rectangle');
        $show_names       = get_option('gml_switcher_show_names', true);
        $use_fullname     = get_option('gml_switcher_use_fullname', true);
        $switcher_position = get_option('gml_switcher_position', 'none');
        $custom_css       = get_option('gml_switcher_custom_css', '');

        $languages           = get_option('gml_languages', []);
        $wp_locale           = get_locale();
        $source_lang         = get_option('gml_source_lang', substr($wp_locale, 0, 2));
        $available_languages = $this->get_available_languages();
        $source_lang_info    = $available_languages[$source_lang] ?? $available_languages['en'];

        if (empty($languages)): ?>
            <div class="notice notice-warning"><p>
                <strong><?php _e('No languages configured yet.', 'gml-translate'); ?></strong>
                <?php _e('Add languages in the Settings tab first.', 'gml-translate'); ?>
            </p></div>
        <?php endif; ?>

        <form method="post" action="">
            <?php wp_nonce_field('gml_switcher_settings'); ?>

            <h2><?php _e('Language Button Design', 'gml-translate'); ?></h2>
            <table class="form-table">
                <tr>
                    <th><label><?php _e('Button Preview', 'gml-translate'); ?></label></th>
                    <td>
                        <div id="gml-button-preview-container" style="padding:20px;background:#f9f9f9;border:1px solid #ddd;border-radius:4px;min-height:80px;">
                            <div id="gml-button-preview"></div>
                        </div>
                        <p class="description"><?php _e('Live preview — updates automatically', 'gml-translate'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th><label for="gml_switcher_is_dropdown"><?php _e('Is Dropdown', 'gml-translate'); ?></label></th>
                    <td>
                        <input type="checkbox" id="gml_switcher_is_dropdown" name="gml_switcher_is_dropdown" value="1" <?php checked($is_dropdown, true); ?> />
                        <span class="description"><?php _e('Show as a dropdown menu', 'gml-translate'); ?></span>
                    </td>
                </tr>
                <tr>
                    <th><label for="gml_switcher_show_flags"><?php _e('With Flags', 'gml-translate'); ?></label></th>
                    <td>
                        <input type="checkbox" id="gml_switcher_show_flags" name="gml_switcher_show_flags" value="1" <?php checked($show_flags, true); ?> />
                        <span class="description"><?php _e('Show flag icons', 'gml-translate'); ?></span>
                    </td>
                </tr>
                <tr id="flag_type_row" style="<?php echo $show_flags ? '' : 'display:none;'; ?>">
                    <th><label for="gml_switcher_flag_type"><?php _e('Type of Flags', 'gml-translate'); ?></label></th>
                    <td>
                        <select id="gml_switcher_flag_type" name="gml_switcher_flag_type">
                            <option value="emoji"     <?php selected($flag_type, 'emoji'); ?>><?php _e('Emoji (Recommended)', 'gml-translate'); ?></option>
                            <option value="circle"    <?php selected($flag_type, 'circle'); ?>><?php _e('Circle', 'gml-translate'); ?></option>
                            <option value="square"    <?php selected($flag_type, 'square'); ?>><?php _e('Square', 'gml-translate'); ?></option>
                            <option value="rectangle" <?php selected($flag_type, 'rectangle'); ?>><?php _e('Rectangle', 'gml-translate'); ?></option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><label for="gml_switcher_show_names"><?php _e('With Name', 'gml-translate'); ?></label></th>
                    <td>
                        <input type="checkbox" id="gml_switcher_show_names" name="gml_switcher_show_names" value="1" <?php checked($show_names, true); ?> />
                        <span class="description"><?php _e('Show language names', 'gml-translate'); ?></span>
                    </td>
                </tr>
                <tr id="fullname_row" style="<?php echo $show_names ? '' : 'display:none;'; ?>">
                    <th><label for="gml_switcher_use_fullname"><?php _e('Is Fullname', 'gml-translate'); ?></label></th>
                    <td>
                        <input type="checkbox" id="gml_switcher_use_fullname" name="gml_switcher_use_fullname" value="1" <?php checked($use_fullname, true); ?> />
                        <span class="description"><?php _e('Full name instead of language code', 'gml-translate'); ?></span>
                    </td>
                </tr>
                <tr>
                    <th><label for="gml_switcher_position"><?php _e('Automatic Position', 'gml-translate'); ?></label></th>
                    <td>
                        <select id="gml_switcher_position" name="gml_switcher_position">
                            <option value="none"          <?php selected($switcher_position, 'none'); ?>><?php _e('None (Use shortcode/widget/function)', 'gml-translate'); ?></option>
                            <optgroup label="<?php _e('Header', 'gml-translate'); ?>">
                                <option value="header_left"   <?php selected($switcher_position, 'header_left'); ?>><?php _e('Header - Left', 'gml-translate'); ?></option>
                                <option value="header_center" <?php selected($switcher_position, 'header_center'); ?>><?php _e('Header - Center', 'gml-translate'); ?></option>
                                <option value="header_right"  <?php selected($switcher_position, 'header_right'); ?>><?php _e('Header - Right', 'gml-translate'); ?></option>
                            </optgroup>
                            <optgroup label="<?php _e('Navigation', 'gml-translate'); ?>">
                                <option value="menu_before" <?php selected($switcher_position, 'menu_before'); ?>><?php _e('Before Menu', 'gml-translate'); ?></option>
                                <option value="menu_after"  <?php selected($switcher_position, 'menu_after'); ?>><?php _e('After Menu', 'gml-translate'); ?></option>
                            </optgroup>
                            <optgroup label="<?php _e('Footer', 'gml-translate'); ?>">
                                <option value="footer_left"   <?php selected($switcher_position, 'footer_left'); ?>><?php _e('Footer - Left', 'gml-translate'); ?></option>
                                <option value="footer_center" <?php selected($switcher_position, 'footer_center'); ?>><?php _e('Footer - Center', 'gml-translate'); ?></option>
                                <option value="footer_right"  <?php selected($switcher_position, 'footer_right'); ?>><?php _e('Footer - Right', 'gml-translate'); ?></option>
                            </optgroup>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><label for="gml_switcher_custom_css"><?php _e('Override CSS', 'gml-translate'); ?></label></th>
                    <td>
                        <textarea id="gml_switcher_custom_css" name="gml_switcher_custom_css" rows="8" class="large-text code"
                                  placeholder=".country-selector { margin-bottom: 20px; }"><?php echo esc_textarea($custom_css); ?></textarea>
                        <p class="description"><?php _e('Custom CSS for the language switcher.', 'gml-translate'); ?></p>
                    </td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>

        <hr>
        <h2><?php _e('Usage Methods', 'gml-translate'); ?></h2>
        <h3>1. <?php _e('Shortcode', 'gml-translate'); ?></h3>
        <pre style="background:#f5f5f5;padding:10px;border:1px solid #ddd;border-radius:4px;">[gml_language_switcher]</pre>
        <pre style="background:#f5f5f5;padding:10px;border:1px solid #ddd;border-radius:4px;">[gml_language_switcher is_dropdown="true" show_flags="true" show_names="true" use_fullname="true"]</pre>
        <h3>2. <?php _e('Widget', 'gml-translate'); ?></h3>
        <p><?php _e('Go to', 'gml-translate'); ?> <a href="<?php echo admin_url('widgets.php'); ?>"><?php _e('Appearance → Widgets', 'gml-translate'); ?></a> <?php _e('and add the "GML Language Switcher" widget.', 'gml-translate'); ?></p>
        <h3>3. <?php _e('PHP Function', 'gml-translate'); ?></h3>
        <pre style="background:#f5f5f5;padding:10px;border:1px solid #ddd;border-radius:4px;">&lt;?php if (function_exists('gml_language_switcher')) gml_language_switcher(); ?&gt;</pre>

        <style>
        .gml-lang-switcher-preview .gml-flag-img { display:inline-block;vertical-align:middle;object-fit:cover;flex-shrink:0; }
        .gml-lang-switcher-preview .gml-flag-rectangle { width:24px;height:16px;border-radius:2px; }
        .gml-lang-switcher-preview .gml-flag-square    { width:20px;height:20px;border-radius:3px; }
        .gml-lang-switcher-preview .gml-flag-circle    { width:20px;height:20px;border-radius:50%; }
        .gml-lang-switcher-preview .gml-flag-emoji     { font-size:18px;line-height:1; }
        </style>
        <script>
        jQuery(document).ready(function($) {
            var sourceCountry = <?php echo json_encode(self::get_country_from_locale($source_lang, $wp_locale)); ?>;
            var sourceEmoji   = <?php echo json_encode($source_lang_info['flag'] ?? '🏴'); ?>;
            var sourceName    = <?php echo json_encode($source_lang_info['native']); ?>;
            var sourceCode    = <?php echo json_encode(strtoupper($source_lang)); ?>;
            var allLangs      = <?php echo json_encode($this->get_available_languages()); ?>;
            var languages     = <?php echo json_encode(array_slice($languages, 0, 3)); ?>;
            languages = languages.map(function(l) {
                var info = allLangs[l.code] || {};
                l.country = info.country || l.code;
                l.emoji   = info.flag || '🏴';
                return l;
            });

            function getFlagHtml(country, emoji, flagType, alt) {
                if (flagType === 'emoji') return '<span style="font-size:18px;line-height:1;vertical-align:middle;">' + emoji + '</span>';
                var sizeMap = { rectangle:'width:24px;height:16px;border-radius:2px;', square:'width:20px;height:20px;border-radius:3px;', circle:'width:20px;height:20px;border-radius:50%;' };
                var style = 'display:inline-block;vertical-align:middle;object-fit:cover;flex-shrink:0;' + (sizeMap[flagType] || sizeMap.rectangle);
                return '<img src="https://flagcdn.com/w40/' + country + '.png" alt="' + alt + '" style="' + style + '">';
            }

            $('#gml_switcher_show_flags').change(function() { $('#flag_type_row').toggle($(this).is(':checked')); updatePreview(); });
            $('#gml_switcher_show_names').change(function() { $('#fullname_row').toggle($(this).is(':checked')); updatePreview(); });
            $('input[type="checkbox"], select').change(updatePreview);
            updatePreview();

            function updatePreview() {
                var isDropdown  = $('#gml_switcher_is_dropdown').is(':checked');
                var showFlags   = $('#gml_switcher_show_flags').is(':checked');
                var showNames   = $('#gml_switcher_show_names').is(':checked');
                var useFullname = $('#gml_switcher_use_fullname').is(':checked');
                var flagType    = $('#gml_switcher_flag_type').val();

                function itemHtml(country, emoji, label, extraStyle) {
                    var html = '<div style="padding:8px 12px;display:flex;align-items:center;gap:8px;font-size:14px;cursor:pointer;' + (extraStyle||'') + '">';
                    if (showFlags) html += getFlagHtml(country, emoji, flagType, label);
                    if (showNames) html += '<span>' + label + '</span>';
                    return html + '</div>';
                }

                var html = '<div class="gml-lang-switcher-preview">';
                if (isDropdown) {
                    var btnLabel = useFullname ? sourceName : sourceCode;
                    html += '<div style="position:relative;display:inline-block;">';
                    html += '<button type="button" style="padding:8px 12px;border:1px solid #ddd;border-radius:4px;background:#fff;cursor:pointer;font-size:14px;display:inline-flex;align-items:center;gap:8px;min-width:140px;">';
                    if (showFlags) html += getFlagHtml(sourceCountry, sourceEmoji, flagType, sourceName);
                    if (showNames) html += '<span style="flex:1;text-align:left;">' + btnLabel + '</span>';
                    html += '<span style="font-size:10px;color:#888;margin-left:auto;">▼</span></button>';
                    if (languages.length > 0) {
                        html += '<div style="margin-top:4px;background:#fff;border:1px solid #ddd;border-radius:4px;box-shadow:0 2px 8px rgba(0,0,0,.08);padding:4px 0;min-width:140px;">';
                        html += itemHtml(sourceCountry, sourceEmoji, useFullname ? sourceName : sourceCode, 'background:#f5f5f5;font-weight:500;');
                        languages.forEach(function(l) { html += itemHtml(l.country, l.emoji, useFullname ? l.native_name : l.code.toUpperCase(), ''); });
                        html += '</div>';
                    }
                    html += '</div>';
                } else {
                    html += '<div style="display:inline-flex;gap:8px;flex-wrap:wrap;align-items:center;">';
                    html += '<a href="#" style="padding:6px 12px;border:1px solid #0073aa;border-radius:3px;background:#0073aa;color:#fff;text-decoration:none;font-size:13px;display:inline-flex;align-items:center;gap:6px;font-weight:500;">';
                    if (showFlags) html += getFlagHtml(sourceCountry, sourceEmoji, flagType, sourceName);
                    if (showNames) html += '<span>' + (useFullname ? sourceName : sourceCode) + '</span>';
                    html += '</a>';
                    languages.forEach(function(l) {
                        var lbl = useFullname ? l.native_name : l.code.toUpperCase();
                        html += '<a href="#" style="padding:6px 12px;border:1px solid #ddd;border-radius:3px;background:#fff;color:#555;text-decoration:none;font-size:13px;display:inline-flex;align-items:center;gap:6px;">';
                        if (showFlags) html += getFlagHtml(l.country, l.emoji, flagType, lbl);
                        if (showNames) html += '<span>' + lbl + '</span>';
                        html += '</a>';
                    });
                    html += '</div>';
                }
                html += '</div>';
                $('#gml-button-preview').html(html);
            }
        });
        </script>
        <?php
    }

    // ── Tab: Translations ────────────────────────────────────────────────────

    private function render_translations_tab() {
        global $wpdb;

        $action_result = GML_Translation_Controls::handle_request( wp_unslash( $_POST ) );
        if ( is_wp_error( $action_result ) ) {
            echo '<div class="notice notice-error"><p>' . esc_html( __( $action_result->get_error_message(), 'gml-translate' ) ) . '</p></div>';
        } elseif ( $action_result === true ) {
            $message = isset( $_POST['gml_cache_action'] )
                ? __( 'Page cache refreshed. Saved translations and queue items were preserved.', 'gml-translate' )
                : __( 'Translation controls updated.', 'gml-translate' );
            echo '<div class="notice notice-success"><p>' . esc_html( $message ) . '</p></div>';
        }

        $languages           = get_option('gml_languages', []);
        $wp_locale           = get_locale();
        $source_lang         = get_option('gml_source_lang', substr($wp_locale, 0, 2));
        if ( class_exists( 'GML_Language_Utils' ) ) {
            $source_lang = GML_Language_Utils::normalize_code( $source_lang ) ?: substr($wp_locale, 0, 2);
        }
        $is_paused           = get_option('gml_translation_paused', false);
        $ai_translation_on   = GML_Translation_State::ai_available();
        $is_enabled          = GML_Translation_State::multilingual_enabled() && $ai_translation_on;
        $available_languages = $this->get_available_languages();
        $source_country      = self::get_country_from_locale($source_lang, $wp_locale);
        $source_native       = $available_languages[$source_lang]['native'] ?? strtoupper($source_lang);
        $queue_pending       = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}gml_queue WHERE status IN ('pending','processing')");
        $total_failed        = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}gml_queue WHERE status='failed'");
        $circuit             = GML_Queue_Processor::get_circuit_breaker();
        $safety_paused       = ! empty( $circuit );
        $failure_summary     = $total_failed > 0 ? GML_Queue_Processor::get_failure_summary( '', 3 ) : [];

        $state_labels = [
            'unavailable' => __( 'AI unavailable', 'gml-translate' ),
            'safety_paused' => __( 'Safety paused', 'gml-translate' ),
            'paused' => __( 'Paused', 'gml-translate' ),
            'pausing' => __( 'Pausing after current batch', 'gml-translate' ),
            'processing' => __( 'Processing batch', 'gml-translate' ),
            'idle' => __( 'Idle', 'gml-translate' ),
            'scheduled' => __( 'Scheduled', 'gml-translate' ),
            'not_scheduled' => __( 'Not scheduled', 'gml-translate' ),
            'overdue' => __( 'Schedule overdue', 'gml-translate' ),
            'scanning' => __( 'Scanning', 'gml-translate' ),
            'blocked' => __( 'Blocked', 'gml-translate' ),
            'completed' => __( 'Completed', 'gml-translate' ),
            'stopped' => __( 'Stopped', 'gml-translate' ),
        ];
        $queue_status = GML_Translation_Controls::queue_status( '', $queue_pending );
        $crawl_status = GML_Content_Crawler::get_status();
        $sample = GML_Translation_Controls::sample_status();
        $sample_running = $sample['active'];
        $can_start = $is_enabled && ! $safety_paused && ! $sample_running && ! empty( $languages );
        $can_resume_sample = $is_enabled && ! $safety_paused && $sample['valid'];
        ?>
        <style>
        .gml-translation-toolbar,.gml-translation-actions{display:flex;align-items:center;gap:10px;flex-wrap:wrap}
        .gml-translation-toolbar{justify-content:space-between;margin:16px 0}
        .gml-translation-toolbar h2{margin:0}
        .gml-translation-actions form{margin:0}
        .gml-translation-actions .dashicons,.gml-content-scan .dashicons{vertical-align:middle;margin-right:4px}
        .gml-translation-summary{display:flex;gap:24px;flex-wrap:wrap;padding:12px 0;border-top:1px solid #dcdcde}
        .gml-translation-summary dl{margin:0;min-width:140px}
        .gml-translation-summary dt{color:#50575e;font-size:12px}
        .gml-translation-summary dd{margin:4px 0 0;font-weight:600}
        .gml-content-scan{display:flex;justify-content:space-between;gap:16px;align-items:center;flex-wrap:wrap;padding:16px 0;margin-bottom:20px;border-top:1px solid #dcdcde;border-bottom:1px solid #dcdcde}
        .gml-content-scan h3{margin:0 0 6px}
        .gml-scan-progress{display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-top:8px}
        .gml-scan-progress progress{width:220px;max-width:100%;height:10px}
        .gml-translation-table{max-width:100%;overflow-x:auto;background:#fff;border:1px solid #dcdcde}
        .gml-translation-table table{min-width:1100px}
        .gml-translation-table td:first-child>div{flex-wrap:wrap}
        .gml-translation-table tr{display:table-row}
        .gml-translation-table th,.gml-translation-table td{display:table-cell}
        .gml-content-scan button[hidden]{display:none!important}
        .gml-translation-table .button{min-width:34px;height:32px;padding:0 5px}
        .gml-translation-table .dashicons{line-height:30px}
        .gml-translation-danger{margin:24px 0;padding-top:16px;border-top:1px solid #d63638}
        .gml-translation-danger h3{color:#b32d2e}
        .gml-translation-danger .gml-danger-actions{display:flex;gap:8px;flex-wrap:wrap}
        </style>
        <div class="gml-translation-toolbar">
            <h2><?php esc_html_e( 'Translation Queue', 'gml-translate' ); ?></h2>
            <div class="gml-translation-actions">
                <form method="post">
                    <?php wp_nonce_field( 'gml_translation_action', 'gml_translation_nonce' ); ?>
                    <?php if ( $is_enabled && ! $is_paused && ( ! $sample_running || ( ! $sample['paused'] && $queue_status['next_run'] ) ) ): ?>
                        <button type="submit" name="gml_global_action" value="pause_all" class="button">
                            <span class="dashicons dashicons-controls-pause" aria-hidden="true"></span><?php esc_html_e( 'Pause Translation', 'gml-translate' ); ?>
                        </button>
                    <?php elseif ( $sample_running ): ?>
                        <button type="submit" name="gml_global_action" value="resume_sample" class="button button-primary" <?php disabled( ! $can_resume_sample ); ?>>
                            <span class="dashicons dashicons-controls-play" aria-hidden="true"></span><?php echo esc_html( $sample['remaining'] ? __( 'Resume Sample', 'gml-translate' ) : __( 'Finish Sample', 'gml-translate' ) ); ?>
                        </button>
                    <?php else: ?>
                        <button type="submit" name="gml_global_action" value="start_all" class="button button-primary" <?php disabled( ! $can_start ); ?>>
                            <span class="dashicons dashicons-controls-play" aria-hidden="true"></span><?php esc_html_e( 'Start Translation', 'gml-translate' ); ?>
                        </button>
                    <?php endif; ?>
                </form>
                <form method="post">
                    <?php wp_nonce_field( 'gml_cache_action', 'gml_cache_nonce' ); ?>
                    <button type="submit" name="gml_cache_action" value="refresh_page_cache" class="button">
                        <span class="dashicons dashicons-update" aria-hidden="true"></span><?php esc_html_e( 'Refresh Page Cache', 'gml-translate' ); ?>
                    </button>
                </form>
            </div>
        </div>
        <div class="gml-translation-summary" aria-live="polite">
            <dl><dt><?php esc_html_e( 'Queue Status', 'gml-translate' ); ?></dt><dd id="gml-queue-state"><?php echo esc_html( $state_labels[$queue_status['state']] ); ?></dd></dl>
            <dl><dt><?php esc_html_e( 'Pending Segments', 'gml-translate' ); ?></dt><dd><?php echo esc_html( number_format_i18n( $queue_pending ) ); ?></dd></dl>
            <dl><dt><?php esc_html_e( 'Failed Segments', 'gml-translate' ); ?></dt><dd><?php echo esc_html( number_format_i18n( $total_failed ) ); ?></dd></dl>
            <dl><dt><?php esc_html_e( 'Last Worker Activity', 'gml-translate' ); ?></dt><dd id="gml-queue-last"><?php echo esc_html( $queue_status['last_activity'] ? wp_date( 'Y-m-d H:i:s', $queue_status['last_activity'] ) : __( 'Not recorded yet', 'gml-translate' ) ); ?></dd></dl>
        </div>
        <div class="gml-content-scan">
            <div>
                <h3><?php esc_html_e( 'Content Scan', 'gml-translate' ); ?></h3>
                <span id="gml-scan-state"><?php echo esc_html( $state_labels[$crawl_status['state']] ); ?></span>
                <div class="gml-scan-progress">
                    <progress id="gml-crawl-bar" max="100" value="<?php echo esc_attr( $crawl_status['percent'] ); ?>"></progress>
                    <span id="gml-crawl-text"><?php echo esc_html( $crawl_status['processed'] . ' / ' . $crawl_status['total'] ); ?></span>
                </div>
            </div>
            <div>
                <button type="button" id="gml-crawl-stop" class="button" <?php echo ! $crawl_status['running'] ? 'hidden' : ''; ?>><span class="dashicons dashicons-controls-stop" aria-hidden="true"></span><?php esc_html_e( 'Stop Scan', 'gml-translate' ); ?></button>
                <button type="button" id="gml-crawl-start" class="button" <?php echo $crawl_status['running'] ? 'hidden' : ''; ?> <?php disabled( ! $can_start ); ?>><span class="dashicons dashicons-search" aria-hidden="true"></span><?php esc_html_e( 'Scan Website Content', 'gml-translate' ); ?></button>
            </div>
        </div>
        <div id="gml-scan-error" class="notice notice-error inline" hidden role="alert"><p></p></div>

        <?php if ( $sample_running ): ?>
            <div class="notice notice-info inline"><p>
                <strong><?php printf( esc_html__( 'Limited sample: %1$s, %2$d of %3$d items remaining.', 'gml-translate' ), esc_html( strtoupper( $sample['language'] ) ), (int) $sample['remaining'], (int) $sample['total'] ); ?></strong>
                <?php esc_html_e( 'Other queue items and content scanning are on hold. The queue pauses automatically when this sample finishes.', 'gml-translate' ); ?>
                <?php if ( ! $sample['valid'] ): ?><br><?php esc_html_e( 'No valid limited sample is available for an enabled language.', 'gml-translate' ); ?><?php endif; ?>
            </p></div>
        <?php endif; ?>

        <?php if (!$ai_translation_on): ?>
            <div class="notice notice-warning inline"><p><?php _e('AI Translation is currently unavailable, so no new translation jobs will run. Existing multilingual URLs and saved translations remain available while the multilingual site is enabled.', 'gml-translate'); ?></p></div>
        <?php endif; ?>

        <?php if ($safety_paused): ?>
            <div class="notice notice-error inline" style="margin:0 0 20px;"><p>
                <strong><?php _e('Translation safety pause is active.', 'gml-translate'); ?></strong>
                <?php echo esc_html( $circuit['message'] ?? __( 'The AI provider, model, or failed queue must be verified before more API calls are made.', 'gml-translate' ) ); ?>
                <?php if ( ! empty( $circuit['engine'] ) || ! empty( $circuit['model'] ) ) : ?>
                    <br><?php printf( esc_html__( 'Saved provider: %1$s; model: %2$s.', 'gml-translate' ), esc_html( strtoupper( $circuit['engine'] ?? '' ) ), esc_html( $circuit['model'] ?? '' ) ); ?>
                <?php endif; ?>
                <br><a href="<?php echo esc_url( admin_url( 'admin.php?page=gml-translate&tab=settings' ) ); ?>"><?php _e('Test the saved AI connection', 'gml-translate'); ?></a>.
                <?php _e('A successful test clears the breaker but does not resume the queue automatically.', 'gml-translate'); ?>
            </p></div>
        <?php endif; ?>

        <?php if ($total_failed > 0): ?>
        <!-- Failed Items Banner -->
        <div style="background:#fef3cd;border:1px solid #ffc107;border-radius:4px;padding:12px 20px;margin-bottom:20px;">
            <div>
                <strong style="color:#856404;">⚠ <?php echo $total_failed; ?> <?php _e('failed translations', 'gml-translate'); ?></strong>
                <span style="color:#856404;font-size:13px;margin-left:8px;"><?php _e('Retry All is disabled to protect API quota. After a successful connection test, retry one language in samples of at most 25.', 'gml-translate'); ?></span>
            </div>
            <?php if ( ! empty( $failure_summary ) ) : ?>
                <ul style="margin:8px 0 0 18px;color:#6c5400;">
                    <?php foreach ( $failure_summary as $failure ) : ?>
                        <li><?php echo esc_html( number_format_i18n( (int) $failure->item_count ) . ' × ' . ( $failure->error_message ?: __( 'Unknown error', 'gml-translate' ) ) ); ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <?php if (empty($languages)): ?>
            <div class="notice notice-warning"><p>
                <strong><?php _e('No languages configured yet.', 'gml-translate'); ?></strong>
                <a href="<?php echo esc_url(admin_url('admin.php?page=gml-translate&tab=settings')); ?>"><?php _e('Add a language →', 'gml-translate'); ?></a>
            </p></div>
        <?php else: ?>

        <div class="gml-translation-table">
            <table class="wp-list-table widefat fixed" style="border:none;margin:0;">
                <thead>
                    <tr style="background:#f9f9f9;">
                        <th style="padding:14px 20px;border-bottom:1px solid #ddd;width:22%;"><?php _e('FROM / TO', 'gml-translate'); ?></th>
                        <th style="padding:14px 20px;border-bottom:1px solid #ddd;width:12%;text-align:center;"><?php esc_html_e('SEGMENTS', 'gml-translate'); ?></th>
                        <th style="padding:14px 20px;border-bottom:1px solid #ddd;width:12%;text-align:center;"><?php _e('TRANSLATED', 'gml-translate'); ?></th>
                        <th style="padding:14px 20px;border-bottom:1px solid #ddd;width:16%;text-align:center;"><?php _e('PROGRESS', 'gml-translate'); ?></th>
                        <th style="padding:14px 20px;border-bottom:1px solid #ddd;width:12%;text-align:center;"><?php _e('STATUS', 'gml-translate'); ?></th>
                        <th style="padding:14px 20px;border-bottom:1px solid #ddd;width:26%;text-align:right;"><?php _e('ACTIONS', 'gml-translate'); ?></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($languages as $lang):
                    $lang_code    = $lang['code'];
                    $lang_name    = $lang['native_name'] ?? strtoupper($lang_code);
                    $lang_country = $lang['country'] ?? ($available_languages[$lang_code]['country'] ?? $lang_code);
                    $lang_paused  = $lang['paused'] ?? false;
                    $lang_running = $is_enabled && !$is_paused && !$lang_paused;

                    $translated_words = (int) $wpdb->get_var($wpdb->prepare(
                        "SELECT COUNT(*) FROM {$wpdb->prefix}gml_index WHERE target_lang=%s AND status IN ('auto','manual')", $lang_code
                    ));
                    $pending_count = (int) $wpdb->get_var($wpdb->prepare(
                        "SELECT COUNT(*) FROM {$wpdb->prefix}gml_queue WHERE target_lang=%s AND status IN ('pending','processing')", $lang_code
                    ));
                    $failed_count = (int) $wpdb->get_var($wpdb->prepare(
                        "SELECT COUNT(*) FROM {$wpdb->prefix}gml_queue WHERE target_lang=%s AND status='failed'", $lang_code
                    ));
                    $total_words = $translated_words + $pending_count + $failed_count;
                    $pct = $total_words > 0 ? min(100, round($translated_words / $total_words * 100)) : 0;
                    $bar_color = $pct >= 100 ? '#00a32a' : '#2271b1';
                ?>
                <tr style="border-bottom:1px solid #f0f0f0;">
                    <td style="padding:16px 20px;">
                        <div style="display:flex;align-items:center;gap:8px;">
                            <?php echo $this->flag_img($source_country, $source_native, 28); ?>
                            <span style="font-size:13px;font-weight:600;"><?php echo esc_html($source_native); ?></span>
                            <span style="color:#bbb;font-size:16px;">→</span>
                            <?php echo $this->flag_img($lang_country, $lang_name, 28); ?>
                            <span style="font-size:13px;font-weight:600;"><?php echo esc_html($lang_name); ?></span>
                        </div>
                    </td>
                    <td style="padding:16px 20px;text-align:center;">
                        <strong style="font-size:16px;"><?php echo number_format($total_words); ?></strong>
                        <?php if ($pending_count > 0): ?>
                            <br><span style="font-size:11px;color:#888;"><?php echo $pending_count; ?> <?php _e('pending', 'gml-translate'); ?></span>
                        <?php endif; ?>
                        <?php if ($failed_count > 0): ?>
                            <br><span style="font-size:11px;color:#d63638;cursor:pointer;" class="gml-retry-lang" data-lang="<?php echo esc_attr($lang_code); ?>"><?php echo $failed_count; ?> <?php _e('failed', 'gml-translate'); ?> ↻</span>
                        <?php endif; ?>
                    </td>
                    <td style="padding:16px 20px;text-align:center;">
                        <strong style="font-size:16px;color:#2271b1;"><?php echo number_format($translated_words); ?></strong>
                    </td>
                    <td style="padding:16px 20px;">
                        <div style="text-align:center;font-size:13px;font-weight:600;margin-bottom:5px;"><?php echo $pct; ?>%</div>
                        <div style="height:6px;background:#e0e0e0;border-radius:3px;overflow:hidden;">
                            <div style="width:<?php echo $pct; ?>%;height:100%;background:<?php echo $bar_color; ?>;transition:width .3s;"></div>
                        </div>
                    </td>
                    <td style="padding:16px 20px;text-align:center;">
                        <?php $language_status = GML_Translation_Controls::queue_status( $lang_code, $pending_count ); ?>
                        <span><?php echo esc_html( $state_labels[$language_status['state']] ); ?></span>
                    </td>
                    <td style="padding:16px 20px;text-align:right;">
                        <div style="display:inline-flex;gap:6px;align-items:center;position:relative;">
                            <!-- Manage Translations button -->
                            <button type="button" class="button button-small gml-open-editor" data-lang="<?php echo esc_attr($lang_code); ?>" data-lang-name="<?php echo esc_attr($lang_name); ?>" title="<?php esc_attr_e('Manage Translations', 'gml-translate'); ?>" aria-label="<?php esc_attr_e('Manage Translations', 'gml-translate'); ?>"><span class="dashicons dashicons-edit" aria-hidden="true"></span></button>

                            <form method="post" style="margin:0;">
                                <?php wp_nonce_field('gml_translation_action', 'gml_translation_nonce'); ?>
                                <input type="hidden" name="gml_lang_code" value="<?php echo esc_attr($lang_code); ?>">
                                <?php if ($lang_running): ?>
                                    <button type="submit" name="gml_lang_action" value="pause_lang" class="button button-small" title="<?php esc_attr_e('Pause', 'gml-translate'); ?>" aria-label="<?php esc_attr_e('Pause', 'gml-translate'); ?>"><span class="dashicons dashicons-controls-pause" aria-hidden="true"></span></button>
                                <?php else: ?>
                                    <?php if ( $sample_running && $sample['language'] === $lang_code ): ?>
                                    <button type="submit" name="gml_global_action" value="resume_sample" class="button button-small button-primary" title="<?php esc_attr_e('Resume Sample', 'gml-translate'); ?>" aria-label="<?php esc_attr_e('Resume Sample', 'gml-translate'); ?>" <?php disabled( ! $can_resume_sample ); ?>><span class="dashicons dashicons-controls-play" aria-hidden="true"></span></button>
                                    <?php else: ?>
                                    <button type="submit" name="gml_lang_action" value="start_lang" class="button button-small button-primary" title="<?php esc_attr_e('Start', 'gml-translate'); ?>" aria-label="<?php esc_attr_e('Start', 'gml-translate'); ?>" <?php disabled( ! $can_start ); ?>><span class="dashicons dashicons-controls-play" aria-hidden="true"></span></button>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <script>
        window.gmlTranslationStates = <?php echo wp_json_encode( $state_labels ); ?>;
        (function(){

            // Retry failed buttons
            document.querySelectorAll('.gml-retry-lang').forEach(function(btn) {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    var lang = btn.dataset.lang;
                    if (!window.confirm('<?php echo esc_js( __( 'Retry at most 25 failed items for this language? Watch the result before retrying another sample.', 'gml-translate' ) ); ?>')) {
                        return;
                    }
                    btn.style.opacity = '0.5';
                    jQuery.post(gmlEditor.ajaxUrl, {action:'gml_retry_failed', lang:lang, nonce:gmlEditor.nonce}, function(r) {
                        if (r.success) {
                            location.reload();
                        } else {
                            alert((r.data && r.data.message) ? r.data.message : r.data || '<?php echo esc_js( __( 'The retry sample could not start.', 'gml-translate' ) ); ?>');
                            btn.style.opacity = '1';
                        }
                    });
                });
            });
        })();
        </script>

        <!-- Translation Editor Modal -->
        <div id="gml-editor-modal" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;z-index:100000;background:rgba(0,0,0,.6);">
            <div style="position:absolute;top:32px;left:50%;transform:translateX(-50%);width:90%;max-width:1100px;max-height:calc(100vh - 64px);background:#fff;border-radius:8px;display:flex;flex-direction:column;overflow:hidden;box-shadow:0 8px 32px rgba(0,0,0,.3);">
                <div style="padding:16px 24px;border-bottom:1px solid #ddd;display:flex;justify-content:space-between;align-items:center;background:#f9f9f9;">
                    <h2 id="gml-editor-title" style="margin:0;font-size:16px;"></h2>
                    <button type="button" id="gml-editor-close" style="background:none;border:none;font-size:24px;cursor:pointer;color:#666;padding:0 4px;">×</button>
                </div>
                <div style="padding:12px 24px;border-bottom:1px solid #eee;display:flex;gap:12px;align-items:center;flex-wrap:wrap;">
                    <input type="text" id="gml-editor-search" style="flex:1;min-width:200px;padding:6px 12px;border:1px solid #ddd;border-radius:4px;">
                    <select id="gml-editor-filter" style="padding:6px 12px;border:1px solid #ddd;border-radius:4px;">
                        <option value="all"><?php _e('All', 'gml-translate'); ?></option>
                        <option value="auto"><?php _e('Auto', 'gml-translate'); ?></option>
                        <option value="manual"><?php _e('Manual', 'gml-translate'); ?></option>
                    </select>
                </div>
                <div id="gml-editor-body" style="flex:1;overflow-y:auto;padding:0;min-height:300px;"></div>
                <div id="gml-editor-footer" style="padding:12px 24px;border-top:1px solid #ddd;display:flex;justify-content:space-between;align-items:center;background:#f9f9f9;">
                    <span id="gml-editor-info" style="font-size:13px;color:#666;"></span>
                    <div style="display:flex;gap:8px;">
                        <button type="button" id="gml-editor-prev" class="button button-small">←</button>
                        <button type="button" id="gml-editor-next" class="button button-small">→</button>
                    </div>
                </div>
            </div>
        </div>
        <section class="gml-translation-danger">
            <h3><?php esc_html_e( 'Delete Saved Translations', 'gml-translate' ); ?></h3>
            <p><?php esc_html_e( 'Deleting a saved translation cannot be undone. Review individual records before deleting.', 'gml-translate' ); ?></p>
            <div class="gml-danger-actions">
                <?php foreach ( $languages as $language ): ?>
                    <button type="button" class="button gml-open-editor" data-lang="<?php echo esc_attr( $language['code'] ); ?>" data-lang-name="<?php echo esc_attr( $language['native_name'] ?? $language['code'] ); ?>"><?php echo esc_html( sprintf( __( 'Review %s translations', 'gml-translate' ), $language['native_name'] ?? $language['code'] ) ); ?></button>
                <?php endforeach; ?>
            </div>
        </section>

        <?php endif; ?>
        <?php
    }

    // ── Tab: Exclusion Rules ─────────────────────────────────────────────────

    private function render_exclusions_tab() {
        // Handle save
        if (isset($_POST['gml_save_exclusions']) && check_admin_referer('gml_exclusion_rules', 'gml_exclusion_nonce')) {
            $rules = [];
            $types   = $_POST['rule_type']    ?? [];
            $values  = $_POST['rule_value']   ?? [];
            $notes   = $_POST['rule_note']    ?? [];
            $enabled = $_POST['rule_enabled'] ?? [];
            for ($i = 0; $i < count($types); $i++) {
                if (empty($values[$i])) continue;
                $rules[] = [
                    'type'    => $types[$i],
                    'value'   => $values[$i],
                    'note'    => $notes[$i] ?? '',
                    'enabled' => isset($enabled[$i]),
                ];
            }
            GML_Exclusion_Rules::save_rules($rules);
            echo '<div class="notice notice-success"><p>' . __('Exclusion rules saved!', 'gml-translate') . '</p></div>';
        }

        $rules = (new GML_Exclusion_Rules())->get_rules();
        ?>
        <h2><?php _e('Translation Exclusion Rules', 'gml-translate'); ?></h2>
        <p class="description" style="margin-bottom:16px;">
            <?php _e('Define rules to exclude specific pages or elements from translation. Similar to Weglot\'s exclusion feature.', 'gml-translate'); ?>
        </p>

        <form method="post">
            <?php wp_nonce_field('gml_exclusion_rules', 'gml_exclusion_nonce'); ?>
            <input type="hidden" name="gml_save_exclusions" value="1" />

            <table class="wp-list-table widefat fixed" id="gml-exclusion-table">
                <thead>
                    <tr>
                        <th style="width:5%;text-align:center;"><?php _e('On', 'gml-translate'); ?></th>
                        <th style="width:20%;"><?php _e('Type', 'gml-translate'); ?></th>
                        <th style="width:35%;"><?php _e('Value', 'gml-translate'); ?></th>
                        <th style="width:30%;"><?php _e('Note', 'gml-translate'); ?></th>
                        <th style="width:10%;text-align:center;"><?php _e('Remove', 'gml-translate'); ?></th>
                    </tr>
                </thead>
                <tbody id="gml-exclusion-rows">
                    <?php if (empty($rules)): ?>
                    <tr class="gml-exclusion-row">
                        <td style="text-align:center;"><input type="checkbox" name="rule_enabled[0]" checked /></td>
                        <td>
                            <select name="rule_type[]" style="width:100%;">
                                <option value="url_is"><?php _e('URL is exactly', 'gml-translate'); ?></option>
                                <option value="url_starts"><?php _e('URL starts with', 'gml-translate'); ?></option>
                                <option value="url_contains"><?php _e('URL contains', 'gml-translate'); ?></option>
                                <option value="url_regex"><?php _e('URL matches regex', 'gml-translate'); ?></option>
                                <option value="selector"><?php _e('CSS selector', 'gml-translate'); ?></option>
                            </select>
                        </td>
                        <td><input type="text" name="rule_value[]" style="width:100%;" placeholder="/checkout/" /></td>
                        <td><input type="text" name="rule_note[]" style="width:100%;" placeholder="<?php esc_attr_e('Optional note', 'gml-translate'); ?>" /></td>
                        <td style="text-align:center;"><button type="button" class="button button-small gml-remove-rule" style="color:#d63638;">×</button></td>
                    </tr>
                    <?php else: foreach ($rules as $i => $rule): ?>
                    <tr class="gml-exclusion-row">
                        <td style="text-align:center;"><input type="checkbox" name="rule_enabled[<?php echo $i; ?>]" <?php checked(!empty($rule['enabled'])); ?> /></td>
                        <td>
                            <select name="rule_type[]" style="width:100%;">
                                <option value="url_is" <?php selected($rule['type'], 'url_is'); ?>><?php _e('URL is exactly', 'gml-translate'); ?></option>
                                <option value="url_starts" <?php selected($rule['type'], 'url_starts'); ?>><?php _e('URL starts with', 'gml-translate'); ?></option>
                                <option value="url_contains" <?php selected($rule['type'], 'url_contains'); ?>><?php _e('URL contains', 'gml-translate'); ?></option>
                                <option value="url_regex" <?php selected($rule['type'], 'url_regex'); ?>><?php _e('URL matches regex', 'gml-translate'); ?></option>
                                <option value="selector" <?php selected($rule['type'], 'selector'); ?>><?php _e('CSS selector', 'gml-translate'); ?></option>
                            </select>
                        </td>
                        <td><input type="text" name="rule_value[]" style="width:100%;" value="<?php echo esc_attr($rule['value']); ?>" /></td>
                        <td><input type="text" name="rule_note[]" style="width:100%;" value="<?php echo esc_attr($rule['note'] ?? ''); ?>" /></td>
                        <td style="text-align:center;"><button type="button" class="button button-small gml-remove-rule" style="color:#d63638;">×</button></td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>

            <p style="margin-top:12px;">
                <button type="button" id="gml-add-exclusion" class="button button-secondary">+ <?php _e('Add Rule', 'gml-translate'); ?></button>
            </p>

            <?php submit_button(__('Save Exclusion Rules', 'gml-translate')); ?>
        </form>

        <hr style="margin:30px 0;">
        <h3><?php _e('Examples', 'gml-translate'); ?></h3>
        <table class="form-table" style="max-width:700px;">
            <tr><td><code>URL is exactly</code></td><td><code>/checkout/</code></td><td><?php _e('Exclude the checkout page', 'gml-translate'); ?></td></tr>
            <tr><td><code>URL starts with</code></td><td><code>/my-account/</code></td><td><?php _e('Exclude all account pages', 'gml-translate'); ?></td></tr>
            <tr><td><code>URL contains</code></td><td><code>cart</code></td><td><?php _e('Exclude any URL with "cart"', 'gml-translate'); ?></td></tr>
            <tr><td><code>CSS selector</code></td><td><code>.no-translate</code></td><td><?php _e('Exclude elements with this class', 'gml-translate'); ?></td></tr>
            <tr><td><code>CSS selector</code></td><td><code>#legal-notice</code></td><td><?php _e('Exclude element with this ID', 'gml-translate'); ?></td></tr>
        </table>

        <script>
        (function(){
            var idx = <?php echo max(count($rules), 1); ?>;
            document.getElementById('gml-add-exclusion').addEventListener('click', function() {
                var tbody = document.getElementById('gml-exclusion-rows');
                var tr = document.createElement('tr');
                tr.className = 'gml-exclusion-row';
                tr.innerHTML = '<td style="text-align:center;"><input type="checkbox" name="rule_enabled[' + idx + ']" checked /></td>'
                    + '<td><select name="rule_type[]" style="width:100%;"><option value="url_is"><?php echo esc_js(__('URL is exactly', 'gml-translate')); ?></option><option value="url_starts"><?php echo esc_js(__('URL starts with', 'gml-translate')); ?></option><option value="url_contains"><?php echo esc_js(__('URL contains', 'gml-translate')); ?></option><option value="url_regex"><?php echo esc_js(__('URL matches regex', 'gml-translate')); ?></option><option value="selector"><?php echo esc_js(__('CSS selector', 'gml-translate')); ?></option></select></td>'
                    + '<td><input type="text" name="rule_value[]" style="width:100%;" placeholder="/checkout/" /></td>'
                    + '<td><input type="text" name="rule_note[]" style="width:100%;" /></td>'
                    + '<td style="text-align:center;"><button type="button" class="button button-small gml-remove-rule" style="color:#d63638;">×</button></td>';
                tbody.appendChild(tr);
                idx++;
            });
            document.addEventListener('click', function(e) {
                if (e.target.classList.contains('gml-remove-rule')) {
                    e.target.closest('tr').remove();
                }
            });
        })();
        </script>
        <?php
    }

    // ── Tab: Glossary ────────────────────────────────────────────────────────

    private function render_glossary_tab() {
        // Handle save
        if (isset($_POST['gml_save_glossary']) && check_admin_referer('gml_glossary_rules', 'gml_glossary_nonce')) {
            $rules = [];
            $sources = $_POST['glossary_source']  ?? [];
            $targets = $_POST['glossary_target']  ?? [];
            $langs   = $_POST['glossary_lang']    ?? [];
            $enabled = $_POST['glossary_enabled'] ?? [];
            for ($i = 0; $i < count($sources); $i++) {
                if (empty($sources[$i])) continue;
                $rules[] = [
                    'source'  => $sources[$i],
                    'target'  => $targets[$i] ?? '',
                    'lang'    => $langs[$i] ?? 'all',
                    'enabled' => isset($enabled[$i]),
                ];
            }
            GML_Glossary::save_rules($rules);
            echo '<div class="notice notice-success"><p>' . __('Glossary rules saved!', 'gml-translate') . '</p></div>';
        }

        // Also handle protected terms save
        if (isset($_POST['gml_save_protected']) && check_admin_referer('gml_protected_terms', 'gml_protected_nonce')) {
            $terms = array_filter(array_map('trim', explode("\n", $_POST['gml_protected_terms'] ?? '')));
            update_option('gml_protected_terms', $terms, false);
            echo '<div class="notice notice-success"><p>' . __('Protected terms saved!', 'gml-translate') . '</p></div>';
        }

        $glossary_rules  = GML_Glossary::get_rules();
        $protected_terms = get_option('gml_protected_terms', ['GML', 'WordPress', 'WooCommerce', 'Gemini']);
        $languages       = get_option('gml_languages', []);
        ?>

        <h2><?php _e('Protected Terms (Never Translate)', 'gml-translate'); ?></h2>
        <p class="description" style="margin-bottom:12px;">
            <?php _e('These terms will never be translated. One per line. Brand names, product names, etc.', 'gml-translate'); ?>
        </p>
        <form method="post">
            <?php wp_nonce_field('gml_protected_terms', 'gml_protected_nonce'); ?>
            <input type="hidden" name="gml_save_protected" value="1" />
            <textarea name="gml_protected_terms" rows="6" class="large-text code" style="max-width:500px;"><?php
                echo esc_textarea(implode("\n", $protected_terms));
            ?></textarea>
            <?php submit_button(__('Save Protected Terms', 'gml-translate')); ?>
        </form>

        <hr style="margin:30px 0;">

        <h2><?php _e('Glossary Rules (Always Translate X as Y)', 'gml-translate'); ?></h2>
        <p class="description" style="margin-bottom:16px;">
            <?php _e('Define how specific terms should always be translated. These rules are injected into the AI prompt to ensure consistent translations.', 'gml-translate'); ?>
        </p>

        <form method="post">
            <?php wp_nonce_field('gml_glossary_rules', 'gml_glossary_nonce'); ?>
            <input type="hidden" name="gml_save_glossary" value="1" />

            <table class="wp-list-table widefat fixed" id="gml-glossary-table">
                <thead>
                    <tr>
                        <th style="width:5%;text-align:center;"><?php _e('On', 'gml-translate'); ?></th>
                        <th style="width:25%;"><?php _e('Source Term', 'gml-translate'); ?></th>
                        <th style="width:25%;"><?php _e('Translate As', 'gml-translate'); ?></th>
                        <th style="width:20%;"><?php _e('Language', 'gml-translate'); ?></th>
                        <th style="width:10%;text-align:center;"><?php _e('Remove', 'gml-translate'); ?></th>
                    </tr>
                </thead>
                <tbody id="gml-glossary-rows">
                    <?php if (empty($glossary_rules)): ?>
                    <tr class="gml-glossary-row">
                        <td style="text-align:center;"><input type="checkbox" name="glossary_enabled[0]" checked /></td>
                        <td><input type="text" name="glossary_source[]" style="width:100%;" placeholder="Add to Cart" /></td>
                        <td><input type="text" name="glossary_target[]" style="width:100%;" placeholder="Agregar al carrito" /></td>
                        <td>
                            <select name="glossary_lang[]" style="width:100%;">
                                <option value="all"><?php _e('All Languages', 'gml-translate'); ?></option>
                                <?php foreach ($languages as $lang): ?>
                                <option value="<?php echo esc_attr($lang['code']); ?>"><?php echo esc_html($lang['native_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                        <td style="text-align:center;"><button type="button" class="button button-small gml-remove-glossary" style="color:#d63638;">×</button></td>
                    </tr>
                    <?php else: foreach ($glossary_rules as $i => $rule): ?>
                    <tr class="gml-glossary-row">
                        <td style="text-align:center;"><input type="checkbox" name="glossary_enabled[<?php echo $i; ?>]" <?php checked(!empty($rule['enabled'])); ?> /></td>
                        <td><input type="text" name="glossary_source[]" style="width:100%;" value="<?php echo esc_attr($rule['source']); ?>" /></td>
                        <td><input type="text" name="glossary_target[]" style="width:100%;" value="<?php echo esc_attr($rule['target']); ?>" /></td>
                        <td>
                            <select name="glossary_lang[]" style="width:100%;">
                                <option value="all" <?php selected($rule['lang'], 'all'); ?>><?php _e('All Languages', 'gml-translate'); ?></option>
                                <?php foreach ($languages as $lang): ?>
                                <option value="<?php echo esc_attr($lang['code']); ?>" <?php selected($rule['lang'], $lang['code']); ?>><?php echo esc_html($lang['native_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                        <td style="text-align:center;"><button type="button" class="button button-small gml-remove-glossary" style="color:#d63638;">×</button></td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>

            <p style="margin-top:12px;">
                <button type="button" id="gml-add-glossary" class="button button-secondary">+ <?php _e('Add Rule', 'gml-translate'); ?></button>
            </p>

            <?php submit_button(__('Save Glossary Rules', 'gml-translate')); ?>
        </form>

        <hr style="margin:30px 0;">
        <h3><?php _e('How It Works', 'gml-translate'); ?></h3>
        <p><?php _e('Glossary rules are injected into the AI translation prompt. The AI is instructed to always translate the source term using the specified translation.', 'gml-translate'); ?></p>
        <p><?php _e('This is useful for:', 'gml-translate'); ?></p>
        <ul style="list-style:disc;margin-left:20px;">
            <li><?php _e('Industry-specific terminology that generic AI might translate incorrectly', 'gml-translate'); ?></li>
            <li><?php _e('Product names that should have specific translations per market', 'gml-translate'); ?></li>
            <li><?php _e('UI labels that need consistent translation across the site', 'gml-translate'); ?></li>
        </ul>
        <p class="description"><?php _e('Note: After adding glossary rules, clear the translation cache for affected languages to re-translate with the new rules.', 'gml-translate'); ?></p>

        <script>
        (function(){
            var idx = <?php echo max(count($glossary_rules), 1); ?>;
            var langOptions = '<option value="all"><?php echo esc_js(__('All Languages', 'gml-translate')); ?></option><?php foreach ($languages as $lang): ?><option value="<?php echo esc_attr($lang['code']); ?>"><?php echo esc_js($lang['native_name']); ?></option><?php endforeach; ?>';
            document.getElementById('gml-add-glossary').addEventListener('click', function() {
                var tbody = document.getElementById('gml-glossary-rows');
                var tr = document.createElement('tr');
                tr.className = 'gml-glossary-row';
                tr.innerHTML = '<td style="text-align:center;"><input type="checkbox" name="glossary_enabled[' + idx + ']" checked /></td>'
                    + '<td><input type="text" name="glossary_source[]" style="width:100%;" /></td>'
                    + '<td><input type="text" name="glossary_target[]" style="width:100%;" /></td>'
                    + '<td><select name="glossary_lang[]" style="width:100%;">' + langOptions + '</select></td>'
                    + '<td style="text-align:center;"><button type="button" class="button button-small gml-remove-glossary" style="color:#d63638;">×</button></td>';
                tbody.appendChild(tr);
                idx++;
            });
            document.addEventListener('click', function(e) {
                if (e.target.classList.contains('gml-remove-glossary')) {
                    e.target.closest('tr').remove();
                }
            });
        })();
        </script>
        <?php
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function flag_img($country, $alt, $size = 24) {
        $country = strtolower(esc_attr($country ?: 'un'));
        return '<img src="https://flagcdn.com/w40/' . $country . '.png" alt="' . esc_attr($alt) . '" '
             . 'style="width:' . $size . 'px;height:' . round($size * 0.67) . 'px;object-fit:cover;border-radius:2px;vertical-align:middle;" loading="lazy">';
    }

    private function add_language($data) {
        $languages = get_option('gml_languages', []);
        $lang_code = sanitize_text_field($data['lang_code']);
        $available_languages = $this->get_available_languages();
        $lang_info = $available_languages[$lang_code] ?? null;
        if (!$lang_info) return;
        $languages[] = [
            'code'        => $lang_code,
            'name'        => $lang_info['name'],
            'native_name' => $lang_info['native'],
            'flag'        => $lang_info['flag'],
            'country'     => $lang_info['country'],
            'url_prefix'  => '/' . $lang_code . '/',
            'enabled'     => true,
        ];
        update_option('gml_languages', $languages);
        $this->register_rewrite_rules_now();
        flush_rewrite_rules();
    }

    private function remove_language($lang_code) {
        $languages = get_option('gml_languages', []);
        $languages = array_values(array_filter($languages, function($l) use ($lang_code) { return $l['code'] !== $lang_code; }));
        update_option('gml_languages', $languages);
        $this->register_rewrite_rules_now();
        flush_rewrite_rules();
    }

    private function register_rewrite_rules_now() {
        $codes = [];
        foreach (get_option('gml_languages', []) as $lang) {
            if ($lang['enabled'] ?? true) $codes[] = $lang['code'];
        }
        if (empty($codes)) return;
        $pattern = implode('|', array_map('preg_quote', $codes));
        add_rewrite_rule("^({$pattern})/(.+?)/?$", 'index.php?gml_lang=$matches[1]&gml_path=$matches[2]', 'top');
        add_rewrite_rule("^({$pattern})/?$",        'index.php?gml_lang=$matches[1]',                      'top');
    }

    private function start_translation_process() {
        return GML_Translation_Controls::start() === true;
    }

    private function pause_translation_process() {
        GML_Translation_Controls::pause();
    }

    private function save_settings() {
        $was_multilingual = GML_Translation_State::multilingual_enabled();
        $was_ai_enabled   = GML_Translation_State::ai_translation_enabled();

        $previous_engine = get_option( 'gml_translation_engine', 'gemini' );
        $engine = sanitize_key( wp_unslash( $_POST['gml_translation_engine'] ?? 'gemini' ) );
        if ( ! in_array( $engine, [ 'gemini', 'deepseek' ], true ) ) {
            add_settings_error( 'gml_messages', 'gml_engine_invalid', __( 'Unknown AI provider. Settings were not saved.', 'gml-translate' ), 'error' );
            return;
        }
        update_option('gml_translation_engine', $engine);
        if ( get_option( 'gml_translation_engine' ) !== $engine ) {
            add_settings_error( 'gml_messages', 'gml_engine_save_failed', __( 'AI provider selection could not be saved. Check database writes before testing the connection.', 'gml-translate' ), 'error' );
            return;
        }

        $api_key_updated = false;
        $save_failed = false;

        if ($engine === 'deepseek') {
            // Save DeepSeek model and API base FIRST (needed for key validation)
            $model = sanitize_text_field($_POST['gml_deepseek_model'] ?? 'deepseek-chat');
            if (!empty($model)) {
                update_option('gml_deepseek_model', $model);
            }
            $submitted_api_base = trim( (string) ( $_POST['gml_deepseek_api_base'] ?? '' ) );
            $api_base = GML_Gemini_API::secure_base_url( $submitted_api_base, GML_Gemini_API::DEEPSEEK_API_BASE );
            if ( $submitted_api_base !== '' && untrailingslashit( $submitted_api_base ) === $api_base ) {
                update_option('gml_deepseek_api_base', $api_base, false);
            } elseif ( $submitted_api_base !== '' ) {
                add_settings_error('gml_messages', 'gml_api_base_invalid', __('The DeepSeek API URL was rejected. Only the official HTTPS host is allowed; the default endpoint will be used.', 'gml-translate'), 'error');
                delete_option('gml_deepseek_api_base');
            } else {
                delete_option('gml_deepseek_api_base');
            }
        }

        $field = $engine === 'deepseek' ? 'gml_deepseek_api_key' : 'gml_api_key';
        $submitted = $_POST[ $field ] ?? '';
        $api_key = is_string( $submitted ) ? trim( wp_unslash( $submitted ) ) : '';
        // Accept old, already-open forms without ever storing their mask as a key.
        if ( $api_key === str_repeat( '*', 32 ) ) $api_key = '';
        if ( $api_key !== '' || ! is_string( $submitted ) ) {
            $api_key_updated = GML_Gemini_API::save_api_key( $api_key, $engine );
            if ( $api_key_updated ) {
                add_settings_error( 'gml_messages', 'gml_api_key_saved', __( 'API key encrypted, saved, and read back successfully. Use Test Saved AI Connection to verify provider access. Translation remains paused.', 'gml-translate' ), 'success' );
            } else {
                $save_failed = true;
                add_settings_error( 'gml_messages', 'gml_api_key_storage_failed', __( 'API key save could not be verified. Check the key for whitespace, OpenSSL availability, and database writes. No connection test was sent.', 'gml-translate' ), 'error' );
            }
        }

        update_option('gml_source_lang', sanitize_text_field($_POST['gml_source_lang'] ?? 'en'));
        $multilingual = ! empty( $_POST['gml_multilingual_enabled'] );
        $ai_requested = ! empty( $_POST['gml_ai_translation_enabled'] );
        $changed      = GML_Translation_State::set_multilingual_enabled( $multilingual );
        $ai_enabled   = $ai_requested && GML_Translation_State::has_api_key();
        GML_Translation_State::set_ai_translation_enabled( $ai_enabled );

        if ( $ai_requested && ! $ai_enabled ) {
            $save_failed = true;
            add_settings_error( 'gml_messages', 'gml_ai_key_required', __( 'AI Translation was not enabled because the selected provider does not have a valid saved API key.', 'gml-translate' ), 'error' );
        }

        if ( ! $multilingual || ! $ai_enabled || $api_key_updated || $save_failed || $previous_engine !== $engine || ! $was_multilingual || ! $was_ai_enabled ) {
            GML_Translation_Controls::pause();
        }
        if ( ! $multilingual || ! $ai_enabled ) {
            GML_Content_Crawler::stop_crawl();
        }

        if ( $changed ) {
            update_option( 'gml_translation_flush_rewrite_rules', 1, false );
        }
        if (!$api_key_updated && !$save_failed) {
            add_settings_error('gml_messages', 'gml_settings_saved', __('Settings saved successfully!', 'gml-translate'), 'success');
        }
    }

    private function save_switcher_settings() {
        update_option('gml_switcher_is_dropdown',  isset($_POST['gml_switcher_is_dropdown']));
        update_option('gml_switcher_show_flags',   isset($_POST['gml_switcher_show_flags']));
        update_option('gml_switcher_flag_type',    sanitize_text_field($_POST['gml_switcher_flag_type'] ?? 'rectangle'));
        update_option('gml_switcher_show_names',   isset($_POST['gml_switcher_show_names']));
        update_option('gml_switcher_use_fullname', isset($_POST['gml_switcher_use_fullname']));
        update_option('gml_switcher_position',     sanitize_text_field($_POST['gml_switcher_position'] ?? 'none'));
        update_option('gml_switcher_custom_css',   wp_strip_all_tags($_POST['gml_switcher_custom_css'] ?? ''));
    }

    private function get_available_languages() {
        return [
            'en' => ['name'=>'English',      'native'=>'English',         'flag'=>'🇺🇸','country'=>'us'],
            'zh' => ['name'=>'Chinese',      'native'=>'中文',             'flag'=>'🇨🇳','country'=>'cn'],
            'es' => ['name'=>'Spanish',      'native'=>'Español',         'flag'=>'🇪🇸','country'=>'es'],
            'fr' => ['name'=>'French',       'native'=>'Français',        'flag'=>'🇫🇷','country'=>'fr'],
            'de' => ['name'=>'German',       'native'=>'Deutsch',         'flag'=>'🇩🇪','country'=>'de'],
            'ja' => ['name'=>'Japanese',     'native'=>'日本語',           'flag'=>'🇯🇵','country'=>'jp'],
            'ko' => ['name'=>'Korean',       'native'=>'한국어',           'flag'=>'🇰🇷','country'=>'kr'],
            'pt' => ['name'=>'Portuguese',   'native'=>'Português',       'flag'=>'🇵🇹','country'=>'pt'],
            'ru' => ['name'=>'Russian',      'native'=>'Русский',         'flag'=>'🇷🇺','country'=>'ru'],
            'ar' => ['name'=>'Arabic',       'native'=>'العربية',         'flag'=>'🇸🇦','country'=>'sa'],
            'hi' => ['name'=>'Hindi',        'native'=>'हिन्दी',           'flag'=>'🇮🇳','country'=>'in'],
            'it' => ['name'=>'Italian',      'native'=>'Italiano',        'flag'=>'🇮🇹','country'=>'it'],
            'nl' => ['name'=>'Dutch',        'native'=>'Nederlands',      'flag'=>'🇳🇱','country'=>'nl'],
            'pl' => ['name'=>'Polish',       'native'=>'Polski',          'flag'=>'🇵🇱','country'=>'pl'],
            'tr' => ['name'=>'Turkish',      'native'=>'Türkçe',          'flag'=>'🇹🇷','country'=>'tr'],
            'vi' => ['name'=>'Vietnamese',   'native'=>'Tiếng Việt',      'flag'=>'🇻🇳','country'=>'vn'],
            'th' => ['name'=>'Thai',         'native'=>'ไทย',             'flag'=>'🇹🇭','country'=>'th'],
            'id' => ['name'=>'Indonesian',   'native'=>'Bahasa Indonesia','flag'=>'🇮🇩','country'=>'id'],
            'ms' => ['name'=>'Malay',        'native'=>'Bahasa Melayu',   'flag'=>'🇲🇾','country'=>'my'],
            'tl' => ['name'=>'Filipino',     'native'=>'Filipino',        'flag'=>'🇵🇭','country'=>'ph'],
            'sv' => ['name'=>'Swedish',      'native'=>'Svenska',         'flag'=>'🇸🇪','country'=>'se'],
            'da' => ['name'=>'Danish',       'native'=>'Dansk',           'flag'=>'🇩🇰','country'=>'dk'],
            'nb' => ['name'=>'Norwegian',    'native'=>'Norsk',           'flag'=>'🇳🇴','country'=>'no'],
            'fi' => ['name'=>'Finnish',      'native'=>'Suomi',           'flag'=>'🇫🇮','country'=>'fi'],
            'cs' => ['name'=>'Czech',        'native'=>'Čeština',         'flag'=>'🇨🇿','country'=>'cz'],
            'sk' => ['name'=>'Slovak',       'native'=>'Slovenčina',      'flag'=>'🇸🇰','country'=>'sk'],
            'hu' => ['name'=>'Hungarian',    'native'=>'Magyar',          'flag'=>'🇭🇺','country'=>'hu'],
            'ro' => ['name'=>'Romanian',     'native'=>'Română',          'flag'=>'🇷🇴','country'=>'ro'],
            'bg' => ['name'=>'Bulgarian',    'native'=>'Български',       'flag'=>'🇧🇬','country'=>'bg'],
            'hr' => ['name'=>'Croatian',     'native'=>'Hrvatski',        'flag'=>'🇭🇷','country'=>'hr'],
            'sr' => ['name'=>'Serbian',      'native'=>'Српски',          'flag'=>'🇷🇸','country'=>'rs'],
            'sl' => ['name'=>'Slovenian',    'native'=>'Slovenščina',     'flag'=>'🇸🇮','country'=>'si'],
            'uk' => ['name'=>'Ukrainian',    'native'=>'Українська',      'flag'=>'🇺🇦','country'=>'ua'],
            'el' => ['name'=>'Greek',        'native'=>'Ελληνικά',        'flag'=>'🇬🇷','country'=>'gr'],
            'he' => ['name'=>'Hebrew',       'native'=>'עברית',           'flag'=>'🇮🇱','country'=>'il'],
            'lt' => ['name'=>'Lithuanian',   'native'=>'Lietuvių',        'flag'=>'🇱🇹','country'=>'lt'],
            'lv' => ['name'=>'Latvian',      'native'=>'Latviešu',        'flag'=>'🇱🇻','country'=>'lv'],
            'et' => ['name'=>'Estonian',      'native'=>'Eesti',           'flag'=>'🇪🇪','country'=>'ee'],
            'ca' => ['name'=>'Catalan',      'native'=>'Català',          'flag'=>'🇪🇸','country'=>'es'],
            'fa' => ['name'=>'Persian',      'native'=>'فارسی',           'flag'=>'🇮🇷','country'=>'ir'],
            'ur' => ['name'=>'Urdu',         'native'=>'اردو',            'flag'=>'🇵🇰','country'=>'pk'],
            'bn' => ['name'=>'Bengali',      'native'=>'বাংলা',            'flag'=>'🇧🇩','country'=>'bd'],
            'ta' => ['name'=>'Tamil',        'native'=>'தமிழ்',            'flag'=>'🇮🇳','country'=>'in'],
            'te' => ['name'=>'Telugu',       'native'=>'తెలుగు',           'flag'=>'🇮🇳','country'=>'in'],
            'sw' => ['name'=>'Swahili',      'native'=>'Kiswahili',       'flag'=>'🇰🇪','country'=>'ke'],
            'af' => ['name'=>'Afrikaans',    'native'=>'Afrikaans',       'flag'=>'🇿🇦','country'=>'za'],
            'ka' => ['name'=>'Georgian',     'native'=>'ქართული',         'flag'=>'🇬🇪','country'=>'ge'],
            'hy' => ['name'=>'Armenian',     'native'=>'Հայերեն',         'flag'=>'🇦🇲','country'=>'am'],
            'az' => ['name'=>'Azerbaijani',  'native'=>'Azərbaycan',      'flag'=>'🇦🇿','country'=>'az'],
            'kk' => ['name'=>'Kazakh',       'native'=>'Қазақ',           'flag'=>'🇰🇿','country'=>'kz'],
            'uz' => ['name'=>'Uzbek',        'native'=>'Oʻzbek',          'flag'=>'🇺🇿','country'=>'uz'],
            'mn' => ['name'=>'Mongolian',    'native'=>'Монгол',          'flag'=>'🇲🇳','country'=>'mn'],
            'km' => ['name'=>'Khmer',        'native'=>'ខ្មែរ',             'flag'=>'🇰🇭','country'=>'kh'],
            'my' => ['name'=>'Myanmar',      'native'=>'မြန်မာ',           'flag'=>'🇲🇲','country'=>'mm'],
            'lo' => ['name'=>'Lao',          'native'=>'ລາວ',             'flag'=>'🇱🇦','country'=>'la'],
            'ne' => ['name'=>'Nepali',       'native'=>'नेपाली',           'flag'=>'🇳🇵','country'=>'np'],
        ];
    }

    public static function get_country_from_locale($lang_code, $locale = '') {
        if (!$locale) $locale = get_locale();
        $locale_map = [
            'en_US'=>'us','en_GB'=>'gb','en_AU'=>'au','en_CA'=>'ca','en_NZ'=>'nz','en_ZA'=>'za','en_IE'=>'ie',
            'zh_CN'=>'cn','zh_TW'=>'tw','zh_HK'=>'hk',
            'pt_BR'=>'br','pt_PT'=>'pt',
            'es_MX'=>'mx','es_AR'=>'ar','es_CO'=>'co','es_ES'=>'es',
            'fr_FR'=>'fr','fr_CA'=>'ca','fr_BE'=>'be','fr_CH'=>'ch',
            'de_DE'=>'de','de_AT'=>'at','de_CH'=>'ch',
            'nl_NL'=>'nl','nl_BE'=>'be',
            'ar_SA'=>'sa','ar_EG'=>'eg','ar_AE'=>'ae',
            'ja'=>'jp','ko_KR'=>'kr','ru_RU'=>'ru',
            'it_IT'=>'it','pl_PL'=>'pl','tr_TR'=>'tr',
            'vi'=>'vn','vi_VN'=>'vn',
        ];
        if (isset($locale_map[$locale])) return $locale_map[$locale];
        if (strpos($locale, '_') !== false) { $parts = explode('_', $locale); return strtolower(end($parts)); }
        $lang_defaults = ['en'=>'us','zh'=>'cn','ja'=>'jp','fr'=>'fr','de'=>'de','es'=>'es','pt'=>'pt','ru'=>'ru','ko'=>'kr','ar'=>'sa','it'=>'it','nl'=>'nl','pl'=>'pl','tr'=>'tr','vi'=>'vn'];
        return $lang_defaults[$lang_code] ?? $lang_code;
    }

    // Keep backward-compat: old sub-menu slugs redirect to the new tabbed page
    public function render_settings_page()   { wp_redirect(admin_url('admin.php?page=gml-translate&tab=settings'));     exit; }
    public function render_switcher_page()   { wp_redirect(admin_url('admin.php?page=gml-translate&tab=switcher'));     exit; }
    public function render_progress_page()   { wp_redirect(admin_url('admin.php?page=gml-translate&tab=translations')); exit; }
}
