<?php

/**
 * @file
 * Contains \Drupal\redirect\Form\RedirectFix404Form
 */

namespace Drupal\redirect\Form;

use Drupal\Core\Database\Query\SelectInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\Request;

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
  public function buildForm(array $form, FormStateInterface $form_state) {
    $destination = $this->getDestinationArray();

    $search = $this->getRequest()->get('search');
    $form['#attributes'] = array('class' => array('search-form'));

    $form['basic'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Filter 404s'),
      '#attributes' => array('class' => array('container-inline')),
    );
    $form['basic']['filter'] = array(
      '#type' => 'textfield',
      '#title' => '',
      '#default_value' => $search,
      '#maxlength' => 128,
      '#size' => 25,
    );
    $form['basic']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Filter'),
      '#action' => 'filter',
    );
    if ($search) {
      $form['basic']['reset'] = array(
        '#type' => 'submit',
        '#value' => $this->t('Reset'),
        '#action' => 'reset',
      );
    }

    $languages = \Drupal::languageManager()->getLanguages(LanguageInterface::STATE_ALL);
    $multilingual = \Drupal::languageManager()->isMultilingual();

    $header = array(
      array('data' => $this->t('Path'), 'field' => 'path'),
      array('data' => $this->t('Count'), 'field' => 'count', 'sort' => 'desc'),
      array('data' => $this->t('Last accessed'), 'field' => 'timestamp'),
    );
    if ($multilingual) {
      $header[] = array('data' => $this->t('Language'), 'field' => 'language');
    }
    $header[] = array('data' => $this->t('Operations'));

    $query = \Drupal::database()
      ->select('redirect_404', 'r404')
      ->extend('Drupal\Core\Database\Query\TableSortExtender')
      ->orderByHeader($header)
      ->extend('Drupal\Core\Database\Query\PagerSelectExtender')
      ->limit(25)
      ->fields('r404');

    if ($search) {
      // Replace wildcards with PDO wildcards.
      $wildcard = '%' . trim(preg_replace('!\*+!', '%', \Drupal::database()->escapeLike($search)), '%') . '%';
      $query->condition('path', $wildcard, 'LIKE');
    }
    $results = $query->execute();

    $rows = array();
    foreach ($results as $result) {
      $path = ltrim($result->path, '/');

      $row = array();
      $row['source'] = Link::fromTextAndUrl($result->path, Url::fromUri('base:' . $path, array('query' => $destination)));
      $row['count'] = $result->count;
      $row['timestamp'] = \Drupal::service('date.formatter')->format($result->timestamp, 'short');
      if ($multilingual) {
        if (isset($languages[$result->langcode])) {
          $row['language'] =$languages[$result->langcode]->getName();
        }
        else {
          $row['language'] =$this->t('Undefined @langcode', array('@langcode' => $result->langcode));
        }
      }

      $operations = array();
      if (\Drupal::entityTypeManager()->getAccessControlHandler('redirect')->createAccess()) {
        $operations['add'] = array(
          'title' =>$this->t('Add redirect'),
          'url' => Url::fromRoute('redirect.add', [], ['query' => array('source' => $path, 'language' => $result->langcode) + $destination]),
        );
      }
      $row['operations'] = array(
        'data' => array(
          '#type' => 'operations',
          '#links' => $operations,
        ),
      );

      $rows[] = $row;
    }

    $form['redirect_404_table']  = array(
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => $this->config('redirect.settings')->get('log_404') ? $this->t('No 404 pages without redirects found.') : $this->t('404 requests are currently not logged, enable it in the Settings.'),
    );
    $form['redirect_404_pager'] = array('#type' => 'pager');
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    if ($form_state->getTriggeringElement()['#action'] == 'filter') {
      $form_state->setRedirect('redirect.fix_404', array(), array('query' => array('search' => trim($form_state->getValue('filter')))));
    }
    else {
      $form_state->setRedirect('redirect.fix_404');
    }
  }

}
