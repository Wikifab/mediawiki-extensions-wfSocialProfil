<?php

/**
 * User profile Wiki Page
 *
 * @file
 * @ingroup Extensions
 * @author David Pean <david.pean@gmail.com>
 * @copyright Copyright © 2007, Wikia Inc.
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License 2.0 or later
 */

class UserProfilePage extends Article {

	/**
	 * @var Title
	 */
	public $title = null;

	/**
	 * @var String: user name of the user whose profile we're viewing
	 */
	public $user_name;

	/**
	 * @var Integer: user ID of the user whose profile we're viewing
	 */
	public $user_id;

	/**
	 * @var User: User object representing the user whose profile we're viewing
	 */
	public $user;

	/**
	 * @var Boolean: is the current user the owner of the profile page?
	 */
	public $is_owner;

	/**
	 * @var Array: user profile data (interests, etc.) for the user whose
	 * profile we're viewing
	 */
	public $profile_data;

	/**
	 * Constructor
	 */
	function __construct( $title ) {
		global $wgUser;
		parent::__construct( $title );
		$this->user_name = $title->getText();
		$this->user_id = User::idFromName( $this->user_name );
		$this->user = User::newFromId( $this->user_id );
		$this->user->loadFromDatabase();

		$this->is_owner = ( $this->user_name == $wgUser->getName() );

		$profile = new UserProfile( $this->user_name );
		$this->profile_data = $profile->getProfile();
	}

	/**
	 * Is the current user the owner of the profile page?
	 * In other words, is the current user's username the same as that of the
	 * profile's owner's?
	 *
	 * @return Boolean
	 */
	function isOwner() {
		return $this->is_owner;
	}

	function view() {
		global $wgOut;

		$wgOut->setPageTitle( $this->mTitle->getPrefixedText() );

		// No need to display noarticletext, we use our own message
		if ( !$this->user_id ) {
			parent::view();
			return '';
		}

		// User does not want social profile for User:user_name, so we just
		// show header + page content
		if (
			$this->getTitle()->getNamespace() == NS_USER &&
			$this->profile_data['user_id'] &&
			$this->profile_data['user_page_type'] == 0
		)
		{
			parent::view();
			return '';
		}

		$wgOut->addHTML( '<div id="profileWf">' );
		$wgOut->addHTML( '<div id="mw-content-text" lang="fr" dir="ltr" class="mw-content-ltr">' );
		$wgOut->addHTML( '<div class="row">' );

		$wgOut->addHTML( '<div class="col-md-3 col-sm-6 col-xs-12">' );
		$wgOut->addHTML( $this->getProfileLeftColHead( $this->user_id, $this->user_name ));
		$wgOut->addHTML( '</div>' );

		$wgOut->addHTML( '<div class="col-md-9 col-sm-6 col-xs-12">' );
		$wgOut->addHTML( $this->getCenterCol());
		$wgOut->addHTML( '</div>' );

		$wgOut->addHTML( '</div></div></div>' );

	}

	public function getCenterCol() {
		global $wgUser;
		global $wgUserProfileDisplay;


		// Variables and other crap
		$page_title = $this->getTitle()->getText();
		$title_parts = explode( '/', $page_title );
		$user = $title_parts[0];
		$user_safe = urlencode( $user );
		$userObj = \User::newFromName($user_safe);

		$tabs = [];
		$tabs['tutorials'] = [
				'label' => wfMessage('userprofilepage-tabs-created-page-label'),
				'content' => $this->getTutorials( $this->user_name)
		];
		$tabs['contribs'] = [
				'label' => wfMessage('userprofilepage-tabs-contributions-label'),
				'content' => $this->getContributions( $this->user_name)
		];
		if( $wgUserProfileDisplay['userswatchlist']) {
			$tabs['star'] = [
					'label' => wfMessage('userspageslinks-tab-favorites'),
					'content' => $this->getFavorites($this->user)
			];
		}
		if( $wgUserProfileDisplay['userswatchlist']) {
			$tabs['followers'] = [
					'label' => wfMessage('userswatchbutton-followers'),
					'content' => $this->getFollowers($this->user)
			];
			$tabs['following'] = [
					'label' => wfMessage('userswatchbutton-following'),
					'content' => $this->getFollowings( $this->user)
			];
		}

		Hooks::run('SocialProfile-profileTabs', [$userObj, &$tabs]);

		$out ='';

		$out .= '<div>
		  <!-- Nav tabs -->
		  <ul class="nav nav-tabs" role="tablist">';

		$activeClass = 'active';
		foreach ($tabs as $key => $tab) {
			$out .= '
					<li id="tab-'.$key.'" role="presentation" class="'.$activeClass.'">
					<a href="#'.$key.'" aria-controls="'.$key.'" role="tab" data-toggle="tab">
							'.$tab['label'].'
					</a></li>';
			$activeClass = '';
		}
		//$out .= '
		//		<li role="presentation" class="active"><a href="#tutorials" aria-controls="tutorials" role="tab" data-toggle="tab">Tutoriels</a></li>
		//		<li role="presentation" ><a href="#contribs" aria-controls="contribs" role="tab" data-toggle="tab">Contributions</a></li>
		//';




		$out .= '
			  <div class="tabs-follow-btn">
			  ';
		if ( $wgUser->getName() == $this->getTitle()->getText() ) {
			$send_message = SpecialPage::getTitleFor( 'UpdateProfile' );
			$send_message_url = htmlspecialchars( $send_message->getFullURL( ) );
			$out .= '<a href="' . $send_message_url . '"
				  <button class="btn btn-sm btn-message"><i class="fa fa-pencil-square-o"></i><span class="btn-follow-text"> '.wfMessage( 'user-edit-profile' )->escaped() . '</span></button>
				  </a>';
		} else {
			// follow button
			if ($wgUserProfileDisplay['userswatchlist']) {
				$out .= UsersWatchButton::getHtml($user);
			}
			// send message button
			$send_message = SpecialPage::getTitleFor( 'UserBoardAdvanced' );
			$send_message_url = htmlspecialchars( $send_message->getFullURL( 'user=' . $user_safe ) );
			$out .= '<a href="' . $send_message_url . '"
				  <button class="btn btn-sm btn-message"><i class="fa fa-envelope-o"></i><span class="btn-follow-text"> '.wfMessage( 'user-send-message' )->escaped() . '</span></button>
				  </a>';
		}
		$out .= '
			  </div>';
		$out .= '
		  </ul>';



		$out .= '
		  <div class="tab-content">';

		$activeClass = 'active';
		foreach ($tabs as $key => $tab) {
			$out .= '<div role="tabpanel" class="tab-pane '.$activeClass.'" id="'.$key.'">' . $tab['content'] . '</div>';

			$activeClass = '';
		}
		//$out .= '<div role="tabpanel" class="tab-pane active" id="tutorials">' . $this->getTutorials( $this->user_name) . '</div>';
		//$out .= '<div role="tabpanel" class="tab-pane" id="contribs">' . $this->getContributions( $this->user_name) . '</div>';
		$out .= '</div>
		</div>';

		//$out .= $this->getCenterTabs();
		//$out .= $this->getTutorials( $this->user_name);
		//$out .= $this->getContributions( $this->user_name);

		return $out;
	}

	public function getCenterTabs() {


		$out = '<div class="wf-profile-tabs">
			  <div class="tabs-actions">
				<a href="#" class="active" rel="nofollow">'. wfMessage('userprofilepage-tabs-created-page-label').'</a>
				<a href="#" rel="nofollow">Contributions</a>
			  </div>
			  <div class="tabs-follow-btn">
				  <button class="btn btn-sm btn-message"><i class="fa fa-envelope-o"></i> Envoyer un message</button>
			  </div>
			</div><div class="cleared"></div>';

		return $out;
	}

	public function getFavorites( User $user ) {
		$usersLinksIdidit = UsersPagesLinks\UsersPagesLinksCore::getInstance()->getUsersPagesLinks($user, 'ididit');
		$usersLinksStars = UsersPagesLinks\UsersPagesLinksCore::getInstance()->getUsersPagesLinks($user, 'star');


		$wikifabSearchResultFormatter = new WikifabExploreResultFormatter();
		$wikifabSearchResultFormatter->setTemplate($GLOBALS['egChameleonLayoutFileSearchResult']);

		$out = '';
		if ($usersLinksIdidit) {
			$out .= '<h4>Je les ai fait :</h4>';
			$out .= '<div class="row">';
			foreach ($usersLinksIdidit as $title) {
				$result = SearchResult::newFromTitle( $title );
				$out .= $wikifabSearchResultFormatter->getPageDetails( $result );
			}
			$out .= '</div>';
		}
		if ($usersLinksStars) {
			$out .= '<h4>Favoris</h4>';
			$out .= '<div class="row">';

			foreach ($usersLinksStars as $title) {
				$result = SearchResult::newFromTitle( $title );
				$out .= $wikifabSearchResultFormatter->getPageDetails( $result );
			}
			$out .= '</div>';
		}
		return $out;
	}

