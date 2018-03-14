<?php

use phpDocumentor\Reflection\DocBlock\Tags\Var_;

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
        global $wgGroupPermissions;
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


        $nb_conversation_show = 20;
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

        // If users don't have rights to write any message, display a push message to inform them
        if (!($wgGroupPermissions['user']['userboard-sendMessage'])){
            $html = '<div class="uba-discussion-error-message">'. $this->msg('userboard-advanced-norightsmessages')->plain(). '</div>';
            return $out->addHTML($html);
        }

        $b = new UserBoard();
        $ba = new UserBoard();
        // Si l'utilisateur choisi n'est pas encore sélectionné, on le met à 0 sinon c'est celui choisi
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
        // Si on a pas d'user 2 et qu'il y a quand même des messages on donne par défaut la valeur de cet user à l'user2
        if($user_2_id==0 && $ub_messages ){
            $lastMessage = $ub_messages[0];
            if($lastMessage['user_id_from'] == $currentUser->getId()) {
                $user_2_id = $lastMessage['user_id'];
            } else {
                $user_2_id = $lastMessage['user_id_from'];

            }
            $user_2= User::newFromId($user_2_id);
        }
        $html = "<div class=\"row\">";
        $html .= $this->displayAllConversation($currentUser, $user_2,$ub_messages);


        if($user_2 && $user_2_id != $currentUser->getId()){
            // Messages quand on a un user (droite)
            $uba_messages = $ba->getUserBoardMessages(
                $currentUser->getId(),
                $user_2_id,
                $nb_conversation_show,
                $page
                );

            $html .='<h2 class="uba-discussion-list-title">'.$this->msg('userboard-advanced-all-messages',$user_2->getName())->parse().'</h2>';
            $html .= $this->getAllMessages($currentUser, $user_2,$uba_messages, $page);

            // Appel de la fonction qui met à 0 un message entrant et à 1 si on l'a lu.
            $b->markMessageRead($currentUser, $uba_messages);

            //Si on a jamais parlé à cet utilisateur affiché message pour commencer conversation
            if($user_2 && !($uba_messages)){
                $html .= "<div class=\"uba-discussion-error-message\"> " .$this->msg('userboard-advanced-nomessageswiththisone')->plain() ."</div>";
            }

        }
        // On ne peut pas parler à nous-même
        if($user_2 && $user_2_id == $currentUser->getId()){
            $html .= "<div class=\"uba-discussion-error-message\"> " .$this->msg('userboard-advanced-nomessageswithyourself')->plain() ."</div>";

        }

        $html .= "</div>";
        $out->addHTML($html);

    }

