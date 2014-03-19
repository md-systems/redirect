<?php
/**
 * @file
 * Contains \Drupal\redirect\Entity\Redirect.
 */

namespace Drupal\redirect\Entity;

use Drupal\Component\Utility\Crypt;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityStorageControllerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\FieldDefinition;
use Drupal\Core\Language\Language;
use Drupal\link\LinkItemInterface;

/**
 * The redirect entity class.
 *
 * @ContentEntityType(
 *   id = "redirect",
 *   label = @Translation("Redirect"),
 *   bundle_label = @Translation("Redirect type"),
 *   controllers = {
 *     "form" = {
 *       "default" = "Drupal\redirect\Form\RedirectFormController",
 *       "delete" = "Drupal\redirect\Form\RedirectDeleteForm",
 *       "edit" = "Drupal\redirect\Form\RedirectFormController"
 *     },
 *   },
 *   base_table = "redirect",
 *   fieldable = TRUE,
 *   translatable = FALSE,
 *   entity_keys = {
 *     "id" = "rid",
 *     "label" = "redirect",
 *     "uuid" = "uuid",
 *     "bundle" = "type"
 *   }
 * )
 */
class Redirect extends ContentEntityBase {

  public static function generateHash($path, $query, $language) {
    $hash = array(
      'source' => $path,
      'language' => $language,
    );

    if (!empty($query)) {
      $hash['source_query'] = $query;
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
  public static function preCreate(EntityStorageControllerInterface $storage_controller, array &$values) {
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
  public function preSave(EntityStorageControllerInterface $storage_controller) {
    $source = $this->getSource();
    $this->set('hash', Redirect::generateHash($source['url'], $this->getSourceOption('query'), $this->getLanguage()));
  }

  public function setType($type) {
    $this->set('type', $type);
  }

  public function getType() {
    return $this->get('type')->value;
  }

  public function setLanguage($language) {
    $this->set('language', $language);
  }

  public function getLanguage() {
    return $this->get('language')->value;
  }

  public function setStatusCode($status_code) {
    $this->set('status_code', $status_code);
  }

  public function getStatusCode() {
    return $this->get('status_code')->value;
  }

  public function setSource($url, array $options = array()) {
    $this->source->set(0, array(
      'url' => $url,
      'options' => $options,
    ));
  }

  public function getSource() {
    return $this->get('source')->get(0)->getValue();
  }

  public function getSourceUrl() {
    $source = $this->getSource();
    return $source['url'];
  }

  public function getRedirect() {
    return $this->get('redirect')->get(0)->getValue();
  }

  public function getRedirectUrl() {
    $redirect = $this->getRedirect();
    return $redirect['url'];
  }

  public function getSourceOptions() {
    $redirect = $this->getSource();
    // @todo - shouldn't this work out of the box?
    if (!is_array($redirect['options'])) {
      return unserialize($redirect['options']);
    }
    return $redirect['options'];
  }

  public function getSourceOption($key) {
    $options = $this->getSourceOptions();
    return isset($options[$key]) ? $options[$key] : FALSE;
  }

  public function getRedirectOptions() {
    $redirect = $this->getRedirect();
    return $redirect['options'];
  }

  public function getRedirectOption($key) {
    $options = $this->getRedirectOptions();
    return isset($options[$key]) ? $options[$key] : FALSE;
  }

  public function getHash() {
    return $this->get('hash')->value;
  }

  public function setAccess($access) {
    $this->set('access', $access);
  }

  public function getAccess() {
    return $this->get('access')->value;
  }

  public function setCount($count) {
    $this->set('count', $count);
  }

  public function getCount() {
    return $this->get('count')->value;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields['rid'] = FieldDefinition::create('integer')
      ->setLabel(t('Redirect ID'))
      ->setDescription(t('The redirect ID.'))
      ->setReadOnly(TRUE);

    $fields['uuid'] = FieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The record UUID.'))
      ->setReadOnly(TRUE);

    $fields['hash'] = FieldDefinition::create('string')
      ->setLabel(t('Hash'))
      ->setDescription(t('The redirect hash.'));

    $fields['type'] = FieldDefinition::create('string')
      ->setLabel(t('Type'))
      ->setDescription(t('The redirect type.'));

    $fields['uid'] = FieldDefinition::create('entity_reference')
      ->setLabel(t('User ID'))
      ->setDescription(t('The user ID of the node author.'))
      ->setSettings(array(
        'target_type' => 'user',
        'default_value' => 0,
      ));

    $fields['source'] = FieldDefinition::create('link')
      ->setLabel(t('From'))
      ->setDescription(t("Enter an internal Drupal path or path alias to redirect (e.g. %example1 or %example2). Fragment anchors (e.g. %anchor) are <strong>not</strong> allowed.", array('%example1' => 'node/123', '%example2' => 'taxonomy/term/123', '%anchor' => '#anchor')))
      ->setRequired(TRUE)
      ->setTranslatable(FALSE)
      ->setSettings(array(
        'default_value' => '',
        'max_length' => 560,
        'url_type' => LinkItemInterface::LINK_INTERNAL,
      ))
      ->setDisplayOptions('view', array(
        'label' => 'hidden',
        'type' => 'link_redirect',
        'weight' => -5,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'link_redirect',
        'weight' => -5,
      ))
      ->setDisplayConfigurable('form', TRUE);

    $fields['redirect'] = FieldDefinition::create('link')
      ->setLabel(t('To'))
      ->setRequired(TRUE)
      ->setTranslatable(FALSE)
      ->setSettings(array(
        'default_value' => '',
        'max_length' => 560,
        'url_type' => LinkItemInterface::LINK_GENERIC,
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

    $fields['language'] = FieldDefinition::create('language')
      ->setLabel(t('Language code'))
      ->setDescription(t('The node language code.'));

    $fields['status_code'] = FieldDefinition::create('integer')
      ->setLabel(t('Status code'))
      ->setDescription(t('The redirect status code.'));

    $fields['count'] = FieldDefinition::create('integer')
      ->setLabel(t('Count'))
      ->setDescription(t('The redirect count.'));

    $fields['access'] = FieldDefinition::create('integer')
      ->setLabel(t('Access timestamp'))
      ->setDescription(t('The timestamp the redirect was last accessed.'));

    return $fields;
  }

}
