<?php
/**
 * @file
 * Contains \Drupal\wieting\Plugin\Action\UpdateTeamStatus.
 *
 * Lifted from https://www.drupal.org/node/2330631 and see https://docs.acquia
 * .com/article/lesson-71-loading-and-editing-fields for guidance.
 */

namespace Drupal\wieting\Plugin\Action;

use Drupal\Core\Action\ActionBase;
use Drupal\Core\Session\AccountInterface;

/**
 * Updates volunteer information including "Team Name" and "Available", and generates corresponding "Assignment" nodes.
 *
 * @Action(
 *   id = "update_volunteer",
 *   label = @Translation("Update volunteer information."),
 *   type = "user"
 * )
 */
class UpdateVolunteer extends ActionBase {

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    drupal_set_message('This is \Drupal\wieting\Plugin\Action\UpdateVolunteer\execute.');
    // ksm("entity...", $entity);  /* or use kint($entity) but it disappears when the page redirects! */

    //--- Update volunteer's team status ------------------------------------------------------

    $team = "* Needs Attention!";
    $username = $entity->getUsername();
    $uid = (int)$entity->uid->value;
    $roles = $this->getVolunteerRoles($entity);

    // Process roles in reverse priority order...
    // Manager and Ticket Seller, then Partner, then Monitor and Concessions last.

    // Manager and Ticket Seller...no team needed.
    if (in_array("Manager",$roles) || in_array("Ticket Seller", $roles)) {
      $team = "";
    }

    // Partner...needs a primary team member.
    if (in_array("Partner", $roles)) {
      $team = $this->findPrimaryPartner($uid, $username);
    }

    // Monitor or Concessions primary... should have a partner.
    if (in_array("Monitor",$roles) || in_array("Concessions", $roles)) {
      if (!$has_partner = $entity->get('field_has_partner')->getValue()) {     // Needs a partner!
          $team = '! Needs a Partner!';
        } else {                                                               // Has a partner...
          $pID = (int)$has_partner[0]['target_id'];
          $partner = \Drupal\user\Entity\User::load($pID);
          $name = $partner->getUsername();
          if (user_is_blocked($name)) {                                        // Partner is blocked!
            $team = "! Partner $name is Blocked!";
          } else {
            $team = $username . " and " . $name;
            $names = explode(' ', $team);
            if ($names[1] === end($names)) {
              unset($names[1]);
              $team = implode(' ', $names);
            }
          }
        }
      }

    // Managers often have a partner... so recognize this here.
    if (in_array("Manager",$roles)) {
      if ($has_partner = $entity->get('field_has_partner')->getValue()) {    // Has a partner
        $pID = (int)$has_partner[0]['target_id'];
        $partner = \Drupal\user\Entity\User::load($pID);
        $name = $partner->getUsername();
        if (user_is_blocked($name)) {                                        // Partner is blocked!
          $team = "! Partner $name is Blocked!";
        } else {
          $team = $username . " and " . $name;
          $names = explode(' ', $team);
          if ($names[1] === end($names)) {
            unset($names[1]);
            $team = implode(' ', $names);
          }
        }
      }
    }
    $entity->set('field_team_name', $team);

    //--- Build the user's availabilty string ------------------------------------------------------

    $aString = str_split("______");
    $a = array("Thursday Night", "Friday Night", "Saturday Matinee", "Saturday Night", "Sunday Matinee", "Sunday Night");
    $c = str_split("TFsSuU");

    $fields = $entity->toArray();
    $days = $fields['field_available_days_times'];
    foreach ($days as $day) {
      // ksm("key, day...", $key, $day);
      if (false !== $key = array_search($day['value'], $a)) {
        $aString[$key] = $c[$key];
        // ksm("a, day, key, aString...", $a, $day['value'], $key, $aString);
      } else {
        drupal_set_message("Available '$day' NOT found in acceptable list!", "error");
      }
    }
    $entity->set('field_available', implode("", $aString));

    /*--- Genearate, or find, corresponding assignment nodes -----------------------------------------

    // $roles holds this user's volunteer roles... loop on them.
    // $team holds this user's team name/status.

    $assignments = array();

    foreach ($roles as $role) {
      $assignment = false;

      if ($role === "Monitor" || $role === "Concessions") {
        if (strlen($team) > 0) {
          if ($team[0] != '*' && $team[0] != '~' && $team[0] != '!' ) {
            $assignment = $team . " - " . $role;
          }
        }
      }

      if ($role === "Manager" || $role === "Ticket Seller") {
        $assignment = $username . " - " . $role;
      }

      // ksm("roles, role, username, team, assignment...", $roles, $role, $username, $team, $assignment);

      // Check if the assignment already exists, load if not or create if necessary.
      if ($assignment) {
        $values = \Drupal::entityQuery('node')
            ->condition('type', 'assignment')
            ->condition('title', $assignment)
            ->execute();
        if ($node_exists = !empty($values)) {
          $nid = $values[key($values)];
          $node = \Drupal\node\Entity\Node::load($nid);         // ...assignment exists, nothing to do here
          drupal_set_message("Found an existing assignment role for '$assignment'.'");
        } else {
          $node = \Drupal\node\Entity\Node::create(array(       // ...assignment doesn't exist, create it.
              'type' => 'assignment',
              'title' => $assignment,
              'langcode' => 'en',
              'uid' => '1',
              'status' => 1,
          ));
          $node->save();
          // drupal_set_message("Generated a new assignment role of '$assignment'.");
        }
        $nid = $node->id( );   // ksm("nid...", $nid);
        $assignments[] = array('target_id' => "$nid");
      }
    }

    */

    //--- Save ALL user changes -----------------------------------------------------------------------

    // $entity->set('field_assignment_role', $assignments);  // ksm("all done, saving new entity...", $entity);
    $entity->save();

    drupal_set_message("Completed volunteer update for '$username'.");

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
    drupal_set_message('This is \Drupal\wieting\Plugin\Action\UpdateVolunteer\access.');
    return TRUE;
  }

  /** getVolunteerRoles
   *
   * @param $vol
   * @return array
   */
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

  /** findPrimaryPartner
   *
   * @param $id
   * @param $name
   * @return string
   */
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

}

?>
