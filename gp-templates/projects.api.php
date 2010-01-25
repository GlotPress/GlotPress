<?php
	echo json_encode( array_map( lambda( '$p', '$p->fields();' ), $projects ) );