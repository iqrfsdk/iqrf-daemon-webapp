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

namespace App\IqrfAppModule\Model;

/**
 * The exception that indicates empty JSON DPA or DPA response
 */
class EmptyResponseException extends \Exception {

}

/**
 * The exception that indicates invalid gateway operational mode
 */
class InvalidOperationModeException extends \Exception {

}

/**
 * The exception that indicates invalid RF channel type
 */
class InvalidRfChannelTypeException extends \Exception {

}

/**
 * The exception that indicates invalid RF LP timeout
 */
class InvalidRfLpTimeoutException extends \Exception {

}

/**
 * The exception that indicates invalid RF output power
 */
class InvalidRfOutputPowerException extends \Exception {

}

/**
 * The exception that indicates invalid RF signal filter
 */
class InvalidRfSignalFilterException extends \Exception {

}

/**
 * The exception that indicates unsupported input format
 */
class UnsupportedInputFormatException extends \Exception {

}

/**
 * The exception that indicates unsupported security type
 */
class UnsupportedSecurityTypeException extends \Exception {

}
