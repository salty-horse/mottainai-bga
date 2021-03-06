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

'use strict';

define([
	'dojo','dojo/_base/declare',
	'ebg/core/gamegui',
	'ebg/counter',
	'ebg/stock',
],
function(dojo, declare) {
	return declare('bgagame.mottainai', ebg.core.gamegui, {
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

		setup: function(gamedatas) {
			console.log('Starting game setup');
			console.log('gamedatas', gamedatas);

			for (let [k, v] of Object.entries(gamedatas['card_id_to_type'])) {
				gamedatas['card_id_to_type'][k] = gamedatas.cards[v];
			}

			for (let v of Object.values(gamedatas['cards'])) {
				v.material = gamedatas.materials[v.material];
			}

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
			dojo.create('div', {class: 'table whiteblock', innerHTML: '<div><span class="table_label">Deck:</span><div class="card_list" id="deck_count"></div></div><h3>Floor</h3><div id="floor"></div>'}, 'player_table', 'last');
			this.floor = this.createAndPopulateStock(this.gamedatas.floor, 'floor'),
			this.deck_count = new ebg.counter();
			this.deck_count.create('deck_count');
			this.deck_count.setValue(this.gamedatas.deck_count);

			this.players = [];

			let current_player = this.getThisPlayerId();
			for (let player_id of this.gamedatas.playerorder) {
				let player = this.gamedatas.players[player_id];
				dojo.place(this.format_block((current_player == player.id) ? 'jstpl_playerTable' : 'jstpl_otherPlayerTable', player), 'player_table', 'last');

				if (player.initial_task) {
					player.task = {'9999': {id: '9999', type_arg: '9999'}};
				} else if (player.task) {
					let task = player.task;
					player.task = {};
					player.task[task.id] = task;
				} else {
					player.task = {};
				}

				// TODO: Show opponent's revealed hand
				this.players[player.id] = {
					task: this.createAndPopulateStock(player.task, `player_${player.id}_task`),
					gallery: this.createAndPopulateStock(player.gallery, `player_${player.id}_gallery`),
					gift_shop: this.createAndPopulateStock(player.gift_shop, `player_${player.id}_gift_shop`),
					helpers: this.createAndPopulateStock(player.helpers, `player_${player.id}_helpers`),
					craft_bench: this.createAndPopulateStock(player.craft_bench, `player_${player.id}_craft_bench`),
					sales: this.createAndPopulateStock(player.sales, `player_${player.id}_sales`),
				}

				if (current_player != player_id) {
					this.players[player_id].hand_count = new ebg.counter();
					this.players[player_id].hand_count.create(`player_${player_id}_hand_count`);
					this.players[player_id].hand_count.setValue(player.hand_count);
				} else {
					this.playerHand = this.createAndPopulateStock(this.gamedatas.hand, `player_${player.id}_hand`);
				}

				this.players[player_id].waiting_area = new ebg.counter();
				this.players[player_id].waiting_area.create(`player_${player_id}_waiting_area`);
				this.players[player_id].waiting_area.setValue(player.waiting_area_count);
			}
		},

		createAndPopulateStock: function(card_list, element_id) {
			let stock = new ebg.stock();
			stock.jstpl_stock_item= "<div id=\"${id}\" class=\"card\" ></div>";
			stock.centerItems = false;
			// stock.setSelectionMode(0);
			stock.setSelectionAppearance('');
			stock.create(this, $(element_id), 5, 5);
			stock.onItemCreate = dojo.hitch(this, 'setupNewCard');
			for (let c in this.gamedatas.cards) {
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
				let card = this.gamedatas.cards[card_type_id];
				card_div.innerHTML = `${card.name} ${card.material.symbol}`;
			}
		},

		addCardsToStock: function(cards, stock) {
			for (let c in cards) {
				let card = cards[c];
				stock.addToStockWithId(card.type_arg, card.id);
			}
		},

		createCardElement: function(cardObj) {
			let card = this.gamedatas.cards[cardObj.type_arg];
			let elem = dojo.create('div', {
				id: `card_${cardObj.id}`,
				class: 'card',
				innerHTML: `${card.name} ${card.material.symbol}`,
			});

			return elem;
		},

		addCardElements: function(cards, element) {
			for (let c in cards) {
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
			console.log('Entering state', stateName, args);
			let player_id = this.getThisPlayerId();

			switch (stateName) {
			case 'reduceHand':
				if (!this.isCurrentPlayerActive())
					break;

				this.reduceHandCards = new Set();
				this.reduceHandTarget = args.args.count;
				dojo.query(`#player_${player_id}_hand > .card`).forEach((node, index, arr) => {
					this.selectableElements.push(node);
					node.classList.add('selectable');
					this.connections.push(dojo.connect(node, 'onclick', this, 'onChoosingReduceHand'));
				});
				break;
			case 'chooseNewTask':
				if (!this.isCurrentPlayerActive())
					break;

				dojo.query(`#player_${player_id}_hand > .card`).forEach((node, index, arr) => {
					this.selectableElements.push(node);
					node.classList.add('selectable');
					this.connections.push(dojo.connect(node, 'onclick', this, 'onChoosingTask'));
				});
				break;
			case 'client_doClerk':
				if (!this.isCurrentPlayerActive())
					break;

				dojo.query(`#player_${player_id}_craft_bench > .card`).forEach((node, index, arr) => {
					this.selectableElements.push(node);
					node.classList.add('selectable');
					this.connections.push(dojo.connect(node, 'onclick', this, 'onChoosingClerkCard'));
				});
				break;
			case 'client_doMonk':
				if (!this.isCurrentPlayerActive())
					break;

				dojo.query('#floor > .card').forEach((node, index, arr) => {
					this.selectableElements.push(node);
					node.classList.add('selectable');
					this.connections.push(dojo.connect(node, 'onclick', this, 'onChoosingMonkFloorCard'));
				});
				break;
			case 'client_doTailor':
				if (!this.isCurrentPlayerActive())
					break;
				dojo.query(`#player_${player_id}_hand > .card`).forEach((node, index, arr) => {
					this.selectableElements.push(node);
					node.classList.add('selectable');
					this.connections.push(dojo.connect(node, 'onclick', this, 'onChoosingTailorHandCard'));
				});
				break;

			case 'client_doPotter':
				if (!this.isCurrentPlayerActive())
					break;

				dojo.query('#floor > .card').forEach((node, index, arr) => {
					this.selectableElements.push(node);
					node.classList.add('selectable');
					this.connections.push(dojo.connect(node, 'onclick', this, 'onChoosingPotterFloorCard'));
				});
				break;
			case 'client_doSmith':
				if (!this.isCurrentPlayerActive())
					break;
				for (let card_id of this.getSmithableCards()) {
					let node = document.getElementById(`player_${player_id}_hand_item_${card_id}`);
					this.selectableElements.push(node);
					node.classList.add('selectable');
					this.connections.push(dojo.connect(node, 'onclick', this, 'onSelectingCardToSmith'));
				}
				break;
			case 'client_selectSmithRevealCards':
				if (!this.isCurrentPlayerActive())
					break;
				dojo.query(`#player_${player_id}_hand > .card`).forEach((node, index, arr) => {
					let card_id = node.id.split('_').pop();
					if (card_id == this.clientStateArgs.card_id)
						return;
					let card_material = this.gamedatas.card_id_to_type[card_id].material.id;
					if (card_material != this.clientStateArgs.material_id)
						return;
					this.selectableElements.push(node);
					node.classList.add('selectable');
					this.connections.push(dojo.connect(node, 'onclick', this, 'onChoosingSmithRevealCard'));
				});
				break;
			case 'client_doCraft':
				if (!this.isCurrentPlayerActive())
					break;

				for (let card_id of this.getCraftableCards(this.clientStateArgs.material)) {
					let node = document.getElementById(`player_${player_id}_hand_item_${card_id}`);
					this.selectableElements.push(node);
					node.classList.add('selectable');
					this.connections.push(dojo.connect(node, 'onclick', this, 'onSelectingCardToCraft'));
				}
				break;
			}
		},

		// onLeavingState: this method is called each time we are leaving a game state.
		//				 You can use this method to perform some user interface changes at this moment.
		//
		onLeavingState: function(stateName) {
			console.log('Leaving state:', stateName);
			for (let elem of this.selectableElements) {
				elem.classList.remove('selected', 'selectable');
			};
			for (let handle of this.connections) {
				dojo.disconnect(handle);
			};
		},

		// onUpdateActionButtons: in this method you can manage "action buttons" that are displayed in the
		//						action status bar (ie: the HTML links in the status bar).
		//
		onUpdateActionButtons: function(stateName, args) {
			console.log('onUpdateActionButtons:', stateName);

			if (this.isCurrentPlayerActive()) {
				let player_id = this.getThisPlayerId();
				switch (stateName) {
				case 'reduceHand':
					this.addActionButton('button_reduce', _('Return card(s)'), 'doReduceHand', null, false, 'gray');
					break;
				case 'chooseNewTask':
					this.addActionButton('button_1_id', _('Skip'), 'doSkipNewTask');
					break;
				case 'chooseAction':
					this.clientStateArgs = {};
					let task_id = args.task_id;
					if (this.canPerformTask(task_id)) {
						if (task_id == 1) { // Clerk
							this.addActionButton('button_1_id', _('Clerk: Sell a material'), 'doClerk');
						} else if (task_id == 2) { // Monk
							this.addActionButton('button_1_id', _('Monk: Hire a helper'), 'doMonk');
						} else if (task_id == 3) { // Tailor
							let label;
							if (this.players[player_id].waiting_area.getValue() < 5) {
								label = _('Tailor: Refill your hand')
							} else {
								label = _('Tailor: Discard your hand')
							}
							this.addActionButton('button_1_id', label, 'doTailor');
						} else if (task_id == 4) { // Potter
							this.addActionButton('button_1_id', _('Potter: Collect a material'), 'doPotter');
						} else if (task_id == 5) { // Smith
							this.addActionButton('button_1_id', _('Smith: Complete any work'), 'doSmith');
						}
					}
					if (this.canPerformCraft(task_id)) {
						let craft_string = dojo.string.substitute(_('Craft a ${material} work'), {
							material: this.gamedatas.materials[task_id].name
						});
						this.clientStateArgs.material = task_id;
						this.addActionButton('button_6_id', craft_string, 'doCraft');
					}
					// TODO: Allow praying for all remaining actions
					this.addActionButton('button_7_id', _('Pray'), 'doPray');
					break;
				case 'client_doPotter':
					this.addActionButton('button_1_id', _('Cancel'), () => {
						this.restoreServerGameState();
					});
					break;
				case 'client_doClerk':
				case 'client_doMonk':
				case 'client_doPotter':
				case 'client_doSmith':
				case 'client_doCraft':
					this.addActionButton('button_1_id', _('Cancel'), () => {
						this.restoreServerGameState();
					});
					break;
				case 'client_doTailor':
					this.addActionButton('button_tailor', this.getTailorButtonText(0), 'confirmTailor');
					this.addActionButton('button_1_id', _('Cancel'), () => {
						this.restoreServerGameState();
					});
					break;
				case 'client_selectSmithCraftWing':
					this.addActionButton('button_1_id', _('Gallery'), event => {
						this.doSmithCraftWing(event, 'gallery');
					});
					this.addActionButton('button_2_id', _('Gift Shop'), event => {
						this.doSmithCraftWing(event, 'gift_shop');
					});
					this.addActionButton('button_3_id', _('Cancel'), () => {
						this.restoreServerGameState();
					});
					break;
				case 'client_selectSmithRevealCards':
					this.addActionButton('button_smith_reveal', _('Confirm'), () => {
						this.setClientState('client_selectSmithCraftWing', {
							descriptionmyturn: _('Select a wing for ${card_name}'),
							args: {
								card_name: this.gamedatas.card_id_to_type[this.clientStateArgs.card_id].name,
							}
						});
					}, null, false, 'gray');
					this.addActionButton('button_3_id', _('Cancel'), () => {
						this.restoreServerGameState();
					});
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
			if (typeof func == 'undefined' || func == null) {
				func = result => {};
			}

			let name = this.game_name;
			this.ajaxcall(`/${name}/${name}/${action}.html`, args, this, func, err);
		},

		calculatePlayerScore: function(player_id) {
			let score = 0;

			// TODO: Kite

			// Gallery works
			for (let card of this.players[player_id].gallery.getAllItems()) {
				score += this.gamedatas.cards[card.type].material.value;
			}

			// Gift Shop works
			let giftShopByMaterial = {1: 0, 2: 0, 3: 0, 4: 0, 5: 0};
			for (let card of this.players[player_id].gift_shop.getAllItems()) {
				let material = this.gamedatas.cards[card.type].material;
				score += material.value;
				giftShopByMaterial[material.id] += 1;
			}

			// TODO: Works that affect sales: Pillar, Quilt, Go Set

			// Covered sales
			let salesByMaterial = this.countStockByMaterial(this.players[player_id].sales);
			for (let material_id in salesByMaterial) {
				if (!salesByMaterial[material_id]) continue;
				let material_value = this.gamedatas.materials[material_id].value;
				if (salesByMaterial[material_id] <= giftShopByMaterial[material_id] * material_value) {
					score += salesByMaterial[material_id] * material_value;
				}
			}

			// TODO: Works that give points: Scroll, Bench, Tapestry, Haniwa, Teapot

			this.scoreCtrl[player_id].setValue(score);
		},

		canPerformTask: function(task_id) {
			let player_id = this.getThisPlayerId();
			if (task_id == 1) { // Clerk
				return this.players[player_id].craft_bench.count() > 0;
			} else if (task_id == 2 || task_id == 4) { // Monk or Potter
				return this.floor.count() > 0;
			} else if (task_id == 3) { // Tailor
				return this.players[player_id].waiting_area.getValue() < 5;
			} else if (task_id == 5) { // Smith
				// TODO: Consider Crane effect that reduces cost
				// TODO: Consider Straw effect that reduces cost
				// TODO: Consider Brick effect that uses tasks for support
				let handMaterials = this.countStockByMaterial(this.playerHand);
				let items = this.playerHand.getAllItems();
				for (let card of items) {
					let material = this.gamedatas.cards[card.type].material;
					if (handMaterials[material.id] >= material.value)
						return true;
				}
			}

			return false;
		},

		canPerformSmith: function() {
			return Boolean(this.getSmithableCards().next().value);
		},

		getSmithableCards: function*() {
			// TODO: Consider Crane, Straw, Brick effects that reduce cost
			let benchMaterials = this.countStockByMaterial(this.playerHand);
			let items = this.playerHand.getAllItems();
			for (let card of items) {
				let material = this.gamedatas.cards[card.type].material;
				if (benchMaterials[material.id] >= material.value)
					yield card.id;
			}
		},

		canPerformCraft: function(material_type) {
			return Boolean(this.getCraftableCards(material_type).next().value);
		},

		getCraftableCards: function*(material_type) {
			// TODO: Consider Crane effect that reduces cost
			let benchMaterials = this.countStockByMaterial(this.players[this.getThisPlayerId()].craft_bench);
			let items = this.playerHand.getAllItems();
			for (let card of items) {
				let material = this.gamedatas.cards[card.type].material;
				if (material.id != material_type)
					continue;
				if (benchMaterials[material] >= material.value - 1)
					yield card.id;
			}
		},

		countStockByMaterial: function(stock) {
			let stockByMaterial = {1: 0, 2: 0, 3: 0, 4: 0, 5: 0};
			for (let card of stock.getAllItems()) {
				let material = this.gamedatas.cards[card.type].material;
				stockByMaterial[material.id] += 1;
			}
			return stockByMaterial;
		},

		getTailorButtonText: function(return_count) {
			let draw_count = Math.max(0, 5 - (this.playerHand.count() - this.tailorHandCards.size) -
				this.players[this.getCurrentPlayerId()].waiting_area.getValue());
			return dojo.string.substitute(_('Return ${return} cards & draw ${draw}'), {
				return: return_count,
				draw: draw_count,
			});
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

		doReduceHand: function(event) {
			dojo.stopEvent(event);
			if (!this.checkAction('reduceHand')) return;
			if (this.reduceHandCards.size < this.reduceHandTarget) return;
			this.ajaxAction('reduceHand', {
				ids: [...this.reduceHandCards].join(','),
			});
		},

		doSkipNewTask: function(event) {
			dojo.stopEvent(event);
			if (!this.checkAction('chooseNewTask')) return;
			this.ajaxAction('chooseNewTask');
		},

		onChoosingReduceHand: function(event) {
			dojo.stopEvent(event);
			if (this.isInterfaceLocked()) return;
			if (!this.checkAction('reduceHand')) return;
			let elem = event.target;
			let card_id = elem.id.split('_').pop();
			if (elem.classList.contains('selected')) {
				elem.classList.add('selectable');
				elem.classList.remove('selected');
				this.reduceHandCards.delete(card_id);
			} else if (this.reduceHandCards.size < this.reduceHandTarget) {
				elem.classList.add('selected');
				elem.classList.remove('selectable');
				this.reduceHandCards.add(card_id);
			}

			let buttonElem = document.getElementById('button_reduce');
			if (this.reduceHandCards.size == this.reduceHandTarget) {
				buttonElem.classList.add('bgabutton_blue');
				buttonElem.classList.remove('bgabutton_gray');
			} else {
				buttonElem.classList.add('bgabutton_gray');
				buttonElem.classList.remove('bgabutton_blue');
			}
		},

		onChoosingTask: function(event) {
			dojo.stopEvent(event);
			if (this.isInterfaceLocked()) return;
			if (!this.checkAction('chooseNewTask')) return;
			let card_id = dojo.attr(event.target, 'id').split('_').pop();
			this.ajaxAction('chooseNewTask', {
				id: card_id,
			});
		},

		onChoosingClerkCard: function(event) {
			dojo.stopEvent(event);
			if (this.isInterfaceLocked()) return;
			if (!this.checkAction('chooseAction')) return;
			let card_id = dojo.attr(event.target, 'id').split('_').pop();
			this.ajaxAction('chooseAction', {
				action_: 'clerk',
				card_id: card_id,
			});
		},

		onChoosingMonkFloorCard: function(event) {
			dojo.stopEvent(event);
			if (this.isInterfaceLocked()) return;
			if (!this.checkAction('chooseAction')) return;
			let card_id = dojo.attr(event.target, 'id').split('_').pop();
			this.ajaxAction('chooseAction', {
				action_: 'monk',
				card_id: card_id,
			});
		},

		onChoosingTailorHandCard: function(event) {
			dojo.stopEvent(event);
			if (this.isInterfaceLocked()) return;
			if (!this.checkAction('chooseAction')) return;
			let elem = event.target;
			let card_id = elem.id.split('_').pop();
			if (elem.classList.contains('selected')) {
				elem.classList.add('selectable');
				elem.classList.remove('selected');
				this.tailorHandCards.delete(card_id);
			} else {
				elem.classList.add('selected');
				elem.classList.remove('selectable');
				this.tailorHandCards.add(card_id);
			}

			document.getElementById('button_tailor').textContent = this.getTailorButtonText(this.tailorHandCards.size);
		},

		confirmTailor: function(event) {
			this.ajaxAction('chooseAction', {
				action_: 'tailor',
				card_list: [...this.tailorHandCards].join(','),
			});
		},

		onChoosingPotterFloorCard: function(event) {
			dojo.stopEvent(event);
			if (this.isInterfaceLocked()) return;
			if (!this.checkAction('chooseAction')) return;
			let card_id = dojo.attr(event.target, 'id').split('_').pop();
			this.ajaxAction('chooseAction', {
				action_: 'potter',
				card_id: card_id,
			});
		},

		onSelectingCardToSmith: function(event) {
			let card_id = dojo.attr(event.target, 'id').split('_').pop();
			this.clientStateArgs.card_id = card_id;
			let card_material = this.gamedatas.card_id_to_type[card_id].material;
			// TODO: Count already-revealed cards
			let support_required = card_material.value - 1;
			if (support_required) {
				this.clientStateArgs.material_id = card_material.id;
				this.clientStateArgs.support_required = support_required;
				this.setClientState('client_selectSmithRevealCards', {
					descriptionmyturn: _('Select ${count} ${material} card(s) to reveal'),
					args: {
						count: support_required,
						material: card_material.name,
					}
				});
			} else {
				this.setClientState('client_selectSmithCraftWing', {
					descriptionmyturn: _('Select a wing for ${card_name}'),
					args: {
						card_name: this.gamedatas.card_id_to_type[card_id].name,
					}
				});
			}
		},

		onChoosingSmithRevealCard: function(event) {
			dojo.stopEvent(event);
			if (this.isInterfaceLocked()) return;
			if (!this.checkAction('chooseAction')) return;
			let elem = event.target;
			let card_id = elem.id.split('_').pop();
			if (elem.classList.contains('selected')) {
				elem.classList.add('selectable');
				elem.classList.remove('selected');
				this.smithRevealCards.delete(card_id);
			} else if (this.smithRevealCards.size < this.clientStateArgs.support_required) {
				elem.classList.add('selected');
				elem.classList.remove('selectable');
				this.smithRevealCards.add(card_id);
			}

			let buttonElem = document.getElementById('button_smith_reveal');
			if (this.smithRevealCards.size == this.clientStateArgs.support_required) {
				buttonElem.classList.add('bgabutton_blue');
				buttonElem.classList.remove('bgabutton_gray');
			} else {
				buttonElem.classList.add('bgabutton_gray');
				buttonElem.classList.remove('bgabutton_blue');
			}
		},

		confirmSmithReveal: function(event) {
			// TODO
		},

		onSelectingCardToCraft: function(event) {
			let card_id = dojo.attr(event.target, 'id').split('_').pop();
			this.clientStateArgs.card_id = card_id;
			this.setClientState('client_selectSmithCraftWing', {
				descriptionmyturn: _('Select a wing for ${card_name}'),
				args: {
					card_name: this.gamedatas.card_id_to_type[card_id].name,
				}
			});
		},

		doClerk: function(event) {
			dojo.stopEvent(event);
			// TODO: Robe, Bell
			this.setClientState('client_doClerk', {
				descriptionmyturn: _('Select a card from the Craft Bench to sell'),
			});
		},

		doMonk: function(event) {
			dojo.stopEvent(event);
			// TODO: Flute, Sword
			this.setClientState('client_doMonk', {
				descriptionmyturn: _('Select a card from the Floor to add to your Helpers'),
			});
		},

		doTailor: function(event) {
			dojo.stopEvent(event);
			this.tailorHandCards = new Set();
			this.setClientState('client_doTailor', {
				descriptionmyturn: _('Select any cards from your hand to return to the deck'),
			});
		},

		doPotter: function(event) {
			dojo.stopEvent(event);
			// TODO: Socks, Flute, Sword
			this.setClientState('client_doPotter', {
				descriptionmyturn: _('Select a card from the Floor to add to your Craft Bench'),
			});
		},

		doSmith: function(event) {
			this.clientStateArgs.action = 'smith';
			this.smithRevealCards = new Set();
			this.setClientState('client_doSmith', {
				descriptionmyturn: _('Select a card to smith'),
			});
		},

		doCraft: function(event) {
			this.clientStateArgs.action = 'craft';
			this.setClientState('client_doCraft', {
				descriptionmyturn: _('Select a ${material_name} card to craft'),
				args: {
					material_name: this.gamedatas.materials[this.clientStateArgs.material].name,
				}
			});
		},

		doSmithCraftWing: function(event, wing) {
			dojo.stopEvent(event);
			let args = {
				action_: this.clientStateArgs.action,
				card_id: this.clientStateArgs.card_id,
				wing: wing,
			};
			if (this.smithRevealCards && this.smithRevealCards.size) {
				args.card_list = [...this.smithRevealCards].join(',');
			}
			this.ajaxAction('chooseAction', args);
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
			dojo.subscribe('chooseClerkCard', this, 'notif_chooseClerkCard');
			dojo.subscribe('chooseMonkCardFloor', this, 'notif_chooseMonkCardFloor');
			dojo.subscribe('choosePotterCardFloor', this, 'notif_choosePotterCardFloor');
			dojo.subscribe('chooseActionPray', this, 'notif_chooseActionPray');
			dojo.subscribe('smithedWork', this, 'notif_smithedWork');
			dojo.subscribe('craftedWork', this, 'notif_craftedWork');
			if (this.isSpectator) {
				dojo.subscribe('drawWaitingAreaSpectator', this, 'notif_drawWaitingAreaSpectator');
				dojo.subscribe('returnCardsSpectator', this, 'notif_returnCardsSpectator');
				dojo.subscribe('chooseTailorCardsSpectator', this, 'notif_chooseTailorCardsSpectator');
			} else {
				dojo.subscribe('drawWaitingArea', this, 'notif_drawWaitingArea');
				dojo.subscribe('returnCards', this, 'notif_returnCards');
				dojo.subscribe('chooseTailorCards', this, 'notif_chooseTailorCards');
			}
		},

		notif_discardOldTask: function(notif) {
			let card_id = notif.args.card_id;
			let card_type = this.gamedatas.card_id_to_type[card_id].id;
			let player_id = notif.args.player_id;
			if (!notif.args.initial) {
				this.players[player_id].task.removeFromStockById(card_id);
			} else {
				this.players[player_id].task.removeFromStockById('9999');
			}
			this.floor.addToStockWithId(card_type, card_id, this.players[player_id].task.container_div);
		},

		notif_chooseNewTask: function(notif) {
			let card_id = notif.args.card_id;
			if (!card_id) return;
			let card_type = this.gamedatas.card_id_to_type[card_id].id;
			let player_id = notif.args.player_id;
			if (player_id == this.getThisPlayerId()) {
				this.playerHand.removeFromStockById(card_id);
				this.players[player_id].task.addToStockWithId(card_type, card_id, this.playerHand.container_div);
			} else {
				this.players[player_id].hand_count.incValue(-1);
				this.players[player_id].task.addToStockWithId(card_type, card_id);
			}
		},

		notif_chooseClerkCard: function(notif) {
			let card_id = notif.args.card_id;
			if (!card_id) return;
			let card_type = this.gamedatas.card_id_to_type[card_id].id;
			let player_id = notif.args.player_id;
			this.players[player_id].craft_bench.removeFromStockById(card_id);
			this.players[player_id].sales.addToStockWithId(card_type, card_id, this.players[player_id].craft_bench.container_div);
			this.calculatePlayerScore(player_id);
		},

		notif_chooseMonkCardFloor: function(notif) {
			let card_id = notif.args.card_id;
			if (!card_id) return;
			let card_type = this.gamedatas.card_id_to_type[card_id].id;
			let player_id = notif.args.player_id;
			this.floor.removeFromStockById(card_id);
			this.players[player_id].helpers.addToStockWithId(card_type, card_id, this.floor.container_div);
		},

		notif_choosePotterCardFloor: function(notif) {
			let card_id = notif.args.card_id;
			if (!card_id) return;
			let card_type = this.gamedatas.card_id_to_type[card_id].id;
			let player_id = notif.args.player_id;
			this.floor.removeFromStockById(card_id);
			this.players[player_id].craft_bench.addToStockWithId(card_type, card_id, this.floor.container_div);
		},

		notif_chooseActionPray: function(notif) {
			let player_id = notif.args.player_id;
			this.deck_count.incValue(-1);
			this.players[player_id].waiting_area.incValue(1);
		},

		notif_smithedWork: function(notif) {
			let card_id = notif.args.card_id;
			if (!card_id) return;
			let card_info = this.gamedatas.card_id_to_type[card_id];
			let player_id = notif.args.player_id;
			if (player_id == this.getThisPlayerId()) {
				this.playerHand.removeFromStockById(card_id);
			} else {
				this.players[player_id].hand_count.incValue(-1);
			}
			this.players[player_id][notif.args.wing].addToStockWithId(card_info.id, card_id);

			this.calculatePlayerScore(player_id);
		},

		notif_craftedWork: function(notif) {
			let card_id = notif.args.card_id;
			if (!card_id) return;
			let card_type = this.gamedatas.card_id_to_type[card_id].id;
			let player_id = notif.args.player_id;
			if (player_id == this.getThisPlayerId()) {
				this.playerHand.removeFromStockById(card_id);
			} else {
				this.players[player_id].hand_count.incValue(-1);
			}
			this.players[player_id][notif.args.wing].addToStockWithId(card_type, card_id);

			this.calculatePlayerScore(player_id);
		},

		notif_drawWaitingAreaSpectator: function(notif) {
			this.notif_drawWaitingArea(notif);
		},

		notif_drawWaitingArea: function(notif) {
			let player_id = notif.args.player_id;
			let card_count = notif.args.ca;
			this.players[player_id].waiting_area.setValue(0);
			if (player_id == this.getThisPlayerId() && !this.isSpectator) {
				let cards = notif.args.cards;
				for (let c in cards) {
					let card = cards[c];
					this.playerHand.addToStockWithId(card.type_arg, card.id, this.players[player_id].waiting_area.container_div);
				}
				this.playerHand.updateDisplay();
			} else {
				this.players[player_id].hand_count.incValue(notif.args.card_count);
			}
		},

		notif_returnCardsSpectator: function(notif) {
			this.notif_returnCards(notif);
		},

		notif_returnCards: function(notif) {
			let player_id = notif.args.player_id;
			let card_count = notif.args.card_count;
			this.players[player_id].waiting_area.setValue(0);
			if (player_id == this.getThisPlayerId() && !this.isSpectator) {
				let cards = notif.args.cards;
				for (let c of cards) {
					this.playerHand.removeFromStockById(c, 'deck_count', true);
				}
				this.playerHand.updateDisplay();
			} else {
				this.players[player_id].hand_count.incValue(-card_count);
			}
			this.deck_count.incValue(card_count);
			// TODO: Hide revealed cards
		},

		notif_chooseTailorCardsSpectator: function(notif) {
			this.notif_returnCards(notif);
		},

		notif_chooseTailorCards: function(notif) {
			let player_id = notif.args.player_id;
			let return_count = notif.args.return_count;
			let draw_count = notif.args.draw_count;
			this.players[player_id].waiting_area.setValue(0);
			if (player_id == this.getThisPlayerId() && !this.isSpectator) {
				let cards = notif.args.cards;
				for (let c of cards) {
					this.playerHand.removeFromStockById(c, 'deck_count', true);
				}
				this.playerHand.updateDisplay();
			} else {
				this.players[player_id].hand_count.incValue(-return_count);
			}
			this.players[player_id].waiting_area.incValue(draw_count);
			this.deck_count.incValue(return_count - draw_count);
			// TODO: Hide revealed cards
		},
	});
});
