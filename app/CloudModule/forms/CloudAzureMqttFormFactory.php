<?php

/**
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
declare(strict_types = 1);

namespace App\CloudModule\Forms;

use App\CloudModule\Model\AzureManager;
use App\CloudModule\Presenters\AzurePresenter;
use App\CloudModule\Model\InvalidConnectionString;
use App\ConfigModule\Model\BaseServiceManager;
use App\ConfigModule\Model\InstanceManager;
use App\Forms\FormFactory;
use App\ServiceModule\Model\ServiceManager;
use Nette;
use Nette\Application\UI\Form;
use Nette\Forms\Controls\SubmitButton;
use Nette\IOException;
use Nette\Utils\ArrayHash;

/**
 * Form for creating MQTT instance and Base service from Microsoft Azure IoT Hub Connection String for Device
 */
class CloudAzureMqttFormFactory {

	use Nette\SmartObject;

	/**
	 * @var AzureManager Microsoft Azure IoT Hub manager
	 */
	private $cloudManager;

	/**
	 * @var BaseServiceManager Base service manager
	 */
	private $baseService;

	/**
	 * @var InstanceManager MQTT instance manager
	 */
	private $manager;

	/**
	 * @var FormFactory Generic form factory
	 */
	private $factory;

	/**
	 * @var ServiceManager Service manager
	 */
	private $serviceManager;

	/**
	 * Constructor
	 * @param AzureManager $azure Microsoft Azure IoT Hub manager
	 * @param BaseServiceManager $baseService Base service manager
	 * @param InstanceManager $manager MQTT instance manager
	 * @param FormFactory $factory Generic form factory
	 * @param ServiceManager $serviceManager Service manager
	 */
	public function __construct(AzureManager $azure, BaseServiceManager $baseService, InstanceManager $manager, FormFactory $factory, ServiceManager $serviceManager) {
		$this->cloudManager = $azure;
		$this->baseService = $baseService;
		$this->manager = $manager;
		$this->factory = $factory;
		$this->serviceManager = $serviceManager;
	}

	/**
	 * Create MQTT configuration form
	 * @param AzurePresenter $presenter MS Azure presenter
	 * @return Form MQTT configuration form
	 */
	public function create(AzurePresenter $presenter): Form {
		$form = $this->factory->create();
		$form->setTranslator($form->getTranslator()->domain('cloud.msAzure.form'));
		$fileName = 'MqttMessaging';
		$this->manager->setFileName($fileName);
		$form->addText('ConnectionString', 'connectionString')->setRequired();
		$form->addSubmit('save', 'save')
				->onClick[] = function (SubmitButton $button) use ($presenter) {
			$values = $button->getForm()->getValues();
			$this->save($values, $presenter);
		};
		$form->addSubmit('save_restart', 'save_restart')
				->onClick[] = function (SubmitButton $button) use ($presenter) {
			$values = $button->getForm()->getValues();
			$this->save($values, $presenter, true);
		};
		$form->addProtection('core.errors.form-timeout');
		return $form;
	}

	/**
	 * Create the base service and MQTT interface
	 * @param ArrayHash $values Values from the form
	 * @param AzurePresenter $presenter MS Azure presenter
	 * @param bool $needRestart Is restart needed?
	 */
	public function save(ArrayHash $values, AzurePresenter $presenter, bool $needRestart = false) {
		try {
			$mqttInterface = $this->cloudManager->createMqttInterface($values['ConnectionString']);
			$baseService = $this->cloudManager->createBaseService();
			$this->baseService->add($baseService);
			$this->manager->add($mqttInterface);
		} catch (\Exception $e) {
			if ($e instanceof InvalidConnectionString) {
				$presenter->flashMessage('Invalid MS Azure IoT Hub connection string for device.', 'danger');
			} else if ($e instanceof IOException) {
				$presenter->flashMessage('IQRF Daemon\'s configuration files not found.', 'danger');
			} else {
				throw $e;
			}
		}
		if ($needRestart) {
			try {
				$this->serviceManager->restart();
				$presenter->flashMessage('service.actions.restart.message', 'info');
			} catch (NotSupportedInitSystemException $e) {
				$presenter->flashMessage('service.errors.unsupportedInit', 'danger');
			}
		}
		$presenter->redirect(':Config:Mqtt:default');
	}

}
