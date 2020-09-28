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
 * gameoptions.inc.php
 *
 * Mottainai game options description
 *
 * In this file, you can define your game options (= game variants).
 *
 * Note: If your game has no variant, you don't have to modify this file.
 *
 * Note²: All options defined in this file should have a corresponding "game state labels"
 *        with the same ID (see "initGameStateLabels" in mottainai.game.php)
 *
 * !! It is not a good idea to modify this file when a game is running !!
 *
 */

$game_options = [
    100 => [
        'name' => totranslate('Game mode'),
        'values' => [
            5 => ['name' => totranslate('Normal'), 'description' => totranslate('The game ends when any wing has 5 works')],
            6 => ['name' => totranslate('Extended'), 'description' => totranslate('The game ends when any wing has 6 works. Recommended when playing with 4 or 5 players')],
        ],
        'default' => 5
    ],

    // TODO: Team Play
];
