<?php
/*
  Plugin Name: SCD - Smart Currency Detector - Premium Variant for WcVendor
  Plugin URI: http://gajelabs.com/product/scd
   Description: This wordpress / woocommerce plugin is an ALL-IN-ONE solution for online market places owners, sellers, end customers. Multivendors variant
  Version: 4.7.8.12
   WC tested up to: 5.6

  Author: GaJeLabs
  Author URI: http://gajelabs.com
 */
 
if (in_array('scd-smart-currency-detector/index.php', apply_filters('active_plugins', get_option('active_plugins')))){
include_once "scd_multivendors_settings.php";
include 'scd_multivendors_renders.php';
}


define( 'SCDS_PLUGIN_DIR_PATH', plugin_dir_path( __FILE__ ) );

function scds_add_script_to_admin_dashboard() {
    
   // wp_enqueue_style("ch_lsm_css", trailingslashit(plugins_url("", __FILE__)) . "/css/style.css");
        $cur_screen = get_current_screen();
    //echo("<script>console.log('PHP: ".get_current_screen()->id."');</script>");
    //if (!is_admin()) {
    wp_enqueue_script("scds-script", trailingslashit(plugins_url("", __FILE__)) . "js/scd_lic_form.js", array("jquery"));
   //}
}
add_action('current_screen', 'scds_add_script_to_admin_dashboard');

function scd_multi_add_scrypt_topost() {
     wp_enqueue_script("scd-wcv-multivendor", trailingslashit(plugins_url("", __FILE__)) . "js/scd_wcv_multivendor.js", array("jquery"));
    $variable_to_js = [
		'ajax_url' => admin_url('admin-ajax.php')
	];
	wp_localize_script('scd-wcv-multivendor', 'scd_ajax', $variable_to_js);

}
add_action('wp_enqueue_scripts', 'scd_multi_add_scrypt_topost');

add_filter('scd-admin-tab-list','scd_license_settings',10,1);
function scd_license_settings($tabs) {
    $lic_manager =array('id'=>'license','label'=>'LICENSE MANAGER','class'=>'nav-tab','page'=>'scd_options_page','name'=>'scd_license_options','submit'=>false);
    //$tabs['license']=$lic_manager;
    $tabs = array_slice($tabs, 0, 2, true) +
    array("license" => $lic_manager) +
    array_slice($tabs, 2, count($tabs) - 1, true) ;
    
    return $tabs;
}

add_filter('scd-pro-unactivated','scd_multivendors_enabled');
function scd_multivendors_enabled($unactivated) {
if(scd_check_license_active()){
    return false;
}  else {
    return true;
}
}

add_filter('scd_notice','scd_premium_notice');
function scd_premium_notice($tmesage) {
    //if not activated
    if(!scd_check_license_active())
    return '<div class="notice notice-warning is-dismissible">
        <p>The SCD-Multivendors Pro license key has not been activated or has expired, so you will be unable
            to get automatic updates or support! Click <a href= "'. admin_url('admin.php?page=scd_options_page&tab=license') .'" >here</a> to activate 
            or update your license key.</p>
    </div>';
    else 
        return '';
}

add_filter('scd_license_manager_tab','scd_license_manager_tab_func',10,2);
function scd_license_manager_tab_func($no,$active_tab) {
    if($active_tab=='license') $no=true;
return $no;
}
add_action('scd_activate_license_form','scd_license_manager');
function scd_license_manager() {
  
            // Display message if license not active
            if (!scd_check_license_active()) {
            ?>
                <div class="scd-notice" style="margin-top: 10px">
                    <p>Your SCD License is not activated or has expired. Please activate a new license to get access to the plugin features and support.</p>
                    <p>You can get new license key <a href="https://gajelabs.com/product/scd/" target="_blank">here</a></p>
                </div>
            <?php
            } 
            // Add button to activate new key
            scd_license_activation();
            ?>
<div>
                <hr />
                <input type="submit" id="scd_activate_new" class="button-primary scd_save" value="Activate New License" />
                <hr />
            </div>
              <?php
}
function scd_get_slm_info(){
    return json_decode(file_get_contents(SCDS_PLUGIN_DIR_PATH . "slm.json"));
}
function scd_license_activation() {
    /*     * * License activate button was clicked ** */
    if (isset($_REQUEST['activate_license'])) {
        $license_key = sanitize_text_field($_REQUEST['scd_license_key']);
        $slm_info = scd_get_slm_info();

        // API query parameters
        $api_params = array(
            'slm_action' => 'slm_activate',
            'secret_key' => $slm_info->api,
            'license_key' => $license_key,
            'registered_domain' => $_SERVER['SERVER_NAME'],
            'item_reference' => urlencode($slm_info->reference),
        );

        // Send query to the license manager server
        $query = esc_url_raw(add_query_arg($api_params, $slm_info->url));
        $response = wp_remote_get($query, array('timeout' => 20, 'sslverify' => false));

        // Check for error in the response
        if (is_wp_error($response)) {
            echo "<h3 style='color: red;'>Sorry the server could not activate your license key. Try again later or contact us.</h3>";
        } else if (strpos($response['body'], 'Error 404') !== FALSE) {
            echo "<h3 style='color: red;'>ERROR 404. The activation server was not found. Please contact contact us to correct.</h3>";
        }

        // var_dump($response);//uncomment it if you want to look at the full response license data.

        $license_data = json_decode(wp_remote_retrieve_body($response));
 
        // var_dump($license_data);//uncomment it to look at the data

        if ($license_data->result == 'success') {//Success was returned for the license activation

            //Save the license key in the options table
            update_option('scd_license_key', base64_encode($license_key));
            $date = date('Y-m-d H:i:s');
            update_option('scd_license_start_date', base64_encode($date));
            delete_option('scd_license_expiry_date');
            scd_set_expiry(get_option('scd_license_key'), new DateTime($date));
            update_option('scd_license_options',array('scd_license_key'=>'','scd_license_expiry'=>''));
            //Print message and refresh page
            echo '<br /><h3 style = "color : red;" > ' . $license_data->message . '. Please wait the page will reload in <span id="abc">3</span> seconds...</h3>'
                    . '<script>'
                    . 'var count = 2;
                    var x = setInterval(function(){ 
                    document.getElementById("abc").innerHTML = count;
                    count = count - 1;
                    if(count < 0){
                        clearInterval(x);
                    }
                    }, 1000);
'
                    . '</script>';
            header("Refresh: 3");
        } else {
            //Show error to the user. Probably entered incorrect license key.
            //Uncomment the followng line to see the message that returned from the license server
            echo '<br /><h3 style = "color : red;" >' . $license_data->message . '</h3>';
        }
    }
    /*     * * End of license activation ** */
}


