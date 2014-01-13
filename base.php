#!/usr/bin/php
<?php
	// a tabulator
define('TAB', chr(9));
	// a linefeed
define('LF', chr(10));
	// a carriage return
define('CR', chr(13));
	// a CR-LF combination
define('CRLF', CR . LF);
define('HRLine', "\n=======================\n");
/*
	@TODO Destroy Setup if error ouccured. Delete folder and Version Stuff if done by builder.
*/
class WmdbBase {
	private $shortOptions = array('k:', 't::', 'h::', 'novcs::', 'b::');
	private $longOptions = array('extkey:' => 'k:', 'type::' => 't::', 'help::' => 'h::', 'baseurl::' => 'b::');
	private $options = array();
	private $returnHelp = false;
	private $conf = array();
	private $validParams = array();


	/**
	 * Main function
	 * @return bool
	 */
	public function main() {
		try {
			$this->parseArgumentsAndSetOptions();
			$this->getInitConf();
			if($this->returnHelp === true) {
				$this->helpTxt();
				return true;
			}
			if(is_dir($this->options['k'])) {
				throw new Exception('The Extension ' . $this->options['k'] . ' does already exist!');
			}
			if($this->options['novcs'] !== '1') {
				$this->testVcs($this->options);
			}
			$this->doExportFromBaseRepos($this->options);
			$this->doRenamingStuff();
			
			if($this->options['novcs'] !== '1') {
				$this->importToVersionControle($this->options);
			}
		} catch(Exception $e) {
			echo $e->getMessage() . LF;
			echo 'Stopping built process...'. LF;
			sleep(1);
		}
		return true;
	}

