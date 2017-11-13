<?php

class SpecialUserBoardAdvanced extends SpecialPage {

    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct( 'UserBoardAdvanced' );
    }

    function getGroupName() {
        return 'users';
    }

    function execute( $par ) {

        $out = $this->getOutput();
        $request = $this->getRequest();
        //$currentUser est l'objet USER de la personne connectée
        //$userName est donc le nom de la personne connectée
        $currentUser = $this->getUser();
        $user_name = $currentUser->getName();

        // Set the page title, robot policies, etc.
        $this->setHeaders();
        // Add CSS & JS
        $out->addModuleStyles( array(
            'ext.socialprofile.userboardadvanced.css'
        ) );
        $out->addModules( 'ext.socialprofile.userboardadvanced.js' );


        // On cherche à avoir le deuxième utilisateur (celui-choisi) en tant qu'objet
        // On a donc son nom dans l'url après le =user et à partir du nom on obtient l'objet
        $user_name_2 = $request->getVal( 'user' );
        $user_2= User::newFromName($user_name_2);

        $nb_conversation_show = 25;
        $page = $request->getInt( 'page', 1 );


        /**
         * Redirect Non-logged in users to Login Page
         * It will automatically return them to the UserBoard page
         */

        if ( $currentUser->getID() == 0) {
            $login = SpecialPage::getTitleFor( 'Userlogin' );
            $out->redirect( $login->getFullURL( 'returnto=Special:UserBoardAdvanced' ) );
            return false;
        }


        $b = new UserBoard();
        $ba = new UserBoard();
        // Si l'utilisateur choisi n'est pas encore sélectionné, on le met à 0 pour pas qu'il soit booléen
        if (!($user_2)){
            $user_2_id = 0;
        }
        else {
            $user_2_id = $user_2->getId();
        }
        //Messages quand on a pas d'user (gauche)
        $ub_messages = $b->getUserBoardAllMessages(
            $currentUser->getId(),
            0,
            $nb_conversation_show,
            $page
            );
        // Messages quand on a un user (droite)
        $uba_messages = $ba->getUserBoardMessages(
            $currentUser->getId(),
            $user_2_id,
            $nb_conversation_show,
            $page
            );

        if($user_2_id==0){
            $html= $this->getAllMessages ($currentUser, $user_2,$ub_messages);
        }
        else{
            $html= $this->getAllMessages($currentUser, $user_2,$ub_messages);
            $html .= $this->getAllDiscussions($currentUser, $user_2,$uba_messages);
        }

        $out->addHTML($html);

    }

