<?php
require_once 'db.php';
class event extends db{
  public function get_total_locations(){
      $sql = "SELECT * FROM locations WHERE active = 1";
      $smtm = $this->connect()->query($sql);
      
      $count = $smtm->fetchAll(PDO::FETCH_ASSOC);
      $total = count($count);
      return $total;

  }
  public function get_pending_reports_total(){
    $sql = "SELECT * FROM events WHERE status = 'pending' ";
      $smtm = $this->connect()->query($sql);
      
      $count = $smtm->fetchAll(PDO::FETCH_ASSOC);
      $total = count($count);
      return $total;
  }
  public function get_high_risk(){
    $sql = "SELECT * FROM events WHERE risk_level = 'high' ";
      $smtm = $this->connect()->query($sql);
      
      $count = $smtm->fetchAll(PDO::FETCH_ASSOC);
      $total = count($count);
      return $total;
  }
  public function get_med_risk(){
    $sql = "SELECT * FROM events WHERE risk_level = 'medium' ";
      $smtm = $this->connect()->query($sql);
      
      $count = $smtm->fetchAll(PDO::FETCH_ASSOC);
      $total = count($count);
      return $total;
  }
  public function get_readings(){
    $sql = "SELECT * FROM events INNER JOIN locations ON events.location_id = locations.id";
    $readings = $this->connect()->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    return $readings;

  }
  public function get_dialog($id){
    $stmt = $this->connect()->prepare(
    "SELECT * FROM events INNER JOIN locations ON events.location_id = locations.id WHERE locations.id = ?");

    $stmt->execute([$id]);

    $reading = $stmt->fetch(PDO::FETCH_ASSOC);
    return $reading;
  }

  function risk_color($risk){
    if($risk === 'low') return "<td class='text text-success'>LOW</td>";
    if($risk === 'normal') return "<td class='text text-primsry'>NORMAL</td>";
    if($risk === 'medium') return "<td class='text text-warning'>MEDIUM</td>";
    if($risk === 'high') return "<td class='text text-danger'>HIGH</td>";
    if($risk === 'unknown') return "<td class='text text-inverse'>UNKNOWN</td>";
    if($risk === 'unavailable') return "<td class='text text-inverse'>UNAVAILABLE</td>";
  
}

function get_pending_reports(){
  $sql = "SELECT locations.name AS location_name, users.full_name AS reporter, users.phone AS contact, events.status AS report_status, users.created_at AS report_date FROM events INNER JOIN locations INNER JOIN users ON events.location_id = locations.id AND events.user_id = users.id WHERE status = 'pending' ";
  $reports = $this->connect()->query($sql)->fetchAll(PDO::FETCH_ASSOC);
  return $reports;
}


}



?>