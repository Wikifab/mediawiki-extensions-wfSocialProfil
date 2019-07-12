<?php

class ViewGiftUsers extends SpecialPage {

	/**
	 * Constructor
	 */
	public function __construct() {
		parent::__construct( 'ViewGiftUsers' );
	}

	/**
	 * Show the special page
	 *
	 * @param $par Mixed: parameter passed to the page or null
	 */
	public function execute( $par ) {
		global $wgUploadPath;

		$out = $this->getOutput();

		// Set the page title, robot policies, etc.
		$this->setHeaders();

		// Add CSS
		$out->addModuleStyles( 'ext.socialprofile.usergifts.css' );

		$giftId = $this->getRequest()->getInt( 'gift_id' );
		if ( !$giftId || !is_numeric( $giftId ) ) {
			$out->setPageTitle( $this->msg( 'g-error-title' )->plain() );
			$out->addHTML( $this->msg( 'g-error-message-invalid-link' )->plain() );
			return false;
		}

		$gift = Gifts::getGift( $giftId );

		if ( $gift ) {

			// DB stuff
			$dbr = wfGetDB( DB_SLAVE );
			$res = $dbr->select(
				'user_gift',
				array( 'ug_user_name_to', 'ug_user_id_to' ),
				array(
					'ug_gift_id' => $gift['gift_id']
				),
				__METHOD__,
				array(
					'ORDER BY' => 'ug_date DESC'
				)
			);

			$count = 0;
			$users = '';
			foreach ( $res as $row ) {
				$count++;
				$userId = $row->ug_user_id_to;
				$avatar = new wAvatar( $userId, 's' );
				$userNameLink = Title::makeTitle( NS_USER, $row->ug_user_name_to );

				$users .= '<div class="user-item"><div class="user-avatar">' .
					$avatar->getAvatarURL() .
				'</div><a href="' . htmlspecialchars( $userNameLink->getFullURL() ) . '">'.$row->ug_user_name_to.'</a></div>';
			}


			$out->setPageTitle( $this->msg(
				'g-userlist',
				$gift['gift_name']
			)->parse() );

			$output = '<div class="back-links">
				<a href="' . htmlspecialchars( SpecialPage::getTitleFor( 'GiftManager' )->getFullURL()) . '">'
				. $this->msg( 'giftmanager-view' )->parse() . '</a>
			</div>';

			$giftImage = '<img src="' . $wgUploadPath . '/awards/' .
				Gifts::getGiftImage( $gift['gift_id'], 'l' ) .
				'" border="0" alt="" />';

			$output .= '<div class="g-description-container">';
			$output .= '<div class="row g-description"><div class="col-md-2 col-sm-3 col-xs-12">' .
					$giftImage .
					'</div><div class="col-md-10 col-sm-9 col-xs-12"><div class="g-name">' . $gift['gift_name'] . '</div>';			
			
			$output .= '<div class="g-user-message">' . $gift['gift_description'] . '</div>';
			$output .= '<div class="visualClear"></div>';
			if($count === 0){
				$output .= '<div class="g-gift-count">' .
				$this->msg( 'g-no-given', $count )->parse() .
				'</div>';
			} else {
				$output .= '<div class="g-gift-count">' .
				$this->msg( 'g-given', $count )->parse() .
				'</div>';
			}
			$output .= '</div></div>';

			$output .= '<div class="user-list">';

			$output .= $users;

			$output .= '</div>';
			
			$output .= '<div class="visualClear"></div>
				</div>
			</div>';

			$out->addHTML( $output );
		} else {
			$out->setPageTitle( $this->msg( 'g-error-title' )->plain() );
			$out->addHTML( $this->msg( 'g-error-message-invalid-link' )->plain() );
		}
	}
}
