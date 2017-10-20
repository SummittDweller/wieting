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
 * Generate 3 months of TBD shows after the selected show.
 *
 * @Action(
 *   id = "generate_tbd_shows",
 *   label = @Translation("Generate 3 months of TBD shows after the specified show."),
 *   type = "node"
 * )
 */
class GenerateTBDShows extends ActionBase {

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    // drupal_set_message('This is \Drupal\wieting\Plugin\Action\GenerateTBDShows\execute.');
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

    // Loop forward through 99 possible TBD dates...
    for ($i=0; $i<99; $i++) {
      $next = $opens + ($i*24*60*60);                                  // a UTC timestamp
      $utc = date("Y-m-d\TH:i:s", $next);                       // a UTC string
      $local = strtotime($utc . " UTC");                          // equiv. local timestamp
      $localS = date('l, F j, Y - g A', $local);
      $weekday = (int)date("N", $local);                         // 1 = Monday ... 7 = Sunday
      $month = (int)date("n", $local);                           // 1 = Jan ... 12 = Dec

      // ksm("opens, next, utc, local, weekday, month...", $opens, $next, $utc, $local, $weekday, $month);

      if ($weekday < 4) { continue; }                                   // skip Monday thru Wednesday
      if ($weekday === 4 && ($month < 6 || $month > 8)) { continue; }   // Thursday, but not summer

      // OK, we have a good target date.
      // $msg = "Generating a TBD performance for $localS.";
      // drupal_set_message($msg);
      Common::createOnePerformance($entity, $localS, $utc, false);
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
