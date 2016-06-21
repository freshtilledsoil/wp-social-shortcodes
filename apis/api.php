<?php
class BaseAPI {

  private $_endpoint;
  private $_cache_name;
  private $_cache_expiration;

  function __construct( $endpoint ) {
    $this->_endpoint    = $endpoint;
    $this->_cache_name  = 'api_cache';
  }

  public function getEndpoint() { return $this->_endpoint; }

  //***************************************************************************
  // Transient Name
  //***************************************************************************
  public function getCacheName() { return $this->_cache_name; }

  public function setCacheName( $cache_name ) {
    if( $cache_name ) { $this->_cache_name = $cache_name; }
    return $this;
  }

  //***************************************************************************
  // Transient Expiration (in seconds)
  //***************************************************************************
  public function getCacheExpiration() { return $this->_cache_expiration; }

  public function setCacheExpiration( $cache_expiration=3600 ) {
    if( is_numeric( $cache_expiration ) ) { $this->_cache_expiration = $cache_expiration; }
    return $this;
  }

  //***************************************************************************
  // Transients Wrapper
  //***************************************************************************
  public function getCacheContents() { return get_transient( $this->getCacheName() ); }

  public function setCacheContents( $results ) { return set_transient( $this->getCacheName(), $results, $this->getCacheExpiration() ); }

  //***************************************************************************
  //
  //***************************************************************************
  public function request($url, $method = 'GET', $params = array()) {
    if( 'GET' == $method && $params ) {
      $url = $url . '?' . http_build_query($params, null, '&');
    }

    $results = $this->getCacheContents();
    if( $results ) { return $results; }

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $this->getEndpoint() . $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

    if( 'GET' == $method ) {
      curl_setopt($ch, CURLOPT_HEADER, 0);
    }
    else
    if( 'POST' == $method ) {
      curl_setopt($ch, CURLOPT_POST, 1);
      curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
    }

    $results  = curl_exec($ch);
    $status   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if( $results === false ) {
      error_log('CURL Error: ' . curl_error($ch) . ' ' . curl_errno($ch) . ' ' . $url);
    }
    else {
      $this->setCacheContents($results);
    }
    return $results;
  }
} // BaseAPI
