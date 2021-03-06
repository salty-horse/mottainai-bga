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
    
    public function reduceHand() {
        self::setAjaxMode();
        $card_ids = self::getArg('ids', AT_numberlist, true);
        $this->game->reduceHand(array_unique(explode(',', $card_ids)));
        self::ajaxResponse();
    }

    public function chooseNewTask() {
        self::setAjaxMode();
        $card_id = self::getArg('id', AT_posint);
        $this->game->chooseNewTask($card_id);
        self::ajaxResponse();
    }

    public function chooseAction() {
        self::setAjaxMode();
        $action = self::getArg('action_', AT_alphanum, true);
        $card_id = self::getArg('card_id', AT_posint);
        $wing = self::getArg('wing', AT_alphanum);
        if ($wing && !in_array($wing, ['gallery', 'gift_shop'])) {
            throw new BgaUserException(self::_('Invalid wing'));
        }
        $card_list = self::getArg('card_list', AT_numberlist);
        if ($card_list) {
            $card_list = array_unique(explode(',', $card_list));
        } else {
            $card_list = [];
        }
        $this->game->chooseAction($action, $card_id, $wing, $card_list);
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
