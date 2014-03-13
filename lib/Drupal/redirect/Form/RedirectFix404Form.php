<?php

namespace Drupal\redirect\Form;

use Drupal\Core\Form\FormBase;

class RedirectFix404Form extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'redirect_fix_404_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state) {
    $destination = drupal_get_destination();

    // Get filter keys and add the filter form.
    $keys = func_get_args();
    //$keys = array_splice($keys, 2); // Offset the $form and $form_state parameters.
    $keys = implode('/', $keys);

    $form['#attributes'] = array('class' => array('search-form'));
    $form['basic'] = array(
      '#type' => 'fieldset',
      '#title' => t('Filter 404s'),
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

    $header = array(
      array('data' => t('Page'), 'field' => 'message'),
      array('data' => t('Count'), 'field' => 'count', 'sort' => 'desc'),
      array('data' => t('Last accessed'), 'field' => 'timestamp'),
      array('data' => t('Operations')),
    );

    $count_query = db_select('watchdog', 'w');
    $count_query->addExpression('COUNT(DISTINCT(w.message))');
    $count_query->leftJoin('redirect', 'r', 'w.message = r.source');
    $count_query->condition('w.type', 'page not found');
    $count_query->isNull('r.rid');
    redirect_build_filter_query($count_query, array('w.message'), $keys);

    $query = db_select('watchdog', 'w')->extend('PagerDefault')->extend('TableSort');
    $query->fields('w', array('message'));
    $query->addExpression('COUNT(wid)', 'count');
    $query->addExpression('MAX(timestamp)', 'timestamp');
    $query->leftJoin('redirect', 'r', 'w.message = r.source');
    $query->isNull('r.rid');
    $query->condition('w.type', 'page not found');
    $query->groupBy('w.message');
    $query->orderByHeader($header);
    $query->limit(25);
    redirect_build_filter_query($query, array('w.message'), $keys);
    $query->setCountQuery($count_query);
    $results = $query->execute();

    $rows = array();
    foreach ($results as $result) {
      $row = array();
      $row['source'] = l($result->message, $result->message, array('query' => $destination));
      $row['count'] = $result->count;
      $row['timestamp'] = format_date($result->timestamp, 'short');

      $operations = array();
      if (redirect_access('create', 'redirect')) {
        $operations['add'] = array(
          'title' => t('Add redirect'),
          'href' => 'admin/config/search/redirect/add/',
          'query' => array('source' => $result->message) + $destination,
        );
      }
      $row['operations'] = array(
        'data' => array(
          '#theme' => 'links',
          '#links' => $operations,
          '#attributes' => array('class' => array('links', 'inline', 'nowrap')),
        ),
      );

      $rows[] = $row;
    }

    $form['redirect_404_table']  = array(
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => t('No 404 pages without redirects found.'),
    );
    $form['redirect_404_pager'] = array('#theme' => 'pager');
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    if ($form_state['triggering_element']['#action'] == 'filter') {
      $form_state['redirect'] = 'admin/config/search/redirect/404/' . trim($form_state['values']['filter']);
    }
    else {
      $form_state['redirect'] = 'admin/config/search/redirect/404';
    }
  }

}
