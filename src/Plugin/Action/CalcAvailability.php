<?php
/**
 * @file
 * Contains \Drupal\wieting\Plugin\Action\CalcAvailability.
 *
 * Lifted from https://www.drupal.org/node/2330631
 */

namespace Drupal\wieting\Plugin\Action;

use \Drupal\Core\Action\ActionBase;
use \Drupal\Core\Session\AccountInterface;
use \Drupal\wieting\Plugin\Action\Common;
use \Drupal\user\Entity\User;


/**
 *
 * Action to calculate all volunteer availability scores for selected Performance(s).
 *
 * @Action(
 *   id = "calc_availability",
 *   label = @Translation("Calculate volunteer availability scores for selected performance(s)"),
 *   type = "node"
 * )
 */
class CalcAvailability extends ActionBase {
  
  const WRONG_DOW = 50;       // if volunteer does not normally work this DOW, add this value to their score.
  const PARTNER_ADJ = 75;     // if volunteer is a partner, add this value to their score.
  const MANAGER_ADJ = 25;     // if volunteer is a managher, add this value to their score.

  const ONE_DAY = 24. * 60. * 60;                                            // number of seconds in one day
  const WINDOW_DAYS = 30;                                                    // windows is +/- 30 days
  const WINDOW = CalcAvailability::WINDOW_DAYS * CalcAvailability::ONE_DAY;  // number of seconds in the window

  /**
   * {@inheritdoc}
   *
   * Note that this function will attempt to modify the $entity (performance) so that
   * $entity has to be loaded here so that it can be altered within!
   *
   */
  public function execute($entity = NULL) {
    // drupal_set_message('This is \Drupal\wieting\Plugin\Action\CalcAvailability\execute.');
    // ksm($entity);
    $node = \Drupal\node\Entity\Node::load($entity->id( ));         // ...performance exists, load it for update.
    \Drupal\wieting\Plugin\Action\Common::updateAvailability($node);

    // @TODO...The following is for testing ONLY.
    // Common::remindAssignedVolunteers($entity, TRUE);   // Just testing here.

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
