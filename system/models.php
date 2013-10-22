	<?php
	
		class Tag extends Model{
			public function photos(){
				return $this->has_many('Photo');
			}
		}
		class Photo extends Model {
			public function tag(){
				return $this->belongs_to('Tag');
			}
		}
