<?php
/**
 * @file
 * Contains \Drupal\wieting\Plugin\Action\DeletePerformances.
 *
 * Lifted from https://www.drupal.org/node/2330631
 */

namespace Drupal\wieting\Plugin\Action;

use Drupal\Core\Action\ActionBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\wieting\Plugin\Action\Common;

/**
 *
 * Action to delete selected Performance(s).
 *
 * @Action(
 *   id = "delete_performances",
 *   label = @Translation("Delete a selected set of performances."),
 *   type = "node"
 * )
 */
class DeletePerformances extends ActionBase {

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    // drupal_set_message('This is \Drupal\wieting\Plugin\Action\DeletePerformances\execute.');
    // ksm($entity);
    $pub = $entity->isPublished( );
    if ($pub) {
      $showTitle = $entity->get('title')->value;
      drupal_set_message("Performance '$showTitle' is still published and cannot be deleted.", 'warning');
    } else {
      $entity->delete( );
    }
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
