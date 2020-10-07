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
 * states.inc.php
 *
 * Mottainai game states description
 *
 */

/*
   Game state machine is a tool used to facilitate game developpement by doing common stuff that can be set up
   in a very easy way from this configuration file.

   Please check the BGA Studio presentation about game state to understand this, and associated documentation.

   Summary:

   States types:
   _ activeplayer: in this type of state, we expect some action from the active player.
   _ multipleactiveplayer: in this type of state, we expect some action from multiple players (the active players)
   _ game: this is an intermediary state where we don't expect any actions from players. Your game logic must decide what is the next game state.
   _ manager: special type for initial and final state

   Arguments of game states:
   _ name: the name of the GameState, in order you can recognize it on your own code.
   _ description: the description of the current game state is always displayed in the action status bar on
                  the top of the game. Most of the time this is useless for game state with "game" type.
   _ descriptionmyturn: the description of the current game state when it's your turn.
   _ type: defines the type of game states (activeplayer / multipleactiveplayer / game / manager)
   _ action: name of the method to call when this game state become the current game state. Usually, the
             action method is prefixed by "st" (ex: "stMyGameStateName").
   _ possibleactions: array that specify possible player actions on this step. It allows you to use "checkAction"
                      method on both client side (Javacript: this.checkAction) and server side (PHP: self::checkAction).
   _ transitions: the transitions are the possible paths to go from a game state to another. You must name
                  transitions in order to use transition names in "nextState" PHP method, and use IDs to
                  specify the next game state for each transition.
   _ args: name of the method to call to retrieve arguments for this gamestate. Arguments are sent to the
           client side to be used on "onEnteringState" or to set arguments in the gamestate description.
   _ updateGameProgression: when specified, the game progression is updated (=> call to your getGameProgression
                            method).
*/

//    !! It is not a good idea to modify this file when a game is running !!


$machinestates = [

    // The initial state. Please do not modify.
    1 => [
        'name' => 'gameSetup',
        'description' => clienttranslate('Game setup'),
        'type' => 'manager',
        'action' => 'stGameSetup',
        'transitions' => ['' => STATE_DISCARD_OLD_TASK]
    ],

    STATE_CHECK_HAND_SIZE => [
        'name' => 'checkHandSize',
        'description' => '',
        'type' => 'game',
        'action' => 'stCheckHandSize',
        'transitions' => ['ok' => STATE_DISCARD_OLD_TASK] // TODO
    ],

    STATE_DISCARD_OLD_TASK => [
        'name' => 'discardOldTask',
        'description' => '',
        'type' => 'game',
        'action' => 'stDiscardOldTask',
        'transitions' => ['choose_new' => STATE_CHOOSE_NEW_TASK, 'skip' => STATE_PERFORM_NEXT_PLAYER_TASK]
    ],

    STATE_CHOOSE_NEW_TASK => [
        'name' => 'chooseNewTask',
		'description' => clienttranslate('${actplayer} may choose a new task'),
		'descriptionmyturn' => clienttranslate('${you} may choose a new task'),
        'type' => 'activeplayer',
        'possibleactions' => ['chooseNewTask'],
        'transitions' => ['chooseNewTask' => STATE_PERFORM_NEXT_PLAYER_TASK]
    ],

    STATE_PERFORM_NEXT_PLAYER_TASK => [
        'name' => 'performNextPlayerTask',
        'description' => '',
        'type' => 'game',
        'action' => 'stPerformNextPlayerTask',
        'transitions' => [
            /* 'done' => STATE_NIGHT_EFFECTS, */
            'done' => STATE_CHECK_HAND_SIZE,
            'perform' => STATE_PERFORM_TASK,
        ],
    ],

    STATE_PERFORM_TASK => [
        'name' => 'performTask',
        'description' => '',
        'type' => 'game',
        'action' => 'stPerformTask',
        'transitions' => [
            /* 'done' => STATE_NIGHT_EFFECTS, */
            'done' => STATE_CHECK_HAND_SIZE,
            /* 'action' => STATE_PERFORM_ACTION, */
            'action' => STATE_CHECK_HAND_SIZE,
        ],
    ],

    // Trick

    30 => [
        'name' => 'newTrick',
        'description' => '',
        'type' => 'game',
        'action' => 'stNewTrick',
        'transitions' => ['' => 31]
    ],
    31 => [
        'name' => 'playerTurn',
        'description' => clienttranslate('${actplayer} must play a card'),
        'descriptionmyturn' => clienttranslate('${you} must play a card'),
        'type' => 'activeplayer',
        'possibleactions' => ['playCard'],
        'transitions' => ['playCard' => 32]
    ],
    32 => [
        'name' => 'nextPlayer',
        'description' => '',
        'type' => 'game',
        'action' => 'stNextPlayer',
        'transitions' => ['nextPlayer' => 31, 'nextTrick' => 30, 'endHand' => 40]
    ],


    // End of the hand (scoring, etc...)
    40 => [
        'name' => 'endHand',
        'description' => '',
        'type' => 'game',
        'action' => 'stEndHand',
        'transitions' => ['nextHand' => 20, 'endGame' => STATE_GAME_END]
    ],

    // Final state.
    // Please do not modify.
    STATE_GAME_END => [
        'name' => 'gameEnd',
        'description' => clienttranslate('End of game'),
        'type' => 'manager',
        'action' => 'stGameEnd',
        'args' => 'argGameEnd'
    ],
];