function scds_check_license_active() {
    $opt_license_key = get_option('scd_license_key');
    $opt_license_start_date = get_option('scd_license_start_date');
    $opt_license_expiry_date = get_option('scd_license_expiry_date');

    if (empty($opt_license_key) && empty($opt_license_start_date) && !file_exists($GLOBALS['scd_license_file'])) {
        return FALSE;
    } else {
        if (!empty($opt_license_start_date)) {
            $startdate = new DateTime(base64_decode(get_option('scd_license_start_date')));
        } else if (file_exists($GLOBALS['scd_license_file'])) {
            $startdate = new DateTime(base64_decode(file_get_contents($GLOBALS['scd_license_file'])));
        } else { //only the license key varable remains
            return FALSE;
        }

        if(empty($opt_license_expiry_date) && is_admin()){
            scd_set_expiry($opt_license_key, $startdate);
            $opt_license_expiry_date = get_option('scd_license_expiry_date');
        }

        $todaydate = new DateTime(date('Y-m-d'));
        $duration = $startdate->diff($todaydate);

        if(!empty($opt_license_expiry_date)){
            $expirydate = new DateTime(base64_decode($opt_license_expiry_date));
            if($todaydate < $expirydate) {
                return TRUE;
            } else {    
                return FALSE;
            }
        }
        else{
            // For backward compatibility with older activations prior to 4.5.2 
            if ($duration->days > $GLOBALS['scd_license_duration']) {
                return FALSE;
            } else {
                return TRUE;
            }
        }
    }
}

function scd_set_expiry($key, $startdate) {
    $slm_info = scd_get_slm_info();

    // API query parameters
    $api_params = array(
        'slm_action' => 'slm_check',
        'secret_key' => $slm_info->api,
        'license_key' => base64_decode($key),
    );

    // Send query to the license manager server
    $query = esc_url_raw(add_query_arg($api_params, $slm_info->url));
    $response = wp_remote_get($query, array('timeout' => 20, 'sslverify' => false));
    
    // Check for error in the response
    if (is_wp_error($response)) {
        return FALSE;
    }

    $license_data = json_decode(wp_remote_retrieve_body($response));

    if ($license_data->result == 'success') {
      update_option('scd_license_expiry_date', base64_encode($license_data->date_expiry));
        return TRUE;
    }
    else{
        return FALSE;
    }
}
add_action('admin_notices','scd_premium_require');
function scd_premium_require() {
if(!is_plugin_active('scd-smart-currency-detector/index.php')){
    echo '<h3 style="color:red;">SCD Multivendors for Wc vendors require scd-smart-currency-detector before use, please <a target="__blank" href="https://wordpress.org/plugins/scd-smart-currency-detector/"> download and install it here</a><h3>';    
}
}

if (scd_check_license_active() && in_array('scd-smart-currency-detector/index.php', apply_filters('active_plugins', get_option('active_plugins')))){
    require'scd_wcv_multivendor.php';
}
//widget model 1
add_shortcode('scd_widget1','scd_widget_display');

  if(is_admin())
    {
        // Include the plugin update checker.
        // Note: One customer had an issue with the update checker code which was causing
        //       a fatal error on access to its admin page. To avoid that in the future 
        //       we wrap the code inside a try/catch block
        try
        {
           include 'scd_update_checker_wcv.php';
        }
        catch(\Error $e)
        {
            echo (" Smart Currency Detector (SCD) plugin error: ". $e->__toString());
        }
        catch(\Exception $e)
        {
            echo (" Smart Currency Detector (SCD) plugin exception: ". $e->__toString());    
        }
    }     


add_action( 'woocommerce_email_order_details','scd_woocommerce_email_order_details_cus',10,4);

function scd_woocommerce_email_order_details_cus($order, $sent_to_admin, $plain_text, $email){
    add_filter( 'wc_price', 'scd_convert_price_in_html_markup', 10,4);
}


add_filter( 'wc_get_template', function($located, $template_name, $args, $template_path, $default_path ){
    
    //var_dump($located);
    if($template_name == "overview.php" && $template_path == "wc-vendors/dashboard/reports/"){
        //we overwrite the themplate
        return SCDS_PLUGIN_DIR_PATH . "templates/wcv-overview.php";
    }
    if($template_name == "reports.php" && $template_path == "wc-vendors/dashboard/reports/"){
        //we overwrite the themplate
        return SCDS_PLUGIN_DIR_PATH . "templates/wcv-reports.php";
    }
    return $located;
},999,5); 