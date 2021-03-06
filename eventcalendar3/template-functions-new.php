<?php

function ec3_get_infos()                                                                                                         
 {
   global $ec3;
   return stripslashes($ec3->event->infos);
}


// latest

/** Returns TRUE if the current post is an event. */
function ec3_is_event()
{
  global $post;
  return( !empty($post->ec3_schedule) );
}

/** Returns TRUE if $query is an event category query. */
function ec3_is_event_category_q($query)
{
  global $ec3;
  // This bit nabbed from is_category()
  if($query->is_category)
  {
    $cat_obj = $query->get_queried_object();
    if($cat_obj->term_id == $ec3->event_category)
      return true;
  }
  return false;
}

/** Returns TRUE if $ec3->query is an event category query. */
function ec3_is_event_category()
{
  global $ec3;
  return ec3_is_event_category_q($ec3->query);
}

/** Determines the type of listing for $query - P(OST),E(VENT),A(LL),D(ISABLE).
 *  When $query->ec3_listing the result is A or E, depending upon the query. */
function ec3_get_listing_q($query)
{
  global $ec3;
  if(empty($query->ec3_listing))
  {
    if($ec3->advanced && ec3_is_event_category_q($query))
      return 'E';
    else
      return 'A';
  }
  return substr($query->ec3_listing,0,1);
}

/** Determines the type of listing for $ec3->query - P(OST),E(VENT),A(LL).
 *  When $query->ec3_listing the result is A or E, depending upon the query. */
function ec3_get_listing()
{
  global $ec3;
  return ec3_get_listing_q($ec3->query);
}

/** Comparison function for events' start times.
 *  Example: Sort the events in a post by start time.
 *
 *    usort( $post, 'ec3_cmp_events' );
 *
 * (Note. This isn't a practical example, because posts' events are already
 *  sorted by start time.)
 */
function ec3_cmp_events($e0,$e1)
{
  if( $e0->start < $e1->start ) return -1;
  if( $e0->start > $e1->start ) return 1;
  return 0;
}

/** Fetch the first sensible 'current' event. Use this function if you want
 *  to look at the start time. */
function ec3_sensible_start_event()
{
  global $ec3, $post;
  if(!empty($ec3->event))
    return $ec3->event;
  elseif(isset($post->ec3_schedule) && count($post->ec3_schedule)>0)
    return $post->ec3_schedule[0];
  else
    return false;
}

/** Fetch the last sensible 'current' event. Use this function if you want
 *  to look at the end time. */
function ec3_sensible_end_event()
{
  global $ec3, $post;
  if(!empty($ec3->event))
    return $ec3->event;
  elseif(isset($post->ec3_schedule) && count($post->ec3_schedule)>0)
    return $post->ec3_schedule[ count($post->ec3_schedule) - 1 ];
  else
    return false;
}

/** Get the sched_id of the current event. */
function ec3_get_sched_id()
{
  $event = ec3_sensible_start_event();
  if(empty($event))
    return '';
  else
    return $event->sched_id;
}

/** Return TRUE if the current event is in the past. */
function ec3_is_past()
{ global $ec3;
  $event = ec3_sensible_end_event();
  if(empty($event))
    return false;
  else
    return( $event->end < $ec3->today );
}

/** Get a human-readable 'time since' the current event. */
function ec3_get_since()
{
  // To use %SINCE%, you need Dunstan's 'Time Since' plugin.
  if(function_exists('time_since'))
  {
    $event = ec3_sensible_start_event();
    if(!empty($event))
      return time_since( time(), ec3_to_time($event->start) );
  }
  return '';
}

/** Get the start time of the current event. */
function ec3_get_start_time($d='')
{
  $event = ec3_sensible_start_event();
  if(empty($event))
    return '';
  elseif($event->allday)
    return __('all day','ec3');
  $d = empty($d)? get_option('time_format'): $d;
  return mysql2date($d,$event->start);
}

/** Get the end time of the current event. */
function ec3_get_end_time($d='')
{
  $event = ec3_sensible_end_event();
  if(empty($event) || $event->allday)
    return '';
  $d = empty($d)? get_option('time_format'): $d;
  return mysql2date($d,$event->end);

}

/** Get the start month of the current event. */
function ec3_get_start_month($d='F Y')
{
  $event = ec3_sensible_start_event();
  if(empty($event))
    return '';
  return mysql2date($d,$event->start);
}

/** Get the end month of the current event. */
function ec3_get_end_month($d='F Y')
{
  $event = ec3_sensible_end_event();
  if(empty($event))
    return '';
  return mysql2date($d,$event->end);
}

/** Get the start date of the current event. */
function ec3_get_start_date($d='')
{
  $event = ec3_sensible_start_event();
  if(empty($event))
    return '';
  $d = empty($d)? get_option('date_format'): $d;
  return mysql2date($d,$event->start);
}

/** Get the end date of the current event. */
function ec3_get_end_date($d='')
{
  $event = ec3_sensible_end_event();
  if(empty($event))
    return '';
  $d = empty($d)? get_option('date_format'): $d;
  return mysql2date($d,$event->end);
}

function ec3_get_time($d='')  { return ec3_get_start_time( $d); }
function ec3_get_month($d='') { return ec3_get_start_month($d); }
function ec3_get_date($d='')  { return ec3_get_start_date( $d); }


/** Get the current version of the EC3 plug-in. */
function ec3_get_version()
{
  global $ec3;
  return $ec3->version;
}

/** Initialise an event-loop, just for the events in the current $post.
 *  Example:
 *
 *    // First a normal loop over the current query's posts.
 *    while(have_posts())
 *    {
 *      the_post();
 *      // Now a nested loop, over the events in each post.
 *      for($evt=ec3_iter_post_events(); $evt->valid(); $evt->next())
 *      {
 *        ...
 *      }
 *    }
 */
