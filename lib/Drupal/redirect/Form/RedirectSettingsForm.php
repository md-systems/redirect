<?php

namespace Drupal\redirect\Form;

use Drupal\Core\Form\FormBase;

class RedirectSettingsForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'redirect_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state) {
    $form['redirect_auto_redirect'] = array(
      '#type' => 'checkbox',
      '#title' => t('Automatically create redirects when URL aliases are changed.'),
      '#default_value' => redirect_settings_get('auto_redirect'),
      '#disabled' => !module_exists('path'),
    );
    $form['redirect_passthrough_querystring'] = array(
      '#type' => 'checkbox',
      '#title' => t('Retain query string through redirect.'),
      '#default_value' => redirect_settings_get('passthrough_querystring'),
      '#description' => t('For example, given a redirect from %source to %redirect, if a user visits %sourcequery they would be redirected to %redirectquery. The query strings in the redirection will always take precedence over the current query string.', array('%source' => 'source-path', '%redirect' => 'node?a=apples', '%sourcequery' => 'source-path?a=alligators&b=bananas', '%redirectquery' => 'node?a=apples&b=bananas')),
    );
    $form['redirect_warning'] = array(
      '#type' => 'checkbox',
      '#title' => t('Display a warning message to users when they are redirected.'),
      '#default_value' => redirect_settings_get('warning'),
      '#access' => FALSE,
    );
    $form['redirect_default_status_code'] = array(
      '#type' => 'select',
      '#title' => t('Default redirect status'),
      '#description' => t('You can find more information about HTTP redirect status codes at <a href="@status-codes">@status-codes</a>.', array('@status-codes' => 'http://en.wikipedia.org/wiki/List_of_HTTP_status_codes#3xx_Redirection')),
      '#options' => redirect_status_code_options(),
      '#default_value' => redirect_settings_get('default_status_code'),
    );
    $cache_enabled = \Drupal::config('system.performance')->get('cache.page.use_internal');
    $invoke_hooks = \Drupal::config('system.performance')->get('cache.page.invoke_hooks');
    $form['redirect_page_cache'] = array(
      '#type' => 'checkbox',
      '#title' => t('Allow redirects to be saved into the page cache.'),
      '#default_value' => redirect_settings_get('page_cache'),
      '#description' => t('This feature requires <a href="@performance">Cache pages for anonymous users</a> to be enabled and the %variable variable to be TRUE.', array('@performance' => url('admin/config/development/performance'), '%variable' => "\$conf['page_cache_invoke_hooks']")),
      '#disabled' => !$cache_enabled || !$invoke_hooks,
    );
    $form['redirect_purge_inactive'] = array(
      '#type' => 'select',
      '#title' => t('Delete redirects that have not been accessed for'),
      '#default_value' => redirect_settings_get('purge_inactive'),
      '#options' => array(0 => t('Never (do not discard)')) + drupal_map_assoc(array(604800, 1209600, 1814400, 2592000, 5184000, 7776000, 10368000, 15552000, 31536000), 'format_interval'),
      '#description' => t('Only redirects managaged by the redirect module itself will be deleted. Redirects managed by other modules will be left alone.'),
      '#disabled' => redirect_settings_get('page_cache') && !$invoke_hooks,
    );

    $form['globals'] = array(
      '#type' => 'fieldset',
      '#title' => t('Always enabled redirections'),
      '#description' => t('(formerly Global Redirect features)'),
      '#access' => FALSE,
    );
    $form['globals']['redirect_global_home'] = array(
      '#type' => 'checkbox',
      '#title' => t('Redirect from paths like index.php and /node to the root directory.'),
      '#default_value' => redirect_settings_get('global_home'),
      '#access' => FALSE,
    );
    $form['globals']['redirect_global_clean'] = array(
      '#type' => 'checkbox',
      '#title' => t('Redirect from non-clean URLs to clean URLs.'),
      '#default_value' => redirect_settings_get('global_clean'),
      // @todo - does still apply? See https://drupal.org/node/1659580
      //'#disabled' => !variable_get('clean_url', 0),
      '#access' => FALSE,
    );
    $form['globals']['redirect_global_canonical'] = array(
      '#type' => 'checkbox',
      '#title' => t('Redirect from non-canonical URLs to the canonical URLs.'),
      '#default_value' => redirect_settings_get('global_canonical'),
    );
    $form['globals']['redirect_global_deslash'] = array(
      '#type' => 'checkbox',
      '#title' => t('Remove trailing slashes from paths.'),
      '#default_value' => redirect_settings_get('global_deslash'),
      '#access' => FALSE,
    );
    $form['globals']['redirect_global_admin_paths'] = array(
      '#type' => 'checkbox',
      '#title' => t('Allow redirections on admin paths.'),
      '#default_value' => redirect_settings_get('global_admin_paths'),
    );

    $form['#submit'][] = 'redirect_settings_form_submit';
    return system_settings_form($form);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    redirect_page_cache_clear();
  }
}
