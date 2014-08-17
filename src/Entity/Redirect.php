<?php
/**
 * @file
 * Contains \Drupal\redirect\Entity\Redirect.
 */

namespace Drupal\redirect\Entity;

use Drupal\Component\Utility\Crypt;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Language\Language;
use Drupal\Core\Url;
use Drupal\link\LinkItemInterface;

/**
 * The redirect entity class.
 *
 * @ContentEntityType(
 *   id = "redirect",
 *   label = @Translation("Redirect"),
 *   bundle_label = @Translation("Redirect type"),
 *   controllers = {
 *     "list_builder" = "Drupal\redirect\Controller\RedirectListBuilder",
 *     "form" = {
 *       "default" = "Drupal\redirect\Form\RedirectForm",
 *       "delete" = "Drupal\redirect\Form\RedirectDeleteForm",
 *       "edit" = "Drupal\redirect\Form\RedirectForm"
 *     }
 *   },
 *   base_table = "redirect",
 *   fieldable = TRUE,
 *   translatable = FALSE,
 *   admin_permission = "administer redirects",
 *   entity_keys = {
 *     "id" = "rid",
 *     "label" = "source",
 *     "uuid" = "uuid",
 *     "bundle" = "type"
 *   },
 *   links = {
 *     "delete-form" = "redirect.delete",
 *     "edit-form" = "redirect.edit",
 *   }
 * )
 */
class Redirect extends ContentEntityBase {

  /**
   * Generates a unique hash for identification purposes.
   *
   * @param string $source_path
   *   Source path of the redirect.
   * @param array $source_query
   *   Source query as an array.
   * @param string $language
   *   Redirect language.
   *
   * @return string
   *   Base 64 hash.
   */
  public static function generateHash($source_path, array $source_query, $language) {
    $hash = array(
      'source' => $source_path,
      'language' => $language,
    );

    if (!empty($source_query)) {
      $hash['source_query'] = $source_query;
    }
    redirect_sort_recursive($hash, 'ksort');
    return Crypt::hashBase64(serialize($hash));
  }

