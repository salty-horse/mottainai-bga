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
	"ebg/counter",
	"ebg/stock",
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
			console.log('Starting game setup');
			console.log('gamedatas', gamedatas);

			this.setupPlayerTables();
			this.setupNotifications();

			console.log('Ending game setup');
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
			this.floor = this.createAndPopulateStock(this.gamedatas.floor, 'floor'),

			this.players = [];

			var current_player = this.getThisPlayerId();
			for (var i = 0; i < this.gamedatas.playerorder.length; i++) {
				var player_id = this.gamedatas.playerorder[i]
				var player = this.gamedatas.players[player_id];
				dojo.place(this.format_block((current_player == player.id) ? 'jstpl_playerTable' : 'jstpl_otherPlayerTable', player), 'player_table', 'last');

				if (player.initial_task) {
					player.task = {'9999': {id: '9999', type_arg: '9999'}};
				} else if (player.task) {
					var task = player.task;
					player.task = {};
					player.task[task.id] = task;
				} else {
					player.task = {};
				}

				// TODO: Show opponent's revealed hand
				this.players[player.id] = {
					task: this.createAndPopulateStock(player.task, 'player_' + player.id + '_task'),
					gallery: this.createAndPopulateStock(player.gallery, 'player_' + player.id + '_gallery'),
					gift_shop: this.createAndPopulateStock(player.gift_shop, 'player_' + player.id + '_gift_shop'),
					helpers: this.createAndPopulateStock(player.helpers, 'player_' + player.id + '_helpers'),
					craft_bench: this.createAndPopulateStock(player.craft_bench, 'player_' + player.id + '_craft_bench'),
					sales: this.createAndPopulateStock(player.sales, 'player_' + player.id + '_sales'),
				}

				if (current_player != player_id) {
					this.players[player_id].hand_size = new ebg.counter();
					this.players[player_id].hand_size.create('player_' + player_id + '_hand_size');
					this.players[player_id].hand_size.setValue(player.hand_count);
				} else {
					this.playerHand = this.createAndPopulateStock(this.gamedatas.hand, 'player_' + player.id + '_hand');
				}

				this.players[player_id].waiting_area = new ebg.counter();
				this.players[player_id].waiting_area.create('player_' + player_id + '_waiting_area');
				this.players[player_id].waiting_area.setValue(player.waiting_area_count);
			}
		},

		createAndPopulateStock: function(card_list, element_id) {
			var stock = new ebg.stock();
			stock.jstpl_stock_item= "<div id=\"${id}\" class=\"card\" ></div>";
			stock.centerItems = false;
			// stock.setSelectionMode(0);
			stock.create(this, $(element_id), 5, 5);
			stock.onItemCreate = dojo.hitch(this, 'setupNewCard');
			for (c in this.gamedatas.cards) {
				stock.addItemType(this.gamedatas.cards[c].id, 1, '');
			}
			stock.addItemType('9999', 1, '');
			this.addCardsToStock(card_list, stock);
			return stock;
		},

		setupNewCard: function(card_div, card_type_id, card_id) {
			if (card_type_id == '9999') {
				card_div.innerHTML = 'Hidden';
			} else {
				var card = this.gamedatas.cards[card_type_id];
				var material = this.gamedatas.materials[card.material];
				card_div.innerHTML = card.name + ' ' + material.symbol;
			}
		},

		addCardsToStock: function(cards, stock) {
			for (c in cards) {
				var card = cards[c];
				stock.addToStockWithId(card.type_arg, card.id);
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
				case 'chooseAction':
				   this.addActionButton('button_1_id', _('Pray'), 'doPray');
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
			var card_id = dojo.attr(event.target, 'id').split('_').pop();
			this.ajaxAction('chooseNewTask', {
				id: card_id,
			});
		},

		doPray: function(event) {
			dojo.stopEvent(event);
			if (!this.checkAction('chooseAction')) return;
			this.ajaxAction('chooseAction', {
				action_: 'pray',
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
		setupNotifications: function() {
			console.log('notifications subscriptions setup');

			dojo.subscribe('discardOldTask', this, 'notif_discardOldTask');
			this.notifqueue.setSynchronous('discardOldTask', 1000);
			dojo.subscribe('chooseNewTask', this, 'notif_chooseNewTask');
			dojo.subscribe('chooseActionPray', this, 'notif_chooseActionPray');
			if (this.isSpectator) {
				dojo.subscribe('drawWaitingAreaSpectator', this, 'notif_drawWaitingAreaSpectator');
			} else {
				dojo.subscribe('drawWaitingArea', this, 'notif_drawWaitingArea');
			}
		},

		notif_discardOldTask: function(notif) {
			var card_id = notif.args.card_id;
			var card_type = notif.args.card_type;
			var player_id = notif.args.player_id;
			if (!notif.args.initial) {
				this.players[player_id].task.removeFromStockById(card_id);
			} else {
				this.players[player_id].task.removeFromStockById('9999');
			}
			this.floor.addToStockWithId(card_type, card_id, this.players[player_id].task.container_div);
		},

		notif_chooseNewTask: function(notif) {
			var card_id = notif.args.card_id;
			if (!card_id) return;
			var card_type = notif.args.card_type;
			var player_id = notif.args.player_id;
			if (player_id == this.getThisPlayerId()) {
				this.playerHand.removeFromStockById(card_id);
				this.players[player_id].task.addToStockWithId(card_type, card_id, this.playerHand.container_div);
			} else {
				this.players[player_id].hand_size.incValue(-1);
				this.players[player_id].task.addToStockWithId(card_type, card_id);
			}
		},

		notif_chooseActionPray: function(notif) {
			var player_id = notif.args.player_id;
			this.players[player_id].waiting_area.incValue(1);
		},

		notif_drawWaitingAreaSpectator: function(notif) {
			this.notif_drawWaitingArea(notif);
		},

		notif_drawWaitingArea: function(notif) {
			var player_id = notif.args.player_id;
			var card_count = notif.args.ca;
			this.players[player_id].waiting_area.setValue(0);
			if (player_id == this.getThisPlayerId()) {
				var cards = notif.args.cards;
				for (c in cards) {
					var card = cards[c];
					this.playerHand.addToStockWithId(card.type_arg, card.id, 'player_' + player_id + '_waiting_area');
				}
			} else {
				this.players[player_id].hand_size.incValue(notif.args.card_count);
			}
		},
	});
});