function ec3_iter_post_events($id=0)
{
  global $ec3;
  $post = get_post($id);
  unset($ec3->events);
  if(!isset($post->ec3_schedule) || empty($post->ec3_schedule))
  {
    $ec3->events       = false;
  }
  else
  {
    $ec3->events       = $post->ec3_schedule;
  }
  return new ec3_EventIterator();
}


/** Initialise an event-loop, for ALL events in all posts in a query.
 *  You must explicitly state which query is to be used. If you just want to use
 *  the current query, then use the variant form: ec3_iter_all_events(). */
function ec3_iter_all_events_q($query)
{
  global $ec3, $post;
  unset($ec3->events);
  $ec3->events = array();
  $listing = ec3_get_listing_q($query);

  if($query->is_page || $query->is_single || $query->is_admin || $listing=='D'){

      // Emit all events.
      while($query->have_posts())
      {
        $query->the_post();
        if(!isset($post->ec3_schedule))
          continue;
        foreach($post->ec3_schedule as $s)
          $ec3->events[] = $s;
      }
  }
  elseif($listing=='P'){ // posts-only

      ; // Leave the $ec3->events array empty - list no events.
  }
  elseif($query->is_date && !$query->is_time){

      // Only emit events that occur on the given day (or month or year).
      // There two alternate ways to specify a date, the 'm' parameter...
      if($query->query_vars['m'])
      {
        if(strlen($query->query_vars['m'])>=8)
        {
          $m=substr($query->query_vars['m'],0,8);
          $fmt='Ymd';
        }
        elseif(strlen($query->query_vars['m'])>=6)
        {
          $m=substr($query->query_vars['m'],0,6);
          $fmt='Ym';
        }
        else
        {
          $m=substr($query->query_vars['m'],0,4);
          $fmt='Y';
        }
      }
      else // ...or the 'year', 'monthnum' and 'day' parameters...
      {
        $m=date('Ymd'); // Start with today.
        $fmt='Ymd';
        if($query->query_vars['year'])
        {
          $m=''.zeroise($query->query_vars['year'],4).substr($m,4,2);
          $fmt='Y';
        }
        if($query->query_vars['monthnum'])
        {
          $m=substr($m,0,4).zeroise($query->query_vars['monthnum'],2);
          $fmt='Ym';
        }
        if($query->query_vars['day'])
        {
          $m=substr($m,0,6).zeroise($query->query_vars['day'],2);
          $fmt='Ymd';
        }
      }

      while($query->have_posts())
      {
        $query->the_post();
        if(!isset($post->ec3_schedule))
          continue;
        foreach($post->ec3_schedule as $s)
          if(mysql2date($fmt,$s->end) >= $m && mysql2date($fmt,$s->start) <= $m)
            $ec3->events[] = $s;
      }
  }
  elseif($ec3->is_date_range){

      // The query is date-limited, so only emit events that occur
      // within the date range.
      while($query->have_posts())
      {
        $query->the_post();
        if(!isset($post->ec3_schedule))
          continue;
        foreach($post->ec3_schedule as $s)
          if( ( empty($ec3->range_from) ||
                  mysql2date('Y-m-d',$s->end) >= $ec3->range_from ) &&
              ( empty($ec3->range_before) ||
                  mysql2date('Y-m-d',$s->start) <= $ec3->range_before ) )
          {
            $ec3->events[] = $s;
          }
      }
  }
  elseif($ec3->advanced &&( $listing=='E' || $query->is_search )){

      // Hide inactive events
      while($query->have_posts())
      {
        $query->the_post();
        if(!isset($post->ec3_schedule))
          continue;
        foreach($post->ec3_schedule as $s)
          if( $s->end >= $ec3->today )
            $ec3->events[] = $s;
      }
  }
  else{

      // Emit all events (same as the first branch).
      while($query->have_posts())
      {
        $query->the_post();
        if(!isset($post->ec3_schedule))
          continue;
        foreach($post->ec3_schedule as $s)
          $ec3->events[] = $s;
      }
  }
  
  usort($ec3->events,'ec3_cmp_events');
  // This is a bit of a hack - only detect 'order=ASC' query var.
  // Really need our own switch.
  if(strtoupper($query->query_vars['order'])=='ASC')
    $ec3->events=array_reverse($ec3->events);
  return new ec3_EventIterator();
}


/** Initialise an event-loop, for ALL events in all posts in the current query.
 *  Example:
 *
 *    if(have_posts())
 *    {
 *      for($evt=ec3_iter_all_events(); $evt->valid(); $evt->next())
 *      {
 *        ...
 *      }
 *    }
 */
function ec3_iter_all_events()
{
  global $wp_query;
  return ec3_iter_all_events_q($wp_query);
}


/** Resets the global $post status from $wp_query. Allows us to continue
 *  with the main loop, after a nested loop. */
function ec3_reset_wp_query()
{
  global $wp_query,$post;
  if($wp_query->in_the_loop)
  {
    $wp_query->post = $wp_query->posts[$wp_query->current_post];
    $post = $wp_query->post;
    setup_postdata($post);
  }
}


/** Iterator class implements loops over events. Generated by
 *  ec3_iter_post_events() or ec3_iter_all_events().
 *  These iterators are not independent - don't try to get smart with nested
 *  loops!
 *  This class is ready to implement PHP5's Iterator interface.
 */
class ec3_EventIterator
{
  var $_idx   =0;
  var $_begin =0;
  var $_limit =0;

  /** Parameters are andices into the $ec3->events array.
   *  'begin' points to the first event.
   *  'limit' is one higher than the last event. */
  function ec3_EventIterator($begin=0, $limit=-1)
  {
    global $ec3;
    $this->_begin = $begin;
    if(empty($ec3->events))
      $this->_limit = 0;
    elseif($limit<0)
      $this->_limit = count($ec3->events);
    else
      $this->_limit = $limit;
    $this->rewind();
  }

