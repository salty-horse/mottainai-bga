<?php
 /**
  *------
  * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
  * Mottainai implementation : © Ori Avtalion <ori@avtalion.name>
  * 
  * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
  * See http://en.boardgamearena.com/#!doc/Studio for more information.
  * -----
  * 
  * mottainai.game.php
  *
  * This is the main file for your game logic.
  *
  * In this PHP file, you are going to defines the rules of the game.
  *
  */


require_once(APP_GAMEMODULE_PATH.'module/table/table.game.php');

require_once('modules/MOT_Utils.php');

class Mottainai extends Table {

    function __construct() {

        // Your global variables labels:
        //  Here, you can assign labels to global variables you are using for this game.
        //  You can use any number of global variables with IDs between 10 and 99.
        //  If your game has options (variants), you also have to associate here a label to
        //  the corresponding ID in gameoptions.inc.php.
        // Note: afterwards, you can get/set the global variables with getGameStateValue/setGameStateInitialValue/setGameStateValue
        
        parent::__construct();
        self::initGameStateLabels([
            "currentTaskPlayerId" => 10, // The player ID whose task is currently being performed
            "currentTask" => 11,
            "currentTaskActionCurrent" => 12,
            "currentTaskActionTotal" => 13,
            "completedCard" => 14,
            "maxWorksInWing" => 100,
        ]);
        
        $this->deck = self::getNew("module.common.deck");
        $this->deck->init("card");
    }

    protected function getGameName()
    {
        // Used for translations and stuff. Please do not modify.
        return "mottainai";
    }

    /*
        setupNewGame:

        This method is called only once, when a new game is launched.
        In this method, you must setup the game according to the game rules, so that
        the game is ready to be played.
    */
    protected function setupNewGame($players, $options = [])
    {
        // Set the colors of the players with HTML color code
        // The default below is red/green/blue/orange/brown
        // The number of colors defined here must correspond to the maximum number of players allowed for the game
        $default_colors = ["ff0000", "008000", "0000ff", "ffa500", "773300"];

        // Create players
        // Note: if you added some extra field on "player" table in the database (dbmodel.sql), you can initialize it there.
        $sql = "INSERT INTO player (player_id, player_color, player_canal, player_name, player_avatar) VALUES ";
        $values = [];
        foreach($players as $player_id => $player)
        {
            $color = array_shift($default_colors);
            $values[] = "('".$player_id."','$color','".$player['player_canal']."','".addslashes($player['player_name'])."','".addslashes($player['player_avatar'])."')";
        }
        $sql .= implode($values, ',');
        self::DbQuery($sql);
        self::reattributeColorsBasedOnPreferences($players, ["ff0000", "008000", "0000ff", "ffa500", "773300"]);
        self::reloadPlayersBasicInfos();

        /************ Start the game initialization *****/

        // Init global values with their initial values

        self::setGameStateInitialValue('currentTaskPlayerId', 0);
        self::setGameStateInitialValue('currentTask', 0);
        self::setGameStateInitialValue('currentTaskActionCurrent', 0);
        self::setGameStateInitialValue('currentTaskActionTotal', 0);
        self::setGameStateInitialValue('completedCard', 0);

        // Init game statistics
        self::initStat('player', 'turns_number', 0);
        self::initStat('table', 'turns_number', 1);

        // Create cards
        $cards = [];
        $card_copies = count($players) > 3 ? 2 : 1;
        foreach ($this->cards as $card_id => $card) {
            $cards[] = [
                'type' => '',
                'type_arg' => $card_id,
                'nbr' => $card_copies,
            ];
        }

        $this->deck->createCards($cards, 'deck');

        // Shuffle deck
        $this->deck->shuffle('deck');
        // Deal 5 cards to each players
        $players = self::loadPlayersBasicInfos();
        foreach ($players as $player_id => $player) {
            $this->deck->pickCards(5, 'deck', $player_id);
            $this->deck->pickCardForLocation('deck', 'initial_task', $player_id);
        }
        $this->deck->pickCardsForLocation(count($players), 'deck', 'floor');

        // Activate first player (which is in general a good idea :))
        $this->activeNextPlayer();

        self::incStat(1, 'turns_number', self::getActivePlayerId());


        /************ End of the game initialization *****/
    }

