<?php
/*
Copyright (c) 2005-2008, Alex Tingle.

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
*/


/** Utility class used by upgrade_database().
 *  Breaks apart a version string into an array of comparable parts. */
class ec3_Version
{
  var $part; ///< Array of version parts.

  function ec3_Version($str)
  {
    $s=preg_replace('/([-a-z]+)([0-9]+)/','\1.\2',$str);
    $v=explode('.',$s);
    $this->part=array();
    foreach($v as $i)
    {
      if(preg_match('/^[0-9]+$/',$i))
          $this->part[]=intval($i);
      elseif(preg_match('/^dev/',$i))
          $this->part[]=-1000;
      elseif(preg_match('/^_/',$i))
          $this->part[]=-500;
      elseif(preg_match('/^a(lpha)?/',$i))
          $this->part[]=-3;
      elseif(preg_match('/^b(eta)?/',$i))
          $this->part[]=-2;
      elseif(preg_match('/^rc?/',$i))
          $this->part[]=-1;
      elseif(empty($i))
          $this->part[]=0;
      else
          $this->part[]=$i;
    }
  }

  /** Compares this version with $other. */
  function cmp($other)
  {
    for($i=0; $i < max(count($this->part),count($other->part)); $i++)
    {
      // Fill in empty pieces.
      if( !isset($this->part[$i]) )
          $this->part[$i] = 0;
      if( !isset($other->part[$i]) )
          $other->part[$i] = 0;
      // Compare
      if( $this->part[$i] > $other->part[$i] )
          return 1;
      if( $this->part[$i] < $other->part[$i] )
          return -1;
    }
    // They really are equal.
    return 0;
  }
};


class ec3_Admin
{


  function filter_admin_head()
  {
    global $ec3;

    // Turn OFF advanced mode when we're in the admin screens.
    $ec3->advanced=false;

    ?>
    <!-- Added by eventcalendar3/admin.php -->
    <style type='text/css' media='screen'>
    @import url(<?php echo $ec3->myfiles; ?>/admin.css);
    </style>
    <!-- These scripts are only needed by edit_form screens. -->
    <script type='text/javascript' src='<?php echo $ec3->myfiles; ?>/addEvent.js'></script>
    <script type='text/javascript' src='<?php echo $ec3->myfiles; ?>/edit_form.js'></script>
    <script type='text/javascript'><!--
    Ec3EditForm.event_cat_id='<?php echo $ec3->wp_in_category.$ec3->event_category; ?>';
    Ec3EditForm.start_of_week=<?php echo intval( get_option('start_of_week') ); ?>;
    // --></script>
    <!-- jscalendar 1.0 JavaScripts and css locations --> 
    <style type="text/css">@import url(<?php echo $ec3->myfiles; ?>/css/calendar-blue.css);</style>
    <script type="text/javascript" src="<?php echo $ec3->myfiles; ?>/js/calendar.js"></script>
    <script type="text/javascript" src="<?php echo $ec3->myfiles; ?>/js/calendar-en.js"></script>
    <script type="text/javascript" src="<?php echo $ec3->myfiles; ?>/js/calendar-setup.js"></script>
    
    <?php
  }


  //
  // EDIT FORM
  //


  /** Only for pre WP2.5. Inserts the Event Editor into the Write Post page. */
  function filter_edit_form()
  { ?>
    
    <!-- Build the user interface for Event-Calendar. -->
    <div class="dbx-b-ox-wrapper">
    <fieldset id='ec3_schedule_editor' class="dbx-box">
    <div class="dbx-h-andle-wrapper">
    <h3 class="dbx-handle"><?php _e('Event Editor','ec3'); ?></h3>
    </div>
    <div class="dbx-c-ontent-wrapper">
    <div class="dbx-content">

    <?php $this->event_editor_box() ?>

    </div>
    </div>
    </fieldset>
    </div>

    <?php
  }


