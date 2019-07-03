<?php
/**
 * Special page for creating and editing user-to-user gifts.
 *
 * @file
 */
class GiftManager extends SpecialPage {

	/**
	 * Constructor
	 */
	public function __construct() {
		parent::__construct( 'GiftManager'/*class*/, 'giftadmin'/*restriction*/ );
	}

	/**
	 * Group this special page under the correct header in Special:SpecialPages.
	 *
	 * @return string
	 */
	function getGroupName() {
		return 'wiki';
	}

	/**
	 * Show the special page
	 *
	 * @param $par Mixed: parameter passed to the page or null
	 */
	public function execute( $par ) {
		$out = $this->getOutput();
		$request = $this->getRequest();
		$user = $this->getUser();

		// Set the page title, robot policies, etc.
		$this->setHeaders();

		$out->setPageTitle( $this->msg( 'giftmanager' )->plain() );

		// Make sure that the user is logged in and that they can use this
		// special page<
		if ( $user->isAnon() || !$this->canUserManage() ) {
			throw new ErrorPageError( 'error', 'badaccess' );
		}

		// Show a message if the database is in read-only mode
		if ( wfReadOnly() ) {
			$out->readOnlyPage();
			return;
		}

		// Add CSS
		$out->addModuleStyles( 'ext.socialprofile.usergifts.css' );

		//Add WAC menu
		$out->addModuleScripts('ext.wikiadminconfig.wikiadminconfig.js');
		$out->addModuleStyles('ext.wikiadminconfig.wikiadminconfig.css');

		$content = '<div class="row"><div class="col-xs-3">';
		$content .= \WAC\WikiAdminConfig::transcludeSidebar();
		$content .= '</div><div class="col-xs-9">';
		$out->addHTML($content);

		if ( $request->wasPosted() ) {
			if ( !$request->getInt( 'id' ) ) {
				$giftId = Gifts::addGift(
					$request->getVal( 'gift_name' ),
					$request->getVal( 'gift_description' ),
					$request->getInt( 'access' )
				);
				$out->addHTML(
					'<span class="view-status">' .
					$this->msg( 'giftmanager-giftcreated' )->plain() .
					'</span><br /><br />'
				);
			} else {
				$giftId = $request->getInt( 'id' );
				Gifts::updateGift(
					$giftId,
					$request->getVal( 'gift_name' ),
					$request->getVal( 'gift_description' ),
					$request->getInt( 'access' )
				);
				$out->addHTML(
					'<span class="view-status">' .
					$this->msg( 'giftmanager-giftsaved' )->plain() .
					'</span><br /><br />'
				);
			}

			$out->addHTML( $this->displayForm( $giftId ) );
		} else {
			$giftId = $request->getInt( 'id' );
			if ( $giftId || $request->getVal( 'method' ) == 'edit' ) {
				$out->addHTML( $this->displayForm( $giftId ) );
			} else {
				// If the user is allowed to create new gifts, show the
				// "add a gift" link to them
				if ( $this->canUserCreateGift() ) {
					$out->addHTML(
						'<div><div class="btn btn-primary btn-badge-create"><a href="' .
						htmlspecialchars( $this->getPageTitle()->getFullURL( 'method=edit' ) ) .
						'">' . $this->msg( 'giftmanager-addgift' )->plain() .
						'</a></div></div>'
					);
				}
				$out->addHTML( $this->displayGiftList() );
			}
		}
	}

	/**
	 * Function to check if the user can manage created gifts
	 *
	 * @return Boolean: true if user has 'giftadmin' permission or is
	 *			a member of the giftadmin group, otherwise false
	 */
	function canUserManage() {
		global $wgMaxCustomUserGiftCount;

		$user = $this->getUser();

		if ( $user->isBlocked() ) {
			return false;
		}

		if ( $wgMaxCustomUserGiftCount > 0 ) {
			return true;
		}

		if (
			$user->isAllowed( 'giftadmin' ) ||
			in_array( 'giftadmin', $user->getGroups() )
		)
		{
			return true;
		}

		return false;
	}

