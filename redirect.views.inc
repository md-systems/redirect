<?php

/**
 * @file
 * Views integration and data for the redirect module.
 */

/**
 * Implements hook_views_data().
 */
function redirect_views_data() {
  // Basic table information.
  $data['redirect']['table']['group'] = t('Redirect');

  // Advertise this table as a possible base table.
  $data['redirect']['table']['base'] = array(
    'field' => 'rid',
    'title' => t('URL redirects'),
    'help' => t('Listings of URL redirects.'),
    'weight' => 10,
  );

  $data['redirect']['table']['entity type'] = 'redirect';

  $data['redirect']['rid'] = array(
    'title' => t('Redirect ID'),
    'help' => t('The internal ID of the redirect.'),
    'field' => array(
      'id' => 'numeric',
    ),
    'filter' => array(
      'id' => 'numeric',
    ),
    'sort' => array(
      'id' => 'standard',
    ),
  );

  $data['redirect']['type'] = array(
    'title' => t('Type'),
    'help' => t('The redirect type.'),
    'field' => array(
      'id' => 'redirect_type',
    ),
    'sort' => array(
      'id' => 'standard',
    ),
    'filter' => array(
      'id' => 'bundle',
    ),
    'argument' => array(
      'id' => 'standard',
    ),
  );

  $data['redirect']['uid'] = array(
    'title' => t('User id'),
    'help' => t('The user that created the redirect.'),
    'relationship' => array(
      'title' => t('Redirect creator'),
      'help' => t('Relate redirect to the user who created it.'),
      'id' => 'standard',
      'base' => 'users',
      'field' => 'uid',
      'label' => t('user'),
    ),
    'filter' => array(
      'id' => 'user_name',
    ),
    'argument' => array(
      'id' => 'numeric',
    ),
    'field' => array(
      'id' => 'user',
    ),
  );

//  // {redirect}.source
//  $data['redirect']['source'] = array(
//    'title' => t('Source URL'),
//    'help' => t('The source URL which generates a redirect'),
//    'field' => array(
//      'handler' => 'redirect_handler_field_redirect_source',
//      'click sortable' => TRUE,
//    ),
//    'filter' => array(
//      'handler' => 'views_handler_filter_string',
//    ),
//    'sort' => array(
//      'handler' => 'views_handler_sort',
//    ),
//    'argument' => array(
//      'handler' => 'views_handler_argument_string',
//    ),
//  );
//
//  // {redirect}.redirect
//  $data['redirect']['redirect'] = array(
//    'title' => t('Redirect URL'),
//    'help' => t('The destination URL'),
//    'field' => array(
//      'handler' => 'redirect_handler_field_redirect_redirect',
//      'click sortable' => TRUE,
//    ),
//    'filter' => array(
//      'handler' => 'views_handler_filter_string',
//    ),
//    'sort' => array(
//      'handler' => 'views_handler_sort',
//    ),
//    'argument' => array(
//      'handler' => 'views_handler_argument_string',
//    ),
//  );

  if (\Drupal::moduleHandler()->moduleExists('language')) {
    $data['node_revision']['language'] = array(
      'title' => t('Language'),
      'help' => t('The language the redirect is for.'),
      'field' => array(
        'id' => 'language',
      ),
      'filter' => array(
        'id' => 'language',
      ),
      'argument' => array(
        'id' => 'language',
      ),
      'sort' => array(
        'id' => 'standard',
      ),
    );
  }

  $data['redirect']['count'] = array(
    'title' => t('Clicks'),
    'help' => t('Number of times this URL redirect has been followed.'),
    'field' => array(
      'id' => 'numeric',
    ),
    'filter' => array(
      'id' => 'numeric',
    ),
    'sort' => array(
      'id' => 'standard',
    ),
  );

  $data['redirect']['access'] = array(
    'title' => t('Last accessed date'),
    'help' => t('The date/time the URL redirect was last accessed.'),
    'field' => array(
      'id' => 'date',
    ),
    'sort' => array(
      'id' => 'date',
    ),
    'filter' => array(
      'id' => 'date',
    ),
  );

//  $data['redirect']['operations'] = array(
//    'field' => array(
//      'title' => t('Operations'),
//      'help' => t('Provide links to operations the user can perform for the URL redirect.'),
//      'handler' => 'redirect_handler_field_redirect_operations',
//    ),
//  );

  return $data;
}
