<?php
/**
 * @file
 * Contains \Drupal\wieting\Plugin\Action\SelectVolunteer.
 *
 * Lifted from https://www.drupal.org/node/2330631 and see https://docs.acquia
 * .com/article/lesson-71-loading-and-editing-fields for guidance.
 */

namespace Drupal\wieting\Plugin\Action;

use Drupal\Core\Action\ActionBase;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Updates volunteer information including "Team Name" and "Available", and generates corresponding "Assignment" nodes.
 *
 * @Action(
 *   id = "select_volunteer",
 *   label = @Translation("Select a volunteer"),
 *   type = "user"
 * )
 */
class SelectVolunteer extends ActionBase {

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    $username = $entity->getUsername( );
    $uid = $entity->id( );
    $team = $entity->get('field_team_name')->getString( );
    \Drupal::state()->set('wieting_selected_volunteer_id', $uid);
    \Drupal::state()->set('wieting_selected_volunteer_name', $username);
    \Drupal::state()->set('wieting_selected_volunteer_team', $team);
   if (strlen($team) < 1) { $team = '<blank>'; }
    drupal_set_message("$username ($uid) is now the ACTIVE volunteer. This volunteers team status/name is '$team'.");
    $response = new RedirectResponse("/manage-performances");
    $response->send();
    // $data = \Drupal::state()->get('wieting_selected_volunteer_id') ?: false;
    // \Drupal::state()->delete('wieting_selected_volunteer_id');
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
