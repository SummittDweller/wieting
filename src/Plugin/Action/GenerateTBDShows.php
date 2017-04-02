<?php
/**
 * @file
 * Contains \Drupal\wieting\Plugin\Action\CreatePerformances.
 *
 * Lifted from https://www.drupal.org/node/2330631
 */

namespace Drupal\wieting\Plugin\Action;

use Drupal\Core\Action\ActionBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\wieting\Plugin\Action\Common;

/**
 * Generate 1 month of TBD shows after the selected show.
 *
 * @Action(
 *   id = "generate_tbd_shows",
 *   label = @Translation("Generate 1 month of TBD shows after the specified show."),
 *   type = "node"
 * )
 */
class GenerateTBDShows extends ActionBase {

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    drupal_set_message('This is \Drupal\wieting\Plugin\Action\GenerateTBDShows\execute.');
    // ksm("entity...", $entity);

    // Check that "TO BE DETERMINED" is the selected show!
    $showTitle = $entity->get('title')->value;
    if ($showTitle != 'TO BE DETERMINED') {
      drupal_set_message("The selected show must be 'TO BE DETERMINED' for this function to work!", "error");
      return;
    }

    // Find the opening date of the selected show.
    $fields = $entity->toArray();
    $opens = strtotime($fields['field_opens'][0]['value']);    // a UTC timestamp
    // ksm("opens...", $opens);

    // Loop forward through 34 possible TBD dates...
    for ($i=0; $i<34; $i++) {
      $next = $opens + ($i*24*60*60);                          // a UTC timestamp
      $utc = date("Y-m-d\TH:i:s", $next);    // a UTC string
      $local = strtotime($utc . " UTC");                 // equiv. local timestamp
      $localS = date('l, F j, Y - g A', $local);
      $weekday = (int)date("N", $local);               // 1 = Monday ... 7 = Sunday
      $month = (int)date("n", $local);                // 1 = Jan ... 12 = Dec
      // ksm("opens, next, utc, local, weekday, month...", $opens, $next, $utc, $local, $weekday, $month);
      if ($weekday < 4) { continue; }                                   // skip Monday thru Wednesday
      if ($weekday === 4 && ($month < 6 || $month > 8)) { continue; }   // Thursday, but not summer

      // OK, we have a good target date.
      // $msg = "Generating a TBD performance for $localS.";
      // drupal_set_message($msg);
      Common::createOnePerformance($entity, $localS, $utc, false);
    }
  }


  /** createTBDPerformance
   *
   * @param $entity
   * @param $title
   * @param string $utc - Performance start time in UTC (GMT time)
   * @param $final
   *
  private function createTBDPerformance($entity, $title, $utc, $final) {

    // Check if the performance already exists, load if not or create if necessary.
    $values = \Drupal::entityQuery('node')
        ->condition('type', 'performance')
        ->condition('title', $title)
        ->execute();
    if ($node_exists = !empty($values)) {
      $nid = $values[key($values)];
      $node = \Drupal\node\Entity\Node::load($nid);         // ...performance exists, load it for update.
      drupal_set_message("Updating an existing performance node for $title.");
      // ksm("existing node...", $node);
    } else {
      $node = \Drupal\node\Entity\Node::create(array(       // ...performance doesn't exist, create it.
        'type' => 'performance',
        'title' => $title,
        'langcode' => 'en',
        'uid' => '1',
        'status' => 1,
      ));
    }

    // Complete the performance info.
    $node->field_show->entity = $entity;
    $running = intval($entity->get('field_running_time')->value) * 60;
    $end = strtotime($utc) + $running;
    $endT = date("Y-m-d\TH:i:s", $end);
    // ksm("utc...", $utc, "running", $running, "end...", $end, "endT...", $endT);
    $local = strtotime($utc.' UTC');    // returns a "local" timestamp!
    $node->set('field_format', "Not Applicable");
    $node->set('field_performance_times', $utc);
    $node->set('field_performance_ends', $endT);
    $node->set('field_final_wieting_performance_', $final);
    $node->set('field_performance_date', date("Y-m-d", $local));  // @TODO Kept to overcome Views calendar BUG
    $node->save();
  }
*/

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
    drupal_set_message('This is \Drupal\wieting\Plugin\Action\GenerateTBDShows\access.');
    return TRUE;
  }

}

?>
