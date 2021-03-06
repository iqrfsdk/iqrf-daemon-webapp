<?php

/**
 * Copyright 2017 MICRORISC s.r.o.
 * Copyright 2017-2018 IQRF Tech s.r.o.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */
declare(strict_types=1);

namespace App\ConfigModule\Presenters;

use App\ConfigModule\Model\InstanceManager;
use App\ConfigModule\Forms\ConfigMqttFormFactory;
use App\Presenters\BasePresenter;

class MqttPresenter extends BasePresenter {

	/**
	 * @var ConfigMqttFormFactory MQTT interface configuration form factory
	 * @inject
	 */
	public $formFactory;

	/**
	 * @var InstanceManager Interface instance manager
	 */
	private $configManager;

	/**
	 * @var string File name (without .json)
	 */
	private $fileName = 'MqttMessaging';

	/**
	 * Constructor
	 * @param InstanceManager $configManager Interface instance manager
	 */
	public function __construct(InstanceManager $configManager) {
		$this->configManager = $configManager;
		$this->configManager->setFileName($this->fileName);
	}

	/**
	 * Render list of MQTT interfaces
	 */
	public function renderDefault() {
		$this->onlyForAdmins();
		$this->template->instances = $this->configManager->getInstances();
	}

	/**
	 * Edit MQTT interface
	 * @param int $id ID of MQTT interface
	 */
	public function renderEdit(int $id) {
		$this->onlyForAdmins();
		$this->template->id = $id;
	}

	/**
	 * Delete MQTT interface
	 * @param int $id ID of MQTT interface
	 */
	public function actionDelete(int $id) {
		$this->onlyForAdmins();
		$this->configManager->delete($id);
		$this->redirect('Mqtt:default');
		$this->setView('default');
	}

	/**
	 * Create MQTT interface form
	 * @return Form MQTT interface form
	 */
	protected function createComponentConfigMqttForm() {
		$this->onlyForAdmins();
		return $this->formFactory->create($this);
	}

}
