<?php
require_once __DIR__ . '/../bootstrap-mock.php';

$settings = file_get_contents( __DIR__ . '/../../admin/class-admin-settings.php' );
$review = file_get_contents( __DIR__ . '/../../admin/class-resource-review-admin.php' );

gml_test_assert( strpos( $settings, "'review'" ) !== false, 'registers a dedicated Review tab' );
gml_test_assert( strpos( $settings, 'GML_Resource_Review_Admin' ) !== false, 'delegates review UI outside the admin settings god class' );
gml_test_assert( strpos( $review, 'REQUEST_METHOD' ) !== false && strpos( $review, "!== 'POST'" ) !== false, 'review mutations explicitly require HTTP POST' );
gml_test_assert( strpos( $review, "current_user_can( 'manage_options' )" ) !== false, 'review mutations require administrator capability' );
gml_test_assert( strpos( $review, "check_admin_referer( 'gml_resource_review_action'" ) !== false, 'review mutations require a WordPress nonce' );
gml_test_assert( strpos( $review, "! class_exists( 'GML_Resource_Approval' )" ) !== false, 'partial upgrades fail closed when the Core review service is unavailable' );
gml_test_assert( strpos( $review, "GML_Resource_Approval::approve" ) !== false, 'review approval delegates to Core' );
gml_test_assert( strpos( $review, "GML_Resource_Approval::reject" ) !== false, 'review rejection delegates to Core' );
gml_test_assert( strpos( $review, 'gml_expected_manifest_fingerprint' ) !== false && strpos( $review, 'gml_expected_translation_fingerprint' ) !== false, 'review form submits both exact snapshot fingerprints' );
gml_test_assert( strpos( $review, 'gml_expected_review_revision' ) !== false, 'review form binds the decision revision seen by the reviewer' );
gml_test_assert( strpos( $review, "'review_state'" ) !== false && strpos( $review, "'review_lang'" ) !== false, 'review queue exposes bounded language and effective-state filters' );
gml_test_assert( strpos( $review, 'transaction_health' ) !== false && strpos( $review, 'InnoDB' ) !== false, 'review UI fails closed with an actionable transaction-engine health error' );
gml_test_assert( strpos( $review, 'approve_all' ) === false, 'review UI exposes no bulk approval bypass' );
gml_test_assert( strpos( $review, 'get_clusters_bulk' ) !== false, 'review list derives public state in one bulk call' );
gml_test_assert( strpos( $review, 'Public State' ) !== false, 'review list distinguishes Human Review from derived public state' );
gml_test_assert( strpos( $review, 'anonymous visitors are redirected to the source URL' ) !== false, 'review detail explains the Phase 2D visitor behavior' );

echo "OK test-resource-review-admin\n";
