<?php

/**
 * Description of ControllerScocJob
 *
 * @author RAMAN
 */
class ControllerScocJob extends Controller
{
    private $scoc_encoder;
    private $scoc_lib;
    
    //put your code here
    public function getjob()
    {
        $this->scoc_encoder = new scoc_encoder($this->registry);
        $this->scoc_lib=new scoc_lib($this->registry);
        
        $this->scoc_lib->httpHeaders();

        $qryStrArray = $this->scoc_encoder->authenticate();
        if ($qryStrArray == null) {
            return;
        }

        $newId = !empty($qryStrArray['id']) ? intval($qryStrArray['id']) : 0;

        $job_row = $this->scoc_lib->getJob($newId);
    
        $this->scoc_lib->getJobXml2($job_row);
    }

    private function _get($key)
    {
        return $this->request->post($key);
    }

    public function localfilename()
    {
        $this->scoc_encoder = new scoc_encoder($this->registry);
        $this->scoc_lib=new scoc_lib($this->registry);
        
        $this->scoc_lib->httpHeaders();

        $qryStrArray = $this->scoc_encoder->authenticate();
        if ($qryStrArray == null) {
            return;
        }

        $jobId = !empty($qryStrArray['id']) ? intval($qryStrArray['id']) : 0;

        $this->response->setOutput($this->scoc_lib->getJobFileSource($jobId));
    }
    
    public function logfilename()
    {
        $this->scoc_encoder = new scoc_encoder($this->registry);
        $this->scoc_lib=new scoc_lib($this->registry);
        
        $this->scoc_lib->httpHeaders();

        $qryStrArray = $this->scoc_encoder->authenticate();
        if ($qryStrArray == null) {
            return;
        }

        $jobId = !empty($qryStrArray['id']) ? intval($qryStrArray['id']) : 0;

        $this->response->setOutput($this->scoc_lib->getJobLogFileSource($jobId));
    }
}
