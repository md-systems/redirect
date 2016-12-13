<?php

/**
 * @file
 * Contains \Drupal\redirect\RedirectStorageSchema.
 */

namespace Drupal\redirect;

use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\Sql\SqlContentEntityStorageSchema;

/**
 * Defines the redirect schema.
 */
class RedirectStorageSchema extends SqlContentEntityStorageSchema {

  /**
   * {@inheritdoc}
   */
  protected function getEntitySchema(ContentEntityTypeInterface $entity_type, $reset = FALSE) {
    $schema = parent::getEntitySchema($entity_type, $reset);

    // Add indexes.
    $schema['redirect']['unique keys'] += [
      'hash' => ['hash'],
    ];
    $schema['redirect']['indexes'] += [
      // Limit length to 191.
      'source_language' => [['redirect_source__path', 191], 'language'],
    ];
    $schema['redirect_error'] = array(
      'description' => 'Stores information on redirect errors.',
      'fields' => array(
        'reid' => array(
          'type' => 'serial',
          'not null' => TRUE,
          'description' => 'Primary Key: Unique redirect error ID.',
        ),
        'source' => array(
          'type' => 'varchar',
          'length' => 255,
          'not null' => TRUE,
          'description' => 'The source path where the error happened.',
        ),
        'uid' => array(
          'type' => 'int',
          'unsigned' => TRUE,
          'not null' => TRUE,
          'default' => 0,
          'description' => 'The {users}.uid of the user who created the error.',
        ),
        'language' => array(
          'description' => 'The language this error is for; if blank, language is undefined',
          'type' => 'varchar',
          'length' => 12,
          'not null' => TRUE,
          'default' => 'und',
        ),
        'timestamp' => array(
          'type' => 'int',
          'unsigned' => TRUE,
          'not null' => TRUE,
          'default' => 0,
          'description' => 'The timestamp of when the error was created.'
        ),
      ),
      'primary key' => array('reid'),
      'indexes' => array(
        'source_language' => array('source', 'language'),
      ),
    );
    return $schema;
  }

}
