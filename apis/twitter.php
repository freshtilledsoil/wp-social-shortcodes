<?php
require_once('api.php');
require_once('TwitterAPIExchange.php');

class TwitterAPI extends BaseAPI {

  public function __construct() {
    parent::__construct('https://api.twitter.com/1.1');
  }

  //***************************************************************************
  //
  //***************************************************************************
  public function request($url, $method = 'GET', $params=null) {
    $results = $this->getCacheContents();
    if( $results ) { return $results; }

    $settings = array(
        'oauth_access_token'        => get_option('fts_ss_oauth_access_token', ''),
        'oauth_access_token_secret' => get_option('fts_ss_oauth_access_token_secret', ''),
        'consumer_key'              => get_option('fts_ss_consumer_key', ''),
        'consumer_secret'           => get_option('fts_ss_consumer_secret', ''),
    );

    $t = new TwitterAPIExchange( $settings );
    $results =  $t->setGetfield( $params )
                 ->buildOauth( $this->getEndpoint() . $url, $method )
                 ->performRequest();

    if( $results === false ) {
      error_log('Twitter Error: ' . $url);
    }
    else {
      $this->setCacheContents( $results );
    }
    return $results;
  }
    //***************************************************************************
    //
    //***************************************************************************
    public function getTweets( $username, $count = 3 ) {

      $this->setCacheName( 'tw_' . $username . 'c' . $count );
      $this->setCacheExpiration( 3600 ); // 1 hour

      $results = $this->request('/statuses/user_timeline.json', 'GET', sprintf('?screen_name=%s&count=%d&trim_user=true&exclude_replies=true&include_rts=true', $username, $count));

      if( $results && $json = json_decode($results) ) {
          $tweets = array();
          foreach( $json as $tweet ) {
              $tweets[] = array(
                  'id'              => $tweet->id,
                  'text'            => TwitterAPI::linkifyTweet( $tweet->text ),
                  'created_at'      => TwitterAPI::getRelativeTime( $tweet->created_at ),
                  'time'            => $tweet->created_at,
                  'retweet_count'   => $tweet->retweet_count,
                  'favorite_count'  => $tweet->favorite_count
              );
          }
          return $tweets;
      }
      return false;
    }

    //***************************************************************************
    //
    //***************************************************************************
    public static function linkifyTweet( $text ) {
        if( !$text ) return;

        //Links
        $text = preg_replace('/(https?:\/\/\S+)/', '<a href="\1" rel="nofollow">\1</a>', $text);

        //Users
        $text = preg_replace('/(^|\s)@(\w+)/', '\1<a href="https://twitter.com/\2" rel="nofollow">@\2</a>', $text);

        //Hashtags
        $text = preg_replace('/(^|\s)#(\w+)/', '\1<a href="https://twitter.com/search?q=%23\2" rel="nofollow">#\2</a>',$text);
        return $text;
    }

    public static function getRelativeTime( $time ) {
      $time = strtotime($time);
      $gap = time() - $time;

      if ($gap < 5) {
          return 'less than 5 seconds ago';
      } else if ($gap < 10) {
          return 'less than 10 seconds ago';
      } else if ($gap < 20) {
          return 'less than 20 seconds ago';
      } else if ($gap < 40) {
          return 'half a minute ago';
      } else if ($gap < 60) {
          return 'less than a minute ago';
      }

      $gap = round($gap / 60);
      if ($gap < 60)  {
          return $gap.' minute'.($gap > 1 ? 's' : '').' ago';
      }

      $gap = round($gap / 60);
      if ($gap < 24)  {
          return 'about '.$gap.' hour'.($gap > 1 ? 's' : '').' ago';
      }
      return date('d M', $time);
    }
} // TwitterAPI
