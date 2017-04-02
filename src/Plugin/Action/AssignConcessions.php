<?php
/**
 * @file
 * Contains \Drupal\wieting\Plugin\Action\AssignConcessions.
 *
 * Lifted from https://www.drupal.org/node/2330631
 */

namespace Drupal\wieting\Plugin\Action;

use Drupal\Core\Action\ActionBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\wieting\Plugin\Action\Common;

/**
 * Assign user as Concessions to selected performance(s)
 *
 * @Action(
 *   id = "assign_concessions_to_performance",
 *   label = @Translation("Assign the ACTIVE volunteer team as concessions for the selected performance(s)"),
 *   type = "node"
 * )
 */
class AssignConcessions extends ActionBase {

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    $uid = \Drupal\wieting\Plugin\Action\Common::getActiveUID( );
    if (\Drupal\wieting\Plugin\Action\Common::isHelpNeeded('concessions', $entity)) {
      if (\Drupal\wieting\Plugin\Action\Common::allowedPerformanceDate($uid, $entity)) {
        if (\Drupal\wieting\Plugin\Action\Common::allowedVolunteerRole($uid, 'concessions')) {
          \Drupal\wieting\Plugin\Action\Common::setPerformanceRole($uid, $entity, 'concessions');
        }
      }
    }
    return;
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
    return TRUE;
  }

}

?>
