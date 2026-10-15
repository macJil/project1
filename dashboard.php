<?php
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/class/events.php';
if (empty($_SESSION['USER_ID']) || !in_array($_SESSION['ROLE'] ?? '', ['admin', 'user'], true)) {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

$event = new event();
$readings = [];
$reports = [];
$dialog_readings = [];
$totalLocations = $totalReports = $highReadings = $mediumReadings = 0;
$error = '';
try {
    $readings = $event->get_readings();
    $totalLocations = $event->get_total_locations();
    $highReadings = $event->get_high_risk();
    $mediumReadings = $event->get_med_risk();
    if ($_SESSION['ROLE'] === 'admin') {
        $reports = $event->get_pending_reports();
        $totalReports = $event->get_pending_reports_total();
    }
    $id = filter_input(INPUT_GET, 'dialog_id', FILTER_VALIDATE_INT);
    if ($id && $id > 0) {
        $reading = $event->get_dialog($id);
        if ($reading) {
            $dialog_readings = [$reading];
        }
    }
} catch (PDOException $exception) {
    error_log('SmartSlope dashboard: ' . $exception->getMessage());
    http_response_code(503);
    $error = 'Dashboard data is unavailable. Check MySQL and the database configuration.';
}
require_once __DIR__ . '/includes/header.php';
?>
<?php if ($error !== ''): ?>
    <p class="alert alert-danger" role="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
<?php endif; ?>

<div class="container text-center" style="margin-top: 5px; padding:5px">
  <div class="row">
    <div class="col ">
      <h1>Dashboard</h1>
    </div>
    <div class="col">
    
    </div>
    <div class="col">
   
    </div>
  </div>
</div>
<div class="container" style="border: 2px solid; height: 400px; width: 100%; ">
  <h2>Barangay Irisan Map</h2>
</div>
<div class="container" style="  width: 100%; margin-top:10px">


  <div class="card" >
    <div class="card-header">
      <h5>Reading summary</h5>
    </div>
    <div class="card-body"  >
      <div class="row" style="text-align: center;">
          <div class="col ">
            <h2><?php echo $totalLocations?></h2>
            <span >Monitored Locations</span>
          </div>
          <div class="col">
            <h2><?php echo $totalReports?></h2>
            <span>Pending Reports</span>
          </div>
          <div class="col">
            <h2><?php echo $highReadings?></h2>
            <span>Saved high readings</span>
          </div>
          <div class="col">
            <h2><?php echo $mediumReadings?></h2>
            <span>Saved medium readings</span>
          </div>
        </div>
      </div>
    </div>

  </div>
</div>
<div class="container" style=" height: 400px; width: 100%; margin-top:10px">
  <div class="card">
    <div class="card-header">
      <h5>Landslide awareness analyzer</h5>
    </div>
    <div class="card-body">
      <div class="card-body " style="background: lightgray; border-radius: 5px">
        <h6 class="card-title fw-bold">Current assessment unavailable</h6>
        <span>Select a location and refresh weather. Last saved readings remain historical context.</span>
      </div>
      <p class="">Academic prototype, not an official warning. Updates follow manual weather refresh. No automatic evacuation instructions.</p>
      <span class="fw-bold">Prototype rainfall-screening category</span><br>
      <p class="btn btn-secondary fw-bold">Unavailable</p>
      <div class="card-body" style="background: lightgray; border-radius: 5px">
        <span>A current assessment is unavailable. Select an Irisan location to view its assessment.</span>
      </div>
      <div class="together-container">
        <span class="fw-bold">Location:</span>
        <span>No Location Selected</span>
      </div>
      
      <div class="row">
        <div class="col">
          <div class="card-body" style="border: 1px solid lightgray; border-radius: 5px">
            <span>1h rainfall</span><br>
            <span class="fw-bold">Unavailable</span>
          </div>
        </div>
        <div class="col">
          <div class="card-body" style="border: 1px solid lightgray; border-radius: 5px">
            <span>24h rainfall</span><br>
            <span class="fw-bold">Unavailable</span>
          </div>
        </div>
        <div class="col">
          <div class="card-body" style="border: 1px solid lightgray; border-radius: 5px">
            <span>72h rainfall</span><br>
            <span class="fw-bold">Unavailable</span>
          </div>
        </div>
      </div>
      <div class="together-container">
        <span class="fw-bold">Provider valid time:</span>
        <span>Unavailable</span><br>
        <span class="fw-bold">Rainfall window end:</span>
        <span>Unavailable</span><br>
        <span class="fw-bold">Retrieved: </span>
        <span>Unavailable</span><br>
        <span class="fw-bold">Source: </span>
        <span>Source Unavailable</span>
      </div>
      <div class="card-body" style="border: 1px solid lightgray; border-radius: 5px">
         <div class="together-container">
            <span >Why this category?</span>
            <span class="btn btn-secondary fw-bold" >CALCULATED</span>
          </div>
          <h6>No saved reading is available.</h6>
          <span >Rule: prototype-1 (legacy version not recorded). Historical inputs: not retained for this legacy record.</span>
      </div>
      <div class="card-body" style="border: 1px solid lightgray; border-radius: 5px; margin-top:10px">
          <h6>Baseline susceptibility: Unknown</h6>
          <span >No reviewed susceptibility dataset is installed.</span>
      </div>
    </div>
  </div>

  <div class="card">
    <div class="card-header">
        <h5>Recent Weather Readings</h5>
        <div class="d-flex align-items-end" style="justify-content: end;">
        
          <button class="btn btn-outline-danger" <?php echo check_role()?> >Remove Selected</button>
          <button class="btn btn-outline-primary">View All</button>
        </div>
        
    </div>
    <div class="card-body">
      <table class="table table-striped">
          <tr>
            <th>
              <button class="btn btn-outlined-danger" <?php echo check_role() ?>>
                <img src="<?= BASE_URL ?>/assets/img/trash.svg" alt="delete">
              </button>
            </th>
            <th>Location</th>
            <th>Current assessment</th>
            <th>Rainfall mm <br>1h / 24h / 72h</th>
            <th>Provider time (PHT)</th>
            <th>Details</th>
            <th>Data Status</th>
            <th <?php echo check_role() ?>>Action</th>
          </tr>
          
          <?php foreach ($readings as $reading):?>
          <tr>
            <td>
              <input type="checkbox">
            </td>
            <td><?= htmlspecialchars((string) $reading['name'], ENT_QUOTES, 'UTF-8') ?></td>
            <?php echo $event->risk_color($reading['risk_level'])?>
            <td><?php echo $reading['rainfall_1h']. "/" . $reading['rainfall_24h']. "/" .  $reading['rainfall_72h']?></td>
            <td><?= htmlspecialchars((string) $reading['observed_at'], ENT_QUOTES, 'UTF-8') ?></td>
            <td>
              
              <a class="btn btn-outline-success" command="show-modal" commandfor="view_all" href="?dialog_id=<?= (int) $reading['id'] ?>">View All</a>
            </td>
            <td><?php echo check_status($reading['stale'])?></td>
            <td <?php echo check_role() ?>>
              <button class="btn btn-outline-primary">Edit</button>
            </td>
          </tr>
          <?php endforeach?>
          
           
            
         
      </table>
      <div class="card" style="margin-inline-start:auto;">
     

      <dialog id="view_all" class="dialog" <?= !empty($dialog_readings) ? 'open' : '' ?> >
          <div class="card">
              <?php if (!empty($dialog_readings)): ?>
                  <?php foreach ($dialog_readings as $reading): ?>
                      <div class="card-header">
                          <h6>
                              Additional Details
                              <?= htmlspecialchars((string) $reading['id'], ENT_QUOTES, 'UTF-8') ?>
                          </h6>
                      </div>

                      <div class="card-body">
                          <p>
                              <strong>Location:</strong>
                              <?= htmlspecialchars((string) $reading['name'], ENT_QUOTES, 'UTF-8') ?>
                          </p>
                          <p>
                              <strong>Provider valid time:</strong>
                              <?= htmlspecialchars((string) $reading['observed_at'], ENT_QUOTES, 'UTF-8') ?>
                          </p>
                      </div>
                  <?php endforeach; ?>
              <?php else: ?>
                  <div class="card-body">
                      No details were found for this event.
                  </div>
              <?php endif; ?>

              <div class="card-footer">
                  <button command="close" commandfor="view_all">Close</button>
              </div>
          </div>
      </dialog>
      
      
      </div>

    </div>

  </div>

  <?php if ($_SESSION['ROLE'] === 'admin'): ?>
  <div class="card">
      <div class="card-header">
        <h5>Pending Reports</h5>
        <div class="d-flex align-items-end" style="justify-content: end;">
        
         
          <button class="btn btn-outline-success">All Reports</button>
        </div>
      </div>
      <div class="card-body">
          <table class="table table-striped">
            <tr>
              <th>Location</th>
              <th>Reporter</th>
              <th>Contact</th>
              <th>Status</th>
              <th>Date</th>
              <th>Actions</th>
            </tr>
            <?php foreach($reports as $report):?>
            <tr>
              <td><?= htmlspecialchars((string) $report['location_name'], ENT_QUOTES, 'UTF-8') ?></td>
              <td><?= htmlspecialchars((string) $report['reporter'], ENT_QUOTES, 'UTF-8') ?></td>
              <td><?= htmlspecialchars((string) $report['contact'], ENT_QUOTES, 'UTF-8') ?></td>
              <td><?= htmlspecialchars((string) $report['report_status'], ENT_QUOTES, 'UTF-8') ?></td>
              <td><?= htmlspecialchars((string) $report['report_date'], ENT_QUOTES, 'UTF-8') ?></td>
              <td>
                <a href=""><?php echo check_report_status($report['report_status'])?></a>
              </td>
            </tr>
            <?php endforeach?>
          </table>
      </div>
  </div>

  <?php endif; ?>
</div>

</body>
</html>