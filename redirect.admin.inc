<?php
// $Id$

/**
 * @file
 * Administrative page callbacks for the redirect module.
 */



function redirect_list_form($form, &$form_state) {
  $destination = drupal_get_destination();

  // Set up the header.
  $header = array(
    'source' => array('data' => t('From'), 'field' => 'source', 'sort' => 'asc'),
    'redirect' => array('data' => t('To'), 'field' => 'redirect'),
    'status_code' => array('data' => t('Type'), 'field' => 'status_code'),
    'language' => array('data' => t('Language'), 'field' => 'language'),
    'last_used' => array('data' => t('Last used'), 'field' => 'last_used'),
    'operations' => array('data' => t('Operations')),
  );

  // Do not include the language column if locale is disabled.
  if (!module_exists('locale')) {
    unset($header['language']);
  }

  // Get filter keys and add the filter form.
  $keys = func_get_args();
  $keys = array_splice($keys, 2); // Offset the $form and $form_state parameters.
  $keys = implode('/', $keys);
  $form['redirect_list_filter_form'] = redirect_list_filter_form($keys);

  //// Build the 'Update options' form.
  //$form['operations'] = array(
  //  '#type' => 'fieldset',
  //  '#title' => t('Update options'),
  //  '#prefix' => '<div class="container-inline">',
  //  '#suffix' => '</div>',
  //);
  //$form['#operations'] = module_invoke_all('path_redirect_operations');
  //$operations = array();
  //foreach ($form['#operations'] as $key => $operation) {
  //  $operations[$key] = $operation['action'];
  //}
  //$form['operations']['operation'] = array(
  //  '#type' => 'select',
  //  '#options' => $operations,
  //  '#default_value' => 'delete',
  //);
  //$form['operations']['submit'] = array(
  //  '#type' => 'submit',
  //  '#value' => t('Update'),
  //  '#validate' => array('path_redirect_list_form_operations_validate'),
  //  '#submit' => array('path_redirect_list_form_operations_submit'),
  //);

  // Building the SQL query and load the redirects.
  $query = db_select('redirect');
  $query->addField('redirect', 'rid');
  $query->extend('TableSort')->orderByHeader($header);
  $query->extend('PagerDefault')->limit(50);
  redirect_build_filter_query($query, $keys);
  $rids = $query->execute()->fetchCol();
  $redirects = redirect_load_multiple($rids);

  $rows = array();
  foreach ($redirects as $rid => $redirect) {
    $row = array();
    $redirect['source_options'] = array_merge($redirect['source_options'], array('alias' => TRUE, 'language' => redirect_language_load($redirect['language'])));
    $source_url = redirect_url($redirect['source'], $redirect['source_options']);
    $redirect_url = redirect_url($redirect['redirect'], array_merge($redirect['redirect_options'], array('alias' => TRUE)));
    $row['source'] = l($source_url, $redirect['source'], $redirect['source_options']);
    $row['redirect'] = l($redirect_url, $redirect['redirect'], $redirect['redirect_options']);
    $row['status_code'] = $redirect['status_code'];
    $row['language'] = module_invoke('locale', 'language_name', $redirect['language']);
    $row['last_used'] = $redirect['last_used'] ? format_date($redirect['last_used'], 'short') : t('Never');

    $operations = array();
    $operations['edit'] = array(
      'title' => t('Edit'),
      'href' => 'admin/config/search/redirect/edit/' . $rid,
      'query' => $destination,
    );
    $operations['delete'] = array(
      'title' => t('Delete'),
      'href' => 'admin/config/search/redirect/delete/' . $rid,
      'query' => $destination,
    );
    $row['operations'] = array(
      'data' => array(
        '#theme' => 'links',
        '#links' => $operations,
        '#attributes' => array('class' => array('links', 'inline', 'nowrap')),
      ),
    );

    $rows[$rid] = $row;
  }

  $form['rids'] = array(
    '#type' => 'tableselect',
    '#header' => $header,
    '#options' => $rows,
    '#empty' => t('No URL redirects available.') . ' ' . l(t('Add URL redirect.'), 'admin/config/search/redirect/add', array('query' => $destination)),
  );
  $form['pager'] = array('#theme' => 'pager');
  return $form;
}

