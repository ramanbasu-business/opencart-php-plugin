<?php

class ControllerScocInstall extends Controller {

    public function index() {

        $scoc_lib = new scoc_lib($this->registry);
        $scoc_lib->createJobTable();

        echo "<h1>Marketplace sync plugin for OpenCart has been installed successfully</h1>";
    }

}
