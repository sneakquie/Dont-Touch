<?php

class Building
{
	private $_data = array();

	public function __construct($data)
	{
		$data = trim((string) $data);
		if(empty($data)) {
			return;
		}
		$data = $this->parseData($data);

		if(!is_array($data) || sizeof($data) < 1) {
			return;
		}
		$this->_data = $data;
	}

	private function parseData($data)
	{
		return json_decode(base64decode($data), true);
	}
}