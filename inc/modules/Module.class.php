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



require_once(dirname(__FILE__) . '/ModulesManager.class.php');
ModulesManager::file("/inc/modules/modules.const");
ModulesManager::file("/inc/db/db.inc");
ModulesManager::file("/inc/cli/Shell.class.php");
ModulesManager::file("/inc/helper/Messages.class.php");

/**
 *
 */
class Module {

	var $name;
	var $path;
	var $actions;
	var $sql_constructor;
	var $sql_constructor_file;
	var $sql_destructor;
	var $sql_destructor_file;
	var $messages;

	/**
	 *  @public
	 */
	function Module($name, $path) {

		if ( empty($name) || empty($path) ) {
			die("* ERROR: name and path in Module constructor must be provided.\n");
		}

		$this->messages = new Messages();

		//$this->name = get_class($this);
		$this->name = $name;
		$this->path = $path;

		$this->sql_constructor = array();
		$this->sql_destructor = array();

      //  $this->messages->add(sprintf(_("sys {%s} : Module instanciated (%s) (%s)"),
      //   __CLASS__, $this->name, $this->path), MSG_TYPE_NOTICE);
		XMD_Log::info(sprintf(_("sys {%s} : Module instanciated (%s) (%s)"), __CLASS__, $this->name, $this->path));

	}

    /**
     *  @public
     */
	function getModulePath() {
		return $this->path;
	}

    /**
     *  @public
     */
	function getModuleName() {
		return $this->name;
		//return trim($this->name, MODULE_PREFIX);
	}

    /**
     *  @public
     */
	function getModuleClassName() {
		return MODULE_PREFIX . $this->name;
		//return get_class($this);
	}

    /**
     *  @private
     *  @param $sql_file Filename (without path information) which contain SQL.
     *  @return NULL or SQL Data Array.
     */
	function loadSQL($sql_file) {

        	$sql_path = $this->getModulePath() . "/sql/";
	        $sql_file = $sql_path . $sql_file;

        	if (file_exists($sql_file)) {
            		$sql_data = file($sql_file);
            		if (is_array($sql_data)) {
                		foreach ($sql_data as $idx => $sql) {
                    			$sql_data[] = rtrim($sql);
                		}
			}
			else {
		        	$this->messages->add(sprintf(_("** ERROR: %s is empty"), $sql_file), MSG_TYPE_ERROR);
                		return null;
            		}
        	}
		else {
	        	$this->messages->add(sprintf(_("** ERROR: %s doesn't exist"), $sql_file), MSG_TYPE_ERROR);
            		return null;
        	}
		return $sql_data;
	}

	/**
	 * @protected
	 */
	function loadConstructorSQL($sql_file) {

		$this->sql_constructor_file = $sql_file;
		$data = $this->loadSQL($sql_file);

		if (!empty($data)) {
			$this->sql_constructor = $data;
			return true;
		} else {
			XMD_Log::error("Error loading module constructor $sql_file");
			return false;
		}
	}

	/**
	 * @protected
	 */
	function loadDestructorSQL($sql_file) {

		$this->sql_destructor_file = $sql_file;
		$data = $this->loadSQL($sql_file);

		if (!is_null($data)) {
			$this->sql_destructor = $data;
			return true;
		} else {
			return false;
		}
	}

	/**
	 * @private
     * NOT FUNCTIONAL YET
	 */
	function injectSQL($sql_sentences) {
		//$db = new DB();
		// Use Ximdex DB class?
		if (is_array($sql_sentences)) {
		    	foreach ($sql_sentences as $idx => $sql) {
				$this->messages->add (sprintf(_("[%s]: Executing (%s)"), $idx, $sql), MSG_TYPE_NOTICE);
				$db->Execute($sql);
				$id = $db->newID;
			}
		}
		else {
		    return false;
		}
	}


	function sqlFileExists($sql_file) {
		$sql_path = $this->getModulePath() . '/sql/';
		$sql_file = $sql_path . $sql_file;
		return file_exists($sql_file);
	}



    /**
     * @private
     */
	function injectSQLFile($sql_file) {
	// Inject via mysql command...

		$sql_path = $this->getModulePath() . '/sql/';
		$sql_file = $sql_path . $sql_file;

		$install_params_file = MAIN_INSTALL_PARAMS;

		if (file_exists($sql_file)) {

			if (file_exists($install_params_file)) {
				include(MAIN_INSTALL_PARAMS);
			} else {
				$this->messages->add(sprintf(_("* FATAL ERROR: ximDEX is not fully configured. [%s not found]"), $install_params_file), MSG_TYPE_ERROR);
				return false;
			}

			// Mysql call construction...
			$command = "mysql --host=$DBHOST --port=$DBPORT --user=$DBUSER";

			if ( !empty($DBPASSWD) ) {
				$command .= " --password=$DBPASSWD";
			}

			$command .= " $DBNAME  < $sql_file";

			//$this->messages->add (sprintf(_("sys: Launching command [%s]"), $command), MSG_TYPE_NOTICE);
			// Verificar salida correcta y en caso contrario eliminar entradas.
			system($command);

		} else {
			$this->messages->add (sprintf(_("%s not exists"), $file_name), MSG_TYPE_WARNING);
			return false;
		}
	}