	public function getFollowers( User $user ) {
		return UsersWatchButton::getFollowers($user);
	}

	public function getFollowings( User $user ) {
		return UsersWatchButton::getFollowing($user);
	}

	public function getTutorials( $user_name ) {

		$context = new RequestContext();
		$options =  [
			'namespace' => "0", //namespace principal, to get only tutorials
			'target' => $this->user_name,
			'newOnly' => 1,
			'start' => false,
			'end' => false
		];
		$contribsPager = new ContribsPager($context, $options);

		$contribs = $contribsPager->reallyDoQuery( 0, 100, true);


		$wikifabSearchResultFormatter = new WikifabExploreResultFormatter();
		$wikifabSearchResultFormatter->setTemplate($GLOBALS['egChameleonLayoutFileSearchResult']);

		$out = "<div class=\"row\">";

		while($contrib = $contribs->next()) {
			$title = Title::newFromText( $contrib->page_title );
			$result = SearchResult::newFromTitle( $title );
			$out .= $wikifabSearchResultFormatter->getPageDetails( $result );
		}

		$out .= "</div>";

		// If LatestDiscussions extension is installed
		global $IP;

		// Stop if LatestDiscussions extension doesn't exists, do not generate list
		if(file_exists("$IP/extensions/LatestDiscussions/extension.json")){
			$out .= $this->getLastDiscussions($this->user);
		}

		return $out;
	}

	public function getLastDiscussions(User $user){
		$out = "<h3>Vos dernières discussions</h3>";

		$ld = new LatestDiscussions();
		$out .= $ld->renderDiscussionsFromUser($user, 10, 0);

		return $out;
	}

	public function getContributions( $user_name ) {

		$context = new RequestContext();
		$options =  [
			'namespace' => "0", //namespace principal, to get only tutorials
			'target' => $this->user_name,
			//'newOnly' => 1,
			'start' => false,
			'end' => false
		];
		$pager = new ContribsPager($context, $options);

		if ( !$pager->getNumRows() ) {
			$out = wfMessage( 'no-contribs' )->escaped() ;
		} else {

			$out = $pager->getBody();
		}

		return $out;
	}


	function getUserStatsRow( $label, $value ) {
		$output = ''; // Prevent E_NOTICE

		if ( $value != 0 ) {
			global $wgLang;
			$formattedValue = $wgLang->formatNum( $value );
			$output = "<div>
					<b>{$label}</b>
					{$formattedValue}
			</div>";
		}

		return $output;
	}

	function getUserStats( $user_id, $user_name ) {
		global $wgUserProfileDisplay;

		if ( $wgUserProfileDisplay['stats'] == false ) {
			return '';
		}

		$output = ''; // Prevent E_NOTICE

		$stats = new UserStats( $user_id, $user_name );
		$stats_data = $stats->getUserStats();

		$total_value = $stats_data['edits'] . $stats_data['votes'] .
						$stats_data['comments'] . $stats_data['recruits'] .
						$stats_data['poll_votes'] .
						$stats_data['picture_game_votes'] .
						$stats_data['quiz_points'];

		if ( $total_value != 0 ) {
			$output .= '<div class="user-section-heading">
				<div class="user-section-title">' .
					wfMessage( 'user-stats-title' )->escaped() .
				'</div>
				<div class="user-section-actions">
					<div class="action-right">
					</div>
					<div class="action-left">
					</div>
					<div class="visualClear"></div>
				</div>
			</div>
			<div class="visualClear"></div>
			<div class="profile-info-container bold-fix">' .
				$this->getUserStatsRow(
					wfMessage( 'user-stats-edits', $stats_data['edits'] )->escaped(),
					$stats_data['edits']
				) .
				$this->getUserStatsRow(
					wfMessage( 'user-stats-votes', $stats_data['votes'] )->escaped(),
					$stats_data['votes']
				) .
				$this->getUserStatsRow(
					wfMessage( 'user-stats-comments', $stats_data['comments'] )->escaped(),
					$stats_data['comments'] ) .
				$this->getUserStatsRow(
					wfMessage( 'user-stats-recruits', $stats_data['recruits'] )->escaped(),
					$stats_data['recruits']
				) .
				$this->getUserStatsRow(
					wfMessage( 'user-stats-poll-votes', $stats_data['poll_votes'] )->escaped(),
					$stats_data['poll_votes']
				) .
				$this->getUserStatsRow(
					wfMessage( 'user-stats-picture-game-votes', $stats_data['picture_game_votes'] )->escaped(),
					$stats_data['picture_game_votes']
				) .
				$this->getUserStatsRow(
					wfMessage( 'user-stats-quiz-points', $stats_data['quiz_points'] )->escaped(),
					$stats_data['quiz_points']
				);
			if ( $stats_data['currency'] != '10000' ) {
				$output .= $this->getUserStatsRow(
					wfMessage( 'user-stats-pick-points', $stats_data['currency'] )->escaped(),
					$stats_data['currency']
				);
			}
			$output .= '</div>';
		}

		return $output;
	}

	/**
	 * Get three of the polls the user has created and cache the data in
	 * memcached.
	 *
	 * @return Array
	 */
	function getUserPolls() {
		global $wgMemc;

		$polls = array();

		// Try cache
		$key = wfMemcKey( 'user', 'profile', 'polls', $this->user_id );
		$data = $wgMemc->get( $key );

		if( $data ) {
			wfDebug( "Got profile polls for user {$this->user_id} from cache\n" );
			$polls = $data;
		} else {
			wfDebug( "Got profile polls for user {$this->user_id} from DB\n" );
			$dbr = wfGetDB( DB_SLAVE );
			$res = $dbr->select(
				array( 'poll_question', 'page' ),
				array(
					'page_title', 'UNIX_TIMESTAMP(poll_date) AS poll_date'
				),
				/* WHERE */array( 'poll_user_id' => $this->user_id ),
				__METHOD__,
				array( 'ORDER BY' => 'poll_id DESC', 'LIMIT' => 3 ),
				array( 'page' => array( 'INNER JOIN', 'page_id = poll_page_id' ) )
			);
			foreach( $res as $row ) {
				$polls[] = array(
					'title' => $row->page_title,
					'timestamp' => $row->poll_date
				);
			}
			$wgMemc->set( $key, $polls );
		}
		return $polls;
	}

	function sortItems( $x, $y ) {
		if ( $x['timestamp'] == $y['timestamp'] ) {
			return 0;
		} elseif ( $x['timestamp'] > $y['timestamp'] ) {
			return - 1;
		} else {
			return 1;
		}
	}

	function getProfileSection( $label, $value, $required = true ) {
		global $wgUser, $wgOut;

		$output = '';
		if ( $value || $required ) {
			if ( !$value ) {
				if ( $wgUser->getName() == $this->getTitle()->getText() ) {
					$value = wfMessage( 'profile-updated-personal' )->escaped();
				} else {
					$value = wfMessage( 'profile-not-provided' )->escaped();
				}
			}

			$value = $wgOut->parse( trim( $value ), false );

			$output = "<div><b>{$label}</b>{$value}</div>";
		}
		return $output;
	}

