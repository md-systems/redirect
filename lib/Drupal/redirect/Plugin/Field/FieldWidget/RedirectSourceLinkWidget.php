<?php

/**
 * @file
 * Contains \Drupal\redirect\Plugin\Field\FieldWidget\RedirectSourceLinkWidget
 */

namespace Drupal\redirect\Plugin\Field\FieldWidget;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\ParamConverter\ParamNotConvertedException;
use Drupal\Core\Routing\MatchingRouteNotFoundException;
use Drupal\link\LinkItemInterface;
use Drupal\link\Plugin\Field\FieldWidget\LinkWidget;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Drupal\Core\Url;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;

/**
 * Plugin implementation of the 'link' widget for the redirect module.
 *
 * Note that this field is meant only for the source field of the redirect
 * entity as it drops validation for non existing paths.
 *
 * @FieldWidget(
 *   id = "link_redirect",
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
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, array &$form_state) {
    $url_type = $this->getFieldSetting('url_type');

    $default_url_value = NULL;
    if (isset($items[$delta]->url)) {
      try {
        $url = Url::createFromPath($items[$delta]->url);
        $url->setOptions($items[$delta]->options);
        $default_url_value = ltrim($url->toString(), '/');
      }
      // If the path has no matching route reconstruct it manually.
      catch (MatchingRouteNotFoundException $e) {
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
      '#type' => 'url',
      '#title' => $this->t('URL'),
      '#placeholder' => $this->getSetting('placeholder_url'),
      '#default_value' => $default_url_value,
      '#maxlength' => 2048,
      '#required' => $element['#required'],
    );

    // If the field is configured to allow internal paths, it cannot use the
    // 'url' form element and we have to do the validation ourselves.
    if ($url_type & LinkItemInterface::LINK_INTERNAL) {
      $element['url']['#type'] = 'textfield';
      $element['#element_validate'][] = array($this, 'validateUrl');
    }

    // If the field is configured to allow only internal paths, add a useful
    // element prefix.
    if ($url_type == LinkItemInterface::LINK_INTERNAL) {
      $element['url']['#field_prefix'] = \Drupal::url('<front>', array(), array('absolute' => TRUE));
    }
    // If the field is configured to allow both internal and external paths,
    // show a useful description.
    elseif ($url_type == LinkItemInterface::LINK_GENERIC) {
      $element['url']['#description'] = $this->t('This can be an internal Drupal path such as %add-node or an external URL such as %drupal. Enter %front to link to the front page.', array('%front' => '<front>', '%add-node' => 'node/add', '%drupal' => 'http://drupal.org'));
    }

    $element['title'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Link text'),
      '#placeholder' => $this->getSetting('placeholder_title'),
      '#default_value' => isset($items[$delta]->title) ? $items[$delta]->title : NULL,
      '#maxlength' => 255,
      '#access' => $this->getFieldSetting('title') != DRUPAL_DISABLED,
    );
    // Post-process the title field to make it conditionally required if URL is
    // non-empty. Omit the validation on the field edit form, since the field
    // settings cannot be saved otherwise.
    $is_field_edit_form = ($element['#entity'] === NULL);
    if (!$is_field_edit_form && $this->getFieldSetting('title') == DRUPAL_REQUIRED) {
      $element['#element_validate'][] = array($this, 'validateTitle');
    }

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

      if (isset($form_state['values']['redirect_source'][0]['url'])) {

        // Warning about creating a redirect from a valid path.
        // @todo - Hmm... exception driven logic. Find a better way how to
        //   determine if we have a valid path.
        try {
          \Drupal::service('router')->match('/' . $form_state['values']['redirect_source'][0]['url']);
          $element['status_box'][]['#markup'] = '<div class="messages messages--warning">' . t('The source path %path is likely a valid path. It is preferred to <a href="@url-alias">create URL aliases</a> for existing paths rather than redirects.',
              array('%path' => $form_state['values']['redirect_source'][0]['url'], '@url-alias' => url('admin/config/search/path/add'))) . '</div>';
        }
        catch (ResourceNotFoundException $e) {
          // Do nothing, expected behaviour.
        }

        // Warning about the path being already redirected.
        $parsed_url = UrlHelper::parse(trim($form_state['values']['redirect_source'][0]['url']));
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
  public function validateUrl(&$element, &$form_state, $form) {
    $url_type = $this->getFieldSetting('url_type');
    $url_is_valid = TRUE;

    // Validate the 'url' element.
    if ($element['url']['#value'] !== '') {
      try {
        $url = Url::createFromPath($element['url']['#value']);

        if ($url->isExternal() && !UrlHelper::isValid($element['url']['#value'], TRUE)) {
          $url_is_valid = FALSE;
        }
        elseif ($url->isExternal() && $url_type == LinkItemInterface::LINK_INTERNAL) {
          $url_is_valid = FALSE;
        }
      }
      catch (NotFoundHttpException $e) {
        $url_is_valid = FALSE;
      }
      catch (MatchingRouteNotFoundException $e) {
        // User is creating a redirect from non existing path. This is not an
        // error state.
        $url_is_valid = TRUE;
      }
      catch (ParamNotConvertedException $e) {
        $url_is_valid = FALSE;
      }
    }

    if (!$url_is_valid) {
      \Drupal::formBuilder()->setError($element['url'], $form_state, $this->t('The URL %url is not valid.', array('%url' => $element['url']['#value'])));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, array &$form_state) {
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
