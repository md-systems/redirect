<?php

namespace Drupal\redirect\Tests;


class RedirectFunctionalTest extends RedirectTestBase {
  private $admin_user;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('redirect', 'node', 'path', 'dblog');

  public static function getInfo() {
    return array(
      'name' => 'Redirect functional tests',
      'description' => 'Test interface functionality.',
      'group' => 'Redirect',
    );
  }

  function setUp() {
    parent::setUp();

    $content_type = $this->drupalCreateContentType();

    $this->admin_user = $this->drupalCreateUser(array('administer redirects', 'access site reports', 'access content', 'create ' . $content_type->type . ' content', 'edit any ' . $content_type->type . ' content', 'create url aliases'));
  }

  function test404Interface() {
    $this->drupalLogin($this->admin_user);
    // Check that 404 pages do get add redirect links for admin users.
    $this->drupalGet('invalid-path1');
    $this->drupalGet('invalid-path2');
    $this->assertLink('Add URL redirect from this page to another location');

    // Check that 403 pages do not get the add redirect link at all.
    $this->drupalGet('admin/config/system/site-information');
    $this->assertNoLink('Add URL redirect from this page to another location');

    $this->drupalGet('admin/reports/page-not-found');
    $this->clickLink('Fix 404 pages with URL redirects');

    // Check that normal users do not see the add redirect link on 404 pages.
    $this->drupalLogout();
    $this->drupalGet('invalid-path3');
    $this->assertNoLink('Add an URL redirect from this page to another location');
  }

  function testPageCache() {
    $this->drupalLogin($this->admin_user);
    // Set up cache variables.
    \Drupal::config('system.performance')->set('cache.page.use_internal', TRUE)->save();
    \Drupal::config('system.performance')->set('cache.page.invoke_hooks', TRUE)->save();
    $edit = array(
      'page_cache' => TRUE,
      'purge_inactive' => 604800,
    );
    $this->drupalPostForm('admin/config/search/redirect/settings', $edit, 'Save configuration');
    $this->assertText('The configuration options have been saved.');
    $this->drupalLogout();

    // Add a new redirect.
    $redirect = $this->addRedirect('redirect', 'node');
    $this->assertEqual($redirect->getLastAccessed(), 0);
    $this->assertEqual($redirect->getCount(), 0);
    $this->assertPageNotCached('redirect');

    // Perform the redirect and check that last_used
    $this->assertRedirect($redirect);

    // Reload the redirect.
    \Drupal::entityManager()->getStorageController('redirect')->resetCache();
    $redirect = redirect_load($redirect->id());

    $this->assertEqual($redirect->getCount(), 1);
    $this->assertTrue($redirect->getLastAccessed() > 0);

    $cache = $this->assertPageCached('redirect');
    $this->assertHeader('Location', url('node', array('absolute' => TRUE)), $cache->data['headers']);
    $this->assertHeader('X-Redirect-ID', $redirect->id(), $cache->data['headers']);

    // Set a redirect to not used in a while and disable running bootstrap
    // hooks during cache page serve. Running cron to remove inactive redirects
    // should not remove since they cannot be tracked.
    $redirect->setAccess(1);
    $redirect->save();
    \Drupal::config('system.performance')->set('cache.page.invoke_hooks', FALSE)->save();
    $this->cronRun();
    $this->assertRedirect($redirect);

    // Reload the redirect.
    \Drupal::entityManager()->getStorageController('redirect')->resetCache();
    $redirect = redirect_load($redirect->id());

    $redirect->setAccess(1);
    $redirect->save();
    \Drupal::config('system.performance')->set('cache.page.invoke_hooks', TRUE)->save();
    $this->cronRun();
    $this->assertNoRedirect($redirect);
  }

  function testPathChangeRedirects() {
    $this->drupalLogin($this->admin_user);
    // Create an initial article node with a path alias.
    $node = $this->drupalCreateNode(array('type' => 'article', 'path' => array('alias' => 'first-alias')));

    // Change the node's alias will create an automatic redirect from 'first-alias' to the node.
    $this->drupalPostForm("node/{$node->id()}/edit", array('path[alias]' => 'second-alias'), 'Save');
    //$redirect = redirect_load_by_source('first-alias');
    //$this->assertRedirect($redirect);

    $this->drupalPostForm("node/{$node->id()}/edit", array('path[alias]' => 'first-alias'), 'Save');
    //$redirect = redirect_load_by_source('second-alias');
    //$this->assertRedirect($redirect);

    $this->drupalPostForm("node/{$node->id()}/edit", array('path[alias]' => 'second-alias'), 'Save');
    //$redirect = redirect_load_by_source('first-alias');
    //$this->assertRedirect($redirect);
  }
}