	function getPersonalInfo( $user_id, $user_name ) {
	    global $wgUser, $wgUserProfileDisplay, $property_name, $wgSocialProfileCustomFields;


		if ( $wgUserProfileDisplay['personal'] == false ) {
			return '';
		}


		$stats = new UserStats( $user_id, $user_name );
		$stats_data = $stats->getUserStats();
		$user_level = new UserLevel( $stats_data['points'] );
		$level_link = Title::makeTitle( NS_HELP, wfMessage( 'user-profile-userlevels-link' )->inContentLanguage()->text() );

		$this->initializeProfileData( $user_name );
		$profile_data = $this->profile_data;

		$defaultCountry = wfMessage( 'user-profile-default-country' )->inContentLanguage()->text();

		// Current location
		$location = $profile_data['location_city'] . ', ' . $profile_data['location_state'];
		if ( $profile_data['location_country'] != $defaultCountry ) {
			if ( $profile_data['location_city'] && $profile_data['location_state'] ) { // city AND state
				$location = $profile_data['location_city'] . ', ' .
							$profile_data['location_state'] . ', ' .
							$profile_data['location_country'];
			} elseif ( $profile_data['location_city'] && !$profile_data['location_state'] ) { // city, but no state
				$location = $profile_data['location_city'] . ', ' . $profile_data['location_country'];
			} elseif ( $profile_data['location_state'] && !$profile_data['location_city'] ) { // state, but no city
				$location = $profile_data['location_state'] . ', ' . $profile_data['location_country'];
			} else {
				$location = '';
				$location .= $profile_data['location_country'];
			}
		}

		if ( $location == ', ' ) {
			$location = '';
		}

		// Hometown
		$hometown = $profile_data['hometown_city'] . ', ' . $profile_data['hometown_state'];
		if ( $profile_data['hometown_country'] != $defaultCountry ) {
			if ( $profile_data['hometown_city'] && $profile_data['hometown_state'] ) { // city AND state
				$hometown = $profile_data['hometown_city'] . ', ' .
							$profile_data['hometown_state'] . ', ' .
							$profile_data['hometown_country'];
			} elseif ( $profile_data['hometown_city'] && !$profile_data['hometown_state'] ) { // city, but no state
				$hometown = $profile_data['hometown_city'] . ', ' . $profile_data['hometown_country'];
			} elseif ( $profile_data['hometown_state'] && !$profile_data['hometown_city'] ) { // state, but no city
				$hometown = $profile_data['hometown_state'] . ', ' . $profile_data['hometown_country'];
			} else {
				$hometown = '';
				$hometown .= $profile_data['hometown_country'];
			}
		}

		if ( $hometown == ', ' ) {
			$hometown = '';
		}

		$joined_data = $profile_data['real_name'] . $location . $hometown .
						$profile_data['birthday'] . $profile_data['occupation'] .
						$profile_data['websites'] . $profile_data['places_lived'] .
						$profile_data['schools'] . $profile_data['about'] .
						$profile_data['custom_1'] . $profile_data['custom_2'].
						$profile_data['custom_3'] . $profile_data['custom_4'].
						$profile_data['custom_5'] . $profile_data['custom_6'].
						$profile_data['custom_7'] . $profile_data['custom_8'];
// 						$profile_data['custom_9'] . $profile_data['custom_10'].
// 						$profile_data['custom_11'] . $profile_data['custom_12'].
// 		                $profile_data['custom_13'];

		$edit_info_link = SpecialPage::getTitleFor( 'UpdateProfile' );

		$output = '';




		if ( $joined_data ) {
			$output .= '<div class="user-section-heading">';

			$output .= '</div>
			<div class="visualClear"></div>
			<div class="profile-info-container">' .
				//$this->getProfileSection( wfMessage( 'user-personal-info-real-name' )->escaped(), $profile_data['real_name'], false ) .
				//$this->getProfileSection( wfMessage( 'user-personal-info-location' )->escaped(), $location, false ) .
				$this->getProfileSection( '<i class="fa fa-map-marker"></i>', $location, false ) .
				//$this->getProfileSection( wfMessage( 'user-personal-info-hometown' )->escaped(), $hometown, false ) .
				$this->getProfileSection( '<i class="fa fa-map-marker"></i>', $hometown , false ) .
				//$this->getProfileSection( wfMessage( 'user-personal-info-birthday' )->escaped(), $profile_data['birthday'], false ) .
				$this->getProfileSection( '<i class="fa fa-birthday-cake"></i>', $profile_data['birthday'], false ) .
				//$this->getProfileSection( wfMessage( 'user-personal-info-occupation' )->escaped(), $profile_data['occupation'], false ) .
				//$this->getProfileSection( '<i class="fa fa-suitcase"></i>', $profile_data['occupation'], false ) .
				//$this->getProfileSection( wfMessage( 'user-personal-info-websites' )->escaped(), $profile_data['websites'], false ) .
				$this->getProfileSection( '<i class="fa fa-globe"></i> ', $profile_data['websites'], false ) .
				$this->getProfileSection( '<i class="fa fa-globe"></i> ', $profile_data['custom_1'], false ) .
				$this->getProfileSection( '<i class="fa fa-globe"></i> ', $profile_data['custom_2'], false ) .
				$this->getProfileSection( '<i class="fa fa-linkedin-square "></i> ', $profile_data['custom_3'], false ) .
				$this->getProfileSection( '<i class="fa fa-facebook-square "></i> ', $profile_data['custom_4'], false ) .
				$this->getProfileSection( '<i class="fa fa-twitter-square "></i> ', $profile_data['custom_5'], false ) .
				$this->getProfileSection( '<i class="fa fa-google-plus-square "></i> ', $profile_data['custom_6'], false ) .
				$this->getProfileSection( '<i class="fa fa-slideshare "></i> ', $profile_data['custom_7'], false ) .
				$this->getProfileSection( '<i class="fa  fa-briefcase "></i> ', $profile_data['custom_8'], false ) .

				//$this->getProfileSection( wfMessage( 'user-personal-info-places-lived' )->escaped(), $profile_data['places_lived'], false ) .
				//$this->getProfileSection( wfMessage( 'user-personal-info-schools' )->escaped(), $profile_data['schools'], false ) .
				//$this->getProfileSection( '<i class="fa fa-graduation-cap"></i> ', $profile_data['schools'], false ) .
				'<div class="profile-user-info">' .
				$this->getProfileSection( wfMessage( 'user-personal-info-about-me' )->escaped() .'<br/>', $profile_data['about'], false ) .
				$this->getProfileSection( wfMessage( 'user-personal-info-skills' )->escaped() .'<br/>', $profile_data['occupation'], false ) .
				$this->getProfileSection( wfMessage( 'user-personal-front-labs' )->escaped() .'<br/>', $profile_data['schools'], false ) ;
// 				$this->getProfileSection( wfMessage( 'custom-info-field9' )->escaped() .'<br/>', $profile_data['custom_9'], false ) .
// 				$this->getProfileSection( wfMessage( 'custom-info-field10' )->escaped() .'<br/>', $profile_data['custom_10'], false ) .
// 				$this->getProfileSection( wfMessage( 'custom-info-field11' )->escaped() .'<br/>', $profile_data['custom_11'], false ) ;
				if($wgSocialProfileCustomFields){
				    $text = '';
				    $custom12Array = explode(",", $profile_data['custom_12']) ;
				    foreach ($custom12Array as $value) {
				        $text .= $value . '<br/>' ;
				    }

				    $output .= $this->getProfileSection(wfMessage( 'custom-info-field12' )->escaped(). '</br>' , $text, false) ;

				}


                if($property_name){
                    $custom13Display ='';
				// Decode le json pour afficher un tableau
                    if($profile_data['custom_13']){
    				    $custom13Decode = json_decode ($profile_data['custom_13'],TRUE);
    				    // Pour chaque élement du tableau on affiche la valeur puis un retour à la ligne
    				    $custom13Display .= '<ul class="custom13List">';
    				    foreach ($custom13Decode as $val){
    				        $custom13Display .= '<li>' . $val . '</li>' ;
    				    }
    				    $custom13Display .= '</ul>';
                    }
				    $output .= $this->getProfileSection(wfMessage( 'custom-info-field13' )->escaped(). '</br>' , $custom13Display, false) ;
                }
				$output .= '</div>' .

			'</div>';


		} elseif ( $wgUser->getName() == $user_name ) {
			$output .= '<div class="user-section-heading">
				<div class="user-section-title">' .
					wfMessage( 'user-personal-info-title' )->escaped() .
				'</div>
				<div class="user-section-actions">
					<div class="action-right">
						<a href="' . htmlspecialchars( $edit_info_link->getFullURL() ) . '">' .
							wfMessage( 'user-edit-this' )->escaped() .
						'</a>
					</div>
					<div class="visualClear"></div>
				</div>
			</div>
			<div class="visualClear"></div>
			<div class="no-info-container">' .
				wfMessage( 'user-no-personal-info' )->escaped() .
			'</div>';
		}

		return $output;
	}

	/**
	 * Get the custom info (site-specific stuff) for a given user.
	 *
	 * @param $user_name String: user name whose custom info we should fetch
	 * @return String: HTML
	 */
	function getCustomInfo( $user_name ) {
	    global $wgUser, $wgUserProfileDisplay, $wgSocialProfileCustomFields, $property_name;

		if ( $wgUserProfileDisplay['custom'] == false ) {
			return '';
		}

		$this->initializeProfileData( $user_name );

		$profile_data = $this->profile_data;

		$joined_data = $profile_data['custom_1'] . $profile_data['custom_2'] .
		$profile_data['custom_3'] . $profile_data['custom_4'] . $profile_data['custom_5'] .
		$profile_data['custom_6'] . $profile_data['custom_7'] . $profile_data['custom_8']
		. $profile_data['custom_9'] . $profile_data['custom_10'] . $profile_data['custom_11']
		. $profile_data['custom_12']. $profile_data['custom_13'];
		$edit_info_link = SpecialPage::getTitleFor( 'UpdateProfile' );

		$output = '';
		if ( $joined_data ) {
			$output .= '<div class="user-section-heading">
				<div class="user-section-title">' .
					wfMessage( 'custom-info-title' )->escaped() .
				'</div>
				<div class="user-section-actions">
					<div class="action-right">';
			if ( $wgUser->getName() == $user_name ) {
				$output .= '<a href="' . htmlspecialchars( $edit_info_link->getFullURL() ) . '/custom">' .
					wfMessage( 'user-edit-this' )->escaped() . '</a>';
			}
			$output .= '</div>
					<div class="visualClear"></div>
				</div>
			</div>
			<div class="visualClear"></div>
			<div class="profile-info-container">' .
				$this->getProfileSection( wfMessage( 'custom-info-field9' )->escaped(), $profile_data['custom_9'], false ) .
				$this->getProfileSection( wfMessage( 'custom-info-field10' )->escaped(), $profile_data['custom_10'], false ) .
				$this->getProfileSection( wfMessage( 'custom-info-field11' )->escaped(), $profile_data['custom_11'], false ) .

    			$custom12Array = explode(",", $profile_data['custom_12']) ;
    			$text = '';

    			foreach ($custom12Array as $value) {
    				 $text .= $value . '<br/>' ;
    			}
    			$output .= $this->getProfileSection(wfMessage( 'custom-info-field12' )->escaped(). '</br>' , $text, false) ;

// 				if($property_name){
				// Decode le json pour afficher un tableau
				$custom13Decode = json_decode ($profile_data['custom_13'],TRUE);
				$custom13Display ='';
				// Pour chaque élement du tableau on affiche la valeur puis un retour à la ligne
				foreach ($custom13Decode as $val){
				    $custom13Display .= $val . '<br/>' ;
				}

				$output .= $this->getProfileSection(wfMessage( 'custom-info-field13' )->escaped(). '</br>' , $custom13Display, false) ;

			} elseif ( $wgUser->getName() == $user_name ) {
			$output .= '<div class="user-section-heading">
				<div class="user-section-title">' .
					wfMessage( 'custom-info-title' )->escaped() .
				'</div>
				<div class="user-section-actions">
					<div class="action-right">
						<a href="' . htmlspecialchars( $edit_info_link->getFullURL() ) . '/custom">' .
							wfMessage( 'user-edit-this' )->escaped() .
						'</a>
					</div>
					<div class="visualClear"></div>
				</div>
			</div>
			<div class="visualClear"></div>
			<div class="no-info-container">' .
				wfMessage( 'custom-no-info' )->escaped() .
			'</div>';
		}
		$output .= '</div>' .

		  		'</div>';
		return $output;
	}

