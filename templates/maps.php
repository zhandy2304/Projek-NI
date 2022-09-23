<!DOCTYPE html>
<html> 
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>Maps Jalan Toll</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.1/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-iYQeCzEYFbKjA/T2uDLTpkwGzCiq6soy8tYaI1GyVh/UjpbCx/TYkiZhlZB6+fzT" crossorigin="anonymous">
  <script src="https://kit.fontawesome.com/0ca54e6540.js" crossorigin="anonymous"></script>
  <link rel="icon" href="https://tolmakassar.com/apexnew/app-assets/img/Logo_MMN_JTSE.png">
  <!-- <script src="https://maps.google.com/maps/api/js?key=AIzaSyCgELFbsvJqa5gTNtLYGINwu8dJcjbVIbc" type="text/javascript"></script> -->
  <script src="https://maps.googleapis.com/maps/api/js?sensor=false&callback=myMap"></script>

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
          <a href="#" class="btn btn-light" role="button" >
            <i class="fa-sharp fa-solid fa-bell"></i> <span class="position-absolute top-20 start-10  p-1 badge rounded-pill bg-danger">
              99+
              <span class="visually-hidden">unread messages</span>
            </span>
         </a>
          </button>
          <h5 class="offcanvas-title " id="offcanvasDarkNavbarLabel">MAPS</h5>
          <button type="button" class="btn-close btn-close-black" data-bs-dismiss="offcanvas" aria-label="Close"></button> 
        </div>
        <div class="offcanvas-body">
          <ul class="navbar-nav justify-content-end flex-grow-1 pe-3 me-auto mb-2 mb-lg-0">
            <li class="nav-item">
              <a class="nav-link text-dark" aria-current="page" href="/#Counting">Counting</a>
            </li>
            <li class="nav-item">
              <a class="nav-link text-dark" href="/#pelanggaran">Pelanggaran</a>
            </li>
            <li class="nav-item">
              <a class="nav-link text-dark" href="/maps">Peta</a>
            </li>
            <li class="nav-item dropdown">
              <a class="nav-link dropdown-toggle text-dark" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                Data
              </a>
              <ul class="dropdown-menu dropdown-menu-secondary">
                <li><a class="dropdown-item" href="/data_traffic" target="_blank">Counting</a></li>
                <li><a class="dropdown-item" href="/data_pelanggaran" target="_blank">Pelanggaran</a></li>
              </ul>
            </li>
          </ul>
        </div>
      </div>
    </div>
  </nav>
  <h1 class="Counting" id="Counting" style="margin-top:80px"><center>Maps Pelanggaran Lalu Lintas</center></h1>
  <center><div id="map" style="width: 1200px; height: 500px; margin-top: 10px; margin-left: 20px; margin-right: 20px;"></div></center>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.1/dist/js/bootstrap.bundle.min.js" integrity="sha384-u1OknCvxWvY5kfmNBILK2hRnQC3Pr17a+RTT6rIHI7NnikvbZlHgTPOOmMi466C8" crossorigin="anonymous"></script>
<script>
<?php 

$conn = new mysqli('localhost', 'root', '', 'jalan_toll');
$day = null;
$jumlah = null;

$query = $conn->query("
SELECT LOKASI AS lokasi, DAY(WAKTU) AS hari, COUNT(*) AS number_of_appearances 
    FROM data_pelanggaran
    WHERE DAY(WAKTU) = DAY(CURDATE())
    GROUP BY LOKASI");

$query2 = $conn->query("
SELECT LOKASI AS lokasi, DAY(WAKTU) AS hari, COUNT(*) AS number_of_appearances 
    FROM data_pelanggaran
    WHERE DAY(WAKTU) = DAY(CURDATE())
    GROUP BY LOKASI");

foreach ($query as $data)
{
  $day[] = $data['hari'];
  $jumlah[] = $data['number_of_appearances'];
}

?>

function initMap() {
    var map;
    var bounds = new google.maps.LatLngBounds();
    var mapOptions = {
        mapTypeId: 'roadmap'
    };
                    
    // Display a map on the web page
    map = new google.maps.Map(document.getElementById("map"), mapOptions);
    map.setTilt(50);

    // Add location
    var locations = [
      ['Gerbang Tol Kaluku Badoa', -5.117499135915403, 119.44175786972707],
      ['Off Ramp Rappokalling', -5.121935023337312, 119.44207769473527],
      // ['On Ramp Abu Bakar Lambogo', -5.14201454396365, 119.4387834789535],
      // ['Area Rappokalling', -5.129894348879903, 119.44061448951626],
    ];

    var marker, i;
                        
    // Info window content
    var infoWindowContent = [
        <?php if($query2->num_rows > 0){
            while($row = $query2->fetch_assoc()){ ?>
                ['<div class="info_content">' +
                '<h9 style="font-weight: bold; "><?php echo $row['lokasi']; ?></h9>' +
                '<p> Terjadi pelanggaran sebanyak <?php echo $row['number_of_appearances']; ?></p>' + '</div>'],
        <?php }
        }
        ?>
    ];
        
    // Add multiple markers to map
    var infoWindow = new google.maps.InfoWindow(), marker, i;
    
    // Place each marker on the map  
    for( i = 0; i < locations.length; i++ ) {
        var position = new google.maps.LatLng(locations[i][1], locations[i][2]);
        bounds.extend(position);
        marker = new google.maps.Marker({
            position: position,
            label: locations[i][0],
            map: map,
            title: locations[i][0]
        });
        
        // Add info window to marker    
        google.maps.event.addListener(marker, 'click', (function(marker, i) {
            return function() {
                infoWindow.setContent(infoWindowContent[i][0]);
                infoWindow.open(map, marker);
            }
        })(marker, i));

        infoWindow.setContent(infoWindowContent[i][0]);
        infoWindow.open(map, marker);

        // Center the map to fit all markers on the screen
        map.fitBounds(bounds);
    }

    // Set zoom level
    var boundsListener = google.maps.event.addListener((map), 'bounds_changed', function(event) {
        this.setZoom(14);
        google.maps.event.removeListener(boundsListener);
    });
    
}

// Load initialize function
google.maps.event.addDomListener(window, 'load', initMap);
</script>

</body>
</html>