  /** Resets this iterator to the beginning. */
  function rewind()
  {
    $this->_idx = $this->_begin - 1;
    $this->next();
  }

  /** Move along to the next (possibly empty) event. */
  function next()
  {
    $this->_idx++;
    $this->current();
  }
  
  /** Returns TRUE if this iterator points to an event. */
  function valid()
  {
    if( $this->_idx < $this->_limit )
      return TRUE;
    ec3_reset_wp_query();
    return FALSE;
  }

  /** Set the global $ec3->event to match this iterator's index. */
  function current()
  {
    global $ec3,$id,$post;
    if( $this->_idx < $this->_limit )
    {
      $ec3->event = $ec3->events[$this->_idx];
      if($post->ID != $ec3->event->post_id || $id != $ec3->event->post_id)
      {
        $post = get_post($ec3->event->post_id);
        setup_postdata($post);
      }
    }
    else
    {
      unset($ec3->event); // Need to break the reference.
      $ec3->event = false;
    }
  }
  
  function key()
  {
    return $this->_idx;
  }
}; // limit class ec3_EventIterator


/** Template function, for backwards compatibility.
 *  Call this from your template to insert a list of forthcoming events.
 *  Available template variables are:
 *   - template_day: %DATE% %SINCE% (only with Time Since plugin)
 *   - template_event: %DATE% %TIME% %LINK% %TITLE% %AUTHOR%
 */
function ec3_get_events(
  $limit,
  $template_event=EC3_DEFAULT_TEMPLATE_EVENT,
  $template_day  =EC3_DEFAULT_TEMPLATE_DAY,
  $date_format   =EC3_DEFAULT_DATE_FORMAT,
  $template_month=EC3_DEFAULT_TEMPLATE_MONTH,
  $month_format  =EC3_DEFAULT_MONTH_FORMAT)
{
  if(!ec3_check_installed(__('Upcoming Events','ec3')))
    return;

  // Parse $limit:
  //  NUMBER      - limits number of posts
  //  NUMBER days - next NUMBER of days
  $query = new WP_Query();
  if(preg_match('/^ *([0-9]+) *d(ays?)?/',$limit,$matches))
      $query->query( 'ec3_listing=event&ec3_days='.intval($matches[1]) );
  elseif(intval($limit)>0)
      $query->query( 'ec3_after=today&posts_per_page='.intval($limit) );
  elseif(intval($limit)<0)
      $query->query( 'ec3_before=today&order=asc&posts_per_page='.abs(intval($limit)) );
  else
      $query->query( 'ec3_after=today&posts_per_page=5' );

  echo "<ul class='ec3_events'>";
  echo '<!-- Generated by Event-Calendar v'.ec3_get_version().' -->'."\n";

  if($query->have_posts())
  {
    $current_month=false;
    $current_date=false;
    $data=array();
    for($evt=ec3_iter_all_events_q($query); $evt->valid(); $evt->next())
    {
      $data['SINCE']=ec3_get_since();

      // Month changed?
      $data['MONTH']=ec3_get_month($month_format);
      if((!$current_month || $current_month!=$data['MONTH']) && $template_month)
      {
        if($current_date)
            echo "</ul></li>\n";
        if($current_month)
            echo "</ul></li>\n";
        echo "<li class='ec3_list ec3_list_month'>"
        .    ec3_format_str($template_month,$data)."\n<ul>\n";
        $current_month=$data['MONTH'];
        $current_date=false;
      }

      // Date changed?
      $data['DATE'] =ec3_get_date($date_format);
      if((!$current_date || $current_date!=$data['DATE']) && $template_day)
      {
        if($current_date)
            echo "</ul></li>\n";
        echo "<li class='ec3_list ec3_list_day'>"
        .    ec3_format_str($template_day,$data)."\n<ul>\n";
        $current_date=$data['DATE'];
      }

      $data['TIME']  = ec3_get_start_time();
      $data['TITLE'] =get_the_title();
      $data['LINK']  =get_permalink();
      $data['AUTHOR']=get_the_author();
      echo " <li>".ec3_format_str($template_event,$data)."</li>\n";
    }
    if($current_date)
        echo "</ul></li>\n";
    if($current_month)
        echo "</ul></li>\n";
  }
  else
  {
    echo "<li>".__('No events.','ec3')."</li>\n";
  }
  echo "</ul>\n";

}


/** Formats the schedule for the current post.
 *  Returns the HTML fragment as a string. */
function ec3_get_schedule(
  $format_single =EC3_DEFAULT_FORMAT_SINGLE,
  $format_range  =EC3_DEFAULT_FORMAT_RANGE,
  $format_wrapper=EC3_DEFAULT_FORMAT_WRAPPER
)
{
  if(!ec3_is_event())
    return '';

  global $ec3;
  $result='';
  $date_format=get_option('date_format');
  $time_format=get_option('time_format');
  $current=false;
  for($evt=ec3_iter_post_events(); $evt->valid(); $evt->next())
  {
    $date_start=ec3_get_start_date();
    $date_end  =ec3_get_end_date();
    $time_start=ec3_get_start_time();
    $time_end  =ec3_get_end_time();
    $infos = ec3_get_infos();
    if($ec3->event->active)
      $active ='';
    else
      $active ='ec3_past';

    if($ec3->event->allday)
    {
      if($date_start!=$date_end)
      {
        $result.=
          sprintf($format_range,$date_start,$date_end,__(' &rarr; ','ec3'),$active,$infos);
      }
      elseif($date_start!=$current)
      {
        $current=$date_start;
        $result.=sprintf($format_single,$date_start,$active);
      }
    }
    else if($date_start!=$date_end)
    {
      $current=$date_start;
      $result.=sprintf(
          $format_range,
          "$date_start $time_start",
          "$date_end $time_end",
          __(' &rarr; ','ec3'),
          $active,
	  $infos
        );
    }
    else
    {
      if($date_start!=$current)
      {
        $current=$date_start;
        $result.=sprintf($format_single,$date_start,$active,$infos);
      }
      if($time_start==$time_end)
        $result.=sprintf($format_single,$time_start,$active,$infos);
      else
        $result.=
          sprintf($format_range,$time_start,$time_end,__('à','ec3'),$active,$infos);
    }
  }
  return sprintf($format_wrapper,$result);
}


