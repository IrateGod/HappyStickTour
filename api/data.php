<?php

date_default_timezone_set('UTC');

require_once '../php_classes/Database.php';
require_once '../php_classes/OsuApi.php';

$database = new Database();
$osuApi = new OsuApi();

switch ($_GET['query']) {
	case 'user':
		switch ($_SERVER['REQUEST_METHOD']) {
			case 'GET': getUser(); break; // get user data
			case 'PUT': putUser(); break; // update user data
		}
		break;
	case 'registrations':
		switch ($_SERVER['REQUEST_METHOD']) {
			case 'GET': getRegistrations(); break; // get all player registrations
		}
		break;
	case 'registration':
		switch ($_SERVER['REQUEST_METHOD']) {
			case 'GET': getRegistration(); break; // get player registration
			case 'PUT': putRegistration(); break; // update player registration
			case 'POST': postRegistration(); break; // create new player registration
			case 'DELETE': deleteRegistration(); break; // delete player registration
		}
		break;
	case 'rounds':
		switch ($_SERVER['REQUEST_METHOD']) {
			case 'GET': getRounds(); break; // get all rounds
			case 'POST': postRound(); break; // create  new round
		}
		break;
	case 'round':
		switch ($_SERVER['REQUEST_METHOD']) {
			case 'GET': getRound(); break; // get round
			case 'PUT': putRound(); break; // update round
			case 'DELETE': deleteRound(); break; // delete round
		}
		break;
	case 'tiers':
		switch ($_SERVER['REQUEST_METHOD']) {
			case 'GET': getTiers(); break; // get all tiers
			case 'POST': postTier(); break; // create new tier
		}
		break;
	case 'tier':
		switch ($_SERVER['REQUEST_METHOD']) {
			case 'GET': getTier(); break; // get tier
			case 'PUT': putTier(); break; // update tier
			case 'DELETE': deleteTier(); break; // delete tier
		}
		break;
	case 'lobbies':
		switch ($_SERVER['REQUEST_METHOD']) {
			case 'GET': getLobbies(); break; // get all lobbies
			case 'POST': postLobbies(); break; // create new lobbies
			case 'DELETE': deleteLobbies(); break; // delete lobbies
		}
		break;
	case 'lobby':
		switch ($_SERVER['REQUEST_METHOD']) {
			case 'GET': getLobby(); break; // get lobby
			case 'PUT': putLobby(); break; // update lobby
		}
		break;
	case 'mappools':
		switch ($_SERVER['REQUEST_METHOD']) {
			case 'GET': getMappools(); break; // get all mappools
			case 'POST': postMappool(); break; // create new mappool
			case 'DELETE': deleteMappool(); break; // delete mappool
		}
		break;
	case 'mappoolslots':
		switch ($_SERVER['REQUEST_METHOD']) {
			case 'GET': getMappoolSlots(); break; // get mappool slots
			case 'PUT': putMappoolSlot(); break; // update mappool slot
			case 'POST': postMappoolSlot(); break; // create mappool slot
			case 'DELETE': deleteMappoolSlot(); break; // delete mappool slot
		}
		break;
	case 'osuprofile':
		switch ($_SERVER['REQUEST_METHOD']) {
			case 'GET': getOsuProfile(); break; // get osu profile
		}
		break;
	case 'osubeatmap':
		switch ($_SERVER['REQUEST_METHOD']) {
			case 'GET': getOsuBeatmap(); break; // get osu beatmap
		}
		break;
	case 'osumatch':
		switch ($_SERVER['REQUEST_METHOD']) {
			case 'GET': getOsuMatch(); break; // get osu match
		}
		break;
	case 'osugame':
	 	switch ($_SERVER['REQUEST_METHOD']) {
	 		case 'PUT': putOsuGame(); break; // update osu game
	 	}
	 	break;
	case 'availability':
		switch ($_SERVER['REQUEST_METHOD']) {
			case 'GET': getAvailability(); break; // get availability
			case 'PUT': putAvailability(); break; // update availability
		}
		break;
	case 'settings':
		switch ($_SERVER['REQUEST_METHOD']) {
			case 'GET': getSettings(); break; // get all settings
			case 'PUT': putSettings(); break; // update settings
		}
		break;
	case 'feedback':
		switch ($_SERVER['REQUEST_METHOD']) {
			case 'GET': getFeedback(); break; // get feedback
			case 'PUT': putFeedback(); break; // update feedback
		}
		break;
}

