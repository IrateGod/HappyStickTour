<?php

class DiscordApi {

	private $clientId;
	private $clientSecret;
	private $redirectUri;
	private $botToken;

	private $scopes = 'identify';

	//private $guild_id = '279958943890145280'; // BorengarTest Guild
	private $guildId = '110691455600689152'; // HappyStick Guild
	private $roleId = '333708355854139412'; // Player role

	function __construct() {
		$config = parse_ini_file('config.ini');
		$this->clientId = $config['discordClientId'];
		$this->clientSecret = $config['discordClientSecret'];
		$this->redirectUri = $config['discordRedirectUri'];
		$this->botToken = $config['discordBotToken'];
	}

	public function getLoginUri() {
		return 'https://discordapp.com/oauth2/authorize?client_id=' . $this->clientId . '&scope=' . $this->scopes . '&redirect_uri=' . urlencode($this->redirectUri) . '&response_type=token';
	}

	public function getUser($token) {
		$curl = curl_init();
		curl_setopt_array($curl, array(
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_FOLLOWLOCATION => false,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_URL => 'https://discordapp.com/api/users/@me',
			CURLOPT_HTTPHEADER => array(
				'Authorization: Bearer ' . $token
				)
			)
		);
		$response = json_decode(curl_exec($curl));
		curl_close($curl);
		return $response;
	}

	public function addUserToPlayers($playerId) {
		$curl = curl_init();
		curl_setopt_array($curl, array(
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_FOLLOWLOCATION => false,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_CUSTOMREQUEST => 'PUT',
			CURLOPT_URL => 'https://discordapp.com/api/guilds/' . $this->guildId . '/members/' . $playerId . '/roles/' . $this->roleId,
			CURLOPT_HTTPHEADER => array(
				'Authorization: Bot ' . $this->botToken
				),
			CURLOPT_POSTFIELDS => ''
			)
		);
		$response = json_decode(curl_exec($curl));
		curl_close($curl);
		return $response;
	}

	public function removeUserFromPlayers($playerId) {
		$curl = curl_init();
		curl_setopt_array($curl, array(
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_FOLLOWLOCATION => false,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_CUSTOMREQUEST => 'DELETE',
			CURLOPT_URL => 'https://discordapp.com/api/guilds/' . $this->guildId . '/members/' . $playerId . '/roles/' . $this->roleId,
			CURLOPT_HTTPHEADER => array(
				'Authorization: Bot ' . $this->botToken
				),
			CURLOPT_POSTFIELDS => ''
			)
		);
		$response = json_decode(curl_exec($curl));
		curl_close($curl);
		return $response;
	}

	public function addUserRole($playerId, $roleId) {
		$curl = curl_init();
		curl_setopt_array($curl, array(
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_FOLLOWLOCATION => false,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_CUSTOMREQUEST => 'PUT',
			CURLOPT_URL => 'https://discordapp.com/api/guilds/' . $this->guildId . '/members/' . $playerId . '/roles/' . $roleId,
			CURLOPT_HTTPHEADER => array(
				'Authorization: Bot ' . $this->botToken
				),
			CURLOPT_POSTFIELDS => ''
			)
		);
		$response = json_decode(curl_exec($curl));
		curl_close($curl);
		return $response;
	}

	public function getUserRoles($playerId) {
		$response = $this->getGuildMember($playerId);
		if ($response === false) {
			return false;
		}
		return $response->roles;
	}

	public function getGuildRoles() {
		$curl = curl_init();
		curl_setopt_array($curl, array(
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_FOLLOWLOCATION => false,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_URL => 'https://discordapp.com/api/guilds/' . $this->guildId . '/roles',
			CURLOPT_HTTPHEADER => array(
				'Authorization: Bot ' . $this->botToken
				)
			)
		);
		$response = json_decode(curl_exec($curl));
		curl_close($curl);
		return $response;
	}

	public function getGuildMember($playerId) {
		$curl = curl_init();
		curl_setopt_array($curl, array(
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_FOLLOWLOCATION => false,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_URL => 'https://discordapp.com/api/guilds/' . $this->guildId . '/members/' . $playerId,
			CURLOPT_HTTPHEADER => array(
				'Authorization: Bot ' . $this->botToken
				)
			)
		);
		$response = json_decode(curl_exec($curl));
		if (!empty($response->code) && $response->code == 10007) {
			return false;
		}
		curl_close($curl);
		return $response;
	}

}

?>