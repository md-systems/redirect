<?php

/**
 * @file
 * Contains \Drupal\redirect\Tests\Migrate\d6\PathRedirectTest.
 */

namespace Drupal\Tests\redirect\Tests\Migrate\d6;

use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\redirect\Entity\Redirect;
use Drupal\migrate\Entity\Migration;
use Drupal\Tests\migrate_drupal\Kernel\MigrateDrupalTestBase;


/**
 * Tests the d6_path_redirect source plugin.
 *
 * @group redirect
 */
class PathRedirectTest extends MigrateDrupalTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = array('redirect','link');

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installSchema('system', array('router'));
    $this->installEntitySchema('redirect');
    $this->loadFixture( __DIR__ . '/../../../../tests/fixtures/drupal6.php');

    $this->executeMigrations(['d6_path_redirect']);
  }

  /**
   * Tests the Drupal 6 path redirect to Drupal 8 migration.
   */
  public function testPathRedirect() {

    /** @var Redirect $redirect */
    $redirect = Redirect::load(5);
    $this->assertIdentical($this->getMigration('d6_path_redirect')
      ->getIdMap()
      ->lookupDestinationID(array(5)), array($redirect->id()));
    $this->assertIdentical("/test/source/url", $redirect->getSourceUrl());
    $this->assertIdentical("base:test/redirect/url", $redirect->getRedirectUrl()->toUriString());

    $redirect = Redirect::load(7);
    $this->assertIdentical("/test/source/url2", $redirect->getSourceUrl());
    $this->assertIdentical("http://test/external/redirect/url?foo=bar&biz=buz", $redirect->getRedirectUrl()->toUriString());
  }
}
