<?php

namespace Drupal\redirect\Tests;


use Drupal\Core\Language\Language;
use Drupal\redirect\Entity\Redirect;
use Drupal\simpletest\WebTestBase;

class RedirectUITest extends WebTestBase {
  private $admin_user;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('redirect', 'node', 'path', 'dblog', 'views');

  public static function getInfo() {
    return array(
      'name' => 'Redirect UI tests',
      'description' => 'Test interface functionality.',
      'group' => 'Redirect',
    );
  }

  function setUp() {
    parent::setUp();

    $content_type = $this->drupalCreateContentType();

    $this->admin_user = $this->drupalCreateUser(array('administer redirects', 'access site reports', 'access content', 'create ' . $content_type->type . ' content', 'edit any ' . $content_type->type . ' content', 'create url aliases'));
  }

  function testRedirectEntityForm() {
    $this->drupalLogin($this->admin_user);

    $source_url = 'non-existing';
    $redirect_url = 'node';
    $this->drupalPostForm('admin/config/search/redirect/add', array(
      'source[0][url]' => $source_url,
      'redirect[0][url]' => $redirect_url,
    ), t('Save'));

    $redirects = \Drupal::entityManager()
      ->getStorageController('redirect')
      ->loadByProperties(array('hash' => Redirect::generateHash($source_url, array(), Language::LANGCODE_DEFAULT)));
    $redirect = array_shift($redirects);
    $this->assertEqual($redirect->getSourceUrl(), $source_url);
  }

}