// Permet d'afficher toutes les conversations qu'on a eu avec tous les utilsateurs (qui nous ont envoyé un message)
    private function getAllMessages  ($user, $user_2, $messages){
        $b = new UserBoard();
        $html ="<div class=\"uba-message-list col-md-5 \">";
        $user_name=$user->getName();
        foreach ( $messages as $message) {

            $user_title = Title::makeTitle( NS_USER, $message['user_name_from'] );
            $delete_link = '';

            $message_text = $message['message_text'];
            $userPageURL = htmlspecialchars( $user_title->getFullURL() );

           //Cas où on a pas de user_2
            if (!($user_2)){
                $user_2 =
                $user_2_name = $message['user_name_from'];

            }
            // Si l'expéditeur est la personne connectée
            if($user_name == $message['user_name_from']){
                $avatar = new wAvatar( $message['user_id'], 'm' );
                $user_2_name = $message['user_name']  ;

                $board_to_board = SpecialUserBoardAdvanced::getUserBoardToBoardURLAdvanced( $user_2_name);

                    $html .= "<div class=\"uba-message\">
                        <a href=\"{$board_to_board}\">
        				<div class=\"uba-message-avatar\">
        						{$avatar->getAvatarURL()}
        					</div>
                        <div class=\"uba-content\">
                            <h4 class=\"uba-message-from\">
        						{$user_2_name}
        				    </h4>
                            <span class=\"uba-message-time\">"
        				        . $this->msg( 'userboard_posted_ago', $b->getTimeAgo( $message['timestamp'] ) )->parse() .
        				    "</span>
                            <p class=\"uba-message-body\">
        						{$message_text}
        					</p>
                        </div>

                     </div></a>";
            }
            // Si l'expéditeur est un autre utilisateur que le connecté
            else {
                $avatar = new wAvatar( $message['user_id_from'], 'm' );
                $user_2_name = $message['user_name_from']  ;

                $board_to_board = SpecialUserBoardAdvanced::getUserBoardToBoardURLAdvanced( $user_2_name);
                $html .=  "<div class=\"uba-message\">
                        <a href=\"{$board_to_board}\">
        				<div class=\"uba-message-avatar\">
        						{$avatar->getAvatarURL()}
        					</div>
                        <div class=\"uba-content\">
                            <h4 class=\"uba-message-from\">
        						{$user_2_name}
        				    </h4>
                            <span class=\"uba-message-time\">"
                            . $this->msg( 'userboard_posted_ago', $b->getTimeAgo( $message['timestamp'] ) )->parse() .
                            "</span>
                            <p class=\"uba-message-body\">
        						{$message_text}
        					</p>
                        </div>

                     </div></a>";
            }

        }

        $html .= "</div>";
        return ($html);

    }

    private function getAllDiscussions($user, $user_2,$messageUsers){
        $per_page = 25;


        if (!($user_2)){
            return;
        }
        else {
            $ba =  new UserBoard();
            $html = "<div class=\"user-page-message-form col-md-7\">";

            foreach ($messageUsers as $messageUser){
                $user_title = Title::makeTitle( NS_USER, $messageUser['user_name_from'] );
                $avatar = new wAvatar( $messageUser['user_id_from'], 'm' );
                $delete_link = '';
                $board_to_board='';


             $message_text = $messageUser['message_text'];
             $userPageURL = htmlspecialchars( $user_title->getFullURL() );
             $html .= "<div class=\"uba-discussion\">
                            <div class=\"uba-discussion-avatar\">
            						<a href=\"{$userPageURL}\" title=\"{$messageUser['user_name_from']}\">{$avatar->getAvatarURL()}</a>
            				</div>
                            <div class=\"uba-discussion-content\">
                                <h4 class=\"uba-discussion-from\">
            						<a href=\"{$userPageURL}\" title=\"{$messageUser['user_name_from']}}\">{$messageUser['user_name_from']}</a>
            				    </h4>
                                <span class=\"uba-discussion-time\">"
            				        . $this->msg( 'userboard_posted_ago', $ba->getTimeAgo( $messageUser['timestamp'] ) )->parse() .
            				    "</div>
            					<div class=\"uba-discussion-body\">
                                    {$message_text}
            					</div>
                    </div>";
            }
            // Input avec le message à envoyer et l'url sur laquelle on voit le message
            $html .= '<div class="user-page-message-form">
					<input type="hidden" id="user_name_to" name="user_name_to" value="' . $user_2->getName() . '"/>
					<input type="hidden" id="user_name_from" name="user_name_from" value="' . $user->getName() . '"/>
					<span class="user-board-message-type user-board-message-hide">' . $this->msg( 'userboard_messagetype' )->plain() . ' </span>
					<select class="user-board-message-hide" id="message_type">
						<option value="1">' . $this->msg( 'userboard_public' )->plain() . '</option>
						<option value="0">' . $this->msg( 'userboard_private' )->plain() . '</option>
					</select>
					<p>
					<textarea name="message" id="message" cols="63" rows="4"></textarea>

					<div class="user-page-message-box-button">
						<input type="button" value="' . $this->msg( 'userboard_sendbutton' )->plain() . '" class="site-button" data-per-page="' . $per_page . '" />
					</div>

				</div>';

            $html .= "</div>";


	        return ($html);


        }
     }

    //Rajout de la fonction pour obtenir une url UserBoardAdvanced
     static function getUserBoardToBoardURLAdvanced( $user_name_2 ) {
        $title = SpecialPage::getTitleFor( 'UserBoardAdvanced' );
        $user_name_2 = str_replace( '&', '%26', $user_name_2 );
        return htmlspecialchars( $title->getFullURL( 'user=' . $user_name_2) );
    }


}

