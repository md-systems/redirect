<?php

namespace Drupal\redirect\Tests;

use Drupal\redirect\Entity\Redirect;
use Drupal\simpletest\WebTestBase;

class RedirectTestBase extends WebTestBase {

  protected function assertRedirect(Redirect $redirect) {
    $source_url = url($redirect->getSource(), array('absolute' => TRUE) + $redirect->getSourceOptions());
    $redirect_url = url($redirect->getRedirect(), array('absolute' => TRUE) + $redirect->getSourceOptions());
    $this->drupalGet($source_url);
    $this->assertEqual($this->getUrl(), $redirect_url, t('Page %source was redirected to %redirect.', array('%source' => $source_url, '%redirect' => $redirect_url)));
  }

  protected function assertNoRedirect($redirect) {
    $source_url = url($redirect->source, array('absolute' => TRUE) + $redirect->source_options);
    $this->drupalGet($source_url);
    $this->assertEqual($this->getUrl(), $source_url, t('Page %url was not redirected.', array('%url' => $source_url)));
  }

  /**
   * Add an URL redirection
   *
   * @param string $source_path
   *   A source path.
   * @param string $redirect_path
   *   The path to redirect to.
   * @param array $values
   *   A redirect path.
   *
   * @return \Drupal\redirect\Entity\Redirect
   *   The redirect entity.
   */
  protected function addRedirect($source_path, $redirect_path, array $values = array()) {
    $source_parsed = redirect_parse_url($source_path);
    $values['source'] = $source_parsed['url'];
    if (isset($source_parsed['query'])) {
      $values['source_options']['query'] = $source_parsed['query'];
    }

    $redirect_parsed = redirect_parse_url($redirect_path);
    $values['redirect'] = $redirect_parsed['url'];
    if (isset($redirect_parsed['query'])) {
      $values['redirect_options']['query'] = $redirect_parsed['query'];
    }
    if (isset($redirect_parsed['fragment'])) {
      $values['redirect_options']['fragment'] = $redirect_parsed['fragment'];
    }

    $redirect = redirect_create($values);
    $redirect->save();
    return $redirect;
  }

  protected function assertPageCached($url, array $options = array()) {
    $options['absolute'] = TRUE;
    $url = url($url, $options);
    $cache = \Drupal::cache('cache_page')->get($url);
    $this->assertTrue($cache, t('Page %url was cached.', array('%url' => $url)));
    return $cache;
  }

  protected function assertPageNotCached($url, array $options = array()) {
    $options['absolute'] = TRUE;
    $url = url($url, $options);
    $cache = \Drupal::cache('cache_page')->get($url);
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