/** Formats the schedule for the current post as one or more 'iconlets'.
 *  Returns the HTML fragment as a string. */
function ec3_get_iconlets()
{
  if(!ec3_is_event())
    return '';

  global $ec3;
  $result='';

  $current=false;
  $this_year=date('Y');
  for($evt=ec3_iter_post_events(); $evt->valid(); $evt->next())
  {
    $year_start =ec3_get_start_date('Y');
    $month_start=ec3_get_start_date('M');
    $day_start  =ec3_get_start_date('j');
    // Don't bother about intra-day details. Empeche d'afficher plusieur fois le même jour.
    /*if($current==$day_start.$month_start.$year_start)
      continue;*/
    $current=$day_start.$month_start.$year_start;
    // Grey-out past events.
    if($ec3->event->active)
      $active ='';
    else
      $active =' ec3_past';
    // Only put the year in if it isn't *this* year.
    if($year_start!=$this_year)
      $month_start.='&nbsp;&rsquo;'.substr($year_start,2);
    // OK, make the iconlet.
    //$result.='<pre></pre>';
    $result.="<div class='ec3_iconlet$active'><table><tbody>";
    if(!$ec3->event->allday)
    {
      // Event with start time.
      $time_start=ec3_get_start_time();
      
      if( substr($ec3->event->start,0,10) < substr($ec3->event->end,0,10) )
      {
        $month_end=ec3_get_end_date('M');
        $day_end  =ec3_get_end_date('j');
        $time_end  =ec3_get_end_time();
        $result.="<tr class='ec3_month'>"
             .  "<td class='ec3_multi_start'>$month_start</td>"
             .  "<td class='ec3_multi_end'>$month_end</td></tr>";
        $result.="<tr class='ec3_day'>"
               .  "<td class='ec3_multi_start'>$day_start</td>"
               .  "<td class='ec3_multi_end'>$day_end</td></tr>";
        $result.="<tr class='ec3_time'>"
                  ."<td>$time_start</td>"
                  ."<td>$time_end</td></tr>";
      }
      else{
        $result.="<tr class='ec3_month'><td>$month_start</td></tr>"
             . "<tr class='ec3_day'><td>$day_start</td></tr>"
             . "<tr class='ec3_time'><td>$time_start</td></tr>";
      }
      
    }
    elseif(substr($ec3->event->start,0,10) == substr($ec3->event->end,0,10))
    {
      // Single, all-day event.
      $result.="<tr class='ec3_month'><td>$month_start</td></tr>"
             . "<tr class='ec3_day'><td>$day_start</td></tr>";
    }
    else
    {
      // Multi-day event.
      $month_end=ec3_get_end_date('M');
      $day_end  =ec3_get_end_date('j');
      $result.="<tr class='ec3_month'>"
             .  "<td class='ec3_multi_start'>$month_start</td>"
             .  "<td class='ec3_multi_end'>$month_end</td></tr>";
      $result.="<tr class='ec3_day'>"
             .  "<td class='ec3_multi_start'>$day_start</td>"
             .  "<td class='ec3_multi_end'>$day_end</td></tr>";
    }
    //echo stripslashes($ec3->event->info_shed);
    if (!empty($ec3->event->info_shed)) {
      $bulle = stripslashes($ec3->event->info_shed);
      $result.="<tr><td class='bulle'>$bulle</td></tr></tbody></table></div>\n";
    }
    else{
      $result.="</tbody></table></div>\n";
    }
    
  }
  return apply_filters( 'ec3_filter_iconlets', $result );
}

/** Formats the schedule for the current post as one or more 'iconlets'.
 *  Returns the HTML fragment as a string. */