function echoError($error, $message) {
	$response = new stdClass;
	$response->error = $error ? '1' : '0';
	$response->message = $message;
	echo json_encode($response);
}

function recalculateRound($round) {
	global $database;
	$db = $database->getConnection();

	if (empty($round)) {
		$stmt = $db->prepare('SELECT has_continue, continue_round, has_drop_down, drop_down_round
			FROM rounds
			WHERE is_first_round = 1');
		$stmt->execute();
		$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
		if (!empty($rows[0]) && !empty($rows[0]['has_continue'])) {
			recalculateRound($rows[0]['continue_round']);
		}
		if (!empty($rows[0]) && !empty($rows[0]['has_drop_down'])) {
			recalculateRound($rows[0]['drop_down_round']);
		}
	} else {
		$playerAmount = 0;

		$stmt = $db->prepare('SELECT player_amount, lobby_size, continue_amount
			FROM rounds
			WHERE has_continue = 1 AND continue_round = :continue_round');
		$stmt->bindValue(':continue_round', $round, PDO::PARAM_INT);
		$stmt->execute();
		$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
		foreach ($rows as $row) {
			$playerAmount += $row['player_amount'] / $row['lobby_size'] * $row['continue_amount'];
		}
		$stmt = $db->prepare('SELECT player_amount, lobby_size, drop_down_amount
			FROM rounds
			WHERE has_drop_down = 1 AND drop_down_round = :drop_down_round');
		$stmt->bindValue(':drop_down_round', $round, PDO::PARAM_INT);
		$stmt->execute();
		$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
		foreach ($rows as $row) {
			$playerAmount += $row['player_amount'] / $row['lobby_size'] * $row['drop_down_amount'];
		}

		$stmt = $db->prepare('UPDATE rounds
			SET player_amount = :player_amount
			WHERE id = :id');
		$stmt->bindValue(':player_amount', $playerAmount, PDO::PARAM_INT);
		$stmt->bindValue(':id', $round, PDO::PARAM_INT);
		$stmt->execute();

		$stmt = $db->prepare('SELECT has_continue, continue_round, has_drop_down, drop_down_round
			FROM rounds
			WHERE id = :id');
		$stmt->bindValue(':id', $round, PDO::PARAM_INT);
		$stmt->execute();
		$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
		if (!empty($rows[0]['has_continue'])) {
			recalculateRound($rows[0]['continue_round']);
		}
		if (!empty($rows[0]['has_drop_down'])) {
			recalculateRound($rows[0]['drop_down_round']);
		}
	}
}

function getUser() {

}

function putUser() {

}

function getRegistrations() {

}

function getRegistration() {

}

function putRegistration() {

}

function postRegistration() {

}

function deleteRegistration() {

}

function getRounds() {
	global $database;
	$db = $database->getConnection();

	$stmt = $db->prepare('SELECT id, name, lobby_size as lobbySize, best_of as bestOf, is_first_round as isFirstRound, player_amount as playerAmount, is_start_round as isStartRound, has_continue as hasContinue, continue_amount as continueAmount, continue_round as continueRound, has_drop_down as hasDropDown, drop_down_amount as dropDownAmount, drop_down_round as dropDownRound, has_elimination as hasElimination, eliminated_amount as eliminatedAmount, has_bracket_reset as hasBracketReset, mappools_released as mappoolsReleased, lobbies_released as lobbiesReleased
		FROM rounds
		ORDER BY id ASC');
	$stmt->execute();
	$rounds = $stmt->fetchAll(PDO::FETCH_ASSOC);
	foreach ($rounds as &$round) {
		$stmt = $db->prepare('SELECT time_from as `from`, time_to as `to`
			FROM round_times
			WHERE round = :round');
		$stmt->bindValue(':round', $round['id'], PDO::PARAM_INT);
		$stmt->execute();
		$round['times'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
	}
	echo json_encode($rounds);
}

function postRound() {
	global $database;
	$db = $database->getConnection();

	$body = json_decode(file_get_contents('php://input'));

	$stmt = $db->prepare('INSERT INTO rounds (name, lobby_size, best_of, is_first_round, player_amount, is_start_round, has_continue, continue_amount, continue_round, has_drop_down, drop_down_amount, drop_down_round, has_elimination, eliminated_amount, has_bracket_reset, mappools_released, lobbies_released)
		VALUES (:name, :lobby_size, :best_of, :is_first_round, :player_amount, :is_start_round, :has_continue, :continue_amount, :continue_round, :has_drop_down, :drop_down_amount, :drop_down_round, :has_elimination, :eliminated_amount, :has_bracket_reset, :mappools_released, :lobbies_released)');
	$stmt->bindValue(':name', $body->name, PDO::PARAM_STR);
	$stmt->bindValue(':lobby_size', $body->lobbySize, PDO::PARAM_INT);
	$stmt->bindValue(':best_of', $body->bestOf, PDO::PARAM_INT);
	$stmt->bindValue(':is_first_round', $body->isFirstRound, PDO::PARAM_BOOL);
	$stmt->bindValue(':player_amount', $body->playerAmount, PDO::PARAM_INT);
	$stmt->bindValue(':is_start_round', $body->isStartRound, PDO::PARAM_BOOL);
	$stmt->bindValue(':has_continue', $body->hasContinue, PDO::PARAM_BOOL);
	$stmt->bindValue(':continue_amount', $body->continueAmount, PDO::PARAM_INT);
	$stmt->bindValue(':continue_round', $body->continueRoundId, PDO::PARAM_INT);
	$stmt->bindValue(':has_drop_down', $body->hasDropDown, PDO::PARAM_BOOL);
	$stmt->bindValue(':drop_down_amount', $body->dropDownAmount, PDO::PARAM_INT);
	$stmt->bindValue(':drop_down_round', $body->dropDownRoundId, PDO::PARAM_INT);
	$stmt->bindValue(':has_elimination', $body->hasElimination, PDO::PARAM_BOOL);
	$stmt->bindValue(':eliminated_amount', $body->eliminatedAmount, PDO::PARAM_INT);
	$stmt->bindValue(':has_bracket_reset', $body->hasBracketReset, PDO::PARAM_BOOL);
	$stmt->bindValue(':mappools_released', $body->mappoolsReleased, PDO::PARAM_BOOL);
	$stmt->bindValue(':lobbies_released', $body->lobbiesReleased, PDO::PARAM_BOOL);
	$stmt->execute();

	$round = $db->lastInsertId();

	foreach ($body->times as $time) {
		$stmt = $db->prepare('INSERT INTO round_times (round, time_from, time_to)
			VALUES (:round, :time_from, :time_to)');
		$stmt->bindValue(':round', $round, PDO::PARAM_INT);
		$stmt->bindValue(':time_from', $time->from, PDO::PARAM_STR);
		$stmt->bindValue(':time_to', $time->to, PDO::PARAM_STR);
		$stmt->execute();
	}

	recalculateRound(0);

	echoError(0, 'Round saved');
}

function getRound() {
	global $database;
	$db = $database->getConnection();

	$stmt = $db->prepare('SELECT id, name, lobby_size as lobbySize, best_of as bestOf, is_first_round as isFirstRound, player_amount as playerAmount, is_start_round as isStartRound, has_continue as hasContinue, continue_amount as continueAmount, continue_round as continueRound, has_drop_down as hasDropDown, drop_down_amount as dropDownAmount, drop_down_round as dropDownRound, has_elimination as hasElimination, eliminated_amount as eliminatedAmount, has_bracket_reset as hasBracketReset, mappools_released as mappoolsReleased, lobbies_released as lobbiesReleased
		FROM rounds
		WHERE id = :id');
	$stmt->bindValue(':id', $_GET['round'], PDO::PARAM_INT);
	$stmt->execute();
	$round = $stmt->fetchAll(PDO::FETCH_ASSOC)[0];
	$stmt = $db->prepare('SELECT id, time_from as timeFrom, time_to as timeTo
		FROM round_times
		WHERE round = :round');
	$stmt->bindValue(':round', $_GET['round'], PDO::PARAM_INT);
	$stmt->execute();
	$round['times'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
	echo json_encode($round);
}

function putRound() {
	global $database;
	$db = $database->getConnection();

	$body = json_decode(file_get_contents('php://input'));

	$stmt = $db->prepare('UPDATE rounds
		SET name = :name, lobby_size = :lobby_size, best_of = :best_of, is_first_round = :is_first_round, player_amount = :player_amount, is_start_round = :is_start_round, has_continue = :has_continue, continue_amount = :continue_amount, continue_round = :continue_round, has_drop_down = :has_drop_down, drop_down_amount = :drop_down_amount, drop_down_round = :drop_down_round, has_elimination = :has_elimination, eliminated_amount = :eliminated_amount, has_bracket_reset = :has_bracket_reset, mappools_released = :mappools_released, lobbies_released = :lobbies_released
		WHERE id = :id');
	$stmt->bindValue(':name', $body->name, PDO::PARAM_STR);
	$stmt->bindValue(':lobby_size', $body->lobbySize, PDO::PARAM_INT);
	$stmt->bindValue(':best_of', $body->bestOf, PDO::PARAM_INT);
	$stmt->bindValue(':is_first_round', $body->isFirstRound, PDO::PARAM_BOOL);
	$stmt->bindValue(':player_amount', $body->playerAmount, PDO::PARAM_INT);
	$stmt->bindValue(':is_start_round', $body->isStartRound, PDO::PARAM_BOOL);
	$stmt->bindValue(':has_continue', $body->hasContinue, PDO::PARAM_BOOL);
	$stmt->bindValue(':continue_amount', $body->continueAmount, PDO::PARAM_INT);
	$stmt->bindValue(':continue_round', $body->continueRoundId, PDO::PARAM_INT);
	$stmt->bindValue(':has_drop_down', $body->hasDropDown, PDO::PARAM_BOOL);
	$stmt->bindValue(':drop_down_amount', $body->dropDownAmount, PDO::PARAM_INT);
	$stmt->bindValue(':drop_down_round', $body->dropDownRoundId, PDO::PARAM_INT);
	$stmt->bindValue(':has_elimination', $body->hasElimination, PDO::PARAM_BOOL);
	$stmt->bindValue(':eliminated_amount', $body->eliminatedAmount, PDO::PARAM_INT);
	$stmt->bindValue(':has_bracket_reset', $body->hasBracketReset, PDO::PARAM_BOOL);
	$stmt->bindValue(':mappools_released', $body->mappoolsReleased, PDO::PARAM_BOOL);
	$stmt->bindValue(':lobbies_released', $body->lobbiesReleased, PDO::PARAM_BOOL);
	$stmt->bindValue(':id', $_GET['round'], PDO::PARAM_INT);
	$stmt->execute();

	$stmt = $db->prepare('DELETE FROM round_times
		WHERE round = :round');
	$stmt->bindValue(':round', $_GET['round'], PDO::PARAM_INT);
	$stmt->execute();
	foreach ($body->times as $time) {
		$stmt = $db->prepare('INSERT INTO round_times (round, time_from, time_to)
			VALUES (:round, :time_from, :time_to)');
		$stmt->bindValue(':round', $_GET['round'], PDO::PARAM_INT);
		$stmt->bindValue(':time_from', $time->from, PDO::PARAM_STR);
		$stmt->bindValue(':time_to', $time->to, PDO::PARAM_STR);
		$stmt->execute();
	}

	recalculateRound(0);

	echoError(0, 'Round saved');
}

function deleteRound() {
	global $database;
	$db = $database->getConnection();

	$stmt = $db->prepare('UPDATE rounds
		SET has_continue = 0, continue_amount = 0, continue_round = NULL
		WHERE continue_round = :continue_round');
	$stmt->bindValue(':continue_round', $_GET['round'], PDO::PARAM_INT);
	$stmt->execute();
	$stmt = $db->prepare('UPDATE rounds
		SET has_drop_down = 0, drop_down_amount = 0, drop_down_round = NULL
		WHERE drop_down_round = :drop_down_round');
	$stmt->bindValue(':drop_down_round', $_GET['round'], PDO::PARAM_INT);
	$stmt->execute();

	$stmt = $db->prepare('DELETE FROM round_times
		WHERE round = :round');
	$stmt->bindValue(':round', $_GET['round'], PDO::PARAM_INT);
	$stmt->execute();

	$stmt = $db->prepare('DELETE FROM rounds
		WHERE id = :id');
	$stmt->bindValue(':id', $_GET['round'], PDO::PARAM_INT);
	$stmt->execute();

	recalculateRound(0);

	echoError(0, 'Round deleted');
}

function getTiers() {
	global $database;
	$db = $database->getConnection();

	$stmt = $db->prepare('SELECT id, name, lower_endpoint as lowerEndpoint, upper_endpoint as upperEndpoint, starting_round as startingRound, seed_by_rank as seedByRank, seed_by_time as seedByTime, seed_by_random as seedByRandom, sub_bonus as subBonus
		FROM tiers
		ORDER BY id ASC');
	$stmt->execute();
	echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
}

function postTier() {
	global $database;
	$db = $database->getConnection();

	$body = json_decode(file_get_contents('php://input'));

	$stmt = $db->prepare('INSERT INTO tiers (name, lower_endpoint, upper_endpoint, starting_round, seed_by_rank, seed_by_time, seed_by_random, sub_bonus)
		VALUES (:name, :lower_endpoint, :upper_endpoint, :starting_round, :seed_by_rank, :seed_by_time, :seed_by_random, :sub_bonus)');
	$stmt->bindValue(':name', $body->name, PDO::PARAM_STR);
	$stmt->bindValue(':lower_endpoint', $body->lowerEndpoint, PDO::PARAM_INT);
	$stmt->bindValue(':upper_endpoint', $body->upperEndpoint, PDO::PARAM_INT);
	$stmt->bindValue(':starting_round', $body->startingRound, PDO::PARAM_INT);
	$stmt->bindValue(':seed_by_rank', $body->selectedSeeding == 'rank', PDO::PARAM_BOOL);
	$stmt->bindValue(':seed_by_time', $body->selectedSeeding == 'time', PDO::PARAM_BOOL);
	$stmt->bindValue(':seed_by_random', $body->selectedSeeding == 'random', PDO::PARAM_BOOL);
	$stmt->bindValue(':sub_bonus', $body->subBonus, PDO::PARAM_BOOL);
	$stmt->execute();

	echoError(0, 'Tier saved');
}

function getTier() {
	global $database;
	$db = $database->getConnection();

	$stmt = $db->prepare('SELECT id, name, lower_endpoint as lowerEndpoint, upper_endpoint as upperEndpoint, starting_round as startingRound, seed_by_rank as seedByRank, seed_by_time as seedByTime, seed_by_random as seedByRandom, sub_bonus as subBonus
		FROM tiers
		WHERE id = :id');
	$stmt->bindValue(':id', $_GET['tier'], PDO::PARAM_INT);
	$stmt->execute();
	echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC)[0]);
}

function putTier() {
	global $database;
	$db = $database->getConnection();

	$body = json_decode(file_get_contents('php://input'));

	$stmt = $db->prepare('UPDATE tiers
		SET name = :name, lower_endpoint = :lower_endpoint, upper_endpoint = :upper_endpoint, starting_round = :starting_round, seed_by_rank = :seed_by_rank, seed_by_time = :seed_by_time, seed_by_random = :seed_by_random, sub_bonus = :sub_bonus
		WHERE id = :id');
	$stmt->bindValue(':name', $body->name, PDO::PARAM_STR);
	$stmt->bindValue(':lower_endpoint', $body->lowerEndpoint, PDO::PARAM_INT);
	$stmt->bindValue(':upper_endpoint', $body->upperEndpoint, PDO::PARAM_INT);
	$stmt->bindValue(':starting_round', $body->startingRound, PDO::PARAM_INT);
	$stmt->bindValue(':seed_by_rank', $body->selectedSeeding == 'rank', PDO::PARAM_BOOL);
	$stmt->bindValue(':seed_by_time', $body->selectedSeeding == 'time', PDO::PARAM_BOOL);
	$stmt->bindValue(':seed_by_random', $body->selectedSeeding == 'random', PDO::PARAM_BOOL);
	$stmt->bindValue(':sub_bonus', $body->subBonus, PDO::PARAM_BOOL);
	$stmt->bindValue(':id', $_GET['tier'], PDO::PARAM_INT);
	$stmt->execute();

	echoError(0, 'Tier saved');
}

function deleteTier() {
	global $database;
	$db = $database->getConnection();

	$stmt = $db->prepare('DELETE FROM tiers
		WHERE id = :id');
	$stmt->bindValue(':id', $_GET['tier'], PDO::PARAM_INT);
	$stmt->execute();

	echoError(0, 'Tier deleted');
}

function getLobbies() {

}

function postLobbies() {

}

function deleteLobbies() {

}

function getLobby() {

}

function putLobby() {

}

function getMappools() {

}

function postMappool() {

}

function deleteMappool() {

}

function getMappoolSlots() {

}

function putMappoolSlot() {

}

function postMappoolSlot() {

}

function deleteMappoolSlot() {

}

function getOsuProfile() {
	global $osuApi;
	echo json_encode($osuApi->getUser($_GET['id']));
}

function getOsuBeatmap() {
	global $osuApi;
	echo json_encode($osuApi->getBeatmap($_GET['id']));
}

function getOsuMatch() {

}

function putOsuGame() {

}

function getAvailability() {

}

function putAvailability() {

}

function getSettings() {
	global $database;
	$db = $database->getConnection();

	$stmt = $db->prepare('SELECT registrations_open as registrationsOpen, registrations_from as registrationsFrom, registrations_to as registrationsTo
		FROM settings');
	$stmt->execute();
	echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC)[0]);
}

function putSettings() {
	global $database;
	$db = $database->getConnection();

	$body = json_decode(file_get_contents('php://input'));

	if (isset($body->registrationsOpen)) {
		$stmt = $db->prepare('UPDATE settings
			SET registrations_open = :registrations_open');
		$stmt->bindValue(':registrations_open', $body->registrationsOpen, PDO::PARAM_BOOL);
		$stmt->execute();
	}
	if (isset($body->registrationsFrom)) {
		$stmt = $db->prepare('UPDATE settings
			SET registrations_from = :registrations_from');
		$stmt->bindValue(':registrations_from', $body->registrationsFrom, PDO::PARAM_STR);
		$stmt->execute();
	}
	if (isset($body->registrationsTo)) {
		$stmt = $db->prepare('UPDATE settings
			SET registrations_to = :registrations_to');
		$stmt->bindValue(':registrations_to', $body->registrationsTo, PDO::PARAM_STR);
		$stmt->execute();
	}

	echoError(0, 'Settings saved');
}

function getFeedback() {

}

function putFeedback() {
	
}

?>