/**
 * Return a form to filter URL redirects.
 *
 * @see redirect_list_filter_form_submit()
 *
 * @ingroup forms
 */
function redirect_list_filter_form($keys = '') {
  $form['#attributes'] = array('class' => array('search-form'));
  $form['basic'] = array(
    '#type' => 'fieldset',
    '#title' => t('Filter redirects'),
    '#attributes' => array('class' => array('container-inline')),
  );
  $form['basic']['filter'] = array(
    '#type' => 'textfield',
    '#title' => '',
    '#default_value' => $keys,
    '#maxlength' => 128,
    '#size' => 25,
  );
  $form['basic']['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Filter'),
    '#submit' => array('redirect_list_filter_form_submit'),
  );
  if ($keys) {
    $form['basic']['reset'] = array(
      '#type' => 'submit',
      '#value' => t('Reset'),
      '#submit' => array('redirect_list_filter_form_reset'),
    );
  }
  return $form;
}

/**
 * Process filter form submission when the Filter button is pressed.
 */
function redirect_list_filter_form_submit($form, &$form_state) {
  $form_state['redirect'] = 'admin/config/search/redirect/list/' . trim($form_state['values']['filter']);
}

/**
 * Process filter form submission when the Reset button is pressed.
 */
function redirect_list_filter_form_reset($form, &$form_state) {
  $form_state['redirect'] = 'admin/config/search/redirect';
}

/**
 * Extends a query object for URL redirect filters.
 *
 * @param $query
 *   Query object that should be filtered.
 * @param $keys
 *   The filter string to use.
 */
function redirect_build_filter_query(SelectQuery $query, $keys = '') {
  if ($keys) {
    // Replace wildcards with PDO wildcards.
    $conditions = db_or();
    $wildcard = '%' . trim(preg_replace('!\*+!', '%', db_like($keys)), '%') . '%';
    $conditions->condition('source', $wildcard, 'LIKE');
    $conditions->condition('redirect', $wildcard, 'LIKE');
    $query->condition($conditions);
  }
}

/**
 * Form builder to add or edit an URL redirect.
 *
 * @see redirect_element_validate_source()
 * @see redirect_element_validate_redirect()
 * @see redirect_edit_form_validate()
 * @see redirect_edit_form_submit()
 *
 * @ingroup forms
 */