	/**
	 *  Install new module into ximDEX.
     *  @public
	 *
	 *  @return
	 */
	function install() {

		$ret = true;
		if (!$this->preInstall()) {
			echo "Se ha abortado la instalación por no cumplirse los prerequisitos\n";
			$ret = false;
		}
		else {
			// SQL Insertion
			if (!empty($this->sql_constructor)) {
				$this->injectSQLFile($this->sql_constructor_file);
				//$this->messages->add(_("-- SQL constructor loaded"), MSG_TYPE_NOTICE);
			   XMD_Log::info(_("-- SQL constructor loaded"));
			} else {
				$this->messages->add(_("* ERROR: SQL constructor not loaded"), MSG_TYPE_ERROR);
				$ret = false;
			}
			// Actions Registration
			// Actions Activation
			if (!$this->postInstall()) {
				echo "Ha fallado el proceso de post instalación, puede que el módulo no funcione correctamente\n";
				$ret = false;
			}
		}
		// Muestra los mensajes
		$this->messages->displayRaw();
		return $ret;
	}

	/**
	 *  Instructions previous to the installation
     *  @public
	 *
	 *  @return
	 */
	function preInstall() {
		return true;
	}

	function checkDependences($arrDependences) {
		if (!is_array($arrDependences)) {
			return NULL;
		}
		foreach ($arrDependences as $dependence) {
			$ret = Shell::exec("which " . $dependence, true);
			if (empty($ret[0])) {
				return $dependence;
			}
		}
		return null;
	}

	/**
	 *  Instructions subsequent to the installation
     *  @public
	 *
	 *  @return
	 */
	function postInstall() {
		return true;
	}

	/**
	 *  Uninstall module from ximDEX.
     *  @public
	 */
	function uninstall() {

        // SQL Remove
        if (!empty($this->sql_destructor)) {
            $this->injectSQLFile($this->sql_destructor_file);
			$this->messages->add(_("-- SQL destructor loaded"), MSG_TYPE_NOTICE);
        } else {
			$this->messages->add(_("* ERROR: SQL destructor not loaded"), MSG_TYPE_ERROR);
        }
        // Actions deRegistration
        // Actions deActivation

		//show messages
		$this->messages->displayRaw();
	}

 // States -

	/**
	 *  Enable module.
     *  @public
	 */
	function enable() {

	}


	/**
	 *  Disable module.
     *  @public
	 */
	function disable() {

	}

    /**
     *  @public
     */
    function state() {

        // SQL loaded?
		$db = new DB();

		$module_name = $this->getModuleName();

		$db->Query("SELECT DISTINCT Command FROM Actions WHERE Module = '$module_name'");

		if (!$db->numErr) {
			while (!$db->EOF) {
				$data = $db->GetValue("Command");
				$this->messages->add("data = $data", MSG_TYPE_NOTICE);
				$db->Next();
			}
		} else {
			$this->messages->add(sprintf(_("* ERROR [: SQL query %s"), $db->numErr), MSG_TYPE_ERROR);
			return MODULE_STATE_ERROR;
		}


        // Esta en el fichero de configuracion ? continue : ERROR

        // Estan las acciones en la tabla Actions ? continue : ERROR
        $this->getActions();

        //print_r($this->actions);

        return MODULE_STATE_UNINSTALLED;
    }

    /**
     * @protected
     */
    function checkState() {

    }

// Actions -

    /**
     *  @private
     */
	function loadActions() {

		$actions_dir = $this->getModulePath() . "/actions/";

		if (!is_dir($actions_dir)) {
			return false;
		}

		if ($dir_hnd = opendir($actions_dir)) {

			while(false !== ($item = readdir($dir_hnd))) {
				if (is_dir($actions_dir . $item) &&
				    $item != "." && $item != ".." && $item != ".svn") {
					$this->actions[] = $item;
				}
			}

			closedir($dir_hnd);
		}

		return true;
	}

    /**
     *  @protected
     */
	function getActions() {

		if ($this->actions === NULL) {
			$this->loadActions();
		}

		return $this->actions;
	}

	/**
	 * Future ! :]
	 */
	function registerAction() {

	}

}
?>