	/**
	 * Get the interests (favorite movies, TV shows, music, etc.) for a given
	 * user.
	 *
	 * @param $user_name String: user name whose interests we should fetch
	 * @return String: HTML
	 */
	function getInterests( $user_name ) {
		global $wgUser, $wgUserProfileDisplay;

		if ( $wgUserProfileDisplay['interests'] == false ) {
			return '';
		}

		$this->initializeProfileData( $user_name );

		$profile_data = $this->profile_data;
		$joined_data = $profile_data['movies'] . $profile_data['tv'] .
						$profile_data['music'] . $profile_data['books'] .
						$profile_data['video_games'] .
						$profile_data['magazines'] . $profile_data['drinks'] .
						$profile_data['snacks'];
		$edit_info_link = SpecialPage::getTitleFor( 'UpdateProfile' );

		$output = '';
		if ( $joined_data ) {
			$output .= '<div class="user-section-heading">
				<div class="user-section-title">' .
					wfMessage( 'other-info-title' )->escaped() .
				'</div>
				<div class="user-section-actions">
					<div class="action-right">';
			if ( $wgUser->getName() == $user_name ) {
				$output .= '<a href="' . htmlspecialchars( $edit_info_link->getFullURL() ) . '/personal">' .
					wfMessage( 'user-edit-this' )->escaped() . '</a>';
			}
			$output .= '</div>
					<div class="visualClear"></div>
				</div>
			</div>
			<div class="visualClear"></div>
			<div class="profile-info-container">' .
				$this->getProfileSection( wfMessage( 'other-info-movies' )->escaped(), $profile_data['movies'], false ) .
				$this->getProfileSection( wfMessage( 'other-info-tv' )->escaped(), $profile_data['tv'], false ) .
				$this->getProfileSection( wfMessage( 'other-info-music' )->escaped(), $profile_data['music'], false ) .
				$this->getProfileSection( wfMessage( 'other-info-books' )->escaped(), $profile_data['books'], false ) .
				$this->getProfileSection( wfMessage( 'other-info-video-games' )->escaped(), $profile_data['video_games'], false ) .
				$this->getProfileSection( wfMessage( 'other-info-magazines' )->escaped(), $profile_data['magazines'], false ) .
				$this->getProfileSection( wfMessage( 'other-info-snacks' )->escaped(), $profile_data['snacks'], false ) .
				$this->getProfileSection( wfMessage( 'other-info-drinks' )->escaped(), $profile_data['drinks'], false ) .
			'</div>';
		} elseif ( $this->isOwner() ) {
			$output .= '<div class="user-section-heading">
				<div class="user-section-title">' .
					wfMessage( 'other-info-title' )->escaped() .
				'</div>
				<div class="user-section-actions">
					<div class="action-right">
						<a href="' . htmlspecialchars( $edit_info_link->getFullURL() ) . '/personal">' .
							wfMessage( 'user-edit-this' )->escaped() .
						'</a>
					</div>
					<div class="visualClear"></div>
				</div>
			</div>
			<div class="visualClear"></div>
			<div class="no-info-container">' .
				wfMessage( 'other-no-info' )->escaped() .
			'</div>';
		}
		return $output;
	}

	/**
	 * Get left column Head social profile page, which includes the user's
	 * points and user level (if enabled in the site configuration) and lots
	 * more.
	 *
	 * @param $user_id Integer: user ID
	 * @param $user_name String: user name
	 */
	function getProfileLeftColHead( $user_id, $user_name ) {
		global $wgUser, $wgLang;
		global $wgUserLevels;
		global $wgUserProfileDisplay;

		$stats = new UserStats( $user_id, $user_name );
		$stats_data = $stats->getUserStats();
		$user_level = new UserLevel( $stats_data['points'] );
		$level_link = Title::makeTitle( NS_HELP, wfMessage( 'user-profile-userlevels-link' )->inContentLanguage()->text() );

		$this->initializeProfileData( $user_name );
		$profile_data = $this->profile_data;

		// Variables and other crap
		$page_title = $this->getTitle()->getText();
		$title_parts = explode( '/', $page_title );
		$user = $title_parts[0];
		$id = User::idFromName( $user );
		$user_safe = urlencode( $user );



		$avatar = new wAvatar( $this->user_id, 'l' );

		wfDebug( 'profile type: ' . $profile_data['user_page_type'] . "\n" );
		$output = '';


		if ( $this->isOwner() ) {
			$uploadAvatarUrl = htmlspecialchars( \SpecialPage::getTitleFor( 'UploadAvatar')->getFullURL() );
			$output .= '<div id="profile-image" class="owner-image">
					<div class="profile-image-edit-btn">
					<a href="'.$uploadAvatarUrl.'"><span class="glyphicon glyphicon-camera"></span></a>
					</div>
					' . $avatar->getAvatarURL() .
			'</div>';
		}
		else {
			$output .= '<div id="profile-image">' . $avatar->getAvatarURL() .
				'</div>';
		}

		$toggleUserPageActive = false;
		if ( $this->isOwner()  && $toggleUserPageActive) {
			$toggle_title = SpecialPage::getTitleFor( 'ToggleUserPage' );
			// Cast it to an int because PHP is stupid.
			if (
				(int) $profile_data['user_page_type'] == 1 ||
				$profile_data['user_page_type'] === ''
			)
			{
				$toggleMessage = wfMessage( 'user-type-toggle-old' )->escaped();
			} else {
				$toggleMessage = wfMessage( 'user-type-toggle-new' )->escaped();
			}
			$output .= '<div id="profile-toggle-button">
				<a href="' . htmlspecialchars( $toggle_title->getFullURL() ) . '" rel="nofollow">' .
					$toggleMessage . '</a>
			</div>';
		}

		$output .= '<div id="profile-right">';

		$output .= '<div id="profile-title-container">
				<div class="profile-name">' .
					htmlspecialchars($profile_data['real_name']).
				'</div>
				<div id="profile-title">' .
					$user_name .
				'</div>';
		// Show the user's level and the amount of points they have if
		// UserLevels has been configured
		if ( $wgUserLevels ) {
			$output .= '<div id="points-level">
					<a href="' . htmlspecialchars( $level_link->getFullURL() ) . '">' .
						wfMessage(
							'user-profile-points',
							$wgLang->formatNum( $stats_data['points'] )
						)->escaped() .
					'</a>
					</div>
					<div id="honorific-level">
						<a href="' . htmlspecialchars( $level_link->getFullURL() ) . '" rel="nofollow">(' . $user_level->getLevelName() . ')</a>
					</div>';
		}
		$output .= '<div class="visualClear"></div>
			</div>';


		//$output .= $this->getProfileAction();

		global $wgAutoloadClasses;

		if ($wgUserProfileDisplay['userswatchlist']) {
			$output .= UsersWatchButton::getUsersCounters($user);
		}

		$output .= $this->getPersonalInfo( $user_id, $user_name );

		$output .= $this->getGifts( $this->user_name );


		$output .= '</div>';

		return $output;
	}

