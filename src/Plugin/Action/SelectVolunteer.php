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
 * Selects the ACTIVE volunteer.
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
    $redirect = TRUE;
    $username = $entity->getUsername( );
    $uid = $entity->id( );
    $tempstore = \Drupal::service('user.private_tempstore')->get('wieting');
    $uid_list = $tempstore->get('wieting_selected_volunteers');

    // If "HELP NEEDED" was selected, clear the list and re-populate it with 100 instances of "HELP NEEDED".
    if ($username === "HELP NEEDED") {
      $uid_list = array();
      for ($i=0; $i<100; $i++) { $uid_list[ ] = $uid; }
      $msg = "The ACTIVE volunteer list now contains 100 instances of 'HELP NEEDED'.";

    // If CLEAR ACTIVE LIST was selected just clear the list.
    } else if ($uid === Common::CLEAR) {
      $uid_list = array();
      $msg = "The ACTIVE volunteer list has been cleared.";
      $redirect = FALSE;

    // If REPEAT is at the top of the list, clear it and add this user 100 times.
    } else if ($uid_list[0] === Common::REPEAT) {
        $uid_list = array();
        for ($i=0; $i<100; $i++) { $uid_list[ ] = $uid; }
        $msg = "The ACTIVE volunteer list now contains 100 instances of '$username'.";

    // Otherwise, add the selected user to the list.
    } else {
      $uid_list[ ] = $uid;
      $n = count($uid_list);
      $msg = "$username ($uid) is now ACTIVE volunteer number $n.";
    }

    $tempstore->set('wieting_selected_volunteers', $uid_list);
    drupal_set_message($msg);

    // @TODO...The following is for testing ONLY.
    // Common::dispatchVolunteerReminders();   // Just testing here.
  
    // Clear the message buffer
    \Drupal\wieting\Plugin\Action\Common::setBufferedText("");

    if ($redirect) {
      $response = new RedirectResponse("/manage-performances");
      $response->send();
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
