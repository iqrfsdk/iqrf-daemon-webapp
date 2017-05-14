<?php

/**
 * Copyright 2017 MICRORISC s.r.o.
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

namespace App\Model;

use Nette;
use Nette\Utils\ArrayHash;

class ConfigParser {

	use Nette\SmartObject;

	public function componentsToJson($components) {
		$array = [];
		foreach ($components as $component => $enabled) {
			array_push($array, ['ComponentName' => $component, 'Enabled' => $enabled]);
		}
		return $array;
	}

	public function instancesToJson(array $instances, ArrayHash $update, $id) {
		$instance = [];
		$instance['Name'] = $update['Name'];
		$instance['Enabled'] = $update['Enabled'];
		unset($update['Name']);
		unset($update['Enabled']);
		$instance['Properties'] = (array) $update;
		$instances[$id] = $instance;
		return $instances;
	}

	public function instancesToForm(array $json, $id = 0) {
		$data = $json['Instances'][$id];
		$instance = $data['Properties'];
		$instance['Name'] = $data['Name'];
		$instance['Enabled'] = $data['Enabled'];
		return $instance;
	}

}