<?php
/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * Mottainai implementation : © Ori Avtalion <ori@avtalion.name>
 *
 * This code has been produced on the BGA studio platform for use on https://boardgamearena.com.
 * See http://en.doc.boardgamearena.com/Studio for more information.
 * -----
 *
 * mottainai.action.php
 *
 * Mottainai main action entry point
 *
 *
 * In this file, you are describing all the methods that can be called from your
 * user interface logic (javascript).
 *
 * If you define a method 'myAction' here, then you can call it from your javascript code with:
 * this.ajaxcall( '/mottainai/mottainai/myAction.html', ...)
 *
 */


class action_mottainai extends APP_GameAction
{
    // Constructor: please do not modify
    public function __default() {
        if (self::isArg('notifwindow')) {
            $this->view = 'common_notifwindow';
            $this->viewArgs['table'] = self::getArg('table', AT_posint, true);
        } else {
            $this->view = 'mottainai_mottainai';
            self::trace('Complete reinitialization of board game');
        }
    }
    
    public function chooseNewTask() {
        self::setAjaxMode();
        $card_id = self::getArg('id', AT_posint);
        $this->game->chooseNewTask($card_id);
        self::ajaxResponse();
    }

    public function chooseAction() {
        self::setAjaxMode();
        $action = self::getArg('action_', AT_alphanum);
		$work_to_create = null;
		$cards_to_reveal = null;
		$this->game->chooseAction($action, $work_to_create, $cards_to_reveal);
        self::ajaxResponse();
    }
    
    /*
    
    Example:
    
    public function myAction()
    {
        self::setAjaxMode();
    
        // Retrieve arguments
        // Note: these arguments correspond to what has been sent through the javascript 'ajaxcall' method
        $arg1 = self::getArg( 'myArgument1', AT_posint, true );
        $arg2 = self::getArg( 'myArgument2', AT_posint, true );
    
        // Then, call the appropriate method in your game logic, like 'playCard' or 'myAction'
        $this->game->myAction( $arg1, $arg2 );
    
        self::ajaxResponse( );
    }
    
    */

}
