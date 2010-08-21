<?php
// $Id$

/**
 * @file
 * Documentation for the redirect module API.
 */

/**
 * @addtogroup hooks
 * @{
 */

function hook_redirect_load(array &$redirects) {

}

/**
 * Control access to a redirect.
 *
 * Modules may implement this hook if they want to have a say in whether or not
 * a given user has access to perform a given operation on a redirect.
 *
 * The administrative account (user ID #1) always passes any access check,
 * so this hook is not called in that case. Users with the "administer redirects"
 * permission may always update and delete redirects through the administrative
 * interface.
 *
 * Note that not all modules will want to influence access on all
 * redirect types. If your module does not want to actively grant or
 * block access, return REDIRECT_ACCESS_IGNORE or simply return nothing.
 * Blindly returning FALSE will break other redirect access modules.
 *
 * @ingroup redirect_access
 * @param $redirect
 *   The redirect on which the operation is to be performed, or, if it does
 *   not yet exist, the type of redirect to be created.
 * @param $op
 *   The operation to be performed. Possible values:
 *   - "create"
 *   - "delete"
 *   - "update"
 * @param $account
 *   A user object representing the user for whom the operation is to be
 *   performed.
 *
 * @return
 *   REDIRECT_ACCESS_ALLOW if the operation is to be allowed;
 *   REDIRECT_ACCESS_DENY if the operation is to be denied;
 *   REDIRECT_ACCESSS_IGNORE to not affect this operation at all.
 */
function hook_redirect_access($op, $redirect, $account) {
  $type = is_string($redirect) ? $redirect : $redirect['type'];

  if (in_array($type, array('normal', 'special'))) {
    if ($op == 'create' && user_access('create ' . $type . ' redirects', $account)) {
      return REDIRECT_ACCESS_ALLOW;
    }

    if ($op == 'update') {
      if (user_access('edit any ' . $type . ' content', $account) || (user_access('edit own ' . $type . ' content', $account) && ($account->uid == $redirect['uid']))) {
        return REDIRECT_ACCESS_ALLOW;
      }
    }

    if ($op == 'delete') {
      if (user_access('delete any ' . $type . ' content', $account) || (user_access('delete own ' . $type . ' content', $account) && ($account->uid == $redirect['uid']))) {
        return REDIRECT_ACCESS_ALLOW;
      }
    }
  }

  // Returning nothing from this function would have the same effect.
  return REDIRECT_ACCESS_IGNORE;
}

function hook_redirect_presave(array &$redirect) {

}

function hook_redirect_insert(array $redirect) {

}

function hook_redirect_update(array $redirect) {

}

function hook_redirect_delete(array $redirect) {

}

function hook_redirect_alter(array &$redirect) {
  // @see drupal_page_is_cacheable()
}

function hook_redirect_cache_clear(array $redirect) {

}

/**
 * @} End of "addtogroup hooks".
 */