// Permet d'afficher toutes les conversations qu'on a eu avec tous les utilsateurs (qui nous ont envoyé un message)
    private function displayAllConversation  ($user, $user_2_active, $messages){
        $b = new UserBoard();
        $user_name=$user->getName();
        $html = '<h2 class="uba-message-list-title">'.$this->msg('userboardadvanced').'</h2>';
        $html .="<div class=\"uba-message-list\">";

        foreach ( $messages as $message) {
            $user_title = Title::makeTitle( NS_USER, $message['user_name_from'] );
            $delete_link = '';

            $message_text = $message['message_text'];
            $userPageURL = htmlspecialchars( $user_title->getFullURL() );
           //Si le $user choisi est le user qui a envoyé ou reçu des messages alors on met la classe active
            if ($user_2_active->getName() == $message['user_name_from'] || $user_2_active->getName() == $message['user_name']){
                $class = "active";
            }

            else {
                $class="";
            }
            // Si le message est non lu ET qu'il est pour la personne connectée on lui donne la classe unread
            if($message['read']==0 && $message['user_id']== $user->getId()){
                $class .= " unread";
            }

            // Si l'on a un message de ou pour quelqu'un cette personne devra apparaître dans le fil de la conversation
            if($user_name == $message['user_name_from']){
                $user_2_id=$message['user_id'];
                $user_2_name = $message['user_name'] ;

            }
            else {
                $user_2_id=$message['user_id_from'];
                $user_2_name = $message['user_name_from']  ;

            }
                $avatar = new wAvatar( $user_2_id, 'm' );

                $board_to_board = SpecialUserBoardAdvanced::getUserBoardToBoardURLAdvanced( $user_2_name);

                    $html .= "<div class=\"uba-message {$class}\">
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
                            <div class=\"uba-message-body\">
                                {$message_text}
                            </div>
                        </div>

                     </div></a>";

        }

        $html .= "</div>";
        return ($html);

    }

    private function getAllMessages($user, $user_2,$messageUsers, $page){

        $nb_conversation_show = 20;
        $per_page = 25;
        $ba =  new UserBoard();
        $html = "<div class=\"user-page-message-form col-md-7\">";

        // Mettre une limite de messages à charger lors du chargement de la conversation
        $totalMessages = $ba->getUserBoardToBoardCount( $user->getId(), $user_2->getId());
        $numofpages = $totalMessages / $nb_conversation_show;

        if ( $numofpages > 1 ) {
                $html .= '<div class="more-message-container">

                            <i class="more-message fa fa-spinner fa-pulse fa-2x fa-fw"></i>
                         </div>';

        }
        // Boucle sur les messages d'une même conversation pour afficher les messages de droite du plus ancien au plus récent
        for ($i=count($messageUsers)-1; $i>=0; $i--){
            $user_title = Title::makeTitle( NS_USER, $messageUsers[$i]['user_name_from'] );
            $avatar = new wAvatar( $messageUsers[$i]['user_id_from'], 'm' );
            $board_to_board='';
            $timeAgo='';

           // Permet de mettre la classe right aux messages de la personne connectée et left pour ceux reçus
           if ($messageUsers[$i]['user_id_from'] == $user->getId()){
               $delete_link = "<a href=\"javascript:void(0);\" class=\"fa fa-trash-o\" data-message-id=\"{$messageUsers[$i]['id']}\"> </a>";
               $class='message-right';
               $timeAgo = $this->msg('userboard_posted_ago', $ba->getTimeAgo($messageUsers[$i]['timestamp']) )->parse() ;

           }
           else {
               $delete_link = '';
               $class = 'message-left';
               $timeAgo = $this->msg('userboard_received_ago', $ba->getTimeAgo($messageUsers[$i]['timestamp']) )->parse() ;

           }
           if ($messageUsers[$i]['read']==0 && $messageUsers[$i]['user_id'] == $user->getId()){
               $class .= " unread";
           }


            $message_text = $messageUsers[$i]['message_text'];
            $userPageURL = htmlspecialchars( $user_title->getFullURL() );

            $html .= "<div class=\"uba-discussion {$class}\">
                        <div class=\"uba-discussion-avatar\">
                                <a href=\"{$userPageURL}\" title=\"{$messageUsers[$i]['user_name_from']}\">{$avatar->getAvatarURL()}</a>
                        </div>
                        <div class=\"uba-discussion-content\">
                            <h4 class=\"uba-discussion-from\">
                                <a href=\"{$userPageURL}\" title=\"{$messageUsers[$i]['user_name_from']}}\">{$messageUsers[$i]['user_name_from']}</a>
                            </h4>
                            <span class=\"user-board-red\">
                            {$delete_link}
                            </span>
                            <div class=\"uba-discussion-body\" data-toggle=\"tooltip\" data-placement=\"right\" title=\"{$timeAgo}\">
                                {$message_text}
                            </div>
                        </div>
                     </div>";
        }
        $html .= "</div>";

        // Input avec le message à envoyer et l'url sur laquelle on voit le message
        $html .= '<div class="user-page-message-send">
                <div class="uba-send-message">
                <p class="uba-send-message-author"> '.$this->msg('userboard-send-message-title',$user_2->getName())->plain().'</p>
                <input type="hidden" id="user_name_to" name="user_name_to" value="' . $user_2->getName() . '"/>
                <input type="hidden" id="user_name_from" name="user_name_from" value="' . $user->getName() . '"/>
                <span class="user-board-message-type user-board-message-hide">' . $this->msg( 'userboard_messagetype' )->plain() . ' </span>
                <select class="user-board-message-hide" id="message_type">
                    <option value="1">' . $this->msg( 'userboard_public' )->plain() . '</option>
                    <option value="0">' . $this->msg( 'userboard_private' )->plain() . '</option>
                </select>
                <p class="uba-send-message-textarea">
                <textarea name="message" id="message" rows="2"></textarea>
                </p>

			<div class="user-page-message-box-button">
					<input type="button" value="' . $this->msg( 'userboard_sendbutton' )->text() . '" class="site-button" data-per-page="' . $per_page . '" />
			         <i class="loaderWait fa fa-spinner fa-pulse fa-2x fa-fw"" aria-hidden="true"></i>
            </div>

            </div>';



        return ($html);

     }


    //Rajout de la fonction pour obtenir une url UserBoardAdvanced
     static function getUserBoardToBoardURLAdvanced( $user_name_2 ) {
        $title = SpecialPage::getTitleFor( 'UserBoardAdvanced' );
        $user_name_2 = str_replace( '&', '%26', $user_name_2 );
        return htmlspecialchars( $title->getFullURL( 'user=' . $user_name_2) );
    }


}

