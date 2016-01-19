<?php
	class Model_Territory extends RedBean_SimpleModel {
		public function update() {

			if( empty($this->bean->title ) ) {
				throw new ValidationException( 'Territory title required!' );
			}
			if( !$this->bean->created_at || empty($this->bean->created_at) ) {
				$this->bean->created_at = R::isoDate();
				// throw new ValidationException("Territory requires create Date");
			}
			$this->bean->last_updated = R::isoDate();
		}

		public function dispense() {
			$this->bean->published = true;
		}
		public function open() {
			
		}
	}

	class Model_Building extends RedBean_SimpleModel {
		public function update() {
			
			if( empty($this->bean->address ) ) {
				throw new ValidationException( 'Building address required!' );
			}
			if( empty($this->bean->territory_id) || $this->bean->territory_id == 0 ){
				throw new ValidationException( $this->bean->address . " (building) must be added to a valid territory.");
			}
			if( !$this->bean->created_at || empty($this->bean->created_at) ) {
				$this->bean->created_at = R::isoDate();
			}
			$this->bean->last_updated = R::isoDate();
			
		}
	}

	class Model_Person extends RedBean_SimpleModel {
		public function update() {
			
			if( empty($this->bean->number ) ) {
				throw new ValidationException( 'Phone number required!' );
			}
			if( empty($this->bean->building_id) || $this->bean->building_id == 0 ){
				throw new ValidationException( $this->bean->name . " (person) must be added to a valid building.");
			}
			if( !$this->bean->created_at || empty($this->bean->created_at) ) {
				$this->bean->created_at = R::isoDate();
			}			
			$this->bean->last_updated = R::isoDate();
	

		}
	}

	class Model_Checkout extends RedBean_SimpleModel {
		public function update() {
			
			if( empty($this->bean->name ) || empty( $this->bean->email ) ) {
				throw new ValidationException( 'Name and email required' );
			}
			if( !$this->bean->created_at || empty($this->bean->created_at) ) {
				$this->bean->created_at = R::isoDate();
			}
			$this->bean->last_updated = R::isoDate();
	

		}
	}