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
    if (!\Drupal::config('redirect.settings')->get('error_log')) {
      return;
    }
    // Log 404 exceptions.
    if ($event->getException() instanceof NotFoundHttpException) {
      $user = \Drupal::currentUser();
      $langcode = \Drupal::languageManager()->getCurrentLanguage()->getId();
      $now = new \DateTime();

      // Write record.
      $record = array(
        'source' => ltrim(\Drupal::service('path.current')->getPath(), '/'),
        'uid' => $user->id(),
        'language' => $langcode,
        'timestamp' => $now->getTimestamp(),
      );
      Database::getConnection()->insert('redirect_error')->fields($record)->execute();
    }
  }

}
