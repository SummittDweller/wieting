<?php
/**
 * Created by PhpStorm.
 * User: markmcfate
 * Date: 1/28/17
 * Time: 6:09 PM
 */

/**
 * Performs a volunteer analysis action for one specified user account.
 *
 * @param $object
 * @param $context
 */
function wieting_volunteer_analysis_action($object, $context) {
    static $bot = '======================================================================<p/>';
    static $blanks = "   ";
    static $sep = "  |  ";

    // Report this volunteer's roles and partner info.
    $first_name = $object->profile_first_name;

    // Standard text
    $txt  = "$object->name [$object->uid]<br/>";
    $mail = ( strstr( $object->mail, "bogus" ) ? "<b>None!</b>" : $object->mail );
    $txt = "Email: $mail $sep Phone: $object->profile_phone $sep Roles: ";

    // Roles
    $needs_partner = $is_partner = FALSE;
    foreach ( $object->roles as $rid => $role ) {
        if ( $rid > 2 ) {
            $txt .= "$role, ";
            if ($rid == 5 || $rid == 9) { $needs_partner = TRUE; }
            if ($rid == 12 || $rid == 13) { $is_partner = TRUE; }
        }
    };
    $txt = rtrim( $txt, ', ' );
    $txt .= "\n";

    // Construct a string showing this volunteer's available nights (in black, unavailable in red).
    $nights = "";
    $nights .= ((strpos($context[row]->node_users_node_data_field_vol_analysis_results_field_available_no_thursdays_value,"not available")) ? "<font color='red'>" : "<font color='blue'>");
    $nights .= "Th</font>";
    $nights .= ((strpos($context[row]->node_users_node_data_field_vol_analysis_results_field_available_no_fridays_value,"not available")) ? "<font color='red'>" : "<font color='black'>");
    $nights .= "Fr</font>";
    $nights .= ((strpos($context[row]->node_users_node_data_field_vol_analysis_results_field_available_no_saturdays_value,"not available")) ? "<font color='red'>" : "<font color='blue'>");
    $nights .= "Sa</font>";
    $nights .= ((strpos($context[row]->node_users_node_data_field_vol_analysis_results_field_available_no_sundays_value,"not available")) ? "<font color='red'>" : "<font color='black'>");
    $nights .= "Su</font>";

    // drupal_set_message( "Object: ".print_r($object,TRUE), 'status');
    // drupal_set_message( "Context: ".print_r($context,TRUE), 'status');
    // drupal_set_message( "Friday: ".$context[row]->node_users_node_data_field_vol_analysis_results_field_available_no_fridays_value, 'status'); */

    // Partner?  If $rid is 5 (monitor) or 9 (concession) they should have one!
    $puid = $context[row]->node_users_node_data_field_vol_analysis_results_field_partner_uid;
    // $puid = $context[row]->node_users_node_data_field_partner_field_partner_uid;

    if ($puid > 0 && $partner = user_load($puid)) {
        $txt .= "$first_name ($nights) is partnered with $partner->name [$partner->uid].";
    } else if ($needs_partner) {
        $txt .= "$first_name ($nights) has <font color='red'><b>NO</b></font> partner!";
    } else {
        $txt .= "$first_name ($nights) has no partner.";
    }

    // If this person is a partner ($rid == 12 or 13) then we need to find out who they are partnered with.
    if ($is_partner) {
        $sql = "SELECT vid FROM content_type_available_dates WHERE ( field_partner_uid=".$object->uid." )";
        $resource = db_query( $sql );
        while ($row = db_fetch_array( $resource )) { $vids[] = $row[vid]; }
        foreach ($vids as $vid) {
            $sql = "SELECT title FROM node_revisions WHERE ( vid=" . $vid . " )";
            $resource = db_query($sql);
            $partners = "";
            while ($row = db_fetch_array($resource)) {
                $partners .= " " . $row[title] . ",";
                $onePartner = $row[title];
            }
            $txt .= "$sep $first_name is a partner for " . trim($partners, " ,") . ".\n";
        }

        // Assuming they have only ONE partner...print that person's analysis info.
        $sql = "SELECT vid FROM content_type_available_dates WHERE ( uid='".$onePartner."' )";
        $resource = db_query( $sql );
        while ($row = db_fetch_array( $resource )) { $vid = $row[vid]; }
        if ($vid > 0) { $txt .= "\nonePartner = $onePartner has a NAD VID of $vid.\n"; }

    } else {
        $txt .= "\n";
    }

    /* $msg = print_r($context, TRUE);
    drupal_set_message("User context: $msg", 'status'); */

    // Find this volunteer's NOT Available Dates record and load that node.
    $nadNID = $context['row']->node_users_nid;
    if (!$nadNode = node_load($nadNID)) {
        drupal_set_message("Could not load NOT Available Dates record [$nadNID] for ".$object->name.".", 'error');
        return;
    }

    // Save the analysis into the volunteer's NOT_Available_Dates record.
    $nadNode->field_vol_analysis_results[0]['value'] = $txt;
    node_save($nadNode);

    return;
}

