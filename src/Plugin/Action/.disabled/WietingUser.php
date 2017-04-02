<?php
/**
 * @file
 * Contains \Drupal\wieting\Plugin\Action\WietingUser.
 *
 * Lifted from https://www.drupal.org/node/2330631
 */

namespace Drupal\wieting\Plugin\Action;

use Drupal\Core\Action\ActionBase;
use Drupal\Core\Session\AccountInterface;

/**
 * @TODO change this: Makes a node example.
 *
 * @Action(
 *   id = "wieting_user",
 *   label = @Translation("Perform some action regarding a Wieting user/account."),
 *   type = "user"
 * )
 */
class WietingUser extends ActionBase {

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    drupal_set_message('This is \Drupal\wieting\Plugin\Action\WietingUser\execute.');
    ksm($entity);  /* or use kint($entity) but it disappears when the page redirects! */
  }

  /**
   * Checks object access.
   *
   * @param mixed $object
   *   The object to execute the action on.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   (optional) The user for which to check access, or NULL to check access
   *   for the current user. Defaults to NULL.
   * @param bool $return_as_object
   *   (optional) Defaults to FALSE.
   *
   * @return bool|\Drupal\Core\Access\AccessResultInterface
   *   The access result. Returns a boolean if $return_as_object is FALSE (this
   *   is the default) and otherwise an AccessResultInterface object.
   *   When a boolean is returned, the result of AccessInterface::isAllowed() is
   *   returned, i.e. TRUE means access is explicitly allowed, FALSE means
   *   access is either explicitly forbidden or "no opinion".
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    drupal_set_message('This is \Drupal\wieting\Plugin\Action\WietingUser\access.');
    return TRUE;
  }

}

?>
