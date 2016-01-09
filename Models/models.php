<?php
	class Model_Territory extends RedBean_SimpleModel {
		public function update() {

			if( empty($this->bean->title ) ) {
				throw new ValidationException( 'Territory title required!' );
			}
			if( !$this->bean->created_at || empty($this->bean->created_at) ) {
				$this->bean->created_at = R::isoDate();
				throw new ValidationException("No Created Date");
			}
			$this->bean->last_updated = R::isoDate();
		}

		public function dispense() {
			
		}
		public function open() {
			
		}
	}

	class Model_Building extends RedBean_SimpleModel {
		public function update() {
			
			if( empty($this->bean->address ) ) {
				throw new Exception( 'Building address required!' );
			}

			$this->bean->last_updated = R::isoDate();
			
		}
	}

	class Model_Person extends RedBean_SimpleModel {
		public function update() {
			
			if( empty($this->bean->number ) ) {
				throw new Exception( 'Phone number required!' );
			}
			
			$this->bean->last_updated = R::isoDate();
	

		}
	}