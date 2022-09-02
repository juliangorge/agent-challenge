<?php 

/*
Agente:
Obtiene la siguiente información y la envía hacia un endpoint

- Información sobre el procesador.
- Listado de procesos corriendo.
- Usuarios con una sesión abierta en el sistema.
- Nombre del sistema operativo.
- Versión del sistema operativo.
*/

class Agent 
{

	protected $apiURL = 'https://mp.juliangorge.com.ar'; //'http://127.0.0.1';
	protected $credentials;

	public function __construct(string $username, string $password)
	{
		print_r('Starting Agent' . PHP_EOL);
		$this->credentials = [
			'username' => $username,
			'password' => $password
		];
	}

	private function executeCommand(string $command)
	{
		$output = '';
		exec($command, $output);

		return $output;
	}

	public function getProcessorInfo()
	{
		return $this->executeCommand('lscpu');
	}

	public function getRunningProcesses()
	{
		return $this->executeCommand('ps -f -u www-data 2>&1');
	}

	public function getLoggedInUsers()
	{
		$values = $this->executeCommand('who');
		$users = [];

		foreach($values as $value)
		{
			$line = explode(' ', $value);
			$users[] = $line[0];
		}

		return $users;
	}

	public function getOSName()
	{
		return $this->executeCommand('hostnamectl');
	}

	public function getOSVersion()
	{
		return $this->executeCommand('uname -v');
	}

	private function getToken()
	{
		$ch = curl_init();

		curl_setopt($ch, CURLOPT_URL, $this->apiURL . '/oauth');
		curl_setopt($ch, CURLOPT_HTTPHEADER, [
			'Authorization: Basic '. base64_encode($this->credentials['username'] . ':' . $this->credentials['password'])
		]);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, [
			'grant_type' => 'password',
			'username' => $this->credentials['username'],
			'password' => $this->credentials['password']
		]);

		$response = curl_exec($ch);
		curl_close($ch);

		if($response == false) exit('An error has ocurred');

		$decoded_response = json_decode($response);

		if(isset($decoded_response->detail)) exit($decoded_response->detail);

		return json_decode($response);
	}

	public function init()
	{
		$token = $this->getToken();

		$post = [
			'ip_address' => $this->getIpAddress(),
			'logged_in_users' => $this->getLoggedInUsers(),
			'running_processes' => $this->getRunningProcesses(),
			'processor_info' => $this->getProcessorInfo(),
			'os_name' => $this->getOSName(),
			'os_version' => $this->getOSVersion(),
		];

        $ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $this->apiURL . '/reports');
		curl_setopt($ch, CURLOPT_HTTPHEADER, [
			'Authorization: Bearer '. $token->access_token
		]);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $post);

		$response = curl_exec($ch);
		
		curl_close($ch);

		return;
	}

}