    /*
        getAllDatas:

        Gather all informations about current game situation (visible by the current player).

        The method is called each time the game interface is displayed to a player, ie:
        _ when the game starts
        _ when a player refreshes the game page (F5)
    */
    protected function getAllDatas()
    {
        $result = ['players' => []];

        $current_player_id = self::getCurrentPlayerId();    // !! We must only return informations visible by this player !!

        // Get information about players
        // Note: you can retrieve some extra field you added for "player" table in "dbmodel.sql" if you need it.
        $sql = "SELECT player_id id, player_no player_number, player_score score FROM player";
        $result['players'] = self::getCollectionFromDb($sql);

        // Cards in player hand
        $result['hand'] = $this->deck->getCardsInLocation('hand', $current_player_id);

        // Cards on the floor
        $result['floor'] = $this->deck->getCardsInLocation('floor');

        $result['deck_count'] = intval($this->deck->countCardInLocation('deck'));

        foreach ($result['players'] as &$player) {
            $player_id = $player['id'];
            // Task
            $initial_task = $player['initial_task'] = boolval($this->deck->getCardsInLocation('initial_task', $player_id));
            if (!$initial_task) {
                $task = $this->deck->getCardsInLocation('task', $player_id);
                if ($task) {
                    $player['task'] = array_values($task)[0];
                }
            }

            // Player area
            $player['gallery'] = $this->deck->getCardsInLocation('gallery', $player_id);

            $player['gift_shop'] = $this->deck->getCardsInLocation('gift_shop', $player_id);
            $player['helpers'] = $this->deck->getCardsInLocation('helpers', $player_id);
            $player['craft_bench'] = $this->deck->getCardsInLocation('craft_bench', $player_id);
            $player['sales'] = $this->deck->getCardsInLocation('sales', $player_id);

            $player['waiting_area_count'] = intval($this->deck->countCardInLocation('waiting_area', $player_id));
            $player['hand_count'] = intval($this->deck->countCardInLocation('hand', $player_id));
            // TODO: Store revealed cards in hand_revealed location
            // TODO: Return current task player, action num, total action count
        }

        // Material and card database
        $materials = [];
        $result['materials'] = &$materials;
        foreach ($this->materials as $mat_id => $material) {
            $materials[$mat_id] = $material->toJson();
        }

        $cards = [];
        $result['cards'] = &$cards;
        foreach ($this->cards as $card_id => $card) {
            $cards[$card_id] = $card->toJson();
        }

        // TODO: Query from card DB once at the top of the function
        $result['card_id_to_type'] = self::getCollectionFromDb('SELECT card_id id, card_type_arg type FROM card', true);

        return $result;
    }

    /*
        getGameProgression:

        Compute and return the current game progression.
        The number returned must be an integer beween 0 (=the game just started) and
        100 (= the game is finished or almost finished).

        This method is called each time we are in a game state with the "updateGameProgression" property set to true
        (see states.inc.php)
    */
    function getGameProgression()
    {
        // TODO: compute and return the game progression based on max completed works per player per side

        return 0;
    }

    //////////////////////////////////////////////////////////////////////////////
    //////////// Utility functions
    ////////////
    //
    // In this space, you can put any utility methods useful for your game logic
    //

    // get score
    function dbGetScore($player_id) {
        return $this->getUniqueValueFromDB("SELECT player_score FROM player WHERE player_id='$player_id'");
    }

    // set score
    function dbSetScore($player_id, $count) {
        $this->DbQuery("UPDATE player SET player_score='$count' WHERE player_id='$player_id'");
    }

    // set aux score (tie breaker)
    function dbSetAuxScore($player_id, $score) {
        $this->DbQuery("UPDATE player SET player_score_aux=$score WHERE player_id='$player_id'");
    }

    function calculateScore($player_id) {
        $score = 0;

        // TODO: Kite

        // Gallery works
        $gallery_cards = $this->deck->getCardsInLocation('gallery', $player_id);
        foreach ($gallery_cards as $card_id => $card) {
            $score += $this->cards[$card['type_arg']]->material->value;
        }

        // Gift Shop works
        $gift_shop_by_material = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];
        $gift_shop_cards = $this->deck->getCardsInLocation('gift_shop', $player_id);
        foreach ($gift_shop_cards as $card_id => $card) {
            $material = $this->cards[$card['type_arg']]->material;
            $score += $material->value;
            $gift_shop_by_material[$material->id] += 1;
        }

