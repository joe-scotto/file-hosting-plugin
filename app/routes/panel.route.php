<?php

// Define variables
$user = User::returnUserInfo($_SESSION['user_id']);
$panel = new Panel($user['username']);

// Get 
$app->get('/panel', function ($request, $response) use ($panel, $user) {
    // Verify user is logged in
    if (!$_SESSION['user_id']) {
        // Redirect to login page if not logged in
        return $response->withHeader('Location', '/');
    } else {
        // Define files
        $files = $panel->listDirectory();

        // Return panel view with data
        return $this->view->render($response, 'panel/panel.twig', [
            'name' => $user['name'],
            'username' => $user['username'],
            'folder_name' => $_COOKIE['folder_name'],
            'files' => [
                'file' => $files,
                'count' => $panel->countFilesInDirectory($files),
                'dir' => $panel->checkIfDirectory($files)
            ],
            'url' => '/panel',
            'message' => $_SESSION['message'],
        ]);
    }
});

$app->get('/panel/[{path:.*}]', function ($request, $response) use ($panel, $user) {
    // Verify user is logged in
    if (!$_SESSION['user_id']) {
        // Redirect to login page if not logged in
        return $response->withHeader('Location', '/');
    } else {
        // Breadcrumbs
        $breadcrumbs = explode('/', $request->getAttribute('path'));

        // Breadcrumb Paths
        $breadcrumbDirectory = Panel::formatBreadcrumbs($breadcrumbs);

        // Define files
        $files = $panel->listDirectory($request->getAttribute('path'));

        // Return panel view with data
        return $this->view->render($response, 'panel/panel.twig', [
            'name' => $user['name'],
            'username' => $user['username'],
            'folder_name' => $_COOKIE['folder_name'],
            'breadcrumbs' => array(
                'path' => $breadcrumbs,
                'directory' => $breadcrumbDirectory
            ),
            'files' => [
                'file' => $files,
                'count' => $panel->countFilesInDirectory($files, $request->getAttribute('path')),
                'dir' => $panel->checkIfDirectory($files, $request->getAttribute('path'))
            ],
            'url' => $request->getUri()->getPath(),
            'message' => $_SESSION['message']
        ]);
    }
});

// Post
$app->post('/panel', function ($request, $response) use ($panel) {
    // Define max upload size
    $maxUploadSize = Utilities::convertToBytes(ini_get('post_max_size'));

    // Verify user did not exceed max upload
    if ($_SERVER['CONTENT_LENGTH'] > $maxUploadSize) {
        // Set error message 
        Utilities::setMessage("Whoa!", "You're attempting to upload a file or files that are too large, please try again with a smaller upload. The maximum upload size is: " . Utilities::convertDataType(ini_get('post_max_size')) . ".", "message_modal");
        
        // Redirect to original page
        return $response->withHeader('Location', $request->getUri()->getPath());
    }

    // Define POST data
    $params = $request->getParams();

    // Set cookie with folder name for refresh errors
    setcookie("folder_name", $params['folder_name'], time() + 5);

    // Check if new folder form was sent
    if (isset($params['submit_folder'])) {
        // Check to make sure folder name is not empty
        if ($params['folder_name']) {
            // Check if folder fits parameters
            if ($panel->checkFolderName($params['folder_name'])) {
                // Check to see if new folder was created, redirect to original page if not.
                if ($panel->createNewDirectory($params['folder_name'])) {
                    // Redirect to new folder
                    return $response->withHeader('Location', '/panel/' . $params['folder_name']);
                } else {
                    // Set error message 
                    Utilities::setMessage("Whoa!", "Either the folder name you chose is already in use or you're trying to create a folder with the same name as a system folder. Please try another name.", "folder_modal");

                    // Redirect to original page
                    return $response->withHeader('Location', $request->getUri()->getPath());
                } 
            } else {
                // Set error message 
                Utilities::setMessage("Whoa!", "param_error", "folder_modal");

                // Redirect to original page
                return $response->withHeader('Location', $request->getUri()->getPath());
            }
        } else {
            // Set error message 
            Utilities::setMessage("Whoa!", "You cannot create a folder without a name.", "folder_modal");
            
            // Redirect to original page
            return $response->withHeader('Location', $request->getUri()->getPath());
        }
    }

    // Check if upload form was sent 
    if (isset($params['submit_files'])) {
        // Get files from form 
        $files = $request->getUploadedFiles()['files'];

        if (!$files[0]->file) {
            // Set error message 
            Utilities::setMessage("Whoa!", "You must select a file to upload.", "upload_modal");
            
            // Redirect to original page
            return $response->withHeader('Location', $request->getUri()->getPath());
        } else {
            // Upload Files
            if (!$panel->uploadFiles($files)) {
                // Set too large message message 
                Utilities::setMessage("Whoa!", "We've encountered an error, please try again later", "message_modal");
            } else {
                // Set success message 
                Utilities::setMessage("Great!", "Upload was successful!", "message_modal");
            }

            // Redirect to original page
            return $response->withHeader('Location', $request->getUri()->getPath());
        }
    }
});

