<?php
/**
 * @file
 * Contains \Drupal\redirect\Entity\RedirectEntity.
 */

namespace Drupal\redirect\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityStorageControllerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\FieldDefinition;

/**
 * The redirect entity class.
 *
 * @ContentEntityType(
 *   id = "redirect",
 *   label = @Translation("Redirect"),
 *   bundle_label = @Translation("Redirect type"),
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
class RedirectEntity extends ContentEntityBase {

  /**
   * {@inheritdoc}
   */
  public function id() {
    return $this->get('rid')->value;
  }

  /**
   * {@inheritdoc}
   */
  public static function postLoad(EntityStorageControllerInterface $storage_controller, array &$entities) {
    // Unserialize the URL option fields.
    // @todo - this should not be necessary as the schema fields declare
    //   "serialise".
    foreach ($entities as $key => $redirect) {
      $entities[$key]->source_options = unserialize($redirect->source_options);
      $entities[$key]->redirect_options = unserialize($redirect->redirect_options);
    }
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
    // @todo - provide field definitions.
    $fields['sensor_name'] = FieldDefinition::create('string')
      ->setLabel(t('Sensor name'))
      ->setDescription(t('The machine name of the sensor.'));

    $fields['sensor_status'] = FieldDefinition::create('string')
      ->setLabel(t('Sensor status'))
      ->setDescription(t('The sensor status at the moment of the sensor run.'));

    $fields['sensor_value'] = FieldDefinition::create('string')
      ->setLabel(t('Sensor value'))
      ->setDescription(t('The sensor value at the moment of the sensor run.'));

    $fields['sensor_message'] = FieldDefinition::create('string')
      ->setLabel(t('Sensor message'))
      ->setDescription(t('The sensor message reported by the sensor.'));

    // @todo Convert to a "created" field in https://drupal.org/node/2145103.
    $fields['timestamp'] = FieldDefinition::create('integer')
      ->setLabel(t('Timestamp'))
      ->setDescription(t('The time that the sensor was executed.'));

    $fields['execution_time'] = FieldDefinition::create('string')
      ->setLabel(t('Execution time'))
      ->setDescription(t('The time needed for the sensor to execute in ms.'));

    return $fields;
  }

}
