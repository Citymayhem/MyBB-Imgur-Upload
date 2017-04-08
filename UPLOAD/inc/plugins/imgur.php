<?php

/**
 * Imgur.com plugin : allow to upload an image to imgur
 * and add it in the post
 * (c) CrazyCat 2014 - 2016
 */
if (!defined("IN_MYBB"))
    die('Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.');

define('CN_ABPIMGUR', str_replace('.php', '', basename(__FILE__)));

$plugins->add_hook('newreply_end', 'imgur_button');
$plugins->add_hook('newthread_end', 'imgur_button');
$plugins->add_hook('editpost_start', 'imgur_button');
$plugins->add_hook('private_send_start', 'imgur_button');
$plugins->add_hook("xmlhttp", "imgur_ajax");

/**
 * Displayed informations
 */
function imgur_info() {
    global $lang;
    $lang->load(CN_ABPIMGUR);
    return array(
        'name' => $lang->imgur_name,
        'description' => $lang->imgur_desc,
        'website' => 'http://ab-plugin.cc/ABP-Imgur-t-4.html',
        'author' => 'CrazyCat',
        'authorsite' => 'http://ab-plugins.cc',
        'version' => '2.2',
        'compatibility' => '18*',
        'codename' => CN_ABPIMGUR
    );
}

/**
 * Install procedure
 * Just add the setting to MyBB
 */
function imgur_install() {
    global $db, $lang;
    $lang->load(CN_ABPIMGUR);

    // Setting group
    $settinggroups = array(
        'name' => CN_ABPIMGUR,
        'title' => $lang->imgur_setting_title,
        'description' => $lang->imgur_setting_description,
        'disporder' => 0,
        "isdefault" => 0
    );

    $db->insert_query('settinggroups', $settinggroups);
    $gid = $db->insert_id();

    // Settings
    $settings[] = array(
        'name' => CN_ABPIMGUR . '_client_id',
        'title' => $lang->imgur_ci_title,
        'description' => $lang->imgur_ci_description,
        'optionscode' => 'text',
        'value' => $lang->imgur_ci_default,
        'disporder' => 1
    );

    // This is a (bad) way to have an alter of the settings
    $settings[] = imgur_update('imgur_display');
    $settings[] = imgur_update('imgur_link');

    foreach ($settings as $i => $setting) {
        $insert = array(
            'name' => $db->escape_string($setting['name']),
            'title' => $db->escape_string($setting['title']),
            'description' => $db->escape_string($setting['description']),
            'optionscode' => $db->escape_string($setting['optionscode']),
            'value' => $db->escape_string($setting['value']),
            'disporder' => $setting['disporder'],
            'gid' => $gid,
        );
        $db->insert_query('settings', $insert);
    }
    rebuild_settings();
}

/**
 * Adds a setting if needed
 * @var string $settingname uniq identifier
 * @return array|boolean
 */
function imgur_update($settingname) {
    global $mybb, $db, $lang;
    $lang->load(CN_ABPIMGUR);
    if ($mybb->settings[$settingname]) {
        return false;
    }
    if ($settingname == CN_ABPIMGUR . '_display') {
        $dispopts = array(
            'r=' . $lang->imgur_disp_raw,
            't=' . $lang->imgur_disp_small,
            'm=' . $lang->imgur_disp_medium,
            'l=' . $lang->imgur_disp_large
        );
        $setting = array(
            'name' => CN_ABPIMGUR . '_display',
            'title' => $lang->imgur_display_title,
            'description' => $lang->imgur_display_description,
            'optionscode' => "select\n" . implode("\n", $dispopts),
            'value' => 'm',
            'disporder' => 2
        );
        return $setting;
    }
    if ($settingname == CN_ABPIMGUR . '_link') {
        $setting = array(
            'name' => CN_ABPIMGUR . '_link',
            'title' => $lang->imgur_link_title,
            'description' => $lang->imgur_link_description,
            'optionscode' => "yesno",
            'value' => 1,
            'disporder' => 3
        );
        return $setting;
    }
    return false;
}

/**
 * Uninstall function
 * Remove settings and templates
 * @see imgur_deactivate
 */
function imgur_uninstall() {
    global $db;
    $db->delete_query('settings', "name LIKE '" . CN_ABPIMGUR . "_%'");
    $db->delete_query('settinggroups', "name = '" . CN_ABPIMGUR . "'");
    rebuild_settings();
    imgur_deactivate();
}

/**
 * Checks if the plugin is installed or not
 */
function imgur_is_installed() {
    global $mybb;
    if (isset($mybb->settings['imgur_client_id'])) {
        return true;
    }
    return false;
}

/**
 * Plugin activation
 * Adds and modify the templates
 */
