<?php
/**
 * @file
 * Contains \Drupal\redirect\EventSubscriber\Redirect404Subscriber.
 */

namespace Drupal\redirect\EventSubscriber;

use Drupal\Core\Database\Database;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * An EventSubscriber that listens to redirect 404 errors.
 */
class Redirect404Subscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::EXCEPTION][] = 'onKernelException';
    return $events;
  }

  /**
   * Logs an exception of 404 Redirect errors.
   *
   * @param GetResponseForExceptionEvent $event
   *   Is given by the event dispatcher.
   */
  public function onKernelException(GetResponseForExceptionEvent $event) {
    // Log 404 only exceptions.
    if ($event->getException() instanceof NotFoundHttpException) {
      if (!\Drupal::config('redirect.settings')->get('log_404')) {
        return;
      }
      $langcode = \Drupal::languageManager()->getCurrentLanguage()->getId();

      // Write record.
      Database::getConnection()->merge('redirect_404')
        ->key('path', \Drupal::service('path.current')->getPath())
        ->key('langcode', $langcode)
        ->expression('count', 'count + 1')
        ->fields([
          'timestamp' => REQUEST_TIME,
          'count' => 1,
        ])
        ->execute();

    }
  }

}
