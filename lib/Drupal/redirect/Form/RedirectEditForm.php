<?php

namespace Drupal\redirect\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Language\Language;
use Drupal\Core\Url;

class RedirectEditForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'redirect_edit_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state, $redirect_id = NULL) {
    if (!isset($redirect)) {
      $redirect = entity_create('redirect', array('type' => 'vole'));
    }

    $source_options = array();
    parse_str($this->getRequest()->get('source_options'), $source_options);
    $redirect_options = array();
    parse_str($this->getRequest()->get('redirect_options'), $redirect_options);

    // Merge default values.
    $redirect = redirect_create(array(
      'source' => isset($_GET['source']) ? urldecode($_GET['source']) : '',
      'source_options' => $source_options,
      'redirect' => isset($_GET['redirect']) ? urldecode($_GET['redirect']) : '',
      'redirect_options' => $redirect_options,
      'language' => isset($_GET['language']) ? urldecode($_GET['language']) : Language::LANGCODE_NOT_SPECIFIED,
    ));

    $form['rid'] = array(
      '#type' => 'value',
      '#value' => $redirect->id(),
    );
    $form['type'] = array(
      '#type' => 'value',
      '#value' => $redirect->getType(),
    );
    $form['hash'] = array(
      '#type' => 'value',
      '#value' => $redirect->getHash(),
    );

    $form['source'] = array(
      '#type' => 'textfield',
      '#title' => t('From'),
      '#description' => t("Enter an internal Drupal path or path alias to redirect (e.g. %example1 or %example2). Fragment anchors (e.g. %anchor) are <strong>not</strong> allowed.", array('%example1' => 'node/123', '%example2' => 'taxonomy/term/123', '%anchor' => '#anchor')),
      '#maxlength' => 560,
      '#default_value' => $redirect->id() || $redirect->getSource() ? redirect_url($redirect->getSourceOptions(), $redirect->getSourceOptions() + array('alter' => FALSE)) : '',
      '#required' => TRUE,
      // @todo - probably needs to be prefixed with index.php instead of ?q=
      //    See https://drupal.org/node/1659580.
      //'#field_prefix' => $GLOBALS['base_url'] . '/' . (variable_get('clean_url', 0) ? '' : '?q='),
      '#element_validate' => array('redirect_element_validate_source'),
    );
    $form['source_options'] = array(
      '#type' => 'value',
      '#value' => $redirect->getSourceOptions(),
      '#tree' => TRUE,
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
    $form['redirect_options'] = array(
      '#type' => 'value',
      '#value' => $redirect->getRedirectOptions(),
      '#tree' => TRUE,
    );

    // This will be a hidden value unless locale module is enabled.
    $form['language'] = array(
      '#type' => 'value',
      '#value' => $redirect->getLanguage(),
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
      '#default_value' => $redirect->getStatusCode(),
      '#options' => array(0 => t('Default (@default)', array('@default' => \Drupal::config('redirect.settings')->get('default_status_code')))) + redirect_status_code_options(),
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
   * {@inheritdoc}
   */
  public function validateForm(array &$form, array &$form_state) {
    $redirect = (object) $form_state['values'];

    if (empty($form_state['values']['override'])) {
      if ($existing = redirect_load_by_source($redirect->source, $redirect->language)) {
        if ($redirect->rid != $existing->rid && $redirect->language == $existing->language) {
          // The "from" path should not conflict with another redirect
          $form_state['storage']['override_messages']['redirect-conflict'] = t('The base source path %source is already being redirected. Do you want to <a href="@edit-page">edit the existing redirect</a>?', array('%source' => $redirect->source, '@edit-page' => url('admin/config/search/redirect/edit/'. $existing->rid)));
          $form_state['rebuild'] = TRUE;
        }
      }

      if ($form['override']['#access']) {
        drupal_set_message('Did you read the warnings and click the checkbox?', 'error');
        $form_state['rebuild'] = TRUE;
        //form_set_error('override', 'CLICK DA BUTTON!');
      }
    }

    redirect_validate($redirect, $form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    form_state_values_clean($form_state);
    $redirect = (object) $form_state['values'];
    redirect_save($redirect);
    drupal_set_message(t('The redirect has been saved.'));
    $form_state['redirect'] = 'admin/config/search/redirect';
  }
}
