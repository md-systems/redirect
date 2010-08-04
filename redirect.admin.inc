<?php
// $Id$

function redirect_settings_form(&$form) {
  $form['redirect_auto_redirect'] = array(
    '#type' => 'checkbox',
    '#title' => t('Automatically create redirects when URL aliases are changed.'),
    '#default_value' => variable_get('redirect_auto_redirect', TRUE),
    '#disabled' => !module_exists('path'),
  );
  $form['redirect_passthrough_querystring'] = array(
    '#type' => 'checkbox',
    '#title' => t('Retain query string through redirect.'),
    '#default_value' => variable_get('redirect_querystring', 0),
  );
  $form['redirect_warning'] = array(
    '#type' => 'checkbox',
    '#title' => t('Display a warning message to users when they are redirected.'),
    '#default_value' => variable_get('redirect_warning', FALSE),
  );
  $form['redirect_default_status_code'] = array(
    '#type' => 'select',
    '#title' => t('Default redirect status'),
    '#description' => t('You can find more information about HTTP redirect status codes at <a href="@status-codes">@status-codes</a>.', array('@status-codes' => 'http://en.wikipedia.org/wiki/List_of_HTTP_status_codes#3xx_Redirection')),
    '#options' => redirect_status_code_options(),
    '#default_value' => variable_get('redirect_default_status', 301),
  );
  $form['redirect_purge_inactive'] = array(
    '#type' => 'select',
    '#title' => t('Discard redirects that have not been accessed for'),
    '#default_value' => variable_get('redirect_purge_inactive', 0),
    '#options' => array(0 => t('Never (do not discard)')) + drupal_map_assoc(array(604800, 1209600, 2419200, 4838400, 7257600, 9676800, 31536000), 'format_interval'),
  );

  $form['globals'] = array(
    '#type' => 'fieldset',
    '#title' => t('Always enabled redirections'),
    '#description' => t('Formerly Global Redirect features')
  );
  $form['globals']['redirect_global_home'] = array(
    '#type' => 'checkbox',
    '#title' => t('Redirect from paths like index.php and /node to the root directory.'),
    '#default_value' => variable_get('redirect_home', 1),
  );
  $form['globals']['redirect_global_clean'] = array(
    '#type' => 'checkbox',
    '#title' => t('Redirect from non-clean URLs to clean URLs.'),
    '#default_value' => variable_get('redirect_clean', 1),
    '#disabled' => !variable_get('clean_url', 0),
  );
  $form['globals']['redirect_global_canonical'] = array(
    '#type' => 'checkbox',
    '#title' => t('Redirect from non-canonical URLs to the canonical URLs.'),
    '#default_value' => variable_get('redirect_canonical', 1),
  );
  $form['globals']['redirect_global_admin_paths'] = array(
    '#type' => 'checkbox',
    '#title' => t('Allow redirections on admin paths.'),
    '#default_value' => variable_get('redirect_global_admin_paths', 0),
  );

  return system_settings_form($form);
}

function redirect_status_code_options() {
  return array(
    300 => t('300 Multiple Choices'),
    301 => t('301 Moved Permanently'),
    302 => t('302 Found'),
    303 => t('303 See Other'),
    304 => t('304 Not Modified'),
    305 => t('305 Use Proxy'),
    307 => t('307 Temporary Redirect'),
  );
}
