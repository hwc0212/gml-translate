<?php
require_once __DIR__ . '/../bootstrap-mock.php';

$settings = file_get_contents( __DIR__ . '/../../admin/class-admin-settings.php' );
$review = file_get_contents( __DIR__ . '/../../admin/class-resource-review-admin.php' );

gml_test_assert( strpos( $settings, "'review'" ) !== false, 'registers a dedicated Review tab' );
gml_test_assert( strpos( $settings, 'GML_Resource_Review_Admin' ) !== false, 'delegates review UI outside the admin settings god class' );
gml_test_assert( strpos( $review, "current_user_can( 'manage_options' )" ) !== false, 'review mutations require administrator capability' );
gml_test_assert( strpos( $review, "check_admin_referer( 'gml_resource_review_action'" ) !== false, 'review mutations require a WordPress nonce' );
gml_test_assert( strpos( $review, "! class_exists( 'GML_Resource_Approval' )" ) !== false, 'partial upgrades fail closed when the Core review service is unavailable' );
gml_test_assert( strpos( $review, "GML_Resource_Approval::approve" ) !== false, 'review approval delegates to Core' );
gml_test_assert( strpos( $review, "GML_Resource_Approval::reject" ) !== false, 'review rejection delegates to Core' );
gml_test_assert( strpos( $review, 'approve_all' ) === false && strpos( $review, 'bulk' ) === false, 'Phase 2C exposes no bulk approval bypass' );
gml_test_assert( strpos( $review, 'does not publish' ) !== false, 'review UI states the shadow-only publication boundary' );

echo "OK test-resource-review-admin\n";