	public function getProfileAction() {
		global $wgUser, $wgLang;
		global $wgUserLevels;

		// Variables and other crap
		$page_title = $this->getTitle()->getText();
		$title_parts = explode( '/', $page_title );
		$user = $title_parts[0];
		$id = User::idFromName( $user );
		$user_safe = urlencode( $user );


		if ( $id != 0 ) {
			$relationship = UserRelationship::getUserRelationshipByID( $id, $wgUser->getID() );
		}

		// Safe urls
		$add_relationship = SpecialPage::getTitleFor( 'AddRelationship' );
		$remove_relationship = SpecialPage::getTitleFor( 'RemoveRelationship' );
		$give_gift = SpecialPage::getTitleFor( 'GiveGift' );
		$send_board_blast = SpecialPage::getTitleFor( 'SendBoardBlast' );
		$update_profile = SpecialPage::getTitleFor( 'UpdateProfile' );
		$watchlist = SpecialPage::getTitleFor( 'Watchlist' );
		$send_message = SpecialPage::getTitleFor( 'UserBoard' );
		$upload_avatar = SpecialPage::getTitleFor( 'UploadAvatar' );
		$contributions = SpecialPage::getTitleFor( 'Contributions', $user );
		$user_page = Title::makeTitle( NS_USER, $user );
		$user_social_profile = Title::makeTitle( NS_USER_PROFILE, $user );
		$user_wiki = Title::makeTitle( NS_USER_WIKI, $user );

		$output = '<div class="profile-actions">';

		if ( $this->isOwner() ) {
			$output .= $wgLang->pipeList( array(
				'<a href="' . htmlspecialchars( $update_profile->getFullURL() ) . '">' . wfMessage( 'user-edit-profile' )->escaped() . '</a>',
				'<a href="' . htmlspecialchars( $upload_avatar->getFullURL() ) . '">' . wfMessage( 'user-upload-avatar' )->escaped() . '</a>',
				'<a href="' . htmlspecialchars( $watchlist->getFullURL() ) . '">' . wfMessage( 'user-watchlist' )->escaped() . '</a>',
				''
			) );
		} elseif ( $wgUser->isLoggedIn() ) {
			if ( $relationship == false ) {
				$output .= $wgLang->pipeList( array(
					'<a href="' . htmlspecialchars( $add_relationship->getFullURL( 'user=' . $user_safe . '&rel_type=1' ) ) . '" rel="nofollow">' . wfMessage( 'user-add-friend' )->escaped() . '</a>',
					'<a href="' . htmlspecialchars( $add_relationship->getFullURL( 'user=' . $user_safe . '&rel_type=2' ) ) . '" rel="nofollow">' . wfMessage( 'user-add-foe' )->escaped() . '</a>',
					''
				) );
			} else {
				if ( $relationship == 1 ) {
					$output .= $wgLang->pipeList( array(
						'<a href="' . htmlspecialchars( $remove_relationship->getFullURL( 'user=' . $user_safe ) ) . '">' . wfMessage( 'user-remove-friend' )->escaped() . '</a>',
						''
					) );
				}
				if ( $relationship == 2 ) {
					$output .= $wgLang->pipeList( array(
						'<a href="' . htmlspecialchars( $remove_relationship->getFullURL( 'user=' . $user_safe ) ) . '">' . wfMessage( 'user-remove-foe' )->escaped() . '</a>',
						''
					) );
				}
			}

			global $wgUserBoard;
			if ( $wgUserBoard ) {
				$output .= '<a href="' . htmlspecialchars( $send_message->getFullURL( 'user=' . $wgUser->getName() . '&conv=' . $user_safe ) ) . '" rel="nofollow">' .
					wfMessage( 'user-send-message' )->escaped() . '</a>';
				$output .= wfMessage( 'pipe-separator' )->escaped();
			}
			$output .= '<a href="' . htmlspecialchars( $give_gift->getFullURL( 'user=' . $user_safe ) ) . '" rel="nofollow">' .
				wfMessage( 'user-send-gift' )->escaped() . '</a>';
			$output .= wfMessage( 'pipe-separator' )->escaped();

		}


		$output .= '<a href="' . htmlspecialchars( $contributions->getFullURL() ) . '" rel="nofollow">' . wfMessage( 'user-contributions' )->escaped() . '</a> ';

		// Links to User:user_name from User_profile:
		if ( $this->getTitle()->getNamespace() == NS_USER_PROFILE && $this->profile_data['user_id'] && $this->profile_data['user_page_type'] == 0 ) {
			$output .= '| <a href="' . htmlspecialchars( $user_page->getFullURL() ) . '" rel="nofollow">' .
				wfMessage( 'user-page-link' )->escaped() . '</a> ';
		}

		// Links to User:user_name from User_profile:
		if ( $this->getTitle()->getNamespace() == NS_USER && $this->profile_data['user_id'] && $this->profile_data['user_page_type'] == 0 ) {
			$output .= '| <a href="' . htmlspecialchars( $user_social_profile->getFullURL() ) . '" rel="nofollow">' .
				wfMessage( 'user-social-profile-link' )->escaped() . '</a> ';
		}

		if ( $this->getTitle()->getNamespace() == NS_USER && ( !$this->profile_data['user_id'] || $this->profile_data['user_page_type'] == 1 ) ) {
			$output .= '| <a href="' . htmlspecialchars( $user_wiki->getFullURL() ) . '" rel="nofollow">' .
				wfMessage( 'user-wiki-link' )->escaped() . '</a>';
		}

		$output .= '</div>';

		return $output;
	}

	/**
	 * This is currently unused, seems to be a leftover from the ArmchairGM
	 * days.
	 *
	 * @param $user_name String: user name
	 * @return String: HTML
	 */
	function getProfileImage( $user_name ) {
		global $wgUser;

		$avatar = new wAvatar( $this->user_id, 'l' );
		$avatarTitle = SpecialPage::getTitleFor( 'UploadAvatar' );

		$output = '<div class="profile-image">';
		if ( $wgUser->getName() == $this->user_name ) {
			if ( strpos( $avatar->getAvatarImage(), 'default_' ) != false ) {
				$caption = 'upload image';
			} else {
				$caption = 'new image';
			}
			$output .= '<a href="' . htmlspecialchars( $avatarTitle->getFullURL() ) . '" rel="nofollow">' .
						$avatar->getAvatarURL() . '<br />
					(' . $caption . ')
				</a>';
		} else {
			$output .= $avatar->getAvatarURL();
		}
		$output .= '</div>';

		return $output;
	}

	/**
	 * Get the relationships for a given user.
	 *
	 * @param $user_name String: name of the user whose relationships we want
	 *                           to fetch
	 * @param $rel_type Integer: 1 for friends, 2 (or anything else than 1) for
	 *                           foes
	 */
	function getRelationships( $user_name, $rel_type ) {
		global $wgMemc, $wgUser, $wgUserProfileDisplay, $wgLang;

		// If not enabled in site settings, don't display
		if ( $rel_type == 1 ) {
			if ( $wgUserProfileDisplay['friends'] == false ) {
				return '';
			}
		} else {
			if ( $wgUserProfileDisplay['foes'] == false ) {
				return '';
			}
		}

		$output = ''; // Prevent E_NOTICE

		$count = 4;
		$rel = new UserRelationship( $user_name );
		$key = wfMemcKey( 'relationship', 'profile', "{$rel->user_id}-{$rel_type}" );
		$data = $wgMemc->get( $key );

		// Try cache
		if ( !$data ) {
			$friends = $rel->getRelationshipList( $rel_type, $count );
			$wgMemc->set( $key, $friends );
		} else {
			wfDebug( "Got profile relationship type {$rel_type} for user {$user_name} from cache\n" );
			$friends = $data;
		}

		$stats = new UserStats( $rel->user_id, $user_name );
		$stats_data = $stats->getUserStats();
		$view_all_title = SpecialPage::getTitleFor( 'ViewRelationships' );

		if ( $rel_type == 1 ) {
			$relationship_count = $stats_data['friend_count'];
			$relationship_title = wfMessage( 'user-friends-title' )->escaped();
		} else {
			$relationship_count = $stats_data['foe_count'];
			$relationship_title = wfMessage( 'user-foes-title' )->escaped();
		}

		if ( count( $friends ) > 0 ) {
			$x = 1;
			$per_row = 4;

			$output .= '<div class="user-section-heading">
				<div class="user-section-title">' . $relationship_title . '</div>
				<div class="user-section-actions">
					<div class="action-right">';
			if ( intval( $relationship_count ) > 4 ) {
				$output .= '<a href="' . htmlspecialchars( $view_all_title->getFullURL( 'user=' . $user_name . '&rel_type=' . $rel_type ) ) .
					'" rel="nofollow">' . wfMessage( 'user-view-all' )->escaped() . '</a>';
			}
			$output .= '</div>
					<div class="action-left">';
			if ( intval( $relationship_count ) > 4 ) {
				$output .= wfMessage( 'user-count-separator', $per_row, $relationship_count )->escaped();
			} else {
				$output .= wfMessage( 'user-count-separator', $relationship_count, $relationship_count )->escaped();
			}
			$output .= '</div>
				</div>
				<div class="visualClear"></div>
			</div>
			<div class="visualClear"></div>
			<div class="user-relationship-container">';

			foreach ( $friends as $friend ) {
				$user = Title::makeTitle( NS_USER, $friend['user_name'] );
				$avatar = new wAvatar( $friend['user_id'], 'ml' );

				// Chop down username that gets displayed
				$user_name = $wgLang->truncate( $friend['user_name'], 9, '..' );

				$output .= "<a href=\"" . htmlspecialchars( $user->getFullURL() ) . "\" title=\"{$friend['user_name']}\" rel=\"nofollow\">
					{$avatar->getAvatarURL()}<br />
					{$user_name}
				</a>";

				if ( $x == count( $friends ) || $x != 1 && $x % $per_row == 0 ) {
					$output .= '<div class="visualClear"></div>';
				}

				$x++;
			}

			$output .= '</div>';
		}

		return $output;
	}

