<?php

namespace Drupal\redirect\Form;

use Drupal\Core\Entity\ContentEntityFormController;
use Drupal\Core\Language\Language;

class RedirectFormController extends ContentEntityFormController {

  /**
   * {@inheritdoc}
   */
  protected function prepareEntity() {
    /** @var \Drupal\redirect\Entity\Redirect $redirect */
    $redirect = $this->entity;
    if ($redirect->isNew()) {
      $redirect->setSource(isset($_GET['source']) ? urldecode($_GET['source']) : '');
      $redirect->setRedirect(isset($_GET['redirect']) ? urldecode($_GET['redirect']) : '');
      $redirect->setLanguage(isset($_GET['language']) ? urldecode($_GET['language']) : Language::LANGCODE_NOT_SPECIFIED);

      $source_options = array();
      parse_str($this->getRequest()->get('source_options'), $source_options);
      $redirect_options = array();
      parse_str($this->getRequest()->get('redirect_options'), $redirect_options);

      $redirect->setSourceOptions($source_options);
      $redirect->setRedirectOptions($redirect_options);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, array &$form_state) {
    $form = parent::form($form, $form_state);
    /** @var \Drupal\redirect\Entity\Redirect $redirect */
    $redirect = $this->entity;

    $form['source'] = array(
      '#type' => 'textfield',
      '#title' => t('From'),
      '#description' => t("Enter an internal Drupal path or path alias to redirect (e.g. %example1 or %example2). Fragment anchors (e.g. %anchor) are <strong>not</strong> allowed.", array('%example1' => 'node/123', '%example2' => 'taxonomy/term/123', '%anchor' => '#anchor')),
      '#maxlength' => 560,
      '#default_value' => $redirect->id() || $redirect->getSource() ? redirect_url($redirect->getSource(), $redirect->getSourceOptions() + array('alter' => FALSE)) : '',
      '#required' => TRUE,
      //'#field_prefix' => $GLOBALS['base_url'] . '/' . (variable_get('clean_url', 0) ? '' : '?q='),
      '#element_validate' => array('redirect_element_validate_source'),
    );
    $form['redirect'] = array(
      '#type' => 'textfield',
      '#title' => t('To'),
      '#maxlength' => 560,
      '#default_value' => $redirect->id() || $redirect->getRedirect() ? redirect_url($redirect->getRedirect(), $redirect->getRedirectOptions(), TRUE) : '',
      '#required' => TRUE,
      '#description' => t('Enter an internal Drupal path, path alias, or complete external URL (like http://example.com/) to redirect to. Use %front to redirect to the front page.', array('%front' => '<front>')),
      '#element_validate' => array('redirect_element_validate_redirect'),
    );
    $form['status_code'] = array(
      '#type' => 'select',
      '#title' => t('Redirect status'),
      '#description' => t('You can find more information about HTTP redirect status codes at <a href="@status-codes">@status-codes</a>.', array('@status-codes' => 'http://en.wikipedia.org/wiki/List_of_HTTP_status_codes#3xx_Redirection')),
      '#default_value' => $redirect->getStatusCode(),
      '#options' => array(0 => t('Default (@default)', array('@default' => \Drupal::config('redirect.settings')->get('default_status_code')))) + redirect_status_code_options(),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validate(array $form, array &$form_state) {
    /** @var \Drupal\redirect\Entity\Redirect $redirect */
    $redirect = $this->entity;
    parent::validate($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submit(array $form, array &$form_state) {
    parent::submit($form, $form_state);
  }
}