function imgur_activate() {
    global $db, $lang;

    $button_template = $db->escape_string(file_get_contents(MYBB_ROOT . "inc/plugins/imgur/imgur_button.html"));
    
    $imgur_template = array();
    $imgur_template[] = array(
        'title' => CN_ABPIMGUR . '_button',
        'template' => $button_template,
        'sid' => -1,
        'version' => 1.0,
        'dateline' => TIME_NOW
    );

    foreach ($imgur_template as $row) {
        $db->insert_query("templates", $row);
    }

    require_once MYBB_ROOT . '/inc/adminfunctions_templates.php';
    find_replace_templatesets('newreply', '#{\$smilieinserter}#', '{\$smilieinserter}<!-- Imgur -->{$imgur_button}<!-- /Imgur -->');
    find_replace_templatesets('newthread', '#{\$smilieinserter}#', '{\$smilieinserter}<!-- Imgur -->{$imgur_button}<!-- /Imgur -->');
    find_replace_templatesets('editpost', '#{\$smilieinserter}#', '{\$smilieinserter}<!-- Imgur -->{$imgur_button}<!-- /Imgur -->');
    find_replace_templatesets('private_send', '#{\$smilieinserter}#', '{\$smilieinserter}<!-- Imgur -->{$imgur_button}<!-- /Imgur -->');

    // Here, we'll manage the setting update
    // test of imgur_display
    $imgur_display = imgur_update(CN_ABPIMGUR . '_display');
    if (is_array($imgur_display)) {
        $query = $db->simple_select('settinggroups', 'gid', "name = '" . CN_ABPIMGUR . "'");
        $sg = $db->fetch_array($query);
        $imgur_display['gid'] = $sg['gid'];
        $db->insert_query("settings", $imgur_display);
        rebuild_settings();
    }
    // test of imgur_link
    $imgur_link = imgur_update(CN_ABPIMGUR . '_link');
    if (is_array($imgur_link)) {
        $query = $db->simple_select('settinggroups', 'gid', "name = '" . CN_ABPIMGUR . "'");
        $sg = $db->fetch_array($query);
        $imgur_link['gid'] = $sg['gid'];
        $db->insert_query("settings", $imgur_link);
        rebuild_settings();
    }
}

/**
 * Plugin deactivation
 * Removes the templates
 */
function imgur_deactivate() {
    global $db;
    $db->delete_query('templates', "title LIKE '" . CN_ABPIMGUR . "_%'");
    require_once MYBB_ROOT . '/inc/adminfunctions_templates.php';
    find_replace_templatesets('newreply', '#\<!--\sImgur\s--\>(.+)\<!--\s/Imgur\s--\>#is', '', 0);
    find_replace_templatesets('newthread', '#\<!--\sImgur\s--\>(.+)\<!--\s/Imgur\s--\>#is', '', 0);
    find_replace_templatesets('editpost', '#\<!--\sImgur\s--\>(.+)\<!--\s/Imgur\s--\>#is', '', 0);
    find_replace_templatesets('private_send', '#\<!--\sImgur\s--\>(.+)\<!--\s/Imgur\s--\>#is', '', 0);
}

//########## FUNCTIONS ##########

/**
 * Displays the button
 */
function imgur_button() {
    global $db, $mybb, $lang, $templates, $theme, $imgur_button;
    $lang->load(CN_ABPIMGUR);
    eval("\$imgur_button .= \"" . $templates->get('imgur_button') . "\";");
}


function imgur_ajax(){
  global $mybb;
  
  if($mybb->input['action'] === 'imgur_upload')
  {
    imgur_upload();
  }
}


function imgur_upload(){
  global $_FILES, $mybb;

  if(!isset($_FILES['image'])){
    ImgurResponse::BadRequestResponse("Image not supplied");
  }

  $filename = $_FILES['image']['tmp_name'];
  $handle = fopen($filename, "r");
  $data = fread($handle, filesize($filename));
  $pvars = array('image' => base64_encode($data));

  $client_id = $mybb->settings['imgur_client_id'];
  $timeout = 30;

  $curl = curl_init();
  curl_setopt($curl, CURLOPT_URL, 'https://api.imgur.com/3/image.json');
  curl_setopt($curl, CURLOPT_TIMEOUT, $timeout);
  curl_setopt($curl, CURLOPT_HTTPHEADER, array('Authorization: Client-ID ' . $client_id));
  curl_setopt($curl, CURLOPT_POST, 1);
  curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt($curl, CURLOPT_POSTFIELDS, $pvars);

  $imgurResponse = curl_exec($curl);
  curl_close ($curl);

  $jsonResponse = json_decode($imgurResponse,true);
  if($jsonResponse['status'] !== 200){
    error_log("Error uploading image to imgur: " . $imgurResponse);
    ImgurResponse::InternalServerErrorResponse();
  }

  $url = str_replace("http:", "", $jsonResponse['data']['link']);

  $response = new stdClass();
  $response->url = $url;
  ImgurResponse::OkResponseWithObject($response);
}


class ImgurResponse {
  public static function OkResponseWithObject($responseObject){
    http_response_code(200);
    header('Content-Type: application/json');
    echo json_encode($responseObject);
    exit;
  }

  public static function BadRequestResponse($message){
    http_response_code(400);
    header('Content-Type: application/json');
    $response = new StdClass();
    $response->message = $message;
    echo json_encode($response);
    exit;
  }

  public static function InternalServerErrorResponse(){
    http_response_code(500);
    exit;
  }
}