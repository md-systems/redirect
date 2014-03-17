<?php
/**
 * @file
 * Contains \Drupal\redirect\Entity\Redirect.
 */

namespace Drupal\redirect\Entity;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityStorageControllerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\FieldDefinition;
use Drupal\Core\Language\Language;

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
 *   fieldable = FALSE,
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
      'source_options' => array(),
      'redirect_options' => array(),
      'language' => Language::LANGCODE_NOT_SPECIFIED,
      'status_code' => 0,
      'count' => 0,
      'access' => 0,
      'hash' => '',
    );
  }

  /**
   * {@inheritdoc}
   */
  public function postCreate(EntityStorageControllerInterface $storage_controller) {
    // Run the getHash method to initialise the hash value.
    $this->getHash();
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

  public function setSource($source) {
    $this->set('source', $source);
  }

  public function getSource() {
    return $this->get('source')->value;
  }

  public function setRedirect($redirect) {
    $this->set('redirect', $redirect);
  }

  public function getRedirect() {
    return $this->get('redirect')->value;
  }

  public function setSourceOptions(array $options) {
    $this->set('source_options', $options);
  }

  public function getSourceOptions() {
    if (!is_string($this->get('source_options')->value) && unserialize($this->get('source_options')->value)) {
      return unserialize($this->get('source_options')->value);
    }
    elseif (is_array($this->get('source_options')->value)) {
      return $this->get('source_options')->value;
    }

    return array();
  }

  public function getSourceOption($key) {
    $options = $this->getSourceOptions();
    return isset($options[$key]) ? $options[$key] : FALSE;
  }

  public function setRedirectOptions(array $options) {
    $this->set('redirect_options', $options);
  }

  public function getRedirectOptions() {
    return $this->get('redirect_options')->value;
  }

  public function getRedirectOption($key) {
    $options = $this->getRedirectOptions();
    return isset($options[$key]) ? $options[$key] : FALSE;
  }

  public function getHash() {

    if ($hash = $this->get('hash')->value) {
      return $hash;
    }

    $hash = array(
      'source' => $this->getSource(),
      'language' => $this->getLanguage(),
    );

    if ($query = $this->getSourceOption('query')) {
      $hash['source_query'] = $query;
    }

    \Drupal::moduleHandler()->alter('redirect_hash', $hash, $this);

    redirect_sort_recursive($hash, 'ksort');
    $hash = Crypt::hashBase64(serialize($hash));
    $this->set('hash', $hash);

    return $hash;
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
      ->setLabel(t('Source '))
      ->setDescription(t('The source url.'))->setTranslatable(FALSE);

    $fields['redirect'] = FieldDefinition::create('link')
      ->setLabel(t('Redirect'))
      ->setDescription(t('The redirect url.'))->setTranslatable(FALSE);

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