	/**
	 * Gets the recent social activity for a given user.
	 *
	 * @param $user_name String: name of the user whose activity we want to fetch
	 */
	function getActivity( $user_name ) {
		global $wgUser, $wgUserProfileDisplay, $wgExtensionAssetsPath, $wgUploadPath;

		// If not enabled in site settings, don't display
		if ( $wgUserProfileDisplay['activity'] == false ) {
			return '';
		}

		$output = '';

		$limit = 8;
		$rel = new UserActivity( $user_name, 'user', $limit );
		$rel->setActivityToggle( 'show_votes', 0 );
		$rel->setActivityToggle( 'show_gifts_sent', 1 );

		/**
		 * Get all relationship activity
		 */
		$activity = $rel->getActivityList();

		if ( $activity ) {
			$output .= '<div class="user-section-heading">
				<div class="user-section-title">' .
					wfMessage( 'user-recent-activity-title' )->escaped() .
				'</div>
				<div class="user-section-actions">
					<div class="action-right">
					</div>
					<div class="visualClear"></div>
				</div>
			</div>
			<div class="visualClear"></div>';

			$x = 1;

			if ( count( $activity ) < $limit ) {
				$style_limit = count( $activity );
			} else {
				$style_limit = $limit;
			}

			foreach ( $activity as $item ) {
				$item_html = '';
				$title = Title::makeTitle( $item['namespace'], $item['pagetitle'] );
				$user_title = Title::makeTitle( NS_USER, $item['username'] );
				$user_title_2 = Title::makeTitle( NS_USER, $item['comment'] );

				if ( $user_title_2 ) {
					$user_link_2 = '<a href="' . htmlspecialchars( $user_title_2->getFullURL() ) .
						'" rel="nofollow">' . $item['comment'] . '</a>';
				}

				$comment_url = '';
				if ( $item['type'] == 'comment' ) {
					$comment_url = "#comment-{$item['id']}";
				}

				$page_link = '<b><a href="' . htmlspecialchars( $title->getFullURL() ) .
					"{$comment_url}\">" . $title->getPrefixedText() . '</a></b> ';
				$b = new UserBoard(); // Easier than porting the time-related functions here
				$item_time = '<span class="item-small">' .
					wfMessage( 'user-time-ago', $b->getTimeAgo( $item['timestamp'] ) )->escaped() .
				'</span>';

				if ( $x < $style_limit ) {
					$item_html .= '<div class="activity-item">
						<img src="' . $wgExtensionAssetsPath . '/SocialProfile/images/' .
							UserActivity::getTypeIcon( $item['type'] ) . '" alt="" border="0" />';
				} else {
					$item_html .= '<div class="activity-item-bottom">
						<img src="' . $wgExtensionAssetsPath . '/SocialProfile/images/' .
							UserActivity::getTypeIcon( $item['type'] ) . '" alt="" border="0" />';
				}

				$viewGift = SpecialPage::getTitleFor( 'ViewGift' );

				switch( $item['type'] ) {
					case 'edit':
						$item_html .= wfMessage( 'user-recent-activity-edit' )->escaped() . " {$page_link} {$item_time}
							<div class=\"item\">";
						if ( $item['comment'] ) {
							$item_html .= "\"{$item['comment']}\"";
						}
						$item_html .= '</div>';
						break;
					case 'vote':
						$item_html .= wfMessage( 'user-recent-activity-vote' )->escaped() . " {$page_link} {$item_time}";
						break;
					case 'comment':
						$item_html .= wfMessage( 'user-recent-activity-comment' )->escaped() . " {$page_link} {$item_time}
							<div class=\"item\">
								\"{$item['comment']}\"
							</div>";
						break;
					case 'gift-sent':
						$gift_image = "<img src=\"{$wgUploadPath}/awards/" .
							Gifts::getGiftImage( $item['namespace'], 'm' ) .
							'" border="0" alt="" />';
						$item_html .= wfMessage( 'user-recent-activity-gift-sent' )->escaped() . " {$user_link_2} {$item_time}
						<div class=\"item\">
							<a href=\"" . htmlspecialchars( $viewGift->getFullURL( "gift_id={$item['id']}" ) ) . "\" rel=\"nofollow\">
								{$gift_image}
								{$item['pagetitle']}
							</a>
						</div>";
						break;
					case 'gift-rec':
						$gift_image = "<img src=\"{$wgUploadPath}/awards/" .
							Gifts::getGiftImage( $item['namespace'], 'm' ) .
							'" border="0" alt="" />';
						$item_html .= wfMessage( 'user-recent-activity-gift-rec' )->escaped() . " {$user_link_2} {$item_time}</span>
								<div class=\"item\">
									<a href=\"" . htmlspecialchars( $viewGift->getFullURL( "gift_id={$item['id']}" ) ) . "\" rel=\"nofollow\">
										{$gift_image}
										{$item['pagetitle']}
									</a>
								</div>";
						break;
					case 'system_gift':
						$gift_image = "<img src=\"{$wgUploadPath}/awards/" .
							SystemGifts::getGiftImage( $item['namespace'], 'm' ) .
							'" border="0" alt="" />';
						$viewSystemGift = SpecialPage::getTitleFor( 'ViewSystemGift' );
						$item_html .= wfMessage( 'user-recent-system-gift' )->escaped() . " {$item_time}
								<div class=\"user-home-item-gift\">
									<a href=\"" . htmlspecialchars( $viewSystemGift->getFullURL( "gift_id={$item['id']}" ) ) . "\" rel=\"nofollow\">
										{$gift_image}
										{$item['pagetitle']}
									</a>
								</div>";
						break;
					case 'friend':
						$item_html .= wfMessage( 'user-recent-activity-friend' )->escaped() .
							" <b>{$user_link_2}</b> {$item_time}";
						break;
					case 'foe':
						$item_html .= wfMessage( 'user-recent-activity-foe' )->escaped() .
							" <b>{$user_link_2}</b> {$item_time}";
						break;
					case 'system_message':
						$item_html .= "{$item['comment']} {$item_time}";
						break;
					case 'user_message':
						$item_html .= wfMessage( 'user-recent-activity-user-message' )->escaped() .
							" <b><a href=\"" . UserBoard::getUserBoardURL( $user_title_2->getText() ) .
								"\" rel=\"nofollow\">{$item['comment']}</a></b>  {$item_time}
								<div class=\"item\">
								\"{$item['namespace']}\"
								</div>";
						break;
					case 'network_update':
						$network_image = SportsTeams::getLogo( $item['sport_id'], $item['team_id'], 's' );
						$item_html .= wfMessage( 'user-recent-activity-network-update' )->escaped() .
								'<div class="item">
									<a href="' . SportsTeams::getNetworkURL( $item['sport_id'], $item['team_id'] ) .
									"\" rel=\"nofollow\">{$network_image} \"{$item['comment']}\"</a>
								</div>";
						break;
					}

					$item_html .= '</div>';

					if ( $x <= $limit ) {
						$items_html_type['all'][] = $item_html;
					}
					$items_html_type[$item['type']][] = $item_html;

				$x++;
			}

			$by_type = '';
			foreach ( $items_html_type['all'] as $item ) {
				$by_type .= $item;
			}
			$output .= "<div id=\"recent-all\">$by_type</div>";
		}

		return $output;
	}

