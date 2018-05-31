<?php
//ini_set('display_errors', 1);
include('simple_html_dom.php');
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
    $error = new ErrorMessage();
    try {
        //  global $src, $session;
        exec('wkhtmltoimage ' . $src . ' ' . $dst);
        //        exec('/usr/local/bin/dump.sh '.$session);
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


$result = array();

if (isset($_POST['footprint'])) {

    $footprint = $_POST['footprint'];
    $q = urlencode(str_replace(' ', '+', $footprint));
    $data = get_content('http://www.google.com/search?hl=en&q=' . $q . '&num=200&filter=0');
 
        // create curl resource 
        $ch = curl_init(); 

        // set url 
        curl_setopt($ch, CURLOPT_URL, 'http://www.google.com/search?hl=en&q=' . $q . '&num=200&filter=0'); 

        //return the transfer as a string 
        $proxy = '18.188.165.157:80';
        //$proxyauth = 'user:password';
//         $ch = curl_init();
// curl_setopt($ch, CURLOPT_URL,$url);
curl_setopt($ch, CURLOPT_PROXY, $proxy);
//curl_setopt($ch, CURLOPT_PROXYUSERPWD, $proxyauth);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_HEADER, 1);
$curl_scraped_page = curl_exec($ch);
curl_close($ch);

echo $curl_scraped_page;exit;

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
                background: #66a9bd;
            }

            table.dataTable {
                border-color: #66a9bd;
            }

        </style>
    </head>
    <body>
        <div id="app"  class="container">
            <h1>Google scraper</h1>
            <form method="post" action="scraper.php">
                <div class="row">
                    <input type="hidden" name='id' id='id'  value="true"/>
                    <input type="hidden" name='title' id='title' value="true"/>
                    <input type="hidden" name="link" id='link' value="true"/>
                    <input type="hidden" name="description" id='description' value="true"/>                    
                    <input type="text"  class="form-control" placeholder="Search" id="footprint" name="footprint"  style="width: 30%;    display: inline;"  value="<?php echo $footprint; ?>" />
                    <button type="submit" class="btn btn-success">
                        <span class="glyphicon glyphicon-search"></span> Scrap!
                    </button>
                    <button type="submit" class="btn btn-danger" id="clear" >
                        <span class="glyphicon glyphicon-remove"></span> clear!
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
                            '<td><a href="print.php?url=' . $line['link'] . '"    class="btn btn-info"  target="_blank" > <span class="glyphicon glyphicon-print"></span> CAPTURE</a></td>' .
                            '</tr>';
                }

                $body .= '</tbody></table>';

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
                        {
                            extend: 'pdf',
                            exportOptions: {
                                columns: ':visible'
                            }
                        }, {
                            extend: 'print',
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

                });
            });
        </script>


    </body>
</html>