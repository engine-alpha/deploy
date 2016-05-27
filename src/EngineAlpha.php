<?php

namespace App;

class EngineAlpha {
	private $event;
	private $data;

	public function handleEvent($event, $data) {
		$this->event = $event;
		$this->data = $data;

		switch ($event) {
			case 'push':
				$this->handlePush();
				break;
			case 'release':
				$this->handleRelease();
				break;
			default:
				die('Unknown event: "' . $event . '"');
				break;
		}
	}

	public function handlePush() {
		if ($this->data->ref !== 'refs/heads/master') {
			die('ref !== refs/heads/master: "' . $this->data->ref . '"');
		}

		$dir = sys_get_temp_dir() . '/engine-alpha';

		mkdir($dir, 0755, true);
		chdir($dir);

		$this->exec('git', 'clone', 'https://github.com/engine-alpha/engine-alpha');

		if (!file_exists('engine-alpha/res')) {
			mkdir('engine-alpha/res', 0755, true);
		}

		file_put_contents('engine-alpha/res/commit', $this->data->after);

		if (!file_exists(DEPLOY_ROOT . '/downloads/nightly')) {
			mkdir(DEPLOY_ROOT . '/downloads/nightly', 0755, true);
		}

		chdir('engine-alpha');
		$ant = $this->exec('ant', 'jar');
		$error = strpos($ant, 'BUILD SUCCESSFUL') === false;

		if (!$error) {
			$this->exec('cp', 'build/Engine.Alpha.jar', DEPLOY_ROOT . '/downloads/nightly/engine-alpha.jar');
		}

		rrmdir($dir);
	}

	public function handleRelease() {
		$tag = $this->data->release->tag_name;

		echo 'release: ' . $tag . "\n";

		if ($this->data->release->draft || $this->data->release->prerelease) {
			die('no final release, aborting');
		}

		if (!preg_match('@^v[0-9]+\.[0-9]+(\.[0-9]+)?$@', $tag)) {
			die('no valid tag name: "' . $tag . '"');
		}

		$dir = sys_get_temp_dir() . '/engine-alpha';

		mkdir($dir, 0755, true);
		chdir($dir);

		$this->exec('git', 'clone', 'https://github.com/engine-alpha/engine-alpha');

		chdir('engine-alpha');
		$this->exec('git', 'checkout', $tag);

		if (!file_exists('res')) {
			mkdir('res', 0755, true);
		}

		file_put_contents('res/version', $tag);

		if (!file_exists(DEPLOY_ROOT . '/downloads/' . $tag)) {
			mkdir(DEPLOY_ROOT . '/downloads/' . $tag, 0755, true);
		}

		if (!file_exists(DEPLOY_ROOT . '/downloads/latest')) {
			mkdir(DEPLOY_ROOT . '/downloads/latest', 0755, true);
		}

		$ant = $this->exec('ant', 'jar');
		$error = strpos($ant, 'BUILD SUCCESSFUL') === FALSE;

		if(!$error) {
			$this->exec('cp', 'build/Engine.Alpha.jar', DEPLOY_ROOT . '/downloads/' . $tag . '/engine-alpha.jar"');
			$this->exec('cp', 'build/Engine.Alpha.jar', DEPLOY_ROOT . '/downloads/latest/engine-alpha.jar"');
		}

		$ant = $this->exec('ant', 'docs');
		$error = strpos($ant, 'BUILD SUCCESSFUL') === FALSE;

		if (!$error) {
			$this->exec('cd', 'doc', '&&', 'zip',  '-r', DEPLOY_ROOT . '/downloads/' . $tag . '/engine-alpha-docs.zip', '*');
			$this->exec('cd', 'doc', '&&', 'zip',  '-r', DEPLOY_ROOT . '/downloads/latest/engine-alpha-docs.zip', '*');
		}

		rrmdir($dir);

		$dir = sys_get_temp_dir() . '/engine-alpha-marketing';

		mkdir($dir);
		chdir($dir);

		$this->exec('git', 'clone', 'https://github.com/engine-alpha/marketing', '.');

		$this->exec('cp', 'Engine Alpha.pdf', DEPLOY_ROOT . '/downloads/' . $tag . '/engine-alpha.pdf');
		$this->exec('cp', 'Engine Alpha.pdf', DEPLOY_ROOT . '/downloads/latest/engine-alpha.pdf');

		rrmdir($dir);

		$this->exec('cp', '-r', DEPLOY_ROOT . '/downloads/figureneditor', DEPLOY_ROOT . '/downloads/latest');
		$this->exec('cp', '-r', DEPLOY_ROOT . '/downloads/figureneditor', DEPLOY_ROOT . '/downloads/' . $tag);

		$this->exec('cd', DEPLOY_ROOT . '/downloads/' . $tag, '&&', 'zip', '-r', '../' . $tag . '.zip', '*');
		$this->exec('cd', DEPLOY_ROOT . '/downloads/latest', '&&', 'zip', '-r', '../latest.zip', '*');
	}

	private function exec(string ...$args) {
		return shell_exec(implode(" ", array_map("escapeshellarg", $args)));
	}
}
