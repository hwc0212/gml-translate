<?php
/** Phase 2C resource review controller and views. */
if ( ! defined( 'ABSPATH' ) ) exit;

final class GML_Resource_Review_Admin {
    const PAGE_SIZE = 25;
    const STRING_PAGE_SIZE = 50;

    public function __construct() {
        add_action( 'admin_init', [ $this, 'handle_request' ] );
    }

    public function handle_request() {
        $page = sanitize_key( wp_unslash( $_GET['page'] ?? '' ) );
        $tab = sanitize_key( wp_unslash( $_GET['tab'] ?? '' ) );
        if ( ! is_admin() || $page !== 'gml-translate' || $tab !== 'review' ) return;
        if ( ! isset( $_POST['gml_resource_review_action'] ) ) return;
        if ( ! current_user_can( 'manage_options' ) ) wp_die( esc_html__( 'Sorry, you are not allowed to review translations.', 'gml-translate' ) );
        check_admin_referer( 'gml_resource_review_action', 'gml_resource_review_nonce' );

        $action = sanitize_key( wp_unslash( $_POST['gml_resource_review_action'] ) );
        $resource_key = sanitize_text_field( wp_unslash( $_POST['gml_resource_key'] ?? '' ) );
        $lang = sanitize_key( wp_unslash( $_POST['gml_target_lang'] ?? '' ) );
        $note = sanitize_textarea_field( wp_unslash( $_POST['gml_review_note'] ?? '' ) );
        if ( ! class_exists( 'GML_Resource_Approval' ) || ! GML_Resource_Approval::tables_ready() ) {
            $result = new WP_Error( 'gml_review_schema', __( 'The review database schema is unavailable.', 'gml-translate' ) );
        } elseif ( $action === 'approve' && empty( $_POST['gml_review_confirm'] ) ) {
            $result = new WP_Error( 'gml_review_confirmation', __( 'Confirm that you reviewed the current translation snapshot.', 'gml-translate' ) );
        } elseif ( $action === 'approve' ) {
            $result = GML_Resource_Approval::approve( $resource_key, $lang, get_current_user_id(), $note );
        } elseif ( $action === 'reject' ) {
            $result = GML_Resource_Approval::reject( $resource_key, $lang, get_current_user_id(), $note );
        } else {
            $result = new WP_Error( 'gml_review_action', __( 'Unknown review action.', 'gml-translate' ) );
        }

        $query = [
            'page' => 'gml-translate', 'tab' => 'review', 'resource' => $resource_key, 'lang' => $lang,
        ];
        if ( is_wp_error( $result ) ) $query['review_error'] = $result->get_error_code();
        else $query['review_notice'] = $action === 'reject' ? 'rejected' : 'approved';
        wp_safe_redirect( add_query_arg( $query, admin_url( 'admin.php' ) ) );
        exit;
    }

    public function render() {
        if ( ! current_user_can( 'manage_options' ) ) return;
        $this->render_notice();
        if ( ! class_exists( 'GML_Resource_Approval' ) || ! GML_Resource_Approval::tables_ready() ) {
            echo '<div class="notice notice-warning inline"><p>' . esc_html__( 'The review database schema is not ready. Reload this administration page after the database upgrade completes.', 'gml-translate' ) . '</p></div>';
            return;
        }

        $resource = sanitize_text_field( wp_unslash( $_GET['resource'] ?? '' ) );
        $lang = sanitize_key( wp_unslash( $_GET['lang'] ?? '' ) );
        if ( $resource !== '' && $lang !== '' ) $this->render_detail( $resource, $lang );
        else $this->render_list();
    }

