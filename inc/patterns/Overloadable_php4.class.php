<?php
/**
 *  \details &copy; 2011  Open Ximdex Evolution SL [http://www.ximdex.org]
 *
 *  Ximdex a Semantic Content Management System (CMS)
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU Affero General Public License as published
 *  by the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU Affero General Public License for more details.
 *
 *  See the Affero GNU General Public License for more details.
 *  You should have received a copy of the Affero GNU General Public License
 *  version 3 along with Ximdex (see LICENSE file).
 *
 *  If not, visit http://gnu.org/licenses/agpl-3.0.html.
 *
 *  @author Ximdex DevTeam <dev@ximdex.com>
 *  @version $Revision$
 */



class Overloadable {

	function Overloadable() {
		$this->overload();
	}

	function overload() {
		if (function_exists('overload')) {
			if (func_num_args() > 0) {
				foreach (func_get_args() as $class) {
					if (is_object($class)) {
						overload(get_class($class));
					} elseif (is_string($class)) {
						overload($class);
					}
				}
			} else {
				overload(get_class($this));
			}
		}
	}

	function __call($method, $params, &$return) {
		if (!method_exists($this, 'call__')) {
			trigger_error(sprintf(__('Magic method handler call__ not defined in %s', true), get_class($this)), E_USER_ERROR);
		}
		$return = $this->call__($method, $params);
		return true;
	}

	function __get($name, &$value) {
		$value = $this->get__($name);
		return true;
	}

	function __set($name, $value) {
		$this->set__($name, $value);
		return true;
	}
}
Overloadable::overload('Overloadable');


?>