<?php
namespace Typo3BaseBuilder\Base;

class Helper {

	const SEPLENGTH = 80;

	public static function printHelp() {
		$conf = Configuration::getConf();
		$importRepo = $conf[$conf['default']['importType']]['importRepos'];
		self::printLine(
			'Valid parameters:' . LF .
			' -k, -extkey :' . TAB . TAB . '[required] You have to pass the extension key with this option (-k wmdb_base_awesome)' . LF .
			' --h, --help :' . TAB . TAB . 'Will show informations' . LF .
			' --novcs:' . TAB . TAB . 'Disable the VCS import' . LF .
			' --b, --baseurl:' . TAB . TAB . 'Sets the base URL to the given url. Please do not add any protocol to your url! (Example: www.wmdb.de, NOT http://www.wmdb.de/)' . LF . LF .
			'The builder will automatically import the new Extension to the repository "' . $importRepo . '".' . LF .
			"
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
           -----------------------------------------------"
		);
		die();
	}

	public static function clearScr() {
		passthru('clear');
	}

	/**
	 * @param $url
	 * @return bool
	 */
	public static function urlExists($url) {
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
	 * @param $extKey
	 * @param string $baseUrl
	 */
	public static function replaceNecessaryStringsAndFiles($extKey, $baseUrl = ''){
		Helper::printLine(LF . 'Start renaming...');
		sleep(1);
		$explodeKey = explode('_', $extKey);
		array_walk($explodeKey, function(&$item) {
			$item = ucfirst($item);
		});
		$list = array(
			'wmdb_base_default' => $extKey,
			'WmdbBaseDefault' => implode('', $explodeKey),
			'wmdbbasedefault' => strtolower(implode('', $explodeKey)),
			'Wmdb Base: Default' => implode(' ', $explodeKey),
			'Wmdb Base CE:' => implode(' ', $explodeKey) . ' CE:',
			'wmdb_default' => $extKey,
			'Wmdb Base Default' => implode(' ', $explodeKey)
		);
		if(isset($baseUrl) && trim($baseUrl) != '') {
			$list['baseUrl'] = trim($baseUrl);
		}
		/** Get All needed files */
		exec('grep -lrE "' . implode('|', array_keys($list)) . '" ' . $extKey . '/*', $out, $error);
		foreach($out AS $file) {
			// read the file
			$fileContent = file_get_contents($file);
			// replace the data
			$fileContent = str_replace(array_keys($list), array_values($list), $fileContent);
			// write the file
			file_put_contents($file, $fileContent);
			Helper::printLine('Content replaced in: ' . $file);
			if(strpos($file, '_wmdbbasedefault_') !== false) {
				$newName = str_replace('wmdbbasedefault', $list['wmdbbasedefault'], $file);
				exec('mv ' . $file . ' ' . $newName);
				Helper::printLine('File renamed: ' . $file . LF . 'To: ' . $newName . LF);
			}
		}
		Helper::printSeperator(LINE);
	}

	/**
	 * @param string $msg
	 * @param string $seperator
	 */
	public static function printLine($msg, $seperator = ''){
		echo $msg . LF;
		self::printSeperator($seperator);
	}

	public static function printSeperator($seperator = ''){
		if($seperator != '') {
			for($i = 1; $i <= self::SEPLENGTH; $i++) {
				echo $seperator;
			}
			echo LF;
		}
	}
}