	function getGifts( $user_name ) {
		global $wgUser, $wgMemc, $wgUserProfileDisplay, $wgUploadPath;

		// If not enabled in site settings, don't display
		if ( $wgUserProfileDisplay['gifts'] == false ) {
			return '';
		}

		$output = '';

		// User to user gifts
		$g = new UserGifts( $user_name );
		$user_safe = urlencode( $user_name );

		// Try cache
		$key = wfMemcKey( 'user', 'profile', 'gifts', "{$g->user_id}" );
		$data = $wgMemc->get( $key );

		if ( !$data ) {
			wfDebug( "Got profile gifts for user {$user_name} from DB\n" );
			$gifts = $g->getUserGiftList( 0, 4 );
			$wgMemc->set( $key, $gifts, 60 * 60 * 4 );
		} else {
			wfDebug( "Got profile gifts for user {$user_name} from cache\n" );
			$gifts = $data;
		}

		$gift_count = $g->getGiftCountByUsername( $user_name );
		$gift_link = SpecialPage::getTitleFor( 'ViewGifts' );
		$per_row = 4;

		if ( $gifts ) {
			$output .= '<div class="user-section-heading-gift"><div class="user-section-heading">
				<hr>
				<div class="user-section-actions">
					<div class="action-right">';
			if ( $gift_count > 4 ) {
				$output .= '<a href="' . htmlspecialchars( $gift_link->getFullURL( 'user=' . $user_safe ) ) . '" rel="nofollow">' .
					wfMessage( 'user-view-all' )->escaped() . '</a>';
			}
			$output .= '</div>
					<div class="action-left">';
			if ( $gift_count > 4 ) {
				$output .= wfMessage( 'user-count-separator', '4', $gift_count )->escaped();
			} else {
				$output .= wfMessage( 'user-count-separator', $gift_count, $gift_count )->escaped();
			}
			$output .= '</div>
					<div class="visualClear"></div>
				</div>
			</div></div>
			<div class="visualClear"></div>
			<div class="user-gift-container"><div class="row">';

			$x = 1;

			foreach ( $gifts as $gift ) {
				if ( $gift['status'] == 1 && $user_name == $wgUser->getName() ) {
					$g->clearUserGiftStatus( $gift['id'] );
					$wgMemc->delete( $key );
					$g->decNewGiftCount( $wgUser->getID() );
				}

				$user = Title::makeTitle( NS_USER, $gift['user_name_from'] );
				$gift_image = '<img src="' . $wgUploadPath . '/awards/' .
					Gifts::getGiftImage( $gift['gift_id'], 'ml' ) .
					'" border="0" alt="" />';
				$gift_link = $user = SpecialPage::getTitleFor( 'ViewGift' );
				$class = '';
				if ( $gift['status'] == 1 ) {
					$class = 'class="user-page-new"';
				}
				$output .= '<div class="col-md-3 col-sm-3 col-xs-3"><a href="' . htmlspecialchars( $gift_link->getFullURL( 'gift_id=' . $gift['id'] ) ) . '" ' .
					$class . " rel=\"nofollow\">{$gift_image}</a></div>";
				if ( $x == count( $gifts ) || $x != 1 && $x % $per_row == 0 ) {
					$output .= '<div class="visualClear"></div></div>';
				}

				$x++;
			}

			$output .= '</div>';
		}

		return $output;
	}

	function getAwards( $user_name ) {
		global $wgUser, $wgMemc, $wgUserProfileDisplay, $wgUploadPath;

		// If not enabled in site settings, don't display
		if ( $wgUserProfileDisplay['awards'] == false ) {
			return '';
		}

		$output = '';

		// System gifts
		$sg = new UserSystemGifts( $user_name );

		// Try cache
		$sg_key = wfMemcKey( 'user', 'profile', 'system_gifts', "{$sg->user_id}" );
		$data = $wgMemc->get( $sg_key );
		if ( !$data ) {
			wfDebug( "Got profile awards for user {$user_name} from DB\n" );
			$system_gifts = $sg->getUserGiftList( 0, 4 );
			$wgMemc->set( $sg_key, $system_gifts, 60 * 60 * 4 );
		} else {
			wfDebug( "Got profile awards for user {$user_name} from cache\n" );
			$system_gifts = $data;
		}

		$system_gift_count = $sg->getGiftCountByUsername( $user_name );
		$system_gift_link = SpecialPage::getTitleFor( 'ViewSystemGifts' );
		$per_row = 4;

		if ( $system_gifts ) {
			$x = 1;

			$output .= '<div class="user-section-heading">
				<div class="user-section-title">' .
					wfMessage( 'user-awards-title' )->escaped() .
				'</div>
				<div class="user-section-actions">
					<div class="action-right">';
			if ( $system_gift_count > 4 ) {
				$output .= '<a href="' . htmlspecialchars( $system_gift_link->getFullURL( 'user=' . $user_name ) ) . '" rel="nofollow">' .
					wfMessage( 'user-view-all' )->escaped() . '</a>';
			}
			$output .= '</div>
					<div class="action-left">';
			if ( $system_gift_count > 4 ) {
				$output .= wfMessage( 'user-count-separator', '4', $system_gift_count )->escaped();
			} else {
				$output .= wfMessage( 'user-count-separator', $system_gift_count, $system_gift_count )->escaped();
			}
			$output .= '</div>
					<div class="visualClear"></div>
				</div>
			</div>
			<div class="visualClear"></div>
			<div class="user-gift-container">';

			foreach ( $system_gifts as $gift ) {
				if ( $gift['status'] == 1 && $user_name == $wgUser->getName() ) {
					$sg->clearUserGiftStatus( $gift['id'] );
					$wgMemc->delete( $sg_key );
					$sg->decNewSystemGiftCount( $wgUser->getID() );
				}

				$gift_image = '<img src="' . $wgUploadPath . '/awards/' .
					SystemGifts::getGiftImage( $gift['gift_id'], 'ml' ) .
					'" border="0" alt="" />';
				$gift_link = $user = SpecialPage::getTitleFor( 'ViewSystemGift' );

				$class = '';
				if ( $gift['status'] == 1 ) {
					$class = 'class="user-page-new"';
				}
				$output .= '<a href="' . htmlspecialchars( $gift_link->getFullURL( 'gift_id=' . $gift['id'] ) ) .
					'" ' . $class . " rel=\"nofollow\">
					{$gift_image}
				</a>";

				if ( $x == count( $system_gifts ) || $x != 1 && $x % $per_row == 0 ) {
					$output .= '<div class="visualClear"></div>';
				}
				$x++;
			}

			$output .= '</div>';
		}

		return $output;
	}

	/**
	 * Get the user board for a given user.
	 *
	 * @param $user_id Integer: user's ID number
	 * @param $user_name String: user name
	 */
	function getUserBoard( $user_id, $user_name ) {
		global $wgUser, $wgOut, $wgUserProfileDisplay;

		// Anonymous users cannot have user boards
		if ( $user_id == 0 ) {
			return '';
		}

		// Don't display anything if user board on social profiles isn't
		// enabled in site configuration
		if ( $wgUserProfileDisplay['board'] == false ) {
			return '';
		}

		$output = ''; // Prevent E_NOTICE

		// Add JS
		$wgOut->addModules( 'ext.socialprofile.userprofile.js' );

		$rel = new UserRelationship( $user_name );
		$friends = $rel->getRelationshipList( 1, 4 );

		$stats = new UserStats( $user_id, $user_name );
		$stats_data = $stats->getUserStats();
		$total = $stats_data['user_board'];

		// If the user is viewing their own profile or is allowed to delete
		// board messages, add the amount of private messages to the total
		// sum of board messages.
		if ( $wgUser->getName() == $user_name || $wgUser->isAllowed( 'userboard-delete' ) ) {
			$total = $total + $stats_data['user_board_priv'];
		}

		$output .= '<div class="user-section-heading">
			<div class="user-section-title">' .
				wfMessage( 'user-board-title' )->escaped() .
			'</div>
			<div class="user-section-actions">
				<div class="action-right">';
		if ( $wgUser->getName() == $user_name ) {
			if ( $friends ) {
				$output .= '<a href="' . UserBoard::getBoardBlastURL() . '">' .
					wfMessage( 'user-send-board-blast' )->escaped() . '</a>';
			}
			if ( $total > 10 ) {
				$output .= wfMessage( 'pipe-separator' )->escaped();
			}
		}
		if ( $total > 10 ) {
			$output .= '<a href="' . UserBoard::getUserBoardURL( $user_name ) . '">' .
				wfMessage( 'user-view-all' )->escaped() . '</a>';
		}
		$output .= '</div>
				<div class="action-left">';
		if ( $total > 10 ) {
			$output .= wfMessage( 'user-count-separator', '10', $total )->escaped();
		} elseif ( $total > 0 ) {
			$output .= wfMessage( 'user-count-separator', $total, $total )->escaped();
		}
		$output .= '</div>
				<div class="visualClear"></div>
			</div>
		</div>
		<div class="visualClear"></div>';

		if ( $wgUser->getName() !== $user_name ) {
			if ( $wgUser->isLoggedIn() && !$wgUser->isBlocked() ) {
				$output .= '<div class="user-page-message-form">
						<input type="hidden" id="user_name_to" name="user_name_to" value="' . addslashes( $user_name ) . '" />
						<span class="profile-board-message-type">' .
							wfMessage( 'userboard_messagetype' )->escaped() .
						'</span>
						<select id="message_type">
							<option value="0">' .
								wfMessage( 'userboard_public' )->escaped() .
							'</option>
							<option value="1">' .
								wfMessage( 'userboard_private' )->escaped() .
							'</option>
						</select><p>
						<textarea name="message" id="message" cols="43" rows="4"></textarea>
						<div class="user-page-message-box-button">
							<input type="button" value="' . wfMessage( 'userboard_sendbutton' )->escaped() . '" class="site-button" />
						</div>
					</div>';
			} else {
				$login_link = SpecialPage::getTitleFor( 'Userlogin' );
				$output .= '<div class="user-page-message-form">' .
					wfMessage( 'user-board-login-message', $login_link->getFullURL() )->text() .
				'</div>';
			}
		}

		$output .= '<div id="user-page-board">';
		$b = new UserBoard();
		$output .= $b->displayMessages( $user_id, 0, 10 );
		$output .= '</div>';

		return $output;
	}