        // TODO: Works that affect sales: Pillar, Quilt, Go Set

        // Covered sales
        $sales_by_material = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];
        $sales_cards = $this->deck->getCardsInLocation('sales', $player_id);
        foreach ($sales_cards as $card_id => $card) {
            $sales_by_material[$this->cards[$card['type_arg']]->material->id] += 1;
        }
        foreach ($sales_by_material as $material_id => $sales_count) {
            if ($sales_count == 0) continue;
            $material_value = $this->materials[$material_id]->value;
            if ($sales_count <= $gift_shop_by_material[$material_id] * $material_value) {
                $score += $sales_count * $material_value;
            }
        }

        // TODO: Works that give points: Scroll, Bench, Tapestry, Haniwa, Teapot

        $this->dbSetScore($player_id, $score);
    }

    //////////////////////////////////////////////////////////////////////////////
    //////////// Player actions
    ////////////
    /*
     * Each time a player is doing some game action, one of the methods below is called.
     * (note: each method below must match an input method in template.action.php)
     */
    function reduceHand($card_ids) {
        self::checkAction('reduceHand');
        $player_id = self::getActivePlayerId();
        $cards_in_hand = $this->deck->getCardsInLocation('hand', $player_id);
        if (count($card_ids) != count($cards_in_hand) - 5) {
            throw new BgaUserException(self::_('Received unexpected amount of cards.'));
        }
        $cards_in_hand = array_keys($cards_in_hand);
        foreach ($card_ids as &$card) {
            if (!in_array($card, $cards_in_hand)) {
                throw new BgaUserException(self::_('You do not have that card.'));
            }
        }
        foreach ($card_ids as &$card) {
            $this->deck->insertCardOnExtremePosition($card, 'deck', false);
        }
        // TODO: Hide revealed cards

        $players = self::loadPlayersBasicInfos();
        $player_name = self::getActivePlayerName();
        foreach ($players as $player => $info) {
            if ($player == $player_id) {
                // Notify active player with the actual cards
                self::notifyPlayer($player, 'returnCards', '', [
                    'player_id' => $player_id,
                    'player_name' => $player_name,
                    'card_count' => count($card_ids),
                    'cards' => $card_ids,
                ]);
            } else {
                // Notify other players with number of cards
                self::notifyPlayer($player, 'returnCards', '', [
                    'player_id' => $player_id,
                    'player_name' => $player_name,
                    'card_count' => count($card_ids),
                ]);
            }
        }

        // Notify spectators
        self::notifyAllPlayers('returnCardsSpectator', clienttranslate('${player_name} returns ${card_count} card(s) from hand'), [
            'player_id' => $player_id,
            'player_name' => $player_name,
            'card_count' => count($card_ids),
        ]);

        $this->gamestate->nextState();
    }

    function chooseNewTask($card_id) {
        self::checkAction('chooseNewTask');
        $player_id = self::getActivePlayerId();
        if (is_null($card_id)) {
            self::notifyAllPlayers('chooseNewTask', clienttranslate('${player_name} chooses no new task'), [
                'player_id' => $player_id,
                'player_name' => self::getActivePlayerName(),
            ]);
            $this->gamestate->nextState('chooseNewTask');
            return;
        }

        $card = $this->deck->getCard($card_id);
        if (!$card || $card['location'] != 'hand' || $card['location_arg'] != $player_id) {
            throw new BgaUserException(self::_('You do not have that card.'));
        }
        $this->deck->moveCard($card_id, 'task', $player_id);

        $card_info = $this->cards[$card['type_arg']];

        self::notifyAllPlayers('chooseNewTask', clienttranslate('${player_name} plays ${card_name} as a ${task_name} task'), [
            'i18n' => ['card_name', 'task_name'],
            'card_id' => $card_id,
            'player_id' => $player_id,
            'player_name' => self::getActivePlayerName(),
            'card_name' => $card_info->name,
            'task_name' => $card_info->material->task,
        ]);
        $this->gamestate->nextState('chooseNewTask');
    }

    function chooseAction($action, $card_id, $wing, $card_list) {
        self::checkAction('chooseAction');
        $player_id = self::getActivePlayerId();

        // PRAY
        if ($action == 'pray') {
            $this->gamestate->nextState('pray');
            return;
        }

        $current_task = self::getGameStateValue('currentTask');

        // CLERK
        if ($action == 'clerk') {
            if ($current_task != $this->PAPER->id) {
                throw new BgaUserException(self::_('Action does not match task.'));
            }
            $card = $this->deck->getCard($card_id);
            if (!$card || $card['location'] != 'craft_bench' || $card['location_arg'] != $player_id) {
                throw new BgaUserException(self::_('This card is not allowed.'));
            }
            $this->deck->moveCard($card_id, 'sales', $player_id);

            $card_info = $this->cards[$card['type_arg']];

            // Update score
            $this->calculateScore($player_id);

            self::notifyAllPlayers('chooseClerkCard', clienttranslate('${player_name} sells ${card_name} from the Craft Bench'), [
                'i18n' => ['card_name'],
                'card_id' => $card_id,
                'player_id' => $player_id,
                'player_name' => self::getActivePlayerName(),
                'card_name' => $card_info->name,
            ]);

        // MONK
        } else if ($action == 'monk') {
            if ($current_task != $this->STONE->id) {
                throw new BgaUserException(self::_('Action does not match task.'));
            }
            $card = $this->deck->getCard($card_id);
            // TODO: Socks, Flute, Sword
            if (!$card || $card['location'] != 'floor') {
                throw new BgaUserException(self::_('This card is not allowed.'));
            }
            $this->deck->moveCard($card_id, 'helpers', $player_id);

            $card_info = $this->cards[$card['type_arg']];

            self::notifyAllPlayers('chooseMonkCardFloor', clienttranslate('${player_name} hires ${card_name} from the Floor'), [
                'i18n' => ['card_name'],
                'card_id' => $card_id,
                'player_id' => $player_id,
                'player_name' => self::getActivePlayerName(),
                'card_name' => $card_info->name,
            ]);

        // TAILOR
        } else if ($action == 'tailor') {
            if ($current_task != $this->CLOTH->id) {
                throw new BgaUserException(self::_('Action does not match task.'));
            }
            $cards_in_hand = $this->deck->getCardsInLocation('hand', $player_id);
            if (count($card_list) > count($cards_in_hand)) {
                throw new BgaUserException(self::_('Received unexpected amount of cards.'));
            }
            $cards_in_hand = array_keys($cards_in_hand);
            foreach ($card_list as &$card) {
                if (!in_array($card, $cards_in_hand)) {
                    throw new BgaUserException(self::_('You do not have that card.'));
                }
            }

            // Return cards to deck
            foreach ($card_list as &$card) {
                $this->deck->insertCardOnExtremePosition($card, 'deck', false);
            }

            // TODO: Hide revealed cards

            // Draw cards to waiting area
            $cards_to_draw = 5 - (count($cards_in_hand) - count($card_list)) -
                intval($this->deck->countCardInLocation('waiting_area', $player_id));
            $deck_count = intval($this->deck->countCardInLocation('deck'));
            $cards_drawn = 0;
            for ($i = 0; $i < $cards_to_draw; $i++) {
                $this->deck->pickCardForLocation('deck', 'waiting_area', $player_id);
                $cards_drawn++;
                $deck_count--;
                if ($deck_count == 0) {
                    break;
                }
            }

            // Notify everyone
            $players = self::loadPlayersBasicInfos();
            $player_name = self::getActivePlayerName();
            foreach ($players as $player => $info) {
                if ($player == $player_id) {
                    // Notify active player with the actual cards
                    self::notifyPlayer($player, 'chooseTailorCards', '', [
                        'player_id' => $player_id,
                        'player_name' => $player_name,
                        'return_count' => count($card_list),
                        'draw_count' => $cards_drawn,
                        'cards' => $card_list,
                    ]);
                } else {
                    // Notify other players with number of cards
                    self::notifyPlayer($player, 'chooseTailorCards', '', [
                        'player_id' => $player_id,
                        'player_name' => $player_name,
                        'return_count' => count($card_list),
                        'draw_count' => $cards_drawn,
                    ]);
                }
            }

            // Notify spectators
            self::notifyAllPlayers('chooseTailorCardsSpectator', clienttranslate('${player_name} tailors, returning ${return_count} card(s) and drawing ${draw_count}'), [
                'player_id' => $player_id,
                'player_name' => $player_name,
                'return_count' => count($card_list),
                'draw_count' => $cards_drawn,
            ]);

            if ($deck_count == 0) {
                // TODO: Notify players why the game is over
                $this->gamestate->nextState('end_game');
                return;
            }

        // POTTER
        } else if ($action == 'potter') {
            if ($current_task != $this->CLAY->id) {
                throw new BgaUserException(self::_('Action does not match task.'));
            }
            $card = $this->deck->getCard($card_id);
            // TODO: Socks, Flute, Sword
            if (!$card || $card['location'] != 'floor') {
                throw new BgaUserException(self::_('This card is not allowed.'));
            }
            $this->deck->moveCard($card_id, 'craft_bench', $player_id);

            $card_info = $this->cards[$card['type_arg']];

            self::notifyAllPlayers('choosePotterCardFloor', clienttranslate('${player_name} collects ${card_name} from the Floor to the Craft Bench'), [
                'i18n' => ['card_name'],
                'card_id' => $card_id,
                'player_id' => $player_id,
                'player_name' => self::getActivePlayerName(),
                'card_name' => $card_info->name,
            ]);
            // TODO: Plane

        // SMITH
        } else if ($action == 'smith') {
            if ($current_task != $this->METAL->id) {
                throw new BgaUserException(self::_('Action does not match task.'));
            }
            $hand_cards = $this->deck->getCardsInLocation('hand', $player_id);
            if (!array_key_exists($card_id, $hand_cards)) {
                throw new BgaUserException(self::_('This card is not allowed.'));
            }
            $card_info = $this->cards[$hand_cards[$card_id]['type_arg']];
            if (count($card_list) != $card_info->material->value - 1) {
                throw new BgaUserException(self::_('Wrong revealed card amount.'));
            }
            if ($card_info->material->value > 1) {
                foreach ($card_list as &$card_to_reveal) {
                    if ($card_to_reveal == $card_id) {
                        throw new BgaUserException(self::_('Can\'t reveal smithed card.'));
                    }
                    if (!array_key_exists($card_to_reveal, $hand_cards)) {
                        throw new BgaUserException(self::_('Revealed card not in hand.'));
                    }
                    $card_to_reveal_info = $this->cards[$hand_cards[$card_to_reveal]['type_arg']];
                    if ($card_to_reveal_info->material->id != $card_info->material->id) {
                        throw new BgaUserException(self::_('Revealed card of wrong type.'));
                    }
                }
            }

            $this->deck->moveCard($card_id, $wing, $player_id);
            self::setGameStateValue('completedCard', $card_id);

            $notification_text;
            $notification_args = [
                'i18n' => ['card_name', 'wing_name'],
                'card_id' => $card_id,
                'wing' => $wing,
                'player_id' => $player_id,
                'player_name' => self::getActivePlayerName(),
                'card_name' => $card_info->name,
                'wing_name' => $this->wing_names[$wing],
                'revealed_cards' => $card_list,
            ];
            switch (count($card_list)) {
                case 0:
                    $notification_text = clienttranslate('${player_name} smiths ${card_name} into the ${wing_name}');
                    break;
                case 1:
                    $notification_text = clienttranslate('${player_name} smiths ${card_name} into the ${wing_name}, revealing ${reveal_card_1}');
                    array_push($notification_args['i18n'], 'reveal_card_1');
                    $notification_args['reveal_card_1'] = $this->cards[$hand_cards[$card_list[0]]['type_arg']]->name;
                    break;
                case 2:
                    $notification_text = clienttranslate('${player_name} smiths ${card_name} into the ${wing_name}, revealing ${reveal_card_1} and ${reveal_card_2}');
                    array_push($notification_args['i18n'], 'reveal_card_1', 'reveal_card_2');
                    $notification_args['reveal_card_1'] = $this->cards[$hand_cards[$card_list[0]]['type_arg']]->name;
                    $notification_args['reveal_card_2'] = $this->cards[$hand_cards[$card_list[1]]['type_arg']]->name;
                    break;
            }

            self::notifyAllPlayers('smithedWork', $notification_text, $notification_args);

            $this->gamestate->nextState('completed_work');
            return;

        // CRAFT
        } else if ($action == 'craft') {
            $card = $this->deck->getCard($card_id);
            if (!$card || $card['location'] != 'hand' || $card['location_arg'] != $player_id) {
                throw new BgaUserException(self::_('This card is not allowed.'));
            }
            $card_info = $this->cards[$card['type_arg']];
            if ($card_info->material->id != $current_task) {
                throw new BgaUserException(self::_('Cannot craft this material.'));
            }
            if ($card_info->material->value > 1) {
                $bench_cards = $this->deck->getCardsInLocation('craft_bench', $player_id);
                $bench_count = 0;
                foreach ($bench_cards as $bench_card_id => $bench_card) {
                    $bench_card_info = $this->cards[$bench_card['type_arg']];
                    if ($bench_card_info->material->id == $current_task) {
                        $bench_count++;
                    }
                }
                if ($bench_count < $card_info->material->value - 1) {
                    throw new BgaUserException(self::_('Not enough materials in Craft Bench.'));
                }
            }

            $this->deck->moveCard($card_id, $wing, $player_id);
            self::setGameStateValue('completedCard', $card_id);

            self::notifyAllPlayers('craftedWork', clienttranslate('${player_name} crafts ${card_name} into the ${wing_name}'), [
                'i18n' => ['card_name', 'wing_name'],
                'card_id' => $card_id,
                'wing' => $wing,
                'player_id' => $player_id,
                'player_name' => self::getActivePlayerName(),
                'card_name' => $card_info->name,
                'wing_name' => $this->wing_names[$wing],
            ]);

            $this->gamestate->nextState('completed_work');
            return;

        } else {
            // TODO other actions
            throw new BgaUserException(self::_('Unsupported action.'));
        }
        $this->gamestate->nextState('next');
    }

    //////////////////////////////////////////////////////////////////////////////
    //////////// Game state arguments
    ////////////
    /*
     * Here, you can create methods defined as "game state arguments" (see "args" property in states.inc.php).
     * These methods function is to return some additional information that is specific to the current
     * game state.
     */

    function argReduceHand() {
        return [
            'count' => intval($this->deck->countCardInLocation('hand', self::getActivePlayerId())) - 5,
        ];
    }
    function argPerformAction() {
        $current_task = self::getGameStateValue('currentTask');
        return [
            'task_id' => $current_task,
            'task_name' => $this->materials[$current_task]->task,
            'action_count' => self::getGameStateValue('currentTaskActionCurrent'),
            'action_total' => self::getGameStateValue('currentTaskActionTotal'),
        ];
    }

    //////////////////////////////////////////////////////////////////////////////
    //////////// Game state actions
    ////////////
    /*
     * Here, you can create methods defined as "game state actions" (see "action" property in states.inc.php).
     * The action method of state X is called everytime the current game state is set to X.
     */

    function stNewTurn() {
        self::activeNextPlayer();
        self::incStat(1, 'turns_number');
        self::incStat(1, 'turns_number', self::getActivePlayerId());

        if (intval($this->deck->countCardInLocation('hand', self::getActivePlayerId())) > 5) {
            $this->gamestate->nextState('reduce_hand');
            return;
        }
        // TODO: Frog: extra turn
        $this->gamestate->nextState('ok');
    }

    function stDiscardOldTask() {
        // Move current player's task to the floor
        $player_id = self::getActivePlayerId();
        $task_cards = $this->deck->getCardsInLocation('task', $player_id);
        $initial = false;
        if (!$task_cards) {
            $task_cards = $this->deck->getCardsInLocation('initial_task', $player_id);
            if ($task_cards) {
                $initial = true;
            }
        }
        if ($task_cards) {
            $card = array_values($task_cards)[0];
            $this->deck->moveCard($card['id'], 'floor');
            $card_info = $this->cards[$card['type_arg']];
            self::notifyAllPlayers('discardOldTask',
                clienttranslate('${player_name} discards task ${card_name} to the floor'), [
                'i18n' => ['card_name'],
                'player_name' => self::getActivePlayerName(),
                'card_name' => $card_info->name,
                'player_id' => $player_id,
                'card_id' => $card['id'],
                'initial' => $initial,
            ]);
        }

        if (intval($this->deck->countCardInLocation('hand', $player_id))) {
            $this->gamestate->nextState('choose_new');
        } else {
            $this->gamestate->nextState('skip');
        }
    }

    function stPerformNextPlayerTask() {
        // Advance task player counter and perform task
        $player_id = self::getActivePlayerId();
        $players = self::loadPlayersBasicInfos();
        $saved_value = $current_task_player_id = self::getGameStateValue('currentTaskPlayerId');

        $found_task = null;

        while (!$found_task) {
            if ($current_task_player_id == $player_id) {
                if ($saved_value != 0) {
                    self::setGameStateValue('currentTaskPlayerId', 0);
                }
                $this->gamestate->nextState('done');
                return;
            }

            if ($current_task_player_id == 0) {
                $current_task_player_id = self::getPlayerAfter($player_id);
            } else {
                $current_task_player_id = self::getPlayerAfter($current_task_player_id);
            }

            $found_task = $this->deck->getCardsInLocation('task', $current_task_player_id);
            // If the active player has no task, it becomes a prayer
            if (!$found_task && $current_task_player_id == $player_id) {
                $found_task = 6;
            }
        }
        self::setGameStateValue('currentTaskPlayerId', $current_task_player_id);
        if ($found_task != 6) {
            $task_card = $this->cards[array_values($found_task)[0]['type_arg']];
            self::setGameStateValue('currentTask', $task_card->material->id);
        }
        $this->gamestate->nextState('perform');
    }

    function stPerformTask() {
        $player_id = self::getActivePlayerId();
        $current_task_player_id = self::getGameStateValue('currentTaskPlayerId');
        $players = self::loadPlayersBasicInfos();
        $task_card = $this->deck->getCardsInLocation('task', $current_task_player_id);

        // If the active player has no task, it becomes a prayer
        if (!$task_card && $player_id == $current_task_player_id) {
            self::setGameStateValue('currentTaskActionCurrent', 1);
            self::setGameStateValue('currentTaskActionTotal', 1);
            $this->gamestate->nextState('pray');
            return;
        }

        $task_card = array_values($task_card)[0];
        $task_material = $this->cards[$task_card['type_arg']]->material;
        $task_material_id = $task_material->id;

        // Count helpers
        $matching_helpers = 0;
        $helpers = $this->deck->getCardsInLocation('helpers', $player_id);
        foreach ($helpers as &$card) {
            $card_info = $this->cards[$card['type_arg']];
            if ($card_info->material->id == $task_material_id) {
                $matching_helpers++;
            }
        }

        // Count covered helpers
        $matching_gallery_works = 0;
        $gallery_works = $this->deck->getCardsInLocation('gallery', $player_id);
        foreach ($gallery_works as &$work) {
            $card_info = $this->cards[$work['type_arg']];
            if ($card_info->material->id == $task_material_id) {
                $matching_gallery_works++;
            }
        }

        $actions = 1 + $matching_helpers;
        if ($matching_helpers <= $matching_gallery_works * $task_material->value) {
            $actions += $matching_helpers;
        }

        self::setGameStateValue('currentTaskActionCurrent', 0);
        self::setGameStateValue('currentTaskActionTotal', $actions);

        if ($actions) {
            $this->gamestate->nextState('perform');
        } else {
            $this->gamestate->nextState('done');
        }
    }

    function stPerformNextAction() {
        $current = self::getGameStateValue('currentTaskActionCurrent');
        $total = self::getGameStateValue('currentTaskActionTotal');
        $current += 1;
        if ($current > $total) {
            $this->gamestate->nextState('done');
            return;
        }
        self::setGameStateValue('currentTaskActionCurrent', $current);
        $this->gamestate->nextState('perform');
    }

    function stPerformPray() {
        $player_id = self::getActivePlayerId();
        $this->deck->pickCardForLocation('deck', 'waiting_area', $player_id);
        self::notifyAllPlayers('chooseActionPray', clienttranslate('${player_name} prays'), [
            'player_id' => $player_id,
            'player_name' => self::getActivePlayerName(),
        ]);
        if ($this->deck->countCardInLocation('deck') == 0) {
            // TODO: Notify players why the game is over
            $this->gamestate->nextState('end_game');
            return;
        }
        $this->gamestate->nextState('next');
    }

    function stCompletedWork() {
        $player_id = self::getActivePlayerId();
        $card_id = self::getGameStateValue('completedCard');
        $card_info = $this->cards[$card_id];

        // Update score
        $this->calculateScore($player_id);

        # TODO: Check end of game based on maxWorksInWing
        # TODO: Check triggered effects
        $this->gamestate->nextState('next');
    }

    function stDrawWaitingArea() {
        $player_id = self::getActivePlayerId();
        $cards_in_waiting_area = $this->deck->getCardsInLocation('waiting_area', $player_id);
        if (!$cards_in_waiting_area) {
            $this->gamestate->nextState();
            return;
        }

        $this->deck->moveAllCardsInLocation('waiting_area', 'hand', $player_id, $player_id);

        $players = self::loadPlayersBasicInfos();
        $player_name = self::getActivePlayerName();
        foreach ($players as $player => $info) {
            if ($player == $player_id) {
                // Notify active player with the actual cards
                self::notifyPlayer($player, 'drawWaitingArea', '', [
                    'player_id' => $player_id,
                    'player_name' => $player_name,
                    'card_count' => count($cards_in_waiting_area),
                    'cards' => $cards_in_waiting_area,
                ]);
            } else {
                // Notify other players with number of cards
                self::notifyPlayer($player, 'drawWaitingArea', '', [
                    'player_id' => $player_id,
                    'player_name' => $player_name,
                    'card_count' => count($cards_in_waiting_area),
                ]);
            }
        }

        // Notify spectators
        self::notifyAllPlayers('drawWaitingAreaSpectator', clienttranslate('${player_name} draws ${card_count} card(s) from the waiting area'), [
            'player_id' => $player_id,
            'player_name' => $player_name,
            'card_count' => count($cards_in_waiting_area),
        ]);

        $this->gamestate->nextState();
    }

