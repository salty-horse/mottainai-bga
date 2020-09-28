/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * Mottainai implementation : © Ori Avtalion <ori@avtalion.name>
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 *
 * mottainai.js
 *
 * Mottainai user interface script
 *
 * In this file, you are describing the logic of your user interface, in Javascript language.
 *
 */

define([
	"dojo","dojo/_base/declare",
	"ebg/core/gamegui",
	"ebg/counter"
],
function(dojo, declare) {
	return declare("bgagame.mottainai", ebg.core.gamegui, {
		constructor: function(){
			console.log('mottainai constructor');

			// Here, you can init the global variables of your user interface
			// Example:
			// this.myGlobalValue = 0;

		},

		/*
			setup:

			This method must set up the game user interface according to current game situation specified
			in parameters.

			The method is called each time the game interface is displayed to a player, ie:
			_ when the game starts
			_ when a player refreshes the game page (F5)

			"gamedatas" argument contains all datas retrieved by your "getAllDatas" PHP method.
		*/

		setup: function( gamedatas )
		{
			console.log( "Starting game setup" );
			console.log( 'gamedatas', gamedatas );

			this.gamedatas_local = dojo.clone(gamedatas);

			this.setupPlayerTables();

			// Setting up player boards
			for( var player_id in gamedatas.players )
			{
				var player = gamedatas.players[player_id];

				// TODO: Setting up players boards if needed
			}

			// TODO: Set up your game interface here, according to "gamedatas"


			// Setup game notifications to handle (see "setupNotifications" method below)
			this.setupNotifications();

			console.log( "Ending game setup" );
		},


		/**
		 * function wrapper for this.player_id,
		 * it checks if spectator
		 */
		getThisPlayerId: function() {
			if (!this.isSpectator) {
				return this.player_id;
			}
			if (!this.firstPlayerId) {
				this.firstPlayerId = Object.keys(this.gamedatas_local.players)[0];
			}
			return this.firstPlayerId;
		},

		setupPlayerTables: function() {
			dojo.create('div', {class: 'table whiteblock', innerHTML: '<h3>Floor</h3><div id="floor"></div>'}, 'player_table', 'last');
			this.addCardElements(this.gamedatas_local.floor, document.getElementById('floor'));

			var current_player = this.getThisPlayerId();
			for (var i = 0; i < this.gamedatas_local.playerorder.length; i++) {
				var player = this.gamedatas_local.players[this.gamedatas_local.playerorder[i]];
				dojo.place(this.format_block((current_player == player.id) ? 'jstpl_playerTable' : 'jstpl_otherPlayerTable', player), 'player_table', 'last');

				if (current_player != player.id) {
					document.getElementById('player_' + player.id + '_hand_size').innerHTML = player.hand_count;
				} else {
					this.addCardElements(this.gamedatas_local.hand, document.getElementById('player_' + player.id + '_hand'));
				}

				if (player.initial_task) {
					document.getElementById('player_' + player.id + '_task').innerHTML = 'Hidden';
				} else if (player.task) {
					document.getElementById('player_' + player.id + '_task').appendChild(this.createCardElement(player.task));
				}

				// TODO: Show opponent's revelaed hand
				this.addCardElements(player.gallery, document.getElementById('player_' + player.id + '_gallery'));
				this.addCardElements(player.gift_shop, document.getElementById('player_' + player.id + '_gift_shop'));
				this.addCardElements(player.helpers, document.getElementById('player_' + player.id + '_helpers'));
				this.addCardElements(player.craft_bench, document.getElementById('player_' + player.id + '_craft_bench'));
				this.addCardElements(player.sales, document.getElementById('player_' + player.id + '_sales'));
				document.getElementById('player_' + player.id + '_waiting_area').innerHTML = player.waiting_area_count;
			}
		},

		createCardElement: function(cardObj) {
			var card = this.gamedatas_local.cards[cardObj.type_arg];
			var material = this.gamedatas_local.materials[card.material];
			var elem = dojo.create('div', {
				id: 'card_' + cardObj.id,
				class: 'card',
				innerHTML: card.name + ' ' + material.symbol,
			});

			return elem;
		},

		addCardElements: function(cards, element) {
			for (c in cards) {
				element.appendChild(this.createCardElement(cards[c]));
			}
		},


		///////////////////////////////////////////////////
		//// Game & client states

		// onEnteringState: this method is called each time we are entering into a new game state.
		//				  You can use this method to perform some user interface changes at this moment.
		//
		onEnteringState: function( stateName, args )
		{
			console.log( 'Entering state: '+stateName );

			switch( stateName )
			{

			/* Example:

			case 'myGameState':

				// Show some HTML block at this game state
				dojo.style( 'my_html_block_id', 'display', 'block' );

				break;
		   */


			case 'dummmy':
				break;
			}
		},

		// onLeavingState: this method is called each time we are leaving a game state.
		//				 You can use this method to perform some user interface changes at this moment.
		//
		onLeavingState: function( stateName )
		{
			console.log( 'Leaving state: '+stateName );

			switch( stateName )
			{

			/* Example:

			case 'myGameState':

				// Hide the HTML block we are displaying only during this game state
				dojo.style( 'my_html_block_id', 'display', 'none' );

				break;
		   */


			case 'dummmy':
				break;
			}
		},

		// onUpdateActionButtons: in this method you can manage "action buttons" that are displayed in the
		//						action status bar (ie: the HTML links in the status bar).
		//
		onUpdateActionButtons: function( stateName, args )
		{
			console.log( 'onUpdateActionButtons: '+stateName );

			if( this.isCurrentPlayerActive() )
			{
				switch( stateName )
				{
/*
				 Example:

				 case 'myGameState':

					// Add 3 action buttons in the action status bar:

					this.addActionButton( 'button_1_id', _('Button 1 label'), 'onMyMethodToCall1' );
					this.addActionButton( 'button_2_id', _('Button 2 label'), 'onMyMethodToCall2' );
					this.addActionButton( 'button_3_id', _('Button 3 label'), 'onMyMethodToCall3' );
					break;
*/
				}
			}
		},

		///////////////////////////////////////////////////
		//// Utility methods

		/*

			Here, you can defines some utility methods that you can use everywhere in your javascript
			script.

		*/


		///////////////////////////////////////////////////
		//// Player's action

		/*

			Here, you are defining methods to handle player's action (ex: results of mouse click on
			game objects).

			Most of the time, these methods:
			_ check the action is possible at this game state.
			_ make a call to the game server

		*/

		/* Example:

		onMyMethodToCall1: function( evt )
		{
			console.log( 'onMyMethodToCall1' );

			// Preventing default browser reaction
			dojo.stopEvent( evt );

			// Check that this action is possible (see "possibleactions" in states.inc.php)
			if( ! this.checkAction( 'myAction' ) )
			{   return; }

			this.ajaxcall( "/mottainai/mottainai/myAction.html", {
																	lock: true,
																	myArgument1: arg1,
																	myArgument2: arg2,
																	...
																 },
						 this, function( result ) {

							// What to do after the server call if it succeeded
							// (most of the time: nothing)

						 }, function( is_error) {

							// What to do after the server call in anyway (success or failure)
							// (most of the time: nothing)

						 } );
		},

		*/


		///////////////////////////////////////////////////
		//// Reaction to cometD notifications

		/*
			setupNotifications:

			In this method, you associate each of your game notifications with your local method to handle it.

			Note: game notification names correspond to "notifyAllPlayers" and "notifyPlayer" calls in
				  your mottainai.game.php file.

		*/
		setupNotifications: function()
		{
			console.log('notifications subscriptions setup');

			// TODO: here, associate your game notifications with local methods

			// Example 1: standard notification handling
			// dojo.subscribe( 'cardPlayed', this, "notif_cardPlayed" );

			// Example 2: standard notification handling + tell the user interface to wait
			//			during 3 seconds after calling the method in order to let the players
			//			see what is happening in the game.
			// dojo.subscribe( 'cardPlayed', this, "notif_cardPlayed" );
			// this.notifqueue.setSynchronous( 'cardPlayed', 3000 );
			//
		},

		// TODO: from this point and below, you can write your game notifications handling methods

		/*
		Example:

		notif_cardPlayed: function( notif )
		{
			console.log( 'notif_cardPlayed' );
			console.log( notif );

			// Note: notif.args contains the arguments specified during you "notifyAllPlayers" / "notifyPlayer" PHP call

			// TODO: play the card in the user interface.
		},

		*/
   });
});
