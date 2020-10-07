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
				this.firstPlayerId = Object.keys(this.gamedatas.players)[0];
			}
			return this.firstPlayerId;
		},

		setupPlayerTables: function() {
			dojo.create('div', {class: 'table whiteblock', innerHTML: '<h3>Floor</h3><div id="floor"></div>'}, 'player_table', 'last');
			this.addCardElements(this.gamedatas.floor, document.getElementById('floor'));

			var current_player = this.getThisPlayerId();
			for (var i = 0; i < this.gamedatas.playerorder.length; i++) {
				var player = this.gamedatas.players[this.gamedatas.playerorder[i]];
				dojo.place(this.format_block((current_player == player.id) ? 'jstpl_playerTable' : 'jstpl_otherPlayerTable', player), 'player_table', 'last');

				if (current_player != player.id) {
					document.getElementById('player_' + player.id + '_hand_size').innerHTML = player.hand_count;
				} else {
					this.addCardElements(this.gamedatas.hand, document.getElementById('player_' + player.id + '_hand'));
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
			var card = this.gamedatas.cards[cardObj.type_arg];
			var material = this.gamedatas.materials[card.material];
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
		onEnteringState: function(stateName, args) {
			this.connections = [];
			this.selectableElements = [];
			var _this = this;
			console.log('Entering state', stateName, args);
			var playerId = this.getThisPlayerId();

			switch (stateName) {
			case 'chooseNewTask':
				if (!this.isCurrentPlayerActive())
					break;

				dojo.query('#player_' + playerId + '_hand > .card').forEach(function(node, index, arr) {
					_this.selectableElements.push(node);
					node.classList.add('selectable');
					_this.connections.push(dojo.connect(node, 'onclick', _this, 'onChoosingTask'));
				});
				break;
			}
		},

		// onLeavingState: this method is called each time we are leaving a game state.
		//				 You can use this method to perform some user interface changes at this moment.
		//
		onLeavingState: function(stateName) {
			console.log('Leaving state:', stateName);
			this.selectableElements.forEach(function(elem) {
				elem.classList.remove('selectable');
			});
			this.connections.forEach(function(handle) {
				dojo.disconnect(handle);
			});
		},

		// onUpdateActionButtons: in this method you can manage "action buttons" that are displayed in the
		//						action status bar (ie: the HTML links in the status bar).
		//
		onUpdateActionButtons: function( stateName, args ) {
			console.log('onUpdateActionButtons:', stateName);

			if(this.isCurrentPlayerActive())
			{
				switch(stateName) {
				case 'chooseNewTask':
				   this.addActionButton('button_1_id', _('Skip'), 'doSkipNewTask');
				   break;
				}
			}
		},

		///////////////////////////////////////////////////
		//// Utility methods

		/*

			Here, you can defines some utility methods that you can use everywhere in your javascript
			script.

		*/

		ajaxAction: function (action, args, func, err, lock) {
			if (!args) {
				args = [];
			}
			delete args.action;
			if (!args.hasOwnProperty('lock') || args.lock) {
				args.lock = true;
			} else {
				delete args.lock;
			}
			if (typeof func == "undefined" || func == null) {
				var self = this;
				func = function (result) {
				};
			}

			var name = this.game_name;
			this.ajaxcall("/" + name + "/" + name + "/" + action + ".html", args, this, func, err);
		},


		///////////////////////////////////////////////////
		//// Player's action

		/*

			Here, you are defining methods to handle player's action (ex: results of mouse click on
			game objects).

			Most of the time, these methods:
			_ check the action is possible at this game state.
			_ make a call to the game server

		*/

		doSkipNewTask: function(event) {
			dojo.stopEvent(event);
			if (!this.checkAction('chooseNewTask')) return;
			this.ajaxAction('chooseNewTask');
		},

		onChoosingTask: function(event) {
			dojo.stopEvent(event);
			if (!this.checkAction('chooseNewTask')) return;
			var card_id = dojo.attr(event.target, 'id').split('_')[1];
			console.log('Clicked card', card_id);
			this.ajaxAction('chooseNewTask', {
				id: card_id,
			});
		},


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

			dojo.subscribe('discardOldTask', this, 'notif_discardOldTask');
			this.notifqueue.setSynchronous('discardOldTask', 1000);
		},

		notif_discardOldTask: function(notif) {
			console.log('notif_discardOldTask');
			console.log(notif);
			// We received a new full hand of 13 cards.
			// this.playerHand.removeAll();

			// for ( var i in notif.args.cards) {
			//	 var card = notif.args.cards[i];
			//	 var color = card.type;
			//	 var value = card.type_arg;
			//	 this.playerHand.addToStockWithId(this.getCardUniqueId(color, value), card.id);
			// }
		},
	});
});
