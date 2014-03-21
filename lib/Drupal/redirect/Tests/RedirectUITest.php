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
      'redirect_source[0][url]' => $source_url,
      'redirect_redirect[0][url]' => $redirect_url,
    ), t('Save'));

    $redirects = \Drupal::entityManager()
      ->getStorageController('redirect')
      ->loadByProperties(array('hash' => Redirect::generateHash($source_url, array(), Language::LANGCODE_NOT_SPECIFIED)));
    $redirect = array_shift($redirects);
    $this->assertEqual($redirect->getSourceUrl(), $source_url);
    $this->assertEqual($redirect->getRedirectUrl(), $redirect_url);

    $this->assertUrl('admin/config/search/redirect');
    $this->assertText($source_url);
    $this->assertLink($redirect_url);
    $this->assertText(Language::LANGCODE_NOT_SPECIFIED);

    $this->clickLink(t('Edit'));
    $this->assertFieldByName('redirect_source[0][url]', $source_url);
    $this->assertFieldByName('redirect_redirect[0][url]', $redirect_url);
    $this->assertFieldByName('status_code', $redirect->getStatusCode());

    $source_query = '?key=value';
    $this->drupalPostForm(NULL, array(
      'redirect_source[0][url]' => $source_url . $source_query,
    ), t('Save'));

    $this->assertUrl('admin/config/search/redirect');
    $this->assertText($source_url . $source_query);

    // The url field should not contain the query string.
    \Drupal::entityManager()->getStorageController('redirect')->resetCache();
    $redirects = \Drupal::entityManager()
      ->getStorageController('redirect')
      ->loadByProperties(array('redirect_source__url' => $source_url));
    $redirect = array_shift($redirects);
    $this->assertEqual($redirect->getSourceUrl(), $source_url . $source_query);
    $this->assertEqual($redirect->getSourceOption('query'), array('key' => 'value'));

    $this->clickLink(t('Edit'));
    $this->drupalPostAjaxForm('admin/config/search/redirect/edit/' . $redirect->id(), array(
      'redirect_source[0][url]' => $source_url . $source_query,
    ), 'redirect_source[0][url]');
  }

}
