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

namespace App\GatewayModule\Presenters;

use App\GatewayModule\Model\LogManager;
use App\Presenters\BasePresenter;
use Nette\Application\BadRequestException;
use Nette\IOException;
use Tracy\Debugger;

/**
 * IQRF Daemon's log presenter
 */
class LogPresenter extends BasePresenter {

	/**
	 * @var LogManager
	 */
	private $manager;

	/**
	 * Constructor
	 * @param LogManager $manager
	 */
	public function __construct(LogManager $manager) {
		$this->manager = $manager;
	}

	/**
	 * Render default page
	 */
	public function renderDefault() {
		$this->onlyForAdmins();
		try {
			$this->template->log = $this->manager->load();
		} catch (IOException $e) {
			Debugger::log('Cannot read log file.');
		}
	}

	/**
	 * Download action
	 */
	public function actionDownload() {
		$this->onlyForAdmins();
		try {
			$this->sendResponse($this->manager->download());
		} catch (BadRequestException $e) {
			Debugger::log('Cannot read log file.');
			$this->redirect('Log:default');
			$this->setView('default');
		}
	}

}
