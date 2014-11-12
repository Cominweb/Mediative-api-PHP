Mediative-api-PHP
=================

Mediative PHP API wrapper

This PHP class let you use the Mediative API with a developer account. You can manipulate easily every data of the Mediative you want thanks to this class.

How to install ?
----------------
Get your developers identifients (public token, secret token, domain URL) on [api.omi.tv/developers](https://api.omi.tv/developers/ "Developers Console") (You need an administrative account on the Mediative you want to use). Then, in your PHP script, require the file MediativeApi.php and keep the file Curl.php in the same directory. Then do:

        $client = new MediativeApi(PUBLIC, SECRET, DOMAIN);


Documentation
------

####GET
To GET a ressource, use the GET method and fill the ressource you want.

    $client->get('medias');

You can get a specific ressource by providing its ID, using one of these ways :

    $client->get('medias', 3254);
    $client->get('medias', array('id' => 3254));
    $client->get('medias/3254');

Use the $options array to set your select options:

    $response = $client->get('medias', array('where' => 'title%%test;created<2014-11-12', 
                                             'order' => 'created:DESC,title',
                                             'fields' => 'Media.id,Media.created,Media.title',
                                             'recursive' => -1,
                                             'limit' => '1,25',
                                             ));

The response is contained in the return class, with the ressource name in singular in camel case (example: ressource 'medias' is accessible in $response->Media)

####POST
To POST and create a new ressource, use the post method.

    $datas = array('title' => 'Test', 'tags' => 'demo, test, try, tries', 'type' => 'LocalVideo');
    $response = $client->post('medias', $datas);

See the doc on [api.omi.tv/developers](https://api.omi.tv/developers/ "Developers Console") to know which fields to POST on which ressources. Response is an array containing the added rows. The first one added is contained in $response[0], then get your ressource added in singular in camel case (example: the first row added in ressource 'medias' is accessible in $response[0]->Media)

####PUT
To PUT and update a ressource, use the put method.

    $datas = array('title' => 'Updated title', 'tags' => 'update, api', 'id' => 3254);
    $response = $client->put('medias', $datas);

You can use a different way to indicate which ressource ID to update: 

    $datas = array('title' => 'Updated title', 'tags' => 'update, api');
    $response = $client->put('medias/3254', $datas);

To make a PUT request without ID, set the $check flag to false :

    $datas = array('title' => 'Updated title', 'tags' => 'update, api');
    $response = $client->put('medias', $datas, array(), false);

The updated fields are available in the $response class, in the singular camel cased ressource name (example: PUT result on 'medias' ressource would be accessible in $response->Media)

####DELETE
To delete a ressource, use the delete method. You can use one of those syntax :

    $response = $client->delete('medias', 3254);
    $response = $client->delete('medias', array('id' => 3254));
    $response = $client->delete('medias/3254');

$response is false if delete fails, true if it worked.
