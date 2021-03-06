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
declare(strict_types=1);

namespace App\IqrfAppModule\Forms;

use App\Forms\FormFactory;
use App\IqrfAppModule\Model\IqrfNetManager;
use Nette;
use Nette\Application\UI\Form;
use Nette\Forms\Controls\SubmitButton;

/**
 * IQMESH Bonding form factory.
 */
class IqrfNetBondingFormFactory {

	use Nette\SmartObject;

	/**
	 * @var IqrfNetManager IQMESH Network manager
	 */
	private $manager;

	/**
	 * @var FormFactory Generic form factory
	 */
	private $factory;

	/**
	 * Constructor
	 * @param FormFactory $factory Generic form factory
	 * @param IqrfNetManager $manager IQMESH Network manager
	 */
	public function __construct(FormFactory $factory, IqrfNetManager $manager) {
		$this->factory = $factory;
		$this->manager = $manager;
	}

	/**
	 * Create IQMESH bonding form
	 * @return Form IQMESH bonding form
	 */
	public function create(): Form {
		$form = $this->factory->create();
		$form->setTranslator($form->getTranslator()->domain('iqrfapp.network-manager.bonding'));
		$form->addCheckbox('autoAddress', 'autoAddress');
		$form->addText('address', 'address')->setDefaultValue('01')
				->addConditionOn($form['autoAddress'], Form::EQUAL, false)
				->addRule(Form::PATTERN, 'messages.address', '[0-9a-fA-F]{1,2}')
				->setRequired('messages.address');
		$form->addSubmit('bond', 'bondNode')->onClick[] = [$this, 'bondNode'];
		$form->addSubmit('rebond', 'rebondNode')->onClick[] = [$this, 'rebondNode'];
		$form->addSubmit('remove', 'removeNode')->onClick[] = [$this, 'removeNode'];
		$form->addSubmit('clear', 'clearAllBonds')->onClick[] = [$this, 'clearAllBonds'];
		$form->addProtection('core.errors.form-timeout');
		return $form;
	}

	/**
	 * Bond new node
	 * @param SubmitButton $button Submit button for bonding
	 */
	public function bondNode(SubmitButton $button) {
		$values = $button->getForm()->getValues();
		$address = $values['autoAddress'] ? '00' : $values['address'];
		$this->manager->bondNode($address);
	}

	/**
	 * Clear all bonds
	 * @param SubmitButton $button Submit button for cleaning all bonds
	 */
	public function clearAllBonds(SubmitButton $button) {
		$this->manager->clearAllBonds();
	}

	/**
	 * Rebond node
	 * @param SubmitButton $button Submit button for rebonding
	 */
	public function rebondNode(SubmitButton $button) {
		$values = $button->getForm()->getValues();
		$this->manager->rebondNode($values['address']);
	}

	/**
	 * Remove node
	 * @param SubmitButton $button Submit button for removing node
	 */
	public function removeNode(SubmitButton $button) {
		$values = $button->getForm()->getValues();
		$this->manager->removeNode($values['address']);
	}

}
