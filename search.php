<?php
/*
  RARBG.to DLM file to be used with Synology Download Station
*/

class synologyDLM_RARBG {
  private $host_url = 'https://rarbg.to/torrents.php';      
  private $qurl = '?search=%s&order=seeders&by=DESC';
  
  public function __construct() {
    $this->qurl = $this->host_url.$this->qurl;
    $this->cookies = "gaDts48g=q8h5pp9t; tcc; use_alt_cdn=1; aby=2; skt=cusyhpga4r; skt=cusyhpga4r; gaDts48g=q8h5pp9t";
    $this->user_agent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/97.0.4692.71 Safari/537.36';
  }

  private function getMagnetLink($page){
    /*
      Retreive the given torrent page to parse and 
      look for the magnet link contained in the main table
      in the first row
    */
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_COOKIE, $this->cookies);
    curl_setopt($ch, CURLOPT_FAILONERROR, 1);
    curl_setopt($ch, CURLOPT_REFERER, $page);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
    curl_setopt($ch, CURLOPT_TIMEOUT, 20);
    curl_setopt($ch, CURLOPT_USERAGENT, $this->user_agent);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_URL, $page);
    $pageResponse = curl_exec($ch);
    curl_close($ch);
    /* php-dom lib to parse the html page*/
    $dom = new domDocument;
    @$dom->loadHTML($pageResponse);
    $dom->preserveWhiteSpace = false;
    $tables = $dom->getElementsByTagName('table');
    /* get the magnet link from the first row of the table */
    $rows = $tables->item(8)->getElementsByTagName('tr'); // bound to table number 8, should find a way to xpath
    $data = $rows->item(0)->getElementsByTagName('a');
    $magnetLink = $data->item(1)->getAttribute('href');
    return $magnetLink;
  }
  
  public function getFileSize($data){
    /*
      Converts human bytes size to machine bytes size
    */
    $data = explode(" ", $data);
    $size = str_replace(",",".",$data[0]);
    $size_dim =  $data[1];
    switch ($size_dim){
      case 'KB':
        $size = $size * 1024;
        break;
      case 'MB':
        $size = $size * 1024 * 1024;
        break;
      case 'GB': 
        $size = $size * 1024 * 1024 * 1024;
        break;
      case 'TB': 
        $size = $size * 1024 * 1024 * 1024 * 1024;
        break;
    }
    return (int) $size;
  }

  public function getTorrentPage($data){
    /*
      Returns the page link: http://rarbg.to/torrent/ID
      Used as metadata for the torrent and for the magnet lookup
    */
    $links = $data->getElementsByTagName('a');
    $aTag = $links->item(0)->getAttribute('href'); // first is rarbg, second is imdb tag if any
    return "http://rarbg.to".$aTag;
  }

  public function prepare($curl, $query) {
    /*
      MANDATORY DLM PREPARE CLASS
    */
    $url = sprintf($this->qurl, urlencode($query));
    curl_setopt($curl, CURLOPT_COOKIE, $this->cookies);
    curl_setopt($curl, CURLOPT_FAILONERROR, 1);
    curl_setopt($curl, CURLOPT_REFERER, $url);
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER,1);
    curl_setopt($curl, CURLOPT_TIMEOUT, 20);
    curl_setopt($curl, CURLOPT_USERAGENT, $this->user_agent);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_URL, $url);
  }

  public function parse($plugin, $response) {
    /*
      MANDATORY DLM PARSE CLASS
    */
    $dom = new domDocument; // use php-dom to parse the table
    try {
      @$dom->loadHTML($response);
      $dom->preserveWhiteSpace = false;
      $tables = $dom->getElementsByTagName('table');
      $rows = $tables->item(11)->getElementsByTagName('tr'); // Should use xpath instead to look for table with class 'list2t'
    } catch (Exception $e) {
      $plugin->addResult("ERROR.PARSING.PAGE.PROBABLE.BOT.VERIFICATION.CAPTCHA", "", "", "BAD DATE", "", "", "0", "0", "ERROR");
      return 1;
    }
    $resValue=0; // PARSE function is required to return an integer
    foreach ($rows as $row) {
      $cols = $row->getElementsByTagName('td');
      $title = $cols[1]->firstChild->textContent;
      if ($title != ""){
        $page = $this->getTorrentPage($cols[1]);
        $download = $this->getMagnetLink($page); // Magnet link on RARBG is on the torrent page not on the main table
        $size = $this->getFileSize($cols[3]->firstChild->textContent);
        $datetime = $cols[2]->firstChild->textContent;
        $hash = "HASH".$res; // looks like it's required in the docs so let's just fill it
        $seeds = (int) $cols[4]->firstChild->textContent; 
        $leechs = (int) $cols[5]->firstChild->textContent;
        $category = $resValue." - "."no-category"; // RARBG has numeric categories to be transcoded. Maybe future update.
        $plugin->addResult($title, $download, $size, $datetime, $page, $hash, $seeds, $leechs, $category);
        $resValue++;
      }
    }
    return $resValue;
  }
}
?>