function redirect_edit_form($form, &$form_state, array $redirect = array()) {
  // Merge default values.
  $redirect += array(
    'rid' => NULL,
    'source' => isset($_GET['source']) ? urldecode($_GET['source']) : '',
    'source_options' => isset($_GET['source_options']) ? drupal_get_query_array($_GET['source_options']) : array(),
    'redirect' => isset($_GET['redirect']) ? urldecode($_GET['redirect']) : '',
    'redirect_options' => isset($_GET['redirect_options']) ? drupal_get_query_array($_GET['redirect_options']) : array(),
    'language' => isset($_GET['language']) ? urldecode($_GET['language']) : LANGUAGE_NONE,
    'status_code' => variable_get('path_redirect_default_status', 301),
    'last_used' => 0,
    'hash' => '',
  );

  $form['rid'] = array(
    '#type' => 'value',
    '#value' => $redirect['rid'],
  );
  $form['hash'] = array(
    '#type' => 'value',
    '#value' => $redirect['hash'],
  );
  $form['last_used'] = array(
    '#type' => 'value',
    '#value' => $redirect['last_used'],
  );

  $form['source'] = array(
    '#type' => 'textfield',
    '#title' => t('From'),
    '#description' => t("Enter an interal Drupal path or path alias to redirect (e.g. %example1 or %example2). Fragment anchors (e.g. %anchor) are <strong>not</strong> allowed.", array('%example1' => 'node/123', '%example2' => 'taxonomy/term/123', '%anchor' => '#anchor')),
    '#maxlength' => 560,
    '#default_value' => redirect_url($redirect['source'], $redirect['source_options']),
    '#required' => TRUE,
    '#field_prefix' => url('', array('absolute' => TRUE)) . (variable_get('clean_url', 0) ? '' : '?q='),
    '#element_validate' => array('redirect_element_validate_source'),
  );
  $form['source_options'] = array(
    '#type' => 'value',
    '#value' => $redirect['source_options'],
  );
  $form['redirect'] = array(
    '#type' => 'textfield',
    '#title' => t('To'),
    '#maxlength' => 560,
    '#default_value' => redirect_url($redirect['redirect'], $redirect['redirect_options'], TRUE),
    '#required' => TRUE,
    '#description' => t('Enter an internal Drupal path, path alias, or complete external URL (like http://example.com/) to redirect to. Use %front to redirect to the front page.', array('%front' => '<front>')),
    '#element_validate' => array('redirect_element_validate_redirect'),
  );
  $form['redirect_options'] = array(
    '#type' => 'value',
    '#value' => $redirect['redirect_options'],
  );

  // This will be a hidden value unless locale module is enabled.
  $form['language'] = array(
    '#type' => 'value',
    '#value' => $redirect['language'],
  );

  $form['advanced'] = array(
    '#type' => 'fieldset',
    '#title' => t('Advanced options'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
  );
  $form['advanced']['status_code'] = array(
    '#type' => 'select',
    '#title' => t('Redirect status'),
    '#description' => t('You can find more information about HTTP redirect status codes at <a href="@status-codes">@status-codes</a>.', array('@status-codes' => 'http://en.wikipedia.org/wiki/List_of_HTTP_status_codes#3xx_Redirection')),
    '#default_value' => $redirect['status_code'],
    '#options' => redirect_status_code_options(),
  );

  $form['override'] = array(
    '#type' => 'checkbox',
    '#title' => t('I understand the following warnings and would like to proceed with saving this URL redirect'),
    '#default_value' => FALSE,
    '#access' => FALSE,
    '#required' => FALSE,
    '#weight' => -100,
    '#prefix' => '<div class="messages warning">',
    '#suffix' => '</div>',
  );
  if (!empty($form_state['storage']['override_messages'])) {
    $form['override']['#access'] = TRUE;
    //$form['override']['#required'] = TRUE;
    $form['override']['#description'] = theme('item_list', array('items' => $form_state['storage']['override_messages']));
    // Reset the messages.
    $form_state['storage']['override_messages'] = array();
  }

  $form['actions'] = array('#type' => 'actions');
  $form['actions']['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Save'),
  );
  $form['actions']['cancel'] = array(
    '#type' => 'link',
    '#title' => t('Cancel'),
    '#href' => isset($_GET['destination']) ? $_GET['destination'] : 'admin/config/search/redirect',
  );

  return $form;
}

/**
 * Element validate handler; validate the source of an URL redirect.
 *
 * @see redirect_edit_form()
 */
function redirect_element_validate_source($element, &$form_state) {
  $value = &$element['#value'];

  // Check that the source contains no URL fragment.
  if (strpos($value, '#') !== FALSE) {
    form_error($element, t('The source path cannot contain an URL fragment anchor.'));
  }

  _redirect_extract_url_options($element, $form_state);

  // Disallow redirections from the frontpage.
  if ($value === '<front>') {
    form_error($element, t('The source path cannot be the front page.'));
  }

  // Cannot create redirects for valid paths.
  if (empty($form_state['values']['override'])) {
    $menu_item = menu_get_item($value);
    if ($menu_item && $menu_item['page_callback'] != 'redirect_redirect' && $value == $menu_item['path']) {
      $form_state['storage']['override_messages']['valid-path'] = t('The source path %path is likely a valid path. It is preferred to <a href="@url-alias">create URL aliases</a> for existing paths rather than redirects.', array('%path' => $value, '@url-alias' => url('admin/config/search/path/add')));
      $form_state['rebuild'] = TRUE;
    }
  }

  return $element;
}