function ec3_get_iconlets_active()
{
  if(!ec3_is_event())
    return '';

  global $ec3;
  $result='';
  $current=false;
  $this_year=date('Y');
  for($evt=ec3_iter_post_events(); $evt->valid(); $evt->next())
  {
    $year_start =ec3_get_start_date('Y');
    $month_start=ec3_get_start_date('M');
    $day_start  =ec3_get_start_date('j');
    // Don't bother about intra-day details. Empeche d'afficher plusieur fois le même jour.
    /*if($current==$day_start.$month_start.$year_start)
      continue;*/
    $current=$day_start.$month_start.$year_start;
    // Grey-out past events.
    if( $ec3->event->active){
      // Only put the year in if it isn't *this* year.
      if($year_start!=$this_year)
        $month_start.='&nbsp;&rsquo;'.substr($year_start,2);
      // OK, make the iconlet.
      $result.="<div class='ec3_iconlet'><table><tbody>";
      if(!$ec3->event->allday)
      {
        // Event with start time.
        $time_start=ec3_get_start_time();
        
        if( substr($ec3->event->start,0,10) < substr($ec3->event->end,0,10) )
        {
          $month_end=ec3_get_end_date('M');
          $day_end  =ec3_get_end_date('j');
          $time_end  =ec3_get_end_time();
          $result.="<tr class='ec3_month'>"
               .  "<td class='ec3_multi_start'>$month_start</td>"
               .  "<td class='ec3_multi_end'>$month_end</td></tr>";
          $result.="<tr class='ec3_day'>"
                 .  "<td class='ec3_multi_start'>$day_start</td>"
                 .  "<td class='ec3_multi_end'>$day_end</td></tr>";
          $result.="<tr class='ec3_time'>"
                    ."<td>$time_start</td>"
                    ."<td>$time_end</td></tr>";
        }
        else{
          $result.="<tr class='ec3_month'><td>$month_start</td></tr>"
               . "<tr class='ec3_day'><td>$day_start</td></tr>"
               . "<tr class='ec3_time'><td>$time_start</td></tr>";
        }
        
      }
      elseif(substr($ec3->event->start,0,10) == substr($ec3->event->end,0,10))
      {
        // Single, all-day event.
        $result.="<tr class='ec3_month'><td>$month_start</td></tr>"
               . "<tr class='ec3_day'><td>$day_start</td></tr>";
      }
      else
      {
        // Multi-day event.
        $month_end=ec3_get_end_date('M');
        $day_end  =ec3_get_end_date('j');
        $result.="<tr class='ec3_month'>"
               .  "<td class='ec3_multi_start'>$month_start</td>"
               .  "<td class='ec3_multi_end'>$month_end</td></tr>";
        $result.="<tr class='ec3_day'>"
               .  "<td class='ec3_multi_start'>$day_start</td>"
               .  "<td class='ec3_multi_end'>$day_end</td></tr>";
      }
      //echo stripslashes($ec3->event->info_shed);
      if (!empty($ec3->event->info_shed)) {
        $bulle = stripslashes($ec3->event->info_shed);
        $result.="<tr><td class='bulle'>$bulle</td></tr></tbody></table></div>\n";
      }
      else{
        $result.="</tbody></table></div>\n";
      }
    }
    else{
      $result.="";
    }
  }
  return apply_filters( 'ec3_filter_iconlets_active', $result );
}


/** Template function, for backwards compatibility.
 *  Call this from your template to insert the Sidebar Event Calendar. */
function ec3_get_calendar($options = false)
{
  if(!ec3_check_installed('Event-Calendar'))
    return;
  require_once(dirname(__FILE__).'/calendar-sidebar.php');
  $calobj = new ec3_SidebarCalendar($options);
  echo $calobj->generate();
}

// added  

/** Fetch the first active sensible 'current' event. Use this function if you want
 *  to look at the active start time. */
function ec3_active_sensible_start_event()
{
  global $ec3, $post;
  if(!empty($ec3->event))
    return $ec3->event;
  elseif(isset($post->ec3_schedule) && count($post->ec3_schedule)>0){
	foreach ($post->ec3_schedule as $s => $i) {
		if ($i->active)	{
			return $post->ec3_schedule[$s];
			}
		}}
  else
    return false;
}

/** Fetch the last sensible 'current' event. Use this function if you want
 *  to look at the end time. */
function ec3_active_sensible_end_event()
{
  global $ec3, $post;
  if(!empty($ec3->event))
    return $ec3->event;
  elseif(isset($post->ec3_schedule) && count($post->ec3_schedule)>0){
	foreach ($post->ec3_schedule as $s => $i) {
		if ($i->active)	{
			return $post->ec3_schedule[ $s ];
			}
		}}
  else
    return false;
}

/** Get the start date of the current active event. */
function ec3_get_active_end_date($d='')
{
  $event = ec3_active_sensible_end_event();
  if(empty($event))
    return '';
  $d = empty($d)? get_option('date_format'): $d;
  return mysql2date($d,$event->end);
}

/** Get the start time of the current active event. */
function ec3_get_active_start_time($d='')
{ 
  $event = ec3_active_sensible_start_event();
  if(empty($event))
    return '';
  elseif($event->allday)
    return __('all day','ec3');
  $d = empty($d)? get_option('time_format'): $d;
  return mysql2date($d,$event->start);
}

/** Get the start date of the current active event. */
function ec3_get_active_start_date($d='')
{
  $event = ec3_active_sensible_start_event();
  if(empty($event))
    return '';
  $d = empty($d)? get_option('date_format'): $d;
  return mysql2date($d,$event->start);
}

/** Formats the active schedule for the current post.
 *  Returns the HTML fragment as a string. */
function ec3_get_active_schedule(
  $format_single =EC3_DEFAULT_FORMAT_SINGLE,
  $format_range  =EC3_DEFAULT_FORMAT_RANGE,
  $format_wrapper=EC3_DEFAULT_FORMAT_WRAPPER
)
{
  if(!ec3_is_event())
    return '';

  global $ec3;
  $result='';
  $current=false;
  for($evt=ec3_iter_post_events(); $evt->valid(); $evt->next())
  {
    $date_start=ec3_get_start_date('jS F');
    $date_end  =ec3_get_end_date('jS F');
    $year  =ec3_get_end_date('Y');
    if($ec3->event->active)
      $active ='';
    else
      continue;

    if($ec3->event->allday)
    {
      if($date_start!=$date_end)
      {
        $result.=
          sprintf($format_range,$date_start,$date_end,__('to','ec3'),$active);
      }
      elseif($date_start!=$current)
      {
        $current=$date_start;
        $result.=sprintf($format_single,$date_start,$active);
      }
    }
    else if($date_start!=$date_end)
    {
      $current=$date_start;
      $result.=sprintf(
          $format_range,
          "$date_start",
          "$date_end $year",
          __('to','ec3'),
          $active
        );
    }
    else
    {
      if($date_start!=$current)
      {
        $current=$date_start;
        $result.=sprintf($format_single,$date_start,$active);
      }
      if($time_start==$time_end)
        $result.=sprintf($format_single,$time_start,$active);
      else
        $result.=
          sprintf($format_range,$time_start,$time_end,__('to','ec3'),$active);
    }
  }
  return sprintf($format_wrapper,$result);
}

