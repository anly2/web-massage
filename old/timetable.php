<?php
include "mysql.php";

//mysql_("DELETE FROM timetable WHERE Date<CURDATE()");

if(isset($_REQUEST['new'])){
   mysql_("INSERT INTO timetable VALUES('".$_REQUEST['date']."', '".$_REQUEST['time']."', '".$_REQUEST['length']."', '".$_REQUEST['client']."')") or die(mysql_error());
   echo '<script type="text/javascript">window.location.href = "timetable.php";</script>';
   exit;
}

if(isset($_REQUEST['remove'])){
   mysql_("DELETE FROM timetable WHERE Date='".$_REQUEST['date']."' AND Time='".$_REQUEST['time']."'") or die(mysql_error());
   echo '<script type="text/javascript">window.location.href = "timetable.php";</script>';
   exit;
}


define('days_displayed', 7, true);
define('px_per_min', 0.37, true);
define('snap_to', 30, true);

$today = date('Y-m-d');


$code  = '<table name="timetable" class="timetable" rules="cols" >'."\n";
$code .= '   <tr>'."\n";

for($i=0; $i<DAYS_DISPLAYED; $i++){
   $day = date_add(date_create($today), date_interval_create_from_date_string($i.' days'));
   $wDay = date_format($day, 'Y-m-d');
   $appointments = mysql_("SELECT * FROM timetable WHERE Date='".$wDay."' ORDER BY Time", MYSQL_ASSOC|MYSQL_TABLE);

   $code .= '      <td valign="top" onclick="complete_appointment(snap( (event.clientY - findPos(ttParent(this))[1] -40) ), \''.$wDay.'\');">'."\n";

   $code .= '         <div class="header">'.str_replace("\n", "\n<br />", date_format($day, "l\nd.m.Y") ).'</div>'."\n";


   $current_offset = 0;

   if($appointments){
      foreach($appointments as $a){
         list($h, $m, $s) = explode(":", $a['Time']);

         $o  = $h*60 + $m;
         $mt = $o * PX_PER_MIN  -  $current_offset;

         $l  = (int)$a['Length'];
         $sh = $l * PX_PER_MIN;

         $current_offset += $mt + $sh;

         $code .= '         '.'<div class="cell" title="'.$a['Length'].' min session'.($a['Client']? ' with '.$a['Client'] : '').'" style="margin-top:'.round($mt).'px; height:'.(round($sh)-2).'px;" onclick="stop(event);">'.$h.":".$m.'<div class="small close button" style="float:right; margin: 0px -10px 0px 0px;" onclick="window.location.href=\'?remove&date='.$a['Date'].'&time='.$a['Time'].'\'"></div> </div>'."\n";

         unset($h, $m, $s);
         unset($o, $mt, $l, $sh);
      }
   }

   $tail = round(1440*PX_PER_MIN - $current_offset);
   if($tail > 0){
      $code .= '         '.'<div class="tail" style="height:'.$tail.'px;" onclick="stop(event); complete_appointment( snap( (event.clientY - findPos(ttParent(this))[1] -40) ), \''.$wDay.'\');"></div>'."\n";
   }

   unset($current_offset);
   unset($tail);

   $code .= '      </td>'."\n";
}

$code .= '   </tr>'."\n";
$code .= '</table>'."\n";
?>
<html>
<head>
   <title>Timetable</title>

   <style type="text/css">
   .timetable{
      background-image: url('grid.png');
   }

   .timetable td{
      padding: 0px 10px;
   }

   .timetable .header{
      text-align: center;
      padding: 0px 15px 10px;

      height: 31px;
      background-color: white;
   }
   .timetable .header:first-line{
      font-size: 1.1em;
      font-weight: bold;
   }

   .timetable .cell{
      border: 1px solid gray;
      padding: 0px 10px;
      text-align: center;

      background-color: white;
   }
   .timetable .cell:nth-of-type(odd){
      background-color: #F5F5FF;
   }

   .timetable .tail.colorful:hover{
      background-color: #FF8984;
   }

   .notice{
      position: absolute;
      top: 35%;
      left: 35%;

      background-color: white;
      border: 10px solid gray;
      border-radius: 30px;
      padding: 25px;
   }
   .close.button{
      color: white;
      font-size: 10px;
      font-weight: bold;

      width: 1.3em;
      height: 1.3em;
      text-align: center;

      background-color: #FF7D7D;
      border: 1px solid #D10606;
      border-radius: 5px;

      cursor: pointer;
   }
   .small.close.button{
      font-size: 3px;
      border-radius: 2px;
   }
   </style>

   <script type="text/javascript">
   function complete_appointment(t, d, l, c){
      if(!t) t = '00:00';
      if(!d) return false;
      if(!l) l = '60';
      if(!c) c = '';

      notice = document.createElement('div');
      notice.className = 'notice';

      var code = '<div class="close button" onclick="notice.parentNode.removeChild(notice);" style="float: right; margin: -15px -15px 15px 15px;">X</div>'+"\n";
      code += '<form action="?new" method="POST">'+"\n";
      code += '   <table cellspacing="10">'+"\n";
      code += '      <tr> <label> <td> <span>Appointment Time:</span> </td><td> <input type="text" name="time" value="'+t+'" /> </td> </label> </tr>'+"\n";
      code += '      <tr> <label> <td> <span>Appointment Date:</span> </td><td> <input type="text" name="date" value="'+d+'" /> </td> </label> </tr>'+"\n";
      code += '      <tr> <label> <td> <span>Appointment Duration:</span> </td><td> <input type="text" name="length" value="'+l+'" /> </td> </label> </tr>'+"\n";
      code += '      <tr> <label> <td> <span>Client:</span> </td><td> <input type="text" name="client" value="'+c+'" /> </td> </label> </tr>'+"\n";
      code += '      <tr> <td colspan="2" align="center"> <input type="submit" value="Submit" /> </td> </tr>'+"\n";
      code += '   </table>'+"\n";
      code += '</form>';

      notice.innerHTML = code;
      document.body.appendChild(notice);

      window.onkeydown = function(event){ if(event.keyCode == 27) notice.parentNode.removeChild(notice); };

      document.getElementsByName("time")[0].focus();
   }

   function stop(evn){
      evn.stopPropagation();
      evn.cancelBubble = true;
   }

   function findPos(obj){
      var curleft = curtop = 0;

      if (obj.offsetParent) {
         do {
            curleft += obj.offsetLeft;
            curtop += obj.offsetTop;
         } while (obj = obj.offsetParent);

         return [curleft,curtop];
      }
   }

   function ttParent(obj){
      while(obj.parentNode){
         if(obj.className.indexOf("timetable") != -1)
            return obj;
         obj = obj.parentNode;
      }
      return false;
   }

   snapTo   = <?php echo SNAP_TO; ?>;
   pxPerMin = <?php echo PX_PER_MIN; ?>;

   function snap(n){
      var s = Math.round( n/pxPerMin );
      var m = s - s%snapTo;

      var na1 = Math.floor( m/60 );
      if(na1<10) na1 = '0'+na1;
      var na2 = m%60;
      if(na2<10) na2 = '0'+na2;

      return na1+":"+na2;
   }
   </script>
</head>
<body>

<?php echo '   '.trim( join("\n   ", explode("\n", $code)) ); ?>

</body>
</html>