<?php

namespace Drupal\redirect\Form;

use Drupal\Core\Database\Query\Select;
use Drupal\Core\Form\FormBase;

class RedirectListForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'redirect_list_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state) {
    $form['#operations'] = redirect_get_redirect_operations();
    if (isset($form_state['values']['operation']) && empty($form_state['values']['confirm'])) {
      return $this->operationsConfirmForm($form, $form_state, $form_state['values']['operation'], array_filter($form_state['values']['rids']));
    }

    $destination = drupal_get_destination();
    $default_status_code = \Drupal::config('redirect.settings')->get('default_status_code');

    // Set up the header.
    $header = array(
      'source' => array('data' => t('From'), 'field' => 'source', 'sort' => 'asc'),
      'redirect' => array('data' => t('To'), 'field' => 'redirect'),
      'status_code' => array('data' => t('Type'), 'field' => 'status_code'),
      'language' => array('data' => t('Language'), 'field' => 'language'),
      'count' => array('data' => t('Count'), 'field' => 'count'),
      'access' => array('data' => t('Last accessed'), 'field' => 'access'),
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
    $form['redirect_list_filter_form'] = $this->filterForm($keys);

    // Build the 'Update options' form.
    $form['operations'] = array(
      '#type' => 'fieldset',
      '#title' => t('Update options'),
      '#prefix' => '<div class="container-inline">',
      '#suffix' => '</div>',
      '#attributes' => array(
        'class' => array('redirect-list-operations'),
      ),
    );
    $operations = array();
    foreach ($form['#operations'] as $key => $operation) {
      $operations[$key] = $operation['action'];
    }
    $form['operations']['operation'] = array(
      '#type' => 'select',
      '#options' => $operations,
      '#default_value' => 'delete',
    );
    $form['operations']['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Update'),
    );

    // Building the SQL query and load the redirects.
    $query = db_select('redirect', 'r');
    $query->extend('Drupal\Core\Database\Query\TableSortExtender')->orderByHeader($header);
    $query->extend('Drupal\Core\Database\Query\PagerSelectExtender')->limit(50);
    $query->addField('r', 'rid');
    $query->condition('r.type', 'redirect');
    $query->addTag('redirect_list');
    $query->addTag('redirect_access');
    $this->buildFilterQuery($query, array('source', 'redirect'), $keys);
    $rids = $query->execute()->fetchCol();
    $redirects = redirect_load_multiple($rids);

    $rows = array();
    foreach ($redirects as $rid => $redirect) {
      $row = array();
      $redirect->source_options = array_merge($redirect->source_options, array('alias' => TRUE, 'language' => redirect_language_load($redirect->language)));
      $source_url = redirect_url($redirect->source, $redirect->source_options);
      $redirect_url = redirect_url($redirect->redirect, array_merge($redirect->redirect_options, array('alias' => TRUE)));
      drupal_alter('redirect_url', $redirect->source, $redirect->source_options);
      drupal_alter('redirect_url', $redirect->redirect, $redirect->redirect_options);
      $row['source'] = l($source_url, $redirect->source, $redirect->source_options);
      $row['redirect'] = l($redirect_url, $redirect->redirect, $redirect->redirect_options);
      $row['status_code'] = $redirect->status_code ? $redirect->status_code : t('Default (@default)', array('@default' => $default_status_code));
      $row['language'] = module_invoke('locale', 'language_name', $redirect->language);
      $row['count'] = $redirect->count;
      if ($redirect->access) {
        $row['access'] = array(
          'data' => t('!interval ago', array('!interval' => format_interval(REQUEST_TIME - $redirect->access))),
          'title' => t('Last accessed on @date', array('@date' => format_date($redirect->access))),
        );
      }
      else {
        $row['access'] = t('Never');
      }

      // Mark redirects that override existing paths with a warning in the table.
      if (drupal_valid_path($redirect->source)) {
        $row['#attributes']['class'][] = 'warning';
        $row['#attributes']['title'] = t('This redirect overrides an existing internal path.');
      }

      $operations = array();
      if (redirect_access('update', $redirect)) {
        $operations['edit'] = array(
          'title' => t('Edit'),
          'href' => 'admin/config/search/redirect/edit/' . $rid,
          'query' => $destination,
        );
      }
      if (redirect_access('delete', $redirect)) {
        $operations['delete'] = array(
          'title' => t('Delete'),
          'href' => 'admin/config/search/redirect/delete/' . $rid,
          'query' => $destination,
        );
      }
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
      '#empty' => t('No URL redirects available.'),
      '#attributes' => array(
        'class' => array('redirect-list-tableselect'),
      ),
      '#attached' => array(
        'js' => array(
          drupal_get_path('module', 'redirect') . '/redirect.admin.js',
        ),
      ),
    );
    if (redirect_access('create', 'redirect')) {
      $form['rids']['#empty'] .= ' ' . l(t('Add URL redirect.'), 'admin/config/search/redirect/add', array('query' => $destination));
    }
    $form['pager'] = array('#theme' => 'pager');
    return $form;
  }

  public function validateForm(array &$form, array &$form_state) {
    // Error if there are no redirects selected.
    if (!is_array($form_state['values']['rids']) || !count(array_filter($form_state['values']['rids']))) {
      $this->setFormError('', t('No redirects selected.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {

    if ($form_state['triggering_element']['#action'] == 'filter') {
      $form_state['redirect'] = 'admin/config/search/redirect/list/' . trim($form_state['values']['filter']);
      return;
    }

    if ($form_state['triggering_element']['#action'] == 'reset') {
      $form_state['redirect'] = 'admin/config/search/redirect/list';
      return;
    }

    $operations = $form['#operations'];
    $operation = $operations[$form_state['values']['operation']];

    // Filter out unchecked redirects
    $rids = array_filter($form_state['values']['rids']);

    if (!empty($operation['confirm']) && empty($form_state['values']['confirm'])) {
      // We need to rebuild the form to go to a second step. For example, to
      // show the confirmation form for the deletion of redirects.
      $form_state['rebuild'] = TRUE;
    }
    else {
      $function = $operation['callback'];

      // Add in callback arguments if present.
      if (isset($operation['callback arguments'])) {
        $args = array_merge(array($rids), $operation['callback arguments']);
      }
      else {
        $args = array($rids);
      }
      call_user_func_array($function, $args);

      $count = count($form_state['values']['rids']);
      watchdog('redirect', '@action @count redirects.', array('@action' => $operation['action_past'], '@count' => $count));
      drupal_set_message(format_plural(count($rids), '@action @count redirect.', '@action @count redirects.', array('@action' => $operation['action_past'], '@count' => $count)));
    }
  }

  /**
   * Return a form to filter URL redirects.
   *
   * @see redirect_list_filter_form_submit()
   *
   * @ingroup forms
   */
  protected function filterForm($keys = '') {
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
      '#action' => 'filter',
    );
    if ($keys) {
      $form['basic']['reset'] = array(
        '#type' => 'submit',
        '#value' => t('Reset'),
        '#action' => 'reset',
      );
    }
    return $form;
  }

  protected function buildFilterQuery(Select $query, array $fields, $keys = '') {
    if ($keys && $fields) {
      // Replace wildcards with PDO wildcards.
      $conditions = db_or();
      $wildcard = '%' . trim(preg_replace('!\*+!', '%', db_like($keys)), '%') . '%';
      foreach ($fields as $field) {
        $conditions->condition($field, $wildcard, 'LIKE');
      }
      $query->condition($conditions);
    }
  }

  protected function operationsConfirmForm($form, &$form_state, $operation, $rids) {
    $operations = $form['#operations'];
    $operation = $operations[$form_state['values']['operation']];

    $form['rids_list'] = array(
      '#theme' => 'item_list',
      '#items' => array(),
    );
    $form['rids'] = array(
      '#type' => 'value',
      '#value' => $rids,
    );

    $redirects = redirect_load_multiple($rids);
    foreach ($redirects as $rid => $redirect) {
      $form['rids_list']['#items'][$rid] = check_plain(redirect_url($redirect->source, $redirect->source_options));
    }

    $form['operation'] = array('#type' => 'hidden', '#value' => $form_state['values']['operation']);
    $form['#submit'][] = 'redirect_list_form_operations_submit';
    $confirm_question = format_plural(count($rids), 'Are you sure you want to @action this redirect?', 'Are you sure you want to @action these redirects?', array('@action' => drupal_strtolower($operation['action'])));

    return confirm_form(
      $form,
      $confirm_question,
      'admin/config/search/redirect', // @todo This does not redirect back to filtered page.
      t('This action cannot be undone.'),
      $operation['action'],
      t('Cancel')
    );
  }
}
