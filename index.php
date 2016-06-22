<?php
/*
* Plugin Name: Social Shortcodes by Fresh Tilled Soil
* Plugin URI: https://github.com/freshtilledsoil/wp-social-shortcodes/
* Description: Short code for display social network data for a specific user using shortcodes
* Version: 0.1
* Author: Fresh Tilled Soil (Dave Romero & Tim Wright)
* Author URI: http://www.freshtilledsoil.com
*/

class FTSSocialShortCodes {

  public static function twitter( $atts ) {
      extract(shortcode_atts( FTSSocialShortCodes::getDefaults(), $atts ));

      if( empty( $user ) || !is_numeric( $count ) ) { return ''; }

      $output = '';

      include_once('apis/twitter.php');

      $t = new TwitterAPI();

      $ns = 'social-code';

      if( $tweets = $t->getTweets( $user, $count ) ) {

          $output.= '<section class="' . $ns . ' ' . $ns . '-twitter">';

          if ( count( $tweets ) >= 1 ) {

            if( $title ) {
                $output.= '<h2 class="' . $ns . '--heading">' . $title . '</h2>' . "\n";
                $output.= '<p class="' . $ns . '--subheading">Via <a href="' . esc_url( 'https://twitter.com/' . $user ) . '"><em>@' . $user . '</em></a></p>' . "\n";
            }

            $output.= '<ul class="' . $ns . '--list">' . "\n";
            foreach( $tweets as $tweet ) {

                $output .= '<li class="' . $ns . '--item">' . "\n";
                $output .= $tweet['text'] . "\n";
                $output .= '<ul class="' . $ns . '--meta">' . "\n";

                $output .= '<li class="' . $ns . '--subitem"><a href="https://twitter.com/intent/tweet?in_reply_to='.$tweet['id'].'" class="social-code--item__anchor" target="_blank">' . "\n";
                  $output .= '<img src="' . esc_url( FTSSocialShortCodes::getPluginURL() . 'assets/icons/mail-reply.svg' ) .'" alt="Reply" class="' . $ns . '--icon ' . $ns . '--icon__reply">' . "\n";
                  $output .= '<span class="' . $ns . '--item__label">Reply to this Tweet</span>' . "\n";
                $output .= '</a></li>' . "\n";

                $output .= '<li class="' . $ns . '--subitem"><a href="https://twitter.com/intent/retweet?tweet_id='.$tweet['id'].'" class="social-code--item__anchor" target="_blank">' . "\n";
                  $output .= '<img src="' . esc_url( FTSSocialShortCodes::getPluginURL() . 'assets/icons/retweet.svg' ) . '" alt="Retweet" class="' . $ns . '--icon ' . $ns . '--icon__retweet">' . "\n";
                  $output .= '<span class="' . $ns . '--item__label">Retweet count: </span>' . $tweet['retweet_count'] .  "\n";
                $output .= '</a></li>' . "\n";

                $output .= '<li class="' . $ns . '--subitem"><a href="https://twitter.com/intent/like?tweet_id='.$tweet['id'].'" class="social-code--item__anchor" target="_blank">' . "\n";
                  $output .= '<img src="' . esc_url( FTSSocialShortCodes::getPluginURL() . 'assets/icons/heart.svg' ) .'" alt="Like" class="' . $ns . '--icon ' . $ns . '--icon__heart">' . "\n";
                  $output .= '<span class="' . $ns . '--item__label">Favorite count: </span>' . $tweet['favorite_count'] . "\n";
                $output .= '</a></li>' . "\n";

                $output .= '</li>' . "\n";
                $output .= '</ul>' . "\n";

            } // foreach

            $output.= '</ul>' . "\n";

          } else {

              $output .= '<div class="' . $ns . '--msg"><p>My most sincere apologies, but there are no tweets to display. Please make sure the <a href="' . get_admin_url() . 'options-general.php?page=social_shortcodes">plugin settings</a> are filled out correctly.</p></div>' . "\n";

          }

          $output.= '</section><!--/.' . $ns . '-->' . "\n";
      }
      return $output;
  }

  //***************************************************************************
  // Shortcode default values
  //***************************************************************************
  public static function getDefaults( $atts = null ) {
      return array(
          'user'    => '',
          'title'   => '',
          'count'   => 3
      );
  } // getDefaults

  public static function getPluginURL() {
    return plugin_dir_url( __FILE__ );
  }
} // end Class FTSSocialShortCodes

//***************************************************************************
// Shortcode style
//***************************************************************************
function ftsSocialEnqueueScripts() {
  wp_enqueue_style( 'social-shortcodes-styles', plugin_dir_url( __FILE__ ) . 'assets/css/social-shortcodes.css' );
}
add_action( 'wp_enqueue_scripts', 'ftsSocialEnqueueScripts' );

//***************************************************************************
// Create the twitter shortcode
//***************************************************************************
add_shortcode( 'twitter', array('FTSSocialShortCodes', 'twitter'));

//***************************************************************************
// Settings Page
//***************************************************************************
function ftsSocialAdminMenu() {
  add_options_page( 'Social Shortcodes Settings', 'Social Shortcodes', 'manage_options', 'social_shortcodes', 'ftsSocialOptionsPage' );
}
add_action( 'admin_menu', 'ftsSocialAdminMenu' );

function ftsSocialOptionsPage() { ?>
  <form action="options.php" method="post">
    <h2>Social Shortcodes Settings</h2>
    <?php
    settings_fields( 'social_shortcodes_settings' );
    do_settings_sections( 'social_shortcodes_settings' );
    submit_button();
    ?>
  </form>
<?php }

//***************************************************************************
// Settings Page Fields
//***************************************************************************
function ftsSocialAdminInit() {
  add_settings_section(
    'fts_ss_section',
    'Twitter API Settings',
    'ftsSocialSettingsSectionCallback',
    'social_shortcodes_settings'
  );

  $fields = array(
    'fts_ss_oauth_access_token'         => 'Twitter OAuth Access Token',
    'fts_ss_oauth_access_token_secret'  => 'Twitter OAuth Access Token Secret',
    'fts_ss_consumer_key'               => 'Twitter Consumer Key',
    'fts_ss_consumer_secret'            => 'Twitter Consumer Secret'
  );

  // Add and register settings field
  foreach( $fields as $id => $label ) {
    add_settings_field(
      $id,
      $label,
      'ftsSocialFieldCallback',
      'social_shortcodes_settings',
      'fts_ss_section',
      array( $id )
    );

    register_setting( 'social_shortcodes_settings', $id );
  }
}
add_action( 'admin_init', 'ftsSocialAdminInit' );

function ftsSocialSettingsSectionCallback() {

}

// Output options field
function ftsSocialFieldCallback( $args ) {
  $option = get_option( $args[0] );
  echo '<input type="text" id="'. $args[0] .'" name="'. $args[0] .'" value="' . $option . '" class="regular-text" />';
}
