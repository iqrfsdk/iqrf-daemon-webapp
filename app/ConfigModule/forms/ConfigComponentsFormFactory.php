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

namespace App\ConfigModule\Forms;

use App\ConfigModule\Model\ComponentManager;
use App\ConfigModule\Presenters\MainPresenter;
use App\Forms\FormFactory;
use Nette;
use Nette\Application\UI\Form;

class ConfigComponentsFormFactory {

	use Nette\SmartObject;

	/**
	 * @var ComponentManager
	 */
	private $manager;

	/**
	 * @var FormFactory Generic form factory
	 */
	private $factory;

	/**
	 * Constructor
	 * @param ComponentManager $manager
	 * @param FormFactory $factory Generic form factory
	 */
	public function __construct(ComponentManager $manager, FormFactory $factory) {
		$this->manager = $manager;
		$this->factory = $factory;
	}

	/**
	 * Create components configuration form
	 * @param MainPresenter $presenter
	 * @return Form Components configuration form
	 */
	public function create(MainPresenter $presenter): Form {
		$form = $this->factory->create();
		$form->setTranslator($form->getTranslator()->domain('config.components.form'));
		$components = $this->manager->load();
		foreach ($components as $component) {
			$form->addCheckbox($component['ComponentName'], $component['ComponentName'])
					->setDefaultValue($component['Enabled']);
		}
		$form->addSubmit('save', 'Save');
		$form->addProtection('core.errors.form-timeout');
		$form->onSuccess[] = function (Form $form, $values) use ($presenter) {
			$this->manager->save($values);
			$presenter->redirect('Homepage:default');
		};
		return $form;
	}

}
