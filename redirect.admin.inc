<?php
// $Id$

/**
 * @file
 * Administrative page callbacks for the redirect module.
 */

function redirect_list_form($form, &$form_state) {
  return array();
}

/**
 * Form builder to add or edit an URL redirect.
 *
 * @ingroup forms
 */
function redirect_edit_form($form, &$form_state, array $redirect = array()) {
  return array();
}

/**
 * Form builder to delete an URL redirect.
 *
 * @see redirect_delete_form()
 * @see confirm_form()
 *
 * @ingroup forms
 */
function redirect_delete_form($form, &$form_state, array $redirect) {
  $form['rid'] = array(
    '#type' => 'value',
    '#value' => $redirect['rid'],
  );

  return confirm_form(
    $form,
    t('Are you sure you want to delete the URL redirect from %source to %redirect?', array('%source' => $redirect['source'], '%redirect' => $redirect['redirect'])),
    'admin/config/search/path-redirect'
  );
}

/**
 * Form submit handler; delete an URL redirect after confirmation.
 *
 * @see redirect_delete_form()
 */
function redirect_delete_form_submit($form, &$form_state) {
  redirect_delete($form_state['values']['rid']);
  drupal_set_message(t('The redirect has been deleted.'));
  $form_state['redirect'] = 'admin/config/search/redirect';
}

/**
 * Form builder for redirection settings.
 *
 * @see system_settings_form()
 *
 * @ingroup forms
 */
function redirect_settings_form($form, &$form_state) {
  $form['redirect_auto_redirect'] = array(
    '#type' => 'checkbox',
    '#title' => t('Automatically create redirects when URL aliases are changed.'),
    '#default_value' => variable_get('redirect_auto_redirect', TRUE),
    '#disabled' => !module_exists('path'),
    '#access' => FALSE,
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
    '#access' => FALSE,
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
    '#title' => t('Delete redirects that have not been accessed for'),
    '#default_value' => variable_get('redirect_purge_inactive', 0),
    '#options' => array(0 => t('Never (do not discard)')) + drupal_map_assoc(array(604800, 1209600, 1814400, 2592000, 5184000, 7776000, 10368000, 15552000, 31536000), 'format_interval'),
    '#description' => t('Only redirects managaged by the redirect module itself will be deleted. Redirects managed by other modules will be left alone.')
  );

  $form['globals'] = array(
    '#type' => 'fieldset',
    '#title' => t('Always enabled redirections'),
    '#description' => t('(formerly Global Redirect features)')
  );
  $form['globals']['redirect_global_home'] = array(
    '#type' => 'checkbox',
    '#title' => t('Redirect from paths like index.php and /node to the root directory.'),
    '#default_value' => variable_get('redirect_home', 1),
    '#access' => FALSE,
  );
  $form['globals']['redirect_global_clean'] = array(
    '#type' => 'checkbox',
    '#title' => t('Redirect from non-clean URLs to clean URLs.'),
    '#default_value' => variable_get('redirect_clean', 1),
    '#disabled' => !variable_get('clean_url', 0),
    '#access' => FALSE,
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