	/**
	 * Function to check if the user can delete created gifts
	 *
	 * @return Boolean: true if user has 'giftadmin' permission or is
	 *			a member of the giftadmin group, otherwise false
	 */
	function canUserDelete() {
		$user = $this->getUser();

		if ( $user->isBlocked() ) {
			return false;
		}

		if (
			$user->isAllowed( 'giftadmin' ) ||
			in_array( 'giftadmin', $user->getGroups() )
		)
		{
			return true;
		}

		return false;
	}

	/**
	 * Function to check if the user can create new gifts
	 *
	 * @return Boolean: true if user has 'giftadmin' permission, is
	 *			a member of the giftadmin group or if $wgMaxCustomUserGiftCount
	 *			has been defined, otherwise false
	 */
	function canUserCreateGift() {
		global $wgMaxCustomUserGiftCount;

		$user = $this->getUser();

		if ( $user->isBlocked() ) {
			return false;
		}

		$createdCount = Gifts::getCustomCreatedGiftCount( $user->getID() );
		if (
			$user->isAllowed( 'giftadmin' ) ||
			in_array( 'giftadmin', $user->getGroups() ) ||
			( $wgMaxCustomUserGiftCount > 0 && $createdCount < $wgMaxCustomUserGiftCount )
		)
		{
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Display the text list of all existing gifts and a delete link to users
	 * who are allowed to delete gifts.
	 *
	 * @return String: HTML
	 */
	function displayGiftList() {
		global $wgUploadPath;
		$output = ''; // Prevent E_NOTICE
		$page = 0;
		/**
		 * @todo FIXME: this is a dumb hack. The value of this variable used to
		 * be 10, but then it would display only the *first ten* gifts, as this
		 * special page seems to lack pagination.
		 * @see https://www.mediawiki.org/w/index.php?oldid=988111#Gift_administrator_displays_10_gifts_only
		 */
		$per_page = 1000;
		$gifts = Gifts::getManagedGiftList( $per_page, $page );
		if ( $gifts ) {
			foreach ( $gifts as $gift ) {
				$editLink = '';
				$deleteLink = '';
				if ( $this->canUserDelete() ) {
					$deleteLink = '<a class="btn btn-primary btn-badge-actions" href="' .
						htmlspecialchars( SpecialPage::getTitleFor( 'RemoveMasterGift' )->getFullURL( "gift_id={$gift['id']}" ) ) .
						'">' .
						$this->msg( 'delete' )->plain() . '</a>';
					$editLink = '<a class="btn btn-primary btn-badge-actions" href="' .
						htmlspecialchars( SpecialPage::getTitleFor( 'GiftManager' )->getFullURL( "id={$gift['id']}" ) ) .
						'">' .
						$this->msg( 'edit' )->plain() . '</a>';
				}

				$gift_image = '<img src="' . $wgUploadPath . '/awards/' .
					Gifts::getGiftImage( $gift['id'], 'l' ) . '" border="0" alt="' .
					$this->msg( 'g-gift' )->plain() . '" />';

				$output .= '<div class="badge-item"><div class="badge-img">' . $gift_image . '</div>
				<a href="' . htmlspecialchars( $this->getPageTitle()->getFullURL( "id={$gift['id']}" ) ) . '">' .
					$gift['gift_name'] . '</a>
					<div class="badge-actions">'. $editLink . '</div><div class="badge-actions">' . $deleteLink . "</div></div>\n";
			}
		}
		return '<div id="badge-list">' . $output . '</div>';
	}

	function displayForm( $gift_id ) {
		$user = $this->getUser();

		if ( !$gift_id && !$this->canUserCreateGift() ) {
			return $this->displayGiftList();
		}

		$form = '<div><b><a href="' . htmlspecialchars( $this->getPageTitle()->getFullURL() ) .
			'">' . $this->msg( 'giftmanager-view' )->plain() . '</a></b></div>';

		if ( $gift_id ) {
			$gift = Gifts::getGift( $gift_id );
			if (
				$user->getID() != $gift['creator_user_id'] &&
				(
					!in_array( 'giftadmin', $user->getGroups() ) &&
					!$user->isAllowed( 'delete' )
				)
			)
			{
				throw new ErrorPageError( 'error', 'badaccess' );
			}
		}

		$form .= '<form action="" method="post" enctype="multipart/form-data" name="gift" class="form-horizontal">';
		$form .= '<div class="form-group">
					<label for="badgeName" class="col-sm-2 control-label">' . $this->msg( 'g-gift-name' )->plain() . '</label>
						<div class="col-sm-10">
							<input type="text" class="form-control" id="badgeName" name="gift_name" value="' .
			( isset( $gift['gift_name'] ) ? $gift['gift_name'] : '' ) . '"/>
						</div>
				  </div>';
		$form .= '<div class="form-group">
					<label for="badgeDescription" class="col-sm-2 control-label">' . $this->msg( 'giftmanager-description' )->plain() . '</label>
					<div class="col-sm-10">
						<textarea class="form-control" id="badgeDescription" name="gift_description">' .
			( isset( $gift['gift_description'] ) ? $gift['gift_description'] : '' ) . '</textarea>
					</div>
				  </div>';

		if ( $gift_id ) {
			$creator = Title::makeTitle( NS_USER, $gift['creator_user_name'] );
			$form .= '<div class="form-group">
						<label for="badgeCreator" class="col-sm-2 control-label">' . $this->msg( 'g-created-by', $gift['creator_user_name'] )->parse() . '</label>
						<div class="col-sm-10">
							<a id="badgeCreator" href="' . htmlspecialchars( $creator->getFullURL() ) . '">' .
				$gift['creator_user_name'] . '</a>
					    </div>
					  </div>';
		}

		if ( !$user->isAllowed( 'giftadmin' ) ) {
			$form .= '<input type="hidden" name="access" value="1" />';
		} else {
			$publicSelected = $privateSelected = '';
			if ( isset( $gift['access'] ) && $gift['access'] == 0 ) {
				$publicSelected = ' selected="selected"';
			}
			if ( isset( $gift['access'] ) && $gift['access'] == 1 ) {
				$privateSelected = ' selected="selected"';
			}
			$form .= '<div class="form-group">
						<label for="badgeAccess" class="col-sm-2 control-label">' . $this->msg( 'giftmanager-access' )->parse() . '</label>
						<div class="col-sm-10">
							<select class="form-control" id="badgeAccess" name="access">
								<option value="0"' . $publicSelected . '>' .
								$this->msg( 'giftmanager-public' )->plain() .
								'</option>
									<option value="1"' . $privateSelected . '>' .
								$this->msg( 'giftmanager-private' )->plain() .
								'</option>
							</select>
						</div>
					  </div>';
		}

		if ( $gift_id ) {
			global $wgUploadPath;
			$gml = SpecialPage::getTitleFor( 'GiftManagerLogo' );
			$gift_image = '<img src="' . $wgUploadPath . '/awards/' .
				Gifts::getGiftImage( $gift_id, 'l' ) . '" border="0" alt="' .
				$this->msg( 'g-gift' )->plain() . '" />';
			$form .= '<div class="form-group">
						<label for="badgeImage" class="col-sm-2 control-label">' . $this->msg( 'giftmanager-giftimage' )->plain() . '</label>
						<div class="col-sm-10">' . $gift_image .
							'<div>
								<a id="badgeImage" href="' . htmlspecialchars( $gml->getFullURL( 'gift_id=' . $gift_id ) ) . '">' .
								$this->msg( 'giftmanager-image' )->plain() . '</a>
						</div>
					  </div>';
		}

		if ( isset( $gift['gift_id'] ) ) {
			$button = $this->msg( 'edit' )->plain();
		} else {
			$button = $this->msg( 'g-create-gift' )->plain();
		}

		$form .= '<div class="form-group">
					<div class="col-sm-10">
						<input type="hidden" name="id" value="' . ( isset( $gift['gift_id'] ) ? $gift['gift_id'] : '' ) . '" />
						<input type="button" class="btn btn-default" value="' . $button . '" size="20" onclick="document.gift.submit()" />
						<input type="button" class="btn btn-default" value="' . $this->msg( 'cancel' )->plain() . '" size="20" onclick="history.go(-1)" />
					</div>
				  </div>';

		$form .= '</form>';
		return $form;
	}
}
