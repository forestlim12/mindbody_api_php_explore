<?php
// Author: Jack Lim
// Date: 2019-09-19


// mindbody credentials
include 'mb_connect.php';

class DanceClass
{
  const URL_PREFIX = 'https://clients.mindbodyonline.com/classic/ws?studioid='.MB_SITE_ID.'&classid=';

  var $id,
      $name,
      $dateTime,
      $day,
      $start_time,
      $end_time,
      $instructor,
      $description;

  public function __construct() {}
  public function __destruct()  {}

  public function parse($class_data)
  {
    $this->id          = $class_data['ClassScheduleId'];
    $this->class_name  = $class_data['ClassDescription']['Name'];
    $this->dateTime    = $class_data['StartDateTime'];
    $this->day         = date('D', strtotime($this->dateTime));
    $this->start_time  = substr($this->dateTime, 11, 5);
    $this->end_time    = substr($class_data['EndDateTime'], 11, 5);
    $this->description = $class_data['ClassDescription']['Description'];
    $this->instructor  = $class_data['Staff']['FirstName'] . ' ' .
                         $class_data['Staff']['LastName'];
  }

  public function toString()
  {
    return $this->id    . ' | ' . 
      $this->day        . ' '   .
      $this->result     . ' '   .
      $this->start_time . '-'   .
      $this->end_time   . ' '   .
      $this->class_name . ' by '.
      $this->instructor . ' '   .
      '<br />';
  }

  public function toLinks()
  {
    return '<a href="'.self::URL_PREFIX.$this->id.'">'.
      $this->day        . ' '   .
      $this->start_time . '-'   .
      $this->end_time   . ' '   .
      $this->class_name . ' by '.
      $this->instructor . ' '   .
      '</a><br />';
  }

  public function toRow()
  {
    return '<tr>'.
      '<td><a href="'.self::URL_PREFIX.$this->id.'">Sign Up</a></td>'.
      '<td>'.$this->day.'</td>'.
      '<td>'.$this->start_time.'-'.$this->end_time.'</td>'.
      '<td>'.$this->class_name.'</td>'.
      '<td>'.$this->instructor.'</td>'.
      "</tr>\r\n";
  }
}


class MindbodySchedule
{
  // member variables
  var $schedule;

  public function __construct() { $this->schedule = []; }
  public function __destruct() {}

  function get_data($range)
  {
    if (isset(MB_API_KEY))
    {
      $curl = curl_init();

      curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://api.mindbodyonline.com/public/v6/class/classes?'.
          'StartDateTime='.$range->start_date.'&EndDateTime='.$range->end_date.
          '&HideCanceledClasses=true',
        CURLOPT_RETURNTRANSFER =>  true,
        CURLOPT_ENCODING       =>    "",
        CURLOPT_MAXREDIRS      =>    10,
        CURLOPT_TIMEOUT        =>     0,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST  => "GET",
        CURLOPT_HTTPHEADER     => array(
          "Content-Type: application/json",
          "API-Key: ".MB_API_KEY,
          "SiteId: ".MB_SITE_ID
        ),
      ));

      $response = curl_exec($curl);
      $err      = curl_error($curl);

      curl_close($curl);

