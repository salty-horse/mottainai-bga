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
 * material.inc.php
 *
 * Mottainai game material description
 *
 * Here, you can describe the material of your game with PHP variables.
 *
 * This file is loaded in your game logic class constructor, ie these variables
 * are available everywhere in your game logic code.
 *
 */

$this->wing_names = [
    'gallery' => clienttranslate('Gallery'),
    'gift_shop' => clienttranslate('Gift Shop'),
];

$this->materials = [
    1 => new MOT_Material(1, clienttranslate('Paper'), 1, '📜', clienttranslate('Clerk'), clienttranslate('Sell a material')),
    2 => new MOT_Material(2, clienttranslate('Stone'), 2, '🗿', clienttranslate('Monk'), clienttranslate('Hire a helper')),
    3 => new MOT_Material(3, clienttranslate('Cloth'), 2, '🧵', clienttranslate('Tailor'), clienttranslate('Refill your hand')),
    4 => new MOT_Material(4, clienttranslate('Clay'), 3, '🧱', clienttranslate('Potter'), clienttranslate('Collect a material')),
    5 => new MOT_Material(5, clienttranslate('Metal'), 3, '🔧', clienttranslate('Smith'), clienttranslate('Complete any work')),
];

$this->PAPER = $this->materials[1];
$this->STONE = $this->materials[2];
$this->CLOTH = $this->materials[3];
$this->CLAY = $this->materials[4];
$this->METAL = $this->materials[5];

$this->cards = [
     1 => new MOT_Card(1, clienttranslate('Crane'), $this->PAPER),
     2 => new MOT_Card(2, clienttranslate('Curtain'), $this->PAPER),
     3 => new MOT_Card(3, clienttranslate('Deck of Cards'), $this->PAPER),
     4 => new MOT_Card(4, clienttranslate('Doll'), $this->PAPER),
     5 => new MOT_Card(5, clienttranslate('Fan'), $this->PAPER),
     6 => new MOT_Card(6, clienttranslate('Lampshade'), $this->PAPER),
     7 => new MOT_Card(7, clienttranslate('Pinwheel'), $this->PAPER),
     8 => new MOT_Card(8, clienttranslate('Plane'), $this->PAPER),
     9 => new MOT_Card(9, clienttranslate('Poem'), $this->PAPER),
    10 => new MOT_Card(10, clienttranslate('Scroll'), $this->PAPER),
    11 => new MOT_Card(11, clienttranslate('Sketch'), $this->PAPER),
    12 => new MOT_Card(12, clienttranslate('Straw'), $this->PAPER),
    13 => new MOT_Card(13, clienttranslate('Amulet'), $this->STONE),
    14 => new MOT_Card(14, clienttranslate('Bench'), $this->STONE),
    15 => new MOT_Card(15, clienttranslate('Daitoro'), $this->STONE),
    16 => new MOT_Card(16, clienttranslate('Fountain'), $this->STONE),
    17 => new MOT_Card(17, clienttranslate('Frog'), $this->STONE),
    18 => new MOT_Card(18, clienttranslate('Go Set'), $this->STONE),
    19 => new MOT_Card(19, clienttranslate('Pillar'), $this->STONE),
    20 => new MOT_Card(20, clienttranslate('Statue'), $this->STONE),
    21 => new MOT_Card(21, clienttranslate('Stool'), $this->STONE),
    22 => new MOT_Card(22, clienttranslate('Tablet'), $this->STONE),
    23 => new MOT_Card(23, clienttranslate('Tower'), $this->STONE),
    24 => new MOT_Card(24, clienttranslate('Cloak'), $this->CLOTH),
    25 => new MOT_Card(25, clienttranslate('Flag'), $this->CLOTH),
    26 => new MOT_Card(26, clienttranslate('Handkerchief'), $this->CLOTH),
    27 => new MOT_Card(27, clienttranslate('Kite'), $this->CLOTH),
    28 => new MOT_Card(28, clienttranslate('Mask'), $this->CLOTH),
    29 => new MOT_Card(29, clienttranslate('Puppet'), $this->CLOTH),
    30 => new MOT_Card(30, clienttranslate('Quilt'), $this->CLOTH),
    31 => new MOT_Card(31, clienttranslate('Robe'), $this->CLOTH),
    32 => new MOT_Card(32, clienttranslate('Socks'), $this->CLOTH),
    33 => new MOT_Card(33, clienttranslate('Tapestry'), $this->CLOTH),
    34 => new MOT_Card(34, clienttranslate('Umbrella'), $this->CLOTH),
    35 => new MOT_Card(35, clienttranslate('Bangle'), $this->CLAY),
    36 => new MOT_Card(36, clienttranslate('Bowl'), $this->CLAY),
    37 => new MOT_Card(37, clienttranslate('Brick'), $this->CLAY),
    38 => new MOT_Card(38, clienttranslate('Cup'), $this->CLAY),
    39 => new MOT_Card(39, clienttranslate('Dice'), $this->CLAY),
    40 => new MOT_Card(40, clienttranslate('Figurine'), $this->CLAY),
    41 => new MOT_Card(41, clienttranslate('Haniwa'), $this->CLAY),
    42 => new MOT_Card(42, clienttranslate('Jar'), $this->CLAY),
    43 => new MOT_Card(43, clienttranslate('Teapot'), $this->CLAY),
    44 => new MOT_Card(44, clienttranslate('Vase'), $this->CLAY),
    45 => new MOT_Card(45, clienttranslate('Bell'), $this->METAL),
    46 => new MOT_Card(46, clienttranslate('Chopsticks'), $this->METAL),
    47 => new MOT_Card(47, clienttranslate('Coin'), $this->METAL),
    48 => new MOT_Card(48, clienttranslate('Flute'), $this->METAL),
    49 => new MOT_Card(49, clienttranslate('Gong'), $this->METAL),
    50 => new MOT_Card(50, clienttranslate('Pin'), $this->METAL),
    51 => new MOT_Card(51, clienttranslate('Ring'), $this->METAL),
    52 => new MOT_Card(52, clienttranslate('Shuriken'), $this->METAL),
    53 => new MOT_Card(53, clienttranslate('Sword'), $this->METAL),
    54 => new MOT_Card(54, clienttranslate('Turtle'), $this->METAL),
];