function ec3_display_calendar($start="",$end="",$loc="",$cat="",$query){

  global $ec3, $wpdb;
  global $wp_query;
  
  $table_lieux = $wpdb->prefix . 'ec3_lieux';
  setlocale(LC_TIME, "fr_FR");

  if (isset($_GET['cat'])) { $cat_id =  $_GET['cat']; }
  else{ $cat_id =  get_query_var('cat'); }

  $category = &get_category($cat_id);
  $cat_title = $category->name ;

  $cat_slug = $category->slug ;

  $cat_ec3 = $ec3->event_category;
  $select_cat = array();
  $select_lieux = array();

      
   if (!isset($_GET['m'])) {
        $start_date = date("Y-m-d");
        //$mois_en_cour = date("F Y");
        $year = date("Y");
        $month = date("n");
        $day = date('d');
        $date = date("Ym");
        $date_prev = date('Ym',strtotime('-1 month',strtotime($year."-".$month."-".$day)));
        $date_next = date('Ym',strtotime('+1 month',strtotime($year."-".$month."-".$day)));
        $date_en_cour = utf8_encode(strftime("%B %G",strtotime("F Y")));
        $next_month = utf8_encode(strftime("%B",strtotime('+1 month', mktime(0,0,0,$month,$day,$year) )))."&nbsp>>";
        $prev_month = "<<&nbsp;". utf8_encode(strftime("%B",strtotime('-1 month', mktime(0,0,0,$month,$day,$year) )));
      }
    else{
        $year = substr($_GET['m'], 0, 4);
        $month = substr($_GET['m'], 4, 2);
        $day = date('d');
      if(strlen($_GET['m'])>=8)
        {
          $day = substr($_GET['m'], 6, 2);
          $date = date("Ymd", strtotime($year."-".$month."-".$day));
          $date_prev = date('Ymd',strtotime('-1 days',strtotime($year."-".$month."-".$day)));
          $date_next = date('Ymd',strtotime('+1 days',strtotime($year."-".$month."-".$day)));
          $date_en_cour = utf8_encode(strftime("%A %d %B %G",strtotime($year."-".$month."-".$day)));
          $next_month = utf8_encode(strftime("%A %d",strtotime('+1 days', mktime(0,0,0,$month,$day,$year) )))."&nbsp>>";
          $prev_month = "<<&nbsp;". utf8_encode(strftime("%A %d",strtotime('-1 days', mktime(0,0,0,$month,$day,$year) )));
        }
      elseif(strlen($_GET['m'])==6)
        {
          $date = date("Ym", strtotime($year."-".$month."-".$day));
          $date_prev = date('Ym',strtotime('-1 month',strtotime($year."-".$month."-".$day)));
          $date_next = date('Ym',strtotime('+1 month',strtotime($year."-".$month."-".$day)));
          $date_en_cour = utf8_encode(strftime("%B %G",strtotime($year."-".$month."-".$day)));
          $next_month = utf8_encode(strftime("%B",strtotime('+1 month', mktime(0,0,0,$month,$day,$year) )))."&nbsp>>";
          $prev_month = "<<&nbsp;". utf8_encode(strftime("%B",strtotime('-1 month', mktime(0,0,0,$month,$day,$year) )));
        }
    }

    $url = get_site_url() .'?m='. $date .'&amp;ec3_listing=events&amp;cat='.$cat_id.'&amp;select_lieux='.$_GET['select_lieux'];
    $url_prev = get_site_url() .'?m='. $date_prev .'&amp;ec3_listing=events&amp;cat='.$cat_id.'&amp;select_lieux='.$_GET['select_lieux'];
    $url_next = get_site_url() .'?m='. $date_next .'&amp;ec3_listing=events&amp;cat='.$cat_id.'&amp;select_lieux='.$_GET['select_lieux'];



// Recuperation de toute les catégorie et id_lieux presente dans le mois.

    
$args_liste_cat = array(
          'cat' => $cat_ec3,
          'posts_per_page' => 100,
          'posts_per_archive_page' => 100,
          'm' => $_GET['m'],
          'ec3_listing' => $_GET['ec3_listing'],
          'ec3_after' => $start_date
             );
$query_cat = new WP_Query( $args_liste_cat );
  if($query_cat->have_posts()) {
    ?>
    <?php echo $query_cat->post_count." posts trouvé."; ?>
      <?php  foreach ($query_cat->posts as $value) { ?>
          <?php foreach ($value->ec3_schedule as $lieux) {
            
            if (!in_array($lieux->lieux_id, $select_lieux) && $lieux->lieux_id != "") {
              array_push($select_lieux, $lieux->lieux_id);
            }
           
          } ?>
       <?php } ?>

    <?php while ( have_posts() ) : the_post(); ?>
        <?php $categories = get_the_category( $post->ID ); ?>
        <?php foreach ($categories as $value) {
          if ($value->parent == $cat_ec3 ) {
            if (!in_array($value->slug.",".$value->cat_ID, $select_cat)) {
              array_push($select_cat, $value->slug.",".$value->cat_ID);
            }
          }
        } ?> 
    <?php endwhile; ?>
    <?php
  }
  wp_reset_query();

  $ids = join(',',$select_lieux);  
  $liste_lieux = $wpdb->get_results("SELECT * FROM $table_lieux WHERE lieux_id IN ($ids)");

        ?>
    <STYLE type="text/css">
      .row{
        line-height: 2em;
        background-color: #aaa;
        width: 100%;
        text-align: center;
      }
      .prev{
        padding: 0 2em;
      }
      .now{
        display: inline-block;
        width: 50%;
        text-align: center;
      }
    </STYLE>
    <?php $nav_bar = '<div class="row">'
        .'<span class="prev"><a href="'. $url_prev .'">'. $prev_month .'</a></span>'
        .'<span class="now"><a href="'. $url .'">'. $date_en_cour .'</a></span>'
        .'<span class="prev"><a href="'. $url_next .'">'. $next_month .'</a></span>'
        .'</div>'; ?>
    <div class="entry-content">
      <?php // Menu de navigation ?>
      <?php echo $nav_bar; ?>

      <?php // Select pour choisir une categorie dans le mois en cours. ?>
      <form action="<?php echo get_site_url(); ?>">
        <input type="hidden" name="m" value="<?php echo $date; ?>">
        <input type="hidden" name="ec3_listing" value="events">
        <select name="cat" id="select_cat"><?php
          if (empty($cat_slug)) {
            ?><option value="" selected="selected" >Choisir une catégorie</option> <?php
          }
          else{ ?><option value="" >Choisir une catégorie</option> <?php }
          foreach ($select_cat as $val_cat) {
            $value = explode(",", $val_cat);
            ?> 
              <option value="<?php echo $value['1']; ?>" 
                  <?php if ($value['0'] == $cat_slug) {
                    ?>selected="selected"<?php
                  } ?>
                >
                <?php echo $value['0']; ?>
              </option>
            <?php
          }
          ?></select>
          <?php // Select pour choisir une categorie dans le mois en cours. 
            if (isset($_GET['select_lieux'])) { $choix_lieux = $_GET['select_lieux']; }
            else { $choix_lieux = ""; }
          ?>
          <select name="select_lieux" id="select_lieux"><?php
          if (empty($choix_lieux)) {
            ?><option value="" selected="selected" >Choisir un lieu</option> <?php
          }
          else{ ?><option value="" >Choisir un lieu</option> <?php }

          foreach ($liste_lieux as $val_lieu) {
            ?> 
              <option value="<?php echo $val_lieu->lieux_id; ?>" 
                  <?php if ($choix_lieux == $val_lieu->lieux_id) {
                    ?>selected="selected"<?php
                  } ?>
                >
                <?php echo $val_lieu->nom_lieux; ?>
              </option>
            <?php
          }
          ?></select>
          <input type="submit" value="OK">
        </form>
    
    <?php

    // Recuperation des infos et affichage des posts.
    
    $args = array(
              'cat' => $cat_id,
              'posts_per_page' => 100,
              'm' => $_GET['m'],
              'ec3_listing' => $_GET['ec3_listing'],
              'ec3_after' => $start_date,
              'ec3_id_lieux' => $_GET['select_lieux']
              //'ec3_before' => $end_date
              );

    $query = new WP_Query( $args );
      if($query->have_posts()) {
        
        ?>
        <?php while ( have_posts() ) : the_post(); ?>
            <?php foreach ($query->posts as $schedule) {

              } ?>
              <?php $examplePost = get_post(); 
                foreach ($examplePost->ec3_schedule as $schedule) {
                  if ($schedule->lieux_id == $_GET['select_lieux'] || $_GET['select_lieux']=="") {
                      
                      ?><h5><a href="<?php echo get_permalink(); ?>"><?php echo get_the_title(); ?></a></h5><?php
                      
                      break;
                   } 
                }
              ?>      

        <?php endwhile; ?>
        <?php
        }
      else{
          $message_no_post = "<h3>Il n'y à pas de post pour les critères sélectionnés</h3>";
        }
      wp_reset_query(); ?>

      <?php echo $nav_bar; ?>
    </div> <?php
}

