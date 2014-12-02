<?php

namespace jon;

class SendEmail {

  public function __construct($emailMessage) {
    self::compileEmail($emailMessage);
  }

  protected function compileEmail($emailMessage) {
    require_once 'assets/email-info.php';
    $addresses = explode(',', $email['to']);

    foreach ($addresses as $address) {
      mail($address, $email['subject'], $emailMessage, $email['headers']);
    }
  }
}
