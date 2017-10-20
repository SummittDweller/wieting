<?php
/**
 * @file
 * Contains \Drupal\wieting\Plugin\Action\BlockVolunteer.
 *
 * Lifted from https://www.drupal.org/node/2330631
 */

namespace Drupal\wieting\Plugin\Action;

use Drupal\Core\Action\ActionBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\wieting\Plugin\Action\Common;

/**
 * Block Volunteer from assignment to the selected Performance.
 *
 * @Action(
 *   id = "block_volunteer_from_performance",
 *   label = @Translation("Block the ACTIVE volunteer from working the selected performance(s)"),
 *   type = "node"
 * )
 */
class BlockVolunteer extends ActionBase {

  /**
   * {@inheritdoc}
   *
   * Note that this function will attempt to modify the $entity (performance) so that
   * $entity has to be loaded here so that it can be altered within!
   *
   */
  public function execute($entity = NULL) {
    // ksm("BlockVolunteer execute entity...", $entity);
    $uid = \Drupal\wieting\Plugin\Action\Common::getActiveUID( );
    // ksm("BlockVolunteer execute uid...", $uid);
    $node = \Drupal\node\Entity\Node::load($entity->id( ));         // ...performance exists, load it for update.
    \Drupal\wieting\Plugin\Action\Common::setPerformanceRole($uid, $node, 'blocked');
    // \Drupal\wieting\Plugin\Action\Common::setPerformanceRole($uid, $node, 'unblock');
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