	/**
	 * Gets the user's fanboxes if $wgEnableUserBoxes = true; and
	 * $wgUserProfileDisplay['userboxes'] = true; and the FanBoxes extension is
	 * installed.
	 *
	 * @param $user_name String: user name
	 * @return String: HTML
	 */
	function getFanBoxes( $user_name ) {
		global $wgOut, $wgUser, $wgMemc, $wgUserProfileDisplay, $wgEnableUserBoxes;

		if ( !$wgEnableUserBoxes || $wgUserProfileDisplay['userboxes'] == false ) {
			return '';
		}

		// Add CSS & JS
		$wgOut->addModules( 'ext.fanBoxes' );

		$output = '';
		$f = new UserFanBoxes( $user_name );

		// Try cache
		/*
		$key = wfMemcKey( 'user', 'profile', 'fanboxes', "{$f->user_id}" );
		$data = $wgMemc->get( $key );

		if ( !$data ) {
			wfDebug( "Got profile fanboxes for user {$user_name} from DB\n" );
			$fanboxes = $f->getUserFanboxes( 0, 10 );
			$wgMemc->set( $key, $fanboxes );
		} else {
			wfDebug( "Got profile fanboxes for user {$user_name} from cache\n" );
			$fanboxes = $data;
		}
		*/

		$fanboxes = $f->getUserFanboxes( 0, 10 );

		$fanbox_count = $f->getFanBoxCountByUsername( $user_name );
		$fanbox_link = SpecialPage::getTitleFor( 'ViewUserBoxes' );
		$per_row = 1;

		if ( $fanboxes ) {
			$output .= '<div class="user-section-heading">
				<div class="user-section-title">' .
					wfMessage( 'user-fanbox-title' )->plain() .
				'</div>
				<div class="user-section-actions">
					<div class="action-right">';
			// If there are more than ten fanboxes, display a "View all" link
			// instead of listing them all on the profile page
			if ( $fanbox_count > 10 ) {
				$output .= Linker::link(
					$fanbox_link,
					wfMessage( 'user-view-all' )->plain(),
					array(),
					array( 'user' => $user_name )
				);
			}
			$output .= '</div>
					<div class="action-left">';
			if ( $fanbox_count > 10 ) {
				$output .= wfMessage( 'user-count-separator' )->numParams( 10, $fanbox_count )->parse();
			} else {
				$output .= wfMessage( 'user-count-separator' )->numParams( $fanbox_count, $fanbox_count )->parse();
			}
			$output .= '</div>
					<div class="visualClear"></div>

				</div>
			</div>
			<div class="visualClear"></div>

			<div class="user-fanbox-container clearfix">';

			$x = 1;
			$tagParser = new Parser();
			foreach ( $fanboxes as $fanbox ) {
				$check_user_fanbox = $f->checkIfUserHasFanbox( $fanbox['fantag_id'] );

				if ( $fanbox['fantag_image_name'] ) {
					$fantag_image_width = 45;
					$fantag_image_height = 53;
					$fantag_image = wfFindFile( $fanbox['fantag_image_name'] );
					$fantag_image_url = '';
					if ( is_object( $fantag_image ) ) {
						$fantag_image_url = $fantag_image->createThumb(
							$fantag_image_width,
							$fantag_image_height
						);
					}
					$fantag_image_tag = '<img alt="" src="' . $fantag_image_url . '" />';
				}

				if ( $fanbox['fantag_left_text'] == '' ) {
					$fantag_leftside = $fantag_image_tag;
				} else {
					$fantag_leftside = $fanbox['fantag_left_text'];
					$fantag_leftside = $tagParser->parse(
						$fantag_leftside, $this->getTitle(),
						$wgOut->parserOptions(), false
					);
					$fantag_leftside = $fantag_leftside->getText();
				}

				$leftfontsize = '10px';
				$rightfontsize = '11px';
				if ( $fanbox['fantag_left_textsize'] == 'mediumfont' ) {
					$leftfontsize = '11px';
				}

				if ( $fanbox['fantag_left_textsize'] == 'bigfont' ) {
					$leftfontsize = '15px';
				}

				if ( $fanbox['fantag_right_textsize'] == 'smallfont' ) {
					$rightfontsize = '10px';
				}

				if ( $fanbox['fantag_right_textsize'] == 'mediumfont' ) {
					$rightfontsize = '11px';
				}

				// Get permalink
				$fantag_title = Title::makeTitle( NS_FANTAG, $fanbox['fantag_title'] );
				$right_text = $fanbox['fantag_right_text'];
				$right_text = $tagParser->parse(
					$right_text, $this->getTitle(), $wgOut->parserOptions(), false
				);
				$right_text = $right_text->getText();

				// Output fanboxes
				$output .= "<div class=\"fanbox-item\">
					<div class=\"individual-fanbox\" id=\"individualFanbox" . $fanbox['fantag_id'] . "\">
						<div class=\"show-message-container-profile\" id=\"show-message-container" . $fanbox['fantag_id'] . "\">
							<a class=\"perma\" style=\"font-size:8px; color:" . $fanbox['fantag_right_textcolor'] . "\" href=\"" . htmlspecialchars( $fantag_title->getFullURL() ) . "\" title=\"{$fanbox['fantag_title']}\">" . wfMessage( 'fanbox-perma' )->plain() . "</a>
							<table class=\"fanBoxTableProfile\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\">
								<tr>
									<td id=\"fanBoxLeftSideOutputProfile\" style=\"color:" . $fanbox['fantag_left_textcolor'] . "; font-size:$leftfontsize\" bgcolor=\"" . $fanbox['fantag_left_bgcolor'] . "\">" . $fantag_leftside . "</td>
									<td id=\"fanBoxRightSideOutputProfile\" style=\"color:" . $fanbox['fantag_right_textcolor'] . "; font-size:$rightfontsize\" bgcolor=\"" . $fanbox['fantag_right_bgcolor'] . "\">" . $right_text . "</td>
								</tr>
							</table>
						</div>
					</div>";

				if ( $wgUser->isLoggedIn() ) {
					if ( $check_user_fanbox == 0 ) {
						$output .= '<div class="fanbox-pop-up-box-profile" id="fanboxPopUpBox' . $fanbox['fantag_id'] . '">
							<table cellpadding="0" cellspacing="0" align="center">
								<tr>
									<td style="font-size:10px">' .
										wfMessage( 'fanbox-add-fanbox' )->plain() .
									'</td>
								</tr>
								<tr>
									<td align="center">
										<input type="button" class="fanbox-add-button-half" value="' . wfMessage( 'fanbox-add' )->plain() . '" size="10" />
										<input type="button" class="fanbox-cancel-button" value="' . wfMessage( 'cancel' )->plain() . '" size="10" />
									</td>
								</tr>
							</table>
						</div>';
					} else {
						$output .= '<div class="fanbox-pop-up-box-profile" id="fanboxPopUpBox' . $fanbox['fantag_id'] . '">
							<table cellpadding="0" cellspacing="0" align="center">
								<tr>
									<td style="font-size:10px">' .
										wfMessage( 'fanbox-remove-fanbox' )->plain() .
									'</td>
								</tr>
								<tr>
									<td align="center">
										<input type="button" class="fanbox-remove-button-half" value="' . wfMessage( 'fanbox-remove' )->plain() . '" size="10" />
										<input type="button" class="fanbox-cancel-button" value="' . wfMessage( 'cancel' )->plain() . '" size="10" />
									</td>
								</tr>
							</table>
						</div>';
					}
				}

				// Show a message to anonymous users, prompting them to log in
				if ( $wgUser->getID() == 0 ) {
					$output .= '<div class="fanbox-pop-up-box-profile" id="fanboxPopUpBox' . $fanbox['fantag_id'] . '">
						<table cellpadding="0" cellspacing="0" align="center">
							<tr>
								<td style="font-size:10px">' .
									wfMessage( 'fanbox-add-fanbox-login' )->parse() .
								'</td>
							</tr>
							<tr>
								<td align="center">
									<input type="button" class="fanbox-cancel-button" value="' . wfMessage( 'cancel' )->plain() . '" size="10" />
								</td>
							</tr>
						</table>
					</div>';
				}

				$output .= '</div>';

				$x++;
			}

			$output .= '</div>';
		}

		return $output;
	}

	/**
	 * Initialize UserProfile data for the given user if that hasn't been done
	 * already.
	 *
	 * @param $username String: name of the user whose profile data to initialize
	 */
	private function initializeProfileData( $username ) {
		if ( !$this->profile_data ) {
			$profile = new UserProfile( $username );
			$this->profile_data = $profile->getProfile();
		}
	}
}