$this->CRANE = $this->cards[1];
$this->CURTAIN = $this->cards[2];
$this->DECK_OF_CARDS = $this->cards[3];
$this->DOLL = $this->cards[4];
$this->FAN = $this->cards[5];
$this->LAMPSHADE = $this->cards[6];
$this->PINWHEEL = $this->cards[7];
$this->PLANE = $this->cards[8];
$this->POEM = $this->cards[9];
$this->SCROLL = $this->cards[10];
$this->SKETCH = $this->cards[11];
$this->STRAW = $this->cards[12];
$this->AMULET = $this->cards[13];
$this->BENCH = $this->cards[14];
$this->DAITORO = $this->cards[15];
$this->FOUNTAIN = $this->cards[16];
$this->FROG = $this->cards[17];
$this->GO_SET = $this->cards[18];
$this->PILLAR = $this->cards[19];
$this->STATUE = $this->cards[20];
$this->STOOL = $this->cards[21];
$this->TABLET = $this->cards[22];
$this->TOWER = $this->cards[23];
$this->CLOAK = $this->cards[24];
$this->FLAG = $this->cards[25];
$this->HANDKERCHIEF = $this->cards[26];
$this->KITE = $this->cards[27];
$this->MASK = $this->cards[28];
$this->PUPPET = $this->cards[29];
$this->QUILT = $this->cards[30];
$this->ROBE = $this->cards[31];
$this->SOCKS = $this->cards[32];
$this->TAPESTRY = $this->cards[33];
$this->UMBRELLA = $this->cards[34];
$this->BANGLE = $this->cards[35];
$this->BOWL = $this->cards[36];
$this->BRICK = $this->cards[37];
$this->CUP = $this->cards[38];
$this->DICE = $this->cards[39];
$this->FIGURINE = $this->cards[40];
$this->HANIWA = $this->cards[41];
$this->JAR = $this->cards[42];
$this->TEAPOT = $this->cards[43];
$this->VASE = $this->cards[44];
$this->BELL = $this->cards[45];
$this->CHOPSTICKS = $this->cards[46];
$this->COIN = $this->cards[47];
$this->FLUTE = $this->cards[48];
$this->GONG = $this->cards[49];
$this->PIN = $this->cards[50];
$this->RING = $this->cards[51];
$this->SHURIKEN = $this->cards[52];
$this->SWORD = $this->cards[53];
$this->TURTLE = $this->cards[54];