      if ($err) 
      {
        echo "cURL Error #:" . $err;
      } else 
      {
        $this->get_classes($response);
      }
    }
    else
    {
      echo '<p>Please set the API_KEY</p>'."\r\n";
    }
  }

  function get_classes($response)
  {
    $data        = json_decode($response, true);
    $classes     = $data["Classes"];
    $num_classes = count($classes);

    /*
    for($x = 0; $x < $num_classes; $x++)
    {
      $class = new DanceClass();
      $class->parse($classes[$x]);
      array_push($this->schedule, $class);
    }
    */
    foreach ($classes as $class) {
      $dance_class = new DanceClass();
      $dance_class->parse($class);
      $this->schedule[] = $dance_class;
    }
      
    usort($this->schedule, function ($a, $b)
    {
      if ($a->dateTime == $b->dateTime) {
        return 0;
      }

      return ($a->dateTime < $b->dateTime) ? -1 : 1;
    });
  }

  function toString()
  {
    $num_classes = count($this->schedule);

    for($x = 0; $x < $num_classes; $x++)
    {
      echo $this->schedule[$x]->toString();
    }
  }

  function toLinks()
  {
    $num_classes = count($this->schedule);

    for($x = 0; $x < $num_classes; $x++)
    {
      echo $this->schedule[$x]->toLinks();
    }
  }

  function toTable()
  {
    $num_classes = count($this->schedule);

    if (0 < $num_classes)
    {
      echo '<table style="border: 1px solid black;">'."\r\n".
           "  <tr><th>Register</th><th>Day</th><th>Time</th><th>Class</th><th>Instructor</th></tr>\r\n";

      for($x = 0; $x < $num_classes; $x++)
      {
        echo $this->schedule[$x]->toRow();
      }

      echo "</table>\r\n";
    }
  }
  
  function toICal() {
    $header_parameters = array(
      'BEGIN'                           =>  'VCALENDAR',
      'VERSION'                         =>	'2.0',
      'PRODID'				                  =>	'-//Jack/Mindbody Schedule//NONSGML v1.0//EN',
      'URL'						                  =>	'TBD',
      'NAME'					                  =>	'Dance with Joy Class Schedule',
      'X-WR-CALNAME'	                  =>	'Dance with Joy Class Schedule',
      'DESCRIPTION'		                  =>	'Class schedule for upcoming dance courses',
      'X-WR-CALDESC'	                  =>	'Class schedule for upcoming dance courses',
      'REFRESH-INTERVAL;VALUE=DURATION'	=>	'PT12H',
      'X-PUBLISHED-TTL'							    =>	'PT12H',
    );
    $header = implode("\r\n", $header_parameters);
    foreach ($this->schedule as $event)
    {
      /*
      $this->id          = $class_data['ClassScheduleId'];
      $this->class_name  = $class_data['ClassDescription']['Name'];
      $this->dateTime    = $class_data['StartDateTime'];
      $this->day         = date('D', strtotime($this->dateTime));
      $this->start_time  = substr($this->dateTime, 11, 5);
      $this->end_time    = substr($class_data['EndDateTime'], 11, 5);
      $this->description = $class_data['ClassDescription']['Description'];
      $this->instructor  = $class_data['Staff']['FirstName'] . ' ' .
                           $class_data['Staff']['LastName'];
      */
      $calendar .= "BEGIN:VEVENT\r\n".
			"UID:".$event->id."\r\n".
			"DTSTAMP:" . gmdate('Ymd\THis\Z', time()) . "\r\n" .
			"DTSTART;VALUE=DATE:" . gmdate('Ymd', strtotime($event->dateTime . ' ' . $event->start_time)) . "\r\n" .
			"DTEND;VALUE=DATE:" . gmdate('Ymd', strtotime($event->dateTime . ' ' . $event->end_time)) . "\r\n" .
			"SUMMARY:" . $event->class_name . ", " . $event->description . ", " . $event->instructor . "\r\n".
		  "END:VEVENT\r\n";
    }
    $footer = "END:VCALENDAR";
    header('Content-type: text/calendar; charset=utf-8');
	  header('Content-Disposition: inline; filename=dwj-class-schedule.ics');
	  echo $header . $calendar . $footer;
  }
}


class DateRange
{
  var $start_date, $end_date;

  public function __construct() {}
  public function __destruct()  {}

  public function set_to_one_week()
  {
    date_default_timezone_set('America/Los_Angeles');

    $this->start_date = date('Y/m/d');
    $this->end_date   = date('Y/m/d', strtotime('+6 days'));
  } 
}

$range = new DateRange;
$range->set_to_one_week();

$schedule = new MindbodySchedule;
$schedule->get_data($range);

// $schedule->toString();
// $schedule->toLink();
$schedule->toTable();

?>
