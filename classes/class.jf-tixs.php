<?php

namespace jf;

class Tixs {
  protected $jsonAvailableDates = array();
  protected $storedDateList;
  protected $storedDateListPath = 'assets/available-dates.txt';
  protected $dateInfo = array();
  protected $emailMessage;

  public function __construct($jsonFileName) {
    self::loadJsonFile($jsonFileName);
  }

  protected function loadJsonFile($jsonFileContent) {
    $this->storedDateList = self::loadDatesFile();
    $jsonFileContent = json_decode(file_get_contents($jsonFileContent));

    self::jsonAvailableDates($jsonFileContent);
  }

  protected function loadDatesFile() {
    if (file_exists($this->storedDateListPath)) {
      return file($this->storedDateListPath, FILE_IGNORE_NEW_LINES);
    }
  }

  protected function jsonAvailableDates($jsonDates) {
    for ($i = 0; $i < count($jsonDates->times); $i++) {
      if ($jsonDates->times[$i]->event_status == 'on_sale') {
        foreach ($jsonDates->times[$i] as $key => $value) {
          $this->jsonAvailableDates[$jsonDates->times[$i]->time][$key] = $value;
        }
      }
    }

    $availabilityChange = self::checkAvailabilityChange();

    if ((empty($this->jsonAvailableDates) && empty($this->storedDateList)) || $availabilityChange) {
      die();
    } else {
      self::sortDateStatus();
    }
  }

  protected function checkAvailabilityChange() {
    // get json keys (dates) as own array
    $jasonDateKeyValue = array_keys($this->jsonAvailableDates);
    $diffOne = array_diff($jasonDateKeyValue, $this->storedDateList);
    $diffTwo = array_diff($this->storedDateList, $jasonDateKeyValue);
    $diff = array_merge($diffOne, $diffTwo);

    if (empty($diff)) {
      return true;
    } else {
      return false;
    }
  }

  protected function sortDateStatus() {
    // get json keys (dates) as own array
    $jasonDateKeyValue = array_keys($this->jsonAvailableDates);

    // reoccurring date
    foreach ($jasonDateKeyValue as $keyDate) {
      if (in_array($keyDate, $this->storedDateList)) {
        $this->dateInfo['reoccurring'][] = $keyDate;
      } else {
        $this->dateInfo['new'][] = $keyDate;
      }
    }

    // sold out
    foreach ($this->storedDateList as $storedDate) {
      if (!in_array($storedDate, $jasonDateKeyValue)) {
        $this->dateInfo['soldOut'][] = $storedDate;
      }
    }

    // add new dates to stored file
    if (!empty($this->dateInfo['new'])) {
      foreach ($this->dateInfo['new'] as $key => $newDate) {
        self::addDateToFile($newDate);
      }
    }

    // remove dates from stored file
    if (!empty($this->dateInfo['soldOut'])) {
      foreach ($this->dateInfo['soldOut'] as $key => $soldOutDate) {
        self::deleteDate($soldOutDate);
      }
    }

    self::compileEmailMessage();
  }

  protected function deleteDate($soldOutDates) {
    $fileDates = file_get_contents($this->storedDateListPath);
    $fileDates = str_replace($soldOutDates."\n", '', $fileDates);
    file_put_contents($this->storedDateListPath, $fileDates);
  }

  protected function addDateToFile($newDates) {
    $fileDates = fopen($this->storedDateListPath, 'a+');
    fwrite($fileDates, $newDates."\n");
    fclose($fileDates);
  }

  protected function compileEmailMessage() {
    $message = '';
    $soldOutMessage = '';

    if (!empty($this->jsonAvailableDates)) {
      $message .= 'The following {date_count} available:<br />';
      $dateCount = 0;

      // add available dates with link to email message
      foreach ($this->jsonAvailableDates as $date) {
        $message .= self::emailDateLinksTpl($date);
        $dateCount = $dateCount+1;
      }

      if ($dateCount <= 1) {
        $message = str_replace('{date_count}', 'date is', $message);
      } else {
        $message = str_replace('{date_count}', 'dates are', $message);
      }
    }

    if (!empty($this->dateInfo['soldOut'])) {
      if (!empty($this->jsonAvailableDates)) {
        $soldOutMessage .= '<br />';
      }

      $soldOutMessage .= 'The following {soldout_count} sold out:<br />';
      $soldoutCount = 0;

      // add sold out dates to email message
      foreach ($this->dateInfo['soldOut'] as $soldOutDate) {
        $soldOutMessage .= $soldOutDate.'<br />';
        $soldoutCount = $soldoutCount+1;
      }

      if ($soldoutCount <= 1) {
        $soldOutMessage = str_replace('{soldout_count}', 'date is', $soldOutMessage);
      } else {
        $soldOutMessage = str_replace('{soldout_count}', 'dates have', $soldOutMessage);
      }
    }

    $this->emailMessage = $message.$soldOutMessage;
  }

  protected function emailDateLinksTpl($date) {
    $tpl = '<a href="http://www.showclix.com/event/{eid}">{time}</a><br />';

   foreach ($date as $key => $val) {
     $tpl = str_replace("{".$key."}", $val, $tpl);
   }

   return $tpl;
  }

  public function emailMessage() {
    $email = $this->emailMessage;
    return $email;
  }
}
