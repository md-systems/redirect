<?php
// $Id$

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

  // Advertise this table as a possible base table
  $data['redirect']['table']['base'] = array(
    'field' => 'rid',
    'title' => t('URL redirects'),
    'help' => t('Listings of URL redirects.'),
    'weight' => 10,
  );

  // {redirect}.rid
  $data['redirect']['rid'] = array(
    'title' => t('Redirect ID'),
    'help' => t('The internal ID of the redirect.'),
    'field' => array(
      'handler' => 'views_handler_field',
      'click sortable' => TRUE,
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_numeric',
      'allow empty' => TRUE,
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
    'argument' => array(
      'handler' => 'views_handler_argument_numeric',
    ),
  );

  // Node type
  $data['redirect']['type'] = array(
    'title' => t('Type'), // The item it appears as on the UI,
    'help' => t('The type of a node (for example, "blog entry", "forum post", "story", etc).'), // The help that appears on the UI,
    'field' => array(
      'handler' => 'views_handler_field',
      'click sortable' => TRUE,
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
    'filter' => array(
      'handler' => 'redirect_handler_filter_redirect_type',
    ),
    'argument' => array(
      'handler' => 'views_handler_argument_string',
    ),
  );

  // {redirect}.uid
  $data['users']['table']['join']['redirect'] = array(
    'left_field' => 'uid',
    'field' => 'uid',
  );
  $data['redirect']['uid'] = array(
    'title' => t('User ID'),
    'help' => t('ID of user who created the URL redirect.'),
    'field' => array(
      'handler' => 'views_handler_field_user',
      'click sortable' => TRUE,
    ),
    'argument' => array(
      'handler' => 'views_handler_argument_user_uid',
      'name field' => 'name', // display this field in the summary
    ),
    'filter' => array(
      'title' => t('Name'),
      'handler' => 'views_handler_filter_user_name',
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
    'relationship' => array(
      'handler' => 'views_handler_relationship',
      'base' => 'users',
      'base field' => 'uid',
      'label' => t('user'),
    ),
  );

  // uid
  $data['redirect']['uid_current'] = array(
    'real field' => 'uid',
    'title' => t('Current user'),
    'help' => t('Filter the view to the currently logged in user.'),
    'filter' => array(
      'handler' => 'views_handler_filter_user_current',
      'type' => 'yes-no',
    ),
  );

  // {redirect}.source
  $data['redirect']['source'] = array(
    'title' => t('Source URL'),
    'help' => t('The source URL which generates a redirect'),
    'field' => array(
      'handler' => 'redirect_handler_field_redirect_source',
      'click sortable' => TRUE,
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_string',
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
    'argument' => array(
      'handler' => 'views_handler_argument_string',
    ),
  );

  // {redirect}.redirect
  $data['redirect']['redirect'] = array(
    'title' => t('Redirect URL'),
    'help' => t('The destination URL'),
    'field' => array(
      'handler' => 'redirect_handler_field_redirect_redirect',
      'click sortable' => TRUE,
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_string',
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
    'argument' => array(
      'handler' => 'views_handler_argument_string',
    ),
  );

  // {redirect}.count
  $data['redirect']['count'] = array(
    'title' => t('Clicks'),
    'help' => t('Number of times this URL redirect has been followed.'),
    'field' => array(
      'handler' => 'views_handler_field',
      'click sortable' => TRUE,
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_numeric',
      'allow empty' => TRUE,
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
    'argument' => array(
      'handler' => 'views_handler_argument_numeric',
    ),
  );

  // {redirect}.last_used
  $data['redirect']['last_used'] = array(
    'title' => t('Last used date'),
    'help' => t('The last date/time the URL redirect was used.'),
    'field' => array(
      'handler' => 'views_handler_field_date',
      'click sortable' => TRUE,
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_date',
    ),
    'argument' => array(
      'handler' => 'views_handler_argument_date',
    ),
  );

  $data['redirect']['edit_redirect'] = array(
    'field' => array(
      'title' => t('Edit link'),
      'help' => t('Provide a simple link to edit the URL redirect.'),
      'handler' => 'redirect_handler_field_redirect_link_edit',
    ),
  );

  $data['redirect']['delete_redirect'] = array(
    'field' => array(
      'title' => t('Delete link'),
      'help' => t('Provide a simple link to delete the URL redirect.'),
      'handler' => 'redirect_handler_field_redirect_link_delete',
    ),
  );

  return $data;
}
