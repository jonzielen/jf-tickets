<?php
  $jsonFile = 'http://www.showclix.com/event/TheTonightShowStarringJimmyFallon/recurring-event-times';

  require_once 'classes/class.jf-tixs.php';
  $tickets = new jf\Tixs($jsonFile);
  $emailMessage = $tickets->emailMessage();

  require_once 'classes/class.send-email.php';
  $email = new jon\SendEmail($emailMessage);
?>
