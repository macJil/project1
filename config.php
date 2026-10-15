<?php


function check_role(){
  if($_SESSION['ROLE'] === 'user'){
    return 'hidden';
  }
}

function check_status($status){
  return $status ? "Outdated" : "Current";
}

function check_report_status($report){
  return $report === 'reviewed' && 'pending' ? "Review" : "Resolve";
}

?>