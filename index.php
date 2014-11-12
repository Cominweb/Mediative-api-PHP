<?php
/*   CONFIG TEST   */
define('PUBLIC', '***your public developer key***');
define('SECRET', '***your secret developer key***');
define('DOMAIN', '***your working domain***');
/* END CONFIG PART */

// let see in real time what happens
ob_implicit_flush(true);
ob_end_flush();

// include the API
require('MediativeApi.php');

//start a new instance of the API
$client = new MediativeApi(PUBLIC, SECRET, DOMAIN);

// first, we do not have token, let's ask one
$client->disableSecure()->auth();
$token = $client->getToken();

// let's prepare datas to add a media
$datas = array('title' => 'test api',
               'type' => 'youtube',
               'local_datas' => '{"filenames":["mt_534febff1d56d.mp4"]}',
               'settings' => '{"gmaps":{"latitude":"45.875616","longitude":"4.683845000000019"}}',
               'license' => 'public'
              );

try {
    // request to add a media
    $response = $client->post('medias', $datas);
    if(isset($response[0]->Media->id)) {
        $id = $response[0]->Media->id;
        echo '<h4>Media added ! <small>#'.$id.'</small></h4>';
        echo '<h4>Media datas : </h4>';
        $query = $client->get('medias', $id); // request to get the last added media
        var_dump($query->Media);
        echo '<h4>Updating media #'.$id.'...</h4>';
        $datas = array('id' => $id, 'title' => 'updated api');
        $response = $client->put('medias', $datas); // request to update the media
        var_dump($response);
        echo '<h4>New media datas : </h4>';
        $query = $client->get('medias', array('id' => $id)); // request to get the updated media
        var_dump($query->Media);
        echo '<h4>Deleting #'.$id.' </h4>';
        $query = $client->delete('medias', $id); // request to delete the added media
        echo '<h4>Confirmation...</h4>';
        $query = $client->get('medias', $id); // request to check media has been deleted
        var_dump($query);
    } else {
        throw new Exception('Cannot parse response data'); // media couldn't be added
    }
} catch(Exception $e) {
    echo '<p class="error" style="color:red">'.$e->getMessage().'</p>';
    if($e->xdebug_message) echo '<table>'.$e->xdebug_message.'</table>';
}