/**
 * Element validate handler; validate the redirect of an URL redirect.
 *
 * @see redirect_edit_form()
 */
function redirect_element_validate_redirect($element, &$form_state) {
  $value = &$element['#value'];

  _redirect_extract_url_options($element, $form_state);

  if (!valid_url($value) && !valid_url($value, TRUE) && $value != '<front>') {
    form_error($element, t('The redirect path %value is not valid.', array('%value' => $value)));
  }

  return $element;
}

/**
 * Extract the query and fragment parts out of an URL field.
 *
 * @param
 */
function _redirect_extract_url_options(&$element, &$form_state) {
  $value = &$element['#value'];
  $type = $element['#name'];

  if ($fragment = parse_url($value, PHP_URL_FRAGMENT)) {
    $form_state['values']["{$type}_options"]['fragment'] = $fragment;
    $value = substr($value, 0, -strlen($fragment));
    $value = trim($value, "#");
    $form_state['values'][$type] = $value;
  }
  else {
    unset($form_state['values']["{$type}_options"]['fragment']);
  }

  if ($query = parse_url($value, PHP_URL_QUERY)) {
    $form_state['values']["{$type}_options"]['query'] = drupal_get_query_array($query);
    $value = substr($value, 0, -strlen($query));
    $value = trim($value, "?&");
    $form_state['values'][$type] = $value;
  }
  else {
    unset($form_state['values']["{$type}_options"]['query']);
  }
}

/**
 * Form validate handler; validate an URL redirect
 *
 * @see redirect_edit_form()
 */
function redirect_edit_form_validate($form, &$form_state) {
  $redirect = &$form_state['values'];

  // check that there there are no redirect loops
  if (url($redirect['source']) == url($redirect['redirect'])) {
    form_set_error('redirect', t('You are attempting to redirect the page to itself. This will result in an infinite loop.'));
  }

  $hash = redirect_get_redirect_hash($redirect);
  if ($existing = redirect_load_by_hash($hash)) {
    if ($redirect['rid'] != $existing['rid']) {
      form_set_error('source', t('The source path %source is already being redirected. Do you want to <a href="@edit-page">edit the existing redirect</a>?', array('%source' => redirect_url($redirect['source'], $redirect['source_options']), '@edit-page' => url('admin/config/search/redirect/edit/'. $existing['rid']))));
    }
  }

  if (empty($form_state['values']['override'])) {
    if ($existing = redirect_load_by_source($redirect['source'], $redirect['language'])) {
      if ($redirect['rid'] != $existing['rid']) {
        // The "from" path should not conflict with another redirect
        $form_state['storage']['override_messages']['redirect-conflict'] = t('The base source path %source is already being redirected. Do you want to <a href="@edit-page">edit the existing redirect</a>?', array('%source' => $redirect['source'], '@edit-page' => url('admin/config/search/redirect/edit/'. $existing['rid'])));
        $form_state['rebuild'] = TRUE;
      }
    }

    if ($form['override']['#access']) {
      drupal_set_message('Did you read the warnings and click the checkbox?', 'error');
      $form_state['rebuild'] = TRUE;
      //form_set_error('override', 'CLICK DA BUTTON!');
    }
  }
}

/**
 * Form submit handler; insert or update an URL redirect.
 *
 * @see redirect_edit_form()
 */
function redirect_edit_form_submit($form, &$form_state) {
  form_state_values_clean($form_state);
  redirect_save($form_state['values']);
  drupal_set_message(t('The redirect has been saved.'));
  $form_state['redirect'] = 'admin/config/search/redirect';
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
    'admin/config/search/redirect'
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
    '#default_value' => variable_get('redirect_default_status_code', 301),
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
