<?php
/**
 * @file
 * Contains Drupal\redirect\RedirectChecker.
 */

namespace Drupal\redirect;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\State\StateInterface;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Redirect checker class.
 */
class RedirectChecker {

  /**
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  public function __construct(ConfigFactoryInterface $config, StateInterface $state) {
    $this->config = $config->get('redirect.settings');
    $this->state = $state;
  }

  /**
   * Determines if redirect may be performed.
   *
   * @param Request $request
   *   The current request object.
   *
   * @return bool
   *   TRUE if redirect may be performed.
   */
  public function canRedirect(Request $request) {
    $can_redirect = TRUE;

    $route = $request->attributes->get(RouteObjectInterface::ROUTE_OBJECT);
    if ($route) {
      $is_admin = (bool) $route->getOption('_admin_route');
    }

    if (strpos($request->getScriptName(), 'index.php') === FALSE) {
      // Do not redirect if the root script is not /index.php.
      $can_redirect = FALSE;
    }
    elseif (!($request->isMethod('GET') || $request->isMethod('HEAD'))) {
      // Do not redirect if this is other than GET request.
      $can_redirect = FALSE;
    }
    elseif ($this->state->get('system.maintenance_mode') || defined('MAINTENANCE_MODE')) {
      // Do not redirect in offline or maintenance mode.
      $can_redirect = FALSE;
    }
    elseif (!$this->config->get('global_admin_paths') && !empty($is_admin)) {
      // Do not redirect on admin paths.
      $can_redirect = FALSE;
    }

    return $can_redirect;
  }

}
