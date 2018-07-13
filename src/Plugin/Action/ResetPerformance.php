<?php
/**
 * @file
 * Contains \Drupal\wieting\Plugin\Action\ResetPerformance.
 *
 * Lifted from https://www.drupal.org/node/2330631
 */

namespace Drupal\wieting\Plugin\Action;

use Drupal\Core\Action\ActionBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\wieting\Plugin\Action\Common;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Assign HELP NEEDED in every role for selected performance(s)
 *
 * @Action(
 *   id = "reset_performance_to_HELP_NEEDED",
 *   label = @Translation("Reset all roles to HELP NEEDED for the selected performance(s)"),
 *   type = "node"
 * )
 */
class ResetPerformance extends ActionBase {

  /**
   * {@inheritdoc}
   */
  public function execute(&$entity = NULL) {
    $uid = \Drupal\wieting\Plugin\Action\Common::HELP_NEEDED;
    # manager
    \Drupal\wieting\Plugin\Action\Common::setPerformanceRole($uid, $entity, 'manager');
    # ticker seller
    \Drupal\wieting\Plugin\Action\Common::setPerformanceRole($uid, $entity, 'ticket_seller');
    # monitors
    \Drupal\wieting\Plugin\Action\Common::setPerformanceRole($uid, $entity, 'monitor');
    # concessions
    \Drupal\wieting\Plugin\Action\Common::setPerformanceRole($uid, $entity, 'concessions');
    drupal_flush_all_caches();
    return;
  }

  /**
   * Checks object access.
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    $status = \Drupal\wieting\Plugin\Action\Common::hasManageAccess($object);
    return $status;
  }

}

?>
