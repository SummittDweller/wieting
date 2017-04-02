<?php
/**
 * @file
 * Contains \Drupal\wieting\Plugin\Action\TransferAvailableDates.
 *
 * Lifted from https://www.drupal.org/node/2330631 and see https://docs.acquia
 * .com/article/lesson-71-loading-and-editing-fields for guidance.
 */

namespace Drupal\wieting\Plugin\Action;

use Drupal\Core\Action\ActionBase;
use Drupal\Core\Session\AccountInterface;

/**
 * @TODO change this: Makes a node example.
 *
 * @Action(
 *   id = "wieting_transfer_available_dates",
 *   label = @Translation("Transfer available dates."),
 *   type = "node"
 * )
 */
class TransferAvailableDates extends ActionBase {

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    drupal_set_message('This is \Drupal\wieting\Plugin\Action\TransferAvailableDates\execute.');
    // ksm($entity);  /* or use kint($entity) but it disappears when the page redirects! */
    $username = $entity->title->value;
    drupal_set_message("Found NOT avaialable dates for '$username'.");

    $fields = $entity->toArray();
    // ksm($fields);
    $available = array( );

    // ksm('thursday...', $fields['field_available_no_thursdays'][0]);

    $available['thursday'] = ($fields['field_available_no_thursdays'][0]['value'] === "0");
    $available['friday'] = ($fields['field_available_no_fridays'][0]['value'] === "0");
    $available['saturday'] = ($fields['field_available_no_saturdays'][0]['value'] === "0");
    $available['sunday'] = ($fields['field_available_no_sundays'][0]['value'] === "0");
    $available['saturday-matinee'] = ($fields['field_available_no_sat_matinee'][0]['value'] === "0");
    $available['sunday-matinee'] = ($fields['field_available_no_sun_matinee'][0]['value'] === "0");

    $a = array('thursday' =>  "Thursday Night", 'friday' => "Friday Night", 'saturday' => "Saturday Night",
        'sunday' => "Sunday Night", 'saturday-matinee' => 'Saturday Matinee', 'sunday-matinee' => "Sunday Matinee");
    $userAvail = array( );

    $msg = $username . " is available :";
    foreach ($available as $key => $tf) {
      if ($tf) {
        $msg .= " $key ";
        $userAvail[] = $a[$key];
      }
    }
    drupal_set_message($msg);

    if ($user = user_load_by_name($username)) {
      // ksm("user before...", $user);
      $user->set('field_available_days_times', $userAvail);
      $user->save();
      // ksm("user after...", $user);
    } else {
      drupal_set_message("Found NO username for '$username'.");
    }
  }

    //$partner = \Drupal\user\Entity\User::load($pID);
    //$name = $partner->getUsername();
    //$entity->set('field_team_name', $team);
    //$entity->save();

/*  Default ALL accounts to "* Needs Attention!" in the Team field.
    $roles = $entity->get('field_volunteer_roles');
    $entity->set('field_team_name', '* Needs Attention!');
    $entity->save(); */

/*  This code was used on 24-Mar-2017 to sync account roles with taxonomy volunteer roles
    $roles = $entity->getRoles();
    ksm($roles);
    $vRoles = $entity->get('field_volunteer_roles')->getValue();
    ksm($vRoles);
    $translate = array( 'manager' => '64', 'monitor' => '65', 'concessions' => '66',
        'ticket_seller' => '67', 'm_partner' => '74', 'c_partner' => '74');
    $vRoles = array( );
    foreach ($roles as $role) {
      if (array_key_exists($role, $translate)) {
        $vRoles[] = $translate[$role];
      }
    }
   ksm(array_unique($vRoles));
   $entity->set('field_volunteer_roles', array_unique($vRoles));
   $entity->save();  */


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

  /*
  private function getVolunteerRoles($vol) {
    $return = array();
    $field = $vol->get('field_volunteer_roles');
    $roles = $field->getValue();
    foreach ($roles as $role) {
      $tid = (int)$role['target_id'];
      $term = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->load($tid);
      $title = $term->name->value;
      $return[] = $title;
    }
    return $return;
  }

  private function findPrimaryPartner($id, $name) {
    $all_users = \Drupal\user\Entity\User::loadMultiple();
    foreach ($all_users as $user) {
      if ($has_partner = $user->get('field_has_partner')->getValue()) {
        $pID = (int)$has_partner[0]['target_id'];
        if ($pID === $id) {
          $username = $user->name->value;
          $status = $user->status->value;
          if ($status === '1') {
            return "~ Partner for $username";
          } else {
            return "** Partner '$username' is Blocked!";
          }
        }
      }
    }
    return "** Has NO Team!";
  }
  */

}

?>
