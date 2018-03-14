<?php
class UserInfo {
    // Register any render callbacks with the parser
    public static function onParserSetup( &$parser ) {

        // Create a function hook associating the "example" magic word with renderExample()
        $parser->setFunctionHook( 'userInfo', 'UserInfo::renderUserInfo' );
        $parser->setFunctionHook( 'userInfoChecked', 'UserInfo::renderUserInfoChecked' );

    }

    // First row with user informations
    public static function renderUserInfo( $parser) {

    	$parser->disableCache();

        global $wgUser, $wgUserProfileDisplay;
        $avatar = new wAvatar( $wgUser -> getId(), 'm' );

        $userRealName = $wgUser -> getRealName();
        $userName = $wgUser->getName();

        $pageEditProfile = SpecialPage::getTitleFor( 'UpdateProfile' );
        $linkToUpdateProfile = '<div class="UpdateProfileLink"><a href="'.$pageEditProfile->getFullURL().'"><i class="fa fa-edit"></i></a></div>';

        $linkToUserProfile = $wgUser->getUserPage()->getFullURL();

        //Get the "about" section of user
        $profile = new UserProfile($userName);
        $profile_data = $profile->getProfile();

        // Check if User is connected if it's not --> return nothing
        if ( $wgUser->getID() == 0) {
            return false;
        }
        $out = '<div class="col-lg-3">
                    <div class="UserInfo">
                    <div class="UserInfoAvatar"><a href='.$linkToUserProfile.'>' . $avatar .'</a></div> '
                        .$linkToUpdateProfile .'
                    <div class="UserInfoProfile">
                        <div class="UserInfoUserName"> <a href="'. $linkToUserProfile .'">' . $userRealName  .'</a><p>' . $userName . ' </p></div>
                        <span><strong> ' . wfMessage('user-personal-info-about-me')->escaped() .' : </strong></span>';


        // if this section is empty we put a link to send to edit profile user
        if ($profile_data['about']==''){

            $out .='<a href="'.$pageEditProfile->getFullURL().'">' . wfMessage('user_about_empty')->escaped() .'</a>';

        }
        $out .='<p>' . $profile_data['about'] . '</p>';

        $out .= '</div></div></div>';
        return array( $out, 'noparse' => true, 'isHTML' => true );

    }
    // Second row with user informations checkboxes
    public static function renderUserInfoChecked( $parser) {


    	$parser->disableCache();

        global $wgUser, $wgUserProfileDisplay, $wgUploadPath;
        $counterFollowing = '<i class="fa fa-circle"></i>';
        $checkTutoUser = '<i class="fa fa-circle"></i>';
        $checkAvatarUser = '<i class="fa fa-circle"></i>';

        //Check if connected user is already following other makers
        $userGetCounters = new UsersWatchListCore();
        $userCounterFollowing = $userGetCounters -> getUserCounters($wgUser);

        if ($userCounterFollowing['following'] > 0 ){
            $counterFollowing = '<i class="fa fa-check-circle"> </i>' ;
        }

        //Check if connected user get a real image and not the default one
        $avatar = new wAvatar( $wgUser -> getId(), 'm' );
        $defaultAvatar = "{$wgUploadPath}/avatars/default_m.gif";

        $avatarParams = array(
            'src' => "{$wgUploadPath}/avatars/{$avatar->getAvatarImage()}",
            'alt' => 'avatar',
            'border' => '0',
        );
        if ($avatarParams['src'] != $defaultAvatar){
            $checkAvatarUser = '<i class="fa fa-check-circle"> </i> ';
        }
        // Check if connected user already made a tuto
        $context = new RequestContext();
        $options =  [
            'namespace' => "0", //namespace principal, to get only tutorials
            'target' => $wgUser->getName(),
            'newOnly' => 1
        ];
        $contribsPager = new ContribsPager($context, $options);
        $contribs = $contribsPager->reallyDoQuery( 0, 12, true);

        if (count($contribs->result) > 0 ){
            $checkTutoUser = '<i class="fa fa-check-circle"> </i> ' ;

        }

        $out = '<div class="col-lg-3"><div class="UserInfoChecked">';
        $out .= '<div class="UserInfoAccount">';
        $out .= '<i class="fa fa-check-circle"> </i>' .' '. wfMessage('user-info-create-account')->escaped() ;
        $out .= '</div>';
        $out .= '<div class="UserInfoFollowing">' . $counterFollowing .' '. wfMessage('user-info-following')->escaped() . '</div>' ;
        $out .= '<div class="UserInfoCheckAvatar">' . $checkAvatarUser .' '. wfMessage('user-info-check-avatar') . '</div>';
        $out .= '<div class="UserInfoCheckTuto">' . $checkTutoUser .' '. wfMessage('user-info-check-tuto') . '</div>';
        $out .= '</div></div>';

        return array( $out, 'noparse' => true, 'isHTML' => true );

    }


    public static function onBeforePageDisplay( OutputPage &$out, Skin &$skin ) {
        $out->addModules('ext.socialprofile.userinfo.js');
        $out->addModuleStyles('ext.socialprofile.userinfo.css');
    }
}

?>