// Retourne la liste des lieux du mois en cours
function ec3_get_lieux_active_schedule(){

  global $ec3, $wpdb;
  require_once(dirname(__FILE__).'/calendar-sidebar.php');
  $month_callendar = new ec3_SidebarCalendar($options);

  $table_schedule = $wpdb->prefix . 'ec3_schedule';
  $table_lieux = $wpdb->prefix . 'ec3_lieux';
  $table_opt = $wpdb->prefix . 'ec3_add_opt';

  setlocale(LC_TIME, "fr_FR");
  
  $year  = $month_callendar->begin_dateobj->year_num;
  $month = $month_callendar->begin_dateobj->month_num;
  $month_num = zeroise($month, 2);
  $prev = $month_callendar->begin_dateobj->prev_month();
  $next = $month_callendar->begin_dateobj->next_month();
  $prev_num = zeroise($prev->month_num, 2);
  $next_num = zeroise($next->month_num, 2);

  $month = utf8_encode(strftime("%B",strtotime($year."-".$month."-01")));
  $prev_month = utf8_encode(strftime("%B",strtotime($year."-".$prev_num."-01")));
  $next_month = utf8_encode(strftime("%B",strtotime($year."-".$next_num."-01")));

  $date_select = $year."-".$month;



  // Menu de recherche par mois
  $agenda_nav = '<div class="block_nav_agenda">'
            . '<div class="two columns alpha">'
            . '<a class="button" href="'. get_site_url() .'?m='. $year.$prev_num .'&amp;ec3_listing=events" ><< '. $prev_month .'</a>'
            . '</div>'
            . '<div class="seven columns agenda-nav">'
            . '<a href="'. get_site_url() .'?m='. $year.$month_num .'&amp;ec3_listing=events">'. $month." ".$year .'</a>'
            . '</div>'
            . '<div class="two columns omega">'
            . '<a class="button" href="'. get_site_url() .'?m='. $year.$next_num .'&amp;ec3_listing=events">'. $next_month .' >></a>'
            . '</div>'
            . '</div>';

  echo  $agenda_nav;
  
  $lieu_default = $wpdb->get_results("SELECT DISTINCT lieux_id FROM $table_schedule WHERE MONTH(start) = $month_num OR MONTH(end) = $month_num ;");
  $ids_lieux = array();
  foreach ($lieu_default as $key) {
    if (!empty($key->lieux_id)) {
      array_push($ids_lieux, $key->lieux_id);
    }
    
  }
  $ids = join(',',$ids_lieux);  
  $tous_les_lieux = $wpdb->get_results("SELECT * FROM $table_lieux WHERE lieux_id IN ($ids)");

  if (isset($_GET['lieux']) ) {
    $lieu_choisi = $_GET['lieux'];
  }

  ?><select name="ec3_lieux_<?php echo $id_date; ?>" id="ec3_lieux_<?php echo $id_date; ?>"><?php
    if (!isset($_GET['lieux']) ) {
      ?><option value="" selected="selected" >Choisir un lieux</option> <?php
    }
    foreach ($tous_les_lieux as $key_lieu) {
      ?> 

      <option value="<?php echo $key_lieu->lieux_id; ?>" 
            <?php if ($lieu_choisi == $key_lieu->lieux_id) {
              ?>selected="selected"<?php
            } ?>
          >
          <?php echo $key_lieu->nom_lieux; ?>
        </option>
      <?php
    }
  ?></select>
  <input type="button" value="OK" id="lieux_choisit"> <?php

  echo  $agenda_nav;

}

