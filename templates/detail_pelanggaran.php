<?php include 'header1.php' ; ?>
        <div class="container mt-5">
        <h1 class="Counting" id="Counting" style="margin-top:80px"><center>Data Pelanggaran Lalu Lintas</center></h1>
        <button type="button" class="btn btn-outline-success" onclick="exportData()">Download Data</button>
            <table id="data_pelanggaran" class="table table-borderless table-responsive card-1 p-4 gmd-4" style="margin-top: 20px;">
            <thead>
            <tr class="border-bottom">
            <th>
                <span class="ml-2">Waktu</span>
            </th>
            <th>
                <span class="ml-2">Gambar</span>
            </th>
            <th>
                <span class="ml-2">Jenis Pelanggaran</span>
            </th>
            <th>
                <span class="ml-2">Lokasi</span>
            </th>
            <th>
                <span class="ml-4">Action</span>
            </th>
            </tr>
        </thead>
        <tbody>
            <?php
                while ($data = $query -> fetch_assoc()){
                    echo ("
                        <tr class='border-bottom'>
                        <td>
                            <div class='p-2'>
                                <span class='d-block font-weight-bold'>".substr($data['WAKTU'], 0, 11)."</span>
                                <small> Pukul ".substr($data['WAKTU'], 11)."</small>
                            </div>
                        </td>
                        <td>
                            <div class='p-2 d-flex flex-row align-items-center mb-2'>
                    ");

                    if($data['JENIS_PELANGGARAN'] == 'Melawan Arus' || $data['JENIS_PELANGGARAN'] == 'Over Speed') {
                    echo ("
                    <img src='http://192.168.0.211:5000/static/counting000/".substr($data['project'], 11)."/melawan_arus/".$data['GAMBAR']."' width='30'
                    style='margin-left:16px;'
                    class='d-block w-50 h-100 rounded'
                    alt='...'/>
                    </li>
                    ");
                    }else{
                    echo ("
                    <img src='http://192.168.0.211:5000/static/counting000/".substr($data['project'], 11)."/person/".$data['GAMBAR']."' width='30'
                    style='margin-left:16px;'
                    class='d-block w-50 h-100 rounded'
                    alt='...'/>
                    
                    </li>");
                    };

                    $id = $data['id'];
                    $query_detail = $conn->query("SELECT * FROM data_pelanggaran WHERE id = '$id'");
                    while ($data_detail = $query_detail -> fetch_assoc()){
                        echo ("

                        </td>
                        <td>
                            <div class='p-2'>
                                <span class='font-weight-bold'>".$data['JENIS_PELANGGARAN']."</span>
                            </div>
                        </td>
                        <td>
                            <div class='p-2 d-flex flex-column'>
                                <span>".$data['LOKASI']."</span>
                            </div>
                        </td>
                        <td>

                        <!-- Modal -->
                        <div class= 'modal fade' id='modalDetail".$data['id']."' tabindex='-1' role='dialog' aria-labelledby='exampleModalLabel' aria-hidden='true'>
                            <div class='modal-dialog modal-lg' role='document'>
                                <div class='modal-content'>
                                <div class='modal-header'>
                                    <h5 class='modal-title' id='printPageButton'>Laporan Pelanggaran</h5>
                                    <button type='button' class='close' data-dismiss='modal' aria-label='Close'>
                                    <span id='printPageButton' aria-hidden='true'>&times;</span>
                                    </button>
                                </div>
                                <div class='modal-body'>
                                    <center><b><h5>
                                        LAPORAN PELANGGARAN LALU LINTAS
                                    </h5></b></center>
                                    </br>
                                    Telah terjadi pelanggaran lalu lintas dengan detail sebagai berikut : 
                                    <table spacing='10' style='margin-top:20px;'>
                                        <tr>
                                            <td>Jenis Pelanggaran </td>
                                            <td>: ".$data['JENIS_PELANGGARAN']."</td> 
                                        </tr>
                                        <tr>
                                            <td>Lokasi </td>
                                            <td>: ".$data['LOKASI']." </td> 
                                        </tr>
                                        <tr>
                                            <td>Tanggal </td>
                                            <td>: ".substr($data['WAKTU'], 0, 11)."</td> 
                                        </tr>
                                        <tr>
                                            <td>Pukul </td>
                                            <td>: ".substr($data['WAKTU'], 11)." WITA</td> 
                                        </tr>
                                    </table>

                                    <br>Berikut adalah capture gambar pelanggaran yang berhasil di tangkap
                                    oleh kamera CCTV
                                    
                                    ");

                                if($data['JENIS_PELANGGARAN'] == 'Melawan Arus' || $data['JENIS_PELANGGARAN'] == 'Over Speed') {
                                    echo ("
                                    <center>
                                    <img src='http://192.168.0.211:5000/static/counting000/".substr($data['project'], 11)."/melawan_arus/".$data['GAMBAR']."' width='30'
                                    style='margin-left:16px;'
                                    class='d-block w-50 h-100 rounded'
                                    alt='...'/>
                                    </li>
                                    
                                    ");
                                }else{
                                    echo ("
                                    <center>
                                    <img src='http://192.168.0.211:5000/static/counting000/".substr($data['project'], 11)."/person/".$data['GAMBAR']."'width='100''
                                    class='d-block w-40 h-20 rounded'
                                    style='margin-top:20px;'
                                    alt='...'/>
                                    </center>
                                    </li>");
                                        };

                                echo ("
                                </div>
                                <div class='modal-footer'>
                                    <button id='printPageButton' type='button' class='btn btn-secondary' data-dismiss='modal'>Close</button>
                                    <button id='printPageButton' type='button' class='btn btn-primary' onclick=printDetail('modalDetail".$data['id']."')>Print</button>
                                </div>
                                </div>
                            </div>
                        </div>
                            <div class='p-2 icons'>
                            <button type='button' class='btn btn-outline-primary' data-toggle='modal' data-target='#modalDetail".$data['id']."'>Detail</button>
                            </div>
                        </td>
                        </tr>
                        
                        ");
                    };
                    
                };

                ?>
                
            </div>
            

            </tbody>
            </table>
     </div>
    <!-- <script type='text/javascript' src='https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-alpha1/dist/js/bootstrap.bundle.min.js'></script> -->
    <script type='text/javascript' src='#'></script>
    <script type='text/javascript' src='#'></script>
    <script type='text/javascript' src='#'></script>
    <script type='text/javascript'>'#'</script>
    <script>
        function printDetail(divName) {
            var printContents = document.getElementById(divName).innerHTML;
            var originalContents = document.body.innerHTML;

            document.body.innerHTML = printContents;

            window.print();

            document.body.innerHTML = originalContents;
        }
    </script>
    <script>
    function exportData(){
        /* Get the HTML data using Element by Id */
        var table = document.getElementById("data_pelanggaran");
    
        /* Declaring array variable */
        var rows =[];
    
        //iterate through rows of table
        for(var i=0,row; row = table.rows[i];i++){
            //rows would be accessed using the "row" variable assigned in the for loop
            //Get each cell value/column from the row
            column1 = row.cells[0].innerText;
            column2 = row.cells[2].innerText;
            column3 = row.cells[3].innerText;
    
        /* add a new records in the array */
            rows.push(
                [
                    column1,
                    column2,
                    column3,
                ]
            );
    
            }
            csvContent = "data:text/csv;charset=utf-8,";
            /* add the column delimiter as semicolon(;) and each row splitted by new line character (\n) */
            rows.forEach(function(rowArray){
                row = rowArray.join(";");
                csvContent += row + "\r\n";
            });
    
            /* create a hidden <a> DOM node and set its download attribute */
            var encodedUri = encodeURI(csvContent);
            var link = document.createElement("a");
            link.setAttribute("href", encodedUri);
            link.setAttribute("download", "Data Pelanggaran.csv");
            document.body.appendChild(link);
            link.click();
        }
    </script>

    </body>
</html>