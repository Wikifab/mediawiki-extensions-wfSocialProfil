var UserBoardAdvanced = {
	posted: 0,
	existingUsers : [],

	sendMessage: function( perPage ) {
		if ( !perPage ) {
			perPage = 25;
		}
		var message = document.getElementById( 'message' ).value,
			recipient = document.getElementById( 'user_name_to' ).value,
			sender = document.getElementById( 'user_name_from' ).value;
		if ( message && !UserBoardAdvanced.posted ) {
			UserBoardAdvanced.posted = 1;
			var encodedName = encodeURIComponent( recipient ),
				encodedMsg = encodeURIComponent( message ),
				messageType = document.getElementById( 'message_type' ).value;
			//Remplace le bouton "envoyer" par un loader pour faire comprendre que ça charge. 
			$('.site-button').hide();
			$('.loaderWait').show();
			jQuery.post(
				mw.util.wikiScript(), {
					action: 'ajax',
					rs: 'wfSendBoardMessage',
					rsargs: [encodedName, encodedMsg, messageType, perPage]
				},
				function( data ) {
					UserBoardAdvanced.posted = 0;
					var user_1, user_2;
					if ( sender ) { // it's a board to board
						user_1 = sender;
						user_2 = recipient;
					} else {
						user_1 = recipient;
						user_2 = '';
					}
					var url = mw.config.get( 'wgScriptPath' ) + '/index.php?title=Special:UserBoardAdvanced&user=' + user_2;
					window.location = url;
				}
			);
		}
	},

	deleteMessage: function( id ) {
		if ( confirm( mw.msg( 'userboard_confirmdelete' ) ) ) {
			jQuery.post(
				mw.util.wikiScript(), {
					action: 'ajax',
					rs: 'wfDeleteBoardMessage',
					rsargs: [id]
				},
				function( data ) {
					window.location.reload();
					// 1st parent = span.user-board-red
					// 2nd parent = div.uba-discussion-content
					// 3rd parent = div.uba-discussion.message-right
					jQuery( this ).parent().parent().parent(".div.uba-discussion.message-right").hide();
				}
			);
		}
	},

	 displayUsers: function(users){
		var modalBody = jQuery('.modal-body');
		var existingUsers = Array.from(UserBoardAdvanced.existingUsers);

		//Add new users
		jQuery.each(users, function (id, user) {
			if(!UserBoardAdvanced.existingUsers.includes(user.name)){
				var userName = '<span class="userName">' + user.name + '</span>';
				var userElement = new OO.ui.Element({
					classes : ['user'],
					$content: [user.avatar, userName]
				});
				modalBody.append(userElement.$element);
				UserBoardAdvanced.existingUsers.push(user.name);
			//If user is in existingUsers, we splice it
			} else {
				existingUsers.splice(existingUsers.indexOf(user.name), 1);
			}
		});

		//ExistingUsers contains displayed users who do not match the current search, so we remove them
		jQuery.each(existingUsers, function (key, value) {
			jQuery('.user').filter(function () {
				return jQuery(this).children().last().text() === value;
			}).remove();
			UserBoardAdvanced.existingUsers.splice(UserBoardAdvanced.existingUsers.indexOf(value), 1);
		});

		jQuery('.user').on('mouseover', function () {
			jQuery(this).css({'background-color' : 'lightgrey', 'cursor' : 'pointer'});
		});
		jQuery('.user').on('mouseout', function () {
			jQuery(this).css({'background-color' : 'white', 'cursor' : 'default'});
		});
		jQuery('.user').on('click', function () {
			var userNameClicked = jQuery(this).children().last().text();
			window.location = mediaWiki.config.get('wgScriptPath') + '?title=Spécial:UserBoardAdvanced&user=' + userNameClicked;
		});
    },

    load: function(query){
        if (!query.length) return;
        $.ajax({
            url: mediaWiki.config.get('wgScriptPath') + '/api.php?action=spQueryUser&query=' + encodeURIComponent(query) + '&format=json',
            type: 'GET',
            error: function() {
                console.log('error');
            },
            success: function(res) {
                UserBoardAdvanced.displayUsers(res.results);
            }
        });
    },
	
	init:function(){
		$page = mw.util.getParamValue( 'page' );
		
		$nb_message_show = 10;
	
		if(!$page){
			$page = 1;
		}
		// Si on est pas dans une discussion c'est-à-dire pas dans un formulaire pour de nouveaux messages
		if($(".user-page-message-form ")){

			$('.user-page-message-form').scroll(function(){
			    if ($('.user-page-message-form').scrollTop() == 0){
			    	$page++;
			    	$.ajax({
			    		
			            url: mw.util.getUrl('Special:UserBoardAdvanced',{user:mw.util.getParamValue( 'user' ), page:$page}),
			            success: function(html) {
			            	var $data = $(html);
							wfUba = $data.find('.user-page-message-form ').contents();
							var resultDiv = $('.user-page-message-form ');
							resultDiv.prepend(wfUba);
							if( $('.more-message').length > 0 ){
								$( ".more-message" ).first().replaceWith($( ".more-message" ).last());
							}
							if (wfUba.length == 1){
								$('.more-message').last().remove();
							}
							// Permet d'afficher la fin des messages chargés et non le début. 
							if($(wfUba).last()[0]) {
								console.log("scrooll to last child");
								$(wfUba).last()[0].scrollIntoView();
							} else if($(".user-page-message-form ")[0]) {
								console.log("scrooll to bottom");
								$(".user-page-message-form ").scrollTop($(".user-page-message-form ")[0].scrollHeight);
							} else {
								console.log('Error, new scroll div not found');
							}
						}
			    	
			        });
			    	
			    }
			    
			});
			if($(".user-page-message-form ")[0]) {
				$(".user-page-message-form ").scrollTop($(".user-page-message-form ")[0].scrollHeight);
			} else {
				console.log('Error, scroll div not found');
			}
		}
		// "Delete" link
		jQuery( 'span.user-board-red a' ).on( 'click', function() {
			UserBoardAdvanced.deleteMessage( jQuery( this ).data( 'message-id' ) );
		} );

		// Submit button
		jQuery( 'div.user-page-message-box-button input[type="button"]' ).on( 'click', function() {
			UserBoardAdvanced.sendMessage( jQuery( this ).data( 'per-page' ) );
		} );

		var crossButton = new OO.ui.Element({
            classes: ['close'],
            text: '×'
        });

        var modalTitle = new OO.ui.Element({
            classes: ['modal-title'],
            text: mediaWiki.msg("userboard-advanced-modal-title"),
        });

        var modalHeader = new OO.ui.Element({
            classes: ['modal-header'],
            $content: [modalTitle.$element, crossButton.$element]
        });

        var textInput = new OO.ui.TextInputWidget({
            placeholder: mediaWiki.msg("userboard-advanced-user-name")
        });

        var modalBody = new OO.ui.Element({
            classes: ['modal-body'],
            $content: textInput.$element
        });

        var modalContent = new OO.ui.Element({
            classes: ['modal-content'],
            $content: [modalHeader.$element, modalBody.$element]
        });

        var modalDialog = new OO.ui.Element({
            classes: ['modal-dialog'],
            $content: modalContent.$element
        });

        var modal = new OO.ui.Element({
            classes: ['modal fade'],
            id: 'userboardadvancedModal',
            $content: modalDialog.$element
        });

        $( document.body ).append( modal.$element );

        crossButton.$element.attr('data-dismiss', 'modal');

        textInput.on('change', function () {
        	if(textInput.getValue() === ""){
				UserBoardAdvanced.load('emptycontent');
			} else {
				UserBoardAdvanced.load(textInput.getValue());

			}
        });

        jQuery('.write-button').on('click', function () {
			UserBoardAdvanced.load('emptycontent');
		});	
		
	}
};


jQuery( document ).ready( function() {
	/* This callback is invoked as soon as the modules are available. */ 
	mw.loader.using( ['mediawiki.util', 'ext.socialprofile.userboard.js'] ).then( function () { 
		UserBoardAdvanced.init();
	} );
	
} );