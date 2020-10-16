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

// States
if (!defined('STATE_END_GAME')) {

define('STATE_CHECK_HAND_SIZE', 2);
define('STATE_REDUCE_HAND', 3);
define('STATE_MORNING_EFFECTS', 4);
define('STATE_DISCARD_OLD_TASK', 5);
define('STATE_CHOOSE_NEW_TASK', 6);
define('STATE_PERFORM_NEXT_PLAYER_TASK', 7);
define('STATE_PERFORM_TASK', 8);
define('STATE_PERFORM_NEXT_ACTION', 9);
define('STATE_PERFORM_ACTION', 10);
define('STATE_PERFORM_CLERK', 11);
define('STATE_PERFORM_MONK', 12);
define('STATE_PERFORM_TAILOR', 13);
define('STATE_PERFORM_POTTER', 14);
define('STATE_PERFORM_SMITH', 15);
define('STATE_REVEAL_CARDS', 16);
define('STATE_PERFORM_CRAFT', 17);
define('STATE_PERFORM_PRAY', 18);
define('STATE_CHOOSE_COMPLETED_WORK_POS', 19);
define('STATE_COMPLETED_WORK', 20);
define('STATE_NIGHT_EFFECTS', 21);
define('STATE_DRAW_WAITING_AREA', 22);
define('STATE_END_GAME', 99);

}


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
            'done' => STATE_DRAW_WAITING_AREA,
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
            'done' => STATE_DRAW_WAITING_AREA,
            'perform' => STATE_PERFORM_NEXT_ACTION,
            'pray' => STATE_PERFORM_PRAY,
        ],
    ],

    STATE_PERFORM_NEXT_ACTION => [
        'name' => 'performNextAction',
        'description' => '',
        'type' => 'game',
        'action' => 'stPerformNextAction',
        'transitions' => [
            'done' => STATE_PERFORM_NEXT_PLAYER_TASK,
            'perform' => STATE_PERFORM_ACTION,
        ],
    ],

    STATE_PERFORM_ACTION => [
        'name' => 'chooseAction',
        'description' => clienttranslate('${actplayer} must perform ${task_name} action ${action_count} of ${action_total}'),
        'descriptionmyturn' => clienttranslate('${you} must perform ${task_name} action ${action_count} of ${action_total}'),
        'type' => 'activeplayer',
        'args' => 'argPerformAction',
        'possibleactions' => ['chooseAction'],
        'transitions' => [
            'pray' => STATE_PERFORM_PRAY,
            'completed_work' => STATE_COMPLETED_WORK,
            'next' => STATE_PERFORM_NEXT_ACTION,
        ],
    ],

    STATE_PERFORM_PRAY => [
        'name' => 'performPray',
        'description' => '',
        'type' => 'game',
        'action' => 'stPerformPray',
        'transitions' => [
            'next' => STATE_PERFORM_NEXT_ACTION,
            'end_game' => STATE_END_GAME,
        ],
    ],

    STATE_COMPLETED_WORK => [
        'name' => 'completedWork',
        'description' => '',
        'type' => 'game',
        'action' => 'stCompletedWork',
        'transitions' => [
            'next' => STATE_PERFORM_NEXT_ACTION,
        ],
    ],

    STATE_DRAW_WAITING_AREA => [
        'name' => 'drawWaitingArea',
        'description' => '',
        'type' => 'game',
        'action' => 'stDrawWaitingArea',
        'transitions' => [
            '' => STATE_CHECK_HAND_SIZE,
        ],
    ],

    // Final state.
    // Please do not modify.
    STATE_END_GAME => [
        'name' => 'gameEnd',
        'description' => clienttranslate('End of game'),
        'type' => 'manager',
        'action' => 'stGameEnd',
        'args' => 'argGameEnd'
    ],
];