  function event_editor_box()
  {
    global $ec3,$wp_version,$wpdb,$post_ID;
    if(isset($post_ID))
      $schedule = $wpdb->get_results(
        "SELECT
           sched_id,
           DATE_FORMAT(start,'%Y-%m-%d') AS start,
           DATE_FORMAT(end,'%Y-%m-%d') AS end,
           time_start,
           time_end,
           allday,
           lieux_id,
           option_id,
           rpt
         FROM $ec3->schedule WHERE post_id=$post_ID ORDER BY start");
    else
      $schedule = false;

    if(function_exists('wp_create_nonce'))
    {
      echo '<input type="hidden" name="ec3_nonce" id="ec3_nonce" value="' . 
        wp_create_nonce( plugin_basename(__FILE__) ) . '" />';
    }
    ?>

    <!-- Event-Calendar: Event Editor -->
    <table cellspacing="2" cellpadding="5" class="editform" style="position : relative;">
     <thead><tr>
      <th><?php _e('Start','ec3'); ?></th>
      <th><?php _e('End','ec3'); ?></th>
      <th><?php _e('Time Start','ec3'); ?></th>
      <th><?php _e('Time End','ec3'); ?></th>
      <th style="text-align:center"><?php _e('All Day','ec3'); ?></th>
      <th style="text-align:center"><?php _e('Lieux','ec3'); ?></th>
      <th><?php _e('Options','ec3'); ?></th>
     </tr></thead>
     <tbody>
    <?php
      if($schedule)
      {
	  echo '<script>';
	  echo 'var ec3_oldrows = new Array();';
	  echo 'function ec3_show_oldrows(){ for (var i=0; i<ec3_oldrows.length; i++) ec3_oldrows[i].style.display = ""; }';
	  echo 'function ec3_hide_oldrows(){ for (var i=0; i<ec3_oldrows.length; i++) ec3_oldrows[i].style.display = "none"; }';
	  echo '</script>';
        foreach($schedule as $s){
            $time_start = substr($s->time_start, 0, 5);
            $time_end = substr($s->time_end, 0, 5);
            $this->schedule_row(
              $s->start,$s->end,$time_start,$time_end,$s->sched_id,'update',$s->allday,$s->lieux_id,$s->option_id
            );
          }
        $ec3_rows=count($schedule);
	  echo '<script>ec3_hide_oldrows();</script>';
      }
      $default=ec3_strftime('%Y-%m-%d',3600+time());
      $time_default = '00:00';
      $this->schedule_row($default,$default,$time_default,$time_default,'','create',False,'','');
    ?>
      <tr> 
       <td colspan="7" style="text-align:left">
        <p style="margin:0;padding:0;text-align:left">
          <span style="vertical-align:middle;"><input type="button" name="ec3_new_row" style="display: block; margin-bottom: 20px;" value=" + " title="<?php _e('Add a new event','ec3'); ?>" onclick="if (document.getElementById('ec3_repeat_check').checked && document.getElementById('ec3_repeat_count').value == '') { alert('Please enter a repeat number!'); return false; } else if (document.getElementById('ec3_repeatuntil_check').checked && (document.getElementById('ec3_repeatuntil_date').value == '' || document.getElementById('ec3_repeatuntil_date').value == 'yyyy-mm-dd')) { alert('Please enter a repeat end date!'); return false; } else { if (ec3_repeatuntil_check.checked) { Ec3EditForm.add_row(ec3_repeatuntil_check.checked,ec3_repeatuntil_date.value,ec3_repeatuntil_type.value); } else { Ec3EditForm.add_row(ec3_repeat_check.checked,ec3_repeat_count.value,ec3_repeat_type.value); } }" /></span>
	  <span style="vertical-align:middle;">&nbsp;&nbsp;<input type="checkbox" onclick="if (this.checked) { ec3_show_oldrows(); } else { ec3_hide_oldrows(); }"></span>
	  <span style="vertical-align:middle;">Show past events</span>
          <br>

          <div class="paramDefault">
            <label for="lieuxDefault">Lieux par défault</label>
            <span style="max-width:160px; display:inline-block;">
              <?php get_lieux('def',$post_ID); ?>
            </span>
            <label for="horraireDefault">Horraire par défault</label>:
            <label for="debut">debut :</label><input class="hD" name="ec3_hDebut" type="text" size="3" maxlength="5" disabled="disabled" placeholder="00:00">
            <label for="fin">fin :</label><input type="text" size="3" maxlength="5" disabled="disabled" placeholder="00:00">
          </div>

	    <span style="vertical-align:middle;">&nbsp;&nbsp;<input type="checkbox" name="ec3_repeat_check" id="ec3_repeat_check" value="yes" onclick="if (this.checked && document.getElementById('ec3_repeatuntil_check').checked) document.getElementById('ec3_repeatuntil_check').click(); document.getElementById('ec3_repeat_type').disabled = !this.checked; document.getElementById('ec3_repeat_count').disabled = !this.checked;" /></span>

          <span style="vertical-align:middle;">Repeat last entry for</span>
          <span style="vertical-align:middle;"><input type="text" name="ec3_repeat_count" id="ec3_repeat_count" size="4" style="font-size:8pt;" disabled /></span>
          <span style="vertical-align:middle;padding-top:5px;"><select name="ec3_repeat_type" id="ec3_repeat_type" disabled>
            <option value="opt1">weeks</option>
            <option value="opt2">months (e.g. 15th of every month)</option>
            <option value="opt3">months (e.g. 2nd Monday of every month)</option>
          </select></span><br>

          <span style="vertical-align:middle;">&nbsp;&nbsp;<input type="checkbox" name="ec3_repeatuntil_check" id="ec3_repeatuntil_check" value="yes" onclick="if (this.checked && document.getElementById('ec3_repeat_check').checked) document.getElementById('ec3_repeat_check').click(); document.getElementById('ec3_repeatuntil_type').disabled = !this.checked; document.getElementById('ec3_repeatuntil_date').disabled = !this.checked;" /></span>
          <span style="vertical-align:middle;">Repeat last entry every</span>
          <span style="vertical-align:bottom;"><select name="ec3_repeatuntil_type" id="ec3_repeatuntil_type" disabled>
            <option value="opt1">week</option>
            <option value="opt2">month (e.g. 15th of every month)</option>
            <option value="opt3">month (e.g. 2nd Monday of every month)</option>
          </select></span>
          <span style="vertical-align:middle;">until</span>
	    <span style="vertical-align:bottom;"><input type="text" size="10" maxlength="10" value="yyyy-mm-dd" onclick="if (this.value=='yyyy-mm-dd') this.value='';" onblur="if (this.value=='') this.value='yyyy-mm-dd';" name="ec3_repeatuntil_count" id="ec3_repeatuntil_date" size="4" style="font-size:8pt;" disabled /></span>
          <input type="hidden" id="ec3_rows" name="ec3_rows" value="<?php echo $ec3_rows; ?>" />

        </p>
       </td>
      </tr> 
     </tbody>
    </table>


    <?php
  }

  /** Utility function called by event_editor_box(). */
  function schedule_row($start,$end,$time_start,$time_end,$sid,$action,$allday,$lieux_id,$option_id)
  {
      global $post_ID;
    $s="ec3_start_$sid";
    $e="ec3_end_$sid";
    $ts="ec3_timeStart_$sid";
    $te="ec3_timeEnd_$sid";
    $now=substr(date(DATE_W3C),0,10);
    ?>
      <tr class="ec3_schedule_row" valign="middle" id="ec3_tr_<?php echo $sid; ?>" name="ec3_tr_<?php echo $sid; ?>" <?php
       if('create'==$action){ echo ' style="display:none"'; } ?>>
       <td>
        <input type="hidden" name="ec3_action_<?php echo $sid;
         ?>" value="<?php echo $action; ?>" />
        <input type="text" name="<?php echo $s;
         if('update'==$action){ echo "\" id=\"$s"; }
         ?>" value="<?php echo $start; ?>" />
        <button type="reset" id="trigger_<?php echo $s; ?>">&hellip;</button>
       </td>
       <td>
        <input type="text" name="<?php echo $e;
         if('update'==$action){ echo "\" id=\"$e"; }
         ?>" value="<?php echo $end; ?>" />
        <button type="reset" id="trigger_<?php echo $e; ?>">&hellip;</button>
       </td>
       <td>
        <input type="text" name="<?php echo $ts;
         if('update'==$action){ echo "\" id=\"$ts"; }
         ?>" value="<?php echo $time_start; ?>" />
       </td>
       <td>
        <input type="text" name="<?php echo $te;
         if('update'==$action){ echo "\" id=\"$te"; }
         ?>" value="<?php echo $time_end; ?>" />
       </td>
       <td style="text-align:center">
        <input type="checkbox" name="ec3_allday_<?php echo $sid;
         ?>" value="1"<?php if($allday){ echo ' checked="checked"'; } ?> />
       </td>
       <td style="max-width : 200px;">
         <?php get_lieux($sid,$post_ID); ?>
       </td>
        <td>
          <?php get_option_event($sid,$post_ID); ?>
        </td>
        <td>
        <p style="margin:0;padding:0">
         <input type="button" name="ec3_del_row_<?php echo $sid;
          ?>" value=" &mdash; "
          title="<?php _e('Delete this event','ec3'); ?>"
          onclick="Ec3EditForm.del_row(this)" />
        </p>
       </td>
      </tr> 
    <?php
    if ($start < $now) echo '<script>ec3_oldrows.push(ec3_tr_' . $sid . ');</script>';
  }


  function action_save_post($post_ID)
  {
    if(!$_POST)
        return;

    if(function_exists('wp_verify_nonce'))
    {
      if(!wp_verify_nonce($_POST['ec3_nonce'], plugin_basename(__FILE__) ))
          return;
    }
    global $ec3,$wpdb;
    
    $tablePost = $wpdb->prefix . 'posts';
    $testType = $wpdb->get_row('SELECT post_type FROM '.$tablePost.' WHERE ID = '.$post_ID.' ');
    if ( $testType->post_type == 'revision' ) {
      return;
    }
    // Ensure that we only save each post once.
    if(isset($this->save_post_called) && $this->save_post_called[$post_ID])
        return;
    if(!isset($this->save_post_called))
       $this->save_post_called=array();
    $this->save_post_called[$post_ID]=true;

    
    // Use this to check the DB before DELETE/UPDATE. Should use
    // ...IGNORE, but some people insist on using ancient version of MySQL.
    $count_where="SELECT COUNT(0) FROM $ec3->schedule WHERE";

    // If this post is no longer an event, then purge all schedule records.
    if(isset($_POST['ec3_rows']) && '0'==$_POST['ec3_rows'])
    {
      if($wpdb->get_var("$count_where post_id=$post_ID"))
         $wpdb->query("DELETE FROM $ec3->schedule WHERE post_id=$post_ID");
      return;
    }

    // Find all of our parameters
    $sched_entries=array();
    $fields =array('start','end','timeStart','timeEnd','allday','rpt','lieux','option');
    $idLieuDef = $_POST['ec3_def_lieux'];
    update_post_meta($post_ID, 'ec3_lieu_default', $idLieuDef);

    
    foreach($_POST as $k => $v)
    {
      if(preg_match('/^ec3_(action|'.implode('|',$fields).')_(_?)([0-9]+)$/',$k,$match))
      {
        $sid=intval($match[3]);
        if(!isset( $sched_entries[$sid] ))
            $sched_entries[ $sid ]=array('allday' => 0);
        $sched_entries[ $sid ][ $match[1] ] = $v;
      }
    }

    foreach($sched_entries as $sid => $vals)
    {
      // Bail out if the input data looks suspect.
      if(!array_key_exists('action',$vals) || count($vals)<5)
        continue;
      // Save the value of 'action' and remove it. Leave just the column vals.
      $action=$vals['action'];
      unset($vals['action']);
      // Reformat the column values for SQL:
      foreach($vals as $k => $v)
        ?><script type="text/javascript">
          console.log(<?php echo $v; ?>);
        </script><?php
          if('allday'==$k){
              $vals[$k]=intval($v);
          }
          /*elseif ('lieux'==$k ){
            $vals[$k]= $v;
          }*/
          elseif ('option'==$k) {
            $vals[$k]=$v;
          }
          elseif ('timeStart'==$k || 'timeEnd'==$k) {
            $vals[$k]= "'".$v.":00'";
          }
          else{
              $vals[$k]="'".$wpdb->escape($v)."'";
          }
      $sid_ok=$wpdb->get_var("$count_where post_id=$post_ID AND sched_id=$sid");
      // Execute the SQL.
      if($action=='delete' && $sid>0 && $sid_ok):
        $wpdb->query(
         "DELETE FROM $ec3->schedule
          WHERE post_id=$post_ID
            AND sched_id=$sid"
        );
      elseif($action=='update' && $sid>0 && $sid_ok):
        $val_lieu = $vals['lieux'];
        if ($val_lieu == 99999) {
          $val_lieu = $_POST['ec3_def_lieux'];
        }
        $val_option = $vals['option'];
        $val_start = $vals['start'];
        $val_end = $vals['end'];
        $val_Tstart = $vals['timeStart'];
        $val_Tend = $vals['timeEnd'];
        $val_allday = $vals['allday'];
        $val_rpt = $vals['rpt'];
        //$sync = '0';
        $wpdb->update( $ec3->schedule, array( 'start' => $val_start, 'end' => $val_end, 'time_start' => $val_Tstart, 'time_end' => $val_Tend, 'allday' => $val_allday, 'rpt' => $val_rpt, 'lieux_id' => $val_lieu, 'option_id' => $val_option, 'sync' => '0' ), array( 'sched_id' => $sid, 'post_id' => $post_ID ) );

        $wpdb->query(
         "UPDATE $ec3->schedule
          SET sequence=sequence+1
          WHERE post_id=$post_ID
            AND sched_id=$sid"
        );
        
      elseif($action=='create'):
        $val_lieu = $vals['lieux'];
        if ($val_lieu == 99999) {
          $val_lieu = $_POST['ec3_def_lieux'];
        }
        if (empty($vals['option']) || !isset($vals['option']) ) {
          $val_option = '';
        }
        else{ $val_option = $vals['option']; }
        $val_start = $vals['start'];
        $val_end = $vals['end'];
        $val_Tstart = $vals['timeStart'];
        $val_Tend = $vals['timeEnd'];
        $val_allday = $vals['allday'];
        $val_rpt = $vals['rpt'];
        
        $wpdb->insert( $ec3->schedule, array( 'post_id' => $post_ID, 'start' => $val_start, 'end' => $val_end, 'time_start' => $val_Tstart, 'time_end' => $val_Tend, 'allday' => $val_allday, 'rpt' => $val_rpt, 'sequence' => '1', 'lieux_id' => $val_lieu, 'option_id' => $val_option, 'sync' => '0', 'event_uid' => '0') );
        
      endif;
    }

    // Verifie si tout les events du post sont synchronisé
    $listeSyncEvent = $wpdb->get_results('SELECT sync FROM '.$ec3->schedule.' WHERE post_id = '.$post_ID.';');
    $syncOrNot = 1;
    foreach ($listeSyncEvent as $key => $valSync) {
      if ($valSync->sync != 1){
        $syncOrNot = 0;
      }
    }
    update_post_meta( $post_ID, 'syncOrNot', $syncOrNot );
      
    // Force all end dates to be >= start dates.
    $wpdb->query("UPDATE $ec3->schedule SET end=start WHERE end<start");

  } // end function action_save_post()

  /** Utility function called by action_save_post(). */
  function implode_assoc($glue,$arr)
  {
    $result=array();
    foreach($arr as $key=>$value)
        $result[]=$key."=".$value;
    return implode($glue,$result);
  }
  
  /** Clear events for the post. */
  function action_delete_post($post_ID)
  {
    global $ec3,$wpdb;
    $wpdb->query("DELETE FROM $ec3->schedule WHERE post_id=$post_ID");
  }


  //
  // OPTIONS
  //


  /** Upgrade the installation, if necessary. */
  function upgrade_database()
  {
    global $ec3,$wpdb;
    // Check version - return if no upgrade required.
    $installed_version=get_option('ec3_version');
    if($installed_version==$ec3->version)
      return;

    $v0 = new ec3_Version($installed_version);
    $v1 = new ec3_Version($ec3->version);
    if( $v0->cmp($v1) > 0 )
      return; // Installed version later than this one ?!?!

    // Upgrade.
    $message = sprintf(__('Upgraded database to %1$s Version %2$s','ec3'),
        'Event-Calendar',$ec3->version
      ) . '.';

    $tables=$wpdb->get_results('SHOW TABLES',ARRAY_N);
    if(!$tables)
    {
      die(sprintf(__('Error upgrading database for %s plugin.','ec3'),
          'Event-Calendar'
        ));
    }

    $table_exists=false;
    foreach($tables as $t)
        if(preg_match("/$ec3->schedule/",$t[0]))
            $table_exists=true;

    if($table_exists)
    {
      $message .= '<br />'.__('Table already existed','ec3').'.';
    }
    else
    {
      $message .= '<br />'
        . sprintf(__('Created table %s','ec3'),$ec3->schedule).'.';
      $wpdb->query(
        "CREATE TABLE $ec3->schedule (
           sched_id BIGINT(20) AUTO_INCREMENT,
           post_id  BIGINT(20),
           sequence BIGINT(20),
           start    DATETIME,
           end      DATETIME,
           allday   BOOL,
           rpt      VARCHAR(64),
           PRIMARY KEY(sched_id)
         )");
      // Force the special upgrade page if we are coming from v3.0
      if( $ec3->event_category &&
          ( empty($v0) || $v0[0]<3 || ($v0[0]==3 && $v0[1]==0) ) )
      {
        update_option('ec3_upgrade_posts',1);
      }
    } // end if(!$table_exists)

    // Sequence column is new in v3.2.dev-01
    $v32dev01 = new ec3_Version('3.2.dev-01');
    if( $v0->cmp($v32dev01) < 0 )
    {
      $message .= '<br />'
        . sprintf(__('Added SEQUENCE column to table %s','ec3'),$ec3->schedule)
        . '.';
      $wpdb->query(
        "ALTER TABLE $ec3->schedule ADD COLUMN sequence BIGINT(20) DEFAULT 1"
      );
    }

    // Option ec3_show_event_box is new in v3.2.dev-02
    $hide_event_box=get_option('ec3_hide_event_box');
    if($hide_event_box!==false)
    {
      if(intval($hide_event_box))
        $ec3->set_show_event_box(2);
      else
        $ec3->set_show_event_box(0);
      update_option('ec3_hide_event_box',false);
    }

    // Record the new version number
    update_option('ec3_version',$ec3->version);

    // Display an informative message.
    echo '<div id="message" class="updated fade"><p><strong>';
    echo $message;
    echo "</strong></p></div>\n";
  } // end function upgrade_database();


  function action_admin_menu()
  {
    global $ec3;
    add_options_page(
      __('Event Calendar Options','ec3'),
      'Event-Calendar',
      'manage_options', // mod from 6 to stop it showing in Editor admin
      'ec3_admin',
      'ec3_options_subpanel'
    );

    if(empty($ec3->event_category))
      return; // Until EC is properly configured, only show the options page.

    if(function_exists('add_meta_box'))
    {
      add_meta_box(
        'ec3_schedule_editor',   // HTML id for container div
         __('Event Editor','ec3'),
        'ec3_event_editor_box',  // callback function
        'post',
        'normal',              // context
        'high'                   // priority
      );
    }
    else
    {
      global $ec3_admin;
      // Old (pre WP2.5) functionality.
      add_filter('simple_edit_form',    array(&$ec3_admin,'filter_edit_form'));
      if($ec3->wp_have_dbx)
        add_filter('dbx_post_advanced', array(&$ec3_admin,'filter_edit_form'));
      else
        add_filter('edit_form_advanced',array(&$ec3_admin,'filter_edit_form'));
    }
  }


  function options_subpanel()
  {
    global $ec3, $wpdb;
    $table_opt = $wpdb->prefix . 'ec3_add_opt';
    //$post_types = get_post_types( '', 'names' );
    //$listeActivePosteType = $ec3->listPostType;
    $OpenAgandaKey = $ec3->OpenAgandaKey;
    $OpenAgandaSecretKey = $ec3->OpenAgandaSecretKey;
    $OpenAgandaSlugName = $ec3->OpenAgandaSlugName;
    
    if(isset($_POST['info_update']))
    {
      echo '<div id="message" class="updated fade"><p><strong>';
      if(isset($_POST['ec3_event_category']))
          $ec3->set_event_category( intval($_POST['ec3_event_category']) );
      if(isset($_POST['ec3_show_event_box']))
          $ec3->set_show_event_box( intval($_POST['ec3_show_event_box']) );
      if(isset($_POST['ec3_advanced']))
          $ec3->set_advanced( intval($_POST['ec3_advanced']) );
      if(isset($_POST['ec3_tz']))
          $ec3->set_tz( $_POST['ec3_tz'] );
        
        // listPostType
      //$newlistPostType = 'post';
/*
        if( isset($_POST['ec3PosteType']) ){
          $newlistPostType = $_POST['ec3PosteType'];
        }

      $ec3->set_listPostType( $newlistPostType );
      $listeActivePosteType = $ec3->listPostType;
*/
      if(isset($_POST['OpenAgandaSlugName'])){
        $newOpenAgandaSlugName = $_POST['OpenAgandaSlugName'];
        $ec3->set_OpenAgandaSlugName( $newOpenAgandaSlugName );
        $OpenAgandaSlugName = $ec3->OpenAgandaSlugName;
      }

      if(isset($_POST['OpenAgandaKey'])){
        $newOpenAgandaKey = $_POST['OpenAgandaKey'];
        $ec3->set_OpenAgandaKey( $newOpenAgandaKey );
        $OpenAgandaKey = $ec3->OpenAgandaKey;
      }

      if(isset($_POST['OpenAgandaSecretKey'])){
        $newOpenAgandaSecretKey = $_POST['OpenAgandaSecretKey'];
        $ec3->set_OpenAgandaSecretKey( $newOpenAgandaSecretKey );
        $OpenAgandaSecretKey = $ec3->OpenAgandaSecretKey;
      }

      foreach($_POST as $k => $v)
      {
        if( preg_match('/^nom_option_new_([0-9]+)$/',$k,$match) )
        {
          $sid=intval($match[1]);
          $nom_option = $_POST['nom_option_new_'.$sid];
          $message_option = $_POST['message_option_new_'.$sid];

          if ( !empty($nom_option) && !empty($message_option) ) {
            $wpdb->insert( $table_opt, array( 'nom' => $nom_option, 'message' => $message_option ), array( '%s', '%s' ) );
          }
          else{

          }

        }
        if( preg_match('/^nom_option_modif_([0-9]+)$/',$k,$match) )
        {
          $sid=intval($match[1]);
          $nom_option = $_POST['nom_option_modif_'.$sid];
          $message_option = $_POST['message_option_modif_'.$sid];

          if ( !empty($nom_option) && !empty($message_option) ) {
            $wpdb->update( $table_opt, array( 'nom' => $nom_option, 'message' => $message_option ), array( 'option_id' => $sid ), array( '%s', '%s' ) );
          }
        }
        if( preg_match('/^nom_option_delete_([0-9]+)$/',$k,$match) )
        {
          $sid=intval($match[1]);
          $wpdb->delete( $table_opt, array( 'option_id' => $sid ) );
        }
      }

      _e('Options saved.');
      echo '</strong></p></div>';
    }
    ?>

   <div class="wrap">
    <form method="post">
     <h2><?php _e('Event Calendar Options','ec3'); ?></h2>

     <?php if(isset($_GET['ec3_easteregg'])): ?>

     <h3><?php _e('Easter Egg','ec3') ?>:
       <input type="submit" name="ec3_upgrade_posts"
        value="<?php _e('Upgrade Event Posts','ec3') ?>" /></h3>

     <?php endif ?>

     <table class="form-table"> 

      <tr valign="top"> 
       <th width="33%" scope="row"><?php _e('Event category','ec3'); ?>:</th> 
       <td>
        <select name="ec3_event_category">
        <?php
          if(0==$ec3->event_category)
              echo '<option value="0">'.__('- Select -').'</option>';
          wp_dropdown_cats( 0, $ec3->event_category );
         ?>
        </select>
        <br /><em>
         <?php _e("Event posts are put into this category for you. Don't make this your default post category.",'ec3'); ?>
        </em>
       </td> 
      </tr> 

       <tr valign="top"> 
        <th width="33%" scope="row"><?php _e('Show times within post content','ec3'); ?>:</th> 
        <td>
         <select name="ec3_show_event_box">          
          <option value='0'<?php if($ec3->show_event_box==0) echo " selected='selected'" ?> >
           <?php _e('Hide Times','ec3'); ?>
          </option>
          <option value='1'<?php if($ec3->show_event_box==1) echo " selected='selected'" ?> >
           <?php _e('List Times','ec3'); ?>
          </option>
          <option value='2'<?php if($ec3->show_event_box==2) echo " selected='selected'" ?> >
           <?php _e('Show Times as Icons','ec3'); ?>
          </option>
         </select>
        </td> 
       </tr>

      <tr valign="top">
       <th width="33%" scope="row"><?php _e('Show events as blog entries','ec3'); ?>:</th> 
       <td>
        <select name="ec3_advanced">
         <option value='0'<?php if(!$ec3->advanced_setting) echo " selected='selected'" ?> >
          <?php _e('Events are Normal Posts','ec3'); ?>
         </option>
         <option value='1'<?php if($ec3->advanced_setting) echo " selected='selected'" ?> >
          <?php _e('Keep Events Separate','ec3'); ?>
         </option>
        </select>
        <br /><em>
         <?php _e('Keep Events Separate: the Event Category page shows future events, in date order. Events do not appear on front page.','ec3'); ?>
        </em>
       </td> 
      </tr>

      <tr valign="top">
      <?php //if($ec3->tz_disabled): ?>
      <!-- <th style="color:gray" width="33%" scope="row"><?php // _e('Timezone','ec3'); ?>:</th> 
       <td>
         <input disabled="disabled" type="text" value="<?php
          // if(empty($ec3->tz))
          //     _e('unknown','ec3');
          // else
          //     echo $ec3->tz; ?>" />
         <br /><em>
          <?php// _e("You cannot change your timezone. Turn off PHP's 'safe mode' or upgrade to PHP5.",'ec3'); ?>
         </em>
       </td> -->
      <?php //else: ?>
       <th width="33%" scope="row"><?php _e('Timezone','ec3'); ?>:</th> 
       <td>
         <select name="ec3_tz">
          <option value="wordpress">WordPress</option>
          <?php ec3_get_tz_options($ec3->tz); ?>
         </select>
       </td> 
      <?php //endif; ?>
      </tr>

      <?php 
        
        $liste_option = $wpdb->get_results("SELECT * FROM $table_opt");
      ?>
      
      <tr valign="top" id="block_liste_option">
        <th width="33%" scope="row"><?php _e('Créer des options sur l\'etat des evenement','ec3'); ?>:</th> 
        <?php if(count($liste_option) > 0): ?>
          <?php foreach ($liste_option as $value) { ?>
            <td class="row_option">
                
                <label for="nom_option_modif_<?php echo $value->option_id; ?>">Nom :</label>
                <input type="text" name="nom_option_modif_<?php echo $value->option_id; ?>" id="nom_option_modif_<?php echo $value->option_id; ?>" value="<?php echo $value->nom; ?>" >
              
                <label for="message_option_modif_<?php echo $value->option_id; ?>" >Message :</label>
                <textarea name="message_option_modif_<?php echo $value->option_id; ?>" id="message_option_modif_<?php echo $value->option_id; ?>" cols="40" rows="1" ><?php echo $value->message; ?></textarea>
                <button class="del" >-</button>
            </td>
         <?php } ?>
        <?php endif; ?>
            <td class="row_option" id="new_block">
              <button >+</button>
            </td>
        </tr>
        <tr valign="top">
          <th width="33%" scope="row"><?php _e('The slug name of your Agenda','ec3'); ?>:</th>
          <td class="row_option">
            <input type="text" name="OpenAgandaSlugName" value="<?php echo $OpenAgandaSlugName; ?>" size="40">
            <p>[agenda-slug]: slug of the agenda, found in the url of agenda pages</p>
          </td>
        </tr>
        <tr valign="top">
          <th width="33%" scope="row"><?php _e('Your Key of Open Agenda','ec3'); ?>:</th>
          <td class="row_option">
            <input type="text" name="OpenAgandaKey" value="<?php echo $OpenAgandaKey; ?>" size="40">
          </td>
        </tr>
        <tr valign="top">
          <th width="33%" scope="row"><?php _e('Your Secret Key of Open Agenda','ec3'); ?>:</th>
          <td class="row_option">
            <input type="text" name="OpenAgandaSecretKey" value="<?php echo $OpenAgandaSecretKey; ?>" size="40">
          </td>
        </tr>
        <tr valign="top">
          <th width="33%" scope="row"><?php _e('Synchroniser avec Open Agenda','ec3'); ?>:</th>
          <td class="row_option">
            <button id="syncNow">Synchroniser Maintenant</button>
            <div id="reponseA"></div>
          </td>
        </tr>
     </table>

    <script>
      jQuery(document).ready(function($){
          var nbr = 0;
          //var new_block = $('.new_block_option').html();

          $('#new_block').click(function(e){
              e.preventDefault();
              nbr++;
              $(' <td class="row_option"><label for="nom_option_new_'+nbr+'">Nom : </label><input type="text" name="nom_option_new_'+nbr+'" id="nom_option_new_'+nbr+'" ><label for="message_option_new_'+nbr+'" > Message : </label><textarea name="message_option_new_'+nbr+'" id="message_option_new_'+nbr+'" cols="40" rows="1"></textarea><button class="del" > -</button></td>').insertBefore('#new_block');
              
              $('button.del').click(function(event){
                event.preventDefault();
                $(this).parent().css({"display":"none"});

                var text_temp = $(this).parent().children('input').attr('name') ;
                text_temp = text_temp.replace( /modif|new/gi ,"delete");
                $(this).parent().children('input').attr('name', text_temp) ;
              }); 
          });

          $('button.del').click(function(event){
              event.preventDefault();
              $(this).parent().css({"display":"none"});

              var text_temp = $(this).parent().children('input').attr('name') ;
              text_temp = text_temp.replace( /modif|new/gi ,"delete");
              $(this).parent().children('input').attr('name', text_temp) ;
          }); 
      });
    </script>

     <p class="submit"><input type="submit" name="info_update"
        value="<?php _e('Save Changes') ?>" /></p>
    </form>

   </div> <?php
  } // end function options_subpanel()

}; // end class ec3_Admin


$ec3_admin=new ec3_Admin();

function ec3_options_subpanel()
{
  global $ec3_admin;

  // Upgrade
  if(isset($_POST['ec3_cancel_upgrade']))
    update_option('ec3_upgrade_posts',0);

  $ec3_admin->upgrade_database(); // May set option ec3_force_upgrade

  if( intval(get_option('ec3_upgrade_posts')) ||
      isset($_POST['ec3_upgrade_posts']) )
  {
    require_once(dirname(__FILE__).'/upgrade-posts.php');
    ec3_upgrade_posts();
    return;
  }
  
  // Normal options page...
  $ec3_admin->options_subpanel();
}

function ec3_event_editor_box()
{
  global $ec3_admin;
  $ec3_admin->event_editor_box();
}


//
// Hook in...
if($ec3->event_category)
{
  add_filter('admin_head', array(&$ec3_admin,'filter_admin_head'));
  add_action('save_post',  array(&$ec3_admin,'action_save_post'));
  add_action('delete_post',array(&$ec3_admin,'action_delete_post'));
}

// Always hook into the admin_menu - it's required to allow users to
// set things up.
add_action('admin_menu', array(&$ec3_admin,'action_admin_menu'));

