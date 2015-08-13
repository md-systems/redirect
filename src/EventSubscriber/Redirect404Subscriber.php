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
    $events[KernelEvents::EXCEPTION][] = array('onKernelException');
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
      $language = \Drupal::languageManager()->getCurrentLanguage();
      $langcode = $language->getId();
      $request = $event->getRequest();
      $now = new \DateTime();

      // Write record.
      $record = array(
        'source' => ltrim($request->getPathInfo(), '/'),
        'uid' => $user->id(),
        'language' => $langcode,
        'timestamp' => $now->getTimestamp(),
      );
      Database::getConnection()->insert('redirect_error')->fields($record)->execute();
    }
  }

}
