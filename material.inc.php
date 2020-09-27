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


$this->materials = [
    1 => new MOT_Material(1, 'Paper', 1, '📜', 'Clerk', 'Sell a material'),
    2 => new MOT_Material(2, 'Stone', 2, '🗿', 'Monk', 'Hire a helper'),
    3 => new MOT_Material(3, 'Cloth', 2, '🧵', 'Tailor', 'Refill your hand'),
    4 => new MOT_Material(4, 'Clay', 3, '🧱', 'Potter', 'Collect a material'),
    5 => new MOT_Material(5, 'Metal', 3, '🔧', 'Smith', 'Complete any work'),
];

$this->PAPER = $this->materials[1];
$this->STONE = $this->materials[2];
$this->CLOTH = $this->materials[3];
$this->CLAY = $this->materials[4];
$this->METAL = $this->materials[5];

$this->cards = [
     1 => new MOT_Card(1, 'Crane', $this->PAPER),
     2 => new MOT_Card(2, 'Curtain', $this->PAPER),
     3 => new MOT_Card(3, 'Deck of Cards', $this->PAPER),
     4 => new MOT_Card(4, 'Doll', $this->PAPER),
     5 => new MOT_Card(5, 'Fan', $this->PAPER),
     6 => new MOT_Card(6, 'Lampshade', $this->PAPER),
     7 => new MOT_Card(7, 'Pinwheel', $this->PAPER),
     8 => new MOT_Card(8, 'Plane', $this->PAPER),
     9 => new MOT_Card(9, 'Poem', $this->PAPER),
    10 => new MOT_Card(10, 'Scroll', $this->PAPER),
    11 => new MOT_Card(11, 'Sketch', $this->PAPER),
    12 => new MOT_Card(12, 'Straw', $this->PAPER),
    13 => new MOT_Card(13, 'Statue', $this->STONE),
    14 => new MOT_Card(14, 'Pillar', $this->STONE),
    15 => new MOT_Card(15, 'Frog', $this->STONE),
    16 => new MOT_Card(16, 'Tablet', $this->STONE),
    17 => new MOT_Card(17, 'Stool', $this->STONE),
    18 => new MOT_Card(18, 'Go Set', $this->STONE),
    19 => new MOT_Card(19, 'Fountain', $this->STONE),
    20 => new MOT_Card(20, 'Tower', $this->STONE),
    21 => new MOT_Card(21, 'Daitoro', $this->STONE),
    22 => new MOT_Card(22, 'Amulet', $this->STONE),
    23 => new MOT_Card(23, 'Bench', $this->STONE),
    24 => new MOT_Card(24, 'Kite', $this->CLOTH),
    25 => new MOT_Card(25, 'Umbrella', $this->CLOTH),
    26 => new MOT_Card(26, 'Socks', $this->CLOTH),
    27 => new MOT_Card(27, 'Quilt', $this->CLOTH),
    28 => new MOT_Card(28, 'Robe', $this->CLOTH),
    29 => new MOT_Card(29, 'Flag', $this->CLOTH),
    30 => new MOT_Card(30, 'Tapestry', $this->CLOTH),
    31 => new MOT_Card(31, 'Handkerchief', $this->CLOTH),
    32 => new MOT_Card(32, 'Puppet', $this->CLOTH),
    33 => new MOT_Card(33, 'Mask', $this->CLOTH),
    34 => new MOT_Card(34, 'Cloak', $this->CLOTH),
    35 => new MOT_Card(35, 'Vase', $this->CLAY),
    36 => new MOT_Card(36, 'Haniwa', $this->CLAY),
    37 => new MOT_Card(37, 'Teapot', $this->CLAY),
    38 => new MOT_Card(38, 'Dice', $this->CLAY),
    39 => new MOT_Card(39, 'Bowl', $this->CLAY),
    40 => new MOT_Card(40, 'Jar', $this->CLAY),
    41 => new MOT_Card(41, 'Brick', $this->CLAY),
    42 => new MOT_Card(42, 'Figurine', $this->CLAY),
    43 => new MOT_Card(43, 'Bangle', $this->CLAY),
    44 => new MOT_Card(44, 'Cup', $this->CLAY),
    45 => new MOT_Card(45, 'Ring', $this->METAL),
    46 => new MOT_Card(46, 'Flute', $this->METAL),
    47 => new MOT_Card(47, 'Sword', $this->METAL),
    48 => new MOT_Card(48, 'Shuriken', $this->METAL),
    49 => new MOT_Card(49, 'Gong', $this->METAL),
    50 => new MOT_Card(50, 'Pin', $this->METAL),
    51 => new MOT_Card(51, 'Coin', $this->METAL),
    52 => new MOT_Card(52, 'Turtle', $this->METAL),
    53 => new MOT_Card(53, 'Bell', $this->METAL),
    54 => new MOT_Card(54, 'Chopsticks', $this->METAL),
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
$this->STATUE = $this->cards[13];
$this->PILLAR = $this->cards[14];
$this->FROG = $this->cards[15];
$this->TABLET = $this->cards[16];
$this->STOOL = $this->cards[17];
$this->GO_SET = $this->cards[18];
$this->FOUNTAIN = $this->cards[19];
$this->TOWER = $this->cards[20];
$this->DAITORO = $this->cards[21];
$this->AMULET = $this->cards[22];
$this->BENCH = $this->cards[23];
$this->KITE = $this->cards[24];
$this->UMBRELLA = $this->cards[25];
$this->SOCKS = $this->cards[26];
$this->QUILT = $this->cards[27];
$this->ROBE = $this->cards[28];
$this->FLAG = $this->cards[29];
$this->TAPESTRY = $this->cards[30];
$this->HANDKERCHIEF = $this->cards[31];
$this->PUPPET = $this->cards[32];
$this->MASK = $this->cards[33];
$this->CLOAK = $this->cards[34];
$this->VASE = $this->cards[35];
$this->HANIWA = $this->cards[36];
$this->TEAPOT = $this->cards[37];
$this->DICE = $this->cards[38];
$this->BOWL = $this->cards[39];
$this->JAR = $this->cards[40];
$this->BRICK = $this->cards[41];
$this->FIGURINE = $this->cards[42];
$this->BANGLE = $this->cards[43];
$this->CUP = $this->cards[44];
$this->RING = $this->cards[45];
$this->FLUTE = $this->cards[46];
$this->SWORD = $this->cards[47];
$this->SHURIKEN = $this->cards[48];
$this->GONG = $this->cards[49];
$this->PIN = $this->cards[50];
$this->COIN = $this->cards[51];
$this->TURTLE = $this->cards[52];
$this->BELL = $this->cards[53];
$this->CHOPSTICKS = $this->cards[54];

$this->colors = array(
    1 => array( 'name' => clienttranslate('spade'),
                'nametr' => self::_('spade') ),
    2 => array( 'name' => clienttranslate('heart'),
                'nametr' => self::_('heart') ),
    3 => array( 'name' => clienttranslate('club'),
                'nametr' => self::_('club') ),
    4 => array( 'name' => clienttranslate('diamond'),
                'nametr' => self::_('diamond') )
);

$this->values_label = array(
    2 =>'2',
    3 => '3',
    4 => '4',
    5 => '5',
    6 => '6',
    7 => '7',
    8 => '8',
    9 => '9',
    10 => '10',
    11 => clienttranslate('J'),
    12 => clienttranslate('Q'),
    13 => clienttranslate('K'),
    14 => clienttranslate('A')
);
