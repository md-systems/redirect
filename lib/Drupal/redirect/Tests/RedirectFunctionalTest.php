<?php

namespace Drupal\redirect\Tests;


class RedirectFunctionalTest extends RedirectTestBase {
  private $admin_user;

  public static function getInfo() {
    return array(
      'name' => 'Redirect functional tests',
      'description' => 'Test interface functionality.',
      'group' => 'Redirect',
    );
  }

  function setUp(array $modules = array()) {
    parent::setUp($modules);

    $this->admin_user = $this->drupalCreateUser(array('administer redirects', 'access site reports', 'access content', 'create article content', 'edit any article content', 'create url aliases'));
    $this->drupalLogin($this->admin_user);
  }

  function test404Interface() {
    // Check that 404 pages do get add redirect links for admin users.
    $this->drupalGet('invalid-path1');
    $this->drupalGet('invalid-path2');
    $this->assertLink('Add URL redirect from this page to another location');

    // Check that 403 pages do not get the add redirect link at all.
    $this->drupalGet('admin/config/system/actions');
    $this->assertNoLink('Add URL redirect from this page to another location');

    $this->drupalGet('admin/reports/page-not-found');
    $this->clickLink('Fix 404 pages with URL redirects');

    // Check that normal users do not see the add redirect link on 404 pages.
    $this->drupalLogout();
    $this->drupalGet('invalid-path3');
    $this->assertNoLink('Add an URL redirect from this page to another location');
  }

  function testPageCache() {
    // Set up cache variables.
    variable_set('cache', 1);
    $edit = array(
      'redirect_page_cache' => TRUE,
      'redirect_purge_inactive' => 604800,
    );
    $this->drupalPost('admin/config/search/redirect/settings', $edit, 'Save configuration');
    $this->assertText('The configuration options have been saved.');
    $this->drupalLogout();

    // Add a new redirect.
    $redirect = $this->addRedirect('redirect', 'node');
    $this->assertEqual($redirect->access, 0);
    $this->assertEqual($redirect->count, 0);
    $this->assertPageNotCached('redirect');

    // Perform the redirect and check that last_used
    $redirect = $this->assertRedirect($redirect);
    $this->assertEqual($redirect->count, 1);
    $this->assertTrue($redirect->access > 0);
    $cache = $this->assertPageCached('redirect');
    $this->assertHeader('Location', url('node', array('absolute' => TRUE)), $cache->data['headers']);
    $this->assertHeader('X-Redirect-ID', $redirect->rid, $cache->data['headers']);

    // Set a redirect to not used in a while and disable running bootstrap
    // hooks during cache page serve. Running cron to remove inactive redirects
    // should not remove since they cannot be tracked.
    $redirect->access = 1;
    redirect_save($redirect);
    variable_set('page_cache_invoke_hooks', FALSE);
    $this->cronRun();
    $this->assertRedirect($redirect);

    $redirect->access = 1;
    redirect_save($redirect);
    variable_set('page_cache_invoke_hooks', TRUE);
    $this->cronRun();
    $this->assertNoRedirect($redirect);
  }

  function testPathChangeRedirects() {
    // Create an initial article node with a path alias.
    $node = $this->drupalCreateNode(array('type' => 'article', 'path' => array('alias' => 'first-alias')));

    // Change the node's alias will create an automatic redirect from 'first-alias' to the node.
    $this->drupalPost("node/{$node->id()}/edit", array('path[alias]' => 'second-alias'), 'Save');
    //$redirect = redirect_load_by_source('first-alias');
    //$this->assertRedirect($redirect);

    $this->drupalPost("node/{$node->id()}/edit", array('path[alias]' => 'first-alias'), 'Save');
    //$redirect = redirect_load_by_source('second-alias');
    //$this->assertRedirect($redirect);

    $this->drupalPost("node/{$node->id()}/edit", array('path[alias]' => 'second-alias'), 'Save');
    //$redirect = redirect_load_by_source('first-alias');
    //$this->assertRedirect($redirect);
  }
}
