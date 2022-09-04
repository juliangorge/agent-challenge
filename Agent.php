<?php 

class Agent 
{

	protected $apiURL = 'https://mp.juliangorge.com.ar';
	protected $credentials;
	protected $usageMode;

	// Inicializa Agente con las credenciales y usageMode (0 = POST, 1 = GET)
	public function __construct(string $username, string $password, bool $usageMode)
	{
		print_r('Starting Agent' . PHP_EOL);
		$this->credentials = [
			'username' => $username,
			'password' => $password
		];
		$this->usageMode = $usageMode;
	}

	// Retorna los resultados de la ejecución de comandos de terminal
	private function executeCommand(string $command)
	{
		$output = '';
		exec($command, $output);

		return $output;
	}

	public function getIpAddress()
	{
		$command = $this->executeCommand('dig TXT +short o-o.myaddr.l.google.com @ns1.google.com');

		return str_replace('"','', $command[0]);
	}

	public function getProcessorInfo()
	{
		$command = $this->executeCommand('lscpu');
		return json_encode($command);
	}

	public function getRunningProcesses()
	{
		$command = $this->executeCommand('ps -aux');
		return json_encode($command);
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

		return json_encode($users);
	}

	public function getOSName()
	{
		$command = $this->executeCommand('uname -mrs');
		return json_encode($command);
	}

	public function getOSVersion()
	{
		$command = $this->executeCommand('uname -v');
		return json_encode($command);
	}

	// Obtiene el token y lo retorna si es válido, sino termina el script.
	private function getToken()
	{
		$curl = $this->execCurl(
			$this->apiURL . '/oauth', 
			true, 
			['Authorization: Basic '. base64_encode($this->credentials['username'] . ':' . $this->credentials['password'])],
			[
				'grant_type' => 'password',
				'username' => $this->credentials['username'],
				'password' => $this->credentials['password']
			]
		);

		$response = json_decode($curl['response']);

		if(isset($response->detail)) exit($response->detail);

		return $response;
	}

	// Envía un método POST al Endpoint. Retorna un volcado si hubo un error. 
	public function post($token)
	{
		$curl = $this->execCurl(
			$this->apiURL . '/reports', 
			true, 
			['Authorization: Bearer ' . $token->access_token], 
			[
				'ip_address' => $this->getIpAddress(),
				'logged_in_users' => $this->getLoggedInUsers(),
				'running_processes' => $this->getRunningProcesses(),
				'processor_info' => $this->getProcessorInfo(),
				'os_name' => $this->getOSName(),
				'os_version' => $this->getOSVersion(),
			]
		);

		if($curl['http_code'] != 201)
		{
			print_r(json_decode($curl['response']));
			return;
		}

		echo 'Successful!' . PHP_EOL;
		return;
	}

	// Envía un método GET al Endpoint. Retorna su respuesta
	public function get($token)
	{
		$curl = $this->execCurl(
			$this->apiURL . '/reports',
			false, 
			['Authorization: Bearer ' . $token->access_token]
		);

		if($curl['http_code'] == 200)
		{
			echo 'Successful!' . PHP_EOL;
		}

		return json_decode($curl['response']);
	}

	// Función genérica para ejecutar la extensión cURL
	// Recibe la URL de destino, si es un metodo POST o GET (POST = 0, GET = 1), las cabeceras y los campos POST
	// Si es exitoso retorna el código y respuesta. Si hubo un error termina el script.
	public function execCurl($endpointUrl = '', $isPost = false, $httpHeader = [], $postFields = [])
	{
        $ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $endpointUrl);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $httpHeader);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

		if($isPost){
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
		}

		$response = curl_exec($ch);
		$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

		curl_close($ch);

		if($response == false) exit('An error has occurred: ' . $endpointUrl . PHP_EOL);

		return [
			'http_code' => $http_code,
			'response' => $response
		];
	}

	// Main function. Ejecuta la función get() y post() dependiendo del modo de uso.
	public function init()
	{
		$token = $this->getToken();

		if($this->usageMode)
		{
			$this->get($token);
		}
		else{
			$this->post($token);
		}
		
		return;
	}

}