//-------------------------------------------------------------------------------------
// Perform a monitors 'swap'.
//

function wieting_swap_monitors_action($object,$context) {

    static $objects = array();  // array must be static to persist between rows (calls)

    array_push($objects,$object);
    $count = count($objects);

    // If exactly two objects are available, swap their monitors.

    if ($count === 2) {
        $path = 'wieting/swap_form/Monitors/'.$objects[0]->nid.'/'.$objects[1]->nid;
        unset( $objects );
        drupal_goto($path);        // Invoke the form.
    }

    // If exactly one performance is selected, print a lit of available swaps/replacements.
    /*  Can't be done like this since there will always be at least one performance checked!
    if ($count === 1) {
      $path = 'wieting/available/Monitors/'.$objects[0]->nid;
      unset( $objects );
      drupal_goto($path);        // Invoke the available listing.
    }
    */
    return;
}

//-------------------------------------------------------------------------------------
// Perform a monitors 'check'.
//

function wieting_check_monitors_action($object,$context) {

    $path = 'wieting/available/Monitors/'.$object->nid;
    drupal_goto($path);        // Invoke the available listing.

    return;
}
//-------------------------------------------------------------------------------------
// Perform a managers 'check'.
//

function wieting_check_managers_action($object,$context) {

    $path = 'wieting/available/Managers/'.$object->nid;
    drupal_goto($path);        // Invoke the available listing.

    return;
}

//-------------------------------------------------------------------------------------
// Perform a concessions 'check'.
//

function wieting_check_concessions_action($object,$context) {

    $path = 'wieting/available/Concessions/'.$object->nid;
    drupal_goto($path);        // Invoke the available listing.

    return;
}

//-------------------------------------------------------------------------------------
// Perform a ticket sellers 'check'.
//

function wieting_check_ticket_sellers_action($object,$context) {

    $path = 'wieting/available/Ticket_Sellers/'.$object->nid;
    drupal_goto($path);        // Invoke the available listing.

    return;
}

//-------------------------------------------------------------------------------------
// Perform a concessions 'swap'.
//

function wieting_swap_concessions_action( $object, $context ) {

    global $user;
    if (!array_key_exists(3,$user->roles)) {
        drupal_set_message(t('Only a mangaer can perform a volunteer swap!'));
        return;
    }

    static $objects = array( );  // array must be static to persist between rows (calls)

    array_push( $objects, $object );
    $count = count( $objects );

    // If exactly two objects are available, swap their concessions.

    if ( $count === 2 ) {
        $path = 'wieting/swap_form/Concessions/'.$objects[0]->nid.'/'.$objects[1]->nid;
        unset( $objects );
        drupal_goto($path);        // Invoke the form.
    }

    return;
}

/*
* Implements hook_validate.
*
*/

function wieting_swapform_validate($form,&$form_state) {

    global $user;
    if (!array_key_exists(3,$user->roles)) {
        drupal_set_message(t('Only a mangaer can perform a volunteer swap!'));
        return;
    }

    // drupal_set_message(t('wieting_swapform_validate has been called.'));
    // var_dump($form_state);

    for ( $i=0; $i<2; $i++ ) {
        $count[$i] = 0;
        foreach ($form_state['clicked_button']['#post'][$i]['select'] as $k => $v) {
            $form_state['wieting_swapform'][$i][$count[$i]] = $k;
            $count[$i]++;
        }
    }

    if ($count[0] + $count[1] < 2)
        form_error($form,'You must select at least 2 volunteers!');
    else if ($count[0] != $count[1])
        form_error($form,'You must select the same number of volunteers from each performance!');
    else
        $form_state['wieting_swapform']['count'] = $count[0];

    return;
}

/*
* Implements hook_submit.
*
*/

