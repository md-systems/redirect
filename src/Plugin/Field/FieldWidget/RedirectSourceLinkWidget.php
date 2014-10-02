<?php

/**
 * @file
 * Contains \Drupal\redirect\Plugin\Field\FieldWidget\RedirectSourceLinkWidget
 */

namespace Drupal\redirect\Plugin\Field\FieldWidget;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\link\Plugin\Field\FieldWidget\LinkWidget;
use Drupal\Core\Url;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;

/**
 * Plugin implementation of the 'link' widget for the redirect module.
 *
 * Note that this field is meant only for the source field of the redirect
 * entity as it drops validation for non existing paths.
 *
 * @FieldWidget(
 *   id = "redirect_link",
 *   label = @Translation("Redirect link"),
 *   field_types = {
 *     "link"
 *   },
 *   settings = {
 *     "placeholder_url" = "",
 *     "placeholder_title" = ""
 *   }
 * )
 */
class RedirectSourceLinkWidget extends LinkWidget {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $default_url_value = NULL;
    if (isset($items[$delta]->url)) {
      try {
        $url = Url::fromUri('base://' . $items[$delta]->url);
        $url->setOptions($items[$delta]->options);
        $default_url_value = ltrim($url->toString(), '/');
      }
      // If the path has no matching route reconstruct it manually.
      catch (ResourceNotFoundException $e) {
        $default_url_value = $items[$delta]->url;
        if (isset($items[$delta]->options['query']) && is_array($items[$delta]->options['query'])) {
          $default_url_value .= '?';
          $i = 0;
          foreach ($items[$delta]->options['query'] as $key => $value) {
            if ($i > 0) {
              $default_url_value .= '&';
            }
            $default_url_value .= "$key=$value";
            $i++;
          }
        }
      }
    }
    $element['url'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('URL'),
      '#placeholder' => $this->getSetting('placeholder_url'),
      '#default_value' => $default_url_value,
      '#maxlength' => 2048,
      '#required' => $element['#required'],
      '#field_prefix' => \Drupal::url('<front>', array(), array('absolute' => TRUE)),
    );

    // Exposing the attributes array in the widget is left for alternate and more
    // advanced field widgets.
    $element['attributes'] = array(
      '#type' => 'value',
      '#tree' => TRUE,
      '#value' => !empty($items[$delta]->options['attributes']) ? $items[$delta]->options['attributes'] : array(),
      '#attributes' => array('class' => array('link-field-widget-attributes')),
    );

    // If cardinality is 1, ensure a label is output for the field by wrapping it
    // in a details element.
    if ($this->fieldDefinition->getCardinality() == 1) {
      $element += array(
        '#type' => 'fieldset',
      );
    }

    // If creating new URL add checks.
    if ($items->getEntity()->isNew()) {
      $element['status_box'] = array(
        '#prefix' => '<div id="redirect-link-status">',
        '#suffix' => '</div>',
      );

      if ($form_state->hasValue(array('redirect_source', 0, 'url'))) {

        // Warning about creating a redirect from a valid path.
        // @todo - Hmm... exception driven logic. Find a better way how to
        //   determine if we have a valid path.
        try {
          \Drupal::service('router')->match('/' . $form_state->getValue(array('redirect_source', 0, 'url')));
          $element['status_box'][]['#markup'] = '<div class="messages messages--warning">' . t('The source path %path is likely a valid path. It is preferred to <a href="@url-alias">create URL aliases</a> for existing paths rather than redirects.',
              array('%path' => $form_state->getValue(array('redirect_source', 0, 'url')), '@url-alias' => url('admin/config/search/path/add'))) . '</div>';
        }
        catch (ResourceNotFoundException $e) {
          // Do nothing, expected behaviour.
        }

        // Warning about the path being already redirected.
        $parsed_url = UrlHelper::parse(trim($form_state->getValue(array('redirect_source', 0, 'url'))));
        $path = isset($parsed_url['path']) ? $parsed_url['path'] : NULL;
        if (!empty($path)) {
          /** @var \Drupal\redirect\RedirectRepository $repository */
          $repository = \Drupal::service('redirect.repository');
          $redirects = $repository->findBySourcePath($path);
          if (!empty($redirects)) {
            $redirect = array_shift($redirects);
            $element['status_box'][]['#markup'] = '<div class="messages messages--warning">' . t('The base source path %source is already being redirected. Do you want to <a href="@edit-page">edit the existing redirect</a>?', array('%source' => $redirect->getSourceUrl(), '@edit-page' => url('admin/config/search/redirect/edit/'. $redirect->id()))) . '</div>';
          }
        }
      }

      $element['url']['#ajax'] = array(
        'callback' => 'redirect_source_link_get_status_messages',
        'wrapper' => 'redirect-link-status',
      );
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    $values = parent::massageFormValues($values, $form, $form_state);
    // It is likely that the url provided for this field is not existing and
    // so the logic in the parent method did not set any defaults. Just run
    // through all url values and add defaults.
    foreach ($values as &$value) {
      if (!empty($value['url'])) {
        // In case we have query process the url.
        if (strpos($value['url'], '?') !== FALSE) {
          $url = UrlHelper::parse($value['url']);
          $value['url'] = $url['path'];
          $value['options']['query'] = $url['query'];
        }
        $value += array(
          'route_name' => NULL,
          'route_parameters' => array(),
          'options' => array(
            'attributes' => array(),
          ),
        );
      }
    }
    return $values;
  }
}