// affiche un calendrier avec les event du mois
function ec3_big_cal( $postId ){

  global $ec3, $wpdb;
  $table_schedule = $wpdb->prefix . 'ec3_schedule';

  if( !isset($_GET['ec3_month']) || empty($_GET['ec3_month']) ){
    $month = date('n');
  }
  else{
    $month = $_GET['ec3_month'];
  }

  $yyyy = date( 'Y' );
  $name_month = date( 'F' , strtotime( $yyyy.'-'.$month.'-01' ) );
  $nb_jours = date( 't' , strtotime( $yyyy.'-'.$month.'-01' ) );

  $begin = $yyyy.'-'.$month.'-01';
  $endMonth = $yyyy.'-'.$month.'-31';

  $toutes_les_dates = $wpdb->get_results('SELECT start, end FROM '.$table_schedule.' WHERE post_id = '.$postId.' AND end > "'.$begin.'"  ');

  //$wpdb->show_errors(); 
  //$wpdb->print_error(); 
  $dates = array();
  foreach ($toutes_les_dates as $key => $val_date) {
    $start =  new DateTime( substr($val_date->start, 0, 10) );
    $end =  new DateTime( substr($val_date->end, 0, 10) );

    while ($start <= $end) {
      $day = $start->format('Y-m-d');
      if( !in_array($day, $dates) ){
        array_push($dates, $day);
      }
      $start->modify('+1 day');
    }
  } 
  ?>
  <div class="periode">
    
    <div class="listemois"> 
        
      <span class="prev"> < prev </span>
      <span class="nomMois"><?php echo $name_month ." ". $yyyy; ?></span>
      <span class="next"> next > </span>

    </div>
    <?php for ($i=1; $i <= 18; $i++) { ?> <!-- for 6 month -->
     
      <?php if ( $month > 12 ) {
        $month = 1;
        $yyyy++;
      } ?>
    
      <div class="mois" id="mois_<?php echo $month; ?>"> 
          <table>
            <thead>
              <tr>
                  <th>Lundi</th>
                  <th>Mardi</th>
                  <th>Mercredi</th>
                  <th>Jeudi</th>
                  <th>Vendredi</th>
                  <th>Samedi</th>
                  <th>Dimanche</th>
              </tr>
            </thead>

            <tbody>
              <tr>
                <?php
                $premier_jour_du_mois = date( 'N' , strtotime( $yyyy.'-'.$month.'-01' ) );
                $dernier_jour_du_mois = date( 'N' , strtotime( $yyyy.'-'.$month.'-'.$nb_jours ) );

                for($j = 1; $j <= $nb_jours; $j++ ) { ?>

                  <?php $jour_de_la_semaine = date( 'N' , strtotime( $yyyy.'-'.$month.'-'.$j ) ); ?>
                  <?php $the_day = date( 'Y-m-d' , strtotime( $yyyy.'-'.$month.'-'.$j ) ); ?>

                  <?php if($j == 1 && $premier_jour_du_mois != 1) { ?>
                    <td colspan="<?php echo $premier_jour_du_mois-1; ?>" class="casevide"></td>
                  <?php } ?>
                  <td
                    <?php 
                        if ( in_array($the_day, $dates) ) {
                          echo 'class="notDispo"';
                        }
                    ?>
                  > <!-- fin du td -->
                    <?php echo $j; ?>
                  </td>
                  <?php if ($jour_de_la_semaine == 7) { ?>
                    </tr><tr>
                  <?php } ?>
                <?php } ?>
                <?php if($dernier_jour_du_mois != 7) { ?>
                    <td colspan="<?php echo 7-$dernier_jour_du_mois; ?>" class="casevide"></td>
                <?php } ?>
              </tr>
            </tbody>
          </table>
          
        </div>
        <?php $month++; ?>
      <?php } ?> <!-- end for -->
  </div>
  <?php


} // end ec3_big_cal

// attention à utiliser sur des query avec uniquement des posts à date
function trieQuery($query){
  $trie = false;
  while ($trie == false) {
    $trie = true;
    foreach ($query->posts as $key => $value) {
      if (!empty($value->ec3_schedule[0]->start) && isset($query->posts[$key+1]->ec3_schedule[0]->start) && !empty($query->posts[$key+1]->ec3_schedule[0]->start)) {
        if ( $value->ec3_schedule[0]->start > $query->posts[$key+1]->ec3_schedule[0]->start ) {
          $temp = $query->posts[$key];
          $query->posts[$key] = $query->posts[$key+1];
          $query->posts[$key+1] = $temp;
          $trie = false;
        }
      }
    }
  }
  
  return $query;
}