$app->post('/panel/[{path:.*}]', function ($request, $response) use ($panel) {
    // Define max upload size
    $maxUploadSize = Utilities::convertToBytes(ini_get('post_max_size'));

    // Verify user did not exceed max upload
    if ($_SERVER['CONTENT_LENGTH'] > $maxUploadSize) {
        // Set error message 
        Utilities::setMessage("Whoa!", "You're attempting to upload a file or files that are too large, please try again with a smaller upload. The maximum upload size is: " . Utilities::convertDataType(ini_get('post_max_size')) . ".", "message_modal");
        
        // Redirect to original page
        return $response->withHeader('Location', $request->getUri()->getPath());
    }

    // Define POST data
    $params = $request->getParams();

    // Set cookie with folder name for refresh errors
    setcookie("folder_name", $params['folder_name'], time() + 5);

    if (isset($params['submit_folder'])) {
        // Check to make sure folder name is not empty
        if ($params['folder_name']) {
            // Check if folder fits parameters
            if ($panel->checkFolderName($params['folder_name'])) {
                // Check to see if new folder was created, redirect to original page if not.
                if ($panel->createNewDirectory($params['folder_name'], $request->getUri()->getPath())) {
                    // Redirect to new folder
                    return $response->withHeader('Location', $request->getUri()->getPath() . '/' . $params['folder_name']);
                } else {
                    // Set error message 
                    Utilities::setMessage("Whoa!", "Either the folder name you chose is already in use or you're trying to create a folder with the same name as a system folder. Please try another name.", "folder_modal");
                    
                    // Redirect to original page
                    return $response->withHeader('Location', $request->getUri()->getPath());
                }
            } else {
                // Set error message 
                Utilities::setMessage("Whoa!", "param_error", "folder_modal");

                // Redirect to original page
                return $response->withHeader('Location', $request->getUri()->getPath());
            }
        } else {
            // Set error message 
            Utilities::setMessage("Whoa!", "You cannot create a folder without a name", "folder_modal");
            
            // Redirect to original page
            return $response->withHeader('Location', $request->getUri()->getPath());
        }
    }

    // Check if upload form was sent 
    if (isset($params['submit_files'])) {
        // Get files from form 
        $files = $request->getUploadedFiles()['files'];

        if (!$files[0]->file) {
            // Set error message 
            Utilities::setMessage("Whoa!", "You must select a file to upload.", "upload_modal");
            
            // Redirect to original page
            return $response->withHeader('Location', $request->getUri()->getPath());
        } else {
            // Upload Files
            if (!$panel->uploadFiles($files, $request->getUri()->getPath())) {
                // Set too large message message 
                Utilities::setMessage("Whoa!", "We've encountered an error, please try again later", "message_modal");
            } else {
                // Set success message 
                Utilities::setMessage("Great!", "Upload was successful!", "message_modal");
            }

            // Redirect to original page
            return $response->withHeader('Location', $request->getUri()->getPath());
        }
    }
});