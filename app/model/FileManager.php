<?php

/**
 * Copyright 2017 MICRORISC s.r.o.
 * Copyright 2017-2018 IQRF Tech s.r.o.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *      http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

declare(strict_types=1);

namespace App\Model;

use Nette;
use Nette\Utils\FileSystem;

/**
 * Tool for reading and writing text files.
 */
class FileManager {

	use Nette\SmartObject;

	/**
	 * @var string
	 */
	private $configDir;

	/**
	 * Constructor
	 * @param string $configDir Directory with files
	 */
	public function __construct(string $configDir) {
		$this->configDir = $configDir;
	}

	/**
	 * Read file
	 * @param string $fileName File name
	 * @return string File content
	 */
	public function read(string $fileName) {
		return FileSystem::read($this->configDir . '/' . $fileName);
	}

	/**
	 * Encode JSON from array and write JSON file
	 * @param string $fileName File name
	 * @param array $content File content
	 */
	public function write(string $fileName, $content) {
		$fileName = 'nette.safe://' . $this->configDir . '/' . $fileName;
		FileSystem::write($fileName, $content, null);
	}

}
