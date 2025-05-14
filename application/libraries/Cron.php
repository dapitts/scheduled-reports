<?php 
defined('BASEPATH') OR exit('No direct script access allowed');

require_once 'cron_expression/autoload.inc.php';
	
use Cron\CronExpression;

class Cron extends CronExpression
{
    public function __construct()
    {
        parent::__construct('* * * * *');
    }
}