<?php if (!defined('APPLICATION')) exit();
/**
 * @copyright Copyright 2014 NORDUnet A/S.
 * @license MIT License https://www.tldrlegal.com/l/mit
 * @since 0.0 Adding MentionsFormatter
 */

// Define the plugin:
$PluginInfo['jsconnect'] = array(
   'Name' => 'Spaced mentions',
   'Description' => 'Enables mentions of people who have spaces in their usernames. To mention "Markus Krogh" you would write @Markus+Krogh. Also changes username validation to accept spaces.',
   'Version' => '0.2',
   'RequiredApplications' => array('Vanilla' => '2.0.18b1'),
   'MobileFriendly' => TRUE,
   'Author' => 'Markus Krogh',
   'AuthorEmail' => 'markus@nordu.net',
   'AuthorUrl' => 'http://casadelkrogh.dk',
   //'SettingsUrl' => '/dashboard/settings/jsconnect',
   //'SettingsPermission' => 'Garden.Settings.Manage',
);

Gdn::FactoryInstall('MentionsFormatter', 'SpacedMentionsPlugin', __FILE__, Gdn::FactoryInstance);
$Configuration['Garden']['User']['ValidationRegex'] = "\d\w][\d\w_ "; //Match original + space
$Configuration['Garden']['User']['ValidationLength'] = "{2,30}"; //One less than original + 10 extra

class SpacedMentionsPlugin extends Gdn_Plugin {
  
     private $regex  = '/(^|[\s,\.>])@([\w\+]{3,50})\b/i';
  
     function ReplacePlus(&$Value, $key) {
        $Value = str_replace("+", " ", $Value);
     }
 
      
  
     function GetMentions($String) {
      $Mentions = array();

      // This one grabs mentions that start at the beginning of $String
      preg_match_all($this->regex, $String, $Matches);
      if (count($Matches) > 1) {
         $Mentions = array_unique($Matches[1]);
         array_walk($Mentions, array($this, "ReplacePlus"));
      }
      return $Mentions;
   }
  
  
  function AccountLink($match) {
     $mention = str_replace("+", " ", $match[2]);
     return $match[1].Anchor("@$mention", "/profile/$mention");
  }
  
  function FormatMentions($Mixed) {
   
    $Mixed = preg_replace_callback($this->regex, array($this, "AccountLink"), $Mixed);
    
     // Handle #hashtag searches
    if(C('Garden.Format.Hashtags')) {
        $Mixed = preg_replace(
            '/(^|[\s,\.>])\#([\w\-]+)(?=[\s,\.!?]|$)/i',
            '\1'.Anchor('#\2', '/search?Search=%23\2&Mode=like').'\3',
            $Mixed
        );
    }

        // Handle "/me does x" action statements
    if(C('Garden.Format.MeActions')) {
      $Mixed = preg_replace(
         '/(^|[\n])(\/me)(\s[^(\n)]+)/i',
         '\1'.Wrap(Wrap('\2', 'span', array('class' => 'MeActionName')).'\3', 'span', array('class' => 'AuthorAction')),
         $Mixed
      );
    }
    return $Mixed;
  }
}
