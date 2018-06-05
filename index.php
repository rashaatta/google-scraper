<?php
session_start();
//ini_set('display_errors', 1);
include('simple_html_dom.php');
include_once('utils.php');

if(!isset($_SESSION['counter'])) $_SESSION['counter'] = getVisits();

function strip_tags_content($text, $tags = '', $invert = FALSE) {
    $text = str_ireplace("<br>", "", $text);
    preg_match_all('/<(.+?)[\s]*\/?[\s]*>/si', trim($tags), $tags);
    $tags = array_unique($tags[1]);

    if (is_array($tags) AND count($tags) > 0) {
        if ($invert == FALSE) {
            return preg_replace('@<(?!(?:' . implode('|', $tags) . ')\b)(\w+)\b.*?>.*?</\1>@si', '', $text);
        } else {
            return preg_replace('@<(' . implode('|', $tags) . ')\b.*?>.*?</\1>@si', '', $text);
        }
    } elseif ($invert == FALSE) {
        return preg_replace('@<(\w+)\b.*?>.*?</\1>@si', '', $text);
    }

    return $text;
}

function exportToolPDF($src, $dst) {
    try {
        //  global $src, $session;        
        $r = exec('wkhtmltoimage  ' . $src . ' ' . $dst);
        return 1;
    } catch (Exception $ex) {
        echo $ex->getMessage();
        return;
    }
}

function extract_url_from_redirect_link($url) {
    if ($url != '') {
        $q = parse_url($url)['query'];
        parse_str($q, $url_params)['q']['q'];

        if (isset($url_params['q']) AND ( strpos($url_params['q'], 'https://') !== false OR strpos($url_params['q'], 'http://') !== false))
            return $url_params['q'];
        else
            return false;
    }
}

function get_content($url) {
    $data = file_get_html($url);
    return $data;
}

function getVisits(){
    $error = new ErrorMessage();
    try {
        $database = new Database();
        $db = $database->webConnect();

        $query = "SELECT `value` from statistics where code = 'visits'";
        $result = mysqli_query($db, $query) or die('Query error: ' . mysqli_connect_error());
        $row = mysqli_fetch_assoc($result);
        $db->close();
        return $row['value'];
    } catch (Exception $ex) {
        echo $error->GetError($ex->getFile(), $ex->getLine(), $ex->getMessage());
        return;
    }
}

function updateVisits($visits){
    $error = new ErrorMessage();
    try {
        $database = new Database();
        $db = $database->getMySQLConnection();

        $query = "update statistics set `value`=:value where code = 'visits'";
        $stmt = $db->prepare($query);
        $stmt->bindParam("value", $visits);

        $result = $stmt->execute();
        return $result;
    } catch (Exception $ex) {
        echo $error->GetError($ex->getFile(), $ex->getLine(), $ex->getMessage());
        return;
    }
}

function url(){
  return sprintf(
    "%s://%s/",
    isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off' ? 'https' : 'http',
    $_SERVER['SERVER_NAME'],
    $_SERVER['REQUEST_URI']
  );
}

$result = array();

