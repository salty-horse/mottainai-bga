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


// States
define('STATE_CHECK_HAND_SIZE', 2);
define('STATE_REDUCE_HAND', 3);
define('STATE_MORNING_EFFECTS', 4);
define('STATE_DISCARD_OLD_TASK', 5);
define('STATE_CHOOSE_NEW_TASK', 6);
define('STATE_PERFORM_NEXT_PLAYER_TASK', 7);
define('STATE_PERFORM_TASK', 9);
define('STATE_PERFORM_ACTION', 10);
define('STATE_PERFORM_CLERK', 11);
define('STATE_PERFORM_MONK', 12);
define('STATE_PERFORM_TAILOR', 13);
define('STATE_PERFORM_POTTER', 14);
define('STATE_PERFORM_SMITH', 15);
define('STATE_REVEAL_CARDS', 16);
define('STATE_PERFORM_CRAFT', 17);
define('STATE_CHOOSE_COMPLETED_WORK_POS', 18);
define('STATE_PLACE_COMPLETED_WORK', 19);
define('STATE_NIGHT_EFFECTS', 20);
define('STATE_DRAW_WAITING_AREA', 21);
define('STATE_GAME_END', 99);

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
            "currentTaskPlayerId" => 10,
            "trickColor" => 11,
            "alreadyPlayedHearts" => 12,
            "wingSizeEndGameTrigger" => 100,
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

        // The player ID whose task is currently being performed
        self::setGameStateInitialValue('currentTaskPlayerId', 0);

        // Set current trick color to zero (= no trick color)
        self::setGameStateInitialValue('trickColor', 0);

        // Mark if we already played some heart during this hand
        self::setGameStateInitialValue('alreadyPlayedHearts', 0);

        // Init game statistics
        // (note: statistics are defined in your stats.inc.php file)

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
        /*
     * In this space, you can put any utility methods useful for your game logic
     */
        //////////////////////////////////////////////////////////////////////////////
        //////////// Player actions
        ////////////
        /*
     * Each time a player is doing some game action, one of the methods below is called.
     * (note: each method below must match an input method in template.action.php)
     */
    function chooseNewTask($card_id) {
        self::checkAction('chooseNewTask');
        $player_id = self::getActivePlayerId();
        if (is_null($card_id)) {
            self::notifyAllPlayers('chooseNewTask', clienttranslate('${player_name} chooses no new task'), [
                'card_id' => $card_id,
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

        //////////////////////////////////////////////////////////////////////////////
        //////////// Game state arguments
        ////////////
        /*
     * Here, you can create methods defined as "game state arguments" (see "args" property in states.inc.php).
     * These methods function is to return some additional information that is specific to the current
     * game state.
     */
    function argGiveCards() {
        return [];
    }

        //////////////////////////////////////////////////////////////////////////////
        //////////// Game state actions
        ////////////
        /*
     * Here, you can create methods defined as "game state actions" (see "action" property in states.inc.php).
     * The action method of state X is called everytime the current game state is set to X.
     */

    function stCheckHandSize() {
        self::activeNextPlayer();
        $this->gamestate->nextState();
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

        $found_task_to_perform = false;

        while (!$found_task_to_perform) {
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

            if ($this->deck->getCardsInLocation('task', $current_task_player_id)) {
                $found_task_to_perform = true;
            }
        }
        self::setGameStateValue('currentTaskPlayerId', $current_task_player_id);
        $this->gamestate->nextState('perform');
    }

    function stPerformTask() {
        $player_id = self::getActivePlayerId();
        $current_task_player_id = self::getGameStateValue('currentTaskPlayerId');
        $players = self::loadPlayersBasicInfos();
        $task_card = array_values($this->deck->getCardsInLocation('task', $current_task_player_id))[0];

        // TODO: Calculate how many actions
        // self.actions_to_perform = 1 + \
        //     sum(1 for helper in self.active_player.helpers
        //         if helper.material == task.material) + \
        //         self.active_player.covered_helpers[task.material]
        //

        // def calculate_cover(self):
        //     self.covered_helpers = Counter()
        //     helpers_by_material = Counter(helper.material for helper in self.helpers)
        //     gallery_works_by_material = Counter(work.material for work in self.gallery)
        //     for material, count in helpers_by_material.items():
        //         if count <= gallery_works_by_material[material] * material.value:
        //             self.covered_helpers[material] = helpers_by_material[material]

        //     self.covered_sales_value = Counter()
        //     sales_by_material = Counter(helper.material for helper in self.sales)
        //     gift_shop_works_by_material = Counter(work.material for work in self.gift_shop)
        //     for material, count in sales_by_material.items():
        //         if count <= gift_shop_works_by_material[material] * material.value:
        //             self.covered_sales_value[material] = count * material.value
        
		// TODO: Save current action num and total actions
        $actions = 1;
        if ($actions) {
            $this->gamestate->nextState('action');
        } else {
            $this->gamestate->nextState('done');
        }
    }

    function stNextPlayer() {
        // Active next player OR end the trick and go to the next trick OR end the hand
        if ($this->deck->countCardInLocation('cardsontable') == 4) {
            // This is the end of the trick
            $cards_on_table = $this->deck->getCardsInLocation('cardsontable');
            $best_value = 0;
            $best_value_player_id = null;
            $currentTrickColor = self::getGameStateValue('trickColor');
            foreach ($cards_on_table as $card) {
                // Note: type = card color
                if ($card ['type'] == $currentTrickColor) {
                    if ($best_value_player_id === null || $card ['type_arg'] > $best_value) {
                        $best_value_player_id = $card ['location_arg']; // Note: location_arg = player who played this card on table
                        $best_value = $card ['type_arg']; // Note: type_arg = value of the card
                    }
                }
            }

            // Active this player => he's the one who starts the next trick
            $this->gamestate->changeActivePlayer($best_value_player_id);

            // Move all cards to "cardswon" of the given player
            $this->deck->moveAllCardsInLocation('cardsontable', 'cardswon', null, $best_value_player_id);

            // Notify
            // Note: we use 2 notifications here in order we can pause the display during the first notification
            //  before we move all cards to the winner (during the second)
            $players = self::loadPlayersBasicInfos();
            self::notifyAllPlayers('trickWin', clienttranslate('${player_name} wins the trick'), [
                'player_id' => $best_value_player_id,
                'player_name' => $players[ $best_value_player_id ]['player_name']
            ]);
            self::notifyAllPlayers('giveAllCardsToPlayer','', [
                    'player_id' => $best_value_player_id
            ]);

            if ($this->deck->countCardInLocation('hand') == 0) {
                // End of the hand
                $this->gamestate->nextState("endHand");
            } else {
                // End of the trick
                $this->gamestate->nextState("nextTrick");
            }
        } else {
            // Standard case (not the end of the trick)
            // => just active the next player
            $player_id = self::activeNextPlayer();
            self::giveExtraTime($player_id);
            $this->gamestate->nextState('nextPlayer');
        }
    }

    function stEndHand() {
        // Count and score points, then end the game or go to the next hand.
        $players = self::loadPlayersBasicInfos();
        // Gets all "hearts" + queen of spades

        $player_to_points = [];
        foreach ($players as $player_id => $player) {
            $player_to_points [$player_id] = 0;
        }
        $cards = $this->deck->getCardsInLocation("cardswon");
        foreach ($cards as $card) {
            $player_id = $card ['location_arg'];
            // Note: 2 = heart
            if ($card ['type'] == 2) {
                $player_to_points [$player_id] ++;
            }
        }
        // Apply scores to player
        foreach ($player_to_points as $player_id => $points) {
            if ($points != 0) {
                $sql = "UPDATE player SET player_score=player_score-$points  WHERE player_id='$player_id'";
                self::DbQuery($sql);
                $heart_number = $player_to_points [$player_id];
                self::notifyAllPlayers("points", clienttranslate('${player_name} gets ${nbr} hearts and looses ${nbr} points'), [
                    'player_id' => $player_id,'player_name' => $players [$player_id] ['player_name'],
                    'nbr' => $heart_number
                ]);
            } else {
                // No point lost (just notify)
                self::notifyAllPlayers("points", clienttranslate('${player_name} did not get any hearts'), [
                    'player_id' => $player_id,
                    'player_name' => $players[$player_id]['player_name']
                ]);
            }
        }
        $newScores = self::getCollectionFromDb("SELECT player_id, player_score FROM player", true);
        self::notifyAllPlayers("newScores", '', ['newScores' => $newScores]);

        ///// Test if this is the end of the game
        foreach ($newScores as $player_id => $score) {
            if ($score <= -100) {
                // Trigger the end of the game !
                $this->gamestate->nextState("endGame");
                return;
            }
        }


        $this->gamestate->nextState("nextHand");
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