    private function render_notice() {
        $notice = sanitize_key( wp_unslash( $_GET['review_notice'] ?? '' ) );
        if ( $notice === 'approved' ) echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'The current translation snapshot was approved.', 'gml-translate' ) . '</p></div>';
        elseif ( $notice === 'rejected' ) echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'The current translation snapshot was rejected and the reason was recorded.', 'gml-translate' ) . '</p></div>';

        $error = sanitize_key( wp_unslash( $_GET['review_error'] ?? '' ) );
        $messages = [
            'gml_review_confirmation' => __( 'Confirm that you reviewed the current translation snapshot.', 'gml-translate' ),
            'gml_review_note' => __( 'Add a reason before rejecting this translation.', 'gml-translate' ),
            'gml_review_machine' => __( 'This translation is not machine-complete. Finish or repair it before review.', 'gml-translate' ),
            'gml_review_language' => __( 'Only configured local target languages can be reviewed.', 'gml-translate' ),
            'gml_review_schema' => __( 'The review database schema is unavailable.', 'gml-translate' ),
            'gml_review_write' => __( 'The review decision could not be saved. No partial decision was kept.', 'gml-translate' ),
            'gml_review_action' => __( 'Unknown review action.', 'gml-translate' ),
        ];
        if ( isset( $messages[ $error ] ) ) echo '<div class="notice notice-error"><p>' . esc_html( $messages[ $error ] ) . '</p></div>';
    }

    private function render_list() {
        $page = max( 1, (int) ( $_GET['review_page'] ?? 1 ) );
        $result = GML_Resource_Approval::list_resources( [ 'page' => $page, 'per_page' => self::PAGE_SIZE ] );
        ?>
        <div class="notice notice-info inline"><p>
            <?php esc_html_e( 'Phase 2C review is a shadow workflow. An approval records your decision for the exact current manifest and translation snapshot, but it does not publish, hide, route, index, or advertise a language page yet.', 'gml-translate' ); ?>
        </p></div>
        <div class="gml-review-heading">
            <div>
                <h2><?php esc_html_e( 'Human Review', 'gml-translate' ); ?></h2>
                <p><?php esc_html_e( 'Review one machine-complete resource and language at a time. Source or translation changes automatically make the old decision stale.', 'gml-translate' ); ?></p>
            </div>
            <span><?php printf( esc_html__( '%s resource-language snapshots', 'gml-translate' ), esc_html( number_format_i18n( $result['total'] ) ) ); ?></span>
        </div>
        <div class="gml-review-table">
            <table class="widefat striped">
                <thead><tr>
                    <th><?php esc_html_e( 'Resource', 'gml-translate' ); ?></th>
                    <th><?php esc_html_e( 'Language', 'gml-translate' ); ?></th>
                    <th><?php esc_html_e( 'Machine Readiness', 'gml-translate' ); ?></th>
                    <th><?php esc_html_e( 'Human Review', 'gml-translate' ); ?></th>
                    <th><?php esc_html_e( 'Coverage', 'gml-translate' ); ?></th>
                    <th><?php esc_html_e( 'Action', 'gml-translate' ); ?></th>
                </tr></thead>
                <tbody>
                <?php if ( ! $result['rows'] ): ?>
                    <tr><td colspan="6"><?php esc_html_e( 'No current resource manifests are available for local target languages yet.', 'gml-translate' ); ?></td></tr>
                <?php else: foreach ( $result['rows'] as $row ): ?>
                    <tr>
                        <td><strong><?php echo esc_html( $row['resource_key'] ); ?></strong><br><small><?php echo esc_html( $row['resource_type'] ); ?></small></td>
                        <td><?php echo esc_html( strtoupper( $row['target_lang'] ) ); ?></td>
                        <td><?php $this->status_badge( $row['machine_status'] ); ?></td>
                        <td><?php $this->status_badge( $row['review_status'] ); ?></td>
                        <td><?php echo esc_html( number_format_i18n( $row['translated_count'] ) . ' / ' . number_format_i18n( $row['required_count'] ) ); ?></td>
                        <td><a class="button" href="<?php echo esc_url( $this->detail_url( $row['resource_key'], $row['target_lang'] ) ); ?>"><?php esc_html_e( 'Review', 'gml-translate' ); ?></a></td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
        <?php $this->pagination( $result['page'], $result['pages'], [] ); ?>
        <?php
    }

    private function render_detail( $resource, $lang ) {
        $string_page = max( 1, (int) ( $_GET['string_page'] ?? 1 ) );
        $payload = GML_Resource_Approval::get_review_payload( $resource, $lang, $string_page, self::STRING_PAGE_SIZE );
        if ( is_wp_error( $payload ) ) {
            $message = $payload->get_error_code() === 'gml_review_missing_manifest'
                ? __( 'The current resource manifest is unavailable.', 'gml-translate' )
                : $payload->get_error_message();
            echo '<div class="notice notice-error inline"><p>' . esc_html( $message ) . '</p></div>';
            return;
        }
        $summary = $payload['summary'];
        $can_decide = $summary['machine_status'] === 'complete';
        ?>
        <p><a href="<?php echo esc_url( admin_url( 'admin.php?page=gml-translate&tab=review' ) ); ?>">&larr; <?php esc_html_e( 'Back to review queue', 'gml-translate' ); ?></a></p>
        <div class="gml-review-detail-header">
            <div>
                <h2><?php echo esc_html( $summary['label'] ); ?></h2>
                <code><?php echo esc_html( $summary['resource_key'] ); ?></code>
            </div>
            <div class="gml-review-status-pair">
                <span><?php esc_html_e( 'Machine', 'gml-translate' ); ?>: <?php $this->status_badge( $summary['machine_status'] ); ?></span>
                <span><?php esc_html_e( 'Review', 'gml-translate' ); ?>: <?php $this->status_badge( $summary['review_status'] ); ?></span>
            </div>
        </div>
        <div class="notice notice-info inline"><p><?php esc_html_e( 'This decision is stored for the exact manifest and target-language translation generation shown below. It does not publish the page in Phase 2C.', 'gml-translate' ); ?></p></div>
        <dl class="gml-review-metadata">
            <div><dt><?php esc_html_e( 'Target language', 'gml-translate' ); ?></dt><dd><?php echo esc_html( strtoupper( $summary['target_lang'] ) ); ?></dd></div>
            <div><dt><?php esc_html_e( 'Coverage', 'gml-translate' ); ?></dt><dd><?php echo esc_html( number_format_i18n( $summary['translated_count'] ) . ' / ' . number_format_i18n( $summary['required_count'] ) ); ?></dd></div>
            <div><dt><?php esc_html_e( 'Critical missing', 'gml-translate' ); ?></dt><dd><?php echo esc_html( number_format_i18n( $summary['critical_missing_count'] ) ); ?></dd></div>
            <div><dt><?php esc_html_e( 'Manifest generation', 'gml-translate' ); ?></dt><dd><?php echo esc_html( number_format_i18n( $summary['manifest_generation'] ) ); ?></dd></div>
            <div><dt><?php esc_html_e( 'Translation generation', 'gml-translate' ); ?></dt><dd><?php echo esc_html( number_format_i18n( $summary['translation_generation'] ) ); ?></dd></div>
        </dl>
        <p class="gml-review-links">
            <?php if ( $summary['source_url'] ): ?><a class="button" target="_blank" rel="noopener noreferrer" href="<?php echo esc_url( $summary['source_url'] ); ?>"><?php esc_html_e( 'Open source page', 'gml-translate' ); ?></a><?php endif; ?>
            <?php if ( $summary['translated_url'] ): ?><a class="button" target="_blank" rel="noopener noreferrer" href="<?php echo esc_url( $summary['translated_url'] ); ?>"><?php esc_html_e( 'Open translated page', 'gml-translate' ); ?></a><?php endif; ?>
        </p>
        <div class="gml-review-table">
            <table class="widefat striped">
                <thead><tr>
                    <th><?php esc_html_e( 'Context', 'gml-translate' ); ?></th>
                    <th><?php esc_html_e( 'Source Text', 'gml-translate' ); ?></th>
                    <th><?php esc_html_e( 'Translation', 'gml-translate' ); ?></th>
                    <th><?php esc_html_e( 'Status', 'gml-translate' ); ?></th>
                </tr></thead>
                <tbody>
                <?php foreach ( $payload['strings'] as $string ): ?>
                    <tr>
                        <td><?php echo esc_html( $string->context_type ); ?><?php if ( $string->critical ): ?><br><strong><?php esc_html_e( 'Critical', 'gml-translate' ); ?></strong><?php endif; ?></td>
                        <td><?php echo $string->source_text !== '' ? esc_html( $string->source_text ) : '<code>' . esc_html( $string->source_hash ) . '</code>'; ?></td>
                        <td><?php echo $string->translated_text !== null && $string->translated_text !== '' ? esc_html( $string->translated_text ) : '<em>' . esc_html__( 'Missing', 'gml-translate' ) . '</em>'; ?></td>
                        <td><?php echo esc_html( $string->translation_status ?: __( 'Missing', 'gml-translate' ) ); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php $this->pagination( $payload['page'], $payload['pages'], [ 'resource' => $summary['resource_key'], 'lang' => $summary['target_lang'] ], 'string_page' ); ?>

        <div class="gml-review-decision">
            <h3><?php esc_html_e( 'Record Review Decision', 'gml-translate' ); ?></h3>
            <?php if ( ! $can_decide ): ?><p class="notice notice-warning inline"><?php esc_html_e( 'This snapshot is not machine-complete. Approval and rejection remain disabled until its current manifest is complete.', 'gml-translate' ); ?></p><?php endif; ?>
            <div class="gml-review-decision-grid">
                <form method="post">
                    <?php wp_nonce_field( 'gml_resource_review_action', 'gml_resource_review_nonce' ); ?>
                    <input type="hidden" name="gml_resource_key" value="<?php echo esc_attr( $summary['resource_key'] ); ?>">
                    <input type="hidden" name="gml_target_lang" value="<?php echo esc_attr( $summary['target_lang'] ); ?>">
                    <label><input type="checkbox" name="gml_review_confirm" value="1" required <?php disabled( ! $can_decide ); ?>> <?php esc_html_e( 'I reviewed this current source and translation snapshot.', 'gml-translate' ); ?></label>
                    <p><label><?php esc_html_e( 'Approval note (optional)', 'gml-translate' ); ?><br><textarea name="gml_review_note" rows="3" maxlength="4000"></textarea></label></p>
                    <button class="button button-primary" name="gml_resource_review_action" value="approve" <?php disabled( ! $can_decide ); ?>><?php esc_html_e( 'Approve Current Snapshot', 'gml-translate' ); ?></button>
                </form>
                <form method="post">
                    <?php wp_nonce_field( 'gml_resource_review_action', 'gml_resource_review_nonce' ); ?>
                    <input type="hidden" name="gml_resource_key" value="<?php echo esc_attr( $summary['resource_key'] ); ?>">
                    <input type="hidden" name="gml_target_lang" value="<?php echo esc_attr( $summary['target_lang'] ); ?>">
                    <label><?php esc_html_e( 'Rejection reason', 'gml-translate' ); ?><br><textarea name="gml_review_note" rows="3" maxlength="4000" required <?php disabled( ! $can_decide ); ?>></textarea></label>
                    <p><button class="button" name="gml_resource_review_action" value="reject" <?php disabled( ! $can_decide ); ?>><?php esc_html_e( 'Reject Current Snapshot', 'gml-translate' ); ?></button></p>
                </form>
            </div>
        </div>

        <h3><?php esc_html_e( 'Review History', 'gml-translate' ); ?></h3>
        <table class="widefat striped gml-review-audit"><thead><tr>
            <th><?php esc_html_e( 'Decision', 'gml-translate' ); ?></th><th><?php esc_html_e( 'Reviewer', 'gml-translate' ); ?></th>
            <th><?php esc_html_e( 'Recorded at', 'gml-translate' ); ?></th><th><?php esc_html_e( 'Note', 'gml-translate' ); ?></th>
        </tr></thead><tbody>
        <?php if ( ! $payload['audit'] ): ?><tr><td colspan="4"><?php esc_html_e( 'No review decisions have been recorded.', 'gml-translate' ); ?></td></tr>
        <?php else: foreach ( $payload['audit'] as $event ): ?><tr>
            <td><?php $this->status_badge( $event->decision ); ?></td>
            <td><?php echo esc_html( sprintf( __( 'User #%d', 'gml-translate' ), $event->actor_user_id ) ); ?></td>
            <td><?php echo esc_html( $event->created_at ); ?></td><td><?php echo esc_html( $event->review_note ); ?></td>
        </tr><?php endforeach; endif; ?>
        </tbody></table>
        <?php
    }

    private function status_badge( $status ) {
        $status = sanitize_key( $status );
        $labels = [
            'complete' => __( 'Complete', 'gml-translate' ), 'incomplete' => __( 'Incomplete', 'gml-translate' ),
            'approved' => __( 'Approved', 'gml-translate' ), 'rejected' => __( 'Rejected', 'gml-translate' ),
            'unreviewed' => __( 'Unreviewed', 'gml-translate' ), 'stale' => __( 'Stale', 'gml-translate' ),
            'blocked' => __( 'Blocked', 'gml-translate' ), 'unknown' => __( 'Unknown', 'gml-translate' ),
            'render_error' => __( 'Render Error', 'gml-translate' ), 'rebuilding' => __( 'Rebuilding', 'gml-translate' ),
        ];
        echo '<span class="gml-review-badge gml-review-badge-' . esc_attr( $status ) . '">' . esc_html( $labels[ $status ] ?? ucwords( str_replace( '_', ' ', $status ) ) ) . '</span>';
    }

    private function detail_url( $resource, $lang ) {
        return add_query_arg( [ 'page' => 'gml-translate', 'tab' => 'review', 'resource' => $resource, 'lang' => $lang ], admin_url( 'admin.php' ) );
    }

    private function pagination( $current, $total, array $extra, $page_key = 'review_page' ) {
        if ( $total < 2 ) return;
        $base_args = array_merge( [ 'page' => 'gml-translate', 'tab' => 'review', $page_key => 999999999 ], $extra );
        $base = str_replace( '999999999', '%#%', add_query_arg( $base_args, admin_url( 'admin.php' ) ) );
        $links = paginate_links( [
            'base' => $base,
            'format' => '', 'current' => $current, 'total' => $total, 'type' => 'list',
        ] );
        if ( $links ) echo '<nav class="gml-review-pagination" aria-label="' . esc_attr__( 'Review pages', 'gml-translate' ) . '">' . wp_kses_post( $links ) . '</nav>';
    }
}