function wieting_swapform_submit($form,&$form_state) {

    // drupal_set_message(t('wieting_swapform_submit has been called.'));

    $count = $form_state['wieting_swapform']['count'];
    $role = $form_state['wieting_swapform']['role'];
    $nid[0] = $form_state['wieting_swapform']['nid'][0];
    $nid[1] = $form_state['wieting_swapform']['nid'][1];

    // drupal_set_message('Role is: '.$role);

    // Retrieve both nodes.
    for ( $i=0; $i<2; $i++ )
        $n[$i] = node_load($nid[$i]);

    $users = array();

    // Loop through the swaps.
    for ( $i=0; $i<$count; $i++ ) {
        $pos0 = $form_state['wieting_swapform'][0][$i];  // position of selected performance 0 volunteer
        $pos1 = $form_state['wieting_swapform'][1][$i];  // position of selected performance 1 volunteer

        switch($role) {
            case 'Monitors':
                $users[] = $temp = $n[0]->field_performance_monitors[$pos0]['uid'];
                $users[] = $n[0]->field_performance_monitors[$pos0]['uid'] = $n[1]->field_performance_monitors[$pos1]['uid'];
                $n[1]->field_performance_monitors[$pos1]['uid'] = $temp;
                break;
            case 'Concessions':
                $users[] = $temp = $n[0]->field_performance_concessions[$pos0]['uid'];
                $users[] = $n[0]->field_performance_concessions[$pos0]['uid'] = $n[1]->field_performance_concessions[$pos1]['uid'];
                $n[1]->field_performance_concessions[$pos1]['uid'] = $temp;
                break;
            case 'Managers':
                $users[] = $temp = $n[0]->field_performance_manager[0]['uid'];
                $users[] = $n[0]->field_performance_manager[0]['uid'] = $n[1]->field_performance_manager[0]['uid'];
                $n[1]->field_performance_manager[0]['uid'] = $temp;
                break;
            case 'Ticket_Sellers':
                $users[] = $temp = $n[0]->field_performance_ticket_seller[0]['uid'];
                $users[] = $n[0]->field_performance_ticket_seller[0]['uid'] = $n[1]->field_performance_ticket_seller[0]['uid'];
                $n[1]->field_performance_ticket_seller[0]['uid'] = $temp;
                break;
        }
    }

    node_save(&$n[0]);
    node_save(&$n[1]);

    wieting_notify_swap($role,
        array($n[0]->title,$n[1]->title),
        array($n[0]->field_performance_manager[0]['uid'],$n[1]->field_performance_manager[0]['uid']),
        $users);

    drupal_goto('volunteer_schedule_vbo');
    return;
}

//-------------------------------------------------------------------------------------
// Perform a single volunteer assignment change...NOT a swap!
//

function wieting_change_one_volunteer_action( $object, $context ) {
    global $user;
    if ( !array_key_exists( 3, $user->roles )) {
        drupal_set_message( t( 'Only a mangaer can change a volunteer assignment!' ));
        return;
    }

    static $objects = array( );  // array must be static to persist between rows (calls)

    array_push( $objects, $object );
    $count = count( $objects );

    /*
    print '<pre>';
    print 'The $object is: ';
    McFate_Dump( $object );
    print 'The $context is: ';
    McFate_Dump( $context );
    print 'Count of $objects is now ' . $count = count( $objects );
    print '</pre>';
    */

    // If exactly one performance was selected...proceed.

    if ( $count === 1 ) {
        $args = $objects[0]->field_performance_manager[0]['uid'].'/';
        $args .= $objects[0]->field_performance_ticket_seller[0]['uid'].'/';
        for ( $i=0; $i<4; $i++ ) {
            if ( $m = $objects[0]->field_performance_monitors[$i]['uid'] ) { $args .= $m.'/'; }
            if ( $c = $objects[0]->field_performance_concessions[$i]['uid'] ) { $args .= $c.'/'; }
        }
        $path = 'performance_team_vbo/'.$args;
        unset( $objects );
        drupal_goto( $path );        // Invoke the form.
    }

    return;
}


//-------------------------------------------------------------------------------------
// Perform a manager 'swap'.
//

function wieting_swap_managers_action($object,$context) {
    global $user;
    if (!array_key_exists(3,$user->roles)) {
        drupal_set_message(t('Only a mangaer can perform a volunteer swap!'));
        return;
    }

    static $objects = array();  // array must be static to persist between rows (calls)

    array_push($objects,$object);
    $count = count($objects);

    /*
    print '<pre>';
    print 'The $object is: ';
    McFate_Dump( $object );
    print 'The $context is: ';
    McFate_Dump( $context );
    print 'Count of $objects is now ' . $count = count( $objects );
    print '</pre>';
    */

    // If exactly two objects are available, swap their managers.

    if ($count === 2) {
        $path = 'wieting/swap_form/Managers/'.$objects[0]->nid.'/'.$objects[1]->nid;
        unset($objects);
        drupal_goto($path);        // Invoke the form.
    }

    return;
}

//-------------------------------------------------------------------------------------
// Perform a ticket seller 'swap'.
//

function wieting_swap_ticket_sellers_action($object,$context) {

    global $user;
    if (!array_key_exists(3,$user->roles)) {
        drupal_set_message(t('Only a mangaer can perform a volunteer swap!'));
        return;
    }

    static $objects = array();  // array must be static to persist between rows (calls)

    array_push($objects,$object);
    $count = count($objects);

    // If exactly two objects are available, swap their ticket sellers.

    // fb('fb:Swapping ticket sellers!');
    // dfb('dfb:Swapping ticket sellers!');

    if ($count === 2) {
        $path = 'wieting/swap_form/Ticket_Sellers/'.$objects[0]->nid.'/'.$objects[1]->nid;
        unset($objects);
        drupal_goto($path);        // Invoke the form.
    }

    return;
}
