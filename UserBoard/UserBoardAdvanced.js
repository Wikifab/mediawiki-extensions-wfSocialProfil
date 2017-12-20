var UserBoardAdvanced = {
	posted: 0,

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
	}
};


jQuery( document ).ready( function() {

	$page = mw.util.getParamValue( 'page' );
	
	$nb_message_show = 10;

	
	if(!$page){
		$page = 1;
	}
	// Si on est pas dans une discussion c'est-à-dire pas dans un formulaire pour de nouveaux messages
	if($(".user-page-message-form ")[0]){

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
						wfUba[$nb_message_show].scrollIntoView();	            }
		    	
		        });
		    	
		    }
		    
		});
		$(".user-page-message-form ").scrollTop($(".user-page-message-form ")[0].scrollHeight);
	}
	// "Delete" link
	jQuery( 'span.user-board-red a' ).on( 'click', function() {
		UserBoardAdvanced.deleteMessage( jQuery( this ).data( 'message-id' ) );
	} );

	// Submit button
	jQuery( 'div.user-page-message-box-button input[type="button"]' ).on( 'click', function() {
		UserBoardAdvanced.sendMessage( jQuery( this ).data( 'per-page' ) );
	} );
	
	

} );