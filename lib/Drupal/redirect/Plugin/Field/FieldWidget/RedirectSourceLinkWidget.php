<?php

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

  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, array &$form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);
    $element['status_box'] = array(
      '#prefix' => '<div id="redirect-link-status">',
      '#suffix' => '</div>',
    );

    if (isset($form_state['values']['source'][0]['url'])) {

      // Warning about creating a redirect from a valid path.
      // @todo - Hmm... exception driven logic. Find a better way how to
      //   determine if we have a valid path.
      try {
        \Drupal::service('router')->match('/' . $form_state['values']['source'][0]['url']);
        $element['status_box'][]['#markup'] = '<div class="messages messages--warning">' . t('The source path %path is likely a valid path. It is preferred to <a href="@url-alias">create URL aliases</a> for existing paths rather than redirects.',
            array('%path' => $form_state['values']['source'][0]['url'], '@url-alias' => url('admin/config/search/path/add'))) . '</div>';
      }
      catch (ResourceNotFoundException $e) {
        // Do nothing, expected behaviour.
      }

      // Warning about the path being already redirected.
      $parsed_url = parse_url($form_state['values']['source'][0]['url']);
      $path = isset($parsed_url['path']) ? $parsed_url['path'] : NULL;
      if (!empty($path)) {
        $redirects = \Drupal::entityManager()
          ->getStorageController('redirect')
          ->loadByProperties(array('source__url' => $path));
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

    return $element;
  }

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
    foreach ($values as &$value) {
      if (!empty($value['url'])) {
        try {
          $url = Url::createFromPath($value['url']);
          $url->setOption('attributes', $value['attributes']);
          $value += $url->toArray();
        }
        catch (NotFoundHttpException $e) {
          // Nothing to do here, validateUrl() emits form validation errors.
        }
        catch (MatchingRouteNotFoundException $e) {
          // User is creating a redirect from non existing path. This is not an
          // error state. Set default values.
          $value += array(
            'route_name' => NULL,
            'route_parameters' => array(),
            'options' => array(
              'attributes' => array(),
            ),
          );
        }
        catch (ParamNotConvertedException $e) {
          // Nothing to do here, validateUrl() emits form validation errors.
        }
      }
    }
    return $values;
  }
}
