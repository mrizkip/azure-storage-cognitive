<?php

require_once 'vendor/autoload.php';
require_once "./random_string.php";

use MicrosoftAzure\Storage\Blob\BlobRestProxy;
use MicrosoftAzure\Storage\Common\Exceptions\ServiceException;
use MicrosoftAzure\Storage\Blob\Models\ListBlobsOptions;
use MicrosoftAzure\Storage\Blob\Models\CreateContainerOptions;
use MicrosoftAzure\Storage\Blob\Models\PublicAccessType;

$connectionString = "DefaultEndpointsProtocol=https;AccountName=mrizkipwebfinal;AccountKey=+YSkxzouh8c2sunSnyYVpxByWZCLQ/H/AIs+7cUJgefZU37v6H0cLCu3Y0pVx/2PY2rIC8S7iPv4kl3gxrMXpg==";

// Create blob client.
$blobClient = BlobRestProxy::createBlobService($connectionString);

$uploadOk = 0;

if(isset($_POST["submit"])) {
    // check extension file
    $imageFileType = strtolower(pathinfo($_FILES["fileToUpload"]["name"],PATHINFO_EXTENSION));
    if($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg"
        && $imageFileType != "gif" && $imageFileType != "bmp" ) {
        echo "Maaf, hanya file JPG, JPEG, PNG, GIF & BMP yang diizinkan.";
        $uploadOk = 0;
        exit();
    }

    // Check if image file is a actual image or fake image
    $check = getimagesize($_FILES["fileToUpload"]["tmp_name"]);
    if($check !== false) {
        echo "File is an image - " . $check["mime"] . ".";
        $uploadOk = 1;
    } else {
        echo "File yang dipilih bukan gambar.";
        $uploadOk = 0;
        exit();
    }

    // Check file size
    if ($_FILES["fileToUpload"]["size"] > 4000000) {
        echo "File tidak bisa lebih dari 4Mb.";
        $uploadOk = 0;
        exit();
    }
}

if ($uploadOk == 1) {
    $containerName = "mrizkipwebcontainer";

        try {
            // Getting local file so that we can upload it to Azure
            $myfile = fopen($_FILES["fileToUpload"]["tmp_name"], 'r') or die("Unable to open file!");
            fclose($myfile);
            
            $fileToUpload = $_FILES["fileToUpload"]["name"];
            # Upload file as a block blob
            echo "Uploading BlockBlob: ".PHP_EOL;
            echo $fileToUpload;
            echo "<br />";
            
            $content = fopen($_FILES["fileToUpload"]["tmp_name"], 'r');

            //Upload blob
            $blobClient->createBlockBlob($containerName, $fileToUpload, $content);

            // List blobs.
            $listBlobsOptions = new ListBlobsOptions();
            $listBlobsOptions->setPrefix($fileToUpload);

            echo "These are the blobs present in the container: ";

            $url = "";
            do{
                $result = $blobClient->listBlobs($containerName, $listBlobsOptions);
                foreach ($result->getBlobs() as $blob)
                {
                    echo $blob->getName().": ".$blob->getUrl()."<br />";
                    $url = $blob->getUrl();
                }
            
                $listBlobsOptions->setContinuationToken($result->getContinuationToken());
            } while($result->getContinuationToken());
            echo "<br />";

            // Get blob.
            echo "This is the content of the blob uploaded: ";
            $blob = $blobClient->getBlob($containerName, $fileToUpload);
            echo $url;
            // fpassthru($blob->getContentStream());
            echo "<br />";

            header("Location: result.php?id=".$url);
        }
        catch(ServiceException $e){
            // Handle exception based on error codes and messages.
            // Error codes and messages are here:
            // http://msdn.microsoft.com/library/azure/dd179439.aspx
            $code = $e->getCode();
            $error_message = $e->getMessage();
            echo $code.": ".$error_message."<br />";
        }
        catch(InvalidArgumentTypeException $e){
            // Handle exception based on error codes and messages.
            // Error codes and messages are here:
            // http://msdn.microsoft.com/library/azure/dd179439.aspx
            $code = $e->getCode();
            $error_message = $e->getMessage();
            echo $code.": ".$error_message."<br />";
        }
} 
?>