  /**
   * {@inheritdoc}
   */
  public function id() {
    return $this->get('rid')->value;
  }

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    $values += array(
      'type' => 'redirect',
      'uid' => \Drupal::currentUser()->id(),
      'language' => Language::LANGCODE_NOT_SPECIFIED,
      'status_code' => 0,
      'count' => 0,
      'access' => 0,
    );
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage_controller) {
    $source = $this->getSource();
    $this->set('hash', Redirect::generateHash($source['url'], $this->getSourceOption('query', array()), $this->getLanguage()));
  }

  /**
   * {@inheritdoc}
   *
   * @todo - here we unserialize the options fields for source and redirect.
   *   Shouldn't this be done automatically?
   */
  public static function postLoad(EntityStorageInterface $storage_controller, array &$entities) {
    foreach ($entities as $entity) {
      $i = 0;
      foreach ($entity->get('redirect_source') as $source) {
        if (is_string($source->options)) {
          $entity->redirect_source->get($i)->options = unserialize($source->options);
        }
        if (is_string($source->route_parameters)) {
          $entity->redirect_source->get($i)->route_parameters = unserialize($source->route_parameters);
        }
        $i++;
      }
      $i = 0;
      foreach ($entity->get('redirect_redirect') as $redirect) {
        if (is_string($redirect->options)) {
          $entity->redirect_redirect->get($i)->options = unserialize($redirect->options);
        }
        else {
          $entity->redirect_redirect->get($i)->options = array();
        }
        if (is_string($redirect->route_parameters)) {
          $entity->redirect_redirect->get($i)->route_parameters = unserialize($redirect->route_parameters);
        }
        else {
          $entity->redirect_redirect->get($i)->route_parameters = array();
        }
        $i++;
      }
    }
  }

  /**
   * Sets the redirect entity bundle.
   *
   * @param string $type
   *   Redirect entity bundle.
   */
  public function setType($type) {
    $this->set('type', $type);
  }

  /**
   * Gets the redirect entity bundle.
   *
   * @return string
   *   The redirect entity budnle.
   */
  public function getType() {
    return $this->get('type')->value;
  }

  /**
   * Sets the redirect language.
   *
   * @param string $language
   *   Language code.
   */
  public function setLanguage($language) {
    $this->set('language', $language);
  }

  /**
   * Gets the redirect language.
   *
   * @return string
   *   The redirect language.
   */
  public function getLanguage() {
    return $this->get('language')->value;
  }

  /**
   * Sets the redirect status code.
   *
   * @param int $status_code
   *   The redirect status code.
   */
  public function setStatusCode($status_code) {
    $this->set('status_code', $status_code);
  }

  /**
   * Gets the redirect status code.
   *
   * @return int
   *   The redirect status code.
   */
  public function getStatusCode() {
    return $this->get('status_code')->value;
  }

  /**
   * Sets the source URL data.
   *
   * @param string $url
   *   The base url of the source.
   * @param array $options
   *   The source url options.
   */
  public function setSource($url, array $options = array()) {
    $value = array();
    try {
      $parsed_url = UrlHelper::parse($url);

      $url = Url::createFromPath($parsed_url['path']);
      if (!empty($parsed_url['query'])) {
        $url->setOption('query', $parsed_url['query']);
      }
      if (!empty($parsed_url['fragment'])) {
        $url->setOption('fragment', $parsed_url['fragment']);
      }

      $value += $url->toArray();
      // Reset the URL value to contain only the path.
      $value['url'] = $parsed_url['path'];
    }
    // We have invalid URL - for the source process anyway.
    catch (\Exception $e) {
      // In case we have query process the url.
      if (strpos($url, '?') !== FALSE) {
        $url = UrlHelper::parse($value['url']);
        $value['url'] = $url['path'];
        $value['options']['query'] = $url['query'];
      }
      else {
        $value['url'] = $url;
      }
      $value += array(
        'route_name' => NULL,
        'route_parameters' => array(),
        'options' => array(
          'attributes' => array(),
        ),
      );
    }

    $value['options'] += $options;

    $this->redirect_source->set(0, $value);
  }

  /**
   * Gets the source URL data.
   *
   * @return array
   */
  public function getSource() {
    return $this->get('redirect_source')->get(0)->getValue();
  }

  /**
   * Gets the source base URL.
   *
   * @return string
   */
  public function getSourceUrl() {
    $query_string = '';
    $i = 0;
    foreach ($this->getSourceOption('query', array()) as $key => $value) {
      if ($i > 0) {
        $query_string .= '&';
      }
      $query_string .= "$key=$value";
      $i++;
    }
    return $this->get('redirect_source')->url . (!empty($query_string) ? '?' . $query_string : '');
  }

  /**
   * Gets the redirect URL data.
   *
   * @return array
   *   The redirect URL data.
   */
  public function getRedirect() {
    return $this->get('redirect_redirect')->get(0)->getValue();
  }

  /**
   * Sets the source URL data.
   *
   * @param string $url
   *   The base url of the source.
   * @param array $options
   *   The source url options.
   */
  public function setRedirect($url, array $options = array()) {
    $value = array();

    $parsed_url = UrlHelper::parse($url);

    /** @var \Drupal\Core\Path\AliasManager $alias_manager */
    $alias_manager = \Drupal::service('path.alias_manager');
    // Make sure we have the system path.
    $parsed_url['path'] = $alias_manager->getPathByAlias($parsed_url['path']);

    $url = Url::createFromPath($parsed_url['path']);
    if (!empty($parsed_url['query'])) {
      $url->setOption('query', $parsed_url['query']);
    }
    if (!empty($parsed_url['fragment'])) {
      $url->setOption('fragment', $parsed_url['fragment']);
    }

    $value += $url->toArray();
    // Reset the URL value to contain only the path.
    $value['url'] = $parsed_url['path'];

    $value['options'] += $options;

    $this->redirect_redirect->set(0, $value);
  }

  /**
   * Gets the redirect URL.
   *
   * @return string
   *   The redirect URL.
   */
  public function getRedirectUrl() {
    $query_string = '';
    $i = 0;
    foreach ($this->getRedirectOption('query', array()) as $key => $value) {
      if ($i > 0) {
        $query_string .= '&';
      }
      $query_string .= "$key=$value";
      $i++;
    }
    return $this->get('redirect_redirect')->url . (!empty($query_string) ? '?' . $query_string : '');
  }

  /**
   * Gets the source URL options.
   *
   * @return array
   *   The source URL options.
   */
  public function getSourceOptions() {
    return $this->get('redirect_source')->options;
  }

  /**
   * Gets a specific source URL option.
   *
   * @param string $key
   *   Option key.
   * @param mixed $default
   *   Default value used in case option does not exist.
   *
   * @return mixed
   *   The option value.
   */
  public function getSourceOption($key, $default = NULL) {
    $options = $this->getSourceOptions();
    return isset($options[$key]) ? $options[$key] : $default;
  }

  /**
   * Gets the redirect URL options.
   *
   * @return array
   *   The redirect URL options.
   */
  public function getRedirectOptions() {
    return $this->get('redirect_redirect')->options;
  }

  /**
   * Gets a specific redirect URL option.
   *
   * @param string $key
   *   Option key.
   * @param mixed $default
   *   Default value used in case option does not exist.
   *
   * @return mixed
   *   The option value.
   */
  public function getRedirectOption($key, $default = NULL) {
    $options = $this->getRedirectOptions();
    return isset($options[$key]) ? $options[$key] : $default;
  }

  public function getRedirectRouteName() {
    return $this->get('redirect_redirect')->route_name;
  }

  public function getRedirectRouteParameters() {
    return $this->get('redirect_redirect')->route_parameters;
  }

  /**
   * Gets the current redirect entity hash.
   *
   * @return string
   *   The hash.
   */
  public function getHash() {
    return $this->get('hash')->value;
  }

  /**
   * Sets the last access timestamp.
   *
   * @param int $access
   *   The last access timestamp.
   */
  public function setLastAccessed($access) {
    $this->set('access', $access);
  }

  /**
   * Gets the last access timestamp.
   *
   * @return int
   *   The last accessed timestamp.
   */
  public function getLastAccessed() {
    return $this->get('access')->value;
  }

  /**
   * Sets the count.
   *
   * @param int $count
   *   The count.
   */
  public function setCount($count) {
    $this->set('count', $count);
  }

  /**
   * Gets the count.
   *
   * @return int
   *   The count.
   */
  public function getCount() {
    return $this->get('count')->value;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields['rid'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Redirect ID'))
      ->setDescription(t('The redirect ID.'))
      ->setReadOnly(TRUE);

    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The record UUID.'))
      ->setReadOnly(TRUE);

    $fields['hash'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Hash'))
      ->setDescription(t('The redirect hash.'));

    $fields['type'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Type'))
      ->setDescription(t('The redirect type.'));

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('User ID'))
      ->setDescription(t('The user ID of the node author.'))
      ->setSettings(array(
        'target_type' => 'user',
        'default_value' => 0,
      ));

    $fields['redirect_source'] = BaseFieldDefinition::create('redirect_source_link')
      ->setLabel(t('From'))
      ->setDescription(t("Enter an internal Drupal path or path alias to redirect (e.g. %example1 or %example2). Fragment anchors (e.g. %anchor) are <strong>not</strong> allowed.", array('%example1' => 'node/123', '%example2' => 'taxonomy/term/123', '%anchor' => '#anchor')))
      ->setRequired(TRUE)
      ->setTranslatable(FALSE)
      ->setSettings(array(
        'default_value' => '',
        'max_length' => 560,
        'link_type' => LinkItemInterface::LINK_INTERNAL,
      ))
      ->setDisplayOptions('view', array(
        'label' => 'hidden',
        'type' => 'redirect_link',
        'weight' => -5,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'redirect_link',
        'weight' => -5,
      ))
      ->setDisplayConfigurable('form', TRUE);

    $fields['redirect_redirect'] = BaseFieldDefinition::create('link')
      ->setLabel(t('To'))
      ->setRequired(TRUE)
      ->setTranslatable(FALSE)
      ->setSettings(array(
        'default_value' => '',
        'max_length' => 560,
        'link_type' => LinkItemInterface::LINK_GENERIC,
      ))
      ->setDisplayOptions('view', array(
        'label' => 'hidden',
        'type' => 'link',
        'weight' => -4,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'link',
        'weight' => -4,
      ))
      ->setDisplayConfigurable('form', TRUE);

    $fields['language'] = BaseFieldDefinition::create('language')
      ->setLabel(t('Language code'))
      ->setDescription(t('The node language code.'));

    $fields['status_code'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Status code'))
      ->setDescription(t('The redirect status code.'));

    $fields['count'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Count'))
      ->setDescription(t('The redirect count.'));

    $fields['access'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Access timestamp'))
      ->setDescription(t('The timestamp the redirect was last accessed.'));

    return $fields;
  }

}
