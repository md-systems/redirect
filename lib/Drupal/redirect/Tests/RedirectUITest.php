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

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'name' => 'Redirect UI tests',
      'description' => 'Test interface functionality.',
      'group' => 'Redirect',
    );
  }

  /**
   * {@inheritdoc}
   */
  function setUp() {
    parent::setUp();

    $content_type = $this->drupalCreateContentType();

    $this->admin_user = $this->drupalCreateUser(array('administer redirects', 'access site reports', 'access content', 'create ' . $content_type->type . ' content', 'edit any ' . $content_type->type . ' content', 'create url aliases'));
  }

  /**
   * Test the redirect UI.
   */
  function testRedirectUI() {
    $this->drupalLogin($this->admin_user);

    $source_url = 'non-existing';
    $redirect_url = 'node';

    // Test populating the redirect form with predefined values.
    $this->drupalGet('admin/config/search/redirect/add', array('query' => array(
      'source' => $source_url,
      'source_options' => array('query' => array('key' => 'val', 'key1' => 'val1')),
      'redirect' => $redirect_url,
      'redirect_options' => array('query' => array('key' => 'val', 'key1' => 'val1')),
    )));
    $this->assertFieldByName('redirect_source[0][url]', $source_url . '?key=val&key1=val1');
    $this->assertFieldByName('redirect_redirect[0][url]', $redirect_url . '?key=val&key1=val1');

    // Test creating a new redirect via UI.
    $this->drupalPostForm('admin/config/search/redirect/add', array(
      'redirect_source[0][url]' => $source_url,
      'redirect_redirect[0][url]' => $redirect_url,
    ), t('Save'));

    // Try to load the new redirect by hash. That will also test if the hash
    // has been generated correctly via UI.
    $redirects = \Drupal::entityManager()
      ->getStorageController('redirect')
      ->loadByProperties(array('hash' => Redirect::generateHash($source_url, array(), Language::LANGCODE_NOT_SPECIFIED)));
    $redirect = array_shift($redirects);
    $this->assertEqual($redirect->getSourceUrl(), $source_url);
    $this->assertEqual($redirect->getRedirectUrl(), $redirect_url);

    // After adding the redirect we should end up in the list. Check if the
    // redirect is listed.
    $this->assertUrl('admin/config/search/redirect');
    $this->assertText($source_url);
    $this->assertLink($redirect_url);
    $this->assertText(Language::LANGCODE_NOT_SPECIFIED);

    // Test the edit form and update action.
    $this->clickLink(t('Edit'));
    $this->assertFieldByName('redirect_source[0][url]', $source_url);
    $this->assertFieldByName('redirect_redirect[0][url]', $redirect_url);
    $this->assertFieldByName('status_code', $redirect->getStatusCode());

    // Append a query string to see if we handle query data properly.
    $source_query = '?key=value';
    $this->drupalPostForm(NULL, array(
      'redirect_source[0][url]' => $source_url . $source_query,
    ), t('Save'));

    // Check the location after update and check if the value has been updated
    // in the list.
    $this->assertUrl('admin/config/search/redirect');
    $this->assertText($source_url . $source_query);

    // The url field should not contain the query string and therefore we
    // should be able to load the redirect using only the url part without
    // query.
    \Drupal::entityManager()->getStorageController('redirect')->resetCache();
    $redirects = \Drupal::entityManager()
      ->getStorageController('redirect')
      ->loadByProperties(array('redirect_source__url' => $source_url));
    $redirect = array_shift($redirects);
    $this->assertEqual($redirect->getSourceUrl(), $source_url . $source_query);
    $this->assertEqual($redirect->getSourceOption('query'), array('key' => 'value'));

    // Test the source url hints.
    // The hint about an existing base path.
    $this->drupalPostAjaxForm('admin/config/search/redirect/add', array(
      'redirect_source[0][url]' => $source_url . $source_query,
    ), 'redirect_source[0][url]');
    $this->assertRaw(t('The base source path %source is already being redirected. Do you want to <a href="@edit-page">edit the existing redirect</a>?',
      array('%source' => $source_url . $source_query, '@edit-page' => url('admin/config/search/redirect/edit/'. $redirect->id()))));

    // The hint about a valid path.
    $this->drupalPostAjaxForm('admin/config/search/redirect/add', array(
      'redirect_source[0][url]' => 'node',
    ), 'redirect_source[0][url]');
    $this->assertRaw(t('The source path %path is likely a valid path. It is preferred to <a href="@url-alias">create URL aliases</a> for existing paths rather than redirects.',
      array('%path' => 'node', '@url-alias' => url('admin/config/search/path/add'))));

    // Test validation.
    // Duplicate redirect.
    $source_url = 'non-existing';
    $redirect_url = 'node';
    $this->drupalPostForm('admin/config/search/redirect/add', array(
      'redirect_source[0][url]' => $source_url . $source_query,
      'redirect_redirect[0][url]' => $redirect_url,
    ), t('Save'));
    $this->assertRaw(t('The source path %source is already being redirected. Do you want to <a href="@edit-page">edit the existing redirect</a>?',
      array('%source' => $source_url . $source_query, '@edit-page' => url('admin/config/search/redirect/edit/'. $redirect->id()))));

    // Redirecting to itself.
    $source_url = 'node';
    $redirect_url = 'node';
    $this->drupalPostForm('admin/config/search/redirect/add', array(
      'redirect_source[0][url]' => $source_url,
      'redirect_redirect[0][url]' => $redirect_url,
    ), t('Save'));
    $this->assertRaw(t('You are attempting to redirect the page to itself. This will result in an infinite loop.'));

    // Redirecting the front page.
    $source_url = '<front>';
    $redirect_url = 'node';
    $this->drupalPostForm('admin/config/search/redirect/add', array(
      'redirect_source[0][url]' => $source_url,
      'redirect_redirect[0][url]' => $redirect_url,
    ), t('Save'));
    $this->assertRaw(t('It is not allowed to create a redirect from the front page.'));

    // Redirecting a url with fragment.
    $source_url = 'page-to-redirect#content';
    $redirect_url = 'node';
    $this->drupalPostForm('admin/config/search/redirect/add', array(
      'redirect_source[0][url]' => $source_url,
      'redirect_redirect[0][url]' => $redirect_url,
    ), t('Save'));
    $this->assertRaw(t('The anchor fragments are not allowed.'));
  }

  /**
   * Tests the fix 404 pages workflow.
   */
  function testFix404Pages() {
    $this->drupalLogin($this->admin_user);

    // Visit a non existing page to have the 404 watchdog entry.
    $this->drupalGet('non-existing');

    // Go to the "fix 404" page and check the listing.
    $this->drupalGet('admin/config/search/redirect/404');
    $this->assertText('non-existing');
    $this->clickLink(t('Add redirect'));

    // Check if we generate correct Add redirect url and if the form is
    // pre-filled.
    $this->assertUrl('admin/config/search/redirect/add?source=non-existing&destination=admin/config/search/redirect/404');
    $this->assertFieldByName('redirect_source[0][url]', 'non-existing');

    // Save the redirect.
    $this->drupalPostForm(NULL, array('redirect_redirect[0][url]' => 'node'), t('Save'));
    $this->assertUrl('admin/config/search/redirect/404');

    // Check if the redirect works as expected.
    $this->drupalGet('non-existing');
    $this->assertUrl('node');
  }

}
