<!DOCTYPE html>
<html> 
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>Data Counting dan pelanggaran</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.1/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-iYQeCzEYFbKjA/T2uDLTpkwGzCiq6soy8tYaI1GyVh/UjpbCx/TYkiZhlZB6+fzT" crossorigin="anonymous">
  <script src="https://kit.fontawesome.com/0ca54e6540.js" crossorigin="anonymous"></script>
  <link rel="icon" href="https://tolmakassar.com/apexnew/app-assets/img/Logo_MMN_JTSE.png">
  <script src="https://maps.google.com/maps/api/js?key=AIzaSyCgELFbsvJqa5gTNtLYGINwu8dJcjbVIbc" type="text/javascript"></script>
  <!-- <script src="https://maps.googleapis.com/maps/api/js?sensor=false&callback=myMap"></script> -->
  
  <!-- Automatic refresh page every 10 minutes -->
  
  <script>
    function autoRefresh() {
        window.location = window.location.href;
    }
    setInterval('autoRefresh()', 300000);
</script>

  <style>
      .chartjs-table, th, td{

        border-collapse : collapse;
        border: 1px solid #67748E;
        padding: 10px;
        margin-left:20px;
        font-size:12px;
      }

      .chartjs-thead {
        font-weight : bold; 
      }

      .chartjs-body {
        text-align : center;
      }
      .dropdown-menu {
        max-height: 600px;
        overflow-y: scroll;
      }
    </style>

</head> 

<body>
  <nav class="navbar navbar-light bg-light fixed-top">
    <div class="container-fluid">
      <a class="navbar-brand text-dark fw-semibold" href="/" style="font-size:30px;">
        <img src="https://tolmakassar.com/apexnew/app-assets/img/Logo_MMN_JTSE.png" alt="Logo" width="50" height="40" class="d-inline-block align-text-top">
          CCTV</a>
      <button class="navbar-toggler" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasDarkNavbar" aria-controls="offcanvasDarkNavbar">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="offcanvas offcanvas-end text-bg-light" tabindex="-1" id="offcanvasDarkNavbar" aria-labelledby="offcanvasDarkNavbarLabel">
        <div class="offcanvas-header bg-light border-bottom rounded-4 border-secondary">
          <div class="dropstart">
            <button class="btn btn-light " type="button" data-bs-toggle="dropdown" aria-expanded="false">
              <i class="fa-sharp fa-solid fa-bell"></i>
              <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                0
                <span class="visually-hidden">unread messages</span>
              </span> 
            </button>
            <ul class="dropdown-menu">
            
              <!-- Pengkoneksian ke database untuk pembuatan notifikasi -->
              <?php
              $conn = new mysqli('192.168.0.66', 'admin', 'a1b2c3d4', 'jalan_toll');
                $query10 = $conn->query("
                SELECT * FROM data_pelanggaran ORDER BY ID DESC LIMIT 5 
                ");
  
                $result = array();
                while ($data7 = $query10 -> fetch_assoc()){
                  echo ("
                  <li>
                  <a href='detail_pelanggaran.php' class='dropdown-item' role='button' style='height: auto; width:250px;' type='button' data-bs-placement='left' tabindex='0'  data-bs-toggle='popover' data-bs-trigger='hover' data-bs-title='Pelanggaran ".$data7['WAKTU']."'data-bs-content='".$data7['JENIS_PELANGGARAN']." di ".$data7['LOKASI']."'><span><i class='fa-sharp fa-solid fa-envelope' aria-pressed='true' ></i></span>".$data7['JENIS_PELANGGARAN']."<br><p>".$data7['LOKASI']."</p>
                ");
  
                if($data7['JENIS_PELANGGARAN'] == 'Melawan Arus') {
                  echo ("
                  <img src='http://192.168.0.211:5000/static/counting000/".substr($data7['project'], 11)."/melawan_arus/".$data7['GAMBAR']."'
                  style='margin-left:16px;'
                  class='d-block w-100 h-75 rounded'
                  alt='...'/>
                  ");
                }
                elseif($data7['JENIS_PELANGGARAN'] == 'Over Speed') {
                  echo ("
                  <img src='http://192.168.0.211:5000/static/pelanggaran/".$data7['GAMBAR']."'
                  style='margin-left:16px;'
                  class='d-block w-100 h-75 rounded'
                  alt='...'/>
                
                  ");
                }
                
                else{
                  echo ("
                  <img src='http://192.168.0.211:5000/static/counting000/".substr($data7['project'], 11)."/person/".$data7['GAMBAR']."'
                  style='margin-left:16px;'
                  class='d-block w-100 h-75 rounded'
                  alt='...'/>
                ");
                }
                 echo ("</a>");
              echo ("</li>");
                };
               
              ?>
         
             </ul>
          </div>
          <h5 class="offcanvas-title fw-bold fs-3 " id="offcanvasDarkNavbarLabel ">CCTV</h5>
          <button type="button" class="btn-close btn-close-black" data-bs-dismiss="offcanvas" aria-label="Close"></button> 
        </div>
        <div class="offcanvas-body">
          <ul class="navbar-nav justify-content-end flex-grow-1 pe-3 me-auto mb-2 mb-lg-0">
            <li class="nav-item">
              <a class="nav-link text-dark fw-semibold fs-5" href="maps.php" target="_blank">Peta</a>
            </li>
            <li class="nav-item dropdown">
              <a class="nav-link dropdown-toggle text-dark fw-semibold fs-5" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                Data
              </a>
              <ul class="dropdown-menu dropdown-menu-secondary">
                <li><a class="dropdown-item fw-semibold" href="data_traffic.php" target="_blank">Counting</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item fw-semibold" href="data_pelanggaran.php" target="_blank">Pelanggaran</a></li>
              </ul>
            </li>
          </ul>
        </div>
      </div>
    </div>
  </nav>