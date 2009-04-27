<?php

require_once (t3lib_extMgm::extPath('caretaker').'/interfaces/interface.tx_caretaker_LoggerInterface.php');
require_once (t3lib_extMgm::extPath('caretaker').'/interfaces/interface.tx_caretaker_NotifierInterface.php');
require_once (t3lib_extMgm::extPath('caretaker').'/classes/results/class.tx_caretaker_NodeResultRange.php');
require_once (t3lib_extMgm::extPath('caretaker').'/classes/class.tx_caretaker_Helper.php');

abstract class tx_caretaker_Node {
	
	protected $uid       = false;
	protected $title     = false;
	protected $type      = '';
	protected $parent    = NULL;
	protected $logger    = false;
	protected $notifier  = false;
	protected $notificationIds = array();
	protected $description = '';
	protected $hidden    = 0;
	
	public function __construct( $uid, $title, $parent, $type='', $hidden = false ){
		$this->uid    = $uid;
		$this->title  = $title;
		$this->parent = $parent;
		$this->type   = $type;
		$this->hidden = (boolean)$hidden;
	}
	
	public function setNotificationIds($id_array){
		$this->notificationIds = $id_array;
	}
	
	public function setDescription($decription){
		$this->description = $decription;
	}
	
	public function getUid(){
		return $this->uid;
	}
	
	public function getHidden(){
		return $this->hidden;
	}
		
	public function getTitle(){
		return $this->title;
	}
	
	public function getDescription(){
		return $this->description;
	}
	
	public function getType(){
		return $this->type;
	}	
	
	public function getInstance(){
		
		if ( is_a($this, 'tx_caretaker_Instance') ){
			return $this;
		} else if ($this->parent){
			return $this->parent->getInstance();
		} else {
			return false;
		}
	}
	
	/*
	 * Update Node Result and store in DB. 
	 * 
	 * @param boolean Force update of children
	 * @return tx_caretaker_NodeResult
	 */
	
	abstract public function updateTestResult($force_update = false);
	
	/*
	 * Read aggregator node state from DB
	 * @return tx_caretaker_NodeResult
	 */
	
	abstract public function getTestResult();
	
	abstract public function getTestResultRange($startdate, $stopdate, $distance = FALSE);
	
	
	/*
	 * Logging Methods
	 */
	
	public function setLogger (tx_caretaker_LoggerInterface $logger){
		$this->logger = $logger;
	}
	

	
	public function log($msg, $add_info=true){
		if ($add_info){
				$msg = ' +- '.$this->type.' '.$this->title.'['.$this->uid.'] '.$msg;
		}
		if ($this->logger){
			$this->logger->log($msg);
		} else if ($this->parent) {
			$this->parent->log(' | '.$msg , false);
		}
	}
	
	/*
	 * Notification Methods
	 */
	
	public function setNotifier (tx_caretaker_NotifierInterface $notifier){
		$this->notifier = $notifier;
	}
	
	public function sendNotification( $state, $msg){
		if ( count($this->notificationIds) > 0 ){ 
			foreach($this->notificationIds as $notfificationId){
				$this->notify( $notfificationId, $state, $this->type.' '.$this->title.'['.$this->uid.'] '.$msg, $this->description, tx_caretaker_Helper::node2id($this) );
			}
		}
	}
	
	private function notify( $recipients, $state, $msg = '' , $description = '' ,$node_id ){
		if ($this->notifier){
			$this->notifier->addNotification($recipients, $state, $msg, $description, $node_id);
		} else if ($this->parent) {
			$this->parent->notify($recipients, $state, $msg, $description, $node_id);
		}
	}
	
	public function getValueDescription(){
		return '';
	}
}
?>