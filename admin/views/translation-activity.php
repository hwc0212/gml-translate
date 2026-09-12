<?php
if ( ! defined( 'ABSPATH' ) || ! current_user_can( 'manage_options' ) ) return;
$reasons = [
    'manual_pause' => __( 'Paused by an administrator.', 'gml-translate' ),
    'credentials_changed' => __( 'Provider settings changed; verify the connection, then resume.', 'gml-translate' ),
    'provider_configuration' => __( 'Provider configuration failed; verify the saved connection, then resume.', 'gml-translate' ),
    'sample_finished' => __( 'The approved sample finished. Ordinary translation remains paused.', 'gml-translate' ),
    'language_paused' => __( 'No language is enabled for queue processing.', 'gml-translate' ),
    'legacy_pause' => __( 'An existing pause was retained. Older versions did not record who paused it or why.', 'gml-translate' ),
];
if ( in_array( $queue_status['state'], [ 'paused', 'pausing', 'safety_paused' ], true ) ) : ?>
    <p role="status"><?php echo esc_html( $reasons[$queue_status['reason'] ?? ''] ?? $reasons['legacy_pause'] ); ?>
    <?php if ( ! empty( $queue_status['paused_at'] ) ) echo ' ' . esc_html( wp_date( 'Y-m-d H:i:s', $queue_status['paused_at'] ) ); ?></p>
<?php endif; ?>
<details class="gml-translation-activity" style="margin:16px 0">
    <summary><?php esc_html_e( 'Translation Activity Log', 'gml-translate' ); ?></summary>
    <div style="overflow-x:auto">
    <table class="widefat striped">
        <thead><tr><th><?php esc_html_e( 'Time', 'gml-translate' ); ?></th><th><?php esc_html_e( 'Event', 'gml-translate' ); ?></th><th><?php esc_html_e( 'Details', 'gml-translate' ); ?></th></tr></thead>
        <tbody>
        <?php $events = GML_Translation_Activity::recent( 100 ); foreach ( $events as $event ) :
            $at = (int) ( $event['at'] ?? 0 ); $name = $event['event'] ?? ''; unset( $event['at'], $event['event'] ); ?>
            <tr><td><?php echo esc_html( wp_date( 'Y-m-d H:i:s', $at ) ); ?></td><td><code><?php echo esc_html( $name ); ?></code></td><td><code style="overflow-wrap:anywhere"><?php echo esc_html( wp_json_encode( $event, JSON_UNESCAPED_SLASHES ) ); ?></code></td></tr>
        <?php endforeach; if ( ! $events ) : ?>
            <tr><td colspan="3"><?php esc_html_e( 'No activity has been recorded by this version yet.', 'gml-translate' ); ?></td></tr>
        <?php endif; ?>
        </tbody>
    </table>
    </div>
</details>
