<?php
if (!defined('ABSPATH')) exit;
final class GML_Page_Demand_Tracker {
    public function __construct() {
        add_action('wp_enqueue_scripts',[$this,'enqueue']);
        add_action('rest_api_init',[$this,'routes']);
        if(is_admin() && !wp_next_scheduled('gml_page_demand_cleanup')) wp_schedule_event(time()+3600,'daily','gml_page_demand_cleanup');
    }
    public function enqueue() {
        if(!get_option('gml_page_demand_enabled',false) || is_user_logged_in() || is_preview() || is_404()) return;
        // Keep anonymous cached HTML identical across user agents. Reject bots at ingest.
        $resource=GML_Resource_Identity::current_public();
        $manifest=$resource ? GML_Resource_Manifest_Store::get_by_key($resource->get_key()) : null;
        if(!$manifest) return;
        $provider=new GML_Translation_Provider();
        $lang=$provider->get_current_language();
        $day=gmdate('Y-m-d');
        wp_enqueue_script('gml-page-demand',GML_PLUGIN_URL.'assets/js/page-demand.js',[],GML_VERSION,true);
        wp_localize_script('gml-page-demand','gmlPageDemand',[
            'endpoint'=>rest_url('gml/v1/page-demand'),'resource'=>(int)$manifest->id,'language'=>$lang,
            'day'=>$day,'token'=>GML_Page_Demand::token($manifest->id,$lang,$day),
        ]);
    }
    public function routes() {
        register_rest_route('gml/v1','/page-demand',[
            'methods'=>'POST','permission_callback'=>'__return_true','callback'=>[$this,'record'],
        ]);
    }
    public function record($request) {
        $origin=$request->get_header('origin');
        $expected=wp_parse_url(home_url(),PHP_URL_HOST);
        if(!$origin || strcasecmp((string)wp_parse_url($origin,PHP_URL_HOST),(string)$expected)!==0
            || is_user_logged_in() || preg_match('/bot|crawl|spider|headless/i',$_SERVER['HTTP_USER_AGENT']??'')) return new WP_Error('gml_demand_forbidden','Request not counted.',['status'=>403]);
        $saved=GML_Page_Demand::record((int)$request['resource'],sanitize_key($request['language']),
            (string)$request['day'],(string)$request['token'],$_SERVER['REMOTE_ADDR']??'unknown');
        $response=new WP_REST_Response(['counted'=>$saved],200);
        $response->header('Cache-Control','no-store');
        return $response;
    }
}