//////////////////////////////////////////////////////////////////////////////
//////////// Zombie
////////////

    /*
        zombieTurn:

        This method is called each time it is the turn of a player who has quit the game (= "zombie" player).
        You can do whatever you want in order to make sure the turn of this player ends appropriately
        (ex: pass).
    */

    function zombieTurn($state, $active_player)
    {
        $statename = $state['name'];

        if ($state['type'] == "activeplayer") {
            switch ($statename) {
                default:
                    $this->gamestate->nextState("zombiePass");
                    break;
            }

            return;
        }

        if ($state['type'] == "multipleactiveplayer") {
            // Make sure player is in a non blocking status for role turn
            $sql = "
                UPDATE  player
                SET     player_is_multiactive = 0
                WHERE   player_id = $active_player
            ";
            self::DbQuery($sql);

            $this->gamestate->updateMultiactiveOrNextState('');
            return;
        }

        throw new feException("Zombie mode not supported at this game state: ".$statename);
    }

///////////////////////////////////////////////////////////////////////////////////:
////////// DB upgrade
//////////

    /*
        upgradeTableDb:

        You don't have to care about this until your game has been published on BGA.
        Once your game is on BGA, this method is called everytime the system detects a game running with your old
        Database scheme.
        In this case, if you change your Database scheme, you just have to apply the needed changes in order to
        update the game database and allow the game to continue to run with your new version.

    */

    function upgradeTableDb($from_version)
    {
        // $from_version is the current version of this game database, in numerical form.
        // For example, if the game was running with a release of your game named "140430-1345",
        // $from_version is equal to 1404301345

        // Example:
//        if($from_version <= 1404301345)
//        {
//            $sql = "ALTER TABLE xxxxxxx ....";
//            self::DbQuery($sql);
//        }
//        if($from_version <= 1405061421)
//        {
//            $sql = "CREATE TABLE xxxxxxx ....";
//            self::DbQuery($sql);
//        }
//        // Please add your future database scheme changes here
//
//


    }
}


