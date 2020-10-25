{OVERALL_GAME_HEADER}
<!-- BEGIN myblock -->
<div id="player_table"></div>
<!-- END myblock -->

<script type="text/javascript">

// Javascript HTML templates
var jstpl_playerTable =
	'<div class="table whiteblock" id="player_table_${id}">' +
	'  <h3 style="color: #${color}">${name}</h3>' +
	'  <div style="float: right"><span class="table_label">Waiting Area:</span><div class="card_list" id="player_${id}_waiting_area"></div></div>' +
	'  <h3>Hand</h3><div id="player_${id}_hand"></div>' +
	'  <div class="player_board">' +
	'  <div class="player_board_item wing"><span class="table_label">Gallery</span><div id="player_${id}_gallery"></div></div>' +
	'  <div class="player_board_item"><span class="table_label">Helpers</span><div class="card_list" id="player_${id}_helpers"></div></div>' +
	'  <div class="player_board_item">' +
	'  <div><span class="table_label">Task</span><div class="card_list" id="player_${id}_task"></div></div>' +
	'  <div><span class="table_label">Craft Bench</span><div class="card_list" id="player_${id}_craft_bench"></div></div>' +
	'  </div>' +
	'  <div class="player_board_item"><span class="table_label">Sales</span><div class="card_list" id="player_${id}_sales"></div></div>' +
	'  <div class="player_board_item wing"><span class="table_label">Gift Shop</span><div id="player_${id}_gift_shop"></div></div>' +
	'  </div>' +
	'</div>';

var jstpl_otherPlayerTable =
	'<div class="table whiteblock" id="player_table_${id}">' +
	'  <h3 style="color: #${color}">${name}</h3>' +
	'  <div><span class="table_label">Revealed hand:</span><div class="card_list" id="player_${id}_hand"></div></div>' +
	'  <div><span class="table_label">Hand size:</span><div class="card_list" id="player_${id}_hand_count"></div></div>' +
	'  <div><span class="table_label">Task:</span><div class="card_list" id="player_${id}_task"></div></div>' +
	'  <div><span class="table_label">Gallery:</span><div class="card_list" id="player_${id}_gallery"></div></div>' +
	'  <div><span class="table_label">Gift Shop:</span><div class="card_list" id="player_${id}_gift_shop"></div></div>' +
	'  <div><span class="table_label">Helpers:</span><div class="card_list" id="player_${id}_helpers"></div></div>' +
	'  <div><span class="table_label">Craft Bench:</span><div class="card_list" id="player_${id}_craft_bench"></div></div>' +
	'  <div><span class="table_label">Sales:</span><div class="card_list" id="player_${id}_sales"></div></div>' +
	'  <div><span class="table_label">Waiting Area:</span><div class="card_list" id="player_${id}_waiting_area"></div></div>' +
	'</div>';
</script>  

{OVERALL_GAME_FOOTER}
