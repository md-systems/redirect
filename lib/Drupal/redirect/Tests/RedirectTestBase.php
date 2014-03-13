<?php

namespace Drupal\redirect\Tests;

use Drupal\simpletest\WebTestBase;

class RedirectTestBase extends WebTestBase {
  function setUp(array $modules = array()) {
    array_unshift($modules, 'redirect');
    parent::setUp($modules);
  }

  protected function assertRedirect($redirect) {
    $source_url = url($redirect->source, array('absolute' => TRUE) + $redirect->source_options);
    $redirect_url = url($redirect->redirect, array('absolute' => TRUE) + $redirect->redirect_options);
    $this->drupalGet($source_url);
    $this->assertEqual($this->getUrl(), $redirect_url, t('Page %source was redirected to %redirect.', array('%source' => $source_url, '%redirect' => $redirect_url)));

    // Reload the redirect.
    if (!empty($redirect->rid)) {
      return redirect_load($redirect->rid);
    }
  }

  protected function assertNoRedirect($redirect) {
    $source_url = url($redirect->source, array('absolute' => TRUE) + $redirect->source_options);
    $this->drupalGet($source_url);
    $this->assertEqual($this->getUrl(), $source_url, t('Page %url was not redirected.', array('%url' => $source_url)));
  }

  /**
   * Add an URL redirection
   *
   * @param $source
   *   A source path.
   * @param $redirect
   *   A redirect path.
   */
  protected function addRedirect($source_path, $redirect_path, array $redirect = array()) {
    $source_parsed = redirect_parse_url($source_path);
    $redirect['source'] = $source_parsed['url'];
    if (isset($source_parsed['query'])) {
      $redirect['source_options']['query'] = $source_parsed['query'];
    }

    $redirect_parsed = redirect_parse_url($redirect_path);
    $redirect['redirect'] = $redirect_parsed['url'];
    if (isset($redirect_parsed['query'])) {
      $redirect['redirect_options']['query'] = $redirect_parsed['query'];
    }
    if (isset($redirect_parsed['fragment'])) {
      $redirect['redirect_options']['fragment'] = $redirect_parsed['fragment'];
    }

    $redirect_object = new stdClass();
    redirect_object_prepare($redirect_object, $redirect);
    redirect_save($redirect_object);
    return $redirect_object;
  }

  protected function assertPageCached($url, array $options = array()) {
    $options['absolute'] = TRUE;
    $url = url($url, $options);
    $cache = cache_get($url, 'cache_page');
    $this->assertTrue($cache, t('Page %url was cached.', array('%url' => $url)));
    return $cache;
  }

  protected function assertPageNotCached($url, array $options = array()) {
    $options['absolute'] = TRUE;
    $url = url($url, $options);
    $cache = cache_get($url, 'cache_page');
    $this->assertFalse($cache, t('Page %url was not cached.', array('%url' => $url)));
  }

  protected function assertHeader($name, $expected, $headers = NULL) {
    if (!isset($headers)) {
      $headers = $this->drupalGetHeaders();
      $name = strtolower($name);
    }
    return $this->assertIdentical($headers[$name], $expected);
  }
}