	/**
	 * echo Helptext if -h or --help parameter was given.
	 */
	private function helpTxt() {
		echo 'Valid parameters:' . LF;
		echo ' -k, -extkey :' . TAB . TAB . '[required] You have to pass the extension key with this option (-k wmdb_base_awesome)' . LF;
		echo ' -h, -help :' . TAB . TAB . 'Will show informations' . LF;
		echo ' -novcs:' . TAB . TAB . 'Disable the VCS import' . LF;
		echo ' -b, -baseurl:' . TAB . TAB . 'Sets the base URL to the given url. Please do not add any protocol to your url! (Example: www.wmdb.de, NOT http://www.wmdb.de/)' . LF;
		echo LF. 'The builder will automatically import the new Extension to the repository "' . $this->conf['importRepos'] . '".' . LF;
		echo 'Up to now your have not the possibility to choose the import repo by yourself.' . LF;
		echo "
                               ____                  
                            _.' :  `._               
                        .-.'`.  ;   .'`.-.           
               __      / : ___\\ ;  /___ ; \\      __
             ,'_ \"\"--.:__;\".-.\";: :\".-.\":__;.--\"\" _`,
             :' `.t\"\"--.. '<@.`;_  ',@>` ..--\"\"j.' `;
                  `:-.._J '-.-'L__ `-- ' L_..-;'     
                    \"-.__ ;  .-\"  \"-.  : __.-\"       
                        L ' /.------.\\ ' J
                         \"-.   \"--\"   .-\"            
                        __.l\"-:_JL_;-\";.__
                    .-j/'.;  ;\"\"\"\"  / .'\\\"-.
                   .' /:`. \"-.:     .-\" .';  `.      
                .-\"  / ; Use the source Luke! :    \"-.
             .+\"-.  : :      \"-.__.-\"      ;-._   \\
           -----------------------------------------------";
	}

	/**
	 * Try to parse the config.ini.
	 * @throws Exception
	 */
	private function getInitConf() {
		$conf = dirname($_SERVER['SCRIPT_FILENAME']) . '/config.ini';
		if(!file_exists($conf)) {
			throw new Exception('Could not find config.ini in base.php folder!');
		}
		$tempConf = parse_ini_file($conf, true);
		$defaults = $tempConf['default'];
		if( !isset($tempConf[$defaults['exportType']]) || !isset($tempConf[$defaults['importType']])) {
			throw new Exception(
				'No configuration found for handle type: ' . LF . var_dump($defaults) . '.' . LF .
				'Please check your configuration in your config.ini file.'
			);
		}
		$this->conf['importType'] = $defaults['importType'];
		$this->conf['importRepos'] = $tempConf[$defaults['importType']]['importRepos'];
		$this->conf['exportType'] = $defaults['exportType'];
		$this->conf['exportRepos'] = $tempConf[$defaults['exportType']]['exportRepos'];
		$this->checkConf();
	}

	/**
	 * Check if given configuration has the needed parameter, if not throw exception
	 * @throws Exception
	 */
	private function checkConf() {
		if(
			($this->conf['exportType'] == 'svn' || $this->conf['exportType'] == 'git') &&
			(!isset($this->conf['exportRepos']) || trim($this->conf['exportRepos']) == '')
		) {
			throw new Exception(
				'Configuration file was found but export configurations are missing or empty.' . LF .
				'Please check your configuration in your config.ini file and set the needed parameter for ' . $this->conf['exportType'] . '.'
			);
		}

		if(
			($this->conf['importType'] == 'svn' || $this->conf['importType'] == 'git') &&
			(!isset($this->conf['importRepos']) || trim($this->conf['importRepos']) == '')
		) {
			throw new Exception(
				'Configuration file was found but import configurations are missing or empty.' . LF .
				'Please check your configuration in your config.ini file and set the needed parameter for ' . $this->conf['importType'] . '.'
			);
		}
		if($this->url_exists($this->conf['exportRepos']) === false) {
			throw new Exception(
				'Export URL does not exsists. Please check the url: "' . $this->conf['exportRepos'] . '"!'
			);
		}
		if($this->url_exists($this->conf['importRepos']) === false) {
			throw new Exception(
				'Import URL does not exsists. Please check the url: "' . $this->conf['importRepos'] . '"!'
			);
		}
	}

	function url_exists($url) {
		$exists = true;
		$file_headers = @get_headers($url);
		$InvalidHeaders = array('404', '403', '500');
		if($file_headers === false) {
			return false;
		}
		foreach($InvalidHeaders as $HeaderVal) {
			if(strstr($file_headers[0], $HeaderVal)) {
				$exists = false;
				break;
			}
		}
		return $exists;
	}

	/**
	 * Parse given arguments and check if they are valid, if not throw exception.
	 * @throws Exception
	 */
	private function parseArgumentsAndSetOptions() {
		$args = $GLOBALS['argv'];
		$options = array();
		array_shift($args);
		$key = '';
		if(count($args) <= 0) {
			throw new Exception('No parameters given!' . LF . 'type --help to get an List of valid parameters.' . LF);
		}
		foreach($args AS $cnt => $v) {
			$pKey = '';
			if(strpos($v,'--') === 0 || strpos($v,'-') === 0) {
				if(isset($options[$key])) {
						$options[$key] = implode(' ', $options[$key]);
				}
				$key = str_replace(array('--','-'),array('',''),$v);
				if(array_search($key . ':', $this->shortOptions) === false && isset($this->longOptions[$key . ':']) === false
					&& isset($this->longOptions[$key . '::']) === false && array_search($key . '::', $this->shortOptions) === false
				)  {
					throw new Exception('Wrong parameter was passed: ' . $v . LF . 'type --help to get an List of valid parameters.' . LF);
				} else {
					if(isset($this->longOptions[$key . '::'])) {
						$pKey = $this->longOptions[$key . '::'];
					} elseif (isset($this->longOptions[$key . ':'])) {
						$pKey = $this->longOptions[$key . ':'];
					} elseif (array_search($key . '::', $this->shortOptions) !== false) {
						$pKey = $key . '::';
					} elseif (array_search($key . ':', $this->shortOptions) !== false) {
						$pKey = $key . ':';
					}
					$key = str_replace(array('::',':'),array('',''),$pKey);
					$this->validParams[$pKey] = 1;
				}
				if(
					(strpos($args[$cnt + 1 ],'--') === 0 || strpos($args[$cnt + 1 ],'-') === 0 || !isset($args[$cnt + 1 ]))  && $pKey != 'k:'
				) {
					$options[$key][] = true;
				}
				if( (strpos($args[$cnt + 1 ],'--') === 0 || strpos($args[$cnt + 1 ],'-') === 0 || !isset($args[$cnt + 1 ]))  && $pKey == 'k:' ) {
					unset( $this->validParams[$pKey]    );
				}
			} else {
				$options[$key][] = $v;
			}
		}
		if(isset($options[$key])) {
			$options[$key] = implode(' ', $options[$key]);
		}
		$this->options = $options;
		$this->checkParameters();
	}

	/**
	 * Check if required parameter are set, if not throw exception.
	 * @throws Exception
	 */
	private function checkParameters() {
		if(isset($this->validParams['h::'])) {
			$this->returnHelp = true;
			return;
		}
		foreach($this->shortOptions AS $param) {
			$notRequired = false;
			if(strpos($param, '::') !== false) {
				$notRequired = true;
			}
			if(isset($this->validParams[$param]) === false && $notRequired === false) {
				throw new Exception('Required parameter not found: ' . str_replace(array('::',':'),array('',''),$param) . LF . 'type --help to get an List of valid and required parameters.' . LF );
			}
		}
	}

	/**
	 * Renaming of all needed stuff in our base default extension
	 * Following will be renamed:
	 *      wmdb_base_default
	 *      WmdbBaseDefault
	 *      wmdbbasedefault
	 *      Wmdb Base: Default
	 *      wmdb_default
	 *      Wmdb Base Default
	 */
	private function doRenamingStuff() {
		echo 'Initial import...' . LF;
		sleep(1);
		$explodeKey = explode('_', $this->options['k']);
		array_walk($explodeKey, function(&$item) {
			$item = ucfirst($item);
		});
		$list = array(
			'wmdb_base_default' => $this->options['k'],
			'WmdbBaseDefault' => implode('', $explodeKey),
			'wmdbbasedefault' => strtolower(implode('', $explodeKey)),
			'Wmdb Base: Default' => implode(' ', $explodeKey),
			'Wmdb Base CE:' => implode(' ', $explodeKey) . ' CE:',
			'wmdb_default' => $this->options['k'],
			'Wmdb Base Default' => implode(' ', $explodeKey)
		);
		if(isset($this->options['b']) && trim($this->options['b']) != '') {
			$list['baseUrl'] = trim($this->options['b']);
		}
		exec('grep -lrE "' . implode('|', array_keys($list)) . '" ' . $this->options['k'] . '/*', $out, $error);
		foreach($out AS $file) {
			// read the file
			$fileContent = file_get_contents($file);
			// replace the data
			$fileContent = str_replace(array_keys($list), array_values($list), $fileContent);
			// write the file
			file_put_contents($file, $fileContent);
			echo 'Content replaced in: ' . $file . LF;
			if(strpos($file, '_wmdbbasedefault_') !== false) {
				$newName = str_replace('wmdbbasedefault', $list['wmdbbasedefault'], $file);
				exec('mv ' . $file . ' ' . $newName);
				echo 'File renamed: ' . $file . LF . 'To: ' . $newName . LF . LF;
			}
		}
		echo HRLine;
	}

	/**
	 * Will export the Base Default extension
	 *
	 * @TODO Export for GIT and other VCS
	 * @param $options
	 * @throws Exception
	 */
	private function doExportFromBaseRepos($options) {
		$error = false;
		$output = '';
		if(trim($options['k']) == '') {
			throw new Exception("Please set an extension key!");
		}
		if(!isset($this->conf['exportPath']) && $options['t'] == 'local') {
			throw new Exception("No path to storage folder set, but type 'local' found.");
		}
		switch($this->conf['exportType']) {
			case 'svn':
				exec('svn export ' . $this->conf['exportRepos'] . ' ./' . $options['k'], $output, $error);
			break;
			case 'git':
				#exec('svn export ' . $this->conf['exportRepos'] . ' ./' . $options['k'], $output, $error);
			break;
			case 'local':
				exec('cp -R ' . $this->conf['exportPath'] . ' ./' . $options['k'], $output, $error);
			break;
		}
		
		if($error) {
			throw new Exception(print_r($output, true));
		}
	}

	private function testVcs() {
		echo 'Check if Extension repo already existing...' . LF;
		sleep(1);
		switch($this->conf['exportType']) {
			case 'svn':
				$this->testSvnRepo($this->options);
				break;
			case 'git':
				$this->testGitRepo($this->options);
				break;
			case 'local':

				break;
		}
	}

	private function testGitRepo($options) {
		$e = 1;
		$out = 0;
		if($e == 0 && is_array($out) && count($out) > 0 ) {
			throw new Exception('The extension "' . $options['k'] . '" aleready exists in GIT repos "' .  $this->conf['importRepos'] . $options['k'] . '.git"');
		}
		echo HRLine;
	}

	/**
	 * Check if given ExtKey already exists.
	 *
	 * @param $options
	 * @throws Exception is thrown if config is wrong or Extension already exsits
	 */
	private function testSvnRepo($options) {
		exec('svn ls ' . $this->conf['importRepos'] . $options['k'] . '/trunk/', $out, $e);
		if($e == 0 && is_array($out) && count($out) > 0 ) {
			throw new Exception('The extension "' . $options['k'] . '" aleready exists in SVN repos "' .  $this->conf['importRepos'] . $options['k'] . '/trunk/"');
		}
		echo HRLine;
	}

	/**
	 * Import to version control, based on config.ini and given options (ExtKey)
	 *
	 * @todo Import to other version controls like git
	 * @param array $options
	 */
	private function importToVersionControle($options) {
		switch($this->conf['exportType']) {
			case 'svn':

				break;
			case 'git':

				break;
			case 'local':

				break;
		}

		echo 'Initial import...' . LF;
		sleep(1);
		passthru('svn import -m "Initial import of ' . $options['k'] . '" ./' . $options['k'] . '/ ' . $this->conf['importRepos'] . $options['k'] . '/trunk/', $e);
		echo HRLine;
		echo 'Backup Extension...';
		sleep(1);
		passthru('mv ' . $options['k'] . ' ' . $options['k'] . '_');
		echo HRLine;
		echo 'Checkout working copy...' . LF;
		sleep(1);
		passthru('svn checkout ' . $this->conf['importRepos'] . $options['k'] . '/trunk/ ' . $options['k'], $e);
		echo HRLine;
		echo 'Deleting backup folder...' . LF;
		sleep(1);
		passthru('rm -rf ' . $options['k'] . '_');
	}
	
}
passthru('clear');
print_r("\n" . 'Process Start' . HRLine);

$obj = new WmdbBase();
$obj->main();

print_r(HRLine . 'Process end' . LF .LF);
?>