if (isset($_POST['footprint'])) {
    $_SESSION['counter'] = $_POST['counter'];
    updateVisits($_POST['counter']);
    $footprint = $_POST['footprint'];
    $q = urlencode(str_replace(' ', '+', $footprint));
    $data = get_content('http://www.google.com/search?hl=en&q=' . $q . '&num=200&filter=0');
    $html = str_get_html($data);

    foreach ($html->find('.g') as $g) {
        $url = '';
        $h3 = $g->find('h3.r', 0);
        $s = $g->find('span.st', 0);
        if (isset($h3)) {
            $a = $h3->find('a', 0);
            $url = $a->getAttribute('href');
        }

        $link = extract_url_from_redirect_link($url);
        if (extract_url_from_redirect_link($url)) {
            $link = extract_url_from_redirect_link($url);
            $result[] = array(
                'title' => strip_tags($a->innertext),
                'link' => $link,
                'description' => strip_tags_content($s->innertext)
            );
        }
    }
} else {
    $footprint = '';
}
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <title>Google Scraper</title>
        <link rel="stylesheet" href="css/bootstrap.min.css">
        <link rel="stylesheet" href="css/dataTables.bootstrap.min.css">
        <link rel="stylesheet" href="css/buttons.dataTables.min.css">

        <style>
            /* Header cells */
            table.dataTable thead th {
                text-align: center;
                background: #269abc;
            }

            table.dataTable {
                border-color: #269abc; /* #66a9bd; */
            }

        </style>
    </head>
    <body>
        <div id="app"  class="container">
            <h1>Google scraper</h1>
            <?php
            // $host= gethostname();
            // $ip = gethostbyname($host);
            $ip = $_SERVER['REMOTE_ADDR']; //$_SERVER['SERVER_ADDR'];
            ?>
            <div class="row">
                <p><b>Server IP:</b> <?php echo $ip; ?></p>
                <p id="counterel"><b>Counter : </b> <?php echo $_SESSION['counter']; ?> </p>
                <!-- <p><b>Timer : </b> <span id="timer">30</span> </p> -->
            </div><br>
            <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                <div class="row">
                    <input type="hidden" name='id' id='id'  value="true"/>
                    <input type="hidden" name='title' id='title' value="true"/>
                    <input type="hidden" name="link" id='link' value="true"/>
                    <input type="hidden" name="counter" id='counter' value="<?php echo $_SESSION['counter']; ?>"/>
                    <input type="hidden" name="description" id='description' value="true"/>                    
                    <input type="text"  class="form-control" placeholder="Search" id="footprint" name="footprint"  style="width: 30%;    display: inline;"  value="<?php echo $footprint; ?>" />
                    <button type="submit" class="btn btn-success" id="submit">
                        <span class="glyphicon glyphicon-search"></span> Scrap!
                    </button>
                    <button type="button" class="btn btn-danger" id="clear" >
                        <span class="glyphicon glyphicon-remove"></span> Clear!
                    </button>
                </div>
            </form>

            <br/>
            <div class="row">
                <?php
                $i = 1;
                $index = 1;
                $body = '  <table id="tblId" width="100%" class="table table-striped  table-bordered">
                    <thead>
                        <tr>
                            <th >ID</th>
                            <th >Title</th>
                            <th >Link</th>
                            <th >Description</th>  
                            <th >CAPTURE</th>
                        </tr>
                    </thead>
                    <tbody>';
                foreach ($result as $line) {

                    $index = $i++;
                    $body .= '<tr>' .
                            '<td>' . $index . '</td>' .
                            '<td>' . $line['title'] . '</td>' .
                            '<td><a href="' . $line['link'] . '"  target="_blank" >' . $line['link'] . ' </a></td>' .
                            '<td>' . $line['description'] . '</td>' .
//                            '<td><a href="print.php?url=' . $line['link'] . '"    class="btn btn-info"  target="_blank" > <span class="glyphicon glyphicon-print"></span>CAPTURE</a></td>' .
                             '<td><a href="capt.php?url=' . $line['link'] . '"    class=""  target="_blank" >' . url() . 'capt.php?url='  . $line['link'] . '</a></td>' .
                            '</tr>';
                }

                $body .= '</tbody></table><br/><br/><br/>';

//                echo '<b>Total: ' . count($result) . '</b><br>';

                echo $body;
                ?>

            </div>
        </div>
        <script src="js/jquery-3.2.1.min.js"></script> 
        <script src="js/bootstrap.min.js"></script>
        <script src="js/jquery.dataTables.min.js"></script>
        <script src="js/dataTables.bootstrap.min.js"></script>
        <script src="js/dataTables.buttons.min.js"></script>
        <script src="js/buttons.flash.min.js"></script>
        <script src="js/jszip.min.js"></script>
        <script src="js/pdfmake.min.js"></script>
        <script src="js/vfs_fonts.js"></script>
        <script src="js/buttons.html5.min.js"></script>
        <script src="js/buttons.print.min.js"></script>
        <script src="js/buttons.colVis.min.js"></script>
        <script>

            $(document).ready(function () {
                var timer = 0;
                var minus = 30;

                // setInterval(function(){
                //     timer++;
                //     minus = 30 - timer;
                //     if(minus > 0){
                //         $("#timer").html(minus);
                //     } else {
                //         $("#timer").html('You can search now!');
                //     }
                // }, 1000);

                var table = $('#tblId').DataTable({
                    dom: 'Bfrtip',
                    buttons: [
                        {
                            extend: 'print',
                            exportOptions: {
                                columns: ':visible'
                            }
                        },
                        {
                            extend: 'csv',
                            exportOptions: {
                                columns: ':visible'
                            }
                        },
                        {
                            extend: 'excel',
                            exportOptions: {
                                columns: ':visible'
                            }
                        },
                        'colvis'
                    ],
                    targets: -1,
                    visible: true,
                    sortable: false,
                    "sPaginationType": "full_numbers"
                })

                $("#clear").on('click', function clearInput(e) {
                    var count = table.data().count();

                    if (count == 0) {
                        e.preventDefault();
                    }
                    $('#footprint').val('');
                    table.clear().draw();
                });

                $("#submit").on('click', function clearInput(e) {
                    if($.trim($("#footprint").val()) == '') {
                        alert("Please enter search term.");
                        return false;
                    }

                    var countr = $("#counter").val();
                    // if(timer < 30 && countr > 0){
                    //     alert("Please give us 30 seconds between requests, You need " + minus + " more seconds");
                    //     return false;
                    // }
                    
                    console.log(countr);
                    countr++;
                    // $("#counterel").html('<b>Counter : </b> ' + countr);
                    $("#counter").val(countr);
                });
            });
        </script>


    </body>
</html>
