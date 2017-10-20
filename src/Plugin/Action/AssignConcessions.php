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
use Symfony\Component\HttpFoundation\RedirectResponse;

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
    if ($uid = \Drupal\wieting\Plugin\Action\Common::getActiveUID( )) {
      if (\Drupal\wieting\Plugin\Action\Common::isHelpNeeded('concessions', $entity)) {
        if (\Drupal\wieting\Plugin\Action\Common::allowedPerformanceDate($uid, $entity)) {
          if (\Drupal\wieting\Plugin\Action\Common::allowedVolunteerRole($uid, 'concessions')) {
            \Drupal\wieting\Plugin\Action\Common::setPerformanceRole($uid, $entity, 'concessions');
            // $response = new RedirectResponse("/manage-volunteers");
            // $response->send();
          }
        }
      }
    }
    \Drupal\wieting\Plugin\Action\Common::advanceActiveUID( );
    return;
  }

  /**
   * Checks object access.
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    $status = \Drupal\wieting\Plugin\Action\Common::hasAccess($object);
    return $status;
  }

}

?>
