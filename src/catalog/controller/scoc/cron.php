<?php

class ControllerScocCron extends Controller {
    
    private $scoc_lib;
    private $scoc_encoder;
    
    
    public function index() {
	
        $this->scoc_lib=new scoc_lib($this->registry);
        $this->scoc_encoder = new scoc_encoder($this->registry);
        
        $qryStrArray = $this->scoc_encoder->authenticate( TRUE, FALSE );
        if ($qryStrArray["status"] == "error") {
            $this->scoc_lib->printLoginResultXml( $qryStrArray );
            return;
        }
        
	$id = 0; // default value for $id
	$verbose = 1; // default mode

	if (isset($_GET['id']) && $_GET['id'] != "") {
	    //cron.php?id=XXX
	    $id = (int) $_GET["id"];
	}
	//var_dump($id); return false;
	
	$globalHelper = $this->scoc_lib;

	//$this->log->write('SCOC :  ' . "job# " . $id . " not found");
	if ($id != 0) {
	    $job_row = $globalHelper->getJob($id);
	} else {
	    $job_row = $globalHelper->getOneSubmittedJob(NULL);
	}

	//var_dump($job_row);
	if ($job_row && count($job_row)) {
	    $id = $job_row["id"];
	    $module = $job_row["module"];

	    if ($id > 0) {

		echo "<h2>cron job $id started ........</h2>";

		if ($module == '' || $module == 'invprice') {
		    $output = $globalHelper->importProducts($id, $verbose);
		} else if ($module == 'shipping') {
		    $output = $globalHelper->importProducts($id, $verbose);
		}

		if ($verbose)
		    echo $output;
		echo "<h2>cron job finished............</h2>";
	    }
	}else {
	    echo "<h2>No submitted job is found</h2>";
	}
	exit;
    }

}