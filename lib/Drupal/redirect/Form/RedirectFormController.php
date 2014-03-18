<?php

namespace Drupal\redirect\Form;

use Drupal\Core\Entity\ContentEntityFormController;
use Drupal\Core\Field\FieldDefinition;
use Drupal\Core\Language\Language;
use Drupal\Core\Routing\MatchingRouteNotFoundException;
use Drupal\Core\Url;
use Drupal\redirect\Entity\Redirect;

class RedirectFormController extends ContentEntityFormController {

  /**
   * {@inheritdoc}
   */
  protected function prepareEntity() {
    /** @var \Drupal\redirect\Entity\Redirect $redirect */
    $redirect = $this->entity;
    if ($redirect->isNew()) {
//      $redirect->setSource(isset($_GET['source']) ? urldecode($_GET['source']) : '');
//      $redirect->setRedirect(isset($_GET['redirect']) ? urldecode($_GET['redirect']) : '');
      $redirect->setLanguage(isset($_GET['language']) ? urldecode($_GET['language']) : Language::LANGCODE_NOT_SPECIFIED);

      $source_options = array();
      parse_str($this->getRequest()->get('source_options'), $source_options);
      $redirect_options = array();
      parse_str($this->getRequest()->get('redirect_options'), $redirect_options);

//      $redirect->setSourceOptions($source_options);
//      $redirect->setRedirectOptions($redirect_options);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, array &$form_state) {
    $form = parent::form($form, $form_state);
    /** @var \Drupal\redirect\Entity\Redirect $redirect */
    $redirect = $this->entity;

    if (\Drupal::moduleHandler()->moduleExists('locale')) {
      $form['language'] = array(
        '#type' => 'select',
        '#title' => t('Language'),
        '#options' => array(Language::LANGCODE_NOT_SPECIFIED => t('All languages')) + \Drupal::languageManager()->getLanguages(),
        '#default_value' => $form['language']['#value'],
        '#description' => t('A redirect set for a specific language will always be used when requesting this page in that language, and takes precedence over redirects set for <em>All languages</em>.'),
      );
    }
    else {
      $form['language'] = array(
        '#type' => 'value',
        '#value' => Language::LANGCODE_NOT_SPECIFIED,
      );
    }

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
    parent::validate($form, $form_state);
    $source = $form_state['values']['source'][0];
    $redirect = $form_state['values']['redirect'][0];

    if ($source['url'] == '<front>') {
      $this->setFormError('source', t('It is not allowed to create a redirect from the front page.'));
    }
    if (strpos($source['url'], '#') !== FALSE) {
      $this->setFormError('source', t('The anchor fragments are not allowed.'));
    }

    try {
      $source_url = Url::createFromPath($source['url']);
      $redirect_url = Url::createFromPath($redirect['url']);

      // It is relevant to do this comparison only in case the source path has
      // a valid route. Otherwise the validation will fail on the redirect path
      // being an invalid route.
      if ($source_url->toString() == $redirect_url->toString()) {
        $this->setFormError('redirect', $form_state, t('You are attempting to redirect the page to itself. This will result in an infinite loop.'));
      }
    }
    catch (MatchingRouteNotFoundException $e) {
      // Do nothing, we want to only compare the resulting URLs.
    }

    $parsed_url = parse_url($source['url']);
    $path = isset($parsed_url['path']) ? $parsed_url['path'] : NULL;
    $query = isset($parsed_url['query']) ? $parsed_url['query'] : NULL;
    $hash = Redirect::generateHash($path, $query, $form_state['values']['language']);
    debug($path);
    debug($query);
debug($hash);
    // Search for duplicate.
    $redirects = \Drupal::entityManager()
      ->getStorageController('redirect')
      ->loadByProperties(array('hash' => $hash));
    if (!empty($redirects)) {
      $redirect = array_shift($redirects);
      $this->setFormError('source', $form_state, t('The source path %source is already being redirected. Do you want to <a href="@edit-page">edit the existing redirect</a>?',
        array('%source' => $redirect->getSourceUrl(), '@edit-page' => url('admin/config/search/redirect/edit/'. $redirect->id()))));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submit(array $form, array &$form_state) {
    parent::submit($form, $form_state);

    $this->entity->save();
  }
}
